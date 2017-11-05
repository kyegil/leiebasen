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
	$tp = $this->mysqli->table_prefix;
	$utskriftsforsøk = (bool)$this->valg['utskriftsforsøk'];
?>
Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?php echo $this->http_host . "/" . $this->ext_bibliotek . "/examples/ux";?>');

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
	
<?php
	include_once("_menyskript.php");

	$kravsett = array();

	foreach ( $_POST as $angivelse => $tattmed ) {
		if ( (int)$angivelse > 0 and $tattmed ) {
			$kravsett[] = $angivelse;
		}
	}
	
	$ubetalte = $this->mysqli->arrayData(array(
		'source'	=> "{$tp}krav AS krav\n"
					.	"LEFT JOIN {$tp}kontrakter AS kontrakter on krav.kontraktnr = kontrakter.kontraktnr",		
		'class'		=> "Krav",
		'fields'	=> "krav.id\n",
		'where'		=> "krav.utestående > 0 and krav.utskriftsdato IS NOT NULL and krav.forfall < NOW() AND !kontrakter.frosset"
					.	(isset($_GET['leieforhold']) ? " AND leieforhold = '{$this->GET['leieforhold']}'" : ""),
		'orderfields'	=> "krav.forfall ASC, krav.utskriftsdato ASC, krav.gironr ASC"
	));
	
	$liste = array();
	foreach( $ubetalte->data as $krav ) {
		$leieforhold = $krav->hent('leieforhold');
		$liste [$leieforhold->hentId()] ['purres'] = false;
			
		$giro = $krav->hent('giro');
		
		// Leieforholdet foreslåes purret dersom minst én av giroene
		// er moden for det.
		$sisteForfall = $giro->hent('sisteForfall');

		if($sisteForfall) {
			$sisteForfall = clone $giro->hent('sisteForfall');

			if( $sisteForfall->add(
				new DateInterval( 
					$this->valg['purreintervall'] 
				) 
			) < date_create() ) {
				$liste [$leieforhold->hentId()] ['purres'] = true;
			}
		}
		

		$liste
			[$leieforhold->hentId()]
			['giroer']
			[$giro->hentId()]
			[$krav->hentId()] = $krav;

		$liste
			[$leieforhold->hentId()]
			['leieobjekt'] = $leieforhold->hent('leieobjekt');
			 
		$liste
			[$leieforhold->hentId()]
			['frosset'] = $leieforhold->hent('frosset');
		
		if( $leieforhold->hent('avvent_oppfølging') > new DateTime ) {
			$liste
				[$leieforhold->hentId()]
				['oppfølgingsstopp'] = true;
			$liste
				[$leieforhold->hentId()]
				['oppfølgingsfrist'] = clone $leieforhold->hent('avvent_oppfølging');
		}
		else {
			$liste
				[$leieforhold->hentId()]
				['oppfølgingsstopp'] = $leieforhold->hent('stopp_oppfølging');
			$liste
				[$leieforhold->hentId()]
				['oppfølgingsfrist'] = null;
		}
	}
	
?>

forkastUtskrift = function() {
	Ext.MessageBox.wait('Utskriften forkastes', 'Vent litt...');
	Ext.Ajax.request({
	
		waitMsg: 'Sletter...',
		url: 'index.php?oppslag=utskriftsmeny&oppdrag=manipuler&data=forkast_utskrift',

		failure: function( response, options ) {
			Ext.MessageBox.alert('Whoops! Problemer...', 'Oppnår ikke kontakt med databasen! Prøv igjen senere.');
		},
		
		success: function(response, options) {
			var tilbakemelding = Ext.JSON.decode(response.responseText);
			if(tilbakemelding['success'] == true) {
				Ext.MessageBox.alert('Utført', tilbakemelding.msg, function() {
					window.onbeforeunload = null;
					window.location = "index.php";
				});
			}
			else {
				Ext.MessageBox.alert('Hmm..', tilbakemelding['msg']);
				
			}
		}
		
	});
}


registrerUtskrift = function() {
	Ext.MessageBox.wait('Utskriften registreres', 'Vent litt...');
	Ext.Ajax.request({
	
		waitMsg: 'Skriver...',
		url: 'index.php?oppslag=utskriftsmeny&oppdrag=taimotskjema&skjema=oppdatering',
		params: {},
		failure:function(response,options){
			Ext.MessageBox.alert('Whoops! Problemer...','Oppnår ikke kontakt med databasen! Prøv igjen senere.');
		},
		
		success: function(response,options) {
				var tilbakemelding = Ext.JSON.decode(response.responseText);
				if(tilbakemelding['success'] == true) {
					window.onbeforeunload = null;
					Ext.MessageBox.alert('Utført', tilbakemelding.msg, function() {
						Ext.Msg.buttonText.yes = "Skriv ut konvolutter";
						Ext.Msg.buttonText.no = "Nei, takk";
						Ext.MessageBox.confirm("Vil du skrive ut konvolutter?",
							"Før du skriver ut må du putte konvolutter i skriveren.<br />Justering av adressefeltet kan gjøres i innstillingene for leiebasen.",
							function(buttonId, text, opt) {
								if(buttonId == 'yes' && tilbakemelding['adresser']) {
									window.open( "index.php?oppslag=personadresser_utskrift&oppdrag=lagpdf&pdf=konvolutter&leieforhold=" + tilbakemelding['adresser'].join());
								}
								window.location = "index.php";
							}
						);
					});
				}
				else {
					Ext.MessageBox.alert('Hmm..',tilbakemelding['msg']);
					
				}
		}
	});
}


