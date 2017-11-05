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

	$this->fra = @$this->mysqli->real_escape_string($_GET['fra']);
	$this->til = @$this->mysqli->real_escape_string($_GET['til']);
	if ( isset( $_POST['fra'] ) ) {
		$this->fra = $this->mysqli->real_escape_string($_POST['fra']);
	}
	if ( isset( $_POST['til'] ) ) {
		$this->til = $this->mysqli->real_escape_string($_POST['til']);
	}
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

// 	Ext.QuickTips.init();
// 	Ext.form.Field.prototype.msgTarget = 'side';

	// oppretter datasettet
	var innbetalinger = new Ext.data.JsonStore({
		url:'index.php?oppslag=statistikk_innbetalinger&oppdrag=hentdata&data=innbetalinger',
		fields: [
			{name: 'måned'},
			{name: 'innbetalt', type: 'float'},
			{name: 'krav', type: 'float'}
		],
		root: 'data'
    });

	var refresh = function(){
		innbetalinger.load({
			params: {
				fra: Ext.util.Format.date(fradato.getValue(), 'Y-m-d'),
				til: Ext.util.Format.date(tildato.getValue(), 'Y-m-d')
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
	
	var bunnlinje = new Ext.Toolbar({
		items: [{html: '  Fra dato:  '}, fradato, {html: '  Til dato:  '}, tildato]
	});

	var panel = new Ext.Panel({
		width: 900,
        height: 500,
		iconCls: 'chart',
		title: 'Innbetalinger (linje) mot krav (søyler)',
		frame: true,
		layout: 'fit',
		autoScroll: true,
		bbar: bunnlinje,
		items: [{
			xtype: 'linechart',
			store: innbetalinger,
			xField: 'måned',
			yField: 'innbetalt',
			yAxis: new Ext.chart.NumericAxis({
				displayName: 'Beløp',
				labelRenderer : Ext.util.Format.noMoney
			}),
			series: [{
				type: 'column',
				displayName: 'Krav',
				yField: 'krav',
				style: {
					mode: 'stretch',
					color:0xA40000
				}
			},{
				type:'line',
				displayName: 'Innbetalt',
				yField: 'innbetalt',
				style: {
					color: 0x228B22
				}
			}]

		}
		]
	});
	
	// Rutenettet rendres in i HTML-merket '<div id="panel">':
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
	}
}

}
?>