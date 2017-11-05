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
	$this->hoveddata = "SELECT * FROM leieobjekter ORDER BY gateadresse, etg, beskrivelse";
}

function skript() {
?>
window.name = 'leieobjekt_liste';


Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

    // egendefinert renderfunksjon
	function hake(val){
		if(val == false){
			return '';
		}else if(val == 1){
			return '<img src="<?$this->http_host;?>/bilder/hake9.png" alt="X"/>';
		}
		return val;
	}

    // egendefinert renderfunksjon
	function etasjerenderer(val){
		switch(val){
			case '+': return 'loft';
			case '5': return '5. etg.';
			case '4': return '4. etg.';
			case '3': return '3. etg.';
			case '2': return '2. etg.';
			case '1': return '1. etg.';
			case '0': return 'sokkel';
			case '-1': return 'kjeller';
			case '': return '';
		}
	}

    // egendefinert renderfunksjon
	function toakatrenderer(val){
		switch(val){
			case '2': return 'Eget toalett';
			case '1': return 'Felles toaletter i bygningen';
			case '0': return 'Ingenting / utendørs';
		}
	}

    // oppretter datasettet
    var datasett = new Ext.data.JsonStore({
    	url:'index.php?oppdrag=hentdata&oppslag=leieobjekt_liste',
        fields: [
           {name: 'leieobjektnr', type: 'float'},
           {name: 'navn'},
           {name: 'gateadresse'},
           {name: 'etg'},
           {name: 'beskrivelse'},
           {name: 'areal', type: 'float'},
           {name: 'bad', type: 'bool'},
           {name: 'toalett'},
           {name: 'toalett_kategori'},
           {name: 'leieberegning'},
           {name: 'merknader'}
        ],
    	root: 'data'
    });
    datasett.load();

	// Definerer hver enkelt kolonne i rutenettet
	var leieobjektnr = {
		align: 'right',
		dataIndex: 'leieobjektnr',
		id: 'leieobjektnr',
		header: 'Nr.',
		sortable: true,
		width: 40
	};
	var navn = {
		dataIndex: 'navn',
		header: 'Navn',
		sortable: true,
		width: 100
	};
	var gateadresse = {
		dataIndex: 'gateadresse',
		header: 'Gateadresse',
		sortable: true,
		width: 140
	};
	var etg = {
		align: 'right',
		dataIndex: 'etg',
		header: 'Etg.',
		renderer: etasjerenderer,
		sortable: true,
		width: 35
	};
	var beskrivelse = {
		dataIndex: 'beskrivelse',
		header: 'Beskrivelse',
		sortable: true,
		width: 100
	};
	var areal = {
		align: 'right',
		dataIndex: 'areal',
		header: 'Areal',
		sortable: true,
		width: 30
	};
	var bad = {
		dataIndex: 'bad',
		header: 'Bad',
		renderer: hake,
		sortable: true,
		width: 30
	};
	var toalett_kategori = {
		dataIndex: 'toalett_kategori',
		header: 'Toalett',
		renderer: toakatrenderer,
		sortable: true,
		width: 70
	};
	var leieberegning = {
		dataIndex: 'leieberegning',
		header: 'Leieberegning',
		sortable: true,
		width: 60
	};
	var merknader = {
		dataIndex: 'merknader',
		header: 'Merknader',
		sortable: true,
		width: 120
	};
	
	// oppretter rutenettet med de forskjellige kolonnene og fyller dette med datasettet
    var rutenett = new Ext.grid.GridPanel({
		// autoExpandColumn: 'personid',
		columns: [leieobjektnr, gateadresse, navn, beskrivelse, etg, areal, bad, toalett_kategori],
		enableColumnMove: true,
		height:500,
		store: datasett,
		stripeRows: true,
		title:'Oversikt over leieobjekter',
		width: 650
    });

	// Rutenettet rendres in i HTML-merket '<div id="leieobjektliste">':
    rutenett.render('leieobjektliste');

	// Oppretter detaljpanelet
	var ct = new Ext.Panel({
		renderTo: 'detaljpanel',
		frame: true,
		title: 'Detaljer',
		width: 250,
		height: 500,
		layout: 'border',
		items: [
			{
				bodyStyle: {
					background: '#ffffff',
					padding: '7px'
				},
				html: 'Velg en oppføring i listen til venstre for å se detaljene.',
				id: 'detailPanel',
				region: 'center'
			}
		]
	})

//	Hva skjer når du klikker på ei linje i rutenettet:
//	var button = Ext.get('show-btn');
//	rutenett.on('rowdblclick', function(sm, rowIdx, r){

//	Hva skjer når du velger ei linje i rutenettet?:
	rutenett.getSelectionModel().on('rowselect', function(sm, rowIdx, r) {
		var detailPanel = Ext.getCmp('detailPanel');

		// Henter variabelen etg og rendrer denne til ønsket datoformat
		var rec = datasett.getAt(rowIdx); // evt rutenett.getStore().getAt(rowIdx)

		var etg = rec.get('etg');
		var toalett_kategori = rec.get('toalett_kategori');
		var bad = rec.get('bad');

		// Format for detaljvisningen
		var detaljer = new Ext.Template([
			'Leieobjekt nr. {leieobjektnr}<br />Navn på leieobjektet:<br /><b>{navn}</b><br /><br />',
			'Gateadresse: <b>{gateadresse}</b><br/><br/>',
			'Etasje: <b>' + etasjerenderer(etg) + '</b><br/><br/>',
			'Annen angivelse: <b>{beskrivelse}</b><br /><br/>',
			'Areal: <b>{areal} m&#178;</b><br /><br/>',
			'Leieobjektet har tilgang på bad: <b>' + hake(bad) + '</b><br /><br/>',
			'Toalettkategori: <b>' + toakatrenderer(toalett_kategori) + '</b><br /><br/>',
			'Leieberegningstype: <b>{leieberegning}</b><br /><br/>',
			'{merknader}<br/><br /><br />',
			'<a href="index.php?oppslag=leieobjekt_kort&id={leieobjektnr}">Flere detaljer</a><br /><br />'
		]);
		detaljer.overwrite(detailPanel.body, r.data);
	});

});
<?
}

function design() {
?>
<table style="text-align: left;" border="0" cellpadding="2" cellspacing="0">
<tbody>
<tr>
<td style="vertical-align: top;">
<div id="leieobjektliste"></div></td>
<td style="vertical-align: top;">
<div id="detaljpanel"></div>
</td>
</tr>
</tbody>
</table>
<?
}

function hentData($data = "") {
	switch ($data) {
		default:
			return json_encode($this->arrayData($this->hoveddata));
	}
}

}
?>