<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {
public $ext_bibliotek = 'ext-4.2.1.883';

function __construct() {
	parent::__construct();
	if(!$id = $_GET['id']) die("Ugyldig oppslag: Notatnummer ikke angitt");
	$this->hoveddata = array(
		'source' => "notater",
		'where' => "notatnr = '{$this->GET['id']}'",
	);
}

function skript() {
	if(@$_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	$tp = $this->mysqli->table_prefix;

	$leieforhold = $this->hent('Leieforhold', $_GET['leieforhold']);
		
	switch($_GET['type']) {
		case "notat":
			$type = "";
			break;
		case "spm":
			$type = "spørsmål";
			break;
		case "utlv":
			$type = "utløpsvarsel";
			break;
		case "418":
			$type = "§4.18-varsel";
			break;
		case "tvangsfravikelsesbegj":
			$type = "tvangsfravikelsesbegjæring";
			break;
		case "utleggsbegj":
			$type = "utleggsbegjæring";
			break;
		default:
			$type = $_GET['type'];
			break;
	}
	
	$overskrift = array();
	if ($type) {
		$overskrift[] = $type;
	}
	if ($leieforhold->hentId()) {
		$overskrift[] = "leieforhold {$leieforhold}: {$leieforhold->hent('beskrivelse')}";
	}
	$overskrift = "<h1>" . ucfirst(implode(', ',  $overskrift)) . "</h1>";
?>

Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?=$this->http_host . "/" . $this->ext_bibliotek . "/examples/ux"?>');

Ext.require([
 	'Ext.data.*',
 	'Ext.form.*'
]);

Ext.onReady(function() {

    Ext.tip.QuickTipManager.init();
	Ext.form.Field.prototype.msgTarget = 'side';
	Ext.Loader.setConfig({enabled:true});

<?
	include_once("_menyskript.php");
?>

	Ext.define('Leieforhold', {
		extend: 'Ext.data.Model',
		idProperty: 'leieforhold',
		fields: [
			{name: 'leieforhold', type: 'string'}, // combo value is type sensitive
			{name: 'visningsfelt', type: 'string'}
		]
	});
	
	Ext.define('Kategori', {
		extend: 'Ext.data.Model',
		idProperty: 'verdi',
		fields: [
			{name: 'verdi', type: 'string'},
			{name: 'visning', type: 'string'}
		]
	});
	
	var leieforholddata = Ext.create('Ext.data.Store', {
		model: 'Leieforhold',
		pageSize: 50,
		remoteSort: false,
		proxy: {
			type: 'ajax',
			simpleSortMode: true,
			url: 'index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=hentdata&data=leieforhold&id=<?=$_GET['id']?>',
			reader: {
				type: 'json',
				root: 'data',
				totalProperty: 'totalRows'
			}
		},
		autoLoad: false
	});
	

	var notatnr = Ext.create('Ext.form.field.Text', {
		fieldLabel: 'notatnr',
		name: 'notatnr',
		width: 200
	});

<?if(!$_GET['leieforhold'] and $_GET['id'] == '*'):?>
	var leieforhold = Ext.create('Ext.form.field.ComboBox', {
//		allowBlank: false,
//		autoSelect: true,
		displayField: 'visningsfelt',
//		editable: true,
//		emptyText: 'Velg leieforhold',
//		forceSelection: false,
//		formBind: true,
		hideLabel: true,
		hideTrigger: true,
		labelWidth: 0,
		maxHeight: 600,
		matchFieldWidth: false,
		minChars: 1,
		name: 'leieforhold',
//		queryDelay: 1000,
		queryMode: 'remote',
//		selectOnFocus: true,
		store: leieforholddata,
//		typeAhead: false,
		valueField: 'leieforhold',
		listConfig: {
			loadingText: 'Søker ...',
			emptyText: 'Ingen treff...',
			maxHeight: 600,
			width: 600
		},
//		pageSize: 10,
		width: 500
	});

<?else:?>
	var leieforhold =  Ext.create('Ext.form.field.Hidden', {
		name: 'leieforhold',
		value: '<?=$_GET['leieforhold']?>'
	});

<?endif?>

	var leieforholdvisning =  Ext.create('Ext.form.field.Display', {
		name: 'leieforholdvisning',
		value: '<?=$overskrift?>'
	});

	var dato = Ext.create('Ext.form.field.Date', {
//		labelAlign: 'right',
		allowBlank: false,
		fieldLabel: 'Dato',
		format: 'd.m.Y',
		altFormat: 'Y-m-d',
		submitFormat: 'Y-m-d',
		name: 'dato',
		value: '<?=date("Y-m-d")?>',
		width: 200
	});

	var notat = Ext.create('Ext.form.field.TextArea', {
		fieldLabel: 'Notat',
		name: 'notat',
		padding: 5,
		height: 80,
		width: 700
	});

<?if($type == "" or $type == "spørsmål" or $type == "brev" or $type == "avtale" or $type == "betalingsplan"):?>
	var henvendelse_fra = Ext.create('Ext.form.RadioGroup', {
		padding: 5,
		columns: 'auto',
		fieldLabel: 'Gjelder henvendelse',
		vertical: false,
		items: [{
			boxLabel: 'Fra utleier',
			name: 'henvendelse_fra',
			inputValue: 'fra utleier'
		}, {
			boxLabel: 'Fra leietaker',
			name: 'henvendelse_fra',
			inputValue: 'fra leietaker'
		}, {
			boxLabel: 'Fra framleier',
			name: 'henvendelse_fra',
			inputValue: 'fra famleier'
		}, {
			boxLabel: 'Fra andre',
			name: 'henvendelse_fra',
			inputValue: 'fra andre'
		}, {
			boxLabel: 'Ikke relevant',
			name: 'henvendelse_fra',
			inputValue: '',
			checked: true
		}]
	});

<?else:?>
	var henvendelse_fra =  Ext.create('Ext.form.field.Hidden', {
		name: 'henvendelse_fra'
	});

<?endif?>

<?if($_GET['type']):?>
	var kategori = Ext.create('Ext.form.field.Hidden', {
		name: 'kategori',
		value: '<?php echo $type;?>'
	});

<?else:?>
	var kategori = Ext.create('Ext.form.field.ComboBox', {
		allowBlank: true,
		displayField: 'visning',
		fieldLabel: 'Aktivitet',
		name: 'kategori',
		listWidth: 500,
		maxHeight: 600,
		queryMode: 'local',
		store: Ext.create('Ext.data.Store', {
			model: 'Kategori',
			data : [
				['', 'Notat'],
				['spørsmål', 'Spørsmål'],
				['brev', 'Brev'],
				['purring', 'Purring / Betalingspåminnelse'],
				['betalingsplan', 'Betalingsplan'],
				['avtale', 'Avtale'],
				['utløpsvarsel', 'Varsel om at leieavtalen utløper / må fornyes'],
				['§4.18-varsel', 'Varsel etter tvangsfullbyrdelseslovens §4.18 sendt'],
				['forliksvarsel', 'Sendt varsel (ihht tvisteloven  §5-2) om at saken kan bli klaget inn for forliksrådet'],
				['forliksklage', 'Forliksklage sendt til forliksrådet'],
				['inkassovarsel', 'Sendt varsel om at saken kan bli oversendt til inkasso'],
				['tvangsfravikelsesbegjæring', 'Begjæring om fravikelse sendt namsmannen (ihht tvangsfullbyrdelsesloven § 13-2)'],
				['utleggsbegjæring', 'Begjæring om utlegg sendt namsmannen']
			]
		}),
		valueField: 'verdi',
		width: 500
	});


<?endif?>
	var brevtekst = Ext.create('Ext.form.field.HtmlEditor', {
		fieldLabel: 'Skriv brevet i tekstbehandleren',
		name: 'brevtekst',
		height: 300,
		width: 850
	});

	var vedlegg = Ext.create('Ext.form.field.File', {
		fieldLabel: 'Last opp vedlegg',
		name: 'vedlegg',
		labelWidth: 120
	});
	
	var dokumentreferanse = Ext.create('Ext.form.field.Text', {
		fieldLabel: 'Evt. ref. til eksternt dokument',
		labelWidth: 150,
		name: 'dokumentreferanse'
	});

	var dokumenttype = Ext.create('Ext.form.field.Text', {
		fieldLabel: 'Det eksterne dokumentet er av type',
		name: 'dokumenttype',
		labelWidth: 150
	});

	var pause = Ext.create('Ext.form.field.Date', {
		labelWidth: 200,
		allowBlank: true,
		fieldLabel: 'Vent med videre oppfølging fram til',
		format: 'd.m.Y',
		name: 'pause',
		minValue: '<?=date("Y-m-d")?>',
		width: 90
	});

	var stopp = Ext.create('Ext.form.field.Checkbox', {
		labelWidth: 100,
		boxLabel: 'Stopp oppfølgingen av dette leieforholdet helt',
		name: 'stopp',
		inputValue: 1,
		listeners: {
			change: function(checkbox, newValue, oldValue, eOpts) {
				if(newValue) {
					pause.disable();
				}
				else {
					pause.enable();
				}
			}
		}
	});


	var lagreknapp = Ext.create('Ext.button.Button', {
		text: 'Lagre',
		disabled: true,
		handler: function() {
			skjema.form.submit({
				url:'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET['id']}";?>&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...'
				});
		}
	});

	var skjema = Ext.create('Ext.form.Panel', {
		renderTo: 'skjema',
		autoScroll: true,
		bodyPadding: 5,
		buttons: [
			{
				text: 'Tilbake',
				handler: function() {
					window.location = '<?=$this->returi->get();?>';
				}
			},
			lagreknapp
		],
		frame:true,
		height: 500,
		items: [
			leieforhold,
			leieforholdvisning,
			notat,
			{
				xtype: 'container',
				layout: 'column',
				items: [{
					xtype: 'container',
					columnWidth: 0.25,
					items: dato
				}, {
					xtype: 'container',
					columnWidth: 0.75,
					labelWidth: 100,
					items: kategori
				}]
			},
			henvendelse_fra,
			{
				xtype: 'container',
				layout: 'column',
				defaults: {
					padding: '0 5'
				},
				items: [{
					xtype: 'container',
					columnWidth: 0.35,
					items: vedlegg
				}, {
					xtype: 'container',
					columnWidth: 0.3,
					items: dokumentreferanse
				}, {
					xtype: 'container',
					columnWidth: 0.3,
					items: dokumenttype
				}]
			},
			{
				xtype: 'container',
				layout: 'column',
				defaults: {
					padding: '0 5'
				},
				items: [{
					xtype: 'container',
					layout: 'form',
					columnWidth: 0.5,
					items: pause
				}, {
					xtype: 'container',
					columnWidth: 0.5,
					labelWidth: 0,
					items: stopp
				}]
			},
			{
				xtype: 'fieldset',
				labelAlign: 'top',
				title: 'Her kan du skrive eller kopiere inn evt. brev som vedlegg.',
 				width: 'auto',
 				collapsible: true,
 				collapsed: <?=$_GET['type'] == 'brev' ? "false" : "true"?>,
				items: [brevtekst]
			}
		],
		labelAlign: 'right', // evt right
		labelWidth: 150,
		url: 'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET['id']}";?>&oppdrag=hentdata', waitMsg:'Henter opplysninger...',
		standardSubmit: false,
		title: 'Loggført notat eller henvendelse knyttet til leieavtale. Oppføringen vil også kunne leses på leietakerens beboersider.',
		width: 900
	});

<?
	if($_GET['id'] != '*'){
?>
	skjema.getForm().load({
		url: 'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET['id']}";?>&oppdrag=hentdata', waitMsg:'Henter opplysninger...'
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
				lagreknapp.enable();
				leieforholddata.load({
					params: {
						query: leieforhold.getValue()
					},
					callback: function() {
						leieforhold.setValue(leieforhold.getValue());
					}
				});
			} 
			
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Mottok ikke bekreftelsesmelding fra tjeneren  i JSON-format som forventet');
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
							Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', action.result.msg);
						}
						else {
							Ext.MessageBox.alert('Problem:', 'Innhenting av data mislyktes av ukjent grunn. (trolig manglende success-parameter i den returnerte datapakken). Action type=' + action.type + ', failure type=' + action.failureType);
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
						Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', action.result.msg);
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
<div id="skjema"></div>
<?
}


function utskrift() {
	$tekst = $this->mysqli->arrayData( $this->hoveddata );
	echo reset($tekst->data)->brevtekst;?>
<script type="text/javascript">
	window.print();
</script>
<?
}


function taimotSkjema() {
	$id = (int)$_GET['id'];
	$resultat = $this->mysqli->saveToDb(array(
		'update' => ($_GET['id'] != '*' ? true : false),
		'insert' => ($_GET['id'] == '*' ? true : false),
		'id' => $id,
		'table' => "notater",
		'where' => ($_GET['id'] != '*' ? "notatnr = '$id'" : ""),
		'fields' => array(
			'registrerer' => $this->bruker['navn'],
			'leieforhold' => ($_POST['leieforhold'] ? (int)$_POST['leieforhold'] : "NULL"),
			'dato' => $_POST['dato'],
			'notat' => $_POST['notat'],
			'henvendelse_fra' => $_POST['henvendelse_fra'],
			'kategori' => $_POST['kategori'],
			'brevtekst' => $_POST['brevtekst'],
			'dokumentreferanse' => $_POST['dokumentreferanse'],
			'dokumenttype' => $_POST['dokumenttype']
		)
	));
	
	if($resultat->success) {
		$notatnr = $resultat->id;
		$resultat = $this->mysqli->saveToDb(array(
			'update' => true,
			'table' => "kontrakter",
			'where' => "leieforhold = {$this->POST['leieforhold']}",
			'fields' => array(
				'avvent_oppfølging' => ($_POST['pause'] ? $this->tolkDato($_POST['pause']) : null),
				'stopp_oppfølging' => (int)@$_POST['stopp']
			)
		));
	}
	
	// Lagre evt vedlegg
	$vedlegg = $_FILES['vedlegg'];
	if($resultat->success and $vedlegg['name']) {
		$sti = "{$this->filarkiv}/dokumenter/";
		$filnavn = basename($vedlegg["name"]);
		$lagerref = $sti . md5(time() . $notatnr);
		$filendelse = pathinfo($filnavn, PATHINFO_EXTENSION);

		if($vedlegg['error']) {
			die(json_encode((object) array(
				'success' => false,
				'msg'	=> "Det skjedde en feil under opplastingen av vedlegget, eller den ble avbrutt."
			)));			
		}

		$mimeliste = array(
			'doc'	=>	'application/msword',
			'pdf'	=>	'application/pdf',
			'ods'	=>	'application/vnd.oasis.opendocument.spreadsheet',
			'odt'	=>	'application/vnd.oasis.opendocument.text',
			'docx'	=>	'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'tar'	=>	'application/x-tar',
			'zip'	=>	'application/zip',
			'jpg'	=>	'image/jpeg',
			'jpeg'	=>	'image/jpeg',
			'png'	=>	'image/png',
			'txt'	=>	'text/plain'
		);

		$mime = $mimeliste[$filendelse];

		if(function_exists('mime_content_type')) {
			$mime = mime_content_type($vedlegg['tmp_name']);
		}
		if(function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime = finfo_file($finfo, $vedlegg['tmp_name']);
			finfo_close($finfo);
		}

		switch ($mime) {
			case 'application/msword':
			case 'application/pdf':
			case 'application/vnd.oasis.opendocument.spreadsheet':
			case 'application/vnd.oasis.opendocument.text':
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
			case 'application/x-tar':
			case 'application/zip':
			case 'image/jpeg':
			case 'image/png':
			case 'text/plain':
				break;
			default:
				die(json_encode((object) array(
					'success' => false,
					'msg'	=> "Filer av typen {$mime} tillates desverre ikke. Vedlegget ble ikke lagret."
				)));			
		}

		if ($vedlegg["size"] > 1000000) {
			die(json_encode((object) array(
				'success' => false,
				'msg'	=> "Fila er for stor. Maks. filstørrelse er 1Mb."
			)));
		}

		if (!file_exists($sti)) {
			if(!mkdir($sti)) {
				die(json_encode((object) array(
					'success' => false,
					'msg'	=> "Klarte ikke finne eller opprette filplasseringen '{$sti}'."
				)));
			}
		}
		
		if (!move_uploaded_file($vedlegg["tmp_name"], $lagerref)) {
			die(json_encode((object) array(
				'success' => false,
				'msg'	=> "Fila kunne ikke lagres pga ukjent feil."
			)));
		}

		$eksisterende = $this->mysqli->arrayData(array(
			'source' => "notater",
			'fields' => "vedlegg",
			'where' => "notater.notatnr = '$id'"
		))->data[0]->vedlegg;
		
		if(file_exists($eksisterende)) {
			unlink($eksisterende);
		}
			
		if($resultat->success) {
			$resultat = $this->mysqli->saveToDb(array(
				'returnQuery'	=> true,
				'table'	=> "notater",
				'update' => true,
				'where'	=> "notater.notatnr = '$notatnr'",
				'fields' => array(
					'vedlegg' => $lagerref,
					'vedleggsnavn' => $filnavn
				)
			));
		}
	}


	
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		case "leieforhold":
			if($_GET['query']){
				$filter =	"WHERE concat(fornavn, ' ', etternavn) LIKE '%{$this->GET['query']}%'\n"
				.	"OR kontrakter.kontraktnr LIKE '%{$this->GET['query']}%'\n";
			}
			$sql =	"SELECT\n"
				.	"kontrakter.leieforhold, max(kontrakter.kontraktnr) as kontraktnr, leieobjekt , gateadresse, andel, min(fradato) AS startdato ,max(tildato) AS tildato\n"
				.	"FROM\n"
				.	"((kontrakter INNER JOIN leieobjekter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr)\n"
				.	"INNER JOIN kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt)\n"
				.	"INNER JOIN personer ON kontraktpersoner.person = personer.personid\n"
				.	"LEFT JOIN innbetalinger ON kontrakter.leieforhold = innbetalinger.leieforhold\n"
				.	$filter
				.	"GROUP BY kontrakter.leieforhold, leieobjekt, gateadresse, andel\n"
				.	"ORDER BY startdato DESC, tildato DESC, etternavn, fornavn\n";
			$liste = $this->arrayData($sql);
			foreach($liste['data'] as $linje => $d) {
				$liste['data'][$linje]['visningsfelt'] = $d['leieforhold'] . ' | ' . ($this->liste($this->kontraktpersoner($d['kontraktnr']))) . ' i #' . $d['leieobjekt'] . ', ' . $d['gateadresse'] . ' | ' . date('d.m.Y', strtotime($d['startdato'])) . ($d['tildato'] ? " - " . date('d.m.Y', strtotime($d['tildato'])) : "");
			}
			return json_encode($liste);
			break;
		default:
			$resultat = $this->mysqli->arrayData($this->hoveddata);
			$resultat->data = $resultat->data[0];
			$leieforhold = $resultat->data->leieforhold;
			$resultat->data->leieforholdvisning = $leieforhold . " | " . $this->liste($this->kontraktpersoner($this->sistekontrakt($leieforhold))) . " i " . $this->leieobjekt($this->kontraktobjekt($leieforhold));
			return json_encode($resultat);
	}
}

}
?>