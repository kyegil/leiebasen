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
	$this->fra = $this->mysqli->real_escape_string($_GET['fra']);
	$this->til = $this->mysqli->real_escape_string($_GET['til']);
	if(!$this->fra)
		$this->fra = date('Y-m-01');
	if(!$this->til)
		$this->til = date('Y-m-d', $this->leggtilIntervall(strtotime($this->fra), 'P1M')-86400);
	$this->hoveddata =	"SELECT leieobjekter.leieobjektnr AS leil, tabell1.* FROM (SELECT *\n"
	.	"FROM krav\n"
	.	"WHERE type = 'Husleie' AND kravdato >= '$this->fra' AND kravdato <= '$this->til') AS tabell1 RIGHT JOIN leieobjekter ON tabell1.leieobjekt = leieobjekter.leieobjektnr\n";

}

function skript() {
	$this->returi->reset();
	$this->returi->set();
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	// oppretter datasettet
	var datasett = new Ext.data.JsonStore({
		url: "index.php?oppdrag=hentdata&oppslag=oversikt_husleiekrav&fra=<?=$this->fra?>&til=<?=$this->til?>",
		fields: [
			{name: 'leil', type: 'float'},
			{name: 'leieobjektbeskrivelse'},
			{name: 'kontraktnr', type: 'float'},
			{name: 'kontraktpersoner'},
			{name: 'leieforhold', type: 'float'},
			{name: 'id', type: 'float'},
			{name: 'kravdato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'tekst'},
			{name: 'beløp', type: 'float'},
			{name: 'andel'},
			{name: 'termin'},
			{name: 'fom', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'tom', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'utestående', type: 'float'}
		],
		root: 'data'
    });
    datasett.load();
    
	var leil = {
		align: 'right',
		dataIndex: 'leil',
		header: 'Leil',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return "<a href=\"index.php?oppslag=leieobjekt_kort&id=" + value + "\">" + value + "</a>";
		},
		sortable: true,
		width: 50
	};

	var andel = {
		align: 'right',
		dataIndex: 'andel',
		header: 'Andel',
		sortable: true,
		width: 40
	};

	var leieobjektbeskrivelse = {
		dataIndex: 'leieobjektbeskrivelse',
		header: 'Beskrivelse',
		sortable: true,
		width: 200
	};

	var leieforhold = {
		dataIndex: 'leieforhold',
		header: 'Leieforhold (Leieavtale nr.)',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return record.data.kontraktnr ? (value + " (<a href=\"index.php?oppslag=leieforholdkort&id=" + record.data.kontraktnr + "\">" + record.data.kontraktnr + ") " + record.data.kontraktpersoner + "</a>") : null;
		},
		sortable: true,
		width: 200
	};

	var fom = {
		dataIndex: 'fom',
		header: 'Fra og med',
		hidden: true,
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		sortable: true,
		width: 70
	};

	var tom = {
		dataIndex: 'tom',
		header: 'Til og med',
		hidden: true,
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		sortable: true,
		width: 70
	};

	var beløp = {
		align: 'right',
		dataIndex: 'beløp',
		header: 'Beløp',
		renderer: Ext.util.Format.noMoney,
		sortable: true,
		width: 70
	};

	var id = {
		align: 'right',
		dataIndex: 'id',
		header: 'ID',
		hidden: true,
		sortable: true,
		width: 50
	};

	var tekst = {
		dataIndex: 'tekst',
		header: 'tekst',
		sortable: true,
		width: 50
	};

	var termin = {
		dataIndex: 'termin',
		header: 'Termin',
		sortable: true,
		width: 150
	};


	var rutenett = new Ext.grid.GridPanel({
		buttons: [{
			text: '<< 1 mnd. <<',
			handler: function() {
				window.location = "index.php?oppslag=<?=$_GET['oppslag']?>&fra=<?=date('Y-m-01', $this->leggtilIntervall(strtotime($this->fra), 'P-1M'));?>&til=<?=date('Y-m-d', ($this->leggtilIntervall(strtotime(date('Y-m-01', $this->leggtilIntervall(strtotime($this->fra), 'P-1M'))), 'P1M')-86400));?>";
			}
		}, {
			text: 'Tilbake',
			handler: function() {
				window.location = "index.php?oppslag=oversikt_innbetalinger";
			}
		}, {
			text: '>> 1 mnd. >>',
			handler: function() {
				window.location = "index.php?oppslag=<?=$_GET['oppslag']?>&fra=<?=date('Y-m-d', $this->leggtilIntervall(strtotime($this->fra), 'P1M'));?>&til=<?=date('Y-m-d', ($this->leggtilIntervall(strtotime($this->fra), 'P2M')-86400));?>";
			}
		}, {
			text: 'Utskriftsversjon',
			handler: function() {
				window.open("index.php?oppdrag=utskrift&oppslag=oversikt_husleiekrav&fra=<?=$this->fra?>&til=<?=$this->til?>");
			}
		}],
		store: datasett,
		columns: [
			leil,
			leieobjektbeskrivelse,
			andel,
			leieforhold,
			fom,
			tom,
			beløp,
			termin,
			id
		],
		autoExpandColumn: 3,
		stripeRows: true,
        height: 500,
        width: 900,
        title: "Terminkrav for husleie for perioden <?=date('d.m.Y', strtotime($this->fra))?> - <?=date('d.m.Y', strtotime($this->til))?> rangert etter leieobjekt"
    });

	// Rutenettet rendres in i HTML-merket '<div id="adresseliste">':
    rutenett.render('panel');

});
<?
}

