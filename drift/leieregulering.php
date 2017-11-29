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
}

function skript() {
	$tp = $this->mysqli->table_prefix;

	if(@$_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	
	// GET-verdien utført angir om leiereguleringa er utført

	if(@$_GET['utført'] == '1' and $this->brukPolett($_POST['polett'])) {

		$ikrafttreden	= date_create_from_format('d.m.Y', $_POST['ikrafttreden']);
		$justeringsbeløp	= (int)@$_POST['justeringsbeløp'];
		$prosentsats	= str_replace(",", ".", @$_POST['prosentsats']);
		$epostvarsling	= (bool)@$_POST['epostvarsling'];
		$arkiveres		= (bool)@$_POST['arkiveres'];

		$variabler = array(
			"{kontraktnr}",
			"{leieforhold}",
			"{fast KID}",
			"{leieobjektbeskrivelse}",
			"{dato}",
			"{virkning fra dato}",
			"{utleier}",
			"{utleieradresse}",
			"{leietaker}",
			"{leietakeradresse}",
			"{justering}",
			"{gammel bruttoleie}",
			"{gammel årsleie}",
			"{ny nettoleie}",
			"{ny bruttoleie}",
			"{terminlengde}"
		);

		$html = "Resultat av leiereguleringa:<br />";
		
		// Gjennomfør kun dersom Justeringsbeløp, prosentsats og dato er angitt
		if(
			$ikrafttreden > date_create()
			and ( $justeringsbeløp xor $prosentsats )
		) {
		
			$kontrakter = array();
			if(@$_POST['kontrakter']) {
				$kontrakter = explode(",", $_POST['kontrakter']);
			}
			foreach($kontrakter as $kontraktnr) {
				$leieforhold = $this->leieforhold($kontraktnr, true);
				$leieobjekt = $leieforhold->hent('leieobjekt');
				$antTerminer = $leieforhold->hent('ant_terminer');
				$gmlNettoÅrsleie = $leieforhold->hent('årlig_basisleie');
				$gmlBruttoTerminleie = $leieforhold->hent('leiebeløp');
				$terminlengde = $this->periodeformat( $leieforhold->hent('terminlengde') );
				$gmlBruttoÅrsleie = $gmlBruttoTerminleie * $antTerminer;


				if(!$justeringsbeløp) {
					$justeringsbeløp = round( $gmlNettoÅrsleie * $prosentsats/100 );
				}

				$nyNettoårsleie = $gmlNettoÅrsleie + $justeringsbeløp;
				$nyBruttoårsleie = $gmlBruttoÅrsleie + $justeringsbeløp;

				if($leieforhold->sett("årlig_basisleie", $nyNettoårsleie)) {

					$erstatningstekst = array(
						$kontraktnr,										// kontraktnr
						strval( $leieforhold ),								// leieforhold
						$this->genererKid($leieforhold),					// fast KID
						$leieobjekt->hent('beskrivelse'),
						date('d.m.Y'),										// dato
						$ikrafttreden->format('d.m.Y'),						// virkning fra dato
						$this->valg['utleier'],								// utleier
						(
							"{$this->valg['adresse']}<br />"
						.	"{$this->valg['postnr']} {$this->valg['poststed']}<br />"
						.	"org. nr. {$this->valg['orgnr']}"
						),													// utleieradresse
						$leieforhold->hent('navn'),							// leietaker(e)
						nl2br($leieforhold->hent('navn') . "\n" . $leieforhold->hent('adressefelt')),			// leietakeradresse
						(
							$prosentsats
							? "{$this->prosent( $prosentsats/100 )}"
							: "{$this->kr( $justeringsbeløp )} per år"
						),													// justering
						$this->kr( $gmlBruttoTerminleie ),					// Oppr brutto terminleie
						$this->kr( $gmlBruttoTerminleie * $antTerminer ),	// Opprinnelig brutto årsleie
						$this->kr( round($nyNettoårsleie / $antTerminer) ),	// Ny netto terminleie
						$this->kr( round($nyBruttoårsleie / $antTerminer) ),// Ny brutto terminleie
						$terminlengde										// terminlengde
					);
					
					$varseltekst = str_replace($variabler, $erstatningstekst, $this->valg['leiereguleringsbrevmal']);

					if(
						$epostvarsling and (
							$adressefelt = $leieforhold->hent('brukerepost')
						)
					) {
						$this->sendMail(array(
							'to' => implode(',', $adressefelt),
							'subject' => "Regulering av leie",
							'html' => $varseltekst,
							'testcopy' => true
						));
					}
					if($arkiveres) {
						$this->mysqli->saveToDb(array(
							'insert'	=> true,
							'table'		=>	"{$tp}notater",
							'fields'	=> array(
								'leieforhold'		=> $leieforhold,
								'dato'				=> date('Y-m-d'),
								'henvendelse_fra'	=> 'fra utleier',
								'kategori'			=> 'brev',
								'brevtekst'			=> $varseltekst,
								'registrert'		=> date('Y-m-d H:i:s'),
								'registrerer'		=> $this->bruker['navn']
							)
						));
					}
					$leieforhold->opprettLeiekrav( $ikrafttreden );
					$html .= "Leieforhold {$leieforhold} ({$leieforhold->hent('navn')}): Leiebeløpet endret fra {$this->kr($gmlBruttoTerminleie)} til {$this->kr($leieforhold->hent('leiebeløp'))}.<br />";
				}
			}
		}
		
		else {
			$html .= "Mislyktes: Justeringsbeløp / prosentsats, eller dato var ikke angitt";
		}
		
		$this->sendMail(array(
			'to' => $this->valg['epost'],
			'subject' => "Resultat fra leieregulering",
			'priority'	=> 80,
			'html' => $html,
			'from' => "{$this->valg['utleier']}<{$this->valg['epost']}>",
			'testcopy' => true
		));
?>

Ext.onReady(function() {
<?
		include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	var hovedpanel = new Ext.form.FormPanel({
		autoWidth: false,
		border: false,
		height: 500,
		width: 900,
		buttons: [{
			text: 'Avslutt',
			handler: function(){
				window.location = "index.php";
			}
		}],
		html: "<?=$html;?>"
	});


	hovedpanel.render('panel');

});
<?
		return;
	}

	// Slutt på behandling av utført regulering

	// Her begynner skjemaet for å gjøre ei ny regulering:	
	
	$terskeldato = new DateTime;
	$terskeldato->sub( new DateInterval('P10M') );
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	var ikrafttreden = new Ext.form.DateField({
		disabled: false,
		fieldLabel: 'Justering fra',
		format: 'd.m.Y',
		name: 'ikrafttreden',
		value: '<?=date('01.m.Y', $this->leggtilIntervall(time(), 'P2M'))?>',
		width: 90
	});

	// oppretter datasettet
	var datasett = new Ext.data.JsonStore({
		url: 'index.php?oppdrag=hentdata&oppslag=leieregulering',
		fields: [
			{name: 'kontraktnr', type: 'float'},
			{name: 'leieforhold', type: 'float'},
			{name: 'leieobjekt', type: 'float'},
			{name: 'leiebeløp', type: 'float'},
			{name: 'fom', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'fradato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'kontraktbeskrivelse'},
			{name: 'leieobjektbeskrivelse'}
		],
		idIndex: 0,
		root: 'data'
    });
	datasett.load({params: {'ikrafttreden': Date.parseDate(ikrafttreden.value, "d.m.Y").format("Y-m-d")}});
	ikrafttreden.addListener('change', function(){
		datasett.load({params: {'ikrafttreden': Date.parseDate(ikrafttreden.value, "d.m.Y").format("Y-m-d")}});
	});

	var kontraktnr = {
		dataIndex: 'kontraktnr',
		header: 'Leieavtale',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return value + ': ' + record.data.kontraktbeskrivelse;
		},
		sortable: true,
		width: 250
	};

	var leieobjekt = {
		dataIndex: 'leieobjekt',
		header: 'Leil',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return record.data.leieobjektbeskrivelse;
		},
		sortable: true,
		width: 250
	};

	var leiebeløp = {
		dataIndex: 'leiebeløp',
		header: 'Netto leie',
		renderer: Ext.util.Format.noMoney,
		sortable: true,
		width: 60
	};


	var fom = {
		dataIndex: 'fom',
		header: 'Leia justert',
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		sortable: true,
		width: 70
	};


	var fradato = {
		dataIndex: 'fradato',
		header: 'Avt. fornyet',
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		sortable: true,
		width: 70
	};

	var sm = new Ext.grid.CheckboxSelectionModel({
		checkOnly: true
	});

	var rutenett = new Ext.grid.GridPanel({
		store: datasett,
		columns: [
			sm,
			kontraktnr,
			leieobjekt,
			leiebeløp,
			fom,
			fradato
		],
		stripeRows: true,
		sm: sm,
		height: 410,
		width: 900,
		region: 'center',
		title: ''
	});

	var justeringsbeløp = new Ext.form.NumberField({
		allowBlank: true,
		allowDecimals: true,
		allowNegative: true,
		decimalSeparator: ',',
		decimalPrecision: 2,
		disabled: false,
		fieldLabel: 'Justering i kr per år',
		listeners: {
			'change': function(){
				prosentsats.reset()
			}
		},
		name: 'justeringsbeløp',
		validator: function(value){
			if(prosentsats.value || value) return true;
			else return "Du må oppgi enten et beløp eller en prosentsats som leien skalreguleres med.";
		},
		width: 90
	});

	var prosentsats = new Ext.form.NumberField({
		allowBlank: true,
		allowDecimals: true,
		allowNegative: true,
		decimalSeparator: ',',
		decimalPrecision: 3,
		disabled: false,
		fieldLabel: 'Justering i %',
		listeners: {
			'change': function(){
				justeringsbeløp.reset()
			}
		},
		name: 'prosentsats',
		validator: function(value){
			if(justeringsbeløp.value || value) return true;
			else return "Du må oppgi enten et beløp eller en prosentsats som leien skalreguleres med.";
		},
		width: 90
	});

	var terskeldato = new Ext.form.DateField({
		disabled: false,
		fieldLabel: 'Merk alle som har hatt samme leie siden',
		format: 'd.m.Y',
		name: 'terskeldato',
		value: '<?php echo $terskeldato->format('01.m.Y');?>',
		width: 90
	});

	var fornyelsesforbehold = new Ext.form.Checkbox({
		boxLabel: 'Utelat avtaler som er fornyet senere enn denne datoen',
		hideLabel: true,
		checked: false,
		name: 'fornyelsesforbehold',
		inputValue: 1
	});

	var minimumsbeløp = new Ext.form.NumberField({
		allowBlank: true,
		allowDecimals: true,
		allowNegative: false,
		decimalSeparator: ',',
		decimalPrecision: 2,
		disabled: false,
		fieldLabel: 'Ikke merk de med månedlig leie lavere enn kr',
		listeners: {
			'change': function(){
				prosentsats.reset()
			}
		},
		name: 'minimumsbeløp',
		width: 40
	});

	var maksimumsbeløp = new Ext.form.NumberField({
		allowBlank: true,
		allowDecimals: true,
		allowNegative: false,
		decimalSeparator: ',',
		decimalPrecision: 2,
		disabled: false,
		fieldLabel: 'Ikke merk de med månedlig leie høyere enn kr',
		listeners: {
			'change': function(){
				prosentsats.reset()
			}
		},
		name: 'maksimumsbeløp',
		width: 40
	});


	var merkeknapp = new Ext.Button({
		text: 'Merk',
		handler: function(){
			sm.clearSelections();
			for(i=0; i<datasett.getCount(); i++){
				var record = datasett.getAt(i);
				if((!terskeldato.getValue() || (!fornyelsesforbehold.getValue() && record.get('fom') <= terskeldato.getValue()) || (record.get('fom') <= terskeldato.getValue() && record.get('fradato') <= terskeldato.getValue())) && (!minimumsbeløp.getValue() || minimumsbeløp.getValue() <= record.get('leiebeløp')) && (!maksimumsbeløp.getValue() || maksimumsbeløp.getValue() >= record.get('leiebeløp'))){
					sm.selectRecords([record], true);
				}
			}
		}
	});


	var merkekriterier = new Ext.form.FieldSet({
		layout: 'column',
		title: 'Markeringsvalg',
		items: [{
			columnWidth: 0.5,
			border: false,
			layout: 'form',
			labelAlign: 'top',
			items: [terskeldato, fornyelsesforbehold]
		},{
			columnWidth: 0.5,
			labelAlign: 'right',
			labelWidth: 150,
			border: false,
			layout: 'form',
			items: [minimumsbeløp, maksimumsbeløp, merkeknapp]
		}]
	});

	var brevvinduknapp = new Ext.Button({
		text: 'Rediger varselmal',
		handler: function(){
			brevvindu.show();
			brevskjema.getForm().load({
				url: 'index.php?oppslag=leieregulering&oppdrag=hentdata&data=brevmal'
			});
		}
	});

	var epostvarsling = new Ext.form.Checkbox({
		boxLabel: 'send epostvarsler i tillegg',
		hideLabel: true,
		checked: true,
		name: 'epostvarsling',
		inputValue: 1
	});

	var arkiveres = new Ext.form.Checkbox({
		boxLabel: 'arkiver varselet blant oppfølgingsnotatene',
		hideLabel: true,
		checked: true,
		name: 'arkiveres',
		inputValue: 1
	});

	var kontrakter = new Ext.form.Hidden({
		name: 'kontrakter',
		value: ''
	});

	var polett = new Ext.form.Hidden({
		name: 'polett',
		value: '<?=$this->opprettPolett();?>'
	});


	var utskriftsknapp = new Ext.Button({
		text: 'Skriv ut varsler',
		handler: function(){
			if(sm.getCount() < 1) {
				Ext.MessageBox.alert("Ingen leieavtaler er markert", "Du må markere ett eller flere leieforhold som skal reguleres.");
			}
			else{
				var records = sm.getSelections();
				var data = [];
				Ext.each(records, function(r) {
					data.push(r.get('kontraktnr'));
				});
	
				kontrakter.setRawValue(data.join(','));
				hovedpanel.getForm().getEl().dom.action = 'index.php?oppslag=leieregulering&oppdrag=utskrift';
				hovedpanel.getForm().getEl().dom.target = '_blank';
				sm.lock();
				gjennomfør.enable();
				hovedpanel.getForm().submit();
			}
		}
	});

	var gjennomfør = new Ext.Button({
		disabled: true,
		text: 'Utfør reguleringa',
		handler: function(){

			var records = sm.getSelections();
			var data = [];
			Ext.each(records, function(r) {
				data.push(r.get('kontraktnr'));
			});

			kontrakter.setRawValue(data.join(','));
			hovedpanel.getForm().getEl().dom.action = 'index.php?oppslag=leieregulering&utført=1';
			hovedpanel.getForm().getEl().dom.target = '_self';
			hovedpanel.getForm().submit();
		}
	});

	var leiereguleringsbrevmal = new Ext.form.HtmlEditor({
			name: 'leiereguleringsbrevmal',
			anchor: '100% 100%',
			hideLabel: true
		});

	var brevskjema = new Ext.form.FormPanel({
		autoScroll: false,
		region: 'center',
		reader: new Ext.data.JsonReader({
			defaultType: 'textfield',
			fields: [leiereguleringsbrevmal],
			root: 'data'
		}),
		items: [leiereguleringsbrevmal],
		buttons: [
			{
				text: 'Avbryt',
				handler: function(){
					brevvindu.hide();
				}
			},
			{
				text: 'Lagre',
				handler: function(){
					brevskjema.form.submit({
						url:'index.php?oppslag=leieregulering&oppdrag=taimotskjema&skjema=leiereguleringsbrevmal',
						waitMsg:'Prøver å lagre...',
						success: function(){
							brevvindu.hide();
						}
					});
				}
			}, {
				text: 'Lagre og skriv ut varsler',
				handler: function(){
					brevskjema.form.submit({
						url:'index.php?oppslag=leieregulering&oppdrag=taimotskjema&skjema=leiereguleringsbrevmal',
						waitMsg:'Prøver å lagre...',
						success: function(){
							brevvindu.hide();
						}
					});
					if(sm.getCount() < 1) {
						Ext.MessageBox.alert("Ingen leieavtaler er markert", "Du må markere ett eller flere leieforhold som skal reguleres.");
					}
					else{
						var records = sm.getSelections();
						var data = [];
						Ext.each(records, function(r) {
							data.push(r.get('kontraktnr'));
						});
			
						kontrakter.setRawValue(data.join(','));
						hovedpanel.getForm().getEl().dom.action = 'index.php?oppslag=leieregulering&oppdrag=utskrift';
						hovedpanel.getForm().getEl().dom.target = '_blank';
						sm.lock();
						hovedpanel.getForm().submit({
							success: function(){
								gjennomfør.enable();
							}
						});
//						gjennomfør.enable();
					}
				}
			}
		]
	});
	
	var brevvindu = new Ext.Window({
		layout: 'fit',
		modal: true,
		width: 850,
		height: 450,
		closeAction: 'hide',
		plain: true,
		items: new Ext.Panel({
			layout: 'border',
			items: [
				{
					region: 'east',
					autoScroll: true,
					collapsible: true,
					width: 200,
					title: '?',
					html: "Rediger teksten som skal brukes som mal for varslene.<br>Du må skrive ut disse varslene, og se over at utskriften er tilfredsstillende, før du utfører selve leiereguleringen.<br>Variabler settes inn i teksten i {krøllklammer}. Disse vil bli erstattet med faktiske verdier ved utskrift.<br><br>Du kan bruke følgende variabler i teksten:<br><ul><li><b>{kontraktnr}</b> = Leieavtalens nummer.</li><li><b>{leieforhold}</b> = Leieforholdnummeret</li><li><b>{fast KID}</b> = Fast KIDnummer for innbetalinger til dette leieforholdet.</li><li><b>{leieobjektbeskrivelse}</b> = Leieobjektets adresse ogbeskrivelse.</li><li><b>{dato}</b> = Dagens dato (dvs. utskriftsdato)</li><li><b>{virkning fra dato}</b> = Datoen da leiereguleringen trer i kraft.</li><li><b>{utleier}</b> = <?=$this->valg['utleier']?>.</li><li><b>{utleieradresse}</b> = <?=$this->valg['utleier']?>s adresse.</li><li><b>{leietaker}</b> = Leietakeren(e)s navn.</li><li><b>{leietakeradresse}</b> = Adressen varselet skal sendes til.</li><li><b>{justering}</b> = Beskrivelse av justeringen som skal skje i beløp eller prosent.</li><li><b>{gammel bruttoleie}</b> = Nåværende leie per leietermin, inklusive alle delkrav.</li><li><b>{gammel årsleie}</b> = Nåværende leie per år, inklusive alle delkrav.</li><li><b>{ny nettoleie}</b> = Den nye basisleia per leietermin, dvs. utenom alle delkrav.</li><li><b>{ny bruttoleie}</b> = Den nye leia per leietermin, inklusive alle delkrav.</li><li><b>{terminlengde}</b> = Lengden på en leietermin, normalt 'en måned'.</li></ul>"
				},
				brevskjema
			]
		})
	});
	

	var configområde = new Ext.Panel({
		autoWidth: false,
		border: false,
		bodyStyle: 'padding: 5px',
		region: 'north',
		layout: 'column',
		height: 150,
		width: 900,
		items: [{
			columnWidth: 0.20,
			border: false,
			layout: 'form',
			labelAlign: 'right',
			labelWidth: 80,
			items: [ikrafttreden, justeringsbeløp, prosentsats]
		},{
			columnWidth: 0.60,
			border: false,
			layout: 'form',
			labelAlign: 'top',
			items: [merkekriterier]
		},{
			columnWidth: 0.20,
			labelAlign: 'right',
			labelWidth: 60,
			border: false,
			layout: 'form',
			items: [polett, kontrakter, brevvinduknapp, utskriftsknapp, epostvarsling, arkiveres, gjennomfør]
		}]
	});


	var hovedpanel = new Ext.form.FormPanel({
		autoWidth: false,
		standardSubmit: true,
		border: false,
		layout: 'border',
		height: 500,
		width: 900,
		items: [configområde, rutenett]
	});


	// Rutenettet rendres in i HTML-merket '<div id="panel">':
	hovedpanel.render('panel');

});
<?
}

