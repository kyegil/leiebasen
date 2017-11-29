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
	$this->hoveddata = "SELECT krav.*\n"
		.	"FROM krav\n"
		.	"WHERE kravdato >= '$this->fra'\n"
		.	"AND kravdato <= '$this->til'\n"
		.	($_GET['kravtype'] ? ("AND type = '" . $this->mysqli->real_escape_string($_GET['kravtype']) . "'\n") : "")
		.	"ORDER BY kravdato, kontraktnr, id";
}

function skript() {
	if($_GET['returi'] == "default") $this->returi->reset();
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
		url: "index.php?oppdrag=hentdata&oppslag=<?=$_GET['oppslag'];?>&fra=<?=$this->fra;?>&til=<?=$this->til;?><?=$_GET['kravtype'] ? ("&kravtype=" . $_GET['kravtype']) : "";?>",
		fields: [
			{name: 'id', type: 'float'},
			{name: 'kravdato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'opprettet', type: 'date', dateFormat: 'Y-m-d H:i:s'},
			{name: 'oppretter'},
			{name: 'kontraktnr', type: 'float'},
			{name: 'kontraktpersoner'},
			{name: 'type'},
			{name: 'termin'},
			{name: 'tekst'},
			{name: 'gironr', type: 'float'},
			{name: 'beløp', type: 'float'},
			{name: 'utestående', type: 'float'}
		],
		root: 'data'
    });
    datasett.load();

	var id = {
		align: 'right',
		dataIndex: 'id',
		header: 'ID',
		hidden: true,
		sortable: true,
		width: 50
	};

	var dato = {
		dataIndex: 'kravdato',
		header: 'Dato',
		hidden: false,
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		sortable: true,
		width: 70
	};

	var kontraktnr = {
		dataIndex: 'kontraktnr',
		header: 'Leieavtale',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return "<a href=\"index.php?oppslag=leieforholdkort&id=" + value + "\">" + value + "</a> " + record.data.kontraktpersoner;
		},
		sortable: true,
		width: 160
	};

	var type = {
		dataIndex: 'type',
		header: 'Kravtype',
		sortable: true,
		width: 50
	};

	var termin = {
		dataIndex: 'termin',
		header: 'Termin',
		sortable: true,
		width: 130
	};

	var tekst = {
		dataIndex: 'tekst',
		header: 'Tekst',
		sortable: true,
		width: 50
	};

	var giro = {
		align: 'right',
		dataIndex: 'gironr',
		header: 'Giro',
		renderer: function(v){
			if(!v) return "";
			return "<a href=\"index.php?oppslag=giro&oppdrag=lagpdf&gironr=" + v + "\">" + v + "</a>";
		},
		sortable: true,
		width: 50
	};

	var beløp = {
		align: 'right',
		dataIndex: 'beløp',
		header: 'Beløp',
		renderer: Ext.util.Format.noMoney,
		sortable: true,
		width: 70
	};

	var utestående = {
		align: 'right',
		dataIndex: 'utestående',
		header: 'Utestående',
		renderer: Ext.util.Format.noMoney,
		sortable: true,
		width: 80
	};

	var opprettet = {
		dataIndex: 'opprettet',
		header: 'Opprettet',
		hidden: true,
		renderer: Ext.util.Format.dateRenderer('d.m.Y H:i'),
		sortable: true,
		width: 110
	};

	var oppretter = {
		dataIndex: 'oppretter',
		header: 'Registrert av',
		hidden: true,
		sortable: true,
		width: 120
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
				window.open("index.php?oppdrag=utskrift&oppslag=oversikt_krav<?=$_GET['kravtype'] ? ("&kravtype=" . $_GET['kravtype']) : "";?>&fra=<?=$this->fra?>&til=<?=$this->til?>");
			}
		}],
		store: datasett,
		columns: [
			id,
			dato,
			kontraktnr,
			type,
			termin,
			tekst,
			giro,
			beløp,
			utestående,
			opprettet,
			oppretter
		],
		autoExpandColumn: 5,
		stripeRows: true,
        height: 500,
        width: 900,
        title: "<?=($_GET['kravtype'] ? $_GET['kravtype'] : 'Alle ') . 'krav ' . date('d.m.Y', strtotime($this->fra)) . ' - ' . date('d.m.Y', strtotime($this->til))?>"
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
				$resultat['data'][$linje]['kontraktpersoner'] = $this->liste($this->kontraktpersoner($detaljer['kontraktnr']));
			}
			return json_encode($resultat);
	}
}

function utskrift(){
	$kravsett = $this->arrayData($this->hoveddata);
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:ext="http://www.extjs.com" xml:lang="no" lang="no">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?=($_GET['kravtype'] ? $_GET['kravtype'] : 'Alle ') . 'krav ' . date('d.m.Y', strtotime($this->fra)) . ' - ' . date('d.m.Y', strtotime($this->til))?></title>
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
<h1><?=($_GET['kravtype'] ? $_GET['kravtype'] : 'Alle ') . 'krav ' . date('d.m.Y', strtotime($this->fra)) . ' - ' . date('d.m.Y', strtotime($this->til))?></h1>
<p></p>
<table style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="0">
<tbody>
<?
		foreach($kravsett['data'] as $krav){
			$bakgrunn = $bakgrunn ? "" : "background-color: #E0E0E0;";
?>
	<tr style="vertical-align:top;">
		<td style="width: 60px; text-align: right;  border-top-style: solid; border-width: thin; <?=$bakgrunn?>"><?=date('d.m.Y', strtotime($krav['kravdato']))?></td>
		<td style="width: 40px; text-align: right; border-top-style: solid; border-width: thin; <?=$bakgrunn?>"><?=$krav['kontraktnr']?></td>
		<td style="width: 200px; text-indent: 2px; border-top-style: solid; border-width: thin; <?=$bakgrunn?>"><?=$this->liste($this->kontraktpersoner($krav['kontraktnr']))?></td>
		<td style="border-top-style: solid; border-width: thin; <?=$bakgrunn?>"><?=$krav['type']?></td>
		<td style="border-top-style: solid; border-width: thin; <?=$bakgrunn?>"><?=$krav['termin']?></td>
		<td style="width: 40px; text-align: right; border-top-style: solid; border-width: thin; <?=$bakgrunn?>"><?=$krav['id']?></td>
		<td style="text-indent: 2px; border-top-style: solid; border-width: thin; <?=$bakgrunn?>"><?=$krav['tekst']?></td>
		<td style="width: 70px; text-align: right; border-top-style: solid; border-width: thin; font-weight: bold; <?=$bakgrunn?>">kr. <?=number_format($krav['beløp'], 2, ",", " ")?></td>
	</tr>
<?
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