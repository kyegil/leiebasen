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
	if(!$this->fra)
		$this->fra = date('Y-m-01');
	if(!$this->til)
		$this->til = date('Y-m-d', $this->leggtilIntervall(strtotime($this->fra), 'P1M')-86400);
	$this->hoveddata = "
		SELECT dato, IF(konto !='OCR-giro', betaler, 'OCR-overføring') AS betaler, konto, SUM(innbetalinger.beløp) AS beløp, ref, OCRdetaljer.filID
		FROM innbetalinger LEFT JOIN OCRdetaljer ON innbetalinger.OCRtransaksjon = OCRdetaljer.id
		WHERE konto != '0' AND dato >= '$this->fra' AND dato <= '$this->til'
		GROUP BY konto, dato, ref
		ORDER BY konto, dato, CAST(ref AS SIGNED), betaler
	";
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
		url: "index.php?oppdrag=hentdata&oppslag=oversikt_kontobevegelser&fra=<?=$this->fra?>&til=<?=$this->til?>",
		fields: [
			{name: 'dato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'betaler'},
			{name: 'konto'},
			{name: 'beløp'},
			{name: 'filID'},
			{name: 'ref'},
			{name: 'saldo'},
			{name: 'siste'},
			{name: 'html'}
		],
		root: 'data'
    });
    datasett.load();

	function sendmelding(){
		Ext.Ajax.request({
			waitMsg: 'Prøver å sende meldinger per epost...',
			url: 'index.php?oppslag=oversikt_kontobevegelser&oppdrag=oppgave&oppgave=sendmelding',
			failure:function(response,options){
				Ext.MessageBox.alert('Mislykket...','Klarte ikke å sende meldinger om nye innbetalinger. Prøv igjen senere.');
			},
			success:function(response,options){
				var tilbakemelding = Ext.util.JSON.decode(response.responseText);
				if(tilbakemelding['success'] == true) {
					Ext.MessageBox.alert('Utført', tilbakemelding.msg);
				}
				else {
					Ext.MessageBox.alert('Hmm..',tilbakemelding['msg']);
				}
			}
		});
	}

    var expander = new Ext.ux.grid.RowExpander({        tpl : new Ext.Template(
            '{html}'
        )
    });

	var dato = {
		dataIndex: 'dato',
		header: 'Dato',
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		sortable: false,
		width: 70
	};

	var betaler = {
		dataIndex: 'betaler',
		header: 'Betaler',
		sortable: false,
		width: 180
	};

	var konto = {
		dataIndex: 'konto',
		header: 'Konto',
		sortable: false,
		width: 60
	};

	var beløp = {
		align: 'right',
		dataIndex: 'beløp',
		header: 'Beløp',
		renderer: Ext.util.Format.noMoney,
		sortable: false,
		width: 70
	};

	var ref = {
		dataIndex: 'ref',
		header: 'Ref',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			if(record.data.filID) {
				return '<a title="Klikk her for å lese detaljene i OCR-forsendelsen" href="index.php?oppslag=ocr_kort&id=' + record.data.filID + '">' + value + '</a>';
			}
			else {
				return '<a title="Klikk her for å åpne detaljene i denne innbetalinga" href="index.php?oppslag=innbetalingskort&betalingsmet=' + record.data.konto + '&dato=' + record.data.dato.format('Y-m-d') + '&betaler=' + encodeURIComponent(record.data.betaler) + '&OCRtransaksjon=0&ref=' + encodeURIComponent(record.data.ref) + '">' + value + "</a>";
			}
		},
		sortable: false,
		width: 90
	};

	var saldo = {
		align: 'right',
		dataIndex: 'saldo',
		header: 'Saldo',
		renderer: function(value, metadata, record, rowIndex, colIndex, store) {
			if(record.get('siste')){
				return "<b><u>" + Ext.util.Format.noMoney(value) + "</u></b>";
			}
			else {
				return Ext.util.Format.noMoney(value);
			}
		},
		sortable: false,
		width: 90
	};


	var rutenett = new Ext.grid.GridPanel({
		autoExpandColumn: 1,
		buttons: [{
			text: '<< 1 mnd. <<',
			handler: function() {
				window.location = "index.php?oppslag=<?=$_GET['oppslag']?>&fra=<?=date('Y-m-01', $this->leggtilIntervall(strtotime($this->fra), 'P-1M'));?>&til=<?=date('Y-m-d', ($this->leggtilIntervall(strtotime(date('Y-m-01', $this->leggtilIntervall(strtotime($this->fra), 'P-1M'))), 'P1M')-86400));?>";
			}
		}, {
			text: 'Andre månedsvise oversikter',
			handler: function() {
				window.location = "index.php?oppslag=oversikt_innbetalinger";
			}
		}, {
			text: '>> 1 mnd. >>',
			handler: function() {
				window.location = "index.php?oppslag=<?=$_GET['oppslag']?>&fra=<?=date('Y-m-d', $this->leggtilIntervall(strtotime($this->fra), 'P1M'));?>&til=<?=date('Y-m-d', ($this->leggtilIntervall(strtotime($this->fra), 'P2M')-86400));?>";
			}
		}, {
			text: 'Send bekreftelser for nye innbetalinger',
			handler: sendmelding
		}, {
			text: 'Utskriftsversjon',
			handler: function() {
				window.open("index.php?oppdrag=utskrift&oppslag=oversikt_kontobevegelser&fra=<?=$this->fra?>&til=<?=$this->til?>");
			}
		}],
		store: datasett,
		columns: [
			expander,
			dato,
			betaler,
			konto,
			beløp,
			ref,
			saldo
		],
		stripeRows: true,
        height: 500,
        width: 900,
		viewConfig: {
			forceFit: false
		},       
		autoExpandColumn: 2,
        plugins: expander,
        title: "Innbetalinger <?=date('d.m.Y', strtotime($this->fra)) . " - " . date('d.m.Y', strtotime($this->til));?>"
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

		foreach($resultat['data'] as $index=>$linje){
			$sql =	"
				SELECT innbetalinger.innbetalingsid, innbetalinger.OCRtransaksjon, innbetalinger.betaler, innbetalinger.leieforhold, innbetalinger.beløp AS innbetalt, innbetalinger.krav, krav.*, kontrakter.leieobjekt AS leil
				FROM innbetalinger
				LEFT JOIN krav ON innbetalinger.krav = krav.id
				LEFT JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr
				WHERE
				innbetalinger.dato = '{$linje['dato']}'
				AND konto = '{$linje['konto']}'
				AND ref = '{$linje['ref']}'
				ORDER BY kontrakter.leieforhold, krav.kontraktnr, krav.type DESC, krav.id
			";
			$detaljer = $this->arrayData($sql);
			foreach($detaljer['data'] as $detalj) {
				settype($resultat['data'][$index]['transaksjoner'][((int)$detalj['OCRtransaksjon'])]['beløp'], 'string');
				$resultat['data'][$index]['transaksjoner'][((int)$detalj['OCRtransaksjon'])]['utlikninger'][((int)$detalj['leieforhold'])][$detalj['innbetalingsid']] = $detalj;
				$resultat['data'][$index]['transaksjoner'][((int)$detalj['OCRtransaksjon'])]['beløp'] += $detalj['innbetalt'];
				$resultat['data'][$index]['transaksjoner'][((int)$detalj['OCRtransaksjon'])]['betaler'] = $detalj['betaler'];
			}
		}

		$saldo = 0;
		$type = "";
		$siste = 1;

		foreach($resultat['data'] as $index=>$linje){
			$html = "";
			if($type == $linje['konto']){
				$saldo += $linje['beløp'];
			}
			else{
				$saldo = $linje['beløp'];
			}
			$type = $linje['konto'];
			
			if($type == @$resultat['data'][$index + 1]['konto']){
				$siste = 0;
			}
			else{
				$siste = 1;
			}

			foreach($linje['transaksjoner'] as $transaksjonsnr => $transaksjon){
				if($linje['konto'] == 'OCR-giro') {
					$html .=	"<p style=\"margin-left: 10px;\"><a title=\"Klikk her for å åpne detaljene i denne innbetalinga\" href=\"index.php?oppslag=innbetalingskort&betalingsmet={$linje['konto']}&dato={$linje['dato']}&betaler=" . rawurlencode($transaksjon['betaler']) . "&OCRtransaksjon=$transaksjonsnr&ref=" . rawurlencode($linje['ref']) . "\">Innbetaling på kr. " . number_format($transaksjon['beløp'], 2, ",", " ") . " fra {$transaksjon['betaler']}</a></p>";
				}
				foreach($transaksjon['utlikninger'] as $leieforhold => $utlikninger) {
					$html	.= "<p style=\"margin-left: 10px;\">"
							.	(
								$leieforhold
								?	("Kreditert leieforhold <a title=\"Klikk her for å gå til kontrakten\" href=\"index.php?oppslag=leieforholdkort&id={$leieforhold}\">{$leieforhold}</a>: " . $this->liste($this->kontraktpersoner($this->sistekontrakt($leieforhold))) . " i " . $this->leieobjekt($this->kontraktobjekt($leieforhold)) . ":")
								: "<i style=\"color: red;\">ikke kreditert:</i>"
							)
							. "</p>";
					foreach( $utlikninger as $utlikning ) {
						$html	.=	"<p style=\"margin-left: 50px;\">kr. " . number_format($utlikning['innbetalt'], 2, ",", " ")
								. (
									$utlikning['krav']
									? " utliknet mot <a title=\"Klikk her for å åpne detaljene i betalingskravet\" href=\"index.php?oppslag=krav_kort&id={$utlikning['id']}\">{$utlikning['tekst']}</a>"
									: ($utlikning['krav'] !== null ? " tilbakebetalt" : "<i style=\"color: red;\"> ikke utliknet</i>")
								)
								. "</p>";
					}
					$html .= "<p><br /></p>";
				}
			}

			if(!$detaljer['success'])
				$html .= $detaljer['msg'];
			$resultat['data'][$index]['html'] = $html;
			$resultat['data'][$index]['saldo'] = $saldo;
			$resultat['data'][$index]['siste'] = $siste;
		}

		return json_encode($resultat);
		break;
	}
}



