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
	$id = $_GET['id'];
	if( $id != '*' ) {
		settype($id, 'integer');
	}
	if(!$id) die("Ugyldig oppslag: ID ikke angitt for kontrakt");
	$this->hoveddata = "SELECT * FROM avtalemaler WHERE malnr = '{$id}'";
}

function skript() {
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	var malnavn = new Ext.form.TextField({
		fieldLabel: 'Mal',
		name: 'malnavn',
		width: 800
	});

	var mal = new Ext.form.HtmlEditor({
		fieldLabel: 'Tekst',
		height: 370,
		labelSeparator: '',
		name: 'mal',
		width: 800
	});

	var skjema = new Ext.FormPanel({
		autoScroll: true,
		bodyStyle:'padding:5px 5px 0',
		buttons: [],
		frame:true,
		height: 500,
		items: [malnavn, mal],
		labelAlign: 'left', // evt right
		labelWidth: 50,
		reader: new Ext.data.JsonReader({
			defaultType: 'textfield',
			fields: [malnavn, mal],
			root: 'data'
		}),
		standardSubmit: false,
		title: 'Mal nr <?=$_GET['id']?> for leieavtaler',
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
				url:'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET["id"]}";?>&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...'
				});
		}
	});

	skjema.render('panel');

<?
	if($_GET['id'] != '*'){
?>
	skjema.getForm().load({
		url:'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET["id"]}";?>&oppdrag=hentdata', waitMsg:'Henter opplysninger...'
	});

<?
	}
	else{
?>
	lagreknapp.enable();
<?
	}
?>

	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'load'){
				lagreknapp.enable();
			} 
			
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Mottok ikke bekreftelsesmelding fra tjeneren  i JSON-format som forventet');
				} else {
					Ext.MessageBox.alert('Suksess', 'Opplysningene er oppdatert');
					window.location = '<?=$this->returi->get();?>';				
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


function hentData($data = "") {

	switch ($data) {

	default: {
		return json_encode($this->arrayData($this->hoveddata));
	}
	}
}


function taimotSkjema() {

	$sql =	"REPLACE avtalemaler\n";
	$sql .=	"SET malnr = '" . $this->GET['id'] . "',\n";
	$sql .=	"malnavn = '" . $this->POST['malnavn'] . "',\n";
	$sql .=	"mal = '" . $this->POST['mal'] . "'\n";
	
	if($resultat['success'] = $this->mysqli->query($sql))
		$resultat['msg'] = "";
	else
		$resultat['msg'] = "KLarte ikke å lagre. Feilmeldingen fra databasen lyder:<br />" . $this->mysqli->error;
	
	echo json_encode($resultat);
}


}
?>