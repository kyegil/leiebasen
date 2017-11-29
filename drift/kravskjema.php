<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

if(!defined('LEGAL')) die('No access!<br />Check your URI.');

class oppsett extends leiebase {

public $tittel = 'leiebasen';
public $ext_bibliotek = 'ext-4.2.1.883';
	
	

function __construct() {
	parent::__construct();
	if(!$id = $this->GET['id']) die("Ugyldig oppslag: ID ikke angitt for kravet");
}



function skript() {
	if(@$_GET['returi'] == "default") {
		$this->returi->reset();
	}
	$this->returi->set();
	$tp 			= $this->mysqli->table_prefix;
	$id				= $_GET['id'];
	$krav			= $this->hent('Krav', $_GET['id']);
	
	$kreditt = false;
	
	if($krav->hentId()) {
		$leieforhold = $krav->hent('leieforhold');
		$kreditt = $krav->hent('beløp') < 0 ? true : false;
	}
	else {
		$leieforhold	= $this->hent('Leieforhold', @$_GET['leieforhold']);
	}
	if((!$krav->hentId() and $_GET['id'] != '*') or !$leieforhold->hentId()) {
		echo "window.location = '{$this->returi->get()}'";
		return;
	}		
	
	$delkravtyper = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}delkravtyper AS delkravtyper",
		'where'			=> "aktiv AND !selvstendig_tillegg",
		'orderfields'	=> "orden, id"
	))->data;
	
	$låst = ( $krav->hentId() and strval($krav->hent('giro')) );



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
	
<?php
	include_once("_menyskript.php");
