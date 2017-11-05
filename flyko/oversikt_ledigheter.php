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

	var panel = new Ext.Panel({
		autoLoad: 'index.php?oppslag=oversikt_ledigheter&oppdrag=hentdata',
        autoScroll: true,
        bodyStyle: 'padding:5px',
		title: 'Ledige leiligheter og lokaler',
		frame: true,
		height: 500,
		plain: false,
		width: 900
	});

	// Rutenettet rendres in i HTML-merket '<div id="kontrakt">':
	panel.render('panel');

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
	$sql = "SELECT * FROM leieobjekter ORDER BY leieobjektnr";
	$leieobjekter = $this->arrayData($sql);
	foreach($leieobjekter['data'] as $leieobjekt) {
		$u = $this->utleiegrad($leieobjekt['leieobjektnr'], time(), 'kontrakter');
		if($u['ledig'] <> 0) {
			$ledig[$leieobjekt['leieobjektnr']] = $leieobjekt;
			$ledig[$leieobjekt['leieobjektnr']]['utleiegrad'] = $u;
		}
	}
?>
<table style="text-align: left;" border="0" cellpadding="2" cellspacing="0">
<tbody>
<tr>
<td style="vertical-align: top;">
<p>
<?
	if(count($ledig) == 0)
		echo "Det er per idag ingen ledige leieobjekter";
	else {
				echo "Ledig per " . date('d.m.Y') . ":<br /><br />\n";
		foreach ($ledig as $leieobjektnr=>$info) {
			if($info['utleiegrad']['sum'] > 1) {
						echo "<b><img src=\"../bilder/advarsel_rd.png\" style=\"float: right; margin: 4px; height: 50px;\"/>Advarsel!<br /><br />Det er i dag overutleie i " . $this->leieobjekt($leieobjektnr, true) . ":</b><br />";
						foreach ($info['utleiegrad']['andel'] as $kontrakt=>$andel){
							$b = $this->arrayData("SELECT * FROM kontrakter WHERE kontraktnr = $kontrakt");
							echo $this->brok($andel) . " leies av " . $this->liste($this->kontraktpersoner($kontrakt)) . " (" . date('d.m.Y', strtotime($b['data'][0]['fradato'])) . " - " . date('d.m.Y', $this->sluttdato($kontrakt)) . ")<br />\n";
						}
						echo "<hr />\n";
			}
			else {
				$sql =	"SELECT kontrakter.kontraktnr\n"
						.		"FROM kontrakter INNER JOIN oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold\n"
				.		"WHERE leieobjekt = $leieobjektnr AND fristillelsesdato <= '" . date('Y-m-d') . "'\n"
				.		"ORDER BY fristillelsesdato DESC\n"
				.		"LIMIT 0,1";
				$sisteleietakere = $this->arrayData($sql);
				$sisteleietakere = $this->liste($this->kontraktpersoner($sisteleietakere['data'][0]['kontraktnr']));
				$ledighet = $this->brok($info['utleiegrad']['ledig']);
						echo (($ledighet == '1') ? "" : "$ledighet av ") . "<a href= \"index.php?oppslag=leieobjekt_kort&id=$leieobjektnr\">" . ($info['boenhet'] ? "bolig" : "lokale") . " nr. $leieobjektnr</a>: " . $this->etasjerenderer($info['etg']) . " " .(($info['navn']) ? ($info['navn'] . ", ") : $info['gateadresse']) . ", " . $info['beskrivelse'] . "<br />\nSiste leietaker(e): $sisteleietakere<br />";
						foreach ($info['utleiegrad']['andel'] as $kontrakt=>$andel){
							echo $this->brok($andel) . " leies av " . $this->liste($this->kontraktpersoner($kontrakt)) . "<br />\n";
						}
						echo "<hr />\n";
			}
		}
	}
?>
</p>
<hr />
<?
	$sql =	"SELECT fristillelsesdato AS dato, kontraktnr, 'slutt' AS endring\n"
	.		"FROM oppsigelser\n"
	.		"WHERE fristillelsesdato > '" . date('Y-m-d') . "'\n"
	.		"UNION\n"
	.		"SELECT fradato AS dato, kontraktnr, 'start' AS endring\n"
	.		"FROM kontrakter\n"
	.		"WHERE fradato > '" . date('Y-m-d') . "' AND kontraktnr = leieforhold\n"
	.		"ORDER BY dato, endring, kontraktnr\n";
	$endringer = $this->arrayData($sql);

	foreach($endringer['data'] as $endring) {
		
				echo "<b>" . date('d.m.Y', strtotime($endring['dato'])) . ":</b><br />\n";
		
		$sql =	"SELECT kontrakter.*, leieobjekter.*\n"
		.		"FROM kontrakter INNER JOIN leieobjekter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr\n"
		.		"WHERE kontraktnr = " . $endring['kontraktnr'];
		$kontrakt = $this->arrayData($sql);
		$kontrakt = $kontrakt['data'][0];
		$kontrakt['leietakere'] = $this->liste($this->kontraktpersoner($endring['kontraktnr']));
		if($endring['endring'] == "slutt") {
					echo ($kontrakt['andel'] != '1' ? ($kontrakt['andel'] . " av ") : "") . "<a href=\"index.php?oppslag=leieobjekt_kort&id=" . $kontrakt['leieobjektnr'] . "\">" . ($kontrakt['boenhet'] ? "bolig" : "lokale") . " " . $kontrakt['leieobjektnr'] . "</a> blir ledig etter " . $kontrakt['leietakere'] . " (leieavtale nr. " . $kontrakt['kontraktnr'] . ")" . " i " . $this->etasjerenderer($kontrakt['etg']) . " " .(($kontrakt['navn']) ? ($kontrakt['navn'] . ", ") : $kontrakt['gateadresse']) . ", " . $kontrakt['beskrivelse'] . "<br />\n";
		}
		else {
			echo $kontrakt['leietakere'] . " flytter inn i " . ($kontrakt['boenhet'] ? "bolig" : "lokale") . " nr. " . $kontrakt['leieobjektnr'] . " i " . $this->etasjerenderer($kontrakt['etg']) . " " .(($kontrakt['navn']) ? ($kontrakt['navn'] . ", ") : $kontrakt['gateadresse']) . ", " . $kontrakt['beskrivelse'] . "<br />\n";
		}
		$utleiegrad = $this->utleiegrad($kontrakt['leieobjektnr'], strtotime($endring['dato']), 'kontrakter');
		if($utleiegrad['ledig'] > 0) {
					echo (($utleiegrad['ledig'] < 1) ? ($this->brok($utleiegrad['ledig']) . " av ") : "") . ($kontrakt['boenhet'] ? "boligen" : "lokalet") . " er da " . (($utleiegrad['ledig'] < 1) ? "" : "helt ") . "ledig.<br />\n";
		}
		else {
					echo ($kontrakt['boenhet'] ? "boligen" : "lokalet") . " er utleid fra da.<br />\n";
				}
				foreach ($utleiegrad['andel'] as $kontrakt=>$andel){
					echo $this->brok($andel) . " leies av " . $this->liste($this->kontraktpersoner($kontrakt)) . "<br />\n";
		}
		echo "<hr />";
	}
?>
<p>
</p>
</td>
</tr>
</tbody>
</table>
<?
}
}

}
?>