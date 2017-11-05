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

	if(!$id = $_GET['id']) die("Ugyldig oppslag: ID ikke angitt for kontrakt");
	$this->hoveddata = "SELECT * FROM kontrakter LEFT JOIN leieobjekter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr WHERE kontraktnr = $id";
	$this->område['leieforhold'] = $this->leieforhold($id);
}

function skript() {
?>
<?
}

function design() {
}

function utskrift() {
	$leieforhold = $this->leieforhold((int)@$_GET['id'], true);
	echo $leieforhold->gjengiAvtaletekst( true, (int)@$_GET['id'] );
?>
<script type="text/javascript">
	window.print();
</script>
<?php
}

function taimotSkjema() {
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		default:
			return json_encode($this->arrayData($this->hoveddata));
	}
}

}
?>