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
}


function skript() {
	$trinn = isset( $_GET['trinn'] ) ? (int)$_GET['trinn'] : null;
	if(!isset($_POST['etternavn'])) $trinn = 1;
	
	switch($trinn) { // evaluering av oppgitte parametere før du sendes til riktig trinn
	case null:
		$trinn = 1;
		break;
	}

	switch($trinn) {
// ******************************  --- Trinn 1 ---  ******************************
	case 1:
?>

Ext.onReady(function() {
	Ext.QuickTips.init();
<?
	include_once("_menyskript.php");
?>

	var leieforholdliste = new Ext.data.Store({
		data: <?=json_encode($this->arrayData("SELECT leieforhold, CONCAT(leieforhold, ' | leieobjekt ', leieobjekt, ' ', gateadresse) AS beskrivelse FROM kontrakter INNER JOIN kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt LEFT JOIN leieobjekter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr WHERE person = {$this->bruker['id']} GROUP BY leieforhold, leieobjekt"));?>,
		reader: new Ext.data.JsonReader({
			fields: ['leieforhold', 'beskrivelse'],
			root: 'data'
		})
	});
	
	var fornavnliste = new Ext.data.Store({
		data: <?=json_encode($this->arrayData("SELECT fornavn FROM personer GROUP BY fornavn"));?>,
		reader: new Ext.data.JsonReader({
			fields: ['fornavn'],
			root: 'data'
		})
	});
	
	var etternavnliste = new Ext.data.Store({
		data: <?=json_encode($this->arrayData("SELECT etternavn FROM personer GROUP BY etternavn"));?>,
		reader: new Ext.data.JsonReader({
			fields: ['etternavn'],
			root: 'data'
		})
	});

	var polett = new Ext.form.Hidden({
		name: 'polett',
		value: '<?=$this->opprettPolett();?>'
	});

	var leieforhold = new Ext.form.ComboBox({		autoSelect: true,
		allowBlank: false,
		displayField: 'beskrivelse',
		editable: true,
		fieldLabel: 'Leieforhold',
		forceSelection: true,
		hiddenName: 'leieforhold',
		mode: 'local',
		name: 'leieforhold',
		selectOnFocus: true,
		store: leieforholdliste,
		triggerAction: 'all',
		typeAhead: true,
		value: leieforholdliste.getAt(0).get('leieforhold'),
		valueField: 'leieforhold',
		width: 700
	});

	var er_org_rute = new Ext.form.Checkbox({
		boxLabel: 'Firma / organisasjon',
		checked: false,
		fieldLabel: 'Enhet',
		hideLabel: false,
		name: 'er_org'
	});
   
	var fornavnfelt = new Ext.form.ComboBox({		disabled: false,
		displayField: 'fornavn',
		editable: true,
		fieldLabel: 'Fornavn, evt mellomnavn',
		height: 0,
		hideTrigger : true,
		mode: 'local',
		name: 'fornavn',
		queryDelay: 100,
		store: fornavnliste,
		typeAhead: true
	});
   
	var etternavnfelt = new Ext.form.ComboBox({		allowBlank: false,
		displayField: 'etternavn',
		editable: true,
		fieldLabel: 'Etternavn, evt. org/firmanavn',
		hideTrigger : true,
		mode: 'local',
		name: 'etternavn',
		store: etternavnliste,
		typeAhead: true
	});
	
	var skjema = new Ext.FormPanel({
		labelAlign: 'top',
		frame:true,
		title: 'Tildel adgang til leieavtale i beboersidene',
		bodyStyle:'padding:5px 5px 0',
		standardSubmit: true,
		height: 500,
		width: 900,
		items: [leieforhold,
		{
			html:'Skriv inn fornavn og etternavn på personen som skal ha adgang til leieforholdet.<br /><br />'
		}, {
			layout:'column',
			items:[{
				columnWidth: 0.2,
				layout: 'form',
				items: [er_org_rute]
			},
			{
				columnWidth: 0.45,
				layout: 'form',
				items: [fornavnfelt]
			},
			{
				columnWidth: 0.35,
				layout: 'form',
				items: [etternavnfelt]
			}]
		}, {
			html:'<hr />'
		}, polett],

		buttons: [{
			handler: function() {
				window.location = "<?=$this->returi->get();?>";
			},
			text: 'Avbryt'
		}, {
			handler: function() {
				skjema.getForm().getEl().dom.action = 'index.php?oppslag=adgang_opprett&trinn=2';
				skjema.getForm().submit();
			},
			text: 'Fortsett'
		}]
	});
	
<?
	$personer = 0;
	for ($a = 1; $a<= $personer; $a++) {
?>
	er_org_rute.on({
		check: function(a, checked){
			if(checked == true) fornavnfelt.setDisabled(true);
			else fornavnfelt.setDisabled(false);
		}
	})

<?
	}
?>
	skjema.render('panel');

});
<?
		break;
// ******************************  --- Trinn 2 ---  ******************************
	case 2:
?>

Ext.onReady(function() {
	Ext.QuickTips.init();
<?
	include_once("_menyskript.php");
?>

	var leietakerliste = new Ext.data.Store({
		data: <?=json_encode($this->arrayData("SELECT personid, CONCAT(IF(er_org, etternavn, CONCAT(fornavn, ' ', etternavn)), ' - ', personid) AS navn FROM `personer` ORDER BY etternavn"));?>,
		reader: new Ext.data.JsonReader({
			fields: ['personid', 'navn'],
			root: 'data'
		})
	});

	var adressekort = new Ext.form.Radio({
		boxLabel: "Jeg finner riktig person i nedtrekkslista til høyre:",
		hideLabel: true,
		inputValue: true,
		name: 'adressekort'
	});

	var skjema = new Ext.FormPanel({
		labelAlign: 'top',
		frame:true,
		title: 'Tildel adgang til leieavtale i beboersidene',
		bodyStyle:'padding:5px 5px 0',
		standardSubmit: false,
		height: 500,
		width: 900,
		items: [
<?
		$treff = $this->arrayData("SELECT * FROM personer WHERE " . (!isset($_POST['er_org']) ? "fornavn = '" . $_POST['fornavn'] . "' AND " : "") . "etternavn = '" . $_POST['etternavn'] . "'");
		if ($treff['data']) {
?>
		{
			html: "Det ble funnet et adressekort for <b><?=(!isset($_POST['er_org']) ? ($_POST['fornavn'] . ' ') : '') . $_POST['etternavn']?></b>.<br />Er dette riktig <?=(isset($_POST['er_org']) ? 'virksomhet' : 'person')?>?<br /><br />OBS: Evt. feilaktige opplysninger om adresse, telefonnummer, e-postadresse etc. kan korrigeres senere.<br /><br />"
		},
<?
			}
			else {
?>
		{
			html: "<b><?=(!isset($_POST['er_org']) ? ($_POST['fornavn'] . ' ') : '') . $_POST['etternavn']?></b> ble ikke funnet i adresselista. Dersom du tror <?=(isset($_POST['er_org']) ? 'virksomheten' : 'personen')?> er registrert tidligere bør du prøve å finne adressekortet i nedtrekkslista under.<br /><br />"
		},
<?
			}
			foreach($treff['data'] as $linje => $verdi) {
?>
		{
			layout:'column',
			items:[
			{
				columnWidth: 0.4,
				layout: 'form',
				items: [{
<?
					echo "					html: '<b>Oppføring:</b><br />" . (!$verdi['er_org'] ? ($verdi['fornavn'] . ' ') : '') . $verdi['etternavn'] . "<br />"
						. ($verdi['er_org'] ? ('org. nr: ' . $verdi['personnr'] . "<br />") : ($verdi['fødselsdato'] ? ('f. ' . date('d.m.Y', strtotime($verdi['fødselsdato'])) . "<br />") : ""))
						. ($verdi['adresse1'] ? "{$verdi['adresse1']}<br />" : "") . ($verdi['adresse2'] ? "{$verdi['adresse2']}<br />" : "") . "{$verdi['postnr']} {$verdi['poststed']}<br /><br />'\n";
?>
				}]
			},
			{
				columnWidth: 0.6,
				layout: 'form',
				items: [{
					xtype: 'radio',
					boxLabel: "Ja. <?=(isset($_POST['er_org']) ? 'Virksomheten' : 'Personen')?> oppført til venstre er den som skal ha adgang til mitt leieforhold",
					checked: <?=!$linje ? 'true' : 'false'?>,
					hideLabel: true,
					inputValue: <?=$treff['data'][$linje]['personid']?>,
					name: 'adressekort'
				}
				]
			}
			]
		},
<?
			}
?>
		{
			layout:'column',
			items:[{
				columnWidth: 0.4,
				layout: 'form',
				items: [adressekort]
			},
			{
				columnWidth: 0.6,
				layout: 'form',
				items: [{
					xtype: 'combo',
					forceSelection: true,
					hideLabel: true,
					mode: 'local',
					name: 'personkombo',
					displayField: 'navn',
					editable: true,
					hiddenName: 'personkombo',
					listeners: {
						'change': function(combo, newValue, OldValue){
							adressekort.setValue(true);
						}
					},
					store: leietakerliste,
					triggerAction: 'all',
					typeAhead: false,
					valueField: 'personid'
				}]
			}]
		},
		{
			xtype: 'radio',
			boxLabel: "<?=(!isset($_POST['er_org']) ? ($_POST['fornavn'] . ' ') : '') . $_POST['etternavn']?> er ikke registrert fra før. Opprett nytt adressekort",
			checked: <?= !$treff['data'] ? 'true' : 'false' ?>,
			hideLabel: true,
			inputValue: 0,
			name: 'adressekort'
		},
		{
			xtype: 'hidden',
			name: 'fornavn',
			value: '<?=$_POST['fornavn']?>'
		},
		{
			xtype: 'hidden',
			name: 'etternavn',
			value: '<?=$_POST['etternavn']?>'
		},
		{
			xtype: 'hidden',
			name: 'er_org',
			value: '<?=isset($_POST['er_org']) ? (int)$_POST['er_org'] : ""?>'
		},
		{
			xtype: 'hidden',
			name: 'leieforhold',
			value: '<?=$_POST['leieforhold']?>'
		},
		{
			xtype: 'hidden',
			name: 'polett',
			value: '<?=$_POST['polett']?>'
		},
		{
			html: '<hr /><br />'
		}],

		buttons: [{
			handler: function() {
				window.location = "<?=$this->returi->get();?>";
			},
			text: 'Avbryt'
		}, {
			handler: function() {
				skjema.getForm().getEl().dom.action = 'index.php?oppslag=adgang_opprett&oppdrag=taimotskjema';
				skjema.getForm().submit();
			},
			text: 'Opprett adgang'
		}]

	});
	
	skjema.render('panel');

	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Ingen JSON');
				} else {
					Ext.MessageBox.alert('Vellykket', action.result.msg, function(){
						window.location = 'index.php?oppslag=adgang_skjema';
					});
				}
			}
		},
							

		actionfailed: function(form,action){
			if(action.type == 'submit') {
				var result = Ext.decode(action.response.responseText);
				if(result && result.msg) {			
					Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', action.result.msg, function(){
						window.location = 'index.php?oppslag=adgang_skjema';
					});
				}
				else {
					Ext.MessageBox.alert('Problem:', 'Lagring av data mislyktes av ukjent grunn. Action type='+action.type+', failure type='+action.failureType);
				}
			}
			
		} // end actionfailed listener
	}); // end skjema.on
});
<?
		break;
	}
}

