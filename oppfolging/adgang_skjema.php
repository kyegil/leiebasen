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
	
	if(!$id = $_GET['id']) die("Ugyldig oppslag: ID ikke angitt");
	
	$this->hoveddata = "SELECT personer.personid, personer.epost, adganger.adgang, adganger.leieforhold, adganger.epostvarsling, adganger.innbetalingsbekreftelse, adganger.forfallsvarsel\n"
		.	"FROM personer LEFT JOIN adganger ON personer.personid = adganger.personid AND adgangsid = '{$this->GET['id']}'\n"
		.	"WHERE personer.personid = '{$this->GET['personid']}'\n";
}

function skript() {
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';
	
	function aktiver(){
		if(adgang.value == "beboersider"){
			leieforhold.enable();
//			epostvarsling.enable();
			if(epostvarsling.checked){
				innbetalingsbekreftelse.enable();
				forfallsvarsel.enable();
			}
			else{
				innbetalingsbekreftelse.disable();
				forfallsvarsel.disable();
			}
		}
		else{
			leieforhold.disable();
			leieforhold.clearInvalid();
//			epostvarsling.disable();
			innbetalingsbekreftelse.disable();
			forfallsvarsel.disable();
		}
	}

	bekreftSletting = function(id) {
		Ext.Msg.show({
			title: 'Bekreft',
			id: id,
			msg: 'Er du sikker på at du vil slette <?=$this->navn($_GET['personid'])?>s adgang til ' + (adgang.value == "beboersider" ? ("beboersidene for dette leieforholdet") : adgang.value) + '?',
			buttons: Ext.Msg.OKCANCEL,
			fn: function(buttonId, text, opt){
				if(buttonId == 'ok') {
					utførSletting();
				}
			},
			animEl: 'elId',
			icon: Ext.MessageBox.QUESTION
		});
	}


	utførSletting = function(){
		Ext.Ajax.request({
			waitMsg: 'Sletter...',
			url: 'index.php?oppslag=adgang_skjema&oppdrag=oppgave&oppgave=slett&personid=<?=$_GET['personid']?>&id=<?=$_GET['id']?>',
			success: function(response, options){
				var tilbakemelding = Ext.util.JSON.decode(response.responseText);
				if(tilbakemelding['success'] == true) {
					Ext.MessageBox.alert('Utført', tilbakemelding['msg'], function(){
						window.location = '<?=$this->returi->get();?>';				
					});
				}
				else {
					Ext.MessageBox.alert('Hmm..',tilbakemelding['msg']);
				}
			}
		});
	}

	var adgang = new Ext.form.ComboBox({
		allowBlank: false,
		displayField: 'verdi',
		editable: true,
		forceSelection: false,
		fieldLabel: 'Adgangsområde',
		listeners: {'select': aktiver},
		mode: 'local',
		name: 'adgang',
		selectOnFocus: true,
		store: new Ext.data.SimpleStore({
			fields: ['verdi'],
			data : [["beboersider"],["drift"],["flyko"],["oppfolging"]]

		}),
		triggerAction: 'all',
		typeAhead: false,
		value: 'oppfolging',
		valueField: 'verdi',
		width: 200
	});

	var leieforhold = new Ext.form.ComboBox({
		name: 'leieforhold',
		displayField: 'visningsfelt',
		hiddenName: 'leieforhold',
		valueField: 'leieforhold',
		mode: 'remote',
		store: new Ext.data.JsonStore({
			fields: [{name: 'leieforhold'},{name: 'visningsfelt'}],
			root: 'data',
			url: 'index.php?oppslag=adgang_skjema&personid=<?="{$_GET['personid']}&id={$_GET['id']}";?>&oppdrag=hentdata&data=leieforhold'
		}),
		fieldLabel: 'Leieforhold',
		allowBlank: false,
		editable: true,
		forceSelection: true,
		minChars: 0,
		selectOnFocus: true,
		triggerAction: 'all',
		typeAhead: true,
		width: 400
	});

	var epostvarsling = new Ext.form.Checkbox({
		boxLabel: 'Send meg epost herfra.',
		checked: true,
		fieldLabel: 'Epostvarsling',
		listeners: {'check': aktiver},
		name: 'epostvarsling',
		width: 600
	});

	var innbetalingsbekreftelse = new Ext.form.Checkbox({
		boxLabel: 'Send også melding bekreftelse på registrerte innbetalinger',
		checked: true,
		fieldLabel: 'Innbetalingsbekreftelse',
		name: 'innbetalingsbekreftelse',
		width: 600
	});

	var forfallsvarsel = new Ext.form.Checkbox({
		boxLabel: 'Send også påminnelse om krav som er i ferd med å forfalle til betaling',
		checked: true,
		fieldLabel: 'Forfallsvarsel',
		name: 'forfallsvarsel',
		width: 600
	});

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

	var brukerprofil = new Ext.form.FieldSet({
		title: 'Brukernavn og passord',
		layout: 'column',
		items: [{
			layout: 'form',
			columnWidth: 0.5,
			items: [
				{html: 'Endringer her vil påvirke alle <?=$this->navn($_GET['personid'])?>s adgangsområder'},
				login,
				epost
			]
		}, {
			layout: 'form',
			columnWidth: 0.5,
			items: [pw1, pw2]
		}]
	});

	var skjema = new Ext.FormPanel({
		autoScroll: true,
		bodyStyle:'padding:5px 5px 0',
		buttons: [],
		frame:true,
		height: 500,
		items: [adgang, leieforhold, epostvarsling, innbetalingsbekreftelse, forfallsvarsel, brukerprofil],
		labelAlign: 'top', // evt right
		reader: new Ext.data.JsonReader({
			defaultType: 'textfield',
			fields: [login, epost, adgang, leieforhold, epostvarsling, innbetalingsbekreftelse, forfallsvarsel],
			root: 'data'
		}),
		standardSubmit: false,
		title: 'Nettadgang for <?=$this->navn($_GET['personid'])?>',
		width: 900
	});

	skjema.addButton('Avbryt', function(){
		window.location = '<?=$this->returi->get();?>';
	});

	<?=(int)$_GET['id'] ? ("skjema.addButton('Slett denne adgangen', function() {bekreftSletting(" . $this->GET['id'] . ");});") : "";?>

	
	var lagreknapp = skjema.addButton({
		text: 'Lagre',
		disabled: true,
		handler: function(){
			skjema.form.submit({
				url:'index.php?oppslag=<?="{$_GET['oppslag']}&personid={$_GET["personid"]}&id={$_GET["id"]}";?>&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...'
				});
		}
	});

	skjema.render('panel');

	skjema.getForm().load({
		url:'index.php?oppslag=<?="{$_GET['oppslag']}&personid={$_GET["personid"]}&id={$_GET["id"]}";?>&oppdrag=hentdata', waitMsg:'Henter opplysninger...'
	});

	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'load'){
				lagreknapp.enable();
				aktiver();
		leieforhold.doQuery();
			}
			
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Mottok ikke bekreftelsesmelding fra tjeneren  i JSON-format som forventet');
				} else {
					Ext.MessageBox.alert('Suksess', 'Opplysningene er oppdatert', action.result.msg);
					window.location = '<?=$this->returi->get();?>';
				}
			}
		},
							
		// Nedenstående actionfailed er testa, og tilbakemelding fungerer ved feil både for lasting og lagring. Kay-Egil
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
	
	case "leieforhold": {
		if( isset( $_POST['query']) ) {
			$filter =	"WHERE CONCAT(fornavn, ' ', etternavn) LIKE '%{$this->POST['query']}%'\n"
				.	"OR kontrakter.kontraktnr LIKE '%{$this->POST['query']}%'\n";
		}
		else {
			$filter = "";
		}
		$sql =	"SELECT\n"
			.	"kontrakter.leieforhold, max(kontrakter.kontraktnr) as kontraktnr, leieobjekt , gateadresse, andel, min(fradato) AS startdato ,max(tildato) AS tildato\n"
			.	"FROM\n"
			.	"((kontrakter INNER JOIN leieobjekter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr)\n"
			.	"INNER JOIN kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt)\n"
			.	"INNER JOIN personer ON kontraktpersoner.person = personer.personid\n"
			.	$filter
			.	"GROUP BY kontrakter.leieforhold, leieobjekt, gateadresse, andel\n"
			.	"ORDER BY startdato DESC, tildato DESC, etternavn, fornavn\n";
		$liste = $this->arrayData($sql);
		foreach($liste['data'] as $linje => $d) {
			$liste['data'][$linje]['visningsfelt'] = $d['leieforhold'] . ' | ' . ($this->liste($this->kontraktpersoner($d['kontraktnr']))) . ' for #' . $d['leieobjekt'] . ', ' . $d['gateadresse'];
		}
		return json_encode($liste);
		break;
	}
		
	default: {
		$resultat = $this->arrayData($this->hoveddata);

		$autoriserer = new $this->autoriserer;
		
		$resultat['data'][0]['login'] = $autoriserer->trovuUzantoNomo($resultat['data'][0]['personid']);
		
		// Finn fram liste over hvilke leieavtaler en er med i, men ikke har allerede har adgang til
		$sql =	"SELECT leieforhold.leieforhold\n"
			.	"FROM (\n"
			.	"	SELECT kontrakter.leieforhold, kontraktpersoner.person AS personid\n"
			.	"	FROM (`kontrakter` INNER JOIN kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt)\n"
			.	"	WHERE kontraktpersoner.person = '{$_GET['personid']}'\n"
			.	"	GROUP BY kontrakter.leieforhold, kontraktpersoner.person\n"
			.	") AS leieforhold\n"
			.	"LEFT JOIN (\n"
			.	"	SELECT *\n"
			.	"	FROM adganger\n"
			.	"	WHERE personid = '{$_GET['personid']}'\n"
			.	"	AND adgang = 'beboersider'\n"
			.	") AS alleredeadgang ON leieforhold.personid = alleredeadgang.personid\n"
			.	"WHERE alleredeadgang.leieforhold IS NULL\n"
			.	"ORDER BY leieforhold DESC";
		$avtaleforslag = $this->arrayData($sql);
	
		// dersom metoden over ikke fant forslag til leieavtaler, vil vi se om om personen er med i leieavtaler hun kan få adgang til.
		if(!count($avtaleforslag['data'])){
			$sql =	"SELECT leieforhold.leieforhold\n"
				.	"FROM (\n"
				.	"		SELECT kontrakter.leieforhold, framleiepersoner.personid AS personid\n"
				.	"	FROM kontrakter INNER JOIN (framleie INNER JOIN framleiepersoner ON framleie.nr = framleiepersoner.framleieforhold) ON kontrakter.leieforhold = framleie.leieforhold\n"
				.	"	WHERE framleiepersoner.personid = '{$_GET['personid']}'\n"
				.	"	GROUP BY kontrakter.leieforhold, framleiepersoner.personid\n"
				.	") AS leieforhold\n"
				.	"LEFT JOIN (\n"
				.	"	SELECT *\n"
				.	"	FROM adganger\n"
				.	"	WHERE personid = '{$_GET['personid']}'\n"
				.	"	AND adgang = 'beboersider'\n"
				.	") AS alleredeadgang ON leieforhold.personid = alleredeadgang.personid\n"
				.	"WHERE alleredeadgang.leieforhold IS NULL\n"
				.	"ORDER BY leieforhold DESC";
			$avtaleforslag = $this->arrayData($sql);
		}
		
		if($_GET['id'] == '*'){
			$resultat['data'][0]['adgang'] = "oppfolging";
			$resultat['data'][0]['leieforhold'] = @$avtaleforslag['data'][0]['leieforhold'];
			$resultat['data'][0]['epostvarsling'] = 1;
			$resultat['data'][0]['innbetalingsbekreftelse'] = 1;
			$resultat['data'][0]['forfallsvarsel'] = 1;
		}
		return json_encode($resultat);
	}
	}
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
	
	if($_POST['login'] and !$autoriserer->cuLaUzantonomoEstasDisponebla($_POST['login'], $_GET['personid'])) {
		echo json_encode(array(
			'success'	=> false,
			'msg'		=> "Brukernavnet er allerede i bruk. Prøv på nytt."
		));
		return;
	}
	
	if($_POST['login'] and $_POST['epost']) {
		
		if($resultat['success'] = $autoriserer->aldonuUzanto(array(
			'id'			=> (int)$_GET['personid'],
			'uzanto'		=> $_POST['login'],
			'nomo'			=> $this->navn($_GET['personid']),
			'pasvorto'		=> $_POST['pw1'],
			'retpostadreso'	=> $_POST['epost']
		))) {
			$resultat['msg'] = "";
			$this->mysqli->query("
				UPDATE personer
				SET epost = '{$this->POST['epost']}'
				WHERE personid = '{$this->GET['personid']}'
			");
		}
		else{
			$resultat['msg'] = "KLarte ikke å lagre.";
		}
	}

	$sql =	"REPLACE adganger\n";
	$sql .=	"SET adgangsid = " . (($_GET['id'] != '*') ? "'{$this->GET['id']}'" : "DEFAULT") . ",\n";
	$sql .=	"adgang = '" . ($_POST['adgang'] ? $this->POST['adgang'] : "beboersider") . "',\n";
	$sql .=	"personid = '{$this->GET['personid']}',\n";
	$sql .=	"leieforhold = " . ($_POST['adgang'] == 'beboersider' ? "'{$this->POST['leieforhold']}'" : "NULL");
	$sql .=	",\nepostvarsling = " . (($_POST['epostvarsling']) ? "1" : "0");
	if($_POST['adgang'] == 'beboersider'){
		$sql .=	",\ninnbetalingsbekreftelse = " . (($_POST['innbetalingsbekreftelse']) ? "1" : "0");
		$sql .=	",\nforfallsvarsel = " . (($_POST['forfallsvarsel']) ? "1" : "0") . "\n";
	}
	else{
		$sql .=	",\ninnbetalingsbekreftelse = 0";
		$sql .=	",\nforfallsvarsel = 0";
	}
	
	if($resultat['success']){
		if($resultat['success'] = $this->mysqli->query($sql)){
			$resultat['sql'] = $sql;
			$resultat['msg'] = "Utført!!";
		}
		else
			$resultat['msg'] = "Klarte ikke å lagre. Feilmeldingen fra databasen lyder:<br />$sql" . $this->mysqli->error;
	}
	
	echo json_encode($resultat);
}

function oppgave($oppgave) {
	switch($oppgave) {

	case 'slett': {
		$sql =	"DELETE\n"
			.	"FROM adganger\n"
			.	"WHERE adgangsid = '{$_GET['id']}'\n"
			.	"AND personid = '{$_GET['personid']}'";
		if($resultat['success'] = $this->mysqli->query($sql)){
			$resultat['msg'] = "Adgangen er slettet";
		}
		else{
			$resultat['msg'] = "Klarte ikke slette. Databasen sa:<br />" . $this->mysqli->error;
		}
		break;
	}
	}
	
	echo json_encode($resultat);
}

}
?>