<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

require_once("../klassedefinisjoner/DatabaseObjekt.class.php");
require_once("../klassedefinisjoner/Giro.class.php");
require_once("../klassedefinisjoner/Purring.class.php");

class oppsett extends leiebase {

function __construct() {
	parent::__construct();
	$this->ext_bibliotek = 'ext-4.2.1.883';
		$this->mal = '_HTML.php';
}



function skript() {
?>

Ext.onReady(function() {
<?
//	include_once("_menyskript.php");
?>


	var hovedpanel = Ext.create('Ext.panel.Panel', {
		renderTo: 'panel',
		frame: true,
		items: [{xtype: 'displayfield', value: 'content'}],
		title: 'title',
		height: 500
	});
   
});
<?
}




function design() {
echo $this->mysqli->where([
	'table.fornavn >=' => 'Espen',
	'table.etternavn' => 'Askeladd',
	'or'	=> [
		'table.fornavn' => null,
		'table.org' => true,
		'and'	=> [
			'table.antall >=' => 3,
			0	=>	'COUNT(table.column) = settings.count',
			"CONCAT(table.login, '@', $$) = table.email"	=> ['domain.com'],
			'personer.fornavn'	=> ['Per', 'Pål', NULL]
		]
	]
]);
?>
<div id="panel"></div>
<?
}



function hentData($data = "") {
//
}



}

?>