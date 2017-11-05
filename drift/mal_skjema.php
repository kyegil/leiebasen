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
	$this->hoveddata = "";
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

<?
	foreach($datasett as $felt=>$verdi){
		echo "\tvar $felt = new Ext.form.TextField({\n";
		echo "\t\tfieldLabel: '$felt',\n";
		echo "\t\tname: '$felt',\n";
		echo "\t\twidth: 200\n";
		echo "\t});\n\n";
		$feltliste[] = $felt;
	}
?>
	var skjema = new Ext.FormPanel({
		autoScroll: true,
		bodyStyle:'padding:5px 5px 0',
		buttons: [],
		frame:true,
		height: 500,
		items: [<?=implode(", ", $feltliste);?>],
		labelAlign: 'top', // evt right
		reader: new Ext.data.JsonReader({
			defaultType: 'textfield',
			fields: [<?=implode(", ", $feltliste);?>],
			root: 'data'
		}),
		standardSubmit: false,
		title: 'Tittel',
		width: 900
	});

	skjema.addButton('Avbryt', function(){
		window.location = "index.php";
	});
	
	skjema.addButton('Last kortet på nytt', function(){
		skjema.getForm().load({url:'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET["id"]}";?>&oppdrag=hentdata', waitMsg:'Henter data..'});
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

	skjema.getForm().load({
		url:'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET["id"]}";?>&oppdrag=hentdata', waitMsg:'Henter opplysninger...'
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
<div id="panel"></div>
<?
}

function taimotSkjema() {
	$sql =	"";
	$sql .=	isset($_POST['']) ? ("" . $this->mysqli->real_escape_string($_POST['']) . "") : "";
	$sql .=	isset($_POST['']) ? ("" . $this->mysqli->real_escape_string($_POST['']) . "") : "";
	$sql .=	isset($_POST['']) ? ("" . $this->mysqli->real_escape_string($_POST['']) . "") : "";
	$sql .=	"";
	
	if($resultat['success'] = $this->mysqli->query($sql))
		$resultat['msg'] = "";
	else
		$resultat['msg'] = "KLarte ikke å lagre. Feilmeldingen fra databasen lyder:<br />" . $this->mysqli->error;
	
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