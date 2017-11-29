<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Oversikt over anvendte gironummer';
public $ext_bibliotek = 'ext-4.2.1.883';
	

function __construct() {
	parent::__construct();
	
	$tp = $this->mysqli->table_prefix;

	if( isset($_GET['oppdrag'] ) and $_GET['oppdrag'] == 'utskrift' ) {
		$this->mal = "_utskrift";
	}
	
	$fom = @$_GET['fom'];
	$tom = @$_GET['tom'];

	$sort = @$this->GET['sort'];
	$dir = @$this->GET['dir'];
	
	$order = "{$tp}giroer.gironr ASC";

	$limit = (@$_GET['limit'] ? ((int)$_GET['start'] . ', ' . (int)$_GET['limit']) : null);

	$filter = "1\n";
	$filter .= $fom ? "AND {$tp}giroer.gironr >= '$fom'\n" : "";
	$filter .= $tom ? "AND {$tp}.giroer.gironr <= '$tom'\n" : "";
	
	$this->hoveddata = array();
	
	$giroer = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}giroer LEFT JOIN {$tp}krav ON {$tp}giroer.gironr = {$tp}krav.gironr\n",
		'where'			=> $filter,
		'groupfields'	=> "giroer.gironr\n",
		'orderfields'	=> $order,

		'returnQuery'	=> true,
		'fields'		=> "giroer.gironr,\n"
						.	"giroer.sammensatt,\n"
						.	"giroer.utskriftsdato,\n"
						.	"giroer.format,\n"
						.	"if(count(krav.id), 0, 1) AS forkastet\n"
	))->data;

	foreach( $giroer as $giro ) {
	
		// Dersom gruppa eksisterer
		if(
			isset($gruppe)
			and $gruppe->sammensatt == $giro->sammensatt
			and $gruppe->utskriftsdato == $giro->utskriftsdato
			and $gruppe->format == $giro->format
			and $gruppe->forkastet == $giro->forkastet
			and $gruppe->til == ($giro->gironr - 1)
		) {
			$gruppe->til = $giro->gironr;
		}

		// Dersom ny gruppe må opprettes
		else {
			unset( $gruppe );
			$gruppe = (object)array(
				'fra'			=> $giro->gironr,
				'til'			=> $giro->gironr,
				'sammensatt'	=> $giro->sammensatt,
				'utskriftsdato'	=> $giro->utskriftsdato,
				'format'		=> $giro->format,
				'forkastet'		=> $giro->forkastet
			);
		
			$this->hoveddata[] = &$gruppe;
		}
	}
}


