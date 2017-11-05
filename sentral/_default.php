<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/
If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

function __construct() {
	parent::__construct();
	$this->ext_bibliotek = 'ext-4.2.1.883';
	$this->kontrollrutiner();
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
<?
	include_once("_menyskript.php");
?>


	var kolonne1 = Ext.create('Ext.Panel', {
		autoScroll: true,
		loader: {
			url: 'index.php?oppdrag=hentdata&data=kolonne1',
			scripts: true,
			autoLoad: true
		},
		bodyStyle: 'padding: 2px',
		border: false,
		collapsible: false,
		collapsed: false,
		title: 'Gå til område:',
		width: 300,
		flex: 1
	});


	var kolonne2 = Ext.create('Ext.Panel', {
		autoScroll: true,
		autoLoad: '',
		bodyStyle: 'padding: 2px',
		border: false,
		collapsible: true,
		collapsed: false,
		title: ''
	});


	var hovedpanel = Ext.create('Ext.Container', {
		border: false,
		layout: {
			type: 'border',
			align: 'stretch'
		},
		flex: 1,
		renderTo: 'panel',
		defaults: {
			collapsible: true,
			split: true,
			bodyStyle: 'padding: 15px'
		},
		items: [kolonne1],
		title: '',
		height: 500,
		width: 900
	});

});
<?
}

function design() {
?>
<div id="panel"></div>
<?
}

function manipuler($data){
	switch ($data){
		default:
			echo json_encode($resultat);
			break;
	}
}


function hentData($data = "") {
	switch ($data) {
		case "internmeldinger":
			return json_encode($resultat);
			break;
		case "advarsler":
			echo $resultat;
			break;
		case "kolonne1":
?>
	<div><a class="område beboersider" href="../beboersider/">BEBOERSIDER</a></div>
	<div><a class="område egeninnsats" href="../egeninnsats/rekordoj/aldonu">EGENINNSATS</a></div>
	<div><a class="område drift" href="../drift/">DRIFT</a></div>
	<div><a class="område flyko" href="../flyko/">FLYKO</a></div>
<?
			break;
			
		case "kolonne2":
			break;
	}
}

}
?>