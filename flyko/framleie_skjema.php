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
	$this->hoveddata = "SELECT *\n"
		.	"FROM framleie\n"
		.	"WHERE nr = '$id'";
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
	
	var mindato;
	var maxdato;

	strykperson = function(personid, kontraktnr){
		Ext.Ajax.request({
			params: {
				'personid': personid,
				'kontraktnr': kontraktnr
			},
			waitMsg: 'Vent...',
			url: "index.php?oppslag=framleie_skjema&oppdrag=oppgave&oppgave=slett&id=<?=$_GET['id']?>",
			success : function() {
				window.location="index.php?oppslag=framleie_skjema&id=<?=$_GET['id']?>";
			}
		});
	}
	
	
	function hentKontraktinfo(kort, verdi, gammelverdi){
		Ext.Ajax.request({
			params: {'kontraktnr': parseInt(verdi)},
			waitMsg: 'Et øyeblikk...',
			url: "index.php?oppslag=framleie_skjema&oppdrag=hentdata&data=kontraktdetaljer&id=<?=$_GET['id']?>",
			success : function(respons, valg) {
				var tilbakemelding = Ext.util.JSON.decode(respons.responseText);
				mindato = new Date.parseDate(tilbakemelding.data[0]['fradato'], 'Y-m-d');
				if(tilbakemelding.data[0]['tildato']){
					maxdato = new Date.parseDate(tilbakemelding.data[0]['tildato'], 'Y-m-d');
				}
				fradato.validate();
				tildato.validate();
			}
		});
	}

	var kontrakter = new Ext.data.Store({
		url: "index.php?oppslag=framleie_skjema&oppdrag=hentdata&data=kontrakter&id=<?=$_GET['id']?>",
		reader: new Ext.data.JsonReader({
			fields: ['kontraktnr', 'beskrivelse', 'fradato', 'tildato'],
			root: 'data'
		})
	});
	
	var kontraktnr = new Ext.form.ComboBox({		allowBlank: false,
		displayField: 'beskrivelse',
		editable: true,
		fieldLabel: 'Leieavtale som er framleid',
		forceSelection: false,
		listWidth: 700,
		maxHeight: 1000,
		minChars: 0,
		mode: 'remote',
		name: 'kontraktnr',
		selectOnFocus: true,
		store: new Ext.data.JsonStore({
			fields: [{name: 'kontraktnr'},{name: 'beskrivelse'}],
			root: 'data',
			url: "index.php?oppslag=framleie_skjema&oppdrag=hentdata&data=kontrakter&id=<?=$_GET['id']?>"
		}),
		triggerAction: 'all',
		typeAhead: true,
		width: 700
	});
	
	kontraktnr.on({change: hentKontraktinfo});

	var fradato = new Ext.form.DateField({
		allowBlank: false,
		altFormats: "j.n.y|j.n.Y|j/n/y|j/n/Y|j-n-y|j-n-Y|j. M y|j. M -y|j. M. Y|j. F -y|j. F y|j. F Y|j/n|j-n|dm|dmy|dmY|d|Y-m-d",
		fieldLabel: 'Framleie fra dato',
		format: 'd.m.Y',
		name: 'fradato',
		validator: function(verdi){
			dato = new Date.parseDate(verdi, 'd.m.Y');
			if(mindato && dato >= mindato && dato < maxdato) return true;
			else return 'Fradato må være være før tildato, og innenfor leieavtalens periode';
		},
		width: 200
	});

	var tildato = new Ext.form.DateField({
		allowBlank: false,
		altFormats: "j.n.y|j.n.Y|j/n/y|j/n/Y|j-n-y|j-n-Y|j. M y|j. M -y|j. M. Y|j. F -y|j. F y|j. F Y|j/n|j-n|dm|dmy|dmY|d|Y-m-d",
		fieldLabel: 'Framleie til dato',
		format: 'd.m.Y',
		name: 'tildato',
		validator: function(verdi){
			dato = new Date.parseDate(verdi, 'd.m.Y');
			if(maxdato && dato <= maxdato && dato> mindato) return true;
			else return 'Tildato må være etter fradato, og innenfor leieavtalens periode';
		},
		width: 200
	});

	var datasett = new Ext.data.JsonStore({
		url:'index.php?oppdrag=hentdata&oppslag=framleie_skjema&data=personliste&id=<?=$_GET['id']?>',
		fields: [
			{name: 'personid'},
			{name: 'navn'}
		],
		root: 'data'
    });
    datasett.load();


	var slett = {
		dataIndex: 'personid',
		header: 'Slett',
		renderer: function(v){
			return "<a style=\"cursor: pointer\" onClick=\"strykperson(" + v + ", <?=$_GET['id']?>)\"><img src=\"../bilder/slett.png\" /></a>";
		},
		sortable: true,
		width: 50
	};


	var navn = {
		dataIndex: 'navn',
		header: 'navn',
		sortable: true,
		width: 200
	};


	var rutenett = new Ext.grid.GridPanel({
		buttons: [{
			text: 'Legg til ny person',
			handler: function(){
				skjema.form.submit({
					url:'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET["id"]}";?>&oppdrag=taimotskjema&leggtil=1',
					waitMsg:'Prøver å lagre...'
				});
			}
		}],
		store: datasett,
		columns: [
			navn,
			slett	
		],
		stripeRows: true,
        height: 200,
        width: 300,
        title: 'Framleid til'
    });


	var skjema = new Ext.FormPanel({
		autoScroll: true,
		bodyStyle:'padding:5px 5px 0',
		buttons: [],
		frame:true,
		height: 500,
		items: [kontraktnr, fradato, tildato, rutenett],
		labelAlign: 'top', // evt right
		reader: new Ext.data.JsonReader({
			defaultType: 'textfield',
			fields: [kontraktnr, fradato, tildato],
			root: 'data'
		}),
		standardSubmit: false,
		title: 'Framleie',
		width: 900,
		height: 500
	});

	skjema.addButton('Avbryt', function(){
		window.location = "index.php?oppslag=framleie_liste";
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

<?
	if($_GET['id'] != '*'){
?>
	skjema.getForm().load({
		url:'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET["id"]}";?>&oppdrag=hentdata', waitMsg:'Henter opplysninger...'
	});

<?
	}
	else{
?>
	lagreknapp.enable();
<?
	}
?>
	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'load'){
				hentKontraktinfo(0, kontraktnr.value);
				lagreknapp.enable();
			}
			
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Mottok ikke bekreftelsesmelding fra tjeneren  i JSON-format som forventet');
				} else {
					window.location = action.result.henvisning;
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
							Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', action.result.msg);
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

function oppgave($oppgave){
	switch ($oppgave) {
		case 'slett':
			$sql = "DELETE FROM framleiepersoner\n"
				.	"WHERE kontraktnr = '{$_GET['id']}'\n"
				.	"AND personid = '{$_POST['personid']}'";
			$resultat['success'] = $this->mysqli->query($sql);
			echo json_encode($resultat);
			break;
	}
}


function taimotSkjema() {
	$sql =	"SELECT *\n"
		.	"FROM framleiepersoner\n"
		.	"WHERE kontraktnr = {$_GET['id']}";
	$framleiere = $this->arrayData($sql);
	if((!$resultat['success'] = count($framleiere['data'])) and !$_GET['leggtil']){
		$resultat['msg'] = "Du har ikke angitt framleiere";
	}
	else{
		$sql =	"REPLACE framleie\n";
		$sql .=	"SET nr = '{$_GET['id']}',\n";
		$sql .=	"kontraktnr = '" . $this->mysqli->real_escape_string($_POST['kontraktnr']) . "',\n";
		$sql .=	"fradato = '" . date('Y-m-d', strtotime($_POST['fradato'])) . "',\n";
		$sql .=	"tildato = '" . date('Y-m-d', strtotime($_POST['tildato'])) . "'\n";
		
		if($resultat['success'] = $this->mysqli->query($sql)){
			$resultat['msg'] = "";
			if($_GET['leggtil']) $resultat['henvisning'] = "index.php?oppslag=framleie_nyeframleiere&id=" . $this->mysqli->insert_id;
			else $resultat['henvisning'] = "index.php?oppslag=framleie_skjema&id={$_GET['id']}";
		}
		else
			$resultat['msg'] = "KLarte ikke å lagre. Feilmeldingen fra databasen lyder:<br />" . $this->mysqli->error;
	}
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		case 'kontrakter':
			if($_POST['query']){
				$filter =	"WHERE LEFT(etternavn, " . strlen($_POST[query]) . ") = '" . $_POST[query] . "'\n"
				.	"OR LEFT(fornavn, " . strlen($_POST[query]) . ") = '" . $_POST[query] . "'\n"
				.	"OR LEFT(CONCAT(fornavn, ' ', etternavn), " . strlen($_POST[query]) . ") = '" . $_POST[query] . "'\n"
				.	"OR LEFT(kontrakter.kontraktnr, " . strlen((int)$_POST[query]) . ") = '" . (int)$_POST[query] . "'\n";
			}
			$sql =	"SELECT kontrakter.kontraktnr, leieobjekt, fradato, tildato\n"
				.	"FROM kontrakter\n"
				.	"INNER JOIN kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt\n"
				.	"INNER JOIN personer ON kontraktpersoner.person = personer.personid\n"
				.	$filter
				.	"GROUP BY kontrakter.kontraktnr";
			$liste = $this->arrayData($sql);
			foreach($liste['data'] as $linje=>$detaljer){
				$liste['data'][$linje]['beskrivelse'] = "{$detaljer['kontraktnr']} " . $this->liste($this->kontraktpersoner($detaljer['kontraktnr'])) . " i " . $this->leieobjekt($detaljer['leieobjekt']) . " | " . date('d.m.Y', strtotime($detaljer['fradato'])) . " - " . date('d.m.Y', strtotime($detaljer['tildato']));
//				unset($liste['data'][$linje]['leieobjekt']);
			}
			return json_encode($liste);
			break;
		case "kontraktdetaljer":
			$sql =	"SELECT fradato, tildato, leieobjekt\n"
				.	"FROM kontrakter\n"
				.	"WHERE kontraktnr = '{$_POST['kontraktnr']}'";
			return json_encode($this->arrayData($sql));
			break;
		case 'personliste':
			$sql =	"SELECT personid, NULL AS navn FROM framleiepersoner WHERE kontraktnr ='{$_GET['id']}'";
			$resultat = $this->arrayData($sql);
			foreach($resultat['data'] as $linje=>$verdi){
				$resultat['data'][$linje]['navn'] = $this->navn($verdi['personid']);
			}
			return json_encode($resultat);
			break;
		default:
			return json_encode($this->arrayData($this->hoveddata));
	}
}

}
?>