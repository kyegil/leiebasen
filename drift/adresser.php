<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Adresseliste';
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

	Ext.define('Person', {
		extend: 'Ext.data.Model',
		idProperty: 'id',
		fields: [ // http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
			{name: 'id', type: 'int'},
			{name: 'fornavn', type: 'string'},
			{name: 'etternavn', type: 'string'},
			{name: 'er_org', type: 'bool'},
			{name: 'fødselsdato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'personnr'},
			{name: 'adresse'},
			{name: 'postnr'},
			{name: 'poststed'},
			{name: 'land'},
			{name: 'telefon'},
			{name: 'mobil'},
			{name: 'epost'},
			{name: 'html'}
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
		model: 'Person',
		pageSize: 300,
		remoteSort: true,
		proxy: {
			type: 'ajax',
			simpleSortMode: true,
			url: "index.php?oppslag=adresser&oppdrag=hentdata",
			reader: {
				type: 'json',
				root: 'data',
				totalProperty: 'totalRows'
			}
		},
		sorters: [{
			property: 'etternavn',
			direction: 'ASC'
		}],
		autoLoad: {start: 0, limit: 300}
	});
	


	var søkefelt = Ext.create('Ext.form.field.Text', {
//		emptyText: 'Søk og klikk ↵',
		emptyText: 'Søk',
		name: 'søkefelt',
		width: 200,
		listeners: {
// 			specialkey: function( felt, e, eOpts ) {
// 				datasett.getProxy().extraParams = {
// 					søkefelt: søkefelt.getValue()
// 				};
// 				datasett.load({
// 					params: {
// 						start: 0,
// 						limit: 300
// 					}
// 				});
// 			},
			change: function() {
				datasett.getProxy().extraParams = {
					søkefelt: søkefelt.getValue(),
					leietakere: (leietakerfilter.getValue() ? 1 : 0)
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


	var leietakerfilter = Ext.create('Ext.form.field.Checkbox', {
		boxLabel: 'Kun nåværende leietakere',
		name: 'leietakerfilter',
		inputValue: '1',
		uncheckedValue: '0',
		width: 200,
		listeners: {
			change: function( box, newValue, oldValue, eOpts ) {
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


	// Definerer hver enkelt kolonne i rutenettet
	var personid = {
		align:		'right',
		dataIndex:	'id',
		text:		'Nr.',
		sortable:	true,
		hidden:		true,
		width:		40
	};
	
	var fornavn = {
		dataIndex:	'fornavn',
		text:		'Fornavn',
		sortable:	true,
		width:		100
	};

	var etternavn = {
		dataIndex:	'etternavn',
		text:		'Etternavn',
		sortable:	true,
		width:		100
	};

	var fødselsdato = {
		dataIndex:	'fødselsdato',
		text:		'Fødselsdato',
		renderer:	Ext.util.Format.dateRenderer('d.m.Y'),
		sortable:	true,
		width:		100
	};

	var adresse = {
		dataIndex:	'adresse',
		text:		'Adresse',
		sortable:	true,
		width:		200,
		flex:		1
	};

	var telefon = {
		dataIndex:	'telefon',
		text:		'Tlf.',
		sortable:	true,
		width:		80,
		renderer:	function(value, metaData, record, rowIndex, colIndex, store, view) {
			if(value) return '<a href="tel:' + value + '">'+ value + '</a>';
		}
	};

	var mobil = {
		dataIndex:	'mobil',
		text:		'Mobil',
		sortable:	true,
		width:		100,
		renderer:	function(value, metaData, record, rowIndex, colIndex, store, view) {
			if(value) return '<a href="tel:' + value + '">'+ value + '</a>';
		}
	};

	var epost = {
		dataIndex:	'epost',
		text:		'E-post',
		sortable:	true,
		width:		120,
		renderer:	function(value, metaData, record, rowIndex, colIndex, store, view) {
			if(value) return '<a href="mailto:' + value + '">'+ value + '</a>';
		},
		sortable: true,
		width: 150
	};

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
		plugins: [{
			ptype: 'rowexpander',
			rowBodyTpl : ['{html}']
		}],
		store: datasett,
		title: 'Komplett adresseliste',
		listeners: {
			celldblclick: function( panel, td, cellIndex, record, tr, rowIndex, e, eOpts ) {
				window.location = "index.php?oppslag=personadresser_kort&id=" + record.get('id');
			}
		},
		tbar: [
			søkefelt,
			leietakerfilter
		],
		columns: [
			personid,
			fornavn,
			etternavn,
			fødselsdato,
			adresse,
			telefon,
			mobil,
			epost
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

				var url = 'index.php?oppslag=adresser&oppdrag=utskrift';				
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

		$filter = array(
			'or'	=> array(
				"REPLACE(LOWER(CONCAT(personer.fornavn, ' ', personer.etternavn)), 'å', 'aa') LIKE"	=> "%" . str_ireplace(array('Å','å'), 'aa', $søkefelt) . "%",
				"CONCAT(personer.fødselsdato, ' ', personer.personnr) LIKE"								=> "%{$søkefelt}%"
			)
		);
	
		$personer = $this->mysqli->arrayData(array(
			'distinct'		=> true,
			'returnQuery'	=> true,
			'fields'		=> "personer.personid AS id",
			'source'		=> "{$tp}personer AS personer",
			'where'			=> $filter,
			'class'			=> 'Person',
			'orderfields'	=> "personer.personid DESC"
		));
		
		foreach( $personer->data as $person ) {
			if( !$leietakere or $person->hentLeieforhold( new DateTime ) ) {
				$leieforhold = $person->hentLeieforhold();
				$html = "";
			
				foreach( $leieforhold as $lf) {
					$oppsigelse = $lf->hent('oppsigelse');
					if ( $oppsigelse ) {
						$tildato = clone $oppsigelse->fristillelsesdato;
						$tildato->sub( new DateInterval('P1D') );
					}
			
					$html .= '<a href="index.php?oppslag=leieforholdkort&id=' . $lf . '">'
						. ($oppsigelse ? '<del>' : '')
						. "{$lf}"
						. ($oppsigelse ? '</del>' : '')
						. ": "
						. $lf->hent('fradato')->format('d.m.Y')
						. ' – '
						. ($oppsigelse ? $tildato->format('d.m.Y') : '')
						. " "
						. $lf->hent('leieobjekt')->hent('beskrivelse')
						. " "
						. '</a>'
						. '<br>';
				}
			
				$resultat->data[] = (object)array(
					'id'			=> $person->hentId(),
					'fornavn'	=> $person->hent('fornavn'),
					'etternavn'	=> $person->hent('etternavn'),
					'adresse'	=> str_replace("\n", ", ", $person->hent('postadresse')),
					'telefon'	=> $person->hent('telefon'),
					'mobil'		=> $person->hent('mobil'),
					'epost'		=> $person->hent('epost'),
					'fødselsdato'	=> (($fødselsdato = $person->hent('fødselsdato')) ? $fødselsdato->format('Y-m-d') : null),
					'html'		=> $html
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
<h1>Adresseliste</h1>
<p>&nbsp;</p>
<table>
	<tr>
		<th>Fornavn</th>
		<th>Etternavn</th>
		<th>Fødselsdato</th>
		<th>Adresse</th>
		<th>Telefon</th>
		<th>Mobil</th>
		<th>Epost</th>
	</tr>
	
	<?php foreach( $adresser as $adresse ):?>

	<tr>
		<td><?php echo $adresse->fornavn;?></td>
		<td><?php echo $adresse->etternavn;?></td>
		<td><?php echo ($fødselsdato = $adresse->fødselsdato) ? date('d.m.Y', strtotime($fødselsdato)) : "";?></td>
		<td><?php echo $adresse->adresse;?></td>
		<td><?php echo $adresse->telefon;?></td>
		<td><?php echo $adresse->mobil;?></td>
		<td><?php echo $adresse->epost;?></td>
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