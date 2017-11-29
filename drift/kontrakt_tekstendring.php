<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Rediger avtaleteksten';
public $ext_bibliotek = 'ext-4.2.1.883';


function __construct() {
	parent::__construct();

}

function skript() {
	$kontraktnr = (int)$_GET['id'];
	if(@$_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	$tp = $this->mysqli->table_prefix;

	$kontrakt = $this->mysqli->arrayData(array(
		'source'	=> "{$tp}kontrakter AS kontrakter",
		'where'		=> "kontraktnr = '{$kontraktnr}'"
	))->data[0];
	
?>

Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?php echo $this->http_host . "/" . $this->ext_bibliotek . "/examples/ux"?>');

Ext.require([
	'Ext.data.*',
	'Ext.form.*'
]);

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';


	var tekst = Ext.create('Ext.form.field.HtmlEditor', {
		hideLabel:	true,
		name:	'tekst',
		height:	400,
		width:	870
	});
	
	
	var lagreknapp = Ext.create('Ext.button.Button', {
		scale: 'medium',
		text: 'Lagre endringer',
		handler: function() {
			skjema.submit({
				url:	'index.php?oppslag=kontrakt_tekstendring&id=<?php echo $kontraktnr;?>&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...'
			});
		}
	});
	

	var skjema = Ext.create('Ext.form.Panel', {
		renderTo:		'panel',
		autoScroll:		true,
		frame:	true,
		title:	'Leieavtale nr. <?php echo $kontraktnr;?>',
		bodyStyle:	'padding:5px 5px 0',
		buttonAlign:	'right',
		height:	500,
        labelAlign:	'top',
		waitTitle:	'Vent litt..',
        width: 900,
		items:	[tekst],
		buttons: [{
			scale: 'medium',
			text: 'Avbryt',
			handler: function() {
				window.location = '<?php echo $this->returi->get();?>';
			}
		}, {
			scale: 'medium',
			text: 'Last avtaleteksten på nytt',
			handler: function() {
				skjema.load({
					url: 'index.php?oppslag=kontrakt_tekstendring&id=<?php echo $kontraktnr;?>&oppdrag=hentdata',
					waitMsg:	'Henter data..'
				});
			}
		},
		lagreknapp
		]
	});
	

	skjema.on({
		render: Ext.Msg.alert("Husk:", "Leieavtalen må skrives ut og signeres av begge parter, også dersom den endres."),


		actioncomplete: function(form, action){
			if(action.type == 'load'){
				lagreknapp.enable();
			}
			
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Mottok ikke resultat fra serveren');
				} else {
					Ext.MessageBox.alert('Suksess', 'Avtaleteksten er lagret');
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
		}
	});

	skjema.load({
		url:	'index.php?oppslag=kontrakt_tekstendring&id=<?php echo $kontraktnr;?>&oppdrag=hentdata',
		waitMsg:'Henter opplysninger...'
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
	$tp = $this->mysqli->table_prefix;
	switch ($data) {
	
	default:
	
		$kontraktnr = (int)$_GET['id'];
		$kontrakt = $this->mysqli->arrayData(array(
			'source'	=> "{$tp}kontrakter AS kontrakter",
			'where'		=> "kontraktnr = '{$kontraktnr}'"
		))->data[0];
		
		$leieforhold = $this->hent('Leieforhold', $kontrakt->leieforhold);
		$resultat = (object)array(
			'success'	=> true,
			'data'		=> $kontrakt
		);
		
		
		return json_encode($resultat);

	}
}

function taimotSkjema() {
	$tp = $this->mysqli->table_prefix;

	if(!$fradato = $this->tolkDato($_POST['fradato']) and trim($_POST['fradato']) != '') {
		$data['errors']['fradato'] = "Ugyldig dato";
	}
	
	if(!$tildato = $this->tolkDato($_POST['tildato']) and trim($_POST['tildato']) != '') {
		$data['errors']['tildato'] = "Ugyldig dato";
	}
	
	$sql = "";
	if(isset($_POST['tekst'])) {
		$sql =	"UPDATE kontrakter\n"
		.		"SET tekst = '" . $this->POST['tekst'] . "'\n"
		.		"WHERE kontraktnr = " . $this->GET['id'];
	}
	if(!$this->mysqli->query($sql)) {
		$data['msg'] = "Klarte ikke å utføre databasespørringen:<br />$sql<br /><br />Feilmeldingen fra databasen lyder:<br />".$this->mysqli->error;
	}
	if(isset($data)) {
		$data['success'] = false;
	}
	else {
		$data['success'] = true;
	}
	echo json_encode($data);
}

}
?>