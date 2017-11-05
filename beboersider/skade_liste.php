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
	if(isset($_GET['id'])) {
		$bygning = $this->arrayData("SELECT bygning FROM leieobjekter WHERE leieobjektnr = " . (int)$_GET['id']);
		$bygning = $bygning['data'][0]['bygning'];
	}
	$this->hoveddata = "SELECT skader.id, skade, leieobjektnr, registrerer, registrert, beskrivelse, utført, sluttregistrerer, sluttrapport, bygninger.navn\n"
		.	"FROM skader LEFT JOIN skadekategorier ON skader.id = skadekategorier.skadeid\n"
		.	"LEFT JOIN bygninger ON skader.bygning = bygninger.id\n"
		.	"WHERE 1\n"
		.	(isset($_POST['kategori']) ? ("AND kategori = '{$this->POST['kategori']}'\n") : "")
		.	(isset($_GET['id']) ? ("AND (leieobjektnr = '{$this->GET['id']}' OR (!leieobjektnr AND bygning = '{$bygning}'))\n") : "")
		.	"GROUP BY skader.id\n"
		.	"ORDER BY skader.id DESC\n";
}

function skript() {
	if( isset( $_GET['returi'] ) && $_GET['returi'] == "default") $this->returi->reset();
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
		url:'index.php?oppdrag=hentdata&oppslag=skade_liste<?=isset($_GET['id']) ? "&id={$_GET['id']}" : ""?>',
		fields: [
			{name: 'id', type: 'float'},
			{name: 'leieobjektnr', type: 'float'},
			{name: 'leieobjektbesk'},
			{name: 'navn'},
			{name: 'registrerer'},
			{name: 'registrert', type: 'date', dateFormat: 'Y-m-d H:i:s'},
			{name: 'skade'},
			{name: 'beskrivelse'},
			{name: 'utført', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'sluttregistrerer'},
			{name: 'sluttrapport'},
			{name: 'html'}
		],
		root: 'data'
    });
    datasett.load();

	var kategorivelger = new Ext.form.ComboBox({		name: 'kategori',
		mode: 'remote',
		store: new Ext.data.JsonStore({
			fields: [{name: 'kategori'}],
			root: 'data',
			url: 'index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=hentdata&data=kategori'
		}),
		fieldLabel: 'Kategori',
		hideLabel: false,
		minChars: 0,
		queryDelay: 1000,
		allowBlank: true,
		displayField: 'kategori',
		editable: true,
		forceSelection: false,
		selectOnFocus: true,
		listWidth: 500,
		maxHeight: 600,
		typeAhead: false,
		listeners: {
			'change': function(){
				datasett.baseParams = {kategori: kategorivelger.getValue()};
				datasett.load();
			},
			'select': function(){
				datasett.baseParams = {kategori: kategorivelger.getValue()};
				datasett.load();
			}
		},
		width: 450
	});


	var id = {
		dataIndex: 'id',
		header: 'id',
		hidden: true,
		sortable: true,
		width: 50
	};

	var leieobjektnr = {
		dataIndex: 'leieobjektnr',
		header: 'Leil',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			if(value) {
				return record.data.leieobjektbesk;
			}
			else {
				return record.data.navn;
			}
		},
		sortable: true,
		width: 200
	};

	var registrerer = {
		dataIndex: 'registrerer',
		header: 'Registrert av',
		sortable: true,
		width: 100
	};

	var registrert = {
		dataIndex: 'registrert',
		header: 'Registrert',
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		sortable: true,
		width: 80
	};

	var beskrivelse = {
		dataIndex: 'beskrivelse',
		header: 'beskrivelse',
		sortable: true,
		width: 50
	};

	var utført = {
		dataIndex: 'utført',
		header: 'Utbedret',
		renderer: Ext.util.Format.hake,
		sortable: true,
		width: 40
	};

	var sluttregistrerer = {
		dataIndex: 'sluttregistrerer',
		header: 'sluttregistrerer',
		sortable: true,
		width: 50
	};

	var sluttrapport = {
		dataIndex: 'sluttrapport',
		header: 'sluttrapport',
		sortable: true,
		width: 50
	};


	var rutenett = new Ext.grid.GridPanel({
		title: 'Registrerte skader<?=isset($_GET['id']) ? (" i " . $this->leieobjekt((int)$_GET['id'], true)) : ""?>',
		autoScroll: true,
		store: datasett,
		columns: [
			id,
			registrert,
			leieobjektnr,
			registrerer,
			utført
		],
		autoExpandColumn: 2,
		viewConfig: {
			enableRowBody: true,
			showPreview: true,
			getRowClass : function(record, rowIndex, p, ds){
				if(this.showPreview){
					p.body = '' + record.data.skade + '';
					return 'x-grid3-row-expanded';
				}
			return 'x-grid3-row-collapsed';
			}
		},
		stripeRows: true,
		tbar: [kategorivelger],
		buttons: [],
		height: 500,
		width: 500
	});

	// Rutenettet rendres in i HTML-merket '<div id="panel">':
	rutenett.render('panel');

	// Oppretter detaljpanelet
	var detaljpanel = new Ext.Panel({
		title: 'Detaljer',
		autoScroll: true,
		frame: true,
		height: 500,
		width: 400,
		items: [
			{
				id: 'detaljfelt',
				region: 'center',
				bodyStyle: {
					background: '#ffffff',
					padding: '7px'
				},
				html: 'Velg en skade i listen til venstre for å se flere detaljer.'
			}
		],
		buttons: [],
		layout: 'border',
		renderTo: 'detaljpanel'
	})

	var endreknapp = detaljpanel.addButton({
		text: 'Endre skademeldingen',
		disabled: true,
		handler: function(){
			window.location = "index.php?oppslag=skade_skjema<?=isset($_GET['id']) ? "&leieobjektnr=" . (int)$_GET['id'] : ""?>&id=" + rutenett.getSelectionModel().getSelected().data.id;
		}
	});

	var utbedreknapp = detaljpanel.addButton({
		text: 'Meld skaden som utbedret',
		disabled: true,
		handler: function(){
			window.location = "index.php?oppslag=skade_utbedring_skjema<?=isset($_GET['id']) ? "&leieobjektnr=" . (int)$_GET['id'] : ""?>&id=" + rutenett.getSelectionModel().getSelected().data.id;
		}
	});

