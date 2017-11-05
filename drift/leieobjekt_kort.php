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
	if(!$id = $_GET['id']) die("Ugyldig oppslag: ID ikke angitt for leieobjekt");
	$this->hoveddata = "SELECT leieobjekter.*, bygninger.navn AS bygning
	FROM leieobjekter LEFT JOIN bygninger ON leieobjekter.bygning = bygninger.id
	WHERE leieobjektnr = '$id'";
}

function skript() {
	if(@$_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>


	var kort = new Ext.Panel({
		frame: true,
		autoLoad: 'index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=hentdata&id=<?=$_GET['id']?>',
		height: 400,
		title: 'Leieobjekt nr. <?=$_GET['id']?>',
		width: 900,
		height: 500,
		buttons: [{
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
			},
			text: 'Tilbake'
		}, {
			handler: function() {
				window.location = "index.php?oppslag=skade_liste&id=<?=$_GET['id']?>";
			},
			text: 'Vis skader på <?=$this->leieobjekt($_GET['id'], true, true)?>'
		}, {
			handler: function() {
				window.location = "index.php?oppslag=leieobjekt_skjema&id=<?=$_GET["id"]?>";
			},
			text: 'Endre opplysningene'
		}]
	});
    kort.render('panel');

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
		case 'leie':
			break;
		default:
			$data = $this->arrayData($this->hoveddata);
			$data = $data['data'][0];
			if($data['navn']) {
				echo "<h1>{$data['navn']}</h1>\n"
				.	"<p>{$data['gateadresse']}</p>\n";
			}
			else {
				echo "<h1>{$data['gateadresse']}</h1>\n";
			}
			echo "<p>";
			if($data['bilde']) {
				echo "<img src=\"{$data['bilde']}\" style=\"float: right; width: 200px; margin: 10px;\"/>";
			}
			echo "<b>";
			switch($data['etg']){
				case '+':
					echo 'loft';
					break;
				case '0':
					echo 'sokkel';
					break;
				case '-1':
					echo 'kjeller';
					break;
				case '':
					echo '';
					break;
				default:
					echo "{$data['etg']}. etg.";
					break;
			}
			echo " {$data['beskrivelse']}</b><br />&nbsp;</p>\n";
			
			echo "{$data['postnr']}&nbsp;{$data['poststed']}<br />";
			echo "<p>Bygning: <b>{$data['bygning']}</b><br />&nbsp;</p>\n";
			
			echo "<p>Leies ut som: <b>" . ($data['boenhet'] ? "Bolig" : "Nærings- eller annet lokale") . "</b><br /></p>\n";
			
			echo ($data['boenhet'] ? "<p>Aktiv: <b>Boligen " : "<p>Aktivt: <b>Lokalet ") . ($data['ikke_for_utleie'] ? "er <i>ikke</i>" : "er") . " aktivert for utleie.</b><br />&nbsp;</p>\n";
			
			echo "<p>Areal: <b>{$data['areal']}m&#178;</b></p>\n"
			.	"<p>Tilgang til bad: <b>" . ($data['bad'] ? 'Ja' : 'Nei') . "</b><br />\n"
			.	"Toalett: <b>{$data['toalett']} (Kategori {$data['toalett_kategori']})</b></p>\n"
			.	"<p>Andre merknader:<br />{$data['merknader']}<br />&nbsp;</p>\n";

			$dagensBeboere = $this->dagensBeboere($data['leieobjektnr']);
			if(!count($dagensBeboere)) {
				echo "<p><b>" . ($data['boenhet'] ? "Boligen er ubebodd" : "Lokalet er ikke leid ut") . "</b></p>";
			}
			else {
				echo "<p><b>Leietakere:</b><br />\n";
				foreach($dagensBeboere as $kontrakt) {
					echo "<a title=\"Klikk her for å gå til leieavtalen\" href=\"index.php?oppslag=leieforholdkort&id={$this->leieforhold($kontrakt)}\">" . $this->liste($this->kontraktpersoner($kontrakt)) . "</a><br />\n";
				}
				echo "&nbsp;</p>";
			}

			$beregning = $this->arrayData("SELECT *\n"
			.	"FROM leieberegning\n"
			.	"WHERE nr = '{$data['leieberegning']}'\n");
			$beregning = $beregning['data'][0];
			
			echo "<p>Leieberegningsmetode: <b title=\"{$beregning['beskrivelse']}\">{$beregning['navn']}</b><br />\n";
			
			$objektleie = $beregning['leie_objekt']
			+ ($data['bad'] * $beregning['leie_var_bad'])
			+ (($data['toalett_kategori'] == 2) ? $beregning['leie_var_egendo'] : 0)
			+ (($data['toalett_kategori'] == 1) ? $beregning['leie_var_fellesdo'] : 0)
			+ ($data['areal'] * $beregning['leie_kvm']);
			
			$objektbesk = "";
			if($beregning['leie_objekt']) {
				$objektbesk .= "Grunnbeløp kr.&nbsp;" . (number_format($beregning['leie_objekt'], 2, ",", " "));
			}
			if($data['bad'] and $beregning['leie_var_bad']) {
				$objektbesk .= ($objektbesk ? " + " : "") . "kr.&nbsp;" . number_format($beregning['leie_var_bad'], 2, ",", " ") . " for tilgang på bad";
			}
			if(($data['toalett_kategori'] == 2) and $beregning['leie_var_egendo']) {
				$objektbesk .= ($objektbesk ? " + " : "") . "kr.&nbsp;" . number_format($beregning['leie_var_egendo'], 2, ",", " ") . " for egen do";
			}
			if(($data['toalett_kategori'] == 1) and $beregning['leie_var_fellesdo']) {
				$objektbesk .= ($objektbesk ? " + " : "") . "kr.&nbsp;" . number_format($beregning['leie_var_fellesdo'], 2, ",", " ") . " for felles do";
			}
			$objektbesk .= ($objektbesk ? " + (" : "") . "kr. " . number_format($beregning['leie_kvm'], 2, ",", " ") . " * {$data['areal']}m&#178;" . ($objektbesk ? ")" : "");
			
			echo "<b>kr. " . number_format($objektleie, 2, ",", " ") . " per mnd.</b> for leieobjektet ({$objektbesk}).<br />\n";

			if($beregning['leie_kontrakt']) {
				echo "<b>+ kr. " . (number_format($beregning['leie_kontrakt'], 2, ",", " ")) . " per mnd.</b> per leieavtale<br />\n";
				echo "<b>= kr. " . (number_format($objektleie + $beregning['leie_kontrakt'], 2, ",", " ")) . " per mnd.</b> for hele leieobjektet\n";
			}
			$vanligste_andel = $this->arrayData("
			SELECT andel, COUNT(kontraktnr)
			FROM `kontrakter`
			WHERE leieobjekt = '{$data['leieobjektnr']}' AND andel != '1'
			GROUP BY andel
			ORDER BY COUNT(kontraktnr) DESC
			LIMIT 1
			");
			$vanligste_andel = ($vanligste_andel['data'][0]['andel']);
			if($this->evaluerAndel($vanligste_andel) < 1 and $this->evaluerAndel($vanligste_andel)) {
				echo " eller <b> kr. " . (number_format(round($objektleie * $this->evaluerAndel($vanligste_andel) + $beregning['leie_kontrakt']), 2, ",", " ")) . " per mnd.</b> for {$vanligste_andel} i bofellesskap<br />\n";
			}
			echo "</p>";
	}
}

}
?>