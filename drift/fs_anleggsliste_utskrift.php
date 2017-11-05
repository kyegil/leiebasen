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
	$this->hoveddata =	"SELECT fs_fellesstrømanlegg.*\n"
		.	"FROM fs_fellesstrømanlegg\n"
		.	"ORDER BY anleggsnummer";
	$this->tittel = "Oversikt over strømanlegg med målernummere";
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
	$resultat = $this->arrayData($this->hoveddata);
?>
<table class="dataload"><tbody style="font-size: 1em;">
	<tr>
		<th colspan="2" style="padding: 0 5px; width: 30%;">Anleggsnr og&nbsp;bruk</th>
		<th colspan="2" style="padding: 0 5px; width: 30%;">Målernr&nbsp;og plassering</th>
		<th style="padding: 0 5px;">Avlesning</th>
	</tr>
	<?foreach($resultat['data'] as $linje => $anlegg):?>
	<tr>
		<td style="padding: 0 5px;"><b><?=$anlegg['anleggsnummer']?></b></td>
		<td style="padding: 0 5px;"><?=$anlegg['formål']?></td>
		<td style="padding: 0 5px;"><b><?=$anlegg['målernummer']?></b></td>
		<td style="padding: 0 5px;"><?=$anlegg['plassering']?></td>
		<td style="padding: 0 5px;">&nbsp;</td>
	</tr>
	<?endforeach;?>
</tbody></table>
<script type="text/javascript">
	window.print();
</script>
<?
}

}
?>