function design() {
?>
<div id="panel"></div>
<?
}

function taimotSkjema() {
	$resultat = array(
		'success'	=> false,
		'msg'		=> ""
	);
	if(!$this->brukPolett($_POST['polett'])) {
		$resultat['success'] = false;
		$resultat['msg'] .= "Kunne ikke opprette denne adgangen fordi engangspoletten enten er brukt eller for gammel.<br /><br />Dette kan komme av at du allerede har opprettet denne adgangen (for deretter ved en feil ha klikket deg inn på nettsiden på nytt), eller at du har brukt mer enn et døgn på å opprette den. Du kan evt. forsøke en gang til.";
		echo json_encode($resultat);
		return;

	}

	if(!$_POST['adressekort']){
		$sql = "INSERT INTO personer"
		.	" SET fornavn = '" . $this->mysqli->real_escape_string($_POST['fornavn']) . "',"
		.	" etternavn = '" . $this->mysqli->real_escape_string($_POST['etternavn']) . "',"
		.	" er_org = '" . (bool)$_POST['er_org'] . "',"
		.	" epost = '" . (isset($_POST['epost']) ? $this->POST['epost'] : "") . "'";
		if($this->mysqli->query($sql)) {
			$person = $this->mysqli->insert_id;
			$resultat['success'] = true;
			$resultat['msg'] .= "Nytt adressekort opprettet for " . ($_POST['er_org'] ? $_POST['etternavn'] : ($_POST['fornavn'] . " " . $_POST['etternavn'])) . "<br />";
		}
		else {
			$resultat['success'] = false;
			$resultat['msg'] .= "Mislyktes i å opprette nytt adressekort for " . ($_POST['er_org'] ? $_POST['etternavn'] : ($_POST['fornavn'] . " " . $_POST['etternavn'])) . "<br />";
			echo json_encode($resultat);
			return;
		}
	}
	else {
		if($_POST['adressekort'] == 'true' and (int)$_POST['personkombo'])
			$person = (int)$_POST['personkombo'];
		else
			$person = (int)$_POST['adressekort'];
	}

	$sql =	"INSERT INTO adganger\n"
		.	"SET personid = $person,\n"
		.	"leieforhold = " . (int)$_POST['leieforhold'] . ",\n"
		.	"adgang = 'beboersider',\n"
		.	"epostvarsling = 1, innbetalingsbekreftelse = 1, forfallsvarsel = 1";
	if($this->mysqli->query($sql)) {
		$resultat['success'] = true;
		$resultat['msg'] .= $this->navn($person) . " har nå adgang til leieforhold " . (int)$_POST['leieforhold'] . ".<br />" . $this->navn($person) . " må imidlertid selv ta kontakt med administrasjonen / drift for å få brukernavn og passord for beboersidene dersom hun/han ikke har det fra før. ";
	}
	else {
		$resultat['success'] = false;
		$resultat['msg'] .= "Klarte ikke å opprette adgang for " . $this->navn($person) . ".<br />";
	}
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		default:
			break;
	}
}

}
?>