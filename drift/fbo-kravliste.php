<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'AvtaleGiro-trekkliste';
public $ext_bibliotek = 'ext-4.2.1.883';
	

function __construct() {
	parent::__construct();
}

function skript() {
	if( isset( $_GET['returi'] ) && $_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	$tp = $this->mysqli->table_prefix;
?>
Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?=$this->http_host . "/" . $this->ext_bibliotek . "/examples/ux"?>');

Ext.require([
 	'Ext.data.*',
 	'Ext.form.field.*',
    'Ext.layout.container.Border',
 	'Ext.grid.*',
    'Ext.ux.RowExpander'
]);

Ext.onReady(function() {

    Ext.tip.QuickTipManager.init();
	Ext.form.Field.prototype.msgTarget = 'side';
	Ext.Loader.setConfig({enabled:true});
	
<?
	include_once("_menyskript.php");
?>

	Ext.define('FboKrav', {
		extend: 'Ext.data.Model',
		idProperty: 'id',
		fields: [ // http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
			{name: 'id', type: 'int'},
			{name: 'leieforhold', type: 'int'},
			{name: 'leieforholdbeskrivelse', type: 'string'},
			{name: 'gironr', type: 'int'},
			{name: 'kid', type: 'string'},
			{name: 'forsendelse', type: 'string'},
			{name: 'oppdrag', type: 'string'},
			{name: 'beløp', type: 'float'},
			{name: 'overføringsdato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'forfallsdato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'varslet', type: 'date', dateFormat: 'Y-m-d H:i:s'},
			{name: 'varslingsform', type: 'string'},
			{name: 'html', type: 'string'},
			{name: 'slettes', type: 'boolean'},
			{name: 'status', type: 'int'},
			{name: 'statusbeskrivelse', type: 'string'},
			{name: 'mottakskvittering', type: 'string'},
			{name: 'prosessert_kvittering', type: 'string'}
		]
	});
	
	visKvittering = function( kvittering ) {
		var melding = Ext.create('Ext.window.Window', {
			title: 'Kvittering fra NETS for mottatt forsendelse',
			height: 400,
			width: 600,
			layout: 'fit',
			html: kvittering
		});
		melding.show();
	}
	
	
	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
		clicksToEdit: 1
	});
	
	var rowEditing = Ext.create('Ext.grid.plugin.RowEditing', {
		autoCancel: false,
		listeners: {
			beforeedit: function (grid, e, eOpts) {
				return e.column.xtype !== 'actioncolumn';
			},
		},
	});


	var sendteTrekkrav = Ext.create('Ext.data.Store', {
		model: 'FboKrav',
		pageSize: 300,
		remoteSort: true,
		proxy: {
			type: 'ajax',
			simpleSortMode: true,
			url: "index.php?oppslag=fbo-kravliste&oppdrag=hentdata&data=trekkrav",
			reader: {
				type: 'json',
				root: 'data',
				actionMethods: {
					read: 'POST'
				},
				totalProperty: 'totalRows'
			}
		},
		sorters: [{
			property: 'forfallsdato',
			direction: 'ASC'
		}],
		autoLoad: true
	});
	

	var giroerPåVent = Ext.create('Ext.data.Store', {
		model: 'FboKrav',
		pageSize: 300,
		remoteSort: true,
		proxy: {
			type: 'ajax',
			simpleSortMode: true,
			url: "index.php?oppslag=fbo-kravliste&oppdrag=hentdata&data=venteliste",
			reader: {
				type: 'json',
				root: 'data',
				actionMethods: {
					read: 'POST'
				},
				totalProperty: 'totalRows'
			}
		},
		sorters: [{
			property: 'id',
			direction: 'ASC'
		}],
		autoLoad: {start: 0, limit: 300}
	});
	


	var søkefelt = Ext.create('Ext.form.field.Text', {
		emptyText: 'Søk og klikk ↵',
		name: 'søkefelt',
		width: 200,
		listeners: {
			specialkey: function( felt, e, eOpts ) {
				sendteTrekkrav.getProxy().extraParams = {
					søkefelt: søkefelt.getValue()
				};
				sendteTrekkrav.load({
					params: {
						start: 0,
						limit: 300
					}
				});
				
				giroerPåVent.getProxy().extraParams = {
					søkefelt: søkefelt.getValue()
				};
				giroerPåVent.load({
					params: {
						start: 0,
						limit: 300
					}
				});
			}
		}
	});


	var rutenettSendteKrav = Ext.create('Ext.grid.Panel', {
		region: 'center',
		autoScroll: true,
		frame: false,

		plugins: [{
			ptype: 'rowexpander',
			rowBodyTpl : ['{html}']
		}],

		store: sendteTrekkrav,
		title: 'AvtaleGiro-trekk oversendt bank',
		listeners: {
			celldblclick: function( panel, td, cellIndex, record, tr, rowIndex, e, eOpts ) {
//				window.location = "index.php?oppslag=leieforholdkort&id=" + record.get('leieforhold');
			}
		},
		columns: [	// http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
			{
				dataIndex: 'id',
				text: 'ID',
				hidden: true,
				width: 50,
				sortable: true
			},
			{
				dataIndex: 'forfallsdato',
				text: 'Forfall',
				width: 70,
				align: 'right',
				renderer: Ext.util.Format.dateRenderer('d.m.Y')
			},
			{
				dataIndex: 'leieforhold',
				text: 'Leieforhold',
				width: 200,
				renderer: function(value, metadata, record, rowIndex, colIndex, store){
					return record.get('leieforholdbeskrivelse');
				},
				flex: 1,
				sortable: true
			},
			{
				dataIndex: 'gironr',
				text: 'Giro',
				width: 50,
				align: 'right',
				sortable: true
			},
			{
				dataIndex: 'kid',
				text: 'KID',
				width: 90,
				align: 'right'
			},
			{
				dataIndex: 'beløp',
				text: 'Beløp',
				width: 70,
				align: 'right',
				renderer: Ext.util.Format.noMoney
			},
			{
				dataIndex: 'forsendelse',
				text: 'Forsendelse',
				width: 70,
				align: 'right',
				renderer: function(value, metaData, record, rowIndex, colIndex, store) {
					if (record.get('mottakskvittering')) {
						return "<b style=\"cursor: pointer;\" onClick=\"visKvittering('" + record.get('prosessert_kvittering') + "')\">" + value + "</b>";
					}
					else {
						return value;
					}
				}

			},
			{
				dataIndex: 'oppdrag',
				text: 'Oppdrag',
				width: 70,
				align: 'right',
				renderer: function(value, metaData, record, rowIndex, colIndex, store) {
					if (record.get('prosessert_kvittering')) {
						return "<b style=\"cursor: pointer;\" onClick=\"visKvittering('" + record.get('prosessert_kvittering') + "')\">" + value + "</b>";
					}
					else {
						return value;
					}
				}
			},
			{
				dataIndex: 'overføringsdato',
				text: 'Overført',
				width: 70,
				align: 'right',
				renderer: Ext.util.Format.dateRenderer('d.m.Y')
			},
			{
				dataIndex: 'varslet',
				text: 'Varslet',
				width: 70,
				align: 'right',
				renderer: Ext.util.Format.dateRenderer('d.m.Y')
			},
			{
				dataIndex: 'varslingsform',
				text: 'Varslingsform',
				width: 100,
				align: 'left'
			},
			{
				dataIndex: 'status',
				text: 'Status',
				width: 60,
				align: 'left',
				renderer: function(value, metadata, record, rowIndex, colIndex, store){
					return record.get('statusbeskrivelse');
				}
			}
		]
	});


	var venteregister = Ext.create('Ext.grid.Panel', {
		region: 'south',
		autoScroll: true,
		split: true,
		collapsible: true,
		collapsed: true,

		plugins: [{
			ptype: 'rowexpander',
			rowBodyTpl : ['{html}']
		}],

		store: giroerPåVent,
		title: 'Oppdrag på venteliste for overføring til bank',
		listeners: {
			celldblclick: function( panel, td, cellIndex, record, tr, rowIndex, e, eOpts ) {
//				window.location = "index.php?oppslag=leieforholdkort&id=" + record.get('leieforhold');
			}
		},
		columns: [	// http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
			{
				dataIndex: 'forfallsdato',
				text: 'Forfall',
				width: 70,
				align: 'right',
				renderer: Ext.util.Format.dateRenderer('d.m.Y')
			},
			{
				dataIndex: 'leieforhold',
				text: 'Leieforhold',
				width: 200,
				renderer: function(value, metadata, record, rowIndex, colIndex, store){
					return record.get('leieforholdbeskrivelse');
				},
				flex: 1,
				sortable: true
			},
			{
				dataIndex: 'gironr',
				text: 'Giro',
				width: 50,
				align: 'right',
				sortable: true
			},
			{
				dataIndex: 'kid',
				text: 'KID',
				width: 90,
				align: 'right'
			},
			{
				dataIndex: 'beløp',
				text: 'Beløp',
				width: 70,
				align: 'right',
				renderer: Ext.util.Format.noMoney
			},
			{
				dataIndex: 'varslet',
				text: 'Varslet',
				width: 70,
				align: 'right',
				renderer: Ext.util.Format.dateRenderer('d.m.Y')
			},
			{
				dataIndex: 'varslingsform',
				text: 'Varslingsform',
				width: 100,
				align: 'left'
			},
			{
				dataIndex: 'status',
				text: 'Status',
				width: 60,
				align: 'left',
				renderer: function(value, metadata, record, rowIndex, colIndex, store){
					return record.get('statusbeskrivelse');
				}
			}
		],
		height: 240
	});


	var container = Ext.create('Ext.panel.Panel', {
//		autoScroll: true,
		layout: 'border',
//		frame: false,
		tbar: [
			søkefelt
		],
		items: [
			rutenettSendteKrav,
			venteregister
		],
		renderTo: 'panel',
		height: 500,
		width: 900,
		buttons: [{
			text: 'Tilbake',
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
			}
		}, {
			text: 'Faste betalingsoppdrag',
			handler: function() {
				window.location = 'index.php?oppslag=fboliste';
			}
		}]
	});


	giroerPåVent.on({
		load: function( store, records, successful, eOpts ) {
			if( records.length ) {
				venteregister.expand();
			}
			else {
				venteregister.collapse();
			}
		}
	});


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
	$resultat = null;

	switch ($data) {

	case "trekkrav": {
		$søkefelt = @$this->GET['søkefelt'];
		$sort		= @$_GET['sort'];
		$synkende	= @$_GET['dir'] == "DESC" ? true : false;
		$start		= (int)@$_GET['start'];
		$limit		= @$_GET['limit'];
	
		$filter = "AND (fbo_trekkrav.leieforhold LIKE '%{$søkefelt}%' OR CONCAT(personer.fornavn, ' ', personer.etternavn) LIKE '%{$søkefelt}%')";
	
		$resultat = $this->mysqli->arrayData(array(
			'distinct'	=>true,
			'fields'	=> "fbo_trekkrav.*",
			'returnQuery' => true,
			'source' => "{$tp}fbo_trekkrav AS fbo_trekkrav
						INNER JOIN {$tp}kontrakter AS kontrakter
						ON fbo_trekkrav.leieforhold = kontrakter.leieforhold
						LEFT JOIN {$tp}kontraktpersoner AS kontraktpersoner
						ON kontrakter.kontraktnr = kontraktpersoner.kontrakt
						LEFT JOIN {$tp}personer AS personer
						ON kontraktpersoner.person = personer.personid
						",
			'where'	=> "fbo_trekkrav.forfallsdato >= CURDATE() {$filter}",
			'orderfields' => "fbo_trekkrav.id DESC"
		));
		
		foreach($resultat->data as $trekkrav) {
			$leieforhold = $this->hent('Leieforhold', $trekkrav->leieforhold);
			$leieforhold = $this->hent('Leieforhold', $trekkrav->leieforhold);
			$html = array();

			$giro = $this->hent('Giro', $trekkrav->gironr);
			
			$trekkrav->varslingsform = "";
			$trekkrav->status = 0;
			$trekkrav->statusbeskrivelse = "OK";
			$ukedag = date('N');
			$nesteForsendelse = $this->netsNesteForsendelse();
			$forsendelsesdag = $nesteForsendelse->format('Y-m-d') == date('Y-m-d')
							?	"i dag"
							:	(strftime('%A', $nesteForsendelse->format('U')));
			
			if( $giro->hentId() ) {
				$forfall = $giro->hent('forfall');
				$utestående = $giro->hent('utestående');
				$girokrav = $giro->hent('krav');

				foreach ($girokrav as $krav ) {
					$html[] = "<a title=\"Åpne\" href=\"index.php?oppslag=krav_kort&id={$krav}\">{$krav->hent('tekst')}</a>";
				}
				$trekkrav->varslingsform = ucfirst(@$giro->hent('format'));
				
				if(
					!$forfall
					or $forfall->format('Y-m-d') != $trekkrav->forfallsdato
					or $utestående != $trekkrav->beløp
				) {
					$trekkrav->endret = true;
				}
				else {
					$trekkrav->endret = false;
				}
				
			}
			else {
				$trekkrav->endret = true;
			}
			
			if($trekkrav->endret) {
				$trekkrav->status = 1;
				$trekkrav->statusbeskrivelse = "<div title=\"Forfall eller utestående beløp er endret siden trekket ble oversendt bank. Slettemelding vil sendes NETS ved neste forsendelse {$forsendelsesdag} kl. {$nesteForsendelse->format('H:i')}.\">Endret</div>";
				
				if( $trekkrav->forfallsdato <= $nesteForsendelse->format('Y-m-d') ) {
					$trekkrav->status = 2;
					$trekkrav->statusbeskrivelse = "<div style=\"color:red;\" title=\"Forfall eller utestående beløp er endret siden kravet ble oversendt NETS,\nmen en slettemelding nå vil trolig bli avvist av NETS pga for kort frist.\"><span style=\"font-size:larger;\">⚠</span></div>";
				}
				else if(
					$giro->hentId()
					and	($giro->hent('utestående') != 0)
					and	$this->netsNesteForsendelse() > $giro->fboOppdragsfrist()
				) {
					$trekkrav->status = 2;
					$trekkrav->statusbeskrivelse = "<div style=\"color:red;\" title=\"Forfall eller utestående beløp er endret siden trekket ble oversendt bankene,\nmen et korrigert trekk vil trolig bli avvist av NETS pga for kort frist.\nDersom trekket skal korrigeres må forfallsdato flyttes til et senere tidspunkt.\nBetaler kan evt selv slette trekket ifra sin nettbank\"><span style=\"font-size:larger;\">⚠</span></div>";
				}
			}

			$trekkrav->leieforholdbeskrivelse = "<a title=\"Åpne\" href=\"index.php?oppslag=leieforholdkort&id={$leieforhold}\">{$leieforhold}</a>: " . $leieforhold->hent('beskrivelse');

			$trekkrav->html = implode('<br />', $html);
			
		}

		$resultat->data =  $this->sorterObjekter($resultat->data, $sort, $synkende);
		$resultat->totalRows = count($resultat->data);
		$resultat->data = array_slice(  $resultat->data, $start, $limit);
		return json_encode( $resultat );
	}


	case "venteliste": {
		$søkefelt = @$this->GET['søkefelt'];
		$sort		= @$_GET['sort'];
		$synkende	= @$_GET['dir'] == "DESC" ? true : false;
		$start		= (int)@$_GET['start'];
		$limit		= @$_GET['limit'];
	
		$resultat = (object)array(
			'success'	=> true,
			'msg'		=> "",
			'data'		=> array()
		);

		$nesteForsendelse = $this->netsNesteForsendelse();
		$forsendelsesdag = $nesteForsendelse->format('Y-m-d') == date('Y-m-d')
						?	"i dag"
						:	(strftime('%A', $nesteForsendelse->format('U')));
				
		$filter = "kontrakter.leieforhold LIKE '%{$søkefelt}%' OR kontraktpersoner.leietaker LIKE '%{$søkefelt}%' OR CONCAT(personer.fornavn, ' ', personer.etternavn) LIKE '%{$søkefelt}%'";
	
		$giroer = $this->mysqli->arrayData(array(
			'distinct'	=>true,
			'class'		=> "Giro",
			'fields'	=> "{$tp}giroer.gironr AS id",
			'returnQuery' => true,
			'source' => "{$tp}giroer AS giroer
			
						INNER JOIN {$tp}fbo AS fbo
						ON giroer.leieforhold = fbo.leieforhold
						AND (giroer.utskriftsdato IS NULL OR giroer.utskriftsdato >= fbo.registrert)
						
						INNER JOIN {$tp}kontrakter AS kontrakter
						ON giroer.leieforhold = kontrakter.leieforhold
						
						INNER JOIN {$tp}krav AS krav
						ON giroer.gironr = krav.gironr
						
						LEFT JOIN {$tp}fbo_trekkrav AS fbo_trekkrav
						ON giroer.gironr = fbo_trekkrav.gironr
						
						INNER JOIN {$tp}kontraktpersoner AS kontraktpersoner
						ON kontrakter.kontraktnr = kontraktpersoner.kontrakt
						INNER JOIN {$tp}personer AS personer
						ON kontraktpersoner.person = personer.personid
						",
			'where'	=> "krav.utestående > 0 AND !fbo_trekkrav.gironr IS NULL AND ({$filter})
			",
			'orderfields' => "giroer.gironr DESC"
		));
		
		foreach( $giroer->data as $giro ) {
			$utskriftsdato = $giro->hent('utskriftsdato');
			$forfallsdato = $giro->hent('forfall');
			$fboOppdragsfrist = $giro->fboOppdragsfrist();
			$leieforhold = $giro->hent('leieforhold');
			$girokrav = $giro->hent('krav');
			$html = array();
			$status = 0;
			$statusbeskrivelse = "<div title=\"Betalingskravet vil bli oversendt NETS\nved neste AvtaleGiro trekkoppdrag {$forsendelsesdag} kl. {$nesteForsendelse->format('H:i')}.\">OK</div>";

			foreach ($girokrav as $krav ) {
				$html[] = "<a title=\"Åpne\" href=\"index.php?oppslag=krav_kort&id={$krav}\">{$krav->hent('tekst')}</a>";
			}
			
			if( $fboOppdragsfrist and ($nesteForsendelse > $fboOppdragsfrist) ) {
				$status = 2;
				$statusbeskrivelse = "<div style=\"color:red;\" title=\"Det er for kort frist for å oversende dette beløpet til trekk via AvtaleGiro.\nForsøk å endre forfall til en senere dato.\">⌛</div>";
			}
			
			$resultat->data[] = (object)array(
				'gironr'				=> strval($giro),
				'forfallsdato'			=> ( $forfallsdato ? $forfallsdato->format('Y-m-d') : "" ),
				'beløp'					=> $giro->hent('utestående'),
				'kid'					=> $giro->hent('kid'),
				'leieforhold'			=> strval($leieforhold),
				'leieforholdbeskrivelse'	=> "<a title=\"Åpne\" href=\"index.php?oppslag=leieforholdkort&id={$leieforhold}\">{$leieforhold}</a>: {$leieforhold->hent('beskrivelse')}",
				'varslet'				=> $utskriftsdato ? $utskriftsdato->format('Y-m-d H:i:s') : "",
				'varslingsform'			=> ucfirst(@$giro->hent('format')),
				'html'					=> implode('<br />', $html),
				'status'				=> $status,
				'statusbeskrivelse'		=> $statusbeskrivelse,
			);				
		}

		$resultat->data =  $this->sorterObjekter($resultat->data, $sort, $synkende);
		$resultat->totalRows = count($resultat->data);
		$resultat->data = array_slice(  $resultat->data, $start, $limit);
		return json_encode( $resultat );
		break;
	}


	default: {
		return json_encode($resultat);
	}
	}
}

function taimotSkjema($skjema) {
	switch ($skjema) {
		default:
			echo json_encode($resultat);
			break;
	}
}

function oppgave($oppgave) {
	switch ($oppgave) {
		default:
			break;
	}
}

}
?>