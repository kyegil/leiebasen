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
	if(!$id = $this->GET['anleggsnummer']) die("Anleggsnummer ikke oppgitt");
	$this->hoveddata =	"SELECT fs_originalfakturaer.*, fs_fellesstrømanlegg.formål AS bruk, fs_fellesstrømanlegg.målernummer, fs_fellesstrømanlegg.plassering\n"
		.	"FROM fs_fellesstrømanlegg LEFT JOIN fs_originalfakturaer ON fs_fellesstrømanlegg.anleggsnummer = fs_originalfakturaer.anleggsnr\n"
		.	"WHERE fs_fellesstrømanlegg.anleggsnummer = '{$id}'\n"
		.	"ORDER BY fradato DESC, tildato DESC, fakturanummer DESC";
	$this->tittel = "Fellesstrøm anlegg {$id}";
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
	$resultat = $resultat['data'][0];
?>
<table><tbody style="font-size: 1em;">
	<tr>
		<td style="padding: 0 5px;"><b>Anleggsnr:</b></td>
		<td style="padding: 0 5px;"><?=$resultat['anleggsnr']?></td>
	</tr><tr>
		<td style="padding: 0 5px;"><b>Målernr:</b></td>
		<td style="padding: 0 5px;"><?=$resultat['målernummer']?></td>
	</tr><tr>
		<td style="padding: 0 5px;"><b>Brukes til:</b></td>
		<td style="padding: 0 5px;"><?=$resultat['bruk']?></td>
	</tr><tr>
		<td style="padding: 0 5px;"><b>Målerplassering:</b></td>
		<td style="padding: 0 5px;"><?=$resultat['plassering']?></td>
	</tr>
