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
	$filter = @$_GET['filter'];
	switch($filter){
		case "ubestemte": $filter = "WHERE krav IS NULL\n";
		break;
		case "kontant": $filter = "WHERE konto ='Kontant'\n";
		break;
	}

	$this->hoveddata = "SELECT innbetalinger.*, krav.tekst, krav.utestående\n"
					. "FROM innbetalinger LEFT JOIN krav ON innbetalinger.krav = krav.id\n"
					. $filter
					. "ORDER BY dato DESC, ref DESC, innbetalingsid DESC";
}

function skript() {
	$this->returi->reset();
	$this->returi->set();
	$filter = @$_GET['filter'];
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	// oppretter datasettet
	var datasett = new Ext.data.JsonStore({
		fields: [
			{name: 'innbetalingsid', type: 'float'},
			{name: 'krav', type: 'float'},
			{name: 'tekst'},
			{name: 'utestående', type: 'float'},
			{name: 'leieforhold'},
			{name: 'leieforholdbesk'},
			{name: 'beløp', type: 'float'},
			{name: 'konto'},
			{name: 'ref'},
			{name: 'dato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'merknad'},
			{name: 'betaler'}
		],
		id: 'innbetalingsid',
		root: 'data',
		sortInfo: {field: 'dato', direction: 'DESC'},
		totalProperty: 'totalRows',
		proxy: new Ext.data.HttpProxy({
			url:'index.php?oppslag=innbetalinger&filter=<?=$filter?>&oppdrag=hentdata'
		})
	});
    datasett.load({params:{start: 0, limit: 500}});
	
	var kravkomboliste = new Ext.data.JsonStore({
		fields: [{name: 'id', type: 'float'},{name: 'visningsfelt'}],
		root: 'data',
		url: 'index.php?oppslag=innbetalinger&filter=<?=$filter?>&oppdrag=hentdata&data=krav'
				});
	
	function settparametere(redigertLinje){
		if(redigertLinje.field == 'krav') {
			kravkomboliste.baseParams = {
				leieforhold: redigertLinje.record.data.leieforhold,
				krav: redigertLinje.record.data.krav,
				innbetalingsid: redigertLinje.record.id
			};
			kravkomboliste.load();
		}
	}

	var bunnlinje = new Ext.PagingToolbar({
		pageSize: 500,
		store: datasett,
		displayInfo: true,
		displayMsg: 'Viser linje {0} - {1} av {2}',
		emptyMsg: "Ingen innbetalinger å vise",
		items:[
			'-', {
			pressed: false,
			enableToggle:true,
			text: 'Blankt ark',
			handler: function(btn){
				if(btn.pressed){
					datasett.loadData({"data":[{}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}]});
					rutenett.startEditing(0, 0);
				}
				else{
					window.location = "index.php?oppslag=innbetalinger";
				}
			}
		}<?if($filter != 'ubestemte'){?>,
		{
			handler: function() {
				window.location = "index.php?oppslag=innbetalinger&filter=ubestemte";
			},
			text: 'Vis innbetalinger som ikke er utlikna'
		}<?} if($filter != 'kontant'){?>,
		{
			handler: function() {
				window.location = "index.php?oppslag=innbetalinger&filter=kontant";
			},
			text: 'Vis kontantinnbetalinger'
		}<?} if($filter == 'kontant' or $filter == 'ubestemte'){?>,
		{
			handler: function() {
				window.location = "index.php?oppslag=innbetalinger";
			},
			text: 'Vis alle innbetalinger'
		}<?}?>
		]
	});


	function tastetrykk(bokstavtrykk){
		sm = rutenett.getSelectionModel();
		if(bokstavtrykk.getKey() == 113) {
			celle = sm.getSelectedCell();
			rad = celle[0];
			kolonne = celle[1];
			if(rad > 0) {
				feltnavn = rutenett.getColumnModel().getDataIndex(kolonne);
				forrigeLinje = datasett.getAt(rad - 1);
				denneLinje = datasett.getAt(rad);
				verdi = forrigeLinje.data[feltnavn];
				if(forrigeLinje.data[feltnavn] instanceof Date) {
					verdi = verdi.format('Y-m-d');
				}
				originalverdi = denneLinje.data[feltnavn];
				denneLinje.set(feltnavn, verdi);
				rutenett.startEditing(rad, kolonne + 1);
				objekt = new Array();
				objekt.value = verdi;
				objekt.record = denneLinje;
				objekt.field = feltnavn;
				objekt.originalValue = originalverdi;
				lagreEndringer(objekt, originalverdi);
			}
			return true;
		}
	}


	function lagreEndringer(redigertLinje, originalverdi) {
		if (redigertLinje.value instanceof Date) {
			var verdi = redigertLinje.value.format('Y-m-d H:i:s');
			if(redigertLinje.record.modified[redigertLinje.field]) {
				var opprinnelig = Ext.util.Format.date(redigertLinje.record.modified[redigertLinje.field], 'Y-m-d H:i:s');
			}
		}
		else {
			var verdi = redigertLinje.value;
			var opprinnelig = redigertLinje.originalValue;
		}
		var felt = redigertLinje.field;
		var leieforhold = redigertLinje.record.data.leieforhold;
		if((felt == 'leieforhold' || felt == 'krav') && verdi) verdi = parseInt(verdi);
		if (parseInt(leieforhold) > 0) leieforhold = parseInt(leieforhold);
		if((felt == 'leieforhold' || felt == 'krav') && verdi == opprinnelig) {
			redigertLinje.record.set(redigertLinje.field, opprinnelig);
			datasett.commitChanges();
			return true;
		}
		if(felt == 'leieforhold' && (redigertLinje.record.data.krav != '' && redigertLinje.record.data.krav != null)) {
			redigertLinje.record.set(redigertLinje.field, opprinnelig);
			datasett.commitChanges();
			Ext.MessageBox.alert('Konflikt', 'Du kan ikke flytte innbetalinga til et nytt leieforhold så lenge innbetalinga er knytta til et bestemt krav.<br />Du må blanke ut kravfeltet først.');
			return true;
		}
		Ext.Ajax.request({
				waitMsg: 'Feltet lagres...',
				url: 'index.php?oppslag=innbetalinger&filter=<?=$filter?>&oppdrag=taimotskjema&skjema=oppdatering',
				params: {
					id: redigertLinje.record.data.innbetalingsid,
					leieforhold: leieforhold,
					felt: felt,
					verdi: verdi,
					opprinnelig: opprinnelig
				},
				failure:function(response,options){
					Ext.MessageBox.alert('Whoops! Problemer...','Klarte ikke å lagre endringen.<br />Kan du ha mistet nettforbindelsen?');
				},
				success:function(response,options){
						var tilbakemelding = Ext.util.JSON.decode(response.responseText);
						if(tilbakemelding['success'] == true) {
							datoen = new Date(tilbakemelding.dato);
							redigertLinje.record.set(tilbakemelding['felt'],tilbakemelding['verdi']);
							redigertLinje.record.set('dato', datoen);
							redigertLinje.record.set('betaler',tilbakemelding.betaler);
							redigertLinje.record.set('beløp',tilbakemelding.beløp);
							redigertLinje.record.set('konto',tilbakemelding.konto);
							redigertLinje.record.set('leieforhold',tilbakemelding.leieforhold);
							redigertLinje.record.set('leieforholdbesk',tilbakemelding.leieforholdbesk);
							redigertLinje.record.set('krav',tilbakemelding.krav);
							redigertLinje.record.set('tekst',tilbakemelding.tekst);
							redigertLinje.record.set('utestående',tilbakemelding.utestående);
							redigertLinje.record.set('ref',tilbakemelding.ref);
							redigertLinje.record.set('merknad',tilbakemelding.merknad);
							redigertLinje.record.set('innbetalingsid',tilbakemelding.innbetalingsid);
 							datasett.commitChanges();
 							if(tilbakemelding.msg) {
 								Ext.MessageBox.alert('Obs!', tilbakemelding.msg);
 							}
						}
						else {
							Ext.MessageBox.alert('Advarsel!',tilbakemelding['msg']);
							
						}
				}
			}
		);
	};


	var rutenett = new Ext.grid.EditorGridPanel({
//		autoExpandColumn: 4,
		store: datasett,
		columns: [{
			header: "Dato",
			dataIndex: 'dato',
			editor: new Ext.form.DateField({
				allowBlank: false,
				format: 'd.m.Y',
				altFormats: "j.n.y|j.n.Y|j/n/y|j/n/Y|j.n|j. M y|j. M -y|j. M. Y|j. F -y|j. F y|j. F Y|j/n|dm|dmy|dmY|d|j|Y-m-d",
				listeners: {
				   render: function(c) {
					  c.getEl().on({
						keydown: tastetrykk,
						scope: c
					  });
					}
				},
				selectOnFocus: true,
				tabIndex: 0
			}),
			renderer: Ext.util.Format.dateRenderer('d.m.Y'),
			width: 90
		},{
			header: "Betaler",
			dataIndex: 'betaler',
			width: 120,
			editor: new Ext.form.ComboBox({
				allowBlank: true,
				displayField: 'betaler',
				editable: true,
				forceSelection: false,
				listeners: {
				   render: function(c) {
					  c.getEl().on({
						keydown: tastetrykk,
						scope: c
					  });
					}
				},
				maxHeight: 600,
				maxLength: 50,
				minChars: 0,
				mode: 'remote',
				name: 'betaler',
				queryDelay: 1000,
				selectOnFocus: false,
				store: new Ext.data.JsonStore({
					fields: [{name: 'betaler'}],
					root: 'data',
					url: 'index.php?oppslag=innbetalinger&filter=<?=$filter?>&oppdrag=hentdata&data=navneliste'
				}),
				typeAhead: true
			})
		},{
			editor: new Ext.form.ComboBox({
				allowBlank: false,
				displayField: 'verdi',
				editable: true,
				forceSelection: true,
				listeners: {
				   render: function(c) {
					  c.getEl().on({
						keydown: tastetrykk,
						scope: c
					  });
					}
				},
				mode: 'local',
				name: 'konto',
				selectOnFocus: true,
				store: new Ext.data.SimpleStore({
					fields: ['verdi'],
					data : [['Giro'], ['OCR-giro'], ['Kontant']]
				}),
				triggerAction: 'all',
				typeAhead: false,
				valueField: 'verdi'
			}),
			header: "Konto",
			dataIndex: 'konto',
			width: 60
		},{
			align: 'left',
			header: "Leieforhold",
			dataIndex: 'leieforhold',
			editor: new Ext.form.ComboBox({
				allowBlank: true,
				displayField: 'visningsfelt',
				editable: true,
				forceSelection: false,
				listeners: {
				   render: function(c) {
					  c.getEl().on({
						keydown: tastetrykk,
						scope: c
					  });
					}
				},
				listWidth: 500,
				maxHeight: 600,
				minChars: 0,
				mode: 'remote',
				name: 'leieforhold',
				queryDelay: 1000,
				selectOnFocus: true,
				store: new Ext.data.JsonStore({
					fields: [{name: 'leieforhold'},{name: 'visningsfelt'}],
					root: 'data',
					url: 'index.php?oppslag=innbetalinger&filter=<?=$filter?>&oppdrag=hentdata&data=leieforhold'
				}),
				typeAhead: false
			}),
			renderer: function(value, metaData, record, rowIndex, colIndex, store){
				return value ? (value + " " + record.data.leieforholdbesk) : "";
			},
			width: 120
		},{
			align: 'left',
			header: "Krav",
			dataIndex: 'krav',
			editor: new Ext.form.ComboBox({
				allowBlank: true,
				displayField: 'visningsfelt',
				listeners: {
//					beforeedit: settparametere
				},
				listWidth: 500,
				maxHeight: 600,
				minChars: 0,
				mode: 'remote',
				name: 'krav',
				selectOnFocus: true,
				store: kravkomboliste,
//				tabIndex: 1,
				typeAhead: false
			}),
			renderer: function(value, metaData, record, rowIndex, colIndex, store){
				return value ? (value + " " + record.data.tekst) : "";
			},
			width: 240
		},{
			align: 'right',
			editor: new Ext.form.NumberField({
				allowBlank: true,
				allowDecimals: true,
				allowNegative: false,
				blankText: 'Du må angi et beløp',
				decimalPrecision: 2,
				decimalSeparator: ',',
				emptyText: 'Du kan ikke la beløpfeltet være tomt.',
				listeners: {
				   render: function(c) {
					  c.getEl().on({
						keydown: tastetrykk,
						scope: c
					  });
					}
				},
				maskRe: null,
				name: 'beløp',
				selectOnFocus: true
			}),
			header: "Beløp",
			dataIndex: 'beløp',
			renderer: Ext.util.Format.noMoney,
			width: 70
		},{
			align: 'left',
			header: "Kravbeskrivelse",
			hidden: true,
			dataIndex: 'tekst',
			width: 250
		},{
			align: 'right',
			header: "Utestående",
			dataIndex: 'utestående',
			renderer: Ext.util.Format.noMoney,
			width: 70
		},{
			editor: new 	Ext.form.TextField({
				listeners: {
				   render: function(c) {
					  c.getEl().on({
						keydown: tastetrykk,
						scope: c
					  });
					}
				},
				selectOnFocus: true
			}),
			header: "Ref",
			dataIndex: 'ref',
			width: 60
		},{
			editor: new 	Ext.form.TextField({
				listeners: {
				   render: function(c) {
					  c.getEl().on({
						keydown: tastetrykk,
						scope: c
					  });
					}
				},
				selectOnFocus: true
			}),
			header: "Merknad",
			dataIndex: 'merknad',
			width: 30
		},{
			header: "ID",
			hidden: true,
			dataIndex: 'innbetalingsid',
			width: 60
		}],
		selModel: new Ext.grid.CellSelectionModel(),
		stripeRows: true,
		height: 500,
		width: 900,
		bbar: bunnlinje,
		title:'Innbetalinger'
	});

	rutenett.on('afteredit', lagreEndringer);
	rutenett.on('beforeedit', settparametere);

	rutenett.render('panel');
});

