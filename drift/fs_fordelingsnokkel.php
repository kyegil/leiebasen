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
	if(!$id = @$_GET['id']) die("Ugyldig oppslag: ID ikke angitt for fordelingsnøkkel");
}



function skript() {
	if(@$_GET['returi'] == "default") {
		$this->returi->reset();
	}
	$this->returi->set();
	
	$tp 			= $this->mysqli->table_prefix;
	$id				= $_GET['id'];
	$anleggsnummer	= @$_GET['anleggsnummer'];
?>
Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?php echo $this->http_host . "/" . $this->ext_bibliotek . "/examples/ux";?>');

Ext.require([
 	'Ext.data.*',
 	'Ext.form.*'
]);

Ext.onReady(function() {

    Ext.tip.QuickTipManager.init();
	Ext.form.Field.prototype.msgTarget = 'title';
	Ext.Loader.setConfig({enabled:true});
	
<?php include_once("_menyskript.php");?>


	function velgBeregning() {
		if(fordelingsmåte.value == "Prosentvis") {
			andeler.disable();
			prosentsats.enable();
			fastbeløp.disable();
		}
		if(fordelingsmåte.value == "Andeler") {
			andeler.enable();
			prosentsats.disable();
			fastbeløp.disable();
		}
		if(fordelingsmåte.value == "Fastbeløp") {
			andeler.disable();
			prosentsats.disable();
			fastbeløp.enable();
		}
		
		if(følger_leieobjekt.value != 0) {
			leieforhold.disable();
			leieobjekt.enable();
		}
		else{
			leieobjekt.disable();
			leieforhold.enable();
		}
	}

	var anleggsnummer = Ext.create('Ext.form.field.ComboBox', {
		fieldLabel: 'Anleggsnummer',
		hideLabel: false,
		name: 'anleggsnummer',
		tabIndex: 1,
		width: 750,
		matchFieldWidth: false,
		listConfig: {
			width: 700
		},

		store: Ext.create('Ext.data.Store', {
			storeId: 'anleggsnummer',
		
			autoLoad: true,
			proxy: {
				type: 'ajax',
				url: "index.php?oppslag=fs_fordelingsnokkel&oppdrag=hentdata&data=anleggsnummer&id=<?php echo $id;?>",
				reader: {
					type: 'json',
					root: 'data',
					idProperty: 'leieforhold'
				}
			},
			
			fields: [
				{name: 'anleggsnummer'},
				{name: 'anlegg'}
			]
		}),
		queryMode: 'remote',
		displayField: 'anlegg',
		valueField: 'anleggsnummer',
		minChars: 0,
		queryDelay: 1000,

		readOnly: 	<?php echo (($id != '*' or $anleggsnummer) ? "true" : "false");?>,
		<?php echo ( $anleggsnummer ? "value: '{$anleggsnummer}',\n" : "");?>
		allowBlank: true,
		typeAhead: false,
		editable: true,
		selectOnFocus: true,
		forceSelection: false
	});
	
	<?php if($id == '*'):?>

	<?php else:?>
	
	<?php endif;?>
	

	

	var fordelingsmåte = Ext.create('Ext.form.field.ComboBox', {
		fieldLabel: 'Fordelingsmåte',
		hideLabel: false,
		name: 'fordelingsmåte',
		tabIndex: 2,
		width: 750,
		matchFieldWidth: false,
		listConfig: {
			width: 700
		},

		listeners: {
			change: velgBeregning,
			select: velgBeregning
		},

		store: Ext.create('Ext.data.Store', {
			fields: [
				{name: 'verdi'},
				{name: 'visning'}
			],
			data : [
				{
					verdi:		'Fastbeløp',
					visning:	'Beløpet beregnes manuelt og kreves inn for hver enkelt faktura'
				},
				{
					verdi:		'Prosentvis',
					visning:	'Prosentandel beregnes av hver faktura (Beregnes etter fradrag av manuelle beløp)'
				},
				{
					verdi:		'Andeler',
					visning:	'Brøk-andeler av hver faktura, f.eks. 1 av x like store deler (Evt. andre beregninger trekkes fra først)'
				}
			]

		}),
		queryMode: 'local',
		displayField: 'visning',
		valueField: 'verdi',
		minChars: 0,

		allowBlank: false,
		typeAhead: false,
		editable: true,
		selectOnFocus: true,
		forceSelection: true
	});


	var følger_leieobjekt = Ext.create('Ext.form.field.ComboBox', {
		fieldLabel: 'Automatisk videreføring',
		hideLabel: false,
		name: 'følger_leieobjekt',
		tabIndex: 3,
		width: 750,
		matchFieldWidth: false,
		listConfig: {
			width: 700
		},

		listeners: {
			change: velgBeregning,
			select: velgBeregning
		},

		store: Ext.create('Ext.data.Store', {
			fields: [
				{name: 'verdi'},
				{name: 'forklaring'}
			],
			data : [
				{
					verdi:		'1',
					forklaring:	'Dette elementet følger leieobjektet og overføres automatisk til nye leietakere'
				},
				{
					verdi:		'0',
					forklaring:	'Dette elementet gjelder kun angitt leieforhold'
				}
			]

		}),
		queryMode: 'local',
		displayField: 'forklaring',
		valueField: 'verdi',
		minChars: 0,

		allowBlank: false,
		typeAhead: false,
		editable: true,
		selectOnFocus: true,
		forceSelection: true
	});


	var leieobjekt = Ext.create('Ext.form.field.ComboBox', {
		fieldLabel: 'Leieobjekt',
		hideLabel: false,
		name: 'leieobjekt',
		tabIndex: 4,
		width: 750,
		matchFieldWidth: false,
		listConfig: {
			width: 700
		},

		store: Ext.create('Ext.data.JsonStore', {
			storeId: 'leieobjektliste',
		
			autoLoad: true,
			proxy: {
				type: 'ajax',
				url: "index.php?oppslag=fs_fordelingsnokkel&oppdrag=hentdata&data=leieobjekter&id=<?php echo $id;?>",
				reader: {
					type: 'json',
					root: 'data',
					idProperty: 'leieobjekt'
				}
			},
			
			fields: [
				{name: 'id'},
				{name: 'visning'}
			]
		}),
		queryMode: 'remote',
		displayField: 'visning',
		valueField: 'id',
		minChars: 0,
		queryDelay: 1000,

		allowBlank: true,
		typeAhead: false,
		editable: true,
		selectOnFocus: true,
		forceSelection: true
	});


	var leieforhold = Ext.create('Ext.form.field.ComboBox', {
		fieldLabel: 'Leieforhold',
		hideLabel: false,
		name: 'leieforhold',
		tabIndex: 5,
		width: 750,
		matchFieldWidth: false,
		listConfig: {
			width: 700
		},

		store: Ext.create('Ext.data.JsonStore', {
			storeId: 'leieforholdliste',
		
			autoLoad: true,
			proxy: {
				type: 'ajax',
				url: "index.php?oppslag=fs_fordelingsnokkel&oppdrag=hentdata&data=leieforhold&id=<?php echo $id;?>",
				reader: {
					type: 'json',
					root: 'data',
					idProperty: 'leieforhold'
				}
			},
			
			fields: [
				{name: 'id'},
				{name: 'visning'}
			]
		}),
		queryMode: 'remote',
		displayField: 'visning',
		valueField: 'id',
		minChars: 0,
		queryDelay: 1000,

		allowBlank: true,
		typeAhead: false,
		editable: true,
		selectOnFocus: true,
		forceSelection: true
	});


	var andeler = Ext.create('Ext.form.field.Number', {
		fieldLabel:		'Andeler',
		labelAlign:		'top',
		name:			'andeler',

		allowBlank:			true,
		allowDecimals:		false,
		decimalSeparator:	',',
		decimalPrecision:	2,
		hideTrigger:		true,
		width:				190,
		tabIndex:			6
	});


	var prosentsats = Ext.create('Ext.form.field.Number', {
		fieldLabel:			'Prosentsats',
		labelAlign:			'top',
		name:				'prosentsats',

		allowBlank:			true,
		allowDecimals:		true,
		minValue:			0,
		decimalPrecision:	1,
		decimalSeparator:	',',
		hideTrigger:		true,
		width:				190,
		tabIndex:			7,
		renderer: function(value){
			return (value * 100).toFixed(1).replace(".", ",") + "%";
		}
	});


	var fastbeløp = Ext.create('Ext.form.field.Number', {
		fieldLabel:	'Standardbeløp ved neste fordeling',
		labelAlign:	'top',
		name:		'fastbeløp',

		allowBlank:			true,
		allowDecimals:		false,
		minValue:			0,
		decimalPrecision:	2,
		decimalSeparator:	',',
		hideTrigger:		true,
		width:				190,
		tabIndex:			8,
		renderer:	Ext.util.Format.noMoney
	});


	var forklaring = Ext.create('Ext.form.field.TextArea', {
		fieldLabel:	'Forklaring / beskrivelse av dette fordelingsnøkkel-elementet',
		name:		'forklaring',
		width:		750
	});


	var lagreknapp = Ext.create('Ext.Button', {
		text: 'Lagre',
		disabled: true,
		handler: function() {
			skjema.form.submit({
				url:'index.php?oppslag=<?php echo $_GET["oppslag"];?>&id=<?php echo $id;?>&oppdrag=taimotskjema',
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


	var skjema = Ext.create('Ext.form.Panel', {
		autoScroll: true,
		bodyPadding: 5,
		renderTo: 'panel',
		height: 500,
		width: 900,
		buttonAlign: 'right',
		frame: true,
		title: 'Fordelingsnøkkel for beregning av fellesstrømandel',

		fieldDefaults: {
			labelAlign: 'left',
			width: 200
		},

		items: [
			{
				xtype: 'displayfield',
				value: 'Element nr. <?php echo $id;?>'
			},
			anleggsnummer,
			fordelingsmåte,
			følger_leieobjekt,
			leieobjekt,
			leieforhold,
			{
				xtype: 'container',
				layout: {
					type: 'hbox', // Ext.layout.container.HBox
					align: 'top' // Vertical alignment (top | middle | bottom | stretch | stretchmax)
				},

				defaults: {
					margin: '0 5'
				},
				items: [
					andeler,
					prosentsats,
					fastbeløp
				]
			},
			forklaring
		],
		
		buttons: [
			{
				text: 'Tilbake',
				handler: function() {
					window.location = '<?php echo $this->returi->get();?>';
				}
			},
			{
				text: 'Slett',
				handler: function() {
					Ext.Ajax.request({
						waitMsg: 'Sletter...',
						url: "index.php?oppslag=fs_fordelingsnokkel&oppdrag=oppgave&oppgave=slett&id=<?php echo $id;?>",
						failure:function(response,options){
							Ext.MessageBox.alert('Whoops! Problemer...','Oppnår ikke kontakt med databasen! Prøv igjen senere.');
						},
						success:function(response,options){
							var tilbakemelding = Ext.util.JSON.decode(response.responseText);
							if(tilbakemelding['success'] == true) {
								Ext.MessageBox.alert('Slettet', tilbakemelding.msg, function(){
									window.location = '<?php echo $this->returi->get();?>';
								});
							}
							else {
								Ext.MessageBox.alert('Hmm..',tilbakemelding['msg']);
							}
						}
					});
				}
			},
			lagreknapp
		]
	});

	if( <?php echo $id != '*' ? "true" : "false" ;?> ) {
		skjema.getForm().load({
			success: function() {
				lagreknapp.enable();
				velgBeregning();
			},
			url: 'index.php?oppslag=fs_fordelingsnokkel&anleggsnummer=<?php echo $anleggsnummer;?>&id=<?php echo $id;?>&oppdrag=hentdata',
			waitMsg: 'Henter opplysninger...'
		});
	}
	else {
		lagreknapp.enable();
	}

	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Form submit returned an empty string instead of json');
				} else {
					window.location = '<?php echo $this->returi->get();?>';
					Ext.MessageBox.alert('Suksess', 'Opplysningene er oppdatert');
				}
			}
		},
							
		actionfailed: function(form,action){
			if(action.type == 'load') {
				if (action.failureType == "connect") {
					Ext.MessageBox.alert('Problem:', 'Klarte ikke laste data. Fikk ikke kontakt med tjeneren.');
				}
				else {
					if (action.response.responseText == '') {
						Ext.MessageBox.alert('Problem:', 'Skjemaet mottok ikke data i JSON-format som forventet');
					}
					else {
						var result = Ext.decode(action.response.responseText);
						if(result && result.msg) {			
							Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', result.msg);
						}
						else {
							Ext.MessageBox.alert('Problem:', 'Innhenting av data mislyktes av ukjent grunn. (trolig manglende success-parameter i den returnerte datapakken). Action type='+action.type+', failure type='+action.failureType);
						}
					}
				}
			}
			if(action.type == 'submit') {
				if (action.failureType == "connect") {
					Ext.MessageBox.alert('Problem:', 'Klarte ikke lagre data. Fikk ikke kontakt med tjeneren.');
				}
				else {	
					var result = Ext.decode(action.response.responseText);
					if(result && result.msg) {			
						Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', result.msg);
					}
					else {
						Ext.MessageBox.alert('Problem:', 'Lagring av data mislyktes av ukjent grunn. Action type='+action.type+', failure type='+action.failureType);
					}
				}
			}
			
		} // end actionfailed listener
	}); // end skjema.on


});
<?php
}

