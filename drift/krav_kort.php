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
	if(!$_GET['id']) header("Location: index.php");
}

function skript() {
	if(@$_GET['returi'] == "default") {
		$this->returi->reset();
	}
	$this->returi->set();
	$krav			= $this->hent('Krav', (int)$_GET['id']);
	if( !$krav->hentId() ) {
		echo "\nwindow.location = '{$this->returi->get()}';\n";
	}
	$kredittkopling = $krav->hentKredittkopling();
?>

Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?php echo $this->http_host . "/" . $this->ext_bibliotek . "/examples/ux"?>');

Ext.require([
 	'Ext.data.*',
    'Ext.layout.container.Border',
 	'Ext.grid.*',
    'Ext.grid.plugin.BufferedRenderer',
    'Ext.ux.RowExpander'
]);

Ext.onReady(function() {

    Ext.tip.QuickTipManager.init();
	Ext.form.Field.prototype.msgTarget = 'side';
	Ext.Loader.setConfig({
		enabled: true
	});
	
<?
	include_once("_menyskript.php");
?>

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
						url: 'index.php?oppslag=krav_kort&oppdrag=manipuler&data=kople&id=<?php echo $kredittkopling;?>',
						params: {
							leieforhold: leieforhold
						},
						success: function(form, action) {
							panel.getLoader().load('index.php?oppslag=<?php echo $_GET['oppslag'];?>&oppdrag=hentdata&id=<?php echo $kredittkopling;?>');
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
			url: "index.php?oppslag=krav_kort&oppdrag=hentdata&data=utlikningsmuligheter&id=<?php echo $kredittkopling;?>",
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
			url: "index.php?oppslag=krav_kort&oppdrag=manipuler&data=frakople&id=<?php echo $kredittkopling;?>",
			params: {
				innbetalingsid: innbetalingsid
			},
			success : function(result) {
			 	panel.getLoader()
			 		.load('index.php?oppslag=krav_kort&oppdrag=hentdata&id=<?php echo $krav;?>');
			 }
		});
	}

	var panel = Ext.create('Ext.panel.Panel', {
		autoLoad: 'index.php?oppslag=krav_kort&oppdrag=hentdata&id=<?php echo $krav;?>',
        autoScroll: true,
		renderTo: 'panel',
        bodyStyle: 'padding:5px',
		title: '',
		frame: true,
		height: 500,
		width: 900,
		buttons: [{
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
			},
			text: 'Tilbake'
		}, {
			handler: function() {
				window.location = 'index.php?oppslag=kravskjema&id=<?=$_GET['id']?>';
			},
			text: 'Rediger'
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
	switch ($data) {

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
		$krav			= $this->hent('Krav', (int)$_GET['id']);
		$delbeløp		= $krav->hent('delkrav');
		$kravtype		= $krav->hent('type');
		$leieforhold	= $krav->hent('leieforhold');
		$leieobjekt		= $leieforhold->hent('leieobjekt');
		$fom			= $krav->hent('fom') ? $krav->hent('fom')->format('d.m.Y') : "";
		$tom			= $krav->hent('tom') ? $krav->hent('tom')->format('d.m.Y') : "";
		$forfall		= $krav->hent('forfall') ? $krav->hent('forfall')->format('d.m.Y') : "";
		$giro			= $krav->hent('giro');
		$utskriftsdato	= $giro ? $giro->hent('utskriftsdato') : null;
		$kid			= $giro ? $giro->hent('kid') : null;
		$innbetalinger	= $krav->hentUtlikninger();
		$purringer		= $krav->hent('purringer');
		$kredittkopling	= $krav->hentKredittkopling();
		
		$kredittutlikninger = array();
		if( $kredittkopling ) {
			foreach( $kredittkopling->hent('delbeløp') as $kredittdel) {
				if( $kredittdel->beløp > 0 ) {
					$kredittutlikninger[] = $kredittdel;
				}
			}
		}

?>
<h1><?php echo $krav->hent('tekst');?></h1>
<p><a title="Gå til leieforholdet." href="index.php?oppslag=leieforholdkort&id=<?php echo $leieforhold;?>"><?php echo $leieforhold->hent('beskrivelse');?></a></p>
<table>
	<tr>
		<td style="vertical-align:top; padding:0px 20px;">
			<table>
				<tr>
					<td style="font-weight:bold;">Krav nr:</td>
					<td style="text-align:right;"><?php echo $krav;?></td>
				</tr>
				<tr>
					<td style="font-weight:bold;">Dato:</td>
					<td style="text-align:right;"><?php echo $krav->hent('dato')->format('d.m.Y');?></td>
				</tr>
				<tr>
					<td style="font-weight:bold;">Beløp:</td>
					<td style="text-align:right;"><?php echo $this->kr($krav->hent('beløp'));?></td>
				</tr>
				<?php foreach($delbeløp as $del):?>
					<td>Inklusive <?php echo $del->navn;?>:</td>
					<td style="text-align:right;"><?php echo $this->kr($del->beløp);?></td>
				<?php endforeach;?>
				<tr>
					<td style="font-weight:bold;">Utestående:</td>
					<td style="text-align:right;"><?php echo $this->kr($krav->hent('utestående'));?></td>
				</tr>
			</table>
		</td>
		<td style="vertical-align:top; padding:0px 20px;">
			<table>
				<tr>
					<td style="font-weight:bold;">Kravtype:</td>
					<td style="text-align:right;"><?php echo $kravtype;?></td>
				</tr>
				
				<?php if($kravtype == "Husleie"):?>
				<tr>
					<td style="font-weight:bold;">Leieobjekt:</td>
					<td style="text-align:right;"><a title = "Gå til leieobjektkortet." href="index.php?oppslag=leieobjekt_kort&id=<?php echo $leieobjekt;?>"><?php echo $leieobjekt->hent('beskrivelse');?></a></td>
				</tr>
				<?php endif;?>
				
				<?php if($kravtype == "Fellesstrøm"):?>
				<tr>
					<td style="font-weight:bold;">Anleggsnummer:</td>
					<td style="text-align:right;"><?php echo "{$krav->hent('anleggsnr')}";?></td>
				</tr>
				<?php endif;?>
				
				<?php if($kravtype == "Husleie"):?>
				<tr>
					<td style="font-weight:bold;">Andel:</td>
					<td style="text-align:right;"><?php echo ($this->fraBrøk($krav->hent('andel')) == 1) ? "Hele leieobjektet" : $krav->hent('andel');?></td>
				</tr>
				<?php endif;?>
				
				<?php if($kravtype == "Husleie" or $kravtype == "Fellesstrøm"):?>
				<tr>
					<td style="font-weight:bold;">Termin:</td>
					<td style="text-align:right;"><?php echo $krav->hent('termin') ? $krav->hent('termin') : "{$fom} – {$tom}";?></td>
				</tr>
				<?php endif;?>
				
				<?php if($fom || $tom):?>
				<tr>
					<td style="font-weight:bold;">Tidsrom:</td>
					<td style="text-align:right;"><?php echo ( $fom ? ("fra og med {$fom} ") : "") . ( $tom ? ("til og med $tom") : "");?></td>
				</tr>
				<?php endif;?>

			</table>
		</td>
		<td style="vertical-align:top; padding:0px 20px;">
			<table>
				<tr>
					<td style="font-weight:bold;">Gironr:</td>
					<td style="text-align:right;"><?php echo $giro ? ("{$giro}" . ($giro->hent('utskriftsdato') ? " <a href=\"index.php?oppslag=giro&oppdrag=lagpdf&gironr={$giro}\" target=\"_blank\">[Vis&nbsp;giro]</a>" : "")) : "";?></td>
				</tr>
				<tr>
					<td style="font-weight:bold;">Utskriftsdato:</td>
					<td style="text-align:right;"><?php echo $utskriftsdato ? $utskriftsdato->format('d.m.Y') : "Ikke skrevet ut";?></td>
				</tr>
				<tr>
					<td style="font-weight:bold;">Forfallsdato:</td>
					<td style="text-align:right;"><?php echo $forfall;?></td>
				</tr>
				<tr>
					<td style="font-weight:bold;">KID:</td>
					<td style="text-align:right;"><?php echo $kid;?></td>
				</tr>
				<tr>
					<td style="font-weight:bold;">Fast KID:</td>
					<td style="text-align:right;"><?php echo $this->genererKid($leieforhold);?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<?php if($krav->hent('beløp') > 0):?>
	<p>
	<?php foreach($innbetalinger as $innbetaling):?>
		<?php echo $this->kr($innbetaling->beløp);?>
		<?php if($innbetaling->innbetaling->hent('konto') != '0'):?>
			betalt
			<?php echo ($innbetaling->innbetaling->hent('betaler') ? "av {$innbetaling->innbetaling->hent('betaler')} " : "");?>
			<?php echo "den {$innbetaling->innbetaling->hent('dato')->format('d.m.Y')}";?>
		<?php else:?>
			kreditert
		<?php endif;?>
		<?php echo "<a title=\"Gå til betalingskortet\" href=\"index.php?oppslag=innbetalingskort&id={$innbetaling->innbetaling}\">(ref. {$innbetaling->innbetaling->hent('ref')})</a><br />";?>
	<?php endforeach;?>
	</p>
	<p>&nbsp;<br /></p>
	<p>
	<?php foreach($purringer as $purring):?>
		Purret per <?php echo $purring->hent('purremåte');?> den <?php echo $purring->hent('purredato')->format('d.m.Y');?><br>
	<?php endforeach;?>
	</p>

<?php else:?>
	<p>
	<table width="870px">
		<tr>
			<th class="value">Beløp</th>
			<th>Utliknet mot</th>
			<th>&nbsp;</th>
		</tr>
	<?php foreach($kredittutlikninger as $utlikning):?>

		<tr>
			<td class="value"><?php echo $this->kr(abs($utlikning->beløp));?></td>
			<td>
			<?php if($utlikning->krav instanceof Krav):?>
				brukt som betaling for <a href="index.php?oppslag=krav_kort&id=<?php echo $utlikning->krav;?>"><?php echo $utlikning->krav->hent('tekst');?></a>
			<?php elseif($utlikning->krav !== null):?>
				utbetalt
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
	</p>
	<p>&nbsp;<br /></p>
	<p>
	<?php foreach($purringer as $purring):?>
		Purret per <?php echo $purring->hent('purremåte');?> den <?php echo $purring->hent('purredato')->format('d.m.Y');?><br>
	<?php endforeach;?>
	</p>
<?php endif;?>

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




}
?>