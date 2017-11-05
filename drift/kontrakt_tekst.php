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
	if(!$id = $_GET['id']) header("Location: index.php?oppslag=leieforhold_liste");
	$this->hoveddata = "SELECT * FROM kontrakter LEFT JOIN leieobjekter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr WHERE kontraktnr = $id";
}

function skript() {
	if(@$_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	var kontrakt = new Ext.Panel({
		autoLoad: 'index.php?oppslag=kontrakt_tekst&oppdrag=hentdata&data=avtale&id=<?=$_GET["id"]?>',
        autoScroll: true,
        bodyStyle:'padding:5px',
		title: 'Fullstendig leieavtale',
		frame: true,
		height: 500,
		plain:false,
		width: 900,
		buttons: [{
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
			},
			text: 'Ferdig'
		}, {
			handler: function() {
				window.location = "index.php?oppslag=kontrakt_tekstendring&id=<?=$_GET['id']?>";
			},
			text: 'Rediger avtaleteksten'
		}, {
			handler: function() {
				window.open("index.php?oppslag=kontrakt_utskrift&oppdrag=utskrift&id=<?=$_GET["id"]?>");
			},
			text: 'Skriv ut leieavtalen'
		}]
	});

	// Rutenettet rendres in i HTML-merket '<div id="kontrakt">':
	kontrakt.render('panel');

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
			$leieforhold = $this->leieforhold((int)@$_GET['id'], true);
			return $leieforhold->gjengiAvtaletekst( true, (int)@$_GET['id'] );
			break;
	}
}

}
?>