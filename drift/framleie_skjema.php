<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'leiebasen';
public $ext_bibliotek = 'ext-4.2.1.883';

function __construct() {
	parent::__construct();
}
function skript() {
	$id = (int)@$_GET['id'];
?>

Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?=$this->http_host . "/" . $this->ext_bibliotek . "/examples/ux"?>');

Ext.require([
 	'Ext.data.*',
 	'Ext.form.field.*',
    'Ext.layout.container.Border',
 	'Ext.grid.*',
    'Ext.ux.RowExpander'
]);

Ext.onReady(function() {

    Ext.tip.QuickTipManager.init();
	Ext.form.Field.prototype.msgTarget = 'side';
	Ext.Loader.setConfig({enabled:true});
	
<?
	include_once("_menyskript.php");
?>

	strykperson = function(personid, framleieforhold){
		Ext.Ajax.request({
			params: {
				'personid': personid,
				'framleieforhold': framleieforhold
			},
			waitMsg: 'Vent...',
			url: "index.php?oppslag=framleie_skjema&oppdrag=oppgave&oppgave=slett&id=<?=$id?>",
			success : function() {
				window.location="index.php?oppslag=framleie_skjema&id=<?=$id?>";
			}
		});
	}
	
	
	Ext.define('Leieforhold', {
		extend: 'Ext.data.Model',
		idProperty: 'leieforhold',
		fields: [
			{name: 'leieforhold', type: 'string'}, // combo value is type sensitive
			{name: 'visningsfelt', type: 'string'},
			{name: 'startdato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'tildato', type: 'date', dateFormat: 'Y-m-d', useNull: true}
		]
	});

	Ext.define('Person', {
		extend: 'Ext.data.Model',
		idProperty: 'person',
		fields: [
			{name: 'personid', type: 'string'},
			{name: 'navn', type: 'string'}
		]
	});
	

	var leieforholddata = Ext.create('Ext.data.Store', {
		model: 'Leieforhold',
		pageSize: 50,
		remoteSort: false,
		proxy: {
			type: 'ajax',
			simpleSortMode: true,
			url: 'index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=hentdata&data=leieforhold',
			reader: {
				type: 'json',
				root: 'data',
				totalProperty: 'totalRows'
			}
		},
		autoLoad: true
	});

	var datasett = Ext.create('Ext.data.Store', {
		model: 'Person',
		remoteSort: false,
		proxy: {
			type: 'ajax',
			simpleSortMode: true,
			url: 'index.php?oppdrag=hentdata&oppslag=framleie_skjema&data=personliste&id=<?=$id?>',
			reader: {
				type: 'json',
				root: 'data',
				totalProperty: 'totalRows'
			}
		},
		autoLoad: true
	});


	var leieforhold = Ext.create('Ext.form.field.ComboBox', {
		allowBlank: false,
		displayField: 'visningsfelt',
		editable: true,
		emptyText: 'Velg leieforhold som er framleid',
		fieldLabel: 'Leieforhold',
		forceSelection: false,
		hideLabel: false,
		listeners: {
			select: function( combo, records, eOpts ) {
				mindato = records[0].get('startdato');
				maxdato = records[0].get('tildato');
				fradato.setMinValue( mindato );
				fradato.setMaxValue( maxdato );
				tildato.setMinValue( mindato );
				tildato.setMaxValue( maxdato );
			}
		},
		listWidth: 700,
		maxHeight: 600,
		matchFieldWidth: false,
		minChars: 1,
		name: 'leieforhold',
		queryMode: 'remote',
		selectOnFocus: true,
		store: leieforholddata,
		typeAhead: false,
		valueField: 'leieforhold',
		listConfig: {
			loadingText: 'Søker ...',
			emptyText: 'Ingen treff...',
			maxHeight: 600,
			width: 600
		},
		width: 700
	});

	var fradato = Ext.create('Ext.form.field.Date', {
		allowBlank: false,
		fieldLabel: 'Framleie fra dato',
		format: 'd.m.Y',
		name: 'fradato',
		submitFormat: 'Y-m-d',
		width: 200
	});


	var tildato = Ext.create('Ext.form.field.Date', {
		allowBlank: false,
		fieldLabel: 'Framleie til dato',
		format: 'd.m.Y',
		name: 'tildato',
		submitFormat: 'Y-m-d',
		width: 200
	});


	var rutenett = Ext.create('Ext.grid.Panel', {
		autoScroll: true,
		layout: 'border',
//		frame: false,
		store: datasett,
		title: 'Framleid til',
		columns: [
			{
				dataIndex: 'navn',
				header: 'Navn',
				sortable: true,
				flex: 1,
				width: 200
			},
			{
				dataIndex: 'personid',
				header: 'Slett',
				renderer: function(v){
					return "<a style=\"cursor: pointer\" onClick=\"strykperson(" + v + ", <?=$id?>)\"><img src=\"../bilder/slett.png\" /></a>";
				},
				sortable: false,
				width: 50
			}
		],
		height: 200,
		width: 300,
		buttons: [{
			text: 'Legg til ny person',
			handler: function(){
				skjema.form.submit({
					url:'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET["id"]}";?>&oppdrag=taimotskjema&leggtil=1',
					waitMsg:'Prøver å lagre...'
				});
			}
		}]
	});



	var avbryt = Ext.create('Ext.button.Button', {
		text: 'Avbryt',
		id: 'avbryt',
		handler: function() {
			window.location = '<?=$this->returi->get();?>';				
		}
	});

	var lagreknapp = Ext.create('Ext.button.Button', {
		text: 'Lagre endringer',
		disabled: true,
		handler: function() {
			skjema.getForm().submit({
				url: 'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET["id"]}";?>&oppdrag=taimotskjema',
				waitMsg: 'Prøver å lagre...'
			});
		}
	});

	var skjema = Ext.create('Ext.form.Panel', {
		renderTo: 'panel',
		autoScroll: true,
		labelAlign: 'top',
		frame: true,
		title: 'Framleie',
		bodyPadding: 5,
		standardSubmit: false,
		width: 900,
		height: 500,
		items: [
			leieforhold,
			fradato,
			tildato,
			rutenett
		],
		buttons: [
			{
				text: 'Avbryt',
				id: 'avbryt',
				handler: function() {
					window.location = '<?=$this->returi->get();?>';				
				}
			},
			avbryt,
			lagreknapp
		]
	});
	

<?if($_GET['id'] != '*'):?>
	skjema.getForm().load({
		url: 'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET["id"]}";?>&oppdrag=hentdata',
		waitMsg:'Henter opplysninger...'
	});

<?endif;?>
	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'load') {
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
			
		}
	});

});
<?
}