</tbody></table>
<?
	$sql =	"SELECT fs_fordelingsnøkler.*, leieobjekter.boenhet
			FROM fs_fordelingsnøkler LEFT JOIN leieobjekter ON fs_fordelingsnøkler.leieobjekt = leieobjekter.leieobjektnr
			WHERE anleggsnummer = '{$this->GET['anleggsnummer']}'
			ORDER BY field(fordelingsmåte, 'Fastbeløp', 'Prosentvis', 'Andeler'), følger_leieobjekt, leieobjekt";
	$nokler = $this->arrayData($sql);
	$nokler = $nokler['data'];
	echo "<p>&nbsp;</p>\n";
	echo "<h2 class=\"beforegroup\">Fordelingsnøkkel per " . date('d.m.Y') . ":</h2>\n";
	echo "<table class=\"group\" width=\"100%\">\n";
	foreach($nokler as $nokkel){
		$beboere = array();
		$kontrakter = $this->dagensBeboere($nokkel['leieobjekt']);
		foreach($kontrakter AS $kontrakt){
			$beboere[] = $this->liste($this->kontraktpersoner($kontrakt));
		}
		
		echo "\t<tr>\n\t\t<td style=\"vertical-align:top; font-size: 0.9em;\">";
		
		switch ($nokkel['fordelingsmåte']){
			case "Fastbeløp":
				echo "<b>Kr. " . number_format($nokkel['fastbeløp'], 2, ",", " ") . "</b> betales av ";
				if($nokkel['følger_leieobjekt']){
					echo "" . $this->leieobjekt($nokkel['leieobjekt'], true) . " " . "<br />";
				}
				else {
					echo $this->liste($this->kontraktpersoner($this->sistekontrakt($nokkel['leieforhold']))) . "<br />";
				}
				break;
			case "Prosentvis":
				echo "<b>" . (($nokkel['prosentsats'] == 1) ? "Alt" : number_format(($nokkel['prosentsats'] * 100), 2, ",", " ") . "%") . "</b> betales av ";
				if($nokkel['følger_leieobjekt']){
					echo "" . $this->leieobjekt($nokkel['leieobjekt'], true) . " " . "<br />";
				}
				else {
					echo $this->liste($this->kontraktpersoner($this->sistekontrakt($nokkel['leieforhold']))) . "<br />";
				}
				break;
			case "Andeler":
				echo "<b>" . $nokkel['andeler'] . (($nokkel['andeler'] > 1) ? " deler " : " del ") . "</b> betales av ";
				if($nokkel['følger_leieobjekt']){
					echo (count($kontrakter)>1 ? "hver leieavtale i " : "") . $this->leieobjekt($nokkel['leieobjekt'], true) . "<br />";
				}
				else {
					echo $this->liste($this->kontraktpersoner($this->sistekontrakt($nokkel['leieforhold']))) . "<br />";
				}
				break;
		}
		echo "</td>\n\t</tr>\n";
	}
	echo "\t<tr>\n\t\t<td colspan=\"2\">&nbsp;</td>\n\t</tr>\n";
	echo "</table>\n";
	if(!count($nokler)){
		echo "<p>Ingen fordeling av dette anlegget<br /><br /></p>";
	}

	$resultat = $this->arrayData($this->hoveddata);

	echo "<h2 class=\"beforegroup\">" . ($_GET['fordeling'] ? "Fordeling av tidligere strømregninger:" : "Registerte strømregninger på dette anlegget:") . "</h2>\n";
	echo "<table width=\"100%\">\n";
	echo "<tr>\n";
	echo "\t<th>Fakturanr</th>\n";
	echo "\t<th>Termin</th>\n";
	echo "\t<th style=\"width:70px\">Forbruk</th>\n";
	echo "\t<th style=\"width:70px\">Beløp</th>\n";
	echo "</tr>\n";
	foreach($resultat['data'] as $index=>$linje){
		echo "<tr" . ($_GET['fordeling'] ? " class=\"beforegroup\"" : "") . ">\n";
		echo "\t<td" . ($_GET['fordeling'] ? " class=\"bold\"" : "") . ">{$linje['fakturanummer']}</td>\n";
		echo "\t<td" . ($_GET['fordeling'] ? " class=\"bold\"" : "") . ">{$linje['termin']}&nbsp;(" . date('d.m.Y', strtotime($linje['fradato'])) . "&nbsp;-&nbsp;" . date('d.m.Y', strtotime($linje['tildato'])) . ")</td>\n";
		echo "\t<td class=\"" . ($_GET['fordeling'] ? "bold " : "") . "value\">" . ($linje['kWh'] ? (str_replace(" ", "&nbsp;", number_format($linje['kWh'], 0, "", " ")) . "&nbsp;kWh") : "&nbsp;") . "</td>\n";
		echo "\t<td class=\"bold value\">kr.&nbsp;" . str_replace(" ", "&nbsp;", number_format($linje['fakturabeløp'], 2, ",", " ")) . "</td>\n";
		echo "</tr>\n";
		if($_GET['fordeling']) {
			echo "<tr>\n";
			echo "\t<td>&nbsp;</td>\n\t<td colspan=\"3\" class=\"value\"" . ($linje['fordelt'] ? "" : " style=\"color:red;\"") . ">\n";
			$sql = "SELECT kontraktnr, SUM(beløp) as beløp, COUNT(epostvarsel) AS epostvarsel FROM fs_andeler WHERE faktura = '{$linje['fakturanummer']}' GROUP BY kontraktnr ORDER BY kontraktnr";
			$fordeling = $this->arrayData($sql);
			$sum = 0;
			echo "\t\t<table style=\"width: 100%;\"><tbody>\n";
			foreach($fordeling['data'] as $del){
				echo "\t\t\t<tr>\n\t\t\t\t<td style=\"width: 40px\">" . ($linje['fordelt'] ? "&nbsp;" : "Forslag: ") . "</td>\n\t\t\t\t<td class=\"value\" style=\"width: 40px\">{$del['kontraktnr']}</td>\n\t\t\t\t<td>" . $this->liste($this->kontraktpersoner($del['kontraktnr'])) . "</td>\n\t\t\t\t<td style=\"text-align: right; width: 100px;\">kr.&nbsp;" . str_replace(" ", "&nbsp;", number_format($del['beløp'], 2, ",", " ")) . "</td>\n\t\t\t</tr>\n";
				$sum += $del['beløp'];
			}
			echo "\t\t\t<tr>\n\t\t\t\t<td colspan=\"3\">&nbsp;</td>\n\t\t\t\t<td class=\"summary value\">kr.&nbsp;" . str_replace(" ", "&nbsp;", number_format($sum, 2, ",", " ")) . "</td>\n\t\t\t</tr>\n";
			echo "\t\t\t<tr>\n\t\t\t\t<td colspan=\"3\">&nbsp;</td>\n\t\t\t</tr>\n";
			echo "\t\t</tbody></table>\n";
			echo "\t</td>\n";
			echo "</tr>\n";
		}
	}
	echo "</table>\n";
?>
<script type="text/javascript">
	window.print();
</script>
<?
}

}
?>