function design() {
?>
<div id="panel"></div>
<?php
}


function hentData($data = "") {
	$tp = $this->mysqli->table_prefix;

	$id = @$_GET['id'];

	switch ($data) {

	case "anleggsnummer": {
		$query = @$this->GET['query'];
		
		$resultat = (object)array(
			'success'	=> true,
			'data'		=> array()
		);

		$resultat = $this->mysqli->arrayData(array(
			'source'	=> "{$tp}fs_fellesstrømanlegg as fs_fellesstrømanlegg",
			'fields'	=>	"anleggsnummer, CONCAT(anleggsnummer, ' | ', formål) AS anlegg",
			'where'		=>	"fs_fellesstrømanlegg.anleggsnummer LIKE '%{$query}%'
							OR fs_fellesstrømanlegg.formål LIKE '%{$query}%'",
			'distinct'	=> true
		));
		
		return json_encode($resultat);
		break;
	}

	case "leieobjekter": {
		$query = @$_GET['query'];
		
		$resultat = (object)array(
			'success'	=> true,
			'msg'		=> "",
			'data'		=> array()
		);

		// Hent alle leieobjektene som passer til søkefeltet
		$filter = "";
		if( $query ) {
			$filter	= "(leieobjektnr LIKE '" . (int)$query . "' OR bygning LIKE '%{$query}%' OR navn LIKE '%{$query}%' OR gateadresse LIKE '%{$query}%' OR beskrivelse LIKE '%{$query}%')";
		}
		
		$leieobjektsett =	$this->mysqli->arrayData(array(
			'source'	=> "{$tp}leieobjekter AS leieobjekter",
			'fields'	=> "leieobjektnr AS id",
			'orderfields'	=> "CONVERT(leieobjektnr, SIGNED)",
			'class'		=> "Leieobjekt",
			'where'		=> $filter
		));
		
		foreach($leieobjektsett->data as $leieobjekt) {

			$resultat->data[] = (object)array(
				'id'		=> strval($leieobjekt->hent('id')),
				'visning'	=> "{$leieobjekt}: {$leieobjekt->hent('beskrivelse')}"
			);

		}
		
		return (json_encode($resultat));
		break;
	}
		
	case "leieforhold": {
		$tp = $this->mysqli->table_prefix;
		$query = @$_GET['query'];
		
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
				'id'		=> $leieforhold->hentId(),
				'visning'	=> "{$leieforhold->hentId()}: {$leieforhold->hent('beskrivelse')}"
			);
		}
		
		return json_encode($resultat);
		break;
	}

	default: {
		$resultat = $this->mysqli->arrayData(array(
			'source'	=> "{$tp}fs_fordelingsnøkler AS fs_fordelingsnøkler",
			'where'		=> "nøkkel = " . (int)$id,
			'fields'	=> "nøkkel, anleggsnummer, fordelingsmåte, følger_leieobjekt, leieobjekt, leieforhold, andeler, ROUND(prosentsats * 100, 4) AS prosentsats, fastbeløp, forklaring"
		));
		
		foreach( $resultat->data as $verdi ) {
			$verdi->nevner = 0;
			$b = $this->mysqli->arrayData(array(
				'source'	=> "{$tp}fs_fordelingsnøkler AS fs_fordelingsnøkler",
				'where'		=> "fordelingsmåte='Andeler' AND anleggsnummer = '" . $verdi->anleggsnummer . "'"
			));
			
			foreach( $b->data as $nokkel ) {
				if( !$nokkel->følger_leieobjekt ) {
					$verdi->nevner += $nokkel->andeler;
				}
				else {
					$verdi->nevner += ($nokkel->andeler * max(count($this->dagensBeboere($nokkel->leieobjekt)), 1));
				}
			}
		}

		$resultat->data = reset( $resultat->data );		
		return json_encode($resultat);
	}

	}
}


