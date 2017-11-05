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
	if(!$id = $_GET['id']) die("Ugyldig oppslag: ID ikke angitt for strømanlegg");
	$this->hoveddata = "SELECT * FROM fs_fellesstrømanlegg WHERE anleggsnummer = '$id'";
	if($id == "*") $this->hoveddata = "SELECT NULL";
}

function skript() {
	if($_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	var datasett = new Ext.data.JsonStore({
		data: [<?=$this->hentData();?>],
		fields: [
			{name: 'anleggsnummer', type: 'float'},
			{name: 'målernummer', type: 'float'},
			{name: 'plassering'},
			{name: 'formål'}
		]
	});
	
	var anleggsnummer = new Ext.form.TextField({
		fieldLabel: 'Anleggsnummer',
		name: 'anleggsnummer',
		readOnly: false,
		width: 400
	});

	var målernummer = new Ext.form.TextField({
		fieldLabel: 'Målernummer',
		name: 'målernummer',
		readOnly: false,
		width: 400
	});

	var plassering = new Ext.form.TextField({
		fieldLabel: 'Plassering',
		name: 'plassering',
		readOnly: false,
		width: 400
	});

	var formål = new Ext.form.TextField({
		fieldLabel: 'Brukes til',
		name: 'formål',
		readOnly: false,
		width: 400
	});
	
	var dataleser =  new Ext.data.JsonReader({
			defaultType: 'textfield',
			fields: [
				anleggsnummer,
				målernummer,
				plassering, 
				formål
			],
			root: 'data'
		});

	var skjema = new Ext.FormPanel({
		bodyStyle:'padding:5px 5px 0',
		buttonAlign: 'right',
		buttons: [],
		defaultType: 'textfield',
		frame:true,
		items: [anleggsnummer, målernummer, plassering, formål],
		labelAlign: 'right',
		labelWidth: 200,
		reader: dataleser,
		standardSubmit: false,
		title: 'Fellesstrømanlegg',
		width: 900,
		height: 500
	});
	
	skjema.addButton('Avbryt redigering', function(){
		window.location = '<?=$this->returi->get();?>';				
	});

	skjema.addButton('Last kortet på nytt', function(){
		skjema.getForm().load({
			url:'index.php?oppslag=fs_anlegg_skjema&id=<?=$_GET["id"];?>&oppdrag=hentdata',
			waitMsg:'Henter data..'
		});
	});

	var submit = skjema.addButton({
		text: 'Lagre endringer',
		disabled: true,
		handler: function(){
			skjema.form.submit({
				url:'index.php?oppslag=fs_anlegg_skjema&id=<?=$_GET["id"];?>&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...'
				});
		}
	});

	skjema.render('panel');

	skjema.getForm().load({
		url:'index.php?oppslag=fs_anlegg_skjema&id=<?=$_GET["id"];?>&oppdrag=hentdata', waitMsg:'Henter opplysninger...'
	});


	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'load'){
				submit.enable();
			} 
			
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Form submit returned an empty string instead of json');
				} else {
					window.location = 'index.php?oppslag=fs_anlegg_skjema&id=' + action.result.post;				
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
<div id="panel"></div>
<?
}

function taimotSkjema() {
	// taimotSkjema skal returnere parameterene 'success', 'errors' og evt. 'msg'
	// 'errors' vil undertrykke 'msg'

	$sql = "REPLACE fs_fellesstrømanlegg SET anleggsnummer = '" . $this->mysqli->real_escape_string($_POST['anleggsnummer']) . "', målernummer = '" . $this->mysqli->real_escape_string($_POST['målernummer']) . "', plassering = '" . $this->mysqli->real_escape_string($_POST['plassering']) . "', formål = '" . $this->mysqli->real_escape_string($_POST['formål']) . "'";
	if(!$this->mysqli->query($sql)) {
		$data['msg'] = "Klarte ikke å utføre databasespørringen:<br />$sql<br /><br />Feilmeldingen fra databasen lyder:<br />" . $this->mysqli->error;
	}
	if(isset($data)) {
		$data['success'] = false;
	}
	else {
		$data['success'] = true;
		$data['post'] = $_POST['anleggsnummer'];
	}
	echo json_encode($data);
}

function hentData($data = "") {
	switch ($data) {
		default:
			return json_encode($this->arrayData($this->hoveddata));
	}
}

}
?>