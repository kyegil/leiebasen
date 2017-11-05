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
}

function skript() {
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	visinternmelding = function(rowIndex){
		Ext.MessageBox.show({
			title: '<div style="text-align: left;">' + datasett.getAt(rowIndex).get('navn') + ':</div>',
			msg: "<div style=\"text-align: left;\">" + datasett.getAt(rowIndex).get('tekst') + "</div>",
			minWidth: 500
		});
	}
	
	
	slettinternmelding = function(v){
		Ext.Ajax.request({
			waitMsg: 'Sletter...',
			url: "index.php?oppslag=forsiden&oppdrag=manipuler&data=slettinternmelding&id=" + v,
			 success : function(result){
			 	datasett.load();
			 }
		});
	}


	var hovedoppslag = new Ext.Panel({
		autoScroll: true,
		bodyStyle: 'padding: 2px',
		border: false,
		collapsible: true,
		title: 'Hovedoppslag',
		html: [
		'<p><a href=index.php?oppslag=adresser>Adressebok</a><br /></p>',
		'<p><a href=index.php?oppslag=leieobjekt_liste>Leiligheter og lokaler</a><br /></p>',
		'<p></p>',
		'<p><br /></p>',
		'<p><a href=index.php?oppslag=oversikt_ledigheter>Oversikt over ledige leieobjekter</a><br /></p>',
		'<p><a href=index.php?oppslag=oversikt_utleie_krysstabell>Utleietabell</a><br /></p>',
		'<p><a href=index.php?oppslag=framleie_liste>Framleie</a><br /></p>',
		'']
	});


	var datasett = new Ext.data.JsonStore({
		url: 'index.php?oppslag=forsiden&oppdrag=hentdata&data=internmeldinger',
		fields: [
			{name: 'id', type: 'float'},
			{name: 'tekst'},
			{name: 'intro'},
			{name: 'navn'},
			{name: 'avsender'},
			{name: 'tidspunkt', type: 'date', dateFormat: 'Y-m-d H:i:s'}
		],
		root: 'data'
    });
    datasett.load();

	var id = {
		dataIndex: 'id',
		header: 'Slett',
		renderer: function(v){
			return "<a style=\"cursor: pointer\" onClick=\"slettinternmelding(" + v + ")\"><img src=../bilder/slett.png /></a>";
		},
		sortable: false,
		width: 30
	};

	var navn = {
		dataIndex: 'navn',
		header: 'Fra',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return "<b onClick=\"visinternmelding(" + rowIndex + ")\">" + value + "</b>";
		},
		sortable: true,
		width: 80
	};

	var tidspunkt = {
		dataIndex: 'tidspunkt',
		header: 'Tidspunkt',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return "<b onClick=\"visinternmelding(" + rowIndex + ")\">" + Ext.util.Format.date(value, 'D d.m.Y H:i:s') + "</b>";
		},
		sortable: true,
		width: 130
	};


	var internmeldinger = new Ext.grid.GridPanel({
		autoExpandColumn: 1,
		autoHeight: true,
		autoScroll: true,
		border: false,
		store: datasett,
		columnLines: true,
		frame: true,
		columns: [
			tidspunkt,
			navn,
			id
		],
		stripeRows: true,
		viewConfig: {
//			forceFit: true,
			enableRowBody: true,
			showPreview: true,
			getRowClass : function(record, rowIndex, p, ds){
				if(this.showPreview){
					p.body = '' + record.data.intro + '';
					return 'x-grid3-row-expanded';
				}
			return 'x-grid3-row-collapsed';
			}
		},
//		width: 300,
		title: ''
	});
	
	internmeldinger.on(
		'rowbodyclick', function(grid, rowIndex, e){
			visinternmelding(rowIndex);
		});

	var hovedpanel = new Ext.Panel({
		layout:'border',
		defaults: {
			collapsible: true,
			split: true,
			bodyStyle: 'padding: 15px'
		},
		items: [{
			title: 'Interne notater og meldinger',
		autoScroll: true,
			region:'east',
			margins: '5 0 0 0',
			cmargins: '5 5 0 0',
			bodyStyle: 'padding: 0px',
			width: 300,
			minSize: 100,
			maxSize: 250,
			items: [internmeldinger],
		buttons: [{
			handler: function() {
				window.location = "index.php?oppslag=internmeldinger_skjema";
			},
			text: 'Skriv ny melding'
		}]
		}, {
			title: '',
			region: 'south',
			border: false,
			height: 120,
			collapsed: <?=$this->advarsler ? "false" : "true";?>,
			minSize: 75,
			maxSize: 250,
			cmargins: '5 0 0 0',
			items: []
		}, {
			title: '',
			collapsible: false,
			region:'center',
			margins: '5 0 0 0',
			layout:'column',
			items: [{
				bodyStyle: 'padding: 3px',
				border: false,
				title: '',
				columnWidth: .5,
				items: [hovedoppslag]
			},{
				bodyStyle: 'padding: 3px',
				border: false,
				title: '',
				columnWidth: .5,
				items: []
			}]
		}],
		title: '',
		height: 500,
		width: 900
	});

    hovedpanel.render('panel');

});

<?
}

function design() {
?>
<div id="panel"></div>
<?
}

function manipuler($data){
	switch ($data){
		case "slettinternmelding":
			$sql =	"DELETE FROM internmeldinger WHERE id = '{$this->GET['id']}'";
			if($this->mysqli->query($sql)){
				$resultat['msg'] = "Meldingen har blitt slettet";
				$resultat['success'] = true;
			}
			else{
				$resultat['msg'] = "Klarte ikke slette. Meldingen fra database lyder:<br />" . $this->mysqli->error;
				$resultat['success'] = false;
			}
			echo json_encode($resultat);
			break;
	}
}


function hentData($data = "") {
	switch ($data) {
		case "internmeldinger":
			$sql =	"SELECT * FROM internmeldinger WHERE flyko ORDER BY id DESC";
			$resultat = $this->arrayData($sql);
			foreach($resultat['data'] as $linje=>$verdi){
				$resultat['data'][$linje]['navn'] = $this->navn($verdi['avsender']);
				$resultat['data'][$linje]['intro'] = substr($verdi['tekst'], 0, 150);
				if(!$a = max(strripos($resultat['data'][$linje]['intro'], "."), strripos($resultat['data'][$linje]['intro'], "?"), strripos($resultat['data'][$linje]['intro'], "!")))
					$a = strripos($resultat['data'][$linje]['intro'], " ");
				$resultat['data'][$linje]['intro'] = substr($resultat['data'][$linje]['intro'], 0, $a + 1);
			}
			return json_encode($resultat);
			break;
	}
}

}
?>