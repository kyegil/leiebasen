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

	if(!$id = $_GET['id']) die("Ugyldig oppslag: ID ikke angitt for skademelding");
	$this->hoveddata = "SELECT skader.skade, skader.leieobjektnr, bygninger.navn\n"
		.	"FROM skader\n"
		.	"LEFT JOIN bygninger ON skader.bygning = bygninger.id\n"
		.	"WHERE skader.id = $id";
}

function skript() {
	$datasett = $this->arrayData($this->hoveddata);
	$datasett = $datasett['data'][0];
?>

Ext.onReady(function() {
	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	var utført = new Ext.form.DateField({
		name: 'utført',
		fieldLabel: 'Ferdig utbedret dato',
		format: 'd.m.Y',
		allowBlank: false,
		altFormats: "j.n.y|j.n.Y|j/n/y|j/n/Y|j-n-y|j-n-Y|j. M y|j. M -y|j. M. Y|j. F -y|j. F y|j. F Y|j/n|j-n|dm|dmy|dmY|d|Y-m-d",
		value: new Date(),
		width: 200
	});

	var sluttrapport = new Ext.form.HtmlEditor({
		fieldLabel: 'Beskrivelse av det som er utført',
		name: 'sluttrapport',
		width: 800,
		height: 300
	});

	var skjema = new Ext.FormPanel({
		autoScroll: true,
		bodyStyle:'padding:5px 5px 0',
		buttons: [],
		frame:true,
		height: 500,
		items: [
			{'html': [
				'<b><?=$datasett['skade']?></b><br />',
				'<?=($datasett['leieobjektnr'] ? $this->leieobjekt($datasett['leieobjektnr'], true) : $datasett['navn'])?>'
			]},
			utført,
			sluttrapport
		],
		labelAlign: 'top', // evt right
		standardSubmit: false,
		title: 'Melding om utbedret skade',
		width: 900
	});

	skjema.addButton('Avbryt', function(){
		window.location = "<?=$this->returi->get();?>";
	});
	
	
	var lagreknapp = skjema.addButton({
		text: 'Lagre utbedringsmelding',
		disabled: false,
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
					window.location = "index.php?oppslag=skade_liste<?=isset($_GET['leieobjektnr']) ? "&id={$_GET['leieobjektnr']}" : ""?>";
					Ext.MessageBox.alert('Suksess', 'Opplysningene er oppdatert');
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

	$resultat = (object)array(
		'success'	=> false
	);

	if( $this->mysqli->arrayData(array(
		'source'	=> "skader",
		'where'		=> "id = " . (int)$_GET['id'] . " AND utført"
	))->totalRows) {
		$resultat =  (object)array(
			'success'	=> false,
			'msg'		=> "Denne skaden er allerede meldt som utbedret."
		);
	}

	else{
		$resultat = $this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "skader",
			'where'		=> "id = " . (int)$_GET['id'],
			'fields'	=> array(
				'utført'			=> date('Y-m-d', strtotime($_POST['utført'])),
				'sluttregistrerer'	=> $this->bruker['navn'],
				'sluttrapport'		=> $_POST['sluttrapport']
			)
		));
	}
	
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		default:
			return json_encode($this->arrayData($this->hoveddata));
	}
}

}
?>