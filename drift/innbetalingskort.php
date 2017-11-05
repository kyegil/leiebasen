<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br>Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'leiebasen';
public $ext_bibliotek = 'ext-4.2.1.883';


function __construct() {
 	parent::__construct();
}



/*	Pre HTML
Dersom transaksjonen tilhører kreditt vil du bli videresent til denne kreditten
istedetfor å se transaksjonen
******************************************
------------------------------------------
retur (boolsk) Sann for å skrive ut HTML-malen, usann for å stoppe den
*/
public function preHTML() {
	$innbetaling = $this->hent('Innbetaling', @$_GET['id']);
	if($innbetaling->hent('konto') == '0') {
		header("Location: index.php?oppslag=krav_kort&id={$innbetaling->hentKredittkrav()}");
		return false;
	}
	
	else{
		$this->tittel = "Betaling: " . $this->kr($innbetaling->hent('beløp'), false) . " den " . $innbetaling->hent('dato')->format('d.m.Y') . " | Leiebasen";
		return true;
	}
}



function skript() {
	if( isset( $_GET['returi'] ) && $_GET['returi'] == "default") {
		$this->returi->reset();
	}
	$this->returi->set();

	$innbetaling = $this->hent('Innbetaling', @$_GET['id']);
	$ocr = $innbetaling->hent('ocr');
	$utbetaling = $innbetaling->hent('beløp') < 0 ? true : false;
	
	$html = "<h1>" . ($utbetaling ? "Ut" : "Inn") . "betaling på {$this->kr(abs($innbetaling->hent('beløp')))}"
	. (
		$innbetaling->hent('betaler')
		? (($utbetaling ? " til" : " fra") . " {$innbetaling->hent('betaler')}")
		: ""
	)
	. " den {$innbetaling->hent('dato')->format('d.m.Y')}</h1>"
	.	"Registrert av {$innbetaling->hent('registrerer')} den {$innbetaling->hent('registrert')->format('d.m.Y')}<br><br>"
	.	"Konto: {$innbetaling->hent('konto')} " . ($ocr ? "<a title=\"Klikk her for å gå til OCR-forsendelsen\" href=\"index.php?oppslag=ocr_kort&id={$innbetaling->hent('ocr')->filID}\">[Vis OCR-forsendelsen]</a>" : "") . "<br>"
	.	($ocr ? "Transaksjonstype: {$ocr->transaksjonsbeskrivelse}<br>" : "")
	.	($ocr ? "KID: {$ocr->kid}<br>" : "")
	.	"Referanse: {$innbetaling->hent('ref')}<br>"
	.	"Merknad: {$innbetaling->hent('merknad')}<br>";

?>

Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?php echo $this->http_host . "/" . $this->ext_bibliotek . "/examples/ux"?>');

Ext.require([
 	'Ext.data.*',
 	'Ext.form.field.*',
    'Ext.layout.container.Border',
 	'Ext.grid.*',
    'Ext.grid.plugin.BufferedRenderer',
    'Ext.ux.RowExpander'
]);

Ext.onReady(function() {

    Ext.tip.QuickTipManager.init();
	Ext.form.Field.prototype.msgTarget = 'side';
	Ext.Loader.setConfig({enabled:true});
	
<?php
	include_once("_menyskript.php");
?>

	angiLeieforhold = function( innbetalingsid, innbetaling, tidlLeieforhold = '' ) {
		Ext.Ajax.request({
			waitMsg: 'Løsner...',
			url: "index.php?oppslag=innbetalingskort&oppdrag=manipuler&data=frakople&id=<?php echo $innbetaling;?>",
			params: {
				innbetalingsid: innbetalingsid
			},
			success : function( result ) {
				leieforhold.getStore().baseParams = {innbetaling: innbetaling};
				leieforhold.clearValue();
		
				var leieforholdvindu = Ext.create('Ext.window.Window', {
					bodyStyle:'padding:5px 5px 0',
					closeAction: 'hide',
					hideBorders: true,
					width: 600,
					height: 200,
					autoScroll: false,
					items: [
						{
							xtype: 'displayfield',
							value: '<b>Angi hvilket leieforhold beløpet gjelder.</b><br><br>Finn ønsket leieforhold ved å skrive navn eller leieforholdnummer:'
						},
						leieforhold
					],
					buttons: [{
						text: 'Avbryt',
						handler: function() {
							leieforholdvindu.hide();
						}
					}, {
						text: 'Lagre',
						handler: function() {
							Ext.Ajax.request({
								waitMsg: 'Angir leieforhold...',
								url: "index.php?oppslag=innbetalingskort&oppdrag=oppgave&oppgave=endreleieforhold&innbetaling=" + innbetaling + "&tidl_leieforhold=" + tidlLeieforhold + "&leieforhold=" + leieforhold.getValue(),
								success: function(response, options) {
									var tilbakemelding = Ext.JSON.decode(response.responseText);
									if(tilbakemelding['success'] == true) {
										Ext.MessageBox.alert('Utført', tilbakemelding.msg);
										indrepanel.getLoader().load('index.php?oppslag=<?php echo $_GET['oppslag'];?>&oppdrag=hentdata&id=<?php echo $innbetaling;?>');
										leieforholdvindu.hide();
									}
									else {
										Ext.MessageBox.alert('Hmm..',tilbakemelding['msg']);
									}
								}
							});
						}
					}]
				});
				leieforholdvindu.show();
			}
		});


	}	
	
	
	kople = function(innbetalingsid, beløp, leieforhold) {
		var utlikning = 0;
		
		var oppdater = function() {
			utlikning = 0;
			for (index = 0; index < kravsett.length; ++index) {
				var krav = kravsett[index];
				utlikning += (komb[krav.id].getValue()) * (verdi[krav.id].value);
			}

			utlikningsvindu.setTitle('Valgt krav for kr. ' + Ext.util.Format.noMoney(utlikning) + ' --- Rest kr. ' + Ext.util.Format.noMoney(beløp - utlikning));
			return utlikning;
		}

		var utlikningsskjema = Ext.create('Ext.form.Panel', {
			layout: 'column',
			autoScroll: true,
			labelAlign: 'top', // evt right
			standardSubmit: false,
			buttons: [{
				text: 'Lagre',
				handler: function() {
					utlikningsskjema.getForm().submit({
						url: 'index.php?oppslag=innbetalingskort&oppdrag=manipuler&data=kople&id=<?php echo $innbetaling;?>',
						params: {
							leieforhold: leieforhold
						},
						success: function(form, action) {
							indrepanel.getLoader().load('index.php?oppslag=<?php echo $_GET['oppslag'];?>&oppdrag=hentdata&id=<?php echo $innbetaling;?>');
							utlikningsvindu.close();
						}
					});
				}
			}]
		});
		
		var utlikningsvindu = Ext.create('Ext.window.Window', {
			layout: 'fit',
			modal: true,
			title: 'Utlikning av innbetaling',
			width: 600,
			height: 400,
			items: [utlikningsskjema]
		});
		
		var kravsett = [];
		var komb = [];
		var verdi = [];

		Ext.Ajax.request({
			url: "index.php?oppslag=innbetalingskort&oppdrag=hentdata&data=utlikningsmuligheter&id=<?php echo $innbetaling;?>",
			params: {
				innbetalingsid: innbetalingsid,
				leieforhold: leieforhold
			},
			success : function(response, options) {
				var result = Ext.JSON.decode( response.responseText );
				if( result.success ) {
					kravsett = result.data;
					for (index = 0; index < kravsett.length; ++index) {
						var krav = kravsett[index];

						komb[krav.id] = Ext.create('Ext.form.field.Checkbox', {
							boxLabel: krav.tekst + ( krav.id ? " (" + krav.id + ")" : ''),
							hideLabel: true,
							inputValue: 1,
							listeners: {
								change( checkbox, checked, oldValue, eOpts ) {
									var startverdi = verdi[checkbox.getName().slice(4)].startverdi;
									if(checked) {
										var rest = beløp - utlikning;
										var maksverdi = (Math.abs(startverdi) < Math.abs(rest) ? startverdi : rest );
										verdi[checkbox.getName().slice(4)].enable();
										verdi[checkbox.getName().slice(4)].setValue(maksverdi);
									}
									else {
										verdi[checkbox.getName().slice(4)].disable();
										verdi[checkbox.getName().slice(4)].setValue(startverdi);
									}
									oppdater();
									verdi[checkbox.getName().slice(4)].validate();
								}
							},
							name: 'komb' + krav.id,
							width: 500
						});
						utlikningsskjema.add(komb[krav.id]);
					
						verdi[krav.id] = Ext.create('Ext.form.field.Number', {
							decimalSeparator: ',',
							hideTrigger: true,
							disabled: true,
							listeners: {change: oppdater},
							minValue: Math.min(krav.beløp, 0),
							maxValue: Math.max(krav.beløp, 0),
							name: 'verdi' + krav.id,
							value: krav.beløp,
							validator: function(v){
								resultat = oppdater();
								if(Math.abs(resultat) > Math.abs(beløp)) {
									verdi[krav.id].setValue(
										Math.abs(krav.beløp) < Math.abs(v - (resultat - beløp))
										? krav.beløp
										: (v - (resultat - beløp))
									);
								}
								return true;
							},
							width: 50
						});
						verdi[krav.id].startverdi = krav.beløp
						
						utlikningsskjema.add(verdi[krav.id]);

					}
					utlikningsvindu.show();
				}
				else {
				}
			 }
		});
		
	}

	frakople = function(innbetalingsid) {
		Ext.Ajax.request({
			waitMsg: 'Løsner...',
			url: "index.php?oppslag=innbetalingskort&oppdrag=manipuler&data=frakople&id=<?php echo $innbetaling;?>",
			params: {
				innbetalingsid: innbetalingsid
			},
			success : function(result){
			 	indrepanel.getLoader().load('index.php?oppslag=innbetalingskort&oppdrag=hentdata&id=<?php echo $innbetaling;?>');
			 }
		});
	}

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
			storeId: 'leieforholdliste',
		
			autoLoad: false,
			proxy: {
				type: 'ajax',
				url: "index.php?oppslag=innbetalingskort&oppdrag=hentdata&data=leieforholdforslag",
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


	var indrepanel = Ext.create('Ext.panel.Panel', {
		autoLoad: 'index.php?oppslag=innbetalingskort&oppdrag=hentdata&id=<?php echo $innbetaling;?>',
		layout: 'anchor',
		frame: true,
        bodyStyle: 'padding:5px',
		title: '',
		border: false
	});

	var panel = Ext.create('Ext.panel.Panel', {
		renderTo: 'panel',
        autoScroll: true,
        layout: 'anchor',
		border: false,
		items: [
			{
				xtype: 'displayfield',
				value: '<?php echo addslashes($html);?>'
			},
			indrepanel
		],
        bodyStyle: 'padding:5px',
		title: '',
		frame: true,
		height: 500,
		plain: true,
		width: 900,
		buttons: [{
			text: 'Endre',
			handler: function() {
				window.location = 'index.php?oppslag=betalingsskjema&id=<?php echo $innbetaling;?>';
			}
		}, {
			text: 'Tilbake',
			handler: function() {
				window.location = '<?php echo $this->returi->get();?>';
			}
		}]
	});

});
<?php
}

function design() {
?>
<div id="panel"></div>
<?php
}




function hentData( $data = "" ) {
	$this->oppdaterUbetalt();
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
	}

	case "utlikningsmuligheter": {
		$tp = $this->mysqli->table_prefix;
		$resultat = (object)array(
			'success'	=> false,
			'data'		=> array()
		);
		$innbetaling = $this->hent('Innbetaling', $_GET['id']);
		if( !$innbetaling->hentId() ) {
			$resultat->msg = "Ugyldig betaling";
			return $resultat;
		}
		$delbeløp = $innbetaling->hentDelbeløp( $_POST['innbetalingsid'] );
		if( !$delbeløp ) {
			$resultat->msg = "Ugyldig delbeløp";
			return $resultat;
		}
		$leieforhold = $delbeløp->leieforhold;
		$utbetaling  = ($innbetaling->hent('beløp') < 0) ? true : false;
		
		$motbetalinger = $this->mysqli->arrayData(array(
			'source'	=> "{$tp}innbetalinger AS innbetalinger",
			'fields'	=> "innbetalinger.leieforhold, SUM(innbetalinger.beløp) as beløp",
			'groupfields'	=> "innbetalinger.leieforhold",
			'where'		=> "innbetalinger.krav IS NULL
							AND innbetalinger.leieforhold = '{$leieforhold}'
							AND innbetalinger.innbetaling != '{$innbetaling}'
							AND beløp " . ($utbetaling ? ">" : "<") . " 0"
		));
		
		$motkrav = $this->mysqli->arrayData(array(
			'class'			=> "Krav",
			'source'		=> "{$tp}krav as krav INNER JOIN {$tp}kontrakter as kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
			'fields'		=> "krav.id",
			'orderfields'	=> "IF(forfall IS NULL, 1, 0), forfall, kravdato",
			
			'where'			=> "krav.utestående " . ($utbetaling ? "<" : ">") . " 0\n"
							.	"AND kontrakter.leieforhold = '{$leieforhold}'"
		));
		
		foreach( $motbetalinger->data as $betaling ) {
			$resultat->data[] = array(
				'id'	=>	0,
				'tekst'	=>	$utbetaling ? "Innbetalinger" : "Utbetalinger",
				'beløp'	=>	-$betaling->beløp
			);
		}
		
		foreach( $motkrav->data as $krav ) {
			$resultat->data[] = array(
				'id'	=>	$krav->hentId(),
				'tekst'	=>	$krav->hent('tekst'),
				'beløp'	=>	$krav->hent('beløp')
			);
		}
		
		$resultat->success = true;		
		return json_encode($resultat);
		break;
	}

	default: {
		$innbetaling = $this->hent('Innbetaling', $_GET['id']);
		$utbetaling  = ($innbetaling->hent('beløp') < 0) ? true : false;
		
		$utlikninger = $innbetaling->hent('delbeløp');
?>
	<div class="dataload">
		<table width="870px">
			<tr>
				<th class="value">Beløp</th>
				<th>Leieforhold</th>
				<th>Utliknet mot</th>
				<th>&nbsp;</th>
			</tr>
			<?php foreach($utlikninger as $utlikning):?>
			<tr>
				<td class="value"><?php echo $this->kr(abs($utlikning->beløp));?></td>
				<td>
				<?php if($utlikning->leieforhold):?>
					<a title="Klikk her for å åpne detaljene for leieforholdet." href="index.php?oppslag=leieforholdkort&id=<?php echo $utlikning->leieforhold;?>"><?php echo "{$utlikning->leieforhold}: {$utlikning->leieforhold->hent('beskrivelse')}";?></a>&nbsp;&nbsp;<a style="cursor: pointer;" onClick="angiLeieforhold(<?php echo $utlikning->id;?>, '<?php echo $innbetaling;?>', <?php echo $utlikning->leieforhold;?>)">[Endre leieforhold]</a>
				<?php else:?>
					<i>ikke knyttet til leieforhold</i>&nbsp;&nbsp;<a style="cursor: pointer;" onClick="angiLeieforhold(<?php echo $utlikning->id;?>, '<?php echo $innbetaling;?>')">[Angi leieforhold]</a>
				<?endif;?>
				</td>
				<td>
				<?php if($utlikning->krav instanceof Krav):?>
					<a href="index.php?oppslag=krav_kort&id=<?php echo $utlikning->krav;?>"><?php echo $utlikning->krav->hent('tekst');?></a>
				<?php elseif($utlikning->krav !== null):?>
					Utliknet mot <?php echo $utbetaling ? "inn" : "ut";?>betalinger
				<?php else:?>
					<i>ikke utliknet</i>
				<?php endif;?>
				</td>
				<td>
				<?php if($utlikning->krav !== null):?>
					<a style="cursor: pointer;" onClick="frakople(<?php echo $utlikning->id;?>)">[Løsne]</a>
				<?php else:?>
					<a style="cursor: pointer;" onClick="kople(<?php echo $utlikning->id;?>, <?php echo $utlikning->beløp;?>, <?php echo ($utlikning->leieforhold ? $utlikning->leieforhold : 'null');?>)">[Kople]</a>
				<?php endif;?>
				</td>
			</tr>
			<?php endforeach;?>
		</table>
	</div>
<?php
		break;
	}
	}
}




