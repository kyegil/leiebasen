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
}

function skript() {
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	var dato = new Ext.form.DateField({
		fieldLabel: 'Dato',
		name: 'dato',
		format: 'Y-m-d',
		altFormats: "j.n.y|j.n.Y|j/n/y|j/n/Y|j-n-y|j-n-Y|j. M y|j. M -y|j. M. Y|j. F -y|j. F y|j. F Y|j/n|j-n|dm|dmy|dmY|d|Y-m-d",
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		value: '<?=date('Y')-1;?>-12-31',
		width: 200
	});
	
	var skjema = new Ext.form.FormPanel({
		buttonAlign: 'center',
		frame: true,
		labelAlign: 'right',
		labelWidth: 200,
		title: 'Avstemmingsrapport for utestående husleier',
		height: 500,
		width: 900,
		standardSubmit: true,
		waitMsgTarget: true,
		waitMsg: 'Vent litt..',
		items: [{
			html: "Avstemmingsrapport for utestående husleier"
		}, dato]
	});

	var sendknapp = skjema.addButton({
		text: 'Lag rapport',
		disabled: false,
		handler: function(){
			window.open("index.php?oppslag=rapport_avstemming&oppdrag=utskrift&dato=" + dato.value);
		}
	});

	skjema.render('panel');

});
<?
}

function design() {
?>
<div id="panel"></div>
<?
}

function utskrift() {
	$dato = $_GET['dato'];
	$sql =	"SELECT krav.id, krav.kontraktnr, krav.beløp, krav.fom, krav.tom, krav.andel, krav.termin, krav.kravdato, krav.leieobjekt, krav.beløp-IFNULL(innbetalinger.beløp, 0) AS utestående\n"
	.	"FROM krav LEFT JOIN (SELECT krav, SUM(innbetalinger.beløp) AS beløp FROM innbetalinger WHERE dato <= '$dato' GROUP BY krav) AS innbetalinger\n"
	.	"ON krav.id = innbetalinger.krav\n"
	.	"WHERE krav.kravdato <= '$dato'\n"
	.	"AND krav.type = 'Husleie'\n"
	.	"HAVING utestående > 0\n"
	.	"ORDER BY kravdato, leieobjekt, kontraktnr\n";

	$resultat = array();
	$sett = $this->arrayData( $sql );
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:ext="http://www.extjs.com" xml:lang="no" lang="no">

<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Rapport over utestående leie pr. <?=date('d.m.Y', strtotime($_GET['dato']));?></title>
	<link rel="stylesheet" type="text/css" href="/leiebase.css" />
	<link rel="stylesheet" type="text/css" href="/<?=$this->ext_bibliotek?>/resources/css/ext-all.css" />
	<link rel="stylesheet" type="text/css" href="/<?=$this->ext_bibliotek?>/resources/css/xtheme-slate.css" />
	<script language="JavaScript" type="text/javascript" src="/<?=$this->ext_bibliotek?>/adapter/ext/ext-base.js"></script>

	<script language="JavaScript" type="text/javascript" src="/<?=$this->ext_bibliotek?>/ext-all.js"></script>
	<script language="JavaScript" type="text/javascript" src="/<?=$this->ext_bibliotek?>/src/locale/ext-lang-no_NB.js"></script>
	<script language="JavaScript" type="text/javascript" src="/fellesfunksjoner.js"></script>
	<style type="text/css">
	td, th {font-size: small; border-color: #909090; text-align:right; padding-left: 10px; padding-right: 10px;}
	p {font-size: small; border-color: #909090;}
	</style>
</head>

<body>
<h1>Rapport over utestående leie pr. <?=date('d.m.Y', strtotime($_GET['dato']));?></h1>
<p></p>
<?
	foreach($sett['data'] as $linje)
		$resultat[$linje['kravdato']][] = $linje;
	
	foreach($resultat as $gruppe){
		echo "<p style=\"font-weight: bold;\">{$gruppe[0]['termin']}</p>";
		echo "<table><tbody>";
		echo "<tr><th>Leieavtale</th><th>Leieobjekt</th><th>Terminbeløp</th><th>Utestående</th></tr>";
		$sum = 0;
		
		foreach($gruppe as $krav){
			echo "<tr><td>{$krav['kontraktnr']}</td><td>{$krav['leieobjekt']}</td><td>" . number_format($krav['beløp'], 2, ", ", " ") . "</td><td>" . number_format($krav['utestående'], 2, ", ", " ") . "</td></tr>";
			$sum += $krav['utestående'];
		}
		echo "<tr><td></td><td></td><td></td><td style=\"font-weight: bold;\">" . number_format($sum, 2, ", ", " ") . "</td></tr>";
		echo "</tbody></table>";
	}

?>
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
			$dato = $_GET['dato'];
			$sql =	"SELECT krav.id, krav.kontraktnr, krav.beløp, krav.fom, krav.tom, krav.andel, krav.termin, krav.kravdato, krav.leieobjekt, krav.beløp-IFNULL(innbetalinger.beløp, 0) AS utestående\n"
			.	"FROM krav LEFT JOIN (SELECT krav, SUM(innbetalinger.beløp) AS beløp FROM innbetalinger WHERE dato <= '$dato' GROUP BY krav) AS innbetalinger\n"
			.	"ON krav.id = innbetalinger.krav\n"
			.	"WHERE krav.kravdato <= '$dato'\n"
			.	"AND krav.type = 'Husleie'\n"
			.	"HAVING utestående > 0\n"
			.	"ORDER BY kravdato, leieobjekt, kontraktnr\n";

			$resultat = $this->arrayData( $sql );

			return json_encode($resultat);
	}
}

}
?>