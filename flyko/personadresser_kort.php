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
	if(!$id = $_GET['id']) die("Ugyldig oppslag: ID ikke angitt for adressekort");
	$this->hoveddata = "SELECT * FROM personer WHERE personid = $id";
}

function skript() {
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>


	var datasett = new Ext.data.JsonStore({
		data: [<?=$this->hentData();?>],
		fields: [
			{name: 'personid', type: 'float'},
			{name: 'fornavn'},
			{name: 'etternavn'},
			{name: 'er_org', type: 'bool'},
			{name: 'fødselsdato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'personnr'},
			{name: 'adresse1'},
			{name: 'adresse2'},
			{name: 'postnr'},
			{name: 'poststed'},
			{name: 'land'},
			{name: 'telefon'},
			{name: 'mobil'},
			{name: 'epost'}
		]
	});
	
	var row = datasett.getAt(0);
	var personid = row.get('personid');
	var fornavn = row.get('fornavn');
	var etternavn = row.get('etternavn');
	var er_org = row.get('er_org');
	var fødselsdato = Ext.util.Format.date(row.get('fødselsdato'), 'd.m.Y');
	var personnr = row.get('personnr');
	var adresse1 = row.get('adresse1');
	var adresse2 = row.get('adresse2');
	var postnr = row.get('postnr');
	var poststed = row.get('poststed');
	var land = row.get('land');
	var telefon = row.get('telefon');
	var mobil = row.get('mobil');
	var epost = row.get('epost');

	var kort = new Ext.Panel({
		frame: true,
		height: 500,
		html: [
			'Kort nr: ' + personid + '<br />\n',
			'Navn: <b>' + fornavn + ' ' + etternavn + '</b><br />\n',
			'Fødselsdato: <b>' + fødselsdato + '</b><br />\n',
			'<br />Adresse: <br />' + '<b>' + (adresse1 ? (adresse1 + '<br />') : '') + (adresse2 ? (adresse2 + '<br />') : '') + '</b>\n',
			'<b>' + postnr + ' ' + poststed + '</b><br />\n',
			(land != 'Norge' ? ('<b>' + land + '</b><br />\n') : ''),
			'<br />Telefon: <a href="callto:' + telefon + '">' + telefon + '</a><br />\n',
			'Mobil: <a href="callto:' + mobil + '">' + mobil + '</a><br />\n',
			'Epost: <a href="mailto:' + epost + '">' + epost + '</a><br /><br />\n'
			],
		title: 'Adressekort',
		width: 900,
		buttons: [{
			handler: function() {
				window.location = "index.php";
			},
			text: 'Tilbake'
		}]
	});

	// Rutenettet rendres in i HTML-merket '<div id="adresseliste">':
    kort.render('panel');

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
			$data = $this->arrayData($this->hoveddata);
			return json_encode($data['data'][0]);
	}
}

}
?>