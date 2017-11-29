<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Registrer nytt leieforhold - Trinn 2 navn på leietakere';
public $ext_bibliotek = 'ext-4.2.1.883';


function __construct() {
	parent::__construct();

	if(!isset($_POST['leieobjekt']) && !isset($_GET['oppdrag'])) {
		header('Location: index.php?oppslag=leieforhold_opprett-1');
	}	
}

function skript() {
	$tp = $this->mysqli->table_prefix;
	$nyLeietaker = isset($_GET['ny']) ? 1 : 0;
	
	$fornavnliste = $this->mysqli->arrayData(array(
		'distinct'		=> true,
		'source'		=> "{$tp}personer AS personer",
		'fields'		=> "fornavn",
		'orderfields'	=> "fornavn"
	))->data;

	$etternavnliste = $this->mysqli->arrayData(array(
		'distinct'		=> true,
		'source'		=> "{$tp}personer AS personer",
		'fields'		=> "etternavn",
		'orderfields'	=> "etternavn"
	))->data;

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
<?
	include_once("_menyskript.php");
?>

	var hjelp = Ext.create('Ext.window.Window', {
		title: 'Veiledning',
		width: 700,
		height: 400,
		autoScroll: true,
		animCollapse: true,
		closeAction: 'hide',
		html: "<b>Navn:</b><br />Skriv inn både fornavn og etternavn (en om gangen) på de som skal stå oppført som leietakere. Kryss av for 'Legg til flere personer' dersom leieavtalen skal inneholde mer enn ett navn.<br />Dersom det er ei gruppe, organisasjon, eller annen slags virksomhet som skal stå oppført som leietaker istedet for en person krysser du av for 'enhet / organisasjon' og skriver navnet i feltet for etternavn/enhet."
	});


	var fornavnliste = Ext.create('Ext.data.JsonStore', {
		storeId: 'fornavnliste',
		
		autoLoad: false,
		proxy: {
			type: 'ajax',
			url: "index.php?oppslag=leieforhold_opprett-2_leietakere&oppdrag=hentdata&data=fornavn",
			reader: {
				type: 'json',
				root: 'data'
			}
		},
			
		fields: ['fornavn']
	});
	
	
	var etternavnliste = Ext.create('Ext.data.JsonStore', {
		storeId: 'etternavnliste',
		
		autoLoad: false,
		proxy: {
			type: 'ajax',
			url: "index.php?oppslag=leieforhold_opprett-2_leietakere&oppdrag=hentdata&data=etternavn",
			reader: {
				type: 'json',
				root: 'data'
			}
		},
			
		fields: ['etternavn']
	});
	
	
<?
	$a = 1;	
	while($a == 1 || isset($_POST["etternavn" . ($a-$nyLeietaker)])) {
?>
	var er_org<?php echo $a?> = Ext.create('Ext.form.field.Checkbox', {
		boxLabel:	'Bedrifts- / organisasjonsavn',
		checked:	<?php echo @$_POST["er_org{$a}"] ? 'true' : 'false'; ?>,
		labelAlign:	'top',
		hideLabel:	false,
		name:		'er_org<?php echo $a?>',
		inputValue:	1,
		uncheckedValue: 0
	});

   
	var fornavn<?php echo $a?> = Ext.create('Ext.form.field.ComboBox', {
		name: 'fornavn<?php echo $a?>',
		itemId: 'fornavn<?php echo $a?>',
		fieldLabel: 'Fornavn og evt mellomnavn',
		labelAlign: 'top',
		hideLabel: false,
		hideTrigger : true,
		disabled: <?php echo @$_POST["er_org{$a}"] ? 'true' : 'false'; ?>,
		
		store: fornavnliste,
		queryMode: 'remote',
		valueField: 'fornavn',
		displayField: 'fornavn',
		triggerAction: 'all',
				
		forceSelection: false,
		editable: true,
		typeAhead: false,
		minChars: 2,
		value: '<?php echo addslashes(@$_POST["fornavn{$a}"])?>'
	});


	var etternavn<?php echo $a?> = Ext.create('Ext.form.field.ComboBox', {
		name: 'etternavn<?php echo $a?>',
		itemId: 'etternavn<?php echo $a?>',
		fieldLabel: '<?php echo @$_POST["er_org{$a}"] ? "Organisasjonsnavn" : "Etternavn"; ?>',
		labelAlign: 'top',
		hideLabel: false,
		hideTrigger : true,
		
		store: etternavnliste,
		queryMode: 'remote',
		valueField: 'etternavn',
		displayField: 'etternavn',
		triggerAction: 'all',
				
		forceSelection: false,
		editable: true,
		typeAhead: false,
		minChars: 2,
		value: '<?php echo addslashes(@$_POST["etternavn{$a}"])?>'
	});
	
	er_org<?php echo $a?>.on({
		change: function( checkbox, newValue, oldValue, eOpts ) {
			if(newValue) {
				fornavn<?php echo $a?>.disable();
				etternavn<?php echo $a?>.setFieldLabel('Organisasjonsnavn');
			}
			else {
				fornavn<?php echo $a?>.enable();
				etternavn<?php echo $a?>.setFieldLabel('Etternavn');
			}
		}
	});
	
<?
	$a++;	
	}
?>
	var skjema = Ext.create('Ext.form.Panel', {
		title: 'Opprett ny leieavtale',
		renderTo: 'panel',
		autoScroll: true,
		width: 900,
		height: 500,
		standardSubmit: true,
		frame: true,
		bodyStyle: 'padding:5px 5px 0',
		items: [{
			xtype: 'displayfield',
			value: 'Skriv inn fornavn og etternavn på personen som skal stå i leieavtalen, evt navn på firma eller enhet som skal stå som leietaker.<br>Dersom du har behov for å føre på flere personer krysser du av for dette i ruten under.'
		},

<?php $a = 1;	while ($a == 1 || isset($_POST["etternavn" . ($a-$nyLeietaker)])): ?>
		{
			xtype: 'container',
			layout:'column',
			anchorSize: 850,
			items:[{
				xtype: 'container',
				columnWidth: 0.2,
				margin: 5,
				layout: 'form',
				items: [er_org<?php echo $a?>]
			},
			{
				xtype: 'container',
				columnWidth: 0.4,
				margin: 5,
				layout: 'form',
				items: [fornavn<?php echo $a?>]
			},
			{
				xtype: 'container',
				columnWidth: 0.3,
				margin: 5,
				layout: 'form',
				items: [etternavn<?php echo $a?>]
			},
			{
				xtype: 'container',
				columnWidth: 0.1,
				margin: 5,
				layout: 'form',
				items: [
				
					{
						xtype: 'button',
						text: 'Fjern',
						hidden: <?php echo @$_POST["etternavn{$a}"] ? 'true' : 'false'?>,
						handler: function(button, event) {
							er_org<?php echo $a?>.disable();
							er_org<?php echo $a?>.hide();
							fornavn<?php echo $a?>.disable();
							fornavn<?php echo $a?>.hide();
							etternavn<?php echo $a?>.disable();
							etternavn<?php echo $a?>.hide();
							
							button.hide();
						}
					}
				]
			}]
		},
<?php $a++; endwhile;?>

<?php //	Alle mottatte POST-verdier som ikke tilhører dette skjemaet videresendes som skjulte felter ?>
<?php foreach($_POST as $attributt => $verdi):?>
	<?php preg_match('/^[a-zæøå_]+/i', $attributt, $treff);?>
	<?php switch( $treff[0] ):
	case "er_org":
	case "fornavn":
	case "etternavn":
		break;?>
	<?php case "delkrav":?>
		{
			xtype: 'hidden',
			name: '<?php echo $attributt;?>',
			value: '<?php echo str_replace(",", ".", $verdi);?>'
		},

	<?php break;?>
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
			xtype: 'button',
			text: 'Legg til flere personer i leieavtalen',
			handler: function() {
				if( skjema.isValid() ) {
					skjema.getForm().submit({
						url: 'index.php?oppslag=leieforhold_opprett-2_leietakere&ny'
					});
				}
			}
		}],

		buttons: [
			{
				scale: 'medium',
				icon: '../bilder/hjelp-2.png',
				iconAlign: 'right',
				text: 'Hjelp',
				handler: function() {
					hjelp.show();
				}
			},
			{
				scale: 'medium',
				text: 'Tilbake',
				handler: function() {
					if( skjema.isValid() ) {
						skjema.getForm().submit({
							url: 'index.php?oppslag=leieforhold_opprett-1'
						});
					}
				}
			},
			{
				scale: 'medium',
				text: 'Avbryt',
				handler: function() {
					window.location = '<?php echo $this->returi->get();?>';
				}
			},
			{
				scale: 'medium',
				text: 'Fortsett',
				handler: function() {
					if( skjema.isValid() ) {
						skjema.getForm().submit({
							url: 'index.php?oppslag=leieforhold_opprett-3_adressekort'
						});
					}
				}
			}
		]
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

	$resultat = (object)array(
		'success'	=> false,
		'msg'		=> "",
		'data'		=> array()
	);


	switch ($data) {
	
	case "fornavn":
		$query = @$this->GET['query'];
		$filter	= "fornavn LIKE '%{$query}%'";

		$resultat = $this->mysqli->arrayData(array(
			'distinct'		=> true,
			'source'		=> "{$tp}personer AS personer",
			'fields'		=> "fornavn",
			'orderfields'	=> "fornavn",
			'where'			=> $filter
		))->data;

		
		return json_encode($resultat);
		break;
		
	
	case "etternavn":
		$query = @$this->GET['query'];
		$filter	= "etternavn LIKE '%{$query}%'";

		$resultat = $this->mysqli->arrayData(array(
			'distinct'		=> true,
			'source'		=> "{$tp}personer AS personer",
			'fields'		=> "etternavn",
			'orderfields'	=> "etternavn",
			'where'			=> $filter
		))->data;

		
		return json_encode($resultat);
		break;
		
	
	default:
		return json_encode($resultat);
		break;
		
	}
}



function taimotSkjema() {}



}
?>