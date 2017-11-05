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
	if(!$id = $this->GET['anleggsnummer']) header("Location: index.php?oppslag=fs_anlegg");
	$this->hoveddata =	"SELECT fs_originalfakturaer.*, fs_fellesstrømanlegg.formål AS bruk, fs_fellesstrømanlegg.målernummer, fs_fellesstrømanlegg.plassering\n"
		.	"FROM fs_fellesstrømanlegg LEFT JOIN fs_originalfakturaer ON fs_fellesstrømanlegg.anleggsnummer = fs_originalfakturaer.anleggsnr\n"
		.	"WHERE fs_fellesstrømanlegg.anleggsnummer = '{$id}'\n"
		.	(@$_POST['sort'] ? "ORDER BY CAST({$this->POST['sort']} AS SIGNED) {$this->POST['dir']}, {$this->POST['sort']} {$this->POST['dir']}\n" : "ORDER BY fradato DESC, tildato DESC, fakturanummer DESC");
	$this->tittel = "Fellesstrøm anlegg {$id}";
}

function skript() {
	if(@$_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';


	function oppdaterDetaljer(record){
		var detaljfelt = Ext.getCmp('detaljfelt');
		var detaljer = new Ext.Template([
			'{html}'
		]);
		if(record) {
			detaljer.overwrite(detaljfelt.body, record.data);
		}
		else {
			detaljer.overwrite(detaljfelt.body, {
				html: ""
			});
		}
	}


	var datasett = new Ext.data.JsonStore({
		url: 'index.php?oppdrag=hentdata&oppslag=<?=$_GET['oppslag']?>&anleggsnummer=<?=$this->GET['anleggsnummer']?>&data=fakturaliste',
		fields: [
			{name: 'id'},
			{name: 'fakturanummer'},
			{name: 'fakturabeløp', type: 'float', useNull: true},
			{name: 'fradato', type: 'date', dateFormat: 'Y-m-d', useNull: true},
			{name: 'tildato', type: 'date', dateFormat: 'Y-m-d', useNull: true},
			{name: 'kWh', type: 'float'},
			{name: 'termin'},
			{name: 'fordelt'},
			{name: 'lagt_inn_av'},
			{name: 'html'}
		],
		totalProperty: 'totalt',
		remoteSort: true,
		sortInfo: {
			field: 'tildato', // Sett inn kolonnenavnet for standard sortering
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

	var fordelingsnøkkel = new Ext.Window({
		title: 'Fordelingsnøkkel',
		closeAction: 'hide',
		width: 600,
		height: 400,
		autoScroll: true,
		autoLoad: 'index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=hentdata&anleggsnummer=<?=$_GET['anleggsnummer']?>&data=fordelingsnokkel',
	});

	var fordelingsnøkkelknapp = new Ext.Button({
		text: 'Vis fordelingsnøkkel',
		handler: function() {
			fordelingsnøkkel.show();
		}
	});


    var expander = new Ext.ux.grid.RowExpander({        tpl : new Ext.Template(
            '{html}'
        )
    });

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
		hidden: false,
		sortable: false,
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return "<a title=\"Klikk her for å vise denne fakturaen\" href=\"index.php?oppslag=fs_faktura_kort&id=" + record.data.id + "\"><img src=\"../bilder/detaljer_lite.png\" /></a>";
		},
		width: 30
	};

	var fordelt = {
		dataIndex: 'fordelt',
		header: 'Låst',
		id: 'fordelt',
		renderer: Ext.util.Format.hake, 
		sortable: true,
		width: 40
	};

	var fakturanummer = {
		align: 'right',
		dataIndex: 'fakturanummer',
		header: 'Faktura',
		sortable: true,
		width: 70
	};

	var fakturabeløp = {
		align: 'right',
		dataIndex: 'fakturabeløp',
		header: 'Beløp',
		renderer: function(v) {
			if(v != null) {
				return Ext.util.Format.noMoney(v);
			}
			else {
				return null;
			}
		}, 
		sortable: true,
		width: 90
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
		sortable: true,
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			if(value) return value;
		},
		width: 50
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
        plugins: expander,
		columns: [
			expander,
			vis,
			id,
			fakturanummer,
			fradato,
			tildato,
			kWh,
			termin,
			fakturabeløp,
			fordelt
		],
		height: 350,
		store: datasett,
		stripeRows: true,
		title: 'Strømregninger på dette anlegget',
		width: 870
    });

	var venstrekolonne = new Ext.Panel({
        autoScroll: true,
		columnWidth: 0.5,
		autoLoad: 'index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=hentdata&anleggsnummer=<?=$_GET['anleggsnummer']?>',
        bodyStyle: 'padding:5px',
		frame: false,
		height: 90,
		plain: true
	});


	var høyrekolonne = new Ext.Panel({
        autoScroll: true,
        items: [fordelingsnøkkelknapp],
		columnWidth: 0.5,
        bodyStyle: 'padding:5px',
		frame: false,
		height: 90,
		plain: true
	});


	var indrepanel = new Ext.Container({
		layout: 'column',
		items: [venstrekolonne, høyrekolonne]
	});


	var panel = new Ext.Panel({
		items: [indrepanel, rutenett],
        autoScroll: true,
        bodyStyle: 'padding:5px',
		title: '',
		frame: true,
		height: 500,
		plain: false,
		width: 900,
		buttons: [{
			text: 'Skriv ut',
			menu: {
				items: [
					{
						text: 'Liste over strømregningene',
						handler: function(){
							window.open('index.php?oppslag=fs_anlegg_utskrift&anleggsnummer=<?=$_GET['anleggsnummer']?>');
						}
					},
					{
						text: 'Liste over strømregningene med fordeling',
						handler: function(){
							window.open('index.php?oppslag=fs_anlegg_utskrift&anleggsnummer=<?=$_GET['anleggsnummer']?>&fordeling=1');
						}
					}
				]
			}
		}, {
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
			},
			text: 'Tilbake'
		}]
	});

	panel.render('panel');

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
		case "fakturaliste":
			$query = $this->hoveddata;
			
			if(isset($_POST['start'])) $query .= "LIMIT " . (int)$_POST['start'];
			if(isset($_POST['start']) and $_POST['limit']) $query .= ", " . (int)$_POST['limit'];
			$resultat = $this->arrayData($query);

			foreach($resultat['data'] as $index=>$linje){
				$html = "";
				$sql = "SELECT kontraktnr, SUM(beløp) as beløp, COUNT(epostvarsel) AS epostvarsel FROM fs_andeler WHERE faktura = '{$linje['fakturanummer']}' GROUP BY kontraktnr ORDER BY kontraktnr";
				$fordeling = $this->arrayData($sql);
				$sum = 0;
				$fordelingstekst = "<table><tbody style=\"width: 100%;\">";
				foreach($fordeling['data'] as $del){
					$fordelingstekst .= "<tr><td>" . $this->liste($this->kontraktpersoner($del['kontraktnr'])) . "</td><td style=\"text-align: right; width: 100px;\">kr. " . number_format($del['beløp'], 2, ",", " ") . "</td></tr>\n";
					$sum += $del['beløp'];
				}
				$fordelingstekst .= "</tbody></table>";
				$resultat['data'][$index]['html'] = $fordelingstekst;
			}
			return json_encode($resultat);
		case "fordelingsnokkel":
				$sql =	"SELECT fs_fordelingsnøkler.*, leieobjekter.boenhet
						FROM fs_fordelingsnøkler LEFT JOIN leieobjekter ON fs_fordelingsnøkler.leieobjekt = leieobjekter.leieobjektnr
						WHERE anleggsnummer = '{$this->GET['anleggsnummer']}'
						ORDER BY field(fordelingsmåte, 'Fastbeløp', 'Prosentvis', 'Andeler'), følger_leieobjekt, leieobjekt";
				$nokler = $this->arrayData($sql);
				$nokler = $nokler['data'];
				$html = "<table width=\"100%\">";
				foreach($nokler as $nokkel){
					$beboere = array();
					$kontrakter = $this->dagensBeboere($nokkel['leieobjekt']);
					foreach($kontrakter AS $kontrakt){
						$beboere[] = $this->liste($this->kontraktpersoner($kontrakt));
					}
					
					$html .= "<tr><td style=\"vertical-align:top;\"><p style=\"font-size: 1em\">";
					
					switch ($nokkel['fordelingsmåte']){
						case "Fastbeløp":
							$html .= "<b>Kr. " . number_format($nokkel['fastbeløp'], 2, ",", " ") . "</b> betales av ";
							if($nokkel['følger_leieobjekt']){
								$html .= "" . $this->leieobjekt($nokkel['leieobjekt'], true) . " " . ($this->liste($beboere) ? ("(der " . $this->liste($beboere) . " leier nå)") : "<i>(som nå står ledig, så beløpet dekkes av {$this->valg['utleier']})</i>") . "<br />";
							}
							else {
								$html .= $this->liste($this->kontraktpersoner($this->sistekontrakt($nokkel['leieforhold']))) . "<br />";
							}
							break;
						case "Prosentvis":
							$html .= "<b>" . (($nokkel['prosentsats'] == 1) ? "Alt" : number_format(($nokkel['prosentsats'] * 100), 2, ",", " ") . "%") . "</b> betales av ";
							if($nokkel['følger_leieobjekt']){
								$html .= "" . $this->leieobjekt($nokkel['leieobjekt'], true) . " " . ($this->liste($beboere) ? ("(der " . $this->liste($beboere) . " leier nå)") : "<i>(som nå står ledig, så andelen dekkes av {$this->valg['utleier']})</i>") . "<br />";
							}
							else {
								$html .= $this->liste($this->kontraktpersoner($this->sistekontrakt($nokkel['leieforhold']))) . "<br />";
							}
							break;
						case "Andeler":
							$html .= "<b>" . $nokkel['andeler'] . (($nokkel['andeler'] > 1) ? " deler " : " del ") . "</b> betales av ";
							if($nokkel['følger_leieobjekt']){
								$html .= (count($kontrakter)>1 ? "hver av leieavtalene i " : "") . $this->leieobjekt($nokkel['leieobjekt'], true) . "<br />(nå: " . ($this->liste($beboere) ? $this->liste($beboere) : "<i>ledig</i>") . ")<br />";
							}
							else {
								$html .= $this->liste($this->kontraktpersoner($this->sistekontrakt($nokkel['leieforhold']))) . "<br />";
							}
							break;
					}
					$html .= "</p></td><td  style=\"vertical-align:top;\" width=\"20px\">&nbsp;</td></tr>";
				}
				$html .= "</table>";
				if(!count($nokler)){
					$html .= "Ingen fordeling av dette anlegget<br /><br />";
				}
			return $html;
		default:
			$resultat = $this->arrayData($this->hoveddata);
			$resultat = $resultat['data'][0];
?>
<table><tbody style="font-size: 1em;">
	<tr>
		<td style="padding: 0 5px;"><b>Anleggsnr:</b></td>
		<td style="padding: 0 5px;"><?=$resultat['anleggsnr']?></td>
	</tr><tr>
		<td style="padding: 0 5px;"><b>Målernr:</b></td>
		<td style="padding: 0 5px;"><?=$resultat['målernummer']?></td>
	</tr><tr>
		<td style="padding: 0 5px;"><b>Brukes til:</b></td>
		<td style="padding: 0 5px;"><?=$resultat['bruk']?></td>
	</tr><tr>
		<td style="padding: 0 5px;"><b>Målerplassering:</b></td>
		<td style="padding: 0 5px;"><?=$resultat['plassering']?></td>
	</tr>
</tbody></table>
<?
			return;
	}
}

}
?>