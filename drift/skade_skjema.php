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
	if(!$id = (int)$_GET['id'] AND $_GET['id'] != '*') die("Ugyldig oppslag: ID ikke angitt for skademelding");
	$this->hoveddata = "SELECT id, CONCAT(bygning, IF(leieobjektnr, CONCAT('-', leieobjektnr), '')) AS leieobjektnr, registrerer, registrert, skade, beskrivelse, utført, sluttregistrerer, sluttrapport FROM skader WHERE id = '$id'";
}

function skript() {
	$standardobjekt = "";
	if( isset($_GET['leieobjektnr'] ) ) {
		$standardobjekt = $this->mysqli->arrayData(array(
			'source'	=> "leieobjekter",
			'where'		=> "leieobjektnr = {$this->GET['leieobjektnr']}",
			'fields'	=> "CONCAT(bygning, '-', leieobjektnr) AS standardobjekt"
		));
		if( $standardobjekt->totalRows ) {
			$standardobjekt = $standardobjekt->data[0]->standardobjekt;
		}
		else {
			$standardobjekt = "";
		}
	}
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	var leieobjektliste = new Ext.data.JsonStore({
		fields: [
			{name: 'id',		type: 'text', mapping: 'id'},
			{name: 'visning',	type: 'text', mapping: 'visning'}
		],
		root: 'data',
		sortInfo: {
 		   field: 'id',
			direction: 'ASC'
		},
		url: "index.php?oppslag=skade_skjema&id=<?=$_GET['id']?>&oppdrag=hentdata&data=leieobjekter"
	});

<?
	$kategorirad = array();
	$sql =	"SELECT kategorier.kategori, skadekategorier.kategori AS valgt\n"
		.	"FROM (\n"
		.	"SELECT kategori\n"
		.	"FROM skadekategorier\n"
		.	"GROUP BY kategori"
		.	") AS kategorier LEFT JOIN skadekategorier ON skadekategorier.kategori = kategorier.kategori AND skadekategorier.skadeid = " . (int)$_GET['id'];
	$kategorier = $this->arrayData($sql);
	
	foreach($kategorier['data'] as $linje=>$kategori){
?>
	var kategori_<?=$kategori['kategori']?> = new Ext.form.Checkbox({
		fieldLabel: '<?= $linje ? "" : "Kategori" ?>',
		boxLabel: '<?=$kategori['kategori']?>',
		name: 'kategori_<?=$kategori['kategori']?>',
		inputValue: 1,
		checked: <?= $kategori['valgt'] ? "true" : "false"?>,
		width: 200
	});

<?
		$kategorirad[] = "kategori_{$kategori['kategori']}";
	}
?>
	var leieobjektnr = new Ext.form.ComboBox({
		name: 'leieobjektnr',
		displayField: 'visning',
		hiddenName: 'leieobjektnr',
		valueField: 'id',
		allowBlank: true,
		fieldLabel: 'Bygning / bolig',
		forceSelection: true,
		maxHeight: 600,
		minChars: 1,
		mode: 'remote',
		listWidth: 500,
		selectOnFocus: true,
		store: leieobjektliste,
		triggerAction: 'all',
		typeAhead: true,
		width: 400
	});

	var skade = new Ext.form.TextField({
		fieldLabel: 'Skade',
		name: 'skade',
		width: 200
	});

	var nykategori = new Ext.form.TextField({
		fieldLabel: 'Annen kategori',
		name: 'nykategori',
		width: 200
	});

	var beskrivelse = new Ext.form.HtmlEditor({
		fieldLabel: 'Beskrivelse',
		name: 'beskrivelse',
		height: 300,
		width: 700
	});

	var skjema = new Ext.FormPanel({
		autoScroll: true,
		bodyStyle:'padding:5px 5px 0',
		buttons: [],
		frame:true,
		height: 500,
		items: [
			leieobjektnr,
			{
				layout: 'column',
				items: [
					{
						columnWidth: 0.4,
						layout: 'form',
						items: [
							skade,
							nykategori
						]
					}, {
						columnWidth: 0.6,
						layout: 'form',
						items: [<?=implode(", ", $kategorirad)?>]
					}
			]
			},
			beskrivelse
		],
		labelAlign: 'left', // evt right
		reader: new Ext.data.JsonReader({
			defaultType: 'textfield',
			fields: [
				leieobjektnr,
				skade,
				beskrivelse
		],
			root: 'data'
		}),
		standardSubmit: false,
		title: 'Skaderegistrering',
		width: 900
	});

	skjema.addButton('Avbryt', function(){
		window.location = "<?=$this->returi->get();?>";
	});
	
	var lagreknapp = skjema.addButton({
		text: 'Lagre',
		disabled: true,
		handler: function(){
			skjema.form.submit({
				url:'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET["id"]}";?>&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...'
				});
		}
	});

	leieobjektliste.load({
		callback: function( records, options, success ) {
			if(<?= isset($_GET['leieobjektnr']) ? "true" : "false" ?>) {
				leieobjektnr.setValue('<?= $standardobjekt ?>');
			}
		}
	});

	skjema.render('panel');

<?
	if($_GET['id'] != '*'){
?>
	leieobjektliste.on({
		load: function() {
			skjema.getForm().load({
				url: 'index.php?oppslag=<?="{$_GET['oppslag']}&id={$_GET["id"]}";?>&oppdrag=hentdata',
				waitMsg:'Henter opplysninger...'
			});
		}
	});
<?
	}
	else{
?>
	lagreknapp.enable();
<?
	}
?>

	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'load'){
				lagreknapp.enable();
			} 
			
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Mottok ikke bekreftelsesmelding fra tjeneren  i JSON-format som forventet');
				} else {
					window.location = "index.php?oppslag=skade_liste<?=isset($_GET['leieobjektnr']) ? "&id={$_GET['leieobjektnr']}" : ""?>";
					Ext.MessageBox.alert('Suksess', 'Opplysningene er oppdatert');
				}
			}
		},
							
		actionfailed: function(form,action){
			if(action.type == 'load') {
				if (action.failureType == "connect") { 
					Ext.MessageBox.alert('Problem:', 'Klarte ikke laste data. Fikk ikke kontakt med tjeneren.');
				}
				else {
					if (action.response.responseText == '') {
						Ext.MessageBox.alert('Problem:', 'Skjemaet mottok ikke data i JSON-format som forventet');
					}
					else {
						var result = Ext.decode(action.response.responseText);
						if(result && result.msg) {			
							Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', result.msg);
						}
						else {
							Ext.MessageBox.alert('Problem:', 'Innhenting av data mislyktes av ukjent grunn. (trolig manglende success-parameter i den returnerte datapakken). Action type='+action.type+', failure type='+action.failureType);
						}
					}
				}
			}
			if(action.type == 'submit') {
				if (action.failureType == "connect") {
					Ext.MessageBox.alert('Problem:', 'Klarte ikke lagre data. Fikk ikke kontakt med tjeneren.');
				}
				else {	
					var result = Ext.decode(action.response.responseText); 
					if(result && result.msg) {			
						Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', result.msg);
					}
					else {
						Ext.MessageBox.alert('Problem:', 'Lagring av data mislyktes av ukjent grunn. Action type='+action.type+', failure type='+action.failureType);
					}
				}
			}
			
		} // end actionfailed listener
	}); // end skjema.on

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
		case "leieobjekter":
			$resultat['data'] = array();
			if( isset( $_POST['query'] ) ) {
				$filter =	"WHERE leieobjekter.navn LIKE '%" . $_POST['query'] . "%'\n"
				.	"OR bygninger.navn LIKE '%" . $_POST['query'] . "%'\n"
				.	"OR gateadresse LIKE '%" . $_POST['query'] . "%'\n"
				.	"OR leieobjektnr LIKE '" . (int)$_POST['query'] . "'\n";
			}
			else {
				$filter = "";
			}
				
			$sql =	"SELECT\n"
				.	"bygninger.id, concat(bygninger.navn, \" generelt\") AS visning\n"
				.	"FROM bygninger LEFT JOIN (\n"
				.	"leieobjekter LEFT JOIN utdelingsorden ON leieobjekter.leieobjektnr = utdelingsorden.leieobjekt AND utdelingsorden.rute = '{$this->valg['utdelingsrute']}'\n"
				.	") ON leieobjekter.bygning = bygninger.id\n"
				.	$filter
				.	"GROUP BY bygninger.id\n"
				.	"ORDER BY MAX(utdelingsorden.plassering)\n";
			$bygninger = $this->arrayData($sql);
			
			foreach($bygninger['data'] as $linje => $d) {
				$sql =	"SELECT\n"
					.	"CONCAT(bygning, '-', leieobjektnr) AS id\n"
					.	"FROM leieobjekter INNER JOIN bygninger ON leieobjekter.bygning = bygninger.id\n"
					.	$filter
					.	(isset($_POST['query']) ? "AND\n" : "WHERE\n") . "leieobjekter.bygning = '{$d['id']}'\n"
					.	"ORDER BY leieobjektnr\n";
				$leiligheter = $this->arrayData($sql);
				
				$resultat['data'][] = $d;
				
				foreach($leiligheter['data'] AS $leilighet) {
					$leilighet['visning'] = " - - - " . $this->leieobjekt(substr(strstr($leilighet['id'], '-'), 1), true);
					$resultat['data'][] = $leilighet;
				}
			}
			
			$resultat['success'] = true;
			return json_encode($resultat);
			break;
		default:
			return json_encode($this->arrayData($this->hoveddata));
	}
}

