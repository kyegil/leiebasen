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

	if(!$id = $_GET['id']) header("Location: index.php");
	$this->hoveddata = "";
	$this->område['leieforhold'] = $this->leieforhold($id);
}

function skript() {
	if($_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	var panel = new Ext.Panel({
		autoLoad: 'index.php?oppslag=<?=$_GET["oppslag"]?>&oppdrag=hentdata&id=<?=$_GET["id"]?>',
        autoScroll: true,
        bodyStyle: 'padding:5px',
		title: '',
		frame: true,
		height: 500,
		plain: false,
		width: 900,
		buttons: [{
			handler: function() {
				window.location = "index.php";
			},
			text: 'Avbryt'
		}, {
			handler: function() {
				window.open('index.php?oppslag=<?=$_GET["oppslag"]?>&oppdrag=utskrift&id=<?=$_GET["id"]?>');
			},
			text: 'Skriv ut'
		}]
	});

	// Rutenettet rendres in i HTML-merket '<div id="kontrakt">':
	panel.render('panel');

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
			$datasett = $this->arrayData($this->hoveddata);
			echo "<table>\n";
			foreach($datasett['data'][0] as $felt=>$verdi){
				echo "<tr><td style=\"font-weight: bold;\">$felt:</td><td>$verdi</td></tr>";
			}
			echo "</table>\n";
			break;
	}
}

}
?>