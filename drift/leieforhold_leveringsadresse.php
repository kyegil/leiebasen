<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Registrer nytt leieforhold - Trinn 5 Levering';
public $ext_bibliotek = 'ext-4.2.1.883';


function __construct() {
	parent::__construct();

}

function skript() {
	if(@$_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	$tp = $this->mysqli->table_prefix;

	$leieforhold = $this->hent( 'Leieforhold', (int)$_GET['id'] );
	$leieobjekt = $leieforhold->hent( 'leieobjekt' );
	$regningsobjekt = $leieforhold->hent( 'regningsobjekt' );
	$leietakere = $leieforhold->hent( 'leietakere' );
	
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

	Ext.define('Leieobjekt', {
		 extend: 'Ext.data.Model',
		 
		 // http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
		 fields: [
			 {name: 'leieobjektnr',				type: 'int'},
			 {name: 'beskrivelse',				type: 'string'}
		 ]
	 });
	 
	 
	var hjelp = Ext.create('Ext.window.Window', {
		title: 'Veiledning',
		width: 700,
		height: 400,
		autoScroll: true,
		animCollapse: true,
		closeAction: 'hide',
		html: "<b>Regningsadresse:</b><br />Velg hvilket adressekort som skal brukes som regningsadresse når giroer o.l. sendes i posten, eller oppgi en egen regningsadresse.<br /><br />Dersom giroer skal leveres på døra må du oppgi hvilken bolig de skal leveres til."
	});


	var leieobjektliste = Ext.create('Ext.data.JsonStore', {
		storeId: 'leieobjektliste',
		
		autoLoad: false,
		proxy: {
			type: 'ajax',
			url: "index.php?oppslag=leieforhold_leveringsadresse&oppdrag=hentdata&data=leieobjektliste",
			reader: {
				type: 'json',
				root: 'data',
				idProperty: 'leieobjektnr'
			}
		},
			
		model: 'Leieobjekt'
	});
	

	var radiogruppe = Ext.create('Ext.form.RadioGroup', {
		autoHeight: false,
		defaultType: 'radio',
		fieldLabel: 'Regningsadresse',
		vertical: true,
		value: '<?php echo $leieforhold->hent('regningsperson');?>',
		items: [

			<?php foreach($leietakere as $leietaker):?>

			{
				boxLabel:	'<i><?php echo addslashes($leietaker->hent('navn'));?>\'s adresse:</i><br /><?php echo addslashes( str_replace("\n", "", nl2br($leietaker->hent('postadresse'))) );?>',
				inputValue:	'<?php echo $leietaker;?>',
				name:		'regningsperson',
				checked: 	<?php echo (strval($leieforhold->hent('regningsperson')) == strval($leietaker)) ? "true" : "false";?>,
				height:		100
			}<?php echo ($leietaker != end($leietakere)) ? "," : "";?>

		<?php endforeach;?>
			]
		});


	var regningsobjekt = Ext.create('Ext.form.field.ComboBox', {
		name: 'regningsobjekt',
		fieldLabel: 'Intern levering av giroer etc til',
		labelWidth: 180,
		width: 500,
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
		typeAhead: true
		
//		validator: function(v) {}
	});
	
	
	var regning_til_objekt = Ext.create('Ext.form.field.Checkbox', {
		fieldLabel: 'Levering på døra',
		inputValue: 1,
		uncheckedValue: 0,
		name: 'regning_til_objekt',
		width: 200,
		
		listeners: {
			change: function( radiobutton, newValue, oldValue, eOpts ) {
				if(newValue) {
					regningsobjekt.enable();
					radiogruppe.disable();
					egenAdresse.disable();
				}
				else {
					regningsobjekt.disable();
					radiogruppe.enable();
					egenAdresse.enable();
				}
			}
		}

	});
	
	var egenAdresse = Ext.create('Ext.form.field.Radio', {
		boxLabel: 'Oppgi en uavhengig regningsadresse for leieforholdet',
		inputValue: '0',
		name: 'regningsperson',
		checked: false,
		listeners: {
			change: function( radiobutton, newValue, oldValue, eOpts ) {
				if(newValue) {
					regningsadresse.expand();
				}
				else {
					regningsadresse.collapse();
				}
			}
		}
	});

	var regningsadresse = Ext.create('Ext.form.FieldSet', {
		collapsible: true,
		collapsed: true,
		width: 300,
		border: true,
		title: 'Send til egen regningsadresse',
		autoHeight: true,
		defaultType: 'textfield',
		items: [
			{
				xtype: 'textfield',
				fieldLabel: 'Adresse',
				labelAlign: 'right',
				name: 'regningsadresse1',
				value: '<?php echo addslashes($leieforhold->hent('regningsadresse1'));?>'
			},			
			{
				xtype: 'textfield',
				fieldLabel: '',
				labelSeparator: '',
				labelAlign: 'right',
				name: 'regningsadresse2',
				value: '<?php echo addslashes($leieforhold->hent('regningsadresse2'));?>'
			},			
			{
				xtype: 'textfield',
				fieldLabel: 'Postnr',
				labelAlign: 'right',
				name: 'postnr',
				value: '<?php echo addslashes($leieforhold->hent('postnr'));?>'
			},			
			{
				xtype: 'textfield',
				fieldLabel: 'Sted',
				labelAlign: 'right',
				name: 'poststed',
				value: '<?php echo addslashes($leieforhold->hent('poststed'));?>'
			},			
			{
				xtype: 'textfield',
				fieldLabel: 'Land',
				labelAlign: 'right',
				name: 'land',
				value: '<?php echo addslashes($leieforhold->hent('land'));?>'
			}
		]
	});

	regning_til_objekt.setValue(<?php echo (int)$leieforhold->hent('regning_til_objekt');?>);
	
	var skjema = Ext.create('Ext.form.Panel', {
		renderTo:		'panel',
		frame:			true,
		title:			'Giroadresse / levering',
		bodyStyle:		'padding:5px 5px 0',
		standardSubmit:	false,
		autoScroll:		true,
		width:			900,
		height:			500,
		items: [
		
			{
				xtype:	'container',
				layout:	'column',
				items: [
					{
						xtype:	'container',
						items: [
							regning_til_objekt
						]
					},
					{
						xtype:	'container',
						items: [
							regningsobjekt
						]
					}
				]
			},
			radiogruppe,
			{
				xtype:	'container',
				layout: 'column',
				items: [
				{
					xtype:	'container',
					columnWidth: 0.4,
					items: [
						egenAdresse
					]
				},
				{
					xtype:	'container',
					columnWidth: 0.6,
					items: [
						regningsadresse
					]
				}
				]
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
			text: 'Avbryt',
			handler: function() {
				window.location = '<?php echo $this->returi->get();?>';
			}
		}, {
			scale: 'medium',
			text: 'Lagre',
			handler: function() {
				if( skjema.isValid() ) {
					skjema.getForm().submit({
						url: 'index.php?oppslag=leieforhold_leveringsadresse&oppdrag=taimotskjema&id=<?php echo $leieforhold;?>oppdrag=taimotskjema'
					});
				}
			}
		}]
	});
	
	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Fikk ingen JSON-formatert respons fra tjeneren');
				} else {
					window.location = '<?php echo $this->returi->get();?>';
				}
			}
		},
							

		actionfailed: function(form,action){
			if(action.type == 'submit') {
				var result = Ext.decode(action.response.responseText); 
				if(result && result.msg) {			
					Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', result.msg);
				}
				else {
					Ext.MessageBox.alert(
					'Problem:',
					'Lagring av data mislyktes av ukjent grunn.'
					);
				}
			}
			
		}
	});

	leieobjektliste.load({
		callback: function(records, operation, success) {
			regningsobjekt.setValue(<?php echo "$regningsobjekt" ? "$regningsobjekt" : "$leieobjekt";?>);
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

	$resultat = (object)array(
		'success'	=> false,
		'msg'		=> "",
		'data'		=> array()
	);


	switch ($data) {
	
	case "leieobjektliste":
		$query = @$_GET['query'];

		$resultat = (object)array(
			'success'	=> true,
			'msg'		=> "",
			'data'		=> array()
		);

		// Hent alle leieobjektene som passer til søkefeltet
		$filter = "1\n";
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
			
			$resultat->data[] = (object)array(
				'leieobjektnr'				=> (int)$leieobjekt->hent('id'),
				'beskrivelse'				=> $beskrivelse
			);		}
				
		return (json_encode($resultat));
		break;
		
	default:
		return json_encode($this->arrayData($this->hoveddata));
		
	}
}



function taimotSkjema() {
	$tp = $this->mysqli->table_prefix;
	$leieforhold = $this->hent('Leieforhold', (int)$_GET['id']);
	$regningsperson = $this->hent('Person', (int)@$_POST['regningsperson']);
	$regningsobjekt = $this->hent('Leieobjekt', (int)$_POST['regningsobjekt']);
	$regning_til_objekt = (bool)$_POST['regning_til_objekt'];
	$leietakere = $leieforhold->hent('leietakere');
	
	$resultat = (object)array(
		'success'	=> true,
		'msg'		=> ""
	);
	foreach($_POST as $egenskap => $verdi) {
		switch($egenskap) {
		case "regningsperson";
		case "regningsobjekt";
		case "regning_til_objekt";
		case "regningsadresse1";
		case "regningsadresse2";
		case "postnr";
		case "poststed";
		case "land";
			$resultat->success = $leieforhold->sett($egenskap, $verdi);
			break;
		}
	}
	
	// Dersom regningspersonen også har tidligere har vært leietaker,
	//	oppdateres leveringsadresse for disse leieforholdene.
	if($regning_til_objekt) {
		foreach( $this->mysqli->arrayData(array(
			'source'	=> "{$tp}kontrakter as kontrakter INNER JOIN {$tp}kontraktpersoner AS kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt AND kontraktpersoner.slettet IS NULL",
			'where'		=> "kontrakter.regningsperson = '" . implode("' OR kontrakter.regningsperson = '", $leietakere) . "'",
			'fields'	=> "leieforhold as id",
			'distinct'	=> true,
			'class'		=> "Leieforhold"
		))->data as $tidligereLeieforhold)
		{
			$tidligereLeieforhold->sett( 'regningsobjekt', $regningsobjekt);
			$tidligereLeieforhold->sett( 'regning_til_objekt', true);
			$tidligereLeieforhold->sett( 'frosset', false );
		}
	}


	echo json_encode($resultat);
	return;
}



}
?>