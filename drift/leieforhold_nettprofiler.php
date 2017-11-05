<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Oppdatering av nettprofiler';
public $ext_bibliotek = 'ext-4.2.1.883';


function __construct() {
	parent::__construct();
}

function skript() {
	if(@$_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	$tp = $this->mysqli->table_prefix;

	$autoriserer = new $this->autoriserer;
	$leieforhold = $this->hent('Leieforhold', (int)$_GET['id']);
		
	$leietakere = $leieforhold->hent('leietakere');
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

	var skjema = Ext.create('Ext.form.Panel', {
		renderTo:		'panel',
		autoScroll: true,
		bodyStyle:'padding:5px 5px 0',
		buttons: [],
		frame:true,
		height: 500,
		items: [
			{
				xtype: 'container',
				layout: 'column',
				items: [
					<? foreach( $leietakere as $person ):?>
			
				{
					xtype:		'container',
					layout:		'form',
					margin:		5,
					autoScroll:	true,
					defaults: 	{
					},
					items: [
						{
							xtype:	'displayfield',
							value:	'<?php echo "<b>{$person->hent('navn')}:</b>";?>'
						},
						{
							xtype:	'hidden',
							name:	'profil<?php echo $person;?>',
							value:	'<?php echo $person;?>'
						},
						{
							xtype:		'textfield',
							fieldLabel: 'Brukernavn',
							name:		'login<?php echo $person;?>',
							value:		'<?php echo $autoriserer->trovuUzantoNomo($person);?>',
							width:		200
						},
						{
							xtype:		'textfield',
							fieldLabel: 'Epostadresse',
							name:		'epost<?php echo $person;?>',
							value:		'<?php echo $person->hent('epost');?>',
							width:		200
						},
						{
							xtype:		'textfield',
							fieldLabel:	'Ønsket passord',
							inputType:	'password',
							name:		'pwa<?php echo $person;?>',
							width:		200
						},
						{
							xtype:		'textfield',
							fieldLabel:	'Gjenta passord',
							inputType:	'password',
							name:		'pwb<?php echo $person;?>',
							width:		200
						},
						{
							xtype:		'checkbox',
							boxLabel:	'Ønsker epostvarsling',
							checked:	true,
							hideLabel:	true,
							inputValue:	1,
							uncheckedValue: 0,
							name:		'epostvarsel<?php echo $person;?>',
							width:		200
						}
					],
					width: 210
				},
				
				<? endforeach;?>
				
				{
					xtype:	'displayfield',
					value:	'Fyll inn epostadresse og ønsket brukernavn for å opprette eller endre nettprofil for leiebasens beboersider.<br />La brukernavnet være blankt for å ikke opprette en nettprofil nå.<br /><br />Ønsket passord kan enten fylles inn her, eller det kan opprettes av leietakeren selv ved første gangs pålogging.<br />Passord fylles kun inn dersom det skal endres eller opprettes. La passordfeltene være tomme for å beholde evt. tidligere passord.'
					}
				]
			}
			],
		labelAlign: 'top', // evt right
		standardSubmit: false,
		buttons: [{
			scale: 'medium',
			text: 'Avbryt',
			handler: function() {
				window.location = '<?php echo $this->returi->get();?>';
			}
		}, {
			scale: 'medium',
			text:	'Lagre endringer',
			disabled:	false,
			handler: function() {
				skjema.form.submit({
					url: 'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET["id"]}";?>&oppdrag=taimotskjema',
					waitMsg: 'Prøver å lagre...'
					});
			}
		}],
		title: 'Nettprofil',
		width: 900
	});


	skjema.on({
		actioncomplete: function(form, action) {
			if(action.type == 'load') {
				lagreknapp.enable();
			} 
			
			if(action.type == 'submit') {
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Fikk ingen JSON-formatert respons fra tjeneren');
				} else {
					window.location = '<?php echo $this->returi->get();?>';
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
						var result = Ext.decode(action.response.responseText);
						if(result && result.msg) {			
							Ext.MessageBox.alert(
								'Mottatt tilbakemelding om feil:',
								result.msg
							);
						}
						else {
							Ext.MessageBox.alert(
							'Problem:',
							'Innhenting av data mislyktes av ukjent grunn. (trolig manglende success-parameter i den returnerte datapakken).'
							);
						}
					}
				}
			}
	
			if(action.type == 'submit') {
				var result = Ext.decode(action.response.responseText); 
				if(result && result.msg) {			
					Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', result.msg);
				}
				else {
					Ext.MessageBox.alert(
					'Problem:',
					'Lagring av data mislyktes av ukjent grunn.'
					);
				}
			}		
		}
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
		return json_encode($this->arrayData($this->hoveddata));
		
	}
}