public function skript() {
	$this->returi->reset();
	$this->returi->set();
	$tp = $this->mysqli->table_prefix;
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

	Ext.define('Girosett', {
		extend: 'Ext.data.Model',
		fields: [
			{name: 'fra', type: 'int'},
			{name: 'til', type: 'int'},
			{name: 'forkastet', type: 'bool'},
 			{name: 'sammensatt', type: 'date', dateFormat: 'Y-m-d H:i:s', useNull: true},
 			{name: 'utskriftsdato', type: 'date', dateFormat: 'Y-m-d H:i:s', useNull: true},
			{name: 'format', type: 'string'}
		]
	});
	

	var datasett = Ext.create('Ext.data.Store', {
		model: 'Girosett',
		pageSize: 200,
		remoteSort: true,
		proxy: {
			type: 'ajax',
			simpleSortMode: true,
			url: "index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=hentdata",
			reader: {
				type: 'json',
				root: 'data',
				totalProperty: 'totalRows'
			}
		},
		sorters: [{
			property: 'fra',
			direction: 'ASC'
		}],
		autoLoad: true
	});
	
	datasett.on('beforeload', function() {
		datasett.getProxy().extraParams.fom = fom.getValue();
		datasett.getProxy().extraParams.tom = tom.getValue();
	});


	var fom = Ext.create('Ext.form.field.Number', {
		fieldLabel: 'Start fra gironr',
		labelAlign: 'left',
		listeners: {
			blur: function() {
				datasett.getProxy().extraParams.fom = 0;
				datasett.load();
				pagingtb.moveFirst();
			}
		},
		width: 200
	});

	var tom = Ext.create('Ext.form.field.Number', {
		fieldLabel: 'Til og med gironr',
		labelAlign: 'left',
		listeners: {
			blur: function() {
				datasett.getProxy().extraParams.tom = 0;
				datasett.load();
				pagingtb.moveFirst();
			}
		},
		width: 200
	});

	var girofilter = Ext.create('Ext.form.FieldSet', {
		title: 'Vis gironummer',
		layout: 'hbox',
		items: [fom, tom]
	});
	
	var pagingtb = Ext.create('Ext.toolbar.Paging',{
		store: datasett,
		dock: 'bottom',
		displayInfo: true
	});

	var rutenett = Ext.create('Ext.grid.Panel', {
		title: 'Oversikt over anvendte gironummer',
		autoScroll: true,
		features: [{
			ftype: 'summary'
		}],
		layout: 'border',
		store: datasett,
		title: '',
		dockedItems: [pagingtb],
		columns: [
			{
				dataIndex: 'fra',
				text: 'Nummerserie',
				width: 100,
				hidden: false,
				sortable: true,
				flex: 1,
				renderer: function(value, metadata, record, rowIndex, colIndex, store){
					var til = record.data.til;
					if( value != til ) {
						return value + ' – ' + til ;
					}
					else {
						return value;
					}
					
				}
			},
			{
				dataIndex: 'sammensatt',
				text: 'Tatt i bruk',
				width: 200,
				renderer: Ext.util.Format.dateRenderer('d.m.Y'),
				sortable: true
			},
			{
				dataIndex: 'utskriftsdato',
				text: 'Skrevet ut',
				width: 200,
				renderer: function(value, metadata, record, rowIndex, colIndex, store){
					var forkastet = record.data.forkastet;
					if( forkastet ) {
						return 'Forkastet';
					}
					else {
						return Ext.Date.format(value, 'd.m.Y H:i:s');
					}
					
				},
				sortable: true
			},
			{
				dataIndex: 'format',
				text: 'Format',
				sortable: true
			}
		],
		renderTo: 'panel',
		height: 500,
		width: 900,
		tbar: [
			girofilter
		],
		buttons: [{
			text: 'Tilbake',
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
			}
		}, {
			text: 'Skriv ut',
			handler: function() {
				window.open('index.php?oppslag=rapport-anvendte-gironummer&oppdrag=utskrift' + (fom.getValue() ? '&fom=' + fom.getValue() : '') + (tom.getValue() ? '&tom=' + tom.getValue() : '') );
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
	$tp = $this->mysqli->table_prefix;
	$sort		= @$_GET['sort'];
	$synkende	= @$_GET['dir'] == "DESC" ? true : false;
	$start		= (int)@$_GET['start'];
	$limit		= @$_GET['limit'];

	switch ($data) {
	
	default: {
		$resultat = (object)array(
			'success'	=> true,
			'data'		=> $this->hoveddata
		);
		
		$resultat->totalRows = count($resultat->data);
		$resultat->data = array_slice(  $resultat->data, $start, $limit);
		return json_encode($resultat);
		break;

	}
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

	$header = "Oversikt over anvendte gironummer";
	if( @$_GET['fom'] and @$_GET['tom'] ) {
		$header .= " i serien {$_GET['fom']} – {$_GET['fom']}";
	}
	else if( @$_GET['fom'] ) {
		$header .= " fra gironummer {$_GET['fom']}";
	}
	else if( @$_GET['tom'] ) {
		$header .= " til og med gironummer {$_GET['fom']}";
	}

	$resultat = (object)array(
		'success'	=> true,
		'data'		=> $this->hoveddata
	);
	
	$resultat->totalRows = count($resultat->data);
	
?>
<h1><?php echo $header;?></h1>
<table>
	<tbody>
		<tr>
			<th>Nummerserie</th>
			<th>Tatt i bruk</th>
			<th>Skrevet ut</th>
			<th>Format</th>
		</tr>
		<?php foreach( $resultat->data as $girosett ):?>
		<tr>
			<td><?php echo ( $girosett->fra == $girosett->til ) ? "{$girosett->fra}" : "{$girosett->fra} – {$girosett->til}";?></td>
			<td><?php echo date('d.m.Y', strtotime( $girosett->sammensatt ) );?></td>
			<td><?php echo $girosett->forkastet ? "Forkastet" : (  $girosett->utskriftsdato ? date('d.m.Y H:i:s', strtotime( $girosett->utskriftsdato ) ) : '');?></td>
			<td><?php echo $girosett->format;?></td>
		</tr>
		<?php endforeach;?>
	</tbody>
</table>
<script type="text/javascript">
	window.print();
</script>
<?
}


}
?>