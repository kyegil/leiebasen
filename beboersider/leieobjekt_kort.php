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
					echo $this->liste($this->kontraktpersoner($kontrakt)) . "<br />\n";
				}
				echo "&nbsp;</p>";
			}

			$beregning = $this->arrayData("SELECT *\n"
			.	"FROM leieberegning\n"
			.	"WHERE nr = '{$data['leieberegning']}'\n");
			$beregning = $beregning['data'][0];
			
			echo "<p>Leieberegningsmetode: <b title=\"{$beregning['beskrivelse']}\">{$beregning['navn']}</b><br />\n";
			echo "</p>";
	}
}

}
?>