function design() {
?>
<div id="panel"></div>
<?
}

function utskrift() {
	$tp = $this->mysqli->table_prefix;

	$ikrafttreden	= date_create_from_format('d.m.Y', $_POST['ikrafttreden']);
	$justeringsbeløp	= (int)@$_POST['justeringsbeløp'];
	$prosentsats	= str_replace(",", ".", @$_POST['prosentsats']);
	$epostvarsling	= (bool)@$_POST['epostvarsling'];
	$arkiveres		= (bool)@$_POST['arkiveres'];

	$variabler = array(
		"{kontraktnr}",
		"{leieforhold}",
		"{fast KID}",
		"{leieobjektbeskrivelse}",
		"{dato}",
		"{virkning fra dato}",
		"{utleier}",
		"{utleieradresse}",
		"{leietaker}",
		"{leietakeradresse}",
		"{justering}",
		"{gammel bruttoleie}",
		"{gammel årsleie}",
		"{ny nettoleie}",
		"{ny bruttoleie}",
		"{terminlengde}"
	);

	$kontrakter = array();
	if(@$_POST['kontrakter']) {
		$kontrakter = explode(",", $_POST['kontrakter']);
	}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:ext="http://www.extjs.com" xml:lang="no" lang="no">

<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Leieregulering</title>
	<link rel="stylesheet" type="text/css" href="/leiebase.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="/<?=$this->ext_bibliotek?>/resources/css/ext-all.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="/<?=$this->ext_bibliotek?>/resources/css/xtheme-slate.css" media="screen" />
	<script language="JavaScript" type="text/javascript" src="/<?=$this->ext_bibliotek?>/adapter/ext/ext-base.js"></script>

	<script language="JavaScript" type="text/javascript" src="/<?=$this->ext_bibliotek?>/ext-all.js"></script>
	<script language="JavaScript" type="text/javascript" src="/<?=$this->ext_bibliotek?>/src/locale/ext-lang-no_NB.js"></script>
	<script language="JavaScript" type="text/javascript" src="/fellesfunksjoner.js"></script>
	<script language="JavaScript" type="text/javascript">
	
	
	</script>
</head>

<body>
<?
	foreach($kontrakter as $kontraktnr) {
		$leieforhold = $this->leieforhold($kontraktnr, true);
		$leieobjekt = $leieforhold->hent('leieobjekt');
		$antTerminer = $leieforhold->hent('ant_terminer');
		$gmlNettoÅrsleie = $leieforhold->hent('årlig_basisleie');
		$gmlBruttoTerminleie = $leieforhold->hent('leiebeløp');
		$terminlengde = $this->periodeformat( $leieforhold->hent('terminlengde') );
		$gmlBruttoÅrsleie = $gmlBruttoTerminleie * $antTerminer;

		if(!$justeringsbeløp) {
			$justeringsbeløp = round( $gmlNettoÅrsleie * $prosentsats/100 );
		}

		$nyNettoårsleie = $gmlNettoÅrsleie + $justeringsbeløp;
		$nyBruttoårsleie = $gmlBruttoÅrsleie + $justeringsbeløp;

		$erstatningstekst = array(
			$kontraktnr,										// kontraktnr
			strval( $leieforhold ),								// leieforhold
			$this->genererKid($leieforhold),					// fast KID
			$leieobjekt->hent('beskrivelse'),
			date('d.m.Y'),										// dato
			$ikrafttreden->format('d.m.Y'),						// virkning fra dato
			$this->valg['utleier'],								// utleier
			(
				"{$this->valg['adresse']}<br />"
			.	"{$this->valg['postnr']} {$this->valg['poststed']}<br />"
			.	"org. nr. {$this->valg['orgnr']}"
			),													// utleieradresse
			$leieforhold->hent('navn'),							// leietaker(e)
			nl2br($leieforhold->hent('navn') . "\n" . $leieforhold->hent('adressefelt')),			// leietakeradresse
			(
				$prosentsats
				? "{$this->prosent( $prosentsats/100 )}"
				: "{$this->kr( $justeringsbeløp )} per år"
			),													// justering
			$this->kr( $gmlBruttoTerminleie ),					// Oppr brutto terminleie
			$this->kr( $gmlBruttoTerminleie * $antTerminer ),	// Opprinnelig brutto årsleie
			$this->kr( round($nyNettoårsleie / $antTerminer) ),	// Ny netto terminleie
			$this->kr( round($nyBruttoårsleie / $antTerminer) ),// Ny brutto terminleie
			$terminlengde										// terminlengde
		);
		
		$varseltekst = str_replace($variabler, $erstatningstekst, $this->valg['leiereguleringsbrevmal']);

		echo "$varseltekst\n";
		echo "<DIV style=\"page-break-after: always;\"></DIV>\n";
	}
?>
<script type="text/javascript">
	window.print();
</script>
</body>
</html>
<?
}