<?
}

function design() {
?>
<div id="panel"></div>
<?
}

function hentData($data = "") {
	switch ($data) {
		case "navneliste":
			if($_POST[query]) 
				$filter = "AND betaler like '{$this->POST['query']}%'";
			$navneliste = $this->arrayData("SELECT betaler FROM innbetalinger WHERE betaler <> '' $filter GROUP BY betaler");
			return json_encode($navneliste);
			break;
		case "leieforhold":
			if($_POST['query']){
				$filter =	"WHERE CONCAT(fornavn, ' ', etternavn) LIKE '%{$this->POST['query']}%'\n"
					.	"OR kontrakter.kontraktnr LIKE '%{$this->POST['query']}%'\n"
					.	"OR betaler LIKE '%{$this->POST['query']}%'\n";
			}
			$sql =	"SELECT\n"
				.	"kontrakter.leieforhold, max(kontrakter.kontraktnr) as kontraktnr, leieobjekt , gateadresse, andel, min(fradato) AS startdato ,max(tildato) AS tildato\n"
				.	"FROM\n"
				.	"((kontrakter INNER JOIN leieobjekter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr)\n"
				.	"INNER JOIN kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt)\n"
				.	"INNER JOIN personer ON kontraktpersoner.person = personer.personid\n"
				.	"LEFT JOIN innbetalinger ON kontrakter.leieforhold = innbetalinger.leieforhold\n"
				.	$filter
				.	"GROUP BY kontrakter.leieforhold, leieobjekt, gateadresse, andel\n"
				.	"ORDER BY startdato DESC, tildato DESC, etternavn, fornavn\n";
			$liste = $this->arrayData($sql);
			foreach($liste['data'] as $linje => $d) {
				$liste['data'][$linje]['visningsfelt'] = $d['leieforhold'] . ' | ' . ($this->liste($this->kontraktpersoner($d['kontraktnr']))) . ' for #' . $d['leieobjekt'] . ', ' . $d['gateadresse'] . ' | ' . $d['startdato'] . ' - ' . $d['tildato'];
			}
			return json_encode($liste);
			break;
		case "krav":
			$this->oppdaterUbetalt($_POST['innbetalingsid']);
			
			$leieforhold = $_POST['leieforhold'] ? (int)$_POST['leieforhold'] : '';
			$krav = ((int)$_POST['krav'] > 0) ? (int)$_POST['krav'] : '';
			$query = isset($_POST['query']) ? ((int)$_POST['query'] > 0 ? (int)$_POST['query'] : '') : $krav;
			$leieforhold = $_POST['leieforhold'] ? (int)$_POST['leieforhold'] : '';
			$filter1 =	"kontrakter.leieforhold = '$leieforhold'";
			$filter2 =	"krav.id = '$krav'";
			$filter3 =	"beløp - IFNULL(sum, 0) <> 0";
			$filter4 =	"LEFT(krav.id, " . strlen($query) . ") = '$query'";
			$filter =	"WHERE $filter1 AND ($filter2 OR ($filter3 AND $filter4)) ";

			$sql =	"(SELECT kontrakter.leieforhold, id, type, krav.leieobjekt, anleggsnr, termin, krav.tekst, krav.beløp, fom, kravdato, beløp - IFNULL(sum, 0) AS rest\n"
				.	"FROM (krav INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr) LEFT JOIN innbetalt ON krav.id = innbetalt.krav\n"
				. $filter . "AND (type <> 'Husleie' OR kravdato <=  NOW())\n"
				.	")\n"
				.	"UNION\n"
				.	"(SELECT kontrakter.leieforhold, id, type, krav.leieobjekt, anleggsnr, termin, krav.tekst, krav.beløp, fom, kravdato, beløp - IFNULL(sum, 0) AS rest\n"
				.	"FROM (krav INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr) LEFT JOIN innbetalt ON krav.id = innbetalt.krav\n"
				. $filter . "AND (type = 'Husleie' AND kravdato >  NOW())\n"
				.	"ORDER BY kravdato LIMIT 1\n"
				.	")\n";
				
			$sql .=	"ORDER BY IFNULL(fom, kravdato)";

			$liste = $this->arrayData($sql);

			foreach($liste['data'] as $linje => $opplysninger) {
// 				$liste['data'][$linje]['sql'] = $sql;
 				$liste['data'][$linje]['visningsfelt'] = $opplysninger['id'] . ' | ' . (($opplysninger['type'] == 'Husleie' or $opplysninger['type'] == 'Fellesstrøm') ? ($opplysninger['type'] . ' (#' . ($opplysninger['type'] == 'Husleie' ? $opplysninger['leieobjekt'] : $opplysninger['anleggsnr']) . ') ' . $opplysninger['termin']): $opplysninger['tekst']) . ' | Utestående kr ' . $opplysninger['rest'] . ' av kr ' . $opplysninger['beløp'];
			}
			return json_encode($liste);
			break;
		default:
			$this->oppdaterUbetalt();
			$innbetalinger = $this->mysqli->arrayData(array(
				'source'	=> "innbetalinger LEFT JOIN krav ON innbetalinger.krav = krav.id",
				'where'		=> "innbetalinger.konto != '0'",
				'fields'	=> "innbetalinger.*, krav.tekst, krav.utestående",
				'orderfields'	=> "dato DESC, ref DESC, innbetalingsid DESC",
				'limit'		=> "{$this->POST['start']}, {$this->POST['limit']}"
			));
			foreach($innbetalinger->data as $linje => $opplysninger) {
				$innbetalinger->data[$linje]->leieforholdbesk = $this->liste($this->kontraktpersoner($opplysninger->leieforhold));
			}
			$tomfelt[0] = (object)array('dato' => date('Y-m-d'));
			$innbetalinger->data = array_merge($innbetalinger->data, $tomfelt);
			return json_encode($innbetalinger);
	}
}

