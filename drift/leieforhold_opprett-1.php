<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Registrer nytt leieforhold';
public $ext_bibliotek = 'ext-4.2.1.883';


function __construct() {
	parent::__construct();
}

function skript() {
	if(@$_GET['returi'] == "default") $this->returi->reset();
	$tp = $this->mysqli->table_prefix;

	$polett = false;
	if(isset($_POST['polett'])) {
		$polett = $_POST['polett'];
	}

	// Dersom leieobjekt er angitt i GET-parameter, er det ikke nødvendig med combofelt for dette
	$forhåndsvalgtLeieobjekt = false;
	if(isset($_GET['leieobjekt'])) {
		$forhåndsvalgtLeieobjekt = $this->hent('Leieobjekt', (int)$_GET['leieobjekt']);
	}

	$avtalemalliste = $this->mysqli->arrayData( array(
		'source' =>	"{$tp}avtalemaler AS avtalemaler",
		'fields' => "avtalemaler.malnr, avtalemaler.malnavn, avtalemaler.mal"
	))->data;
	array_push( $avtalemalliste, (object)array(
		'malnr'		=> 0,
		'malnavn'	=> "---  Ingen mal, skriv avtale fra scratch ---",
		'mal'		=> ""
	) );

	$delkravtyper = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}delkravtyper AS delkravtyper",
		'where'			=> "aktiv AND !selvstendig_tillegg",
		'orderfields'	=> "orden, id"
	))->data;

	$selvstendigeTillegg = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}delkravtyper AS delkravtyper",
		'where'			=> "aktiv AND selvstendig_tillegg",
		'orderfields'	=> "orden, id"
	))->data;
	$tillegg = array();
	
	foreach($selvstendigeTillegg as $indeks => $delkrav ) {
		settype($tillegg[(int)($indeks/3)], 'array');
		$tillegg[(int)($indeks/3)][] = "{xtype: 'container', flex: 1, padding: '0 5', items: [delkrav{$delkrav->id}]}";
	}
	foreach($tillegg as $indeks => $delkrav ) {
		$tillegg[$indeks] = "{xtype: 'container', layout: 'hbox', items: [	" . implode(',', $delkrav) . "]}";
	}
?>
Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?php echo $this->http_host . "/" . $this->ext_bibliotek . "/examples/ux"?>');

Ext.require([
	'Ext.data.*',
	'Ext.form.*'
]);

