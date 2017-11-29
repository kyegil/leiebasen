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
	if(!$id = $_GET['id']) die("Ugyldig oppgave: ID ikke angitt for kontrakt");
	$this->hoveddata = "SELECT * FROM kontrakter WHERE kontraktnr = $id";
}


function skript() {
	$trinn = $_GET['trinn'];
	if(!isset($_POST['etternavn'])) $trinn = 1;
	
	switch($trinn) { // evaluering av oppgitte parametere før du sendes til riktig trinn
	case "":
		$trinn = 1;
		break;
	}

	switch($trinn) {
// ******************************  --- Trinn 1 ---  ******************************
	case 1:
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();

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

	var er_org_rute = new Ext.form.Checkbox({
		boxLabel: 'Er enhet / organisasjon',
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
		store: fornavnliste,
		typeAhead: true
	});
   
	var etternavnfelt = new Ext.form.ComboBox({		allowBlank: false,
		displayField: 'etternavn',
		editable: true,
		fieldLabel: 'Etternavn / navn på enhet',
		hideTrigger : true,
		mode: 'local',
		name: 'etternavn',
		store: etternavnliste,
		typeAhead: true
	});
	
	var skjema = new Ext.FormPanel({
		labelAlign: 'top',
		frame:true,
		title: 'Legg til personer i leieavtalen',
		bodyStyle:'padding:5px 5px 0',
		standardSubmit: true,
		width: 600,
		items: [{
			html:'Skriv inn fornavn og etternavn på personen som skal tilføyes i leieavtalen, evt navn på firma eller enhet som skal stå som leietaker.<br /><br />'
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
				window.location = '<?=$this->returi->get();?>';
			},
			text: 'Avbryt'
		}, {
			handler: function() {
				skjema.getForm().getEl().dom.action = 'index.php?oppslag=kontrakt_nyeleietakere&trinn=2&id=<?=$_GET['id']?>';
				skjema.getForm().submit();
			},
			text: 'Fortsett'
		}]
	});
	
<?
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
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();

	var leietakerliste = new Ext.data.Store({
		data: <?=json_encode($this->arrayData("SELECT personid, CONCAT(IF(er_org, etternavn, CONCAT(fornavn, ' ', etternavn)), ' - ', personid) AS navn FROM `personer` ORDER BY etternavn"));?>,
		reader: new Ext.data.JsonReader({
			fields: ['personid', 'navn'],
			root: 'data'
		})
	});

	var skjema = new Ext.FormPanel({
		labelAlign: 'top',
		frame:true,
		title: 'Legg til personer i leieavtalen',
		bodyStyle:'padding:5px 5px 0',
		standardSubmit: true,
		width: 600,
		items: [
<?
		$treff = $this->arrayData("SELECT * FROM personer WHERE " . (!$_POST['er_org'] ? "fornavn = '" . $_POST['fornavn'] . "' AND " : "") . "etternavn = '" . $_POST['etternavn'] . "'");
		if ($treff['data']) {
?>
		{
			html: "Det ble funnet et adressekort for <b><?=(!$_POST['er_org'] ? ($_POST['fornavn'] . ' ') : '') . $_POST['etternavn']?></b> i leiebasen. Er dette samme <?=($_POST['er_org'] ? 'virksomhet' : 'person')?>, eller en ny?<br />Du bør evt søke fram riktig oppføring selv dersom du vet at leietakeren allerede er registrert, for å unngå dobbeltregistreringer.<br /><br />OBS: Evt. feilaktige opplysninger om adresse, telefonnummer, e-postadresse etc. kan korrigeres senere.<br /><br />"
		},
<?
			}
			else {
?>
		{
			html: "Det ble ikke funnet noe eksisterende adressekort for <b><?=(!$_POST['er_org'] ? ($_POST['fornavn'] . ' ') : '') . $_POST['etternavn']?></b>. Dersom du tror <?=($_POST['er_org'] ? 'virksomheten' : 'personen')?> har vært registrert i leiebasen tidligere bør du prøve å slå opp adressekortet for å unngå dobbeltregistreringer.<br /><br />OBS: Evt. feilaktige opplysninger om adresse, telefonnummer, e-postadresse etc. kan korrigeres senere.<br /><br />"
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
						. ($verdi['er_org'] ? ('org. nr: ' . $verdi['personnr']) : ('f. ' . date('d.m.Y', strtotime($verdi['fødselsdato'])))) . "<br />"
						. $verdi['adresse1'] . "<br />" . $verdi['adresse2'] . "<br />" . $verdi['postnr'] . " " . $verdi['poststed'] . "<br /><br />'\n";
?>
				}]
			},
			{
				columnWidth: 0.6,
				layout: 'form',
				items: [{
					xtype: 'radio',
					boxLabel: "Ja. <?=($_POST['er_org'] ? 'Virksomheten' : 'Personen')?> oppført til venstre er den som skal inn i leieavtalen",
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
				items: [{
					xtype: 'radio',
					boxLabel: "Bruk følgende adressekort:",
					hideLabel: true,
					inputValue: true,
					name: 'adressekort'
				}]
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
					store: leietakerliste,
					triggerAction: 'all',
					typeAhead: false,
					valueField: 'personid'
				}]
			}]
		},
		{
			xtype: 'radio',
			boxLabel: "Nei. <?=(!$_POST['er_org'] ? ($_POST['fornavn'] . ' ') : '') . $_POST['etternavn']?> er ikke registrert tidligere. Opprett nytt adressekort",
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
			name: 'etternavn<?=$a?>',
			value: '<?=$_POST['etternavn']?>'
		},
		{
			xtype: 'hidden',
			name: 'er_org<?=$a?>',
			value: '<?=$_POST['er_org']?>'
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
				window.location = '<?=$this->returi->get();?>';
			},
			text: 'Avbryt'
		}, {
			handler: function() {
				skjema.getForm().getEl().dom.action = 'index.php?oppslag=kontrakt_nyeleietakere&trinn=3&id=<?=$_GET['id']?>';
				skjema.getForm().submit();
			},
			text: 'Fortsett'
		}]

	});
	
	skjema.render('panel');

});
<?
		break;
