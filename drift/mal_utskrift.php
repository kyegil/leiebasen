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
	$this->hoveddata =	"";
}

function skript() {
}

function design() {
?>
<?
}

function utskrift() {
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:ext="http://www.extjs.com" xml:lang="no" lang="no">

<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title></title>
	<link rel="stylesheet" type="text/css" href="/leiebase.css" />
	<link rel="stylesheet" type="text/css" href="/<?=$this->ext_bibliotek?>/resources/css/ext-all.css" />
	<link rel="stylesheet" type="text/css" href="/<?=$this->ext_bibliotek?>/resources/css/xtheme-slate.css" />
	<script language="JavaScript" type="text/javascript" src="/<?=$this->ext_bibliotek?>/adapter/ext/ext-base.js"></script>

	<script language="JavaScript" type="text/javascript" src="/<?=$this->ext_bibliotek?>/ext-all.js"></script>
	<script language="JavaScript" type="text/javascript" src="/<?=$this->ext_bibliotek?>/src/locale/ext-lang-no_NB.js"></script>
	<script language="JavaScript" type="text/javascript" src="/fellesfunksjoner.js"></script>
	<style type="text/css">
	td, th, p {font-size: x-small; border-color: #909090;}
	</style>
</head>

<body>
<h1></h1>
<p></p>
<script type="text/javascript">
	window.print();
</script>
</body>
</html>
<?
}


function taimotSkjema() {
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		default:
			$resultat = $this->arrayData($this->hoveddata);
			return json_encode($resultat);
	}
}

}
?>