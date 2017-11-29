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
	$this->kontrollrutiner();
}

function skript() {
	$this->returi->reset();
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	var statistikk1 = new Ext.Panel({
		autoScroll: true,
		autoLoad: 'index.php?oppslag=forsiden&oppdrag=hentdata&data=statistikk1',
		bodyStyle: 'padding: 2px',
		border: false,
		collapsible: true,
		collapsed: false,
		title: 'Oppsummert'
	});


	var statistikk2 = new Ext.Panel({
		autoScroll: true,
		autoLoad: 'index.php?oppslag=forsiden&oppdrag=hentdata&data=statistikk2',
		bodyStyle: 'padding: 2px',
		border: false,
		collapsible: true,
		collapsed: false,
		title: 'Oppgjør'
	});

	var hovedpanel = new Ext.Panel({
		layout: 'border',
		defaults: {
			collapsible: true,
			split: true,
			bodyStyle: 'padding: 15px;'
		},
		items: [{
			title: 'Mitt leieforhold',
			autoLoad: 'index.php?oppdrag=hentdata&oppslag=&oppdrag=hentdata&data=sidepanel',
			autoScroll: true,
			collapsed: false,
			animCollapse: true,
			region:'east',
			margins: '5 0 0 0',
			cmargins: '5 5 0 0',
			bodyStyle: 'padding: 5px',
			width: 400,
			minSize: 50,
			maxSize: 600,
			items: [],
			buttons: []
		}, {
			title: '',
			region: 'south',
			border: false,
			height: 120,
			collapsed: true,
			animCollapse: true,
//			collapsed: <?=$this->advarsler ? "false" : "true";?>,
			minSize: 75,
			maxSize: 250,
			cmargins: '5 0 0 0',
			items: []
		}, {
			title: '<?=$this->valg['utleier']?>',
			collapsible: false,
			region:'center',
			margins: '5 0 0 0',
			layout:'column',
			items: [{
				bodyStyle: 'padding: 3px',
				border: false,
				title: '',
				columnWidth: .5,
				items: [statistikk1]
			},{
				bodyStyle: 'padding: 3px',
				border: false,
				title: '',
				columnWidth: .5,
				items: [statistikk2]
			}]
		}],
		title: '',
		height: 500,
		width: 900
	});

    hovedpanel.render('panel');

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
		case "sidepanel":
//			return;
			
			$resultat = "";
			$sql =	"SELECT MAX(kontrakter.kontraktnr) AS kontraktnr, kontrakter.leieforhold, kontrakter.leieobjekt\n"
				.	"FROM (kontrakter INNER JOIN adganger ON kontrakter.leieforhold = adganger.leieforhold)\n"
				.	"WHERE adgang = 'beboersider'\n"
				.	"AND personid = '{$this->bruker['id']}'\n"
				.	"GROUP BY kontrakter.leieforhold\n"
				.	"ORDER BY kontrakter.kontraktnr DESC";
			$leieforhold = $this->arrayData($sql);
			foreach($leieforhold['data'] as $kontrakt){
				$resultat .=
"<table>
	<tr style=\"vertical-align: text-top;\">
		<td>
			<p style=\"font-weight:bold;\"><a href=\"index.php?oppslag=leieforholdkort&id={$kontrakt['leieforhold']}\">Leieforhold {$kontrakt['leieforhold']}</a></p>
			<p style=\"vertical-align: middle; margin: 6px;\"><a href=\"index.php?oppslag=leieforholdkort&id={$kontrakt['leieforhold']}\"><img src=\"../bilder/leieavtaler.png\" style=\"float: left; clear: left; margin-right: 6px;\" /></a>" . $this->liste($this->kontraktpersoner($kontrakt['kontraktnr'])) . "<br />
			i " . $this->leieobjekt($kontrakt['leieobjekt'], false) . "<br />
			<a href=\"index.php?oppslag=leieforholdkort&id={$kontrakt['leieforhold']}\">Leieavtale nr. {$kontrakt['kontraktnr']}</a></p>
		</td>
		<td>
			<p style=\"font-weight:bold;\"><a href=\"index.php?oppslag=leieobjekt_kort&id={$kontrakt['leieobjekt']}\">" . ($this->er_bolig($kontrakt['leieobjekt']) ? "Min bolig" : "Mitt lokale") . "</a></p>
			<p style=\"vertical-align: middle; margin: 6px;\"><a href=\"index.php?oppslag=leieobjekt_kort&id={$kontrakt['leieobjekt']}\"><img src=\"../bilder/leieobjekt.png\" style=\"float: left; clear: left; margin-right: 6px;\" /></a>" . ucfirst($this->leieobjekt($kontrakt['leieobjekt'], false)) . "</p>
			<p><a title=\"Registrer skade på bolig eller bygning\" href=\"index.php?oppslag=skade_skjema&leieobjektnr={$kontrakt['leieobjekt']}&id=*\">Meld skade</a></p>
		</td>
	<tr>
</table>

<p>Utestående i leieforhold {$kontrakt['leieforhold']}:</p>
<table style=\"font-size: 0.85em;\">
	<tr>
		<th style=\"padding: 0px 10px;\">Giro</th>
		<th style=\"padding: 0px 10px;\"></th>
		<th style=\"padding: 0px 10px;\">Å betale</th>
		<th style=\"padding: 0px 10px;\">Forfall</th>
	</tr>";

				$sql =	"SELECT krav.tekst, beløp, utestående, forfall, gironr\n"
					.	"FROM krav INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr\n"
					.	"WHERE kravdato < DATE_ADD(NOW(), INTERVAL 1 MONTH)\n"
					.	"AND utestående\n"
					.	"AND leieforhold = '{$kontrakt['leieforhold']}'\n"
					.	"ORDER BY forfall, gironr";
				$utestående = $this->arrayData($sql);
				if(!count($utestående['data'])) {
					$utestående['data'][0] = array(
						'tekst'		=> "<i>Intet utestående</i> <img src=\"../bilder/midkiffaries_Glossy_Emoticons.png\" style=\"height: 15px; width: 15px;\" />",
						'gironr'	=> null,
						'utestående'=> 0,
						'forfall'	=> null
					);
				}
				$sum = 0;
				foreach($utestående['data'] as $krav) {
					$resultat .= "
	<tr>
		<td style=\"width: 30px; padding: 0px 10px; text-align: right;\"><a title=\"Klikk her for å åpne giroen i PDF-format\" href=\"index.php?oppslag=giro&oppdrag=lagpdf&gironr={$krav['gironr']}\">{$krav['gironr']}</a></td>
		<td style=\"padding: 0px 10px;\">{$krav['tekst']}</td>
		<td style=\"width: 50px; padding: 0px 10px; text-align: right;\">" . number_format($krav['utestående'], 2, ",", " ") . "</td>
		<td style=\"width: 50px; padding: 0px 10px;" . (strtotime($krav['forfall']) < time() ? " color: red;" : "") . "\">" . ($krav['forfall'] ? date('d.m.Y', strtotime($krav['forfall'])) : "") . "</td>
	</tr>";
					$sum += $krav['utestående'];
				}

				if($sum) $resultat .= "
	<tr style=\"font-weight: bold;\">
		<td style=\"padding: 0px 10px;\"></td>
		<td style=\"padding: 0px 10px;\">Sum</td>
		<td style=\"padding: 0px 10px; text-align: right;\">" . number_format($sum, 2, ",", " ") . "</td>
		<td style=\"padding: 0px 10px;\"></td>
	</tr>";
				$resultat .= "
</table>
<p>Innbetalinger kan gjøres til konto {$this->valg['bankkonto']}.<br />
" . ($this->valg['ocr'] ? ("Fast KID for leieforholdet er " . $this->genererKid($kontrakt['leieforhold'])) : "Merk betalinga med leieforhold {$kontrakt['leieforhold']}") . ".</p>
<p>&nbsp;</p>
";

			}

			echo $resultat;
			return;
			break;
		case "ubetalt":
			if($this->adgang("beboersider", $_GET['leieforhold'])){
				$sql =	"SELECT krav.tekst, beløp, utestående, forfall, gironr\n"
					.	"FROM krav INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr\n"
					.	"WHERE kravdato < DATE_ADD(NOW(), INTERVAL 1 MONTH)\n"
					.	"AND utestående\n"
					.	"AND leieforhold = '" . (int)$_GET['leieforhold'] . "'\n"
					.	"ORDER BY forfall, gironr";
				$resultat = $this->arrayData($sql);
				if(!count($resultat['data'])){
					$resultat['data'][0]['tekst'] = "<i>Intet utestående</i> <img src=\"../bilder/midkiffaries_Glossy_Emoticons.png\" style=\"height: 15px; width: 15px;\" />";
				}
				return json_encode($resultat);
			}
			else return false;
			break;
		case "statistikk1":
			$sql =	"SELECT SUM(beløp) AS beløp, DATE_FORMAT(dato, '%Y-%m-01') AS måned\n"
				.	"FROM `innbetalinger`\n"
				.	"WHERE konto<>'0'\n"
				.	"GROUP BY måned\n"
				.	"ORDER BY måned DESC\n"
				.	"LIMIT 0,4";
			$resultat = $this->arrayData($sql);
			echo "<b>Innbetalinger:</b><table>";
			foreach($resultat['data'] as $betalt){
				echo "<tr><td width=\"100px\">" . strftime("%B %Y", strtotime($betalt['måned'] . "-01")) . ":</td><td style=\"text-align: right;\">kr " . number_format($betalt['beløp'], 2, ",", " ") . "</td></tr>";
			}
			echo "</table><a href=\"index.php?oppslag=oversikt_innbetalinger\">se mer ...</a><br /><br />";
			
			$sql =	"SELECT registrerer, registrert\n"
				.	"FROM `innbetalinger`\n"
				.	"WHERE konto <> '0' AND konto <> 'OCR-giro'\n"
				.	"ORDER BY registrert DESC\n"
				.	"LIMIT 0,1";
			$resultat = $this->arrayData($sql);
			echo "Siste manuelle registrering av betaling: " . date('d.m.Y', strtotime($resultat['data'][0]['registrert'])) . "<br />";
			
			echo "<br />";
			
			echo "<b>Annet:</b><br />";
			
			$sql =	"SELECT MAX(utskriftsdato) AS utskriftsdato\n"
				.	"FROM `giroer`\n";
			$resultat = $this->arrayData($sql);
			echo "Siste giroutskrift: " . date("d.m.Y", strtotime($resultat['data'][0]['utskriftsdato'])) . "<br />";
			
			$sql =	"SELECT MAX(purredato) AS purredato\n"
				.	"FROM `purringer`\n";
			$resultat = $this->arrayData($sql);
			echo "Siste purring: " . date("d.m.Y", strtotime($resultat['data'][0]['purredato'])) . "<br />";
			
			$sql =	"SELECT termin, fradato, tildato\n"
				.	"FROM `fs_originalfakturaer`\n"
				.	"WHERE fordelt\n"
				.	"ORDER BY tildato DESC\n"
				.	"LIMIT 0,1";
			$resultat = $this->arrayData($sql);
			echo "Siste fordelte fellesstrøm: <span title=\"Fellesstrøm for perioden " . date("d.m.Y", strtotime($resultat['data'][0]['fradato'])) . " - " . date("d.m.Y", strtotime($resultat['data'][0]['tildato'])) . "\">termin {$resultat['data'][0]['termin']}</span><br />";

			echo "<br />";

			$sql =	"SELECT skade, registrert, navn FROM skader INNER JOIN bygninger ON skader.bygning=bygninger.id WHERE utført IS NULL ORDER BY skader.registrert DESC LIMIT 0,1";
			$resultat = $this->arrayData($sql);
			echo "Siste <a href=\"index.php?oppslag=skade_liste\">skademelding</a>:<br />{$resultat['data'][0]['skade']} i {$resultat['data'][0]['navn']}<br />meldt " . date("d.m.Y", strtotime($resultat['data'][0]['registrert'])) . "<br />";
			break;
			
		case "statistikk2":
//			echo "<b>Oppgjør:</b><br />";
			// oppgjør består av feltene kravid, utestående, oppgjort, forfall, oppgjørsdato, oppfyllelse (=oppgjør antall dager før forfall)
			$oppgjør = "(SELECT id AS kravid, utestående, !utestående AS oppgjort, IFNULL(krav.forfall, krav.kravdato) AS forfall, IF(!utestående, MAX(innbetalinger.dato), NOW()) AS oppgjørsdato, DATEDIFF(IFNULL(krav.forfall, krav.kravdato), IF(!utestående, MAX(innbetalinger.dato), NOW())) AS oppfyllelse\n"
				.	"FROM krav LEFT JOIN innbetalinger ON krav.id = innbetalinger.krav\n"
				.	"GROUP BY krav.id)\n"
				.	"AS oppgjør";

			$sql =	"SELECT COUNT(oppgjør.kravid) AS totalt, SUM(oppgjør.oppgjort) AS oppgjort\n"
				.	"FROM krav INNER JOIN $oppgjør ON krav.id = oppgjør.kravid\n"
				.	"WHERE krav.type = 'Husleie' AND oppgjør.forfall <=NOW() AND oppgjør.forfall > DATE_SUB(NOW(), INTERVAL 1 MONTH)";
			$resultat = $this->arrayData($sql);

			echo "<span title=\"{$resultat['data'][0]['oppgjort']} av totalt {$resultat['data'][0]['totalt']}\">" . number_format($resultat['data'][0]['oppgjort']/($ant_leier = $resultat['data'][0]['totalt'])*100, 1, ",", " ") . "%</span> av forfalte leier siste måned er betalt.<br />";
			
			$sql =	"SELECT COUNT(oppgjør.kravid) AS oppgjort\n"
				.	"FROM krav INNER JOIN $oppgjør ON krav.id = oppgjør.kravid\n"
				.	"WHERE krav.type = 'Husleie' AND oppgjør.forfall <=NOW() AND oppgjør.forfall > DATE_SUB(NOW(), INTERVAL 1 MONTH) AND oppgjørsdato <= oppgjør.forfall";
			$resultat = $this->arrayData($sql);

			echo "<span title=\"{$resultat['data'][0]['oppgjort']} av totalt $ant_leier\">" . number_format($resultat['data'][0]['oppgjort']/$ant_leier*100, 1, ",", " ") . "%</span> ble betalt innen forfall.<br />";
			
			echo "<br />";

			$sql =	"SELECT SUM(utestående) AS utestående, SUM(beløp) AS totalt\n"
				.	"FROM krav\n"
				.	"WHERE IFNULL(krav.forfall, krav.kravdato) <=NOW() AND IFNULL(krav.forfall, krav.kravdato) > DATE_SUB(NOW(), INTERVAL 1 YEAR)";
			$resultat = $this->arrayData($sql);

			echo "Utestående siste 12 mnd:<br /><span title=\"Av totalt kr. " . number_format($resultat['data'][0]['totalt'], 2, ",", " ") . " (Dvs. " . number_format($resultat['data'][0]['utestående']/$resultat['data'][0]['totalt']*100, 1, ",", " ") . "%)\">kr. " . number_format($resultat['data'][0]['utestående'], 2, ",", " ") . "</span><br />";
			
			$sql =	"SELECT SUM(utestående) AS utestående, SUM(beløp) AS totalt\n"
				.	"FROM krav\n"
				.	"WHERE IFNULL(krav.forfall, krav.kravdato) <= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND IFNULL(krav.forfall, krav.kravdato) > DATE_SUB(NOW(), INTERVAL 1 YEAR)";
			$resultat = $this->arrayData($sql);

			echo "- Minus siste 2 måneder:<br /><span title=\"Av totalt kr. " . number_format($resultat['data'][0]['totalt'], 2, ",", " ") . " (Dvs. " . number_format($resultat['data'][0]['utestående']/$resultat['data'][0]['totalt']*100, 1, ",", " ") . "%)\">kr. " . number_format($resultat['data'][0]['utestående'], 2, ",", " ") . "</span><br />";
			
			$sql =	"SELECT AVG(oppfyllelse) AS oppfyllelse\n"
				.	"FROM $oppgjør\n"
				.	"WHERE oppgjørsdato <= NOW() AND oppgjørsdato > DATE_SUB(NOW(), INTERVAL 3 MONTH)";
			$resultat = $this->arrayData($sql);

//			echo "Oppgjør siste måned skjedde gjennomsnittlig " . number_format($resultat['data'][0]['oppfyllelse'], 0, ",", " ") . " dager " . "før forfall<br />";
			
			break;
		default:
			$resultat = $this->arrayData($this->hoveddata);
			return json_encode($resultat);
	}
}

}
?>