?>

	var aktiverFelter = function() {

		if(type.getValue() == "Husleie") {
			andel.enable();
			anleggsnr.disable();
		}
		else {
			andel.disable();
		}
		if(type.getValue() == "Fellesstrøm") {
			anleggsnr.enable();
		}
		else {
			anleggsnr.disable();
		}

		<?php foreach($delkravtyper as $delkrav):?>
		
		if(type.getValue() == "<?php echo addslashes($delkrav->kravtype);?>") {
			delkrav<?php echo $delkrav->id;?>.enable();
		}
		else {
			delkrav<?php echo $delkrav->id;?>.disable();
		}
		<?php endforeach;?>
	}

	var beløpsValidator = function( kravbeløp ) {
		var kravbeløp = beløp. getValue();
		var delkravsum = 0;
		<?php foreach($delkravtyper as $delkrav):?>
		
		if(type.getValue() == "<?php echo addslashes($delkrav->kravtype);?>") {
			delkravsum += delkrav<?php echo $delkrav->id;?>.getValue();
		}
		if( kravbeløp * delkrav<?php echo $delkrav->id;?>.getValue() < 0 ) {
			return 'Beløpet kan ikke være negativt.';
		}
		<?php endforeach;?>
		
		if( Math.abs(kravbeløp) < Math.abs(delkravsum) ) {
			return 'Det totale beløpet kan ikke være mindre enn delbeløpene.';
		}
		
		return true;
	}

	var slettKrav = function(svar) {
		if(svar == 'yes') {
			Ext.Ajax.request({
				waitMsg: 'Sletter...',
				url: 'index.php?oppslag=kravskjema&oppdrag=oppgave&oppgave=slett&id=<?php echo $id;?>',
				success: function( response, options ) {
					var tilbakemelding = Ext.JSON.decode( response.responseText );
					if( tilbakemelding.success == true ) {
						Ext.MessageBox.alert( 'Utført', '<?php echo $kreditt ? "Kreditten" : "Kravet";?> er slettet', function() {
							window.location = tilbakemelding.url;
						});
					}
					else {
						Ext.MessageBox.alert( 'Hmm..', tilbakemelding.msg );
					}
				}
			});
		}
	}

	var fortegn = Ext.create('Ext.form.RadioGroup', {
		fieldLabel: 'Krav eller kreditt',
		columns: 1,
		vertical: false,
		items: [
			{
				name:		'fortegn',
				boxLabel:	'Betalingskrav',
				inputValue:	'',
				readOnly: <?php echo $_GET['id'] == '*' ? "false" : "true"; ?>,
				checked: <?php echo $kreditt ? "false" : "true"; ?>
			},
			{
				name:		'fortegn',
				boxLabel:	'Kreditt',
				inputValue:	'-',
				readOnly: <?php echo $_GET['id'] == '*' ? "false" : "true"; ?>,
				checked: <?php echo $kreditt ? "true" : "false"; ?>
			}
		]
	});

	var tekst = Ext.create('Ext.form.field.Text', {
		fieldLabel: 'Beskrivelse',
		labelWidth: 80,
		maxLength: 100,
		allowBlank: false,
		name: 'tekst',
		tabIndex: 1,
		readOnly: <?php echo $låst ? 'true' : 'false' ;?>,
		flex: 1
	});

	var beløp = Ext.create('Ext.form.field.Number', {
		allowBlank: false,
		allowDecimals: true,
		minValue: 0,
		validator: beløpsValidator,
		decimalSeparator: ',',
		decimalPrecision: 2,
		hideTrigger: true,
		fieldLabel: 'Beløp',
		labelAlign: 'right',
		labelWidth: 40,
		name: 'beløp',
		tabIndex: 2,
		readOnly: <?php echo $låst ? 'true' : 'false' ;?>,
		width: 140
	});

	var leieforhold = Ext.create('Ext.form.field.Hidden', {
		name: 'leieforhold',
		value: '<?php echo $leieforhold;?>'
	});

	var type = Ext.create('Ext.form.field.ComboBox', {
		allowBlank: false,
		fieldLabel: 'Type',
		labelAlign: 'left',
		forceSelection: true,
		name: 'type',
		queryMode: 'local',
		
		store: Ext.create('Ext.data.Store', {
			fields: ['text'],
			data : [
				{text: "Husleie"},
				{text: "Fellesstrøm"},
				{text: "Purregebyr"},
				{text: "Annet"}
			]
		}),
		
		listeners: {
			change: aktiverFelter,
		},

		flex: 1,
		readOnly: <?php echo $låst ? 'true' : 'false' ;?>,
		tabIndex: 3
	});

	var kravdato = Ext.create('Ext.form.field.Date', {
		allowBlank: false,
		fieldLabel: 'Kravdato (regnskapsdato)',
		labelAlign: 'left',
		labelWidth: 150,
		format: 'd.m.Y',
		name: 'kravdato',
		submitFormat: 'Y-m-d',
		tabIndex: 1,
		flex: 1,
		tabIndex: 4,
		readOnly: <?php echo $låst ? 'true' : 'false' ;?>,
		value: new Date()
	});

	var forfall = Ext.create('Ext.form.field.Date', {
		allowBlank: true,
		fieldLabel: 'Forfall',
		labelAlign: 'left',
		labelWidth: 150,
		format: 'd.m.Y',
		submitFormat: 'Y-m-d',
		flex: 1,
		tabIndex: 5,
		name: 'forfall'
	});

	var andel = Ext.create('Ext.form.field.Text', {
		allowBlank: false,
		fieldLabel: 'Andel',
		labelWidth: 150,
		blankText: 'Du må oppgi hvor stor andel (som brøk, desimal eller i prosent) av leieobjektet som omfattes av leieavtalen dersom dette er i et bofellesskap, eller 1 dersom leieavtalen leier hele leieobjektet',
		maskRe: new RegExp("^[0-9/%,.]"),
		name: 'andel',
		tabIndex: 6,
		readOnly: <?php echo $låst ? 'true' : 'false' ;?>
	});

	var anleggsnr = Ext.create('Ext.form.field.ComboBox', {
		
		fieldLabel: 'Anleggsnr (for fellesstrøm)',
		labelAlign: 'top',
		labelWidth: 150,
		tabIndex: 7,
		readOnly: <?php echo $låst ? 'true' : 'false' ;?>,
		name: 'anleggsnr',

		matchFieldWidth: false,
		listConfig: {
			width: 500
		},

		store: Ext.create('Ext.data.JsonStore', {
			storeId: 'leieobjektliste',
	
			autoLoad: true,
			proxy: {
				type: 'ajax',
				url: "index.php?oppslag=kravskjema&id=<?php echo $id;?>&oppdrag=hentdata&data=anleggsnummer",
				reader: {
					type: 'json',
					root: 'data',
					idProperty: 'anleggsnummer'
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

		allowBlank: false,
		typeAhead: true,
		editable: true,
		selectOnFocus: true,
		forceSelection: true
	});

	var fom = Ext.create('Ext.form.field.Date', {
		fieldLabel: 'Fra dato (inklusive)',
		labelWidth: 120,
		format: 'd.m.Y',
		submitFormat: 'Y-m-d',
		tabIndex: 8,
		readOnly: <?php echo $låst ? 'true' : 'false' ;?>,
		name: 'fom'
	});

	var tom = Ext.create('Ext.form.field.Date', {
		fieldLabel: 'Til dato (inklusive)',
		labelWidth: 120,
		format: 'd.m.Y',
		submitFormat: 'Y-m-d',
		tabIndex: 9,
		readOnly: <?php echo $låst ? 'true' : 'false' ;?>,
		name: 'tom'
	});

	var termin = Ext.create('Ext.form.field.Text', {
		fieldLabel: 'Termin',
		labelWidth: 50,
		tabIndex: 10,
		readOnly: <?php echo $låst ? 'true' : 'false' ;?>,
		name: 'termin'
	});

	<?php foreach($delkravtyper as $delkrav):?>
	
	var delkrav<?php echo $delkrav->id;?> = Ext.create('Ext.form.field.Number', {
		allowDecimals: <?php echo $delkrav->relativ ? "true" : "false";?>,
		minValue: 0,
		validator: beløpsValidator,
		allowBlank: false,
		disabled: true,
		decimalPrecision: 1,
		decimalSeparator: ',',
		hideTrigger: true,
		fieldLabel: '<?php echo addslashes($delkrav->navn);?>',
		labelWidth: 120,
		name: 'delkrav<?php echo $delkrav->id;?>',
		tabIndex: <?php echo 10 + $delkrav->id;?>,
		readOnly: <?php echo $låst ? 'true' : 'false' ;?>,
		width: 180
	});
	<?php endforeach;?>
	
	



	var lagreknapp = Ext.create('Ext.Button', {
		text: 'Lagre',
		disabled: true,
		handler: function() {
			skjema.form.submit({
				url:'index.php?oppslag=kravskjema&id=<?php echo $_GET["id"];?>&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...',
				
				success: function(form, action) {
					if( action.response.responseText == '' ) {
						Ext.MessageBox.alert('Problem', 'Det kom en blank respons fra tjeneren.');
					} else {
						var tilbakemelding = Ext.JSON.decode( action.response.responseText );
						Ext.MessageBox.alert( 'Lagret', tilbakemelding.msg );
						window.location = tilbakemelding.url;
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
			labelAlign: 'left',
			width: 200
		},
		items: [
			<?php echo $låst ? "{xtype: 'displayfield', value: 'Det er bare mulig å endre forfallsdato på dette kravet. Ved feil kan du evt. kreditere (slette) det, for så å opprette et nytt.', width: 800}," : '' ;?>
			leieforhold,
			{
				xtype: 'displayfield',
				value: '<a href="index.php?oppslag=leieforholdkort&id=<?php echo $leieforhold;?>" title="Gå til leieforholdkortet"><?php echo addslashes( $leieforhold->hent('beskrivelse') );?></a>',
				width: 800
			},
			fortegn,
			{
				xtype: 'container',
				layout:	{
					type: 'hbox',
					padding: '0',
					defaultMargins: {
						top: 0,
						bottom: 10,
						left: 0,
						right: 0
					}
				},
				items: [
					tekst,
					beløp
				]
			},

			{
				xtype: 'container',
				layout:	{
					
					type: 'column',
					padding: '0',
					defaultMargins: {
						top: 0,
						bottom: 10,
						left: 0,
						right: 0
					}
				},
				defaults: {
					columnWidth: 0.33,
					xtype: 'container'
				},
				items: [
					{
						defaults: {
							width: 250
						},
						items: [
							type,
							kravdato,
							forfall
						]
					},
					{
						defaults: {
							width: 250
						},
						items: [
							andel,
							anleggsnr
						]
					},
					{
						defaults: {
							width: 220
						},
						items: [
							fom,
							tom,
							termin
						]
					}
				]
			},
			{
				xtype: 'displayfield',
				value: 'Delbeløp:'		
			},

			<?php foreach($delkravtyper as $indeks => $delkrav):?>
				<?php if($indeks/3 == (int)($indeks/3)):
					//	dette er første av tre kolonner?>

				{
					xtype: 'container',
					layout: 'hbox',
					items: [
				<?php endif;?>
					
						{
							xtype: 'container',
							flex: 1,
							padding: '0 5',
							items: [
								delkrav<?php echo $delkrav->id;?>

							]
				<?php if((($indeks + 1)/3 == (int)(($indeks + 1)/3)) || ($indeks + 1 == count($delkravtyper))):
					//	dette er tredje av tre eller siste kolonne?>							
						}
					]
				},
				<?php else:?>

						},
				<?php endif;?>
			<?php endforeach;?>

			{}
		],
		layout: 'anchor',
		frame: true,
		title: '<?php echo addslashes( $krav->hentId() ? $krav->hent('tekst') : "Registrer nytt betalingskrav eller kreditt i leieforhold {$leieforhold} {$leieforhold->hent('navn')}");?>',
		buttons: [{
			text: 'Tilbake',
			handler: function() {
				window.location = '<?php echo $this->returi->get();?>';
			}
		},
		{
			text: 'Slett',
			disabled: <?php echo ((int)$id and ($krav->hent('beløp') > 0 or !$krav->hent('giro'))) ? 'false' : 'true';?>,
			handler: function() {
				Ext.Msg.confirm("Bekreft", "Er du sikker på at <?php echo $kreditt ? "kreditten" : "kravet"; ?> skal slettes?", slettKrav);
			}
		},
		lagreknapp
		]
	});



	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'load') {
				<?php echo $låst ? 'aktiverFelter();' : '' ;?>
				lagreknapp.enable();
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
						Ext.MessageBox.alert('Problem:', 'Lagring av data mislyktes av ukjent grunn. Action type=' + action.type + ', failure type=' + action.failureType);
					}
				}
			}
			
		}
	});


	skjema.getForm().load({
		url: 'index.php?oppslag=kravskjema&id=<?php echo $id;?>&oppdrag=hentdata',
		waitMsg: 'Laster kravet...'
	});


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
	$id = $this->GET['id'];

	switch ($data) {

	case "anleggsnummer": {
		return json_encode( $this->mysqli->arrayData(array(
			'source'	=> "fs_fellesstrømanlegg",
			'fields'	=> "anleggsnummer, CONCAT(anleggsnummer, ' (målernr: ', målernummer, '): ', formål) AS anlegg",
			'where'		=>	isset( $_GET['query'] )
							? "CONCAT(anleggsnummer, ' (målernr: ', målernummer, '): ', formål) LIKE '%{$this->GET['query']}%'"
							: null
		)) );
		break;
	}

	default: {
	
		if($_GET['id'] == '*') {
			$resultat = (object)array(
				'success'	=> true,
				'data'		=> array()
			);
		}
		
		else {
			$krav = $this->hent('Krav', $id);
			
			$delkravtyper = $this->mysqli->arrayData(array(
				'source'		=> "{$tp}delkravtyper AS delkravtyper",
				'where'			=> "aktiv AND !selvstendig_tillegg",
				'orderfields'	=> "orden, id"
			))->data;

			if($krav->hentId())	{
				$resultat = (object)array(
					'success'	=> true,
					'data'		=> array(
						'beløp'		=> abs($krav->hent('beløp')),
						'tekst'		=> $krav->hent('tekst'),
						'type'		=> $krav->hent('type'),
						'andel'		=> str_replace('-', '', $krav->hent('andel')),
						'kravdato'	=> ($krav->hent('kravdato') ? $krav->hent('kravdato')->format('Y-m-d') : null),
						'forfall'	=> ($krav->hent('forfall') ? $krav->hent('forfall')->format('Y-m-d') : null),
						'fom'		=> ($krav->hent('fom') ? $krav->hent('fom')->format('Y-m-d') : null),
						'tom'		=> ($krav->hent('tom') ? $krav->hent('tom')->format('Y-m-d') : null),
						'termin'	=> $krav->hent('termin'),
						'anleggsnr'	=> $krav->hent('anleggsnr')
					)
				);
				
				foreach( $delkravtyper as $delkrav ) {
					$resultat->data["delkrav{$delkrav->id}"] = abs($krav->hentDel($delkrav->id));
				}
			}
			else {
				$resultat = (object)array(
					'success'	=> false,
					'msg'		=> "Kunne ikke laste dette kravet pga. en feil."
				);
			}
		}
		echo json_encode($resultat);
		break;
	}
	}
}



