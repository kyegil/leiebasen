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
	setlocale(LC_ALL, "nb_NO");

	$this->fra = $this->mysqli->real_escape_string($_GET['fra']);
	$this->til = $this->mysqli->real_escape_string($_GET['til']);
	if(!$this->fra)
		$this->fra = date('Y-01-01');
	if(!$this->til)
		$this->til = date('Y-m-d', $this->leggtilIntervall((strtotime($this->fra)-86400), 'P1Y'));
}

function skript() {
	$this->fra = $this->mysqli->real_escape_string($_GET['fra']);
	$this->til = $this->mysqli->real_escape_string($_GET['til']);
	if(!$this->fra)
		$this->fra = date('Y-01-01');
	if(!$this->til)
		$this->til = date('Y-m-d', $this->leggtilIntervall((strtotime($this->fra)-86400), 'P1Y'));
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	var panel = new Ext.Panel({
		autoLoad: 'index.php?oppslag=oversikt_utleie_krysstabell&oppdrag=hentdata&fra=<?=$this->fra?>&til=<?=$this->til?>',
		title: '',
        height: 500,
        width: 900,
        boxMaxWidth: 900,
        autoScroll: true,
		buttons: [{
			handler: function() {
				window.location = "index.php?oppslag=<?=$_GET['oppslag']?>&fra=<?=date('Y-m-d', $this->leggtilIntervall(strtotime($this->fra), 'P-6M'));?>&til=<?=date('Y-m-d', ($this->leggtilIntervall(strtotime($this->fra), 'P6M')-86400));?>";
			},
			text: '<< 6 mnd. <<'
		}, {
			handler: function() {
				window.location = "index.php?oppslag=oversikt_utleie_krysstabell&fra=<?=date('Y-m-d', $this->leggtilIntervall(strtotime($this->fra), 'P6M'));?>&til=<?=date('Y-m-d', ($this->leggtilIntervall(strtotime($this->fra), 'P18M')-86400));?>";
			},
			text: '>> 6 mnd. >>'
		}]
	});

	// Rutenettet rendres in i HTML-merket '<div id="adresseliste">':
    panel.render('panel');

});
<?
}

function design() {
?>
<div id="panel"></div>
<?
}

