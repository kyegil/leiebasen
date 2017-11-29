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
	$this->returi->reset();
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


	function sendmelding(){
		Ext.Ajax.request({
			waitMsg: 'Prøver å sende meldinger per epost...',
			url: 'index.php?oppslag=oversikt_innbetalinger&oppdrag=oppgave&oppgave=sendmelding',
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
    	buttons: [{
			text: 'Send meldinger om nye innbetalinger',
			handler: sendmelding
		}],
		frame: true,
		height: 500,
		items: [
			{
				id: 'detaljfelt',
				autoScroll: true,
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



function hentData($data = "") {
	$tp = $this->mysqli->table_prefix;
	
	$delkravtyper = @$this->mysqli->arrayData(array(
		'source'	=> "{$tp}delkravtyper",
		'where'		=> "aktiv"
	))->data;


	switch ($data) {

	default:
	
		$resultat = (object)array(
			'success'	=>true,
			'data'		=> array()
		);

		$perioder = $this->mysqli->arrayData(array(
			'source'		=> "{$tp}innbetalinger AS innbetalinger LEFT JOIN {$tp}krav AS krav ON innbetalinger.krav = krav.id",
			'fields'		=>	"substring(innbetalinger.dato, 1, 7) AS tidsrom, year(innbetalinger.dato) AS år,
								month(innbetalinger.dato) AS måned,
								SUM(innbetalinger.beløp) AS innbetalt,
								innbetalinger.konto,
								krav.type",
			'groupfields'	=>	"tidsrom, konto, krav.type",
			'orderfields'	=>	"tidsrom DESC, måned DESC",
			'where'			=>	"innbetalinger.konto != '0'"
		));
		
		
		$detaljer = $this->arrayData($this->hoveddata);
		
		foreach($perioder->data as $detalj) {
			if( !$detalj->type ) {
				$detalj->type = 'Ubestemt';
			}
			settype($resultat->data[$detalj->tidsrom], 'object');
			settype($resultat->data[$detalj->tidsrom]->husleie, 'float');
			settype($resultat->data[$detalj->tidsrom]->fellesstrøm, 'float');
			
			settype($resultat->data[$detalj->tidsrom]->data, 'object');
			settype($resultat->data[$detalj->tidsrom]->data->{$detalj->type}, 'object');
			settype($resultat->data[$detalj->tidsrom]->data->{$detalj->type}->{$detalj->konto}, 'float');
			

			$resultat->data[$detalj->tidsrom]->dato = "{$detalj->tidsrom}-01";
			$resultat->data[$detalj->tidsrom]->tidsrom = $detalj->tidsrom;
			
			if($detalj->type == 'Husleie') {
				$resultat->data[$detalj->tidsrom]->husleie += $detalj->innbetalt;
			}
			if($detalj->type == 'Fellesstrøm') {
				$resultat->data[$detalj->tidsrom]->fellesstrøm += $detalj->innbetalt;
			}

			$resultat->data[$detalj->tidsrom]->data->{$detalj->type}->{$detalj->konto} += $detalj->innbetalt;
			
		}

		$resultat->data = array_values($resultat->data);
		
		foreach( $resultat->data as $dato ) {
			$dato->html
			= "<p>Innbetalt " . strftime('%B %Y', strtotime($dato->dato)) . ":</p><table cellspacing= \"5\"><tbody>"
			. "<tr><th style=\"text-align: right; width: 60px\"></th><th style=\"text-align: right; width: 100px\">Giro</th><th style=\"text-align: right; width: 100px\">Giro m KID</th><th style=\"text-align: right; width: 100px\">Kontant</th><th style=\"text-align: right; width: 100px\">Totalt</th></tr>";

			$total = new stdclass;
			foreach($dato->data as $type => $metode) {
				$dato->html .=	"<tr>";
				$dato->html .=	"<td style=\"text-align: right;\">$type</td>";
					$dato->html .=	"<td style=\"text-align: right;\">" . $this->kr(@$metode->Giro) . "</td>";
					$dato->html .=	"<td style=\"text-align: right;\">" . $this->kr(@$metode->{"OCR-giro"}, true) . "</td>";
					$dato->html .=	"<td style=\"text-align: right;\">" . $this->kr(@$metode->Kontant) . "</td>";
					$dato->html .=	"<td style=\"text-align: right; font-weight: bold;\">" . $this->kr(@$metode->Giro + @$metode->{"OCR-giro"} + @$metode->Kontant) . "</td>";
				$dato->html .=	"</tr>";
				
				@$total->Kontant		+= @$metode->Kontant;
				@$total->{"OCR-giro"}	+= @$metode->{"OCR-giro"};
				@$total->Giro			+= @$metode->Giro;				

			}
			
			$dato->html .=	"<tr>";
			$dato->html .=	"<td style=\"text-align: right; font-weight: bold;\">Sum</td><td style=\"text-align: right; font-weight: bold;\">" . $this->kr($total->Giro) . "</td><td style=\"text-align: right; font-weight: bold;\">" . $this->kr($total->{'OCR-giro'}) . "</td><td style=\"text-align: right; font-weight: bold;\">" . $this->kr($total->Kontant) . "</td><td style=\"text-align: right; font-weight: bold;\">" . $this->kr($total->Giro + $total->{'OCR-giro'} + $total->Kontant) . "</td>";
			$dato->html .=	"</tr>";
			$dato->html .=	"</tbody></table>";
			$dato->html .=	"<p><a href=\"index.php?oppslag=oversikt_kontobevegelser&fra=" . date('Y-m-01', strtotime($dato->dato)) . "&til=" . date('Y-m-d', $this->leggtilIntervall(strtotime($dato->dato), 'P1M')-86400) . "\">Vis innbetalinger for denne perioden</a></p>";
			foreach($delkravtyper as $del) {
			$dato->html .=	"<p><a href=\"index.php?oppslag=oversikt_delkrav&id={$del->id}&oppdrag=utskrift&fra=" . date('Y-m-01', strtotime($dato->dato)) . "&til=" . date('Y-m-d', $this->leggtilIntervall(strtotime($dato->dato), 'P1M')-86400) . "\" target=\"blank\">{$del->navn}</a></p>";
			}
			$dato->html .=	"<p><hr /></p>";
			$dato->html .=	"<p>Periodens innbetalingskrav:</p>";
			$dato->html .=	"<table cellspacing= \"5\"><tbody>";
		}

		foreach($resultat->data as $nr=> $dato) {
			$gruppering = $this->mysqli->arrayData(array(
				'source'		=>	"{$tp}krav as krav",
				'fields'		=>	"krav.type, SUM(krav.beløp) AS beløp",
				'groupfields'	=>	"krav.type",
				'where'			=>	"kravdato >= '" . date('Y-m-01', strtotime($dato->dato)) . "' AND kravdato <= '" . date('Y-m-t', strtotime($dato->dato)) . "'"
			));
			
			$totalekrav = 0;
			foreach($gruppering->data as $gruppe) {
				$dato->html .=	"<tr><td style=\"text-align: right; width: 150px\"><a href=\"index.php?oppslag=oversikt_krav&kravtype={$gruppe->type}&fra=" . date('Y-m-01', strtotime($dato->dato)) . "&til=" . date('Y-m-t', strtotime($dato->dato)) . "\">{$gruppe->type}</a></td><td style=\"text-align: right; width: 80px\">" . $this->kr($gruppe->beløp) . "</td></tr>";
				$totalekrav += $gruppe->beløp;
			}
			$dato->html .=	"<tr><td style=\"font-weight: bold; text-align: right; width: 150px\"><a href=\"index.php?oppslag=oversikt_krav&fra=" . date('Y-m-01', strtotime($dato->dato)) . "&til=" . date('Y-m-d', $this->leggtilIntervall(strtotime($dato->dato), 'P1M')-86400) . "\">Sum</a></td><td style=\"font-weight: bold; text-align: right; width: 80px\">" . $this->kr($totalekrav) . "</td></tr>";
			$dato->html .=	"</tbody></table>";
			$dato->html .=	"<p><a href=\"index.php?oppslag=oversikt_husleiekrav&fra=" . date('Y-m-01', strtotime($dato->dato)) . "&til=" . date('Y-m-d', $this->leggtilIntervall(strtotime($dato->dato), 'P1M')-86400) . "\">Vis periodens husleiekrav etter leieobjekt</a></p>";
		}

		return json_encode($resultat);
		
	}
}

}
?>