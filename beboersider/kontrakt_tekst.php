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

	if(!$id = $_GET['id']) header("Location: index.php?oppslag=kontrakt_liste");
	$this->hoveddata = "SELECT * FROM kontrakter LEFT JOIN leieobjekter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr WHERE kontraktnr = $id";
	$this->område['leieforhold'] = $this->leieforhold($id);
}

function skript() {
	if( isset( $_GET['returi'] ) && $_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.MessageBox.alert("OBS! Elektronisk avtaletekst", "Avtaleteksten på denne siden kan avvike fra den underskrevne teksten dersom avtalen ble tegnet før juli 2009, eller dersom det har blitt gjort endringer i leieobjektet e.l. etter at leieavtalen ble undertegnet.<br /><br />Dersom du er usikker på detaljer i leieavtalen og du ikke har den underskrevne versjonen tilgjengelig bør du kontakte <?=$this->valg['utleier']?> for en kopi av denne, og evt få oppdatert den elektroniske avtaleteksten.");

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
				window.location = "<?=$this->returi->get();?>";
			},
			text: 'Avbryt'
		}, {
			handler: function() {
				window.open("index.php?oppslag=kontrakt_utskrift&oppdrag=utskrift&id=<?=$_GET["id"]?>");
			},
			text: 'Skriv ut leieavtalen'
		}]
	});

	// Rutenettet rendres in i HTML-merket '<div id="kontrakt">':
	kontrakt.render('kontrakt');

});
<?
}

function design() {
?>


<div id="kontrakt"></div>
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