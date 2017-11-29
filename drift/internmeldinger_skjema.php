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
	$this->hoveddata = "SELECT tekst FROM internmeldinger LIMIT 1";
}

function skript() {
	if($_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	var epost = new Ext.form.Checkbox({
		name: 'epost',
		boxLabel: 'Send også meldinga som epost',
		fieldLabel: 'Epostvarsling',
		checked: true,
		hideLabel: true,
		inputValue: "1",
		width: 700
	});

	var flyko = new Ext.form.Checkbox({
		name: 'flyko',
		boxLabel: 'Kryss av her for å dele meldinga med FlyKo. Hold boksen tom for å holde meldinga intern i drift.',
		fieldLabel: 'Del meldinga med Flyko',
		hideLabel: true,
		inputValue: "1",
		width: 700
	});

	var tekst = new Ext.form.HtmlEditor({
		hideLabel: true,
		name: 'tekst',
		height: 400,
		width: 870
	});

	var skjema = new Ext.FormPanel({
		autoScroll: true,
		bodyStyle:'padding:5px 5px 0',
		frame: true,
		height: 500,
		items: [epost, flyko, tekst],
		labelAlign: 'top',
		standardSubmit: false,
		title: 'Skriv ny intern melding',
		width: 900
	});

	
	skjema.addButton('Avbryt', function(){
		window.location = '<?=$this->returi->get();?>';
	});
	
	var lagreknapp = skjema.addButton({
		text: 'Post melding',
		handler: function(){
			skjema.form.submit({
				url:'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET["id"]}";?>&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...'
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
					window.location = '<?=$this->returi->get();?>';
					Ext.MessageBox.alert('Suksess', 'Meldingen er lagret');
				}
			}
		},
							
		actionfailed: function(form,action){
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
	$tp = $this->mysqli->table_prefix;

	$this->mysqli->saveToDb(array(
		'insert'	=> true,
		'table'		=> "{$tp}internmeldinger",
		'fields'	=> array(
			'avsender'	=> $this->bruker['id'],
			'tekst'		=> $this->POST['tekst'],
			'drift'		=> true,
			'flyko'		=> (bool)@$_POST['flyko']
		)
	));
	
		
	if(@$_POST['epost']) {
		$html =	"<p style=\"font-size: 10px; border: 1px solid grey; padding: 2px; background-color:#cccccc;\">{$this->bruker['navn']} har skrevet ei melding på <a href=\"{$this->http_host}/" . $this->katalog($_SERVER['PHP_SELF']) . "/index.php\">driftssidene</a> for {$this->valg['utleier']}. Klikk <a href=\"{$this->http_host}/" . $this->katalog($_SERVER['PHP_SELF']) . "/index.php?oppslag=internmeldinger_skjema\">her</a> for å skrive ei ny melding eller et åpent svar.<br /></p>"
			.	"<div>" . stripslashes($_POST['tekst']) . "</div>"
			.	"<p style=\"font-size: 10px; border: 1px solid grey; padding: 2px; background-color:#cccccc;\">Dersom du ikke ønsker å motta varsler fra drift kan du endre dette i din <a href=\"{$this->http_host}/" . $this->katalog($_SERVER['PHP_SELF']) . "/index.php?oppslag=adgang_liste\">adgang</a> i leiebasen</p>";
		
		$this->sendMail(array(
			'auto'		=> false,
			'subject'	=> "Ny melding på driftsidene",
			'html'		=> $html,
			'to'		=> "{$this->valg['utleier']} <{$this->valg['epost']}>",
			'from'		=> "{$this->valg['utleier']} <{$this->valg['epost']}>",
			'reply'		=> "{$this->bruker['navn']} <{$this->bruker['epost']}>"
		));

		foreach( $this->mysqli->arrayData(array(
			'source'	=> "{$tp}adganger AS adganger",
			'class'		=> "Person",
			'fields'	=> "adganger.personid AS id",
			'where'		=> "adganger.epostvarsling != 0\n"
						.	"AND (adgang = 'drift'"
						. (@$_POST['flyko'] ? " OR adgang = 'flyko'" : "")
						. ")"
		))->data as $mottaker) {
			$this->sendMail(array(
				'auto'		=> false,
				'subject'	=> "Ny melding på driftsidene",
				'html'		=> $html,
				'to'		=> "{$mottaker->hent('navn')} <{$mottaker->hent('epost')}>",
				'from'		=> "{$this->valg['utleier']} <{$this->valg['epost']}>",
				'reply'		=> "{$this->bruker['navn']} <{$this->bruker['epost']}>"
			));

		}
	}

	echo json_encode(array('success'=>true));
}

function hentData($data = "") {
	switch ($data) {
		default:
			return json_encode($this->arrayData($this->hoveddata));
	}
}

}
?>