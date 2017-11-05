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
	$this->tittel = "Ferdigfordelte strømregninger";
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

	var datasett = new Ext.data.JsonStore({
		url:'index.php?oppdrag=hentdata&oppslag=fs_fakturaer_arkiv',
		fields: [
			{name: 'id'},
			{name: 'fakturanummer'},
			{name: 'fakturabeløp', type: 'float', useNull: true},
			{name: 'anleggsnr'},
			{name: 'bruk'},
			{name: 'fradato', type: 'date', dateFormat: 'Y-m-d', useNull: true},
			{name: 'tildato', type: 'date', dateFormat: 'Y-m-d', useNull: true},
			{name: 'kWh', type: 'float'},
			{name: 'termin'},
			{name: 'fordelt'},
			{name: 'lagt_inn_av'},
			{name: 'html'}
		],
		totalProperty: 'totalRows',
		remoteSort: true,
		sortInfo: {
			field: 'id', // Sett inn kolonnenavnet for standard sortering
			direction: 'DESC' // or 'ASC' (case sensitive for local sorting)
		},
		root: 'data'
    });

	var lastData = function(){
		datasett.baseParams = {};
		datasett.load({
			params: {
				start: 0,
				limit: 100
			}
		});
	}

	lastData();

	var id = {
		dataIndex: 'id',
		header: 'id',
		hidden: true,
		id: 'id',
		sortable: false,
		width: 50
	};

	var vis = {
		dataIndex: 'id',
		header: 'Vis',
		hidden: false,
		sortable: false,
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			if(!record.data.id) return "";
			return "<a title=\"Vis denne fakturaen\" href=\"index.php?oppslag=fs_faktura_kort&id=" + record.data.id + "\"><img src=\"../bilder/detaljer_lite.png\" /></a>";
		},
		width: 30
	};

	var fakturanummer = {
		align: 'right',
		dataIndex: 'fakturanummer',
		header: 'Faktura',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			if(value) return value;
		},
		sortable: true,
		width: 70
	};

	var fakturabeløp = {
		align: 'right',
		dataIndex: 'fakturabeløp',
		header: 'Beløp',
		renderer: Ext.util.Format.noMoney, 
		sortable: true,
		width: 90
	};

	var anleggsnr = {
		align: 'right',
		dataIndex: 'anleggsnr',
		header: 'Anleggsnr',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			if(value) {
				return "<a title=\"Klikk her for å gå til dette strømanlegget\" href=\"index.php?oppslag=fs_anlegg_kort&anleggsnummer=" + value + "\">" + value + "</a>";
			}
		},
		sortable: true,
		width: 110
	};

	var fradato = {
		dataIndex: 'fradato',
		header: 'Fra dato',
		renderer: Ext.util.Format.dateRenderer('d.m.Y'), 
		sortable: true,
		width: 80
	};

	var tildato = {
		dataIndex: 'tildato',
		header: 'Til dato',
		renderer: Ext.util.Format.dateRenderer('d.m.Y'), 
		sortable: true,
		width: 80
	};

	var termin = {
		dataIndex: 'termin',
		header: 'termin',
		id: 'termin',
		sortable: true,
		width: 40
	};


	var kWh = {
		align: 'right',
		dataIndex: 'kWh',
		header: 'kWh',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			if(value) return value;
		},
		sortable: true,
		width: 70
	};


	var bunnlinje = new Ext.PagingToolbar({
		pageSize: 100,
		store: datasett,
		displayInfo: true,
		displayMsg: 'Viser linje {0} - {1} av {2}',
		emptyMsg: "Venter på resultat"
	});


	var rutenett = new Ext.grid.GridPanel({
		autoExpandColumn: 'termin',
		bbar: bunnlinje,
		buttons: [{
			text: 'Legg inn nye fakturaer',
			handler: function(btn, pressed){
				window.location = "index.php?oppslag=fs_fakturaer";
			}
		}],
		columns: [
			id,
			vis,
			fakturanummer,
			fakturabeløp,
			anleggsnr,
			fradato,
			tildato,
			termin,
			kWh
		],
		height: 500,
		store: datasett,
		stripeRows: true,
		title: '<?=$this->tittel?>',
		width: 650
    });

	rutenett.render('panel');

	// Oppretter detaljpanelet
	var ct = new Ext.Panel({
		frame: true,
		height: 500,
		items: [
			{
				autoScroll: true,
				id: 'detaljfelt',
				region: 'center',
				bodyStyle: {
					background: '#ffffff',
					padding: '7px'
				},
				html: 'Velg en faktura i listen til venstre for å se fordelingen.'
			}
		],
		layout: 'border',
		renderTo: 'detaljpanel',
		title: 'Fordeling',
		width: 250
	})


	// Hva skjer når du klikker på ei linje i rutenettet?:
	rutenett.getSelectionModel().on('rowselect', function(sm, rowIdx) {
		var detaljfelt = Ext.getCmp('detaljfelt');
		
		// Format for detaljvisningen
		var detaljer = new Ext.Template([
			'Anlegg nr <a title="Klikk her for å gå til dette anlegget" href="index.php?oppslag=fs_anlegg_kort&anleggsnummer={anleggsnr}">{anleggsnr}</a>: <br />{bruk}<br /><br />{html}'
		]);
		detaljer.overwrite(detaljfelt.body, datasett.getAt(rowIdx).data);
	});

});
<?
}

