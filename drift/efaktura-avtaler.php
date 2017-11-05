<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'eFaktura-avtaler';
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

	Ext.define('Avtale', {
		extend: 'Ext.data.Model',
		idProperty: 'id',
		fields: [ // http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
			{name: 'id', type: 'int'},
			{name: 'navn', type: 'string'},
			{name: 'adresse', type: 'string'},
			{name: 'leieforhold', type: 'int'},
			{name: 'leieforholdbeskrivelse', type: 'string'},
			{name: 'efakturareferanse', type: 'string'},
			{name: 'fbo', type: 'boolean'},
			{name: 'avtalestatus', type: 'string'},
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
		model: 'Avtale',
		pageSize: 300,
		remoteSort: true,
		proxy: {
			type: 'ajax',
			simpleSortMode: true,
			url: "index.php?oppslag=efaktura-avtaler&oppdrag=hentdata",
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
			property: 'registrert',
			direction: 'DESC'
		}],
//        groupField: 'navn',
		autoLoad: {start: 0, limit: 300}
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
		title: 'Registrerte eFaktura-avtaler',
		listeners: {
			celldblclick: function( panel, td, cellIndex, record, tr, rowIndex, e, eOpts ) {
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
				dataIndex: 'navn',
				text: 'Navn',
				width: 150,
				sortable: true
			},
			{
				dataIndex: 'adresse',
				text: 'Adresse',
				width: 170,
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
				dataIndex: 'avtalestatus',
				text: 'Status',
				width: 50,
				renderer: function(value, metadata, record, rowIndex, colIndex, store){
					if(value == 'A') {
						return "Aktiv";
					}
					else if(value == 'P') {
						return "<span title=\"Under behandling\">Beh.</span>";
					}
					else if(value == 'N') {
						return "Avvist";
					}
					else {
						return value;
					}
				},
				sortable: true
			},
			{
				dataIndex: 'fbo',
				text: 'AvtaleGiro',
				width: 70,
				align: 'center',
				renderer: Ext.util.Format.hake,
				sortable: true
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

		dockedItems: [{
			xtype: 'pagingtoolbar',
			store: datasett,
			dock: 'bottom',
			displayInfo: true
		}],

		buttons: [{
			text: 'Utsendte efaktura',
			handler: function() {
				window.location = 'index.php?oppslag=efakturaliste';
			}
		}, {
			text: 'Tilbake',
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
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

	default: {
		$sort		= @$_GET['sort'];
		$synkende	= @$_GET['dir'] == "DESC" ? true : false;
		$start		= (int)@$_GET['start'];
		$limit		= @$_GET['limit'];
	
		$søkefelt = @$this->GET['søkefelt'];
		$filter = "efaktura_avtaler.leieforhold LIKE '%{$søkefelt}%' OR kontraktpersoner.leietaker LIKE '%{$søkefelt}%' OR CONCAT(efaktura_avtaler.fornavn, ' ', efaktura_avtaler.etternavn) LIKE '%{$søkefelt}%' OR CONCAT(personer.fornavn, ' ', personer.etternavn) LIKE '%{$søkefelt}%'";
	
		$resultat = $this->mysqli->arrayData(array(
			'distinct'	=> true,
			'returnQuery' => true,
			'fields'	=> "efaktura_avtaler.*,
							CONCAT(efaktura_avtaler.fornavn, ' ', efaktura_avtaler.etternavn) AS navn",
			'source' => "{$tp}efaktura_avtaler AS efaktura_avtaler
						INNER JOIN {$tp}kontrakter AS kontrakter
						ON efaktura_avtaler.leieforhold = kontrakter.leieforhold
						LEFT JOIN {$tp}kontraktpersoner AS kontraktpersoner
						ON kontrakter.kontraktnr = kontraktpersoner.kontrakt
						LEFT JOIN {$tp}personer AS personer
						ON kontraktpersoner.person = personer.personid
						",
			'where'	=> $filter
		));
		foreach($resultat->data as $avtale) {
			$leieforhold = $this->hent('Leieforhold', $avtale->leieforhold);
			$avtale->leieforholdbeskrivelse = "<a title=\"Åpne\" href=\"index.php?oppslag=leieforholdkort&id={$leieforhold}\">{$leieforhold}</a>: " . $leieforhold->hent('beskrivelse');
			$avtale->adresse = $avtale->adresse1;
			if( $avtale->adresse2 ) {
				$avtale->adresse .= ", {$avtale->adresse2}";
			}
			$avtale->adresse .= ", {$avtale->postnr} {$avtale->poststed}";	
			$avtale->fbo = (bool)$leieforhold->hent('fbo');			
		}

		$resultat->data =  $this->sorterObjekter($resultat->data, $sort, $synkende);
		$resultat->totalRows = count($resultat->data);
		$resultat->data = array_slice(  $resultat->data, $start, $limit);
		return json_encode( $resultat );
	}
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