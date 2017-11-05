<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/
If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class Oppsett extends Leiebase {

function __construct() {
	parent::__construct();
	$this->ext_bibliotek = 'ext-3.4.0';
}

function skript() {
	$this->returi->reset();
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>


	var statistikk1 = new Ext.Panel({
		autoScroll: true,
		autoLoad: 'index.php?oppslag=forsiden&oppdrag=hentdata&data=statistikk1',
		bodyStyle: 'padding: 2px',
		border: false,
		collapsible: true,
		collapsed: false,
		title: 'Oppsummert'
	});


	var statistikk2 = new Ext.Panel({
		autoScroll: true,
		autoLoad: 'index.php?oppslag=forsiden&oppdrag=hentdata&data=statistikk2',
		bodyStyle: 'padding: 2px',
		border: false,
		collapsible: true,
		collapsed: false,
		title: 'Oppgjør'
	});


	visinternmelding = function(rowIndex){
		var melding = new Ext.Window({
			title: '<span style="text-align: left;">' + datasett.getAt(rowIndex).get('navn') + ':</span>',
			html: "<span style=\"text-align: left;\">" + datasett.getAt(rowIndex).get('tekst') + "</span>",
			width: 600,
			height: 400,
			autoScroll: true
		});
		melding.show();
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


	var datasett = new Ext.data.JsonStore({
		model: 'Internmelding',
		url: 'index.php?oppslag=forsiden&oppdrag=hentdata&data=internmeldinger',
		autoLoad: true,
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
			return "<a style=\"cursor: pointer;\" onClick=\"slettinternmelding(" + v + ")\"><img alt=\"Slett\" src=\"../bilder/slett.png\" /></a>";
		},
		sortable: false,
		width: 30
	};

	var navn = {
		dataIndex: 'navn',
		header: 'Fra',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return "<b style=\"cursor: pointer;\" onClick=\"visinternmelding(" + rowIndex + ")\">" + value + "</b>";
		},
		sortable: true,
		width: 80
	};

	var tidspunkt = {
		dataIndex: 'tidspunkt',
		header: 'Tidspunkt',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return "<b style=\"cursor: pointer;\" onClick=\"visinternmelding(" + rowIndex + ")\">" + Ext.util.Format.date(value, 'D d.m.Y H:i:s') + "</b>";
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
			enableRowBody: true,
			showPreview: true,
			getRowClass : function(record, rowIndex, p, ds){
				if(this.showPreview){
					p.body = '' + record.data.intro + "<a style=\"cursor: pointer;\" onClick=\"visinternmelding(" + rowIndex + ")\"> [les hele...]</a>";
					return 'x-grid3-row-expanded';
				}
			return 'x-grid3-row-collapsed';
			}
		},
		title: ''
	});
	
	internmeldinger.on(
		'rowbodyclick', function(grid, rowIndex, e){
			visinternmelding(rowIndex);
	}
	);

