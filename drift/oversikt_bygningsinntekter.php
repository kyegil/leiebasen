<?php
/**********************************************
CollectivePOS
by Kay-Egil Hauan
**********************************************/
if(!defined('LEGAL')) die('No access!<br />Check your URI.');

class oppsett extends leiebase {

public $tittel = 'leiebasen';
public $ext_bibliotek = 'ext-4.2.1.883';
	

function __construct() {
	parent::__construct();
	
	if(@$_GET['oppdrag'] == "utskrift") {
		$this->mal = '_utskrift';
	}
	
	$this->tittel = "Inntekter per bygning";

	$filter = "1";
	$filter .= ( isset( $_GET['fra'] ) ? " AND krav.kravdato >= '{$this->fra}'" : "");
	$filter .= ( isset( $_GET['til'] ) ? " AND krav.kravdato <= '{$this->til}'" : "");
	
	$orderfields = ( isset( $_GET['sort'] ) ? "{$this->GET['sort']} {$this->GET['dir']}, " : "");
	$orderfields .= "bygninger.kode ASC, bygninger.id ASC, leieobjektnr";

	$limit = ( isset( $_GET['limit'] ) ? "{$this->GET['start']}, {$this->GET['limit']}" : null);
	
	$source
		= "(
		bygninger INNER JOIN leieobjekter ON bygninger.id = leieobjekter.bygning
		INNER JOIN kontrakter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr
		)
		LEFT JOIN krav ON kontrakter.kontraktnr = krav.kontraktnr
		"
		;
			
	$this->hoveddata = array(
		'returnQuery' => true,
		'source' => $source,
		'fields' => "bygninger.id AS bygningsid, bygninger.kode AS kode, if(bygninger.kode, concat(bygninger.kode, ' ', bygninger.navn), bygninger.navn) AS bygning, kontrakter.leieforhold AS leieforhold, kontrakter.leieobjekt AS leieobjekt, SUM(if(krav.type = 'Husleie', krav.beløp, null)) AS leie, SUM(if(krav.type = 'Fellesstrøm', krav.beløp, null)) AS fellesstrøm, SUM(if(krav.type != 'Husleie' and krav.type != 'Fellesstrøm', krav.beløp, null)) AS annet, SUM(krav.beløp) AS inntekt, SUM(krav.beløp - krav.utestående) AS betalt",
		'groupfields' => "bygninger.id, kontrakter.leieforhold, kontrakter.leieobjekt",
		'where' => $filter,
		'orderfields' => $orderfields
	);
}

