<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Registrer nytt leieforhold - Oppsummering';
public $ext_bibliotek = 'ext-4.2.1.883';


function __construct() {
	parent::__construct();

	if(!isset($_POST['leieobjekt']) && !isset($_GET['oppdrag'])) {
		header('Location: index.php?oppslag=leieforhold_opprett-1');
	}	
}

function skript() {
	$tp = $this->mysqli->table_prefix;
	$leieobjekt	= $this->hent( 'Leieobjekt', (int)$_POST['leieobjekt'] );
	$html		=	"";
	$leietakere	= array();
	$andel		= $this->fraBrøk($_POST['andel']);
	$fradato	= new DateTime($_POST['fradato']);
	$tildato	= @$_POST['tildato'] ? new DateTime($_POST['tildato']) : null;
	$årsleie	= $_POST['leiebeløp'];
	$basisleie	= $_POST['leiebeløp'];
	$antallTerminer	= (int)@$_POST['ant_terminer'] ? (int)$_POST['ant_terminer'] : 1;
	$gang		= ($antallTerminer - 1) ? "ganger" : "gang";
	$terminbeløp	= round( bcdiv($årsleie, $antallTerminer, 1) );
	$avtalemal	= @$this->mysqli->arrayData(array(
		'source'	=> "{$tp}avtalemaler AS avtalemaler",
		'fields'	=> "malnavn",
		'where'		=> "malnr = " . (int)$_POST['avtalemal']
	))->data[0]->malnavn;
	
	$alleDelkravtyper	= $this->mysqli->arrayData(array(
		'source'	=> "{$tp}delkravtyper AS delkravtyper",
		'fields'	=> "id, navn, relativ, sats, selvstendig_tillegg",
		'where'		=> "aktiv and kravtype = 'Husleie'",
		'orderfields'	=> "orden"
	))->data;
	$delkravtyper = array();
	$selvstendigeTillegg = array();
	
	
	// Basisleia, dvs husleie før delkrav, beregnes.
	//	Vi gjennomgår alle delkravene i angitt orden, og trekker fra delbeløpene
	//	inntil alle er trukket fra eller det ikke er noe igjen:
	//	Alle fastbeløp trekkes direkte, og alle forbigåtte prosentvise satser fordeles etterpå.
	//	Formel: netto = (brutto - del1 - del3) / (1 + sats2 + sats4)
	$nevner = 1;
	foreach($alleDelkravtyper as $delkravtype) {
		$angittSats = str_replace(",", ".", @$_POST["delkrav{$delkravtype->id}"]);
	
		if( $angittSats and $delkravtype->selvstendig_tillegg ) {
		
			// Dersom delkravtypen er relativ formateres satsen som en faktor.
			if($delkravtype->relativ) {
				$delkravtype->sats = bcdiv( $angittSats, 100, 4 );
			}
			
			// Dersom delkravtypen ikke er relativ forblir den som den er
			else {
				$delkravtype->beløp = $angittSats;
			}
			$selvstendigeTillegg[] = $delkravtype;
		}

		// Bruk bare de delkravtypene som er angitt på skjemaet,
		//	og bare så lenge det er noe igjen av grunnbeløpet
		else if( $angittSats and $basisleie > 0) {
		
			// Dersom delkravtypen er relativ formateres satsen som en faktor.
			// Alle de relative satsene legges sammen til en nevner som brukes for å beregne basisbeløpet
			if($delkravtype->relativ) {
				$delkravtype->sats = bcdiv( $angittSats, 100, 4 );
				$nevner = bcadd(
					$nevner,
					$delkravtype->sats,
					3
				);
			}
			
			// Dersom delkravtypen ikke er relativ trekkes delbeløpet direkte ifra bruttoleia,
			//	men bare så lenge det er noe igjen av basisleia.
			else {
				$delkravtype->sats = min($basisleie, $angittSats);
				$delkravtype->beløp = $delkravtype->sats;
				$basisleie = max(0, bcsub($basisleie, $angittSats));
			}
			$delkravtyper[] = $delkravtype;
		}
	}
	
	$basisleie = round( bcdiv( $basisleie, $nevner, 2 ));
	
	foreach($delkravtyper as $delkravtype) {
		if( $delkravtype->relativ ) {
			$delkravtype->beløp = round(bcmul($basisleie, $delkravtype->sats, 2));
		}
	}
	//	Basisleia er ferdig beregnet
	
	
	$a = 1;
	while(isset($_POST["etternavn$a"])) {
		if(isset($_POST["personkombo$a"])) {
			$leietakere[$a] = $this->hent( 'Person', (int)$_POST["personkombo$a"] )->hent('navn');
		}
		else if (isset($_POST["adressekort$a"])) {
			$leietakere[$a] = $this->hent( 'Person', (int)$_POST["adressekort$a"] )->hent('navn');
		}
		
		if( !$leietakere[$a] ) {
			$leietakere[$a]	= @$_POST["er_org$a"]
							? $_POST["etternavn$a"]
							: ($_POST["fornavn$a"] . " " . $_POST["etternavn$a"]);
		}
		
		$a++;
	}
	
	
	$html =	"<div>"
		.	"<strong>Kontroller at opplysningene er korrekte før leieavtale og evt nye adressekort opprettes:</strong><br /><br />"
		.	"<div><strong>Leieobjektet:</strong><br />"
		.	"Leieobjekt nr. <strong>{$leieobjekt}</strong><br />"
		.	$leieobjekt->hent('beskrivelse') . "<br /><br />"
		.	"<strong>" . (($andel == 1) ? "Hele " : ($this->tilBrøk($andel) . " av ")) . "leieobjektet</strong> omfattes av denne leieavtalen.<br /><br /></div>"

		.	"<div>Leietakere:<br /><strong> {$this->liste($leietakere)}</strong><br /><br /></div>"

		.	"<div>Fra: <strong>{$fradato->format('d.m.Y')}</strong><br />"
		.	($tildato ? "Til: <strong>{$tildato->format('d.m.Y')}</strong><br />" : "")
		.	"</div>"

		.	"<div>Oppsigelsestid: <strong>" . $this->oppsigelsestidrenderer($_POST['oppsigelsestid']) . "</strong><br /><br /></div>"

		.	"<div><strong>Leie:</strong><br />Årlig leie {$this->kr($årsleie, true)}" . "<br />(betales som {$this->kr($terminbeløp, true)} {$antallTerminer} {$gang} i året.)<br /><br /></div>"

		.	"<div><strong>Leiebeløpet består av:</strong><br />Basisleie {$this->kr($basisleie, true)}" . "<br />";

		foreach($delkravtyper as $delkravtype) {
			$html .= "{$delkravtype->navn}"
			. (
				$delkravtype->relativ
				? (" (" . $this->prosent($delkravtype->sats, 1) . " av basisleia)")
				: ""
			)
			.  ": " . $this->kr($delkravtype->beløp, true) . "<br />";
		}
		
		if($selvstendigeTillegg) {
			$html .= "<br /><div><strong>Faste tillegg til husleia:</strong><br />";
			
			foreach($selvstendigeTillegg as $delkravtype) {
				$html .= "{$delkravtype->navn}"
				. (
					$delkravtype->relativ
					? (" (" . $this->prosent($delkravtype->sats, 1) . " av basisleia)")
					: ""
				)
				.  ": " . $this->kr($delkravtype->beløp, true) . "<br />";
			}
			$html .= "</div>";
		}


		$html .=	"<br /></div>"

		.	"<div>Mal for leieavtale: <strong>" . ($avtalemal ? $avtalemal : "<i>Ingen mal skal brukes</i>") . "</strong></div>"
		.	"</div>";

?>

Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?php echo $this->http_host . "/" . $this->ext_bibliotek . "/examples/ux"?>');

Ext.require([
	'Ext.data.*',
	'Ext.form.*'
]);

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	var skjema = Ext.create('Ext.form.Panel', {
		renderTo:		'panel',
		frame:			true,
		title:			'Kontroller opplysningene',
		bodyStyle:		'padding:5px 5px 0',
		standardSubmit:	false,
		autoScroll:		true,
		width:			900,
		height:			500,
		items: [
		
<?php //	Alle mottatte POST-verdier videresendes som skjulte felter ?>
<?php foreach($_POST as $attributt => $verdi):?>

			{
				xtype: 'hidden',
				name: '<?php echo $attributt;?>',
				value: '<?php echo addslashes($verdi);?>'
			},

<?php endforeach;?>

			{
			xtype: 'displayfield',
			value:	'<?php echo addslashes( $html );?>'
			}
		],
		buttons: [{
			scale: 'medium',
			text: 'Tilbake',
			handler: function() {
				if( skjema.isValid() ) {
					skjema.getForm().doAction('standardsubmit', {
						url: 'index.php?oppslag=leieforhold_opprett-3_adressekort'
					});
				}
			}
		}, {
			scale: 'medium',
			text: 'Avbryt',
			handler: function() {
				window.location = '<?php echo $this->returi->get();?>';
			}
		}, {
			scale: 'medium',
			text: 'Opprett leieavtalen',
			handler: function() {
				if( skjema.isValid() ) {
					skjema.getForm().submit({
						waitMsg: 'Oppretter leieavtale..',
						url: 'index.php?oppslag=leieforhold_opprett-4_oppsummering&oppdrag=taimotskjema'
					});
				}
			}
		}]
	});
	
	skjema.on({
		actioncomplete: function(form, action){
			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Ingen JSON');
				} else {
					Ext.MessageBox.alert('Vellykket', action.result.msg, function() {
						window.location = 'index.php?oppslag=leieforhold_adressekort&id=' + action.result.id;
					});
				}
			}
		},
							

		actionfailed: function(form,action){
			if(action.type == 'submit') {
				var result = Ext.decode(action.response.responseText); 
				if(result && result.msg) {			
					Ext.MessageBox.alert('Mottatt tilbakemelding om feil:', result.msg, function(){
						window.location = '<?php echo $this->returi->get();?>';
					});
				}
				else {
					Ext.MessageBox.alert('Problem:', 'Lagring av data mislyktes av ukjent grunn. Action type='+action.type+', failure type='+action.failureType);
				}
			}
			
		} // end actionfailed listener
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

	$resultat = (object)array(
		'success'	=> false,
		'msg'		=> "",
		'data'		=> array()
	);


	switch ($data) {
	
	default:
		return json_encode($this->arrayData($this->hoveddata));
		
	}
}



