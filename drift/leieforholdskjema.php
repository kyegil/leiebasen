<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Endre leieavtale';
public $ext_bibliotek = 'ext-4.2.1.883';


function __construct() {
	parent::__construct();
}

function skript() {
	if(@$_GET['returi'] == "default") $this->returi->reset();
	$tp = $this->mysqli->table_prefix;
	
	$leieforhold = $this->leieforhold( (int)@$_GET['id'], true );
	$kontrakter = $leieforhold->hent('kontrakter');
	$sisteKontrakt = reset($kontrakter);
	$kontraktdato = $sisteKontrakt->dato;
	$minKontraktdato = clone $kontraktdato;
	$tildato = $leieforhold->hent('tildato');
	$maksKontraktdato = clone $tildato;
	
	if( count($kontrakter) == 1) {
		$maksKontraktdato = $minKontraktdato;
	}
	else {
		$minKontraktdato = clone next($kontrakter)->dato;
		$minKontraktdato->add(new DateInterval('P1D'));
	}
	
	$delkravtyper = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}delkravtyper AS delkravtyper\n"
			.	"LEFT JOIN {$tp}leieforhold_delkrav AS leieforhold_delkrav\n"
			.	"ON delkravtyper.id = leieforhold_delkrav.delkravtype\n"
			.	"AND leieforhold_delkrav.leieforhold = '{$leieforhold}'",
		'where'			=> "delkravtyper.aktiv AND !IFNULL(leieforhold_delkrav.selvstendig_tillegg, delkravtyper.selvstendig_tillegg)",
		'fields'		=> "delkravtyper.id AS id,\n"
			.	"delkravtyper.navn AS navn,\n"
			.	"delkravtyper.kode AS kode,\n"
			.	"delkravtyper.beskrivelse AS beskrivelse,\n"
			.	"delkravtyper.kravtype AS kravtype,\n"
			.	"delkravtyper.valgfritt AS valgfritt,\n"
			.	"IFNULL(leieforhold_delkrav.relativ, delkravtyper.relativ) AS relativ,\n"
			.	"leieforhold_delkrav.sats AS sats\n",
		'orderfields'	=> "delkravtyper.orden, delkravtyper.id"
	))->data;

	$selvstendigeTillegg = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}delkravtyper AS delkravtyper\n"
			.	"LEFT JOIN {$tp}leieforhold_delkrav AS leieforhold_delkrav\n"
			.	"ON delkravtyper.id = leieforhold_delkrav.delkravtype\n"
			.	"AND leieforhold_delkrav.leieforhold = '{$leieforhold}'",
		'where'			=> "delkravtyper.aktiv AND IFNULL(leieforhold_delkrav.selvstendig_tillegg, delkravtyper.selvstendig_tillegg)",
		'fields'		=> "delkravtyper.id AS id,\n"
			.	"delkravtyper.navn AS navn,\n"
			.	"delkravtyper.kode AS kode,\n"
			.	"delkravtyper.beskrivelse AS beskrivelse,\n"
			.	"delkravtyper.kravtype AS kravtype,\n"
			.	"delkravtyper.valgfritt AS valgfritt,\n"
			.	"IFNULL(leieforhold_delkrav.relativ, delkravtyper.relativ) AS relativ,\n"
			.	"leieforhold_delkrav.sats AS sats\n",
		'orderfields'	=> "delkravtyper.orden, delkravtyper.id"
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

	function fordelLeiaPåTerminer() {
		if(leiebeløp.isValid() && antallTerminer.isValid()) {
			var beløp = leiebeløp.getValue() / antallTerminer.getValue();
			terminbeløp.setValue( Ext.util.Format.noMoney( Math.round(beløp) ) );
		}
	}



	slettLeietaker = function( personid, navn ) {
		Ext.Msg.confirm(
			"Bekreft",
			"Er du sikker på at " + navn + " skal strykes fra leieavtalen?",
			function( svar ) {
				if( svar == "yes" ) {
					Ext.Ajax.request({
						waitMsg: 'Stryker...',
						url: 'index.php?oppslag=leieforholdskjema&oppdrag=manipuler&data=strykleietaker&id=<?php echo $leieforhold;?>',
						params: {
							personid: personid
						},
						success: function(response, opts) {
							var result = Ext.decode( response.responseText );
							if ( result.success ) {
								Ext.MessageBox.alert('Suksess', navn + ' er strøket fra leieavtalen. Last siden på nytt for å se endringene');
							}
							else {
								Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', result.msg);
							}

						},
						failure: function(response, opts) {
							Ext.MessageBox.alert('Ups', 'Klarte ikke slette ' + navn);
						}
					});
				}
			}
		);
	}
	
	
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
		html: "<b>Til-dato:</b><br />Angi til-dato dersom leieforholdet er midlertidig. Til-dato er siste dato i leieforholdet, altså månedens siste dag dersom det opphører ved månedsskifte.<br /><br /><b>Oppsigelsestid:</b><br />Angi avtalt oppsigelsestid. Oppsigelsestiden løper 'fra utløpet av inneværende leietermin' når avtalen blir sagt opp. Det vil løpe leie i oppsigelsestiden med mindre leieobjektet leies ut på nytt.<br /><br /><b>Antall forfall per år:</b><br />Antall leieforfall som avtales i løpet av et år. Leia forfaller forskuddsvis, altså idet en leieterminen begynner. Antallet forfall kan endres i løpet av leieforholdet (f.eks. for å tilpasses lønnsutbetalinger).<br /><br /><b>Leie per år:</b><br />Avtalt leie per år."
	});


	var detaljer = Ext.create('Ext.Template');


	var tildato = Ext.create('Ext.form.field.Date', {
		fieldLabel: 'Til dato',
		labelWidth: 100,
		name: 'tildato',
		width: 200,
		msgTarget: 'title',

		value: '<?php echo $tildato ? $tildato->format('Y-m-d') : '';?>',
		format: 'd.m.Y',
		submitFormat: 'Y-m-d'
	});


	var dato = Ext.create('Ext.form.field.Date', {
		fieldLabel: 'Ikrafttreden leieavtale #<?php echo $sisteKontrakt->kontraktnr;?>',
		labelWidth: 170,
		name: 'dato',
		width: 270,
		msgTarget: 'title',
		
		value: '<?php echo $kontraktdato->format('Y-m-d');?>',
		minValue: '<?php echo $minKontraktdato->format('Y-m-d');?>',
		maxValue: '<?php echo $maksKontraktdato->format('Y-m-d');?>',
		format: 'd.m.Y',
		submitFormat: 'Y-m-d'
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
		msgTarget: 'title',
		
		store: tidsperiode,
		queryMode: 'local',
		displayField: 'visning',
		valueField: 'verdi',
		value: '<?php echo $this->periodeformat( $leieforhold->hent('oppsigelsestid'), true ) ;?>',
		triggerAction: 'all',

		allowBlank: true,
		typeAhead: true,
		editable: true,
		selectOnFocus: true,
		forceSelection: true

	});


	<?foreach($delkravtyper as $delkrav):?>
	var delkrav<?php echo $delkrav->id?> = Ext.create('Ext.form.field.Number', {
		fieldLabel: '<?php echo addslashes($delkrav->navn);?><br />(<?php echo $delkrav->valgfritt ? "Oppgis" : "Låst";?> i <?php echo $delkrav->relativ ? "prosent" : "kr per år";?>)',
		labelWidth: 120,
		name: 'delkrav<?php echo $delkrav->id?>',
		width: 180,
		msgTarget: 'title',

		value: '<?php echo $delkrav->relativ ? bcmul($delkrav->sats, 100, 1) : $delkrav->sats?>',
		allowDecimals: <?php echo $delkrav->relativ ? "true" : "false";?>,
		allowNegative: false,
		allowBlank: true,
		readOnly: <?php echo $delkrav->valgfritt ? "false" : "true";?>,
		decimalPrecision: 5,
		decimalSeparator: ',',
		hideTrigger: true
	});
	<?endforeach;?>
	
	
	<?foreach($selvstendigeTillegg as $delkrav):?>
	var delkrav<?php echo $delkrav->id?> = Ext.create('Ext.form.field.Number', {
		fieldLabel: '<?php echo addslashes($delkrav->navn);?><br />(<?php echo $delkrav->valgfritt ? "Oppgis" : "Låst";?> i <?php echo $delkrav->relativ ? "prosent" : "kr per år";?>)',
		labelWidth: 120,
		name: 'delkrav<?php echo $delkrav->id?>',
		width: 180,
		msgTarget: 'title',
		
		value: '<?php echo $delkrav->relativ ? $delkrav->sats * 100 : $delkrav->sats?>',
		allowDecimals: <?php echo $delkrav->relativ ? "true" : "false";?>,
		allowNegative: false,
		allowBlank: true,
		readOnly: <?php echo $delkrav->valgfritt ? "false" : "true";?>,
		decimalPrecision: 1,
		decimalSeparator: ',',
		hideTrigger: true
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
		msgTarget: 'title',
		width: 200,
		
		listeners: {
			change: function( combo, newValue, oldValue, eOpts ) {
				fordelLeiaPåTerminer();
			}
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
		msgTarget: 'title',
		width: 200,
		
		value: <?php echo $leieforhold->hent('leiebeløp') * $leieforhold->hent('ant_terminer');?>,
		listeners: {
			change: fordelLeiaPåTerminer
		}
	});

	
	var terminbeløp = Ext.create('Ext.form.field.Display', {
		value: '',
		fieldLabel: 'Terminbeløp',
		width: 200
	});
	
	
	var lagreknapp = Ext.create('Ext.button.Button', {
		text: 'Lagre',
		scale: 'medium',
		handler: function() {
			if( skjema.isValid() ) {
				skjema.getForm().submit({
					waitMsg: 'Registrerer leieavtalen..',
					url: 'index.php?oppslag=leieforholdskjema&oppdrag=taimotskjema&id=<?php echo $leieforhold;?>'
				});
			}
		}
	});


	var leietakere = Ext.create('Ext.form.FieldSet', {
		title: 'Leietaker(e)',
		border: false,
		scripts: true,
		loader: {
			url: 'index.php?oppslag=leieforholdskjema&oppdrag=hentdata&data=innehavere&id=<?php echo $leieforhold;?>',
			autoLoad: true
		}
	});


	var skjema = Ext.create('Ext.form.Panel', {
		title:	'Leieavtale i leieforhold <?php echo $leieforhold;?>: <?php echo $leieforhold->hent("beskrivelse");?>',
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
			leietakere,
			{
				xtype: 'fieldset',
				title: 'Leieavtalens varighet',
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
							},
							{
								xtype: 'container',
								flex: 1,
								padding: '0 5',
								items: [
									dato
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
						xtype: 'displayfield'
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
				xtype: 'displayfield'
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
		lagreknapp]
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

	fordelLeiaPåTerminer();

	Ext.Msg.alert("Husk:", "Leieavtalen må skrives ut og signeres dersom den endres.");
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
	
	case "innehavere":
		$leieforhold	= $this->leieforhold( (int)@$_GET['id'], true );
		$leietakere		= $leieforhold->hent('leietakere');
		$slettedeLeietakere		= $leieforhold->hent('slettedeLeietakere');

		$html = "<div>";
		
		foreach( $leietakere as $person ) {
			$html .= "<strong>{$person->hent('navn')}</strong>";
			
			if( $person->hent('fødselsnummer') ) {
				$html .= " f.nr.&nbsp;{$person->hent('fødselsnummer')}";
			}
			else if( $person->hent('fødselsdato') ) {
				$html .= " f.&nbsp;{$person->hent('fødselsdato')->format('d.m.Y')}";
			}
			$html .= " | <a style=\"cursor: pointer\" onClick=\"slettLeietaker({$person}, '{$person->hent('navn')}');\">Stryk fra leieavtalen</a><br />";
		}

		$html .= "</div><div>";
		
		foreach( $slettedeLeietakere as $person ) {
			$html .= "<del>{$person->hent('navn')}";
			
			if( $person->hent('fødselsnummer') ) {
				$html .= " f.nr.&nbsp;{$person->hent('fødselsnummer')}";
			}
			else if( $person->hent('fødselsdato') ) {
				$html .= " f.&nbsp;{$person->hent('fødselsdato')->format('d.m.Y')}";
			}
			$html .= "</del> | Strøket " . date('d.m.Y', strtotime( $person->slettet)) . "<br />";
		}

		$html .= "</div>";
		
		$html .= "<a href=\"index.php?oppslag=kontrakt_nyeleietakere&id={$leieforhold->hent('kontraktnr')}\">Legg til ny leietaker</a><br />";
		return $html;
		break;

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
	$leieobjekt		= $leieforhold->hent('leieobjekt');
	$kontrakter		= $leieforhold->hent('kontrakter');
	$sisteKontrakt	= reset($kontrakter);
	$tildato		= @$_POST['tildato'] ? new DateTime($_POST['tildato']) : null;
	$oppsigelsestid	= (int)$_POST['oppsigelsestid'];
	$antallTerminer	= (int)$_POST['ant_terminer'];
	$basisleie		= $_POST['leiebeløp'];
	$årsleie		= $_POST['leiebeløp'];
	
	// Sjekk at leieforholdet ikke er oppsagt
	if( $leieforhold->hent('oppsigelse') ) {
		
		echo json_encode(array(
			'success'	=>	false,
			'msg'		=> "Kan ikke endre leieforholdet fordi det er oppsagt."
		));
		return;
	}
	
	// Sjekk at til-dato ikke er tidligere enn siste leieavtale
	if( $tildato and ($tildato->format('Y-m-d') <= $sisteKontrakt->dato->format('Y-m-d') )) {
		echo json_encode(array(
			'success'	=>	false,
			'msg'		=> "Leieavtalen kan ikke avsluttes tidligere enn siste leieavtale ble påbegynt."
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
	
	$resultat->success = $leieforhold->sett('tildato', $tildato);
	$resultat->success &= $leieforhold->sett('oppsigelsestid', $oppsigelsestid);
	
	// Slett gjeldende delkravtyper og tillegg før de opprettes på nytt
	$resultat->success &= $leieforhold->sett('delkravtyper', null);
	$resultat->success &= $leieforhold->sett('tillegg', null);

	// Opprett delkravtyper	
	foreach($delkravtyper as $delkravtype) {
		$leieforhold->leggTilDelkravtype(
			$delkravtype->id,
			$delkravtype->relativ,
			$delkravtype->sats,
			false
		);
	}
		
	// Opprett tillegg
	foreach($selvstendigeTillegg as $delkravtype) {
		$leieforhold->leggTilDelkravtype(
			$delkravtype->id,
			$delkravtype->relativ,
			$delkravtype->sats,
			true
		);
	}
	
	$resultat->success &= $leieforhold->sett('ant_terminer', $antallTerminer);
	$resultat->success &= $leieforhold->sett('årlig_basisleie', $basisleie);
		
	// Så opprettes husleiekrav for denne leieavtalen.
	$leie = $leieforhold->opprettLeiekrav( date_create(), false );
	
	if( !$leie ) {
		$resultat->msg .= "<br />!!! OBS!!!<br />Det oppstod problemer med å opprette terminforfall for leieperioden.<br />";
	}
	else {
		$resultat->msg .= "<br />Det er opprettet terminer med forfallsdatoer.<br /><br />";
		
		foreach($leie as $krav) {
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
	
	echo json_encode($resultat);
	return;
}



function manipuler($data = "") {
	$tp = $this->mysqli->table_prefix;
	switch($data) {
	
	case "strykleietaker":
		$leieforhold = $this->leieforhold( (int)@$_GET['id'], true );
		$person		= $this->hent('Person', (int)@$_POST['personid'] );
		
		if( $this->mysqli->arrayData(array(
			'source'	=> "{$tp}kontraktpersoner",
			'where'		=> "kontrakt = '{$leieforhold->hent('kontraktnr')}'"
		))->totalRows < 2) {
			echo json_encode(array(
				'success'	=> false,
				'msg'		=> "Kan ikke stryke eneste personen i leieavtalen."
			));
			return;
		}

		echo json_encode( $this->mysqli->saveToDb( array(
			'update'	=> true,
			'table'		=> "{$tp}kontraktpersoner",
			'where'		=> "slettet is null and kontrakt = '{$leieforhold->hent('kontraktnr')}' and person = '{$person}'",
			'fields'	=> array(
				'slettet' => date('Y-m-d')
			)
		) ) );
		return;
		break;		


	case "frys":
		$leieforhold	= isset( $_GET['id'] )
						? $this->leieforhold($_GET['id'])
						: 0;
						
		$resultat = $this->mysqli->saveToDb(array(
			'update'	=>true,
			'table'		=> "kontrakter",
			'where'		=> "leieforhold = '{$leieforhold}'",
			'fields'	=> array(
				'frosset'	=> 1
			)
		));
		
		echo json_encode($resultat);
		break;


	case "tin":
		$leieforhold	= isset( $_GET['id'] )
						? $this->leieforhold($_GET['id'])
						: 0;
						
		$resultat = $this->mysqli->saveToDb(array(
			'update'	=>true,
			'table'		=> "kontrakter",
			'where'		=> "leieforhold = '{$leieforhold}'",
			'fields'	=> array(
				'frosset'	=> 0
			)
		));
		
		echo json_encode($resultat);
		break;
	}
}



}
?>