function utskrift() {
	$this->fra = $this->mysqli->real_escape_string($_GET['fra']);
	$this->til = $this->mysqli->real_escape_string($_GET['til']);
	if(!$this->fra)
		$this->fra = date('Y-01-01');
	if(!$this->til)
		$this->til = date('Y-m-d', $this->leggtilIntervall((strtotime($this->fra)-86400), 'P1Y'));

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

</head>

<body>
<h1>Utleietabell <?=date('d.m.Y', strtotime($this->fra))?> - <?=date('d.m.Y', strtotime($this->til))?></h1>
<table style="table-layout: fixed; border-collapse: collapse;">
	<tr style="height: 10px;">
		<th style="font-size: x-small; border-color: #909090; text-align: center; border-right: 1px solid; border-left: none; width: 35px">Leil</th>
		<th style="font-size: x-small; border-color: #909090; text-align: center; border-right: 1px solid; border-left: none; width: 114px">Kontrakt</th>
<?
		setlocale(LC_ALL, "nb_NO");
		$tidspkt = strtotime($this->fra);
		while($tidspkt < strtotime($this->til)){
			$månedsskifte = strtotime(date('Y-m-01', $this->leggtilIntervall($tidspkt, "P1M")));
?>
		<th style="font-size: x-small; border-color: #909090; text-align: center; border-left: 1px solid; border-right: 1px solid; width: <?=round(($månedsskifte - $tidspkt)/43200)-1?>px"><?=strftime('%b', $tidspkt)?></th>
<?
			$tidspkt = $månedsskifte;
		}
?>
	</tr>
</table>
<?
		$sql =	"SELECT leieobjektnr FROM leieobjekter\n";
		$leieobjekter = $this->arrayData($sql);
		foreach($leieobjekter['data'] as $leieobjekt){
			$leieobjektrekke[$leieobjekt['leieobjektnr']] = array();
		}
		
		foreach($leieobjektrekke as $leieobjekt=>$a){
			$sql =	"SELECT fom, tom, andel, kontraktnr, beløp\n"
				.	"FROM krav\n"
				.	"WHERE type = 'Husleie'\n"
				.	"AND fom <= '$this->til'\n"
				.	"AND tom >= '$this->fra'\n"
				.	"AND leieobjekt = '$leieobjekt'\n"
				.	"ORDER BY fom, tom";
			$kravrekke = $this->arrayData($sql);
			foreach($kravrekke['data'] as $krav){
				$leieobjektrekke[$leieobjekt][$this->leieforhold($krav['kontraktnr'])][$krav['kontraktnr']][] = $krav;
			}
		}
	
		foreach($leieobjektrekke as $leieobjekt=>$leieforholdsett){
			$skillelinje1 = "2px solid black";
			$skillelinje2 = "2px solid black";
			$skille = 1;
			foreach($leieforholdsett as $leieforhold=>$kontraktsett){
				foreach($kontraktsett as $kontrakt=>$kravsett){
?>
<table style="table-layout: fixed; border-collapse: collapse;">
	<tr style="height: 10px;">
		<td style="font-size: x-small; border-color: #909090; font-weight: bold; text-align: right; border-left: none; border-right: none; border-top: <?=$skillelinje2?>; width: 30px"><?=$skille ? $leieobjekt : ""?></td>
		<td style="font-size: x-small; border-color: #909090; text-align: center; border-left: none; border-top: <?=$skillelinje2?>; width: 120px"><?=($this->evaluerAndel($kravsett[0]['andel']) < 1 ? "{$kravsett[0]['andel']}: " : "") . "$kontrakt " . $this->liste($this->kontraktpersoner($kontrakt));?></td>
<?
					if($kravsett[0]['fom'] > $this->fra){
?>
		<td style="font-size: x-small; border-color: #909090; background-color: grey; border-left: none; border-top: <?=$skillelinje1?>; width: <?=round((strtotime($kravsett[0]['fom']) - strtotime($this->fra)) / 43200)?>px"></td>
<?
					}
					foreach($kravsett as $krav){
?>
		<td style="font-size: x-small; border-color: #909090; text-align: center; border-left: <?=$krav['fom'] < $this->fra ? "none" : "1px solid"?>; border-top: <?=$skillelinje1?>; border-right: <?=$krav['tom'] > $this->til ? "none" : "1px solid"?>; border-top: <?=$skillelinje1?>; width: <?=round((min(strtotime($this->til), strtotime($krav['tom'])) - max(strtotime($this->fra), strtotime($krav['fom'])) + 43200) / 43200)?>px"><?=$krav['fom'] < $this->fra ? "<<" : (date('d/m', strtotime($krav['fom'])) . " - " . date('d/m', strtotime($krav['tom'])) . "<br />kr. " . number_format($krav['beløp'], 0, ",", " "))?></td>
<?
					}
					if($krav['tom'] < $this->til){
?>
		<td style="font-size: x-small; border-color: #909090; background-color: grey; border-left: none; border-top: <?=$skillelinje1?>; width: <?=round((strtotime($this->til) - strtotime($krav['tom'])) / 43200)?>px"></td>
<?
					}
?>
	</tr>
</table>
<?
					$skillelinje1 = "1px solid grey";
					$skillelinje2 = "none";
					$skille = 0;
				}
			}
		}
		
?>

<script type="text/javascript">
	window.print();
</script>
</body>
</html>
<?
}


