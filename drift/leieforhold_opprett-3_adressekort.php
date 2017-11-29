<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Registrer nytt leieforhold - Trinn 3 Adressekort';
public $ext_bibliotek = 'ext-4.2.1.883';


function __construct() {
	parent::__construct();

	if(!isset($_POST['leieobjekt']) && !isset($_GET['oppdrag'])) {
		header('Location: index.php?oppslag=leieforhold_opprett-1');
	}	
}

function skript() {
	$tp = $this->mysqli->table_prefix;
	$adressekorttreff = array();
	$navn = array();
	$org = array();
	
	$a = 1;
	
	while(isset($_POST["etternavn{$a}"])) {
	
		$org[$a] = (bool)@$_POST["er_org{$a}"];

		$navn[$a]	= $org[$a]
					? $_POST["etternavn{$a}"]
					: (@$_POST["fornavn{$a}"] . " " . $_POST["etternavn{$a}"]);

		$fornavn	= @$_POST["fornavn{$a}"]
					? explode('|', str_replace(array('. ', ' ', '.', '-'), '|', $_POST["fornavn{$a}"]) )
					: array();
		$etternavn = $_POST["etternavn{$a}"];

		$adressekorttreff[$a] = $this->mysqli->arrayData(array(
			'source'	=> "{$tp}personer AS personer",
			'fields'	=> "personer.personid AS id",
			'class'		=> "Person",
			'where'		=> "(CONCAT(fornavn, ' ', etternavn) LIKE '%" . implode("%' OR CONCAT(fornavn, ' ', etternavn) LIKE '%", $fornavn) . "%')
							AND
							(CONCAT(fornavn, ' ', etternavn) LIKE '%{$etternavn}%')"
		))->data;
		
		$a++;
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
<?
	include_once("_menyskript.php");
?>

	Ext.define('Person', {
		 extend: 'Ext.data.Model',
		
		 // http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
		 fields: [
			 {name: 'id',	type: 'int'},
			 {name: 'navn',	type: 'string'}
		 ]
	 });
	
	
	var hjelp = Ext.create('Ext.window.Window', {
		title: 'Veiledning',
		width: 700,
		height: 400,
		autoScroll: true,
		animCollapse: true,
		closeAction: 'hide',
		html: "<b>Adressekort:</b><br />Kontaktinfo og profil for beboere lagres i personlige adressekort. For å unngå duplikatoppføringer og for å holde adresseregisteret oppdatert bør eksisterende adressekort oppdateres når en ny leieavtale inngås fremfor å opprette et nytt.<br /><br />Dersom leiebasen finner at et navn i den nye leieavalen matcher et eksisterende adressekort vil du få spørsmål om dette er samme person eller ikke. Du bør svare <b>ja</b> på dette <i>med mindre det er snakk om to forskjellige personer som tilfeldigvis har samme navn</i>.<br /><br />Dersom du mener at en av de nye leietakerene allerede finnes i adresseregisteret, men leiebasen ikke klarte å finne det, - fordi det er stavet annerledes, et mellomnavn er benyttet e.l. -, bør du selv lete opp riktig adressekort i nedtrekksmenyen fremfor å opprette et nytt.<br /><br />Kun dersom du vet helt sikkert at personen ikke står i adresseregisteret fra før bør du velge å opprette et nytt adressekort.<br /><br />At et eksisterende adressekort inneholder feil opplysninger har ingen betydning her; det kan oppdateres i neste trinn."
	});


	var leietakerliste = Ext.create('Ext.data.JsonStore', {
		storeId: 'leietakerliste',
		
		autoLoad: false,
		proxy: {
			type: 'ajax',
			url: "index.php?oppslag=leieforhold_opprett-3_adressekort&oppdrag=hentdata&data=adressekort",
			reader: {
				type: 'json',
				root: 'data',
				idProperty: 'id'
			}
		},
			
		model: 'Person'
	});
	
	
	var skjema = Ext.create('Ext.form.Panel', {
		renderTo: 'panel',
		labelAlign: 'top',
		frame:true,
		title: 'Opprett ny leieavtale',
		bodyStyle: 'padding:5px 5px 0',
		standardSubmit: true,
		autoScroll: true,
		width: 900,
		height: 500,
		items: [
		
<?php //	Alle mottatte POST-verdier som ikke tilhører dette skjemaet videresendes som skjulte felter ?>
<?php foreach($_POST as $attributt => $verdi):?>
	<?php preg_match('/^[a-zæøå_]+/i', $attributt, $treff);?>
	<?php switch( $treff[0] ):
	case "adressekort":
	case "personkombo":
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

	<?php foreach($adressekorttreff as $a => $treff):?>
	
			{
				xtype: 'fieldset',
				name: '<?php echo $attributt;?>',
				items: [
			
		<?php if(count($treff)):?>
		
				{
					xtype: 'displayfield',
					value: "Det ble funnet et adressekort for <strong><?php echo addslashes($navn[$a]);?></strong> i leiebasen. Er dette samme <?php echo $org[$a] ? 'virksomhet' : 'person'?>, eller en ny?<br />Adressekortet kan oppdateres etterpå.<br />Hvis riktig person ikke kommer opp som forslag, kan du slå opp adressekortet selv."
				},
		<?php else:?>

				{
					xtype: 'displayfield',
					value: "Det ble ikke funnet noe eksisterende adressekort for <strong><?php echo addslashes($navn[$a]);?></strong>.<br />Hvis du tror <?php echo addslashes($navn[$a]);?> har vært registrert i leiebasen tidligere bør du prøve å slå opp adressekortet for å unngå dobbeltregistreringer.<br />Adressekortet kan oppdateres etterpå."
				},

		<?php endif;?>

		<?php foreach($treff as $person):?>
		
				{
					xtype: 'container',
					layout: 'column',
					items: [
					{
						xtype: 'container',
						columnWidth: 0.3,
						layout: 'form',
						items: [{
							xtype:	'displayfield',
							value:	'<strong>Adressekort <?php echo $person;?>:</strong><br /><?php echo addslashes($person->hent('navn'));?><br /><?php echo $person->hent('org') ? addslashes($person->hent('orgNr')) : ($person->hent('fødselsdato') ? ("f. " . $person->hent('fødselsdato')->format('d.m.Y')) : "");?><br /><?php echo str_replace("\n", '', addslashes(nl2br($person->hent('postadresse'))));?>'

						}]
					},
					{
						xtype: 'container',
						columnWidth: 0.7,
						layout: 'form',
						items: [{
							xtype: 'radio',
							boxLabel: "Ja. <?php echo $org[$a] ? 'Virksomheten' : 'Personen';?> oppført til venstre er den som skal inn i leieavtalen",
							checked: <?php echo (strval($person) == strval(reset($treff))) ? 'true' : 'false'?>,
							hideLabel: false,
							inputValue: <?php echo strval($person);?>,
							name: 'adressekort<?php echo $a;?>'
						}
						]
					}
					]
				},
		<?php endforeach;?>

				{
					xtype: 'container',
					layout: 'column',
					items: [{
						xtype: 'radiofield',
						name: 'adressekort<?php echo $a;?>',
						itemId: 'adressekort<?php echo $a;?>',
						columnWidth: 0.5,
						fieldLabel: ' ',
						labelSeparator: '',
						labelWidth: 180,
						boxLabel: "Slå opp adressekortet: ",
						inputValue: true,
						listeners: {
							change: function( radiofield, newValue, oldValue, eOpts ) {
								if(newValue) {
									radiofield.up().getComponent('personkombo<?php echo $a;?>').enable();
								}
								else {
									radiofield.up().getComponent('personkombo<?php echo $a;?>').disable();
								}
							}
						}
					},
					{
						xtype: 'combobox',
						name: 'personkombo<?php echo $a;?>',
						itemId: 'personkombo<?php echo $a;?>',
						hideLabel: true,
						disabled: true,
						columnWidth: 0.5,
				
						store: leietakerliste,
						queryMode: 'remote',
						valueField: 'id',
						displayField: 'navn',
						triggerAction: 'all',
				
						forceSelection: true,
						editable: true,
						typeAhead: false,
						minChars: 2
					}]
				},

				{
					xtype:		'radio',
					boxLabel:	'Nei. <?php echo count($treff) ? "<i>Denne</i> " : ""?><?php echo $navn[$a];?> er ikke registrert fra før. Opprett nytt adressekort',
					checked: <?php echo !count($treff) ? 'true' : 'false' ?>,
					fieldLabel: ' ',
					labelSeparator: '',
					labelWidth: 180,
					inputValue: 0,
					name: 'adressekort<?php echo $a;?>'
				},	
			]
		},

	<?php endforeach;?>
		{
			xtype: 'displayfield'
		}
	],

		buttons: [{
			scale: 'medium',
			text: 'Hjelp',
			icon: '../bilder/hjelp-2.png',
			iconAlign: 'right',
			handler: function() {
				hjelp.show();
			}
		}, {
			scale: 'medium',
			text: 'Tilbake',
			handler: function() {
				if( skjema.isValid() ) {
					skjema.getForm().submit({
						url: 'index.php?oppslag=leieforhold_opprett-2_leietakere'
					});
				}
			}
		}, {
			scale: 'medium',
			text: 'Avbryt',
			handler: function() {
				window.location = '<?php echo $this->returi->get();?>';
			}
		}, {
			scale: 'medium',
			text: 'Fortsett',
			handler: function() {
				if( skjema.isValid() ) {
					skjema.getForm().submit({
						url: 'index.php?oppslag=leieforhold_opprett-4_oppsummering'
					});
				}
			}
		}]

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
	
	case "adressekort":
		$query = @$this->GET['query'];
		$filter = "1\n";

		if( $query ) {
			$filter	.= "AND (CONCAT(fornavn, '', etternavn) LIKE '%{$query}%')";
		}
		
		$utvalg =	$this->mysqli->arrayData(array(
			'source'	=> "{$tp}personer AS personer",
			'fields'	=> "personid AS id",
			'orderfields'	=> "etternavn, fornavn",
			'class'		=> "Person",
			'where'		=> $filter
		));
		
		foreach( $utvalg->data as $kort ) {
			$resultat->data[] = array(
				'id'	=> "{$kort}",
				'navn'	=>	$kort->hent('navn')
			);
		}
		
		$resultat->success = $utvalg->success;
		$resultat->msg = $utvalg->msg;
		
		return json_encode($resultat);
		break;
	
	default:
		return json_encode($resultat);
		
	}
}



function taimotSkjema() {}



}
?>