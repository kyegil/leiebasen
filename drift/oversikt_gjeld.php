<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Gjeldsoversikt';
public $ext_bibliotek = 'ext-4.2.1.883';
	

function __construct() {
	parent::__construct();
	setlocale(LC_ALL, array("no","nb_NO"));
}

function skript() {
	if( isset( $_GET['returi'] ) && $_GET['returi'] == "default") $this->returi->reset();
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
		idProperty: 'id',
		fields: [ // http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
			{name: 'leieforhold', type: 'int'},
			{name: 'leieforholdbeskrivelse'},
			{name: 'utestående', type: 'float'},
			{name: 'forfalt', type: 'float'},
			{name: 'leiebeløp'},
			{name: 'tilsvAntLeier', type: 'float'},
			{name: 'oppfølging'},
			{name: 'sisteInnbetaling', useNull: true, type: 'date', dateFormat: 'Y-m-d'},
			{name: 'sisteBeløp', type: 'float', useNull: true},
			{name: 'avsluttet', useNull: true, type: 'date', dateFormat: 'Y-m-d'},
			{name: 'oppfølging', useNull: true, type: 'date', dateFormat: 'Y-m-d'},
			{name: 'frosset', type: 'bool'}
		]
	});
	
	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
		clicksToEdit: 1
	});
	
	var rowEditing = Ext.create('Ext.grid.plugin.RowEditing', {
		autoCancel: false,
		listeners: {
			beforeedit: function (grid, e, eOpts) {
				return e.column.xtype !== 'actioncolumn';
			},
		},
	});


	var datasett = Ext.create('Ext.data.Store', {
		model: 'Leieforhold',
		pageSize: 300,
		remoteSort: true,
		proxy: {
			type: 'ajax',
			simpleSortMode: true,
			url: "index.php?oppslag=oversikt_gjeld&oppdrag=hentdata",
			reader: {
				type: 'json',
				root: 'data',
				totalProperty: 'totalRows'
			}
		},
		sorters: [{
			property: 'sisteInnbetaling',
			direction: 'DESC'
		}]
	});
	


	var søkefelt = Ext.create('Ext.form.field.Text', {
		emptyText: 'Søk og klikk ↵',
//		emptyText: 'Søk',
		name: 'søkefelt',
		width: 200,
		listeners: {
			specialkey: function( felt, e, eOpts ) {
				datasett.getProxy().extraParams = {
					søkefelt: søkefelt.getValue()
				};
				datasett.load({
					params: {
						start: 0,
						limit: 300
					}
				});
			}
// 			change: function() {
// 				datasett.getProxy().extraParams = {
// 					søkefelt: søkefelt.getValue(),
// 					leietakere: (leietakerfilter.getValue() ? 1 : 0)
// 				};
// 				sidevelger.moveFirst();
// 				datasett.load({
// 					params: {
// 						start: 0,
// 						limit: 300
// 					}
// 				});
// 			}
		}
	});


	var leietakerfilter = Ext.create('Ext.form.field.Checkbox', {
		boxLabel: 'Kun nåværende leietakere',
		name: 'leietakerfilter',
		inputValue: '1',
		uncheckedValue: '0',
		checked: true,
		width: 200,
		listeners: {
			change: function( box, newValue, oldValue, eOpts ) {
				if( newValue ) {
					avsluttet.hide();
					frosset.hide();
				}
				else {
					avsluttet.show();
					frosset.show();
				}
				datasett.getProxy().extraParams = {
					søkefelt: søkefelt.getValue(),
					leietakere: (newValue ? 1 : 0)
				};
				sidevelger.moveFirst();
				datasett.load({
					params: {
						start: 0,
						limit: 300
					}
				});
			}
		}
	});


	var leieforhold = Ext.create('Ext.grid.column.Column', {
		dataIndex:	'leieforhold',
		header:		'Leieforhold',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return value + ': ' + record.get('leieforholdbeskrivelse');
		},
		sortable:	true,
		width:		100,
		flex:		1
	});

	var gjeld = Ext.create('Ext.grid.column.Column', {
		align:		'right',
		dataIndex:	'utestående',
		header:		'Utestående',
		renderer: function(value, metaData, record, rowIndex, colIndex, store, view) {
			return "<div title=\"Samlet utestående forfalt og ikke forfalt gjeld\">" + Ext.util.Format.noMoney(value) + "</div> ";
		},
		sortable:	true,
		width:		80
	});

	var forfalt = Ext.create('Ext.grid.column.Column', {
		align:		'right',
		dataIndex:	'forfalt',
		header:		'Forfalt',
		renderer:	function(value, metaData, record, rowIndex, colIndex, store, view) {
			return "<div title=\"Forfalt gjeld\">" + Ext.util.Format.noMoney(value) + "</div> ";
		},
		sortable:	true,
		width:		80
	});

	var leiebeløp = Ext.create('Ext.grid.column.Column', {
		align:		'right',
		dataIndex:	'leiebeløp',
		header:		'Leiebeløp',
		renderer:	Ext.util.Format.noMoney,
		hidden:		true,
		sortable:	true,
		width:		70
	});

	var tilsvAntLeier = Ext.create('Ext.grid.column.Column', {
		align:		'right',
		dataIndex:	'tilsvAntLeier',
		header:		'Leier',
		renderer:	function(value, metaData, record, rowIndex, colIndex, store, view) {
			if (value == Math.floor( value )) {
				return value + '&nbsp;mnd';
			}
			else if (value) {
				return '<&nbsp;' + (Math.floor( value ) + 1) + '&nbsp;mnd';
			}
			else {
				return null;
			}
		},
		sortable:	true,
		width:		60
	});


	var sisteInnbetaling = Ext.create('Ext.grid.column.Column', {
		align:		'right',
		dataIndex:	'sisteInnbetaling',
		header:		'Siste innbetaling',
		renderer:	function(value, metaData, record, rowIndex, colIndex, store, view) {
			if (value) {
				return Ext.util.Format.noMoney( record.data.sisteBeløp ) + ' ' + Ext.util.Format.date( value, 'd.m.Y' );
			}
			else {
				return null;
			}
		},
		sortable:	true,
		width:		130
	});


	var avsluttet = Ext.create('Ext.grid.column.Column', {
		dataIndex:	'avsluttet',
		header:		'Avsluttet',
		renderer:	function(value, metaData, record, rowIndex, colIndex, store, view) {
			if (value) {
				return Ext.util.Format.date( value, 'd.m.Y' );
			}
			else {
				return null;
			}
		},
		hidden:		true,
		sortable:	true,
		width:		70
	});

	var frosset = Ext.create('Ext.grid.column.Column', {
		dataIndex: 'frosset',
		header: 'Frosset',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			if(value){
				return "<div title=\"Leieavtalen er fryst, og det vil dermed ikke bli sendt automatiske purringer m.m.\">" + Ext.util.Format.hake(value) + "</div> ";
			}
		},
		align: 'center',
		hidden:		true,
		sortable: true,
		width: 50
	});

	var oppfølging = Ext.create('Ext.grid.column.Column', {
		dataIndex:	'oppfølging',
		header:		'Oppfølging',
		renderer: function(value, metaData, record, rowIndex, colIndex, store) {
			var idag = new Date( <?php echo date('Y');?>, <?php echo date('m') -1;?>, <?php echo date('d');?>, 0, 0, 0, 0 );
			if(Ext.util.Format.date( value, 'Ymd' ) == Ext.util.Format.date( idag, 'Ymd' )) {
				return "<a title=\"Klikk her for å gå til oppfølgingsoversikten for dette leieforholdet (Krever adgang til oppfølging)\" href=../oppfolging/index.php?oppslag=forsiden&leieforhold=" + record.get('leieforhold') + "><span class=\"green\">Oppfølging</span></a>";
			}
			else if(value) {
				return "<a title=\"Videre oppfølging av leieforholdet bør avventes til " + Ext.util.Format.date( value, 'd.m.Y' ) + "\" href=../oppfolging/index.php?oppslag=forsiden&leieforhold=" + record.get('leieforhold') + "><span class=\"orange\">" + Ext.util.Format.date( value, 'd.m.Y' ) + "</span></a>";
			}
			else {
				return "<a title=\"Videre oppfølging av dette leieforholdet er stoppet\" href=../oppfolging/index.php?oppslag=forsiden&leieforhold=" + record.get('leieforhold') + "><span class=\"red\">Stoppet</span></a>";
			}
		},
		sortable:	true,
		width:		90
	});


	var sidevelger = Ext.create('Ext.toolbar.Paging', {
		xtype: 'pagingtoolbar',
		store: datasett,
		dock: 'bottom',
		displayInfo: true
	});

	
	var rutenett = Ext.create('Ext.grid.Panel', {
		autoScroll: true,
		layout: 'border',
		frame: false,
// 		plugins: [{
// 			ptype: 'rowexpander',
// 			rowBodyTpl : ['{html}']
// 		}],
		store: datasett,
        title: 'Gjeldsoversikt',
		listeners: {
			celldblclick: function( panel, td, cellIndex, record, tr, rowIndex, e, eOpts ) {
				window.location = "index.php?oppslag=leieforholdkort&id=" + record.get('leieforhold');
			}
		},
		tbar: [
			søkefelt,
			leietakerfilter
		],
		columns: [
			leieforhold,
			gjeld,
			forfalt,
			sisteInnbetaling,
			leiebeløp,
			tilsvAntLeier,
			avsluttet,
			frosset,
			oppfølging
		],
		renderTo: 'panel',
		height: 500,
		width: 900,

		dockedItems: [sidevelger],

		buttons: [{
			text: 'Skriv ut',
			handler: function() {
				var sorters = datasett.sorters.items[0];
				var søk = søkefelt.getValue();
				var leietakere = leietakerfilter.getValue() ? 1 : 0;

				var url = 'index.php?oppslag=oversikt_gjeld&oppdrag=utskrift';				
				url += '&sort=' + sorters.property + '&dir=' + sorters.direction;
				url += '&leietakere=' + leietakere;
				if( søk ) {
					url += '&søkefelt=' + søk;
				}
				
				window.open(url);
			}
		}, {
			text: 'Tilbake',
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
			}
		}]
	});

	datasett.getProxy().extraParams = {
		søkefelt: søkefelt.getValue(),
		leietakere: (leietakerfilter.getValue() ? 1 : 0)
	};

	datasett.load({
		params: {
			start: 0,
			limit: 300
		}
	});

});
<?php
}


