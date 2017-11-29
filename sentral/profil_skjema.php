<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

function __construct() {
	parent::__construct();
	$this->ext_bibliotek = 'ext-4.2.1.883';
	$this->kontrollrutiner();
}

function skript() {
	if($_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	$tp = $this->mysqli->table_prefix;
?>
Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?=$this->http_host . "/" . $this->ext_bibliotek . "/examples/ux"?>');

Ext.require([
 	'Ext.data.*',
 	'Ext.form.field.*',
    'Ext.layout.container.Border',
 	'Ext.grid.*',
    'Ext.ux.RowExpander'
]);

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	var login = Ext.create('Ext.form.field.Text', {
		allowBlank: false,
		fieldLabel: 'Brukernavn',
 		labelAlign: 'top', // evt right
		name: 'login',
		width: 200
	});

	var epost = Ext.create('Ext.form.field.Text', {
		allowBlank: false,
		fieldLabel: 'E-postadresse',
 		labelAlign: 'top', // evt right
		name: 'epost',
		width: 200
	});

	var pw1 = Ext.create('Ext.form.field.Text', {
		fieldLabel: 'Nytt passord<br />(La feltet stå tomt dersom du ikke skal endre passordet)',
 		labelAlign: 'top', // evt right
		inputType: 'password',
		name: 'pw1',
		width: 200
	});

	var pw2 = Ext.create('Ext.form.field.Text', {
		fieldLabel: 'Gjenta det nye passordet for bekreftelse',
 		labelAlign: 'top', // evt right
		inputType: 'password',
		name: 'pw2',
		width: 200
	});

	var skjema = Ext.create('Ext.form.Panel', {
		renderTo: 'panel',
 		autoScroll: true,
 		bodyStyle:'padding:5px 5px 0',
 		buttons: [{
			text: 'Avbryt',
			handler: function() {
				window.location = '<?=$this->returi->get();?>';				
			}
		}, {
			text: 'Lagre endringer',
			handler: function() {
				skjema.form.submit({
					url:'index.php?oppslag=profil_skjema&oppdrag=taimotskjema',
					waitMsg:'Prøver å lagre...'
				});
			}
		}],
 		frame:true,
 		items: [login, epost, pw1, pw2],
 		formBind: true,
		title: 'Brukernavn og passord for nettjenester for <?=$this->bruker['navn'];?>',
		width: 900,
		height: 500
	});

	skjema.getForm().load({
		url:'index.php?oppslag=profil_skjema&oppdrag=hentdata',
		waitMsg:'Henter opplysninger...'
	});

	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Mottok ikke bekreftelsesmelding fra tjeneren  i JSON-format som forventet');
				} else {
					window.location = "index.php";
					Ext.MessageBox.alert('Suksess', 'Opplysningene er oppdatert');
				}
			}
		},
							
		actionfailed: function(form,action){
			if(action.type == 'load') {
				if (action.failureType == "connect") {
					Ext.MessageBox.alert('Problem:', 'Klarte ikke laste data. Fikk ikke kontakt med tjeneren.');
				}
				else {
					if (action.response.responseText == '') {
						Ext.MessageBox.alert('Problem:', 'Skjemaet mottok ikke data i JSON-format som forventet');
					}
					else {
						var result = Ext.decode(action.response.responseText);
						if(result && result.msg) {			
							Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', result.msg);
						}
						else {
							Ext.MessageBox.alert('Problem:', 'Innhenting av data mislyktes av ukjent grunn. (trolig manglende success-parameter i den returnerte datapakken). Action type='+action.type+', failure type='+action.failureType);
						}
					}
				}
			}
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
<div id="panel"></div><?
}

function taimotSkjema() {
	$resultat['success'] = false;

	$autoriserer = new $this->autoriserer;

	if($_POST['pw1'] and ($_POST['pw1'] != $_POST['pw2'])) {
		echo json_encode(array(
			'success'	=> false,
			'msg'		=> "Det nye passordet ble ikke bekreftet riktig. Prøv på nytt."
		));
		return;
	}
	
	if($_POST['pw1'] and !$autoriserer->cuLaPasvortoEstasValida($_POST['pw1'])) {
		echo json_encode(array(
			'success'	=> false,
			'msg'		=> "Passordet er ikke bra nok. Prøv på nytt."
		));
		return;
	}
	
	if($_POST['login'] and !$autoriserer->cuLaUzantonomoEstasDisponebla($_POST['login'], $this->bruker['id'])) {
		echo json_encode(array(
			'success'	=> false,
			'msg'		=> "Brukernavnet er allerede i bruk. Prøv på nytt."
		));
		return;
	}
	
	if($_POST['login'] and $_POST['epost']) {
		
		if($resultat['success'] = $autoriserer->aldonuUzanto(array(
			'id'			=> (int)$this->bruker['id'],
			'uzanto'		=> $_POST['login'],
			'nomo'			=> $this->bruker['navn'],
			'pasvorto'		=> $_POST['pw1'],
			'retpostadreso'	=> $_POST['epost']
		))) {
			$resultat['msg'] = "";
			$this->mysqli->query("
				UPDATE personer
				SET epost = '{$this->POST['epost']}'
				WHERE personid = '{$this->bruker['id']}'
			");
		}
		else{
			$resultat['msg'] = "Klarte ikke å lagre.";
		}
	}
	
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		case "adgangsliste":
			$sql = "SELECT adgang, leieforhold, adgangsid AS endre, adgangsid AS slett FROM adganger WHERE personid = '{$this->bruker['id']}'";
			return json_encode($this->arrayData($sql));
		default:
			$datasett = $this->mysqli->arrayData(array(
				'source'	=> "personer",
				'fields'	=> "personid AS id, epost, NULL AS pw1, NULL AS pw2",
				'where'		=> "personid = '{$this->bruker['id']}'"
			));
			
			if ($datasett->success) {
				$datasett->data = $datasett->data[0];
			}
			

			$autoriserer = new $this->autoriserer;
			$datasett->data->login = $autoriserer->trovuUzantoNomo($datasett->data->id);

			return json_encode($datasett);
	}
}

}
?>