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
	$this->hoveddata =	"SELECT adgangsid, personid, adgang, leieforhold, epostvarsling, innbetalingsbekreftelse, forfallsvarsel\n"
		.	"FROM adganger\n"
		.	"ORDER BY adgang DESC, adgangsid DESC\n";
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
		url:'index.php?oppdrag=hentdata&oppslag=adgang_liste',
		fields: [
			{name: 'adgangsid', type:'float'},
			{name: 'personid', type:'float'},
			{name: 'adgang'},
			{name: 'leieforhold', type:'float'},
			{name: 'leieforholdbesk'},
			{name: 'epostvarsling', type:'float'},
			{name: 'innbetalingsbekreftelse', type:'float'},
			{name: 'forfallsvarsel', type:'float'},
			{name: 'navn'}
		],
		root: 'data'
    });
    datasett.load();

	bekreftSletting = function(personid, navn, område, leieforhold, leieforholdbesk) {
		Ext.Msg.show({
			title: 'Bekreft',
			id: personid,
			msg: 'Er du sikker på at du vil slette <b>' + navn + 's</b> adgang til <b>' + område + (område == 'beboersider' ? (' for leieforhold ' + leieforhold + ': ' + leieforholdbesk) : '') + '</b>?',
			buttons: Ext.Msg.OKCANCEL,
			fn: function(buttonId, text, opt){
				if(buttonId == 'ok') {
					utførSletting(personid, område, leieforhold);
				}
			},
			animEl: 'elId',
			icon: Ext.MessageBox.QUESTION
		});
	}


	utførSletting = function(personid, område, leieforhold){
		Ext.Ajax.request({
			waitMsg: 'Sletter...',
			url: "index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=oppgave&oppgave=slett&personid=" + personid + "&adgang=" + område + (leieforhold != 0 ? ("&leieforhold=" + leieforhold) : ""),
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


	var navnevindu = new Ext.Window({
		title: 'Opprett adgang',
		autoLoad: 'index.php?oppdrag=hentdata&oppslag=adgang_liste&data=navneliste',
		width: 500,
		height: 400,
		autoScroll: true,
		animCollapse: true,
		closeAction: 'hide'
	});

	var adgangsid = {
		dataIndex: 'adgangsid',
		header: 'Nr',
		hidden: false,
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return value ? value : null;
		},
		sortable: true,
		width: 60
	};

	var personid = {
		dataIndex: 'personid',
		header: 'Nr.',
		hidden: true,
		sortable: true,
		width: 50
	};

	var navn = {
		dataIndex: 'navn',
		header: 'Hvem',
		sortable: true,
		width: 170
	};

	var adgang = {
		dataIndex: 'adgang',
		header: 'Adgang',
		sortable: true,
		width: 100
	};

	var leieforhold = {
		dataIndex: 'leieforhold',
		header: 'Leieforhold en har adgang til i beboersidene',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return value ? (value + ": " + record.data.leieforholdbesk) : null;
		},
		sortable: true,
		width: 100
	};

	var epostvarsling = {
		dataIndex: 'epostvarsling',
		header: 'epost-<br />varsling',
		renderer: Ext.util.Format.hake,
		sortable: true,
		width: 50
	};

	var innbetalingsbekreftelse = {
		dataIndex: 'innbetalingsbekreftelse',
		header: 'innbetalings-<br />bekreftelse',
		renderer: Ext.util.Format.hake,
		sortable: true,
		width: 70
	};

	var forfallsvarsel = {
		dataIndex: 'forfallsvarsel',
		header: 'forfalls-<br />varsel',
		renderer: Ext.util.Format.hake,
		sortable: true,
		width: 50
	};

	var endre = {
		dataIndex: 'adgangsid',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			if(!value) value = '*';
			return "<a title=\"Endre detaljene i denne adgangen\" href=\"index.php?oppslag=adgang_skjema&personid=" + record.data.personid + "&id=" + value + "\"><img src=\"../bilder/rediger.png\" /></a>";
		},
		sortable: false,
		width: 30
	};

	var slett = {
		dataIndex: 'adgangsid',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return "<a style=\"cursor: pointer\" title=\"Slett adgangen\" onClick=\"bekreftSletting(" + record.data.personid + ", '" + record.data.navn + "', '" + record.data.adgang + "', '" + record.data.leieforhold + "', '" + record.data.leieforholdbesk + "')\"><img src=\"../bilder/slett.png\" /></a>";
		},
		sortable: false,
		width: 30
	};

	var rutenett = new Ext.grid.GridPanel({
		autoScroll: true,
		autoExpandColumn: 4,
		store: datasett,
		columns: [
			adgangsid,
			personid,
			navn,
			adgang,
			leieforhold,
			epostvarsling,
			innbetalingsbekreftelse,
			forfallsvarsel,
			endre,
			slett
		],
		stripeRows: true,
        height: 500,
        width: 900,
		buttons: [{
			text: 'Tildel ny adgang',
			handler: function() {
				navnevindu.show();
			}
		}],
        title: 'Brukere og adganger'
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
		case "navneliste":
			$navneliste = $this->arrayData("SELECT * FROM personer ORDER BY etternavn, fornavn");
			echo "<p>Klikk på den som skal ha adgang.<br />Om du ikke finner personen som skal ha adgang i listen under må du først registrere ham/henne ved å klikke <a href=\"index.php?oppslag=personadresser_skjema&id=*\">her</a><br /><br /></p><p>";
			foreach($navneliste['data'] as $person){
				echo "<a href=\"index.php?oppslag=adgang_skjema&personid={$person['personid']}&id=*\">" . ($person['er_org'] ? "{$person['etternavn']}<br /></a>" : "{$person['fornavn']} {$person['etternavn']}<br /></a>");
			}
			echo "</p>";
			break;
		default:
			$resultat = $this->arrayData($this->hoveddata);
			foreach($resultat['data'] as &$adgang){
				$adgang['navn'] = $this->navn($adgang['personid']);
				$adgang['leieforholdbesk'] = $adgang['leieforhold'] ? ($this->liste($this->kontraktpersoner($this->sistekontrakt($adgang['leieforhold']))) . " i " . $this->leieobjekt($this->kontraktobjekt($adgang['leieforhold']))) : "";
			}
			$resultat['sql'] = $this->hoveddata;
			return json_encode($resultat);
	}
}

function taimotSkjema() {
	echo json_encode($resultat);
}

function oppgave($oppgave) {
	switch ($oppgave) {
		case "slett":
			$resultat = (object)array(
				'success'	=> false,
				'msg'		=> ""
			);
			
			if($resultat->success = $this->mysqli->query(
				"DELETE FROM adganger
				WHERE personid = '{$this->GET['personid']}'
					AND adgang = '{$this->GET['adgang']}'"
					. ($this->GET['adgang'] == 'beboersider'
					? (" AND leieforhold = '{$this->GET['leieforhold']}'")
					: (""))
			)) {
				$resultat->msg = "Adgangen er slettet";
			}
			else {
				$resultat->msg = "Klarte ikke slette. Feilmeldingen fra databasen lyder:<br />"
				. $this->mysqli->error;
			}
			echo json_encode($resultat);
			break;
	}
}

}
?>