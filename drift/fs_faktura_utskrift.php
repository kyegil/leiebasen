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
	$this->mal = "_utskrift.php";
}

function skript() {
}

function design() {
?>
<div id="panel"></div>
<?
}

function utskrift() {
	$faktura = $this->arrayData($this->hoveddata);
	$faktura = $faktura['data'][0];
?>
<h1>Fellesstrømregning med fakturanummer <?=$faktura['fakturanummer']?></h1>
<p><?=$this->valg['utleier']?></p>
<table class="tabell1"><tbody>
	<tr>
		<td style="padding: 0 5px;">Anleggsnr: </td>
		<td style="padding: 0 5px;"><b><?=$faktura['anleggsnr']?></b><br /><?=$faktura['formål']?><br />(målernr. <?=$faktura['målernummer']?>)</td>
		<td rowspan="5" style="padding: 0 5px; width: 200px;"><?=($faktura['fordelt'] ? "<img width=\"176\" height=\"80\" src=\"../bilder/fordelt_stempel_utskrift.png\" alt=\"Ferdig fordelt og oversendt beboerne\" title=\"Fakturaen er ferdig fordelt og andelene krevd inn fra beboerne.\" />" : "")?></td>
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
<?
	if($faktura['fordelt']) {
		echo "<p><b>Strømregninga er fordelt mellom beboerne, og de respektive beløpene krevd innbetalt fra hver enkelt beboer.<br /></b></p>";
	}
	else {
		echo "<p>Strømregninga er foreslått fordelt mellom beboerne, men fordelingen er ikke bekreftet.<br /></p>";
	}
?>
<?=($faktura['epostvarsel'] ? "<p>Fordelinga har blitt oversendt registrerte beboere med e-post.<br /></p>" : "<p><br /></p>")?>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p><b><?=($faktura['fordelt'] ? "Fordeling:" : "Foreslått fordeling:")?></b></p>
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
		<td colspan="2">&nbsp;</td>
		<td style="padding: 0 5px; text-align: right;"><?=($andelssum != null ? round($andelssum * 100) . "%" : "")?></td>
		<td style="padding: 0 5px; text-align: right;"><?="kr. " . number_format($total, 2, ",", " ")?></td>
		<td>&nbsp;</td>
	</tr>
</tbody></table>
<script type="text/javascript">
	window.print();
</script>
<?
}

}
?>