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
	if(!$id = $_GET['id']) die("Ugyldig oppslag: ID ikke angitt for leieobjekt");
	$this->hoveddata = "SELECT * FROM leieobjekter WHERE leieobjektnr = $id";
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
			{name: 'leieobjektnr', type: 'float'},
			{name: 'navn'},
			{name: 'gateadresse'},
			{name: 'etg'},
			{name: 'beskrivelse'},
			{name: 'areal', type: 'float'},
			{name: 'bad', type: 'bool'},
			{name: 'toalett'},
			{name: 'toalett_kategori'},
			{name: 'leieberegning'},
			{name: 'merknader'}
		]
	});
	
	var kortdata = datasett.getAt(0);
	var leieobjektnr = kortdata.get('leieobjektnr');
	var navn = kortdata.get('navn');
	var gateadresse = kortdata.get('gateadresse');
	var etg = Ext.util.Format.etasjerenderer(kortdata.get('etg'));
	var beskrivelse = kortdata.get('beskrivelse');
	var areal = kortdata.get('areal');
	var bad = kortdata.get('bad');
	var toalett = kortdata.get('toalett');
	var toalett_kategori = kortdata.get('toalett_kategori');
	var leieberegning = kortdata.get('leieberegning');
	var merknader = kortdata.get('merknader');

	var kort = new Ext.Panel({
		height: 400,
		html: [
			'Leieobjekt nr: ' + leieobjektnr + '<br />\n',
			'<b>' + navn + '</b><br />\n',
			gateadresse + '<br />\n',
			etg + '<br />\n',
			beskrivelse + '<br /><br />\n',
			'Areal: <b>' + areal + 'm&#178;</b><br /><br />\n',
			'Tilgang til bad: ' + bad + '<br />\n',
			'Toalett: ' + toalett + '<br />\n',
			'Klassifisering toalettforhold: ' + toalett_kategori + '<br />\n',
			'Spesifisering toalettforhold: ' + toalett + '<br /><br />\n',
			'Leieberegningsmetode: ' + leieberegning + '<br /><br />\n',
			'Andre merknader: ' + merknader + '<br /><br />\n',
			'<br /><br />'
			],
		title: 'Leieobjekt',
		width: 600
	});

	// Rutenettet rendres in i HTML-merket '<div id="adresseliste">':
    kort.render('leieobjektkort');

});
<?
}

function design() {
?>
<div id="leieobjektkort"></div>
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