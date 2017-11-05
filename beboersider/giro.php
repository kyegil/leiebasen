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

	$sql =	"SELECT kontraktnr FROM krav WHERE gironr = '{$_GET['gironr']}'";
	$kontraktnr = $this->arrayData($sql);
	$kontraktnr = $kontraktnr['data'][0]['kontraktnr'];
	$this->område['leieforhold'] = $this->leieforhold($kontraktnr);
}



function skript() {}



function design() {
}



function lagPDF() {
	$giro = $this->hent('Giro', $_GET['gironr']);
	$giro->nedlastPdf();
}

}
?>