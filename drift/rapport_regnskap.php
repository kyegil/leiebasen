<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Datautdrag';
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
		title: '',
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
				window.open('index.php?oppslag=rapport_regnskap&oppdrag=utskrift' + '&fra=' + Ext.util.Format.date( fradato.getValue(), 'Y-m-d' ) + '&til=' + Ext.util.Format.date( tildato.getValue(), 'Y-m-d' ) );
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
		$resultat = $this->mysqli->arrayData( $this->hoveddata );
		
		foreach( $resultat->data as $innbetaling ) {
		
			$innbetaling->leieforholdbesk = $this->liste( $this->kontraktpersoner( $this->sistekontrakt( $innbetaling->leieforhold )))
			. " i "
			. $this->leieobjekt( $this->kontraktobjekt( $innbetaling->leieforhold ) );
		}
		
		return json_encode($resultat);
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
	
	$kravsett = (object)array(
		'beløp'		=> 0,
		'krav'		=> $this->mysqli->arrayData(array(
			'class'		=> "Krav",
			'source'	=> "{$tp}krav",
			'fields'	=> "id",
			'where'		=> "kravdato >= '{$this->fra}' and kravdato <= '{$this->til}'"
		))->data,
		'typer'	=> array()
	);
	
	foreach($kravsett->krav as $krav) {
		$kravtype = $krav->hent('type');
		
		if($kravtype == "Annet") {
			$kravtype = $krav->hent('tekst');
		}
	
		settype($kravsett->typer["$kravtype"], 'object');
		settype($kravsett->typer["$kravtype"]->delkrav, 'array');
		settype($kravsett->typer["$kravtype"]->beløp, 'string');
		$kravsett->typer["$kravtype"]->krav[] = $krav;
		$kravsett->beløp = bcadd(
			$kravsett->beløp,
			$krav->hent('beløp'),
			2
		);
		$kravsett->typer["$kravtype"]->beløp = bcadd(
			$kravsett->typer["$kravtype"]->beløp,
			$krav->hent('beløp'),
			2
		);
		
		If( $kravtype == "Husleie" ) {
			settype($kravsett->typer["$kravtype"]->delkrav['solfond'], 'object');
			$kravsett->typer["$kravtype"]->delkrav['solfond']->beskrivelse = "Bidrag til Svartlamon Solidaritetsfond";
			settype($kravsett->typer["$kravtype"]->delkrav['solfond']->beløp, 'string');
			$kravsett->typer["$kravtype"]->delkrav['solfond']->beløp = bcadd(
				$kravsett->typer["$kravtype"]->delkrav['solfond']->beløp,
				$krav->hent('solidaritetsfondbeløp'),
				2
			);
		}
	}

