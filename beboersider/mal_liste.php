<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

function __construct() {
	parent::__construct();
	$this->ext_bibliotek = 'ext-3.4.0';

	$this->hoveddata = "";
}

function skript() {
	if($_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
?>

Ext.onReady(function() {
	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';
<?
	include_once("_menyskript.php");
?>

	// oppretter datasettet
	var datasett = new Ext.data.JsonStore({
		url:'index.php?oppdrag=hentdata&oppslag=<?=$_GET['oppslag'];?>&oppdrag=hentdata',
		fields: [
<?
	$a = $this->arrayData($this->hoveddata);
	if(!isset($a['data'][0])) $a['data'][0] = array();
	$b = array();
	foreach($a['data'][0] as $kolonne=>$verdi) {
		$b[] = "			{name: '$kolonne'}";		
	}
	echo implode(",\n", $b);
?>

		],
		root: 'data'
    });
    datasett.load();

<?
	$b = array();
	foreach($a['data'][0] as $kolonne=>$verdi) {
		echo "	var $kolonne = {
		dataIndex: '$kolonne',
		header: '$kolonne',
		sortable: true,
		width: 50
	};

";		
	}
?>

	var rutenett = new Ext.grid.GridPanel({
		store: datasett,
		columns: [
<?
	$b = array();
	foreach($a['data'][0] as $kolonne=>$verdi) {
		$b[] = "			$kolonne";		
	}
	echo implode(",\n", $b);
?>
		
		],
		stripeRows: true,
        height: 500,
        width: 900,
        title: ''
    });

	// Rutenettet rendres in i HTML-merket '<div id="adresseliste">':
    rutenett.render('rutenett');

});
<?
}

function design() {
?>


<div id="rutenett"></div>
<?
}

function taimotSkjema() {
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		default:
			$resultat = $this->arrayData($this->hoveddata);
			return json_encode($resultat);
	}
}

}
?>