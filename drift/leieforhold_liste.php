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
	$this->tittel = "Leieavtaler";
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
		url:'index.php?oppdrag=hentdata&oppslag=leieforhold_liste',
		fields: [
			{name: 'leieforhold', type: 'float'},
			{name: 'fastkid', type: 'string'},
			{name: 'kontrakt', type: 'float'},
			{name: 'leiebeløp', type: 'float'},
			{name: 'oppsagt', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'tildato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'leieobjekt', type: 'float'},
			{name: 'frosset', type: 'bool'},
			{name: 'beskrivelse'},
			{name: 'leieobjektbeskrivelse'},
			{name: 'kontrakter'}
		],
		totalProperty: 'totalRows',
		remoteSort: true,
		sortInfo: {
			field: 'kontrakt',
			direction: 'DESC' // or 'ASC' (case sensitive for local sorting)
		},
		root: 'data'
    });

	var lastData = function(){
			datasett.baseParams = {søkefelt: ""};
			datasett.load({params: {start: 0, limit: 300}});
	}

	lastData();
	

    var expander = new Ext.ux.grid.RowExpander({        tpl : new Ext.Template(
            '{kontrakter}'
        )
    });

	var leieforhold = {
		dataIndex: 'leieforhold',
		header: 'Leieforhold',
		align: 'right',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return '<a title="Klikk for å se siste leieavtale i dette leieforholdet." href="index.php?oppslag=leieforholdkort&id=' + record.data.leieforhold + '">' + value + '</a>';
		},
		sortable: true,
		width: 40
	};

	var fastkid = {
		dataIndex: 'fastkid',
		header: 'FastKID',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return '<div title="Fast KIDnummer for innbetalinger til dette leieforholdet.">' + value + '</div>';
		},
		sortable: false,
		width: 90
	};

	var leiebeløp = {
		align: 'right',
		dataIndex: 'leiebeløp',
		header: 'Leiebeløp',
		renderer: Ext.util.Format.noMoney,
		sortable: true,
		width: 60
	};

	var oppsagt = {
		dataIndex: 'oppsagt',
		header: 'Avsluttet',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			if(value){
				return '<div title="Siste bo-dag.">' + Ext.util.Format.date(value, 'd.m.Y') + '</div>';
			}
		},
		sortable: true,
		width: 70
	};

	var frosset = {
		dataIndex: 'frosset',
		header: 'Fryst',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			if(value){
				return "<div title=\"Leieavtalen er fryst, og det vil dermed ikke bli sendt automatiske purringer m.m.\">" + Ext.util.Format.hake(value) + "</div> ";
			}
		},
		sortable: true,
		width: 30
	};

	var tildato = {
		dataIndex: 'tildato',
		header: 'Utløper',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return '<span ' + (record.data.oppsagt ? 'style="text-decoration: line-through;" title="Utløpsdatoen er overstyrt av at leieforholdet er avsluttet"' : 'title="Datoen da leieforholdet utløper og må fornyes eller avsluttes"') + '>' + Ext.util.Format.date(value, 'd.m.Y') + '</span>';
		},
		sortable: true,
		width: 70
	};

	var leieobjekt = {
		dataIndex: 'leieobjekt',
		header: 'Leil',
		align: 'right',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return '<a title="Klikk for å gå til leieobjektkortet." href="index.php?oppslag=leieobjekt_kort&id=' + value + '">' + value + '</a>';
		},
		sortable: true,
		width: 40
	};

	var beskrivelse = {
		dataIndex: 'beskrivelse',
		header: 'Beskrivelse',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return '<a ' + (record.data.oppsagt ? 'style="text-decoration: line-through;" ' : '') + 'title="Klikk for å se siste leieavtale i dette leieforholdet." href="index.php?oppslag=leieforholdkort&id=' + record.data.leieforhold + '">' + value + '</a>';
		},
		sortable: false,
		width: 50
	};


	var søkefelt = new Ext.form.TextField({
		fieldLabel: 'Søk',
		name: 'søkefelt',
		width: 200,
		listeners: {'valid': function(){
			datasett.baseParams = {søkefelt: søkefelt.getValue()};
			datasett.load({params: {start: 0, limit: 300}});
		}}
	});

	var bunnlinje = new Ext.PagingToolbar({
		pageSize: 300,
		items: [
			søkefelt
		],
		store: datasett,
		displayInfo: true,
		displayMsg: '<- Søk på navn, adresse, avtalenummer, fastKID e.l.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Viser linje {0} - {1} av {2}',
		emptyMsg: "Venter på resultat"
	});


	var rutenett = new Ext.grid.GridPanel({
		title: '',
		store: datasett,
		columns: [
			expander,
			leieforhold,
			beskrivelse,
			leieobjekt,
			leiebeløp,
			fastkid,
			tildato,
			oppsagt,
			frosset
		],
		viewConfig: {
			forceFit: false
		},        
		autoExpandColumn: 2,
        plugins: expander,
		stripeRows: true,
		height: 500,
		width: 900,
		bbar: bunnlinje
	});

	// Rutenettet rendres in i HTML-merket '<div id="panel">':
	rutenett.render('panel');
	søkefelt.focus();

});
<?
}