// ******************************  --- Trinn 3 ---  ******************************
	case 3:
		if($_POST['adressekort'] and !(int)$_POST['adressekort'] and $_POST['personkombo']) {
			$kort['personid'] = $_POST['personkombo'];
		}
		else {
			$kort['personid'] = $_POST['adressekort'];				
		}
		if(!$kort['personid']) {
			$kort['etternavn'] = $_POST['etternavn'];
			$kort['er_org'] = $_POST['er_org'];
			if(!$kort['er_org']) $kort['fornavn'] = $_POST['fornavn'];
		}
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();

	var skjema = new Ext.FormPanel({
		labelAlign: 'top',
		frame:true,
		title: 'Legg til personer i leieavtalen',
		bodyStyle:'padding:5px 5px 0',
		standardSubmit: false,
		width: 500,
		items: [
			{
			html: "Gå igjennom, fyll ut eller korriger opplysningene i adressekortene under før registrering. Opplysingene kan også endres senere ved å redigere adressekortet.<br /><br />"
			},
			{
				layout: 'column',
				items: [
<?
		if($kort['personid']) {
			$kortdata = $this->arrayData("SELECT * FROM personer WHERE personid = ". $kort['personid']);
			$kort = $kortdata['data'][0];
		}
		else {
			$kortdata = $this->arrayData(
				"SELECT
				IF(leieobjekter.navn <> '', leieobjekter.navn, leieobjekter.gateadresse) AS adresse1,
				IF(leieobjekter.navn = '', '', leieobjekter.gateadresse) AS adresse2,
				leieobjekter.postnr,
				leieobjekter.poststed,
				'Norge' AS land
				FROM kontrakter INNER JOIN leieobjekter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr
				WHERE kontrakter.kontraktnr = {$this->GET['id']}"
			);
			$kort = array_merge($kort, $kortdata['data'][0]);
		}
?>
				{
					layout: 'form',
					items: [
						{
							html: '<?='<b>' . ($kort['er_org'] ? $kort['etternavn'] : ($kort['fornavn'] . ' ' . $kort['etternavn'])) . ((!$kort['er_org'] and $kort['fødselsdato']) ? (' f. ' . date('d.m.Y', strtotime($kort['fødselsdato']))) : '') . (($kort['personnr']) ? (($kort['er_org'] ? ' org. ' : ' ') . $kort['personnr']) : '') . '</b><br /><br />'?>'
						},
						{
							xtype: 'hidden',
							name: 'polett',
							value: '<?=$_POST['polett']?>'
						},
						{
							xtype: 'hidden',
							name: 'adressekort',
							value: '<?=$kort['personid']?>'
						},
						{
							xtype: 'hidden',
							name: 'er_org',
							value: '<?=$kort['er_org']?>'
						},
						{
							xtype: 'hidden',
							name: 'fornavn',
							value: '<?=$kort['fornavn']?>'
						},
						{
							xtype: 'hidden',
							name: 'etternavn',
							value: '<?=$kort['etternavn']?>'
						},
						{
							xtype: 'datefield',
							fieldLabel: '<?=(!$kort['fødselsdato'] and !$kort['er_org']) ? 'Fødselsdato' : ''?>',
							labelSeparator: '<?=(!$kort['fødselsdato'] and !$kort['er_org']) ? ':' : ''?>',
							hidden: <?=(!$kort['fødselsdato'] and !$kort['er_org']) ? 'false' : 'true'?>,
							name: 'fødselsdato',
							format: 'd.m.Y',
							altFormats: "j.n.y|j.n.Y|j/n/y|j/n/Y|j-n-y|j-n-Y|j. M y|j. M -y|j. M. Y|j. F -y|j. F y|j. F Y|j/n|j-n|dm|dmy|dmY|d|Y-m-d",
							value: '<?=$kort['fødselsdato']?>',
							width: 190
						},
						{
							xtype: '<?=$kort['personnr'] ? 'hidden' : 'textfield'?>',
							fieldLabel: '<?=$kort['er_org'] ? 'Org.nr.' : 'Personnummer'?>',
							name: 'personnr',
							value: '<?=$kort['personnr']?>',
							width: 190
						},
						{
							xtype: 'textfield',
							fieldLabel: 'Adresse',
							name: 'adressea',
							value: '<?=$kort['adresse1']?>',
							width: 190
						},
						{
							xtype: 'textfield',
							labelSeparator: '',
							name: 'adresseb',
							value: '<?=$kort['adresse2']?>',
							width: 190
						},
						{
							xtype: 'textfield',
							fieldLabel: 'Postnr',
							name: 'postnr',
							value: '<?=$kort['postnr']?>',
							width: 50
						},
						{
							xtype: 'textfield',
							fieldLabel: 'Poststed',
							name: 'poststed',
							value: '<?=$kort['poststed']?>',
							width: 190
						},
						{
							xtype: 'textfield',
							fieldLabel: 'Land',
							name: 'land',
							value: '<?=$kort['land']?>',
							width: 50
						},
						{
							xtype: 'textfield',
							fieldLabel: 'Telefon',
							name: 'telefon',
							value: '<?=$kort['telefon']?>',
							width: 190
						},
						{
							xtype: 'textfield',
							fieldLabel: 'Mobil',
							name: 'mobil',
							value: '<?=$kort['mobil']?>',
							width: 190
						},
						{
							xtype: 'textfield',
							fieldLabel: 'Epost',
							name: 'epost',
							value: '<?=$kort['epost']?>',
							width: 190
						}					],
					width: 200
					}
<?
?>
				]
			},
			{
				html: '<hr />'
			}
		],
		buttons: [{
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
			},
			text: 'Avbryt'
		}, {
			handler: function() {
				skjema.getForm().getEl().dom.action = 'index.php?oppslag=kontrakt_nyeleietakere&oppdrag=taimotskjema&id=<?=$_GET['id']?>';
				skjema.getForm().submit();
			},
			text: 'Lagre adressekortet og legg personen til i leieavtalen'
		}]

	});
	
	skjema.render('panel');

	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Ingen JSON');
				} else {
					Ext.MessageBox.alert('Vellykket', Ext.decode(action.response.responseText).msg, function(){
						window.location = '<?=$this->returi->get();?>';
					});
				}
			}
		},
							

		actionfailed: function(form,action){
			if(action.type == 'submit') {
				var result = Ext.decode(action.response.responseText);
				if(result && result.msg) {			
					Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', result.msg, function(){
						window.location = '<?=$this->returi->get();?>';
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

	if(!$this->brukPolett($_POST['polett'])) {
		$resultat['success'] = false;		
		$resultat['msg'] .= "Kunne ikke lagre fordi engangspoletten enten er brukt eller for gammel.<br /><br />Dette kan komme av at du allerede har opprettet denne leieavtalen (for deretter ved en feil ha klikket deg inn på nettsiden på nytt), eller at du har brukt mer enn et døgn på å opprette den. Du kan evt. forsøke en gang til.";
		echo json_encode($resultat);
		return;

	}

	if($_POST['adressekort']) {
		$sql = "UPDATE personer"
		.	" SET fornavn = '" . $_POST['fornavn'] . "',"
		.	" etternavn = '" . $_POST['etternavn'] . "',"
		.	" er_org = '" . (bool)$_POST['er_org'] . "',"
		.	" fødselsdato = " . $this->strengellernull($this->tolkDato($_POST['fødselsdato'])) . ","
		.	" personnr = '" . $_POST['personnr'] . "',"
		.	" adresse1 = '" . $_POST['adressea'] . "',"
		.	" adresse2 = '" . $_POST['adresseb'] . "',"
		.	" postnr = '" . $_POST['postnr'] . "',"
		.	" poststed = '" . $_POST['poststed'] . "',"
		.	" land = '" . $_POST['land'] . "',"
		.	" telefon = '" . $_POST['telefon'] . "',"
		.	" mobil = '" . $_POST['mobil'] . "',"
		.	" epost = '" . $_POST['epost'] . "'"
		.	" WHERE personid = " . $_POST['adressekort'];
		$person = $_POST['adressekort'];
		if($this->mysqli->query($sql)) {
			$resultat['success'] = true;
			$resultat['msg'] .= "Oppdatert adressekortet til " . ($_POST['er_org'] ? $_POST['etternavn'] : ($_POST['fornavn'] . " " . $_POST['etternavn'])) . "<br />";
		}
		else {
			$resultat['success'] = false;
			$resultat['msg'] .= "Mislyktes i å oppdatere adressekortet til " . ($_POST['er_org'] ? $_POST['etternavn'] : ($_POST['fornavn'] . " " . $_POST['etternavn'])) . "<br />";
			echo json_encode($resultat);
			return;
		}
	}
	else {
		$sql = "INSERT INTO personer"
		.	" SET fornavn = '" . $_POST['fornavn'] . "',"
		.	" etternavn = '" . $_POST['etternavn'] . "',"
		.	" er_org = '" . (bool)$_POST['er_org'] . "',"
		.	" fødselsdato = " . $this->strengellernull($this->tolkDato($_POST['fødselsdato'])) . ","
		.	" personnr = '" . $_POST['personnr'] . "',"
		.	" adresse1 = '" . $_POST['adressea'] . "',"
		.	" adresse2 = '" . $_POST['adresseb'] . "',"
		.	" postnr = '" . $_POST['postnr'] . "',"
		.	" poststed = '" . $_POST['poststed'] . "',"
		.	" land = '" . $_POST['land'] . "',"
		.	" telefon = '" . $_POST['telefon'] . "',"
		.	" mobil = '" . $_POST['mobil'] . "',"
		.	" epost = '" . $_POST['epost'] . "'";
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

	$sql="INSERT INTO kontraktpersoner "
	.	"SET person = '$person', "
	.	"kontrakt = '" . $_GET['id'] . "'";
	if($this->mysqli->query($sql)) {
		$resultat['success'] = true;
		$resultat['kontraktnr'] = $_GET['id'];
	}
	else {
		$resultat['success'] = false;
		$resultat['msg'] .= "Klarte ikke å legge  " . ($_POST['er_org'] ? $_POST['etternavn'] : ($_POST['fornavn'] . " " . $_POST['etternavn'])) . " til i leieavtalen.<br />";
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