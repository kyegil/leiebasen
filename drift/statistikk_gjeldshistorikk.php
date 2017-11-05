<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'leiebasen';
public $ext_bibliotek = 'ext-4.2.1.883';


function __construct() {
 	parent::__construct();
}



function skript() {
	if( isset( $_GET['returi'] ) && $_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	
?>

Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?php echo $this->http_host . "/" . $this->ext_bibliotek . "/examples/ux"?>');

Ext.require([
 	'Ext.data.*',
    'Ext.layout.container.Border',
    'Ext.chart.*'
]);

Ext.onReady(function() {

    Ext.tip.QuickTipManager.init();
	Ext.form.Field.prototype.msgTarget = 'side';
	Ext.Loader.setConfig({enabled:true});
	
<?
	include_once("_menyskript.php");
?>

	Ext.define('Utestående', {
		extend: 'Ext.data.Model',
		idProperty: 'id',
		fields: [ // http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
			{name: 'dato',					type: 'date', dateFormat: 'Y-m-d'},
			{name: 'dato_formatert',		type: 'string'},
			{name: 'utestående',			type: 'float'},
			{name: 'utestående_formatert',	type: 'string'}
		]
	});
	
	
	var lastData = function() {
		var melding = Ext.MessageBox.wait('Henter data...');
		datasett.load({
			params: {
				fra: fradato.getValue(),
				til: tildato.getValue(),
				dag: datoslider.getValue(),
				gjennomsnitt: (gjennomsnitt.getValue() ? 1 : 0)
			},
			callback: function() {
				melding.close();
			}
		});
	};
	
	
	var datasett = Ext.create('Ext.data.Store', {
		model: 'Utestående',
		
		proxy: {
			type: 'ajax',
			url: "index.php?oppslag=statistikk_gjeldshistorikk&oppdrag=hentdata",
			reader: {
				type: 'json',
				root: 'data'
			}
		}
	});
	datasett.on({
//		load: velgKrav
	});
	


	var fradato = Ext.create('Ext.form.field.Date', {
		allowBlank: false,
		fieldLabel: 'Fra',
		labelAlign:	'right',
		labelWidth: 30,
		format: 'M Y',
		name: 'fra',
		value: '<?=date('01.m.Y', $this->leggtilIntervall(time(), 'P-2Y'))?>',
		width: 120
	});



	var tildato = Ext.create('Ext.form.field.Date', {
		allowBlank: false,
		fieldLabel: 'Til',
		labelAlign:	'right',
		labelWidth: 30,
		format: 'M Y',
		name: 'til',
		value: new Date(),
		width: 120
	});



	var datoslider = Ext.create('Ext.slider.Single', {
		fieldLabel: 'Dag i måneden',
		labelAlign:	'right',
		labelWidth: 50,
		width: 250,
		value: <?php echo date('d');?>,
		increment: 1,
		minValue: 1,
		maxValue: 31,
		
		listeners: {
			changecomplete: lastData
		}
	});



	var gjennomsnitt = Ext.create('Ext.form.field.Checkbox', {
		boxLabel: 'Beregn månedlig snitt',
		margin: '0 10',
		inputValue:	1,
		uncheckedValue: 0,
		checked: false,
		listeners: {
			change: function( checkbox, newValue, oldValue, eOpts ) {
				if( newValue ) {
					datoslider.disable();
				}
				else {
					datoslider.enable();
				}
				lastData();
			}
		}
	});
	


	var diagram = Ext.create('Ext.chart.Chart', {
		store: datasett,
		height: 370,
		width: 850,
		axes: [
			{
				title:		'Utestående',
				type:		'Numeric',
				position:	'left',
				fields:		['utestående'],
				minimum: 0,
				label: {
					renderer: Ext.util.Format.noMoney
				}
			},
			{
				type:		'Time',
				position:	'bottom',
				fields:		['dato'],
				dateFormat:	'M Y',
				step: [Ext.Date.MONTH, 1]
			}
		],
		
		series: [{
			type: 'line',
			fill: true,
			xField: 'dato',
			yField: 'utestående',

			tips: {
				trackMouse: true,
				width: 110,
				height: 36,
				renderer: function(storeItem, item) {
					this.setTitle(storeItem.get('dato_formatert') + ':<br>' + storeItem.get('utestående_formatert'));
				}
			}
		}]
	});



	var panel = Ext.create('Ext.panel.Panel', {
		title: 'Historisk gjeldsoversikt',
		autoScroll: true,
		bodyPadding: 5,
		frame: true,
		items: [
			diagram
		],
		
		tbar: [
			fradato,
			tildato,
			datoslider,
			gjennomsnitt,
			{
				xtype: 'button',
				scale: 'medium',
				text: 'Oppdater',
				handler: lastData
			}
		],
		
		buttons: [
			{
				xtype: 'button',
				scale: 'medium',
				text: 'Tilbake',
				handler: function() {
					window.location = '<?php echo $this->returi->get();?>';
				}
			}
		],
		
		renderTo: 'panel',
		height: 500,
		width: 900
	});
	
	lastData();
	
	
});
<?
}



function design() {
?>
<div id="panel"></div>
<?
}



function hentData($data = "") {

	$tp = $this->mysqli->table_prefix;
	$resultat = (object)array(
		'success'	=> true,
		'msg'		=> "",
		'data'		=> array()
	);
	
	
	switch ($data) {
	
	default: {
		$gjennomsnitt	= @$_GET['gjennomsnitt'];
		$dag			= str_pad( @$_GET['dag'], 2, '0', STR_PAD_LEFT );
		$fra			= substr(@$_GET['fra'], 0, 7);
		$til			= substr(@$_GET['til'], 0, 7);
		$testdato		= new DateTime("{$fra}-01");
		$datosett		= array();
		$intervall = 'P1M';
		
		$fradato = new DateTime("{$fra}-01");
		$tildato = new DateTime("{$til}-01");
		
		$spenn =  $tildato->diff($fradato)->days;
		
		if($spenn > 4383) {
			$intervall = 'P1Y';
		}
		else if($spenn > 2192) {
			$intervall = 'P6M';
		}
		else if($spenn > 1461) {
			$intervall = 'P4M';
		}
		else if($spenn > 1097) {
			$intervall = 'P3M';
		}
		else if($spenn > 731) {
			$intervall = 'P2M';
		}
		
		while($testdato->format('Y-m') <= $til) {
			$testdato = new DateTime( $testdato->format('Y-m-') . min($testdato->format('t'), $dag) );
			$datosett[] = $testdato->format('Y-m' . ( $gjennomsnitt ? '' : '-d' ));
	
			$testdato = new DateTime($testdato->format('Y-m-01'));
			$testdato->add( new DateInterval($intervall) );
		}
		$utestående = $this->beregnUtestående($datosett, true);
		
		foreach( $utestående as $dato => $saldo ) {
			$resultat->data[] = array(
				'dato'					=> str_pad($dato, 10, '-01'),
				'dato_formatert'		=> ( $gjennomsnitt ? strftime('%B %Y', strtotime($dato)) : strftime('%d. %b %Y', strtotime($dato)) ),
				'utestående'			=> $saldo,
				'utestående_formatert'	=> $this->kr($saldo)
			);
		}
		
		return json_encode($resultat);
	}

	}
}



function manipuler( $data ) {
	switch ( $data ) {
	
	case "":
		echo json_encode($resultat);
		break;
	}
}



function taimotSkjema() {

	echo json_encode($resultat);

}



}
?>