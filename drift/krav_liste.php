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
	if($_GET['returi'] == "default") $this->returi->reset();
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
		url:'index.php?oppslag=krav_liste&oppdrag=hentdata',
		fields: [
			{name: 'kontraktnr'},
			{name: 'gironr', type: 'float'},
			{name: 'id', type: 'float'},
			{name: 'kravdato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'tekst'},
			{name: 'beløp', type: 'float'},
			{name: 'type'},
			{name: 'leieobjekt', type: 'float'},
			{name: 'andel'},
			{name: 'termin'},
			{name: 'fom', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'tom', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'anleggsnr'},
			{name: 'anlegg'},
			{name: 'opprettet', type: 'date', dateFormat: 'Y-m-d H:i:s'},
			{name: 'oppretter'},
			{name: 'utskriftsdato', type: 'date', dateFormat: 'Y-m-d H:i:s'},
			{name: 'forfall', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'utestående', type: 'float'},
			{name: 'kid'},
			{name: 'kontraktpersoner'},
			{name: 'leieobjektbesk'}
		],
		totalProperty: 'totalRows',
		remoteSort: true,
		sortInfo: {
			field: 'kravdato',
			direction: 'DESC' // or 'ASC' (case sensitive for local sorting)
		},
		root: 'data'
	});


	var inklBetalte = new Ext.form.Checkbox({
		boxLabel: 'Inkluder betalte krav',
		hideLabel: true,
		name: 'inklBetalte',
		inputValue: 1,
		checked: false,
		listeners: {'check': function(){
			datasett.baseParams = {inklBetalte: inklBetalte.getValue(), inklFramtidige: inklFramtidige.getValue(), leieforhold: leieforhold.getValue(), søkefelt: søkefelt.getValue()};
			datasett.load({params: {start: 0, limit: 300}});
		}}
	});


	var inklFramtidige = new Ext.form.Checkbox({
		boxLabel: 'Inkluder framtidige krav',
		hideLabel: true,
		name: 'inklFramtidige',
		inputValue: 1,
		checked: false,
		listeners: {'check': function(){
			datasett.baseParams = {inklBetalte: inklBetalte.getValue(), inklFramtidige: inklFramtidige.getValue(), leieforhold: leieforhold.getValue(), søkefelt: søkefelt.getValue()};
			datasett.load({params: {start: 0, limit: 300}});
		}}
	});


	var leieforhold = new Ext.form.ComboBox({
		name: 'leieforhold',
		mode: 'remote',
		store: new Ext.data.JsonStore({
			fields: [{name: 'leieforhold'},{name: 'visningsfelt'}],
			root: 'data',
			url: 'index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=hentdata&data=leieforhold'
		}),
		fieldLabel: 'Leieforhold',
		hideLabel: false,
		minChars: 0,
		queryDelay: 1000,
		allowBlank: true,
		displayField: 'visningsfelt',
		editable: true,
		forceSelection: false,
		selectOnFocus: true,
		listWidth: 500,
		maxHeight: 600,
		typeAhead: false,
		listeners: {'select': function(){
			datasett.baseParams = {inklBetalte: inklBetalte.getValue(), inklFramtidige: inklFramtidige.getValue(), leieforhold: leieforhold.getValue(), søkefelt: søkefelt.getValue()};
			datasett.load({params: {start: 0, limit: 300}});
		}},
		width: 150
	});


	var søkefelt = new Ext.form.TextField({
		fieldLabel: 'Frisøk',
		name: 'søkefelt',
		width: 200,
		listeners: {'valid': function(){
			datasett.baseParams = {inklBetalte: inklBetalte.getValue(), inklFramtidige: inklFramtidige.getValue(), leieforhold: leieforhold.getValue(), søkefelt: søkefelt.getValue()};
			datasett.load({params: {start: 0, limit: 300}});
		}}
	});


	var søkeområde = new Ext.Panel({
		autoWidth: false,
		border: false,
		layout: 'column',
		height: 20,
		width: 1000,
		items: [{
			columnWidth: 0.13,
			border: false,
			layout: 'form',
			items: [inklBetalte]
		},{
			columnWidth: 0.15,
			border: false,
			layout: 'form',
			items: [inklFramtidige]
		},{
			columnWidth: 0.22,
			labelAlign: 'right',
			labelWidth: 60,
			border: false,
			layout: 'form',
			items: [leieforhold]
		},{
			columnWidth: 0.3,
			labelAlign: 'right',
			labelWidth: 60,
			border: false,
			layout: 'form',
			items: [søkefelt]
		}]
	});


	var lastData = function(){
		datasett.baseParams = {inklBetalte: inklBetalte.getValue(), inklFramtidige: inklFramtidige.getValue()};
		datasett.load({params: {start: 0, limit: 300}});
	}

	lastData();
	

    var expander = new Ext.ux.grid.RowExpander({        tpl : new Ext.Template(
            '{tekst}'
        )
    });

	var kontraktnr = {
		align: 'right',
		dataIndex: 'kontraktnr',
		header: 'Leieavt',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return "<a title=\"" + record.data.kontraktpersoner + " i " + record.data.leieobjektbesk + "\" href=\"index.php?oppslag=leieforholdkort&id=" + value + "\">" + value + "</a>";
		},		
		sortable: true,
		width: 60
	};

	var gironr = {
		align: 'right',
		dataIndex: 'gironr',
		header: 'Giro',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			if(record.data.utskriftsdato) {
				return "<a title=\"Klikk her for å åpne giroen som PDF\" target=\"_blank\" href=\"index.php?oppslag=giro&oppdrag=lagpdf&pdf=" + value + "\">" + value + "</a>";
			}
			else if(value) {
				return value;
			}
		},		
		sortable: true,
		width: 50
	};

	var id = {
		align: 'right',
		dataIndex: 'id',
		header: 'Nr.',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return "<a title=\"Klikk her for å åpne og evt. endre kravet.\" href=\"index.php?oppslag=krav_kort&id=" + value + "\">" + value + "</a>";
		},
		sortable: true,
		width: 50
	};

	var kravdato = {
		dataIndex: 'kravdato',
		header: 'Dato',
		sortable: true,
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		width: 70
	};

	var beløp = {
		align: 'right',
		dataIndex: 'beløp',
		header: 'Beløp',
		renderer: Ext.util.Format.noMoney,
		sortable: true,
		width: 70
	};

	var type = {
		dataIndex: 'type',
		header: 'Type',
		sortable: true,
		width: 70
	};

	var leieobjekt = {
		align: 'right',
		dataIndex: 'leieobjekt',
		header: 'Leil',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			if(value)
				return "<a title=\"" + record.data.leieobjektbesk + "\" href=\"index.php?oppslag=leieobjekt_kort&id=" + value + "\">" + value + "</a>";
		},		
		sortable: true,
		width: 50
	};

	var andel = {
		align: 'right',
		dataIndex: 'andel',
		header: 'Andel',
		sortable: true,
		width: 50
	};

	var termin = {
		dataIndex: 'termin',
		header: 'Termin',
		sortable: true,
		width: 80
	};

	var fom = {
		dataIndex: 'fom',
		header: 'Fra',
		hidden: true,
		sortable: true,
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		width: 50
	};

	var tom = {
		dataIndex: 'tom',
		header: 'Til',
		hidden: true,
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		sortable: true,
		width: 50
	};

	var anleggsnr = {
		dataIndex: 'anleggsnr',
		header: 'Anleggsnr',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			if(value)
				return "<a title=\"" + record.data.anlegg + "\" href=\"index.php?oppslag=fs_anlegg\">" + value + "</a>";
		},		
		sortable: true,
		width: 90
	};

	var opprettet = {
		dataIndex: 'opprettet',
		header: 'Opprettet',
		hidden: true,
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		sortable: true,
		width: 50
	};

	var oppretter = {
		dataIndex: 'oppretter',
		header: 'Oppretter',
		hidden: true,
		sortable: true,
		width: 50
	};

	var utskriftsdato = {
		dataIndex: 'utskriftsdato',
		header: 'Utskriftsdato',
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		hidden: true,
		sortable: true,
		width: 50
	};

	var forfall = {
		dataIndex: 'forfall',
		header: 'Forfall',
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		sortable: true,
		width: 70
	};

	var utestående = {
		align: 'right',
		dataIndex: 'utestående',
		header: 'Utestående',
		renderer: Ext.util.Format.noMoney,
		sortable: true,
		width: 70
	};

	var kid = {
		align: 'right',
		dataIndex: 'kid',
		header: 'KID',
		sortable: true,
		width: 90
	};


	var bunnlinje = new Ext.PagingToolbar({
		pageSize: 300,
		store: datasett,
		displayInfo: true,
		displayMsg: 'Viser linje {0} - {1} av {2}',
		emptyMsg: "Ingen krav å vise",
		items:[
			'-', {
			pressed: false,
			enableToggle:true,
			text: 'Vis kravbeskrivelsene',
			cls: 'x-btn-text-icon details',
			toggleHandler: function(btn, pressed){
				var view = rutenett.getView();
				view.showPreview = pressed;
				view.refresh();
			}
		}]
	});


	var rutenett = new Ext.grid.GridPanel({
		autoExpandColumn: 11,
        plugins: expander,
		autoScroll: true,
		border: false,
		stripeRows: true,
		store: datasett,
		tbar: [søkeområde],
		bbar: bunnlinje,
		columns: [
			expander,
			kravdato,
			id,
			kontraktnr,
			gironr,
			beløp,
			type,
			leieobjekt,
			andel,
			termin,
			fom,
			tom,
			anleggsnr,
			opprettet,
			oppretter,
			utskriftsdato,
			forfall,
			kid,
			utestående
		],
		height: 500,
		width: 900,
		viewConfig: {
			enableRowBody: true,
			showPreview: true,
			getRowClass : function(record, rowIndex, p, ds){
				if(this.showPreview){
					p.body = '' + record.data.tekst + '';
					return 'x-grid3-row-expanded';
				}
			return 'x-grid3-row-collapsed';
			}
		},
		title: 'Leie og andre krav om betaling'
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
	
		case "leieforhold":
			if($_POST['query']){
				$filter =	"WHERE CONCAT(fornavn, ' ', etternavn) LIKE '%{$this->POST['query']}%'\n"
					.	"OR kontrakter.kontraktnr LIKE '%{$this->POST['query']}%'\n";
			}
			$sql =	"SELECT\n"
				.	"kontrakter.leieforhold, max(kontrakter.kontraktnr) as kontraktnr, leieobjekt , gateadresse, andel, min(fradato) AS startdato ,max(tildato) AS tildato\n"
				.	"FROM\n"
				.	"((kontrakter INNER JOIN leieobjekter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr)\n"
				.	"INNER JOIN kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt)\n"
				.	"INNER JOIN personer ON kontraktpersoner.person = personer.personid\n"
				.	$filter
				.	"GROUP BY kontrakter.leieforhold, leieobjekt, gateadresse, andel\n"
				.	"ORDER BY startdato DESC, tildato DESC, etternavn, fornavn\n";
			$liste = $this->arrayData($sql);
			foreach($liste['data'] as $linje => $d) {
				$liste['data'][$linje]['visningsfelt'] = $d['leieforhold'] . ' | ' . ($this->liste($this->kontraktpersoner($d['kontraktnr']))) . ' for #' . $d['leieobjekt'] . ', ' . $d['gateadresse'] . ' | ' . $d['startdato'] . ' - ' . $d['tildato'];
			}
			return json_encode($liste);
			break;

		default:
			$this->oppdaterUbetalt();
			$resultat = $this->mysqli->arrayData(array(
				'source' => "krav LEFT JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr\n"
					.	"LEFT JOIN giroer ON krav.gironr = giroer.gironr\n"
					.	"LEFT JOIN fs_fellesstrømanlegg ON krav.anleggsnr = fs_fellesstrømanlegg.anleggsnummer",
				'where' => "1"
					.	($_POST['inklBetalte'] == 'true' ? "" : " AND utestående")
					.	($_POST['inklFramtidige'] == 'true' ? "" : " AND kravdato < NOW()")
					.	((isset( $_POST['leieforhold'] ) && $_POST['leieforhold'] ) ? (" AND kontrakter.leieforhold = '" . (int)$_POST['leieforhold'] . "'") : "")
					.	(
						@$_POST['søkefelt']
						? (" AND (krav.gironr LIKE '%{$this->POST['søkefelt']}%' OR id LIKE '%{$this->POST['søkefelt']}%' OR giroer.kid LIKE '%{$this->POST['søkefelt']}%' OR kravdato LIKE '%" . date('Y-m-d', strtotime($_POST['søkefelt'])) . "%' OR forfall LIKE '%" . date('Y-m-d', strtotime($_POST['søkefelt'])) . "%' OR krav.tekst LIKE '%{$this->POST['søkefelt']}%' OR beløp LIKE '%" . str_replace(array(' ', ','), array('', '.'), ($this->POST['søkefelt'])) . "%' OR type LIKE '%{$this->POST['søkefelt']}%' OR krav.leieobjekt LIKE '%{$this->POST['søkefelt']}%' OR krav.andel LIKE '%{$this->POST['søkefelt']}%' OR termin LIKE '%{$this->POST['søkefelt']}%' OR anleggsnr LIKE '%{$this->POST['søkefelt']}%' OR utestående LIKE '%" . str_replace(array(' ', ','), array('', '.'), ($this->POST['søkefelt'])) . "%')")
						: ""
					),
				'fields'	=> "krav.*, kid, fs_fellesstrømanlegg.formål AS anlegg",
				'orderfields'	=> "{$this->POST['sort']} {$this->POST['dir']}, krav.kravdato DESC, krav.kontraktnr ASC, krav.gironr ASC, krav.id ASC",
				'limit'	=> ((int)$_POST['start'] . ', ' . (int)$_POST['limit']),
				'returnQuery'	=> true
			));
			foreach($resultat->data as $linje=>$krav){
				$resultat->data[$linje]->kontraktpersoner = $this->liste($this->kontraktpersoner($krav->kontraktnr));
				$resultat->data[$linje]->leieobjektbesk = $this->leieobjekt($this->kontraktobjekt($krav->kontraktnr), true);
			}
			return json_encode($resultat);
	}
}


function taimotSkjema() {
	echo json_encode($resultat);
}


}
?>