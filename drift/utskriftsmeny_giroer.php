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
	$tildato = @$_POST['tildato'];
	$leieforhold = $this->leieforhold( @$_POST['leieforhold'] );

	if(isset($_POST['girotekst'])) {
		$this->mysqli->query("UPDATE valg SET verdi = '{$this->POST['girotekst']}' WHERE innstilling = 'girotekst'");
		$this->hentValg();
	}

	$this->oppdaterUbetalt();

	
	$utskriftforslag = $this->mysqli->arrayData(array(
		'source'		=> "krav LEFT JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
		'returnQuery'	=> true,
		'class'			=> "Krav",
		'fields'		=> "krav.id",
		'orderfields'		=> "kontrakter.leieforhold, krav.type, krav.id",
		'where'			=> "krav.utskriftsdato IS NULL\n"
						.	"AND krav.kravdato <= '" . date('Y-m-d', strtotime( $tildato )) . "'\n"
						.	(
							$leieforhold
							? "AND kontrakter.leieforhold = '{$leieforhold}'\n"
							: ""
							)
						.	"AND (0"
						.	(( @$_POST["Husleie"] ) ? " OR type = 'Husleie'" : "")
						.	(( @$_POST["Fellesstrøm"] ) ? " OR type = 'Fellesstrøm'" : "")
						.	(( @$_POST["Annet"] ) ? " OR (type != 'Husleie' AND type != 'Fellesstrøm')" : "")
						.	")\n"
	));

	foreach( $utskriftforslag->data as $krav ) {
		$leieforhold = $krav->hent('leieforhold');
		$krav->benevnelse = $krav->hent('tekst') . " (" . $leieforhold->hent('navn') . ")";
		
		if($this->valg['efaktura'] and $leieforhold->hent('efakturaavtale')) {
			$krav->benevnelse .= " <img src=\"../bilder/eFaktura_web_149_35.png\" alt=\"eFaktura\" height=\"12px\">";
		}
	}
	
	
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

forkastUtskrift = function() {
	Ext.MessageBox.wait('Utskriften forkastes', 'Vent litt...');
	Ext.Ajax.request({
	
		url: 'index.php?oppslag=utskriftsmeny&oppdrag=manipuler&data=forkast_utskrift',

		failure: function( response, options ) {
			Ext.MessageBox.alert('Whoops! Problemer...', 'Oppnår ikke kontakt med databasen! Prøv igjen senere.');
		},
		
		success: function(response, options) {
			var tilbakemelding = Ext.JSON.decode(response.responseText);
			if(tilbakemelding['success'] == true) {
				window.onbeforeunload = null;
				Ext.MessageBox.alert('Utført', tilbakemelding.msg, function() {
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
	
		url: 'index.php?oppslag=utskriftsmeny&oppdrag=taimotskjema&skjema=oppdatering',
		params: {},
		failure:function(response,options){
			Ext.MessageBox.alert('Whoops! Problemer...','Oppnår ikke kontakt med databasen! Prøv igjen senere.');
		},
		
		success: function(response,options) {
				var tilbakemelding = Ext.JSON.decode(response.responseText);
				if(tilbakemelding['success'] == true) {
					Ext.MessageBox.alert('Utført', tilbakemelding.msg, function() {
						window.onbeforeunload = null;
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
		window.location = '<?=$this->returi->get();?>';				
	}
});

var pdfKnapp = Ext.create('Ext.button.Button', {
	text: 'Opprett utskriftsfil (PDF) uten purringer',
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
			standardSubmit: false,
			url: 'index.php?oppslag=utskriftsmeny&oppdrag=manipuler&data=lag_giroer',
			success: function () {
				framdriftsindikator.hide();
				window.open('index.php?oppslag=giro&oppdrag=lagpdf');
	
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
		});
	}
});

<?if( !count( $utskriftforslag->data ) ):?>
	pdfKnapp.disable();

<?endif;?>



var purringer = Ext.create('Ext.button.Button', {
	text: 'Fortsett for å legge til purringer',
	handler: function() {
		skjema.getForm().submit({
			waitMsg: "Vent litt...",
			standardSubmit: true,
			url: 'index.php?oppslag=utskriftsmeny_purringer'
		});
	}
});

var framdriftsindikator = Ext.create('Ext.window.MessageBox', {
   width: 300
});


var skjema = Ext.create('Ext.form.Panel', {
	renderTo: 'panel',
	autoScroll: true,
	labelAlign: 'left',
	frame: true,
	height: 500,
	title: 'Krav som skal settes sammen til giroer',
	bodyPadding: 5,
	standardSubmit: false,
	width: 900,
	buttons: [
		avbryt,
		pdfKnapp,
		purringer
	],
	items: [
<?if( !count( $utskriftforslag->data ) ):?>
			{
				xtype: 'displayfield',
				value: '<br />Det ble ikke funnet noen løse krav for det angitte tidsrommet, som ikke er tatt med på giro.<br />Klikk fortsett for å skrive ut purringer.'
			},

<?endif;?>

<?foreach($utskriftforslag->data as $krav):?>
			{
				xtype: 'checkbox',
				fieldLabel: 'Krav <?=$krav->hent('id');?>',
				boxLabel: '<?=addslashes($krav->benevnelse);?>',
				name: '<?=$krav->hent('id');?>',
				inputValue: 1,
				checked: true
			},
<?endforeach;?>
			{
				xtype: 'hidden',
				name: 'adskilt',
				value: <?=(( isset( $_POST['adskilt'] ) && $_POST['adskilt'] ) ? "1" : "0");?>
			},
	]
});
<?if($utskriftsforsøk):?>
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
	
<?endif;?>


});
<?
}



function design() {
?>
<div id="panel"></div>
<?
}



function hentData($data = "") {}



function manipuler( $data ) {
	switch ( $data ) {
	
	default:
		break;
	}
}



function taimotSkjema() {}



}
?>