function oppgave($oppgave) {
	switch($oppgave){
		case 'sendmelding':
			if(!$resultat['success'] = $this->varsleNyeInnbetalinger()){
				$resultat['msg'] = "Klarte ikke å sende meldinger om nye innbetalinger. Prøv igjen senere.";
			}
			else{
				$resultat['msg'] = "Meldingene er sendt.";
			}
			break;
	}
	echo json_encode($resultat);
}



function utskrift(){
	$sql =	"SELECT konto
			FROM innbetalinger
			WHERE dato >= '{$this->fra}' AND dato <= '{$this->til}'
			GROUP BY konto
			ORDER BY konto";
	$kategorier = $this->arrayData($sql);
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:ext="http://www.extjs.com" xml:lang="no" lang="no">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Betalingsoversikt <?=date('d.m.Y', strtotime($this->fra));?> - <?=date('d.m.Y', strtotime($this->til));?></title>
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
<h1>Oversikt over innbetalinger og utlikning av disse for tidsrommet <?=date('d.m.Y', strtotime($this->fra));?> - <?=date('d.m.Y', strtotime($this->til));?></h1>
	<p></p>
<?
	foreach($kategorier['data'] as $kategori){
		echo "\t<h2>{$kategori['konto']}</h2>";
		$sql = "
			SELECT dato, " . ($kategori['konto'] != 'OCR-giro' ? "betaler" : "'OCR-overføring' AS betaler") . ", SUM(beløp) AS beløp, ref
			FROM innbetalinger
			WHERE dato >= '{$this->fra}' AND dato <= '{$this->til}' AND konto = '{$kategori['konto']}'
			GROUP BY dato, ref
			ORDER BY dato, ref
		";
		$saldo = 0;
		$innbetalinger = $this->arrayData($sql);
		foreach($innbetalinger['data'] as $index=>$linje){
			$saldo += $linje['beløp'];
			$innbetalinger['data'][$index]['saldo'] = $saldo;
		}
?>
	<table style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="0">
	<tbody>
		<tr>
			<th>Dato</th>
			<th>Betaler</th>
			<th></th>
			<th style="text-align: right;">Beløp</th>
			<th style="text-align: right;">Referanse</th>
			<th style="text-align: right;">Saldo</th>
		</tr>
<?
		foreach($innbetalinger['data'] as $innbetaling){
			$bakgrunn = $bakgrunn ? "" : "background-color: #E0E0E0;";
?>
		<tr>
			<td style="border-top-style: solid; border-width: thin; width: 70px; font-weight: bold; <?=$bakgrunn?>"><?=date('d.m.Y', strtotime($innbetaling['dato']))?></td>
			<td colspan=2 style="border-top-style: solid; border-width: thin; font-weight: bold; <?=$bakgrunn?>"><?=$innbetaling['betaler']?></td>
			<td style="border-top-style: solid; border-width: thin; width: 70px; text-align: right; font-weight: bold; <?=$bakgrunn?>"><?=number_format($innbetaling['beløp'], 2, ",", " ")?></td>
			<td style="border-top-style: solid; border-width: thin; width: 90px; text-align: right; font-weight: bold; <?=$bakgrunn?>"><?=$innbetaling['ref']?></td>
			<td style="border-top-style: solid; border-width: thin; width: 70px; text-align: right; font-weight: bold; <?=$bakgrunn?>"><?=number_format($innbetaling['saldo'], 2, ",", " ")?></td>
		</tr>
<?
			$sql = "
				SELECT innbetalinger.beløp AS innbetalt, krav.*, kontrakter.leieobjekt AS leil FROM innbetalinger LEFT JOIN krav ON innbetalinger.krav = krav.id
				LEFT JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr
				WHERE
				innbetalinger.dato = '{$innbetaling['dato']}'
				AND konto = '{$kategori['konto']}'
				AND ref = '{$innbetaling['ref']}'
				ORDER BY kontrakter.leieforhold, krav.kontraktnr, krav.type DESC, krav.id
			";
			$utlikninger = $this->arrayData($sql);
			foreach($utlikninger['data'] as $utlikning){
				echo "\t\t<tr>";
				echo "\t\t\t<td style=\"$bakgrunn\"></td>";
				echo "\t\t\t<td style=\"$bakgrunn font-size: xx-small; font-style:italic;\">&nbsp;";
				echo $utlikning['id'] ? ("utliknet mot: {$utlikning['tekst']} (" . number_format($utlikning['beløp'], 2, ",", " ") . ")") : "ikke utliknet";
				echo " for ";
				echo $utlikning['id'] ? ($this->liste($this->kontraktpersoner($utlikning['kontraktnr'])) . ", leieavtale {$utlikning['kontraktnr']}") : "";
				echo "</td>";
				echo "\t\t\t<td style=\"$bakgrunn font-size: xx-small; font-style:italic; text-align: right; width: 40px;\">" . number_format($utlikning['innbetalt'], 2, ",", " ") ."</td>";
				echo "\t\t\t<td colspan=3 style=\"$bakgrunn\"></td>";
				echo "\t\t</tr>";
			}
		}
?>
</tbody>
</table>
<?
	}
?>
<script type="text/javascript">
	window.print();
</script>
</body>
</html>
<?
}


}
?>