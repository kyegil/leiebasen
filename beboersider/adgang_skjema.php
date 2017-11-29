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

	if(!$this->adgang($this->område['område'] = $this->katalog(__FILE__))) die("Nektet adgang");
	$this->hoveddata = "SELECT MAX(egen) AS egen, leieforhold, MAX(adgang) AS adgang\n"
		.	"FROM (\n"
		.	"	SELECT 1 AS egen, kontrakter.leieforhold, adganger.leieforhold AS adgang\n"
		.	"	FROM (kontraktpersoner INNER JOIN kontrakter ON kontraktpersoner.kontrakt = kontrakter.kontraktnr)\n"
		.	"	LEFT JOIN adganger ON (kontraktpersoner.person = adganger.personid AND kontrakter.leieforhold = adganger.leieforhold)\n"
		.	"	WHERE kontraktpersoner.person = {$this->bruker['id']}\n"
		.	"	GROUP BY leieforhold\n"
		.	"	UNION\n"
		.	"	SELECT 0 AS egen, leieforhold, leieforhold AS adgang\n"
		.	"	FROM adganger\n"
		.	"	WHERE adgang = 'beboersider'\n"
		.	"	AND personid = {$this->bruker['id']}\n"
		.	") AS tabell\n"
		.	"GROUP BY leieforhold";
}