function design() {
?>
<div id="panel"></div>
<?
}


function hentData($data = "") {
	$tp = $this->mysqli->table_prefix;
	switch ($data) {
	
	default: {
		$resultat = (object)array(
			'success'	=> true,
			'data'		=> array()	
		);

		$sort		= @$_GET['sort'];
		$synkende	= @$_GET['dir'] == "DESC" ? true : false;
		$start		= (int)@$_GET['start'];
		$limit		= @$_GET['limit'];
	
		$søkefelt = @$this->GET['søkefelt'];
		$leietakere = @$this->GET['leietakere'];

		$filter = "REPLACE(LOWER(CONCAT(personer.fornavn, ' ', personer.etternavn)), 'å', 'aa') LIKE '%" . str_ireplace('å', 'aa', $søkefelt) . "%' OR CONCAT(personer.fødselsdato, ' ', personer.personnr) LIKE '%{$søkefelt}%'\n";

		$leieforholdsett = $this->mysqli->arrayData(array(
			'distinct'	=> true,
			'class'		=> 'Leieforhold',
			'fields'	=> "kontrakter.leieforhold AS id",
			'source'	=> "{$tp}kontrakter AS kontrakter\n"
						.	"INNER JOIN {$tp}kontraktpersoner AS kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt\n"
						.	"INNER JOIN {$tp}personer AS personer ON personer.personid = kontraktpersoner.person\n"
						.	"LEFT JOIN {$tp}krav AS krav ON kontrakter.kontraktnr = krav.kontraktnr\n",
			'where'			=> "krav.kravdato <= NOW() AND krav.utestående AND ({$filter})"
		));
	
		foreach( $leieforholdsett->data as $leieforhold ) {
			$oppsigelse = $leieforhold->hent('oppsigelse');
			if( $oppsigelse ) {
				$avsluttet = clone $leieforhold->hent('oppsigelse')->fristillelsesdato;
				$avsluttet->sub( new DateInterval('P1D') );
			}
			else {
				$avsluttet = false;
			}
			$html = "";
			
			if(
				!$leietakere
				or !$oppsigelse
				or !$oppsigelse->fristillelsesdato > date_create()
			) {
				$sisteBetaling = $leieforhold->hentSisteBetaling();
				
				$oppfølging = new DateTime;
				if( $leieforhold->hent('avvent_oppfølging') > date_create()) {
					$oppfølging =  $leieforhold->hent('avvent_oppfølging');
				}
				
				if( $leieforhold->hent('stopp_oppfølging') ) {
					$oppfølging =  null;
				}
				
				$resultat->data[] = (object)array(
					'leieforhold'	=> $leieforhold->hentId(),
					'leieforholdbeskrivelse'	=> $leieforhold->hent('beskrivelse'),
					'utestående'	=> $leieforhold->hent('utestående'),
					'forfalt'		=> $leieforhold->hent('forfalt'),
					'leiebeløp'		=> $leieforhold->hent('leiebeløp'),
					'tilsvAntLeier'	=> (
										$leieforhold->hent('leiebeløp')
										?
										(
											12 * $leieforhold->hent('utestående')
											/ ($leieforhold->hent('leiebeløp') * $leieforhold->hent('ant_terminer'))
										)
										: null
										),
					'oppfølging'	=> $oppfølging ? $oppfølging->format('Y-m-d') : null,
					'sisteInnbetaling'	=> $sisteBetaling ? $sisteBetaling->dato->format('Y-m-d') : null,
					'sisteBeløp'	=> $sisteBetaling ? $sisteBetaling->beløp : null,
					'avsluttet'		=> $oppsigelse ? $avsluttet->format('Y-m-d') : false,
					'frosset'		=> $leieforhold->hent('frosset'),
					'html'			=> $html
				);		
			}
		}

		$resultat->data =  $this->sorterObjekter($resultat->data, $sort, $synkende);
		$resultat->totalRows = count($resultat->data);
		$resultat->data = array_slice(  $resultat->data, $start, $limit);
		return json_encode( $resultat );
	}
	}
}


