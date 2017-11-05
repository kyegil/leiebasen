<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Innbetalinger mot krav fra gitt tidsrom';
public $ext_bibliotek = 'ext-4.2.1.883';
	

function __construct() {
	parent::__construct();
	
	if( isset($_GET['oppdrag'] ) and $_GET['oppdrag'] == 'utskrift' ) {
		$this->mal = "_utskrift";
	}
	
		$fra = @$_GET['fra'];
		$til = @$_GET['til'];
		$kravfra = @$_GET['kravfra'];
		$kravtil = @$_GET['kravtil'];
		
		$sort = @$this->GET['sort'];
		$dir = @$this->GET['dir'];
		
		$order = ($sort ? "{$sort} {$dir}, " : "") . "innbetalinger.dato ASC, innbetalinger.ref ASC";
	
		$limit = (@$_GET['limit'] ? ((int)$_GET['start'] . ', ' . (int)$_GET['limit']) : null);
	
		$filter = "1\n";
		$filter .= $fra ? "AND innbetalinger.dato >= '$fra'\n" : "";
		$filter .= $til ? "AND innbetalinger.dato <= '$til'\n" : "";
		$filter .= $kravfra ? "AND krav.kravdato >= '$kravfra'\n" : "";
		$filter .= $kravtil ? "AND krav.kravdato <= '$kravtil'\n" : "";
		
	$this->hoveddata = array(
		'source'		=> "innbetalinger\n"
						.	"INNER JOIN krav on innbetalinger.krav = krav.id",
		'where'			=> $filter,
		'orderfields'	=> $order,
		'limit'			=> $limit,
		'returnQuery'	=> true,
		'fields'		=> "innbetalinger.innbetalingsid,\n"
						.	"innbetalinger.dato,\n"
						.	"innbetalinger.betaler,\n"
						.	"innbetalinger.ref,\n"
						.	"innbetalinger.beløp,\n"
						.	"innbetalinger.konto,\n"
						.	"innbetalinger.leieforhold,\n"
						.	"krav.id,\n"
						.	"krav.kravdato,\n"
						.	"krav.tekst,\n"
						.	"krav.type"
	);
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

	Ext.define('Innbetaling', {
		extend: 'Ext.data.Model',
		idProperty: 'innbetalingsid',
		fields: [
			{name: 'innbetalingsid', type: 'int'},
 			{name: 'dato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'betaler', type: 'string'},
			{name: 'ref', type: 'string'},
			{name: 'beløp', type: 'float'},
			{name: 'konto', type: 'string'},
			{name: 'leieforhold', type: 'int'},
			{name: 'leieforholdbesk', type: 'string'},
			{name: 'id', type: 'int'},
			{name: 'kravdato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'tekst', type: 'string'},
 			{name: 'type', type: 'string'}
		]
	});
	

	var datasett = Ext.create('Ext.data.Store', {
		model: 'Innbetaling',
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
			property: 'dato',
			direction: 'ASC'
		}],
		autoLoad: true
	});
	
	datasett.on('beforeload', function() {
		datasett.getProxy().extraParams.fra = fra.getValue();
		datasett.getProxy().extraParams.til = til.getValue();
		datasett.getProxy().extraParams.kravfra = kravfra.getValue();     
		datasett.getProxy().extraParams.kravtil = kravtil.getValue();     
	});


	var fra = Ext.create('Ext.form.field.Date', {
		fieldLabel: 'fra og med',
		labelAlign: 'top',
		listeners: {
			blur: function() {
				datasett.getProxy().extraParams.start = 0;
				datasett.load();
				pagingtb.moveFirst();
			}
		},
		width: 150,
		format: 'd.m.Y',
		value: '<?=date('Y') - 1;?>-01-01',
		submitFormat: 'Y-m-d'
	});

	var til = Ext.create('Ext.form.field.Date', {
		fieldLabel: 'til og med',
		labelAlign: 'top',
		listeners: {
			blur: function() {
				datasett.getProxy().extraParams.start = 0;
				datasett.load();
				pagingtb.moveFirst();
			}
		},
		width: 150,
		format: 'd.m.Y',
		value: '<?=date('Y') - 1;?>-12-31',
		submitFormat: 'Y-m-d'
	});

	var innbetalingsperiode = Ext.create('Ext.form.FieldSet', {
		title: 'Vis innbetalinger',
		layout: 'hbox',
		items: [fra, til]
	});
	
	var kravfra = Ext.create('Ext.form.field.Date', {
		fieldLabel: 'fra og med.',
		labelAlign: 'top',
		listeners: {
			blur: function() {
				datasett.getProxy().extraParams.start = 0;
				datasett.load();
				pagingtb.moveFirst();
			}
		},
		width: 150,
		format: 'd.m.Y',
		submitFormat: 'Y-m-d'
	});

	var kravtil = Ext.create('Ext.form.field.Date', {
		fieldLabel: 'til og med.',
		labelAlign: 'top',
		listeners: {
			blur: function() {
				datasett.getProxy().extraParams.start = 0;
				datasett.load();
				pagingtb.moveFirst();
			}
		},
		width: 150,
		format: 'd.m.Y',
		value: '<?=date('Y') - 2;?>-12-31',
		submitFormat: 'Y-m-d'
	});

	var kravperiode = Ext.create('Ext.form.FieldSet', {
		title: 'Inkluder innbetalinger mot krav datert',
		layout: 'hbox',
		items: [kravfra, kravtil]
	});
	
	var pagingtb = Ext.create('Ext.toolbar.Paging',{
		store: datasett, 
		dock: 'bottom',
		displayInfo: true
	});

	var rutenett = Ext.create('Ext.grid.Panel', {
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
				dataIndex: 'innbetalingsid',
				text: 'ID',
				width: 60,
				hidden: false,
				sortable: true
			},
			{
				dataIndex: 'dato',
				text: 'Betalt',
				width: 70,
				renderer: Ext.util.Format.dateRenderer('d.m.Y'),
				sortable: true
			},
			{
				dataIndex: 'betaler',
				text: 'Betalt av',
				flex: 1,
				width: 130,
				sortable: true,
 				summaryRenderer: function(value, summaryData, dataIndex) {
 					return 'Sum denne side:'; 
 				}
			},
			{
				dataIndex: 'ref',
				text: 'Ref',
				width: 80,
				sortable: true
			},
			{
				dataIndex: 'beløp',
				text: 'Beløp',
				width: 80,
				renderer: Ext.util.Format.noMoney,
				align: 'right',
				sortable: true,
				summaryType: 'sum',
				summaryRenderer: Ext.util.Format.noMoney

			},
			{
				dataIndex: 'konto',
				text: 'Konto',
				width: 70,
				sortable: true
			},
			{
				dataIndex: 'leieforhold',
				text: 'Leieforhold',
				flex: 1,
				renderer: function(value, metadata, record, rowIndex, colIndex, store) {
					return value + " " + record.data.leieforholdbesk;
				},
				width: 100,
				sortable: true
			},
			{
				dataIndex: 'id',
				text: 'Krav',
				renderer: function(value, metadata, record, rowIndex, colIndex, store) {
					return '<a href="index.php?oppslag=krav_kort&id=' + value + '">' + value + '</a>';
				},
				width: 50,
				sortable: true
			},
			{
				dataIndex: 'kravdato',
				text: 'Kravdato',
				width: 70,
				renderer: Ext.util.Format.dateRenderer('d.m.Y'),
				sortable: true
			},
			{
				dataIndex: 'tekst',
				text: 'Beskrivelse',
				width: 100,
				sortable: true
			},
			{
				dataIndex: 'type',
				text: 'Type',
				width: 100,
				sortable: true
			}
		],
		renderTo: 'panel',
		height: 500,
		width: 900,
		tbar: [
			innbetalingsperiode,
			kravperiode
		],
		buttons: [{
			text: 'Tilbake',
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
			}
		}, {
			text: 'Skriv ut',
			handler: function() {
				window.open('index.php?oppslag=rapport_gjeldsnedbetaling&oppdrag=utskrift' + '&fra=' + Ext.util.Format.date( fra.getValue(), 'Y-m-d' ) + '&til=' + Ext.util.Format.date( til.getValue(), 'Y-m-d' ) + '&kravfra=' + Ext.util.Format.date( kravfra.getValue(), 'Y-m-d' ) + '&kravtil=' + Ext.util.Format.date( kravtil.getValue(), 'Y-m-d' ) + '&sort=' + datasett.sorters.items[0].property + '&dir=' + datasett.sorters.items[0].direction );
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

	$header = "Innbetalinger";
	if( @$_GET['fra'] and @$_GET['til'] ) {
		$header .= " i perioden " . date('d.m.Y', strtotime( $_GET['fra'] )) . " til " . date('d.m.Y', strtotime( $_GET['til'] ));
	}
	else if( @$_GET['fra'] ) {
		$header .= " gjort fra " . date('d.m.Y', strtotime( $_GET['fra'] ));
	}
	else if( @$_GET['til'] ) {
		$header .= " fram til " . date('d.m.Y', strtotime( $_GET['til'] ));
	}

	if( @$_GET['kravfra'] and @$_GET['kravtil'] ) {
		$header .= " for krav datert i tidsrommet " . date('d.m.Y', strtotime( $_GET['kravfra'] )) . " ‑ " . date('d.m.Y', strtotime( $_GET['kravtil'] ));
	}
	else if( @$_GET['kravfra'] ) {
		$header .= " for krav datert fra og med " . date('d.m.Y', strtotime( $_GET['kravfra'] ));
	}
	else if( @$_GET['kravtil'] ) {
		$header .= " for krav datert til og med " . date('d.m.Y', strtotime( $_GET['kravtil'] ));
	}

	$resultat = $this->mysqli->arrayData( $this->hoveddata );
	
	$sum = 0;
	foreach( $resultat->data as $innbetaling ) {
		$sum = bcadd( $sum, $innbetaling->beløp, 3 );
	}
		
?>
<h1><?=$header;?></h1>
<table>
	<tbody>
		<tr>
			<th>ID</th>
			<th>Betalt</th>
			<th>Betalt av</th>
			<th>Ref</th>
			<th>Beløp</th>
			<th>Konto</th>
			<th>Leiefh</th>
			<th>Krav</th>
			<th>Kravdato</th>
			<th>Beskrivelse</th>
			<th>Kravtype</th>
		</tr>
		<?foreach( $resultat->data as $innbetaling ):?>
		<tr>
			<td><?=$innbetaling->innbetalingsid;?></td>
			<td><?=date('d.m.Y', strtotime( $innbetaling->dato ) );?></td>
			<td><?=$innbetaling->betaler;?></td>
			<td><?=$innbetaling->ref;?></td>
			<td class="value"><?=str_replace( ' ', '&nbsp;', number_format( $innbetaling->beløp, 2, ",", " " ) );?></td>
			<td><?=$innbetaling->konto;?></td>
			<td><?=$innbetaling->leieforhold;?></td>
			<td><?=$innbetaling->id;?></td>
			<td><?=date('d.m.Y', strtotime( $innbetaling->kravdato ) );?></td>
			<td><?=$innbetaling->tekst;?></td>
			<td><?=$innbetaling->type;?></td>
		</tr>
		<?endforeach;?>
		<tr>
			<td class="summary bold" colspan="4">Sum</td>
			<td class="summary bold value"><?=str_replace( ' ', '&nbsp;', number_format( $sum, 2, ",", " " ) );?></td>
			<td class="summary bold" colspan="6">&nbsp;</td>
		</tr>
	</tbody>
</table>
<script type="text/javascript">
	window.print();
</script>
<?
}


}
?>