function skript() {
	$leieforhold = $this->arrayData($this->hoveddata);
	$adgangsliste = array();
	$leieforholdliste = array();
?>

Ext.onReady(function() {
	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';
<?
	include_once("_menyskript.php");

	foreach($leieforhold['data'] as $boks){
		echo "\tvar leieforhold_{$boks['leieforhold']} = new Ext.form.Checkbox({\n";
		echo "\t\tfieldLabel: 'Leieforhold {$boks['leieforhold']}',\n";
		echo "\t\tboxLabel: '" . $this->liste($this->kontraktpersoner($this->sistekontrakt($boks['leieforhold']))) . "s leieforhold i " . $this->leieobjekt($this->kontraktobjekt($boks['leieforhold']), true) . "',\n";
		echo "\t\tname: 'leieforhold_{$boks['leieforhold']}',\n";
		echo "\t\tchecked: " . ($boks['adgang'] ? "true" : "false") . ",\n";
		echo "\t\tinputValue: 1\n";
		echo "\t});\n\n";
		$leieforholdliste[] = "leieforhold_{$boks['leieforhold']}";
	}
	
	$sql =	"SELECT kontrakter.leieforhold\n"
		.	"FROM kontrakter INNER JOIN kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt\n"
		.	"WHERE person = {$this->bruker['id']}\n"
		.	"GROUP BY leieforhold";
	$a = $this->arrayData($sql);

	foreach($a['data'] as $b){
		$sql =	"SELECT adganger.personid, MAX(kontraktpersoner.person) AS eier\n"
			.	"FROM adganger\n"
			.	"LEFT JOIN (kontrakter INNER JOIN kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt) ON adganger.leieforhold = kontrakter.leieforhold AND adganger.personid = kontraktpersoner.person\n"
			.	"WHERE adgang = 'beboersider'\n"
			.	"AND adganger.leieforhold = {$b['leieforhold']}\n"
			.	"AND personid != {$this->bruker['id']}\n"
			.	"GROUP BY adganger.personid";
		$adganger = $this->arrayData($sql);

		foreach($adganger['data'] as $boks){
			echo "\tvar adgang{$b['leieforhold']}_{$boks['personid']} = new Ext.form.Checkbox({\n";
			echo "\t\tfieldLabel: 'Leieforhold {$b['leieforhold']}',\n";
			echo "\t\tboxLabel: '" . $this->navn($boks['personid']) . "',\n";
			echo "\t\tname: 'adgang{$b['leieforhold']}_{$boks['personid']}',\n";
			echo "\t\tdisabled: " . ($boks['eier'] ? "true" : "false") . ",\n";
			echo "\t\tchecked: true,\n";
			echo "\t\tinputValue: 1\n";
			echo "\t});\n\n";
			$adgangsliste[] = "adgang{$b['leieforhold']}_{$boks['personid']}";
		}
	}
	
	
?>
	var skjema = new Ext.FormPanel({
		autoScroll: true,
		bodyStyle:'padding:5px 5px 0',
		buttons: [],
		frame:true,
		height: 500,
		items: [
			{
				html: '<h2>Leieforhold du selv skal ha adgang til på beboersidene:</h2>'
			},
			<?=implode(", ", $leieforholdliste) . (count($leieforholdliste) ? ",\n" : "\n");?>
			{
				html: '<br /><h2>Andre som også har adgang til dine leieforhold via sine egne beboersider:</h2>'
			},
			<?=implode(", ", $adgangsliste) . (count($adgangsliste) ? ",\n" : "\n");?>
			{
				html: ''
			}
		],
		labelAlign: 'top', // evt right
		standardSubmit: false,
		title: '',
		width: 900
	});

	skjema.addButton('Gi andre adgang til dine beboersider', function(){
		window.location = "index.php?oppslag=adgang_opprett";
	});
	
	skjema.addButton('Avbryt', function(){
		window.location = "<?=$this->returi->get();?>";
	});
	
	var lagreknapp = skjema.addButton({
		text: 'Lagre endringer',
		disabled: false,
		handler: function(){
			skjema.form.submit({
				url:'index.php?oppslag=adgang_skjema&oppdrag=taimotskjema',
				waitMsg:'Prøver å lagre...'
				});
		}
	});

	skjema.render('skjema');

	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Mottok ikke bekreftelsesmelding fra tjeneren  i JSON-format som forventet');
				} else {
					window.location = "index.php?oppslag=adgang_skjema";
					Ext.MessageBox.alert('Fullført', action.result.msg);
				}
			}
		},
							
		actionfailed: function(form,action){
			if(action.type == 'submit') {
				if (action.failureType == "connect") {
					Ext.MessageBox.alert('Problem:', 'Klarte ikke lagre data. Fikk ikke kontakt med tjeneren.');
				}
				else {	
					var result = Ext.decode(action.response.responseText);
					if(result && result.msg) {			
						Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', action.result.msg);
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
<div id="skjema"></div>
<?
}

function taimotSkjema() {
	$resultat['msg'] = "";
	$leieforholdsett = $this->arrayData($this->hoveddata);
	
	foreach($leieforholdsett['data'] as $leieforhold){
		if($_POST["leieforhold_{$leieforhold['leieforhold']}"] and !$leieforhold['adgang']){
			$sql = 	"INSERT INTO adganger\n"
				.	"SET personid = {$this->bruker['id']},\n"
				.	"adgang = 'beboersider',\n"
				.	"leieforhold = {$leieforhold['leieforhold']},\n"
				.	"epostvarsling = 1, innbetalingsbekreftelse = 1, forfallsvarsel = 1";
			if($this->mysqli->query($sql))
				$resultat['msg'] .= "Du har fått adgang til leieforhold {$leieforhold['leieforhold']}.<br />";
		}
		else if(!$_POST["leieforhold_{$leieforhold['leieforhold']}"] and $leieforhold['adgang']){
			$sql =	"DELETE\n"
				.	"FROM adganger\n"
				.	"WHERE adgang = 'beboersider'\n"
				.	"AND personid = {$this->bruker['id']}\n"
				.	"AND leieforhold = {$leieforhold['leieforhold']}";
			if($this->mysqli->query($sql))
				$resultat['msg'] .= "Din adgang til leieforhold {$leieforhold['leieforhold']} er slettet.<br />";
		}
	}

	$sql =	"SELECT kontrakter.leieforhold\n"
		.	"FROM kontrakter INNER JOIN kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt\n"
		.	"WHERE person = {$this->bruker['id']}\n"
		.	"GROUP BY leieforhold";
	$leieforholdsett = $this->arrayData($sql);

	foreach($leieforholdsett['data'] as $leieforhold){
		$sql =	"SELECT adganger.personid, MAX(kontraktpersoner.person) AS eier\n"
			.	"FROM adganger\n"
			.	"LEFT JOIN (kontrakter INNER JOIN kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt) ON adganger.leieforhold = kontrakter.leieforhold AND adganger.personid = kontraktpersoner.person\n"
			.	"WHERE adgang = 'beboersider'\n"
			.	"AND adganger.leieforhold = {$leieforhold['leieforhold']}\n"
			.	"AND personid != {$this->bruker['id']}\n"
			.	"GROUP BY adganger.personid";
		$adganger = $this->arrayData($sql);

		foreach($adganger['data'] as $adgang){
			if(!$_POST["adgang{$leieforhold['leieforhold']}_{$adgang['personid']}"] and !$adgang['eier']){
				$sql =	"DELETE\n"
					.	"FROM adganger\n"
					.	"WHERE adgang = 'beboersider'\n"
					.	"AND personid = {$adgang['personid']}\n"
					.	"AND leieforhold = {$leieforhold['leieforhold']}";
				if($this->mysqli->query($sql))
					$resultat['msg'] .= $this->navn($adgang['personid']) . "s adgang til leieforhold {$leieforhold['leieforhold']} er slettet.<br />";
			}
		}
	}
	
	if(!$resultat['msg'])
		$resultat['msg'] = "Ingen endringer foretatt";
	
	$resultat['success'] = true;
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		default:
			return json_encode($this->arrayData($this->hoveddata));
	}
}

}
?>