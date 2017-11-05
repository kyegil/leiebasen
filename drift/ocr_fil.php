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
	$this->hoveddata =	"SELECT * FROM ocr_filer WHERE filID = '{$_GET['id']}'";
	$ocr = $this->arrayData($this->hoveddata);
	header('Content-type: text/plain; charset=utf-8');
	header('Content-Disposition: attachment; filename="OCR-' . $ocr['data'][0]['forsendelsesnummer'] . '.txt"');
	die($ocr['data'][0]['OCR']);
}

function skript() {
}

function design() {
}

function taimotSkjema() {
}

function hentData($data = "") {
}

}
?>