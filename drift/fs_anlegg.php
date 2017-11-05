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
	$this->returi->reset();
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
	Ext.Loader.setConfig({
		enabled:true
	});
	
<?php include_once("_menyskript.php");?>


	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	Ext.define('Fellesstrømanlegg', {
		extend: 'Ext.data.Model',
		idProperty: 'id',
		fields: [
			{name: 'anleggsnummer'},
			{name: 'målernummer'},
			{name: 'plassering'},
			{name: 'formål'},
			{name: 'html'}
		]
	});
	
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
	
	var datasett = Ext.create('Ext.data.Store', {
		model: 'Fellesstrømanlegg',
		pageSize: 200,
		remoteSort: true,
		proxy: {
			type: 'ajax',
			simpleSortMode: true,
			url: "index.php?oppslag=<?=$_GET['oppslag']?>&oppdrag=hentdata",
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
        groupField: 'navn',
		autoLoad: true
	});
	


	var rutenett = Ext.create('Ext.grid.Panel', {
		autoScroll: true,
		autoExpandColumn: 2,
		layout: 'border',

		store: datasett,
		title: 'Strømanlegg og anvendelse',
		columns: [
			{
				dataIndex: 'anleggsnummer',
				text: 'Anlegg',
				align: 'right',
				width: 90,
				sortable: true
			},
			{
				dataIndex: 'målernummer',
				text: 'Måler',
				align: 'right',
				width: 90,
				sortable: true
			},
			{
				dataIndex: 'plassering',
				text: 'Plassering',
				width: 200,
				flex: 1,
				sortable: true
			},
			{
				dataIndex: 'formål',
				text: 'Bruksformål',
				width: 420,
				flex: 1,
				sortable: true
			},
			{
				dataIndex: 'anleggsnummer',
				width: 40,
				align: 'right',
				renderer: function(value, metadata, record, rowIndex, colIndex, store) {
					return '<a href="index.php?oppslag=fs_anlegg_skjema&id=' + value + '"><img src="../bilder/rediger.png" title="Klikk for å endre" /></a>';
				}
			}
		],
		renderTo: 'panel',
		height: 500,
		width: 500,
		
		buttons: [{
			text: 'Skriv ut liste over strømanlegg',
			handler: function() {
				window.open('index.php?oppslag=fs_anleggsliste_utskrift');
			}
		}, {
			text: 'Registrer nytt fellesstrømanlegg',
			handler: function() {
				window.location = "index.php?oppslag=fs_anlegg_skjema&id=*";
			}
		}]
	});



	// Oppretter detaljpanelet
	var detaljpanel = Ext.create('Ext.panel.Panel', {
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
				autoScroll: true,
				html: 'Velg et strømanlegg i listen til venstre for å se fordelingsnøkkelen.'
			}
		],
		layout: 'border',
		renderTo: 'detaljpanel',
		title: 'Fordelingsnøkkel',
		width: 400
	})



	// Hva skjer når du klikker på ei linje i rutenettet?:
	rutenett.on('select', function( rowModel, record, index, eOpts ) {
		var detaljfelt = Ext.getCmp('detaljfelt');
		
		// Format for detaljvisningen
		var mal = new Ext.Template([
			'{html}'
		]);
		mal.overwrite( detaljfelt.body, record.data );
	});



});
<?php
}

function design() {
?>
<table style="text-align: left; width: 900px;" border="0" cellpadding="2" cellspacing="0">
	<tbody>
		<tr>
			<td style="vertical-align: top;">
				<div id="panel"></div>
			</td>
			<td style="vertical-align: top;">
				<div id="detaljpanel"></div>
			</td>
		</tr>
	</tbody>
</table>
<?php
}



