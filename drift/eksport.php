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
	$this->hoveddata =	"";
}

function skript() {
	$this->returi->reset();
	$this->returi->set();
	$now = new DateTime;
	$date = new DateTime(date('Y-m-01'));
	
	for( $i = 0; $i < 6; $i++ ) {
		$date->sub(new DateInterval('P1M'));
		$a[] = "\t\t\t['" . $date->format('Y-m-01') . "', '" . $date->format('Y-m-t') . "', '" . strftime("%B %Y", $date->getTimestamp()) . "']";
	}
	$date = new DateTime(date('Y-m-01'));
	$date->sub(new DateInterval('P1M'));

	$a[] = "\t\t\t['', '', 'Angi fra- og til-dato']";

?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	var perioder = new Ext.data.SimpleStore({
		fields: ['fom', 'tom', 'visning'],
		data : [
<? echo implode(",\n", $a);?>
		]
	});
	
	var periodevelger = new Ext.form.ComboBox({
		allowBlank: true,
		displayField: 'visning',
		editable: true,
		fieldLabel: 'Periode',
		forceSelection: true,
		hiddenName: 'periode',
		listeners: {
			select: function(combo, record, index) {
				if(!record.data.fom) {
					fradato.enable();
					tildato.enable();
				}
				else {
					fradato.setValue(record.data.fom);
					tildato.setValue(record.data.tom);
					fradato.disable();
					tildato.disable();
				}
			}
		},
		mode: 'local',
		name: 'periode',
		selectOnFocus: true,
		store: perioder,
		triggerAction: 'all',
		typeAhead: false,
		valueField: 'fom',
		value: '<?php echo $date->format('Y-m-d');?>',
		width: 200
	});

	var eksportoppsett = new Ext.form.ComboBox({
		allowBlank: true,
		displayField: 'navn',
		editable: true,
		fieldLabel: 'Tilpasset eksportoppsett',
		forceSelection: true,
		hiddenName: 'periode',
		mode: 'remote',
		name: 'eksportoppsett',
		listeners: {
			select: function(combo, record, index) {
				oppsett = record.get('oppsett');
				if( oppsett.csvSkilletegn ) {
					skilletegn.setValue( oppsett.csvSkilletegn );
				}
				if( oppsett.csvTekstAnførsel ) {
					innpakning.setValue( oppsett.csvTekstAnførsel );
				}
				if( oppsett.datoformat ) {
					datoformat.setValue( oppsett.datoformat );
				}
			}
		},
		selectOnFocus: true,
		store: new Ext.data.JsonStore({
			fields: [
				{name: 'id'},
				{name: 'navn'},
				{name: 'oppsett'}
			],
			root: 'data',
			url: 'index.php?oppslag=eksport&oppdrag=hentdata&data=eksportoppsett'
		}),
		triggerAction: 'all',
		typeAhead: false,
		valueField: 'id',
		width: 200,
		listWidth: 500
	});

	var fradato = new Ext.form.DateField({
		allowBlank: false,
		altFormats: "j.n.y|j.n.Y|j/n/y|j/n/Y|j-n-y|j-n-Y|j. M y|j. M -y|j. M. Y|j. F -y|j. F y|j. F Y|j/n|j-n|dm|dmy|dmY|d|Y-m-d",
		disabled: true,
		fieldLabel: 'Fra dato',
		format: 'd.m.Y',
		name: 'fradato',
		value: '<?=$date->format('01.m.Y')?>',
		width: 200
	});


	var tildato = new Ext.form.DateField({
		allowBlank: false,
		altFormats: "j.n.y|j.n.Y|j/n/y|j/n/Y|j-n-y|j-n-Y|j. M y|j. M -y|j. M. Y|j. F -y|j. F y|j. F Y|j/n|j-n|dm|dmy|dmY|d|Y-m-d",
		disabled: true,
		fieldLabel: 'Til dato',
		format: 'd.m.Y',
		name: 'tildato',
		value: '<?=$date->format('t.m.Y')?>',
		width: 200
	});


	var datoformat = new Ext.form.ComboBox({
		allowBlank: false,
		displayField: 'visning',
		editable: true,
		fieldLabel: 'Datoformat',
		forceSelection: false,
		mode: 'local',
		name: 'datoformat',
		selectOnFocus: true,
		store: new Ext.data.SimpleStore({
		fields: ['format', 'visning'],
		data : [
			['Y-m-d', 'Y-m-d (1999-12-31)'],
			['d.m.Y', 'd.m.Y (31.12.1999)'],
			['d.m.y', 'd.m.y (31.12.99)'],
			['d/m/Y', 'd/m/Y (31/12/1999)'],
			['d/m/y', 'd/m/y (31/12/99)'],
			['m/d/Y', 'm/d/Y (12/31/1999)'],
			['m/d/y', 'm/d/y (12/31/99)']
		]
	}),
		triggerAction: 'all',
		typeAhead: true,
		valueField: 'format',
		value: 'Y-m-d',
		width: 200
	});

	var skilletegn = new Ext.form.ComboBox({
		allowBlank: false,
		displayField: 'visning',
		editable: true,
		fieldLabel: 'CSV Skilletegn (delimiter)',
		forceSelection: true,
		mode: 'local',
		name: 'skilletegn',
		selectOnFocus: true,
		store: new Ext.data.SimpleStore({
		fields: ['tegn', 'visning'],
		data : [
			[',', 'komma (,)'],
			[';', 'semikolon (;)'],
			['\\t', 'tabulator-tegn (\\t)']
		]
	}),
		triggerAction: 'all',
		typeAhead: true,
		valueField: 'tegn',
		value: ',',
		width: 200
	});

	var innpakning = new Ext.form.ComboBox({
		allowBlank: false,
		displayField: 'visning',
		editable: true,
		fieldLabel: 'Anførsel av tekst',
		forceSelection: true,
		mode: 'local',
		name: 'innpakning',
		selectOnFocus: true,
		store: new Ext.data.SimpleStore({
		fields: ['tegn', 'visning'],
		data : [
			["'", "Enkle gåseøyne (')"],
			['"', 'Doble gåseøyne (")']
		]
	}),
		triggerAction: 'all',
		typeAhead: true,
		valueField: 'tegn',
		value: "'",
		width: 200
	});

	var tegnkode = new Ext.form.ComboBox({
		allowBlank: false,
		displayField: 'visning',
		editable: true,
		fieldLabel: 'Tegnkoding',
		forceSelection: false,
//		hiddenName: 'kode',
		mode: 'local',
		name: 'kode',
		selectOnFocus: true,
		store: new Ext.data.SimpleStore({
		fields: ['kode', 'visning'],
		data : [
			['UTF-8', 'Unicode UTF-8'],
			['UTF-16LE', 'Unicode UTF-16LE (for Microsoft Excel)'],
			['iso-8859-1', 'iso-8859-1'],
			['iso-8859-15', 'iso-8859-15']
		]
	}),
		triggerAction: 'all',
		typeAhead: true,
		valueField: 'kode',
		value: 'UTF-8',
		width: 200
	});

	var kort = new Ext.FormPanel({
		title: 'Eksport av data',
		labelWidth: 100,
		frame: true,
		layout: 'hbox',
		items: [
			{
				layout: 'form',
				width: '50%',
				xtype: 'container',
				items: [
					periodevelger,
					fradato,
					tildato,
					datoformat,
					skilletegn,
					innpakning,
					{
						xtype: 'button',
						text: 'Alle leieforhold som CSV',
						handler: function() {
							window.location = "index.php?oppslag=eksport&oppdrag=hentdata&data=leieforhold&metode=csv&fra=" + fradato.getValue().format('Y-m-d') + "&til=" + tildato.getValue().format('Y-m-d') + "&datoformat=" + encodeURIComponent(datoformat.getValue()) + "&skilletegn=" + encodeURIComponent(skilletegn.getValue()) + "&innpakning=" + encodeURIComponent(innpakning.getValue());
						},
						width: 200
					},
					{
						xtype: 'button',
						text: 'Alle krav som CSV',
						handler: function() {
							window.location = "index.php?oppslag=eksport&oppdrag=hentdata&data=krav&metode=csv&fra=" + fradato.getValue().format('Y-m-d') + "&til=" + tildato.getValue().format('Y-m-d') + "&datoformat=" + encodeURIComponent(datoformat.getValue()) + "&skilletegn=" + encodeURIComponent(skilletegn.getValue()) + "&innpakning=" + encodeURIComponent(innpakning.getValue());
						},
						width: 200
					},
					{
						xtype: 'button',
						text: 'Alle utestående krav ved til-dato',
						handler: function() {
							window.location = "index.php?oppslag=eksport&oppdrag=hentdata&data=utestående&metode=csv&fra=" + fradato.getValue().format('Y-m-d') + "&til=" + tildato.getValue().format('Y-m-d') + "&datoformat=" + encodeURIComponent(datoformat.getValue()) + "&skilletegn=" + encodeURIComponent(skilletegn.getValue()) + "&innpakning=" + encodeURIComponent(innpakning.getValue());
						},
						width: 200
					},
					{
						xtype: 'button',
						text: 'Alle innbetalinger som CSV',
						handler: function() {
							window.location = "index.php?oppslag=eksport&oppdrag=hentdata&data=innbetalinger&metode=csv&fra=" + fradato.getValue().format('Y-m-d') + "&til=" + tildato.getValue().format('Y-m-d') + "&datoformat=" + encodeURIComponent(datoformat.getValue()) + "&skilletegn=" + encodeURIComponent(skilletegn.getValue()) + "&innpakning=" + encodeURIComponent(innpakning.getValue());
						},
						width: 200
					},
					{
						xtype: 'button',
						text: 'Inntekter per bygning som CSV',
						handler: function() {
							window.location = "index.php?oppslag=eksport&oppdrag=hentdata&data=bygningsregnskap&metode=csv&fra=" + fradato.getValue().format('Y-m-d') + "&til=" + tildato.getValue().format('Y-m-d') + "&datoformat=" + encodeURIComponent(datoformat.getValue()) + "&skilletegn=" + encodeURIComponent(skilletegn.getValue()) + "&innpakning=" + encodeURIComponent(innpakning.getValue());
						},
						width: 200
					},
					{
						xtype: 'button',
						text: 'Alle giroer som filarkiv',
						menu: new Ext.menu.Menu({
							items: [
								{
									text: 'Last ned komprimert som tar.gz-arkiv',
									handler: function() {
										window.location = "index.php?oppslag=eksport&oppdrag=hentdata&data=giroer_pdf&metode=targz&fra=" + fradato.getValue().format('Y-m-d') + "&til=" + tildato.getValue().format('Y-m-d');
									}
								},
								{
									text: 'Last ned som zip-fil',
									handler: function() {
										window.location = "index.php?oppslag=eksport&oppdrag=hentdata&data=giroer_pdf&metode=zip&fra=" + fradato.getValue().format('Y-m-d') + "&til=" + tildato.getValue().format('Y-m-d');
									}
								}
							]
						}),
						width: 200
					}
				]
			},
			{
				layout: 'form',
				xtype: 'container',
				items: [
					eksportoppsett,
					{
						xtype: 'button',
						text: 'Kjør tilpasset eksport',
						handler: function() {
							window.location = "index.php?oppslag=eksport&oppdrag=hentdata&data=spesialeksport&metode=csv&oppsett=" + eksportoppsett.getValue() + "&fra=" + fradato.getValue().format('Y-m-d') + "&til=" + tildato.getValue().format('Y-m-d') + "&datoformat=" + encodeURIComponent(datoformat.getValue()) + "&skilletegn=" + encodeURIComponent(skilletegn.getValue()) + "&innpakning=" + encodeURIComponent(innpakning.getValue());
						},
						width: 200
					},
				]
			}
		],
		height: 400,
		width: 900,
		height: 500,
		buttons: [{
			handler: function() {
				window.location = '<?=$this->returi->get();?>';
			},
			text: 'Tilbake'
		}]
	});
    kort.render('panel');

});
<?
}

