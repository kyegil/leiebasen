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

	$this->fra = $this->mysqli->real_escape_string($_GET['fra']);
	$this->til = $this->mysqli->real_escape_string($_GET['til']);
	if ($_POST['fra'])
		$this->fra = $this->mysqli->real_escape_string($_POST['fra']);
	if ($_POST['til'])
		$this->til = $this->mysqli->real_escape_string($_POST['til']);
	if(!$this->fra)
		$this->fra = ('2003-07-01');
	if(!$this->til)
		$this->til = date('Y-m-d', $this->leggtilIntervall(time(), 'P-1M'));
}

function skript() {
	if($_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
?>
Ext.chart.Chart.CHART_URL = '../../resources/charts.swf';

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	var utestående = new Ext.data.JsonStore({
		url:'index.php?oppslag=statistikk_innbetalinger&oppdrag=hentdata&data=utestående',
		fields: [
			{name: 'dato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'utestående', type: 'float'}
		],
		root: 'data'
    });

	var refresh = function(){
		utestående.load({
			params: {
				fra: Ext.util.Format.date(fradato.getValue(), 'Y-m-d'),
				til: Ext.util.Format.date(tildato.getValue(), 'Y-m-d'),
				månedsdato: månedsdato.getValue()
			}
		});
	}

	var fradato = new Ext.form.DateField({
		allowBlank: true,
		altFormats: "j.n.y|j.n.Y|j/n/y|j/n/Y|j-n-y|j-n-Y|j. M y|j. M -y|j. M. Y|j. F -y|j. F y|j. F Y|j/n|j-n|dm|dmy|dmY|d|Y-m-d",
		fieldLabel: 'Fradato',
		format: 'd.m.Y',
		name: 'fra',
		value: '<?=date('01.m.Y', $this->leggtilIntervall(time(), 'P-3Y'))?>',
		minValue: '01.07.2003',
		listeners: {
			valid: refresh
		},
		width: 100
	});

	var tildato = new Ext.form.DateField({
		allowBlank: false,
		altFormats: "j.n.y|j.n.Y|j/n/y|j/n/Y|j-n-y|j-n-Y|j. M y|j. M -y|j. M. Y|j. F -y|j. F y|j. F Y|j/n|j-n|dm|dmy|dmY|d|Y-m-d",
		fieldLabel: 'Tildato',
		labelWidth: 100,
		format: 'd.m.Y',
		name: 'til',
		maxValue: '<?=date('t.m.Y', $this->leggtilIntervall(time(), 'P-1M'))?>',
		value: '<?=date('t.m.Y', $this->leggtilIntervall(time(), 'P-1M'))?>',
		listeners: {
			valid: refresh
		},
		width: 100
	});
	
	var tip = new Ext.slider.Tip({
		getText: function(thumb){
			if(thumb.value > 27)
				return '28. - 31. hver måned';
			else 
				return String.format('{0}. hver måned', thumb.value);
		}
	});
	
	var månedsdato = new Ext.slider.SingleSlider({
		value: 10,
		name: 'månedsdato',
		width: 200,
		plugins: tip,
		minValue: 1,
		maxValue: 31,
		listeners: {
			changecomplete: refresh
		},
	});
	
	var bunnlinje = new Ext.Toolbar({
		items: [{html: '  Fra dato:  '}, fradato, {html: '  Til dato:  '}, tildato, {html: '  Stikkdato i måneden:  '}, månedsdato]
	});

	var panel = new Ext.Panel({
		width: 900,
        height: 500,
		iconCls: 'chart',
		title: 'Utestående',
		bbar: bunnlinje,
		frame: true,
		layout: 'fit',
		autoScroll: true,
		items: [{
			xtype: 'linechart',
			store: utestående,
			xField: 'dato',
			yField: 'utestående',
			yAxis: new Ext.chart.NumericAxis({
				displayName: 'Beløp',
				labelRenderer : Ext.util.Format.noMoney
			})

		}
		]
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

function taimotSkjema() {
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		case "innbetalinger":
			$sql = 	$this->hoveddata = 
				"SELECT MONTH(dato) AS måned, YEAR(dato) AS år, SUM(innbetalinger.beløp) AS innbetalt\n"
			.	"FROM innbetalinger\n"
			.	"WHERE konto != '0'\n"
			.	"AND dato >= '$this->fra'\n"
			.	"AND dato <= '$this->til'\n"
			.	"GROUP BY år, måned\n"
			.	"ORDER BY år, måned";
			$resultat = $this->arrayData($sql);
			foreach($resultat['data'] as $id=>$linje){
				$sql =	"SELECT SUM(beløp) AS krav
						FROM krav
						WHERE kravdato >= '$this->fra'
						AND kravdato <= '$this->til'
						AND YEAR(kravdato) = '{$linje['år']}'
						AND MONTH(kravdato) = '{$linje['måned']}'";
				$krav = $this->arrayData($sql);
				$resultat['data'][$id]['krav'] = $krav['data'][0]['krav'];
				$resultat['data'][$id]['måned'] = strftime('%b %y', strtotime("{$linje['år']}-{$linje['måned']}-01"));
			}
			return json_encode($resultat);
		case "utestående":
			$sql = 	$this->hoveddata = 
				"
					SELECT DISTINCT DATE_FORMAT(dato, '%Y-%m-{$this->POST['månedsdato']}') AS dato
					FROM innbetalinger
					WHERE dato >= '$this->fra'
					AND dato <= '$this->til'
				";
			$resultat = $this->arrayData($sql);
			foreach($resultat['data'] as $id=>$linje){
				$sql =	"
					SELECT SUM(utestående) AS utestående
					FROM
					(SELECT krav.id, krav.beløp AS beløp, SUM(innbetalinger.beløp) AS innbetalt, SUM(krav.beløp) - SUM(innbetalinger.beløp) AS utestående
					FROM krav LEFT JOIN innbetalinger ON krav.id = innbetalinger.krav
					WHERE kravdato <= '{$linje['dato']}' AND innbetalinger.dato <= '{$linje['dato']}'
					GROUP BY krav.id
					) AS utestående
					";
				$utestående = $this->arrayData($sql);
				$resultat['data'][$id]['utestående'] = $utestående['data'][0]['utestående'];
			}
			return json_encode($resultat);
	}
}

}
?>