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
	
	$this->hoveddata = "SELECT DISTINCT framleie.*, kontrakter.leieobjekt\n"
		.	"FROM framleie INNER JOIN kontrakter ON framleie.leieforhold = kontrakter.leieforhold\n"
		.	"ORDER BY framleie.tildato\n";

	$this->tittel = "Framleie";
}

function skript() {
	$this->returi->reset();
	$this->returi->set();
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
			{name: 'leieforhold'},
			{name: 'kontraktbeskrivelse'},
			{name: 'framleiere'},
			{name: 'fradato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'tildato', type: 'date', dateFormat: 'Y-m-d'}
		],
		root: 'data'
    });
    datasett.load();

	strykFramleie = function( framleieforhold ) {
		Ext.Ajax.request({
			waitMsg: 'Vent...',
			params: {
				'framleieforhold': framleieforhold
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

	var leieforhold = {
		dataIndex: 'leieforhold',
		header: 'Leieavtale',
		renderer: function( value, metaData, record, rowIndex, colIndex, store ) {
			return record.data.leieforhold ? ("<a href=\"index.php?oppslag=leieforholdkort&id=" + record.data.leieforhold + "\">" + record.data.leieforhold + ": " + record.data.kontraktbeskrivelse + "</a>") : null;
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
			text: 'Legg inn ny framleieavtale',
			handler: function() {
				window.location = "index.php?oppslag=framleie_skjema&id=*";
			}
		}],
		store: datasett,
		columns: [
			fradato,
			tildato,
			leieforhold,
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


function hentData($data = "") {
	switch ($data) {
		default:
			$resultat = $this->mysqli->arrayData(array(
				'distinct'		=> true,
				'source'		=> "framleie LEFT JOIN kontrakter ON framleie.leieforhold = kontrakter.leieforhold",
				'fields'		=> "framleie.*, kontrakter.leieobjekt",
				'orderfields'	=> "framleie.tildato"
			));

			foreach($resultat->data as $avtale){
				$personer = array();

				$framleiepersoner = $this->mysqli->arrayData(array(
					'source'	=> "framleiepersoner",
					'fields'	=> "personid",
					'where'		=> "framleieforhold = '{$avtale->nr}'"
				));

				$navn = array();
				foreach($framleiepersoner->data as $framleieperson){
					$navn[] = $this->navn($framleieperson->personid);
				}
				$avtale->framleiere = $this->liste($navn);
				$avtale->kontraktbeskrivelse = $this->liste($this->kontraktpersoner($avtale->leieforhold)) . " i " . $this->leieobjekt($avtale->leieobjekt, true);
			}
			return json_encode($resultat);
	}
}


function taimotSkjema() {
	echo json_encode($resultat);
}


function oppgave($oppgave){
	switch ($oppgave) {
		case 'slett':
			$framleieforhold = (int)@$_POST['framleieforhold'];
			$sql = "DELETE framleie, framleiepersoner\n"
				.	"FROM framleie LEFT JOIN framleiepersoner ON framleie.nr = framleiepersoner.framleieforhold\n"
				.	"WHERE framleie.nr = '{$framleieforhold}'\n";
			if(!$resultat['success'] = $this->mysqli->query($sql));
				$resultat['msg'] = $this->mysqli->error;
			echo json_encode($resultat);
			break;
	}
}

}
?>