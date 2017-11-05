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

	bekreftTidsubestemt = function(id) {
		Ext.Msg.show({
			title: 'Opphev tidsbegrensingen',
			id: id,
			msg: 'Er du sikker på at du vil oppheve tidsbegrensingen i leieavtale nr. ' + id + '?<br />Leieavtalen vil da løpe til den blir sagt opp.',
			buttons: Ext.Msg.OKCANCEL,
			fn: function(buttonId, text, opt){
				if(buttonId == 'ok') {
					utførTidsubestemt(opt.id);
				}
			},
			animEl: 'elId',
			icon: Ext.MessageBox.QUESTION
		});
	}


	utførTidsubestemt = function(id){
		Ext.Ajax.request({
			waitMsg: 'Sletter...',
			url: "index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=oppgave&oppgave=opphevtidsbegrensing&id=" + id,
			success: function(response, options){
				var tilbakemelding = Ext.util.JSON.decode(response.responseText);
				if(tilbakemelding['success'] == true) {
					Ext.MessageBox.alert('Utført', tilbakemelding.msg, function(){
						panel.load({
							url: "index.php?oppslag=oversikt_fornyelser&oppdrag=hentdata"
						});
					});
				}
				else {
					Ext.MessageBox.alert('Hmm..',tilbakemelding['msg']);
				}
			}
		});
	}

	var panel = new Ext.Panel({
		autoLoad: 'index.php?oppslag=oversikt_fornyelser&oppdrag=hentdata',
        autoScroll: true,
        bodyStyle: 'padding:5px',
		title: 'Leieavtaler som bør fornyes',
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

function hentData($data = "") {
	switch ($data) {
		default:
			$framtildato = $this->leggtilIntervall(time(), 'P6M');
			$sql =	"SELECT MAX(kontraktnr) AS kontraktnr, MAX(fradato) AS fradato, MAX(tildato) AS tildato, leieobjekt\n"
			.		"FROM kontrakter\n"
			.		"GROUP BY leieforhold, leieobjekt\n"
			.		"ORDER BY MAX(tildato)";

		$a = $this->mysqli->arrayData(array(
			'distinct'	=> true,
			'source'	=> "kontrakter LEFT JOIN oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold",
			'where'		=> "oppsigelser.leieforhold IS NULL",
			'having'	=> "MAX(kontrakter.tildato) IS NOT NULL",
			'fields'	=> "MAX(kontrakter.kontraktnr) AS kontraktnr, kontrakter.leieforhold, MAX(kontrakter.fradato) AS fradato, MAX(kontrakter.tildato) AS tildato, kontrakter.leieobjekt",
			'orderfields'	=>	"MAX(kontrakter.tildato)",
			'groupfields'	=>	"kontrakter.leieforhold, kontrakter.leieobjekt"
		));
			
			foreach($a->data as $kontrakt) {
				if(!$this->oppsagt($kontrakt->kontraktnr) and $this->sluttdato($kontrakt->kontraktnr) < $framtildato) {
					if($this->sluttdato($kontrakt->kontraktnr) < time()) {
						echo "<img src=\"../bilder/advarsel_rd.png\" style=\"float: left; margin: 4px; height: 50px;\"/>";
					}
					else if($this->sluttdato($kontrakt->kontraktnr) < $this->leggtilIntervall(time(), 'P1M')) {
						echo "<img src=\"../bilder/tegnestift.png\" style=\"float: left; margin: 4px; height: 35px;\"/>";
					}
					echo "<a href='index.php?oppslag=leieforholdkort&id={$this->leieforhold($kontrakt->kontraktnr)}'>Leieavtale nr. {$kontrakt->kontraktnr}</a>: " . date('d.m.Y', strtotime($kontrakt->fradato)). " - <b>" . date('d.m.Y', strtotime($kontrakt->tildato)). "</b><br />" . $this->liste($this->kontraktpersoner($kontrakt->kontraktnr)) . " i " . $this->leieobjekt($kontrakt->leieobjekt) . "<br /><br />";
					echo "<a href='index.php?oppslag=leieforholdkort&id={$this->leieforhold($kontrakt->kontraktnr)}'>Vis leieavtalen</a> | <a href='index.php?oppslag=leieforhold_kontraktfornying&id={$kontrakt->kontraktnr}'>Forny denne leieavtalen</a> | <a style=\"cursor: pointer\" title=\"Gjør avtalen om til en løpende leieavtale\" onClick=\"bekreftTidsubestemt({$kontrakt->kontraktnr})\">Opphev tidsbegrensingen</a> | <a href='index.php?oppslag=oppsigelsesskjema&id={$kontrakt->kontraktnr}'>Avslutt dette leieforholdet</a><br /><hr />";
				}
			}
		break;
	}
}

function taimotSkjema() {
	echo json_encode($resultat);
}

function oppgave($oppgave) {
	switch ($oppgave) {
		case "opphevtidsbegrensing":
			if($resultat['success'] = $this->mysqli->query("UPDATE kontrakter SET tildato = NULL WHERE kontraktnr = '" . $this->sistekontrakt($this->GET['id']) . "'")) {
				$resultat['msg'] = "Tidsbegrensingen i leieavtalen er opphevet, og avtalen vil løpe uavbrutt til den blir sagt opp enten av " . $this->liste($this->kontraktpersoner($this->GET['id'])) . " eller av " . $this->valg['utleier'] . ".";
			}
			else {
				$resultat['msg'] = "Klarte ikke slette. Feilmeldingen fra databasen lyder:<br />" . $this->mysqli->error;
			}
			echo json_encode($resultat);
			break;
	}
}

}
?>