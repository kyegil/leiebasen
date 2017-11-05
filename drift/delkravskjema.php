<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

if(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'leiebasen';
public $ext_bibliotek = 'ext-4.2.1.883';
	

function __construct() {
	parent::__construct();
}

function skript() {
	if(@$_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	$tp = $this->mysqli->table_prefix;
?>
Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?=$this->http_host . "/" . $this->ext_bibliotek . "/examples/ux"?>');

Ext.require([
 	'Ext.data.*',
 	'Ext.form.*'
]);

Ext.onReady(function() {

    Ext.tip.QuickTipManager.init();
	Ext.form.Field.prototype.msgTarget = 'side';
	Ext.Loader.setConfig({enabled:true});
	
<?
	include_once("_menyskript.php");
?>

	bekreftDeaktivering = function(id) {
		Ext.Msg.show({
			title: 'Bekreft',
			id: id,
			msg: 'Er du sikker på at du vil deaktivere dette delkravet?',
			buttons: Ext.Msg.OKCANCEL,
			fn: function(buttonId, text, opt){
				if(buttonId == 'ok') {
					utførDeaktivering(opt.id);
				}
			},
			animEl: 'elId',
			icon: Ext.MessageBox.QUESTION
		});
	}


	utførDeaktivering = function(id){
		Ext.Ajax.request({
			waitMsg: 'Deaktiverer...',
			url: "index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=oppgave&oppgave=deaktiver&id=<?=$_GET['id']?>",
			success: function(response, options){
				var tilbakemelding = Ext.JSON.decode(response.responseText);
				if(tilbakemelding['success'] == true) {
					Ext.MessageBox.alert('Utført', tilbakemelding.msg, function(){
						window.location = '<?=$this->returi->get();?>';
					});
				}
				else {
					Ext.MessageBox.alert('Hmm..',tilbakemelding['msg']);
				}
			}
		});
	}


	var aktiv = Ext.create('Ext.form.field.Checkbox', {
		boxLabel: 'Aktiver/deaktiver dette delkravet',
		fieldLabel: 'Aktiv',
		inputValue: 1,
		name: 'aktiv',
		tabIndex: 1
	});
	 
	var id = Ext.create('Ext.form.field.Display', {
		hidden: true,
		fieldLabel: 'ID',
		name: 'id'
	});
	 
<?if((int)$_GET["id"]):?>
	var kravtype = Ext.create('Ext.form.field.Display', {
		fieldLabel: 'Tilhører krav av type',
		name: 'kravtype'
	});
<?else:?>
	var kravtype = Ext.create('Ext.form.field.ComboBox', {
		allowBlank: false,
		fieldLabel: 'Tilhører krav av type',
		forceSelection: true,
		name: 'kravtype',
		queryMode: 'local',
		store: Ext.create('Ext.data.Store', {
			fields: ['text'],
			data : [
				{'text': "Husleie"},
				{'text': "Fellesstrøm"},
				{'text': "Purregebyr"},
				{'text': "Annet"}
			]
			}),
		tabIndex: 2
	});
<?endif;?>
	 
	var kode = Ext.create('Ext.form.field.Text', {
		fieldLabel: 'Kode',
		name: 'kode',
		tabIndex: 3
	});
	 
	var navn = Ext.create('Ext.form.field.Text', {
		fieldLabel: 'Navn',
		name: 'navn',
		tabIndex: 4
	});
	 
	var beskrivelse = Ext.create('Ext.form.field.TextArea', {
		fieldLabel: 'Beskrivelse',
		name: 'beskrivelse',
		tabIndex: 5
	});
	 
	var valgfritt = Ext.create('Ext.form.field.Checkbox', {
		boxLabel: 'Leietakerne kan selv velge om de vil betale dette delkravet',
		fieldLabel: 'Valgfritt',
		inputValue: 1,
		uncheckedValue: 0,
		name: 'valgfritt',
		tabIndex: 6
	});
	 
	var relativ = Ext.create('Ext.form.field.Checkbox', {
		boxLabel: 'Delkravbeløpet oppgis i prosenter av hovedbeløpet',
		fieldLabel: 'Relativt beløp',
		inputValue: 1,
		uncheckedValue: 0,
 		listeners: {
 			change: function( box, newValue, oldValue, eOpts ) {
 				if(newValue) {
 					sats.setFieldLabel('Prosentsats som legges på kravbeløpet');
 				}
 				else {
 					sats.setFieldLabel('(Foreslått) beløp i kroner (per år)');
 				}
 			}
 		},
		name: 'relativ',
		tabIndex: 7
	});
	 
	var sats = Ext.create('Ext.form.field.Number', {
		allowBlank: false,
		allowDecimals: false,
		allowNegative: false,
		hideTrigger: true,
		fieldLabel: 'Beløp per år',
		name: 'sats',
		tabIndex: 8
	});
	 
	var tillegg = Ext.create('Ext.form.field.Checkbox', {
		boxLabel: 'Ved å klikke her vil ikke beløpet inngå som et element i hovedkravet,<br />men kreves inn separat som et selvstendig tilleggskrav',
		fieldLabel: 'Selvstendig tillegg',
		inputValue: 1,
		uncheckedValue: 0,
		name: 'selvstendig_tillegg',
		tabIndex: 9
	});
	 
	var lagreknapp = Ext.create('Ext.Button', {
		text: 'Lagre endringer',
		disabled: true,
		handler: function() {
			skjema.form.submit({
				url:'index.php?oppslag=<?=$_GET["oppslag"];?>&id=<?=$_GET["id"];?>&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...'
				});
		}
	});

	var skjema = Ext.create('Ext.form.Panel', {
		autoScroll: true,
		bodyPadding: 5,
		fieldDefaults: {
				labelWidth: 150,
				width: 600
		},
		items: [
			aktiv,
			kravtype,
			id,
			kode,
			navn,
			beskrivelse,
			valgfritt,
			relativ,
			sats,
			tillegg
		],
		frame: true,
		title: '<?=(($_GET['id']== '*') ? "Nytt delkrav" : "Delkrav {$_GET['id']}")?>',
		renderTo: 'panel',
		height: 500,
		width: 900,
		buttons: [{
			text: 'Tilbake',
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
			}
		},
		lagreknapp
		]
	});

	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'load'){
				lagreknapp.enable();

 				if(relativ.getValue()) {
 					sats.setFieldLabel('Prosentsats som legges på kravbeløpet');
 				}
 				else {
 					sats.setFieldLabel('Beløp i kroner (per år)');
 				}
			} 
			
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Det kom en blank respons fra tjeneren.');
				} else {
					Ext.MessageBox.alert('Suksess', 'Opplysningene er oppdatert');
				window.location = '<?=$this->returi->get();?>';
				}
			}
		},
							
		actionfailed: function(form,action) {
			if(action.type == 'load') {
				if (action.failureType == "connect") { 
					Ext.MessageBox.alert('Problem:', 'Klarte ikke laste data. Fikk ikke kontakt med tjeneren.');
				}
				else {
					if (action.response.responseText == '') {
						Ext.MessageBox.alert('Problem:', 'Skjemaet mottok ikke data i JSON-format som forventet');
					}
					else {
						var result = Ext.JSON.decode(action.response.responseText);
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
					var result = Ext.JSON.decode(action.response.responseText); 
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


	skjema.getForm().load({
		url: 'index.php?oppslag=<?=$_GET['oppslag']?>&id=<?=$_GET['id'];?>&oppdrag=hentdata',
		waitMsg: 'Henter opplysninger...'
	});


});
<?
}


function design() {
?>
<div id="panel"></div>
<?
}

function hentData($data = "") {
	$id = (int)$_GET['id'];
	switch ($data) {
		case "navneliste":
			break;
		default:
			$resultat = $this->mysqli->arrayData(array(
				'source' => "delkravtyper",
				'fields' =>	"delkravtyper.*, IF(relativ, sats * 100, sats) AS sats",
				'where' => "id = '$id'"
			));
			$resultat->data = $resultat->data[0];
			if($_GET['id'] == '*') {
				$resultat->data = new stdclass;
			}
			echo json_encode($resultat);
	}
}

function taimotSkjema($skjema) {
	switch ($skjema) {
		default:
			if((!$id = (int)$_GET['id']) and ($_GET['id'] != '*')) {
				die(json_encode((object) array(
					'success' => false,
					'msg'	=> "ID-nummer mangler."
				)));
			}
			
			$fields = array(
				'navn'			=> $_POST['navn'],
				'kode'			=> $_POST['kode'],
				'beskrivelse'	=> $_POST['beskrivelse'],
				'sats'			=> isset($_POST['relativ'])
								?	bcdiv($_POST['sats'], 100, 3)
								:	$_POST['sats'],
				'valgfritt'		=> isset($_POST['valgfritt']) ? $_POST['valgfritt'] : 0,
				'relativ'		=> isset($_POST['relativ']) ? $_POST['relativ'] : 0,
				'selvstendig_tillegg' => isset($_POST['selvstendig_tillegg']) ? $_POST['selvstendig_tillegg'] : 0,
				'aktiv'			=> isset($_POST['aktiv']) ? $_POST['aktiv'] : 0
			);
			if(!id) {
				$fields['kravtype']	= $_POST['kravtype'];
			}
			$fields['sats'] =	$fields['relativ']
								?	bcdiv($_POST['sats'], 100, 3)
								:	$_POST['sats'];
			
			$resultat = $this->mysqli->saveToDb(array(
				'table'		=> "delkravtyper",
				'id'		=> $id,
				'fields'	=> $fields,
				'where'		=> ($id ? "delkravtyper.id = '{$id}'" : null),
				'update'	=> ($id ? true : false),
				'insert'	=> ($id ? false : true),
				'returnQuery'	=> true
			));
			
			$id = $resultat->id;
			
			echo json_encode($resultat);
			break;
	}
}

function oppgave($oppgave) {
	switch ($oppgave) {
		case "deaktiver":
			echo json_encode($resultat);
			break;
		default:
			break;
	}
}

}
?>