function utskrift() {
	$tp = $this->mysqli->table_prefix;

	$datasett = json_decode($this->hentData(), false);
	$adresser = $datasett->data;
	
?>
<h1>Gjeldsoversikt</h1>
<p>&nbsp;</p>
<table>
	<tr>
		<th>Leieforhold</th>
		<th class="value">Utestående</th>
		<th class="value">Forfalt</th>
		<th class="value">Siste innbetaling</th>
		<th class="value">Ant&nbsp;mnd&nbsp;leie</th>
		<th>Avsluttet</th>
		<th>Frosset</th>
		<th>Oppfølging</th>
	</tr>
	
	<?php foreach( $datasett->data as $leieforhold ):?>

	<tr>
		<td><?php echo $leieforhold->leieforhold;?>: <?php echo $leieforhold->leieforholdbeskrivelse;?></td>
		<td class="value"><?php echo $this->kr($leieforhold->utestående);?></td>
		<td class="value"><?php echo $this->kr($leieforhold->forfalt);?></td>
		<td class="value"><?php echo ($leieforhold->sisteInnbetaling ? ("{$this->kr($leieforhold->sisteBeløp)}&nbsp;" . date('d.m.Y', strtotime($leieforhold->sisteInnbetaling))) : "");?></td>
		<td class="value"><?php echo ($leieforhold->tilsvAntLeier == intval($leieforhold->tilsvAntLeier) ? $leieforhold->tilsvAntLeier : ("<&nbsp;" . (intval($leieforhold->tilsvAntLeier) + 1)));?></td>
		<td><?php echo ($leieforhold->avsluttet ? date('d.m.Y', strtotime($leieforhold->avsluttet)) : "");?></td>
		<td style="text-align:center;"><?php echo $leieforhold->frosset ? "✔︎" : "";?></td>
		<td><?php echo ($leieforhold->oppfølging == date('Y-m-d') ? "" : ($leieforhold->oppfølging == null ? "Stoppet" : "Avventes"));?></td>
	</tr>
	<?php endforeach;?>
</table>
<script type="text/javascript">
	window.print();
</script>
<?php

}


function taimotSkjema() {
	echo json_encode($resultat);
}


}
?>