var avbryt = Ext.create('Ext.button.Button', {
	text: 'Avbryt',
	id: 'avbryt',
	handler: function() {
		window.location = '<?php echo $this->returi->get();?>';				
	}
});

var pdfKnapp = Ext.create('Ext.button.Button', {
	text: 'Opprett utskriftsfil (PDF)',
	id: 'pdf',
	handler: function() {
		framdriftsindikator.show({
			msg: 'Vent litt...',
			width: 300,
			wait: true,
			waitConfig: {
				interval: 1000, // interval * increment = tid i ms
				increment: 30,
				text : 'Setter sammen utskriftsfil'
			}
		});
		skjema.getForm().submit({
			url: 'index.php?oppslag=utskriftsmeny&oppdrag=manipuler&data=lag_giroer'
		});
	}
});

var bekreft = Ext.create('Ext.button.Button', {
	text: 'Jeg har sett over at utskriftene er OK. Lagre utskrifts- og forfallsdatoer',
	handler: registrerUtskrift,
	disabled: true
});

var framdriftsindikator = Ext.create('Ext.window.MessageBox', {
   width: 300
});


var skjema = Ext.create('Ext.form.Panel', {
	renderTo: 'panel',
	timeout: 180,
	standardSubmit: false,
	autoScroll: true,
	bodyPadding: 5,
	frame: true,
	width: 900,
	height: 500,
	buttons: [
		avbryt,
		pdfKnapp,
		bekreft
	],
	labelAlign: 'left', // evt top
	labelWidth: 150,
	title: 'Velg hvilke giroer/krav som skal purres',
	items: [
		{
			xtype: 'checkbox',
			boxLabel: 'Send én felles purring for hvert leieforhold.',
			checked: true,
			fieldLabel: 'Kombinerte purringer',
			labelWidth: 150,
			name: 'kombipurring',
			inputValue: 1
		},

<?php
	foreach( $liste as $leieforholdnr => $leieforhold ):
?>

		{
			xtype: 'fieldset',
			collapsible: true,
			collapsed: <?php echo ($leieforhold['purres']  ? 'false' : 'true');?>,
			title: '<b><?php echo ( $leieforhold['oppfølgingsstopp'] ? "<span>" : "" ) . "Leieforhold {$leieforholdnr}: " . addslashes( $this->liste( $this->kontraktpersoner( $this->sistekontrakt( $leieforholdnr ) ) ) ) . ( $leieforhold['oppfølgingsstopp'] ? "</span> (oppfølgingsstopp" . ( $leieforhold['oppfølgingsfrist'] ? " til " . $leieforhold['oppfølgingsfrist']->format('d.m.Y') : "" ) . ")" : "" );?></b>',
			items: [
<?php
			if( $this->valg['purregebyr']):
?>
				{
					xtype: 'checkbox',
					fieldLabel: '',
					checked: <?php echo $leieforhold['oppfølgingsstopp'] ? "false" : "true";?>,
					boxLabel: 'Krev gebyr for de purringene som tilfredstiller kravene til det:',
					name: 'purregebyr:<?php echo $leieforholdnr;?>',
					inputValue: 1
				},
<?php
			endif;

			foreach( $leieforhold['giroer'] as $gironr => $girokrav ):
				$giro = new Giro( $gironr );

				if($giro->hent('antallPurringer')) {
					$purret = "Sist purret: <b>"
					. $giro
						->hent('sistePurring')
						->hent('purredato')
						->format('d.m.Y')
					. "</b>";
				}
				else {
					$purret = "Ikke tidligere purret";
				}
				// foreslåes purret hvis det har gått lang nok tid
				// siden siste purreforfall.
				$purr = false;
				$sisteForfall = $giro->hent('sisteForfall');

				if( $sisteForfall ) {
					$sisteForfall = clone $sisteForfall;

					$purr = $sisteForfall->add(
						new DateInterval( 
							$this->valg['purreintervall'] 
						) 
					) < date_create()
					? true
					: false;
				}
?>
				{
					xtype: 'checkbox',
					boxLabel: '<?php foreach( $girokrav as $krav ):?><?php echo addslashes($krav->hent('tekst'));?><br /><?php endforeach;?>',
					checked: <?php echo (!$leieforhold['oppfølgingsstopp'] and $purr) ? "true" : "false";?>,
					fieldLabel: 'Giro <?php echo $giro->hent('gironr');?>',
					name: 'purregiro:<?php echo $gironr;?>',
					inputValue: 1
				},
				{
					xtype: 'displayfield',
					fieldLabel: ' ',
					labelSeparator: '',
					value: '<?php echo "Forfalt: <b>{$giro->hent('forfall')->format('d.m.Y')}</b>. $purret. Utestående: <b>kr. " . number_format($giro->hent('utestående'), 2, ",", " ") . "</b>";?>'
				},					
<?php
			endforeach;
?>
				{
					xtype: 'checkbox',
					fieldLabel: 'Send oversikt',
					boxLabel: 'Send gebyrfri oversikt over alt utestående',
					checked: <?php echo $leieforhold['oppfølgingsstopp'] ? "true" : "false";?>,
					name: 'statusoversikt:<?php echo $leieforholdnr;?>',
					inputValue: 1
				}
			]
		},
<?php
	endforeach;
?>
<?php
	foreach($kravsett as $krav):
?>
		{
			xtype: 'hiddenfield',
			name: '<?php echo $krav;?>',
			value: 1
		},
<?php
	endforeach;
?>
		{
			xtype: 'hiddenfield',
			name: 'adskilt',
			value: '<?php echo ( isset($_POST['adskilt']) ? $_POST['adskilt'] : "" );?>'
		}
	]
});

