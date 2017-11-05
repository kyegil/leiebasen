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
	$this->område['leieforhold'] = $this->hent('Krav', (int)$_GET['id'])->hent('leieforhold');
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
					<td style="text-align:right;"><?php echo $giro ? ("{$giro}" . ($giro->hent('utskriftsdato') ? " <a href=\"index.php?oppslag=giro&oppdrag=lagpdf&gironr={$giro}\" target=\"_blank\">[Vis giro]</a>" : "")) : "";?></td>
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
		betalt
		<?php echo ($innbetaling->innbetaling->hent('betaler') ? "av {$innbetaling->innbetaling->hent('betaler')} " : "");?>
		<?php echo "den {$innbetaling->innbetaling->hent('dato')->format('d.m.Y')} <a title=\"Gå til betalingskortet\" href=\"index.php?oppslag=innbetalingskort&id={$innbetaling->innbetaling}\">(ref. {$innbetaling->innbetaling->hent('ref')})</a><br />";?>
	<?php endforeach;?>
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


}
?>