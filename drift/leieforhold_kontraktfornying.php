<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Forny leieavtale';
public $ext_bibliotek = 'ext-4.2.1.883';


function __construct() {
	parent::__construct();
}

function skript() {
	if(@$_GET['returi'] == "default") $this->returi->reset();
	$tp = $this->mysqli->table_prefix;
	
	$leieforhold = $this->leieforhold( (int)@$_GET['id'], true );
	$leieobjekt = $leieforhold->hent('leieobjekt');
	
	$grunnleie = $leieobjekt->beregnLeie( $leieforhold->hent('andel') );
	
	if( $leieforhold->hent('tildato') ) {
		$fradato = clone $leieforhold->hent('tildato');
		$fradato->add( new DateInterval('P1D') );
	}
	else {
		$fradato = new DateTime;
	}

	$avtalemalliste = $this->mysqli->arrayData( array(
		'source' =>	"{$tp}avtalemaler AS avtalemaler",
		'fields' => "avtalemaler.malnr, avtalemaler.malnavn, avtalemaler.mal"
	))->data;
	array_unshift( $avtalemalliste, (object)array(
		'malnr'		=> 0,
		'malnavn'	=> "---  Kopier teksten fra forrige leieavtale ---",
		'mal'		=> $leieforhold->gjengiAvtaletekst( false )
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

	function beregnLeie() {
		var grunnleie = <?php echo $grunnleie;?>;

		var årsleie = grunnleie		// !! OBS! Ingen semikolon! Beregninga fortsetter !!
		<?php foreach($delkravtyper as $delkrav):?>
			<?php if($delkrav->relativ):?> + delkrav<?php echo $delkrav->id?>.getValue() / 100 * grunnleie
			<?php else:?> + delkrav<?php echo $delkrav->id?>.getValue()
			<?php endif;?>
		 <?php endforeach;?>;
		 
		leiebeløp.setValue( årsleie );

	}
	
	
	function fordelLeiaPåTerminer() {
		if(leiebeløp.isValid() && antallTerminer.isValid()) {
			var beløp = leiebeløp.getValue() / antallTerminer.getValue();
			terminbeløp.setValue( Ext.util.Format.noMoney( Math.round(beløp) ) );
		}
	}



	var avtalemalliste = Ext.create('Ext.data.Store', {
		storeId: 'avtalemalliste',		
		data: <?php echo json_encode($avtalemalliste);?>,
		fields: ['malnr', 'malnavn', 'mal']
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
		html: "<b>Tekstmal:</b><br />Du kan kopiere eksisterende avtaletekst, eller du kan skrive en helt ny ut ifra en eksisterende mal. Den endelige teksten kan redigeres i forhold til malen. Det er mulig å endre malene eller lage en helt ny mal ifra '<a href=\"index.php?oppslag=valg_skjema\">innstillinger og vedlikehold</a>' i drift-menyen.<br /><br /><b>Fradato:</b><br />Datoen den nye leieavtalen trer i kraft. Leieforholdet løper uavbrutt fra forrige leieavtale, men evt nytt leiebeløp får virkning fra denne datoen.<br /><br /><b>Til-dato:</b><br />Angi til-dato dersom leieforholdet er midlertidig. Til-dato er siste dato i leieforholdet, altså månedens siste dag dersom det opphører ved månedsskifte.<br /><br /><b>Oppsigelsestid:</b><br />Angi avtalt oppsigelsestid. Oppsigelsestiden løper 'fra utløpet av inneværende leietermin' når avtalen blir sagt opp. Det vil løpe leie i oppsigelsestiden med mindre leieobjektet leies ut på nytt.<br /><br /><b>Antall forfall per år:</b><br />Antall leieforfall som avtales i løpet av et år. Leia forfaller forskuddsvis, altså idet en leieterminen begynner. Antallet forfall kan endres i løpet av leieforholdet (f.eks. for å tilpasses lønnsutbetalinger).<br /><br /><b>Leie per år:</b><br />Avtalt leie per år.<br /><br /><b>Juster forfall på dato:</b><br />Angi en dato her dersom lange leieterminer (f.eks. kvartalsvis eller årlig betaling) skal justeres til en bestemt dato, f.eks 1. januar.<br /><br /><b>Månedlig dato:</b><br />Angi en dato her dersom forfall skal justeres til en bestemt dato hver måned. Dersom dato ikke angis vil forfall avhenge av datoen leieavtalen startet (fradato). Det er kun mulig å angi månedlig dato dersom leia er delt i 12, 6, 4, 3, 2 eller 1 leieterminer per år.<br /><br /><b>Fast ukedag:</b><br />Angi en ukedag her dersom forfall skal justeres til en bestemt ukedag. Dersom feltet er blankt vil terminene avhenge av dagen da leieavtalen startet (fradato). Det er kun mulig å angi ukedag dersom leia er delt i 52, 26, 13, 4, 2 eller 1 leieterminer per år."
	});


	var detaljer = Ext.create('Ext.Template');


	var fradato = Ext.create('Ext.form.field.Date', {
		name: 'fradato',
		fieldLabel: 'Fradato',
		labelWidth: 100,
		format: 'd.m.Y',
		submitFormat: 'Y-m-d',
		value: '<?php echo $fradato->format('Y-m-d');?>',
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
		allowNegative: false,
		allowBlank: false,
		readOnly: <?php echo $delkrav->valgfritt ? "false" : "true";?>,
		decimalPrecision: 2,
		decimalSeparator: ',',
		hideTrigger: true,
		fieldLabel: '<?php echo addslashes($delkrav->navn);?><br />(<?php echo $delkrav->valgfritt ? "Oppgis" : "Låst";?> i <?php echo $delkrav->relativ ? "prosent" : "kr per år";?>)',
		labelWidth: 120,
		name: 'delkrav<?php echo $delkrav->id?>',
		value: '<?php echo $delkrav->relativ ? $delkrav->sats * 100 : $delkrav->sats?>',
		width: 180
	});
	<?endforeach;?>
	
	
	<?foreach($selvstendigeTillegg as $delkrav):?>
	var delkrav<?php echo $delkrav->id?> = Ext.create('Ext.form.field.Number', {
		allowDecimals: <?php echo $delkrav->relativ ? "true" : "false";?>,
		allowNegative: false,
		allowBlank: false,
		readOnly: <?php echo $delkrav->valgfritt ? "false" : "true";?>,
		decimalPrecision: 1,
		decimalSeparator: ',',
		hideTrigger: true,
		fieldLabel: '<?php echo addslashes($delkrav->navn);?><br />(<?php echo $delkrav->valgfritt ? "Oppgis" : "Låst";?> i <?php echo $delkrav->relativ ? "prosent" : "kr per år";?>)',
		labelWidth: 120,
		name: 'delkrav<?php echo $delkrav->id?>',
		value: '<?php echo $delkrav->relativ ? $delkrav->sats * 100 : $delkrav->sats?>',
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
		value: <?php echo $leieforhold->hent('ant_terminer'); ?>,
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
		allowNegative: false,
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


	var fortsettknapp = Ext.create('Ext.button.Button', {
		text: 'Fortsett',
		scale: 'medium',
		handler: function() {
			if( skjema.isValid() ) {
				skjema.getForm().submit({
					waitMsg: 'Registrerer leieavtalen..',
					url: 'index.php?oppslag=leieforhold_kontraktfornying&oppdrag=taimotskjema&id=<?php echo $leieforhold;?>'
				});
			}
		}
	});


	var skjema = Ext.create('Ext.form.Panel', {
		title:	'Fornyelse av leieavtale i leieforhold <?php echo $leieforhold;?>: <?php echo $leieforhold->hent("beskrivelse");?>',
		frame: true,
		layout: 'border',
		renderTo:	'panel',
		standardSubmit:	false,
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
				title: 'Leieavtalens gyldighet',
				layout: 'vbox',

				items: [
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
					},
					{
						xtype: 'displayfield',
						value: 'Eksisterende leie er <?php echo $this->kr( $leieforhold->hent("leiebeløp") * $leieforhold->hent("ant_terminer") );?> per år, (<?php echo $this->kr( $leieforhold->hent("leiebeløp") );?> <?php echo $leieforhold->hent("ant_terminer");?> ganger i året)'
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
			}
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


	fradato.on({
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
	
	skjema.on({
		actioncomplete: function(form, action) {
			if(action.type == 'submit') {
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Ingen JSON');
				} else {
					Ext.MessageBox.alert(
						'Vellykket',
						action.result.msg,
						function() {
							window.location = '<?php echo $this->returi->get();?>';
						}
					);
				}
			}
		},
							

		actionfailed: function(form,action){
			if(action.type == 'submit') {
				var result = Ext.decode(action.response.responseText); 
				if(result && result.msg) {			
					Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', result.msg, function() {
						window.location = '<?php echo $this->returi->get();?>';
					});
				}
				else {
					Ext.MessageBox.alert('Problem:', 'Fornying mislyktes av ukjent grunn. Action type='+action.type+', failure type='+action.failureType);
				}
			}
			
		}
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
	
	default:
		return json_encode();
		
	}
}



function taimotSkjema() {
	$tp = $this->mysqli->table_prefix;

	$resultat = (object)array(
		'success'	=> false,
		'msg'		=> "",
		'data'		=> array(),
		'id'		=> 0
	);

	$leieforhold	= $this->leieforhold( (int)@$_GET['id'], true );
	$kontrakter		= $leieforhold->hent('kontrakter');
	$sisteKontrakt	= reset($kontrakter);
	$leieobjekt		= $leieforhold->hent('leieobjekt');
	$fradato		= new DateTime($_POST['fradato']);
	$tildato		= @$_POST['tildato'] ? new DateTime($_POST['tildato']) : null;
	$utleie			= $leieobjekt->hentUtleie($fradato, $tildato, $leieforhold);
	$andel			= $leieforhold->hent('andel');
	$leietakere		= $leieforhold->hent('leietakere');
	$basisleie		= $_POST['leiebeløp'];
	$årsleie		= $_POST['leiebeløp'];
	$antallTerminer	= (int)@$_POST['ant_terminer']
					? (int)$_POST['ant_terminer']
					: $leieforhold->hent('ant_terminer');
	
	$kontrakttekst	= @$this->mysqli->arrayData(array(
		'source'	=> "{$tp}avtalemaler AS avtalemaler",
		'fields'	=> "mal",
		'where'		=> "malnr = '" . (int)@$_POST['avtalemal'] . "'"
	))->data[0]->mal;
	if ( !$kontrakttekst ) {
		$kontrakttekst = $leieforhold->gjengiAvtaletekst( false );
	}


	// Sjekk at leieforholdet ikke er oppsagt
	if( $leieforhold->hent('oppsigelse') ) {
		
		echo json_encode(array(
			'success'	=>	false,
			'msg'		=> "Kunne ikke fornye denne leieavtalen fordi leieforholdet er oppsagt."
		));
		return;
	}
	
	// Sjekk at fra-dato er senere enn tidligere kontrakter
	if( $fradato->format('Y-m-d') <= $sisteKontrakt->dato->format('Y-m-d') ) {
		echo json_encode(array(
			'success'	=>	false,
			'msg'		=> "Leieavtalen kan ikke begynne tidligere enn forrige leieavtale."
		));
		return;
	}
	
	// Sjekk at til-dato er etter fra-dato
	if($tildato and $tildato <= $fradato) {
		echo json_encode(array(
			'success'	=>	false,
			'msg'		=> "Til-dato må være etter fra-dato."
		));
		return;
	}
	
	// Sjekk at leieobjektet er ledig på det aktuelle tidspunktet
	if( round($this->fraBrøk($andel), 4) > round($utleie->ledig, 4) ) {
		echo json_encode(array(
			'success'	=>	false,
			'msg'		=> "{$andel} er ikke tilgjengelig. Kun {$this->tilBrøk($utleie->ledig)} av leieobjektet er ledig for utleie i det aktuelle tidspunktet."
		));
		return;
	}
	
	
	$alleDelkravtyper	= $this->mysqli->arrayData(array(
		'source'	=> "{$tp}delkravtyper AS delkravtyper",
		'fields'	=> "id, navn, relativ, sats, selvstendig_tillegg",
		'where'		=> "aktiv and kravtype = 'Husleie'",
		'orderfields'	=> "orden"
	))->data;
	$delkravtyper = array();
	$selvstendigeTillegg = array();
	
	
	// Basisleia, dvs husleie før delkrav, beregnes.
	//	Vi gjennomgår alle delkravene i angitt orden, og trekker fra delbeløpene
	//	inntil alle er trukket fra eller det ikke er noe igjen:
	//	Alle fastbeløp trekkes direkte, og alle forbigåtte prosentvise satser fordeles etterpå.
	//	Formel: netto = (brutto - del1 - del3) / (1 + sats2 + sats4)
	$nevner = 1;
	foreach($alleDelkravtyper as $delkravtype) {
		$angittSats = str_replace(",", ".", @$_POST["delkrav{$delkravtype->id}"]);


		// Selvstendige tillegg:
		// Selvstendige tillegg puttes i en egen beholder; $selvstendigeTillegg
		if( ($angittSats != 0) and $delkravtype->selvstendig_tillegg ) {
		
			// Dersom delkravtypen er relativ formateres satsen som en faktor.
			if($delkravtype->relativ) {
				$delkravtype->sats = bcdiv( $angittSats, 100, 4 );
			}
			
			// Dersom delkravtypen ikke er relativ forblir den som den er
			else {
				$delkravtype->sats = $angittSats;
			}
			$selvstendigeTillegg[] = $delkravtype;
		}

		// Delkrav
		// Bruk bare de delkravtypene som er angitt på skjemaet,
		//	og bare så lenge det er noe igjen av grunnbeløpet
		else if( $angittSats and $basisleie > 0) {
		
			// Dersom delkravtypen er relativ formateres satsen som en faktor.
			// Alle de relative satsene legges sammen til en nevner som brukes for å beregne basisbeløpet
			if($delkravtype->relativ) {
				$delkravtype->sats = bcdiv( $angittSats, 100, 4 );
				$nevner = bcadd(
					$nevner,
					$delkravtype->sats,
					3
				);
			}
			
			// Dersom delkravtypen ikke er relativ trekkes delbeløpet direkte ifra bruttoleia,
			//	men bare så lenge det er noe igjen av basisleia.
			else {
				$delkravtype->sats = min($basisleie, $angittSats);
				$delkravtype->beløp = $delkravtype->sats;
				$basisleie = max(0, bcsub($basisleie, $angittSats));
			}
			$delkravtyper[] = $delkravtype;
		}
	}
	
	$basisleie = round( bcdiv( $basisleie, $nevner, 2 ));
	
	foreach($delkravtyper as $delkravtype) {
		if( $delkravtype->relativ ) {
			$delkravtype->beløp = round(bcmul($basisleie, $delkravtype->sats, 2));
		}
	}
	//	Basisleia er ferdig beregnet
	
	
	// Registrer leieavtalen
	$nyKontrakt	= $leieforhold->leggTilKontrakt(array(
		'dato'			=> $fradato,
		'tildato'		=> $tildato,
		'tekst'			=> $kontrakttekst,
		'oppsigelsestid' => $_POST['oppsigelsestid'],
		'leietakere'	=> $leietakere
	));
	
	$resultat->success	= (bool)$nyKontrakt;
	$leieforhold->sett('delkravtyper', null);
	$leieforhold->sett('tillegg', null);
	
	foreach($delkravtyper as $delkravtype) {
		$leieforhold->leggTilDelkravtype(
			$delkravtype->id,
			$delkravtype->relativ,
			$delkravtype->sats,
			false
		);
	}
		
	foreach($selvstendigeTillegg as $delkravtype) {
		$leieforhold->leggTilDelkravtype(
			$delkravtype->id,
			$delkravtype->relativ,
			$delkravtype->sats,
			true
		);
	}
	
	$leieforhold->sett('ant_terminer', $antallTerminer);
	$leieforhold->sett('årlig_basisleie', $basisleie);
		
	// Så opprettes husleiekrav for denne leieavtalen.
	$leie = $leieforhold->opprettLeiekrav($fradato, false, @$_POST['ukedag'], @$_POST['dag_i_måneden'], @$fast_dato);
	
	if( !$leie ) {
		$resultat->msg .= "<br />!!! OBS!!!<br />Det oppstod problemer med å opprette terminforfall for leieperioden.<br />";
	}
	else {
		$resultat->msg .= "<br />Det er opprettet terminer med forfallsdatoer.<br /><br />";
		
		foreach($leie as $krav) {

			// Andres leie i samme leieobjekt må kanskje slettes:
			while(
				$grad = $leieobjekt->hentLeiekrav($krav->hent('fom'), $krav->hent('tom') )->grad > 1.0001
				&& 
				$oppsigelsestid = $leieobjekt->hentLeiekrav($krav->hent('fom'), $krav->hent('tom') )->oppsigelsestid
			) {
				$kravForSletting = reset( $oppsigelsestid );
				$beskrivelse = $kravForSletting->hent('leieforhold')->hent('navn') . " sin leie for " . $kravForSletting->hent('termin');
				if( $kravForSletting->slett() ) {
					$resultat->msg .= "{$beskrivelse} har blitt sletta.<br />";
				}
				else {
					$resultat->msg .= "Det oppstod problemer med å slette {$beskrivelse}. Denne må muligens slettes manuelt for å unngå dobbeltfakturering.<br />";
				}
			}
		}
		
	}
	
	$resultat->msg .= "&nbsp;<br />";
	
	// Angi videre adressesti (må oppgis i omvendt rekkefølge)
 	$this->returi->set("{$this->http_host}/drift/index.php?oppslag=leieforholdkort&id={$nyKontrakt}");
 	$this->returi->set("{$this->http_host}/drift/index.php?oppslag=kontrakt_tekst&id={$nyKontrakt}");
	
	echo json_encode($resultat);
	return;
}



}
?>