function lagPDF() {
	$this->fra = $this->mysqli->real_escape_string($_GET['fra']);
	$this->til = $this->mysqli->real_escape_string($_GET['til']);
	if(!$this->fra)
		$this->fra = date('Y-01-01');
	if(!$this->til)
		$this->til = date('Y-m-d', $this->leggtilIntervall((strtotime($this->fra)-86400), 'P1Y'));

	$pdf = new FPDF();
	$pdf->AddPage("L", "A3");
	$pdf->SetFont('Arial','',11);
	$pdf->Cell(40,10,"{$this->valg['utleier']}: Krysstabell over utleie " . date('d.m.Y', strtotime($this->fra)) . " - " . date('d.m.Y', strtotime($this->til)) . ". Produsert " . date('d.m.Y'));
	
	$pdf->SetFont('Arial','B',10);
	
	$x = 10;
	$y = 20;
	$pdf->SetXY($x, $y);
	$pdf->Cell(15, 5, "Leil", "BR", 0, "C");
	$tidspkt = strtotime($this->fra);
	while($tidspkt < strtotime($this->til)){
		$månedsskifte = strtotime(date('Y-m-01', $this->leggtilIntervall($tidspkt, "P1M")));
		$pdf->Cell(($månedsskifte - $tidspkt)/86400, 5, strftime('%b', $tidspkt), "BR", 0, "C");
		$tidspkt = $månedsskifte;
	}

	$x = 10;
	$y += 5;
	$pdf->SetXY($x, $y);
	$sql =	"SELECT leieobjektnr FROM leieobjekter\n"
		.	"ORDER BY leieobjektnr\n";
	$leieobjekter = $this->arrayData($sql);
	foreach($leieobjekter['data'] as $leieobjekt){
		$leieobjektrekke[$leieobjekt['leieobjektnr']] = array();
	}	
	foreach($leieobjektrekke as $leieobjekt=>$a){
		$datosett = array();
		$sql =	"SELECT fradato, tildato, andel, kontraktnr\n"
			.	"FROM kontrakter\n"
			.	"WHERE fradato <= '$this->til'\n"
			.	"AND tildato >= '$this->fra'\n"
			.	"AND leieobjekt = '$leieobjekt'\n"
			.	"ORDER BY fradato, tildato";
		$kontraktrekke = $this->arrayData($sql);
		foreach($kontraktrekke['data'] as $kontrakt){
			$leieobjektrekke[$leieobjekt][$kontrakt['kontraktnr']] = $kontrakt;
			$datosett[strtotime(max($this->fra, $kontrakt['fradato']))] = strtotime(max($this->fra, $kontrakt['fradato']));
			if($kontrakt['tildato'] < $this->fra){
				$datosett[strtotime($kontrakt['tildato']) + 86400] = strtotime($kontrakt['tildato']) + 86400;
			}
		}
		
		$sql =	"SELECT kontraktnr, fom, tom, beløp\n"
			.	"FROM krav\n"
			.	"WHERE type = 'Husleie'\n"
			.	"AND fom <= '$this->til'\n"
			.	"AND tom >= '$this->fra'\n"
			.	"AND leieobjekt = '$leieobjekt'\n"
			.	"ORDER BY fom, tom";
		$kravrekke = $this->arrayData($sql);
		foreach($kravrekke['data'] as $krav){
			$leieobjektrekke[$leieobjekt][$krav['kontraktnr']]['krav'] = $krav;
			$datosett[strtotime(max($this->fra, $krav['fom']))] = strtotime(max($this->fra, $krav['fom']));
			$datosett[strtotime(min($this->til, $krav['tom'])) + 86400] = strtotime(min($this->til, $krav['tom'])) + 86400;
		}
		
		$pdf->SetX(10);
		$pdf->Cell(15, max(count($leieobjektrekke[$leieobjekt]) * 6, 6), $leieobjekt, "BR", 0, "C");
		
		foreach($leieobjektrekke[$leieobjekt] as $kontrakt){
			foreach($datosett as $tidspkt){
				$x = 25 + ($tidspkt - strtotime($this->fra))/86400;
				$pdf->SetX($x);
				$pdf->Cell(15, 3, $kontrakt['kontraktnr'], "TBLR", 0, "L");
			}
		}
		$pdf->ln(6);
	}

	$pdf->Output();

}


