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
	$this->hoveddata = "SELECT tekst FROM internmeldinger LIMIT 1";
}

function skript() {
	if($_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	var drift = new Ext.form.Checkbox({
		name: 'drift',
		boxLabel: 'Kryss av her for å dele meldinga med driftsgruppa / kontoret. Hold boksen tom for bare å legge meldinga som notat for flyko.',
		fieldLabel: 'Del meldinga med Drift',
		hideLabel: true,
		inputValue: "1",
		width: 700
	});

	var tekst = new Ext.form.HtmlEditor({
		hideLabel: true,
		name: 'tekst',
		height: 400,
		width: 870
	});

	var skjema = new Ext.FormPanel({
		autoScroll: true,
		bodyStyle:'padding:5px 5px 0',
		frame: true,
		height: 500,
		items: [drift, tekst],
		labelAlign: 'top', // evt right
		standardSubmit: false,
		title: 'Skriv ny intern melding',
		width: 900
	});

	
	skjema.addButton('Avbryt', function(){
		window.location = '<?=$this->returi->get();?>';
	});
	
	var lagreknapp = skjema.addButton({
		text: 'Post melding',
		handler: function(){
			skjema.form.submit({
				url:'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET["id"]}";?>&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...'
				});
		}
	});

	skjema.render('panel');

	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Mottok ikke bekreftelsesmelding fra tjeneren  i JSON-format som forventet');
				} else {
					window.location = '<?=$this->returi->get();?>';
					Ext.MessageBox.alert('Suksess', 'Meldingen er lagret');
				}
			}
		},
							
		actionfailed: function(form,action){
			if(action.type == 'submit') {
				if (action.failureType == "connect") {
					Ext.MessageBox.alert('Problem:', 'Klarte ikke lagre data. Fikk ikke kontakt med tjeneren.');
				}
				else {	
					var result = Ext.decode(action.response.responseText);
					if(result && result.msg) {			
						Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', result.msg);
					}
					else {
						Ext.MessageBox.alert('Problem:', 'Lagring av data mislyktes av ukjent grunn. Action type='+action.type+', failure type='+action.failureType);
					}
				}
			}
			
		} // end actionfailed listener
	}); // end skjema.on

});
<?
}

function design() {
?>
<div id="panel"></div>
<?
}

function taimotSkjema() {
	$sql =	"INSERT INTO internmeldinger\n"
		.	"SET tekst = '{$this->POST['tekst']}',\n"
		.	"avsender = '{$this->bruker['id']}',\n"
		.	"flyko = TRUE,\n"
		.	"drift = " . ($_POST['drift'] ? "TRUE" : "FALSE");
	
	if($resultat['success'] = $this->mysqli->query($sql)) {
		$resultat['msg'] = "";
	}
	else {
		$resultat['msg'] = "KLarte ikke å lagre. Feilmeldingen fra databasen lyder:<br />" . $this->mysqli->error;
	}
	
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		default:
			return json_encode($this->arrayData($this->hoveddata));
	}
}

}
?>