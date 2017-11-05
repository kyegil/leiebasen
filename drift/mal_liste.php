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
	$this->hoveddata = "SELECT * FROM \n"

		.	($this->POST['søkefelt'] ? "\tWHERE 1 LIKE '%{$this->POST['søkefelt']}%' OR 1 LIKE '%{$this->POST['søkefelt']}%'" : "")
		.	($_POST['sort'] ? "ORDER BY {$this->POST['sort']} {$this->POST['dir']}\n" : "");
}

function skript() {
	if($_GET['returi'] == "default") $this->returi->reset();
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
		url:'index.php?oppdrag=hentdata&oppslag=<?=$_GET['oppslag'];?>',
		fields: [
<?
	$a = $this->arrayData($this->hoveddata);
	if(!isset($a['data'][0])) $a['data'][0] = array();
	$b = array();
	foreach($a['data'][0] as $kolonne=>$verdi) {
		$b[] = "			{name: '$kolonne'}";		
	}
	echo implode(",\n", $b);
?>

		],
		totalProperty: 'totalt',
		remoteSort: true,
		sortInfo: {
			field: '<?=$kolonne;?>', // Sett inn kolonnenavnet for standard sortering
			direction: 'DESC' // or 'ASC' (case sensitive for local sorting)
		},
		root: 'data'
    });

	var lastData = function(){
		datasett.baseParams = {};
		datasett.load({params: {start: 0, limit: 300}});
	}

	lastData();
	

    var expander = new Ext.ux.grid.RowExpander({        tpl : new Ext.Template(
            '{<?=$kolonne;?>}'
        )
    });


<?
	$b = array();
	foreach($a['data'][0] as $kolonne=>$verdi) {
		echo "	var $kolonne = {
		dataIndex: '$kolonne',
		header: '$kolonne',
		sortable: true,
		width: 50
	};

";		
	}
?>

	var søkefelt = new Ext.form.TextField({
		fieldLabel: 'Søk',
		name: 'søkefelt',
		width: 200,
		listeners: {'valid': function(){
			datasett.baseParams = {søkefelt: søkefelt.getValue()};
			datasett.load({params: {start: 0, limit: 300}});
		}}
	});

	var bunnlinje = new Ext.PagingToolbar({
		pageSize: 300,
		items: [søkefelt],
		store: datasett,
		displayInfo: true,
		displayMsg: 'Viser linje {0} - {1} av {2}',
		emptyMsg: "Venter på resultat",
	});


	var rutenett = new Ext.grid.GridPanel({
		store: datasett,
		columns: [
			expander,
<?
	$b = array();
	foreach($a['data'][0] as $kolonne=>$verdi) {
		$b[] = "			$kolonne";		
	}
	echo implode(",\n", $b);
?>

		],
		viewConfig: {
			forceFit: false
		},        
		autoExpandColumn: 2,
        plugins: expander,
		stripeRows: true,
		height: 500,
		width: 900,
		title: '',
		bbar: bunnlinje
	});

	// Rutenettet rendres in i HTML-merket '<div id="panel">':
	rutenett.render('panel');
	søkefelt.focus();

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
		default:
			$query = $this->hoveddata;
			
			if(isset($_POST['start'])) $query .= "LIMIT " . (int)$_POST['start'];
			if(isset($_POST['start']) and $_POST['limit']) $query .= ", " . (int)$_POST['limit'];
			$resultat = $this->arrayData($query);
			
			if($resultat['success']) $resultat['totalt'] = mysql_num_rows($this->mysqli->query($this->hoveddata));
			$resultat['sql'] = $query;

			return json_encode($resultat);
	}
}

}
?>