// Spørring for å hente alle krav fram til den aktuelle datoen for etterbehandling i php: (Ca 0,1 sek)
	$utestående = (object)array(
		'beløp'		=> 0,
		'krav'		=> $this->mysqli->arrayData(array(
			'source'	=> "{$tp}krav AS krav
							LEFT JOIN
							(
								SELECT krav, sum(beløp) AS innbetalt
								FROM {$tp}innbetalinger
								WHERE dato <= '{$this->til}'
								GROUP BY krav
							) AS innbetalinger
							ON krav.id = innbetalinger.krav",
			'fields'	=> "krav.id,
							krav.kravdato,
							krav.beløp,
							(krav.beløp - IFNULL(innbetalinger.innbetalt, 0)) AS rest",
			'where'		=> "kravdato <= '{$this->til}'"
		))->data,
		'tidsrom'	=> array()
	);
	

	foreach($utestående->krav as $krav) {
		if($krav->rest) {
			$utestående->beløp = bcadd(
				$utestående->beløp,
				$krav->rest,
				2
			);
			
			if(substr($krav->kravdato, 0, 4) < (substr($this->fra, 0, 4) -3)) {
				settype( $utestående->tidsrom['0000-00'], 'object');
				settype( $utestående->tidsrom['0000-00']->beløp, 'string');
				$utestående->tidsrom['0000-00']->navn = "fra før " . (substr($this->fra, 0, 4) -3);
				$utestående->tidsrom['0000-00']->beløp = bcadd(
					$utestående->tidsrom['0000-00']->beløp,
					$krav->rest,
					2
				);
			}
			
			else if(substr($krav->kravdato, 0, 4) < (substr($this->fra, 0, 4) -1)) {
				settype( $utestående->tidsrom[substr($krav->kravdato, 0, 4) . "-00"], 'object');
				settype( $utestående->tidsrom[substr($krav->kravdato, 0, 4) . "-00"]->beløp, 'string');
				$utestående->tidsrom[substr($krav->kravdato, 0, 4) . "-00"]->navn = "fra " . substr($krav->kravdato, 0, 4);
				$utestående->tidsrom[substr($krav->kravdato, 0, 4) . "-00"]->beløp = bcadd(
					$utestående->tidsrom[substr($krav->kravdato, 0, 4) . "-00"]->beløp,
					$krav->rest,
					2
				);
			}
			
			else {
				settype( $utestående->tidsrom[date('Y-m', strtotime($krav->kravdato))], 'object');
				settype( $utestående->tidsrom[date('Y-m', strtotime($krav->kravdato))]->beløp, 'string');
				$utestående->tidsrom[date('Y-m', strtotime($krav->kravdato))]->navn = "fra " . strftime('%B %Y', strtotime($krav->kravdato));
				$utestående->tidsrom[date('Y-m', strtotime($krav->kravdato))]->beløp = bcadd(
					$utestående->tidsrom[date('Y-m', strtotime($krav->kravdato))]->beløp,
					$krav->rest,
					2
				);
			}
		}
	}
	ksort($utestående->tidsrom);

// Spørring som oppsummerer (treg): (Ca 35 sek)
// 	$utestående = (object)array(
// 		'beløp'		=> 0,
// 		'krav'		=> $this->mysqli->arrayData(array(
// 			'source'	=> "{$tp}krav AS krav
// 							LEFT JOIN
// 							(
// 								SELECT krav, sum(beløp) AS innbetalt
// 								FROM {$tp}innbetalinger
// 								WHERE dato <= '2015-04-30'
// 								GROUP BY krav
// 							) AS innbetalinger
// 							ON krav.id = innbetalinger.krav",
// 			'fields'	=> "LEFT(krav.kravdato, 7) AS måned,
// 							SUM(krav.beløp - IFNULL(innbetalinger.innbetalt, 0)) AS rest",
// 			'where'		=> "kravdato <= '{$this->til}'",
// 			'groupfields'	=> "måned"
// 		))->data,
// 		'tidsrom'	=> array()
// 	);
// 	
// 	foreach($utestående->krav as $mnd) {
// 		$utestående->beløp = bcadd(
// 			$utestående->beløp,
// 			$mnd->rest,
// 			2
// 		);
// 	}

	$innbetalingssett = (object)array(
		'beløp'		=> 0,
		'innbetalinger'		=> $this->mysqli->arrayData(array(
			'distinct'	=> true,
			'class'		=> "Innbetaling",
			'source'	=> "{$tp}innbetalinger AS innbetalinger",
			'fields'	=> "innbetaling AS id",
			'where'		=> "dato >= '{$this->fra}' and dato <= '{$this->til}'"
		))->data,
		'typer'	=> array()
	);
	
	foreach($innbetalingssett->innbetalinger as $innbetaling) {
		$type = $innbetaling->hent('konto');
		$beløp = $innbetaling->hent('beløp');
		$delbeløp = $innbetaling->hent('delbeløp');
		
		$innbetalingssett->beløp = bcadd(
			$innbetalingssett->beløp,
			$beløp,
			2
		);
		
		settype($innbetalingssett->typer[$type], 'object');
		$type = $innbetalingssett->typer[$type];
		settype($type->beløp, 'string');
		settype($type->ikkeUtliknet, 'string');
		
		$type->beløp = bcadd(
			$type->beløp,
			$beløp,
			2
		);
		
		foreach($delbeløp as $del) {
			if($del->krav) {
				$kravtype = $del->krav->hent('type');
				settype($type->kravtidsrom[$del->krav->hent('kravdato')->format('Y-m-01')], 'object');
				$kravtidsrom = $type->kravtidsrom[$del->krav->hent('kravdato')->format('Y-m-01')];
				settype($kravtidsrom->beløp, 'string');
				
				$kravtidsrom->beløp
					= bcadd($kravtidsrom->beløp, $del->beløp, 2);
				
				settype($kravtidsrom->kravtyper[$kravtype], 'string');
				$kravtidsrom->kravtyper[$kravtype]
					= bcadd($kravtidsrom->kravtyper[$kravtype], $del->beløp, 2);
			}
			else {
				$type->ikkeUtliknet = bcadd($type->ikkeUtliknet, $del->beløp, 2);
			}
		}
		
		ksort($type->kravtidsrom);
		
//		$type->innbetalinger[] = $innbetaling;
	}



		