function design() {
	if(!isset($_GET['id'])) die("Ugyldig oppslag: ID ikke angitt for kontrakt");
?>
<div id="panel"></div>
<?
}

function hentData($data = "") {

	switch ($data) {
	
	case "leieforhold":
		$query = array(
			'distinct'	=> true,
			'source'	=> "((kontrakter INNER JOIN leieobjekter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr)\n"
				.	"INNER JOIN kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt)\n"
				.	"INNER JOIN personer ON kontraktpersoner.person = personer.personid",
			'fields'	=> "kontrakter.leieforhold, max(kontrakter.kontraktnr) as kontraktnr, leieobjekt , gateadresse, andel, min(fradato) AS startdato ,max(tildato) AS tildato",
			'groupfields'	=> "kontrakter.leieforhold, leieobjekt, gateadresse, andel",
			'orderfields'	=> "startdato DESC, tildato DESC, etternavn, fornavn",
			'returnQuery'	=> true
		);
		if(isset( $_GET['query']) && $_GET['query'] ) {
			$query['where'] =	"kontrakter.leieforhold LIKE '%{$this->GET['query']}%' 
			OR kontrakter.kontraktnr LIKE '%{$this->GET['query']}%'
			OR leieobjekter.navn LIKE '%{$this->GET['query']}%'
			OR leieobjekter.gateadresse LIKE '%{$this->GET['query']}%'
			OR kontraktpersoner.leietaker LIKE '%{$this->GET['query']}%'
			OR CONCAT(personer.fornavn, ' ', personer.etternavn) LIKE '%{$this->GET['query']}%'\n";
		}
		$resultat = $this->mysqli->arrayData($query);

		foreach($resultat->data as $d) {
			$d->visningsfelt = $d->leieforhold
			 . ' | '
			 . $this->liste( $this->kontraktpersoner( $d->kontraktnr ) )
			 . ' i #' . $d->leieobjekt . ', '
			 . $d->gateadresse . ' | '
			 . $d->startdato . ' - ' . $d->tildato;
		}
		return json_encode($resultat);
		break;


	case 'personliste':
		$sql =	"SELECT personid, NULL AS navn FROM framleiepersoner WHERE framleieforhold ='{$_GET['id']}'";
		$resultat = $this->arrayData($sql);
		foreach($resultat['data'] as $linje=>$verdi){
			$resultat['data'][$linje]['navn'] = $this->navn($verdi['personid']);
		}
		return json_encode($resultat);
		break;

	default:
		$id = (int)$_GET['id'];
		$resultat = $this->arrayData("SELECT framleie.nr, framleie.fradato, framleie.tildato, framleie.leieforhold\n"
		.	"FROM framleie\n"
		.	"WHERE framleie.nr = '$id'");
		if(is_array($resultat['data'])) {
			$resultat['data'] = $resultat['data'][0];
		}
		return json_encode($resultat);
	}
}


function oppgave($oppgave){
	switch ($oppgave) {
		case 'slett':
			$sql = "DELETE FROM framleiepersoner\n"
				.	"WHERE framleieforhold = '{$_GET['id']}'\n"
				.	"AND personid = '{$_POST['personid']}'";
			$resultat['success'] = $this->mysqli->query($sql);
			echo json_encode($resultat);
			break;
	}
}


function taimotSkjema() {
	$sql =	"SELECT *\n"
		.	"FROM framleiepersoner\n"
		.	"WHERE framleieforhold = {$_GET['id']}";
	$framleiere = $this->arrayData($sql);
	if((!$resultat['success'] = count($framleiere['data'])) and !$_GET['leggtil']){
		$resultat['msg'] = "Du har ikke angitt framleiere";
	}
	else{
		$sql =	"REPLACE framleie\n";
		$sql .=	"SET nr = '{$_GET['id']}',\n";
		$sql .=	"leieforhold = '" . $this->mysqli->real_escape_string($_POST['leieforhold']) . "',\n";
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

}
?>