function manipuler($data = "") {
	$tp = $this->mysqli->table_prefix;
	$resultat = new stdClass;

	switch ($data) {

	case "frakople": {
		$innbetaling = $this->hent('Innbetaling', $_GET['id']);
		$motkrav = $this->mysqli->arrayData(array(
			'source'	=> "{$tp}innbetalinger as innbetalinger",
			'where'		=> "innbetalinger.innbetalingsid = '{$this->POST['innbetalingsid']}'",
			'fields'	=> "krav, leieforhold"
		))->data[0];
		
		if($motkrav->krav > 0) {
			$motkrav->krav = $this->hent('Krav', $motkrav->krav);
			$motkrav->leieforhold = $motkrav->krav->hent('leieforhold');
		}
		else if ($motkrav->krav === '0') {
			$motkrav->krav = new Innbetaling;
			$motkrav->leieforhold = $this->hent('Leieforhold', $motkrav->leieforhold);
		}
		else {
			echo json_encode(array(
				'success'	=> false
			));
			return;
		}
		
		$resultat->success = $innbetaling->frakople( $motkrav->krav, false, $motkrav->leieforhold );

		$innbetaling->samle();
		
		echo (json_encode($resultat));
		break;
	}

	case "kople": {
		$resultat = (object)array(
			'success' => false,
		);
		$innbetaling = $this->hent('Innbetaling', $_GET['id']);
		$leieforhold = $this->hent('Leieforhold', $_POST['leieforhold']);
		
		if( $innbetaling->hentId() ) {
			foreach( $_POST as $parameter => $verdi ) {
				if( substr($parameter, 0, 5) == 'verdi' ) {
					$beløp = str_replace( ',', '.', $verdi );
					if( substr($parameter, 5 ) ) {
						$motkrav = $this->hent('Krav', substr($parameter, 5 ));
					}
					else {
						$motkrav = new Innbetaling;
					}
					
					$resultat->success = (bool)$innbetaling->fordel($motkrav, $beløp, $leieforhold);
				}
			}		
		}
		
		echo (json_encode($resultat));
		break;
	}

	default: {
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



}
?>