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
	
	if(!$id = $_GET['id']) die("Ugyldig oppslag: ID ikke angitt");
	
	$this->hoveddata = "SELECT personid AS id, epost, NULL AS pw1, NULL AS pw2\n"
		.	"FROM personer\n"
		.	"WHERE personid = '$id'";
}

function skript() {
	if( isset( $_GET['returi'] ) && $_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	$datasett = $this->arrayData($this->hoveddata);
	$datasett = $datasett['data'][0];
	$id = "id";
	
	$autoriserer = new $this->autoriserer;
	$datasett['login'] = $autoriserer->trovuUzantoNomo($datasett['id']);
		
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	function slettOmråde(v){
		Ext.Ajax.request({
			waitMsg: 'Prøver å slette adgang...',
			url: 'index.php?oppslag=adgang_skjema&oppdrag=oppgave&oppgave=slett&personid=<?=$_GET['id']?>&id=' + v,
			failure:function(response,options){
				Ext.MessageBox.alert('Mislykket...','Klarte ikke å slette adgangen. Prøv igjen senere.');
			},
			success:function(response,options){
				var tilbakemelding = Ext.util.JSON.decode(response.responseText);
				if(tilbakemelding['success'] == true) {
					Ext.MessageBox.alert('Utført', tilbakemelding.msg);
				}
				else {
					Ext.MessageBox.alert('Hmm..',tilbakemelding['msg']);
				}
			}
		});
	}

	var login = new Ext.form.TextField({
		allowBlank: false,
		fieldLabel: 'Brukernavn',
		name: 'login',
		width: 200
	});

	var epost = new Ext.form.TextField({
		allowBlank: false,
		fieldLabel: 'E-postadresse',
		name: 'epost',
		width: 200
	});

	var pw1 = new Ext.form.TextField({
		fieldLabel: 'Nytt passord<br />(La feltet stå tomt dersom du ikke skal endre passordet)',
		inputType: 'password',
		name: 'pw1',
		width: 200
	});

	var pw2 = new Ext.form.TextField({
		fieldLabel: 'Gjenta det nye passordet for bekreftelse',
		inputType: 'password',
		name: 'pw2',
		width: 200
	});

	var datasett = new Ext.data.JsonStore({
		url: 'index.php?oppdrag=hentdata&oppslag=profil_skjema&data=adgangsliste&id=<?=$_GET['id']?>',
		fields: [
			{name: 'adgang'},
			{name: 'leieforhold'},
			{name: 'endre'},
			{name: 'slett'}
		],
		root: 'data'
    });
    datasett.load();

	var adgang = {
		dataIndex: 'adgang',
		header: 'Adgangsområde',
		sortable: true
	};

	var leieforhold = {
		align: 'right',
		dataIndex: 'leieforhold',
		header: 'Leieforhold',
		sortable: true,
		width: 60
	};

	var endre = {
		dataIndex: 'endre',
		renderer: function(v){
			return "<a href=\"index.php?oppslag=adgang_skjema&personid=<?=$_GET['id']?>&id=" + v + "\"><img src=\"../bilder/rediger.png\" /></a>";
		},
		sortable: false,
		width: 30
	};

	var slett = {
		dataIndex: 'slett',
		renderer: function(v){
			return "<a href=\"index.php?oppslag=adgang_skjema&oppdrag=oppgave&oppgave=slett&personid=<?=$_GET['id']?>&id=" + v + "\"><img src=\"../bilder/slett.png\" /></a>";
		},
		sortable: false,
		width: 30
	};


	var rutenett = new Ext.grid.GridPanel({
		autoExpandColumn: 0,
		autoScroll: true,
		buttons: [{
			text: 'Nytt område',
			handler: function(){
		window.location = "index.php?oppslag=adgang_skjema&personid=<?=$_GET['id']?>&id=*";
			}
		}],
		columns: [
			adgang,
			leieforhold,
			endre,
			slett		
		],
        height: 200,
		store: datasett,
		stripeRows: true,
        title:'',
        width: 300
    });

	var skjema = new Ext.FormPanel({
		autoScroll: true,
		bodyStyle:'padding:5px 5px 0',
		buttons: [],
		frame:true,
		items: [login, epost, pw1, pw2, rutenett],
		labelAlign: 'top', // evt right
		reader: new Ext.data.JsonReader({
			defaultType: 'textfield',
			fields: [id, login, epost, pw1, pw2],
			root: 'data'
		}),
		standardSubmit: false,
		title: 'Brukernavn og passord for nettjenester for <?=$this->navn($_GET['id']);?>',
		width: 900
	});

	skjema.addButton('Avbryt', function(){
		window.location = '<?=$this->returi->get();?>';				
	});

	
	var lagreknapp = skjema.addButton({
		text: 'Lagre endringer',
		disabled: true,
		handler: function(){
			skjema.form.submit({
				url:'index.php?oppslag=profil_skjema&id=<?=$_GET["id"];?>&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...'
				});
		}
	});

	skjema.render('panel');

	skjema.getForm().load({
		url:'index.php?oppslag=profil_skjema&id=<?=$_GET["id"];?>&oppdrag=hentdata', waitMsg:'Henter opplysninger...'
	});

	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'load'){
				lagreknapp.enable();
			} 
			
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Mottok ikke bekreftelsesmelding fra tjeneren  i JSON-format som forventet');
				} else {
					window.location = "index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET["id"]}";?>";
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

	if($_POST['pw1'] and $_POST['pw1'] != $_POST['pw2']) {
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
	
	if($_POST['login'] and !$autoriserer->cuLaUzantonomoEstasDisponebla($_POST['login'], $_GET['id'])) {
		echo json_encode(array(
			'success'	=> false,
			'msg'		=> "Brukernavnet er allerede i bruk. Prøv på nytt."
		));
		return;
	}
	
	if($_POST['login'] and $_POST['epost']) {
		
		if($resultat['success'] = $autoriserer->aldonuUzanto(array(
			'id'			=> (int)$_GET['id'],
			'uzanto'		=> $_POST['login'],
			'nomo'			=> $this->navn($_GET['id']),
			'pasvorto'		=> $_POST['pw1'],
			'retpostadreso'	=> $_POST['epost']
		))) {
			$resultat['msg'] = "";
			$this->mysqli->query("
				UPDATE personer
				SET epost = '{$this->POST['epost']}'
				WHERE personid = '{$this->GET['id']}'
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
			$sql = "SELECT adgang, leieforhold, adgangsid AS endre, adgangsid AS slett FROM adganger WHERE personid = '{$_GET['id']}'";
			return json_encode($this->arrayData($sql));
		default:
			$datasett = $this->arrayData($this->hoveddata);

			$autoriserer = new $this->autoriserer;
			$datasett['data'][0]['login'] = $autoriserer->trovuUzantoNomo($datasett['data'][0]['id']);

			return json_encode($datasett);
	}
}

}
?>