<?
	if($this->advarsler){
		$advarselhtml = "<table border=\"0\"><tbody><tr>";
		foreach($this->advarsler[0] as $varsel){
			$advarselhtml .= "<td style=\"height: 50px; cursor: pointer;\" onClick=\"Ext.MessageBox.alert(\'" . addslashes($varsel['oppsummering']) . "\', \'" .  addslashes($varsel['tekst']) . "\')\"><img  src=\"../bilder/advarsel_rd.png\" alt=\"!\" height=\"50px\" /></td><td style=\"height: 50px; cursor: pointer;\" onClick=\"Ext.MessageBox.alert(\'" . addslashes($varsel['oppsummering']) . "\', \'" .  addslashes($varsel['tekst']) . "\')\"><p style=\"font-size: 0.8em;\">" . addslashes($varsel['oppsummering']) . "</p></td>";			
		}
		foreach($this->advarsler[1] as $varsel){
			$advarselhtml .= "<td style=\"height: 50px; cursor: pointer;\" onClick=\"Ext.MessageBox.alert(\'" . addslashes($varsel['oppsummering']) . "\', \'" . addslashes($varsel['tekst']) . "\')\"><img  src=\"../bilder/advarsel_rd.png\" alt=\"!\" height=\"50px\" /></td><td style=\"height: 50px; cursor: pointer;\" onClick=\"Ext.MessageBox.alert(\'" . addslashes($varsel['oppsummering']) . "\', \'" . addslashes($varsel['tekst']) . "\')\"><p style=\'font-size: 0.8em;\'>" . addslashes($varsel['oppsummering']) . "</p></td>";
		}
		foreach($this->advarsler[2] as $varsel){
			$advarselhtml .= "<td style=\"height: 50px; cursor: pointer;\" onClick=\"Ext.MessageBox.alert(\'" . addslashes($varsel['oppsummering']) . "\', \'" . addslashes($varsel['tekst']) . "\')\"><img  src=\"../bilder/tegnestift.png\" alt=\"!\" height=\"25px\" /></td><td style=\"height: 50px; cursor: pointer;\" onClick=\"Ext.MessageBox.alert(\'" . addslashes($varsel['oppsummering']) . "\', \'" . addslashes($varsel['tekst']) . "\')\"><p style=\"font-size: 0.8em;\">" . addslashes($varsel['oppsummering']) . "</p></td>";			
		}
		$advarselhtml .= "</tr></tbody></table>";
	}
