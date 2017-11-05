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
	
	if(!$this->adgang($this->område['område'] = $this->katalog(__FILE__))) die("Ingen adgang");
	$id = $this->bruker['id'];
	$this->hoveddata = "SELECT personer.*, NULL AS pw1, NULL AS pw2\n"
		.	"FROM personer\n"
		.	"WHERE personid = '$id'";
}

function skript() {
	if($_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	$datasett = $this->arrayData($this->hoveddata);
	$datasett = $datasett['data'][0];
	$id = "id";
	
	$autoriserer = new $this->autoriserer;
	$datasett['login'] = $autoriserer->trovuUzantoNomo($datasett['personid']);
		
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

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

	var innloggingsområde = new Ext.form.FieldSet({
		animCollapse: true,
		autoHeight: true,
		collapsed: false,
		collapsible: true,
		labelAlign: 'top', // evt right
		title: 'Brukernavn og passord',
		items: [login, epost, pw1, pw2],
		layout: 'form'
	});


	var fødselsdato = new Ext.form.DateField({
		disabled: true,
		fieldLabel: 'Fødselsdato',
		name: 'fødselsdato',
		format: 'd.m.Y',
		altFormats: "j.n.y|j.n.Y|j/n/y|j/n/Y|j-n-y|j-n-Y|j. M y|j. M -y|j. M. Y|j. F -y|j. F y|j. F Y|j/n|j-n|dm|dmy|dmY|d|Y-m-d",
		width: 200
	});

	var personnr = new Ext.form.TextField({
		disabled: true,
		fieldLabel: 'Personnummer / Org.nr.',
		name: 'personnr',
		width: 200
	});

	var adresse1 = new Ext.form.TextField({
		fieldLabel: 'Adresse',
		name: 'adresse1',
		width: 200
	});

	var adresse2 = new Ext.form.TextField({
		fieldLabel: '',
		labelSeparator: '',
		name: 'adresse2',
		width: 200
	});

	var postnr = new Ext.form.TextField({
		fieldLabel: 'Postnr',
		name: 'postnr',
		width: 50
	});

	var poststed = new Ext.form.TextField({
		fieldLabel: 'Poststed',
		name: 'poststed',
		width: 200
	});

	var land = new Ext.form.TextField({
		fieldLabel: '(Land)',
		name: 'land',
		width: 200
	});

	var telefon = new Ext.form.TextField({
		fieldLabel: 'Telefon',
		name: 'telefon',
		width: 200
	});

	var mobil = new Ext.form.TextField({
		fieldLabel: 'Mobil',
		name: 'mobil',
		width: 200
	});

	var kontaktopplysninger = new Ext.form.FieldSet({
		animCollapse: true,
		autoHeight: true,
		collapsed: false,
		collapsible: true,
		title: 'Kontaktopplysninger',
		autoHeight: true,
		defaultType: 'textfield',
		labelAlign: 'right', // evt top
		items: [
			fødselsdato,
			personnr,
			adresse1,
			adresse2,
			postnr,
			poststed,
			land,
			telefon,
			mobil
		]
	});


<?
	$sql =	"SELECT adganger.*, MAX(kontrakter.leieobjekt) AS leieobjekt, MAX(kontrakter.kontraktnr) AS kontraktnr FROM adganger LEFT JOIN kontrakter ON adganger.leieforhold = kontrakter.leieforhold\n"
		.	"WHERE adgang = 'beboersider'\n"
		.	"AND personid ='{$this->bruker['id']}'";
	$leieforholdsett = $this->arrayData($sql);
	foreach($leieforholdsett['data'] as $leieforhold){
?>
	var leieforhold<?=$leieforhold['leieforhold']?> = new Ext.form.FieldSet({
		animCollapse: true,
		autoHeight: true,
		collapsed: false,
		collapsible: true,
		labelAlign: 'top', // evt right
		title: 'Leieforhold <?=$leieforhold['leieforhold']?>',
		items: [
			{html: '<?=$this->liste($this->kontraktpersoner($leieforhold['kontraktnr'])) . " i " . $this->leieobjekt($leieforhold['leieobjekt'], true)?><br /><br />'},
			{
				xtype: 'checkbox',
				fieldLabel: 'Epostvarsling',
				hideLabel: false,
				boxLabel: 'Aktiver epostvarsling',
				inputValue: 1,
				checked: <?=$leieforhold['epostvarsling'] ? "true" : "false"?>,
				name: 'epostvarsling<?=$leieforhold['leieforhold']?>'
			},
			{
				xtype: 'checkbox',
				boxLabel: 'Send også påminnelser om ordinære terminforfall som epost',
				fieldLabel: '',
				hideLabel: true,
				inputValue: 1,
				checked: <?=$leieforhold['forfallsvarsel'] ? "true" : "false"?>,
				name: 'forfallsvarsel<?=$leieforhold['leieforhold']?>'
			},
			{
				xtype: 'checkbox',
				boxLabel: 'Send også bekreftelse på innbetalinger',
				fieldLabel: '',
				hideLabel: true,
				inputValue: 1,
				checked: <?=$leieforhold['innbetalingsbekreftelse'] ? "true" : "false"?>,
				name: 'innbetalingsbekreftelse<?=$leieforhold['leieforhold']?>'
			}
		],
		layout: 'form'
	});

<?
	}
?>
	var kolonne1 = {
		layout: 'form',
		columnWidth: 0.5,
		items: [
			innloggingsområde,
<?
		foreach($leieforholdsett['data'] as $leieforhold){
		echo "\t\t\tleieforhold{$leieforhold['leieforhold']},\n";
		}
?>
			{xtype: 'hidden', name: 'personid', value: '<?=$leieforhold['personid']?>'}
		]
	};

	var kolonne2 = {
		layout: 'form',
		columnWidth: 0.5,
		items: [kontaktopplysninger]
	};

	var skjema = new Ext.FormPanel({
		layout: 'column',
		autoScroll: true,
		bodyStyle:'padding:5px 5px 0',
		buttons: [],
		frame:true,
		items: [kolonne1, kolonne2],
		labelAlign: 'right', // evt top
		reader: new Ext.data.JsonReader({
			defaultType: 'textfield',
			fields: [
				id,
				login,
				epost,
				pw1,
				pw2,
				fødselsdato,
				personnr,
				adresse1,
				adresse2,
				postnr,
				poststed,
				land,
				telefon,
				mobil,
			],
			root: 'data'
		}),
		standardSubmit: false,
		title: 'Brukernavn og passord for nettjenester for <?=$this->navn($this->bruker['id']);?>',
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
				url:'index.php?oppslag=profil_skjema&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...'
				});
		}
	});

	skjema.render('panel');

	skjema.getForm().load({
		url: 'index.php?oppslag=profil_skjema&oppdrag=hentdata',
		waitMsg: 'Henter opplysninger...'
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
					window.location = 'index.php?oppslag=profil_skjema';
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
	
	if(!$_POST['login']) {
		echo json_encode(array(
			'success'	=> false,
			'msg'		=> "Du må oppgi et gyldig brukernavn. Prøv på nytt."
		));
		return;
	}
	
	if($_POST['login'] and !$autoriserer->cuLaUzantonomoEstasDisponebla($_POST['login'], $this->bruker['id'])) {
		echo json_encode(array(
			'success'	=> false,
			'msg'		=> "Brukernavnet er allerede i bruk. Prøv på nytt."
		));
		return;
	}
	
	if($_POST['login'] and $_POST['epost']) {
		
		if($resultat['success'] = $autoriserer->aldonuUzanto(array(
			'id'			=> $this->bruker['id'],
			'uzanto'		=> $_POST['login'],
			'nomo'			=> $this->navn($this->bruker['id']),
			'pasvorto'		=> $_POST['pw1'],
			'retpostadreso'	=> $_POST['epost']
		))) {
			$resultat['msg'] = "";
			
			$this->mysqli->query(
				"UPDATE personer\n"
			.	"SET epost = '{$this->POST['epost']}',\n"
			.	(isset($_POST['fødselsdato']) ? ("fødselsdato = " . $this->strengellernull($this->tolkDato($_POST['fødselsdato' . $a])) . ",\n") : "")
			.	(isset($_POST['personnr']) ? "personnr = '{$this->POST['personnr']}',\n" : "")
			.	"adresse1 = '{$this->POST['adresse1']}',\n"
			.	"adresse2 = '{$this->POST['adresse2']}',\n"
			.	"postnr = '{$this->POST['postnr']}',\n"
			.	"poststed = '{$this->POST['poststed']}',\n"
			.	"land = '{$this->POST['land']}',\n"
			.	"telefon = '{$this->POST['telefon']}',\n"
			.	"mobil = '{$this->POST['mobil']}'\n"
			.	"WHERE personid = '{$this->bruker['id']}'");

			$leieforholdsett = $this->arrayData("SELECT *\n"
				.	"FROM adganger\n"
				.	"WHERE adgang = 'beboersider'\n"
				.	"AND personid ='{$this->bruker['id']}'");
				
			foreach($leieforholdsett['data'] as $leieforhold) {
				$sql =	"UPDATE adganger\n"
					.	"SET ";
				$sql .=	"epostvarsling = " . ($_POST["epostvarsling{$leieforhold['leieforhold']}"] ? "1" : "0") . ",\n";
				$sql .=	"forfallsvarsel = " . ($_POST["forfallsvarsel{$leieforhold['leieforhold']}"] ? "1" : "0") . ",\n";
				$sql .=	"innbetalingsbekreftelse = " . ($_POST["innbetalingsbekreftelse{$leieforhold['leieforhold']}"] ? "1" : "0") . "\n";
				$sql .=	"WHERE adgang = 'beboersider'\n"
					.	"AND leieforhold = '{$leieforhold['leieforhold']}'\n"
					.	"AND personid ='{$this->bruker['id']}'";
				$this->mysqli->query($sql);
			}

		}
		else {
			$resultat['msg'] = "KLarte ikke å lagre. ";
		}
	}
	
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		default:
			$datasett = $this->arrayData($this->hoveddata);

			$autoriserer = new $this->autoriserer;
			$datasett['data'][0]['login'] = $autoriserer->trovuUzantoNomo($datasett['data'][0]['personid']);

			return json_encode($datasett);
	}
}

}
?>