function taimotSkjema() {
	$tp = $this->mysqli->table_prefix;

	$resultat = (object)array(
		'success'	=> false,
		'msg'		=> "",
		'data'		=> array(),
		'id'		=> 0
	);

	$leieobjekt		= $this->hent( 'Leieobjekt', (int)$_POST['leieobjekt'] );
	$fradato		= new DateTime($_POST['fradato']);
	$tildato		= @$_POST['tildato'] ? new DateTime($_POST['tildato']) : null;
	$utleie			= $leieobjekt->hentUtleie($fradato, $tildato);
	$andel			= $this->fraBrøk($_POST['andel']);
	$leietakere		= array();
	$basisleie		= $_POST['leiebeløp'];
	$årsleie		= $_POST['leiebeløp'];
	$antallTerminer	= (int)@$_POST['ant_terminer'] ? (int)$_POST['ant_terminer'] : 1;
	
	$kontrakttekst	= @$this->mysqli->arrayData(array(
		'source'	=> "{$tp}avtalemaler AS avtalemaler",
		'fields'	=> "mal",
		'where'		=> "malnr = '" . (int)@$_POST['avtalemal'] . "'"
	))->data[0]->mal;



	// Sjekk at engangspoletten er gyldig
	if(!$this->brukPolett($_POST['polett'])) {
		
		echo json_encode(array(
			'success'	=>	false,
			'msg'		=> "Kunne ikke opprette denne leieavtalen fordi engangspoletten enten er brukt eller for gammel.<br /><br />Dette kan komme av at du allerede har opprettet denne leieavtalen (for deretter ved en feil ha klikket deg inn på nettsiden på nytt), eller at du har brukt mer enn et døgn på å opprette den. Du kan evt. forsøke en gang til."
		));
		return;
	}
	
	// Sjekk at leieobjektet eksisterer
	if(!$leieobjekt->hent('id')) {
		echo json_encode(array(
			'success'	=>	false,
			'msg'		=> "Leieobjektet eksisterer ikke."
		));
		return;
	}
	
	// Sjekk at til-dato er etter fra-dato
	if($tildato and $tildato <= $fradato) {
		echo json_encode(array(
			'success'	=>	false,
			'msg'		=> "Til-dato må være etter fra-dato."
		));
		return;
	}
	
	// Sjekk at andelen er oppgitt
	if(!$andel) {
		echo json_encode(array(
			'success'	=>	false,
			'msg'		=> "Andel av leieobjektet som leies er ikke oppgitt."
		));
		return;
	}

	// Sjekk at leieobjektet er ledig på det aktuelle tidspunktet
	if( round($andel, 4) > round($utleie->ledig, 4) ) {
		echo json_encode(array(
			'success'	=>	false,
			'msg'		=> "Leieobjektet er ikke ledig for utleie på det aktuelle tidspunktet."
		));
		return;
	}
	
	
	$alleDelkravtyper	= $this->mysqli->arrayData(array(
		'source'	=> "{$tp}delkravtyper AS delkravtyper",
		'fields'	=> "id, navn, relativ, sats, selvstendig_tillegg",
		'where'		=> "aktiv and kravtype = 'Husleie'",
		'orderfields'	=> "orden"
	))->data;
	$delkravtyper = array();
	$selvstendigeTillegg = array();
	
	
	// Basisleia, dvs husleie før delkrav, beregnes.
	//	Vi gjennomgår alle delkravene i angitt orden, og trekker fra delbeløpene
	//	inntil alle er trukket fra eller det ikke er noe igjen:
	//	Alle fastbeløp trekkes direkte, og alle forbigåtte prosentvise satser fordeles etterpå.
	//	Formel: netto = (brutto - del1 - del3) / (1 + sats2 + sats4)
	$nevner = 1;
	foreach($alleDelkravtyper as $delkravtype) {
		$angittSats = str_replace(",", ".", @$_POST["delkrav{$delkravtype->id}"]);


		// Selvstendige tillegg:
		// Selvstendige tillegg puttes i en egen beholder; $selvstendigeTillegg
		if( ($angittSats != 0) and $delkravtype->selvstendig_tillegg ) {
		
			// Dersom delkravtypen er relativ formateres satsen som en faktor.
			if($delkravtype->relativ) {
				$delkravtype->sats = bcdiv( $angittSats, 100, 4 );
			}
			
			// Dersom delkravtypen ikke er relativ forblir den som den er
			else {
				$delkravtype->sats = $angittSats;
			}
			$selvstendigeTillegg[] = $delkravtype;
		}

		// Delkrav
		// Bruk bare de delkravtypene som er angitt på skjemaet,
		//	og bare så lenge det er noe igjen av grunnbeløpet
		else if( $angittSats and $basisleie > 0) {
		
			// Dersom delkravtypen er relativ formateres satsen som en faktor.
			// Alle de relative satsene legges sammen til en nevner som brukes for å beregne basisbeløpet
			if($delkravtype->relativ) {
				$delkravtype->sats = bcdiv( $angittSats, 100, 4 );
				$nevner = bcadd(
					$nevner,
					$delkravtype->sats,
					3
				);
			}
			
			// Dersom delkravtypen ikke er relativ trekkes delbeløpet direkte ifra bruttoleia,
			//	men bare så lenge det er noe igjen av basisleia.
			else {
				$delkravtype->sats = min($basisleie, $angittSats);
				$delkravtype->beløp = $delkravtype->sats;
				$basisleie = max(0, bcsub($basisleie, $angittSats));
			}
			$delkravtyper[] = $delkravtype;
		}
	}
	
	$basisleie = round( bcdiv( $basisleie, $nevner, 2 ));
	
	foreach($delkravtyper as $delkravtype) {
		if( $delkravtype->relativ ) {
			$delkravtype->beløp = round(bcmul($basisleie, $delkravtype->sats, 2));
		}
	}
	//	Basisleia er ferdig beregnet
	
	
	// Hent eller opprett adressekortene	
	$a = 1;
	while(isset($_POST["etternavn$a"])) {
		if(@$_POST["personkombo$a"]) {
			$leietakere[$a] = $this->hent( 'Person', (int)$_POST["personkombo$a"] );
		}
		else if (@$_POST["adressekort$a"]) {
			$leietakere[$a] = $this->hent( 'Person', (int)$_POST["adressekort$a"] );
		}
		
		if( !isset($leietakere[$a]) or !$leietakere[$a]->hentId() ) {
			$leietakere[$a]	= $this->opprett('Person', array(
				'org'		=> @$_POST["er_org$a"],
				'fornavn'	=> @$_POST["fornavn$a"],
				'etternavn'	=> @$_POST["etternavn$a"],
				'navn'		=> @$_POST["etternavn$a"],
				'adresse1'	=> $leieobjekt->hent('navn')
								? $leieobjekt->hent('navn')
								: $leieobjekt->hent('gateadresse'),
				'adresse2'	=> $leieobjekt->hent('navn')
								? $leieobjekt->hent('gateadresse')
								: "",
				'postnr'	=> $leieobjekt->hent('postnr'),
				'poststed'	=> $leieobjekt->hent('poststed')
			));
		}
		
		$a++;
	}


	// Opprett leieforholdet
	$regningsperson = reset($leietakere);
	$leieforhold = $this->opprett('Leieforhold', array(
		'leieobjekt'			=> $leieobjekt,
		'leietakere'			=> $leietakere,
		'andel'					=> $_POST['andel'],
		'fradato'				=> $fradato,
		'tildato'				=> $tildato,
		'oppsigelsestid'		=> $_POST['oppsigelsestid'],
		'ant_terminer'			=> $_POST['ant_terminer'],
		'årlig_basisleie'		=> $basisleie,
		'tekst'					=> $kontrakttekst,
		'regningsperson'		=> $regningsperson,
		'regning_til_objekt'	=> true,
		'regningsobjekt'		=> $leieobjekt
	));
	
	if( !$leieforhold ) {
		echo json_encode(array(
			'success'	=> false,
			'msg'		=> "Kunne ikke opprette leieobjektet av ukjent grunn."
		));
		return;		
	}
	
	$resultat->data 	= $leieforhold;
	$resultat->id		= (string)$leieforhold;
	$resultat->success	= true;
	
	foreach($delkravtyper as $delkravtype) {
		$leieforhold->leggTilDelkravtype(
			$delkravtype->id,
			$delkravtype->relativ,
			$delkravtype->sats,
			false
		);
	}
		
	foreach($selvstendigeTillegg as $delkravtype) {
		$leieforhold->leggTilDelkravtype(
			$delkravtype->id,
			$delkravtype->relativ,
			$delkravtype->sats,
			true
		);
	}
		
	// Dersom regningspersonen også har tidligere har vært leietaker,
	//	oppdateres leveringsadresse for disse leieforholdene.
	foreach( $this->mysqli->arrayData(array(
		'source'	=> "{$tp}kontrakter as kontrakter INNER JOIN {$tp}kontraktpersoner AS kontraktpersoner ON kontrakter.kontraktnr = kontraktpersoner.kontrakt AND kontraktpersoner.slettet IS NULL",
		'where'		=> "kontrakter.regningsperson = '" . implode("' OR kontrakter.regningsperson = '", $leietakere) . "'",
		'fields'	=> "leieforhold as id",
		'distinct'	=> true,
		'class'		=> "Leieforhold"
	))->data as $tidligereLeieforhold)
	{
		$tidligereLeieforhold->sett( 'regningsobjekt', $leieobjekt);
		$tidligereLeieforhold->sett( 'regning_til_objekt', true);
		$tidligereLeieforhold->sett( 'frosset', false );
	}	


	// Så opprettes husleiekrav for denne leieavtalen.
	$leie = $leieforhold->opprettLeiekrav($fradato, false, @$_POST['ukedag'], @$_POST['dag_i_måneden'], @$fast_dato);
	
	if( !$leie ) {
		$resultat->msg .= "<br />!!! OBS!!!<br />Det oppstod problemer med å opprette terminforfall for leieperioden.<br />";
	}
	else {
		$resultat->msg .= "<br />Det er opprettet terminer med forfallsdatoer.<br /><br />";
		
		foreach($leie as $krav) {
			while(
				$grad = $leieobjekt->hentLeiekrav($krav->hent('fom'), $krav->hent('tom') )->grad > 1.0001
				&& 
				$oppsigelsestid = $leieobjekt->hentLeiekrav($krav->hent('fom'), $krav->hent('tom') )->oppsigelsestid
			) {
				$kravForSletting = reset( $oppsigelsestid );
				$beskrivelse = $kravForSletting->hent('leieforhold')->hent('navn') . " sin leie for " . $kravForSletting->hent('termin');
				if( $kravForSletting->slett() ) {
					$resultat->msg .= "{$beskrivelse} har blitt sletta.<br />";
				}
				else {
					$resultat->msg .= "Det oppstod problemer med å slette {$beskrivelse}. Denne må muligens slettes manuelt for å unngå dobbeltfakturering.<br />";
				}
			}
		}
		
	}
	
	$resultat->msg .= "&nbsp;<br />";
	
	// Angi videre adressesti (må oppgis i omvendt rekkefølge)
 	$this->returi->set("{$this->http_host}/drift/index.php?oppslag=leieforholdkort&id={$leieforhold}");
 	$this->returi->set("{$this->http_host}/drift/index.php?oppslag=kontrakt_tekst&id={$leieforhold->hent('kontraktnr')}");
 	$this->returi->set("{$this->http_host}/drift/index.php?oppslag=leieforhold_leveringsadresse&id={$leieforhold}");
 	$this->returi->set("{$this->http_host}/drift/index.php?oppslag=leieforhold_nettprofiler&id={$leieforhold}");

	
	echo json_encode($resultat);
	return;
}



}
?>