function taimotSkjema() {
	$autoriserer = new $this->autoriserer;
		
	foreach($_POST as $felt=>$verdi){
		if(substr($felt, 0, 6) == "profil") $kortsett[] = $verdi;
	}

	$resultat['msg'] = "";
	foreach($kortsett as $personid){
		$resultat['success'] = true;
		
		if(!$_POST["login{$personid}"]) {
			$resultat['msg'] .= "Det ble ikke opprettet noen profil for " . $this->navn($personid) . ".<br />";
		}
		else if(!$_POST["epost{$personid}"]) {
			$resultat['success'] = false;
			$resultat['msg'] .= "Epostadresse mangler for " . $this->navn($personid) . ".<br />";
		}
		else if($_POST["pwa{$personid}"] != $_POST["pwb{$personid}"]) {
			$resultat['success'] = false;
			$resultat['msg'] .= "Passordet for " . $this->navn($personid) . " er ikke gjentatt korrekt.<br />";
		}
		else if(!$autoriserer->cuLaUzantonomoEstasDisponebla($_POST["login{$personid}"], $personid)){
			$resultat['success'] = false;
			$resultat['msg'] .= "Brukernavnet  " . $_POST["login{$personid}"] . " er i bruk av andre.<br />";
		}
		else if(!$autoriserer->cuLaRetpostadresoEstasDisponebla($_POST["epost{$personid}"], $personid)){
			$resultat['success'] = false;
			$resultat['msg'] .= "Epostadressen  " . $_POST["epost{$personid}"] . " er allerede i bruk.<br />";
		}
		else if(!$autoriserer->cuLaRetpostadresoEstasDisponebla($_POST["epost{$personid}"], $personid)){
			$resultat['success'] = false;
			$resultat['msg'] .= "Epostadressen  " . $_POST["epost{$personid}"] . " er allerede i bruk.<br />";
		}
		else if(!$this->mysqli->query(
			"UPDATE personer
			SET epost = '" . $this->POST["epost{$personid}"] . "'
			WHERE personid = '$personid'"
		)) {
			$resultat['success'] = false;
			$resultat['msg'] .= "Klarte ikke å oppdatere epostadressen for " . $this->navn($personid) . ".<br> Tilbakemeldingen fra databasen lyder:<br />" . $this->mysqli->error;
		}
		else {
			$resultat['success'] = $autoriserer->aldonuUzanto(array(
				'id'			=> $personid,
				'uzanto'		=> $_POST["login{$personid}"],
				'nomo'			=> $this->navn($personid),
				'pasvorto'		=> $_POST["pwa{$personid}"],
				'retpostadreso'	=> $_POST["epost{$personid}"]
			));

			if($resultat['success']) {
				$resultat['msg'] .= "Nettprofilen lagret for " . $this->navn($personid) . ".<br />";
				$sql =	"DELETE FROM adganger\n"
					.	"WHERE personid = '$personid'\n"
					. 	"AND adgang = 'beboersider'\n"
					.	"AND leieforhold = " . $this->leieforhold($_GET['id']);
				if($this->mysqli->query($sql)){
					$sql =	"INSERT INTO adganger\n"
						.	"SET personid = '$personid',\n"
						.	"adgang = 'beboersider',\n"
						.	"leieforhold = " . $this->leieforhold($_GET['id']) . ",\n"
						.	"epostvarsling = '" . ($_POST["epostvarsel$personid"] ? 1 : 0) . "'\n";
					if($this->mysqli->query($sql)){
						$resultat['msg'] .= $this->navn($personid) . " er gitt adgang for denne leieavtalen.<br /><br />";
						$this->varsleNyeKrav();
					}
					else{
						$resultat['msg'] .= "Lyktes ikke å gi " . $this->navn($personid) . " adgang for denne leieavtalen.<br />Feilmeldingen fra databasen lyder:<br />" . $this->mysqli->error . "<br />";
					}
				}
			}
			else{
				$resultat['success'] = false;
				$resultat['msg'] .= "Klarte ikke å lagre nettprofilen for " . $this->navn($personid) . ".<br /> Tilbakemeldingen fra databasen lyder:<br />" . $this->mysqli->error . "<br />";
			}
		}
	}
	
	echo json_encode($resultat);
	return;
}



}
?>