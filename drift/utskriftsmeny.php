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
	if( isset( $_GET['returi'] ) && $_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	$utskriftsforsøk = (bool)$this->valg['utskriftsforsøk'];
	
	$tildato = @$_GET['tildato'];
	if( $tildato ) {
		$tildato = new DateTime( @$_GET['tildato'] );
	}
	else {
		$tildato = new DateTime( $tildato );
		$tildato->add( new DateInterval( 'P1M' ) );
	}
	
	
	// Avslutt levering til det leide leieobjektet i alle oppsagte leieforhold
	$this->mysqli->saveToDb(array(
		'update'	=> true,
		'table'		=> "kontrakter INNER JOIN oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold",
		'where'		=> "oppsigelser.fristillelsesdato <= NOW() AND regningsobjekt = leieobjekt",
		'fields'	=> array(
			'kontrakter.regning_til_objekt'	=> false
		)
	));

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
					utskriftsbekreftelse.close();
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
						utskriftsbekreftelse.close();
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

	Ext.define('Leieforhold', {
		extend: 'Ext.data.Model',
		idProperty: 'leieforhold',
		fields: [
			{name: 'leieforhold', type: 'string'}, // combo value is type sensitive
			{name: 'visningsfelt', type: 'string'}
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

	var leieforhold = Ext.create('Ext.form.field.ComboBox', {
		allowBlank: true,
		displayField: 'visningsfelt',
		editable: true,
		emptyText: 'Velg leieforhold, eller hold blank for å skrive ut for alle leieforhold',
		fieldLabel: 'Leieforhold',
		forceSelection: false,
		hideLabel: false,
		listWidth: 700,
		maxHeight: 600,
		matchFieldWidth: false,
		minChars: 1,
		name: 'leieforhold',
		queryMode: 'remote',
		selectOnFocus: true,
		store: leieforholddata,
		typeAhead: false,
		value: '<?=isset( $_GET['id'] ) ? $_GET['id'] : "";?>',
		valueField: 'leieforhold',
		listConfig: {
			loadingText: 'Søker ...',
			emptyText: 'Ingen treff...',
			maxHeight: 600,
			width: 600
		},
		width: 700
	});


	var adskilt = Ext.create('Ext.form.field.Checkbox', {
		fieldLabel: 'Adskilte giroer',
		boxLabel: 'Husleie- og fellesstrømkrav på adskilte giroer.',
		name: 'adskilt',
		inputValue: 1,
		checked: false
	});

	var tildato = Ext.create('Ext.form.field.Date', {
		allowBlank: false,
		fieldLabel: 'Inkluder nye krav fram til og med dato',
		format: 'd.m.Y',
		name: 'tildato',
		submitFormat: 'Y-m-d',
		value: '<?php echo $tildato->format('Y-m-d');?>',
		width: 200
	});

	var kravtyper = Ext.create('Ext.form.CheckboxGroup', {
		frame: true,
		fieldLabel: 'Hvilke typer krav skal taes med på giroene?',
		items: [
			{inputValue: 1, boxLabel: 'Husleie', name: 'Husleie', checked: true},
			{inputValue: 1, boxLabel: 'Fellesstrøm', name: 'Fellesstrøm', checked: true},
			{inputValue: 1, boxLabel: 'Andre krav', name: 'Annet', checked: true}
		]
	});

	var girotekst = Ext.create('Ext.form.field.TextArea', {
		fieldLabel: 'Tekst på giroer',
		height: 200,
		name: 'girotekst',
		value: <?=json_encode($this->valg['girotekst'])?>,
		width: 700
	});

	var avbryt = Ext.create('Ext.button.Button', {
		text: 'Avbryt',
		id: 'avbryt',
		handler: function() {
			window.location = '<?=$this->returi->get();?>';				
		}
	});

	var fortsett = Ext.create('Ext.button.Button', {
		text: 'Fortsett',
		id: 'fortsettknapp',
		handler: function() {
			skjema.getForm().submit({
				standardSubmit: true,
				url: 'index.php?oppslag=utskriftsmeny_giroer' + (leieforhold.value ? ('&id=' + leieforhold.value) : '')
			});
		}
	});

	var skjema = Ext.create('Ext.form.Panel', {
		renderTo: 'panel',
		autoScroll: true,
		labelAlign: 'top',
		frame: true,
		title: 'Velg hvilke krav som skal taes med på de nye giroene',
		bodyPadding: 5,
		standardSubmit: false,
		width: 900,
		height: 500,
		items: [
			leieforhold,
			tildato,
			kravtyper,
			adskilt,
			girotekst
		],
		buttons: [
			avbryt,
			fortsett
		]
	});
	

<?if($utskriftsforsøk):?>
	window.onbeforeunload = function() {
		return 'Du bør ikke forlate utskriftsmenyen uten å bekrefte om utskriften skal registreres i leiebasen.';
	};
	
	var utskriftsbekreftelse = Ext.create('Ext.window.MessageBox', {
		buttons: [
			{
				text: 'Avbryt',
				handler: function () {
					window.onbeforeunload = null;
					window.location = '<?=$this->returi->get();?>';				
				}
			},
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



function hentData($data = "") {
	switch ($data) {
	
	case "leieforhold":
		$resultat = new stdclass;
		$filter = "";
		if(isset( $_GET['query']) && $_GET['query'] ) {
			$filter =	"WHERE CONCAT(fornavn, ' ', etternavn) LIKE '%{$this->GET['query']}%'\n"
				.	"OR kontrakter.leieforhold LIKE '" . (int)$this->leieforhold($this->GET['query']) . "'\n";
		}
		$sql =	"SELECT DISTINCT\n"
			.	"kontrakter.leieforhold, max(kontrakter.kontraktnr) as kontraktnr, leieobjekt , gateadresse, MAX(andel) AS andel, min(fradato) AS startdato, max(tildato) AS tildato, max(kontrakter.frosset) AS frosset\n"
			
			.	"FROM\n"
			.	"kontrakter LEFT JOIN leieobjekter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr\n"
			.	"LEFT JOIN kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt\n"
			.	"LEFT JOIN personer ON kontraktpersoner.person = personer.personid\n"
			
			.	$filter
			
			.	"GROUP BY kontrakter.leieforhold, leieobjekt, gateadresse\n"
			.	"ORDER BY startdato DESC, tildato DESC, etternavn, fornavn\n";
		$liste = $this->arrayData($sql);
		$resultat->success = $liste['success'];
		$resultat->sql = $sql;
		$resultat->data[0] = (object) array('leieforhold' => 0, 'frosset' => 0, 'visningsfelt' => "Alle leieforhold");
		foreach($liste['data'] as $linje => $d) {
			$resultat->data[] = (object) array(
				'leieforhold'	=> $d['leieforhold'],
				'frosset'		=> $d['frosset'],
				'visningsfelt'	=> ($d['leieforhold'] . ' | '
				. ($this->liste($this->kontraktpersoner($d['kontraktnr'])))
				. ' for #' . $d['leieobjekt'] . ', ' . $d['gateadresse'] . ' | '
				. $d['startdato'] . ' - ' . $d['tildato']
				. ($d['frosset'] ? " (fryst)" : ""))
			);
		}
		return json_encode($resultat);
		break;
		
	default:
		return json_encode($resultat);

	}
}



function manipuler( $data ) {
	switch ( $data ) {
	
	case "frys":
	{
		$leieforhold = $this->leieforhold(@$_GET['id']);
		$sql =	"UPDATE kontrakter SET frosset = 1 WHERE leieforhold = '{$leieforhold}'";
		if($this->mysqli->query($sql)){
			$resultat['msg'] = "Leieforhold $leieforhold har blitt frosset";
			$resultat['success'] = true;
		}
		else{
			$resultat['msg'] = "Klarte ikke fryse leieavtalen. Meldingen fra database lyder:<br />" . $this->mysqli->error;
			$resultat['success'] = false;
		}
		echo json_encode($resultat);
		break;
	}
		
	case "forkast_utskrift":
	{

		$resultat = (object)array(
			'success'	=> $this->forkastUtskrift()
		);
		
		if( $resultat->success ) {
			$resultat->msg = "Utskriften er slettet.";
		}

		echo json_encode($resultat);
		break;
	}
		
	case "lag_giroer":
	{
		$resultat = (object)array(
			'success'	=> false,
			'msg'		=> ""
		);

		// Sjekk om det allerede er en utskrift på gang, og sender denne på nytt
		if ( $this->valg['utskriftsforsøk'] ) {
			$data = unserialize($this->valg['utskriftsforsøk']);
		}
		
		else {
			$kravsett = array();
			$purregiroer = array();
			$statusoversikter = array();
			$gebyrkontrakter = array();

			$gebyrkontrakter = array();

			foreach ( $_POST as $angivelse => $tattmed ) {
		
				if ( (int)$angivelse > 0 and $tattmed ) {
					$kravsett[] = (int)$angivelse;
				}
				if ( substr( $angivelse, 0, 10 ) == "purregiro:" and $tattmed ) {
					$purregiroer[] = $this->hent('Giro', (int)substr( $angivelse, 10 ) );
				}
				if ( substr( $angivelse, 0, 15 ) == "statusoversikt:" and $tattmed ) {
					$statusoversikter[] = (int)substr( $angivelse, 15 );
				}
				if ( substr( $angivelse, 0, 11 ) == "purregebyr:" and $tattmed ) {
					$gebyrkontrakter[] = (int)substr( $angivelse, 11 );
				}
		
			}
		
			$data = $this->forberedUtskrift( array(
				'kravsett'			=> $kravsett,
				'purregiroer'		=> $purregiroer,
				'gebyrkontrakter'	=> $gebyrkontrakter,
				'adskilt'			=> (bool)$_POST['adskilt'],
				'kombipurring'		=> ( isset( $_POST['kombipurring'] ) ? (bool)$_POST['kombipurring'] : false ),
				'statusoversikter'	=> $statusoversikter
			) );
		}

		
		// Her fjernes alle betalte giroer ifra utskriftsbunken
		foreach ( $data->giroer as $id => $gironr ) {
			if(
				$this->hent('Giro', $gironr)->hent('beløp') > 0
				and $this->hent('Giro', $gironr)->hent('utestående') == 0
			) {
				unset( $data->giroer[$id] );
			}
		}
		
		$resultat->success = $this->lagUtskriftsfil( $data );

		echo json_encode($resultat);
		break;
	}
	}
}



function taimotSkjema() {
	$adresser = $this->utskriftsadresser();
	if( is_array( $adresser ) ) {
		$adresser = array_map( 'strval', $adresser);
	}

	$resultat = (object)array(
		'success'	=> $this->registrerUtskrift()
	);
	
	$resultat->adresser = (array)$adresser;
	if( $resultat->success ) {
		$resultat->msg = "Utskriften er registrert.";
	}
	else {
		$resultat->msg = "Utskriften kunne ikke registreres. Kan hende har den allerede blitt registrert?";
	}

	echo json_encode($resultat);

}



}
?>