function taimotSkjema() {
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		default:
		$this->fra = $this->mysqli->real_escape_string($_GET['fra']);
		$this->til = $this->mysqli->real_escape_string($_GET['til']);
		if(!$this->fra)
			$this->fra = date('Y-01-01');
		if(!$this->til)
			$this->til = date('Y-m-d', $this->leggtilIntervall((strtotime($this->fra)-86400), 'P1Y'));
	
?>
<h1>Utleietabell <?=date('d.m.Y', strtotime($this->fra))?> - <?=date('d.m.Y', strtotime($this->til))?></h1>
<table style="table-layout: fixed; border-collapse: collapse;">
	<tr style="height: 10px;">
		<th style="font-size: x-small; border-color: #909090; text-align: center; border-right: 1px solid; border-left: none; width: 35px">Leil</th>
		<th style="font-size: x-small; border-color: #909090; text-align: center; border-right: 1px solid; border-left: none; width: 114px">Kontrakt</th>
<?
		setlocale(LC_ALL, "nb_NO");
		$tidspkt = strtotime($this->fra);
		while($tidspkt < strtotime($this->til)){
			$månedsskifte = strtotime(date('Y-m-01', $this->leggtilIntervall($tidspkt, "P1M")));
?>
		<th style="font-size: x-small; border-color: #909090; text-align: center; border-left: 1px solid; border-right: 1px solid; width: <?=round(($månedsskifte - $tidspkt)/43200)-1?>px"><?=strftime('%b', $tidspkt)?></th>
<?
			$tidspkt = $månedsskifte;
		}
?>
	</tr>
</table>
<?
		$sql =	"SELECT leieobjektnr FROM leieobjekter\n";
		$leieobjekter = $this->arrayData($sql);
		foreach($leieobjekter['data'] as $leieobjekt){
			$leieobjektrekke[$leieobjekt['leieobjektnr']] = array();
		}
		
		foreach($leieobjektrekke as $leieobjekt=>$a){
			$sql =	"SELECT fom, tom, andel, kontraktnr, beløp\n"
				.	"FROM krav\n"
				.	"WHERE type = 'Husleie'\n"
				.	"AND fom <= '$this->til'\n"
				.	"AND tom >= '$this->fra'\n"
				.	"AND leieobjekt = '$leieobjekt'\n"
				.	"ORDER BY fom, tom";
			$kravrekke = $this->arrayData($sql);
			foreach($kravrekke['data'] as $krav){
				$leieobjektrekke[$leieobjekt][$this->leieforhold($krav['kontraktnr'])][$krav['kontraktnr']][] = $krav;
			}
		}
	
		foreach($leieobjektrekke as $leieobjekt=>$leieforholdsett){
			$skillelinje1 = "2px solid black";
			$skillelinje2 = "2px solid black";
			$skille = 1;
			foreach($leieforholdsett as $leieforhold=>$kontraktsett){
				foreach($kontraktsett as $kontrakt=>$kravsett){
?>
<table style="table-layout: fixed; border-collapse: collapse;">
	<tr style="height: 10px;">
		<td style="font-size: x-small; border-color: #909090; font-weight: bold; text-align: right; border-left: none; border-right: none; border-top: <?=$skillelinje2?>; width: 30px"><?=$skille ? $leieobjekt : ""?></td>
		<td style="font-size: x-small; border-color: #909090; text-align: center; border-left: none; border-top: <?=$skillelinje2?>; width: 120px"><?=($this->evaluerAndel($kravsett[0]['andel']) < 1 ? "{$kravsett[0]['andel']}: " : "") . "$kontrakt " . $this->liste($this->kontraktpersoner($kontrakt));?></td>
<?
					if($kravsett[0]['fom'] > $this->fra){
?>
		<td style="font-size: x-small; border-color: #909090; background-color: grey; border-left: none; border-top: <?=$skillelinje1?>; width: <?=round((strtotime($kravsett[0]['fom']) - strtotime($this->fra)) / 43200)?>px"></td>
<?
					}
					foreach($kravsett as $krav){
?>
		<td style="font-size: x-small; border-color: #909090; text-align: center; border-left: <?=$krav['fom'] < $this->fra ? "none" : "1px solid"?>; border-top: <?=$skillelinje1?>; border-right: <?=$krav['tom'] > $this->til ? "none" : "1px solid"?>; border-top: <?=$skillelinje1?>; width: <?=round((min(strtotime($this->til), strtotime($krav['tom'])) - max(strtotime($this->fra), strtotime($krav['fom'])) + 43200) / 43200)?>px"><?=$krav['fom'] < $this->fra ? "<<" : (date('d/m', strtotime($krav['fom'])) . " - " . date('d/m', strtotime($krav['tom'])) . "<br />kr. " . number_format($krav['beløp'], 0, ",", " "))?></td>
<?
					}
					if($krav['tom'] < $this->til){
?>
		<td style="font-size: x-small; border-color: #909090; background-color: grey; border-left: none; border-top: <?=$skillelinje1?>; width: <?=round((strtotime($this->til) - strtotime($krav['tom'])) / 43200)?>px"></td>
<?
					}
?>
	</tr>
</table>
<?
					$skillelinje1 = "1px solid grey";
					$skillelinje2 = "none";
					$skille = 0;
				}
			}
		}
		break;
	}
}

}
?>