function skript() {
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

	Ext.define('Leieforhold', {
		extend: 'Ext.data.Model',
		idProperty: 'leieforhold',
		fields: [
			{name: 'bygningsid', type: 'int'},
			{name: 'kode', useNull: true},
			{name: 'bygning', type: 'text'},
			{name: 'leieforhold', type: 'int', useNull: true},
			{name: 'leieobjekt', type: 'int', useNull: true},
			{name: 'leieforholdbesk'},
			{name: 'leie', type: 'float', useNull: true},
			{name: 'fellesstrøm', type: 'float', useNull: true},
			{name: 'annet', type: 'float', useNull: true},
			{name: 'inntekt', type: 'float', useNull: true},
			{name: 'betalt', type: 'float', useNull: true}
		]
	});

	
	var lastData = function() {
		datasett.getProxy().setExtraParam('fra', Ext.Date.format(fra.getValue(), 'Y-m-d'));
		datasett.getProxy().setExtraParam('til', Ext.Date.format(til.getValue(), 'Y-m-d'));
		datasett.currentPage = 1;
		datasett.load();
	}

	var fra = Ext.create('Ext.form.field.Date', {
		format: 'd.m.Y',
		startDay: 1,
		submitFormat: 'Y-m-d',
		fieldLabel: 'Fra og med dato',
		name: 'fradato',
		maxValue: new Date(),
		value: new Date(<?=$this->fra ? date('Y, m-1, d', strtotime($this->fra)) : (date('Y') . "-1, 0, 1")?>),
		enableKeyEvents: true,
		listeners: {
			keypress: function(field, event, opts) {
				var key = event.getKey();
				if(key == 13) {
					lastData();
				}
			}
		}
	});
	
	var til = Ext.create('Ext.form.field.Date', {
		format: 'd.m.Y',
		startDay: 1,
		submitFormat: 'Y-m-d',
		fieldLabel: 'Til og med dato',
		name: 'tildato',
		value: new Date(<?=$this->til ? date('Y, m-1, d', strtotime($this->til)) : (date('Y') . "-1, 11, 31")?>),
		enableKeyEvents: true,
		listeners: {
			keypress: function(field, event, opts) {
				var key = event.getKey();
				if(key == 13) {
					lastData();
				}
			}
		}
	});

	var datasett = Ext.create('Ext.data.Store', {
		model: 'Leieforhold',
		pageSize: 200,
		remoteSort: true,
		proxy: {
			type: 'ajax',
			extraParams: {
				fra: Ext.Date.format(fra.getValue(), 'Y-m-d'),
				til: Ext.Date.format(til.getValue(), 'Y-m-d')
			},
			simpleSortMode: true,
			url: "index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=hentdata",
			reader: {
				type: 'json',
				root: 'data',
				actionMethods: {
//					read: 'POST'
				},
				totalProperty: 'totalRows'
			}
		},
		sorters: [{
			property: 'kode',
			direction: 'ASC'
		}],
        groupField: 'bygning',
		autoLoad: true
	});
	
	datasett.on({
		'load': function() {
			oppsummering.collapseAll(1);
		}
	});


	var oppsummering = Ext.create('Ext.grid.feature.GroupingSummary', {
		id: 'bygningsid',
		groupHeaderTpl: '{name}',
		hideGroupedHeader: false,
		enableGroupingMenu: false,
		showSummaryRow: true,
		startCollapsed: true
	});


	var rutenett = Ext.create('Ext.grid.Panel', {
		autoScroll: true,
		autoExpandColumn: 2,
		layout: 'border',
        features: [oppsummering],
// 		plugins: [{
// 			ptype: 'rowexpander',
// 			rowBodyTpl : ['{html}']
// 		}],
		store: datasett,
		title: 'Bygninger',
		columns: [
			{
				dataIndex: 'bygningsid',
				hidden: true,
				text: 'ID',
				width: 40,
				sortable: true
			}, {
				dataIndex: 'kode',
				hidden: true,
				text: 'Kode',
				width: 60,
				sortable: true
			}, {
				dataIndex: 'bygning',
				hidden: true,
				text: 'Bygning',
				width: 120,
				sortable: true
			}, {
				dataIndex: 'leieforhold',
				text: 'Leieforhold',
				flex: 1,
				width: 120,
				renderer: function(value, metadata, record, rowIndex, colIndex, store) {
					return value + ' ' + record.get('leieforholdbesk');
				},
				sortable: true
			}, {
				dataIndex: 'leieobjekt',
				text: 'Leieobjekt',
				width: 40,
				sortable: true
			}, {
				dataIndex: 'leie',
				text: 'Leie',
				align: 'right',
				width: 100,
				renderer: function(value, metadata, record, rowIndex, colIndex, store) {
					if(value) return Ext.util.Format.noMoney(value);
				},
				sortable: true,
				summaryType: 'sum',
				summaryRenderer: Ext.util.Format.noMoney
			}, {
				dataIndex: 'fellesstrøm',
				text: 'Fellesstrøm',
				align: 'right',
				width: 100,
				renderer: function(value, metadata, record, rowIndex, colIndex, store) {
					if(value) return Ext.util.Format.noMoney(value);
				},
				sortable: true,
				summaryType: 'sum',
				summaryRenderer: Ext.util.Format.noMoney
			}, {
				dataIndex: 'annet',
				text: 'Annet',
				align: 'right',
				width: 100,
				renderer: function(value, metadata, record, rowIndex, colIndex, store) {
					if(value) return Ext.util.Format.noMoney(value);
				},
				sortable: true,
				summaryType: 'sum',
				summaryRenderer: Ext.util.Format.noMoney
			}, {
				dataIndex: 'inntekt',
				text: 'Totalt',
				align: 'right',
				width: 100,
				renderer: function(value, metadata, record, rowIndex, colIndex, store) {
					if(value) return Ext.util.Format.noMoney(value);
				},
				sortable: true,
				summaryType: 'sum',
				summaryRenderer: Ext.util.Format.noMoney
			}, {
				dataIndex: 'betalt',
				text: 'Betalt',
				align: 'right',
				width: 100,
				renderer: function(value, metadata, record, rowIndex, colIndex, store) {
					return Ext.util.Format.noMoney(value);
				},
				sortable: true,
				summaryType: 'sum',
				summaryRenderer: Ext.util.Format.noMoney
			}
		],
		renderTo: 'panel',
		height: 500,
		width: 900,
		tbar: [fra, til],
		buttons: [{
			text: 'Tilbake',
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
			}
		}, {
			text: 'Skriv ut',
			handler: function() {
				window.open("index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=utskrift&fra=" + Ext.Date.format(fra.getValue(), 'Y-m-d') + "&til=" + Ext.Date.format(til.getValue(), 'Y-m-d'));
			}
		}]
	});