?>


	var hovedpanel = new Ext.Panel({
		layout:'border',
		defaults: {
			collapsible: true,
			split: true,
			bodyStyle: 'padding: 15px'
		},
		items: [{
			title: 'Meldinger for drift',
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
			autoLoad: 'index.php?oppslag=forsiden&oppdrag=hentdata&data=advarsler',
			region: 'south',
			border: false,
			height: 120,
			collapsed: false,
			minSize: 75,
			maxSize: 250,
			cmargins: '5 0 0 0'
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
				items: [statistikk1]
			},{
				bodyStyle: 'padding: 3px',
				border: false,
				title: '',
				columnWidth: .5,
				items: [statistikk2]
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
	switch ($data) {
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
	
	case "internmeldinger": {

		$resultat = $this->mysqli->arrayData(array(
			'source'		=> "internmeldinger",
			'where'			=> array('drift' => true),
			'orderfields'	=> "id DESC"
		));
		foreach($resultat->data as $melding) {
			$melding->navn = $this->navn( $melding->avsender );
			
			$melding->intro = htmlspecialchars_decode( strip_tags($melding->tekst));
			$melding->intro = mb_substr( $melding->intro, 0, 150 );
			if( !$a = max(
				mb_strripos($melding->intro, "."),
				mb_strripos($melding->intro, "?"),
				mb_strripos($melding->intro, "!")
			) ) {
				$a = mb_strripos($melding->intro, " ");
			}
			$melding->intro = mb_substr($melding->intro, 0, $a + 1);
		}

		return json_encode($resultat);
		break;
	}

	case "advarsler": {
		$this->kontrollerBetalingsutlikninger();
		$this->kontrollerKredittbalanse();
		$this->kontroller_cron();
		$this->kontroller_ocr();
		$this->kontrollerOcrInnbetalinger();
		$this->kontroller_utlop();
		$this->kontroller_innbetalinger();
		$this->kontroller_oppfølgingspåminnelser();
		$this->kontroller_giroutskrifter();
		$this->kontroller_adresseoppdateringer();

		?>
		<table style="border: none;">
		<? foreach( $this->advarsler[0] as $varsel ):?>
			<td style="height: 50px; cursor: pointer;" onClick="Ext.MessageBox.alert('<?= $varsel['oppsummering'] ?>', '<?= $varsel['tekst'] ?>')">
				<img  src="../bilder/advarsel_rd.png" alt="!" height="50px" />
			</td>
			<td style="height: 50px; cursor: pointer;" onClick="Ext.MessageBox.alert('<?= $varsel['oppsummering'] ?>', '<?= $varsel['tekst'] ?>')">
				<p style="font-size: 0.8em;"><?= $varsel['oppsummering'] ?></p>
			</td>			
		<?
		endforeach;
		?>

		<? foreach( $this->advarsler[1] as $varsel ):?>
			<td style="height: 50px; cursor: pointer;" onClick="Ext.MessageBox.alert('<?= $varsel['oppsummering'] ?>', '<?= $varsel['tekst'] ?>')">
				<img  src="../bilder/advarsel_rd.png" alt="!" height="50px" />
			</td>
			<td style="height: 50px; cursor: pointer;" onClick="Ext.MessageBox.alert('<?= $varsel['oppsummering'] ?>', '<?= $varsel['tekst'] ?>')">
				<p style="font-size: 0.8em;"><?= $varsel['oppsummering'] ?></p>
			</td>			
		<?
		endforeach;
		?>

		<? foreach( $this->advarsler[2] as $varsel ):?>
			<td style="height: 50px; cursor: pointer;" onClick="Ext.MessageBox.alert('<?= $varsel['oppsummering'] ?>', '<?= $varsel['tekst'] ?>')">
				<img  src="../bilder/tegnestift.png" alt="!" height="25px" />
			</td>
			<td style="height: 50px; cursor: pointer;" onClick="Ext.MessageBox.alert('<?= $varsel['oppsummering'] ?>', '<?= $varsel['tekst'] ?>')">
				<p style="font-size: 0.8em;"><?= $varsel['oppsummering'] ?></p>
			</td>			
		<?
		endforeach;
		?>

		</table>
		<?
		break;
	}

	case "statistikk1": ?>
		<b>Innbetalinger:</b>
		<table>
	<?
		foreach( $this->mysqli->arrayData(array(
			'fields'		=> array(
				'beløp'			=> "SUM(beløp)",
				'måned'			=> "DATE_FORMAT(dato, '%Y-%m-01')"
			),
			'source'		=> "innbetalinger",
			'where'			=> array(
				'konto <>'		=> '0'
			),
			'groupfields'	=> "måned",
			'orderfields'	=> "måned DESC",
			'limit'			=> 4
		))->data as $betalt ):
	?>
			<tr>
				<td width="100px">
					<a href="index.php?oppslag=oversikt_kontobevegelser&fra=<?= $betalt->måned ?>&til=<?= date('Y-m-t', strtotime($betalt->måned))?>"><?= strftime("%B %Y", strtotime($betalt->måned . "-01")) ?></a>:
				</td>
				<td style="text-align: right;">kr <?= str_replace(" ", "&nbsp;", number_format($betalt->beløp, 2, ",", " ")) ?></td>
			</tr>
		<?
		endforeach;
	?>
		</table>
		<a href="index.php?oppslag=oversikt_innbetalinger">se mer ...</a><br />
		
	<?			

		$resultat = $this->mysqli->arrayData(array(
			'source'	=> "OCRdetaljer",
			'fields'	=> array(
				'oppgjørsdato'	=> "MAX(oppgjørsdato)"
			)
		))->data;
		if( isset( $resultat[0] ) ):
	?>
			Siste <a href="index.php?oppslag=ocr_liste">OCR-fil</a>: <?= date('d.m.Y', strtotime($resultat[0]->oppgjørsdato)) ?><br />
	<?
		endif;
		
		$resultat = $this->mysqli->arrayData(array(
			'source'		=> "innbetalinger",
			'fields'		=> array("registrerer", "registrert"),
			'where'			=> "konto <> '0' AND !OCRtransaksjon",
			'where'			=> array(
				'konto <>'			=> '0',
				'OCRtransaksjon'	=> false
			),
			'orderfields'	=> "registrert DESC",
			'limit'			=> 1
		))->data;
		if( isset( $resultat[0] ) ):
	?>
			Siste manuelle <a href="index.php?oppslag=innbetalinger">registrering av betaling</a>: <?= date('d.m.Y', strtotime($resultat[0]->registrert)) ?><br />
	<?
		endif;
	?>
		<br />
		Annet:</b><br />
	<?
		
		$resultat = $this->mysqli->arrayData(array(
			'source'		=> "giroer",
			'fields'		=> array(
				'utskriftsdato'	=> "MAX(utskriftsdato)"
			)
		))->data;
		if( isset( $resultat[0] ) ):
	?>
			Siste giroutskrift: <?= date('d.m.Y', strtotime($resultat[0]->utskriftsdato)) ?><br />
	<?
		endif;
		
		$resultat = $this->mysqli->arrayData(array(
			'source'		=> "purringer",
			'fields'		=> array(
				'purredato'	=> "MAX(purredato)"
			)
		))->data;
		if( isset( $resultat[0] ) ):
	?>
			Siste purring: <?= date('d.m.Y', strtotime($resultat[0]->purredato)) ?><br />
	<?
		endif;
		
		$resultat = $this->mysqli->arrayData(array(
			'source'		=> "fs_originalfakturaer",
			'fields'		=> array("termin", "fradato", "tildato", "fordelt"),
			'orderfields'	=> "tildato DESC, fordelt DESC",
			'limit'			=> 1
		))->data;
		if( isset( $resultat[0] ) ):
	?>
			Siste <?= ($resultat[0]->fordelt ? "fordelte" : "registrerte") ?> <a href="index.php?oppslag=fs_fakturaer">fellesstrøm</a>:
			<span title="Fellesstrøm for perioden : <?= date('d.m.Y', strtotime($resultat[0]->fradato)) ?> - <?= date('d.m.Y', strtotime($resultat[0]->tildato)) ?>">termin <?= $resultat[0]->termin ?></span>
			<?if(!$resultat[0]->fordelt):?>
				<span title="Den foreslåtte fordelingen må bekreftes før den vil bli krevd inn."> (Fordelingen er ikke bekreftet.)</span>
			<?endif;?>
			<br />
			<br />
	<?
		endif;
		
		$resultat = $this->mysqli->arrayData(array(
			'source'		=> "skader INNER JOIN bygninger ON skader.bygning=bygninger.id",
			'fields'		=> array("skader.skade", "skader.registrert", "bygninger.navn"),
			'where'			=> array(
				'utført IS'	=> null
			),
			'orderfields'	=> "skader.registrert DESC",
			'limit'			=> 1
		))->data;
		if( isset( $resultat[0] ) ):
	?>
			Siste <a href="index.php?oppslag=skade_liste">skademelding</a>:<br />
			<?= $resultat[0]->skade ?> i <?= $resultat[0]->navn ?><br />meldt <?= date( "d.m.Y", strtotime( $resultat[0]->registrert ) ) ?><br />
	<?
		endif;
		
		break;
		

	case "statistikk2":
//			echo "<b>Oppgjør:</b><br />";
		// oppgjør består av feltene kravid, utestående, oppgjort, forfall, oppgjørsdato, oppfyllelse (=oppgjør antall dager før forfall)
		$oppgjør = "(SELECT id AS kravid, utestående, !utestående AS oppgjort, IFNULL(krav.forfall, krav.kravdato) AS forfall, IF(!utestående, MAX(innbetalinger.dato), NOW()) AS oppgjørsdato, DATEDIFF(IFNULL(krav.forfall, krav.kravdato), IF(!utestående, MAX(innbetalinger.dato), NOW())) AS oppfyllelse\n"
			.	"FROM krav LEFT JOIN innbetalinger ON krav.id = innbetalinger.krav\n"
			.	"GROUP BY krav.id)\n"
			.	"AS oppgjør";

		$sql =	"SELECT COUNT(oppgjør.kravid) AS totalt, SUM(oppgjør.oppgjort) AS oppgjort\n"
			.	"FROM krav INNER JOIN $oppgjør ON krav.id = oppgjør.kravid\n"
			.	"WHERE krav.type = 'Husleie' AND oppgjør.forfall <=NOW() AND oppgjør.forfall > DATE_SUB(NOW(), INTERVAL 1 MONTH)";
		$resultat = $this->arrayData($sql);

		echo "<span title=\"{$resultat['data'][0]['oppgjort']} av totalt {$resultat['data'][0]['totalt']}\">" . number_format($resultat['data'][0]['oppgjort']/($ant_leier = $resultat['data'][0]['totalt'])*100, 1, ",", " ") . "%</span> av leier forfalt siste måned er betalt.<br />";
		
		$sql =	"SELECT COUNT(oppgjør.kravid) AS oppgjort\n"
			.	"FROM krav INNER JOIN $oppgjør ON krav.id = oppgjør.kravid\n"
			.	"WHERE krav.type = 'Husleie' AND oppgjør.forfall <=NOW() AND oppgjør.forfall > DATE_SUB(NOW(), INTERVAL 1 MONTH) AND oppgjørsdato <= oppgjør.forfall";
		$resultat = $this->arrayData($sql);

		echo "<span title=\"{$resultat['data'][0]['oppgjort']} av totalt $ant_leier\">" . number_format($resultat['data'][0]['oppgjort']/$ant_leier*100, 1, ",", " ") . "%</span> ble betalt innen forfall.<br />";
		
		echo "<br />";

		$sql =	"SELECT SUM(utestående) AS utestående, SUM(beløp) AS totalt\n"
			.	"FROM krav\n"
			.	"WHERE IFNULL(krav.forfall, krav.kravdato) <=NOW() AND IFNULL(krav.forfall, krav.kravdato) > DATE_SUB(NOW(), INTERVAL 1 YEAR)";
		$resultat = $this->arrayData($sql);

		echo "Utestående siste 12 mnd: <span title=\"Av totalt kr. " . number_format($resultat['data'][0]['totalt'], 2, ",", " ") . " (Dvs. " . number_format($resultat['data'][0]['utestående']/$resultat['data'][0]['totalt']*100, 1, ",", " ") . "%)\">kr. " . number_format($resultat['data'][0]['utestående'], 2, ",", " ") . "</span><br />";
		
		$sql =	"SELECT SUM(utestående) AS utestående, SUM(beløp) AS totalt\n"
			.	"FROM krav\n"
			.	"WHERE IFNULL(krav.forfall, krav.kravdato) <= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND IFNULL(krav.forfall, krav.kravdato) > DATE_SUB(NOW(), INTERVAL 1 YEAR)";
		$resultat = $this->arrayData($sql);

		echo "- Minus siste 2 måneder: <span title=\"Av totalt kr. " . number_format($resultat['data'][0]['totalt'], 2, ",", " ") . " (Dvs. " . number_format($resultat['data'][0]['utestående']/$resultat['data'][0]['totalt']*100, 1, ",", " ") . "%)\">kr. " . number_format($resultat['data'][0]['utestående'], 2, ",", " ") . "</span><br />";
		
		$sql =	"SELECT AVG(oppfyllelse) AS oppfyllelse\n"
			.	"FROM $oppgjør\n"
			.	"WHERE oppgjørsdato <= NOW() AND oppgjørsdato > DATE_SUB(NOW(), INTERVAL 3 MONTH)";
		$resultat = $this->arrayData($sql);

//			echo "Oppgjør siste måned skjedde gjennomsnittlig " . number_format($resultat['data'][0]['oppfyllelse'], 0, ",", " ") . " dager " . "før forfall<br />";
		
		break;
	}
}

}
?>