function taimotSkjema() {
	if($_GET['id'] =='*'){
		$sql =	"INSERT INTO skader\n"
			.	"SET bygning = '" . (int)$_POST['leieobjektnr'] . "',\n"
			.	"leieobjektnr = '" . (substr(strstr($_POST['leieobjektnr'], '-'), 1)) . "',\n"
			.	"registrerer = '{$this->bruker['navn']}',\n"
			.	"skade = '{$this->POST['skade']}',\n"
			.	"beskrivelse = '{$this->POST['beskrivelse']}'\n";
	}
	else{
		$sql =	"UPDATE skader\n"
			.	"SET bygning = '" . (int)$_POST['leieobjektnr'] . "',\n"
			.	"leieobjektnr = '" . substr(strstr($_POST['leieobjektnr'], '-'), 1) . "',\n"
			.	"skade = '{$this->POST['skade']}',\n"
			.	"beskrivelse = '{$this->POST['beskrivelse']}'\n"
			.	"WHERE id = " . (int)$_GET['id'];
	}
	
	if($resultat['success'] = $this->mysqli->query($sql)){
		if($_GET['id'] =='*'){
			$resultat['post'] = $this->mysqli->insert_id;
		}
		else{
			$resultat['post'] = (int)$_GET['id'];
		}
		
		$resultat['msg'] = "";
		$sql =	"SELECT kategori\n"
			.	"FROM skadekategorier\n"
			.	"GROUP BY kategori";
		$kategorier = $this->arrayData($sql);
		
		foreach($kategorier['data'] as $kategori){
			if(isset($_POST["kategori_{$kategori['kategori']}"])) {
				$sql =	"SELECT * FROM skadekategorier WHERE skadeid = {$resultat['post']} AND kategori = '{$kategori['kategori']}'";
				$alleredekategorisert = $this->arrayData($sql);
				if(!count($alleredekategorisert['data'])){
					$this->mysqli->query("INSERT INTO skadekategorier SET skadeid = {$resultat['post']}, kategori = '{$kategori['kategori']}'");
				}
			}
			else{
				$this->mysqli->query("DELETE FROM skadekategorier WHERE skadeid = {$resultat['post']} AND kategori = '{$kategori['kategori']}'");
			}
		}
		if($_POST['nykategori']){
			$sql =	"SELECT * FROM skadekategorier WHERE skadeid = {$resultat['post']} AND kategori = '{$this->POST['nykategori']}'";
			$alleredekategorisert = $this->arrayData($sql);
			if(!count($alleredekategorisert['data'])){
				$this->mysqli->query("INSERT INTO skadekategorier SET skadeid = {$resultat['post']}, kategori = '{$this->POST['nykategori']}'");
			}
		}
	}
	else
		$resultat['msg'] = "KLarte ikke å lagre. Feilmeldingen fra databasen lyder:<br />" . $this->mysqli->error;
	
	echo json_encode($resultat);
}

}
?>