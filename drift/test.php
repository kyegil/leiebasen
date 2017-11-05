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
	$sletteoppdrag = (object)array(
		'tjeneste'		=> 21,
		'oppdragstype'	=> 36,
		'oppdragsnr'	=> $this->netsOpprettOppdragsnummer(),
		'oppdragskonto'	=> $oppdragskonto,
		'transaksjoner'	=> array()
	);

	$giro = $this->hent( 'Giro', 25336 );

	if(
		!$giro->hentId() // Dersom giroen ikke lenger eksisterer
	or	($giro->hent('utestående') <= 0)
	or	$this->netsNesteForsendelse() <= $giro->fboOppdragsfrist()
	) {
		$sletteoppdrag->transaksjoner[] = (object)array(
			'forfallsdato'		=> date_create_from_format( 'Y-m-d', '2017-02-01' ),
			'beløp'				=> 4239,
			'kid'				=> '0087300253363',
			'mobilnr'			=> ''
		);
	}
	
	print_r( $this->netsNesteForsendelse() );

	print_r( $giro->fboOppdragsfrist() );

	print_r( $sletteoppdrag );

?>
<div id="panel"></div>
<?
}



function hentData($data = "") {
//
}



}

?>