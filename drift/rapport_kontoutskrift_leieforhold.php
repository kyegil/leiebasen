<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Kontoutskrift for leieforhold';
public $ext_bibliotek = 'ext-4.2.1.883';
	

function __construct() {
	parent::__construct();
	
	if( isset($_GET['oppdrag'] ) and $_GET['oppdrag'] == 'utskrift' ) {
		$this->mal = "_utskrift";
	}
}

public function skript() {
	$this->returi->reset();
	$this->returi->set();
	$tp = $this->mysqli->table_prefix;
	
	$now = new DateTime;
	$date = new DateTime((date('Y') - 2) . "-01-01");

	while( $date->format('Y-m') < $now->format('Y-m') ) {
		if ( $date->format('Y') < ($now->format('Y')) ) {
			$a[] = "\t\t\t['" . $date->format('Y-01-01') . "', '" . $date->format('Y-12-31') . "', '" . $date->format('Y') . "']";
			$date->add(new DateInterval('P1Y'));
		}
		else {
			$a[] = "\t\t\t['" . $date->format('Y-m-01') . "', '" . $date->format('Y-m-t') . "', '" . strftime("%B %Y", $date->getTimestamp()) . "']";
			$date->add(new DateInterval('P1M'));
		}
	}
	



?>
Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?=$this->http_host . "/" . $this->ext_bibliotek . "/examples/ux"?>');

Ext.require([
 	'Ext.data.*',
 	'Ext.form.field.*',
    'Ext.layout.container.Border',
 	'Ext.grid.*',
    'Ext.ux.RowExpander'
]);

Ext.onReady(function() {

    Ext.tip.QuickTipManager.init();
	Ext.form.Field.prototype.msgTarget = 'side';
	Ext.Loader.setConfig({enabled:true});
	
<?
	include_once("_menyskript.php");
?>

	Ext.define('Periode', {
		extend: 'Ext.data.Model',
		idProperty: 'fom',
		fields: [
 			{name: 'fom', type: 'date', dateFormat: 'Y-m-d'},
 			{name: 'tom', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'visningsfelt', type: 'string'}
		]
	});
	

	var perioder = Ext.create('Ext.data.Store', {
		model: 'Periode',
		autoLoad: true,
		data : [
<?php echo implode(",\n", $a);?>,
			['','', 'Angi fra- og til-dato'],
		]
	});
	
	var periodevelger = Ext.create('Ext.form.field.ComboBox', {
		autoSelect: true,
		displayField: 'visningsfelt',
		editable: true,
		fieldLabel: 'Periode',
		listeners: {
			select: function(combo, records, eOpts) {
				if(!records[0].data.fom) {
					fradato.enable();
					tildato.enable();
				}
				else {
					fradato.setValue(records[0].data.fom);
					tildato.setValue(records[0].data.tom);
					fradato.disable();
					tildato.disable();
				}
			}
		},
		maxHeight: 600,
		matchFieldWidth: false,
		minChars: 1,
		name: 'periode',
		queryMode: 'local',
		selectOnFocus: true,
		store: perioder,
//		typeAhead: false,
		value: '<?php echo (date('Y') - 1) . "";?>',
		valueField: 'fom',
		width: 500
	});

	var fradato = Ext.create('Ext.form.field.Date', {
		allowBlank: false,
		disabled: true,
		fieldLabel: 'Fra dato',
		format: 'd.m.Y',
		name: 'fradato',
		submitFormat: 'Y-m-d',
		value: '<?=(date('Y')-1) . "-01-01";?>',
		width: 200
	});


	var tildato = Ext.create('Ext.form.field.Date', {
		allowBlank: false,
		disabled: true,
		fieldLabel: 'Til dato',
		format: 'd.m.Y',
		name: 'tildato',
		submitFormat: 'Y-m-d',
		value: '<?=(date('Y')-1) . "-12-31";?>',
		width: 200
	});

	var panel = Ext.create('Ext.panel.Panel', {
		autoScroll: true,
//		layout: 'border',
		title: 'Kontoutskrift per leieforhold',
		renderTo: 'panel',
		height: 500,
		width: 900,
		items: [
			periodevelger,
			fradato,
			tildato
		],
		buttons: [{
			text: 'Tilbake',
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
			}
		}, {
			text: 'Skriv ut',
			handler: function() {
				window.open('index.php?oppslag=rapport_kontoutskrift_leieforhold&oppdrag=utskrift' + '&fra=' + Ext.util.Format.date( fradato.getValue(), 'Y-m-d' ) + '&til=' + Ext.util.Format.date( tildato.getValue(), 'Y-m-d' ) );
			}
		}]
	});


});
<?
}


public function design() {
?>
<div id="panel"></div>
<?
}


public function hentData($data = "") {
	switch ($data) {
	
	default:
		break;

	}
}


public function taimotSkjema($skjema) {
	switch ($skjema) {
	
	default:
		echo json_encode($resultat);
		break;

	}
}


public function oppgave($oppgave) {
	switch ($oppgave) {

	default:
		break;

	}
}


