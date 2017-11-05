<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'leiebasen';
public $ext_bibliotek = 'ext-4.2.1.883';
	

function __construct() {
	parent::__construct();
}

function skript() {
	if( isset( $_GET['returi'] ) && $_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	$tp = $this->mysqli->table_prefix;
?>
Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?=$this->http_host . "/" . $this->ext_bibliotek . "/examples/ux"?>');

Ext.require([
 	'Ext.data.*',
 	'Ext.form.field.*',
    'Ext.layout.container.Border',
 	'Ext.grid.*',
    'Ext.ux.RowExpander'
]);

Ext.onReady(function() {

    Ext.tip.QuickTipManager.init();
	Ext.form.Field.prototype.msgTarget = 'side';
	Ext.Loader.setConfig({enabled:true});
	
<?
	include_once("_menyskript.php");
?>

	Ext.define('Fbo', {
		extend: 'Ext.data.Model',
		idProperty: 'id',
		fields: [ // http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
			{name: 'id', type: 'int'},
			{name: 'leieforhold', type: 'int'},
			{name: 'leieforholdbeskrivelse', type: 'string'},
			{name: 'type', type: 'int'},
			{name: 'typebeskrivelse', type: 'string'},
			{name: 'varsel', type: 'boolean'},
			{name: 'registrert', type: 'date', dateFormat: 'Y-m-d H:i:s'}
		]
	});
	
	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
		clicksToEdit: 1
	});
	
	var rowEditing = Ext.create('Ext.grid.plugin.RowEditing', {
		autoCancel: false,
		listeners: {
			beforeedit: function (grid, e, eOpts) {
				return e.column.xtype !== 'actioncolumn';
			},
		},
	});


	var datasett = Ext.create('Ext.data.Store', {
		model: 'Fbo',
		pageSize: 200,
		remoteSort: true,
		proxy: {
			type: 'ajax',
			simpleSortMode: true,
			url: "index.php?oppslag=fboliste&oppdrag=hentdata",
			reader: {
				type: 'json',
				root: 'data',
				actionMethods: {
					read: 'POST'
				},
				totalProperty: 'totalRows'
			}
		},
		sorters: [{
			property: 'id',
			direction: 'DESC'
		}],
//        groupField: 'navn',
		autoLoad: true
	});
	


	var søkefelt = Ext.create('Ext.form.field.Text', {
		emptyText: 'Søk og klikk ↵',
		name: 'søkefelt',
		width: 200,
		listeners: {
			specialkey: function( felt, e, eOpts ) {
				datasett.getProxy().extraParams = {
					søkefelt: søkefelt.getValue()
				};
				datasett.load({
					params: {
						start: 0,
						limit: 300
					}
				});
			}
		}
	});


	var rutenett = Ext.create('Ext.grid.Panel', {
		autoScroll: true,
		layout: 'border',
//		frame: false,
// 		plugins: [{
// 			ptype: 'rowexpander',
// 			rowBodyTpl : ['{html}']
// 		}],
		store: datasett,
		title: 'Registrerte faste betalingsoppdrag (AvtaleGiro-avtaler)',
		listeners: {
			cellclick: function( panel, td, cellIndex, record, tr, rowIndex, e, eOpts ) {
				window.location = "index.php?oppslag=leieforholdkort&id=" + record.get('leieforhold');
			}
		},
		tbar: [
			søkefelt
		],
		columns: [
			{
				dataIndex: 'id',
				text: 'ID',
				hidden: true,
				width: 50,
				sortable: true
			},
			{
				dataIndex: 'leieforhold',
				text: 'Leieforhold',
				width: 200,
				renderer: function(value, metadata, record, rowIndex, colIndex, store){
					return record.get('leieforholdbeskrivelse');
				},
				flex: 1,
				sortable: true
			},
			{
				dataIndex: 'type',
				text: 'Betalingstype',
				width: 80,
				renderer: function(value, metadata, record, rowIndex, colIndex, store){
					return record.get('typebeskrivelse');
				},
				sortable: true
			},
			{
				dataIndex: 'varsel',
				text: 'Skal varsles',
				width: 90,
				align: 'center',
				renderer: Ext.util.Format.hake
			},
			{
				dataIndex: 'registrert',
				text: 'Registrert',
				width: 120,
				align: 'right',
				renderer: Ext.util.Format.dateRenderer('d.m.Y H:i:s')
			}
		],
		renderTo: 'panel',
		height: 500,
		width: 900,
		buttons: [{
			text: 'Tilbake',
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
			}
		}, {
			text: 'Vis trekkoppdrag',
			handler: function() {
				window.location = 'index.php?oppslag=fbo-kravliste';
			}
		}]
	});


});
<?
}


function design() {
?>
<div id="panel"></div>
<?
}

function hentData($data = "") {
	$tp = $this->mysqli->table_prefix;
	switch ($data) {
		default:
			$søkefelt = @$this->GET['søkefelt'];
			$filter = "fbo.leieforhold LIKE '%{$søkefelt}%' OR kontraktpersoner.leietaker LIKE '%{$søkefelt}%' OR CONCAT(personer.fornavn, ' ', personer.etternavn) LIKE '%{$søkefelt}%'";
		
			$resultat = $this->mysqli->arrayData(array(
				'distinct'	=> true,
				'returnQuery' => true,
				'fields'	=> "fbo.*",
				'source' => "{$tp}fbo AS fbo
							INNER JOIN {$tp}kontrakter AS kontrakter
							ON fbo.leieforhold = kontrakter.leieforhold
							LEFT JOIN {$tp}kontraktpersoner AS kontraktpersoner
							ON kontrakter.kontraktnr = kontraktpersoner.kontrakt
							LEFT JOIN {$tp}personer AS personer
							ON kontraktpersoner.person = personer.personid
							",
				'where'	=> $filter,
				'orderfields' => ($_GET['sort'] ? "fbo.{$this->GET['sort']} {$this->GET['dir']}, " : "") . "fbo.id DESC"
			));
			foreach($resultat->data as $fbo) {
				$leieforhold = $this->hent('Leieforhold', $fbo->leieforhold);
				$fbo->leieforholdbeskrivelse = "<a title=\"Åpne\" href=\"index.php?oppslag=leieforholdkort&id={$leieforhold}\">{$leieforhold}</a>: " . $leieforhold->hent('beskrivelse');
				$fbo->typebeskrivelse = '';
				if($fbo->type == 1) {
					$fbo->typebeskrivelse = "Husleie";
				}
				if($fbo->type == 2) {
					$fbo->typebeskrivelse = "Fellesstrøm";
				}
			}
			return json_encode($resultat);
	}
}

function taimotSkjema($skjema) {
	switch ($skjema) {
		default:
			echo json_encode($resultat);
			break;
	}
}

function oppgave($oppgave) {
	switch ($oppgave) {
		default:
			break;
	}
}

}
?>