function hentData($data = "") {
	$tp = $this->mysqli->table_prefix;
	

	switch ($data) {

	case 'brevmal':
		return json_encode(array(
			'success'	=> true,
			'data'		=> array(array(
				'leiereguleringsbrevmal'	=> $this->valg['leiereguleringsbrevmal']
			))
		));
		break;

	default:
		$ikrafttreden = new DateTime( @$_POST['ikrafttreden'] );
		$resultat = (object)array(
			'success'	=> true,
			'data'		=> array()
		);

		foreach( $this->mysqli->arrayData(array(
			'source'	=> "{$tp}kontrakter AS kontrakter\n"
						.	"LEFT JOIN {$tp}oppsigelser AS oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold\n"
						.	"LEFT JOIN {$tp}utdelingsorden AS utdelingsorden ON kontrakter.regningsobjekt = utdelingsorden.leieobjekt AND rute = '{$this->valg['utdelingsrute']}'\n",
						
			'fields'	=> "kontrakter.leieforhold AS id\n",
			'class'		=> "Leieforhold",
			'distinct'	=> true,
			'fields'	=> "kontrakter.leieforhold AS id\n",
			'orderfields'	=> "kontrakter.regning_til_objekt, utdelingsorden.plassering\n",
			'where'		=> "kontrakter.fradato < '{$ikrafttreden->format('Y-m-d')}'\n"
						.	"AND (fristillelsesdato > '{$ikrafttreden->format('Y-m-d')}' OR fristillelsesdato IS NULL)\n"
		))->data as $leieforhold) {
			$sisteJustering = $leieforhold->hentSisteLeiejustering( $ikrafttreden );
			$leieobjekt = $leieforhold->hent('leieobjekt');
			$kontrakter = $leieforhold->hent('kontrakter');
			$kontrakt = reset($kontrakter);
			
			$resultat->data[] = (object)array(
				'leieforhold'			=> "{$leieforhold}",
				'kontraktnr'			=> "{$leieforhold->hent('kontraktnr')}",
				'leieobjekt'			=> "{$leieobjekt}",
				'leiebeløp'				=> "{$sisteJustering->beløp}",
				'fom'					=> "{$sisteJustering->dato->format('Y-m-d')}",
				'fradato'				=> $kontrakt->dato->format('Y-m-d'),
				'kontraktbeskrivelse'	=> "{$leieforhold->hent('navn')}",
				'leieobjektbeskrivelse'	=> "{$leieobjekt->hent('navn')}"
			);
		}
	
		return json_encode($resultat);
		break;
	}
}


function taimotSkjema($skjema) {
	$tp = $this->mysqli->table_prefix;

	switch ($skjema) {
		case 'leiereguleringsbrevmal':
			if($_POST['leiereguleringsbrevmal']) $sql = "UPDATE valg SET verdi = '{$this->POST['leiereguleringsbrevmal']}' WHERE innstilling = 'leiereguleringsbrevmal'";
			if($resultat['success'] = $this->mysqli->query($sql))
				$resultat['msg'] = "";
			else
				$resultat['msg'] = "KLarte ikke å lagre. Feilmeldingen fra databasen lyder:<br />" . $this->mysqli->error;
			echo json_encode($resultat);
			return;
	}
}

}
?>