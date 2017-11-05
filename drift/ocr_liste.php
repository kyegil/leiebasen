<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

function __construct() {
	parent::__construct();
	$this->ext_bibliotek = 'ext-3.4.0';
}

function skript() {
	$this->returi->reset();
	$this->returi->set();
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	// oppretter datasettet
	var datasett = new Ext.data.JsonStore({
		url:'index.php?oppdrag=hentdata&oppslag=ocr_liste',
		fields: [
			{name: 'registrerer'},
			{name: 'registrert', type: 'date', dateFormat: 'Y-m-d H:i:s'},
			{name: 'id', type: 'float'},
			{name: 'filID', type: 'float'},
			{name: 'forsendelsesnummer', type: 'float'},
			{name: 'oppdragsnummer', type: 'float'},
			{name: 'oppdragskonto'},
			{name: 'avtaleid', type: 'float'},
			{name: 'transaksjonstype', type: 'float'},
			{name: 'transaksjonsnummer', type: 'float'},
			{name: 'oppgjørsdato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'bankdatasentral', type: 'float'},
			{name: 'delavregningsnummer', type: 'float'},
			{name: 'løpenummer', type: 'float'},
			{name: 'beløp', type: 'float'},
			{name: 'kid'},
			{name: 'blankettnummer', type: 'float'},
			{name: 'arkivreferanse'},
			{name: 'oppdragsdato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'debetkonto'},
			{name: 'fritekst'}
		],
		totalProperty: 'totalRows',
		remoteSort: true,
		root: 'data'
    });

	var lastData = function() {
		datasett.baseParams = {
			søkefelt: ""
		};
		datasett.load({
			params: {
				start: 0,
				limit: 100
			}
		});
	}

	lastData();

	var forsendelsesnummer = {
		dataIndex: 'forsendelsesnummer',
		header: 'Forsendelse',
		align: 'right',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return "<a href=\"index.php?oppslag=ocr_kort&id=" + record.data.filID + "\">" + value + "</a>";
		},
		sortable: true,
		width: 70
	};

	var registrerer = {
		dataIndex: 'registrerer',
		header: 'Registrert av',
		hidden: true,
		sortable: true,
		width: 100
	};

	var registrert = {
		dataIndex: 'registrert',
		header: 'Registrert dato',
		hidden: true,
		renderer: Ext.util.Format.dateRenderer('d.m.Y H:i:s'),
		sortable: true,
		width: 110
	};

	var oppdragsnummer = {
		dataIndex: 'oppdragsnummer',
		header: 'Oppdrag',
		hidden: true,
		align: 'right',
		sortable: true,
		width: 50
	};

	var oppdragskonto = {
		dataIndex: 'oppdragskonto',
		header: 'Oppdragskto',
		hidden: true,
		sortable: true,
		width: 70
	};

	var avtaleid = {
		dataIndex: 'avtaleid',
		header: 'AvtaleID',
		align: 'right',
		hidden: true,
		sortable: true,
		width: 50
	};

	var transaksjonstype = {
		dataIndex: 'transaksjonstype',
		header: 'Transaksjonstype',
		align: 'right',
		hidden: false,
		renderer: function(v){
			ttype = "";
			if(v == 10) ttype = 'Giro belastet konto';
			if(v == 11) ttype = 'Faste Oppdrag';
			if(v == 12) ttype = 'Direkte Remittering';
			if(v == 13) ttype = 'BTG (Bedrifts Terminal Giro)';
			if(v == 14) ttype = 'SkrankeGiro';
			if(v == 15) ttype = 'AvtaleGiro';
			if(v == 16) ttype = 'TeleGiro';
			if(v == 17) ttype = 'Giro betalt kontant';
			if(v == 18) ttype = 'Reversering med KID';
			if(v == 19) ttype = 'Kjøp med KID';
			if(v == 20) ttype = 'Reversering med fritekst';
			if(v == 21) ttype = 'Kjøp med fritekst';
			return v + ": " + ttype;
		},
		sortable: true,
		width: 100
	};

	var transaksjonsnummer = {
		dataIndex: 'transaksjonsnummer',
		header: 'Tr.nr',
		align: 'right',
		sortable: true,
		width: 30
	};

	var oppgjørsdato = {
		dataIndex: 'oppgjørsdato',
		header: 'Oppgjørsdato',
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		sortable: true,
		width: 80
	};

	var bankdatasentral = {
		dataIndex: 'bankdatasentral',
		header: 'Bankdatasentral',
		align: 'right',
		hidden: true,
		sortable: true,
		width: 90
	};

	var delavregningsnummer = {
		dataIndex: 'delavregningsnummer',
		header: 'Delavregn',
		align: 'right',
		sortable: true,
		width: 70
	};

	var løpenummer = {
		dataIndex: 'løpenummer',
		header: 'Løpenr',
		align: 'right',
		sortable: true,
		width: 50
	};

	var beløp = {
		dataIndex: 'beløp',
		header: 'Beløp',
		align: 'right',
		renderer: Ext.util.Format.noMoney,
		sortable: true,
		width: 70
	};

	var kid = {
		dataIndex: 'kid',
		header: 'KID',
		sortable: true,
		width: 90
	};

	var blankettnummer = {
		dataIndex: 'blankettnummer',
		header: 'Blankettnr',
		align: 'right',
		hidden: true,
		sortable: true,
		width: 70
	};

	var arkivreferanse = {
		dataIndex: 'arkivreferanse',
		header: "Nets' Arkivref",
		align: 'right',
		sortable: true,
		width: 80
	};

	var oppdragsdato = {
		dataIndex: 'oppdragsdato',
		header: 'Oppdragsdato',
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		sortable: true,
		width: 80
	};

	var debetkonto = {
		dataIndex: 'debetkonto',
		header: 'Debetkto',
		sortable: true,
		width: 80
	};

	var fritekst = {
		dataIndex: 'fritekst',
		header: 'Fritekst',
		sortable: true,
		width: 100
	};


	var søkefelt = new Ext.form.TextField({
		fieldLabel: 'Søk',
		name: 'søkefelt',
		width: 200,
		listeners: {
			'valid': function(){
				datasett.baseParams = {søkefelt: søkefelt.getValue()};
				datasett.load({params: {start: 0, limit: 300}});
			}
		}
	});

	var bunnlinje = new Ext.PagingToolbar({
		pageSize: 300,
		items: [
			søkefelt
		],
		store: datasett,
		displayInfo: true,
		displayMsg: 'Viser linje {0} - {1} av {2}',
		emptyMsg: "Venter på resultat"
	});


	var rutenett = new Ext.grid.GridPanel({
		store: datasett,
		title: 'Registrerte OCR-oppdrag med innbetalinger',
		columns: [
			oppgjørsdato,
			forsendelsesnummer,
			løpenummer,
			transaksjonsnummer,
			registrerer,
			registrert,
			oppdragsnummer,
			oppdragskonto,
			avtaleid,
			transaksjonstype,
			bankdatasentral,
			delavregningsnummer,
			beløp,
			kid,
			blankettnummer,
			arkivreferanse,
			oppdragsdato,
			debetkonto,
			fritekst
		],
		autoExpandColumn: 10,
		stripeRows: true,
		height: 500,
		width: 900,
		bbar: bunnlinje

    });

	rutenett.on({
		rowdblclick: function(grid, rowIndex, e){
			window.location = "index.php?oppslag=ocr_kort&id=" + datasett.getAt(rowIndex).get('filID');
		},
		activate: function(grid, rowIndex, e){
			bevegelsesliste.getView().focusRow(passerte_datoer);
		}
	});


	// Rutenettet rendres in i HTML-merket '<div id="panel">':
	rutenett.render('panel');

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
		$tp = $this->mysqli->table_prefix;
		$søkefelt = @$this->POST['søkefelt'];
		$sort = @$this->POST['sort'];
		$dir = @$this->POST['dir'];
		$limit = isset($_POST['start']) ? ((int)$_POST['start'] . ", " . (int)$_POST['limit']): "";
		
		$filter = (
			$søkefelt
			? ("ocr_filer.forsendelsesnummer LIKE '%{$søkefelt}%'\nOR ocr_filer.forsendelsesnummer LIKE '%{$søkefelt}%'\nOR ocr_filer.oppgjørsdato LIKE '" . date('Y-m-d', strtotime($søkefelt)) . "'\nOR OCRdetaljer.oppgjørsdato LIKE '" . date('Y-m-d', strtotime($søkefelt)) . "'\nOR OCRdetaljer.oppdragsdato LIKE '" . date('Y-m-d', strtotime($søkefelt)) . "'\nOR OCRdetaljer.løpenummer LIKE '%{$søkefelt}%'\nOR OCRdetaljer.beløp LIKE '%" . str_replace(",", ".", $søkefelt) . "%'\nOR OCRdetaljer.kid LIKE '%{$søkefelt}%'\nOR OCRdetaljer.blankettnummer LIKE '%{$søkefelt}%'\nOR OCRdetaljer.arkivreferanse LIKE '%{$søkefelt}%'\nOR OCRdetaljer.debetkonto LIKE '%{$søkefelt}%'\nOR OCRdetaljer.fritekst LIKE '%{$søkefelt}%'")
			: NULL
		);

		$resultat = $this->mysqli->arrayData(array(
			'source'		=> "{$tp}ocr_filer AS ocr_filer LEFT JOIN {$tp}OCRdetaljer AS OCRdetaljer ON ocr_filer.filID = OCRdetaljer.filID",
			
			'fields'		=> "ocr_filer.registrerer, ocr_filer.registrert, OCRdetaljer.*",
			
			'orderfields'	=> ($sort ? "{$sort} {$dir}\n" : "OCRdetaljer.oppgjørsdato DESC, ocr_filer.forsendelsesnummer DESC, OCRdetaljer.transaksjonsnummer\n"),

			'limit'			=> $limit,
			
			'where' 		=> $filter,
			'returnQuery'	=> true
		));
		return json_encode($resultat);
	}
	}
}



function taimotSkjema() {
	echo json_encode($resultat);
}



}
?>