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
	setlocale(LC_ALL, "nb_NO");
	$this->hoveddata =
		"SELECT year(innbetalinger.dato) AS år, month(innbetalinger.dato) AS måned, SUM(innbetalinger.beløp) AS innbetalt, konto, krav.type\n"
	.	"FROM innbetalinger LEFT JOIN krav ON innbetalinger.krav = krav.id\n"
	.	"GROUP BY år, måned, konto, krav.type\n"
	.	"ORDER BY år DESC, måned DESC";
}

function skript() {
	if($_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

    // oppretter datasettet
    var datasett = new Ext.data.JsonStore({
    	url:'index.php?oppdrag=hentdata&oppslag=oversikt_innbetalinger&oppdrag=hentdata',
        fields: [
           {name: 'dato', type: 'date', dateFormat: 'Y-m-d'},
           {name: 'husleie', type: 'float'},
           {name: 'fellesstrøm', type: 'float'},
           {name: 'html'}
        ],
    	root: 'data'
    });
    datasett.load();


    var rutenett = new Ext.grid.GridPanel({
        store: datasett,
        columns: [{
		align: 'right',
		dataIndex: 'dato',
		header: 'Periode',
		renderer: Ext.util.Format.dateRenderer('F Y'),
		sortable: true,
		width: 100
	}, {
		align: 'right',
		dataIndex: 'husleie',
		header: 'Husleie',
		renderer: Ext.util.Format.noMoney,
		sortable: true,
		width: 100
	}, {
		align: 'right',
		dataIndex: 'fellesstrøm',
		header: 'Fellesstrøm',
		renderer: Ext.util.Format.noMoney,
		sortable: true,
		width: 100
	}],
		stripeRows: true,
        // autoExpandColumn: 'personid',
        height:500,
        width:350,
        title:'Innbetalt'
    });
	
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
				html: 'Velg periode i listen til venstre for å se detaljene.'
			}
		],
		layout: 'border',
		renderTo: 'detaljpanel',
		title: 'Detaljer',
		width: 550
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
<table style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="0">
<tbody>
<tr>
<td style="vertical-align: top; width: 250px;">
<table style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="0">
<tbody>
<tr>
<td style="vertical-align: top; width: 650px;">
<div id="panel"></div>
</td>
<td style="vertical-align: top;">
<div id="detaljpanel"></div>
</td>
</tr>
</tbody>
</table>
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
			$detaljer = $this->arrayData($this->hoveddata);
			foreach($detaljer['data'] as $detalj) {
				if($detalj['type'] == '') $detalj['type'] = 'Ubestemt';
				$resultat1['data'][strtotime($detalj['år'] . "-" . $detalj['måned'] . "-01")]['dato'] = date('Y-m-d', strtotime($detalj['år'] . "-" . $detalj['måned'] . "-01"));
				$resultat1['data'][strtotime($detalj['år'] . "-" . $detalj['måned'] . "-01")]['data'][$detalj['type']][$detalj['konto']] = $detalj['innbetalt'];
			}
			
			foreach($resultat1['data'] as $dato) {
				$resultat['data'][] = $dato;
			}
			
			foreach($resultat['data'] as $nr=>$dato) {
				$resultat['data'][$nr]['html'] =	"<p>Innbetalt " . strftime('%B %Y', strtotime($dato['dato'])) . ":</p><table cellspacing= \"20\"><tbody>";
				$resultat['data'][$nr]['html'] .=	"<tr><th style=\"text-align: right; width: 60px\"></th><th style=\"text-align: right; width: 100px\">Giro</th><th style=\"text-align: right; width: 100px\">Giro m KID</th><th style=\"text-align: right; width: 100px\">Kontant</th><th style=\"text-align: right; width: 100px\">Totalt</th></tr>";
				$total=array();
				foreach($dato['data'] as $type=>$metode) {
					$resultat['data'][$nr]['html'] .=	"<tr>";
					$resultat['data'][$nr]['html'] .=	"<td style=\"text-align: right;\">$type</td>";
					$resultat['data'][$nr]['html'] .=	"<td style=\"text-align: right;\">" . number_format($metode['Giro'], 2, ',', ' ') . "</td>";
					$resultat['data'][$nr]['html'] .=	"<td style=\"text-align: right;\">" . number_format($metode['OCR-giro'], 2, ',', ' ') . "</td>";
					$resultat['data'][$nr]['html'] .=	"<td style=\"text-align: right;\">" . number_format($metode['Kontant'], 2, ',', ' ') . "</td>";
					$resultat['data'][$nr]['html'] .=	"<td style=\"text-align: right; font-weight: bold;\">" . number_format($metode['Giro'] + $metode['OCR-giro'] + $metode['Kontant'], 2, ',', ' ') . "</td>";
					$resultat['data'][$nr]['html'] .=	"</tr>";
					$total['Kontant'] += $metode['Kontant'];
					$total['OCR-giro'] += $metode['OCR-giro'];
					$total['Giro'] += $metode['Giro'];
					
					if($type == 'Husleie') $resultat['data'][$nr]['husleie'] = $metode['Giro'] + $metode['OCR-giro'] + $metode['Kontant'];
					
					if($type == 'Fellesstrøm') $resultat['data'][$nr]['fellesstrøm'] = $metode['Giro'] + $metode['OCR-giro'] + $metode['Kontant'];
				}
				
				$resultat['data'][$nr]['html'] .=	"<tr>";
				$resultat['data'][$nr]['html'] .=	"<td style=\"text-align: right; font-weight: bold;\">Sum</td><td style=\"text-align: right; font-weight: bold;\">" . number_format($total['Giro'], 2, ',', ' ') . "</td><td style=\"text-align: right; font-weight: bold;\">" . number_format($total['OCR-giro'], 2, ',', ' ') . "</td><td style=\"text-align: right; font-weight: bold;\">" . number_format($total['Kontant'], 2, ',', ' ') . "</td><td style=\"text-align: right; font-weight: bold;\">" . number_format($total['Giro'] + $total['OCR-giro'] + $total['Kontant'], 2, ',', ' ') . "</td>";
				$resultat['data'][$nr]['html'] .=	"</tr>";
				$resultat['data'][$nr]['html'] .=	"</tbody></table>";
				$resultat['data'][$nr]['html'] .=	"<p><hr /></p>";
				$resultat['data'][$nr]['html'] .=	"<p>Periodens innbetalingskrav:</p>";
				$resultat['data'][$nr]['html'] .=	"<table cellspacing= \"20\"><tbody>";
				$sql = "SELECT type, SUM(beløp) AS beløp FROM krav WHERE kravdato >= '" . date('Y-m-01', strtotime($dato['dato'])) . "' AND kravdato <= '" . date('Y-m-d', $this->leggtilIntervall(strtotime($dato['dato']), 'P1M')-86400) . "' GROUP BY type";
				$gruppering = $this->arrayData($sql);
				$totalekrav = 0;
				foreach($gruppering['data'] as $gruppe){
					$resultat['data'][$nr]['html'] .=	"<tr><td style=\"text-align: right; width: 150px\">{$gruppe['type']}</td><td style=\"text-align: right; width: 80px\">" . number_format($gruppe['beløp'], 2, ',', ' ') . "</td></tr>";
					$totalekrav += $gruppe['beløp'];
				}
				$resultat['data'][$nr]['html'] .=	"<tr><td style=\"font-weight: bold; text-align: right; width: 150px\">Sum</td><td style=\"font-weight: bold; text-align: right; width: 80px\">" . number_format($totalekrav, 2, ',', ' ') . "</td></tr>";
				$resultat['data'][$nr]['html'] .=	"</tbody></table>";
			}
			$resultat['success'] = true;
// var_export($resultat);
// die();
			return json_encode($resultat);
	}
}

}
?>