<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
Denne fila ble sist oppdatert 2016-02-01
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Skjema for utlikning av registrerte innbetalinger';
public $ext_bibliotek = 'ext-4.2.1.883';
	

function __construct() {
	parent::__construct();
}

function skript() {
	if(@$_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	$tp = $this->mysqli->table_prefix;

	$this->oppdaterUbetalt();
	
	$innbetalinger = array();
	
	foreach( $this->mysqli->arrayData(array(
		'source'	=> "{$tp}innbetalinger as innbetalinger",
		'where'		=> "innbetalinger.krav IS NULL",
		'fields'	=> "innbetalinger.innbetaling AS id",
		'distinct'	=> true,
		'class'		=> "Innbetaling"
	))->data as $innbetaling) {
		$innbetaling->samle();
		foreach( $innbetaling->hent('delbeløp') as $delbeløp ) {
			if ($delbeløp->krav === null) {
				$innbetalinger[] = (object)array(
					'innbetaling'			=> $innbetaling,
					'dato'					=> $innbetaling->hent('dato'),
					'betaler'				=> $innbetaling->hent('betaler'),
					'ref'					=> $innbetaling->hent('ref'),
					'konto'					=> $innbetaling->hent('konto'),
					'kontonavn'				=> $innbetaling->hent('kontonavn'),
					'id'					=> $delbeløp->id,
					'beløp'					=> $delbeløp->beløp,
					'leieforhold'			=> $delbeløp->leieforhold,
					'muligeLeieforhold'		=> array(),
					'leieforholdFeltsett'	=> array()
				);
			}
		}
	}

	$transaksjonsfeltsett = array();
	
	foreach( $innbetalinger as $innbetaling ) {
		$innbetaling->mulige = array();
		$innbetaling->muligeLeieforhold = array();
		$innbetaling->leieforholdFeltsett = array();
	
		// Dersom betalinga er ut fra konto kan den utliknes mot innbetaling eller mot kreditt
		if( $innbetaling->beløp < 0 ) {
			if($innbetaling->leieforhold) {
				$innbetaling->mulige = array_merge(
					$this->mysqli->arrayData(array(
						'distinct'	=> true,
						'fields'	=> "krav.id",
						'orderfields'	=> "krav.kravdato",
						'class'		=> "Krav",
						'source'	=> "{$tp}kontrakter AS kontrakter INNER JOIN {$tp}krav as krav ON kontrakter.kontraktnr = krav.kontraktnr",
						'where'		=> "kontrakter.leieforhold = '{$innbetaling->leieforhold}'
										AND utestående < 0"
					))->data,
			
					$this->mysqli->arrayData(array(
						'fields'	=> "innbetalinger.leieforhold, SUM(innbetalinger.beløp) as beløp",
						'groupfields'	=> "innbetalinger.innbetaling, innbetalinger.leieforhold",
						'source'	=> "{$tp}innbetalinger AS innbetalinger",
						'where'		=> "innbetalinger.krav IS NULL
										AND innbetalinger.leieforhold = '{$innbetaling->leieforhold}'",
						'having'	=> "beløp > 0"
					))->data
				);
			}
		}
		
		// Dersom betalinga er positiv, dvs inn på konto kan den utliknes mot ubetalte krav
		//	eller mot utbetalinger
		else {
			if ( $innbetaling->leieforhold ) {
			
				// Dersom det er kreditt som skal utliknes, så ser en først etter det
				// krediterte kravet, for å utlikne mot dette.
				if( $innbetaling->konto == '0' ) {
					$kreditertKrav = $innbetaling->innbetaling
									->hentKredittkrav()
									->hentKreditertKrav();
					if( $kreditertKrav ) {
						$innbetaling->mulige[] = $kreditertKrav;
					}
				}
				
			
				$innbetaling->mulige = array_merge(
					$innbetaling->mulige,
					
					$this->mysqli->arrayData(array(
						'distinct'	=> true,
						'fields'	=> "krav.id",
						'class'		=> "Krav",
						'source'	=> "{$tp}kontrakter AS kontrakter INNER JOIN {$tp}krav as krav ON kontrakter.kontraktnr = krav.kontraktnr",
						'where'		=> "kontrakter.leieforhold = '{$innbetaling->leieforhold}'
										AND utestående > 0
										AND type <> 'Husleie'"
					))->data,
				
					$this->mysqli->arrayData(array(
						'distinct'	=> true,
						'fields'	=> "krav.id",
						'class'		=> "Krav",
						'source'	=> "{$tp}kontrakter AS kontrakter INNER JOIN {$tp}krav as krav ON kontrakter.kontraktnr = krav.kontraktnr",
						'where'		=> "kontrakter.leieforhold = '{$innbetaling->leieforhold}'
										AND utestående > 0
										AND type = 'Husleie'
										AND (utestående <> beløp OR kravdato <= NOW())"
					))->data,
				
					$this->mysqli->arrayData(array(
						'distinct'	=> true,
						'fields'	=> "krav.id",
						'class'		=> "Krav",
						'source'	=> "{$tp}kontrakter AS kontrakter INNER JOIN {$tp}krav as krav ON kontrakter.kontraktnr = krav.kontraktnr",
						'limit'		=> 2,
						'orderfields'	=> "if( krav.forfall IS NULL , 1, 0 ) ASC , `krav`.`forfall` ASC , krav.kravdato",
						'where'		=> "kontrakter.leieforhold = '{$innbetaling->leieforhold}'
										AND utestående > 0
										AND type = 'Husleie'
										AND (utestående = beløp OR kravdato > NOW())"
					))->data
				);
				$innbetaling->mulige = array_unique( $innbetaling->mulige );
				usort( $innbetaling->mulige, array($this, 'sammenliknTransaksjonsdatoer'));
				
				$innbetaling->mulige = array_merge(
					$innbetaling->mulige,
					$this->mysqli->arrayData(array(
						'distinct'	=> true,
						'fields'	=> "innbetalinger.leieforhold, SUM(innbetalinger.beløp) * (-1) as beløp",
						'groupfields'	=> "innbetalinger.leieforhold, innbetalinger.innbetaling",
						'source'	=> "{$tp}innbetalinger AS innbetalinger",
						'where'		=> "innbetalinger.krav IS NULL
										AND innbetalinger.leieforhold = '{$innbetaling->leieforhold}'",
						'having'	=> "beløp > 0"
					))->data
				);
			}
			
			
			else if ( $innbetaling->betaler ) {
				$innbetaling->mulige = array_merge(
					$this->mysqli->arrayData(array(
						'distinct'	=> true,
						'fields'	=> "krav.id",
						'class'		=> "Krav",
						'source'	=> "{$tp}kontrakter AS kontrakter
										INNER JOIN {$tp}krav as krav ON kontrakter.kontraktnr = krav.kontraktnr
										INNER JOIN {$tp}innbetalinger AS innbetalinger ON kontrakter.leieforhold = innbetalinger.leieforhold",
						'where'		=> "betaler = '{$innbetaling->betaler}'
										AND utestående > 0
										AND type <> 'Husleie'"
					))->data,
				
					$this->mysqli->arrayData(array(
						'distinct'	=> true,
						'fields'	=> "krav.id",
						'class'		=> "Krav",
						'source'	=> "{$tp}kontrakter AS kontrakter
										INNER JOIN {$tp}krav as krav ON kontrakter.kontraktnr = krav.kontraktnr
										INNER JOIN {$tp}innbetalinger AS innbetalinger ON kontrakter.leieforhold = innbetalinger.leieforhold",
						'where'		=> "betaler = '{$innbetaling->betaler}'
										AND utestående > 0
										AND kravdato <= DATE_ADD(NOW(), INTERVAL 2 MONTH)
										AND type = 'Husleie'"
					))->data
				);
				$innbetaling->mulige = array_unique( $innbetaling->mulige );
				usort( $innbetaling->mulige, array($this, 'sammenliknTransaksjonsdatoer'));			
			}
		}
		
		// Grupper alle muligheter etter leieforhold
		foreach( $innbetaling->mulige as $mulighet ) {
			if( $mulighet instanceof Krav ) {
				$leieforhold = strval($mulighet->hent('leieforhold'));
			}
			else {
				$leieforhold = strval($mulighet->leieforhold);
			}
			settype( $innbetaling->muligeLeieforhold[ $leieforhold ], 'array' );
			$innbetaling->muligeLeieforhold[ $leieforhold ][] = $mulighet;
		}

		foreach( $innbetaling->muligeLeieforhold as $leieforhold => $muligheter ) {
			$leieforhold = $this->hent('Leieforhold', $leieforhold);
			$kravfelt = array();
			foreach( $muligheter as $mulighet ) {
				$kravfelt[] = (
					$mulighet instanceof Krav
					? "komb['{$innbetaling->innbetaling}_{$mulighet}'], verdi['{$innbetaling->innbetaling}_{$mulighet}']"
					: "komb['{$innbetaling->innbetaling}_0_{$leieforhold}'], verdi['{$innbetaling->innbetaling}_0_{$leieforhold}']"
				);
			}
			$innbetaling->leieforholdFeltsett[] = "{
			xtype: 'fieldset',
			collapsible: true,
			collapsed: " . ($innbetaling->leieforhold ? 'false' : 'true') . ",
			autoHeight: true,
			title: 'Leieforhold {$leieforhold}: {$leieforhold->hent('navn')}',
			layout: 'column',
			items: [" . implode(", ", $kravfelt) ."]
		}";
		}
		
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

    Ext.tip.QuickTipManager.init();
	Ext.form.Field.prototype.msgTarget = 'side';
	Ext.Loader.setConfig({enabled:true});
	
	<?php include_once("_menyskript.php");?>

	var oppdater = [];
	var feltsett = [];
	var komb = [];
	var verdi = [];
	var utlikning = [];

	
	var leieforhold = Ext.create('Ext.form.field.ComboBox', {
		fieldLabel: 'Leieforhold',
		hideLabel: false,
		name: 'leieforhold',
		width: 570,
		matchFieldWidth: false,
		listConfig: {
			width: 700
		},

		store: Ext.create('Ext.data.JsonStore', {
			storeId: 'leieobjektliste',
		
			autoLoad: false,
			proxy: {
				type: 'ajax',
				url: "index.php?oppslag=utlikninger_skjema&oppdrag=hentdata&data=leieforholdforslag",
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



	angiLeieforhold = function( innbetaling, beskrivelse, tidlLeieforhold = '' ) {
		leieforhold.getStore().baseParams = {innbetaling: innbetaling};
		leieforhold.clearValue();
		
		var vindu = Ext.create('Ext.window.Window', {
			title: beskrivelse,
			bodyStyle:'padding:5px 5px 0',
			closeAction: 'hide',
			hideBorders: true,
			width: 600,
			height: 200,
			autoScroll: false,
			items: [
				{
					xtype: 'displayfield',
					value: '<b>Angi hvilket leieforhold beløpet gjelder.</b><br />Utlikninger som allerede er markert vil bli lagret, og skjemaet lastet på nytt.<br /><br />Finn ønsket leieforhold ved å skrive navn eller leieforholdnummer:'
				},
				leieforhold
			],
			buttons: [{
				text: 'Avbryt',
				handler: function() {
					vindu.hide();
				}
			}, {
				text: 'Lagre',
				handler: function() {
					Ext.Ajax.request({
						waitMsg: 'Angir leieforhold...',
						url: "index.php?oppslag=<?php echo $_GET['oppslag']?>&oppdrag=oppgave&oppgave=endreleieforhold&innbetaling=" + innbetaling + "&tidl_leieforhold=" + tidlLeieforhold + "&leieforhold=" + leieforhold.getValue(),
						success: function(response, options) {
							var tilbakemelding = Ext.JSON.decode(response.responseText);
							if(tilbakemelding['success'] == true) {
								Ext.MessageBox.alert('Utført', tilbakemelding.msg);
								skjema.form.submit({
									url: 'index.php?oppslag=utlikninger_skjema&oppdrag=taimotskjema',
									waitMsg: 'Lagrer fordeling...'
								});
							}
							else {
								Ext.MessageBox.alert('Hmm..',tilbakemelding['msg']);
							}
						}
					});
				}
			}]
		});
		vindu.show();
	}	
	
	
	<?php foreach($innbetalinger as $innbetaling):?>
	
	utlikning['<?php echo $innbetaling->innbetaling;?>'] = 0;
	
	oppdater['<?php echo $innbetaling->innbetaling;?>'] = function() {
		utlikning['<?php echo $innbetaling->innbetaling;?>'] = 0
	<?php foreach($innbetaling->mulige as $mulighet):?>
	+ (komb['<?php echo ($mulighet instanceof Krav ? "{$innbetaling->innbetaling}_{$mulighet}" : "{$innbetaling->innbetaling}_0_{$innbetaling->leieforhold}");?>'].getValue()
			* verdi['<?php echo ($mulighet instanceof Krav ? "{$innbetaling->innbetaling}_{$mulighet}" : "{$innbetaling->innbetaling}_0_{$innbetaling->leieforhold}");?>'].getValue())
	<?php endforeach;?>	;
		feltsett[<?php echo $innbetaling->id;?>].setTitle('<a title="Klikk for detaljer href="index.php?oppslag=innbetalingskort&id=<?php echo $innbetaling->innbetaling;?>"><?php echo "{$innbetaling->kontonavn} {$this->kr(abs($innbetaling->beløp))}";?></a> <?php echo ($innbetaling->konto ? ( $innbetaling->beløp > 0 ? "betalt inn " : "betalt ut ") : "");?> <?php echo ($innbetaling->leieforhold ? (( $innbetaling->beløp > 0 ? "til " : "av ") . "leieforhold <a title=\"Klikk her for å gå til dette leieforholdet\" href=\"index.php?oppslag=leieforholdkort&id={$innbetaling->leieforhold}\">{$innbetaling->leieforhold}: " . addslashes($innbetaling->leieforhold->hent('beskrivelse')) . "</a><br />") : "") . ( $innbetaling->beløp > 0 ? "av " : "til ") . ($innbetaling->betaler ? $innbetaling->betaler : "ukjent");?> den <?php echo $innbetaling->dato->format('d.m.Y');?>. Ref <?php echo $innbetaling->ref;?> <strong>--- Valgt krav for ' + Ext.util.Format.noMoney(utlikning['<?php echo $innbetaling->innbetaling;?>']) + '</strong>');
		return utlikning['<?php echo $innbetaling->innbetaling;?>'];
	}

		<?php foreach($innbetaling->mulige as $mulighet):?>


			<?php if($mulighet instanceof Krav):?>
				<?php $komb = "{$innbetaling->innbetaling}_{$mulighet}"; $tekst = "Krav {$mulighet}: {$mulighet->hent('tekst')} (mangler {$this->kr($mulighet->hent('utestående'))} av {$this->kr($mulighet->hent('beløp'))})"; $beløp = $mulighet->hent('beløp'); $utestående = $mulighet->hent('utestående');?>
			<?php else:?>
				<?php $komb = "{$innbetaling->innbetaling}_0_{$innbetaling->leieforhold}"; $tekst = "Betalinger: {$this->kr($mulighet->beløp)}"; $beløp = $mulighet->beløp; $utestående = $mulighet->beløp;?>
			<?php endif;?>
			
	komb['<?php echo $komb?>'] = Ext.create('Ext.form.field.Checkbox', {
		boxLabel: '<?php echo addslashes($tekst);?>',
		hideLabel: true,
		disabled: <?php echo $innbetaling->leieforhold ? 'false' : 'true';?>,
		inputValue: 1,
		uncheckedValue: 0,
		listeners: {
			change: function( checkbox, newValue, oldValue, eOpts ) {
				oppdater['<?php echo $innbetaling->innbetaling;?>']();
				if( newValue ) {
					verdi['<?php echo $komb;?>'].enable();
				}
				else {
					verdi['<?php echo $komb;?>'].setValue('<?php echo $utestående;?>');
					verdi['<?php echo $komb;?>'].disable();
				}
				verdi['<?php echo $komb;?>'].validate();
			}
		},
		name: 'komb<?php echo $komb;?>',
		width: 700
	});

	verdi['<?php echo $komb?>'] = Ext.create('Ext.form.field.Number', {
		allowNegative: false,
		decimalSeparator: ',',
		submitLocaleSeparator: false,
		hideTrigger: true,
		disabled: true,
		listeners: {
			change: oppdater['<?php echo $innbetaling->innbetaling;?>']

		},
		maxValue: <?php echo $utestående;?>,
		minValue: 0,
		name: 'verdi<?php echo $komb;?>',
		value: <?php echo $utestående;?>,
		validator: function(v) {
			v = v.replace(',', '.');
			resultat = oppdater['<?php echo $innbetaling->innbetaling;?>']();
			if(resultat > <?php echo abs($innbetaling->beløp);?>) {
				verdi['<?php echo $komb;?>'].setValue(Math.min(<?php echo $utestående;?>, (v - (resultat - <?php echo abs($innbetaling->beløp);?>))));
			}
			return true;
		},
		width: 60
	});


		<?php endforeach;?>	

	feltsett[<?php echo $innbetaling->id;?>] = Ext.create('Ext.form.FieldSet', {
		collapsed: true,
		collapsible: true,
		title: '<a title="Klikk for detaljer" href="index.php?oppslag=innbetalingskort&id=<?php echo $innbetaling->innbetaling;?>"><?php echo "{$innbetaling->kontonavn} {$this->kr(abs($innbetaling->beløp))}";?></a> <?php echo ($innbetaling->konto ? ( $innbetaling->beløp > 0 ? "betalt inn " : "betalt ut ") : "");?> <?php echo ($innbetaling->leieforhold ? (( $innbetaling->beløp > 0 ? "til " : "av ") . "leieforhold <a title=\"Klikk her for å gå til dette leieforholdet\" href=\"index.php?oppslag=leieforholdkort&id={$innbetaling->leieforhold}\">{$innbetaling->leieforhold}: " . addslashes($innbetaling->leieforhold->hent('beskrivelse')) . "</a><br />") : "") . ( $innbetaling->beløp > 0 ? "av " : "til ") . ($innbetaling->betaler ? $innbetaling->betaler : "ukjent");?> den <?php echo $innbetaling->dato->format('d.m.Y');?>. Ref <?php echo $innbetaling->ref;?>',
		layout: 'anchor',
		items: [{
			xtype: 'container',
			layout: 'form',
			columnWidth: 1,
			items:[
			<?php foreach( $innbetaling->muligeLeieforhold as $leieforholdnr => $muligheter ):?>
				<?php foreach( $muligheter as $mulighet ):?>
				<?php endforeach;?>
			<?php endforeach;?>
			<?php echo implode(", ", $innbetaling->leieforholdFeltsett)
			. (count($innbetaling->leieforholdFeltsett) ? ", " : "");?>

				{
					xtype: 'displayfield',
					hidden: <?php echo ( $innbetaling->konto == '0' ? 'true' : 'false' );?>,
					value: '<a style="cursor: pointer;" title="Klikk her for å angi hvilket leieforhold beløpet skal <?php echo ($innbetaling->beløp > 0 ? "krediteres" : "trekkes fra");?>" onClick="angiLeieforhold(\'<?php echo $innbetaling->innbetaling;?>\', \'<?php echo $this->kr(abs($innbetaling->beløp)) . ($innbetaling->beløp > 0 ? ($innbetaling->betaler ? " innbetalt av {$innbetaling->betaler}" : " innbetalt") : ($innbetaling->betaler ? " utbetalt til {$innbetaling->betaler}" : " utbetalt")) . " den " . $innbetaling->dato->format('d.m.Y');?>\'<?php echo $innbetaling->leieforhold ? ", {$innbetaling->leieforhold}" : "";?>)"><?php echo ($innbetaling->leieforhold ? "Endre" : "Angi");?> leieforhold</a>'
				}
			]
		}]
	});
	

		<?php $transaksjonsfeltsett[] = "feltsett[{$innbetaling->id}]";?>

	<?php endforeach;?>

	var lagreknapp = Ext.create('Ext.Button', {
		text: 'Lagre endringer',
		disabled: false,
		handler: function(){
			skjema.form.submit({
				url: 'index.php?oppslag=<?php echo "{$_GET['oppslag']}";?>&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...'
				});
		}
	});

	var skjema = Ext.create('Ext.form.Panel', {
		autoScroll: true,
		bodyPadding: 5,
		items: [<?php echo implode(", ", $transaksjonsfeltsett);?>],
		frame: true,
		standardSubmit: false,
		title: 'Forslag til utlikning av innbetalinger',
		renderTo: 'panel',
		height: 500,
		width: 900,
		buttons: [
			{
				text: 'Tilbake',
				handler: function() {
					window.location = '<?php echo $this->returi->get();?>';
				}
			},
			lagreknapp
		]
	});


	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Det kom en blank respons fra tjeneren.');
				} else {
					Ext.MessageBox.alert('Suksess', 'Opplysningene er oppdatert');
					window.location = "index.php?oppslag=utlikninger_skjema";
				}
			}
		},
							
		actionfailed: function(form,action){
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
						Ext.MessageBox.alert('Problem:', 'Lagring av data mislyktes av ukjent grunn.');
					}
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
	switch ($data) {

	case "leieforholdforslag": {
		$tp = $this->mysqli->table_prefix;
		$query = $this->GET['query'];
		
		$resultat = (object)array(
			'success'	=> true,
			'data'		=> array()
		);

		$leieforholdsett = $this->mysqli->arrayData(array(
			'source'	=> "{$tp}kontrakter as kontrakter
							INNER JOIN {$tp}kontraktpersoner as kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt
							INNER JOIN {$tp}personer as personer ON personer.personid = kontraktpersoner.person
							LEFT JOIN {$tp}oppsigelser as oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold",
			'fields'	=>	"kontrakter.leieforhold AS id",
			'where'		=>	"CONCAT(fornavn, ' ', etternavn) LIKE '%{$query}%'
							OR kontrakter.kontraktnr LIKE '%{$query}%'",
			'distinct'	=> true,
			'orderfields'	=> "IF(oppsigelser.leieforhold IS NULL, 0, 1), kontrakter.leieforhold DESC",
			'class'		=> "Leieforhold"
		))->data;
		
		foreach( $leieforholdsett as $leieforhold ) {
			$oppsigelse = $leieforhold->hent('oppsigelse');
			if( $oppsigelse ) {
				$tildato = clone $oppsigelse->fristillelsesdato;
				$tildato->sub( new DateInterval('P1D') );
			}
			else {
				$tildato = null;
			}
			
			$resultat->data[] = array(
				'leieforhold'	=> $leieforhold->hentId(),
				'visningsfelt'	=> "{$leieforhold} {$leieforhold->hent('beskrivelse')}: {$leieforhold->hent('fradato')->format('d.m.Y')} – " . ( $tildato ? $tildato->format('d.m.Y') : "" )
			);
		}
		
		return json_encode($resultat);
		break;
	}

	default: {
		return json_encode($this->arrayData($this->hoveddata));
		break;
	}
	}
}



function oppgave($oppgave = "") {
	$tp = $this->mysqli->table_prefix;
	switch($oppgave) {

	case "endreleieforhold":
		$resultat = new stdclass;		
		$innbetaling = $this->hent('Innbetaling', $_GET['innbetaling']);
		$leieforhold = @$_GET['tidl_leieforhold'] ? $this->hent('Leieforhold', $_GET['tidl_leieforhold']) : false;
		$nyttLeieforhold = $this->hent('Leieforhold', $_GET['leieforhold']);

		$resultat->success = $innbetaling->hentId() && $nyttLeieforhold->hentId();
		
		if($resultat->success) {
		
			$resultat->success
				= ( $leieforhold ? $innbetaling->frakople(null, true, $leieforhold) : true )
				and $innbetaling->konter( $nyttLeieforhold );
		}
		
		echo json_encode($resultat);
		break;
	}
}



function taimotSkjema() {
	$tp = $this->mysqli->table_prefix;

	// henter ut de innmeldte kombinasjonene av betalinger og krav
	$fordeling = array();
	foreach($_POST as $felt=>$verdi) {
		if(substr($felt, 0, 4) == 'komb') {
			$id = substr($felt, 4);

			$felt = explode("_", $id);
			//	$felt[0]: Betalingsid for beløpet som skal fordeles
			//	$felt[1]: Krav-id eller 0 for betaling
			//	($felt[2]: Leieforhold-id dersom $felt[1] er 0)
			
			
			settype( $fordeling[$felt[0]], 'array' );
			
			if( isset($_POST["verdi{$id}"])
			and is_numeric($_POST["verdi{$id}"]) ) {
				$fordeling[$felt[0]][(int)$felt[1]] = array(
					'beløp'			=> $_POST["verdi{$id}"],
					'leieforhold'	=> (int)@$felt[2]
				);
			}
		}
	}
	
	
	foreach($fordeling as $innbetaling => $motparter) {
		$innbetaling = $this->hent( 'Innbetaling', $innbetaling );
		
		if($innbetaling->hentId()) {
			foreach($motparter as $kravid => $fordeling) {

				// For utbetalinger må fordelingsbeløpet inverteres
				if( $innbetaling->hent('beløp') < 0 ) {
					$fordeling['beløp'] *= (-1);
				}
				
				if( $kravid > 0 ) {
					$motkrav = $this->hent('Krav', $kravid);
					$leieforhold = null;
				}
				else {
					$motkrav = @$this->mysqli->arrayData(array(
						'source'		=> "{$tp}innbetalinger as innbetalinger",
						'where'			=> "innbetalinger.leieforhold = '{$fordeling['leieforhold']}'\n"
										.	"AND innbetalinger.krav IS NULL\n"
										.	"AND innbetalinger.beløp "
										. ( ($innbetaling->hent('beløp') < 0) ? ">0" : "<0" ),
						'distinct'		=> true,
						'orderfields'	=> "IF(innbetalinger.beløp = '" . ($fordeling['beløp'] * (-1)) . "', 0, 1), IF(innbetalinger.beløp > '" . ($fordeling['beløp'] * (-1)) . "', 0, 1)",
						'fields'		=> "innbetalinger.innbetaling as id",
						'class'			=> "Innbetaling"
					))->data[0];
					if(!$motkrav) {
						$motkrav = new Innbetaling;
					}
					$leieforhold = $this->hent('Leieforhold', $fordeling['leieforhold']);
				}
				
				if( $fordeling['beløp'] != 0 ) {
					$innbetaling->fordel( $motkrav, $fordeling['beløp'], $leieforhold );
					
					if( $motkrav instanceof Innbetaling ) {
						$motkrav->fordel( $motkrav, $fordeling['beløp'] * -1, $leieforhold );
					}
				}
			}
		}
	}

	$this->oppdaterUbetalt();

	echo json_encode(array(
		'success'	=> true
	));
}



}
?>