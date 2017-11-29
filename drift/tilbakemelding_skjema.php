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
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	var melding = new Ext.form.TextArea({
		allowBlank: false,
		fieldLabel: 'Melding',
		height: 360,
		name: 'melding',
		width: 800
	});

	var skjema = new Ext.FormPanel({
		autoScroll: true,
		bodyStyle:'padding:5px 5px 0',
		buttons: [],
		frame:true,
		height: 500,
		items: [melding],
		labelAlign: 'top', // evt right
		standardSubmit: false,
		title: 'Send feilmelding eller forbedringsforslag',
		width: 900
	});

	var lagreknapp = skjema.addButton({
		text: 'Send',
		disabled: false,
		handler: function(){
			skjema.form.submit({
				url:'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET["id"]}";?>&oppdrag=taimotskjema',
				waitMsg:'Meldingen sendes...'
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
					window.location = "index.php";
					Ext.MessageBox.alert('Suksess', 'Takk for din tilbakemelding');
				}
			}
		},
							
		actionfailed: function(form,action){
			if(action.type == 'submit') {
				if (action.failureType == "connect") {
					Ext.MessageBox.alert('Problem:', 'Klarte ikke sende. Fikk ikke kontakt med tjeneren.');
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
	$resultat['success'] = $this->sendMail(array(
		'to' => "kyegil@gmail.com",
		'subject' => "Melding via leiebasen",
		'html' => $this->POST['melding'],
		'from' => "{$this->bruker['navn']}<{$this->bruker['epost']}>"
	));
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		default:
			$resultat = "";
			return json_encode($resultat);
	}
}

}
?>