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

	$this->hoveddata = "SELECT * FROM notater WHERE leieforhold = '" . $this->leieforhold($_GET['id']) . "' ORDER BY dato, registrert";
	$this->område['leieforhold'] = $this->leieforhold($_GET['id']);
}

function skript() {
	if( isset( $_GET['returi'] ) && $_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
?>

Ext.onReady(function() {
	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';
<?
	include_once("_menyskript.php");
?>

	// oppretter datasettet
	var datasett = new Ext.data.JsonStore({
		url:'index.php?oppdrag=hentdata&oppslag=notat_liste&id=<?=$_GET['id']?>',
		fields: [
			{name: 'notatnr', type: 'float'},
			{name: 'leieforhold', type: 'float'},
			{name: 'leieforholdbesk'},
			{name: 'dato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'notat'},
			{name: 'henvendelse_fra'},
			{name: 'kategori'},
			{name: 'vedlegg'},
			{name: 'brevtekst'},
			{name: 'dokumentreferanse'},
			{name: 'dokumenttype'},
			{name: 'registrert', type: 'date', dateFormat: 'Y-m-d H:i:s'},
			{name: 'registrerer'}
		],
		root: 'data'
    });
    datasett.load();

	visBrev = function(nr){
		linje = datasett.getAt(nr);
		Ext.MessageBox.show({
			title: 'Brev',
			msg: "<div style=\"text-align: left;\">" + linje.get('brevtekst') + "</div>",
			minWidth: 900,
			maxWidth: 1000
		});
	}


	lastVedlegg = function(nr) {
		window.open(
			'index.php?oppslag=notat_liste&oppdrag=hentdata&data=vedlegg&id=' + nr
		);
	}


	var notatnr = {
		align: 'right',
		dataIndex: 'notatnr',
		header: 'Notatnr',
		hidden: true,
		sortable: true,
		width: 40
	};

	var leieforhold = {
		dataIndex: 'leieforhold',
		header: 'Leieforhold',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return value + " " + record.data.leieforholdbesk;
		},
		sortable: true,
		width: 150
	};

	var dato = {
		dataIndex: 'dato',
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		header: 'Dato',
		sortable: true,
		width: 70
	};

	var notat = {
		dataIndex: 'notat',
		header: 'Notat',
		sortable: true,
		width: 50
	};

	var kategori = {
		dataIndex: 'kategori',
		header: 'Hva',
		sortable: true,
		width: 90
	};

	var brevtekst = {
		dataIndex: 'brevtekst',
		header: 'Brev',
		sortable: true,
		width: 100,
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return value ? "<a style=\"cursor: pointer\" onClick=\"visBrev(" + rowIndex + ")\">Vis</a>" : "";
		}
	};

	var vedlegg = {
		dataIndex: 'vedlegg',
		header: 'Vedlegg',
		sortable: true,
		width: 50,
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return value ? "<a style=\"cursor: pointer\" onClick=\"lastVedlegg(" + record.data.notatnr + ")\"><img src=\"../bilder/binders16.png\" /></a>" : "";
		}
	};

	var dokumentreferanse = {
		dataIndex: 'dokumentreferanse',
		header: 'Ekst. dok',
		renderer: function(value, metaData, record, rowIndex, colIndex, store) {
			return (value ? (value + " ") : "") + (record.data.dokumenttype ? ("(" + record.data.dokumenttype + ")") : "");
		},
		sortable: true,
		width: 120
	};

	var registrert = {
		dataIndex: 'registrert',
		header: 'Lagt inn',
		hidden: true,
		renderer: Ext.util.Format.dateRenderer('d.m.Y H:i:s'),
		sortable: true,
		width: 110
	};

	var registrerer = {
		dataIndex: 'registrerer',
		hidden: true,
		header: 'Lagt inn av',
		sortable: true,
		width: 50
	};


	var rutenett = new Ext.grid.GridPanel({
		autoExpandColumn: 2,
		autoScroll: true,
		store: datasett,
		columns: [
			notatnr,
			dato,
			kategori,
			brevtekst,
			vedlegg,
			dokumentreferanse,
			registrert,
			registrerer
		],
		stripeRows: true,
		height: 500,
		viewConfig: {
//			forceFit: true,
			enableRowBody: true,
			showPreview: true,
			getRowClass : function(record, rowIndex, p, ds){
				if(this.showPreview){
					p.body = '' + record.data.notat + '';
					return 'x-grid3-row-expanded';
				}
			return 'x-grid3-row-collapsed';
			}
		},
		width: 900,
		buttons: [{
			text: 'Tilbake', handler: function(){
				window.location = '<?=$this->returi->get();?>';
			}
		}]
	});

	// Rutenettet rendres in i HTML-merket '<div id="rutenett">':
	rutenett.render('rutenett');

});
<?
}

function design() {
?>
<div id="rutenett"></div>
<?
}

function taimotSkjema() {
	echo json_encode($resultat);
}


function manipuler($data){
	switch ($data) {
		default:
			break;
	}
}


function hentData($data = "") {
	switch ($data) {
	
	default:
	
			$resultat = $this->arrayData($this->hoveddata);
			foreach($resultat['data'] as $linje => $opplysninger) {
				$kontrakt = $this->kontrakt($opplysninger['leieforhold']);
				$resultat['data'][$linje]['leieforholdbesk'] = $this->liste($this->kontraktpersoner($opplysninger['leieforhold'])) . " i " . $this->leieobjekt($kontrakt['leieobjekt']);
			}
			return json_encode($resultat);
	}
}

}
?>