function taimotSkjema() {
	$tp = $this->mysqli->table_prefix;
	$resultat = (object)array(
		'success'	=> false,
		'msg'		=> ""
	);

	$id					= $_GET['id'];
	$anleggsnummer		= (int)@$_POST['anleggsnummer'];
	$fordelingsmåte		= @$_POST['fordelingsmåte'];
	$andeler			= (int)@$_POST['andeler'];
	$prosentsats		= str_replace(',', '.', @$_POST['prosentsats']) /100;
	$fastbeløp			= floatval(str_replace(',', '.', @$_POST['fastbeløp']));
	$følger_leieobjekt	= (bool)@$_POST['følger_leieobjekt'];
	$leieobjekt			= (int)@$_POST['leieobjekt'];
	$leieforhold		= (int)@$_POST['leieforhold'];
	$forklaring			= @$this->POST['forklaring'];

	if(!$anleggsnummer) {
		$resultat->success = false;
		$resultat->msg = "Anleggsnummer mangler";
	}
	else if($fordelingsmåte == 'Andeler' and !$andeler) {
		$resultat->success = false;
		$resultat->msg = "Andelen manglet";
	}
	else if($fordelingsmåte == 'Prosentvis' and !$prosentsats) {
		$resultat->success = false;
		$resultat->msg = "Prosentsatsen manglet";
	}
	else if($følger_leieobjekt and !$leieobjekt) {
		$resultat->success = false;
		$resultat->msg = "Leieobjekt er ikke oppgitt";
	}
	else if(!$følger_leieobjekt and !$leieforhold){
		$resultat->success = false;
		$resultat->msg = "Leieforhold er ikke oppgitt";
	}
	else {
		$lagring = array(
			'table'		=> "{$tp}fs_fordelingsnøkler",
			'fields'	=> array(
				'anleggsnummer'			=> $anleggsnummer,
				'fordelingsmåte'		=> $fordelingsmåte,
				'følger_leieobjekt'		=> $følger_leieobjekt,
				'forklaring'			=> $forklaring
			)
		);
		
		if($id == '*') {
			$lagring['update']	= false;
			$lagring['insert']	= true;
		}
		else {
			$lagring['update']	= true;
			$lagring['insert']	= false;
			$lagring['where']	= "nøkkel = '" . (int)$id . "'";
		}
		
		if($følger_leieobjekt) {
			$lagring['fields']['leieobjekt'] = $leieobjekt;
		}
		else {
			$lagring['fields']['leieforhold'] = $leieforhold;
		}

		switch($fordelingsmåte) {
			case "Fastbeløp": {
				$lagring['fields']['fastbeløp'] = $fastbeløp;
				break;
			}
			case "Prosentvis": {
				$lagring['fields']['prosentsats'] = $prosentsats;
				break;
			}
			case "Andeler": {
				$lagring['fields']['andeler'] = $andeler;
				break;
			}
		}
		
		$resultat = $this->mysqli->saveToDb( $lagring );
	}
	echo json_encode($resultat);
}


function oppgave($oppgave){
	switch($oppgave){
	case "slett":
		$sql = "DELETE FROM fs_fordelingsnøkler WHERE nøkkel = '" . (int)$_GET['id'] . "'";
		if($this->mysqli->query($sql)){
			$resultat['success'] = true;
		}
		else{
			$resultat['success'] = false;
			$resultat['msg'] = "Klarte ikke slette dette elementet.<br /><br />Tilbakemeldinga fra databasen lyder:<br />" . $this->mysqli->error;
		}
		echo json_encode($resultat);	
		break;
	}
}


}
?>