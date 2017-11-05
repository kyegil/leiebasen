<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Utsendte eFakturaer';
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

	Ext.define('Efaktura', {
		extend: 'Ext.data.Model',
		idProperty: 'id',
		fields: [ // http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
			{name: 'id', type: 'int'},
			{name: 'giro', type: 'int'},
			{name: 'kid', type: 'string'},
			{name: 'forfall', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'leieforhold', type: 'int'},
			{name: 'leieforholdbeskrivelse', type: 'string'},
			{name: 'forsendelsesdato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'forsendelse', type: 'string'},
			{name: 'oppdrag', type: 'string'},
			{name: 'kvittert_dato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'kvitteringsforsendelse', type: 'string'},
			{name: 'status', type: 'string'},
			{name: 'html', type: 'string'}
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
		model: 'Efaktura',
		pageSize: 300,
		remoteSort: true,
		proxy: {
			type: 'ajax',
			simpleSortMode: true,
			url: "index.php?oppslag=efakturaliste&oppdrag=hentdata",
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
        groupField: 'forsendelse',
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
		frame: false,
		features: [{
			ftype: 'grouping'
		}],
		plugins: [{
			ptype: 'rowexpander',
			rowBodyTpl : ['{html}']
		}],
		store: datasett,
		title: 'eFakturaer',
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
				dataIndex: 'forsendelsesdato',
				text: 'Dato',
				width: 70,
				align: 'right',
				renderer: Ext.util.Format.dateRenderer('d.m.Y')
			},
			{
				dataIndex: 'giro',
				text: 'Giro',
				width: 60,
				align: 'right',
				sortable: true
			},
			{
				dataIndex: 'kid',
				text: 'KID',
				width: 100,
				align: 'right',
				sortable: true
			},
			{
				dataIndex: 'forfall',
				text: 'Forfall',
				width: 70,
				align: 'right',
				renderer: Ext.util.Format.dateRenderer('d.m.Y'),
				sortable: false
			},
			{
				dataIndex: 'leieforhold',
				text: 'Leieforhold',
				width: 60,
				flex: 1,
				renderer: function(value, metadata, record, rowIndex, colIndex, store){
					return record.get('leieforholdbeskrivelse');
				},
				sortable: true
			},
			{
				dataIndex: 'oppdrag',
				text: 'Oppdrag',
				width: 60,
				sortable: true
			},
			{
				dataIndex: 'forsendelse',
				text: 'Forsendelse',
				width: 70,
				sortable: true
			},
			{
				dataIndex: 'kvittert_dato',
				text: 'Kvittert',
				width: 70,
				align: 'right',
				renderer: Ext.util.Format.dateRenderer('d.m.Y')
			},
			{
				dataIndex: 'kvitteringsforsendelse',
				text: 'Kvit.fors.',
				width: 60,
				sortable: true
			},
			{
				dataIndex: 'status',
				text: 'Status',
				width: 50,
				sortable: true
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
			text: 'Efaktura-avtaler',
			handler: function() {
				window.location = 'index.php?oppslag=efaktura-avtaler';
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
		default:
			$søkefelt = @$this->GET['søkefelt'];
			$filter = "efakturaer.forsendelse LIKE '%{$søkefelt}%' OR efakturaer.giro LIKE '%{$søkefelt}%' OR efakturaer.oppdrag LIKE '%{$søkefelt}%' OR giroer.kid LIKE '%{$søkefelt}%' OR giroer.leieforhold LIKE '%{$søkefelt}%' OR kontraktpersoner.leietaker LIKE '%{$søkefelt}%' OR CONCAT(personer.fornavn, ' ', personer.etternavn) LIKE '%{$søkefelt}%'";
		
			$orderfields = array();
			switch( @$_GET['sort'] ) {
				
			case "kid":
			case "leieforhold":
				$orderfields[] = "giroer.{$this->GET['sort']} {$this->GET['dir']}";
				break;
			
			default:
				$orderfields[] = "efakturaer.{$this->GET['sort']} {$this->GET['dir']}";
				break;
			}
			$orderfields[] = "efakturaer.id DESC";
			
			$limit = @$_GET['limit'];
		
			$resultat = $this->mysqli->arrayData(array(
				'distinct'	=> true,
				'returnQuery' => true,
				'fields'	=> "efakturaer.*, giroer.kid, giroer.leieforhold",
				'source' => "{$tp}efakturaer AS efakturaer
							INNER JOIN {$tp}giroer AS giroer ON efakturaer.giro = giroer.gironr
							INNER JOIN {$tp}kontrakter AS kontrakter
							ON giroer.leieforhold = kontrakter.leieforhold
							LEFT JOIN {$tp}kontraktpersoner AS kontraktpersoner
							ON kontrakter.kontraktnr = kontraktpersoner.kontrakt
							LEFT JOIN {$tp}personer AS personer
							ON kontraktpersoner.person = personer.personid
							",
				'where'	=> $filter,
				'limit' => $limit,
				'orderfields' => implode(',', $orderfields)
			));
			foreach($resultat->data as $efaktura) {
				$efaktura->html = array();
				$leieforhold = $this->hent('Leieforhold', $efaktura->leieforhold);
				$giro = $this->hent('Giro', $efaktura->giro);
				if( $giro->hentId() ) {
					$kravsett = $giro->hent('krav');
				
					foreach ($kravsett as $krav) {
						$efaktura->html[] = "<a title=\"Åpne\" href=\"index.php?oppslag=krav_kort&id={$krav}\">{$krav->hent('tekst')}</a>";
					}
					$efaktura->html[] = "Beløp: {$giro->hent('beløp')}";
				}
				
				$efaktura->forfall = $giro->hent('forfall') ? $giro->hent('forfall')->format('Y-m-d') : "";
				$efaktura->leieforholdbeskrivelse = "<a title=\"Gå til leieforholdkortet\" href=\"index.php?oppslag=leieforholdkort&id={$leieforhold}\">{$leieforhold}</a>: {$leieforhold->hent('beskrivelse')}";
				$efaktura->html = implode('<br>', $efaktura->html);
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