function design() {
?>
<div id="panel"></div>
<?
}

function taimotSkjema() {
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		default:
			$resultat = $this->mysqli->arrayData(array(
				'source'	=> "\t(SELECT kontrakter.leieforhold, MAX(kontrakter.kontraktnr) AS kontrakt, MAX(kontrakter.tildato) AS tildato, MAX(kontrakter.leieobjekt) AS leieobjekt\n"
					.	"\tFROM kontrakter\n"
					.	"\tLEFT JOIN kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt\n"
					.	"\tLEFT JOIN leieobjekter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr\n"
					.	"\tLEFT JOIN personer ON kontraktpersoner.person = personer.personid\n"
					.	($this->POST['søkefelt'] ? "\tWHERE kontrakter.leieforhold LIKE '%" . (int)(($this->POST['søkefelt']-1000000)/10) . "%' OR kontrakter.leieforhold LIKE '%{$this->POST['søkefelt']}%' OR kontrakter.kontraktnr LIKE '%{$this->POST['søkefelt']}%' OR kontrakter.leiebeløp LIKE '%{$this->POST['søkefelt']}%' OR kontrakter.fradato LIKE '%{$this->POST['søkefelt']}%' OR kontrakter.tildato LIKE '%{$this->POST['søkefelt']}%' OR kontrakter.leieobjekt LIKE '%{$this->POST['søkefelt']}%' OR leieobjekter.navn LIKE '%{$this->POST['søkefelt']}%' OR leieobjekter.gateadresse LIKE '%{$this->POST['søkefelt']}%' OR kontraktpersoner.leietaker LIKE '%{$this->POST['søkefelt']}%' OR CONCAT(personer.fornavn, ' ', personer.etternavn) LIKE '%{$this->POST['søkefelt']}%'" : "")
					.	"\tGROUP BY leieforhold\n)\n"
					.	"\tAS leieforhold\n"
					.	"LEFT JOIN oppsigelser ON leieforhold.leieforhold = oppsigelser.leieforhold\n"
					.	"LEFT JOIN kontrakter ON leieforhold.kontrakt = kontrakter.kontraktnr",
				'fields'	=> "leieforhold.*, DATE_SUB(oppsigelser.fristillelsesdato, INTERVAL 1 DAY) AS oppsagt, kontrakter.leiebeløp, kontrakter.frosset",
				'groupfields'	=> "leieforhold",
				'orderfields'	=> ($_POST['sort'] ? "{$this->POST['sort']} {$this->POST['dir']}" : "leieforhold.kontrakt DESC"),
				'limit'			=> ($_POST['start'] ? ((int)$_POST['start'] . ", " . (int)$_POST['limit']) : ""),
				'returnQuery'	=> true
			));

			foreach ($resultat->data as $linje=>$leieforhold) {
				$a = $this->arrayData("SELECT * FROM kontrakter WHERE leieforhold = {$leieforhold->leieforhold} ORDER BY kontraktnr");
				$a = $a['data'];
				$resultat->data[$linje]->fastkid = $this->genererKid($leieforhold->leieforhold);
				$resultat->data[$linje]->beskrivelse = $this->liste($this->kontraktpersoner($leieforhold->kontrakt)) .  " i " . $this->leieobjekt($leieforhold->leieobjekt, true);
				$kontraktbesk = array();
				foreach ($a as $kontrakt) {
					$kontraktbesk[] = "<span style=\"margin-left: 60px\">Avtale nr. <a title=\"Klikk her for å åpne leieavtale nr. {$kontrakt['kontraktnr']}\" href=\"index.php?oppslag=leieforholdkort&id={$this->leieforhold($kontrakt['kontraktnr'])}\">{$kontrakt['kontraktnr']}</a> med " . $this->liste($this->kontraktpersoner($kontrakt['kontraktnr'])) . ": " . date('d.m.Y', strtotime($kontrakt['fradato'])) . " - " . ($kontrakt['tildato'] ? date('d.m.Y', strtotime($kontrakt['tildato'])) : "") . "</span>";
				}
				$resultat->data[$linje]->kontrakter = implode("<br />", $kontraktbesk);
				$kontraktpersoner = $this->kontraktpersoner($leieforhold->kontrakt);
				foreach($kontraktpersoner as $personid => $navn) {
					if($personid){ // adressekortet er ikke slettet
						$resultat->data[$linje]->kontrakter .= "<div style=\"margin-left: 50px\">{$navn}s <a title=\"Klikk her for å åpne kontaktopplysningene for $navn\" href=\"index.php?oppslag=personadresser_kort&id=$personid\">adressekort</a></div>";
					}
				}
			}
			return json_encode($resultat);
	}
}

}
?>