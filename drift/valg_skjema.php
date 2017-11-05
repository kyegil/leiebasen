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
	$this->hoveddata = "SELECT * FROM valg";
}

function skript() {
	if( isset( $_GET['returi'] ) && $_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	$datasett = $this->arrayData($this->hoveddata);
	$datasett = $datasett['data'];
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';
	
	fristvalidering = function (v) {
		if( v.search(/^P[0-9]+D$/) != -1) {
			return true;
		}
		else {
			return "Fristen må være oppgitt i formatet 'P' + antall dager + 'D'.";
		}
	}

	var utleier = new Ext.form.TextField({
		fieldLabel: 'Utleier',
		name: 'utleier',
		width: 180
	});

	var orgnr = new Ext.form.TextField({
		fieldLabel: 'Org. nr.',
		name: 'orgnr',
		width: 180
	});

	var adresse = new Ext.form.TextField({
		fieldLabel: 'Adresse',
		name: 'adresse',
		width: 180
	});

	var postnr = new Ext.form.TextField({
		fieldLabel: 'Postnr/sted',
		name: 'postnr',
		width: 40
	});

	var poststed = new Ext.form.TextField({
		fieldLabel: '',
		hideLabel: true,
		name: 'poststed',
		width: 132
	});

	var telefon = new Ext.form.TextField({
		fieldLabel: 'Telefon',
		name: 'telefon',
		width: 180
	});

	var mobil = new Ext.form.TextField({
		fieldLabel: 'Mobiltlf',
		name: 'mobil',
		width: 180
	});

	var telefax = new Ext.form.TextField({
		fieldLabel: 'Telefaks',
		name: 'telefax',
		width: 180
	});

	var epost = new Ext.form.TextField({
		fieldLabel: 'E-postadresse',
		name: 'epost',
		width: 180
	});

	var autoavsender = new Ext.form.TextField({
		fieldLabel: 'Avsenderadresse for epost',
		name: 'autoavsender',
		width: 140
	});

	var hjemmeside = new Ext.form.TextField({
		fieldLabel: 'Hjemmeside',
		name: 'hjemmeside',
		width: 180
	});

	var bankkonto = new Ext.form.TextField({
		fieldLabel: 'Kontonr for innbetalinger',
		name: 'bankkonto',
		width: 120
	});

	var nets_kundeenhetID = new Ext.form.TextField({
		fieldLabel: 'Kundeenhet-ID NETS',
		name: 'nets_kundeenhetID',
		width: 120
	});

	var nets_avtaleID_fbo = new Ext.form.TextField({
		fieldLabel: 'Avtale-ID',
		name: 'nets_avtaleID_fbo',
		width: 140
	});

	var nets_avtaleID_ocr = new Ext.form.TextField({
		fieldLabel: 'Avtale-ID',
		name: 'nets_avtaleID_ocr',
		width: 140
	});

	var ocr = new Ext.form.Checkbox({
		boxLabel: 'OCR-konto (Bruk KID)',
		fieldLabel: 'OCR',
		name: 'ocr',
		width: 200
	});

	var autoOcr = new Ext.form.Checkbox({
		boxLabel: 'automatisk henting',
		fieldLabel: '',
		name: 'auto_OCR',
		width: 200
	});

	var ocrFelt = new Ext.form.FieldSet({
		title: 'OCR konteringsdata',
		items: [
			ocr,
			nets_avtaleID_ocr,
			autoOcr
		]	
	});
	
	var avtalegiro = new Ext.form.Checkbox({
		boxLabel: 'På',
		fieldLabel: '',
		name: 'avtalegiro',
		width: 200
	});

	var avtalegiroFelt = new Ext.form.FieldSet({
		title: 'AvtaleGiro',
		items: [
			avtalegiro,
			nets_avtaleID_fbo,
		]	
	});
	
	var efaktura = new Ext.form.Checkbox({
		boxLabel: 'På',
		fieldLabel: '',
		name: 'efaktura',
		width: 200
	});

	var efaktura_referansenummer = new Ext.form.TextField({
		fieldLabel: 'Ref. nr.',
		name: 'efaktura_referansenummer',
		width: 120
	});

	var efakturaFelt = new Ext.form.FieldSet({
		title: 'eFaktura',
		items: [
			efaktura,
			efaktura_referansenummer
		]	
	});
	

	var backup_siste_nedlastet = new Ext.form.DateField({
		disabled: true,
		fieldLabel: 'Siste backup nedlastet',
		name: 'backup_siste_nedlastet',
		format: 'd.m.Y H:i:s',
		hideTrigger: true,
		width: 140
	});

	var backup_siste_tilgjengelige = new Ext.form.DateField({
		disabled: true,
		fieldLabel: 'Siste tilgjengelige backup',
		name: 'backup_siste_tilgjengelige',
		format: 'd.m.Y H:i:s',
		hideTrigger: true,
		width: 140
	});

	var backupknapp = new Ext.Button({
		text: 'Last ned',
		handler: function() {
			window.open('index.php?oppslag=eksport&oppdrag=hentdata&data=backup');
		}
	});

	var backup = new Ext.form.FieldSet({
		title: 'Backup av database',
		items: [
			backup_siste_nedlastet,
			backup_siste_tilgjengelige,
			backupknapp
		]	
	});
	

	var sperredato_for_etterregistrering_av_krav = new Ext.form.ComboBox({
		allowBlank: true,
		displayField: 'verdi',
		editable: true,
		fieldLabel: 'Sperredato for etterregistrering av krav for foregående måned',
		forceSelection: true,
		mode: 'local',
		name: 'sperredato_for_etterregistrering_av_krav',
		selectOnFocus: true,
		store: new Ext.data.SimpleStore({
			fields: ['verdi'],
			data : [['1'], ['2'], ['3'], ['4'], ['5'], ['6'], ['7'], ['8'], ['9'], ['10'], ['11'], ['12'], ['13'], ['14'], ['15'], ['16'], ['17'], ['18'], ['19'], ['20'], ['21'], ['22'], ['23'], ['24'], ['25'], ['26'], ['27'], ['31']]
		}),
		triggerAction: 'all',
		typeAhead: false,
		valueField: 'verdi',
		width: 50
	});

	var forfallsfrist = new Ext.form.TextField({
		fieldLabel: 'Minimumsfrist for forfall på nye krav',
		name: 'forfallsfrist',
		validator: fristvalidering,
		width: 50
	});

	var purreforfallsfrist = new Ext.form.TextField({
		fieldLabel: 'Frist for forfall på purringer',
		name: 'purreforfallsfrist',
		validator: fristvalidering,
		width: 50
	});

	var purreintervall = new Ext.form.TextField({
		fieldLabel: 'Minimumsintervall mellom hver purring',
		name: 'purreintervall',
		validator: fristvalidering,
		width: 50
	});

	var utdelingsrute = new Ext.form.ComboBox({
		fieldLabel: 'Utdelingsrute (Utskriftsrekkefølge)',
		name: 'utdelingsrute',
		width: 100
	});

	var forfallsdato = new Ext.form.ComboBox({
		allowBlank: true,
		displayField: 'verdi',
		editable: true,
		fieldLabel: 'Fast dato for forfall på nye krav',
		forceSelection: true,
		mode: 'local',
		name: 'forfallsdato',
		selectOnFocus: true,
		store: new Ext.data.SimpleStore({
			fields: ['verdi'],
			data : [['1'], ['2'], ['3'], ['4'], ['5'], ['6'], ['7'], ['8'], ['9'], ['10'], ['11'], ['12'], ['13'], ['14'], ['15'], ['16'], ['17'], ['18'], ['19'], ['20'], ['21'], ['22'], ['23'], ['24'], ['25'], ['26'], ['27'], ['31']]
		}),
		triggerAction: 'all',
		typeAhead: false,
		valueField: 'verdi',
		width: 50
	});

	var purregebyr = new Ext.form.NumberField({
		fieldLabel: 'Purregebyr',
		name: 'purregebyr',
		allowDecimals: false,
		width: 50
	});

	var girotekst = new Ext.form.TextArea({
		fieldLabel: 'Tekst på giroer',
		height: 100,
		name: 'girotekst',
		width: 540
	});

	var efaktura_tekst1 = new Ext.form.TextArea({
		fieldLabel: 'eFaktura tekst før fakturadetaljer',
		height: 100,
		enforceMaxLength: true,
		maxLength: 480,
		name: 'efaktura_tekst1',
		width: 540
	});

	var efaktura_tekst2 = new Ext.form.TextArea({
		fieldLabel: 'eFaktura tekst etter fakturadetaljer',
		height: 100,
		enforceMaxLength: true,
		maxLength: 480,
		name: 'efaktura_tekst2',
		width: 540
	});

	var eposttekst = new Ext.form.HtmlEditor({
		fieldLabel: 'Tekst i epostvarsler',
		height: 100,
		name: 'eposttekst',
		width: 540
	});

	var utløpsvarseltekst = new Ext.form.HtmlEditor({
		fieldLabel: 'Tekst på automatiske varsler om at leieavtalen må fornyes',
		height: 100,
		name: 'utløpsvarseltekst',
		width: 540
	});

	var strømfordelingstekst = new Ext.form.TextArea({
		fieldLabel: 'Tekst på meldinger om fordeling av fellesstrøm',
		height: 100,
		name: 'strømfordelingstekst',
		width: 540
	});

	var varselstempel_innbetalinger = new Ext.form.DateField({
		disabled: true,
		fieldLabel: 'Siste innbetalingsvarsel',
		format: 'd.m.Y H:i:s',
		hideTrigger: true,
		name: 'varselstempel_innbetalinger',
		width: 140
	});

	var varselstempel_kontraktutløp = new Ext.form.DateField({
		disabled: true,
		fieldLabel: 'Utløp varslet til',
		format: 'd.m.Y H:i:s',
		hideTrigger: true,
		name: 'varselstempel_kontraktutløp',
		width: 140
	});

	var varselstempel_krav = new Ext.form.DateField({
		disabled: true,
		fieldLabel: 'Siste kravvarsel',
		format: 'd.m.Y H:i:s',
		hideTrigger: true,
		name: 'varselstempel_krav',
		width: 140
	});

	var varselstempel_forfall = new Ext.form.DateField({
		disabled: true,
		fieldLabel: 'Forfall varslet til',
		format: 'd.m.Y H:i:s',
		hideTrigger: true,
		name: 'varselstempel_forfall',
		width: 140
	});
	
	var forfallsvarsel_innen = new Ext.form.TextField({
		fieldLabel: 'Tid før forfall det skal sendes epostmelding',
		name: 'forfallsvarsel_innen',
		validator: fristvalidering,
		width: 50
	});
	
	var konvolutt_marg_topp = new Ext.form.NumberField({
		allowDecimals: false,
		allowBlank: false,
		fieldLabel: 'Avstand i mm til adressefelt fra konvoluttenes toppkant',
		name: 'konvolutt_marg_topp',
		width: 140
	});
	
	var konvolutt_marg_venstre = new Ext.form.NumberField({
		allowDecimals: false,
		allowBlank: false,
		minValue: 0,
		maxValue: 150,
		fieldLabel: 'Avstand i mm til adressefelt fra konvoluttenes venstrekant',
		name: 'konvolutt_marg_venstre',
		width: 140
	});
	
	var konvoluttmarger = new Ext.form.FieldSet({
		title: 'Adressefelt på konvolutter',
		items: [
			konvolutt_marg_topp,
			konvolutt_marg_venstre
		]	
	});
	
	var malnr = {
		dataIndex: 'malnr',
		header: '',
		sortable: true,
		width: 20
	};

	var malnavn = {
		dataIndex: 'malnavn',
		header: 'Leieavtalemal',
		sortable: true,
		width: 200
	};


	var maldatasett = new Ext.data.JsonStore({
		url:'index.php?oppdrag=hentdata&oppslag=valg_skjema&data=malliste',
		fields: [
			{name: 'malnr'},
			{name: 'malnavn'}
		],
		root: 'data'
	});
	
	maldatasett.load();

	var endreMal = {
		dataIndex: 'malnr',
		renderer: function(v){
			return "<a href=\"index.php?oppslag=avtalemal_skjema&id=" + v + "\"><img src=\"../bilder/rediger.png\" /></a>";
		},
		sortable: false,
		width: 30
	};

	var malliste = new Ext.grid.GridPanel({
		autoExpandColumn: 1,
		autoScroll: true,
		buttons: [
			{
				text: 'Ny mal',
				handler: function(){
					window.location = "index.php?oppslag=avtalemal_skjema&id=*";
				}
			}
		],
		store: maldatasett,
		columns: [
			malnr, malnavn, endreMal
		],
		stripeRows: true,
        height: 150,
        title:'',
        width: 360
    });



	var delkravdatasett = new Ext.data.JsonStore({
		url:'index.php?oppdrag=hentdata&oppslag=valg_skjema&data=delkravliste',
		fields: [
			{name: 'id'},
			{name: 'navn'},
			{name: 'aktiv', type: 'boolean'}
		],
		root: 'data'
	});
	
	delkravdatasett.load();


	var delkravtype = {
		dataIndex: 'id',
		header: '',
		sortable: true,
		width: 20
	};

	var delkravnavn = {
		dataIndex: 'navn',
		header: 'Delkrav',
		sortable: true,
		width: 200
	};

	var aktivtDelkravtype = {
		dataIndex: 'aktiv',
		header: 'Aktiv',
		renderer: function(v) {
			if (v) {
				return '<img alt="✔︎" src="../bilder/hake9.png" />';
			}
		},
		sortable: false,
		width: 50
	};

	var endreDelkrav = {
		dataIndex: 'id',
		renderer: function(v){
			return "<a href=\"index.php?oppslag=delkravskjema&id=" + v + "\"><img src=\"../bilder/rediger.png\" /></a>";
		},
		sortable: false,
		width: 30
	};

	var delkravliste = new Ext.grid.GridPanel({
		autoExpandColumn: 1,
		autoScroll: true,
		buttons: [
			{
				text: 'Lag ny type delkrav',
				handler: function(){
					window.location = "index.php?oppslag=delkravskjema&id=*";
				}
			}
		],
		store: delkravdatasett,
		columns: [
			delkravtype, delkravnavn, aktivtDelkravtype, endreDelkrav
		],
		stripeRows: true,
        height: 150,
        title:'',
        width: 360
    });

	var beregningsnr = {
		dataIndex: 'nr',
		header: '',
		sortable: true,
		width: 20
	};

	var beregningsnavn = {
		dataIndex: 'navn',
		header: 'Husleieberegning',
		sortable: true,
		width: 200
	};

	var leieberegningsdata = new Ext.data.JsonStore({
		url:'index.php?oppdrag=hentdata&oppslag=valg_skjema&data=leieberegninger',
		fields: [
			{name: 'nr'},
			{name: 'navn'},
			{name: 'beskrivelse'}
		],
		root: 'data'
	});
	
	leieberegningsdata.load();

	var endreBeregning = {
		dataIndex: 'nr',
		renderer: function(v){
			return "<a href=\"index.php?oppslag=leieberegning_skjema&id=" + v + "\"><img src=\"../bilder/rediger.png\" /></a>";
		},
		sortable: false,
		width: 30
	};

	var leieberegningsliste = new Ext.grid.GridPanel({
		autoExpandColumn: 1,
		autoScroll: true,
		buttons: [
			{
				text: 'Ny beregningsmetode',
				handler: function(){
					window.location = "index.php?oppslag=leieberegning_skjema&id=*";
				}
			}
		],
		store: leieberegningsdata,
		columns: [
			beregningsnr, beregningsnavn, endreBeregning
		],
		stripeRows: true,
        height: 150,
        title:'',
        width: 360
    });

	var skjema = new Ext.FormPanel({
		autoScroll: true,
		bodyStyle:'padding:5px 5px 0',
		buttons: [],
		frame:true,
		height: 500,
		items:[

		{	// Øverste to kolonner
			items:[
				{	// Øverste venstrekolonne
					items: [
						utleier,
						orgnr,
						adresse,

						{
							items: [{
								items: postnr,
								columnWidth: 0.5,
								layout: 'form'
							}, {
								items: poststed,
								columnWidth: 0.5,
								layout: 'form'
							}],
							layout: 'column'
						},

						telefon,
						mobil,
						telefax,
						epost,
						hjemmeside,
						bankkonto,
						backup,
						nets_kundeenhetID,
						ocrFelt,
						avtalegiroFelt,
						efakturaFelt,
						varselstempel_innbetalinger,
						varselstempel_kontraktutløp,
						varselstempel_krav,
						varselstempel_forfall,
						autoavsender
					],
					columnWidth: 0.35,
					layout: 'form',
					labelWidth: 100
				},
				
				{	// Øverste høyrekolonne
				items: [
					eposttekst,
					utløpsvarseltekst,
					strømfordelingstekst,
					girotekst,
					efaktura_tekst1,
					efaktura_tekst2
				],
				columnWidth: 0.65,
				labelAlign: 'top',
				layout: 'form'
			}
			],
			layout: 'column'
		},
		
		{	// Nederste to kolonner
			items:[
				{	// Nederste venstrekolonne
					items: [
						malliste,
						sperredato_for_etterregistrering_av_krav,
						forfallsfrist,
						forfallsdato,
						forfallsvarsel_innen,
						konvoluttmarger
					],
					columnWidth: 0.5,
					layout: 'form',
					labelWidth: 350
				},
				
				{	// Nederste høyrekolonne
					items: [
						delkravliste,
						leieberegningsliste,
						purregebyr,
						purreforfallsfrist,
						purreintervall,
					],
					columnWidth: 0.5,
					layout: 'form',
					labelWidth: 250
				}
			],
			layout: 'column'
		}

		],
		labelAlign: 'left',
		labelWidth: 100,
		reader: new Ext.data.JsonReader({
			defaultType: 'textfield',
			fields: [
				{name: 'varselstempel_forfall', type: 'date', dateFormat: 'U'},
				{name: 'varselstempel_innbetalinger', type: 'date', dateFormat: 'U'},
				{name: 'varselstempel_kontraktutløp', type: 'date', dateFormat: 'U'},
				{name: 'varselstempel_krav', type: 'date', dateFormat: 'U'},
				adresse,
				autoavsender,
				autoOcr,
				avtalegiro,
				bankkonto,
				efaktura_referansenummer,
				efaktura,
				epost,
				eposttekst,
				sperredato_for_etterregistrering_av_krav,
				forfallsdato,
				forfallsfrist,
				forfallsvarsel_innen,
				girotekst,
				efaktura_tekst1,
				efaktura_tekst2,
				hjemmeside,
				konvolutt_marg_topp,
				konvolutt_marg_venstre,
				mobil,
				nets_avtaleID_fbo,
				nets_avtaleID_ocr,
				nets_kundeenhetID,
				ocr,
				orgnr,
				postnr,
				poststed,
				purreforfallsfrist,
				purregebyr,
				purreintervall,
				strømfordelingstekst,
				telefax,
				telefon,
				utdelingsrute,
				utleier,
				utløpsvarseltekst,
				{
					name: 'backup_siste_nedlastet',
					type: 'date',
					dateFormat: 'U',
					useNull: true
				},
				{
					name: 'backup_siste_tilgjengelige',
					type: 'date',
					dateFormat: 'U',
					useNull: true
				}
			],
			root: 'data'
		}),
		standardSubmit: false,
		title: 'Innstillinger og vedlikehold',
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
				url:'index.php?oppslag=<?="{$_GET['oppslag']}";?>&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...'
				});
		}
	});

	skjema.render('panel');

	skjema.getForm().load({
		url:'index.php?oppslag=<?="{$_GET['oppslag']}";?>&oppdrag=hentdata', waitMsg:'Henter opplysninger...'
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
					Ext.MessageBox.alert('Suksess', 'Opplysningene er oppdatert');
					window.location = "index.php?oppslag=<?="{$_GET['oppslag']}";?>";
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
	$tp = $this->mysqli->table_prefix;

	switch ($data) {
		case "leieberegninger":
			$sql = "SELECT nr, navn, beskrivelse FROM leieberegning";
			$resultat = $this->arrayData($sql);
			return json_encode($resultat);
			break;
		case "delkravliste":
			$resultat = $this->mysqli->arrayData(array(	
				'source'	=> "delkravtyper"
			));
			return json_encode($resultat);
			break;
		case "malliste":
			$sql = "SELECT malnr, malnavn FROM avtalemaler";
			$resultat = $this->arrayData($sql);
			return json_encode($resultat);
			break;
		default:
			$this->hentValg();
			
			$resultat = (object)array(
				'success'	=> true,
				'data'	=> array(
					0	=> $this->valg
				)
			);

			if( file_exists(LEIEBASEN_BACKUP_DB) ) {
				$resultat->data[0]['backup_siste_tilgjengelige'] = filectime(LEIEBASEN_BACKUP_DB);
			}
			else {
				$resultat->data[0]['backup_siste_tilgjengelige'] = null;
			}
			
			return json_encode($resultat);
	}
}


function taimotSkjema() {
	$resultat = (object)array(
		'success'	=> true
	);
	
	foreach($this->POST as $felt => $verdi) {
		if( $resultat->success ) {
			$resultat = $this->mysqli->saveToDb(array(
				'update'	=> true,
				'table'		=> "valg",
				'where'		=> "innstilling = '{$felt}'",
				'fields'	=> array(
					'verdi'		=> $_POST[$felt]
				)				
			));
		}
	}

	if( $resultat->success ) {
		$resultat = $this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "valg",
			'where'		=> "innstilling = 'ocr'",
			'fields'	=> array(
				'verdi'		=> isset($_POST['ocr']) and $_POST['ocr'] ? "1" : "0"
			)				
		));
	}

	if( $resultat->success ) {
		$resultat = $this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "valg",
			'where'		=> "innstilling = 'auto_OCR'",
			'fields'	=> array(
				'verdi'		=> isset($_POST['auto_OCR']) and $_POST['auto_OCR'] ? "1" : "0"
			)				
		));
	}

	if( $resultat->success ) {
		$resultat = $this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "valg",
			'where'		=> "innstilling = 'avtalegiro'",
			'fields'	=> array(
				'verdi'		=> isset($_POST['avtalegiro']) and $_POST['avtalegiro'] ? "1" : "0"
			)				
		));
	}

	if( $resultat->success ) {
		$resultat = $this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "valg",
			'where'		=> "innstilling = 'efaktura'",
			'fields'	=> array(
				'verdi'		=> isset($_POST['efaktura']) and $_POST['efaktura'] ? "1" : "0"
			)				
		));
	}


	if(!$resultat->success) {
		$resultat['msg'] = "KLarte ikke å lagre. Feilmeldingen fra databasen lyder:<br />" . $this->mysqli->error;
	}
	
	echo json_encode($resultat);
}


}
?>