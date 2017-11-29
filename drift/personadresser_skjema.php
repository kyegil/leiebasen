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
	if(!$id = $_GET['id']) die("Ugyldig oppslag: ID ikke angitt for adressekort");
	$this->hoveddata = "SELECT * FROM personer WHERE personid = $id";
	if ($id =='*') $this->hoveddata = "SELECT '' AS personid";
}

function skript() {
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	var id = "<?=$_GET['id'];?>";

	bekreftSletting = function(id) {
		Ext.Msg.show({
			title: 'Bekreft',
			id: id,
			msg: 'Er du sikker på at du vil slette denne personen fra adresseregisteret?<br />Leiebasen vil miste all personlig kontaktinformasjon.',
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
			url: "index.php?oppslag=personadresser_skjema&oppdrag=oppgave&oppgave=slett&id=" + id,
			success: function(response, options){
				var tilbakemelding = Ext.util.JSON.decode(response.responseText);
				if(tilbakemelding['success'] == true) {
					Ext.MessageBox.alert('Utført', tilbakemelding.msg, function(){
						window.location = '<?=$this->returi->get(1);?>';
					});
				}
				else {
					Ext.MessageBox.alert('Hmm..',tilbakemelding['msg']);
				}
			}
		});
	}


	var fornavn = new Ext.form.TextField({
		fieldLabel: 'Fornavn',
		name: 'fornavn',
		width: 190
	});
	
	var etternavn = new Ext.form.TextField({
		allowBlank:false,
		fieldLabel: 'Etternavn',
		blankText: 'Obligatorisk',
		name: 'etternavn',
		width: 190
	});
	
	var er_org = new Ext.form.Checkbox({
		boxLabel: 'Firma / organisasjon',
		inputValue: 1,
		labelSeparator: '',
		name: 'er_org'
	});
	
	er_org.on({
		check: function( checkbox, checked ) {
			if( checked ) {
				fornavn.disable();
				fornavn.label.update('');
				etternavn.label.update('Navn');
				personnr.label.update('Org. nr.');
			}
			else {
				fornavn.enable();
				fornavn.label.update('Fornavn');
				etternavn.label.update('Etternavn');
				personnr.label.update('Personnr (siste 5 siffer)');
			}
		}
	});
	
	var fødselsdato = new Ext.form.DateField({
		fieldLabel: 'Fødselsdato',
		name: 'fødselsdato',
		format: 'd.m.Y',
		altFormats: "j.n.y|j.n.Y|j/n/y|j/n/Y|j-n-y|j-n-Y|j. M y|j. M -y|j. M. Y|j. F -y|j. F y|j. F Y|j/n|j-n|dm|dmy|dmY|d|Y-m-d",
		width: 190
	});
	
	var personnr = new Ext.form.TextField({
		fieldLabel: 'Personnr (siste 5 siffer)',
		name: 'personnr',
		width: 190
	});
	
	var adresse1 = new Ext.form.TextField({
		fieldLabel: 'Adresse',
		name: 'adresse1',
		width: 190
	});
	
	var adresse2 = new Ext.form.TextField({
		labelSeparator: '',
		name: 'adresse2',
		width: 190
	});
	
	var postnr = new Ext.form.TextField({
		fieldLabel: 'Postnr',
		name: 'postnr',
		width: 50
	});
	
	var poststed = new Ext.form.TextField({
		fieldLabel: 'Poststed',
		name: 'poststed',
		width: 190
	});
	
	var land = new Ext.form.TextField({
		fieldLabel: '(Land)',
		name: 'land',
		width: 190
	});
	
	var telefon = new Ext.form.TextField({
		fieldLabel: 'Telefon',
		name: 'telefon',
		width: 190
	});
	
	var mobil = new Ext.form.TextField({
		fieldLabel: 'Mobil',
		name: 'mobil',
		width: 190
	});
	
	var epost = new Ext.form.TextField({
		fieldLabel: 'Epost',
		name: 'epost',
		width: 190
	});
	

	var skjema = new Ext.form.FormPanel({
		buttonAlign: 'right',
		buttons: [{
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
			},
			text: 'Avbryt'
		}, {
			handler: function() {
				bekreftSletting(id);
			},
			text: 'Slett adressekortet'
		}],
		frame: true,
		labelAlign: 'right',
		labelWidth: 200,
		title: 'Adressekort',
		width: 900,
		waitMsgTarget: true,
		waitMsg: 'Vent litt..',
		reader: new Ext.data.JsonReader({
			defaultType: 'textfield',
			fields: [
					er_org,
					fornavn,
					etternavn,
					fødselsdato,
					personnr,
					adresse1,
					adresse2,
					postnr,
					poststed,
					land,
					telefon,
					mobil,
					epost
			],
			root: 'data'
		}),
		items: [
			new Ext.form.FieldSet({
				title: 'Kontaktopplysninger',
				autoHeight: true,
				defaultType: 'textfield',
				items: [
					er_org,
					fornavn,
					etternavn,
					fødselsdato,
					personnr,
					adresse1,
					adresse2,
					postnr,
					poststed,
					land,
					telefon,
					mobil,
					epost
				]
			})
		]
	});

	skjema.addButton('Last kortet på nytt', function(){
		skjema.getForm().load({url:'index.php?oppslag=personadresser_skjema&id=<?=$_GET["id"];?>&oppdrag=hentdata', waitMsg:'Henter data..'});
	});

	var submit = skjema.addButton({
		text: 'Lagre endringer',
		disabled: true,
		handler: function(){
			skjema.form.submit({
				url:'index.php?oppslag=personadresser_skjema&id=<?=$_GET["id"];?>&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...'
				});
		}
	});

	skjema.render('panel');

	skjema.getForm().load({
		url:'index.php?oppslag=personadresser_skjema&id=<?=$_GET["id"];?>&oppdrag=hentdata', waitMsg:'Henter opplysninger...'
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

	if( !isset( $_GET['id'] )) {
		echo json_encode(array(
			'success'	=> false,
			'msg'		=> "Adressekortets kunne ikke lagres fordi ID-nummeret mangler."
		));
		return;
	}

	$id = $_GET['id'];
	$er_org = @$_POST['er_org'];
	
	$resultat = $this->mysqli->saveToDb(array(
		'table'		=> "personer",
		'id'		=> $id != '*' ? $id : null,
		'where'		=> "personid = '" . (int)$id . "'",
		'update'	=> $id == '*' ? false : true,
		'insert'	=> $id == '*' ? true : false,
		'fields'	=> array(
			'fornavn'		=> !$er_org ? $_POST['fornavn'] : "",
			'etternavn'		=> $_POST['etternavn'],
			'er_org'		=> (boolean)$er_org,
			'fødselsdato'	=> $this->tolkDato($_POST['fødselsdato']),
			'personnr'		=> $_POST['personnr'],
			'adresse1'		=> $_POST['adresse1'],
			'adresse2'		=> $_POST['adresse2'],
			'postnr'		=> $_POST['postnr'],
			'poststed'		=> $_POST['poststed'],
			'land'			=> $_POST['land'],
			'personnr'		=> $_POST['personnr'],
			'telefon'		=> $_POST['telefon'],
			'mobil'			=> $_POST['mobil'],
			'epost'			=> $_POST['epost']
		)
	));

	$resultat->post = $resultat->id;
	
	echo json_encode( $resultat );

}


function oppgave($oppgave) {
	switch ($oppgave) {
		case "slett":
			if($resultat['success'] = $this->mysqli->query("UPDATE kontraktpersoner INNER JOIN personer ON kontraktpersoner.person = personer.personid\n"
			.	"SET kontraktpersoner.leietaker = IF(personer.er_org, personer.etternavn, CONCAT(personer.fornavn, ' ', personer.etternavn)), kontraktpersoner.person = NULL\n"
			.	"WHERE kontraktpersoner.person = '{$this->GET['id']}'")) {
				$resultat['success'] = $this->mysqli->query("DELETE FROM personer WHERE personid = '{$this->GET['id']}'");
			}
			if($resultat['success']) {
				$this->mysqli->query("UPDATE kontrakter SET regningsperson = 0 WHERE regningsperson = '{$this->GET['id']}'");
				$resultat['msg'] = "Adressekortet er slettet";
			}
			else {
				$resultat['msg'] = "Klarte ikke slette. Feilmeldingen fra databasen lyder:<br />" . $this->mysqli->error;
			}
			echo json_encode($resultat);
			break;
	}
}

}
?>