function taimotSkjema($skjema = "") {
	switch ($skjema) {
	case "oppdatering":
		$innbetalingsid = $this->mysqli->real_escape_string($_POST['id']);
		$felt = $_POST['felt'];
		$verdi = $_POST['verdi'];

		$opprinnelig = $this->arrayData("SELECT * FROM innbetalinger WHERE innbetalingsid = $innbetalingsid");
		if((int)$innbetalingsid == 0)
			$opprinnelig = array();
		else
			$opprinnelig = $opprinnelig['data'][0];
			
		if(!isset($_POST['id']) or !$felt) {
			$resultat['success'] = false;
			$resultat['msg'] = "Lagring feilet. Databasen mottok ikke beskjed om enten hvilket felt eller hvilken linje som skulle oppdateres. Prøv igjen. Om problemet gjentar seg bør du gi beskjed til programansvarlig.";
			echo json_encode($resultat);
			break;
		}
		if ((int)$innbetalingsid == 0) {
			$oppdateringssql = "INSERT INTO innbetalinger";
		}
		else {
			$oppdateringssql = "UPDATE innbetalinger";
			if($this->mysqli->arrayData(array(
				'source'	=> "innbetalinger",
				'where'		=> "innbetalingsid = '$innbetalingsid'"
			))->totalRows != 1) {
				echo json_encode(array(
					'success'	=> false,
					'msg'		=> "Linjen du prøver å lagre (med innbetalingsid $innbetalingsid) ser ut til å ha blitt slettet. Prøv å oppdatere innbetalingslisten.<br />"
				));
				break;
			}
		}
		$oppdateringssql .= " SET $felt = " . $this->strengellernull($verdi);
		
		if((int)$innbetalingsid == 0)
			$oppdateringssql .= ", registrerer = '{$this->bruker['navn']}'";
		if($felt != 'dato' and (int)$innbetalingsid == 0)
			$oppdateringssql .= ", dato = NOW()";
		if($felt != 'konto' and (int)$innbetalingsid == 0)
			$oppdateringssql .= ", konto = 'Giro'";
		
		// Forsøk på å foreslå leieforhold basert på hvem som har foretatt innbetalinga
		if($felt == 'betaler' and !@$opprinnelig['leieforhold']) {
		
			// Dersom betaleren selv er oppført i ett eneste leieforhold blir dette leieforholdet foreslått
			$sql =	"SELECT leieforhold "
				.	"FROM ((personer INNER JOIN kontraktpersoner ON personer.personid = kontraktpersoner.person) INNER JOIN kontrakter ON kontraktpersoner.kontrakt = kontrakter.kontraktnr) INNER JOIN krav ON kontrakter.kontraktnr = krav.kontraktnr "
				.	"WHERE (CONCAT(fornavn, ' ', etternavn) = '$verdi' OR (er_org = 1 AND etternavn = '$verdi')) "
				.	"GROUP BY leieforhold";
			$leieforholdforslag = $this->arrayData($sql);			
			if (count($leieforholdforslag['data']) == 1) {
				$oppdateringssql .= ", leieforhold = " . $this->strengellernull($leieforholdforslag['data'][0]['leieforhold']) ;
			}
			
			else {
				$sql =	"SELECT leieforhold "
					.	"FROM ((personer INNER JOIN kontraktpersoner ON personer.personid = kontraktpersoner.person) INNER JOIN kontrakter ON kontraktpersoner.kontrakt = kontrakter.kontraktnr) INNER JOIN krav ON kontrakter.kontraktnr = krav.kontraktnr "
					.	"WHERE (CONCAT(fornavn, ' ', etternavn) = '$verdi' OR (er_org = 1 AND etternavn = '$verdi')) "
					.	"GROUP BY leieforhold "
					.	"HAVING SUM(utestående) <> 0";
				$leieforholdforslag = $this->arrayData($sql);			
				if (count($leieforholdforslag['data']) == 1) {
					$oppdateringssql .= ", leieforhold = " . $this->strengellernull($leieforholdforslag['data'][0]['leieforhold']) ;
				}
				else {
					// Dersom betaleren alltid har betalt inn på samme leieforhold blir dette leieforholdet foreslått
					$sql =	"SELECT leieforhold "
						.	"FROM innbetalinger "
						.	"WHERE betaler = '$verdi' "
						.	"GROUP BY leieforhold";
					$leieforholdforslag = $this->arrayData($sql);
					if (count($leieforholdforslag['data']) == 1) {
					$oppdateringssql .= ", leieforhold = " . $this->strengellernull($leieforholdforslag['data'][0]['leieforhold']) ;
					}
				}
			}
		}

		if($felt == 'krav' and $verdi and !$opprinnelig['leieforhold']) { // Finner fram til riktig leieforhold om krav er oppgitt
			$sql = "SELECT leieforhold "
			.	"FROM kontrakter INNER JOIN krav ON kontrakter.kontraktnr = krav.kontraktnr "
			.	"WHERE krav.id = '$verdi'";
			$leieforholdforslag = $this->arrayData($sql);
			$oppdateringssql .= ", leieforhold = '" . $leieforholdforslag['data'][0]['leieforhold'] . "'";
		}
		
		$oppdateringssql .= ", innbetaling = concat(dato, '-', left(md5(concat(ref, '-', betaler, '-', OCRtransaksjon)), 4))";
		
		if ((int)$innbetalingsid != 0) {
			$oppdateringssql .= " WHERE innbetalingsid = $innbetalingsid";
		}
		
		if ($felt == 'krav' and $verdi) {
			$a = $this->arrayData("SELECT leieforhold "
			. "FROM krav INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr "
			. "WHERE krav.id = " . $this->strengellernull($verdi));
			if (count($a['data']) !=1) {
				$resultat['success'] = false;
				$resultat['msg'] = "Det finnes ingen krav med dette nummeret.";
				echo json_encode($resultat);
				break;
			}
			if (($_POST['leieforhold'] != '') and ($a['data'][0]['leieforhold'] != $_POST['leieforhold'])) {
				$resultat['success'] = false;
				$resultat['msg'] = "Dette kravet hører ikke til det angitte leieforholdet.";
				echo json_encode($resultat);
				break;
			}
		}
		if(!$this->mysqli->query($oppdateringssql)) {
			$resultat['success'] = false;
			$resultat['msg'] = "Klarte ikke å lagre denne verdien i databasen.<br />
			'$oppdateringssql'<br /><br />
			Feilmeldingen fra databasen lyder:<br />" . $this->mysqli->error;
			echo json_encode($resultat);
			break;
		}
		if ((int)$innbetalingsid == 0) {
			$innbetalingsid = $this->mysqli->insert_id;
		}
		$this->oppdaterUbetalt();
		$krav = $this->arrayData("SELECT krav.* FROM innbetalinger LEFT JOIN krav ON innbetalinger.krav = krav.id WHERE innbetalingsid = $innbetalingsid");
		
		if($krav['data'][0]['utestående'] < 0) {
//			$msg = 'Innbetalingen er større enn utestående på kravet det skal dekke.<br />Inbetalingen har derfor blitt korrigert til maksimalt beløp.<br /><br />Overskytende beløp; kr ' . number_format($krav['data'][0]['utestående'] * (-1), 2, ',', ' ') . ' bør overføres til andre krav i samme leieforhold.';
			$this->mysqli->query("UPDATE innbetalinger SET beløp = beløp + {$krav['data'][0]['utestående']} WHERE innbetalingsid = $innbetalingsid");
			$this->oppdaterUbetalt();
			$krav = $this->arrayData("SELECT krav.* FROM innbetalinger LEFT JOIN krav ON innbetalinger.krav = krav.id WHERE innbetalingsid = $innbetalingsid");
		}
		
		$resultat = $this->arrayData("SELECT * FROM innbetalinger WHERE innbetalingsid = $innbetalingsid");
		$resultat = $resultat['data'][0];
		$resultat['leieforholdbesk'] = $this->liste($this->kontraktpersoner($resultat['leieforhold']));
		$resultat['felt'] = $felt;
		$resultat['dato'] = date('m/d/Y', strtotime($resultat['dato']));
		$resultat['registrert'] = date('m/d/Y', strtotime($resultat['registrert']));
		$resultat['verdi'] = $resultat[$felt];
		$resultat['opprinnelig'] = $opprinnelig;
		$resultat['tekst'] = $krav['data'][0]['tekst'];
		$resultat['utestående'] = $krav['data'][0]['utestående'];
//		$resultat['msg'] = $msg;

		$resultat['success'] = true;
		echo json_encode($resultat);
		break;
	}
}

function kontrollerOverbetaling($innbetalingsid, $krav, $verdi) {
	$this->oppdaterUbetalt($innbetalingsid);
	if($verdi)
		$rest = arrayData("SELECT utestående FROM innbetalinger LEFT JOIN krav ON innbetalinger.krav = krav.id WHERE innbetalingsid = $innbetalingsid");
	else
		$rest = arrayData("SELECT utestående FROM krav WHERE id = $krav");
	If($verdi > $rest['data'][0]['utestående']) return false;
	else return true;
}

}
?>