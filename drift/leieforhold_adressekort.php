<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Registrer nytt leieforhold - Trinn 4 Opplysninger om leietaker';
public $ext_bibliotek = 'ext-4.2.1.883';


function __construct() {
	parent::__construct();
}

function skript() {
	if(@$_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	$tp = $this->mysqli->table_prefix;

	$leieforhold = $this->hent( 'Leieforhold', (int)$_GET['id'] );
	$leietakere = $leieforhold->hent( 'leietakere' );
	$leieobjekt = $leieforhold->hent( 'leieobjekt' );
	$objektadresse = (object)array(
		'adresse1'	=> (
			$leieobjekt->hent('navn') ? $leieobjekt->hent('navn') : $leieobjekt->hent('gateadresse')
		),
		'adresse2'	=> (
			$leieobjekt->hent('navn') ? $leieobjekt->hent('gateadresse') : ""
		),
		'postnr'	=> $leieobjekt->hent('postnr'),
		'poststed'	=> $leieobjekt->hent('poststed'),
		'land'		=> "Norge"
	);
	
	$kort = array();
	
	foreach( $leietakere as $person ) {
		$kort[] = (object)array(
			'personid'		=> $person->hent('id'),
			'org'			=> $person->hent('org'),
			'fornavn'		=> $person->hent('fornavn'),
			'etternavn'		=> $person->hent('etternavn'),
			'navn'			=> $person->hent('navn'),
			'fødselsdato'	=> (
									$person->hent('fødselsdato')
									? $person->hent('fødselsdato')->format('d.m.Y')
									: ""
			),
			'personnr'		=> $person->hent('personnr'),
			'orgNr'			=> $person->hent('orgNr'),
			'adresse1'		=> $person->hent('adresse1'),
			'adresse2'		=> $person->hent('adresse2'),
			'postnr'		=> $person->hent('postnr'),
			'poststed'		=> $person->hent('poststed'),
			'land'			=> $person->hent('land'),
			'telefon'		=> $person->hent('telefon'),
			'mobil'			=> $person->hent('mobil'),
			'epost'			=> $person->hent('epost')
		);
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

	var hjelp = Ext.create('Ext.window.Window', {
		title: 'Veiledning',
		width: 700,
		height: 400,
		autoScroll: true,
		animCollapse: true,
		closeAction: 'hide',
		html: "<b>Adressekort:</b><br />Fyll ut kontaktinfo som adresse, telefonnummer og epostadresse, og evt personnummeret.<br /><b>Personnummer:</b><br />Dersom personnummeret skal oppgis så skal bare de siste fem sifferene fylles inn, sammen med fødselsdato.<br /><br /><b>Epostadresse:</b><br />Dersom personene senere på egen hånd skal kunne opprette en brukerprofil for å se betalingsstatus, giroer etc. på nett, må epostadresse oppgis. <br />Alternativt kan alle med adgang til drift gi beboere adgang til beboersidene uten epostadresse."
	});

<? foreach($kort as $a => $info):?>

	var er_org<?php echo $info->personid;?> = Ext.create('Ext.form.field.Checkbox', {
		boxLabel:	'Bedrift-/organisasjon',
		name:		'er_org<?php echo $info->personid;?>',
		inputValue:	1,
		uncheckedValue: 0,
		listeners: {
			change: function( checkbox, newValue, oldValue, eOpts ) {
				if( newValue ) {
					fornavn<?php echo $info->personid;?>.disable();
					etternavn<?php echo $info->personid;?>.setFieldLabel('Navn');
					fødselsdato<?php echo $info->personid;?>.disable();
					personnr<?php echo $info->personid;?>.setFieldLabel('Org. nr.');
				}
				else {
					fornavn<?php echo $info->personid;?>.enable();
					etternavn<?php echo $info->personid;?>.setFieldLabel('Etternavn');
					fødselsdato<?php echo $info->personid;?>.enable();
					personnr<?php echo $info->personid;?>.setFieldLabel('Personnummer');
				}
			}
		}
	});

	var fornavn<?php echo $info->personid;?> = Ext.create('Ext.form.field.Text', {
		fieldLabel:	'Fornavn',
		name:		'fornavn<?php echo $info->personid;?>',
		value:		'<?php echo addslashes($info->fornavn);?>'
	});

	var etternavn<?php echo $info->personid;?> = Ext.create('Ext.form.field.Text', {
		fieldLabel:	'Etternavn',
		name:		'etternavn<?php echo $info->personid;?>',
		value:		'<?php echo addslashes($info->etternavn);?>'
	});

	var fødselsdato<?php echo $info->personid;?> = Ext.create('Ext.form.field.Date', {
		fieldLabel:		'Fødselsdato',
		name:			'fødselsdato<?php echo $info->personid;?>',
		format:			'd.m.Y',
		submitFormat:	'Y-m-d',
		value: 			'<?php echo $info->fødselsdato;?>'
	});

	var personnr<?php echo $info->personid;?> = Ext.create('Ext.form.field.Text', {
		fieldLabel:		'Personnr',
		name:			'personnr<?php echo $info->personid;?>',
		value:			'<?php echo addslashes($info->personnr);?>'
	});

	var adressea<?php echo $info->personid;?> = Ext.create('Ext.form.field.Text', {
			fieldLabel:	'Adresse',
			name:		'adressea<?php echo $info->personid;?>',
			value:		'<?php echo $info->adresse1;?>'
	});

	var adresseb<?php echo $info->personid;?> = Ext.create('Ext.form.field.Text', {
			hideLabel:	true,
			name:		'adresseb<?php echo $info->personid;?>',
			value:		'<?php echo $info->adresse2;?>'
	});

	var postnr<?php echo $info->personid;?> = Ext.create('Ext.form.field.Text', {
			fieldLabel:	'Postnr',
			name:		'postnr<?php echo $info->personid;?>',
			value:		'<?php echo $info->postnr;?>'
	});

	var poststed<?php echo $info->personid;?> = Ext.create('Ext.form.field.Text', {
			fieldLabel:	'Poststed',
			name:		'poststed<?php echo $info->personid;?>',
			value:		'<?php echo $info->poststed;?>'
	});

	var land<?php echo $info->personid;?> = Ext.create('Ext.form.field.Text', {
			fieldLabel:	'Land',
			name:		'land<?php echo $info->personid;?>',
			value:		'<?php echo $info->land;?>'
	});

	var postnr<?php echo $info->personid;?> = Ext.create('Ext.form.field.Text', {
			fieldLabel:	'Postnr',
			name:		'postnr<?php echo $info->personid;?>',
			value:		'<?php echo $info->postnr;?>'
	});

<? endforeach;?>


	var skjema = Ext.create('Ext.form.Panel', {
		renderTo:		'panel',
		frame:			true,
		title:			'Registrer adresseopplysninger for leieforhold <?php echo $leieforhold;?>',
		bodyStyle:		'padding:5px 5px 0',
		standardSubmit:	false,
		autoScroll:		true,
		width:			900,
		height:			500,
		items:	[
		
			{
				xtype: 'container',
				layout: 'column',
				items: [
<? foreach($kort as $a => $info):?>
			
				{
					xtype: 'container',
					layout: 'form',
					margin: 5,
					autoScroll: true,
					defaults: {
					},
					items: [
						er_org<?php echo $info->personid;?>,
						fornavn<?php echo $info->personid;?>,
						etternavn<?php echo $info->personid;?>,
						fødselsdato<?php echo $info->personid;?>,
						personnr<?php echo $info->personid;?>,
						adressea<?php echo $info->personid;?>,
						adresseb<?php echo $info->personid;?>,
						postnr<?php echo $info->personid;?>,
						poststed<?php echo $info->personid;?>,
						{
							xtype:		'button',
							scale:		'medium',
							width:		'100%',
							text:		'Bruk leieobjektets adresse',
							handler: function() {
								adressea<?php echo $info->personid;?>
									.setValue('<?php echo $objektadresse->adresse1?>');
								adresseb<?php echo $info->personid;?>
									.setValue('<?php echo $objektadresse->adresse2?>');
								postnr<?php echo $info->personid;?>
									.setValue('<?php echo $objektadresse->postnr?>');
								poststed<?php echo $info->personid;?>
									.setValue('<?php echo $objektadresse->poststed?>');
								land<?php echo $info->personid;?>
									.setValue('<?php echo $objektadresse->land?>');
							}
						},
						{
							xtype:		'textfield',
							fieldLabel:	'Telefon',
							name:		'telefon<?php echo $info->personid;?>',
							itemId:		'telefon<?php echo $info->personid;?>',
							value:		'<?php echo $info->telefon;?>'
						},
						{
							xtype:		'textfield',
							fieldLabel:	'Mobil',
							name:		'mobil<?php echo $info->personid;?>',
							itemId:		'mobil<?php echo $info->personid;?>',
							value:		'<?php echo $info->mobil;?>'
						},
						{
							xtype:		'textfield',
							fieldLabel:	'Epost',
							name:		'epost<?php echo $info->personid;?>',
							itemId:		'epost<?php echo $info->personid;?>',
							value:		'<?php echo $info->epost;?>'
						}
					],
					width: 220
				}<?php echo $a < (count($kort) - 1) ? "," : "";?>

<?php endforeach;?>

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
						url: 'index.php?oppslag=leieforhold_adressekort&oppdrag=taimotskjema'
					});
				}
			}
		}]
	});
	
	
<? foreach($kort as $a => $info):?>

	er_org<?php echo $info->personid;?>.setValue('<?php echo (int)$info->org?>');

<?php endforeach;?>


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
	
	default:
		return json_encode($this->arrayData($this->hoveddata));
		
	}
}



function taimotSkjema() {
	$tp = $this->mysqli->table_prefix;
	$adressekort = array();
	$resultat = (object)array(
		'success'	=> true,
		'msg'		=> ""
	);
	
	foreach($_POST as $attributt => $verdi) {
		preg_match('/^([a-zæøå_]+)([0-9]+)/i', $attributt, $treff);
		$egenskap = $treff[1];
		$id = (int)$treff[2];
		
		if( $egenskap == "adressea" ) {
			$egenskap = "adresse1";
		}
		if( $egenskap == "adresseb" ) {
			$egenskap = "adresse2";
		}

		if( !is_a(@$adressekort[$id], 'Person' ) ) {
			$adressekort[$id] = $this->hent('Person', $id);
		}
		$resultat->success &= $adressekort[$id]->sett($egenskap, $verdi);
	}
	
	echo json_encode($resultat);
	return;
}



}
?>