function design() {
?>
<div id="panel"></div>
<?
}

function taimotSkjema() {
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		default:
			$resultat = $this->arrayData($this->hoveddata);
			foreach($resultat['data'] as $linje=>$detaljer){
				$resultat['data'][$linje]['leieforhold'] = $this->leieforhold($detaljer['kontraktnr']);
				$resultat['data'][$linje]['kontraktpersoner'] = $this->liste($this->kontraktpersoner($detaljer['kontraktnr']));
				$resultat['data'][$linje]['leieobjektbeskrivelse'] = $this->leieobjekt($detaljer['leil']);
			}
			return json_encode($resultat);
	}
}

function utskrift(){
	$sql = "SELECT leieobjektnr FROM leieobjekter ORDER BY leieobjektnr";
	$leieobjekter = $this->arrayData($sql);
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:ext="http://www.extjs.com" xml:lang="no" lang="no">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Terminkrav for husleie for perioden <?=date('d.m.Y', strtotime($this->fra))?> - <?=date('d.m.Y', strtotime($this->til))?> rangert etter leieobjekt</title>
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
<h1>Terminkrav for husleie for perioden <?=date('d.m.Y', strtotime($this->fra))?> - <?=date('d.m.Y', strtotime($this->til))?> rangert etter leieobjekt</h1>
<p></p>
<table style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="0">
<tbody>
<?
		foreach($leieobjekter['data'] as $leieobjekt){
			$sql =	"SELECT *\n"
				.	"FROM krav\n"
				.	"WHERE type = 'Husleie' AND kravdato >= '$this->fra' AND kravdato <= '$this->til' AND leieobjekt= '{$leieobjekt['leieobjektnr']}'\n";
			$utleie = $this->arrayData($sql);
			$bakgrunn = $bakgrunn ? "" : "background-color: #E0E0E0;";
?>
	<tr>
		<td colspan=5 style="border-top-style: solid; border-width: thin; font-weight: bold; <?=$bakgrunn?>"><?=$this->leieobjekt($leieobjekt['leieobjektnr'], true)?></td>
	</tr>
<?
			foreach($utleie['data'] as $krav){
?>
	<tr>
		<td style="width: 50px; <?=$bakgrunn?>"></td>
		<td style="width: 20px; <?=$bakgrunn?>"><?=(($this->evaluerAndel($krav['andel']) != 1) ? $krav['andel'] : "") . ""?></td>
		<td style="width: 350px; <?=$bakgrunn?>"><?=$krav['kontraktnr'] . " " . $this->liste($this->kontraktpersoner($krav['kontraktnr']))?></td>
		<td style="<?=$bakgrunn?>"><?=$krav['termin']?></td>
		<td style="width: 100px; text-align: right; font-weight: bold; <?=$bakgrunn?>"><?="kr. " . number_format($krav['beløp'], 2, ",", " ")?></td>
	</tr>
<?
			}
		}
?>
</tbody>
</table>
<script type="text/javascript">
	window.print();
</script>
</body>
</html>
<?
}

}
?>