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
	$this->hoveddata = "SELECT leieobjekter.*, bygninger.bilde AS bygningsbilde FROM leieobjekter LEFT JOIN bygninger ON leieobjekter.bygning = bygninger.id WHERE leieobjektnr = '" . (int)$_GET['id'] . "'";
	if ($_GET['id'] =='*') $this->hoveddata = "SELECT '' AS leieobjektnr";
}

function skript() {
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	var janei = new Ext.data.SimpleStore({
		fields: ['verdi', 'svar'],
		data : [
			['1', 'Ja'],
			['0', 'Nei']
		]
	});
	
	var bolig = new Ext.data.SimpleStore({
		fields: ['verdi', 'svar'],
		data : [
			['1', 'Bolig'],
			['0', 'Nærings- eller annet lokale']
		]
	});
	
	var toalettkatliste = new Ext.data.SimpleStore({
		fields: ['verdi', 'svar'],
		data : [
			['2', 'Eget toalett'],
			['1', 'Felles toaletter i bygningen'],
			['0', 'Ingenting / utendørs']
		]
	});
	
	var etasjeliste = new Ext.data.SimpleStore({
		fields: ['etg', 'beskr'],
		data : [
			['', 'Ingen bestemt etasje'],
			['+', 'loft'],
			['5', '5. etasje'],
			['4', '4. etasje'],
			['3', '3. etasje'],
			['2', '2. etasje'],
			['1', '1. etasje'],
			['0', 'sokkel'],
			['-1', 'kjeller']
		]
	});
	
	var bygningsdata = new Ext.data.JsonStore({
		url:'index.php?oppslag=<?=$_GET['oppslag']?>&id=<?=$_GET['id']?>&oppdrag=hentdata&data=bygninger',
		fields: [
			{name: 'id'},
			{name: 'kode'},
			{name: 'navn'},
			{name: 'bilde'}
		],
		root: 'data',
		storeId: 'bygningsdata'
	});
	bygningsdata.load();

	var leieberegningsdata = new Ext.data.JsonStore({
		url:'index.php?oppslag=<?=$_GET['oppslag']?>&id=<?=$_GET['id']?>&oppdrag=hentdata&data=leieberegninger',
		fields: [
			{name: 'nr'},
			{name: 'navn'},
			{name: 'beskrivelse'}
		],
		root: 'data'
	});
	leieberegningsdata.load();

	var navn = new Ext.form.TextField({
		fieldLabel: 'Navn på bolig',
		name: 'navn',
		width: 300
	});

	var gateadresse = new Ext.form.TextField({
		fieldLabel: 'Gateadresse',
		name: 'gateadresse',
		width: 300
	});
	
	var bygning = new Ext.form.ComboBox({
		allowBlank: true,
		displayField: 'navn',
		editable: true,
		fieldLabel: 'Bygning',
		forceSelection: true,
		hiddenName: 'bygning',
		mode: 'remote',
		name: 'bygning',
		selectOnFocus: true,
		store: bygningsdata,
		triggerAction: 'all',
		typeAhead: true,
		valueField: 'id',
		width: 300
	});

	var etg = new Ext.form.ComboBox({
		allowBlank: true,
		displayField: 'beskr',
		editable: true,
		fieldLabel: 'Etasje',
		forceSelection: true,
		hiddenName: 'etg',
		mode: 'local',
		name: 'etg',
		selectOnFocus: true,
		store: etasjeliste,
		triggerAction: 'all',
		typeAhead: true,
		valueField: 'etg',
		width: 300
	});
	
	var beskrivelse = new Ext.form.TextField({
		fieldLabel: 'Evt. annen beskrivelse',
		name: 'beskrivelse',
		width: 300
	});

	var postnr = new Ext.form.TextField({
		fieldLabel: 'Postnummer',
		name: 'postnr',
		width: 300
	});

	var poststed = new Ext.form.TextField({
		fieldLabel: 'Poststed',
		name: 'poststed',
		width: 300
	});

	var bilde = new Ext.form.TextField({
		fieldLabel: 'Bilde av leieobjekt (URL&nbsp;/&nbsp;nettadresse)',
		name: 'bilde',
		width: 300
	});

	var boenhet = new Ext.form.ComboBox({
		allowBlank: false,
		displayField: 'svar',
		editable: true,
		fieldLabel: 'Leies ut som',
		forceSelection: true,
		hiddenName: 'boenhet',
		mode: 'local',
		name: 'boenhet',
		selectOnFocus: true,
		store: bolig,
		triggerAction: 'all',
		typeAhead: true,
		valueField: 'verdi',
		width: 300
	});

	var areal = new Ext.form.NumberField({
		allowBlank: true,
		allowDecimals: false,
		allowNegative: false,
		blankText: 'Du må angi areal',
		decimalSeparator: ',',
		fieldLabel: 'Areal',
		name: 'areal',
		nanText: 'Dette er ikke et tall',
		width: 300
	});

	var bad = new Ext.form.ComboBox({
		allowBlank: false,
		displayField: 'svar',
		editable: true,
		fieldLabel: 'Tilgang på bad eller dusj',
		forceSelection: true,
		hiddenName: 'bad',
		mode: 'local',
		name: 'bad',
		selectOnFocus: true,
		store: janei,
		triggerAction: 'all',
		typeAhead: true,
		valueField: 'verdi',
		width: 300
	});

	var toalett_kategori = new Ext.form.ComboBox({
		allowBlank: false,
		displayField: 'svar',
		editable: true,
		fieldLabel: 'Toalettforhold',
		forceSelection: true,
		hiddenName: 'toalett_kategori',
		mode: 'local',
		name: 'toalett_kategori',
		selectOnFocus: true,
		store: toalettkatliste,
		triggerAction: 'all',
		typeAhead: true,
		valueField: 'verdi',
		width: 300
	});

	var toalett = new Ext.form.TextField({
		fieldLabel: 'Spesifisering av toalettforhold',
		name: 'toalett',
		width: 300
	});

	var leieberegning = new Ext.form.ComboBox({
		allowBlank: false,
		displayField: 'navn',
		editable: true,
		fieldLabel: 'Leieberegning',
		forceSelection: true,
		hiddenName: 'leieberegning',
		mode: 'remote',
		name: 'leieberegning',
		selectOnFocus: true,
		store: leieberegningsdata,
		triggerAction: 'all',
		typeAhead: true,
		valueField: 'nr',
		width: 300
	});

	var merknader = new Ext.form.TextArea({
		fieldLabel: 'Merknader',
		name: 'merknader',
		width: 370
	});

	var ikke_for_utleie = new Ext.form.ComboBox({
		allowBlank: false,
		displayField: 'svar',
		editable: true,
		fieldLabel: 'Deaktivert',
		forceSelection: true,
		hiddenName: 'ikke_for_utleie',
		mode: 'local',
		name: 'ikke_for_utleie',
		selectOnFocus: true,
		store: janei,
		triggerAction: 'all',
		typeAhead: true,
		valueField: 'verdi',
		width: 300
	});


	var skjema = new Ext.form.FormPanel({
		autoScroll: true,
		buttonAlign: 'right',
		buttons: [{
			handler: function() {
				window.location = '<?= $_GET['id'] != '*' ? "index.php?oppslag=leieobjekt_kort&id={$_GET['id']}" : "index.php?oppslag=leieobjekt_liste"?>';
			},
			text: 'Avbryt'
		}, {
			handler: function() {
				window.location = "index.php?oppslag=bygningsliste";
			},
			text: 'Rediger bygningslisten'
		}],
		frame: true,
		labelAlign: 'right',
		labelWidth: 200,
		title: '<?=addslashes($this->leieobjekt($_GET['id'], true))?>',
		width: 900,
		height: 500,
		waitMsgTarget: true,
		waitMsg: 'Vent litt..',
		reader: new Ext.data.JsonReader({
			defaultType: 'textfield',
			fields: [
				navn,
				gateadresse,
				bygning,
				etg,
				beskrivelse,
				postnr,
				poststed,
				boenhet,
				bilde,
				areal,
				bad,
				toalett_kategori,
				toalett,
				leieberegning,
				merknader,
				ikke_for_utleie
			],
			root: 'data'
		}),
		items: [
			new Ext.form.FieldSet({
				title: 'Opplysninger om leieobjektet',
				autoHeight: true,
				defaultType: 'textfield',
				items: [
					navn,
					gateadresse,
					bygning,
					etg,
					beskrivelse,
					postnr,
					poststed,
					boenhet,
					bilde,
					areal,
					bad,
					toalett_kategori,
					toalett,
					leieberegning,
					merknader,
					ikke_for_utleie
				]
			})
		]
	});

	skjema.addButton('Last kortet på nytt', function(){
		skjema.getForm().load({url:'index.php?oppslag=leieobjekt_skjema&id=<?=$_GET["id"];?>&oppdrag=hentdata', waitMsg:'Henter data..'});
	});

	var submit = skjema.addButton({
		text: 'Lagre endringer',
		disabled: true,
		handler: function(){
			skjema.form.submit({
				url:'index.php?oppslag=leieobjekt_skjema&id=<?=$_GET["id"];?>&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...'
				});
		}
	});

	skjema.render('panel');

	skjema.getForm().load({
		url:'index.php?oppslag=leieobjekt_skjema&id=<?=$_GET["id"];?>&oppdrag=hentdata', waitMsg:'Henter opplysninger...'
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
					window.location = "index.php?oppslag=leieobjekt_kort&id=" + action.result.post;
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
		case "bygninger":
			$resultat = $this->mysqli->arrayData(array(
				'source' => "bygninger",
				'orderfields' => "navn"
			));
			return json_encode($resultat);
			break;
		case "leieberegninger":
			$sql = "SELECT nr, navn, beskrivelse FROM leieberegning";
			$resultat = $this->arrayData($sql);
			return json_encode($resultat);
			break;
		default:
			return json_encode($this->arrayData($this->hoveddata));
	}
}


function taimotSkjema() {
	switch($_GET['skjema']) {
		default:
			// taimotSkjema skal returnere parameterene 'success', 'errors' og evt. 'msg'
			// 'errors' vil undertrykke 'msg'
		
			$sql = "leieobjekter SET navn = '{$this->POST['navn']}', gateadresse = '{$this->POST['gateadresse']}', bygning = '{$this->POST['bygning']}', etg = '{$this->POST['etg']}', beskrivelse = '{$this->POST['beskrivelse']}', postnr = '{$this->POST['postnr']}', poststed = '{$this->POST['poststed']}', boenhet = '{$this->POST['boenhet']}', bilde = '{$this->POST['bilde']}', areal = '{$this->POST['areal']}', bad = '{$this->POST['bad']}', toalett = '{$this->POST['toalett']}', toalett_kategori = '{$this->POST['toalett_kategori']}', leieberegning = '{$this->POST['leieberegning']}', merknader = '{$this->POST['merknader']}', ikke_for_utleie = '{$this->POST['ikke_for_utleie']}'";
			if($_GET['id'] == '*') $sql = "INSERT INTO " . $sql;
			else $sql = "UPDATE {$sql} WHERE leieobjektnr = '{$this->GET['id']}'";
			if(!$this->mysqli->query($sql)) {
				$data['msg'] = "Klarte ikke å utføre databasespørringen:<br />$sql<br /><br />Feilmeldingen fra databasen lyder:<br />".$this->mysqli->error;
			}
			if(isset($data)) {
				$data['success'] = false;
			}
			else {
				$data['success'] = true;
				if($_GET['id'] == '*'){
					$data['post'] = $this->mysqli->insert_id;
				}
				else {
					$data['post'] = $_GET['id'];
				}
			}
			echo json_encode($data);
			break;
	}
}


}
?>