skjema.on({
	actioncomplete: function(form, action) {
		if(action.type == 'submit') {
			if(action.response.responseText == '') {
				Ext.MessageBox.alert('Problem', 'Det kom en blank respons fra tjeneren, så det er uvisst om oppgaven var vellykket. Kan du ha mistet nettforbindelsen?');
			} else {
				framdriftsindikator.hide();
				window.open('index.php?oppslag=giro&oppdrag=lagpdf');
	
				bekreft.enable();
	
				window.onbeforeunload = function() {
					return 'Du bør ikke forlate utskriftsmenyen uten å bekrefte om utskriften skal registreres i leiebasen.';
				};
	
				Ext.Msg.buttonText.yes = "Utskriften er OK. Registrer utskrifts- og forfallsdatoer";
				Ext.Msg.buttonText.no = "Utskriften skal forkastes uten å registreres";
				Ext.Msg.show({
					title: 'Bekreft at utskriften er OK',
					msg: '<p>Vent til utskriften er fullført, og bekreft at utskriften er vellykket og vil brukes.<br /><br />Først når den er bekreftet vil utskrift og purringer bli registrert.</p>',
					buttons: Ext.Msg.YESNO,
					closable: false,
					fn: function(buttonId, text, opt) {
						if(buttonId == 'yes') {
							registrerUtskrift();
						}
						else if(buttonId == 'no') {
							forkastUtskrift();
						}
					}
				});
				
			}
		}
	},
						
	actionfailed: function(form,action) {
		if(action.type == 'submit') {
			if (action.failureType == "connect") {
				Ext.MessageBox.alert('Problem:', 'Klarte ikke lagre data. Fikk ikke kontakt med tjeneren.');
			}
			else {	
				var result = Ext.JSON.decode(action.response.responseText); 
				if(result && result.msg) {			
					Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', result.msg);
				}
				else {
					Ext.MessageBox.alert( 'Problem:',
						'Lagring av data mislyktes av ukjent grunn. Action type=' + action.type + ', failure type=' + action.failureType
					);
				}
			}
		}
		
	}
	
});
<?php if($utskriftsforsøk):?>
	window.onbeforeunload = function() {
		return 'Du bør ikke forlate utskriftsmenyen uten å bekrefte om utskriften skal registreres i leiebasen.';
	};
	
	var utskriftsbekreftelse = Ext.create('Ext.window.MessageBox', {
		buttons: [
			{
				text: 'Vis utskriften på nytt.',
				handler: function () {
					window.open('index.php?oppslag=giro&oppdrag=lagpdf');
				}
			},
			{
				text: 'Utskriften var OK. Registrer den.',
				handler: function () {
					registrerUtskrift();				
				}
			},
			{
				text: 'Utskriften skal forkastes',
				handler: function () {
					forkastUtskrift();
				}
			}
		]		
	});

	utskriftsbekreftelse.show({
		title: 'Bekreft den eksisterende utskriften',
		msg: 'Det har allerede blitt påbegynt en utskrift som fortsatt står ubekreftet.<br />Du må bekrefte eller forkaste den påbegynte utskriften før du kan skrive ut på nytt.',
		closable: false,
		fn: function(buttonId, text, opt) {
			if(buttonId == 'yes') {
				registrerUtskrift();
			}
			else if(buttonId == 'no') {
				forkastUtskrift();
			}
		}
	});
	
<?php endif;?>


});

<?php
}


function design() {
?>
<div id="panel"></div>
<?php
}


function hentData( $data = "" ) {}



function manipuler($data) {}



function taimotSkjema() {}


}
?>