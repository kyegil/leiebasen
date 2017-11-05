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
	$this->returi->reset();
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
	Ext.Loader.setConfig({
		enabled:true
	});
	
<?php include_once("_menyskript.php");?>


	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	Ext.define('Ext.ux.BildeTrigger', {
		extend: 'Ext.form.field.Trigger',
		alias: 'widget.bildetrigger',
		
		// override onTriggerClick
		onTriggerClick: function(record) {
			Ext.Msg.alert('Status', 'You clicked my trigger!');
		}
	});
	
	Ext.define('Bygning', {
		extend: 'Ext.data.Model',
		idProperty: 'id',
		fields: [
			{name: 'id', type: 'float'},
			{name: 'kode', type: 'string'},
			{name: 'navn', type: 'string'},
			{name: 'bilde', type: 'string'},
			{name: 'html'}
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
		model: 'Bygning',
		pageSize: 200,
		remoteSort: true,
		proxy: {
			type: 'ajax',
			simpleSortMode: true,
			url: "index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=hentdata",
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
			direction: 'ASC'
		}],
        groupField: 'navn',
		autoLoad: true
	});
	


	var rutenett = Ext.create('Ext.grid.Panel', {
		autoScroll: true,
		autoExpandColumn: 2,
		layout: 'border',
//		frame: false,
		plugins: [{
			ptype: 'rowexpander',
			rowBodyTpl : ['{html}']
		}],
		store: datasett,
		title: 'Bygninger',
		columns: [
			{
				dataIndex: 'kode',
				text: 'Kode',
				width: 120,
				sortable: true
			},
			{
				dataIndex: 'navn',
				text: 'Navn',
				width: 150,
				flex: 1,
				sortable: true
			},
			{
				dataIndex: 'bilde',
				text: 'Bilde',
				width: 420,
				renderer: function(value, metadata, record, rowIndex, colIndex, store){
					if(value) {
						return '<img style= "max-width: 400px; max-height: 150px; height: 100%; width: auto;" src="' + value + '" />';
					}
				},
				sortable: true
			},
			{
				dataIndex: 'id',
				width: 40,
				align: 'right',
				renderer: function(value, metadata, record, rowIndex, colIndex, store) {
					return '<a href="index.php?oppslag=bygningsskjema&id=' + value + '"><img src="../bilder/rediger.png" title="Klikk for å endre" /></a>';
				}
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
			text: 'Opprett ny bygning',
			handler: function() {
				window.location = 'index.php?oppslag=bygningsskjema&id=*';
			}
		}]
	});

//	rutenett.on('edit', lagreEndringer);


});
<?
}


function design() {
?>
<div id="panel"></div>
<?
}

function hentData($data = "") {
	switch ($data) {
		default:
			$resultat = $this->mysqli->arrayData(array(
				'returnQuery' => true,
				'source' => "bygninger",
				'orderfields' => ($_GET['sort'] ? "{$this->GET['sort']} {$this->GET['dir']}, " : "") . "kode, id"
			));
			foreach($resultat->data as $denne) {
				$denne->html = "";
				$leieobjekter = $this->mysqli->arrayData(array(
					'source' => "leieobjekter",
					'where' => "bygning = '{$denne->id}'",
					'orderfields' => "etg DESC, leieobjektnr"
				));
				foreach($leieobjekter->data as $leieobjekt) {
					$denne->html .= $this->leieobjekt($leieobjekt->leieobjektnr, true) . "<br />";
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
		case "slett":
			echo json_encode($resultat);
			break;
		default:
			break;
	}
}

}
?>