Ext.onReady(function() {

	<?include_once("_menyskript.php");?>

	function oppdaterFelt() {
		ledighetsgrad = opptattdata.getAt(0).get('ledighetsgrad');
		detaljer.set(opptattdata.getAt(0).get('html'));
		detaljer.overwrite('detaljpanel', opptattdata.getAt(0).data);
		andel.validate();
	};


	function fraBrøk(v) {
		v = v.toString();
		v = v.replace(',', '.');
		v = v.replace('%', '/100');
		if((!v || v.length > 0) && (v.substr((v.length - 1), 1) == '/')) {
			return false;
		}
		return eval(v);
	}


	function validerAndel(v) {
		v = fraBrøk( v );
		
		var tilgjengelighet = 0;
		var tilgjengelighetForm = "ukjent";

		var record = leieobjekt.findRecordByValue(parseInt(leieobjekt.getValue()));

		if( record ) {
			tilgjengelighet = record.get('tilgjengelighet');
			tilgjengelighetForm = record.get('tilgjengelighetFormatert');
		}
		
		if((v.length > 0) && (v.substr((v.length - 1), 1) == '/')) {
			return 'Ugyldig verdi';
		}

		if(eval(v) && eval(v) <= 1 &&  v <=(tilgjengelighet + 1/100000)) {
			return true;
		}

		else return 'Du kan ikke leie ut en større andel enn det som faktisk er ledig i leieobjektet (' + tilgjengelighetForm + ').<br />Pass på at leieobjektet er ledig fra den datoen du har oppgitt, og registrer evt. oppsigelser først.<br />Andelen i bofellesskap kan oppgis som brøk (eks. 2/5), eller prosent (40%). For leiligheter skal andelen være 100%.';
	}
	
	
	function beregnLeie() {
		var record = leieobjekt.findRecordByValue(leieobjekt.getValue());

		if( !forespørsel && record ) {
			var grunnleie = record.get('årsleie');
			
			var årsleie = grunnleie		// !! OBS! Ingen semikolon! Beregninga fortsetter !!
			<?php foreach($delkravtyper as $delkrav):?>
				<?php if($delkrav->relativ):?> + delkrav<?php echo $delkrav->id?>.getValue() / 100 * grunnleie
				<?php else:?> + delkrav<?php echo $delkrav->id?>.getValue()
				<?php endif;?>
			 <?php endforeach;?>;
			 
			leiebeløp.setValue( årsleie );
		}
	}
	
	
	function fordelLeiaPåTerminer() {
		if(leiebeløp.isValid() && antallTerminer.isValid()) {
			var beløp = leiebeløp.getValue() / antallTerminer.getValue();
			terminbeløp.setValue( Ext.util.Format.noMoney( Math.round(beløp) ) );
		}
	}


	Ext.define('Leieobjekt', {
		 extend: 'Ext.data.Model',
		 
		 // http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
		 fields: [
			 {name: 'leieobjektnr',				type: 'int'},
			 {name: 'beskrivelse',				type: 'string'},
			 {name: 'detaljer',					type: 'string'},
			 {name: 'årsleie',					type: 'int'},
			 {name: 'årsleieFormatert',			type: 'string'},
			 {name: 'tilgjengelighet',			type: 'float'},
			 {name: 'ledig',					type: 'date',		dateFormat: 'Y-m-d',	useNull: true},
			 {name: 'tilgjengelighetFormatert',	type: 'string'},
			 {name: 'avtaletekst',				type: 'string'}
		 ]
	 });
	 
	 
	var forespørsel = 0;


	var fristillesesdato = new Date;


	var avtalemalliste = Ext.create('Ext.data.Store', {
		storeId: 'avtalemalliste',		
		data: <?php echo json_encode($avtalemalliste);?>,
		fields: ['malnr', 'malnavn', 'mal']
	});

	
	var leieobjektliste = Ext.create('Ext.data.JsonStore', {
		storeId: 'leieobjektliste',
		
		autoLoad: <?php echo $forhåndsvalgtLeieobjekt ? 'true' : 'false' ?>,
		proxy: {
			type: 'ajax',
			url: "index.php?oppslag=leieforhold_opprett-1&oppdrag=hentdata&data=leieobjektliste",
			reader: {
				type: 'json',
				root: 'data',
				idProperty: 'leieobjektnr'
			}
		},
			
		model: 'Leieobjekt'
	});
	
	
	var datoer = Ext.create('Ext.data.Store', {
		storeId: 'datoer',
		autoLoad: true,
		
		fields: ['verdi', 'visning'],
		data : [
			{verdi: '',	visning: 'ingen fast dato'},
			{verdi: '1',	visning: '1. hver måned'},
			{verdi: '2',	visning: '2. hver måned'},
			{verdi: '3',	visning: '3. hver måned'},
			{verdi: '4',	visning: '4. hver måned'},
			{verdi: '5',	visning: '5. hver måned'},
			{verdi: '6',	visning: '6. hver måned'},
			{verdi: '7',	visning: '7. hver måned'},
			{verdi: '8',	visning: '8. hver måned'},
			{verdi: '9',	visning: '9. hver måned'},
			{verdi: '10',	visning: '10. hver måned'},
			{verdi: '11',	visning: '11. hver måned'},
			{verdi: '12',	visning: '12. hver måned'},
			{verdi: '13',	visning: '13. hver måned'},
			{verdi: '14',	visning: '14. hver måned'},
			{verdi: '15',	visning: '15. hver måned'},
			{verdi: '16',	visning: '16. hver måned'},
			{verdi: '17',	visning: '17. hver måned'},
			{verdi: '18',	visning: '18. hver måned'},
			{verdi: '19',	visning: '19. hver måned'},
			{verdi: '20',	visning: '20. hver måned'},
			{verdi: '21',	visning: '21. hver måned'},
			{verdi: '22',	visning: '22. hver måned'},
			{verdi: '23',	visning: '23. hver måned'},
			{verdi: '24',	visning: '24. hver måned'},
			{verdi: '25',	visning: '25. hver måned'},
			{verdi: '26',	visning: '26. hver måned'},
			{verdi: '27',	visning: '27. hver måned'},
			{verdi: '31',	visning: 'Siste dagen hver måned'}
		]
	});

	
	var ukedager = Ext.create('Ext.data.Store', {
		storeId: 'ukedager',
		autoLoad: true,
		
		fields: ['verdi', 'visning'],
		data : [
			{verdi: '',	visning: 'ingen fast ukedag'},
			{verdi: '1',	visning: 'mandag'},
			{verdi: '2',	visning: 'tirsdag'},
			{verdi: '3',	visning: 'onsdag'},
			{verdi: '4',	visning: 'torsdag'},
			{verdi: '5',	visning: 'fredag'},
			{verdi: '6',	visning: 'lørdag'},
			{verdi: '7',	visning: 'søndag'}
		]
	});


	var terminantall = Ext.create('Ext.data.Store', {
		storeId: 'tidsperiode',
		autoLoad: true,
		
		fields: ['antall'],
		data: [
			{antall: 1},
			{antall: 2},
			{antall: 3},
			{antall: 4},
			{antall: 6},
			{antall: 12},
			{antall: 13},
			{antall: 26},
			{antall: 52}
		]
	});

	
	var tidsperiode = Ext.create('Ext.data.Store', {
		storeId: 'tidsperiode',
		
		fields: ['verdi', 'visning'],
		data: [
			{verdi: 'P12M',	visning: '1 år'},
			{verdi: 'P6M',	visning: '6 måneder'},
			{verdi: 'P4M',	visning: '4 måneder'},
			{verdi: 'P3M',	visning: '3 måneder'},
			{verdi: 'P2M',	visning: '2 måneder'},
			{verdi: 'P1M',	visning: '1 måned'},
			{verdi: 'P28D',	visning: '4 uker'},
			{verdi: 'P21D',	visning: '3 uker'},
			{verdi: 'P14D',	visning: '2 uker'},
			{verdi: 'P7D',	visning: '1 uke'},
			{verdi: 'P0M',	visning: 'ingen oppsigelsestid'}
		]
	});

	
	var hjelp = Ext.create('Ext.window.Window', {
		title: 'Veiledning',
		width: 700,
		height: 400,
		autoScroll: true,
		animCollapse: true,
		closeAction: 'hide',
		html: "<b>Tekstmal:</b><br />Velg hvilken mal som skal brukes for teksten i leieavtalen. Avtaleteksten kan endres i forhold til den valgte malen, eller du kan skrive en avtale fra scratch ved å velge 'ingen mal'. Det er mulig å legge til en ny mal eller redigere en eksisterende mal ifra '<a href=\"index.php?oppslag=valg_skjema\">innstillinger og vedlikehold</a>' i drift-menyen.<br /><br /><b>Leieobjekt:</b><br />Velg hvilken leilighet eller lokale som inngår i leieforholdet. Dersom leieobjektet er fullt utleid fra før må det først frigjøres ved å avslutte eksisterende leieforhold.<br /><br /><b>Andel:</b><br />Dersom det er flere leieforhold i ett og samme leieobjekt (= bofelleskap) så må graden som disponeres av hvert leieforhold angis. F.eks. dersom to personer deler ei leilighet (med hver sin leieavtale) vil hvert leieforhold sansynligvis disponere 50% av leiligheta. Summen av alle andeler i ett og samme leieobjekt kan aldri overstige 100%. Dersom summen er mindre enn 100% betyr det at det er ledig plass i leieobjektet. Andelen kan angis i prosent (70%), som brøk (2/6), eller som desimal (0,5). I alle tilfeller hvor det er bare et leieforhold per leilighet vil andelen være 100%, 1/1 eller 1.<br /><br /><b>Fradato:</b><br />Datoen da leieforholdet trer i kraft.<br /><br /><b>Til-dato:</b><br />Angi til-dato dersom leieforholdet er tidsbegrenset. Til-dato er siste dato i leieforholdet, altså månedens siste dag dersom det opphører ved månedsskifte.<br /><br /><b>Oppsigelsestid:</b><br />Angi avtalt oppsigelsestid. Oppsigelsestiden løper 'fra utløpet av inneværende leietermin' når avtalen blir sagt opp. Det vil løpe leie i oppsigelsestiden med mindre leieobjektet leies ut på nytt.<br /><br /><b>Antall forfall per år:</b><br />Antall leieforfall som avtales i løpet av et år. Leia forfaller forskuddsvis, altså idet en leieterminen begynner. Antallet forfall kan endres i løpet av leieforholdet (f.eks. for å tilpasses lønnsutbetalinger).<br /><br /><b>Leie per år:</b><br />Avtalt leie per år.<br /><br /><b>Juster forfall på dato:</b><br />Angi en dato her dersom lange leieterminer (f.eks. kvartalsvis eller årlig betaling) skal justeres til en bestemt dato, f.eks 1. januar.<br /><br /><b>Månedlig dato:</b><br />Angi en dato her dersom forfall skal justeres til en bestemt dato hver måned. Dersom dato ikke angis vil forfall avhenge av datoen leieavtalen startet (fradato). Det er kun mulig å angi månedlig dato dersom leia er delt i 12, 6, 4, 3, 2 eller 1 leieterminer per år.<br /><br /><b>Fast ukedag:</b><br />Angi en ukedag her dersom forfall skal justeres til en bestemt ukedag. Dersom feltet er blankt vil terminene avhenge av dagen da leieavtalen startet (fradato). Det er kun mulig å angi ukedag dersom leia er delt i 52, 26, 13, 4, 2 eller 1 leieterminer per år."
	});


	var polett = Ext.create('Ext.form.field.Hidden', {
		name: 'polett',
		value: '<?php echo $polett ? $polett : $this->opprettPolett();?>'
	});


	var detaljer = Ext.create('Ext.Template');


	var leieobjekt = Ext.create('Ext.form.field.ComboBox', {
		name: 'leieobjekt',
		fieldLabel: 'Leieobjekt',
		width: 400,
		matchFieldWidth: false,
		listConfig: {
			width: 600
		},
		
		store: leieobjektliste,
		queryMode: 'remote',
		minChars: 2,
		valueField: 'leieobjektnr',
		displayField: 'beskrivelse',
		triggerAction: 'all',
		
		allowBlank: false,
		forceSelection: true,
		selectOnFocus: true,
		typeAhead: true,
		
		validator: function(v) {
			var value = parseInt(leieobjekt.getValue());

			var record = leieobjekt.findRecordByValue(value);
			var tilgjengelighet = 0;

			if( record ) {

				tilgjengelighet = record.get('tilgjengelighet');
			}
			
			if(tilgjengelighet) {
				return true;
			}
			else {
				return "Velg et leieforhold med stor nok ledig andel fra angitt dato.";
			}
		}
	});
	
	
	var andel = Ext.create('Ext.form.field.Text', {
		name: 'andel',
		fieldLabel: "Andel (for bofellesskap)",
		labelWidth: 150,
		width: 200,
		
		value: '100%',
		maskRe: new RegExp("^[0-9/%,.]"),
		validator: validerAndel,
		allowBlank: false,
		blankText: 'Du må oppgi hvor stor andel (som brøk, desimal eller i prosent) av leieobjektet som inngår i leieforholdet. 1 eller 100% for udelt råderett over leieobjektet'
	});


	var fradato = Ext.create('Ext.form.field.Date', {
		name: 'fradato',
		fieldLabel: 'Fradato',
		labelWidth: 100,
		format: 'd.m.Y',
		submitFormat: 'Y-m-d',
		value: '<?php echo date("Y-m-01", $this->leggtilIntervall(time(), 'P1M'));?>',
		width: 200,
		allowBlank: false,
		
		validator: function(v) {
			if((tildato.getValue()) && v > tildato.getValue()) {
				return 'Fradato kan ikke være senere enn tildato';
			}
			else {
				return true;
			}
		}
	});


	var tildato = Ext.create('Ext.form.field.Date', {
		name: 'tildato',
		fieldLabel: 'Tildato',
		labelWidth: 100,
		format: 'd.m.Y',
		submitFormat: 'Y-m-d',
		width: 200
	});


	var oppsigelsestid = Ext.create('Ext.form.field.ComboBox', {
		fieldLabel: 'Oppsigelsestid',
		labelWidth: 80,
		name: 'oppsigelsestid',
		hiddenName: 'oppsigelsestid',
		width: 200,
		listConfig: {
			width: 300
		},
		
		store: tidsperiode,
		queryMode: 'local',
		displayField: 'visning',
		valueField: 'verdi',
		value: 'P3M',
		triggerAction: 'all',

		allowBlank: true,
		typeAhead: true,
		editable: true,
		selectOnFocus: true,
		forceSelection: true

	});


	var avtalemal = Ext.create('Ext.form.field.ComboBox', {
		fieldLabel: 'Tekstmal for leieavtale',
		labelWidth: 150,
		name: 'avtalemal',
		hiddenName: 'avtalemal',
		width: 400,
		
		store: avtalemalliste,
		queryMode: 'local',
		displayField: 'malnavn',
		valueField: 'malnr',
		value: 1,
		triggerAction: 'all',
		
		allowBlank: false,
		typeAhead: true,
		editable: false,
		forceSelection: true,
		selectOnFocus: true,
		
		listeners: {
			select: function( combo, records, eOpts ) {
				var maleksempel = Ext.create('Ext.window.Window', {
					title: records[0].get('malnavn'),
					width: 700,
					height: 400,
					autoScroll: true,
					animCollapse: true,
					html: records[0].get('mal'),
					buttons: [{
						text: 'OK',
						handler: function() {
							maleksempel.close();
						}
					}]
				});
				maleksempel.show();

			}
		}
	});

	
	<?foreach($delkravtyper as $delkrav):?>
	var delkrav<?php echo $delkrav->id?> = Ext.create('Ext.form.field.Number', {
		allowDecimals: <?php echo $delkrav->relativ ? "true" : "false";?>,
		minValue: 0,
		allowBlank: false,
		readOnly: <?php echo $delkrav->valgfritt ? "false" : "true";?>,
		decimalPrecision: 1,
		decimalSeparator: ',',
		hideTrigger: true,
		fieldLabel: '<?php echo addslashes($delkrav->navn);?><br />(<?php echo $delkrav->valgfritt ? "Oppgis" : "Låst";?> i <?php echo $delkrav->relativ ? "prosent" : "kr per år";?>)',
		labelWidth: 120,
		name: 'delkrav<?php echo $delkrav->id?>',
		value: '<?php echo $delkrav->relativ ? $delkrav->sats * 100 : $delkrav->sats;?>',
		width: 180
	});
	<?endforeach;?>
	
	
	<?foreach($selvstendigeTillegg as $delkrav):?>
	var delkrav<?php echo $delkrav->id?> = Ext.create('Ext.form.field.Number', {
		allowDecimals: <?php echo $delkrav->relativ ? "true" : "false";?>,
		minValue: 0,
		allowBlank: false,
		readOnly: <?php echo $delkrav->valgfritt ? "false" : "true";?>,
		decimalPrecision: 1,
		decimalSeparator: ',',
		hideTrigger: true,
		fieldLabel: '<?php echo addslashes($delkrav->navn);?><br />(<?php echo $delkrav->valgfritt ? "Oppgis" : "Låst";?> i <?php echo $delkrav->relativ ? "prosent" : "kr per år";?>)',
		labelWidth: 120,
		name: 'delkrav<?php echo $delkrav->id?>',
		value: '<?php echo $delkrav->relativ ? $delkrav->sats * 100 : $delkrav->sats;?>',
		width: 180
	});
	<?endforeach;?>
	
	
	var antallTerminer = Ext.create('Ext.form.field.ComboBox', {
		allowBlank: false,
		displayField: 'antall',
		editable: true,
		fieldLabel: 'Antall forfall per år',
		labelWidth: 150,
		forceSelection: false,

		queryMode: 'local',
		nanText: 'Kun heltall er tillatt',
		name: 'ant_terminer',
		selectOnFocus: true,

		store: terminantall,
		triggerAction: 'all',
		typeAhead: true,
		validator: function(v) {
			if(v == parseInt(v) && parseInt(v)) {
				return true;
			}
			else return "Ugyldig verdi.";
		},
		value: 12,
		width: 200,
		
		listeners: {
			change: function( combo, newValue, oldValue, eOpts ) {
				dag_i_måneden.disable();
				ukedag.disable();
				if(newValue == 1 || newValue == 2 || newValue == 3 || newValue == 4 || newValue == 6 || newValue == 12) {
					dag_i_måneden.enable();
				}
				if(newValue == 1 || newValue == 2 || newValue == 4 || newValue == 13 || newValue == 26 || newValue == 52) {
					ukedag.enable();
				}
				fordelLeiaPåTerminer();
			}
		}
	});


	var leieknapp = Ext.create('Ext.button.Button', {
		text: 'Foreslå leie',
		handler: function() {
			beregnLeie();
		}
	});


	var leiebeløp = Ext.create('Ext.form.field.Number', {
		name: 'leiebeløp',
		fieldLabel: 'Leie per år',
		hideTrigger: true,

		allowDecimals: false,
		minValue: 0,
		allowBlank: false,
		decimalSeparator: ',',
		width: 200,
		
		listeners: {
			change: fordelLeiaPåTerminer
		}
	});
	
	
	var terminbeløp = Ext.create('Ext.form.field.Display', {
		value: '',
		fieldLabel: 'Terminbeløp',
		width: 200
	});
	
	
	var fast_dato = Ext.create('Ext.form.field.Date', {
		name: 'fast_dato',
		fieldLabel: 'Juster forfall årlig til den',
		labelWidth: 220,
		submitFormat: 'm-d',
		format: 'd.m',

		allowBlank: true
	});


	var dag_i_måneden = Ext.create('Ext.form.field.ComboBox', {
		name: 'dag_i_måneden',
		hiddenName: 'dag_i_måneden',
		fieldLabel: 'Legg forfall fast til denne datoen',
		labelWidth: 190,
		disabled: false,
		
		store: datoer,
		queryMode: 'local',
		displayField: 'visning',
		valueField: 'verdi',
		value: '1',
		
		allowBlank: true,
		editable: true,
		typeAhead: true,
		forceSelection: true,
		selectOnFocus: true,
		triggerAction: 'all'
	});


	var ukedag = Ext.create('Ext.form.field.ComboBox', {
		name: 'ukedag',
		hiddenName: 'ukedag',
		fieldLabel: 'Legg forfall til denne dagen i uka',
		labelWidth: 190,
		disabled: true,

		store: ukedager,
		queryMode: 'local',
		displayField: 'visning',
		valueField: 'verdi',
		value: '',

		allowBlank: true,
		editable: true,
		typeAhead: true,
		forceSelection: true,
		selectOnFocus: true,
		triggerAction: 'all'
	});


	var detaljpanel = Ext.create('Ext.panel.Panel', {
		autoScroll: true,
		frame: true,
		id: 'detaljpanel',
		region: 'east',
		width: 200
	});

	
	var fortsettknapp = Ext.create('Ext.button.Button', {
		text: 'Fortsett',
		scale: 'medium',
		handler: function() {
			if( skjema.isValid() ) {
				skjema.getForm().submit({
					url: 'index.php?oppslag=leieforhold_opprett-2_leietakere'
				});
			}
		}
	});


	var skjema = Ext.create('Ext.form.Panel', {
		title:	'Registrer nytt leieforhold',
		frame: true,
		layout: 'border',
		renderTo:	'panel',
		standardSubmit:	true,
		autoScroll: true,
		height:	500,
		width:	900,

		fieldDefaults: {
		},
		items: [
			{
				xtype: 'container',
				region: 'center',
		items:	[
			polett,

<?php //	Alle mottatte POST-verdier som ikke tilhører dette skjemaet videresendes som skjulte felter ?>
<?php foreach($_POST as $attributt => $verdi):?>
	<?php preg_match('/^[a-zæøå_]+/i', $attributt, $treff);?>
	<?php switch( $treff[0] ):
	case "polett":
	case "avtalemal":
	case "leieobjekt":
	case "andel":
	case "fradato":
	case "tildato":
	case "oppsigelsestid":
	case "delkrav":
	case "leiebeløp":
	case "ant_terminer":
	case "fast_dato":
	case "dag_i_måneden":
	case "ukedag":
		break;?>
	<?php default:?>
		{
			xtype: 'hidden',
			name: '<?php echo $attributt;?>',
			value: '<?php echo addslashes($verdi);?>'
		},

	<?php break;?>
	<?php endswitch;?>
<?php endforeach;?>

			{
				xtype: 'container',
				layout: {
					type: 'hbox', // Ext.layout.container.HBox
					align: 'top' // Vertical alignment (top | middle | bottom | stretch | stretchmax)
				},

				defaults: {
					padding: '0 5'
				},
				items: [
					avtalemal,
					{
						xtype: 'displayfield',
						value: "Avtaleteksten kan tilpasses før den skrives ut."
					}
				]
			},
			{
				xtype: 'fieldset',
				title: 'Leieforholdets omfang',
				layout: 'vbox',

				items: [
					{
						xtype: 'container',
						layout: 'hbox',

						defaults: {
							margin: '0 5'
						},
						items: [
							leieobjekt,
							andel,
							{
								xtype: 'displayfield',
								value: "<img src=\"../bilder/hjelp-2.png\" title=\"Andelen er 100% for hele leieobjektet.\nFor bofellesskap oppgis andelen som brøk eller prosenter.\nF.eks. '1/6' eller '25%'.\">"
							}

						]
					},
					{
						xtype: 'container',
						layout: 'hbox',
						items: [
							{
								xtype: 'container',
								flex: 1,
								padding: '0 5',
								items: [
									fradato
								]
							},
							{
								xtype: 'container',
								flex: 1,
								padding: '0 5',
								items: [
									tildato
								]
							},
							{
								xtype: 'container',
								flex: 1,
								padding: '0 5',
								items: [
									oppsigelsestid
								]
							}
						]
					}
				]
			},
			{
				xtype: 'fieldset',
				title: 'Leieberegning',
				layout: 'vbox',

				items: [
					{
						xtype: 'container',
						layout: 'hbox',

						defaults: {
							margin: '0 5'
						},
						items: [
						]
					},

				<?php foreach($delkravtyper as $indeks => $delkrav):?>
					<?php if($indeks/3 == (int)($indeks/3)):
						//	dette er første av tre kolonner?>

					{
						xtype: 'container',
						layout: 'hbox',
						items: [
					<?php endif;?>
						
							{
								xtype: 'container',
								flex: 1,
								padding: '0 5',
								items: [
									delkrav<?php echo $delkrav->id;?>

								]
					<?php if((($indeks + 1)/3 == (int)(($indeks + 1)/3)) || ($indeks + 1 == count($delkravtyper))):
						//	dette er tredje av tre eller siste kolonne?>							
							}
						]
					},
					<?php else:?>

							},
					<?php endif;?>
				<?endforeach;?>
					{
						xtype: 'container',
						layout: 'hbox',
						items: [							
							{
								xtype: 'container',
								flex: 1,
								padding: '0 5',
								items: [
									leiebeløp
								]
							},												
							{
								xtype: 'container',
								flex: 1,
								padding: '0 5',
								items: [
									leieknapp
								]
							},												
							{
								xtype: 'container',
								flex: 1,
								padding: '0 5',
								items: [
									antallTerminer
								]
							},												
							{
								xtype: 'container',
								flex: 1,
								padding: '0 5',
								items: [
									terminbeløp
								]			
							}
						]
					}
				]
			},

<?php if($tillegg):?>
			{
				xtype: 'fieldset',
				title: 'Tillegg til husleia',
				layout: 'vbox',

				items: [
					<?php echo implode(",", $tillegg);?>

				]
			},

<?php endif;?>

			{
				xtype: 'fieldset',
				title: 'Terminforfall',
				fieldDefaults: {
					width: 300,
				},
				items: [
					fast_dato,
					dag_i_måneden,
					ukedag
				]
			}
		],
				layout: 'anchor'
			},
			detaljpanel
		],
		buttons: [{
			scale: 'medium',
			handler: function() {
				hjelp.show();
			},
			icon: '../bilder/hjelp-2.png',
			iconAlign: 'right',
			text: 'Hjelp'
		}, {
			scale: 'medium',
			handler: function() {
				window.location = '<?php echo $this->returi->get();?>';
			},
			text: 'Avbryt'
		},
		fortsettknapp]
	});


	var lastLeieobjektliste = function() {
		leieobjektliste.load();
	}

	leieobjektliste.on({
		beforeload: function(store, operation, eOpts ) {
			forespørsel = Math.random();
			leieknapp.disable();
			fortsettknapp.disable();
			
			store.getProxy().extraParams = {
				fra: Ext.Date.format(fradato.getValue(), 'Y-m-d'),
				til: Ext.Date.format(tildato.getValue(), 'Y-m-d'),
				andel: andel.getValue(),
				req: forespørsel
			};
		},
		load: function( store, records, successful, eOpts ) {
			var reader = store.getProxy().getReader();
			
			if(reader.rawData.forespørsel = forespørsel) {
				forespørsel = 0;
				leieknapp.enable();
				fortsettknapp.enable();
				
//				alert(leieobjekt.getValue());
				var valgt = leieobjekt.findRecordByValue( leieobjekt.getValue() );
				if(valgt) {
					detaljpanel.update(valgt.get('detaljer'));
				}
			}
			else {
				leieobjekt.validate();
				andel.validate();
				fradato.validate();
				tildato.validate();
			}
		}
	});
	
	leieobjekt.on({
		select: function( combo, records, eOpts ) {
			leieobjekt.validate();
			andel.validate();
			fradato.validate();
			tildato.validate();
			detaljpanel.update(records[0].get('detaljer'));
		}
	});

	andel.on({
		change: function( field, newValue, oldValue, eOpts ) {
			if( field.isValid() ) {
				lastLeieobjektliste();
			}
		}
	});
	fradato.on({
		change: lastLeieobjektliste,

		blur: function(field, eventObject, eOpts) {
			v = field.getValue();
			y = v.getFullYear() + 3;
			m = v.getMonth(); // getMonth returnerer 0-11
			d = v.getDate();
			if (m < 0) {
				m = 11;
				y = y - 1;
			}
			nydato = new Date(y, m, d);
			nydato.setTime( nydato.getTime() - 3600 * 24 );
			tildato.setValue(nydato);
		}
		
	});
	tildato.on({
		change: lastLeieobjektliste
	});

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
	
	case "leieobjektliste": {
		$query = @$_GET['query'];
		$andel = $_GET['andel'];
		$fra = $this->fra ? $this->fra : date('Y-m-d');
		$til = $this->til ? $this->til : $this->fra;
		
		$resultat = (object)array(
			'success'	=> true,
			'msg'		=> "",
			'data'		=> array()
		);

		// Hent alle leieobjektene som passer til søkefeltet
		$filter = "!ikke_for_utleie\n";
		if( $query ) {
			$filter	.= "AND (leieobjektnr LIKE '" . (int)$query . "' OR bygning LIKE '%{$query}%' OR navn LIKE '%{$query}%' OR gateadresse LIKE '%{$query}%' OR beskrivelse LIKE '%{$query}%')";
		}
		
		$leieobjektsett =	$this->mysqli->arrayData(array(
			'source'	=> "{$tp}leieobjekter AS leieobjekter",
			'fields'	=> "leieobjektnr AS id",
			'orderfields'	=> "CONVERT(leieobjektnr, SIGNED)",
			'class'		=> "Leieobjekt",
			'where'		=> $filter
		));
		
		foreach($leieobjektsett->data as $leieobjekt) {

			$beskrivelse = "{$leieobjekt}: {$leieobjekt->hent('beskrivelse')}";
			$årsleie = $leieobjekt->beregnLeie( $andel );
			// Sjekk utleiegraden for dette leieobjektet
			$utleie = $leieobjekt->hentUtleie($fra, $til);
			$beboere = $leieobjekt->hent('beboere', array('dato' => $fra));
			
			$html = "<div><strong>" . ucfirst($leieobjekt->hent('type')) . "&nbsp;{$leieobjekt}:</strong> {$leieobjekt->hent('beskrivelse')}<br />";
			$html .= "<strong>Areal:</strong> " . $leieobjekt->hent('areal') . "m&sup2;<br />";
			$html .= "<strong>Antall rom:</strong> " . $leieobjekt->hent('antRom') . "<br />";
			$html .= "<strong>Bad:</strong> " . ($leieobjekt->hent('bad') ? "Ja" : "Nei") . "<br />";
			$html .= "<strong>Toalett:</strong> " . ucfirst($leieobjekt->hent('toalett')) . "<br />";
			$html .= ( $leieobjekt->hent('bilde') ? "<img style=\"max-width: 180px;\" src=\"{$leieobjekt->hent('bilde')}\"/>" : "" );
			$html .= $leieobjekt->hent('merknader') . "<br />";
			$html .= "</div>";
			
			$html .= "<div><a href=\"index.php?oppslag=leieobjekt_kort&id={$leieobjekt}\">Klikk her for flere detaljer eller for å oppdatere opplysningene</a></div>";
			$html .= "<div><strong>Beboere per " . date('d.m.Y', strtotime( $fra )) . "</strong>: " . ($beboere ? $beboere : "<i>Ingen</i>") . "</div>";
			
			// Oppsigelsen må hentes for hvert enkelt leieforhold, og sammenliknes
			//	for å fastlå fristillelsesdato
			$oppsigelse = false;
			$andelSomVilFristilles = 0;

			if( !$utleie->ledig ) {
				$beskrivelse .= " (Utleid.";

				foreach($utleie->faktiskeLeieforhold as $leieforhold) {
					if(
						$oppsigelse === false
						|| (
							is_object( $oppsigelse )
						&&	is_object( $leieforhold->hent('oppsigelse') )
						&&	$leieforhold->hent('oppsigelse')->fristillelsesdato
								< $oppsigelse->fristillelsesdato
						&&	$leieobjekt->hentUtleie(
							$leieforhold->hent('oppsigelse')->fristillelsesdato 
						)->ledig > 0
						)
					) {
						$oppsigelse = $leieforhold->hent('oppsigelse');
					}
					
				}
				
				if($oppsigelse) {
					$andelSomVilFristilles = $leieobjekt->hentUtleie(
						$oppsigelse->fristillelsesdato 
					)->ledig;
					
					$beskrivelse .= (
						($andelSomVilFristilles < 1)
						? " {$this->tilBrøk($andelSomVilFristilles)} blir ledig den "
						: " Blir ledig "
					) . $oppsigelse->fristillelsesdato->format('d.m.Y');
				}
				
				$beskrivelse .= ").";
			}
			
			else {
				$beskrivelse	.= ($utleie->ledig < 1)
								? (" (" . $this->brok($utleie->ledig) . " er ledig).")
								: "";
			}
			
			$resultat->data[] = (object)array(
				'leieobjektnr'				=> (int)$leieobjekt->hent('id'),
				'beskrivelse'				=> $beskrivelse,
				'andelSomVilFristilles'		=> $andelSomVilFristilles,
				'årsleie'					=> $årsleie,
				'årsleieFormatert'			=> $this->kr( $årsleie ),
				'tilgjengelighet'			=> $utleie->ledig,
				'tilgjengelighetFormatert'	=> $this->tilBrøk($utleie->ledig),
				'ledig'						=> (
													$oppsigelse
													? $oppsigelse->fristillelsesdato->format('d.m.Y')
													: null
												),
				'detaljer'				=> $html
			);

		}
		
		usort($resultat->data, array( 'Leiebase', 'sammenliknLeieobjektersLedighet' ));
				
		return (json_encode($resultat));
		break;
	}
		
	default: {
		return json_encode($this->arrayData($this->hoveddata));
		break;
	}
	}
}



function taimotSkjema() {
}



}
?>