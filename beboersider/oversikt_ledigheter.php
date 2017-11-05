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
	$this->returi->reset();
	$this->returi->set();
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
			$sql = "SELECT * FROM leieobjekter WHERE !ikke_for_utleie ORDER BY leieobjektnr";
			$leieobjekter = $this->arrayData($sql);
			foreach($leieobjekter['data'] as $leieobjekt) {
				$u = $this->utleie($leieobjekt['leieobjektnr'], time());
				if($u['ledig'] <> 0) {
					$ledig[$leieobjekt['leieobjektnr']] = $leieobjekt;
					$ledig[$leieobjekt['leieobjektnr']]['utleiegrad'] = $u;
				}
			}
?>
<span class="dataload">
<table style="width:100%">
<tbody>
<tr>
<td>
<p>
<?
			if(count($ledig) == 0)
				echo "Det er per idag ingen ledige leieobjekter";
			else {
				echo "<h2>Ledig per " . date('d.m.Y') . ":</h2><br /><br />\n";
				foreach ($ledig as $leieobjektnr=>$info) {
					if($info['utleiegrad']['sum'] > 1) {
						echo "<b><img src=\"../bilder/advarsel_rd.png\" style=\"float: right; margin: 4px; height: 50px;\"/>Advarsel!<br /><br />Det er i dag overutleie i " . $this->leieobjekt($leieobjektnr, true) . ":</b><br />";
						foreach ($info['utleiegrad']['kontrakter'] as $kontraktnr => $kontrakt){
							$b = $this->arrayData("SELECT * FROM kontrakter WHERE kontraktnr = '$kontraktnr'");
							echo "<a href=\"index.php?oppslag=leieforholdkort&id=$kontraktnr\">" . $this->brok($kontrakt['andel']) . " leies av " . $this->liste($this->kontraktpersoner($kontraktnr)) . " (" . date('d.m.Y', strtotime($b['data'][0]['fradato'])) . " - " . date('d.m.Y', $this->sluttdato($kontraktnr)) . ")</a><br />\n";
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
						foreach ($info['utleiegrad']['kontrakter'] as $kontraktnr => $kontrakt){
							echo "<a href=\"index.php?oppslag=leieforholdkort&id=$kontraktnr\">" . $this->brok($kontrakt['andel']) . " leies av " . $this->liste($this->kontraktpersoner($kontraktnr)) . "</a><br />\n";
						}
						echo "<hr />\n";
					}
				}
			}
?>
</p>
</td>
</tr>
<?
			$sql =	"SELECT oppsigelser.fristillelsesdato AS dato, oppsigelser.leieforhold, 'slutt' AS endring, kontrakter.leieobjekt\n"
			.		"FROM oppsigelser INNER JOIN kontrakter ON oppsigelser.leieforhold = kontrakter.leieforhold\n"
			.		"WHERE fristillelsesdato > '" . date('Y-m-d') . "'\n"
			.		"UNION\n"
			.		"SELECT kontrakter.fradato AS dato, kontrakter.leieforhold, 'start' AS endring, kontrakter.leieobjekt\n"
			.		"FROM kontrakter\n"
			.		"WHERE fradato > '" . date('Y-m-d') . "' AND kontraktnr = leieforhold\n"
			.		"ORDER BY dato, leieobjekt, endring, leieforhold\n";
			$data = $this->arrayData($sql);
		
			foreach($data['data'] as $endring) {
				$endringer[$endring['dato']][$endring['leieobjekt']][$endring['endring']][] = $endring['leieforhold'];
			}
			foreach($endringer as $dato => $leieobjekter) {
				echo "<tr><td><h2>" . date('d.m.Y', strtotime($dato)) . ":</h2>";
				echo "</td></tr>";
				foreach($leieobjekter as $leieobjekt => $endring) {
					echo "<tr style=\"border-bottom: thin solid black;\"><td><a title=\"Klikk her for å åpne leieobjektkortet\" href=\"index.php?oppslag=leieobjekt_kort&id={$leieobjekt}\">" . $this->leieobjekt($leieobjekt, true) . "</a><br />";
					foreach($endring['slutt'] as $leieforhold) {
						$sql =	"SELECT kontrakter.*, leieobjekter.*\n"
						.		"FROM kontrakter INNER JOIN leieobjekter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr\n"
						.		"WHERE kontraktnr = '{$leieforhold}'";
						$kontrakt = $this->arrayData($sql);
						$kontrakt = $kontrakt['data'][0];
						echo $this->liste($this->kontraktpersoner($leieforhold));
						echo " flytter ut.<br />";
					}					
					foreach($endring['start'] as $leieforhold) {
						$sql =	"SELECT kontrakter.*, leieobjekter.*\n"
						.		"FROM kontrakter INNER JOIN leieobjekter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr\n"
						.		"WHERE kontraktnr = '{$leieforhold}'";
						$kontrakt = $this->arrayData($sql);
						$kontrakt = $kontrakt['data'][0];
						echo $this->liste($this->kontraktpersoner($leieforhold));
						echo " flytter inn.<br />";
					}					
					$utleie = $this->utleie($leieobjekt, strtotime($dato));
					if($utleie['ledig'] > 0) {
						echo "<span class=\"bold green\">" . (($utleie['ledig'] < 1) ? ($this->brok($utleie['ledig']) . " av ") : "") . $this->leieobjekt($leieobjekt, true, true) . " er da " . (($utleie['ledig'] < 1) ? "" : "helt ") . "ledig.</span><br />\n";
					}
					foreach ($utleie['kontrakter'] as $kontraktnr => $kontrakt){
						echo ($kontrakt['andel'] < 1 ?  ($this->brok($kontrakt['andel']) . " leies av " . $this->liste($this->kontraktpersoner($kontraktnr)) . "<br />\n") : "");
					}
					echo "&nbsp;<br /></td></tr>";
				}
			}
?>
</tbody>
</table>
</span>
<?
	}
}

}
?>