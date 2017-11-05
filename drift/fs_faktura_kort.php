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
	$this->hoveddata = "SELECT fs_originalfakturaer.*, fs_fellesstrømanlegg.formål, fs_fellesstrømanlegg.målernummer, MAX(fs_andeler.epostvarsel) AS epostvarsel\n"
		.	"FROM fs_originalfakturaer LEFT JOIN fs_fellesstrømanlegg ON fs_originalfakturaer.anleggsnr = fs_fellesstrømanlegg.anleggsnummer\n"
		.	"LEFT JOIN fs_andeler ON fs_originalfakturaer.fakturanummer = fs_andeler.faktura\n"
		.	"WHERE fs_originalfakturaer.id = '{$this->GET['id']}'\n"
		.	"GROUP BY fs_originalfakturaer.id\n";
	$faktura = $this->arrayData($this->hoveddata);
	$this->tittel = "Strømregning faktura nr. {$faktura['data'][0]['fakturanummer']}";
}

function skript() {
	if( isset( $_GET['returi'] ) && $_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	var indrepanel = new Ext.Panel({
		autoLoad: 'index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=hentdata&id=<?=$_GET['id']?>',
        autoScroll: true,
        bodyStyle: 'padding:5px',
		title: '',
		frame: false,
		height: 400,
		plain: false
	});

	var panel = new Ext.Panel({
		items: [indrepanel],
        autoScroll: false,
        bodyStyle: 'padding:5px',
		title: '',
		frame: true,
		height: 500,
		plain: false,
		width: 900,
		buttons: [{
			handler: function() {
				window.open('index.php?oppslag=fs_faktura_utskrift&id=<?=$_GET['id']?>');
			},
			text: 'Skriv ut'
		}, {
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
			},
			text: 'Tilbake'
		}]
	});

	// Rutenettet rendres in i HTML-merket '<div id="panel">':
	panel.render('panel');

});
<?
}

function design() {
?>
<div id="panel"></div>
<?
}

function hentData($data = "") {
	switch ($data) {
		default:
			$faktura = $this->arrayData($this->hoveddata);
			$faktura = $faktura['data'][0];
?>
<h1>Fellesstrømregning med fakturanummer <?=$faktura['fakturanummer']?></h1>
<p>Registrert av <?=$faktura['lagt_inn_av']?></p>
<table><tbody style="font-size: 1.2em;">
	<tr>
		<td style="padding: 0 5px;">Anleggsnr: </td>
		<td style="padding: 0 5px;"><b><?=$faktura['anleggsnr']?></b> <?=$faktura['formål']?> (målernr. <?=$faktura['målernummer']?>)</td>
		<td rowspan="5" style="padding: 0 5px; width: 200px;"><?=($faktura['fordelt'] ? "<img src=\"../bilder/fordelt_stempel.png\" alt=\"Ferdig fordelt og oversendt beboerne\" title=\"Fakturaen er ferdig fordelt og andelene krevd inn fra beboerne.\" />" : "")?></td>
	</tr><tr>
		<td style="padding: 0 5px;">Termin:</td>
		<td style="padding: 0 5px;"><b><?=$faktura['termin']?></b></td>
	</tr><tr>
		<td style="padding: 0 5px;">Tidsrom:</td>
		<td style="padding: 0 5px;"><b><?=date('d.m.Y', (strtotime($faktura['fradato']))) . " - " . date('d.m.Y', (strtotime($faktura['tildato'])))?></b></td>
	</tr><tr>
		<td style="padding: 0 5px;">Forbruk:</td>
		<td style="padding: 0 5px;"><b><?=($faktura['kWh'] ? "{$faktura['kWh']} kWh" : "&nbsp;")?></b></td>
	</tr><tr>
		<td style="padding: 0 5px;">Fakturabeløp:</td>
		<td style="padding: 0 5px;"><b>kr. <?=number_format($faktura['fakturabeløp'], 2, ",", " ")?></b></td>
	</tr>
</tbody></table>
<table class="tabell1"><tbody>
	<tr>
		<th style="padding: 0 5px;" colspan="2">Leieforhold</th>
		<th style="padding: 0 5px;">Beskrivelse</th>
		<th style="padding: 0 5px;">Beløp</th>
		<th style="padding: 0 5px;">Krav</th>
	</tr>
<?
			$sql = "SELECT * FROM fs_andeler WHERE faktura = '{$faktura['fakturanummer']}' ORDER BY kontraktnr, fom, tom";
			$andeler = $this->arrayData($sql);
			$andelssum = $total = 0;
			foreach($andeler['data'] as $andel) {
				$total += $andel['beløp'];
				if($andel['andel'] !== null and $andelssum !== null) {
					$andelssum += $andel['andel'];
				}
				else {
					$andelssum = null;
				}
?>
	<tr>
		<td style="padding: 0 5px;"><?=($andel['kontraktnr'] ? "{$andel['kontraktnr']}" : "")?></td>
		<td style="padding: 0 5px;"><?=($andel['kontraktnr'] ? $this->liste($this->kontraktpersoner($andel['kontraktnr'])) : $this->valg['utleier'])?></td>
		<td style="padding: 0 5px;"><?="{$andel['tekst']}"?></td>
		<td style="padding: 0 5px; text-align: right;"><?="kr. " . number_format($andel['beløp'], 2, ",", " ")?></td>
		<td style="padding: 0 5px;"><?="<a href=\"index.php?oppslag=krav_kort&id={$andel['krav']}\">{$andel['krav']}</a>"?></td>
	</tr>
<?
			}
?>
	<tr>
		<td colspan="3">&nbsp;</td>
		<td style="padding: 0 5px; text-align: right;"><?="kr. " . number_format($total, 2, ",", " ")?></td>
		<td>&nbsp;</td>
	</tr>
</tbody></table>
<?=($faktura['epostvarsel'] ? "Fordelingen over er oversendt registrerte beboere med e-post." : "")?>
<?
			break;
	}
}

}
?>