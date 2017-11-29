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
	$this->hoveddata = "SELECT framleie.*, kontrakter.leieobjekt\n"
		.	"FROM framleie INNER JOIN kontrakter ON framleie.kontraktnr = kontrakter.kontraktnr\n"
		.	"ORDER BY framleie.tildato\n";
}

function skript() {
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	// oppretter datasettet
	var datasett = new Ext.data.JsonStore({
		url:'index.php?oppdrag=hentdata&oppslag=framleie_liste',
		fields: [
			{name: 'nr'},
			{name: 'kontraktnr'},
			{name: 'kontraktbeskrivelse'},
			{name: 'fradato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'tildato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'framleiere'}
		],
		root: 'data'
    });
    datasett.load();

	strykFramleie = function(kontraktnr){
		Ext.Ajax.request({
			waitMsg: 'Vent...',
			params: {
				'kontraktnr': kontraktnr
			},
			url: "index.php?oppslag=framleie_liste&oppdrag=oppgave&oppgave=slett",
			success : function() {
				window.location="index.php?oppslag=framleie_liste";
			}
		});
	}
	
	
	var endre = {
		dataIndex: 'nr',
		renderer: function(v){
			return "<a href=\"index.php?oppslag=framleie_skjema&id=" + v + "\"><img src=\"../bilder/rediger.png\" /></a>";
		},
		sortable: false,
		width: 30
	};

	var slett = {
		dataIndex: 'nr',
		renderer: function(v){
			return "<a style=\"cursor: pointer\" onClick=\"strykFramleie(" + v + ")\"><img src=\"../bilder/slett.png\" /></a>";
		},
		sortable: false,
		width: 30
	};

	var kontraktnr = {
		dataIndex: 'kontraktnr',
		header: 'Leieavtale',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return record.data.kontraktnr + ": " + record.data.kontraktbeskrivelse;
		},
		sortable: true,
		width: 250
	};

	var fradato = {
		dataIndex: 'fradato',
		header: 'Fra dato',
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		sortable: true,
		width: 70
	};

	var tildato = {
		dataIndex: 'tildato',
		header: 'Til dato',
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		sortable: true,
		width: 70
	};

	var framleiere = {
		dataIndex: 'framleiere',
		header: 'Framleie til',
		sortable: true,
		width: 180
	};


	var rutenett = new Ext.grid.GridPanel({
		autoExpandColumn: 2,
		buttons: [{
			text: 'Tilbake',
			handler: function(){
				window.location = "index.php";
			}
		}, {
			text: 'Legg inn ny framleieavtale',
			handler: function(){
				window.location = "index.php?oppslag=framleie_skjema&id=*";
			}
		}],
		store: datasett,
		columns: [
			fradato,
			tildato,
			kontraktnr,
			framleiere,
			endre,
			slett
		],
		stripeRows: true,
        height: 500,
        width: 900,
        title: 'Framleieavtaler'
    });

	// Rutenettet rendres in i HTML-merket '<div id="adresseliste">':
    rutenett.render('panel');

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

function oppgave($oppgave){
	switch ($oppgave) {
		case 'slett':
			$sql = "DELETE framleie, framleiepersoner\n"
				.	"FROM framleie LEFT JOIN framleiepersoner ON framleie.nr = framleiepersoner.kontraktnr\n"
				.	"WHERE framleie.nr = '" . $this->mysqli->real_escape_string($_POST['kontraktnr']) . "'\n";
			if(!$resultat['success'] = $this->mysqli->query($sql));
				$resultat['msg'] = $this->mysqli->error;
			echo json_encode($resultat);
			break;
	}
}


function hentData($data = "") {
	switch ($data) {
		default:
			$resultat = $this->arrayData($this->hoveddata);
			foreach($resultat['data'] as $linje=>$avtale){
				$personer = array();
				$sql =	"SELECT personid\n"
					.	"FROM framleiepersoner\n"
					.	"WHERE kontraktnr = '{$avtale['nr']}'";
				$framleiepersoner = $this->arrayData($sql);

				$navn = array();
				foreach($framleiepersoner['data'] as $framleieperson){
					$navn[] = $this->navn($framleieperson['personid'], 1);
				}
				$resultat['data'][$linje]['framleiere'] = $this->liste($navn);
				$resultat['data'][$linje]['kontraktbeskrivelse'] = $this->liste($this->kontraktpersoner($avtale['kontraktnr'])) . " i " . $this->leieobjekt($avtale['leieobjekt'], true);
			}
			return json_encode($resultat);
	}
}

}
?>