?>
<h1><?=$this->valg['utleier'];?></h1>
<h2>Utdrag fra leiebasen for <?=$tidsrom;?></h2>
<h4>Utsendte betalingskrav:</h4>
<div>

	<?foreach($kravsett->typer as $navn => $type):?>
	<span><?=ucfirst($navn)?>:</span>
	<?=$this->kr($type->beløp)?><br />

	<?if( count($type->delkrav) ):?>
	<div style="text-indent: 30px">Herav:
	
		<?foreach($type->delkrav as $delkrav):?>
			<div>
				<span><?=ucfirst($delkrav->beskrivelse)?>:</span>
				<?=$this->kr($delkrav->beløp)?><br />
			</div>
		<?endforeach;?>

	</div>
	<?endif;?>

	<?endforeach;?>

Totalt: <strong><?=$this->kr($kravsett->beløp)?></strong><br />
</div>

<h4>Utestående ved utløpet av rapportperioden:</h4>
<div>
<?foreach($utestående->tidsrom as $del):?>
	<span><?=ucfirst($del->navn)?>:</span>
	<?=$this->kr($del->beløp)?><br />
<?endforeach;?>
Totalt utestående: <strong><?=$this->kr($utestående->beløp)?></strong><br />
</div>

<h3>Innbetalinger <?=$tidsrom;?>:</h3>
<div>
	<strong>
		<?=$this->kr($innbetalingssett->beløp)?>
	</strong>
	<br />&nbsp;<br />
</div>
<div>

	<?foreach($innbetalingssett->typer as $navn => $type):?>
	<div>
		<span><strong><?=ucfirst($navn)?>:</strong></span><br />
		<strong><?=$this->kr($type->beløp)?></strong><br />

		<?foreach($type->kravtidsrom as $tidsrom => $kravtidsrom):?>
		<div>
			<span>Utliknet mot krav datert <?=strftime('%B %Y', strtotime($tidsrom))?>:</span>
			<?=$this->kr($kravtidsrom->beløp)?> (
			<?foreach($kravtidsrom->kravtyper as $kravtype => $beløp):?>
			<span><?=ucfirst($kravtype)?>:</span>
			<?=$this->kr($beløp)?>
			<?endforeach;?>
		) </div>
		<?endforeach;?>

		<?if($type->ikkeUtliknet):?>
		<div>
			<span>Ikke utliknet per <?=date('d.m.Y')?>:</span>
			<?=$this->kr($type->ikkeUtliknet);?>
		</div>
		<?endif;?>

	</div>
	<br />
	<?endforeach;?><br />

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