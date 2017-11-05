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
	if(!$id = $_GET['id']) die("Ugyldig oppslag: ID ikke angitt for kontrakt");
	$this->hoveddata = "SELECT * FROM leieberegning WHERE nr = '$id'";
}

function skript() {
	$datasett = $this->arrayData($this->hoveddata);
	$datasett = $datasett['data'][0];
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	var navn = new Ext.form.TextField({
		fieldLabel: 'Navn på beregningsmetode',
		name: 'navn',
		width: 300
	});

	var beskrivelse = new Ext.form.TextArea({
		fieldLabel: 'Beskrivelse',
		height: 100,
		name: 'beskrivelse',
		width: 300
	});

	var leie_objekt = new Ext.form.NumberField({
		allowBlank: true,
		allowDecimals: true,
		allowNegative: false,
		decimalPrecision: 2,
		decimalSeparator: ',',
		fieldLabel: 'Grunnbeløp per leieobjekt',
		name: 'leie_objekt',
		width: 100
	});

	var leie_kontrakt = new Ext.form.NumberField({
		allowBlank: true,
		allowDecimals: true,
		allowNegative: false,
		decimalPrecision: 2,
		decimalSeparator: ',',
		fieldLabel: 'Grunnbeløp per leieavtale i leieobjektet',
		name: 'leie_kontrakt',
		width: 100
	});

	var leie_kvm = new Ext.form.NumberField({
		allowBlank: true,
		allowDecimals: true,
		allowNegative: false,
		decimalPrecision: 2,
		decimalSeparator: ',',
		fieldLabel: 'Beløp per kvadratmeter',
		name: 'leie_kvm',
		width: 100
	});

	var leie_var_bad = new Ext.form.NumberField({
		allowBlank: true,
		allowDecimals: true,
		allowNegative: false,
		decimalPrecision: 2,
		decimalSeparator: ',',
		fieldLabel: 'Tillegg for tilgang på bad/dusj',
		name: 'leie_var_bad',
		width: 100
	});

	var leie_var_fellesdo = new Ext.form.NumberField({
		allowBlank: true,
		allowDecimals: true,
		allowNegative: false,
		decimalPrecision: 2,
		decimalSeparator: ',',
		fieldLabel: 'Tillegg for tilgang på felles do i samme bygning',
		name: 'leie_var_fellesdo',
		width: 100
	});

	var leie_var_egendo = new Ext.form.NumberField({
		allowBlank: true,
		allowDecimals: true,
		allowNegative: false,
		decimalPrecision: 2,
		decimalSeparator: ',',
		fieldLabel: 'Tillegg for egen do',
		name: 'leie_var_egendo',
		width: 100
	});

	var skjema = new Ext.FormPanel({
		autoScroll: true,
		bodyStyle:'padding:5px 5px 0',
		buttons: [],
		frame:true,
		height: 500,
		items: [navn, beskrivelse, {html: 'Alle beløp oppgitt per måned'}, leie_objekt, leie_kontrakt, leie_kvm, leie_var_bad, leie_var_fellesdo, leie_var_egendo],
		labelAlign: 'left',
		labelWidth: 200,
		reader: new Ext.data.JsonReader({
			defaultType: 'textfield',
			fields: [navn, beskrivelse, leie_objekt, leie_kontrakt, leie_kvm, leie_var_bad, leie_var_fellesdo, leie_var_egendo],
			root: 'data'
		}),
		standardSubmit: false,
		title: 'Leieberegningsmetode',
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
					window.location = '<?=$this->returi->get();?>';
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


function hentData($data = "") {
	switch ($data) {
		default:
			return json_encode($this->arrayData($this->hoveddata));
	}
}


function taimotSkjema() {
	$sql =	"REPLACE leieberegning\n";
	$sql .=	"SET nr = '{$this->GET['id']}',\n";
	$sql .=	"navn = '{$this->POST['navn']}',\n";
	$sql .=	"beskrivelse = '{$this->POST['beskrivelse']}',\n";
	$sql .=	"leie_objekt = '" . str_replace(",", ".", $this->POST['leie_objekt']) . "',\n";
	$sql .=	"leie_kontrakt = '" . str_replace(",", ".", $this->POST['leie_kontrakt']) . "',\n";
	$sql .=	"leie_kvm = '" . str_replace(",", ".", $this->POST['leie_kvm']) . "',\n";
	$sql .=	"leie_var_bad = '" . str_replace(",", ".", $this->POST['leie_var_bad']) . "',\n";
	$sql .=	"leie_var_fellesdo = '" . str_replace(",", ".", $this->POST['leie_var_fellesdo']) . "',\n";
	$sql .=	"leie_var_egendo = '" . str_replace(",", ".", $this->POST['leie_var_egendo']) . "'\n";
	
	if($resultat['success'] = $this->mysqli->query($sql))
		$resultat['msg'] = "";
	else
		$resultat['msg'] = "KLarte ikke å lagre. Feilmeldingen fra databasen lyder:<br />" . $this->mysqli->error;
	
	echo json_encode($resultat);
}


}
?>