<?
	if(isset($_GET['id']) and (int)$_GET['id']){
?>
	var visalleknapp = rutenett.addButton({
		text: 'Vis alle registrerte skader',
		disabled: false,
		handler: function(){
			window.location = "index.php?oppslag=skade_liste";
		}
	});


<?
	}
?>
	rutenett.addButton({
		text: 'Tilbake',
		handler: function(){
			window.location = '<?=$this->returi->get();?>';
		}
	});

	var nyskadeknapp = rutenett.addButton({
		text: 'Meld ny skade',
		disabled: false,
		handler: function(){
			window.location = "index.php?oppslag=skade_skjema<?=isset($_GET['id']) ? "&leieobjektnr=" . (int)$_GET['id'] : ""?>&id=*";
		}
	});


	// Hva skjer når du klikker på ei linje i rutenettet?:
	rutenett.getSelectionModel().on('rowselect', function(sm, rowIdx, r) {
		var detaljfelt = Ext.getCmp('detaljfelt');
		
		// Format for detaljvisningen
		var detaljer = new Ext.Template([
			'{html}'
		]);
		detaljer.overwrite(detaljfelt.body, r.data);
		endreknapp.enable();
		if(!r.data.utført) {
			utbedreknapp.enable();
		}
		else{
			utbedreknapp.disable();
		}
	});

});
<?
}

function design() {
?>
<table style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="0">
<tbody>
<tr>
<td style="vertical-align: top; width: 750px;">
<div id="panel"></div></td>
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
		case "kategori":
			$sql =	"SELECT kategori\n"
				.	"FROM skadekategorier\n"
				.	"GROUP BY kategori";
			$resultat = $this->arrayData($sql);
			return json_encode($resultat);
			break;
		default:
			$resultat = $this->arrayData($this->hoveddata);
			foreach($resultat['data'] as $linje=>$verdi){
				$kategorirad = array();
				$sql =	"SELECT kategori\n"
					.	"FROM skadekategorier\n"
					.	"WHERE skadeid = {$verdi['id']}\n"
					.	"GROUP BY kategori\n";
				$kategorier = $this->arrayData($sql);
				foreach($kategorier['data'] as $kategori){
					$kategorirad[] = $kategori['kategori'];
				}
				$resultat['data'][$linje]['leieobjektbesk'] = $this->leieobjekt($verdi['leieobjektnr'], true, false);
				$html = ($verdi['leieobjektnr'] ? $this->leieobjekt($verdi['leieobjektnr'], true) : $verdi['navn']) . "<br />";
				if($verdi['utført']){
					$html .= "<h1>Skaden er utbedret</h1>";
					$html .= "{$verdi['skade']}<br />";
					$html .= "Meldt utbedret av {$verdi['sluttregistrerer']} " . date('d.m.Y', strtotime($verdi['utført'])) . ":<br />";
					$html .= "{$verdi['sluttrapport']}<br />";
					$html .= "Opprinnelig beskrivelse:<br />";
					$html .= "{$verdi['beskrivelse']}<br />";
				}
				else{
					$html .= "<h1>{$verdi['skade']}</h1>";
					$html .= "Registrert av {$verdi['registrerer']} " . date('d.m.Y', strtotime($verdi['registrert'])) . ":<br />";
					if($kategorirad){
						$html .= count($kategorirad) > 1 ? "Kategorier: <b>" : "Kategori: <b>";
						$html .= $this->liste($kategorirad) . "</b><br /><br />";
					}
					$html .= "{$verdi['beskrivelse']}";
				}
				
				$resultat['data'][$linje]['html'] = $html;
			}
			return json_encode($resultat);
	}
}

}
?>