function design() {
?>
<table style="text-align: left; width: 900px;" border="0" cellpadding="2" cellspacing="0">
<tbody>
<tr>
<td style="vertical-align: top;">
<div id="panel"></div>
<td style="vertical-align: top;">
<div id="detaljpanel"></div>
</td>
</tr>
</tbody>
</table>
<?
}

function hentData($data = "") {
	$tp = $this->mysqli->table_prefix;
	$sort		= @$_GET['sort'];
	$synkende	= @$_GET['dir'] == "DESC" ? true : false;
	$start		= (int)@$_GET['start'];
	$limit		= @$_GET['limit'];

	switch ($data) {

	default: {
		$resultat = $this->mysqli->arrayData(array(
			'source'	=> "fs_originalfakturaer LEFT JOIN fs_fellesstrømanlegg ON fs_originalfakturaer.anleggsnr = fs_fellesstrømanlegg.anleggsnummer",
			'where'		=> "fordelt",
			'fields'	=> "fs_originalfakturaer.*, fs_fellesstrømanlegg.formål AS bruk",
			'orderfields'	=> ($_POST['sort'] ? "CAST({$this->POST['sort']} AS SIGNED) {$this->POST['dir']}, {$this->POST['sort']} {$this->POST['dir']}\n" : "fradato DESC, tildato DESC, fakturanummer DESC"),
			'limit'		=> ($_POST['start'] ? ($_POST['limit'] ? ((int)$_POST['start'] . ", " . (int)$_POST['limit']) : (int)$_POST['start']) : "")
		));
		
		if($resultat->success) {
			$resultat->totalt = $resultat->totalRows;
		}			
		

		foreach($resultat->data as $index=>$linje){
			$html = "";
			$sql = "SELECT kontraktnr, SUM(beløp) as beløp FROM fs_andeler WHERE faktura = '{$linje->fakturanummer}' GROUP BY kontraktnr ORDER BY kontraktnr";
			$fordeling = $this->arrayData($sql);
			$sum = 0;
			$fordelingstekst = "<b>Fordeling</b> (av kr. " . number_format($linje->fakturabeløp, 2, ",", " ") . ")\n<table><tbody style=\"width: 100%;\">";
			foreach($fordeling['data'] as $del){
				$fordelingstekst .= "<tr><td>" . $this->liste($this->kontraktpersoner($del['kontraktnr'])) . "</td><td style=\"text-align: right; width: 100px;\">" . ($linje->fordelt ? "": "<a>") . "kr. " . number_format($del['beløp'], 2, ",", " ") . ($linje->fordelt ? "": "</a>") . "</td></tr>\n";
				$sum += $del['beløp'];
			}
			$fordelingstekst .= "</tbody>\n<tfooter><tr><td>Sum</td><td style=\"width: 100px; font-weight: bold; text-align: right;\">kr. " . number_format($sum, 2, ",", " ") . "</td></tr></tfooter></table>";
			$html .= "Fakturaen er fordelt og kreves inn fra beboerne.<br />\nFordelingen kan ikke endres.<br /><br />\n$fordelingstekst<br /><a style=\"cursor: pointer; text-decoration: underline;\" onClick=\"window.open('index.php?oppslag=fs_fakturaer_arkiv&oppdrag=manipuler&data=skrivfordeling&faktura={$linje->fakturanummer}');\">Vis fordelingen som PDF</a><br />\n";
			$resultat->data[$index]->html = $html;
		}
		$resultat->data =  $this->sorterObjekter($resultat->data, $sort, $synkende);
		return json_encode($resultat);
	}
	}
}

function manipuler($data){
	switch ($data){
		case "skrivfordeling":
			$sett = array();
			if($_GET['faktura']){
				$sett[] = $this->mysqli->real_escape_string($_GET['faktura']);
			}
			else{
				$sql =	"SELECT fakturanummer\n"
					.	"FROM fs_originalfakturaer INNER JOIN fs_andeler ON fs_originalfakturaer.fakturanummer = fs_andeler.faktura\n"
					.	"WHERE !fordelt\n"
					.	"GROUP BY fakturanummer";
				$sett1 = $this->arrayData($sql);
				foreach($sett1['data'] as $faktura){
					$sett[] = $faktura['fakturanummer'];
				}
			}
			$this->fs_skrivFordelingsforslag($sett);
			break;
	}
}


}
?>