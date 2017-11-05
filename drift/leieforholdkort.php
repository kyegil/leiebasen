<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'leiebasen';
public $ext_bibliotek = 'ext-4.2.1.883';


public function __construct() {
	parent::__construct();
}



/*	Pre HTML
Dersom leieforholdet ikke eksisterer vil du bli videresendt til oppslaget leieforhold_liste
******************************************
------------------------------------------
retur (boolsk) Sann for å skrive ut HTML-malen, usann for å stoppe den
*/
public function preHTML() {
	$leieforhold = $this->hent('Leieforhold', (int)@$_GET['id']);
	if( !$leieforhold->hent('id') ) {
		$leieforhold = $this->hent('Leieforhold', $this->leieforhold( (int)@$_GET['id'] ) );
	}
	
	if( !$leieforhold->hent('id') ) {
		header("Location: index.php?oppslag=leieforhold_liste");
		return false;
	}
	
	else {
		$this->tittel = "Leieforhold $leieforhold: " . $leieforhold->hent('navn') . " i " . $leieforhold->hent('leieobjekt')->hent('beskrivelse') . " | Leiebasen";
		return true;
	}
}



public function skript() {
	if( isset( $_GET['returi'] ) && $_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	
	$leieforhold = $this->hent('Leieforhold', (int)@$_GET['id']);
	if( !$leieforhold->hent('id') ) {
		$leieforhold = $this->hent('Leieforhold', $this->leieforhold( (int)@$_GET['id'] ) );
	}
	
	$tab = @$_GET['tab'];
	switch($tab) {
	case "oppsummering":
	case "kontoforløp":
	case "krav":
	case "innbetalinger":
	case "kontooversikt":
	case "varsling":
	case "leieavtale":
	case "fellesstrøm":
	case "statistikk":
		break;
	default:
		$tab = '';
		break;
	}
	
?>

Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?php echo $this->http_host . "/" . $this->ext_bibliotek . "/examples/ux"?>');

Ext.require([
 	'Ext.data.*',
 	'Ext.form.field.*',
    'Ext.layout.container.Border',
 	'Ext.grid.*',
    'Ext.grid.plugin.BufferedRenderer',
    'Ext.ux.RowExpander'
]);

Ext.onReady(function() {

    Ext.tip.QuickTipManager.init();
	Ext.form.Field.prototype.msgTarget = 'side';
	Ext.Loader.setConfig({enabled:true});
	
<?
	include_once("_menyskript.php");
?>

	Ext.define('Transaksjon', {
		extend: 'Ext.data.Model',
		idProperty: 'id',
		fields: [ // http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
			{name: 'id'},
			{name: 'dato',	type: 'date', dateFormat: 'Y-m-d'},
			{name: 'beløp',			type: 'float'},
			{name: 'husleie',		type: 'float'},
			{name: 'fellesstrøm',	type: 'float'},
			{name: 'annet',			type: 'float'},
			{name: 'innbetalt',	type: 'float'},
			{name: 'saldo',			type: 'float'},
			{name: 'tekst'}
		]
	});
	
	Ext.define('Krav', {
		extend: 'Ext.data.Model',
		idProperty: 'id',
		fields: [ // http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
			{name: 'id'},
			{name: 'dato',			type: 'date', dateFormat: 'Y-m-d'},
			{name: 'forfall',		type: 'date', dateFormat: 'Y-m-d', useNull: true},
			{name: 'giro',			type: 'int', useNull: true},
			{name: 'fom',			type: 'date', dateFormat: 'Y-m-d', useNull: true},
			{name: 'tom',			type: 'date', dateFormat: 'Y-m-d', useNull: true},
			{name: 'beløp',			type: 'float'},
			{name: 'utestående',	type: 'float'},
			{name: 'tekst'},
			{name: 'type'},
			{name: 'termin'},
			{name: 'html'}
		]
	});
	
	Ext.define('Innbetaling', {
		extend: 'Ext.data.Model',
		idProperty: 'id',
		fields: [ // http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
			{name: 'id'},
			{name: 'dato',				type: 'date', dateFormat: 'Y-m-d'},
			{name: 'beløp', 			type: 'float'},
			{name: 'betaler'},
			{name: 'konto'},
			{name: 'transaksjonstype',	type: 'int'},
			{name: 'transaksjonsbeskrivelse'},
			{name: 'OCRtransaksjon'},
			{name: 'ref'},
			{name: 'merknad'},
			{name: 'html'}
		]
	});
	
	function velgKrav() {
		var antall = kravsett.getCount();
		var indeks = 0;
		var idag = new Date();
		while(indeks < antall && kravsett.getAt(indeks).get('dato') > idag) {
			indeks ++;
		}
		if(indeks) {
			krav.getView().bufferedRenderer.scrollTo(indeks, false);
		}
	}


	function velgTransaksjon() {
		var antall = transaksjoner.getCount();
		var indeks = 0;
		var idag = new Date();
		while(indeks < antall && transaksjoner.getAt(indeks).get('dato') > idag) {
			indeks ++;
		}
		if(indeks) {
			kontoforløp.getView().bufferedRenderer.scrollTo(indeks, false);
		}
	}


	function saldorenderer(value, metadata, record, rowIndex, colIndex, store) {
		resultat = Ext.util.Format.noMoney(value);
		nå = new Date();
		nestelinje = store.getAt(rowIndex+1);
		if(nestelinje && (nestelinje.get('dato') > nå)) {
			resultat = '<span style="font-weight: bold;">' + resultat + '</span>'
		}
		
		if(record.get('dato') > nå) {
			resultat = '';
		}
		if(value < 0){
			resultat = '<span style="color: red;">' + resultat + '</span>'
		}
		return resultat;
	}


	function restrenderer(value, metadata, record, rowIndex, colIndex, store) {
		resultat = Ext.util.Format.noMoney(value);
		nå = new Date();
		if(value > 0 && record.get('dato') <= nå){
			resultat = '<span style="color: red;">' + resultat + '</span>'
		}
		if(record.get('dato') > nå){
			resultat = '<span style="color: grey;">' + resultat + '</span>'
		}
		if(value == 0){
			resultat = ''
		}
		return resultat;
	}

	gråfremtid = function(value, metadata, record, rowIndex, colIndex, store) {
		nå = new Date();
		if(value instanceof Date){
			value = Ext.util.Format.date(value, 'd.m.Y');
		}
		if(value === null) {
			return '';
		}
		if(record.get('dato') > nå) {
			return '<span style="color: grey;">' + value + '</span>';
		}
		else
			return value;
	}


	gråfremtid.beløp = function(value, metadata, record, rowIndex, colIndex, store) {
		nå = new Date();
		if(value) value = Ext.util.Format.noMoney(value);
		else value = "";
		if(record.get('dato') > nå) {
			return '<span style="color: grey;">' + value + '</span>';
		}
		else
			return value;
	}


	frysAvtale = function(v) {
		Ext.Ajax.request({
			waitMsg: 'Fryser...',
			url: "index.php?oppslag=leieforholdskjema&oppdrag=manipuler&data=frys&id=<?php echo $leieforhold;?>",
			success : function(result){
				Ext.MessageBox.alert('Sånn', 'Leieavtalen er frosset');
				oppsummering.getComponent('hovedoppsummering').getLoader().load();
				varsling.getComponent('hovedvarsling').getLoader().load();
				frysmeny.setHandler(function() {
					tinAvtale();
				});
				frysmeny.setText('Tin leieforholdet');
			},
			failure : function(result){
				Ext.MessageBox.show('Arg!', 'Klarte ikke fryse leieavtalen');
			}
		});
	}


	tinAvtale = function(v) {
		Ext.Ajax.request({
			waitMsg: 'Gjenåpner...',
			url: "index.php?oppslag=leieforholdskjema&oppdrag=manipuler&data=tin&id=<?php echo $leieforhold;?>",
			 success : function(result){
				Ext.MessageBox.alert('Sånn', 'Leieavtalen er åpen');
				oppsummering.getComponent('hovedoppsummering').getLoader().load();
				varsling.getComponent('hovedvarsling').getLoader().load();
				frysmeny.setHandler(function() {
					frysAvtale();
				});
				frysmeny.setText('Frys leieforholdet');
			 },
			 failure : function(result){
				Ext.MessageBox.alert('Arg!', 'Klarte ikke gjenåpne leieavtalen');
			 }
		});
	}
	
	
	var frysmeny = Ext.create('Ext.menu.Item', {
		text: '<?php echo $leieforhold->hent('frosset') ? "Tin leieforholdet" : "Frys leieforholdet";?>',
		handler: function(button, event) {
			<?php echo $leieforhold->hent('frosset') ? "tinAvtale();\n" : "frysAvtale();\n";?>
		}
	});


	var transaksjoner = Ext.create('Ext.data.Store', {
		model: 'Transaksjon',
		
		pageSize: 100,
		buffered: true,
        leadingBufferZone: 100,

		proxy: {
			type: 'ajax',
			simpleSortMode: true,
			url: "index.php?oppslag=leieforholdkort&id=<?php echo $leieforhold;?>&oppdrag=hentdata&data=transaksjoner",
			reader: {
				type: 'json',
				root: 'data',
				actionMethods: {
					read: 'POST'
				},
				totalProperty: 'totalRows'
			}
		},
		
		remoteSort: true,
		sorters: [{
			property: 'dato',
			direction: 'DESC'
		}]
	});
	transaksjoner.on({
		load: velgTransaksjon
	});
	


	var kravsett = Ext.create('Ext.data.Store', {
		model: 'Krav',
		
		pageSize: 100,
		buffered: true,
        leadingBufferZone: 100,
        
		proxy: {
			type: 'ajax',
			simpleSortMode: true,
			url: "index.php?oppslag=leieforholdkort&id=<?php echo $leieforhold;?>&oppdrag=hentdata&data=krav",
			reader: {
				type: 'json',
				root: 'data',
				actionMethods: {
					read: 'POST'
				},
				totalProperty: 'totalRows'
			}
		},

		remoteSort: true,
		sorters: [{
			property: 'dato',
			direction: 'DESC'
		}]
	});
	kravsett.on({
		load: velgKrav
	});
	


	var innbetalingssett = Ext.create('Ext.data.Store', {
		model: 'Innbetaling',
		
		pageSize: 100,
		buffered: true,
        leadingBufferZone: 100,
        
		proxy: {
			type: 'ajax',
			simpleSortMode: true,
			url: "index.php?oppslag=leieforholdkort&id=<?php echo $leieforhold;?>&oppdrag=hentdata&data=innbetalinger",
			reader: {
				type: 'json',
				root: 'data',
				actionMethods: {
					read: 'POST'
				},
				totalProperty: 'totalRows'
			}
		},
		
		remoteSort: true,
		sorters: [{
			property: 'dato',
			direction: 'DESC'
		}]
	});
	


	var status = Ext.create('Ext.form.Panel', {
		itemId: 'status',
		autoScroll: true,
		bodyPadding: 5,
		frame: true,
		region: 'east',
		width: 350,
		autoLoad: 'index.php?oppslag=leieforholdkort&id=<?php echo $leieforhold;?>&oppdrag=hentdata&data=balanse'
	});


	var oppsummering = Ext.create('Ext.panel.Panel', {
		itemId: 'oppsummering',
		title: 'Oppsummering',
		layout: 'border',
		items: [
			{
				itemId: 'hovedoppsummering',
				xtype: 'panel',
				region: 'center',
				autoScroll: true,
				bodyPadding: 5,
				border: false,

				loader: {
					url: 'index.php?oppslag=leieforholdkort&oppdrag=hentdata&id=<?php echo $leieforhold;?>',
					renderer: 'html',
					autoLoad: true
				}
		
			},
			status
		],
		buttons: [{
			scale: 'medium',
			text: 'Mer ...',
			menu: Ext.create('Ext.menu.Menu', {
				items: [
					{
						text: 'Oppfølging av leieforhold <?php echo $leieforhold;?>',
						handler: function() {
							window.location = '../oppfolging/index.php?oppslag=forsiden&leieforhold=<?php echo $leieforhold;?>'
						}
					}
				]
			})
		},
		{
			scale: 'medium',
			text: 'Handlinger',
			menu: Ext.create('Ext.menu.Menu', {
				items: [
<?php if( $leieforhold->hent('oppsigelse') ):?>
					frysmeny
<?php else:?>
					{
						text: 'Endre leieforholdet',
						handler: function() {
							window.location = 'index.php?oppslag=leieforholdskjema&id=<?php echo $leieforhold;?>'
						}
					},
<?php if( $leieforhold->hent('tildato') ):?>
					{
						text: 'Forny leieavtalen',
						handler: function() {
							window.location = 'index.php?oppslag=leieforhold_kontraktfornying&id=<?php echo $leieforhold;?>'
						}
					},
<?php endif;?>
					{
						text: 'Avslutt leieforholdet',
						handler: function() {
							window.location = 'index.php?oppslag=oppsigelsesskjema&id=<?php echo $leieforhold;?>'
						}
					}
<?php endif;?>
				]
			})
		},
		{
			scale: 'medium',
			text: 'Tilbake',
			handler: function() {
				window.location = '<?php echo $this->returi->get();?>';
			}
		}
		]
	});



	var kontoforløp = Ext.create('Ext.grid.Panel', {
		itemId: 'kontoforløp',
		autoScroll: true,
		frame: false,
		features: [],

		plugins: [{
			ptype: 'bufferedrenderer',
			trailingBufferZone: 20,  // Keep 20 rows rendered in the table behind scroll
			leadingBufferZone: 50   // Keep 50 rows rendered in the table ahead of scroll
		}],
		
		store: transaksjoner,
		title: 'Kontoforløp',
		tbar: [
//			søkefelt
		],
		columns: [{
				dataIndex: 'id',
				align: 'right',
				header: 'Id',
				hidden: true,
				renderer: gråfremtid,
				sortable: false,
				width: 110
			},
			{
				dataIndex: 'dato',
				header: 'Dato',
				renderer: gråfremtid,
				sortable: true,
				width: 70
			},
			{
				dataIndex: 'tekst',
				header: 'Beskrivelse',
				flex: 1,
				renderer: gråfremtid,
				sortable: false
			},
			{
				align: 'right',
				dataIndex: 'husleie',
				header: 'Husleie',
				renderer: gråfremtid.beløp,
				sortable: false,
				width: 70
			},
			{
				align: 'right',
				dataIndex: 'fellesstrøm',
				header: 'Fellesstrøm',
				renderer: gråfremtid.beløp,
				sortable: false,
				width: 70
			},
			{
				align: 'right',
				dataIndex: 'annet',
				header: 'Annet',
				renderer: gråfremtid.beløp,
				sortable: false,
				width: 70
			},
			{
				align: 'right',
				style: {
					color: 'green'
				},
				dataIndex: 'innbetalt',
				header: 'Innbetalt',
				renderer: gråfremtid.beløp,
				sortable: false,
				width: 80
			},
			{
				align: 'right',
				dataIndex: 'saldo',
				header: 'Saldo',
				renderer: saldorenderer,
				sortable: false,
				width: 80
			}
		],

		listeners: {
			celldblclick: function(view, td, cellIndex, record, tr, rowIndex) {
				if(record.get('innbetalt')) {
					window.location = "index.php?oppslag=innbetalingskort&id=" + record.get('id');
				}
				else {
					window.location = "index.php?oppslag=krav_kort&id=" + record.get('id');
				}
			}
		},
		
		buttons: []
	});
	kontoforløp.on({
		viewready: function() {
			transaksjoner.loadPage(1);
		}
	});
	

	var krav = Ext.create('Ext.grid.Panel', {
		itemId: 'krav',
		store: kravsett,
		title: 'Betalingskrav',
		autoScroll: true,
		frame: false,
		features: [],

		plugins: [{
			ptype: 'rowexpander',
			rowBodyTpl : ['{html}']
		}, {
			ptype: 'bufferedrenderer',
			trailingBufferZone: 100,  // Keep 20 rows rendered in the table behind scroll
			leadingBufferZone: 100   // Keep 50 rows rendered in the table ahead of scroll
		}],
		
		tbar: [
//			søkefelt
		],
		columns: [{
				align: 'right',
				dataIndex: 'id',
				header: 'Id',
				hidden: true,
				renderer: gråfremtid,
				sortable: true,
				width: 50
			},
			{
				dataIndex: 'dato',
				header: 'Dato',
				renderer: Ext.util.Format.dateRenderer('d.m.Y'), 
				renderer: gråfremtid,
				sortable: true,
				width: 70
			},
			{
				dataIndex: 'tekst',
				header: 'Krav',
				id: 'tekst',
				flex: 1,
				renderer: gråfremtid,
				sortable: true
			},
			{
				dataIndex: 'type',
				header: 'Kravtype',
				renderer: gråfremtid,
				sortable: true,
				width: 70
			},
			{
				dataIndex: 'fom',
				header: 'Fra',
				hidden: true,
				renderer: gråfremtid,
				sortable: true,
				width: 120
			},
			{
				dataIndex: 'tom',
				header: 'Til',
				hidden: true,
				renderer: gråfremtid,
				sortable: true,
				width: 120
			},
			{
				dataIndex: 'fom',
				header: 'Termin',
				renderer: function(value, metadata, record, rowIndex, colIndex, store) {
					return record.get('termin');
				},
				sortable: true,
				width: 140
			},
			{
				dataIndex: 'giro',
				header: 'Regning',
				renderer: gråfremtid,
				sortable: true,
				width: 70
			},
			{
				dataIndex: 'forfall',
				header: 'Forfall',
				renderer: gråfremtid,
				sortable: true,
				width: 120
			},
			{
				align: 'right',
				dataIndex: 'beløp',
				header: 'Beløp',
				renderer: gråfremtid.beløp,
				sortable: true,
				width: 70
			},
			{
				align: 'right',
				dataIndex: 'utestående',
				header: 'Ubetalt',
				renderer: restrenderer,
				sortable: true,
				width: 70
			}
		],

		listeners: {
			celldblclick: function(view, td, cellIndex, record, tr, rowIndex) {
				window.location = "index.php?oppslag=krav_kort&id=" + record.get('id');
			}
		},
		
		buttons: []
	});
	krav.on({
		viewready: function() {
			kravsett.loadPage(1);
		}
	});


	var innbetalinger = Ext.create('Ext.grid.Panel', {
		itemId: 'innbetalinger',
		autoScroll: true,
		frame: false,
		features: [],

		plugins: [{
			ptype: 'rowexpander',
			rowBodyTpl : ['{html}']
		}, {
			ptype: 'bufferedrenderer',
			trailingBufferZone: 20,  // Keep 20 rows rendered in the table behind scroll
			leadingBufferZone: 100   // Keep 50 rows rendered in the table ahead of scroll
		}],
		store: innbetalingssett,
		title: 'Innbetalinger',
		tbar: [
//			søkefelt
		],
		columns: [{
				dataIndex: 'id',
				header: 'Id',
				sortable: true,
				width: 110,
				hidden: true
			},
			{
				dataIndex: 'dato',
				header: 'Dato',
				renderer: Ext.util.Format.dateRenderer('d.m.Y'), 
				sortable: true,
				width: 70
			},
			{
				dataIndex: 'ref',
				header: 'Ref',
				sortable: true,
				width: 100
			},
			{
				dataIndex: 'betaler',
				header: 'Betalt av',
				id: 'betaler',
				flex: 1,
				sortable: true,
				width: 150
			},
			{
				dataIndex: 'konto',
				header: 'Konto',
				sortable: true
			},
			{
				dataIndex: 'transaksjonsbeskrivelse',
				header: 'Betalingsmåte',
				sortable: true,
				width: 150
			},
			{
				align: 'right',
				dataIndex: 'beløp',
				header: 'Beløp',
				renderer: Ext.util.Format.noMoney,
				sortable: true,
				width: 80
			},
			{
				dataIndex: 'merknad',
				header: 'Merknad',
				sortable: true,
				flex: 1
			}
		],

		listeners: {
			celldblclick: function(view, td, cellIndex, record, tr, rowIndex) {
				window.location = "index.php?oppslag=innbetalingskort&id=" + record.get('id');
			}
		},
		
		buttons: []
	});


	var kontooversikt = Ext.create('Ext.tab.Panel', {
		itemId: 'kontooversikt',
		title: 'Kontooversikt',
		items: [
			kontoforløp,
			krav,
			innbetalinger
		],
		activeTab: '<?php echo $tab;?>',
		
		buttons: [{
			scale: 'medium',
			text: 'Mer ...',
			menu: Ext.create('Ext.menu.Menu', {
				items: [
					{
						text: 'Oppfølging av leieforhold <?php echo $leieforhold;?>',
						handler: function() {
							window.location = '../oppfolging/index.php?oppslag=forsiden&leieforhold=<?php echo $leieforhold;?>'
						}
					}
				]
			})
		},
		{
			scale: 'medium',
			text: 'Handlinger',
			menu: Ext.create('Ext.menu.Menu', {
				items: [
					{
						text: 'Skriv ut kontoforløp',
						handler: function() {
							window.open("index.php?oppslag=leieforhold_kontoforløp_utskrift&oppdrag=utskrift&id=<?php echo $leieforhold;?>");
						}
					},
					{
						text: 'Legg inn nytt krav/kreditt ...',
						handler: function() {
							window.location = 'index.php?oppslag=kravskjema&leieforhold=<?php echo $leieforhold;?>&id=*'
						}
					},
					{
						text: 'Send ny giro...',
						handler: function() {
							window.location = 'index.php?oppslag=utskriftsmeny&id=<?php echo $leieforhold;?>'
						}
					},
					{
						text: 'Purr...',
						handler: function() {
							window.location = 'index.php?oppslag=utskriftsmeny_purringer&leieforhold=<?php echo $leieforhold;?>'
						}
					},
					{
						text: 'Skriv ut samlegiro',
						handler: function() {
							window.open('index.php?oppslag=leieforholdkort&id=<?php echo $leieforhold;?>&oppdrag=lagpdf&pdf=samlegiro')
						}
					},
					{
						text: 'Registrer betaling...',
						handler: function() {
							window.location = 'index.php?oppslag=betalingsskjema&id=*'
						}
					},
					{
						text: 'Oppfølging...',
						handler: function() {
							window.location = '../oppfolging/index.php?oppslag=forsiden&leieforhold=<?php echo $leieforhold;?>'
						}
					}
				]
			})
		},
		{
			scale: 'medium',
			text: 'Tilbake',
			handler: function() {
				window.location = '<?php echo $this->returi->get();?>';
			}
		}
		]
	});



	var varsling = Ext.create('Ext.panel.Panel', {
		itemId: 'varsling',
		title: 'Varsling',
		layout: 'border',
		items: [
			{
				itemId: 'hovedvarsling',
				xtype: 'panel',
				autoScroll: true,
				bodyPadding: 5,
				border: false,
				region: 'center',

				loader: {
					url: 'index.php?oppslag=leieforholdkort&id=<?php echo $leieforhold;?>&oppdrag=hentdata&data=varsling',
					renderer: 'html',
					autoLoad: true
				}
		
			}
		],
		
		buttons: [{
			scale: 'medium',
			text: 'Handlinger',
			menu: Ext.create('Ext.menu.Menu', {
				items: [
<?php foreach( $leieforhold->hent('leietakere') as $leietaker ):?>
					{
						text: 'Endre <?php echo addslashes($leietaker->hent('navn'));?> sin adresse',
						handler: function() {
							window.location = 'index.php?oppslag=personadresser_skjema&id=<?php echo $leietaker;?>'
						}
					},
<?php endforeach;?>					
					{
						text: 'Endre levering',
						handler: function() {
							window.location = 'index.php?oppslag=leieforhold_leveringsadresse&id=<?php echo $leieforhold;?>'
						}
					}
				]
			})
		},
		{
			scale: 'medium',
			text: 'Tilbake',
			handler: function() {
				window.location = '<?php echo $this->returi->get();?>';
			}
		}
		]
	});


	var leieavtale = Ext.create('Ext.panel.Panel', {
		itemId: 'leieavtale',
		title: 'Leieavtale',
		layout: 'border',
		items: [
			{
				autoScroll: true,
				bodyPadding: 5,
				border: false,
				xtype: 'panel',
				region: 'center',

				loader: {
					url: 'index.php?oppslag=leieforholdkort&id=<?php echo $leieforhold;?>&oppdrag=hentdata&data=leieavtale',
					renderer: 'html',
					autoLoad: true
				}
		
			}
		],
		
		buttons: [{
			scale: 'medium',
			text: 'Handlinger',
			menu: Ext.create('Ext.menu.Menu', {
				items: [
					{
						text: 'Skriv ut',
						handler: function() {
							window.open('index.php?oppslag=kontrakt_utskrift&oppdrag=utskrift&id=<?php echo $leieforhold->hent('kontraktnr');?>');
						}
					},
					{
						text: 'Endre avtaleteksten...',
						handler: function() {
							window.location = 'index.php?oppslag=kontrakt_tekstendring&id=<?php echo $leieforhold->hent('kontraktnr');?>';
						}
					}
				]
			})
		},
		{
			scale: 'medium',
			text: 'Tilbake',
			handler: function() {
				window.location = '<?php echo $this->returi->get();?>';
			}
		}
		]
	});


	var fellesstrøm = Ext.create('Ext.panel.Panel', {
		itemId: 'fellesstrøm',
		title: 'Fellesstrøm',
		layout: 'border',
		items: [
			{
				autoScroll: true,
				bodyPadding: 5,
				border: false,
				xtype: 'panel',
				region: 'center',

				loader: {
					url: 'index.php?oppslag=leieforholdkort&id=<?php echo $leieforhold;?>&oppdrag=hentdata&data=fellesstrøm',
					renderer: 'html',
					autoLoad: true
				}
		
			}
		],
		
		buttons: [{
			scale: 'medium',
			text: 'Handlinger',
			menu: Ext.create('Ext.menu.Menu', {
				items: []
			})
		},
		{
			scale: 'medium',
			text: 'Tilbake',
			handler: function() {
				window.location = '<?php echo $this->returi->get();?>';
			}
		}
		]
	});


	var statistikk = Ext.create('Ext.panel.Panel', {
		itemId: 'statistikk',
		title: 'Statistikk',
		layout: 'border',
		items: [
			{
				autoScroll: true,
				bodyPadding: 5,
				border: false,
				xtype: 'panel',
				region: 'center',

				loader: {
					url: 'index.php?oppslag=leieforholdkort&id=<?php echo $leieforhold;?>&oppdrag=hentdata&data=statistikk',
					renderer: 'html',
					autoLoad: true
				}
		
			}
		],
		
		buttons: [{
			scale: 'medium',
			text: 'Handlinger',
			menu: Ext.create('Ext.menu.Menu', {
				items: []
			})
		},
		{
			scale: 'medium',
			text: 'Tilbake',
			handler: function() {
				window.location = '<?php echo $this->returi->get();?>';
			}
		}
		]
	});


	var panel = Ext.create('Ext.tab.Panel', {
		title: '<?php echo addslashes("Leieforhold $leieforhold: " . $leieforhold->hent('navn') . " i " . $leieforhold->hent('leieobjekt')->hent('beskrivelse'));?>',
		autoScroll: true,
		bodyPadding: 5,
		frame: true,
		items: [
			oppsummering,
			kontooversikt,
			varsling,
			leieavtale,
			fellesstrøm,
			statistikk
		],
		
		activeTab: '<?php echo addslashes( ($tab == 'kontoforløp' or $tab == 'krav' or $tab == 'innbetalinger') ? 'kontooversikt' : $tab);?>',
		
		renderTo: 'panel',
		height: 500,
		width: 900
	});
	
	innbetalingssett.loadPage(1);
	
});
<?
}



public function design() {
?>
<div id="panel"></div>
<?
}



public function hentData($data = "") {

	$tp = $this->mysqli->table_prefix;
	$sort		= @$_GET['sort'];
	$synkende	= @$_GET['dir'] == "DESC" ? true : false;
	$start		= (int)@$_GET['start'];
	$limit		= @$_GET['limit'];
	
	$leieforhold = $this->hent('Leieforhold', (int)@$_GET['id']);
	if( !$leieforhold->hent('id') ) {
		$leieforhold = $this->hent('Leieforhold', $this->leieforhold( (int)@$_GET['id'] ) );
	}
	$leietakere		= $leieforhold->hent('leietakere');
	$leieobjekt		= $leieforhold->hent('leieobjekt');
	$regningsobjekt	= $leieforhold->hent('regningsobjekt');
	$regningsperson	= $leieforhold->hent('regningsperson');
	$oppsigelse		= $leieforhold->hent('oppsigelse');
	$efakturaavtale	= $leieforhold->hent('efakturaavtale');
	$fbo			= $leieforhold->hent('fbo');
	$innbetalinger	= $leieforhold->hent('innbetalinger');
	$kontrakter		= $leieforhold->hent('kontrakter');
	$kontrakt		= reset($kontrakter);
	

	switch ($data) {
	
	case 'balanse': {
		$ubetalteKrav = $leieforhold->hent('ubetalteKrav');
		$fremtidigeKrav = $leieforhold->hent('fremtidigeKrav');
		$sum = 0;
		$antallFremtidige = 0;
?>
<table class="dataload" style="font-size:9.5px;">
<tr>
	<th>Krav</th>
	<th>Giro</th>
	<th>Forfall</th>
	<th>Å&nbsp;betale</th>
</tr>

<?php foreach($ubetalteKrav as $krav):?>
<?php $giro = $krav->hent('giro');?>
<tr>
	<td>
		<a title="Åpne" href="index.php?oppslag=krav_kort&id=<?php echo $krav->hentId();?>"><?php echo $krav->hent('tekst');?></a>
	</td>
	<td><?php echo (($giro = $krav->hent('giro')) ? "<a title=\"Klikk her for å åpne giroen i PDF-format\" target=\"_blank\" href=\"index.php?oppslag=giro&oppdrag=lagpdf&gironr={$giro}\"><img style=\"height: 15px; width: 15px;\" src=\"../bilder/pdf.png\"></a>" : "&nbsp;" );?></td>
	<td><?php echo ($krav->hent('forfall') ? $krav->hent('forfall')->format('d.m.Y') : "&nbsp;" );?></td>
	<td class="value"><?php echo $this->kr($krav->hent('utestående'));?></td>
</tr>
<?php $sum += $krav->hent('utestående');?>
<?php endforeach;?>
<?php if($sum != 0):?>
<tr class="bold summary">
	<td colspan="3">Utestående</td>
	<td class="value"><?php echo $this->kr( $sum );?></td>
</tr>
<?php else:?>
<tr>
	<td class="value" colspan="4"><i>Intet utestående <img style="height: 15px; width: 15px;" src="../bilder/midkiffaries_Glossy_Emoticons.png"></i></td>
</tr>
<?php endif;?>
<tr>
	<td class="value" colspan="4">&nbsp;</td>
</tr>
<?php if($fremtidigeKrav):?>
<tr class="bold summary">
	<th colspan="4">Kommende krav:</th>
</tr>
<?php endif;?>
<?php foreach($fremtidigeKrav as $krav):?>
	<?php if(!$antallFremtidige or $krav->hent('utestående') != $krav->hent('beløp')):?>
		<tr>
			<td>
				<a title="Åpne" href="index.php?oppslag=krav_kort&id=<?php echo $krav->hentId();?>"><?php echo $krav->hent('tekst');?></a>
			</td>
			<td><?php echo (($giro = $krav->hent('giro')) ? "<a title=\"Klikk her for å åpne giroen i PDF-format\" target=\"_blank\" href=\"index.php?oppslag=giro&oppdrag=lagpdf&gironr={$giro}\"><img style=\"height: 15px; width: 15px;\" src=\"../bilder/pdf.png\"></a>" : "&nbsp;" );?></td>
			<td><?php echo ($krav->hent('forfall') ? $krav->hent('forfall')->format('d.m.Y') : "&nbsp;" );?></td>
			<td class="value"><?php echo $this->kr($krav->hent('utestående'));?></td>
		</tr>
		<?php $antallFremtidige++;?>
	<?php endif;?>
<?php endforeach;?>
</table>
<?php if($innbetalinger):?>
	<div style="font-size:9.5px;">Siste innbetaling: <?php echo $this->kr(end($innbetalinger)->beløp);?> den <?php echo end($innbetalinger)->innbetaling->hent('dato')->format('d.m.Y');?>.</div>
<?php else:?>
	<div style="font-size:9.5px;">Det er ikke registrert noen innbetalinger til dette leieforholdet.</div>
<?php endif;?>

<?php
		break;
	}


	case "varsling": {
	$efakturaavtale = $leieforhold->hent('efakturaavtale');
	$fbo = $leieforhold->hent('fbo');
?>
<div>
	<strong>Adresse:</strong><br />
	<?php if($leieforhold->hent('regning_til_objekt')):?>
	Regninger og henvendelser leveres
	<?php echo (string)$regningsobjekt == (string)$leieobjekt ? "direkte til leieobjektet." : "til {$regningsobjekt->hent('type')} nr. {$regningsobjekt}: {$regningsobjekt->hent('beskrivelse')}";?>
	<?php elseif($regningsperson):?>
	Regninger og henvendelser sendes i posten til <?php echo $regningsperson->hent('navn')?> sin adresse:<br /><?php echo nl2br($regningsperson->hent('postadresse'));?>
	<?php else:?>
	Regninger og henvendelser sendes i posten til:<br /><?php echo nl2br($leieforhold->hent('navn') . "\n" . $leieforhold->hent('adressefelt'))?>
	<?php endif;?>
	<br />&nbsp;<br />
</div>

<?php if($leieforhold->hent('frosset')):?>
<div style="color:blue;"><strong>Leieforholdet er frosset, og det vil ikke bli sendt regninger eller purringer fra leiebasen.</strong></div>
<?php endif;?>

<div>
	Regningsformat: <strong><?php echo ( $efakturaavtale ? ("eFaktura") : "Papirgiro" );?></strong><br />
	<?php if($efakturaavtale):?>eFaktura-avtale registrert <?php echo $efakturaavtale->registrert->format('d.m.Y k\l. H:i');?>
	<br />&nbsp;<br />
	<?php endif;?>
	<?php if($fbo):?>AvtaleGiro registrert <?php echo $fbo->registrert->format('d.m.Y k\l. H:i');?>
	<?php else:?>Ingen AvtaleGiro
	<?php endif;?>
	<br />&nbsp;<br />
</div>

<?php
		break;
	}


	case "krav": {
		$kravsett = $leieforhold->hent('krav');
		$resultat = (object)array(
			'success'	=> true,
			'data'		=> array()	
		);
		foreach( $kravsett as $krav ) {

			$html = "";
			
			$delkrav = $krav->hent('delkrav');
			if( $delkrav ) {
				$html .= "Inklusive:<br />";
			}
			foreach( $delkrav as $del ) {
				$html .= "{$del->navn}: {$this->kr($del->beløp)}<br />";
			}
			if( $delkrav ) {
				$html .= "<br />";
			}
			
			$betalinger = $krav->hentBetalinger();
			$html .= "Betalinger:<br />";
			foreach( $betalinger as $betaling ) {
				foreach( $betaling->hent('delbeløp') as $delbeløp) {
					if(strval($delbeløp->krav) === strval($krav))
						$html .= "<a href=\"index.php?oppslag=innbetalingskort&id={$betaling}\">{$betaling->hent('dato')->format('d.m.Y')}: {$this->kr($delbeløp->beløp)}</a><br />";
				}
			}
			
			$resultat->data[] = array(
				'id'			=> (int)strval($krav),
				'dato'			=> $krav->hent('dato')->format('Y-m-d'),
				'forfall'		=> ( ($forfall = $krav->hent('forfall')) ? $forfall->format('Y-m-d') : null),
				'tekst'			=> $krav->hent('tekst'),
				'giro'			=> ( ($giro = $krav->hent('giro')) ? (int)strval($giro) : null ),
				'beløp'			=> (float)$krav->hent('beløp'),
				'utestående'	=> (float)$krav->hent('utestående'),
				'type'			=> $krav->hent('type'),
				'fom'			=> ($fom = $krav->hent('fom')) ? $fom->format('Y-m-d') : null,
				'tom'			=> ($tom = $krav->hent('tom')) ? $tom->format('Y-m-d') : null,
				'termin'		=> $krav->hent('termin'),
				'html'			=> $html
			);
		}
		$resultat->data =  $this->sorterObjekter($resultat->data, 'id', $synkende);
		$resultat->data =  $this->sorterObjekter($resultat->data, $sort, $synkende);
		$resultat->totalRows = count($resultat->data);
		$resultat->data = array_slice(  $resultat->data, $start, $limit);
		return json_encode( $resultat );
		break;
	}


	case "innbetalinger": {
		$innbetalinger = $leieforhold->hent('innbetalinger');
		$resultat = (object)array(
			'success'	=> true,
			'data'		=> array()	
		);
		foreach( $innbetalinger as $innbetaling ) {
			$html = "";
			foreach( $innbetaling->innbetaling->hent('delbeløp') as $delbeløp ) {
				if( strval($delbeløp->leieforhold) === strval( $leieforhold ) ) {
					if( $delbeløp->krav ) {
						$html .= "{$this->kr($delbeløp->beløp)}: {$delbeløp->krav->hent('tekst')}<br />";
					}
					else {
						$html .= "{$this->kr($delbeløp->beløp)}: <i>Ikke utliknet</i><br />";
					}
				}
			}
		
			$resultat->data[] = array(
				'id'						=> strval($innbetaling->innbetaling),
				'dato'						=> $innbetaling->innbetaling->hent('dato')->format('Y-m-d'),
				'ref'						=> $innbetaling->innbetaling->hent('ref'),
				'beløp'						=> (float)$innbetaling->beløp,
				'betaler'					=> $innbetaling->innbetaling->hent('betaler'),
				'konto'						=> $innbetaling->innbetaling->hent('konto'),
				'transaksjonstype'			=> ($ocr = $innbetaling->innbetaling->hent('ocr')) ? (int)$ocr->transaksjonstype : null,
				'transaksjonsbeskrivelse'	=> ($ocr) ? $ocr->transaksjonsbeskrivelse : null,
				'merknad'					=> $innbetaling->innbetaling->hent('merknad'),
				'html'						=> $html
			);
		}
		$resultat->data =  $this->sorterObjekter($resultat->data, $sort, $synkende);
		$resultat->totalRows = count($resultat->data);
		$resultat->data = array_slice(  $resultat->data, $start, $limit);
		return json_encode($resultat);
		break;
	}


	case "transaksjoner": {
		$transaksjoner = $leieforhold->hent('transaksjoner');
		$resultat = (object)array(
			'success'	=> true,
			'data'		=> array(),
			'totalRows'	=> 0
		);
		$saldo = 0;
		foreach( $transaksjoner as $transaksjon ) {
		
			if( $transaksjon instanceof Krav ) {
				$saldo -= $transaksjon->hent('beløp');
				$resultat->data[] = (object)array(
					'id'						=> (int)strval($transaksjon),
					'dato'						=> $transaksjon->hent('dato')->format('Y-m-d'),
					'tekst'						=> $transaksjon->hent('tekst'),
					'beløp'						=> -$transaksjon->hent('beløp'),
					'husleie'					=> ($transaksjon->hent('type') == "Husleie") ? (float)$transaksjon->hent('beløp') : null,
					'fellesstrøm'				=> ($transaksjon->hent('type') == "Fellesstrøm") ? (float)$transaksjon->hent('beløp') : null,
					'annet'						=> ($transaksjon->hent('type') != "Husleie" and $transaksjon->hent('type') != "Fellesstrøm") ? (float)$transaksjon->hent('beløp') : null,
					'innbetalt'					=> null,
					'saldo'						=> $saldo
				);
			}
			else {
				$saldo += $transaksjon->beløp;
				$ocr = $transaksjon->innbetaling->hent('ocr');
				$betaler = $transaksjon->innbetaling->hent('betaler');
				$retning = ($transaksjon->beløp > 0) ? "innbetaling" : "utbetaling";
				
				if ($transaksjon->beløp > 0) {
					$retning = "innbetaling" . ($betaler ? " fra {$betaler}": "");
				}
				else {
					$retning = "utbetaling" . ($betaler ? " til {$betaler}" : "");
				}
				$resultat->data[] = (object)array(
					'id'						=> strval($transaksjon->innbetaling),
					'dato'						=> $transaksjon->innbetaling->hent('dato')->format('Y-m-d'),
					'tekst'						=> ucfirst(($ocr ? "{$ocr->transaksjonsbeskrivelse} " : '') . "{$retning}"),
					'beløp'						=> (float)$transaksjon->beløp,
					'husleie'					=> null,
					'fellesstrøm'				=> null,
					'annet'						=> null,
					'innbetalt'					=> (float)$transaksjon->beløp,
					'saldo'						=> $saldo
				);
			}
		
		}

		if($synkende) {
			$resultat->data = array_reverse( $resultat->data );
		}
		$resultat->totalRows = count($resultat->data);
		$resultat->data = array_slice(  $resultat->data, $start, $limit);
		return json_encode( $resultat );
		break;
	}


	case "leieavtale": {
		echo $leieforhold->gjengiAvtaletekst();
		break;
	}


	case "fellesstrøm": {
		$fordelingsnøkler = $this->mysqli->arrayData(array(
			'source'	=> "{$tp}fs_fordelingsnøkler AS fs_fordelingsnøkler\n"
						.	"LEFT JOIN {$tp}fs_fellesstrømanlegg AS fs_fellesstrømanlegg ON fs_fordelingsnøkler.anleggsnummer = fs_fellesstrømanlegg.anleggsnummer\n",
			'fields'	=> "fs_fordelingsnøkler.fordelingsmåte,\n"
						.	"fs_fordelingsnøkler.andeler,\n"
						.	"fs_fordelingsnøkler.prosentsats,\n"
						.	"fs_fordelingsnøkler.fastbeløp,\n"
						.	"fs_fordelingsnøkler.følger_leieobjekt,\n"
						.	"fs_fordelingsnøkler.anleggsnummer,\n"
						.	"fs_fellesstrømanlegg.formål\n",
			'where'		=> "(!følger_leieobjekt AND fs_fordelingsnøkler.leieforhold = '{$leieforhold}' )\n"
						.	"OR ( følger_leieobjekt AND fs_fordelingsnøkler.leieobjekt = '{$leieobjekt}' )"
		));
	?>
<h3>Deltakelse i strømdeling:</h3>
<?php foreach($fordelingsnøkler->data as $fordelingsnøkkel):?>
	<?php if($fordelingsnøkkel->fordelingsmåte == 'Andeler'):?>
		<div><?php echo $fordelingsnøkkel->andeler > 1 ? "{$fordelingsnøkkel->andeler} deler" : "{$fordelingsnøkkel->andeler} del";?> av <?php echo "<a href=\"index.php?oppslag=fs_anlegg_kort&anleggsnummer={$fordelingsnøkkel->anleggsnummer}\">anlegg nr {$fordelingsnøkkel->anleggsnummer}</a>, {$fordelingsnøkkel->formål}" . ($fordelingsnøkkel->følger_leieobjekt ? ", gjennom leieobjekt {$leieobjekt}." : ".");?></div>
	<?php elseif($fordelingsnøkkel->fordelingsmåte == 'Prosentvis'):?>
		<div><?php echo $this->prosent($fordelingsnøkkel->prosentsats);?> av <?php echo "<a href=\"index.php?oppslag=fs_anlegg_kort&anleggsnummer={$fordelingsnøkkel->anleggsnummer}\">anlegg nr {$fordelingsnøkkel->anleggsnummer}</a>, {$fordelingsnøkkel->formål}" . ($fordelingsnøkkel->følger_leieobjekt ? ", gjennom leieobjekt {$leieobjekt}." : ".");?></div>
	<?php else:?>
		<div>Manuell beregning ved hver faktura for <?php echo "<a href=\"index.php?oppslag=fs_anlegg_kort&anleggsnummer={$fordelingsnøkkel->anleggsnummer}\">anlegg nr {$fordelingsnøkkel->anleggsnummer}</a>, {$fordelingsnøkkel->formål}" . ($fordelingsnøkkel->følger_leieobjekt ? ", gjennom leieobjekt {$leieobjekt}." : ".");?></div>
	<?php endif;?>
<?php endforeach;?>
<?php if(!$fordelingsnøkler->totalRows):?>
Ingen deltakelse
<?php endif;?>
<?php
		break;
	}


	case "statistikk": {
	$kravsett = $leieforhold->hent('krav');
	$fradato = new DateTime;
	$fradato->sub(new DateInterval('P1Y'));
	$antallDager = 0;
	$antallKrav = 0;
	$løpende = false;
	
	foreach( $kravsett as $krav ) {	
		$forfall = $krav->hent('forfall');
		if( $forfall > $fradato and $forfall <= new DateTime ) {
			$betalt = $krav->hent('betalt');
			$løpende = $løpende || !$betalt; 
//			$betalt = $betalt ? $betalt : new DateTime;
			if($betalt) {
				$antallDager += $betalt->diff($forfall)->format('%r%a');
				$antallKrav++;
			}
		}
	}
	$antallDager = intval($antallDager/max(1, $antallKrav));

?>
<div>Siste leiejustering: <?php echo $leieforhold->hentSisteLeiejustering()->dato->format('d.m.Y');?><br /><br /></div>

<div><strong>Betalingspunktlighet:</strong><br />
<?php if($antallKrav):?>
Regninger som har forfalt siden <?php echo $fradato->format('d.m.Y');?> har gjennomsnittlig blitt betalt <?php echo ($antallDager ? ($antallDager < 0 ? (abs($antallDager) . " dager etter") : "{$antallDager} dager før") : "på");?> forfall.
<?php else:?>
<i>Ikke beregningsgrunnlag</i>
<?php endif;?>
</div>
<?
		break;
	}


	default: {
		$oppsigelsestid = $this->periodeformat($leieforhold->hent('oppsigelsestid'), false);
	
?>
<div>
	Leieforhold nr. <?php echo $leieforhold->hent('id');?>, påbegynt <?php echo $leieforhold->hent('fradato')->format('d.m.Y');?><br />
	Siste inngåtte leieavtale: #<?php echo $kontrakt->kontraktnr;?> gjelder fra <?php echo $kontrakt->dato->format('d.m.Y');?>.<br />

<?php if( $oppsigelse ):?>
	<strong style="color: red">Leieforholdet er oppsagt med virkning fra <?php echo $oppsigelse->fristillelsesdato->format('d.m.Y');?></strong><br />
	Oppsigelsen ble levert <?php echo $oppsigelse->oppsigelsesdato->format('d.m.Y');?>. <br />
	
	<?php if( $oppsigelse->oppsigelsestidSlutt > $oppsigelse->fristillelsesdato ):?>
		Oppsigelsestiden utløpt: <?php echo $oppsigelse->oppsigelsestidSlutt->format('d.m.Y');?><br />
	<?php endif;?>

	<?php if($leieforhold->hent('frosset')):?>
	<strong style="color: blue;">Leieavtalen er frosset. Det sendes ikke ut giroer eller purringer fra leiebasen</strong><br />
	<?php else:?>
	<br />
	<?php endif;?>

<?php else:?>
	<?php if($leieforhold->hent('tildato')):?>
		Leieforholdet er tidsbegrenset til og med
		<strong<?php if($leieforhold->hent('tildato') < new DateTime()):?> style="color:red;"<?php endif;?>>
			<?php echo $leieforhold->hent('tildato')->format('d.m.Y');?>.
		</strong><br />
	<?php endif;?>
	<?php if($leieforhold->hent('tildato') and $leieforhold->hent('tildato') < new DateTime()):?>
		<strong style="color:red;">Leieavtalen har utløpt. Leieforholdet må avsluttes eller avtalen fornyes.</strong><br />
	<?php endif;?>

	<div>
		<strong>Oppsigelsestid:</strong> <?php echo $oppsigelsestid ? $oppsigelsestid : "ingen avtalt oppsigelsestid";?>.<br />
	</div>
<?php endif;?>

<?php if($leieforhold->hent('avvent_oppfølging')):?>
	Videre oppfølging av utestående beløp e.l. bør avventes til etter <?php echo $leieforhold->hent('avvent_oppfølging')->format('d.m.Y');?><br />
<?php endif;?>

<?php if($leieforhold->hent('stopp_oppfølging')):?>
	Utestående på leieforholdet skal ikke følges opp.<br />
<?php endif;?>
	<br />
	<strong><?php echo (count($leietakere) > 1) ? "Leietakere:<br />" : "Leietaker:";?></strong>
<?foreach( $leietakere as $leietaker ):?>
	<a href="index.php?oppslag=personadresser_kort&id=<?php echo $leietaker;?>" title="Klikk på navnet for å åpne adressekortet"><?php echo $leietaker->hent('navn');?></a><br />
<?php endforeach;?>

	<strong>Leieobjekt:</strong>
	<a href="index.php?oppslag=leieobjekt_kort&id=<?php echo $leieobjekt;?>" title="Klikk for å åpne leieobjektkortet">
	<?php echo ucfirst("{$leieobjekt->hent('type')} nr. {$leieobjekt}: {$leieobjekt->hent('beskrivelse')}");?>
	</a><br />

	<?php if($leieforhold->hent('andel') != '1'):?>
		Leieforholdet disponerer <?php echo $leieforhold->hent('andel');?> av leieobjektet i bofellesskap.<br />
	<?php endif;?>
</div>
<br />

<div>
	<strong>Leie:</strong><br />
	Årlig leie: <?php echo $this->kr( $leieforhold->hent('leiebeløp') * $leieforhold->hent('ant_terminer'));?><br />
	Leia forfaller <?php echo $leieforhold->hent('ant_terminer') > 1 ? "i {$leieforhold->hent('ant_terminer')} årlige terminer" : "årlig";?>.<br />
	Terminbeløp: <?php echo $this->kr( $leieforhold->hent('leiebeløp'));?><br />
</div>
<br />

<div>
	Fast KID: <?php echo $this->genererKid($leieforhold);?><br />

	<?php if($this->valg['efaktura']):?>
		eFakturareferanse: <?php echo $leieforhold->hent('efakturareferanse');?><br />
		Status eFakturaavtale: <?php echo $efakturaavtale ? $efakturaavtale->avtalestatus: "Ingen avtale registrert";?><br />
	<?php endif;?>

	<?php if($this->valg['avtalegiro']):?>
		Status AvtaleGiro: <?php echo $fbo ? "Avtale registrert {$fbo->registrert->format('d.m.Y H:i:s')}": "Ingen avtale registrert";?><br />
	<?php endif;?>
<?
		break;
	}

	}
}



public function lagPDF( $pdf ) {
	switch ( $pdf ) {
	
	case "samlegiro":
	{
		$leieforhold = $this->hent('leieforhold', @$_GET['id']);

		$pdf = new FPDF;
		$dato = new DateTime;
		$pdf->SetAutoPageBreak(false);
		
		$leieforhold->gjengi('pdf_statusoversikt', array(
			'pdf' => $pdf,
			'statusdato' => $dato
		));

		$pdf->Output('I', "Samlegiro Leieforhold {$leieforhold}.pdf", true);
		
		break;
	}
	}
}



public function taimotSkjema() {

	echo json_encode($resultat);

}



}
?>