public function utskrift() {
	$tp = $this->mysqli->table_prefix;
	$tid = time();

	if(substr($this->fra, 4) == '-01-01' and substr($this->til, 4) == '-12-31') {
		if(substr($this->fra, 0, 4) == substr($this->til, 0, 4))	 {
			$tidsrom = substr($this->fra, 0, 4);
		}
		else {
			$tidsrom = substr($this->fra, 0, 4) . "–" . $tidsrom = substr($this->til, 0, 4);
		}
	}	
	else if(substr($this->fra, 8) == '01' and substr($this->til, 8) == date('t', strtotime($this->til))) {
		if(substr($this->fra, 0, 8) == substr($this->til, 0, 8)) {
			$tidsrom = strftime("%B %Y", strtotime($this->fra));
		}
		else {
			$tidsrom = strftime("%B %Y", strtotime($this->fra))
			. "–"
			. strftime("%B %Y", strtotime($this->til));
		}
	}
	else {
		$tidsrom = strftime("%A %e. %B %Y", strtotime($this->fra))
		. "–"
		. strftime("%A %e. %B %Y", strtotime($this->til));
	}
	
	$leieforholdsett = $this->mysqli->arrayData(array(
		'source'	=> "{$tp}kontrakter AS kontrakter
					LEFT JOIN {$tp}krav as krav ON kontrakter.kontraktnr = krav.kontraktnr
					LEFT JOIN {$tp}innbetalinger as innbetalinger ON kontrakter.leieforhold = innbetalinger.leieforhold
		",
		'fields'	=> "kontrakter.leieforhold AS id",
		'orderfields'	=> "kontrakter.leieforhold",
		'distinct'	=> true,
		'class'		=> 'Leieforhold',
		'where'		=> "kontrakter.fradato <= '{$this->til}' AND krav.kravdato >= '{$this->fra}' AND krav.kravdato <= '{$this->til}' AND innbetalinger.dato >= '{$this->fra}' AND innbetalinger.dato <= '{$this->til}'"
	))->data;
	
	$forrigeDato = new DateTime($this->fra);
	$forrigeDato->sub( new DateInterval('P1D') );
	
?>
<h1><?=$this->valg['utleier'];?></h1>
<h2>Kontooversikt leieforhold <?=$tidsrom;?></h2>
<div>
	<?php foreach($leieforholdsett as $leieforhold):
		$inngåendeSaldo = $leieforhold->hentSaldoPerDato( $forrigeDato );
		$inn = 0;
		$ut = 0;
		$saldo = $inngåendeSaldo;
		$transaksjoner = $leieforhold->hentTransaksjoner($this->fra, $this->til);
	?>
	<h4>Leieforhold <?php echo "{$leieforhold}: " . $leieforhold->hent('navn');?></h4>
	<table>
		<tr>
			<th>Dato</th>
			<th>Hva</th>
			<th>Inn</th>
			<th>Ut</th>
			<th>Saldo</th>
		</tr>
		<tr>
			<td colspan="4">Saldo per <?php echo $forrigeDato->format('d.m.Y') ;?></td>
			<td class="value"><?php echo $this->kr($inngåendeSaldo);?></td>
		</tr>
		<?php foreach( $transaksjoner as $transaksjon ):?>
			<?php $beløp = $transaksjon->hent('beløp');?>
			<?php if( $transaksjon instanceof Krav ):?>
				<?php $ut = bcadd( $ut, $beløp, 2 );?>
				<?php $saldo = bcsub( $saldo, $beløp, 2 );?>
				<tr>
					<td><?php echo $transaksjon->hent('dato')->format('d.m.Y');?></td>
					<td><?php echo $transaksjon->hent('tekst');?></td>
					<td class="value"></td>
					<td class="value"><?php echo $this->kr($beløp);?></td>
					<td class="value"><?php echo $this->kr($saldo);?></td>
				</tr>
			<?php else:?>
				<?php $inn = bcadd( $inn, $beløp, 2 );?>
				<?php $saldo = bcadd( $saldo, $beløp, 2 );?>
				<tr>
					<td><?php echo $transaksjon->hent('dato')->format('d.m.Y');?></td>
					<td><?php echo $transaksjon->hent('konto');?></td>
					<td class="value"><?php echo $this->kr($beløp);?></td>
					<td class="value"></td>
					<td class="value"><?php echo $this->kr($saldo);?></td>
				</tr>
			<?php endif;?>
		<?php endforeach;?>
		<tr>
			<td class="summary" colspan="2">Per <?php echo date('d.m.Y', strtotime($this->til)) ;?></td>
			<td class="summary value"><?php echo $this->kr($inn);?></td>
			<td class="summary value"><?php echo $this->kr($ut);?></td>
			<td class="summary value"><?php echo $this->kr($saldo);?></td>
		</tr>
	</table>
	<?php endforeach;?>
	
</div>
<br />&nbsp;<br />
<div>Rapporten produsert på <?=(time() - $tid);?> sekunder <?=date('d.m.Y H:i:s');?></div>
<script type="text/javascript">
	window.print();
</script>
<?
}


}
?>