//	rutenett.on('edit', lagreEndringer);


});
<?
}


function design() {
?>
<div id="panel"></div>
<?
}


function utskrift() {
	$parametre = $this->hoveddata;
	$parametre['fields'] = "
		bygninger.id AS bygningsid,
		bygninger.kode AS kode,
		if(bygninger.kode, concat(bygninger.kode, ' ', bygninger.navn), bygninger.navn) AS bygning,
		SUM(if(krav.type = 'Husleie', krav.beløp, null)) AS leie,
		SUM(if(krav.type = 'Fellesstrøm', krav.beløp, null)) AS fellesstrøm,
		SUM(if(krav.type != 'Husleie' and krav.type != 'Fellesstrøm', krav.beløp, null)) AS annet,
		SUM(krav.beløp) AS inntekt, SUM(krav.beløp - krav.utestående) AS betalt
	";
	$parametre['groupfields'] = "bygninger.id";
	$data = $this->mysqli->arrayData($parametre);

?>
<h1><?="Inntekter per bygning tidsrommet " . date('d.m.Y', strtotime($this->fra)) . " - " . date('d.m.Y', strtotime($this->til))?></h1>
<table class = "table1">
<tr>
	<th>Bygning</th>
	<th>Husleie</th>
	<th>Fellesstrøm</th>
	<th>Annet</th>
	<th>Totalt</th>
	<th>Betalt per <?=date('d.m.Y')?></th>
</tr>

<?foreach($data->data as $leieforhold):?>
<tr>
	<td><?=$leieforhold->bygning?></td>
	<td class="value"><?=str_replace(' ', '&nbsp;', number_format($leieforhold->leie, 2, ',', ' '))?></td>
	<td class="value"><?=str_replace(' ', '&nbsp;', number_format($leieforhold->fellesstrøm, 2, ',', ' '))?></td>
	<td class="value"><?=str_replace(' ', '&nbsp;', number_format($leieforhold->annet, 2, ',', ' '))?></td>
	<td class="value"><?=str_replace(' ', '&nbsp;', number_format($leieforhold->inntekt, 2, ',', ' '))?></td>
	<td class="value"><?=str_replace(' ', '&nbsp;', number_format($leieforhold->betalt, 2, ',', ' '))?></td>
</tr>
<?endforeach;?>

</table>
<script type="text/javascript">
window.print();
</script>
<?
}


function hentData($data = "") {
	switch ($data) {
		default:
			$resultat = $this->mysqli->arrayData($this->hoveddata);
			
			foreach($resultat->data as $leieforhold) {
				$leieforhold->leieforholdbesk = ($this->liste($this->kontraktpersoner($this->sistekontrakt($leieforhold->leieforhold)))) . " i " . $this->leieobjekt($leieforhold->leieobjekt);
			}
			return json_encode($resultat);
	}
}


function taimotSkjema($skjema) {
	switch ($skjema) {
		default:
			echo json_encode($resultat);
			break;
	}
}


function oppgave($oppgave) {
	switch ($oppgave) {
		case "slett":
			echo json_encode($resultat);
			break;
		default:
			break;
	}
}

}
?>