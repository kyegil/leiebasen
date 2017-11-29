<?php
/**********************************************
CollectivePOS
by Kay-Egil Hauan
**********************************************/
if(!defined('LEGAL')) die('No access!<br />Check your URI.');

class oppsett extends leiebase {

public $tittel = 'leiebasen';
public $ext_bibliotek = 'ext-4.2.1.883';
	

function __construct() {
	parent::__construct();
}

function skript() {
	if(@$_GET['returi'] == "default") {
		$this->returi->reset();
	}
	$this->returi->set();
	$tp = $this->mysqli->table_prefix;
	
	$kontoer = $this->mysqli->arrayData(array(
		'source'	=> "{$tp}kontoer as kontoer",
		'fields'	=> "kontoer.id, kontoer.navn",
		'where'		=> "id > 0"
	))->data;
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

	var kontoer = Ext.create('Ext.data.Store', {
		fields: ['id', 'navn'],
		data : <?php echo json_encode($kontoer);?>

	});

	var id = Ext.create('Ext.form.field.Hidden', {
		fieldLabel: 'ID',
		name: 'id',
		labelWidth: 120
	});
	
	var dato = Ext.create('Ext.form.field.Date', {
		allowBlank: false,
		fieldLabel: 'Transaksjonsdato',
		format: 'd.m.Y',
		name: 'dato',
		submitFormat: 'Y-m-d',
		width: 200,
		value: '<?php echo date('Y-m-d') ;?>',
		tabIndex: 1
	});
	
	var beløp = Ext.create('Ext.form.field.Number', {
		allowBlank: false,
		allowDecimals: true,
		decimalSeparator: ',',
		decimalPrecision: 2,
		hideTrigger: true,
		fieldLabel: 'Innbetalt beløp<br />(Negativt beløp angir utbetaling)',
		name: 'beløp',
		width: 200,
		tabIndex: 2
	});
	
	var konto = Ext.create('Ext.form.field.ComboBox', {
		fieldLabel: 'Konto',
		name: 'konto',
		width: 200,
		tabIndex: 3,
		allowBlank:	false,
		
		store: kontoer,
		queryMode: 'local',
		displayField: 'navn',
		valueField: 'navn',
		forceSelection: true
	});
	
	var betaler = Ext.create('Ext.form.field.Text', {
		fieldLabel: 'Betaler, som oppgitt på transaksjonsbilag, kontoutskrift e.l.',
		name: 'betaler',
		width: 200,
		tabIndex: 4
	});

	var referanse = Ext.create('Ext.form.field.Text', {
		fieldLabel: 'Referanse til kontoutskrift e.l.',
		width: 200,
		name: 'referanse',
		tabIndex: 5
	});
	
	var merknad = Ext.create('Ext.form.field.Text', {
		fieldLabel: 'Evt. merknader',
		width: 200,
		name: 'merknad',
		tabIndex: 6
	});
	
	var leieforhold = Ext.create('Ext.form.field.ComboBox', {
		allowBlank: true,
		fieldLabel: 'Leieforhold',
		hideLabel: false,
		name: 'leieforhold',
		width: 200,
		matchFieldWidth: false,
		listConfig: {
			width: 700
		},

		store: Ext.create('Ext.data.JsonStore', {
			storeId: 'leieobjektliste',
		
			autoLoad: false,
			proxy: {
				type: 'ajax',
				url: "index.php?oppslag=betalingsskjema&oppdrag=hentdata&data=leieforholdforslag",
				reader: {
					type: 'json',
					root: 'data',
					idProperty: 'leieforhold'
				}
			},
			
			fields: [
				{name: 'leieforhold'},
				{name: 'visningsfelt'}
			]
		}),
		queryMode: 'remote',
		displayField: 'visningsfelt',
		valueField: 'leieforhold',
		minChars: 0,
		queryDelay: 1000,

		allowBlank: true,
		typeAhead: false,
		editable: true,
		selectOnFocus: true,
		forceSelection: true
	});



	var lagreknapp = Ext.create('Ext.Button', {
		text: 'Lagre',
		disabled: true,
		handler: function() {
			skjema.form.submit({
				url:'index.php?oppslag=<?=$_GET["oppslag"];?>&id=<?=$_GET["id"];?>&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...',
				success: function(form, action) {
					if(action.response.responseText == '') {
						Ext.MessageBox.alert('Problem', 'Det kom en blank respons fra tjeneren.');
					} else {
						Ext.MessageBox.alert('Lagret', action.result.msg);
						window.location = action.result.url;
					}
				}
			});
		}
	});

	var lagreOgNyKnapp = Ext.create('Ext.Button', {
		text: 'Lagre og registrer ny',
		disabled: true,
		handler: function() {
			skjema.form.submit({
				url:'index.php?oppslag=<?=$_GET["oppslag"];?>&id=<?=$_GET["id"];?>&oppdrag=taimotskjema',
				waitMsg:'Lagrer denne først...',
				success: function(form, action) {
					if(action.response.responseText == '') {
						Ext.MessageBox.alert('Problem', 'Det kom en blank respons fra tjeneren.');
					} else {
						Ext.MessageBox.alert('Lagret', action.result.msg);
						window.location = 'index.php?oppslag=betalingsskjema&id=*';
					}
				}
			});
		}
	});


	var skjema = Ext.create('Ext.form.Panel', {
		autoScroll: true,
		bodyPadding: 5,
		renderTo: 'panel',
		height: 500,
		width: 900,
		fieldDefaults: {
			labelAlign: 'top',
			width: 200
		},
		items: [
			dato,
			beløp,
			konto,
			betaler,
			referanse,
			merknad
		],
		layout: 'anchor',
		frame: true,
		title: 'Registrer ny betaling',
		buttons: [{
			text: 'Tilbake',
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
			}
		},
		lagreOgNyKnapp,
		lagreknapp
		]
	});



	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'load') {
				lagreknapp.enable();
				lagreOgNyKnapp.enable();
			}
		},
							
		actionfailed: function(form,action) {
			if(action.type == 'load') {
				if (action.failureType == "connect") {
					Ext.MessageBox.alert('Problem:', 'Klarte ikke laste data. Fikk ikke kontakt med tjeneren.');
				}
				else {
					if (action.response.responseText == '') {
						Ext.MessageBox.alert('Problem:', 'Skjemaet mottok ikke data i JSON-format som forventet');
					}
					else {
						var result = Ext.JSON.decode(action.response.responseText);
						if(result && result.msg) {			
							Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', result.msg);
						}
						else {
							Ext.MessageBox.alert('Problem:', 'Innhenting av data mislyktes av ukjent grunn.');
						}
					}
				}
			}
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
						Ext.MessageBox.alert('Problem:', 'Lagring av data mislyktes av ukjent grunn. Action type='+action.type+', failure type='+action.failureType);
					}
				}
			}
			
		}
	});


	skjema.getForm().load({
		url: 'index.php?oppslag=<?=$_GET['oppslag']?>&id=<?=$_GET['id'];?>&oppdrag=hentdata',
		waitMsg: 'Henter opplysninger...'
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
	$id = $this->GET['id'];

	switch ($data) {

	case "leieforholdforslag":
		$tp = $this->mysqli->table_prefix;
		$query = $this->GET['query'];
		
		$resultat = (object)array(
			'success'	=> true,
			'data'		=> array()
		);

		$leieforholdsett = $this->mysqli->arrayData(array(
			'source'	=> "{$tp}kontrakter as kontrakter
							INNER JOIN {$tp}kontraktpersoner as kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt
							INNER JOIN {$tp}personer as personer ON personer.personid = kontraktpersoner.person",
			'fields'	=>	"kontrakter.leieforhold AS id",
			'where'		=>	"CONCAT(fornavn, ' ', etternavn) LIKE '%{$query}%'
							OR kontrakter.kontraktnr LIKE '%{$query}%'",
			'distinct'	=> true,
			'class'		=> "Leieforhold"
		))->data;
		
		foreach( $leieforholdsett as $leieforhold ) {
			$resultat->data[] = array(
				'leieforhold'	=> $leieforhold->hentId(),
				'visningsfelt'	=> $leieforhold->hent('beskrivelse')
			);
		}
		
		return json_encode($resultat);
		break;

	default:
	
		if($_GET['id'] == '*') {
			$resultat = (object)array(
				'success'	=> true,
				'data'		=> array()
			);
		}
		
		else {
			$innbetaling = $this->hent('Innbetaling', $id);
			
			if($innbetaling->hentId())	{
				$resultat = (object)array(
					'success'	=> true,
					'data'		=> array(
						'beløp'		=> $innbetaling->hent('beløp'),
						'referanse'	=> $innbetaling->hent('ref'),
						'merknad'	=> $innbetaling->hent('merknad'),
						'betaler'	=> $innbetaling->hent('betaler'),
						'konto'		=> $innbetaling->hent('konto'),
						'dato'		=> ($innbetaling->hent('dato') ? $innbetaling->hent('dato')->format('Y-m-d') : null)
					)
				);
			}
			else {
				$resultat = (object)array(
					'success'	=> false,
					'msg'		=> "Kunne ikke laste denne betalinga pga. en feil."
				);
			}
		}
		echo json_encode($resultat);
		break;
	}
}

function taimotSkjema($skjema) {

	switch ($skjema) {

	default:
		if(($id = $_GET['id']) == '*') {
			$innbetaling = $this->opprett('Innbetaling', array(
				'dato'			=> $_POST['dato'],
				'beløp'			=> $_POST['beløp'],
				'betaler'		=> $_POST['betaler'],
				'ref'			=> $_POST['referanse'],
				'konto'			=> $_POST['konto'],
				'merknad'		=> $_POST['merknad']
			));
			
			echo json_encode(array(
				'success'	=> (bool)$innbetaling,
				'url'		=> "{$this->http_host}/drift/index.php?oppslag=utlikninger_skjema"
			));
			break;			
		}
		
		else {
			$innbetaling = $this->hent('innbetaling', $id);
			$resultat = (object)array(
				'success'	=> false
			);
			if (!$innbetaling->sett('dato', $_POST['dato'])) {
				$resultat->msg = "Kunne ikke lagre ny dato";
			}
			else if (!$innbetaling->sett('beløp', str_replace(',', '.', $_POST['beløp']))) {
				$resultat->msg = "Kunne ikke lagre nytt beløp";
			}
			else if (!$innbetaling->sett('betaler', $_POST['betaler'])) {
				$resultat->msg = "Kunne ikke lagre betaler";
			}
			else if (!$innbetaling->sett('ref', $_POST['referanse'])) {
				$resultat->msg = "Kunne ikke lagre referanse";
			}
			else if (!$innbetaling->sett('konto', $_POST['konto'])) {
				$resultat->msg = "Kunne ikke lagre konto";
			}
			else if (!$innbetaling->sett('merknad', $_POST['merknad'])) {
				$resultat->msg = "Kunne ikke lagre merknaden";
			}
			else if ( @$_POST['leieforhold'] and !$innbetaling->konter( $_POST['leieforhold'] )) {
				$resultat->msg = "Kunne ikke lagre leieforhold";
			}
			else {
				$resultat->success = true;
			}
			
			if($resultat->success) {
				$resultat->url= "{$this->http_host}/drift/index.php?oppslag=utlikninger_skjema";
				$this->returi->reset();
			}

			echo json_encode($resultat);
			break;
		}
	}
}


function oppgave($oppgave) {
	switch ($oppgave) {
		case "slett":
			echo json_encode($resultat);
			break;
		default:
			break;
	}
}

}
?>