function design() {
?>
<div id="panel"></div>
<?
}


function taimotSkjema() {
}


/**********************************************
Konfigurering av spesialeksporter:
Konfigurasjonen lagres serialisert i tabellen eksportoppsett
sammen med navn på oppsettet.
stdClass:
	kilde (streng): eksisterende grunneksport
	filnavn: Navn som skal brukes på fila.
				Filnavnet etterfølges av fra- og tildato i formatet '_Y-m-d_Y-m-d',
				samt tidsstempel i formatet '(vYmdHis).csv'
	csvSkilletegn (streng):	Standardverdi csv skilletegn for csv-fila
	csvTekstAnførsel (streng): Standardverdi csv tegn for anførsel av tekst i csv-fila
	datoformat (streng): Standard datoformat for csv-fila
	felter (stdClass): Objekt der egenskapens navn angir kolonnenavnet.
		Mulige verdier for hver kolonne er:
		-	null: Verdien hentes direkte fra kilden
		-	formel (streng som begynner med '='): Formel med aritmetiske operasjoner, samt if og switch
		-	funksjon/metode (array m to elementer (funksjon) eller tre (objekt og metode):
			Verdien er resultatet fra nevnte funksjon
**********************************************/


function hentData($data = "") {
	$tp = $this->mysqli->table_prefix;
	$overskrifter = (bool) 1 or $_GET['overskrifter'];
	$filnavn	= $data;
	$spesialeksport	= ( @$_GET['data'] == "spesialeksport" ? true : false);
	$metode		= (isset( $_GET['metode'] )		? $_GET['metode'] : "csv");
	$kode		= (isset( $_GET['kode'] )		? $_GET['kode'] : "UTF-8");
	$oppsett	= (isset( $_GET['oppsett'] )	? $_GET['oppsett'] : "");
	
	$skilletegn = (isset( $_GET['skilletegn'] )	? $_GET['skilletegn'] : ",");
	$skilletegn = ( $skilletegn === "\\\\t" ) ? "\t" : $skilletegn;
	
	$innpakning = stripslashes(isset( $_GET['innpakning'] )	? $_GET['innpakning'] : '"');
	$datoformat = (isset( $_GET['datoformat'] )	? $_GET['datoformat'] : 'Y-m-d');
	
	$filter = "";
	
	//	Hent oppsett for evt spesialeksport
	if($data == "eksportoppsett") {
		$resultat = $this->mysqli->arrayData(array(
			'source'		=> "{$tp}eksportoppsett",
			'orderfields'	=> 'eksportoppsett.navn'
		));
			foreach( $resultat->data as $eksportoppsett ) {
				$eksportoppsett->oppsett = unserialize($eksportoppsett->oppsett);
			}
		echo json_encode($resultat);
		return;
	}
	

	if( $spesialeksport ) {
		$oppsett = $this->mysqli->arrayData(array(
			'source'	=> "{$tp}eksportoppsett",
			'where'		=> "id = '$oppsett'"
		))->data;
		$oppsett = reset($oppsett);
		$oppsett = unserialize($oppsett->oppsett);
		
		$data = @$oppsett->kilde;
		$filnavn = @$oppsett->filnavn ? $oppsett->filnavn : $filnavn;
		
		if( @$oppsett->filter ) {
			$filter = str_replace(
				array(
					'{fra}',
					'{til}'
				),
				array(
					$this->fra,
					$this->til
				),
				$oppsett->filter
			);
		}
	}


	if($data == "backup") {
	
		$fil = LEIEBASEN_BACKUP_DB;
		if(file_exists($fil)) {

			$filendelse = pathinfo($fil, PATHINFO_EXTENSION);

			$mimeliste = array(
				'tar'	=>	'application/x-tar',
				'zip'	=>	'application/zip',
				'sql'	=>	'text/plain',
				'txt'	=>	'text/plain'
			);
	
			$mime = @$mimeliste[$filendelse];
			if(function_exists('mime_content_type')) {
				$mime = mime_content_type($fil);
			}
			if(function_exists('finfo_open')) {
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$mime = finfo_file($finfo, $fil);
				finfo_close($finfo);
			}
		
			if(!$mime) {
				$mime = 'application/octet-stream';
			}
	
			header("Content-Type: {$mime}; charset=utf-8");
			header("Content-disposition: attachment; filename=\"Leiebasen Backup " . date('Y-m-d His', filectime($fil)) . ".{$filendelse}\"");
			header('Content-Length: ' . filesize($fil));
			readfile($fil);
			
			$this->mysqli->saveToDb(array(
				'table'		=> "{$tp}valg",
				'update'	=> true,
				'where'		=> "{$tp}valg.innstilling = 'backup_siste_nedlastet'",
				'fields'	=> array(
					'verdi'		=> filectime($fil)
				)
			));
			
			return;
		}
	}
	

	if($data == "giroer_pdf") {
		$filter = $filter
				? $filter
				: ("giroer.utskriftsdato IS NOT NULL and krav.utskriftsdato IS NOT NULL and krav.kravdato >= '$this->fra'"
				. ($this->til ? " AND krav.kravdato <= '$this->til'" : ""));
	
		$sett = $this->mysqli->arrayData(array(
			'distinct'	=> true,
			'class'		=> "Giro",
			'source' => "krav LEFT JOIN giroer ON krav.gironr = giroer.gironr",
			'fields' => "krav.gironr AS id",
			'where' => $filter
		));

		if( $metode == "zip" ) {
			$arkivfil = "{$this->filarkiv}/giroer/_giroer.zip";

			try {
				$arkiv = new ZipArchive();
				$arkiv->open($arkivfil, ZipArchive::CREATE | ZipArchive::OVERWRITE);
			
				foreach($sett->data as $giro) {
			
					$giro->lagrePdf(array(
						'gjengivelsesfil'	=> "pdf_giro"
					));

					$fil = "{$this->filarkiv}/giroer/{$giro}.pdf";
					$arkiv->addFile($fil, "giroer/{$giro}.pdf");
				}
				$arkiv->close();
			}
		
			catch (Exception $e) {
				echo "Exception : " . $e;
			}
		
			header('Content-Type: application/zip');
			header('Content-disposition: attachment; filename=giroer.zip');
			header('Content-Length: ' . filesize( $arkivfil ));
			readfile( $arkivfil );
			return;
		}
		else {
			$arkivfil = "{$this->filarkiv}/giroer/_giroer.tar";

			if(file_exists($arkivfil)) {
				unlink($arkivfil);
			}
			if(file_exists($arkivfil . ".gz")) {
				unlink($arkivfil.".gz");
			}
			try {
				$arkiv = new PharData($arkivfil);
			
				foreach($sett->data as $giro) {
			
					$giro->lagrePdf(array(
						'gjengivelsesfil'	=> "pdf_giro"
					));

					$fil = "{$this->filarkiv}/giroer/{$giro}.pdf";
					$arkiv->addFile($fil, "{$giro}.pdf");
				}
				$arkiv->compress(Phar::GZ);
			}
		
			catch (Exception $e) {
				echo "Exception : " . $e;
			}
		
			header('Content-Type: application/x-gzip');
			header('Content-disposition: attachment; filename=giroer.tar.gz');
			header('Content-Length: ' . filesize( $arkivfil.".gz" ));
			readfile( $arkivfil.".gz" );
			return;
		}		
	}
	

	if($data == "leieforhold") {
		$filter = $filter
				? $filter
				: ("påbegynt >= '$this->fra'" . ($this->til ? " AND påbegynt <= '$this->til'" : ""));
	
		$sett = $this->mysqli->arrayData(array(
			'source' => "kontrakter INNER JOIN (SELECT MAX(kontraktnr) AS kontrakt, min(fradato) AS påbegynt FROM kontrakter GROUP BY leieforhold) AS sistekontrakter ON kontrakter.kontraktnr = sistekontrakter.kontrakt",
			'fields' => "leieforhold, kontraktnr, leieobjekt, andel, påbegynt, tildato, oppsigelsestid, leiebeløp, ant_terminer",
			'where' => $filter
		));
		foreach($sett->data as $indeks => $leieforhold) {
			$leieforhold->påbegynt = $leieforhold->påbegynt ? date($datoformat, strtotime($leieforhold->påbegynt)) : null;
			$leieforhold->tildato = $leieforhold->tildato ? date($datoformat, strtotime($leieforhold->tildato)) : null;
			$leieforhold->fast_kid = (string)$this->genererKid($leieforhold->kontraktnr);
			$leieforhold->leietakere = $this->liste($this->kontraktpersoner($leieforhold->kontraktnr));
			$leieforhold->adresse = $this->adresse($leieforhold->kontraktnr);
		}
	}

	
	if($data == "krav") {
		$filter = $filter
				? $filter
				: ("kravdato >= '$this->fra'" . ($this->til ? " AND kravdato <= '$this->til'" : ""));
	
		$delkravtyper = $this->mysqli->arrayData(array(
			'source'	=> "{$tp}delkravtyper",
			'where'		=> "!selvstendig_tillegg"
		))->data;
	
	
		$sett = $this->mysqli->arrayData(array(
			'source' => "krav
					LEFT JOIN giroer ON krav.gironr = giroer.gironr
					LEFT JOIN kontrakter ON kontrakter.kontraktnr = krav.kontraktnr
					LEFT JOIN leieobjekter ON krav.leieobjekt = leieobjekter.leieobjektnr
					LEFT JOIN bygninger ON leieobjekter.bygning = bygninger.id",
			'fields' => "krav.id AS kravid, kontrakter.leieforhold, null AS navn, krav.gironr, giroer.kid, giroer.sammensatt, krav.kravdato, krav.tekst, krav.beløp, krav.type, bygninger.kode AS bygningskode, bygninger.navn AS bygning, krav.leieobjekt, krav.anleggsnr, krav.termin, krav.fom, krav.tom, krav.utskriftsdato, krav.forfall",
			'where' => $filter,
			'orderfields'	=> "krav.kravdato, krav.id"
		));
		foreach($sett->data as $linje) {
			$krav = $this->hent('Krav', $linje->kravid);
			$linje->kravdato = $linje->kravdato ? date($datoformat, strtotime($linje->kravdato)) : null;
			$linje->fom = $linje->fom ? date($datoformat, strtotime($linje->fom)) : null;
			$linje->tom = $linje->tom ? date($datoformat, strtotime($linje->tom)) : null;
			$linje->sammensatt = $linje->sammensatt ? date($datoformat, strtotime($linje->sammensatt)) : null;
			$linje->utskriftsdato = $linje->utskriftsdato ? date($datoformat, strtotime($linje->utskriftsdato)) : null;
			$linje->forfall = $linje->forfall ? date($datoformat, strtotime($linje->forfall)) : null;
			$linje->navn = $this->liste($this->kontraktpersoner($this->sistekontrakt($linje->leieforhold)));
			
			foreach( $delkravtyper AS $delkravtype ) {
				$linje->{$delkravtype->kode} = $krav->hentDel($delkravtype->kode);
			}			
		}
	}
	

	if($data == "utestående") {
		$filter = $filter
				? $filter
				: "kravdato <= '{$this->til}'";
	
		$sett = $this->mysqli->arrayData(array(
			'source'	=> "{$tp}krav AS krav
							LEFT JOIN kontrakter ON kontrakter.kontraktnr = krav.kontraktnr
							LEFT JOIN
							(
								SELECT krav, sum(beløp) AS innbetalt
								FROM {$tp}innbetalinger
								WHERE dato <= '{$this->til}'
								GROUP BY krav
							) AS innbetalinger
							ON krav.id = innbetalinger.krav",
			'fields'	=> "krav.id, krav.kravdato, krav.fom, krav.tom, krav.utskriftsdato, krav.forfall, krav.type, krav.termin, krav.gironr, kontrakter.leieforhold, krav.tekst, krav.beløp, (krav.beløp - IFNULL(innbetalinger.innbetalt, 0)) AS utestående, krav.leieobjekt, krav.anleggsnr",
			'where'		=> $filter,
			'orderfields'	=> "krav.kravdato, krav.id"
		));
		foreach($sett->data as $indeks => $krav) {
			if(!(float)$krav->utestående) {
				unset($sett->data[$indeks]);
			}
			else {
				$krav->kravdato = $krav->kravdato ? date($datoformat, strtotime($krav->kravdato)) : null;
				$krav->fom = $krav->fom ? date($datoformat, strtotime($krav->fom)) : null;
				$krav->tom = $krav->tom ? date($datoformat, strtotime($krav->tom)) : null;
				$krav->utskriftsdato = $krav->utskriftsdato ? date($datoformat, strtotime($krav->utskriftsdato)) : null;
				$krav->forfall = $krav->forfall ? date($datoformat, strtotime($krav->forfall)) : null;
				$krav->navn = $this->liste($this->kontraktpersoner($this->sistekontrakt($krav->leieforhold)));
			}
		}
	}
	

	if($data == "innbetalinger") {
		$filter = $filter
				? $filter
				: (
					"innbetalinger.dato >= '$this->fra'\n"
					.	(
						$this->til
						? "AND innbetalinger.dato <= '$this->til'\n"
						: ""
					)
				);
	
		$sett = $this->mysqli->arrayData(array(
			'source' => "innbetalinger\n"
					.	"LEFT JOIN OCRdetaljer ON innbetalinger.OCRtransaksjon = OCRdetaljer.id\n"
					.	"LEFT JOIN krav ON innbetalinger.krav = krav.id\n",
			'fields' => "innbetalinger.innbetaling AS id,
						innbetalinger.innbetalingsid as delid,
						innbetalinger.dato,
						innbetalinger.ref AS referanse,
						innbetalinger.beløp,
						innbetalinger.konto,
						innbetalinger.betaler,
						innbetalinger.leieforhold,
						OCRdetaljer.forsendelsesnummer,
						OCRdetaljer.oppdragsnummer,
						OCRdetaljer.transaksjonsnummer,
						OCRdetaljer.transaksjonstype,
						OCRdetaljer.løpenummer,
						OCRdetaljer.oppgjørsdato,
						OCRdetaljer.kid,
						OCRdetaljer.blankettnummer,
						OCRdetaljer.debetkonto,
						OCRdetaljer.arkivreferanse,
						innbetalinger.krav,
						krav.gironr,
						krav.utskriftsdato,
						krav.kravdato,
						krav.type AS kravtype,
						krav.tekst AS kravbeskrivelse,
						krav.termin AS termin",
			'orderfields' => "innbetalinger.dato,
						innbetalinger.konto,
						OCRdetaljer.forsendelsesnummer,
						OCRdetaljer.transaksjonsnummer,
						OCRdetaljer.løpenummer,
						innbetalinger.ref,
						innbetalinger.leieforhold,
						krav.kravdato,
						krav.type",
			'where' => "innbetalinger.konto != '0'\n"
					.	"AND ({$filter})\n"
		));
		foreach($sett->data as $innbetaling) {
			$innbetaling->dato = $innbetaling->dato ? date($datoformat, strtotime($innbetaling->dato)) : null;
		}
	}

	
	if($data == "bygningsregnskap") {
		$filter = $filter
				? $filter
				: (
					$filter = "1"
					. ($_GET['fra'] ? " AND krav.kravdato >= '{$this->fra}'" : "")
					. ($_GET['til'] ? " AND krav.kravdato <= '{$this->til}'" : "")
				);
	
		$sett = $this->mysqli->arrayData(array(
			'source' => "(bygninger INNER JOIN leieobjekter ON bygninger.id = leieobjekter.bygning INNER JOIN kontrakter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr)
			LEFT JOIN krav ON kontrakter.kontraktnr = krav.kontraktnr",

			'fields' => "min(bygninger.id) AS id, min(bygninger.kode) AS kode, min(bygninger.navn) AS bygning, SUM(if(krav.type = 'Husleie', krav.beløp, null)) AS leieinntekter, SUM(if(krav.type = 'Fellesstrøm', krav.beløp, null)) AS strøm, SUM(if(krav.type != 'Husleie' and krav.type != 'Fellesstrøm', krav.beløp, null)) AS annet, SUM(krav.beløp) AS sum",

			'groupfields' => "bygninger.id",

			'where' => $filter,
			'orderfields' => "bygninger.kode ASC, bygninger.id ASC"
		));
	}


	//	Etterbehandling v/ evt. spesialeksport
	if( $spesialeksport ) {
		$felter = $oppsett->felter;
		
		$nyttSett = array();
		foreach($sett->data as $indeks => $linje) {
			settype( $nyttSett[$indeks], 'object' );

			foreach( $oppsett->felter as $oppsettfelt => $konfigurasjon) {
				if( $konfigurasjon === null ) {
					$nyttSett[$indeks]->{$oppsettfelt} = $linje->{$oppsettfelt};
				}

				else if ( is_string( $konfigurasjon) and strstr( $konfigurasjon, '=') ) {
					$nyttSett[$indeks]->{$oppsettfelt} = $this->evaluer( $konfigurasjon, $linje );
				}
				
				else if ( is_string( $konfigurasjon ) ) {
					$nyttSett[$indeks]->{$oppsettfelt} = $linje->{$konfigurasjon};
				}
			}
		}
		
		$sett->data = $nyttSett;
		$sett->totalRows = count($nyttSett);
	}	


	// Her lages headers for fila som skal lastes ned
	header('Content-type: text/csv; charset=$kode filename="' . $filnavn . '_'. $this->fra . '_'. $this->til . '(v' . date('YmdHis') . ').csv"');
	header('Content-Disposition: attachment; filename="' . $filnavn . '_'. $this->fra . '_'. $this->til . '(v' . date('YmdHis') . ').csv"');
	
	// Åpne ei nedlastingsfil kun for å skrive (w)
	$fil = fopen("php://output", 'w');
	
	// Skriv et BOM-tegn (Byte order mark) på fila sånn at f.eks Excel gjenkjenner tegnkodinga
	fputs($fil, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
	
	// Skriv overskriftsraden dersom denne skal inkluderes
	if($overskrifter) {
		$overskrifter = array();
		foreach(reset($sett->data) as $egenskap => $verdi) {
			$overskrifter[] = ($egenskap);
		}
		fputcsv($fil, $overskrifter, $skilletegn, $innpakning);
	}
	
	// Skriv datainnholdet fra spørringen
	foreach($sett->data as $indeks => $leieforhold) {
		fputcsv($fil, (array) $leieforhold, $skilletegn, $innpakning);
	}
	// Lukk nedlastingsfila
	fclose($fil);
	
	return;
}

}
?>