<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Oppfølgingstiltak, notater og henvendelser';
public $ext_bibliotek = 'ext-4.2.1.883';



function __construct() {
	parent::__construct();
	
	$this->hoveddata = array(
		'returnQuery'	=> true,
		'source' => "notater",
		'where' => ( isset( $_POST['leieforhold']) ? ("leieforhold = '" . (int)$_POST['leieforhold'] . "'") : ( isset( $_GET['leieforhold'] ) ? ("leieforhold = '" . (int)$_GET['leieforhold'] . "'") : null)),
		'orderfields' => "dato DESC, registrert DESC"
	);
}




function skript() {
	if( isset( $_GET['returi'] ) and $_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	$leieforhold = $this->hent('Leieforhold', @$_GET['leieforhold']);
?>
Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?php echo $this->http_host . "/" . $this->ext_bibliotek . "/examples/ux";?>');

Ext.require([
 	'Ext.data.*',
 	'Ext.form.*'
]);

Ext.onReady(function() {

    Ext.tip.QuickTipManager.init();
	Ext.form.Field.prototype.msgTarget = 'title';
	Ext.Loader.setConfig({
		enabled: true
	});
	
<?
	include_once("_menyskript.php");
?>

	Ext.define('Notat', {
		 extend: 'Ext.data.Model',
		
		 // http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
		 fields: [
			{name: 'notatnr', type: 'float'},
			{name: 'leieforhold', type: 'float'},
			{name: 'leieforholdbesk'},
			{name: 'dato', type: 'date', dateFormat: 'Y-m-d'},
			{name: 'notat'},
			{name: 'henvendelse_fra'},
			{name: 'kategori'},
			{name: 'vedlegg'},
			{name: 'brevtekst'},
			{name: 'dokumentreferanse'},
			{name: 'dokumenttype'},
			{name: 'registrert', type: 'date', dateFormat: 'Y-m-d H:i:s'},
			{name: 'registrerer'}
		]
	 });
	
	Ext.define('Leieforhold', {
		 extend: 'Ext.data.Model',
		
		 // http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
		 fields: [
		 	{name: 'leieforhold', type: 'float'},
		 	{name: 'beskrivelse'}
		 ]
	 });
	
	
	visBrev = function(nr){
		linje = notater.getAt(nr);
		Ext.MessageBox.show({
			title: 'Brev',
			msg: "<div style=\"text-align: left;\">" + linje.get('brevtekst') + "</div>",
			minWidth: 900,
			maxWidth: 1000
		});
	}

	fjernVarsel = function( leieforhold ) {
		Ext.Ajax.request({
			url: "index.php?oppslag=forsinden&oppdrag=manipuler&data=restart&leieforhold=" + leieforhold,
			 success : function(result){
				window.location.reload();
			 },
			 failure : function(result){
				Ext.MessageBox.show({
					title: 'Mislyktes',
					msg: 'Klarte ikke fjerne varselet'
				});
			 }
		});
	}

	lastVedlegg = function(nr) {
		window.open(
			'index.php?oppslag=forsiden&oppdrag=hentdata&data=vedlegg&id=' + nr
		);
	}


	tin = function(v){
		Ext.Ajax.request({
			waitMsg: 'Varmer opp...',
			url: "index.php?oppslag=forsiden&oppdrag=manipuler&data=tin&leieforhold=" + v,
			 success: function(response, options){
				var tilbakemelding = Ext.util.JSON.decode(response.responseText);
				if(tilbakemelding['success'] == true) {
					Ext.MessageBox.alert('Utført', tilbakemelding.msg, function(){
					window.location.reload();
//					notater.load();
					});
				}
				else {
					Ext.MessageBox.alert('Klarte ikke..',tilbakemelding['msg']);
				}
			 }
		});
	}


	slettNotat = function(v){
		Ext.MessageBox.confirm('Bekreft', 'Er du sikker på at du vil slette notatet?', function(id){
			if(id == 'yes'){
				Ext.Ajax.request({
					waitMsg: 'Sletter...',
					url: "index.php?oppslag=forsiden&oppdrag=manipuler&data=slett&id=" + v,
					 success: function(response, options){
						var tilbakemelding = Ext.util.JSON.decode(response.responseText);
						if(tilbakemelding['success'] == true) {
							Ext.MessageBox.alert('Slettet', tilbakemelding.msg, function(){
							notater.load();
							});
						}
						else {
							Ext.MessageBox.alert('Klarte ikke..',tilbakemelding['msg']);
						}
					 }
				});
			}
		});
	}


	var notater = Ext.create('Ext.data.JsonStore', {
		storeId: 'notater',
		
		autoLoad: true,
		pageSize: 300,
		proxy: {
			type: 'ajax',
			url: 'index.php?oppdrag=hentdata&data=notater&oppslag=forsiden<?php echo $leieforhold->hentId() ? "&leieforhold={$leieforhold}" : "";?>',
			reader: {
				type:			'json',
				root:			'data',
				idProperty:		'notatnr',
				totalProperty:	'totalRows'
			}
		},
			
		model: 'Notat'
	});
	
	
	var leieforholdliste = Ext.create('Ext.data.JsonStore', {
		storeId: 'leieforholdliste',
		
		autoLoad: true,
		proxy: {
			type: 'ajax',
			url: 'index.php?oppslag=forsiden&oppdrag=hentdata&data=leieforhold',
			reader: {
				type: 'json',
				root: 'data',
				idProperty: 'leieforhold'
			}
		},
			
		model: 'Leieforhold'
	});
	
	
	var leieforholdvelger = new Ext.form.ComboBox({
		name: 'leieforhold',
		fieldLabel: 'Velg leieforhold',
		width: 400,
		matchFieldWidth: false,
		listConfig: {
			width: 600
		},
		
		store: leieforholdliste,
		queryMode: 'remote',
		minChars: 2,
		valueField: 'leieforhold',
		displayField: 'beskrivelse',
		triggerAction: 'all',
		
		allowBlank: true,
		forceSelection: false,
		editable: true,
		selectOnFocus: true,
		typeAhead: false,

		listeners: {
			select: function() {
				window.location = 'index.php?oppslag=forsiden&leieforhold=' + leieforholdvelger.getValue();
			}
		}
	});


	var notatnr = {
		align: 'right',
		dataIndex: 'notatnr',
		header: 'Notatnr',
		hidden: true,
		sortable: true,
		width: 40
	};

	var leieforhold = {
		dataIndex: 'leieforhold',
		header: 'Leieforhold',
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return value + " " + record.data.leieforholdbesk;
		},
		sortable: true,
		width: 150
	};

	var dato = {
		dataIndex: 'dato',
		renderer: Ext.util.Format.dateRenderer('d.m.Y'),
		header: 'Dato',
		sortable: true,
		width: 70
	};

	var notat = {
		dataIndex: 'notat',
		header: 'Notat',
		sortable: true,
		width: 50
	};

	var kategori = {
		dataIndex: 'kategori',
		header: 'Hva',
		sortable: true,
		width: 90,
		flex: 1
	};

	var brevtekst = {
		dataIndex: 'brevtekst',
		header: 'Brev',
		sortable: true,
		width: 100,
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return value ? "<a style=\"cursor: pointer\" onClick=\"visBrev(" + rowIndex + ")\">Vis</a> | <a target=_blank href=index.php?oppslag=notat_skjema&oppdrag=utskrift&id=" + record.data.notatnr + ">Skriv ut</a>" : "";
		}
	};

	var vedlegg = {
		dataIndex: 'vedlegg',
		header: 'Vedlegg',
		sortable: true,
		width: 50,
		renderer: function(value, metaData, record, rowIndex, colIndex, store){
			return value ? "<a style=\"cursor: pointer\" onClick=\"lastVedlegg(" + record.data.notatnr + ")\"><img src=\"../bilder/binders16.png\" /></a>" : "";
		}
	};

	var dokumentreferanse = {
		dataIndex: 'dokumentreferanse',
		header: 'Ekst. dok',
		renderer: function(value, metaData, record, rowIndex, colIndex, store) {
			return (value ? (value + " ") : "") + (record.data.dokumenttype ? ("(" + record.data.dokumenttype + ")") : "");
		},
		sortable: true,
		width: 120
	};

	var registrert = {
		dataIndex: 'registrert',
		header: 'Lagt inn',
		hidden: true,
		renderer: Ext.util.Format.dateRenderer('d.m.Y H:i:s'),
		sortable: true,
		width: 110
	};

	var registrerer = {
		dataIndex: 'registrerer',
		hidden: true,
		header: 'Lagt inn av',
		sortable: true,
		width: 50
	};


	var slett = {
		dataIndex: 'notatnr',
		header: '',
		renderer: function(v){
			return "<a style=\"cursor: pointer\" onClick=\"slettNotat(" + v + ")\"><img src=../bilder/slett.png /></a>";
		},
		sortable: false,
		width: 30
	};

	var endre = {
		dataIndex: 'notatnr',
		header: '',
		renderer: function(v){
			return "<a href=index.php?oppslag=notat_skjema&id=" + v +"><img src=../bilder/rediger.png /></a>";
		},
		sortable: false,
		width: 30
	};


	var rutenett = Ext.create('Ext.grid.Panel', {
		autoScroll: true,
		region: 'center',

		plugins: [{
			ptype: 'rowexpander',
			pluginId: 'rowexpander',
			rowBodyTpl : ['{notat}']
		}],

		dockedItems: [{
			xtype: 'pagingtoolbar',
			store: notater,
			dock: 'bottom',
			displayInfo: true
		}],

		store: notater,
		columns: [
			notatnr,
			dato,
			kategori,
			brevtekst,
			vedlegg,
			dokumentreferanse,
			registrert,
			registrerer,
			endre,
			slett
		],
		stripeRows: true,
		width: 'auto'
	});


	var leieforholdpanel = Ext.create('Ext.panel.Panel', {
		region: 'north',
		title: '<?php echo addslashes(($leieforhold->hentId()) ? "Leieforhold {$leieforhold}: {$leieforhold->hent('beskrivelse')}" : "Påminnelser");?>',
		frame: false,
		layout: 'anchor',
		maxHeight: 200,
		collapsible: true,
		autoLoad: 'index.php?oppslag=forsiden<?php echo $leieforhold->hentId() ? "&leieforhold={$leieforhold}" : "";?>&oppdrag=hentdata<?php echo isset( $_GET['id'] ) ? "&id={$_GET['id']}" : ""?>',
		autoScroll: true
	});


	var panel = Ext.create('Ext.panel.Panel', {
		border: false,

		defaults: {
			border: false			
		},
		layout: 'border',
		tbar: [
			<?php echo !$leieforhold->hentId() ? "leieforholdvelger\n" : "";?>
		],
		items: [
			leieforholdpanel,
			rutenett
		],
        autoScroll: false,
//        bodyStyle: 'padding:5px',
		title: '',
		frame: true,
		renderTo: 'panel',
		height: 500,
		plain: true,
		title: "Oppfølgingstiltak, notater og henvendelser",
		width: 900,

		buttons: [{
			text: 'Tilbake',
			handler: function(){
				window.location = '<?=$this->returi->get();?>';
			}
		}, {
			text: 'Til leieavtalen',
			handler: function(){
				window.location = "../drift/index.php?oppslag=leieforholdkort&id=<?php echo $leieforhold;?>";
			}
		}, {
			text: 'Registrer ny aktivitet',
			menu: new Ext.menu.Menu({
				items: [
					{
						text: 'Notat',
						handler: function(){
							window.location = "index.php?oppslag=notat_skjema&leieforhold=<?=$leieforhold?>&id=*&type=notat";
						}
					},
					{
						text: 'Spørsmål',
						handler: function(){
							window.location = "index.php?oppslag=notat_skjema&leieforhold=<?=$leieforhold?>&id=*&type=spm";
						}
					},
					{
						text: 'Brev',
						handler: function(){
							window.location = "index.php?oppslag=notat_skjema&leieforhold=<?=$leieforhold?>&id=*&type=brev";
						}
					},
					{
						text: 'Betalingspåminnelse',
						handler: function(){
							window.location = "index.php?oppslag=notat_skjema&leieforhold=<?=$leieforhold?>&id=*&type=purring";
						}
					},
					{
						text: 'Betalingsplan',
						handler: function(){
							window.location = "index.php?oppslag=notat_skjema&leieforhold=<?=$leieforhold?>&id=*&type=betalingsplan";
						}
					},
					{
						text: 'Avtale',
						handler: function(){
							window.location = "index.php?oppslag=notat_skjema&leieforhold=<?=$leieforhold?>&id=*&type=avtale";
						}
					},
					{
						text: 'Varsel om at leieavtalen utløper / må fornyes',
						handler: function(){
							window.location = "index.php?oppslag=notat_skjema&leieforhold=<?=$leieforhold?>&id=*&type=utlv";
						}
					},
					{
						text: 'Varsel etter tvangsfullbyrdelseslovens §4.18 sendt',
						handler: function(){
							window.location = "index.php?oppslag=notat_skjema&leieforhold=<?=$leieforhold?>&id=*&type=418";
						}
					},
					{
						text: 'Sendt varsel (ihht tvisteloven  §5-2) om at saken kan bli klaget inn for forliksrådet',
						handler: function(){
							window.location = "index.php?oppslag=notat_skjema&leieforhold=<?=$leieforhold?>&id=*&type=forliksvarsel";
						}
					},
					{
						text: 'Forliksklage sendt til forliksrådet',
						handler: function(){
							window.location = "index.php?oppslag=notat_skjema&leieforhold=<?=$leieforhold?>&id=*&type=forliksklage";
						}
					},
					{
						text: 'Sendt varsel om at saken kan bli oversendt til inkasso',
						handler: function(){
							window.location = "index.php?oppslag=notat_skjema&leieforhold=<?=$leieforhold?>&id=*&type=inkassovarsel";
						}
					},
					{
						text: 'Begjæring (ihht tvangsfullbyrdelsesloven § 13-2) om fravikelse sendt namsmannen',
						handler: function(){
							window.location = "index.php?oppslag=notat_skjema&leieforhold=<?=$leieforhold?>&id=*&type=tvangsfravikelsesbegj";
						}
					},
					{
						text: 'Begjæring om utlegg sendt namsmannen',
						handler: function(){
							window.location = "index.php?oppslag=notat_skjema&leieforhold=<?=$leieforhold?>&id=*&type=utleggsbegj";
						}
					}
				]
			})
		}]
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
	$limit = @$_GET['limit'];

	switch ($data) {
	
	case "leieforhold": {
		$query = @$this->GET['query'];
		
		$resultat = (object)array(
			'success'	=> true,
			'data'		=> array()
		);

		$leieforholdsett = $this->mysqli->arrayData(array(
			'source'	=> "{$tp}kontrakter as kontrakter
							INNER JOIN {$tp}kontraktpersoner as kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt
							INNER JOIN {$tp}personer as personer ON personer.personid = kontraktpersoner.person",
			'fields'	=>	"kontrakter.leieforhold AS id",
			'where'		=>	"CONCAT(fornavn, ' ', etternavn) LIKE '%{$query}%'
							OR kontrakter.kontraktnr LIKE '%{$query}%'",
			'distinct'	=> true,
			'class'		=> "Leieforhold"
		))->data;
		
		foreach( $leieforholdsett as $leieforhold ) {
			$resultat->data[] = array(
				'leieforhold'	=> $leieforhold->hentId(),
				'beskrivelse'	=> $leieforhold->hent('beskrivelse')
			);
		}
		
		return json_encode($resultat);
		break;
	}
		
	case "notater": {
		$resultat = $this->mysqli->arrayData(array(
		'returnQuery'	=> true,
		'source'		=> "notater",
		'limit'			=> $limit,
		'where'			=> (
						isset( $_POST['leieforhold'])
						? ("leieforhold = '" . (int)$_POST['leieforhold'] . "'")
						: (
							isset( $_GET['leieforhold'] )
							? ("leieforhold = '" . (int)$_GET['leieforhold'] . "'")
							: null
						)
		),
		'orderfields'	=> "dato DESC, registrert DESC"
	));
		$leieforhold = @$_GET['leieforhold'];
		
		foreach($resultat->data as $opplysninger) {
			$opplysninger->leieforhold = $this->hent('Leieforhold', $opplysninger->leieforhold);

			$type = "";
			
			switch($opplysninger->kategori) {
			
			case 'brev':
			case 'spørsmål': {
				$type = $opplysninger->kategori
				. ( $opplysninger->henvendelse_fra
					? " {$opplysninger->henvendelse_fra}"
					: ""
				)
				. (
					$leieforhold
					? ""
					: " ang. leieforhold <a title=\"Klikk her for å se tiltak på dette leieforholdet\" href=\"index.php?oppslag=forsiden&leieforhold={$opplysninger->leieforhold}\">{$opplysninger->leieforhold}</a> {$opplysninger->leieforhold->hent('beskrivelse')}"
				);

				break;
			}

			case 'betalingsplan':
			case 'utløpsvarsel':
			case '§4.18-varsel':
			case 'forliksklage':
			case 'forliksvarsel':
			case 'inkassovarsel':
			case 'tvangsfravikelsesbegjæring':
			case 'utleggsbegjæring': {
				$type = "{$opplysninger->kategori}"
				. (
					$leieforhold
					? ""
					: " for leieforhold <a title=\"Klikk her for å se tiltak på dette leieforholdet\" href=\"index.php?oppslag=forsiden&leieforhold={$opplysninger->leieforhold}\">{$opplysninger->leieforhold}</a> {$opplysninger->leieforhold->hent('beskrivelse')}"
				);

				break;
			}

			case 'avtale':
			case 'rettslig kjennelse': {
				$type = "{$opplysninger->kategori}"
				. (
					$leieforhold
					? ""
					: " ang. leieforhold <a title=\"Klikk her for å se tiltak på dette leieforholdet\" href=\"index.php?oppslag=forsiden&leieforhold={$opplysninger->leieforhold}\">{$opplysninger->leieforhold}</a> {$opplysninger->leieforhold->hent('beskrivelse')}"
				);

				break;
			}

			case 'purring': {
				$type = "{$opplysninger->kategori}"
				. (
					$leieforhold
					? ""
					: " på leieforhold <a title=\"Klikk her for å se tiltak på dette leieforholdet\" href=\"index.php?oppslag=forsiden&leieforhold={$opplysninger->leieforhold}\">{$opplysninger->leieforhold}</a> {$opplysninger->leieforhold->hent('beskrivelse')}"
				);

				break;
			}

			default: {
				$type = ( $opplysninger->henvendelse_fra
					? "Henvendelse {$opplysninger->henvendelse_fra}"
					: (
						$opplysninger->kategori
						? "{$opplysninger->kategori}"
						: "Notat"
					)
				)
				. (
					$leieforhold
					? ""
					: " ang. leieforhold <a title=\"Klikk her for å se tiltak på dette leieforholdet\" href=\"index.php?oppslag=forsiden&leieforhold={$opplysninger->leieforhold}\">{$opplysninger->leieforhold}</a> {$opplysninger->leieforhold->hent('beskrivelse')}"
				);

				break;
			}
			}

			$opplysninger->kategori = ucfirst($type);
		}
		return json_encode($resultat);
		break;
	}
		
	case "vedlegg": {
		$this->hoveddata['where'] = "notatnr = '" . (int)$_GET['id'] . "'";
		$vedlegg = $this->mysqli->arrayData($this->hoveddata)->data[0];
		$lagerref = $vedlegg->vedlegg;
		$filnavn = $vedlegg->vedleggsnavn;

		if($lagerref) {
			$filendelse = pathinfo($filnavn, PATHINFO_EXTENSION);

			$mimeliste = array(
				'doc'	=>	'application/msword',
				'pdf'	=>	'application/pdf',
				'ods'	=>	'application/vnd.oasis.opendocument.spreadsheet',
				'odt'	=>	'application/vnd.oasis.opendocument.text',
				'docx'	=>	'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'tar'	=>	'application/x-tar',
				'zip'	=>	'application/zip',
				'jpg'	=>	'image/jpeg',
				'jpeg'	=>	'image/jpeg',
				'png'	=>	'image/png',
				'txt'	=>	'text/plain'
			);
	
			$mime = $mimeliste[$filendelse];
	
			if(function_exists('mime_content_type')) {
				$mime = mime_content_type($lagerref);
			}
			if(function_exists('finfo_open')) {
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$mime = finfo_file($finfo, $lagerref);
				finfo_close($finfo);
			}
		
			header("Content-Type: {$mime}; charset=utf-8");
			header("Content-disposition: attachment; filename=\"{$filnavn}\"");
			header('Content-Length: ' . filesize($lagerref));
			readfile($lagerref);
		}
		break;
	}
		
	default: {
	
		$leieforhold = $this->hent('Leieforhold', @$_GET['leieforhold']);
	
		if( $leieforhold->hentId() ) {
			$ikkeUtliknet = $this->mysqli->arrayData(array(
				'source' => "innbetalinger LEFT JOIN krav ON innbetalinger.krav = krav.id",
				'where' => "krav.id IS NULL and innbetalinger.leieforhold = '{$this->GET['leieforhold']}'",
				'fields' => "SUM(innbetalinger.beløp) AS beløp",
				'groupfields' => "innbetalinger.leieforhold"
			));
			$ikkeUtliknet = isset( $ikkeUtliknet->data[0]->beløp ) ? $ikkeUtliknet->data[0] : 0;
			
			$oppsigelse = $leieforhold->hent('oppsigelse');

			$html = "<table class=\"dataload\"><tr><td><p>\n";
			$html .= "Leieforholdet påbegynt {$leieforhold->hent('fradato')->format('d.m.Y')}<br />\n";

			$html .= $oppsigelse
				? ("Avsluttet: {$oppsigelse->fristillelsesdato->format('d.m.Y')}" . ($oppsigelse->oppsagtAvUtleier ? " (oppsagt av {$this->valg['utleier']})" : "") . ". Oppsigelsesreferanse: {$oppsigelse->ref}<br />" . ($oppsigelse->merknad ? "{$oppsigelse->merknad}<br />" : ""))
				: "";
			$html .= "<br />";

			$html .= "Utestående per " . date('d.m.Y') . ": {$this->kr($leieforhold->hent('utestående'))}, ";
			$html .= "herav forfalt: <b>{$this->kr($leieforhold->hent('forfalt'))}</b><br />\n";
			$html .= ($ikkeUtliknet)
				? ("Til fradrag er kr. " . number_format($ikkeUtliknet, 2, ",", " ") . " innbetalt men ikke utlikna mot udekte krav.<br />")
				: "";
			
			$html .= "</p></td><td><p>\n";
			
			if($leieforhold->hent('frosset')) {
				$html .= "<span style=\"color: blue;\">Leieavtalen er frosset sånn at det ikke vil sendes ut giroer og purringer.</span> <a style=\"cursor: pointer\" onClick=\"tin({$leieforhold})\" title=\"Klikk her for å gjenopprette normal levering av giroer og purringer.\">[Tin]</a><br />";
			}
			
			if($leieforhold->hent('stopp_oppfølging')) {
				$html .= "<span style=\"color: red;\">Oppfølging av denne leieavtalen er stoppet.</span><br />";
			}
			
			else if( $leieforhold->hent('avvent_oppfølging') > new DateTime() ) {
				$html .= "<span style=\"color: red;\">Videre oppfølgingstiltak bør avventes til etter {$leieforhold->hent('avvent_oppfølging')->format('d.m.Y')}.</span><br />";
			}
			
			else {
			
				if($leieforhold->hent('regning_til_objekt')) {
					$html .= "Purringer og henvendelser leveres på døra til {$leieforhold->hent('leieobjekt')->hent('beskrivelse')}<br />";
					$html .= ($leieforhold->hent('leieobjekt')->hent('beboere') ? "({$leieforhold->hent('leieobjekt')->hent('beboere')})<br />" : "");
				}

				else {
					$html .= "Purringer og henvendelser sendes med post til: <br>" . $leieforhold->hent('navn') . "<br>" . nl2br($leieforhold->hent('adressefelt')) . "<br />";
				}

				$html .= "<a title=\"\" href=\"../drift/index.php?oppslag=leieforhold_leveringsadresse&id={$leieforhold}\">[Endre adresseinnstillinger]</a><br />\n";
			}

			$html .= "</p></td></tr></table>\n";
		}


		else {
			$html = "";
			$pauser = $this->mysqli->arrayData(array(
				'distinct'		=> true,
				'source'		=> "kontrakter",
				'class'			=> "Leieforhold",
				'where'			=> "avvent_oppfølging > NOW()",
				'orderfields'	=> "avvent_oppfølging",
				'fields'		=> "leieforhold as id"
			))->data;
			
			$utløptePauser = $this->mysqli->arrayData(array(
				'distinct'		=> true,
				'source'		=> "kontrakter",
				'class'			=> "Leieforhold",
				'where'			=> "avvent_oppfølging IS NOT NULL and avvent_oppfølging < NOW()",
				'orderfields'	=> "avvent_oppfølging",
				'fields'		=> "leieforhold as id"
			))->data;
			
			foreach( $utløptePauser as $indeks => $leieforhold ) {
				if( $leieforhold->hent('utestående') == 0 ) {
					unset($utløptePauser[$indeks]);
				}
			}
?>

<?php if($pauser):?>
	<?php foreach( $pauser as $leieforhold ):?>
		<div style="margin-left: 4px;">
		<span style="font-size: 12px;">📅</span>
		Videre oppfølging av <a title="Klikk her for oppfølgingsnotatene for dette leieforholdet" href="index.php?oppslag=forsiden&leieforhold=<?php echo $leieforhold;?>"><?php echo $leieforhold->hent('navn');?>&#39;s leieforhold nr. <?php echo $leieforhold;?></a> avventes til etter <?php echo $leieforhold->hent('avvent_oppfølging')->format('d.m.Y');?>.
		</div>
	<?php endforeach;?>
	<div>&nbsp;</div>
<?php endif;?>
<?php foreach( $utløptePauser as $leieforhold ):?>
	<div>
	<img src="../bilder/tegnestift.png" height="15" style="margin: 1px -10px -4px 4px;">
	Oppfølging av <a title="Klikk her for oppfølgingsnotatene for dette leieforholdet" href="index.php?oppslag=forsiden&leieforhold=<?php echo $leieforhold;?>"><?php echo $leieforhold->hent('navn');?>&#39;s leieforhold nr. <?php echo $leieforhold;?></a> kan gjenopptas. <a style="cursor: pointer;" onClick="fjernVarsel(<?php echo $leieforhold;?>)">[Fjern denne påminnelsen]</a>
	</div>
<?php endforeach;?>
<?php
		}

		return $html;
		break;
	}
		
	}
}



function taimotSkjema() {
	echo json_encode($resultat);
}



function manipuler($data) {
	switch ($data){
		case "slett":
			$id = (int)$_GET['id'];
			$vedlegg = $this->mysqli->arrayData(array(
				'source' => "notater",
				'fields' => "vedlegg",
				'where' => "notater.notatnr = '$id'"
			))->data[0]->vedlegg;
			
			if(file_exists($vedlegg)) {
				unlink($vedlegg);
			}
				
			$sql =	"DELETE FROM notater WHERE notatnr = '$id'";
			if($this->mysqli->query($sql)){
				$resultat['msg'] = "Notatet har blitt slettet";
				$resultat['success'] = true;
			}
			else{
				$resultat['msg'] = "Klarte ikke slette. Meldingen fra database lyder:<br />" . $this->mysqli->error;
				$resultat['success'] = false;
			}
			echo json_encode($resultat);
			break;
			
		case "restart":
			if($this->mysqli->saveToDb(array(
				'update' => true,
				'table' => "kontrakter",
				'where' => "leieforhold = '{$this->GET['leieforhold']}'",
				'fields' => array(
					'avvent_oppfølging' => null
				)
			))->success) {
				$resultat['msg'] = "Tint";
				$resultat['success'] = true;
			}
			else{
				$resultat['msg'] = "Klarte ikke fjerne varselet. Meldingen fra database lyder:<br />" . $this->mysqli->error;
				$resultat['success'] = false;
			}
			echo json_encode($resultat);
			break;
			
		case "tin":
			if($this->mysqli->saveToDb(array(
				'update' => true,
				'table' => "kontrakter",
				'where' => "leieforhold = '{$this->GET['leieforhold']}'",
				'fields' => array(
					'frosset' => 0
				)
			))->success) {
				$resultat['msg'] = "Tint";
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



}
?>