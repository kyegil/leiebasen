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
	$this->hoveddata = "SELECT *\n"
		.	"FROM oppsigelser\n"
		.	"ORDER BY fristillelsesdato DESC, oppsigelsesdato DESC";
	$this->tittel = "Oppsigelser";
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
		url:'index.php?oppdrag=hentdata&oppslag=oppsigelse_liste',
		fields: [
			{name: 'kontraktnr', type: 'float'},
			{name: 'leieforhold', type: 'float'},
			{name: 'navn'},
			{name: 'oppsigelsesdato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'fristillelsesdato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'oppsigelsestid_slutt', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'ref'},
			{name: 'merknad'},
			{name: 'oppsagt_av_utleier', type: 'float'}
		],
		root: 'data'
    });
    datasett.load();

	bekreftSletting = function(id) {
		Ext.Msg.show({
			title: 'Bekreft',
			id: id,
			msg: 'Er du sikker på at du vil slette oppsigelsen av leieforhold ' + id + ' og gjenopprette leieterminene?',
			buttons: Ext.Msg.OKCANCEL,
			fn: function(buttonId, text, opt){
				if(buttonId == 'ok') {
					utførSletting(opt.id);
				}
			},
			animEl: 'elId',
			icon: Ext.MessageBox.QUESTION
		});
	}


	utførSletting = function(id){
		Ext.Ajax.request({
			waitMsg: 'Sletter...',
			url: "index.php?oppslag=oppsigelse_liste&oppdrag=oppgave&oppgave=slettoppsigelse&id=" + id,
			success: function(response, options){
				var tilbakemelding = Ext.util.JSON.decode(response.responseText);
				if(tilbakemelding['success'] == true) {
					Ext.MessageBox.alert('Utført', tilbakemelding.msg, function(){
					datasett.load();
					});
				}
				else {
					Ext.MessageBox.alert('Hmm..',tilbakemelding['msg']);
				}
			}
		});
	}


	var kontraktnr = {
		dataIndex: 'kontraktnr',
		header: 'Leieavtale',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return  "<a title=\"Klikk på et avtalenummer her for å gå til leieavtalen\" href=\"index.php?oppslag=leieforholdkort&id=" + value + "\">" + value + "</a>" + ": " + record.data.navn;
		},
		sortable: true,
		width: 400
	};

	var oppsigelsesdato = {
		dataIndex: 'oppsigelsesdato',
		header: 'Levert',
		sortable: true,
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		width: 90
	};

	var fristillelsesdato = {
		dataIndex: 'fristillelsesdato',
		header: 'Ledig fra',
		sortable: true,
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		width: 90
	};

	var oppsigelsestid_slutt = {
		dataIndex: 'oppsigelsestid_slutt',
		header: 'Oppsigelsestid til',
		sortable: true,
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		width: 90
	};

	var ref = {
		dataIndex: 'ref',
		header: 'Ref',
		sortable: true,
		width: 50
	};

	var merknad = {
		dataIndex: 'merknad',
		header: 'Merknad',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return  "<div title=\"" + value + "\">" + value + "</div>";
		},
		sortable: true,
		width: 50
	};

	var oppsagt_av_utleier = {
		dataIndex: 'oppsagt_av_utleier',
		header: 'Sagt opp av utleier',
		sortable: true,
		renderer: Ext.util.Format.hake,
		width: 60
	};


	var slett = {
		dataIndex: 'leieforhold',
		header: 'Slett',
		renderer: function(v){
			return "<a style=\"cursor: pointer\" title=\"Slett oppsigelsen av leieforhold " + v + "\" onClick=\"bekreftSletting(" + v + ")\"><img src=\"../bilder/slett.png\" /></a>";
		},
		sortable: false,
		width: 30
	};

	var rutenett = new Ext.grid.GridPanel({
		autoExpandColumn: 0,
		store: datasett,
		columns: [
			kontraktnr,
			oppsagt_av_utleier,
			oppsigelsesdato,
			fristillelsesdato,
			oppsigelsestid_slutt,
			ref,
			merknad,
			slett
		],
		stripeRows: true,
		height: 500,
		width: 900,
		title: 'Oppsigelser',
		buttons: [{
			handler: function() {
				window.location = "index.php";
			},
			text: 'Tilbake'
		}]
	});

	// Rutenettet rendres in i HTML-merket '<div id="panel">':
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
			$resultat = $this->arrayData($this->hoveddata);
			foreach($resultat['data'] as $linje=>$verdi){
				$resultat['data'][$linje]['navn'] = $this->liste($this->kontraktpersoner($verdi['kontraktnr'])) . " i " . $this->leieobjekt($this->kontraktobjekt($verdi['kontraktnr']), true);
			}
			return json_encode($resultat);
	}
}

function oppgave($oppgave) {
	switch ($oppgave) {
		case "slettoppsigelse":
			$leieforhold = $this->hent('Leieforhold', (int)@$_GET['id']);
			if($resultat['success'] = $this->mysqli->query("DELETE FROM oppsigelser WHERE leieforhold = '{$leieforhold}'")) {
				$resultat['msg'] = "Oppsigelsen er slettet";
				$leieforhold->opprettLeiekrav();
			}
			else {
				$resultat['msg'] = "Klarte ikke slette. Feilmeldingen fra databasen lyder:<br />" . $this->mysqli->error;
			}
			echo json_encode($resultat);
			break;
	}
}

function taimotSkjema() {
	echo json_encode($resultat);
}

}
?>