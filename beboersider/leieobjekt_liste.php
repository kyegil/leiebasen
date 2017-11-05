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
	$this->hoveddata = "
	SELECT leieobjekter.*, bygninger.navn AS bygning, utdelingsorden.plassering
	FROM leieobjekter LEFT JOIN bygninger ON leieobjekter.bygning = bygninger.id LEFT JOIN utdelingsorden ON leieobjekter.leieobjektnr = utdelingsorden.leieobjekt AND utdelingsorden.rute = '{$this->valg['utdelingsrute']}'
	WHERE !leieobjekter.ikke_for_utleie
	ORDER BY utdelingsorden.plassering, gateadresse, etg, beskrivelse
	";
}

function skript() {
	if($_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
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
			return '<img src="<?$this->http_host;?>/bilder/hake9.png" alt="✔︎"/>';
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
           {name: 'boenhet'},
           {name: 'navn'},
           {name: 'bygning'},
           {name: 'gateadresse'},
           {name: 'etg'},
           {name: 'beskrivelse'},
           {name: 'areal', type: 'float'},
           {name: 'bad', type: 'bool'},
           {name: 'toalett'},
           {name: 'toalett_kategori'},
           {name: 'leieberegning'},
           {name: 'merknader'},
           {name: 'leietakere'},
           {name: 'ikke_for_utleie', type: 'bool'}
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
		renderer: function(value, metaData, record, rowIndex, colIndex, store) {
			if(record.data.ikke_for_utleie){
				return '<del>' + value + '</del>';
			}
			else {
				return value;
			}
		},
		sortable: true,
		width: 40
	};
	var boenhet = {
		dataIndex: 'boenhet',
		header: 'Bolig',
		renderer: hake,
		sortable: true,
		width: 40
	};
	var navn = {
		dataIndex: 'navn',
		header: 'Navn',
		renderer: function(value, metaData, record, rowIndex, colIndex, store) {
			if(record.data.ikke_for_utleie){
				return '<del>' + value + '</del>';
			}
			else {
				return value;
			}
		},
		sortable: true,
		width: 100
	};
	var gateadresse = {
		dataIndex: 'gateadresse',
		header: 'Gateadresse',
		renderer: function(value, metaData, record, rowIndex, colIndex, store) {
			if(record.data.ikke_for_utleie){
				return '<del>' + value + '</del>';
			}
			else {
				return value;
			}
		},
		sortable: true,
		width: 140
	};
	var bygning = {
		dataIndex: 'bygning',
		header: 'Bygning',
		renderer: function(value, metaData, record, rowIndex, colIndex, store) {
			if(record.data.ikke_for_utleie){
				return '<del>' + value + '</del>';
			}
			else {
				return value;
			}
		},
		sortable: true,
		width: 140
	};
	var etg = {
		align: 'right',
		dataIndex: 'etg',
		header: 'Etg.',
		renderer: etasjerenderer,
		sortable: true,
		width: 50
	};
	var beskrivelse = {
		dataIndex: 'beskrivelse',
		header: 'Beskrivelse',
		renderer: function(value, metaData, record, rowIndex, colIndex, store) {
			if(record.data.ikke_for_utleie){
				return '<del>' + value + '</del>';
			}
			else {
				return value;
			}
		},
		sortable: true,
		width: 100
	};
	var areal = {
		align: 'right',
		dataIndex: 'areal',
		header: 'Areal',
		sortable: true,
		width: 35
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
		renderer: function(value, metaData, record, rowIndex, colIndex, store) {
			switch(value){
				case '2': return '<span title="' + record.data.toalett + '" style="color: green;">' + record.data.toalett + '</span>';
				case '1': return '<span title="' + record.data.toalett + '" style="color: orange;">' + record.data.toalett + '</span>';
				case '0': return '<span title="' + record.data.toalett + '" style="color: red;">' + record.data.toalett + '</span>';
			}
		},
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
		sortable: false,
		width: 120
	};
	var leietakere = {
		dataIndex: 'leietakere',
		header: 'Leietakere',
		renderer: function(value, metaData, record, rowIndex, colIndex, store) {
			return '<span title="' + value + '">' + value + '</span>';
		},
		sortable: false,
		width: 100
	};
	
	var gå = {
		dataIndex: 'leieobjektnr',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			if(!value) value = '*';
			return "<a title=\"Vis leieobjektet\" href=\"index.php?oppslag=leieobjekt_kort&id=" + value + "\"><img src=\"../bilder/detaljer_lite.png\" /></a>";
		},
		sortable: false,
		width: 30
	};

	// oppretter rutenettet med de forskjellige kolonnene og fyller dette med datasettet
    var rutenett = new Ext.grid.GridPanel({
		// autoExpandColumn: 'personid',
		columns: [leieobjektnr, boenhet, navn, gateadresse, beskrivelse, etg, areal, bad, toalett_kategori, leietakere, gå],
		enableColumnMove: true,
		height:500,
		store: datasett,
		stripeRows: true,
		title:'Oversikt over leieobjekter sortert etter utdelingsrute <?=$this->valg['utdelingsrute']?>',
		width: 900
    });

    rutenett.render('panel');

	rutenett.on({
		rowdblclick: function(grid, rowIndex, e){
				window.location = "index.php?oppslag=leieobjekt_kort&id=" + datasett.getAt(rowIndex).get('leieobjektnr');			
		}
	});
	rutenett.on({
		sortchange: function(grid, sortInfo) {
			rutenett.setTitle('Oversikt over leieobjekter');
		}
	});
});
<?
}

function design() {
?>
<div id="panel"></div></td>
<?
}

function hentData($data = "") {
	switch ($data) {
		default:
			$resultat = $this->arrayData($this->hoveddata);
			foreach($resultat['data'] as $indeks => $leieobjekt) {
				$beboere = array();
				$kontrakter = $this->dagensBeboere($leieobjekt['leieobjektnr']);
				foreach($kontrakter as $kontraktnr) {
					$beboere[] = $this->liste($this->kontraktpersoner($kontraktnr));
				}
				$resultat['data'][$indeks]['leietakere'] = $this->liste($beboere);
				unset($kontrakter, $beboere);
			}
			return json_encode($resultat);
	}
}

}
?>