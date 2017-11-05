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
	$leieforhold
		= isset($_POST['leieforhold'])
		? (int)$_POST['leieforhold']
		: false;
	$beløpsøkefelt
		= isset($_POST['beløpsøkefelt'])
		? (float)str_replace(",", ".", $_POST['beløpsøkefelt'])
		: false;
	$søkefelt
		= isset($_POST['søkefelt'])
		? $this->POST['søkefelt']
		: false;
	$dir
		= isset($_POST['dir'])
		? $this->POST['dir']
		: "DESC";
	$sort
		= isset($_POST['sort'])
		? "{$this->POST['sort']} {$dir}, dato DESC, OCRtransaksjon, ref, betaler\n"
		: "dato DESC, OCRtransaksjon, ref, betaler\n";
	
	$this->hoveddata = "SELECT DISTINCT oppsummert.*\n"
		.	"FROM (\n"
		.	"SELECT innbetaling AS id, dato, SUM(beløp) AS beløp, konto, betaler, OCRtransaksjon, MIN(registrert) AS registrert, ref FROM innbetalinger\n"
		.	"GROUP BY innbetaling, dato, konto, betaler, OCRtransaksjon, ref\n"
		.	") AS oppsummert INNER JOIN innbetalinger\n"
		.	"ON oppsummert.dato = innbetalinger.dato AND oppsummert.konto = innbetalinger.konto AND oppsummert.betaler = innbetalinger.betaler AND oppsummert.ref = innbetalinger.ref\n"
		.	"WHERE innbetalinger.konto != '0'\n"
		.	($leieforhold ? ("AND leieforhold = '{$leieforhold}'\n") : "")
		.	(
			$beløpsøkefelt
			? "AND (oppsummert.beløp = '{$beløpsøkefelt}' OR innbetalinger.beløp = '{$beløpsøkefelt}')\n"
			: ""
		)
		.	(
			$søkefelt
			? (
				"AND (\n"
					. (strtotime( $søkefelt )
					? "(
						oppsummert.dato > date_sub('" . date('Y-m-d', strtotime($søkefelt)) . "', INTERVAL 4 DAY)
						AND oppsummert.dato < date_add('" . date('Y-m-d', strtotime($søkefelt)) . "', INTERVAL 4 DAY)
					)"
					: "0"
					)
					. " OR oppsummert.betaler LIKE '%{$søkefelt}%'
						OR oppsummert.ref LIKE '%{$søkefelt}%'
						OR innbetalinger.merknad LIKE '%{$søkefelt}%'\n"
				.")"
			)
			: ""
		)
		.	"\n"
		.	"ORDER BY {$sort}";

	$this->tittel = "Innbetalinger";
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
		url:'index.php?oppslag=innbetaling_liste&oppdrag=hentdata',
		fields: [
			{name: 'id'},
			{name: 'dato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'beløp', type: 'float'},
			{name: 'konto'},
			{name: 'OCRtransaksjon'},
			{name: 'ref'},
			{name: 'merknad'},
			{name: 'betaler'},
			{name: 'registrert', type: 'date', dateFormat: 'Y-m-d H:i:s'},
			{name: 'tekst'}
		],
		totalProperty: 'totalRows',
		remoteSort: true,
		sortInfo: {
			field: 'dato',
			direction: 'DESC' // or 'ASC' (case sensitive for local sorting)
		},
		root: 'data'
	});


	function sendmelding(){
		Ext.Ajax.request({
			waitMsg: 'Prøver å sende meldinger per epost...',
			url: 'index.php?oppslag=innbetaling_liste&oppdrag=oppgave&oppgave=sendmelding',
			failure:function(response,options){
				Ext.MessageBox.alert('Mislykket...','Klarte ikke å sende meldinger om nye innbetalinger. Prøv igjen senere.');
			},
			success:function(response,options){
				var tilbakemelding = Ext.util.JSON.decode(response.responseText);
				if(tilbakemelding['success'] == true) {
					Ext.MessageBox.alert('Utført', tilbakemelding.msg);
				}
				else {
					Ext.MessageBox.alert('Hmm..',tilbakemelding['msg']);
				}
			}
		});
	}

	var leieforhold = new Ext.form.ComboBox({
		name: 'leieforhold',
		mode: 'remote',
		store: new Ext.data.JsonStore({
			fields: [{name: 'leieforhold'},{name: 'visningsfelt'}],
			root: 'data',
			url: 'index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=hentdata&data=leieforhold'
		}),
		emptyText: 'Søk på leieforhold',
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
			datasett.baseParams = {leieforhold: leieforhold.getValue(), søkefelt: søkefelt.getValue(), beløpsøkefelt: beløpsøkefelt.getValue()};
			datasett.load({params: {start: 0, limit: 300}});
		}},
		width: 140
	});


	var søkefelt = new Ext.form.TextField({
		fieldLabel: 'Frisøk',
		emptyText: 'Dato (dd.mm.åååå ± 3 dager), navn eller ref. Min 4 tegn',
		name: 'søkefelt',
		width: 300,
		listeners: {
			valid: function( felt ) {
				if( søkefelt.getValue().length > 3 ) {
					datasett.baseParams = {
						leieforhold: leieforhold.getValue(),
						søkefelt: søkefelt.getValue(),
						beløpsøkefelt: beløpsøkefelt.getValue()
					};
					datasett.load({
						params: {
							start: 0,
							limit: 350
						}
					});
				}
			}
		}
	});

	var beløpsøkefelt = new Ext.form.TextField({
		fieldLabel: 'Beløp',
		emptyText: 'Søk på beløp',
		name: 'beløpsøkefelt',
		width: 100,
		listeners: {
			'valid': function() {
				datasett.baseParams = {
					leieforhold: leieforhold.getValue(),
					søkefelt: søkefelt.getValue(),
					beløpsøkefelt: beløpsøkefelt.getValue()
				};
				datasett.load({params: {start: 0, limit: 300}});
			}
		}
	});


	var søkeområde = new Ext.Panel({
		autoWidth: false,
		border: false,
		layout: 'column',
		height: 20,
		width: 1000,
		items: [{
			columnWidth: 0.25,
			labelAlign: 'right',
			labelWidth: 60,
			border: false,
			layout: 'form',
			items: [leieforhold]
		},{
			columnWidth: 0.20,
			labelAlign: 'right',
			labelWidth: 50,
			border: false,
			layout: 'form',
			items: [beløpsøkefelt]
		},{
			columnWidth: 0.5,
			labelAlign: 'right',
			labelWidth: 60,
			border: false,
			layout: 'form',
			items: [søkefelt]
		}]
	});


	var lastData = function(){
		datasett.load({params: {start: 0, limit: 300}});
	}

	lastData();
	

    var expander = new Ext.ux.grid.RowExpander({        tpl : new Ext.Template(
            '{tekst}'
        )
    });

	var dato = {
		dataIndex: 'dato',
		header: 'Dato',
		sortable: true,
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		width: 70
	};

	var registrert = {
		dataIndex: 'registrert',
		header: 'Registrert',
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

	var konto = {
		dataIndex: 'konto',
		header: 'Konto',
		sortable: true,
		width: 90
	};

	var åpne = {
		header: '',
		sortable: false,
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return '<a title="Klikk for å se detaljene i denne innbetalingen." href="index.php?oppslag=innbetalingskort&id=' + record.data.id + '">Åpne</a>';
		},
		width: 70
	};

	var ref = {
		dataIndex: 'ref',
		header: 'Ref',
		sortable: true,
		width: 100
	};

	var betaler = {
		dataIndex: 'betaler',
		header: 'Betalt av',
		sortable: true,
		width: 70
	};



	var bunnlinje = new Ext.PagingToolbar({
		pageSize: 300,
		store: datasett,
		displayInfo: true,
		displayMsg: 'Viser linje {0} - {1} av {2}',
		emptyMsg: "Ingen innbetalinger å vise",
		items:[
			'-',
			{
				text: 'Send bekreftelser for nye innbetalinger',
				handler: sendmelding
			}
		]
	});


	var rutenett = new Ext.grid.GridPanel({
		autoExpandColumn: 4,
        plugins: expander,
		autoScroll: true,
		border: false,
		stripeRows: true,
		store: datasett,
		tbar: [søkeområde],
		bbar: bunnlinje,
		columns: [
			expander,
			dato,
			beløp,
			konto,
			betaler,
			åpne,
			ref,
			registrert
		],
		height: 500,
		width: 900,
		viewConfig: {
			enableRowBody: false,
			showPreview: true,
			getRowClass : function(record, rowIndex, p, ds){
				if(this.showPreview){
					p.body = '' + record.data.tekst + '';
					return 'x-grid3-row-expanded';
				}
			return 'x-grid3-row-collapsed';
			}
		},
		title: 'Søk i innbetalinger'
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
		$filter
			= isset($_POST['query'])
			? "WHERE CONCAT(fornavn, ' ', etternavn) LIKE '%{$this->POST['query']}%'\n"
			.	"OR kontrakter.kontraktnr LIKE '%{$this->POST['query']}%'\n"
			: "";
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

		$resultat = $this->mysqli->arrayData(array(
			'sql'			=> $this->hoveddata,
			'limit'			=> "{$_POST['start']}, {$_POST['limit']}",
			'returnQuery'	=> true
		));

		foreach( $resultat->data as $innbetaling ) {
		
			$utlikninger = $this->mysqli->arrayData(array(
				'fields'	=> "innbetalinger.beløp, innbetalinger.merknad, innbetalinger.leieforhold, innbetalinger.krav, krav.tekst",
				'source'	=> "innbetalinger LEFT JOIN krav ON innbetalinger.krav = krav.id",
				'where'		=> "innbetalinger.dato = '{$innbetaling->dato}'
								AND konto = '{$innbetaling->konto}'
								AND OCRtransaksjon = '{$innbetaling->OCRtransaksjon}'
								AND betaler = '{$innbetaling->betaler}'
								AND ref = '{$innbetaling->ref}'"
			));
			
			$innbetaling->tekst = "";			
			
			foreach($utlikninger->data as $utlikning) {
				$innbetaling->tekst
					.= "kr. " . number_format($utlikning->beløp, 2, ",", " ") . ": "
					. (
						$utlikning->krav > 0
						? "<a href=\"index.php?oppslag=krav_kort&id={$utlikning->krav}\">{$utlikning->tekst}</a>"
						: ($utlikning->krav !== null ? "Tilbakebetaling" : "<i>ikke utliknet</i>")
					) . ", "
					. $this->liste($this->kontraktpersoner($this->sistekontrakt($utlikning->leieforhold)))
					. " i " . $this->leieobjekt($this->kontraktobjekt($utlikning->leieforhold))
					. (
						$utlikning->merknad
						? " ({$utlikning->merknad})"
						: ""
					) . "<br />";
			}
		}
		return json_encode($resultat);
	}
}

function oppgave($oppgave) {
	switch($oppgave){
		case 'sendmelding':
			if(!$resultat['success'] = $this->varsleNyeInnbetalinger()){
				$resultat['msg'] = "Klarte ikke å sende meldinger om nye innbetalinger. Prøv igjen senere.";
			}
			else{
				$resultat['msg'] = "Meldingene er sendt.";
			}
			break;
	}
	echo json_encode($resultat);
}

function taimotSkjema() {
	echo json_encode($resultat);
}

}
?>