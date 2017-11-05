<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
Denne fila ble sist oppdatert 2016-02-01
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'leiebasen';
public $ext_bibliotek = 'ext-4.2.1.883';
	

function __construct() {
	parent::__construct();
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
 	'Ext.form.*'
]);

Ext.onReady(function() {

    Ext.tip.QuickTipManager.init();
	Ext.form.Field.prototype.msgTarget = 'side';
	Ext.Loader.setConfig({enabled:true});
	
<?
	include_once("_menyskript.php");
?>

	bekreftSletting = function(id) {
		Ext.Msg.show({
			title: 'Bekreft',
			id: id,
			msg: 'Er du sikker på at du vil slette bygningen?',
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
			url: "index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=oppgave&oppgave=slett&id=<?=$_GET['id']?>",
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


	var id = Ext.create('Ext.form.field.Display', {
		hidden: true,
		fieldLabel: 'ID',
		name: 'id',
		labelWidth: 120
	});
	 
	var kode = Ext.create('Ext.form.field.Text', {
		fieldLabel: 'Kode',
		labelWidth: 120,
		name: 'kode',
		tabIndex: 1
	});
	 
	var navn = Ext.create('Ext.form.field.Text', {
		fieldLabel: 'Navn / Beskrivelse',
		labelWidth: 120,
		name: 'navn',
		tabIndex: 2
	});
	 
	var bildefelt = Ext.create('Ext.form.field.File', {
		fieldLabel: 'Last opp bilde',
		name: 'bildefelt',
		labelWidth: 120,
		tabIndex: 3
	});
	
	var bildeverdi = Ext.create('Ext.form.field.Hidden', {
		name: 'bilde'
	});
	 
	var bildevisning = Ext.create('Ext.Img', {
		height: 350,
		maxWidth: 350
	});

	var columns = [{
			xtype: 'container',
			flex: 1,
			items: [
				id,
				kode,
				navn,
				bildefelt,
				bildeverdi
		]
		}, {
			xtype: 'container',
			width: 350,
			items: [bildevisning]
		}];
	
	var lagreknapp = Ext.create('Ext.Button', {
		text: 'Lagre endringer',
		disabled: true,
		handler: function() {
			skjema.form.submit({
				url: 'index.php?oppslag=<?=$_GET["oppslag"];?>&id=<?=$_GET["id"];?>&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...'
				});
		}
	});

	var skjema = Ext.create('Ext.form.Panel', {
		autoScroll: true,
		bodyPadding: 5,
		defaults: {
			width: 600
		},
		items: columns,
		layout: 'hbox',
		frame: true,
		title: '<?=(($_GET['id']== '*') ? "Ny bygning" : "Bygning nr {$_GET['id']}")?>',
		renderTo: 'panel',
		height: 500,
		width: 900,
		buttons: [
			{
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
				bildevisning.setSrc(bildeverdi.getValue());
				lagreknapp.enable();
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
						Ext.MessageBox.alert('Problem:', 'Lagring av data mislyktes av ukjent grunn.');
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
				'source' => "bygninger",
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
			
			$resultat = $this->mysqli->saveToDb(array(
				'table'		=> "bygninger",
				'id'		=> $id,
				'fields'	=> array(
					'kode'	=> ($_POST['kode'] ? $_POST['kode'] : null),
					'navn'	=> $_POST['navn']
				),
				'where'		=> ($id ? "bygninger.id = '{$id}'" : null),
				'update'	=> ($id ? true : false),
				'insert'	=> ($id ? false : true),
				'returnQuery'	=> true
			));
			
			$id = $resultat->id;
			
			$bilde = $_FILES['bildefelt'];
			if($bilde['name'] and $resultat->success) {
				$sti = "../media/bygninger/$id/";
				$filnavn = $sti . basename($bilde["name"]);
				$filendelse = pathinfo($filnavn, PATHINFO_EXTENSION);
				$sjekk = getimagesize($bilde["tmp_name"]);
	
				if($bilde['error']) {
					die(json_encode((object) array(
						'success' => false,
						'msg'	=> "Det skjedde en feil under opplastingen, eller den ble avbrutt."
					)));			
				}
	
				if($sjekk == false) {
					die(json_encode((object) array(
						'success' => false,
						'msg'	=> "Den opplastede fila er ikke et bilde."
					)));			
				}
	
				if(
					$filendelse != "jpg" && $filendelse != "JPG"
					&& $filendelse != "jpeg" && $filendelse != "JPEG"
					&& $filendelse != "png" && $filendelse != "PNG"
					&& $filendelse != "gif" && $filendelse != "GIF") {
					die(json_encode((object) array(
						'success' => false,
						'msg'	=> "Det opplastede bildet må være av typen JPG/JPEG, PNG eller GIF."
					)));
				} 
	
				if ($bilde["size"] > 1000000) {
					die(json_encode((object) array(
						'success' => false,
						'msg'	=> "Bildet er for stort. Maks. filstørrelse er 1Mb."
					)));
				}
	
				if (!file_exists($sti)) {
					if(!mkdir($sti)) {
						die(json_encode((object) array(
							'success' => false,
							'msg'	=> "Klarte ikke finne eller opprette filplasseringen '{$sti}'."
						)));
					}
				}
				
				$eksisterendeBilde = $this->mysqli->arrayData(array(
					'source' => "bygninger",
					'fields' => "bilde",
					'where' => "bygninger.id = '$id'"
				))->data[0]->bilde;
				
				if(file_exists($eksisterendeBilde)) {
					unlink($eksisterendeBilde);
				}
					
				if (!move_uploaded_file($bilde["tmp_name"], $filnavn)) {
					die(json_encode((object) array(
						'success' => false,
						'msg'	=> "Bildet kunne ikke lagres som '{$filnavn}' pga ukjent feil."
					)));
				}

				if($resultat->success) {
					$resultat = $this->mysqli->saveToDb(array(
						'returnQuery'	=> true,
						'table'	=> "bygninger",
						'update' => true,
						'where'	=> "bygninger.id = '$id'",
						'fields' => array(
							'bilde' => $filnavn
						)
					));
				}
			}
			
			echo json_encode($resultat);
			break;
	}
}

function oppgave($oppgave) {
	switch ($oppgave) {
		case "slett":
			echo json_encode($resultat);
			break;
		default:
			break;
	}
}

}
?>