function taimotSkjema( $skjema ) {
	$tp = $this->mysqli->table_prefix;

	$id			= (int)$_GET['id'];
	$leieforhold = $this->hent('Leieforhold', $_POST['leieforhold']);
	$fortegn	= $_POST['fortegn'];
	$beløp		= str_replace(",", ".", $_POST['beløp']);
	$type		= $_POST['type'];
	$tekst		= $_POST['tekst'];
	$termin		= @$_POST['termin'];
	$andel		= @$_POST['andel'];
	$anleggsnr	= @$_POST['anleggsnr'];
	$kravdato	= date_create_from_format('Y-m-d H:i:s', ($_POST['kravdato'] . ' 00:00:00'));
	$forfall	= $_POST['forfall'] ? date_create_from_format('Y-m-d', $_POST['forfall']) : null;
	$fom		= @$_POST['fom'] ? date_create_from_format('Y-m-d H:i:s', ($_POST['fom'] . ' 00:00:00')) : null;
	$tom		= @$_POST['tom'] ? date_create_from_format('Y-m-d H:i:s', ($_POST['tom'] . ' 00:00:00')) : null;
	$delkrav	= array();
	
	$delkravtyper = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}delkravtyper AS delkravtyper",
		'where'			=> "aktiv AND !selvstendig_tillegg",
		'orderfields'	=> "orden, id"
	))->data;
	
	if( !isset($_POST['fortegn'])
	or ($fortegn != '-' and $fortegn != '') )
	{
		echo json_encode(array(
			'success'	=> false,
			'msg'		=> "Mottok ikke korrekt informasjon om beløpet gjelder krav eller kreditt."
		));
		return;
	}


	foreach( $delkravtyper as $delkravtype ) {
		if( isset($_POST["delkrav{$delkravtype->id}"]) ) {
			$delkrav[] = (object)array(
				'type'	=> $delkravtype->id,
				'beløp'	=> $fortegn . str_replace(",", ".", $_POST["delkrav{$delkravtype->id}"])
			);
		}
	}
	
	$eksisterende = $this->hent('Krav', $id);

	$tidligstMuligeKravdato = $this->tidligstMuligeKravdato();
	
	
	if(!$leieforhold->hentId() and !$id) {
		echo json_encode(array(
			'success'	=> false,
			'msg'		=> "Leieforhold eller krav-id mangler. Prøv på nytt ifra leieforholdkortet eller ifra kravkortet."
		));
		return;
	}

	if($beløp == 0) {
		echo json_encode(array(
			'success'	=> false,
			'msg'		=> "Beløpet mangler."
		));
		return;
	}
	if(!$type) {
		echo json_encode(array(
			'success'	=> false,
			'msg'		=> "Kravtype er ikke angitt."
		));
		return;
	}
	if(!$kravdato) {
		echo json_encode(array(
			'success'	=> false,
			'msg'		=> "Kravdato (regnskapsdato) er ikke angitt. Det skal normalt være dagens dato, eller første leiedato for husleiekrav."
		));
		return;
	}
	if(!$tekst) {
		echo json_encode(array(
			'success'	=> false,
			'msg'		=> "Tekstbeskrivelse av kravet mangler."
		));
		return;
	}

	if( $eksisterende->hentId() ) {
		$leieforhold = $eksisterende->hent('leieforhold');
		$fortegn = $eksisterende->hent('beløp') < 0 ? '-' : '';
	}
	
	if(!$leieforhold->hentId()) {
		echo json_encode(array(
			'success'	=> false,
			'msg'		=> "Leieforhold mangler for nytt krav."
		));
		return;
	}

	// Evt negativt fortegn fjernes fra andelen.
	if( $this->fraBrøk($andel) < 0 ) {
		$andel = $this->tilBrøk($this->fraBrøk($andel) * -1);
	}

	// Dersom kravet er utsendt er det bare forfallsdato som kan endres.
	if( $eksisterende->hentId() and $eksisterende->hent('giro') ) {
		if(
				$type		!= $eksisterende->hent('type')
			|| $beløp		!= abs($eksisterende->hent('beløp'))
			|| $tekst		!= $eksisterende->hent('tekst')
			|| $termin		!= $eksisterende->hent('termin')
			|| $andel		!= $eksisterende->hent('andel')
			|| $kravdato	!= $eksisterende->hent('kravdato')
			|| $anleggsnr	!= $eksisterende->hent('anleggsnr')
			|| $fom			!= $eksisterende->hent('fom')
			|| $tom			!= $eksisterende->hent('tom')
		) {
			echo json_encode(array(
				'success'	=> false,
				'msg'		=> "Kravet er sendt ut, og kun forfallsdato kan endres.<br>Du kan evt. kreditere kravet (slette det), og så opprette et nytt."
			));
			return;
		}
		
		foreach( $delkrav as $del ) {
			if( $del->beløp != $eksisterende->hentDel($del->type) ) {
				echo json_encode(array(
					'success'	=> false,
					'msg'		=> "Kravet er sendt ut, og kun forfallsdato kan endres.<br>Du kan evt. kreditere (slette) kravet, og så opprette et nytt."
				));
				return;
			}
		}
	}

	// Nye krav kan ikke tilbakedateres
	if( !$eksisterende->hentId() and ($kravdato < $tidligstMuligeKravdato) ) {
		$kravdato = new DateTime;
	}
	
	$leieobjekt = $leieforhold->hent('leieobjekt');

	//	Sjekk at påkrevde felter er med for husleiekrav
	if($type == 'Husleie') {
		if(
			!$fom
			or !$tom
			or !$this->fraBrøk($andel)
		) {
			echo json_encode(array(
				'success'	=> false,
				'msg'		=> "Fra- og tildato, og andel er påkrevet for husleiekrav"
			));
			return;
		}

		if( $this->fraBrøk($andel) > $leieobjekt->hentLeiekrav( $fom, $tom, $leieforhold )->ledig ) {
			echo json_encode(array(
				'success'	=> false,
				'msg'		=> "Andelen er for høy i forhold til hva som er ledig i {$leieobjekt->hent('type')} {$leieobjekt} i det angitte tidsrommet. Plass må evt frigjøres ved å slette andres leiekrav først."
			));
			return;
		}
	}

	//	Om kravet er nytt kan det opprettes
	if( !$eksisterende->hentId() ) {
		$krav = $this->opprett('Krav', array(
			'kontraktnr'	=> $leieforhold->hent('kontraktnr'),
			'type'			=> $type,
			'kravdato'		=> $kravdato,
			'leieobjekt'	=> $leieobjekt,
			'fom'			=> $fom,
			'tom'			=> $tom,
			'tekst'			=> $tekst,
			'termin'		=> $termin,
			'beløp'			=> $fortegn.$beløp,
			'andel'			=> $fortegn.$andel,
			'anleggsnr'		=> $anleggsnr,
			'delkrav'		=> $delkrav
		));

		if( !$krav ) {
			echo json_encode(array(
				'success'	=> false,
				'msg'		=> "Kravet kunne ikke opprettes."
			));
			return;
		}

		echo json_encode(array(
			'success'	=> true,
			'id'		=> strval($krav),
			'msg'		=> "",
			'url'		=> $this->returi->get(1)
		));
		return;		
	}
	
	//	Eksisterende krav forsøkes endret
	else {
		$krav = $eksisterende;
		
		$resultat = (object)array(
			'success'	=> true,
			'id'		=> strval($krav),
			'msg'		=> "",
			'url'		=> $this->returi->get(1)
		);
		
		if( $eksisterende->hent('giro') === null ) {
			if( $type != $krav->hent('type')
				and !$resultat->success = $krav->sett('type',			$type) ) {
				$resultat->msg	.= "Klarte ikke lagre type<br>";
			}
			if( $kravdato != $krav->hent('kravdato')
				and !$resultat->success = $krav->sett('kravdato',		$kravdato) ) {
				$resultat->msg	.= "Klarte ikke lagre kravdato<br>";
			}
			if( $fom != $krav->hent('fom')
				and !$resultat->success = $krav->sett('fom',			$fom) ) {
				$resultat->msg	.= "Klarte ikke lagre fra-dato<br>";
			}
			if( $tom != $krav->hent('tom')
				and !$resultat->success = $krav->sett('tom',			$tom) ) {
				$resultat->msg	.= "Klarte ikke lagre til-dato<br>";
			}
			if( $tekst != $krav->hent('tekst')
				and !$resultat->success = $krav->sett('tekst',			$tekst) ) {
				$resultat->msg	.= "Klarte ikke lagre kravbeskrivelsen<br>";
			}
			if( $termin != $krav->hent('termin')
				and !$resultat->success = $krav->sett('termin',			$termin) ) {
				$resultat->msg	.= "Klarte ikke lagre terminbeskrivelsen<br>";
			}
			if( "{$fortegn}{$beløp}" != $krav->hent('beløp') ) {
				if(!$resultat->success = $krav->sett('beløp',			"{$fortegn}{$beløp}")) {
				$resultat->msg	.= "Klarte ikke lagre beløpet<br>";
				}
				else {
					$this->oppdaterUbetalt();
				}
			}
			if( "{$fortegn}{$andel}" != $krav->hent('andel')
				and !$resultat->success = $krav->sett('andel',			"{$fortegn}{$andel}") ) {
				$resultat->msg	.= "Klarte ikke lagre andelen<br>";
			}
			if( $anleggsnr != $krav->hent('anleggsnr')
				and !$resultat->success = $krav->sett('anleggsnr',		$anleggsnr) ) {
				$resultat->msg	.= "Klarte ikke lagre anleggsnummer<br>";
			}
		
			foreach( $delkrav as $del ) {
				if( !$resultat->success = $krav->settDel( $del->type, "{$fortegn}{$del->beløp}") ) {
					$resultat->msg	.= "Klarte ikke lagre delkrav {$del->type}.<br>";
				}
			}
		}
		
		if( !$resultat->success = $krav->sett('forfall',		$forfall) ) {
			$resultat->msg	.= "Klarte ikke lagre forfallsdato<br>{$forfall->format('Y-m-d H:i:s')}";
		}

		echo json_encode( $resultat );
		return;		
	}
}



function oppgave($oppgave) {
	switch ($oppgave) {

	case "slett": {
		$id = $_GET['id'];
		if($id == '*') {
			echo json_encode(array(
				'success'	=> false,
				'msg'		=> "Kravet er ikke opprettet enda, og kan derfor ikke slettes."
			));
			break;
		}
		
		$resultat = (object)array(
			'success'		=> false,
			'msg'			=> '',
			'url'			=> $this->returi->get(1)
		);
		$krav = $this->hent('Krav', (int)@$id);
		$resultat->success = $krav->slett();
		if( !$resultat->success ) {
			$resultat->msg = "Kunne ikke slette kravet";
		}
		
		echo json_encode( $resultat );
		return;
		break;
	}
	
	default: {
		break;
	}
	}
}

}
?>