function hentData($data = "") {
	$tp = $this->mysqli->table_prefix;
	$sort		= @$_GET['sort'];
	$synkende	= @$_GET['dir'] == "DESC" ? true : false;
	$start		= (int)@$_GET['start'];
	$limit		= @$_GET['limit'];

	switch ($data) {
	
	default: {
		$resultat = $this->mysqli->arrayData(array(
			'source'	=> "{$tp}fs_fellesstrømanlegg as fs_fellesstrømanlegg"
		));
		
		foreach( $resultat->data as $anlegg ) {
			$anlegg->html = "<div><strong>{$anlegg->formål}</strong></div><div><br /></div>";
			$anlegg->nevner = 0;
			
			$nøkkelelementer = $this->mysqli->arrayData(array(
				'source'	=> "{$tp}fs_fordelingsnøkler AS fs_fordelingsnøkler\n"
							.	"LEFT JOIN {$tp}leieobjekter AS leieobjekter\n"
							.	"ON fs_fordelingsnøkler.leieobjekt = leieobjekter.leieobjektnr\n"
							.	"AND fs_fordelingsnøkler.følger_leieobjekt\n"
							.	"LEFT JOIN {$tp}kontrakter AS leieforhold\n"
							.	"ON fs_fordelingsnøkler.leieforhold = leieforhold.leieforhold\n"
							.	"AND !fs_fordelingsnøkler.følger_leieobjekt\n",

				'fields'	=>	"fs_fordelingsnøkler.*",
				'distinct'	=> true,
				'where'		=> "anleggsnummer = '{$anlegg->anleggsnummer}'",
				'orderfields'	=> "field(fordelingsmåte, 'Fastbeløp', 'Prosentvis', 'Andeler'), følger_leieobjekt, leieobjekt"
			));
			
			foreach( $nøkkelelementer->data AS $nøkkelelement ) {
			
				if( $nøkkelelement->fordelingsmåte == 'Andeler' ) {
					$anlegg->nevner += $nøkkelelement->andeler;
				}

				if( $nøkkelelement->følger_leieobjekt ) {
					$nøkkelelement->leieobjekt
						= $this->hent('Leieobjekt', $nøkkelelement->leieobjekt);
				}
				else {
					$nøkkelelement->leieforhold
						= $this->hent('Leieforhold', $nøkkelelement->leieforhold);
				}
			}
			
			foreach( $nøkkelelementer->data as $nøkkelelement ) {
				$anlegg->html .= "<div><a href=\"index.php?oppslag=fs_fordelingsnokkel&id={$nøkkelelement->nøkkel}\"><img style=\"float:left; margin: 0 10px 10px 0;\" src=\"../bilder/rediger.png\" />";

				switch ($nøkkelelement->fordelingsmåte) {

				case "Fastbeløp": {
					$anlegg->html .= "<b>Et manuelt oppgitt beløp</b> ({$this->kr($nøkkelelement->fastbeløp)})";
					break;
				}
				case "Prosentvis": {
					$anlegg->html .= "<b>" . (($nøkkelelement->prosentsats == 1) ? "Alt" : "{$this->prosent($nøkkelelement->prosentsats)}") . "</b>";
					break;
				}
				case "Andeler": {
					$anlegg->html .= "<b>{$nøkkelelement->andeler} del" . ($nøkkelelement->andeler != 1 ? "er" : "" ) . "</b>";
					break;
				}
				}

				$anlegg->html .= "</a>";

				if( $nøkkelelement->følger_leieobjekt
					and $nøkkelelement->leieobjekt->hentId()
					and count($nøkkelelement->leieobjekt->hent('leietakere')) > 1
				) {
					if( $nøkkelelement->fordelingsmåte == 'Andeler' ) {
						$anlegg->html .= " betales av hvert leieforhold i ";
					}
					else {
						$anlegg->html .= " fordeles blant leieforholdene i ";
					}
				}
				else {
					$anlegg->html .= " betales av ";
				}

				if( $nøkkelelement->følger_leieobjekt ) {
					$anlegg->html .= $nøkkelelement->leieobjekt->hentId() ? "{$nøkkelelement->leieobjekt->hent('type')} {$nøkkelelement->leieobjekt->hentId()}<br />" . ($nøkkelelement->leieobjekt->hent('beboere') ? "(nå: {$nøkkelelement->leieobjekt->hent('beboere')})" : "") : "";
				}
				else {
					$anlegg->html .= $nøkkelelement->leieforhold->hentId() ? "leieforhold {$nøkkelelement->leieforhold->hentId()}<br />{$nøkkelelement->leieforhold->hent('beskrivelse')}" : "";
				}

				$anlegg->html .= ($nøkkelelement->forklaring ? "<br />{$nøkkelelement->forklaring}" : "") . "</div>";
			}
			if(!count($nøkkelelementer->data)) {
				$anlegg->html .= "<div>Ingen fordeling av dette anlegget</div>";
			}
			$anlegg->html .="<br /><div><a href=\"index.php?oppslag=fs_fordelingsnokkel&id=*&anleggsnummer={$anlegg->anleggsnummer}\"> [Legg til nytt element]</div>";
		}

		$resultat->data =  $this->sorterObjekter($resultat->data, $sort, $synkende);		
		return json_encode($resultat);
		break;
	}
	}
}



function taimotSkjema() {
	echo json_encode($resultat);
}

}
?>