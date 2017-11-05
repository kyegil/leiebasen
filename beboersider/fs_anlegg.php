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
	$this->hoveddata = "SELECT * FROM fs_fellesstrømanlegg ORDER BY anleggsnummer";
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
		url:'index.php?oppdrag=hentdata&oppslag=fs_anlegg',
		fields: [
			{name: 'anleggsnummer', type: 'float'},
			{name: 'målernummer', type: 'float'},
			{name: 'plassering'},
			{name: 'formål'},
			{name: 'html'}
		],
		root: 'data'
    });
    datasett.load();

	var anleggsnummer = {
		align: 'right',
		dataIndex: 'anleggsnummer',
		header: 'Anleggsnummer',
		sortable: true,
		width: 90
	};

	var målernummer = {
		align: 'right',
		dataIndex: 'målernummer',
		header: 'Målernummer',
		sortable: true,
		width: 80
	};

	var plassering = {
		dataIndex: 'plassering',
		header: 'Plassering',
		sortable: true,
		width: 200
	};

	var vis = {
		dataIndex: 'anleggsnummer',
		header: 'Vis',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			if(!value) value = '*';
			return "<a title=\"Vis detaljer\" href=\"index.php?oppslag=fs_anlegg_kort&anleggsnummer=" + value + "\"><img src=\"../bilder/detaljer_lite.png\" /></a>";
		},
		sortable: false,
		width: 30
	};

	var formål = {
		id: 'formål',
		dataIndex: 'formål',
		header: 'Brukes til',
		sortable: true
	};


	var rutenett = new Ext.grid.GridPanel({
		autoExpandColumn: 'formål',
		store: datasett,
		columns: [
			vis,
			anleggsnummer,
			målernummer,
			plassering,
			formål		
		],
		stripeRows: true,
        height:500,
        width: 600,
        title:'Strømanlegg og anvendelse'
    });

	// Rutenettet rendres in i HTML-merket '<div id="adresseliste">':
	rutenett.render('panel');

	// Oppretter detaljpanelet
	var ct = new Ext.Panel({
		frame: true,
		height: 500,
		items: [
			{
				id: 'detaljfelt',
				region: 'center',
				bodyStyle: {
					background: '#ffffff',
					padding: '7px'
				},
		autoScroll: true,
				html: 'Velg et strømanlegg i listen til venstre for å se fordelingsnøkkelen.'
			}
		],
		layout: 'border',
		renderTo: 'detaljpanel',
		title: 'Fordelingsnøkkel',
		width: 300
	})


	// Hva skjer når du klikker på ei linje i rutenettet?:
	rutenett.getSelectionModel().on('rowselect', function(sm, rowIdx, r) {
		var detaljfelt = Ext.getCmp('detaljfelt');
		
		// Format for detaljvisningen
		var detaljer = new Ext.Template([
			'{html}'
		]);
		detaljer.overwrite(detaljfelt.body, r.data);
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

function taimotSkjema() {
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		default:
			$resultat = $this->arrayData($this->hoveddata);
			foreach($resultat['data'] as $index=>$linje){
				$html = "<p><b>{$linje['formål']}</b><br /><br /></p>";
				$sql =	"SELECT fs_fordelingsnøkler.*, leieobjekter.boenhet
						FROM fs_fordelingsnøkler LEFT JOIN leieobjekter ON fs_fordelingsnøkler.leieobjekt = leieobjekter.leieobjektnr
						WHERE anleggsnummer = '{$linje['anleggsnummer']}'
						ORDER BY field(fordelingsmåte, 'Fastbeløp', 'Prosentvis', 'Andeler'), følger_leieobjekt, leieobjekt";
				$nokler = $this->arrayData($sql);
				$nokler = $nokler['data'];
				$html .= "<table width=\"100%\">";
				foreach($nokler as $nokkel){
					$beboere = array();
					$kontrakter = $this->dagensBeboere($nokkel['leieobjekt']);
					foreach($kontrakter AS $kontrakt){
						$beboere[] = $this->liste($this->kontraktpersoner($kontrakt));
					}
					
					$html .= "<tr><td style=\"vertical-align:top;\">";
					
					switch ($nokkel['fordelingsmåte']){
						case "Fastbeløp":
							$html .= "<b>Kr. " . number_format($nokkel['fastbeløp'], 2, ",", " ") . "</b> betales av ";
							if($nokkel['følger_leieobjekt']){
								$html .= "" . $this->leieobjekt($nokkel['leieobjekt'], true) . " (nå: " . ($this->liste($beboere) ? $this->liste($beboere) : "<i>ledig</i>") . ")<br />";
							}
							else {
								$html .= $this->liste($this->kontraktpersoner($this->sistekontrakt($nokkel['leieforhold']))) . "<br />";
							}
							break;
						case "Prosentvis":
							$html .= "<b>" . (($nokkel['prosentsats'] == 1) ? "Alt" : number_format(($nokkel['prosentsats'] * 100), 2, ",", " ") . "%") . "</b> betales av ";
							if($nokkel['følger_leieobjekt']){
								$html .= "" . $this->leieobjekt($nokkel['leieobjekt'], true) . " (nå: " . ($this->liste($beboere) ? $this->liste($beboere) : "<i>ledig</i>") . ")<br />";
							}
							else {
								$html .= $this->liste($this->kontraktpersoner($this->sistekontrakt($nokkel['leieforhold']))) . "<br />";
							}
							break;
						case "Andeler":
							$html .= "<b>" . $nokkel['andeler'] . (($nokkel['andeler'] > 1) ? " deler " : " del ") . "</b> betales av ";
							if($nokkel['følger_leieobjekt']){
								$html .= (count($kontrakter)>1 ? "hver leietaker i " : "") . $this->leieobjekt($nokkel['leieobjekt'], true) . " (nå: " . ($this->liste($beboere) ? $this->liste($beboere) : "<i>ledig</i>") . ")<br />";
							}
							else {
								$html .= $this->liste($this->kontraktpersoner($this->sistekontrakt($nokkel['leieforhold']))) . "<br />";
							}
							break;
					}
					$html .= "</td><td  style=\"vertical-align:top;\" width=\"20px\">&nbsp;</td></tr>";
				}
				$html .= "</table>";
				if(!count($nokler)){
					$html .= "Ingen fordeling av dette anlegget<br /><br />";
				}
				$resultat['data'][$index]['html'] = $html;
			}
			return json_encode($resultat);
	}
}

}
?>