<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
Denne fila ble sist oppdatert 2017-03-07
**********************************************/

//require_once("./config.php");
require_once(LEIEBASEN_AUTHORISER . ".klaso.php");
session_set_cookie_params(0, "/", LEIEBASEN_COOKIE_SCOPE);

class Leiebase {

// Endre verdiene under i tråd med installasjonsmiljøet.
protected $filarkiv = LEIEBASEN_FILARKIV;
public $mysqli; // object - MySQLi-forbindelsen
public $advarsler = array(); // Viktige ting som trenger oppmerksomhet ved innlogging. Meldingene er gruppert i kategori 0-teknisk alarm, 1-advarsel, og 2-orientering/påminnelse
public $autoriserer = LEIEBASEN_AUTHORISER; // grensesnitt mot autoriseringsskript
public $bruker = array();
public $ext_bibliotek = LEIEBASEN_EXTJS_LIB;
public $fra; // Angivelse av fradato i oppslag
public $GET = array();
public $hoveddata;
public $http_host = LEIEBASEN_INSTALL_URI;
public $live = LEIEBASEN_LIVE; // Sett denne til false for å hindre epostsendinger etc.
public $mal = "_HTML.php";
public $område = array();
public $oppslag;
public $POST = array();
public $returi; // Hele RETURI samles i dette objektet
public $root = LEIEBASEN_ROOT;
public $til; // Angivelse av tildato i oppslag
public $tittel = 'leiebasen';
public $valg = array();
public $sorteringsegenskap = array();	// Brukes ved sortering av objekter
public $tillegg = array();	// Tillegg
public $knagger = array();	// Ulike handlinger knyttet til framdriftsstadier
public $tilleggsmetoder = array();	// Metoder lagt til av tillegg


public function __construct() {
//	die("<h1>Arbeid p&aring;g&aring;r.</h1>Holder p&aring; &aring; laste opp reparasjoner og endringer.<br />Ved sp&oslash;rsm&aring;l kontakt meg p&aring; mobilnr. <a href=\"callto:+447506206979\">+447506206979</a>, p&aring; <a href=\"skype:kayegil.hauan\">skype</a>, eller send en mail til <a href=\"mailto:kyegil@gmail.com\">kyegil@gmail.com</a><br />Kay-Egil");

	global $mysqliConnection;
	global $leiebase;
	global $tillegg;

	$leiebase = $this;
	$this->mysqli = $mysqliConnection;
	$this->tillegg = $tillegg;
	$this->lastTillegg( $tillegg );

	$this->advarsler[0] = array();
	$this->advarsler[1] = array();
	$this->advarsler[2] = array();
	
	date_default_timezone_set("Europe/Oslo");
	setlocale(LC_TIME, 'nb_NO');
	setlocale(LC_COLLATE, 'nb_NO');
	$this->hentValg();
	
//	$this->varsleForfall();
	$this->varsleFornying();
	
	// Slett etterlatte innbetalingsrader etter 30 minutter
	$this->mysqli->query("DELETE FROM innbetalinger WHERE beløp = 0 AND registrert < '" . date('Y-m-d H:i:s', (time() - 1800)) . "'");
	
	/********************************************************************/
	// Løsne innbetalingene på ikke-eksisterende krav
	$this->mysqli->query("
		UPDATE innbetalinger LEFT JOIN krav ON innbetalinger.krav = krav.id
		SET innbetalinger.krav = NULL
		WHERE krav.id IS NULL AND innbetalinger.krav > 0
	");
	/********************************************************************/
	
	/********************************************************************/
	// Finner innbetalinger som er med på overbetalinger, og løsner disse fra kravene
	$sql =	"
		SELECT innbetalingsid
		FROM innbetalinger INNER JOIN krav on innbetalinger.krav = krav.id
		WHERE ( krav.beløp * krav.utestående ) < 0
		ORDER BY innbetalinger.dato DESC, innbetalinger.innbetalingsid DESC
		LIMIT 1
	";
	$dobbeltbetalinger = $this->arrayData($sql);
	while(count($dobbeltbetalinger['data'])) {
		$this->mysqli->query("UPDATE innbetalinger SET krav = NULL WHERE innbetalingsid = '{$dobbeltbetalinger['data'][0]['innbetalingsid']}'");
		$this->oppdaterUbetalt();
		$dobbeltbetalinger = $this->arrayData($sql);
	}
	
	/********************************************************************/
	// Oppretter Leiebase::POST og Leiebase::GET klare for mysql-innsmetting
	$this->escape();
	
	if( isset( $_GET['oppslag'] ) ) {
		$this->oppslag = $_GET['oppslag'];
	}
	$this->returi = new returi;

	$this->skje('__constructUtført');
}


// Sjekker om brukeren har adgang til et angitt område.
//	Samtidig sjekkes og oppdateres økten, og innlogging kreves om nødvendig
//	$this->bruker fylles også ut som følger:
//		navn:		Fullt navn
//		id:			Brukerens id, i samsvar med personadressekort
//		brukernavn: Brukernavn for innlogging
//		epost:		Brukerens epostadresse
//
/****************************************/
//	$katalog (str)		Området det ønskes adgang til
//	$leieforhold (int)	Aktuelt leieforhold dersom området er 'beboersider'
//	--------------------------------------
//	retur: (bool) Sant dersom innlogget bruker har adgang til området.
public function adgang($katalog = "", $leieforhold = null) {
	$autoriserer = new $this->autoriserer;
	settype($leieforhold, 'integer');
	
	if( !$leieforhold && isset($this->område['leieforhold'])) {
		$leieforhold = $this->område['leieforhold'];
	}

	$this->bruker['navn'] = $autoriserer->akiruNomo();
	$this->bruker['id'] = $autoriserer->akiruId();
	$this->bruker['brukernavn'] = $autoriserer->akiruUzantoNomo();
	$this->bruker['epost'] = $autoriserer->akiruRetpostadreso();

	$autoriserer->postuluIdentigon();
	
	if ($katalog == "") {
		return true;
	}
	
	$resultat = $this->mysqli->arrayData(array(
		'source' => "adganger",
		'returnQuery' => true,
		'where' => "personid = '{$this->bruker['id']}'
		AND adgang = '{$katalog}'"
		 . (($katalog == 'beboersider' and (int)strval($leieforhold) > 0) ? " AND leieforhold = '{$leieforhold}'" : "")
	));
	return (bool)$resultat->totalRows;
}


public function adresse($kontraktnr) {
	$adresse = $this->mysqli->arrayData(array(
		'source' => "kontrakter LEFT JOIN personer ON kontrakter.regningsperson = personer.personid LEFT JOIN leieobjekter ON kontrakter.regningsobjekt = leieobjekter.leieobjektnr",
		'where' => "kontrakter.kontraktnr = " . $kontraktnr,
		'fields' => "regningsperson, regning_til_objekt, regningsobjekt, regningsadresse1, regningsadresse2, kontrakter.postnr AS kontraktpostnr, kontrakter.poststed AS kontraktpoststed, kontrakter.land AS kontraktland, adresse1 AS personadresse1, adresse2 AS personadresse2, personer.postnr AS personpostnr, personer.poststed AS personpoststed, personer.land AS personland, leieobjekter.navn AS leieobjektnavn, leieobjekter.gateadresse AS leieobjektadresse, leieobjekter.postnr AS leieobjektpostnr, leieobjekter.poststed AS leieobjektpoststed"
	))->data[0];
	if($adresse->regning_til_objekt) {
		$resultat = ($adresse->leieobjektnavn ? "{$adresse->leieobjektnavn}\n" : "")
		. $adresse->leieobjektadresse . "\n"
		. "{$adresse->leieobjektpostnr} {$adresse->leieobjektpoststed}";
	}
	else if($adresse->regningsperson) {
		$resultat = ($adresse->personadresse1 ? ($adresse->personadresse1 . "\n") : "")
		. ($adresse->personadresse2 ? ($adresse->personadresse2 . "\n") : "")
		. $adresse->personpostnr . " " . $adresse->personpoststed . "\n"
		. ($adresse->personland != "Norge" ? $adresse->personland : "");
	}
	else {
		$resultat = ($adresse->regningsadresse1 ? ($adresse->regningsadresse1 . "\n") : "")
		. ($adresse->regningsadresse2 ? ($adresse->regningsadresse2 . "\n") : "")
		. $adresse->kontraktpostnr . " " . $adresse->kontraktpoststed . "\n"
		. ($adresse->kontraktland != "Norge" ? $adresse->kontraktland : "");
	}
	return $resultat;
}


public function apostrof($objekt) {
	if(substr($objekt, -1, 1) != 's') return "{$objekt}s";
	else return "{$objekt}'";
}


public function arrayData($sql) {
	$data = $this->mysqli->arrayData(array(
		'sql'	=> $sql
	));
 	$data = json_decode(json_encode($data), true);
 	settype($data['data'], 'array');
	return $data;
}


/*	Bankfridager
Returnerer liste med alle røde dager (ikke vanlige helger) i inneværende eller angitt år
*****************************************/
//	--------------------------------------
//	retur: (array) Liste med datostrenger i formatet 'm-d'
public function bankfridager( $år = null ) {
	if( $år === null ) {
		$år = date('Y');
	}
	$helligdager = array("01-01", "05-01", "05-17", "12-24", "12-25", "12-26");

	$påske = new DateTime( "{$år}-03-21" );
	$påske->add( new DateInterval('P' . easter_days($år) . 'D') );

	$skjærtorsdag = clone $påske;
	$skjærtorsdag->sub( new DateInterval( 'P3D' ) );

	$langfredag = clone $påske;
	$langfredag->sub( new DateInterval( 'P2D' ) );

	$andrePåskedag = clone $påske;
	$andrePåskedag->add( new DateInterval( 'P1D' ) );

	$kristiHimmelfartsdag = clone $påske;
	$kristiHimmelfartsdag->add( new DateInterval( 'P39D' ) );

	$førstePinsedag = clone $påske;
	$førstePinsedag->add( new DateInterval( 'P49D' ) );

	$andrePinsedag = clone $påske;
	$andrePinsedag->add( new DateInterval( 'P50D' ) );

	$helligdager[] = $skjærtorsdag->format('m-d');
	$helligdager[] = $langfredag->format('m-d');
	$helligdager[] = $andrePåskedag->format('m-d');
	$helligdager[] = $kristiHimmelfartsdag->format('m-d');
	$helligdager[] = $førstePinsedag->format('m-d');
	$helligdager[] = $andrePinsedag->format('m-d');

	sort($helligdager);

	return $helligdager;
}


// Funksjon som behandler nyankomne efakturaforespørsler
// Request-objektets avtalestatus endres også (?)
/****************************************/
//	$avtale:	request-transaksjon fra NETS-forsendelse i form av stdClass eller et SimpleXMLElement-objekt
//	--------------------------------------
//	retur: (stdclass) resultat med egenskapene leieforhold, status og kode
public function behandleEfakturaAvtaler( $avtale ) {

	if( $avtale instanceof SimpleXMLElement ) {
	}
	
	if( $avtale instanceof stdClass ) {
	
		$resultat = (object)array(
			'status'	=> $avtale->avtalestatus,
			'kode'		=> $avtale->feilkode
		);

		$resultat->leieforhold = $this->leieforholdFraEfakturareferanse( $avtale->efakturaRef );

		if( $avtale->avtalestatus == "P" ) {

			// Deler opp fornavet i et array.
			// $forenkletFornavn[0] inneholder hele fornavnet,
			// og $forenkletFornavn[1] inneholder det første (del-)fornavnet
			preg_match(
				'/([A-ZÆØÅa-zæøå]+)/',
				$avtale->fornavn,
				$forenkletFornavn
			);


			// Sjekk at efakturareferansen representerer et reelt leieforhold
			if( !$resultat->leieforhold or !$resultat->leieforhold->hent('id') ) {
				$resultat->status = "N";
				$resultat->kode = '01';
			}

			// Sjekk at etternavnet finnes i kontrakten
			else if ( mb_stripos( $this->forenklet($resultat->leieforhold->hent('navn')), $this->forenklet($avtale->etternavn), 0, 'UTF-8' ) === false ) {
				$resultat->forespørsel = $this->forenklet($avtale->etternavn);
				$resultat->navn = $this->forenklet($resultat->leieforhold->hent('navn'));
				$resultat->status = "N";
				$resultat->kode = '02';
			}

			// Sjekk at første del av fornavnet finnes i kontrakten
			else if (
			isset( $forenkletFornavn[1] )
				and mb_stripos(
					$this->forenklet($resultat->leieforhold->hent('navn')),
					$this->forenklet($forenkletFornavn[1]),
					0,
					'UTF-8'
				) === false
			) {
				$resultat->forespørsel = $this->forenklet($forenkletFornavn[1]);
				$resultat->navn = $this->forenklet($resultat->leieforhold->hent('navn'));
				$resultat->status = "N";
				$resultat->kode = '02';
			}

			else {
				$resultat->status = "A";
				$resultat->kode = null;
			}
		}
		
		return $resultat;
	}
}



/*	Beregn utestående
Beregner utestående krav på en bestemte datoer
******************************************
$datoer (array): Datoen(e) utestående skal beregnes for
------------------------------------------
retur (array) Assosiativt array Utestående beløp
*/
public function beregnUtestående( $datoer = array(), $ignorerForskuddsbetaling = false ) {
	$tp = $this->mysqli->table_prefix;
	$alleTransaksjoner = array();
	$resultat = array();
	settype( $datoer, 'array' );

	foreach( $datoer as &$dato ) {
		if( $dato instanceof DateTime ) {
			$dato = $dato->format('Y-m-d');
		}
	}
	sort($datoer);

	if( $ignorerForskuddsbetaling ) {
		$sql = "
SELECT dato, beløp, (@saldo := @saldo + beløp) AS saldo
FROM (
	SELECT dato, SUM(beløp) AS beløp
	FROM ((
			SELECT kravdato AS dato, beløp AS beløp
			FROM krav
		) UNION ALL (
			SELECT GREATEST(innbetalinger.dato, krav.kravdato) AS dato, -innbetalinger.beløp AS beløp
			FROM innbetalinger INNER JOIN krav ON innbetalinger.krav = krav.id
		)
		ORDER BY `dato`  ASC
	) AS transaksjoner
	GROUP BY dato
) AS summering";
	}
	else {
		$sql = "
SELECT dato, beløp, (@saldo := @saldo + beløp) AS saldo
FROM (
	SELECT dato, SUM(beløp) AS beløp
	FROM ((
			SELECT kravdato AS dato, beløp AS beløp
			FROM krav
		) UNION ALL (
			SELECT dato AS dato, -beløp AS beløp
			FROM innbetalinger
		)
		ORDER BY dato  ASC
	) AS transaksjoner
	GROUP BY dato
) AS summering
";
	}
	
	$this->mysqli->query("SET @saldo:=0;");
	$historikk = $this->mysqli->arrayData(array(
		'sql'	=> $sql
	));

	foreach( $historikk->data as $bevegelse ) {
		$alleTransaksjoner[$bevegelse->dato] = $bevegelse->saldo;
	}
	
	if( !$datoer ) {
		return $alleTransaksjoner;
	}

	unset($dato);
	foreach( $datoer as $dato ) {
		if( isset($alleTransaksjoner[$dato]) ) {
			$resultat[$dato] = $alleTransaksjoner[$dato];
		}
		
		else if( strlen($dato) == 10 ) {
 			reset( $alleTransaksjoner );
 			$transaksjon = each($alleTransaksjoner);
			while( is_array($transaksjon) and $transaksjon[0] <= $dato ) {
				$resultat[$dato] = $transaksjon[1];
				$transaksjon = each($alleTransaksjoner);
			}
		}
		
		else {
			foreach( $alleTransaksjoner as $d => $verdi ) {
				if( substr( $d, 0, strlen( $dato ) ) == $dato ) {
					settype( $resultat[$dato], 'array' );
					$resultat[$dato][] = $verdi;
				}
			}
		}
	}
	
	$siste = 0;
	foreach( $resultat as &$saldo ) {
		if( is_array($saldo) ) {
			if( $saldo ) {
				$saldo = ( array_sum($saldo) / count($saldo) );
			}
			else {
				$saldo = $siste;
			}
		}
		else {
			$siste = $saldo;
		}
	}
	
	return $resultat;
}



// Funksjon som erstatter alle forekomster av <br> med \nl
/****************************************/
//	$streng:	HTML-streng
//	--------------------------------------
//	retur: tekststreng
public function br2nl( $streng ) {
	return  str_ireplace(
		array("<br />","<br>","<br/>"),
		"\r\n",
		$streng
	) ;
}



public function brukPolett($p) {
	$this->mysqli->query("DELETE FROM poletter WHERE utløper < " . time());
 	$match = $this->arrayData("SELECT * FROM poletter WHERE polett = '$p'");
	if(count($match['data']) != 1) return false;
	else if(!$this->mysqli->query("DELETE FROM poletter WHERE polett = '$p'")) return false;
	else return true;
}


///// Skal erstattes med tilBrøk()
public function brok($verdi) {
	$v = round($verdi, 4);
	for($i = 120; $i > 1; $i--) {
		for($ii = 1; $ii < $i; $ii++) {
			$uttrykk = "$ii/$i";
			if(round($this->evaluerAndel($uttrykk), 4) == round($v, 4)) {
				$resultat = $uttrykk;
			}
		}
	}
	if(!isset($resultat)) {
		$resultat = $verdi;
	}
	return $resultat;
}


// Funksjon som bunter kravene sammen i giroer for utskrift og påfører forfallsdato.
// Funksjonen returnerer et array med de nye giroene
/****************************************/
//	$krav:	array over Krav-objekter eller Krav-id'er
//	--------------------------------------
//	retur: array med de resulterende Giro-objektene
public function buntGiroer($krav = array()) {
	$tp = $this->mysqli->table_prefix;
	$resultat = array();

	// Første lovlige forfall iflg innstillingene fastsettes.
	$forfall = $this->nyForfallsdato()->format('Y-m-d');
	
	// Kravene grupperes etter leieforhold og forfallsdato
	// Kreditt som allerede er utliknet kommer på egne kreditnotaer
	$giroer = $this->mysqli->arrayData(array(
		'fields'		=> "MAX(krav.kontraktnr) AS kontraktnr, kontrakter.leieforhold, IFNULL(krav.forfall, '$forfall') AS forfall, IF((krav.beløp < 0 AND innbetalinger.krav IS NOT NULL),1,0) AS utliknet_kreditt",
		
		'source'		=> "krav\n"
						.	"LEFT JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr\n"
						.	"LEFT JOIN innbetalinger ON krav.id = innbetalinger.krav",
		'where'	=> "krav.gironr IS NULL\n"
						.	"AND (krav.id = '" . implode("' OR krav.id = '", $krav) . "')",
		'groupfields'	=> "kontrakter.leieforhold, IFNULL(krav.forfall, '$forfall'), utliknet_kreditt",
	
	));

	//	For hver gruppe opprettes en giro.
	//	Kravene knyttes til giroen og kravet får påført forfallsdato
	// En kravbunt er liste over forfallsdatoer per leieforhold
	foreach($giroer->data as $kravbunt) {
		
		//	Lag et nytt gironr
		$sql =	"INSERT INTO giroer (gironr) SELECT NULL";
		$this->mysqli->query($sql);
		$gironr = $this->mysqli->insert_id;

		//	Sett gironummeret på kravene som hører til
		$this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "krav INNER JOIN kontrakter on krav.kontraktnr = kontrakter.kontraktnr\n"
			.	"LEFT JOIN innbetalinger on krav.id = innbetalinger.krav",

			'where'		=> "krav.gironr IS NULL
				AND (krav.id = '" . implode("' OR krav.id = '", $krav) . "')
				AND IFNULL(krav.forfall, '$forfall') = '{$kravbunt->forfall}'
				AND kontrakter.leieforhold = '{$kravbunt->leieforhold}'
				AND IF((krav.beløp < 0 AND innbetalinger.krav IS NOT NULL),1,0) = '{$kravbunt->utliknet_kreditt}'",

			'fields'	=> array(
				'krav.gironr'	=> $gironr,
				'krav.forfall'	=> $kravbunt->forfall
			)
		));

		//	Lag KID for giroen
		$this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "{$tp}giroer",
			'where'		=> "gironr = '{$gironr}'",
			'fields'	=> array(
				'kid'			=> $this->genererKid( $kravbunt->leieforhold, $gironr ),
				'leieforhold'	=> $kravbunt->leieforhold
			)
		));
	}

	return $this->mysqli->arrayData(array(
		'distinct'	=> true,
		'class'		=> "Giro",
		'fields'	=> "krav.gironr AS id",
		'source'	=> "krav",
		'where'		=> "krav.id = '" . implode("' OR krav.id = '", $krav) . "'"
	))->data;
}


// returnerer et array med alle kontraktnr i et leieobjekt for en gitt dato
public function dagensBeboere($leieobjektnr, $dato = 0) {
	if(!$dato) $dato = time();
	$resultat = array();
	$sql =	"SELECT MAX(kontrakter.kontraktnr) AS kontraktnr, kontrakter.leieforhold
			FROM `kontrakter` LEFT JOIN oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold
			WHERE leieobjekt = '$leieobjektnr'
			AND fradato <= '" . date('Y-m-d', $dato) . "'
			AND (fristillelsesdato IS NULL OR fristillelsesdato > '" . date('Y-m-d', $dato) . "')
			GROUP BY kontrakter.leieforhold" ;
	$a = $this->arrayData($sql);
	foreach($a['data'] as $kontrakt) {
		$resultat[] = $kontrakt['kontraktnr'];
	}
	return $resultat;
}


public function design() {
	echo "\n<div id=\"panel\"></div>\n";
}


// Funksjon som klargjør alle GET- og POST-verdier for å smettes inn i databasen
public function escape(){
	if (get_magic_quotes_gpc()) {
		$GET = array_map("stripslashes", $_GET);
		$POST = array_map("stripslashes", $_POST);
	}
	else {
		$GET = $_GET;
		$POST = $_POST;
	}
	$this->GET = array_map(array($this->mysqli, "real_escape_string"), $GET);
	$this->POST = array_map(array($this->mysqli, "real_escape_string"), $POST);
	if( isset( $_GET['fra'] ) )
		$this->fra = $this->GET['fra'];
	if( isset( $_GET['til'] ) )
		$this->til = $this->GET['til'];
	if( isset( $_POST['fra']) && $this->POST['fra']) {
		$this->fra = $this->POST['fra'];
	}
	if( isset( $_POST['til']) && $this->POST['til']) {
		$this->til = $this->POST['til'];
	}
}


public function epostmottaker($kontraktnr, $innbetalingsbekreftelse = false, $forfallsvarsel = false){
	$leieforhold = $this->leieforhold(intval(strval($kontraktnr)));
	$adresseliste = $this->mysqli->arrayData(array(
		'source' => "adganger INNER JOIN personer ON adganger.personid = personer.personid",
		'where' => "adganger.epostvarsling AND personer.epost <> '' and adganger.leieforhold = '{$leieforhold}'",
		'fields'	=> "personer.personid, personer.epost"
	));

	$resultat = array();

	foreach($adresseliste->data as $adresse) {
		$resultat[] = $this->navn($adresse->personid) . " <{$adresse->epost}>";
	}

	return implode(", ", $resultat);
}


public function epostpurring($purringer = array()) {
	$emne =	"Betalingspåminnelse";
	
	$resultat = true;
	
	foreach ( $purringer as $purring ) {
		$leieforhold = $purring->hent('leieforhold');
		$brukerepost = $leieforhold->hent('brukerepost', array());
		
		if ( $brukerepost ) {
			$resultat = $resultat && $this->sendMail(array(
				'to' => implode(",", $brukerepost),
				'subject' => $emne,
				'html' => $purring->gjengi( 'epost_purring_html', array() ),
				'text' => $purring->gjengi( 'epost_purring_txt', array() ),
				'testcopy' => false
			));
		
		}		
	}
	return $resultat;
}



// returnerer true dersom leieobjektet er en bolig.
// ellers returneres false
public function er_bolig($leieobjektnr) {
	$sql =	"SELECT boenhet FROM leieobjekter WHERE leieobjektnr = '$leieobjektnr'";
	$a = $this->arrayData($sql);
	return (boolean)$a['data'][0]['boenhet'];
}



// Kontrollrutinen ser etter leieavtaler som bør fornyes.
public function etasjerenderer($v) {
	if($v == '+') return "loft";
	else if($v == '0') return "sokkel";
	else if($v == '-1') return "kjeller";
	else if((int)$v) return "$v. etg.";
	else return $v;
}

/*	Evaluer
Utfører en enkel beregning som kan inneholde aritmetiske og logiske symboler.
Interne variabler i formelen kan ha maks 3 tegn.
Eksterne variabler angis i {klammer}, og må sendes som egenskaper av $verdier
*****************************************/
//	$formel (streng): Enkel formels som begynner med '='.
//	$verdier (stdClass, normalt null): Objekt bestående av eksterne variabler
//	--------------------------------------
//	retur: (DateTime-objekt) Resultatet av beregningen
public function evaluer( $formel, $variabler = null ) {
	$this->hentValg();
	settype( $variabler, 'object' );
	
	mb_regex_encoding('UTF-8');
	
	// Hver variabel i formelen erstattes med variabelens verdi,
	//	såfremt variabelen eksisterer og ikke følger etter en omvendt skråstrek
	foreach($variabler as $variabel => $verdi ) {
		if(!is_numeric($verdi)) {
			$verdi = '"' . addslashes($verdi) . '"';
		}
		$formel = trim(mb_ereg_replace('(?<!\\\){' . $variabel . '}', $verdi, $formel));
	}
	
	// Fjern omvendt skråstrek på de resterende krøllklammene
	$formel = mb_ereg_replace('\\\{', '{', $formel);
	
	// Sikre innholdet i gåseøyne
//	$formel = mb_ereg_replace('".+?"', '"' . '\\0' . '"', $formel);
	
	// Sjekk at formelen kun inneholder tillatt syntax.
	//	Ingen formler eller eksterne variabler (f.eks. $this) e.l.
	// regex kan testes på https://regex101.com/
	//	tillatte tegn:	0123456789+-*/ ()=.<>?:&|
	//	alle variabler i {krøllklammer}, og må være medsendt
	//	all tekst i doble gåseøyne
	if(!preg_match(
		'/\A=((("[^"\\\]*(?:\\\.[^"\\\]*)*")|([0-9\+\-\*\/\s\(\)\=\.\<\>\?\:\&\|]))+)\z/',
		$formel,
		$treff
	)) {
		throw new Exception("Ulovlige tegn i formelen: {$formel}");
	}
		
	return eval('return ' . substr($formel, 1). ';');
}


// Skal erstattes med fraBrøk();
public function evaluerAndel($uttrykk){
	$uttrykk = str_replace(",", ".", $uttrykk);
	$uttrykk = str_replace("%", "/100", $uttrykk);
	$uttrykk = str_replace(array(",", "%", " "), array(".", "/100", ""), $uttrykk);
	$andel = eval("return $uttrykk;");
	if($andel >1 or $andel <0) return false;
	else return $andel;
}


//	Multibyte- forenklet versjon av str_pad
//	Setter en streng til en fast lengde
/****************************************/
//	$streng: (streng) Strengen som skal formateres
//	$lengde: (heltall) Lengden på den formaterte teksten
//	$fyll: (streng) Tegnet strengen fylles med
//	$side: (konstant) : STR_PAD_RIGHT = venstrejustering, STR_PAD_LEFT = høyrejustering
//	--------------------------------------
//	resultat: formatert streng
public function fastStrenglengde ( $streng, $lengde, $fyll = " ", $side = STR_PAD_RIGHT ) {
		$streng = mb_substr( $streng, 0, $lengde, 'UTF-8' );
		$fyll = mb_substr( $fyll, 0, 1, 'UTF-8' );
		if( $side == STR_PAD_LEFT) {
			return str_repeat($fyll, $lengde - mb_strlen($streng, 'UTF-8') ) . $streng;
		}
		else {
			return $streng . str_repeat($fyll, $lengde - mb_strlen($streng, 'UTF-8') );
		}	
}



// Sender fbo trekkrav på giroer dersom dette ikke er gjort før,
// giroen er ubetalt,
// og fristene er opprettholdt.
// Det sendes ikke krav på giroer skrevet ut før FBO ble registrert,
// eller som forfaller om mindre enn 9 dager eller mer enn 50 dager
/****************************************/
//	--------------------------------------
//	retur: (stdClass-objekt eller false) Oppdrag avtalegiro trekkrav eller false
public function fboSendTrekkrav() {
	if( !$this->valg['avtalegiro'] ) {
		return false;
	}
	
	$tp = $this->mysqli->table_prefix;
	$oppdragskonto = preg_replace('/[^0-9]+/', '', $this->valg['bankkonto']);

	
	$resultat = $this->mysqli->arrayData(array(
		'class'			=> "Giro",
		'returnQuery'	=> true,
		'distinct'		=> true,
		'fields'		=> "giroer.gironr AS id",
			
		'source'		=> "{$tp}giroer AS giroer
							INNER JOIN {$tp}fbo AS fbo ON fbo.leieforhold = giroer.leieforhold
							LEFT JOIN {$tp}krav AS krav ON giroer.gironr = krav.gironr
							LEFT JOIN {$tp}fbo_trekkrav AS fbo_trekkrav ON giroer.gironr = fbo_trekkrav.gironr",
							
		'where'			=> "krav.forfall > DATE_ADD(NOW(), INTERVAL 9 DAY)
							AND krav.utestående > 0
							AND krav.forfall < DATE_ADD(NOW(), INTERVAL 50 DAY)
							AND fbo_trekkrav.id IS NULL
							AND fbo.registrert <= CURDATE()
							AND (giroer.utskriftsdato IS NULL OR giroer.utskriftsdato >= fbo.registrert)"
	));
	
	if( $resultat->totalRows ) {
	
		$oppdrag = (object)array(
			'tjeneste'		=> 21,
			'oppdragstype'	=> 00,
			'oppdragsnr'	=> $this->netsOpprettOppdragsnummer(),
			'oppdragskonto'	=> $oppdragskonto,
			'transaksjoner'	=> $resultat->data
		);

		foreach( $resultat->data as $giro ) {
		
			$leieforhold = $giro->hent('leieforhold');
			$fbo = $leieforhold->hent('fbo');
			
			// Egenvarsel skal være sann i alle tilfeller hvor banken ikke trenger sende varsel.
			//	Dvs:
			// - Dersom betaler ikke ønsker varsel
			// - Dersom betaler ønsker varsel sendt via SMS
			// - Dersom betaler har efaktura-avtale
			// - Dersom leiebasen har epostadresse på leieforholdet
			$egenvarsel = (
				!$fbo->varsel
				|| ( $this->valg['avtalegiro_sms'] and $fbo->mobilnr )
				|| $leieforhold->hent('efakturaavtale')
				|| $leieforhold->hent('brukerepost')
			) ? 1 : 0;
		
			$this->mysqli->saveToDb(array(
				'table'		=> "fbo_trekkrav",
				'insert'	=> true,
				'fields'	=> array(
					'leieforhold'	=> $leieforhold,
					'gironr'		=> $giro->hent('gironr'),
					'kid'			=> $giro->hent('kid'),
					'beløp'			=> $giro->hent('utestående'),
					'overføringsdato' => date('Y-m-d'),
					'oppdrag'		=> $oppdrag->oppdragsnr,
					'forfallsdato'	=> $giro->hent('forfall')->format('Y-m-d'),
					'varslet'		=> $giro->hent('utskriftsdato')
									?	$giro->hent('utskriftsdato')->format('Y-m-d H:i:s')
									:	null,
					'mobilnr'		=> $fbo->mobilnr,
					'egenvarsel'	=> $egenvarsel
				)
			));
		}
		
		return $oppdrag;
	}
	
	else {
		return false;
	}

}



// Sletter fbo trekkrav som har blitt endret siden innsending til Nets,
//	sånn at de kan sendes inn på nytt igjen
/****************************************/
//	--------------------------------------
//	retur: (stdClass-objekt eller false) Sletteoppdrag eller false
public function fboSlettTrekkrav() {
	$tp = $this->mysqli->table_prefix;

	if( !$this->valg['avtalegiro'] ) {
		return false;
	}
	
	$oppdragskonto = preg_replace('/[^0-9]+/', '', $this->valg['bankkonto']);

	
	$resultat = $this->mysqli->arrayData(array(
		'returnQuery'	=> true,
		'source'		=> "{$tp}fbo_trekkrav AS fbo_trekkrav
							LEFT JOIN {$tp}giroer AS giroer ON fbo_trekkrav.gironr = giroer.gironr
							LEFT JOIN {$tp}krav AS krav ON giroer.gironr = krav.gironr",
							
		'fields'		=> "fbo_trekkrav.id,
							fbo_trekkrav.gironr,
							fbo_trekkrav.forfallsdato,
							fbo_trekkrav.kid,
							fbo_trekkrav.beløp,
							fbo_trekkrav.mobilnr,
							fbo_trekkrav.overføringsdato",
		
		'groupfields'	=> "fbo_trekkrav.gironr",
		
		// Sletteanmoding sendes dersom forfall eller beløp er endret (eller kravet er slettet)
		// Sletteanmodning bør ikke sendes før 1-2 dager etter opprinnelig kravforsendelse
		'where'			=> "DATE_ADD(fbo_trekkrav.overføringsdato, INTERVAL 2 DAY) < NOW()
							AND fbo_trekkrav.forfallsdato > NOW()",
		
		'having'			=> "MIN(krav.forfall) IS NULL OR fbo_trekkrav.forfallsdato != MIN(krav.forfall)
							OR fbo_trekkrav.beløp != SUM(krav.utestående)",
		
		'distinct'		=> true
	));
	
	if( $resultat->totalRows ) {
	
		$sletteoppdrag = (object)array(
			'tjeneste'		=> 21,
			'oppdragstype'	=> 36,
			'oppdragsnr'	=> $this->netsOpprettOppdragsnummer(),
			'oppdragskonto'	=> $oppdragskonto,
			'transaksjoner'	=> array()
		);

		foreach( $resultat->data as $feilkrav ) {
			$giro = $this->hent( 'Giro', $feilkrav->gironr );
			
			// Trekket kan slettes dersom utestående beløp er eliminert
			//	eller dersom dersom det tid til å sende nytt trekk
			if(
				!$giro->hentId() // Dersom giroen eksisterer ikke (lenger) 
			or	($giro->hent('utestående') <= 0) // Den er helt betalt
			or	$this->netsNesteForsendelse() <= $giro->fboOppdragsfrist() // Det kan fortsatt sendes nytt trekk
			) {
				$sletteoppdrag->transaksjoner[] = (object)array(
					'forfallsdato'		=> date_create_from_format( 'Y-m-d', $feilkrav->forfallsdato ),
					'beløp'				=> $feilkrav->beløp,
					'kid'				=> $feilkrav->kid,
					'mobilnr'			=> $feilkrav->mobilnr
				);
			
				$this->mysqli->query("DELETE FROM fbo_trekkrav WHERE id = '{$feilkrav->id}'");			
			}			
		}
		
		if($sletteoppdrag->transaksjoner) {
			return $sletteoppdrag;
		}
	}
	
	return false;
}



// Sender varsel om fbo trekkrav som har blitt sendt siste 2 dager
/****************************************/
//	--------------------------------------
public function fboVarsle() {
	$tp = $this->mysqli->table_prefix;

	if( !$this->valg['avtalegiro'] ) {
		return false;
	}
	
	$oppdragskonto = preg_replace('/[^0-9]+/', '', $this->valg['bankkonto']);

	$efakturaer = array();
	
	// Finn alle avtalegiro trekkrav som er mer enn 2 dager gamle,
	// men som det fortsatt ikke er sendt varsel for
	foreach( $this->mysqli->arrayData(array(
		'source'		=> "{$tp}fbo_trekkrav AS fbo_trekkrav INNER JOIN {$tp}fbo AS fbo ON fbo_trekkrav.leieforhold = fbo.leieforhold",
		
		'where'			=> "fbo_trekkrav.varslet IS NULL AND fbo_trekkrav.egenvarsel AND fbo_trekkrav.overføringsdato < DATE_SUB( NOW(), INTERVAL 2 DAY )"
		
	))->data as $krav ) {
	
		$giro = $this->hent( 'Giro', $krav->gironr );
		$leieforhold = $this->hent( 'Leieforhold', $krav->leieforhold );
		$brukerepost = $leieforhold->hent('brukerepost');
		$fbo = $leieforhold->hent('fbo');
		$efakturaavtale = $leieforhold->hent('efakturaavtale');
		
		// Send varsel som eFaktura
		if( $this->valg['efaktura'] and $efakturaavtale ) {
			$efakturaer[] = $giro;		
		}
		
		// Send varsel som epost
		else if( $fbo->varsel ) {
		
			if( $leieforhold->hent('brukerepost') ) {
				$tittel = "Varsel om avtalegiro-trekk";
				$innhold = $giro->gjengi( 'epost_fbo-varsel', array(
					'leieforholdnr'				=> "{$leieforhold}",
					'leieforholdbeskrivelse'	=> "{$leieforhold->hent('beskrivelse')}",
					'gironr'					=> "{$giro}",
					'forfallsdato'				=> "{$giro->hent('forfall')->format('d.m.Y')}",
					'eposttekst'				=> $this->valg['eposttekst'],
					'kravsett'					=> array(),
				));
			
				if($this->sendMail( array(
					'testcopy'	=> true,
					'auto'		=> true,
					'to'		=> implode(', ', $brukerepost),
					'priority'	=> 50, // Priortitet 50 for AvtaleGiro-varsel
					'subject'	=> "Varsel om avtalegiro-trekk",
					'html'		=> $innhold
				))) {
					$this->mysqli->saveToDb(array(
						'table'		=> "fbo_trekkrav",
						'update'	=> true,
						'where'		=> "gironr = '{$krav->gironr}'",
						'fields'	=> array(
							'varslet'		=> date('Y-m-d H:i:s')
						)
					));
				}
			}
			
			else {
			}
		}
	}
	
	if( $efakturaer ) {
	
		$oppdrag = $this->netsLagEfakturaOppdrag ( $efakturaer );
	
		$efakturaforsendelse = $this->netsLagEfakturaForsendelse( array( $oppdrag ) );

		if( $efakturaforsendelse ) {
			foreach( $efakturaer as $giro ) {
				$giro->sett( 'utskriftsdato', new DateTime );
				$giro->opprettEfaktura( array(
					'forsendelsesdato'	=> new DateTime,
					'forsendelse'		=> $efakturaforsendelse->forsendelsesnummer,
					'oppdrag'			=> $oppdrag->oppdragsnr
				) );
			}
			return $efakturaforsendelse;
		}
	}
	else {
		return true;
	}
}



// Forbered utskrift
// Denne funksjonen samler krav til giroer og lager purringer som kan skrives ut.
// Resultatet kan deretter sendes til lagUtskriftsfil
// Resultatet lagres også i databasen for forsinket bruk
/****************************************/
//	$param: liste med kontroller:
//		kravsett:	sett med Krav-objekter som skal settes sammen til purringer
//		purregiroer: sett med Giro-objekter som skal purres
//		gebyrer: 	sett med Leieforhold-objekter eller leieforholdnr
//					som kan tillegges purregebyr
//		adskilt:	(boolsk) Husleie- og fellesstrømkrav på separate giroer
//		kombipurring: (boolsk) En felles purring for alle giroer i hvert leieforhold
//	--------------------------------------
//	resultat: stdClass-objekt:
//		success: bools suksessangivelse
//		giroer: liste over nye gironummer
//		purringer: liste over purreblankett-id'er
//		gebyrpurringer: liste over purreblankett-id'er
//		statusoversikter: liste over leieforholdnummer som skal ha oversikt
//		bruker: streng, navn på innlogget bruker
//		tidspunkt: DateTime-objekt
public function forberedUtskrift ($param) {
	settype($param, 'object');
	settype($param->kravsett,		'array');
	settype($param->purregiroer,	'array');
	settype($param->gebyrkontrakter,'array');
	settype($param->statusoversikter,'array');
	settype($param->adskilt,		'boolean');
	settype($param->kombipurring,	'boolean');
	
	$resultat = (object)array(
		'success'	=> true,
		'giroer'	=>	array(),
		'purringer'	=> array(),
		'gebyrpurringer'	=> array(),
		'statusoversikter'	=> array(),
		'bruker'	=> $this->bruker['navn'],
		'tidspunkt'	=> new dateTime
	);


	// Eksisterende giroer med ubetalte krav som har forfalt,
	//	men som ikke er skrevet ut, får ny forfallsdato.
	$forfall = $this->nyForfallsdato()->format('Y-m-d');
	$this->mysqli->saveToDb(array(
		'update' => true,
		'table' => "krav INNER JOIN krav AS krav2 on krav.gironr = krav2.gironr",
		'where' => "krav2.utskriftsdato IS NULL
			AND krav2.gironr
			AND krav2.forfall < '$forfall'
			AND (krav2.id = '" . implode( "' OR krav2.id = '", $param->kravsett ) . "')",
		'fields' => array(
			'krav.forfall' => $forfall
		),
		'returnQuery' => true
	));
	// Alle krav som ikke er skrevet ut og som har for kort forfallsfrist,
	//	får ny forfallsdato.
	$this->mysqli->saveToDb(array(
		'update' => true,
		'table' => "krav",
		'where' => "krav.utskriftsdato IS NULL
			AND krav.forfall < '$forfall'
			AND (krav.id = '" . implode( "' OR krav.id = '", $param->kravsett ) . "')",
		'fields' => array(
			'krav.forfall' => $forfall
		),
		'returnQuery' => true
	));


	// kravene buntes sammen og påføres forfallsdato
	if ( $param->adskilt ) {
	
		foreach ( $param->kravsett as $krav ) {
			if( !is_a($krav, "Krav") ) {
				$krav = $this->hent( 'Krav', $krav );
			}
			if ( $krav->hent('type') == "Husleie" ) {
				$husleie[] = $krav;
			}
			else if ( $krav->hent('type') == "Fellesstrøm" ) {
				$fellesstrøm[] = $krav;
			}
			else {
				$annet[] = $krav;
			}
		}
		$resultat->giroer = array_map( 'strval', array_merge(
			$this->buntGiroer($husleie),
			$this->buntGiroer($fellesstrøm),
			$this->buntGiroer($annet)
		) );
	}
	
	else {
		$resultat->giroer = array_map( 'strval', $this->buntGiroer($param->kravsett) );
	}


	// Så opprettes purringer for giroene som skal purres
	$purreomgang = time();		
	foreach( $param->purregiroer as $giro) {
		if( !is_a($giro, 'Giro')) {
			$giro = $this->hent('Giro', $giro );
		}
	
		// Lag purreblankettreferanse
		$purreblankett = $purreomgang . "-{$giro->hent('leieforhold')}" . (
			$param->kombipurring
			? ""
			: "-{$giro->id}"
		);


		// Her sjekkes om det kan legges på purregebyr
		if( $this->valg['purregebyr'] ) {
			$sisteForfall = clone $giro->hent('sisteForfall');
			if(
				$giro->hent('utskriftsdato') != null
				&&	in_array( (string)$giro->hent('leieforhold'), $param->gebyrkontrakter )
				&&	$giro->hent('antallGebyr') < 2
				&&	$sisteForfall
					->add(
						new DateInterval( $this->valg['purreintervall'] ) 
					)
					< new DateTime
			) {
				$resultat->gebyrpurringer[$purreblankett] = $purreblankett;
			}
		}
	
		if( $giro->purr(array(
			'blankett'	=> $purreblankett,
			'purremåte'	=> "giro",
			'purregebyr'	=> null,
			'purredato'	=> new DateTime
		)) ) {
			$resultat->purringer[$purreblankett] = $purreblankett;
		}
	}
	$resultat->gebyrpurringer = array_values($resultat->gebyrpurringer);
	$resultat->purringer = array_values($resultat->purringer);
	
	
	// Så opprettes statusoversikter
	$resultat->statusoversikter = array_map( 'strval', $param->statusoversikter );

	$this->mysqli->saveToDb(array(
		'update'	=> true,
		'table'		=> "valg",
		'where'		=> "innstilling = 'utskriftsforsøk'",
		'fields'	=> array(
			'verdi'	=> serialize($resultat)
		)
	));
	
	$this->hentValg();
	return $resultat;

}



/*	Forenklet streng for sammenlikning
******************************************
$streng (streng): Streng
------------------------------------------
retur (streng) forenklet streng
*/
public function forenklet( $streng ) {
	$streng = mb_strtolower($streng, 'UTF-8');
	$streng = trim($streng);
	$streng = str_ireplace(array("\t","\n","\r","  "), " ", $streng);	
	$streng = str_ireplace(array("ae","aa","æ","å","á","ä"), "a", $streng);
	$streng = str_ireplace(array("oe","ø","ó","ö"), "o", $streng);
	$streng = str_ireplace(array("ée","ee","ë"), "e", $streng);
	$streng = str_ireplace(array("ch"), "k", $streng);
	$streng = str_ireplace(array("z", "c", "ss", "ß", "Š"), "s", $streng);
	$streng = preg_replace('/[^a-z ]/i', '', $streng);
	return $streng;
}



// Forkaster det siste utskriftsforsøket
// Denne funksjonen løsner krav fra giroer, sletter purringer, og sletter innholdet i utskriftsforsøk-innstillingen.
//	--------------------------------------
//	resultat: suksessangivelse
public function forkastUtskrift () {

	$fil = "{$this->filarkiv}/giroer/_utskriftsbunke.pdf";
	if( file_exists( $fil ) ) {
		unlink( $fil );
	}

	if ( $this->valg['utskriftsforsøk'] ) {
		$utskriftsforsøk = unserialize($this->valg['utskriftsforsøk']);
		
		if( is_array( $utskriftsforsøk->giroer ) ) {
			$this->slettUbrukteGiroer( $utskriftsforsøk->giroer );
		}
		else {
			$this->slettUbrukteGiroer();
		}
		
		if( is_array( $utskriftsforsøk->purringer ) ) {
			$sql = "DELETE purringer.* from purringer WHERE blankett = '" . implode( "' OR blankett = '", $utskriftsforsøk->purringer ) . "'";
			$this->mysqli->query($sql);
		}
		
	}
	
	$resultat = $this->mysqli->saveToDb(array(
		'update'	=> true,
		'table'		=> "valg",
		'where'		=> "innstilling = 'utskriftsforsøk'",
		'fields'	=> array(
			'verdi'	=> ""
		)
	))->success;	

	$this->hentValg();
	return $resultat;
}



public function fornavn($personid, $lenke = false){
	if(!is_array($personid)) {
		$personid = array($personid);
	}
	foreach($personid as $id) {
		$a = $this->arrayData("SELECT * FROM personer WHERE personid = '$id'");
		$a = $a['data'][0];
		$navn = $a['er_org'] ? $a['etternavn'] : $a['fornavn'];
		$resultat[] = $lenke ? "<a href=\"index.php?oppslag=personadresser_kort&id=$personid>$navn</a>" : $navn;
	}
	return $resultat;
}


public function fortegn($tall){
	if(floatval($tall))
		return $tall/abs($tall);
	else
		return 0;
}


// Gjør om en brøkverdi til desimal
/****************************************/
//	$brøk: (streng) verdien som brøk
//	--------------------------------------
//	retur: verdien som desimal
public function fraBrøk( $brøk ) {
	$brøk = str_replace( array(",", "%"), array(".", "/100"), $brøk);
	
	$brøk = str_replace("%", "/100", $brøk);
	
	$brøk = explode("/", $brøk);
	
	if(!@$brøk[1]) {
		$brøk[1] = 1;
	}
	
	return bcdiv($brøk[0], $brøk[1], 12);
}


public function fs_krevFordelingsforslag($fakturanummer){

	$sql =	"SELECT fs_andeler.anleggsnr, fs_andeler.faktura_id, fs_andeler.faktura, fs_andeler.kontraktnr, fs_originalfakturaer.fradato, fs_originalfakturaer.tildato, fs_originalfakturaer.termin, SUM(fs_andeler.beløp) AS beløp\n"
		.	"FROM fs_andeler LEFT JOIN fs_originalfakturaer ON fs_andeler.faktura_id = fs_originalfakturaer.id\n"
		.	"WHERE fs_originalfakturaer.beregnet = 1 and fs_originalfakturaer.fordelt = 0 and fs_andeler.krav IS NULL and fs_andeler.kontraktnr and (fs_andeler.faktura = '" . implode("' OR fs_andeler.faktura = '", $fakturanummer) . "')\n"
		.	"GROUP BY fs_andeler.anleggsnr, fs_andeler.faktura, fs_andeler.kontraktnr, fs_originalfakturaer.fradato, fs_originalfakturaer.tildato, fs_originalfakturaer.termin";
	$andeler = $this->arrayData($sql);


	$ant = 0;
	$sql = "";
	
	foreach($andeler['data'] as $andel) {
		if($andel['beløp'] > 0) {
			$sql =	"INSERT INTO krav\n"
			.	"SET\n"
			.	"kontraktnr = '{$andel['kontraktnr']}',\n"
			.	"kravdato = CURDATE(),\n"
			.	"tekst = 'Strøm for anl. {$andel['anleggsnr']} " . (date('d.m.y', strtotime($andel['fradato'])) . ' - ' . date('d.m.y', strtotime($andel['tildato']))) . " (termin {$andel['termin']})',\n"
			.	"beløp = '{$andel['beløp']}',\n"
			.	"type = 'Fellesstrøm',\n"
			.	"termin = '{$andel['termin']}',\n"
			.	"fom = '{$andel['fradato']}',\n"
			.	"tom = '{$andel['tildato']}',\n"
			.	"anleggsnr = '{$andel['anleggsnr']}',\n"
			.	"opprettet = NOW(),\n"
			.	"oppretter = '{$this->bruker['navn']}',\n"
			.	"utestående = '{$andel['beløp']}'\n";

			if($this->mysqli->query($sql)) {
				$sql =	"UPDATE fs_andeler\n"
					.	"SET krav = '{$this->mysqli->insert_id}'\n"
					.	"WHERE krav IS NULL AND faktura = '{$andel['faktura']}' AND kontraktnr = '{$andel['kontraktnr']}'";
				$resultat = $this->mysqli->query($sql);
				
				$sql = "";
				$ant++;
 			}
 			$sql = "";
		}
		else {
			//	Sett in kreditt dersom fakturabeløpet er negativt
		}

	}
	
	// Registrer at fakturaene er fordelt
	$this->mysqli->saveToDb(array(
		'table'		=> "fs_originalfakturaer",
		'fields'	=> array(
			'fordelt'	=> 1
		),
		'where'		=> "fs_originalfakturaer.beregnet = 1 AND (fs_originalfakturaer.fakturanummer = '" . implode("' OR fs_originalfakturaer.fakturanummer = '", $fakturanummer) . "')",
		'update'	=> true
	));

	return $ant;
}


/*	Lag fordelingsforslag for fellesstrøm
Lager forslag til fordeling av fellesstrøm basert på fordelingsnøklene.
Manuelle beløp oppgis i formatet array(fakturaid => array(nøkkelelement => beløp))
******************************************
$fakturaId (array/heltall): Strømregning i form av id-nummer eller liste av id-nummer som skal fordeles
$manuelleBeløp (array):		Liste over manuelt beregnede beløp der fordelingsnøklene krever dette:
------------------------------------------
retur (stdClass):	Objekt med egenskaper:
	success (boolsk):	Om fordelingen var vellykket eller ikke
	msg (streng):		Evt feilmelding om fordelingen mislyktes
	fordelt (array):	Liste over strømregninger (id-nummer) som ble fordelt
*/
public function fsLagFordelingsforslag( $fakturaId, $manuelleBeløp = array() ) {
	$tp = $this->mysqli->table_prefix;

	if(!is_array($fakturaId)) {
		$fakturaId = array($fakturaId);
	}
	$resultat = new stdclass;
	$resultat->success = true;
	$resultat->msg = "";
	$resultat->fordelt = array();
	$resultatarray = array();
	
	
	// Slett evt tidligere andeler
	$sql =	"DELETE fs_andeler.*\n"
		.	"FROM fs_andeler INNER JOIN fs_originalfakturaer ON fs_andeler.faktura_id = fs_originalfakturaer.id\n"
		.	"WHERE !fs_originalfakturaer.fordelt\n"
		.	"AND\n"
		.	"(fs_andeler.faktura_id = '" . implode("' OR fs_andeler.faktura_id = '", $fakturaId) . "')";
	$resultat->success = $this->mysqli->query($sql);
	
	if(!$resultat->success) {
		$resultat->msg = $sql;
		return $resultat;
	}


	// Last de berørte fakturaene
	$fakturasett = $this->mysqli->arrayData(array(
		'source' => "fs_originalfakturaer",
		'where' => "!fordelt AND (id = '" . implode("' OR id = '", $fakturaId) . "')"
	));
	if(!$fakturasett->success) {
		$resultat->success = false;
		$resultat->msg = "Klarte ikke å finne faktura(ene) pga. en feil.<br />Databasen svarte: " . $fakturasett->msg;
		return $resultat;
	}
	
	
	// $fakturasett->data inneholder nå den eller de fakturaene som er gjenstand for fordeling. Ingen av disse fakturaene er ferdigfordelt (låst).
	if(!$fakturasett->totalRows) {
		$resultat->msg = "Fant ingen fakturaer";
		$resultat->success = false;
		return $resultat;
	}


	// Hele fordelingsprosedyren repeteres for hver eneste faktura som ikke er fordelt
	foreach($fakturasett->data as $faktura) {
	
		$resultat->msg .= "<div><b>Faktura {$faktura->fakturanummer}</b> ({$this->kr($faktura->fakturabeløp)}):<br />";
		
		// Det ikke fordelte fakturabeløpet, $rest, nullstilles før en begynner å fordele hver faktura
		$rest = $faktura->fakturabeløp;
		
		// Fordelingen av hver faktura foregår i tre etterfølgende operasjoner:
		// Først hentes alle fastbeløp og det lages fordeling etter disse
		// Deretter hentes alle prosentelementer og det lages fordeling etter disse
		// Til slutt hentes alle andeler og det lages fordeling etter disse
		
		
		// *********************************************************************
		// * Først hentes alle fordelingsnøkler med fast beløp,
		// * disse fordeles og trekkes ifra totalbeløpet.
		$fordelingsnokler = $this->mysqli->arrayData(array(
			'source'	=>	"{$tp}fs_fordelingsnøkler AS fs_fordelingsnøkler",
			'where'		=>	"fordelingsmåte = 'Fastbeløp' AND anleggsnummer = '{$faktura->anleggsnr}'"
		));
		
		if( $fordelingsnokler->totalRows and !isset($manuelleBeløp[$faktura->id]) ) {
			
			// Det er ikke oppgitt noe manuelt beløp for denne fakturaen.
			// Denne fakturaen kan ikke fordeles. Hopp til neste faktura.
			$resultat->msg .= "<span style=\"color:red;\">Fordelingsnøkkelen krever manuell beregning anv enkelte andeler, men disse mangler. Fakturaen kunne ikke fordeles.</span><br />";
			continue;		
		}

		foreach($fordelingsnokler->data as $fordelingsnokkel) {
		
			if( !isset($manuelleBeløp[$faktura->id][$fordelingsnokkel->nøkkel]) ) {
			
				// Det er ikke oppgitt noe manuelt beløp for dette fastnøkkelementet.
				// Denne fakturaen kan ikke fordeles. Hopp til neste faktura.
				$resultat->msg .= "<span style=\"color:red;\">Fordelingsbeløp for element {$fordelingsnokkel->nøkkel} i fordelingsnøkkelen mangler. Fakturaen kunne ikke fordeles.</span><br />";
				continue 2;		
			}
		}

		foreach($fordelingsnokler->data as $fordelingsnokkel) {
		
			// Finner ut hvilke kontrakter som berøres av fastnøkkelen og for hvilken tidsperiode. Disse lagres i variabelen $kontrakter
			if($fordelingsnokkel->følger_leieobjekt){
				$sql =	"
					SELECT kontrakter.kontraktnr, kontrakter.andel, MIN(fom) AS fradato, IF(fristillelsesdato IS NULL, MAX(tom), LEAST(DATE_SUB(fristillelsesdato, INTERVAL 1 DAY), MAX(tom))) AS tildato
					FROM `krav` INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr
					LEFT JOIN oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold
					WHERE krav.type = 'Husleie'
					AND krav.leieobjekt = '{$fordelingsnokkel->leieobjekt}'
					GROUP BY kontrakter.andel, kontrakter.kontraktnr
					HAVING fradato <= '{$faktura->tildato}' AND tildato >= '{$faktura->fradato}'
					ORDER BY MIN(fom), MAX(tom) DESC
				";
			}
			else {
				$sql = "
					SELECT MAX(kontraktnr) AS kontraktnr, 1 AS andel, '{$faktura->fradato}' AS fradato, '{$faktura->tildato}' AS tildato
					FROM kontrakter WHERE leieforhold = '{$fordelingsnokkel->leieforhold}'
					GROUP BY leieforhold
					";
			}
			$kontrakter = $this->arrayData($sql);

			//	Dersom fastnøkkelen ikke tilhører noen fordi leieobjektet står tomt, legges det inn en andel hvor kontraktnr er null. Denne andelen betales av utleier.
			if(!count($kontrakter['data'])) {
				$kontrakter['data'][] = array(
					'kontraktnr' => 0,
					'andel' => 1,
					'fradato' => $faktura->fradato,
					'tildato' => $faktura->tildato
				);
			}
				
			// For hver kontrakt lages en faktor ($periodeteller) som angir hvor stor del av fakturatidsrommet leieavtalen dekker, multiplisert med hvor stor andel av leieobjektet leieavtalen gjør beslag på.
			// $periodenevner er summen av alle kontraktenes periodetellere.
			$periodenevner = 0;
			foreach($kontrakter['data'] as $linje=>$kontrakt) {
				$kontrakter['data'][$linje]['fra'] = $fra = strtotime($kontrakt['fradato']);
				$kontrakter['data'][$linje]['til'] = $til = strtotime($kontrakt['tildato']);
				
				$kontrakter['data'][$linje]['periodefaktor'] = $periodefaktor = ($til + 24 * 3600 - $fra) / (strtotime($faktura->tildato) + 24 * 3600 - strtotime($faktura->fradato));
				
				$kontrakter['data'][$linje]['periodeteller'] = $periodeteller = $this->evaluerAndel($kontrakt['andel']) * $periodefaktor;
				
				$periodenevner += $periodeteller;
			}

			// nevneren må aldri være null
			if(!$periodenevner) $periodenevner = 1;
		
			// Beløpet hver enkelt skal betale beregnes, og det opprettes ei spørring som setter alle andelene inn i andelstabellen.
			sort($kontrakter['data']);
			$innsettingssql =	"INSERT INTO fs_andeler (faktura_id, anleggsnr, fom, tom, faktura, kontraktnr, andel, beløp, tekst)\nVALUES\n";
			foreach($kontrakter['data'] as $linje=>$kontrakt) {
				$andelsbelop = round($manuelleBeløp[$faktura->id][$fordelingsnokkel->nøkkel] * $kontrakt['periodeteller']/$periodenevner);
		
				if($linje > 0)
					$innsettingssql .= ",\n";
				$innsettingssql .= "(";
				$innsettingssql .= "{$faktura->id}, ";
				$innsettingssql .= "'{$faktura->anleggsnr}', ";
				$innsettingssql .= "'" . date('Y-m-d', $kontrakt['fra']) . "', ";
				$innsettingssql .= "'" . date('Y-m-d', $kontrakt['til']) . "', ";
				$innsettingssql .= "'{$faktura->fakturanummer}', ";
				$innsettingssql .= "'{$kontrakt['kontraktnr']}', ";
				$innsettingssql .= "'', ";
				$innsettingssql .= "'$andelsbelop', ";
				$innsettingssql .= "'Strøm " . date('d.m.Y', $kontrakt['fra']) . " - " . date('d.m.Y', $kontrakt['til']) . ": kr. " . number_format($andelsbelop, 2, ",", " ") . " av termin {$faktura->termin} anl. {$faktura->anleggsnr}'";
				$innsettingssql .= ")";
			}

			// Fastbeløpet anses som fordelt, og trekkes ifra det resterende beløpet som skal fordeles.
			$rest -= $manuelleBeløp[$faktura->id][$fordelingsnokkel->nøkkel];
			$resultat->msg .= "<b>{$this->kr($manuelleBeløp[$faktura->id][$fordelingsnokkel->nøkkel])}</b> er trukket fra først i henhold til fordelingsnøkkelen.<br />";


			// Innsettingsspørringen utføres kun dersom den inneholder innsettingslinjer. Fakturaen som er fordelt legges til i resultatarrayet.
			if(count($kontrakter['data'])){
				if($this->mysqli->query($innsettingssql)) {
					$resultatarray[$faktura->id] = $faktura->id;
				}
			}
			
			unset($kontrakter, $innsettingssql);
			// Andelene for fastfordeling er opprettet
			
		} // Slutt på fordelingsnøkkelen
		unset($fordelingsnokler);
		// * Slutt på fordeling etter fastnøkler
		// *********************************************************************


		// Dersom hele fakturabeløpet er fordelt, dvs at både det opprinnelige og det resterende beløpet
		// har samme fortegn, kan fordelinga avsluttes og vi hopper til neste faktura
		// 
		if($this->fortegn($faktura->fakturabeløp) != $this->fortegn($rest)) {

			// Registrer at fakturaen er beregnet
			$this->mysqli->saveToDb(array(
				'table'		=> "fs_originalfakturaer",
				'fields'	=> array(
					'beregnet'	=> 1
				),
				'where'		=> "id = {$faktura->id}",
				'update'	=> true
			));
	
			$resultat->msg .= "Fakturaen er fordelt.<br />";
			continue 1;
		}


		// *********************************************************************
		// * Så hentes alle prosentvise fordelingselementer
		// * og ufordelt beløp fordeles etter disse.
		
		$resultat->msg .= "Prosentvis fordeling: ";
		$prosentfordeltbelop = 0;
		$sql = "SELECT * FROM fs_fordelingsnøkler WHERE fordelingsmåte = 'Prosentvis' AND anleggsnummer = '{$faktura->anleggsnr}'";
		$fordelingsnokler = $this->arrayData($sql);
		
		// Perioden må brytes opp i delperioder dersom det er ulik utleiegrad.
		// Alle datoer for endring lagres i variabelen $datoer
		$sql =	"SELECT dato
				FROM (
				SELECT fradato AS dato
				FROM kontrakter
				UNION SELECT ADDDATE(tildato, INTERVAL 1 DAY) AS dato
				FROM kontrakter
				UNION SELECT fristillelsesdato AS dato
				FROM oppsigelser
				UNION SELECT '{$faktura->fradato}'
				) AS dato
				GROUP BY dato
				HAVING
				dato >= '{$faktura->fradato}'
				AND
				dato <= '{$faktura->tildato}'
				ORDER BY dato";
		$datoer = $this->arrayData($sql);
		
		// Alle datoer som ikke egentlig innebærer noen endring i fordelinga forkastes
		$sistenevner = 0;				
		foreach($datoer['data'] as $linje=>$dato){
			$nevner = 0;
			foreach($fordelingsnokler['data'] as $fordelingsnokkel) {
				$nevner = (bool)count($this->dagensBeboere($fordelingsnokkel['leieobjekt'], strtotime($dato['dato']))) * $fordelingsnokkel['andeler'];
			}
			if($nevner == $sistenevner and $dato['dato'] != $faktura->fradato){
				unset($datoer['data'][$linje]);
			}
			else{
				$sistenevner = $nevner;
			}
		}
		
		// Hver dato omgjøres til en delperiode med dato og tildato
		// I tillegg føyes faktoren periodedel som angir hvor lang hver periode er i forhold til den totale fakturaperioden.
		$tildato = $faktura->tildato;
		foreach(array_reverse($datoer['data'], true) as $linje=>$dato){
			$datoer['data'][$linje]['tildato'] = $tildato;
			$tildato = date('Y-m-d', (strtotime($dato['dato']) - 24*3600));
		}
		foreach($datoer['data'] as $linje=>$dato){
			$datoer['data'][$linje]['periodedel'] = (strtotime($dato['tildato']) + 24 * 3600 - strtotime($dato['dato'])) / (strtotime($faktura->tildato) + 24 * 3600 - strtotime($faktura->fradato));
		}

		// Nå fordeles fellesstrømmen i hver enkelt delperiode etter angitt prosentsats
		foreach($datoer['data'] as $dato){
			foreach($fordelingsnokler['data'] as $fordelingsnokkel) {
				// Finner ut hvilke kontrakter som berøres av prosentnøkkelen og for hvilken tidsperiode. Disse lagres i variabelen $kontrakter
				if($fordelingsnokkel['følger_leieobjekt']){
					$sql =	"
						SELECT kontrakter.kontraktnr, kontrakter.andel, MIN(fom) AS fradato, IF(fristillelsesdato IS NULL, MAX(tom), LEAST(DATE_SUB(fristillelsesdato, INTERVAL 1 DAY), MAX(tom))) AS tildato
						FROM `krav` INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr
						LEFT JOIN oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold
						WHERE krav.type = 'Husleie'
						AND krav.leieobjekt = '{$fordelingsnokkel['leieobjekt']}'
						GROUP BY kontrakter.andel, kontrakter.kontraktnr
						HAVING fradato <= '{$dato['tildato']}' AND tildato >= '{$dato['dato']}'
						ORDER BY MIN(fom), MAX(tom) DESC
					";
				}
				else {
					$sql = "SELECT MAX(kontraktnr) AS kontraktnr, 1 AS andel, '{$faktura->fradato}' AS fradato, '{$faktura->tildato}' AS tildato
					FROM kontrakter WHERE leieforhold = '{$fordelingsnokkel['leieforhold']}'
					GROUP BY leieforhold";
				}
				$kontrakter = $this->arrayData($sql);
		
				// Så lages en faktor ($periodeteller) for hver leieavtale, som angir hvor stor del av fakturadeltidsrommet leieavtalen dekker, multiplisert med hvor stor andel av leieobjektet leieavtalen gjør beslag på.
				// $periodenevner angir summen av alle periodetellerene.
				$periodenevner = 0;
				foreach($kontrakter['data'] as $linje=>$kontrakt) {
					$kontrakter['data'][$linje]['fra'] = $fra = max(strtotime($dato['dato']), strtotime($kontrakt['fradato']));			
					$kontrakter['data'][$linje]['til'] = $til = min(strtotime($dato['tildato']), strtotime($kontrakt['tildato']));			
					$kontrakter['data'][$linje]['periodefaktor'] = $periodefaktor = ($til + 24 * 3600 - $fra) / (strtotime($dato['tildato']) + 24 * 3600 - strtotime($dato['dato']));			
					$kontrakter['data'][$linje]['periodeteller'] = $periodeteller = $this->evaluerAndel($kontrakt['andel']) * $periodefaktor;			
					$periodenevner += $periodeteller;
				}
				// nevneren må aldri være null
				if(!$periodenevner) $periodenevner = 1;
				
				// Beløpet hver enkelt skal betale beregnes, og det opprettes ei spørring som setter alle andelene inn i andelstabellen.			
				sort($kontrakter['data']);
				$innsettingssql =	"INSERT INTO fs_andeler (faktura_id, anleggsnr, fom, tom, faktura, kontraktnr, andel, beløp, tekst)\nVALUES\n";
				foreach($kontrakter['data'] as $linje=>$kontrakt) {
					$andelsbelop = round($fordelingsnokkel['prosentsats'] * $rest * $dato['periodedel'] * $kontrakt['periodeteller']/$periodenevner);
					
					if($linje > 0)
						$innsettingssql .= ",\n";
					$innsettingssql .= "(";
					$innsettingssql .= "{$faktura->id}, ";
					$innsettingssql .= "'" . $faktura->anleggsnr . "', ";
					$innsettingssql .= "'" . date('Y-m-d', $kontrakt['fra']) . "', ";
					$innsettingssql .= "'" . date('Y-m-d', $kontrakt['til']) . "', ";
					$innsettingssql .= "'" . $faktura->fakturanummer . "', ";
					$innsettingssql .= "'" . $kontrakt['kontraktnr'] . "', ";
					$innsettingssql .= "'" . $kontrakt['periodeteller']/$periodenevner * $fordelingsnokkel['prosentsats'] . "', ";
					$innsettingssql .= "'$andelsbelop', ";
					$innsettingssql .= "'Strøm " . date('d.m.Y', $kontrakt['fra']) . " - " . date('d.m.Y', $kontrakt['til']) . ": " . number_format(($fordelingsnokkel['prosentsats']*100), 0, ",", " ") . "% av anl. {$faktura->anleggsnr}" . (($kontrakt['andel'] != '1') ? (" fordelt på leieavtalene i #{$fordelingsnokkel['leieobjekt']}") : "") . "'";
					$innsettingssql .= ")";
				}
		
				// Innsettingsspørringen utføres kun dersom den inneholder innsettingslinjer. Fakturaen som er fordelt legges til i resultatarrayet.
				if(count($kontrakter['data'])){
					if($this->mysqli->query($innsettingssql)) {
						$resultatarray[$faktura->id] = $faktura->id;
					}
				}
				$prosentfordeltbelop += $rest * $fordelingsnokkel['prosentsats'] * $dato['periodedel'];
		
				unset($kontrakter, $innsettingssql);
				// Andelene for prosentfordeling er opprettet
				
			} // Slutt på fordelingsnøkkelen
		}
		$resultat->msg .= "kr.&nbsp;<b>" . str_replace(' ', '&nbsp;', number_format($prosentfordeltbelop, 2, ",", " ")) . "</b> er fordelt prosentvis.<br />";

		$rest -= $prosentfordeltbelop;
		unset($fordelingsnokler);
		// * Slutt på fordeling etter prosentsats
		// *********************************************************************

		if($this->fortegn($faktura->fakturabeløp) != $this->fortegn($rest)){
			// Registrer at fakturaen er beregnet
			$this->mysqli->saveToDb(array(
				'table'		=> "fs_originalfakturaer",
				'fields'	=> array(
					'beregnet'	=> 1
				),
				'where'		=> "id = {$faktura->id}",
				'update'	=> true
			));
	
			$resultat->msg .= "Fakturaen er fordelt.<br />";
			continue 1;
		}
		// Hele fakturabeløpet er fordelt. Fordelinga kan avsluttes, og en hopper til neste faktura


		// *********************************************************************
		// * Så hentes alle andelsvise fordelingselementer
		// * og ufordelt beløp fordeles etter disse.
		
		$andelsfordeltbeløp = 0;
		$resultat->msg .= "Fordeling etter andeler: ";
		$sql =	"SELECT *\n"
			.	"FROM fs_fordelingsnøkler\n"
			.	"WHERE fordelingsmåte = 'Andeler'\n"
			.	"AND anleggsnummer = '{$faktura->anleggsnr}'";
		$fordelingsnokler = $this->arrayData($sql);
		if(count($fordelingsnokler['data'])) {
			$resultat->msg .= "I følge fordelingsnøkkelen skal restbeløpet fordeles andelsvis.<br />";
		}
		else {
			$resultat->msg .= "Fordelingsnøkkelen angir ingen andelsvis fordeling.<br />";
		}
		
		// Perioden må brytes opp i delperioder dersom det er ulik utleiegrad.
		// Alle datoer for endring lagres i variabelen $datoer
		$sql =	"SELECT dato
				FROM (
				SELECT DISTINCT fradato AS dato
				FROM kontrakter
				UNION SELECT fristillelsesdato AS dato
				FROM oppsigelser
				UNION SELECT '{$faktura->fradato}'
				) AS dato
				GROUP BY dato
				HAVING
				dato >= '{$faktura->fradato}'
				AND
				dato <= '{$faktura->tildato}'
				ORDER BY dato";
		
		$datoer = $this->arrayData($sql);
		
		// Alle datoer som ikke egentlig innebærer noen endring i fordelinga forkastes
		$sistenevner = 0;				
		foreach($datoer['data'] as $linje=>$dato){
			$nevner = 0;
			foreach($fordelingsnokler['data'] as $fordelingsnokkel) {
				$nevner += count($this->dagensBeboere($fordelingsnokkel['leieobjekt'], strtotime($dato['dato']))) * $fordelingsnokkel['andeler'];
			}
			if( $nevner == $sistenevner){
				unset($datoer['data'][$linje]);
			}
			else{
				$sistenevner = $nevner;
			}
		}
		
		// Hver dato omgjøres til en delperiode med dato og tildato
		// I tillegg føyes faktoren periodedel som angir hvor lang hver periode er i forhold til den totale fakturaperioden.
		$tildato = $faktura->tildato;
		foreach(array_reverse($datoer['data'], true) as $linje=>$dato){
			$datoer['data'][$linje]['tildato'] = $tildato;
			$tildato = date('Y-m-d', (strtotime($dato['dato']) - 24*3600));
		}
		foreach($datoer['data'] as $linje=>$dato){
			$datoer['data'][$linje]['periodedel'] = (strtotime($dato['tildato']) + 24 * 3600 - strtotime($dato['dato'])) / (strtotime($faktura->tildato) + 24 * 3600 - strtotime($faktura->fradato));
		}
		
		// Nå fordeles den resterende fellesstrømmen andelsvis for hver enkelt delperiode
		foreach($datoer['data'] as $dato){
 		
			// Beregn nevneren for hver delperiode. Den er summen av alle andelene
			$nevner = 0;
			foreach($fordelingsnokler['data'] as $fordelingsnokkel) {
				if($fordelingsnokkel['følger_leieobjekt']){
					$nevner += count($this->dagensBeboere($fordelingsnokkel['leieobjekt'], strtotime($dato['dato']))) * $fordelingsnokkel['andeler'];
				}
				else{
					$nevner += $fordelingsnokkel['andeler'];				
				}
			}
			$resultat->msg .= date('d.m.Y', strtotime($dato['dato'])) . " - " . date('d.m.Y', strtotime($dato['tildato'])) . ": " . "$nevner deler<br />";
						
			foreach($fordelingsnokler['data'] as $fordelingsnokkel) {
		
		
			// Finner ut hvilke leieforhold (angitt som siste kontrakt) som berøres av andelene for akkurat denne delperioden. Disse lagres i variabelen $kontrakter
				if($fordelingsnokkel['følger_leieobjekt']){
					$sql =	"
						SELECT MAX(kontrakter.kontraktnr) AS kontraktnr, GREATEST('{$dato['dato']}', MIN(fradato)) AS fradato, LEAST('{$dato['tildato']}', IFNULL(DATE_SUB(fristillelsesdato, INTERVAL 1 DAY), '{$dato['tildato']}')) AS tildato
						FROM kontrakter LEFT JOIN oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold
						WHERE leieobjekt = '{$fordelingsnokkel['leieobjekt']}' AND (fradato <= '{$dato['tildato']}' AND (fristillelsesdato IS NULL OR DATE_SUB(fristillelsesdato, INTERVAL 1 DAY) >= '{$dato['dato']}'))
						GROUP BY kontrakter.leieforhold
						ORDER BY kontrakter.leieforhold
					";
				}
				else {
					$sql =	"SELECT MAX(kontraktnr) AS kontraktnr, '{$dato['dato']}' AS fradato, '{$dato['tildato']}' AS tildato\n"
						.	"FROM kontrakter\n"
						.	"WHERE leieforhold = '{$fordelingsnokkel['leieforhold']}'\n"
						.	"GROUP BY leieforhold\n";
				}

				$kontrakter = $this->arrayData($sql);

				// $kontrakter inneholder nå alle leieforholdene i det aktuelle leieobjektet i akkurat denne delperioden.

				// Så lages en spørring som lagrer en andel for hvert leieforhold i $kontrakter, for akkurat denne delperioden.

				$innsettingssql =	"INSERT INTO fs_andeler (faktura_id, anleggsnr, fom, tom, faktura, kontraktnr, andel, beløp, tekst)\n"
					.	"VALUES\n";
				foreach($kontrakter['data'] as $linje=>$kontrakt) {
					$fra = strtotime($kontrakt['fradato']);
					$til = strtotime($kontrakt['tildato']);
		
					$periodefaktor = ($til + 24 * 3600 - $fra) / ((strtotime($dato['tildato']) + 24 * 3600 - strtotime($dato['dato'])));
		
					$andelsbelop = round($fordelingsnokkel['andeler'] / $nevner * $periodefaktor * $dato['periodedel'] * $rest);
		
					if($linje > 0)
						$innsettingssql .= ",\n";
					$innsettingssql .= "(";
					$innsettingssql .= "{$faktura->id}, ";
					$innsettingssql .= "'" . $faktura->anleggsnr . "', ";
					$innsettingssql .= "'" . date('Y-m-d', $fra) . "', ";
					$innsettingssql .= "'" . date('Y-m-d', $til) . "', ";
					$innsettingssql .= "'" . $faktura->fakturanummer . "', ";
					$innsettingssql .= "'" . $kontrakt['kontraktnr'] . "', ";
					$innsettingssql .= "'" . $fordelingsnokkel['andeler'] / $nevner * $periodefaktor . "', ";
					$innsettingssql .= "'$andelsbelop', ";
					$innsettingssql .= $tekst = "'Strøm " . date('d.m.Y', $fra) . " - " . date('d.m.Y', $til) . ": {$fordelingsnokkel['andeler']}/$nevner av anl. {$faktura->anleggsnr}'";
					$innsettingssql .= ")";
					$andelsfordeltbeløp += $andelsbelop;
				}
				if(count($kontrakter['data'])) {
					if($this->mysqli->query($innsettingssql)) {
						$resultatarray[$faktura->id] = $faktura->id;
					}
					else {
						$resultat->msg .= "<span style=\"color: red;\">Fordeling etter andeler for tidsrommet " . date('d.m.Y', $fra) . " - " . date('d.m.Y', $til) . "mislyktes pga en feil.</span><br />";
					}
				}
				else {
					$resultat->msg .= "<span style=\"color: red;\">Det ble ikke funnet noen leieforhold å fordele mellom i tidsrommet " . date('d.m.Y', $fra) . " - " . date('d.m.Y', $til) . ".</span><br />";
				}
				unset($kontrakter, $innsettingssql);
			}
		}
		$resultat->msg .= "Kr.&nbsp;" . str_replace(' ', '&nbsp;', number_format($andelsfordeltbeløp, 2, ",", " ")) . " er fordelt etter andeler.<br />";
		$rest -= $andelsfordeltbeløp;

		// * Slutt på fordeling etter andeler
		// *********************************************************************

		// Registrer at fakturaen er fordelt
		$this->mysqli->saveToDb(array(
			'table'		=> "fs_originalfakturaer",
			'fields'	=> array(
				'beregnet'	=> 1
			),
			'where'		=> "id = {$faktura->id}",
			'update'	=> true
		));

		$resultat->msg .= "<br />";

		// Hele fakturabeløpet er fordelt. Fordelinga kan avsluttes, og en hopper til neste faktura

		$resultat->msg .= "</div>";	
	}
	// Slutt på fordeling av fakturaer
	$resultat->fordelt = array_values($resultatarray);
	return $resultat;
}

// Funksjonen sender epost om nye strømfordelinger
public function fs_meldFordelingsforslag($fakturanummer){
	$this->hentValg();
	$sql = "SELECT kontraktnr FROM fs_andeler WHERE krav IS NULL AND epostvarsel IS NULL AND (faktura = '" . implode("' OR faktura = '", $fakturanummer) . "')\nGROUP BY kontraktnr";
	$kontrakter = $this->arrayData($sql);
	foreach($kontrakter['data'] as $kontrakt){ // loop for hver leieavtale
		$html =
'<!DOCTYPE html>
<html lang="no">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Untitled</title>
</head>
<body>
	<p><div style="font-weight: bold;">Oppsummering av fellesstrømfordeling:</div>' . str_replace("\n", "<br />\n", $this->valg['strømfordelingstekst']) . '</p>';
		$tekst = "Oppsummering av fellesstrømfordeling:\n\n";
		$sql = "SELECT faktura FROM fs_andeler WHERE krav IS NULL AND kontraktnr = '{$kontrakt['kontraktnr']}'\nGROUP BY faktura";
		$fakturaer = $this->arrayData($sql);
		foreach($fakturaer['data'] as $faktura){ // loop for hver faktura
			$sql = "SELECT * FROM fs_originalfakturaer INNER JOIN fs_fellesstrømanlegg ON fs_originalfakturaer.anleggsnr = fs_fellesstrømanlegg.anleggsnummer WHERE fakturanummer = '{$faktura['faktura']}'";
			$fakturadetaljer = $this->arrayData($sql);
			$fakturadetaljer = $fakturadetaljer['data'][0];
			$tekst .= "Originalfaktura:\t\t{$fakturadetaljer['fakturanummer']}\nFor tidsrom:\t\t\t\t" . date('d.m.Y', strtotime($fakturadetaljer['fradato'])) . " - " . date('d.m.Y', strtotime($fakturadetaljer['tildato'])) . " (termin {$fakturadetaljer['termin']})\n" . ($fakturadetaljer['kWh'] ? ("Forbruk:\t\t\t" . number_format($fakturadetaljer['kWh'], 0, ",", " ") . " kWh\n") : "") . "Fakturabeløp:\t\t\tkr. " . number_format($fakturadetaljer['fakturabeløp'], 2, ",", " ") . "\nStrømanlegg:\t\t\t{$fakturadetaljer['anleggsnummer']} {$fakturadetaljer['formål']}\nMåler:\t\t\t\t{$fakturadetaljer['målernummer']}\n\nFakturaen fordeles sånn:\n";
			$html .= 
"	<table style=\"padding: 10px; border: 1px solid grey;\">
		<tr>
			<td style=\"padding: 10px; border: 0px; background-color: #A7C942;\">
				<table border=\"0\">
					<tr>
						<td style=\"font-weight: bold;\">E-verkets faktura:</td>
						<td>{$fakturadetaljer['fakturanummer']}</td>
					</tr>
					<tr>
						<td style=\"font-weight: bold;\">Strømanlegg:</td>
						<td>{$fakturadetaljer['anleggsnummer']} {$fakturadetaljer['formål']}</td>
					</tr>
					<tr>
						<td style=\"font-weight: bold;\">Måler:</td>
						<td>{$fakturadetaljer['målernummer']}</td>
					</tr>
					<tr>
						<td style=\"font-weight: bold;\">For tidsrom:</td>
						<td>" . date('d.m.Y', strtotime($fakturadetaljer['fradato'])) . " - " . date('d.m.Y', strtotime($fakturadetaljer['tildato'])) . " (termin {$fakturadetaljer['termin']})</td>
					</tr>
					<tr>
						<td style=\"font-weight: bold;\">" . ($fakturadetaljer['kWh'] ? "Forbruk:" : "&nbsp;") . "</td>
						<td> " . ($fakturadetaljer['kWh'] ? (str_replace(' ', '&nbsp;', number_format($fakturadetaljer['kWh'], 0, ",", " ")) . "&nbsp;kWh") : "&nbsp;") . "</td>
					</tr>
					<tr>
						<td style=\"font-weight: bold;\">Fakturabeløp:</td>
						<td>kr.&nbsp;" . str_replace(" ", "&nbsp;", number_format($fakturadetaljer['fakturabeløp'], 2, ",", " ")) . "</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>
				<table style=\"border-style: none;\">
					<tr>
						<td style = \"background-color: white;\">
							Fordelingsnøkkel:
							<ul>
";
			$tekst .= "\nStrømmen er fordelt ut ifra følgende fordelingsnøkkel:\n";
			$sql = "SELECT * FROM fs_fordelingsnøkler WHERE fordelingsmåte = 'Fastbeløp' AND anleggsnummer = '{$fakturadetaljer['anleggsnummer']}'";
			$fastnokler = $this->arrayData($sql);
			$sql = "SELECT * FROM fs_fordelingsnøkler WHERE fordelingsmåte = 'Prosentvis' AND anleggsnummer = '{$fakturadetaljer['anleggsnummer']}'";
			$prosentnokler = $this->arrayData($sql);
			$sql = "SELECT * FROM fs_fordelingsnøkler WHERE fordelingsmåte = 'Andeler' AND anleggsnummer = '{$fakturadetaljer['anleggsnummer']}'";
			$andelsnokler = $this->arrayData($sql);
			foreach($fastnokler['data'] as $nokkel){ // loop for hver fastnøkkel på anlegget
				$tekst .= "\tet direkte oppgitt beløp";
				if(count($this->dagensBeboere($nokkel['leieobjekt'])) == 1)
					$tekst .= " betales av ";
				else
					$tekst .= " fordeles mellom ";
				if($nokkel['følger_leieobjekt'])
					$tekst .= "leietakerne i " . $this->leieobjekt($nokkel['leieobjekt'], true);
				else
					$tekst .= $this->liste($this->kontraktpersoner(($nokkel['leieforhold'])));
				$tekst .= "\n";
				$html .= "\t<li>et direkte oppgitt beløp";
				if(count($this->dagensBeboere($nokkel['leieobjekt'])) == 1)
					$html .= " betales av ";
				else
					$html .= " fordeles mellom ";
				if($nokkel['følger_leieobjekt'])
					$html .= "leietakerne i " . $this->leieobjekt($nokkel['leieobjekt'], true);
				else
					$html .= $this->liste($this->kontraktpersoner(($nokkel['leieforhold'])));
				$html .= "</li>\n";
			} // loop for hver fastnøkkel på anlegget
			foreach($prosentnokler['data'] as $nokkel){ // loop for hver prosentnøkkel på anlegget
				$tekst .= "\t" . number_format($nokkel['prosentsats'] * 100, 0, ",", " ") . "%";
				if(count($this->dagensBeboere($nokkel['leieobjekt'])) == 1)
					$tekst .= " betales av ";
				else
					$tekst .= " fordeles mellom ";
				if($nokkel['følger_leieobjekt'])
					$tekst .= "leietakerne i " . $this->leieobjekt($nokkel['leieobjekt'], true);
				else
					$tekst .= $this->liste($this->kontraktpersoner(($nokkel['leieforhold'])));
				$tekst .= "\n";
				$html .= "\t<li>" . str_replace(' ', '&nbsp;', number_format($nokkel['prosentsats'] * 100, 0, ",", " ")) . "%";
				if(count($this->dagensBeboere($nokkel['leieobjekt'])) == 1)
					$html .= " betales av ";
				else
					$html .= " fordeles mellom ";
				if($nokkel['følger_leieobjekt'])
					$html .= "leietakerne i " . $this->leieobjekt($nokkel['leieobjekt'], true);
				else
					$html .= $this->liste($this->kontraktpersoner(($nokkel['leieforhold'])));
				$html .= "</li>\n";
			} // loop for hver prosentnøkkel på anlegget
			foreach($andelsnokler['data'] as $nokkel){ // loop for hver andelsnøkkel på anlegget
				$tekst .= "\t" . $nokkel['andeler'] . ($nokkel['andeler'] > 1 ? " deler" : " del");
				$beboere = $this->dagensBeboere($nokkel['leieobjekt']);
				if(count($beboere) == 1)
					$tekst .= " betales av ";
				else
					$tekst .= " betales av hvert leieforhold i ";
				if($nokkel['følger_leieobjekt'])
					$tekst .= $this->leieobjekt($nokkel['leieobjekt'], true);
				else
					$tekst .= $this->liste($this->kontraktpersoner(($nokkel['leieforhold'])));
				$tekst .= "\n";
				$html .= "\t<li>" . $nokkel['andeler'] . ($nokkel['andeler'] > 1 ? " deler" : " del");
				$beboere = $this->dagensBeboere($nokkel['leieobjekt']);
				if(count($beboere) == 1)
					$html .= " betales av ";
				else
					$html .= " betales av hvert leieforhold i ";
				if($nokkel['følger_leieobjekt'])
					$html .= $this->leieobjekt($nokkel['leieobjekt'], true);
				else
					$html .= $this->liste($this->kontraktpersoner(($nokkel['leieforhold'])));
				$html .= "</li>\n";
			} // loop for hver andelsnøkkel på anlegget
			$html .=
"							</ul>
						</td>
					</tr>
				</table>
				Fakturaen fordeles sånn:
				<table style=\"border-style: none;\">
";
			$sql = "SELECT kontraktnr, SUM(beløp) AS beløp FROM fs_andeler WHERE faktura_id = '{$fakturadetaljer['id']}' GROUP BY kontraktnr ORDER BY kontraktnr";
			$andeler = $this->arrayData($sql);
			$sql = "SELECT kontraktnr FROM fs_andeler WHERE faktura_id = '{$fakturadetaljer['id']}' GROUP BY kontraktnr ORDER BY kontraktnr";
			$leietakere = $this->arrayData($sql);
			foreach($leietakere['data'] as $leietaker){ // loop for hver kontrakt som er med i fordelinga av strømmen
				$leietakersum = 0;
				$tekst .= $this->liste($this->kontraktpersoner($leietaker['kontraktnr'])) . ":\n";
				$html .= 
"					<tr style=\"font-weight: bold;\">
						<td>&nbsp;</td>
						<td>" . $this->liste($this->kontraktpersoner($leietaker['kontraktnr'])) . ":</td>
						<td style=\"text-align: right;\">&nbsp;</td>
					</tr>
";
				$sql = "SELECT kontraktnr, tekst, beløp FROM fs_andeler WHERE faktura_id = '{$fakturadetaljer['id']}' AND kontraktnr = '{$leietaker['kontraktnr']}' ORDER BY kontraktnr, fom, tom";
				$andeler = $this->arrayData($sql);
				foreach($andeler['data'] as $andel){
					$tekst .= "\t{$andel['tekst']}: kr. " . number_format($andel['beløp'], 2, ",", " ") . "\n";
					$html .= 
"					<tr>
						<td>&nbsp;</td>
						<td>{$andel['tekst']}:</td>
						<td style=\"text-align: right;\">kr.&nbsp;" . str_replace(" ", "&nbsp;", number_format($andel['beløp'], 2, ",", " ")) . "</td>
					</tr>
";
					$leietakersum += $andel['beløp'];
				}
				$tekst .= "\t\t\tTotalt: kr. " . number_format($leietakersum, 2, ",", " ") . "\n";
				$html .= 
"					<tr style=\"font-weight: bold;\">
						<td>&nbsp;</td>
						<td>Totalt:</td>
						<td style=\"text-align: right;\">kr.&nbsp;" . str_replace(" ", "&nbsp;", number_format($leietakersum, 2, ",", " ")) . "</td>
					</tr>
					<tr style=\"font-weight: bold;\">
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td style=\"text-align: right;\">&nbsp;</td>
					</tr>
";
			} // loop for hver kontrakt som er med i fordelinga av strømmen
			
			
			$tekst .= strip_tags($this->valg['strømfordelingstekst']) . "\n";
			$html .=
"				</table>
			</td>
		</tr>
	</table>
";
		} // loop for hver faktura
		
		
		$tekst .= "\n" . strip_tags( str_ireplace(
			array("<br />","<br>","<br/>"),
			"\r\n",
			$this->valg['eposttekst']
		) ) . "\n"; 

		$html .= 
"	<p>{$this->valg['eposttekst']}</p>
</body>
</html>";

		$adressefelt = $this->epostmottaker($kontrakt['kontraktnr']);
		if ($adressefelt) {
			$this->sendMail(array(
				'to' => $adressefelt,
				'subject' => "Fordeling av fellesstrøm",
				'html' => $html,
				'text' => $tekst,
				'testcopy' => true
			));
		}
			
		$sql =	"UPDATE fs_andeler\n"
			.	"SET epostvarsel = NOW()\n"
			.	"WHERE krav IS NULL\n"
			.	"AND epostvarsel IS NULL\n"
			.	"AND (faktura = '" . implode("' OR faktura = '", $fakturanummer) . "')\n"
			.	"AND kontraktnr = '{$kontrakt['kontraktnr']}'";
		$this->mysqli->query($sql);
		
	} // loop for hver leieavtale
}


public function fs_skrivFordelingsforslag($fakturanummer){
	$pdf = new fpdf();
	$sql =	"SELECT *\n"
		.	"FROM fs_originalfakturaer LEFT JOIN fs_fellesstrømanlegg ON fs_originalfakturaer.anleggsnr = fs_fellesstrømanlegg.anleggsnummer\n"
		.	"WHERE fakturanummer = '" . implode("' OR fakturanummer = '", $fakturanummer) . "'";
	$fakturaer = $this->arrayData($sql);
	foreach($fakturaer['data'] as $faktura){
		$pdf->AddPage();
		$pdf->SetFont('Arial','B',16);
		$pdf->Cell(40, 10, utf8_decode("Fordeling av fellesstrøm\n"));
		$pdf->SetFont('Arial','',11);
		$pdf->setXY(10, 20);
		$tekst = "Faktura:\t{$faktura['fakturanummer']}\n"
		. "For tidsrom:\t" . date('d.m.Y', strtotime($faktura['fradato'])) . " - " . date('d.m.Y', strtotime($faktura['tildato'])) . " (termin {$faktura['termin']})\n"
		. ($faktura['kWh'] ? ("Forbruk:\t" . number_format($faktura['kWh'], 0, ",", " ") . " kWh\n") : "")
		. "Fakturabeløp:\tkr. " . number_format($faktura['fakturabeløp'], 2, ",", " ") . "\n\nStrømanlegg:\t{$faktura['anleggsnr']} {$faktura['formål']}\n"
		. "Måler:\t{$faktura['målernummer']}\n";
		$tekst .= "\nStrømmen er fordelt ut ifra følgende fordelingsnøkkel:\n";
		$sql = "SELECT * FROM fs_fordelingsnøkler WHERE fordelingsmåte = 'Fastbeløp' AND anleggsnummer = '{$faktura['anleggsnr']}' ORDER BY leieobjekt, leieforhold";
		$fastnokler = $this->arrayData($sql);
		$sql = "SELECT * FROM fs_fordelingsnøkler WHERE fordelingsmåte = 'Prosentvis' AND anleggsnummer = '{$faktura['anleggsnr']}' ORDER BY leieobjekt, leieforhold";
		$prosentnokler = $this->arrayData($sql);
		$sql = "SELECT * FROM fs_fordelingsnøkler WHERE fordelingsmåte = 'Andeler' AND anleggsnummer = '{$faktura['anleggsnr']}' ORDER BY leieobjekt, leieforhold";
		$andelsnokler = $this->arrayData($sql);
		foreach($fastnokler['data'] as $nokkel){
			$tekst .= "\tet direkte oppgitt beløp";
			if(count($this->dagensBeboere($nokkel['leieobjekt'])) == 1)
				$tekst .= " betales av ";
			else
				$tekst .= " fordeles mellom ";
			if($nokkel['følger_leieobjekt'])
				$tekst .= "leietakerne i " . $this->leieobjekt($nokkel['leieobjekt'], true);
			else
				$tekst .= $this->liste($this->kontraktpersoner(($nokkel['leieforhold'])));
			$tekst .= "\n";
		}
		foreach($prosentnokler['data'] as $nokkel){
			$tekst .= "\t" . number_format($nokkel['prosentsats'] * 100, 0, ",", " ") . "%";
			if(count($this->dagensBeboere($nokkel['leieobjekt'])) == 1)
				$tekst .= " betales av ";
			else
				$tekst .= " fordeles mellom ";
			if($nokkel['følger_leieobjekt'])
				$tekst .= "leietakerne i " . $this->leieobjekt($nokkel['leieobjekt'], true);
			else
				$tekst .= $this->liste($this->kontraktpersoner(($nokkel['leieforhold'])));
			$tekst .= "\n";
		}
		foreach($andelsnokler['data'] as $nokkel){
			$tekst .= "\t" . $nokkel['andeler'] . ($nokkel['andeler'] > 1 ? " deler" : " del");
			$beboere = $this->dagensBeboere($nokkel['leieobjekt']);
			if(count($beboere) == 1)
				$tekst .= " betales av ";
			else
				$tekst .= " betales av hvert leieforhold i ";
			if($nokkel['følger_leieobjekt'])
				$tekst .= $this->leieobjekt($nokkel['leieobjekt'], true);
			else
				$tekst .= $this->liste($this->kontraktpersoner(($nokkel['leieforhold'])));
			$tekst .= "\n";
		}
		$tekst .= "\n" . $this->valg['strømfordelingstekst'] . "\n";

		$tekst .= "\nUt ifra fordelingsnøklene fordeles fakturaen sånn:\n";

		$sql = "SELECT kontraktnr FROM fs_andeler WHERE faktura = '{$faktura['fakturanummer']}' GROUP BY kontraktnr ORDER BY kontraktnr";
		$leietakere = $this->arrayData($sql);
		foreach($leietakere['data'] as $leietaker){
			$tekst .= $this->liste($this->kontraktpersoner($leietaker['kontraktnr'])) . ":\n";
			$sql = "SELECT kontraktnr, tekst, beløp FROM fs_andeler WHERE faktura = '{$faktura['fakturanummer']}' AND kontraktnr = '{$leietaker['kontraktnr']}' ORDER BY kontraktnr, fom, tom";
			$andeler = $this->arrayData($sql);
			foreach($andeler['data'] as $andel){
				$tekst .= "\t\t\t{$andel['tekst']}: kr. " . number_format($andel['beløp'], 2, ",", " ") . "\n";
			}
		}
		$pdf->Write(4, utf8_decode($tekst));
	}
//		die($tekst);
	$pdf->Output('fordeling.pdf', 'I');

}


// Generer KID
// Lager KID på grunnlag av kontrakt/leieforholdnummer og evt gironummer
/****************************************/
//	$kontraktnr (heltall) leieforholdnummer eller gironummer
//	--------------------------------------
//	resultat: (streng) KID
public function genererKid( $kontraktnr, $gironr = null ) {

	if( !$kontraktnr and !$gironr ) {
		return "";
	}
	
	$type = 0;

//	Gammel 7-sifret KID:
// $resultat	= $gironr
// 			? (200000 + $gironr)
// 			: (100000 + $this->leieforhold( $kontraktnr ));
// return $resultat . $this->kontrollsiffer($resultat);
//
	
	// Beregn et KID-nummer uten kontrollsiffer (= 12 siffer)
	$resultat	= str_pad( (
					10000000	* strval($this->leieforhold( $kontraktnr, false ))
					+ 1000000	* $type
					+ $gironr
				),
				12, "0", STR_PAD_LEFT );
	
	// Legg til kontrollsifferet til slutt
	return $resultat . $this->kontrollsiffer( $resultat );
}


public function hentGiro($gironr){
	return $this->mysqli->arrayData(array(
		'source' => "giroer",
		'where' => "gironr = '{$gironr}'",
	))->data[0];
}


public function hentPurreforslag() {
	$datogrense = leggtilIntervall(time(), preg_replace('P', 'P-', $this->valg['purreintervall']));
	$this->oppdaterUbetalt();
	$sql =	"SELECT krav.id\n"
	.		"FROM (krav LEFT JOIN purringer ON purringer.krav = krav.id)\n"
	.		"INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr\n"
	.		"WHERE kontrakter.frosset = 0 AND krav.utestående > 0\n"
	.		"GROUP BY krav, forfall\n"
	.		"HAVING IFNULL(MAX(purringer.purredato), forfall) < '" . date('Y-m-d', $datogrense) . "'\n";
	$resultat = $this->arrayData($sql);
	return $resultat['data'];
}


// Hent et objekt
// Spesialtilpasset funksjon som brukes istedet for new på egendefinerte objekter.
/****************************************/
//	$class: (streng):
//	$arg:	argumenter som sendes til constructor
//	--------------------------------------
//	resultat: object
public function hent($class, $arg = null) {
	$objekt = new $class($arg);
	return $objekt;
}


public function hentValg() {
	$sett = $this->mysqli->arrayData(array(
		'source'	=> "valg"
	))->data;
	foreach ($sett as $valg) {
		$this->valg[$valg->innstilling] = $valg->verdi;
	}
}


// Purringer kan gebyrlegges
// Denne funksjonen sjekker om en faktisk eller potensiell purring kan gebyrlegges.
/****************************************/
//	$kontroll: liste med kontroller:
//		purring:	id for purringen som skal sjekkes.
//		giro:		alternativt til purring kan det sjekkes om en giro kan purres med gebyr
//	--------------------------------------
//	resultat: suksessangivelse
public function kanGebyrlegges($kontroll = array()) {
	if ( !is_array($kontroll) && !is_object($kontroll) ) {
		$kontroll = array($kontroll);
	}
	settype($kontroll, 'object');
	settype($kontroll->purring, 'integer');

	if($this->valg['purregebyr'] == 0) {
		return (object)array(
			'msg'		=> "Det er ikke angitt purregebyr i leiebasens innstillinger",
			'success'	=> false
		);
	}

	$purring = (object)$this->mysqli->arrayData(array(
		'source'	=> "purringer LEFT JOIN krav ON purringer.krav = krav.id",
		'where'		=> "purringer.nr = '{$kontroll->purring}'
						AND purringer.purregebyr IS NULL
						AND krav.type != 'Purregebyr'",
		'fields'	=> "purringer.purredato, krav.gironr, krav.forfall"
	))->data[0];
	$tidspkt = strtotime($purring->purredato);
	if(!$tidspkt) {
		$tidspkt = time();
	}
	$giro = $purring->gironr;
	if(!$giro) {
		if($kontroll->purring) {
			return (object)array(
				'msg'		=> "Kan ikke gebyrlegges",
				'success'	=> false
			);
		}
		$giro = (int)$kontroll->giro;
	}

	if(!$giro) {
		return (object)array(
			'msg'		=> "Finner ikke gironr",
			'success'	=> false
		);
	}

	if( $this->leggtilIntervall(strtotime( $purring->forfall ), "P14D" ) >= $tidspkt) {
		return (object)array(
			'msg'		=> date('d.m.Y', $tidspkt) . " er for tett opp til opprinnelig forfall {$purring->forfall}",
			'success'	=> false
		);
	}


	$sql = "
		SELECT krav.id, krav.forfall, MAX(purringer.purredato) AS sistegebyr, MAX(purringer.purreforfall) AS purreforfall, COUNT(purringer.nr) AS antallgebyr
		FROM krav
		LEFT JOIN purringer ON krav.id = purringer.krav
		WHERE krav.gironr = '{$giro}'
		AND purringer.purregebyr IS NOT NULL
		" . ($kontroll->purring ? "AND purringer.nr < '{$kontroll->purring}'" : "") . "
		GROUP BY krav.id
		ORDER BY COUNT(purringer.nr) DESC
	";	
	$status = (object)$this->mysqli->arrayData(array(
		'sql' => $sql
	))->data[0];	
	
	
	if( $status->antallgebyr > 1) {
		return (object)array(
			'msg'		=> "{$status->antallgebyr} gebyr ilagt allerede.",
			'success'	=> false
		);
	}
	if( $this->leggtilIntervall(strtotime( $status->sistegebyr ), "P14D" ) >= $tidspkt) {
		return (object)array(
			'msg'		=> "For tett opp til forrige gebyr<br>\n{$sql}",
			'success'	=> false
		);
	}
	if( $this->leggtilIntervall(strtotime( $status->purreforfall ), "P14D" ) >= $tidspkt) {
		return (object)array(
			'msg'		=> "For tett opp til purreforfall",
			'success'	=> false
		);
	}
	

	return (object)array(
		'success'	=> true
	);
}


public function katalog($fil) {
	$bane = array_reverse(explode("/", $fil));
	return $bane[1];
	
}


// returnerer beskrivelse av oppgitt leieobjekt.
// Om inklnr er sann taes leieobjektnummeret med i beskrivelsen
public function kontrakt($kontraktnr) {
	$sql =	"SELECT * FROM kontrakter WHERE kontraktnr = $kontraktnr";
	if($a = $this->arrayData($sql))
		return $a['data'][0];
	else return false;
}


// returnerer adressen en leieavtale skal sendes til
public function kontraktadresse($kontraktnr){
	$adresse = "";
	$sql =	"SELECT kontrakter.regningsperson, regningsadresse1, regningsadresse2, kontrakter.postnr AS regningspostnr, kontrakter.poststed AS regningspoststed, personer.*\n"
		.	"FROM kontrakter LEFT JOIN personer ON kontrakter.regningsperson = personer.personid\n"
		.	"WHERE kontrakter.kontraktnr = $kontraktnr";
	$kontrakt = $this->arrayData($sql);
	$kontrakt = $kontrakt['data'][0];
	if($kontrakt['regningsperson']){
		$adresse .= $kontrakt['adresse1'] ? ($kontrakt['adresse1'] . "<br />") : "";
		$adresse .= $kontrakt['adresse2'] ? ($kontrakt['adresse2'] . "<br />") : "";
		$adresse .= $kontrakt['postnr'] . " " . $kontrakt['poststed'];
	}
	else{
		$adresse .= $kontrakt['regningsadresse1'] ? ($kontrakt['regningsadresse1'] . "<br />") : "";
		$adresse .= $kontrakt['regningsadresse2'] ? ($kontrakt['regningsadresse2'] . "<br />") : "";
		$adresse .= $kontrakt['regningspostnr'] . " " . $kontrakt['regningspoststed'];
	}
	
	return $adresse;
}


// returnerer leieobjektet i angitt kontrakt.
public function kontraktobjekt($kontraktnr) {
	$sql =	"SELECT leieobjekt FROM kontrakter WHERE kontraktnr = '$kontraktnr'";
	$a = $this->arrayData($sql);
	return @$a['data'][0]['leieobjekt'];
}


//	returnerer et array bestående av leietakernes navn indexert etter deres adressekorts ID
public function kontraktpersoner($kontraktnr) {
	$sql =	"SELECT person AS id, IF(etternavn IS NULL, leietaker, IF(er_org, etternavn, CONCAT(fornavn, ' ', etternavn))) AS navn\n"
	.		"FROM kontraktpersoner LEFT JOIN personer ON kontraktpersoner.person = personer.personid\n"
	.		"WHERE kontraktpersoner.kontrakt = '{$kontraktnr}' AND slettet IS NULL";
	$a = $this->arrayData($sql);
	$resultat = array();
	foreach($a['data'] as $person) {
		$resultat[$person['id']] = $person['navn'];
	}
	return $resultat;
}


// Kontrollrutinen ser om automatiske kronprosedyrer har blitt utført siste 24 timer
public function kontroller_adresseoppdateringer(){
	$ikkeOppdaterte = $this->mysqli->arrayData(array(
		'fields' => array(
						"personer.personid",
						"personer.fornavn",
						"personer.etternavn",
						"personer.adresse1",
						"personer.adresse2"
					),
		'source' =>	"personer
					INNER JOIN kontrakter on personer.personid = kontrakter.regningsperson
					INNER JOIN krav ON kontrakter.kontraktnr=krav.kontraktnr
					INNER JOIN leieobjekter ON kontrakter.leieobjekt=leieobjekter.leieobjektnr
					LEFT JOIN 
						(SELECT distinct kontraktpersoner.person AS id
						FROM kontraktpersoner INNER JOIN kontrakter ON kontraktpersoner.kontrakt = kontrakter.kontraktnr
						LEFT JOIN oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold
						where (oppsigelser.leieforhold IS NULL or oppsigelser.fristillelsesdato > NOW())
						and slettet IS NULL)
						AS beboere ON personer.personid = beboere.id",
		'where' =>	"!regning_til_objekt
					AND beboere.id IS NULL
					AND (personer.adresse1 = leieobjekter.gateadresse OR personer.adresse2 = leieobjekter.gateadresse)
					AND personer.postnr = leieobjekter.postnr
					AND krav.utestående
					AND !kontrakter.frosset",
		'distinct' => true
	));
	if($ikkeOppdaterte->totalRows) {
		$eks = rand(0, $ikkeOppdaterte->totalRows - 1);
		$this->advarsler[2][] = array(
			'oppsummering'	=> $this->navn($ikkeOppdaterte->data[$eks]->personid) . " sitt adressekort bør oppdateres",
			'tekst'			=> $this->navn($ikkeOppdaterte->data[$eks]->personid) . ($ikkeOppdaterte->totalRows > 2 ? " og " . ($ikkeOppdaterte->totalRows - 1) . " andre" : "") . " sitt adressekort viser fortsatt samme adresse som før utflytting, og giroer sendt i posten vil kanskje ikke komme fram.<br>Klikk <a href=\'index.php?oppslag=personadresser_skjema&id={$ikkeOppdaterte->data[$eks]->personid}\'>her</a> for å oppdatere med rett kontaktinformasjon."
		);
	}
}


/*	Kontroller betalingsutlikninger
Sjekker om betalinger utliknet mot hverandre er i balanse
******************************************
------------------------------------------
retur (array): et objekt for hvert leieforhold med egenskapene
	leieforhold (Leieforhold-objekt): Leieforholdet med ubalanse
	sisteBetaling (Innbetaling-objekt): Den siste betalinga som har ført til
					ubalansen
	balanse (tall): Den aktuelle ubalansen i form av beløp.
					Positiv balanse betyr at det er overvekt på innbetalinger
					Negativ balanse betyr at det er overvekt på utbetalinger
*/
public function kontrollerBetalingsutlikninger() {
	$tp = $this->mysqli->table_prefix;
	
	$resultat = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}innbetalinger as innbetalinger",
		'where'			=> "innbetalinger.krav = '0' AND innbetalinger.konto != '0'",
		'groupfields'	=> "innbetalinger.leieforhold",
		'fields'		=> "innbetalinger.leieforhold,\n"
						.	"SUM(innbetalinger.beløp) AS balanse,\n"
						.	"MAX(IF(innbetalinger.beløp > 0, innbetaling, null)) AS siste_inn,\n"
						.	"MAX(IF(innbetalinger.beløp < 0, innbetaling, null)) AS siste_ut\n",
		'having'		=> "SUM(innbetalinger.beløp) != 0"
	))->data;
	
	foreach($resultat as $ubalansert) {
		$ubalansert->leieforhold = $this->hent('Leieforhold', $ubalansert->leieforhold);
		if($ubalansert->balanse > 0 ) {
			$ubalansert->sisteBetaling = $this->hent('Innbetaling', $ubalansert->siste_inn);
		}
		else {
			$ubalansert->sisteBetaling = $this->hent('Innbetaling', $ubalansert->siste_ut);
		}
		$this->advarsler[0][] = array(
			'oppsummering'	=> "Ubalanse i utlikning av betalinger",
			'tekst'			=> "Det er ubalanse i inn- og utbetalinger som har blitt utliknet mot hverandre for leieforhold {$ubalansert->leieforhold} {$ubalansert->leieforhold->hent('beskrivelse')}.<br>Ubalansen kan skyldes en teknisk svikt.<br>Prøv å åpne <a href=\'index.php?oppslag=utlikninger_skjema\'>utlikningsskjemaet</a> for å korrigere dette.<br>Du kan også prøve å løsne de problematiske betalingene fra utlikning mot andre betalinger, for deretter å utlikne de på nytt:<br>Balanse: {$this->kr($ubalansert->balanse)} for mye på " . ( $ubalansert->balanse > 0 ? "inn" : "ut") . "betalinger<br>Siste betaling: <a href=\'index.php?oppslag=innbetalingskort&id={$ubalansert->sisteBetaling}\'>[Klikk her]</a>"
		);
	}
	return $resultat;
}


// Kontrollrutinen ser om automatiske kronprosedyrer har blitt utført siste 24 timer
public function kontroller_cron(){
	if(($this->valg['cronsuksess'] + 24 * 3600) < time()) {
		$this->advarsler[1][] = array(
			'oppsummering'	=> "Problemer med cron-jobber.",
			'tekst'			=> "Det ser ikke ut til at automatiske cron-prosedyrer har blitt utført på over 24 timer.<br />Dette skyldes muligens en feilkonfigurering av serveren som leiebasen er installert på.<br /><br />Klikk <a target=\'_blank\' href=\'../cron.php\'>her</a> for å kjøre prosedyrene manuelt.<br />La den blanke siden som åpner seg laste ferdig (det kan ta noen minutter). Last deretter leiebasevinduet på nytt.<br /><br />Ta evt kontakt med serverleverandør for å sette opp automatisk cronjobb som bør utføres ca en gang hver time."
		);
	}
}


// Kontrollrutinen ser etter innbetalinger som ikke er utlikna.
public function kontroller_giroutskrifter(){
	$framtildato = date('Y-m-d', $this->leggtilIntervall(time(), $this->valg['forfallsfrist']));
	$sjekk = $this->mysqli->arrayData(array(
		'source' => "krav LEFT JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
		'fields' => "count(krav.id) AS antall, forfall, if(forfall<=NOW(),1,0) AS forfalt",
		'where' => "utestående and !frosset and utskriftsdato IS NULL and forfall <= '$framtildato'",
		'groupfields' => "if(forfall<=NOW(),1,0), forfall",
		'orderfields' => "forfalt DESC, forfall",
		'returnQuery' => true
	));
	if($sjekk->totalRows) {
		$this->advarsler[$sjekk->data[0]->forfalt ? 1 : 2][] = array(
			'oppsummering'	=> "Det er ikke skrevet ut giroer.",
			'tekst'			=> "Det har " . ($sjekk->data[0]->forfalt ? "fortsatt" : "ennå") . " ikke blitt skrevet ut giroer for {$sjekk->data[0]->antall} krav som " . ($sjekk->data[0]->forfalt ? "har forfalt " : "forfaller ") . date('d.m.Y', strtotime($sjekk->data[0]->forfall)) . ".<br /><br />Klikk <u><a href=\'index.php?oppslag=utskriftsmeny&tildato={$sjekk->data[0]->forfall}&returi=default\'>her</a></u> for å skrive ut disse."
		);
	}
}


// Kontrollrutinen ser etter innbetalinger som ikke er utlikna.
public function kontroller_innbetalinger(){
	$framtildato = $this->leggtilIntervall(time(), 'P-1M');
	$sql =	"SELECT MIN(dato) AS fra FROM innbetalinger\n"
		.	"WHERE krav is null";
	$a = $this->arrayData($sql);
	if( isset( $a['data'][0]['fra'] ) ) {
		$this->advarsler[strtotime($a['data'][0]['fra']) < $framtildato ? 1 : 2][] = array(
			'oppsummering'	=> "Det er registrert innbetalinger som ikke er utlikna.",
			'tekst'			=> "Det er registrert innbetalinger som ikke er utlikna mot betalingskrav.<br /><br />Klikk <a href=\'index.php?oppslag=utlikninger_skjema\'>her</a> for å angi hva betalingene gjelder."
		);
	}
}


/*	Kontroller kredittbalanse
Tilser at summen av betalingsdelen av kreditt alltid er 0.
******************************************
------------------------------------------
*/
public function kontrollerKredittbalanse() {
	$tp = $this->mysqli->table_prefix;
	$sjekk = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}innbetalinger as innbetalinger",
		'where'			=> "innbetalinger.konto = '0'",
		'groupfields'	=> "innbetalinger.innbetaling, innbetalinger.leieforhold",
		'fields'		=> "innbetalinger.innbetaling,\n"
						.	"SUM(innbetalinger.beløp) AS balanse\n",
		'having'		=> "SUM(innbetalinger.beløp) != 0"
	))->data;
	
	foreach($sjekk as $ubalansert) {
		$varsel = array(
			'oppsummering'	=> "Ubalanse i kreditt",
			'tekst'			=> "Det har oppstått en ubalanse i kredittransaksjonene i leiebasen.<br>Summen av kredittransaksoner lagret som innbetaling \'{$ubalansert->innbetaling}\' er {$this->kr($ubalansert->balanse)}.<br>Denne skulle vært 0.<br>Avviket skyldes en teknisk svikt i leiebasen som bør rettes."
		);
	
		$this->advarsler[0][] = $varsel;
		
		$this->sendMail(array(
			'to'		=> "kyegil@gmail.com",
			'subject'	=> $varsel['oppsummering'],
			'html'		=> $varsel['tekst']
		));
	}

	$sjekk = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}krav as krav LEFT JOIN {$tp}innbetalinger as innbetalinger ON krav.id = innbetalinger.krav",
		'where'			=> "krav.beløp < 0 AND(innbetalinger.beløp != krav.beløp or innbetalinger.konto != '0')",
		'fields'		=> "krav.id\n"
	))->data;
	
	foreach($sjekk as $ubalansert) {
		$varsel = array(
			'oppsummering'	=> "Feil i kreditt",
			'tekst'			=> "Det har oppstått en feil i kredittene i leiebasen.<br>Kreditt {$ubalansert->id} er ikke lagret riktig.<br>Dette skyldes en teknisk svikt i leiebasen som bør rettes."
		);
	
		$this->advarsler[0][] = $varsel;
		
// 		$this->sendMail(array(
// 			'to'		=> "kyegil@gmail.com",
// 			'subject'	=> $varsel['oppsummering'],
// 			'html'		=> $varsel['tekst']
// 		));
	}

	$sjekk = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}innbetalinger as innbetalinger LEFT JOIN {$tp}krav as krav ON innbetalinger.krav = krav.id",
		'where'			=> "innbetalinger.konto = '0' AND innbetalinger.beløp < 0 AND krav.id IS NULL"
	))->data;
	
	foreach($sjekk as $ubalansert) {
		$varsel = array(
			'oppsummering'	=> "Feil i kreditt",
			'tekst'			=> "Det har oppstått en feil i kredittene i leiebasen.<br>Betaling {$ubalansert->innbetaling} har {$this->kr($ubalansert->beløp)} ikke knyttet til et kredittkrav.<br>Dette skyldes en teknisk svikt i leiebasen som bør rettes."
		);
	
		$this->advarsler[0][] = $varsel;
		
// 		$this->sendMail(array(
// 			'to'		=> "kyegil@gmail.com",
// 			'subject'	=> $varsel['oppsummering'],
// 			'html'		=> $varsel['tekst']
// 		));
	}

}


// Kontrollrutinen ser om det har vært problemer med henting av OCR-fil
public function kontroller_ocr(){
	if($this->valg['OCR_feilmelding']) {
		$this->advarsler[1][] = array(
			'oppsummering'	=> "Problemer med å hente OCR betalingsinformasjon.",
			'tekst'			=> $this->valg['OCR_feilmelding']
		);
	}
}


// Kontrollrutinen sjekker om alle innbetalingene har
// blitt registrert i henhold til ocr-forsendelsene
public function kontrollerOcrInnbetalinger() {
	$tp = $this->mysqli->table_prefix;
	
	$sjekk = $this->mysqli->arrayData(array(
		'source' => "{$tp}innbetalinger AS innbetalinger inner join {$tp}OCRdetaljer AS OCRdetaljer ON innbetalinger.ocrtransaksjon = OCRdetaljer.id",
		'having' => "OCRdetaljer.beløp != sum(innbetalinger.beløp)",
		'fields' => "OCRdetaljer.filid, OCRdetaljer.forsendelsesnummer, OCRdetaljer.oppdragsdato, OCRdetaljer.beløp, sum(innbetalinger.beløp) as registrert_beløp",
		'groupfields' => "OCRdetaljer.id"
	));

	if( $sjekk->totalRows ) {
		
		$tekst = "Det er uoverenstemmelse i mellom oppgitt beløp og registrerte innbetalinger i følgende OCR-forsendelser:<br>";
		
		foreach( $sjekk->data as $fil ) {
			$tekst .= "<a title=\\'Klikk her for å inspisere OCR-forsendelsen\\' href=\\'index.php?oppslag=ocr_kort&id=" . $fil->filid . "\\'>Forsendelse " . $fil->forsendelsesnummer . " " . date_create($fil->oppdragsdato)->format('d.m.Y') . "</a><br>";
		}
		$tekst .= "Dette skyldes en teknisk svikt i leiebasen som bør repareres.";
	
		$this->advarsler[0][] = array(
			'oppsummering'	=> "Mangler i innlesing av innbetalinger.",
			'tekst'			=> $tekst
		);
	}
}


// Kontrollrutinen ser etter innbetalinger som ikke er utlikna.
public function kontroller_oppfølgingspåminnelser() {
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

	if( count( $utløptePauser ) ) {
		$this->advarsler[2][] = array(
			'oppsummering'	=> "Påminnelse om oppfølging",
			'tekst'			=> "Oppfølging av " . count( $utløptePauser ) . " leieforhold, som har vært satt midlertidig på vent, kan nå gjenopptaes.<br>Gå til <a href=\'../oppfolging/index.php\'>oppfølgingsområdet</a> for detaljer, og for å slå av denne påminnelsen."
		);
	}
}


public function kontroller_utlop(){
	$framtildato = $this->leggtilIntervall(time(), 'P1M');
	$a = $this->mysqli->arrayData(array(
		'distinct'	=> true,
		'source'	=> "kontrakter LEFT JOIN oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold",
		'where'		=> "oppsigelser.leieforhold IS NULL",
		'having'	=> "MAX(kontrakter.tildato) IS NOT NULL",
		'fields'	=> "MAX(kontrakter.kontraktnr) AS kontraktnr, kontrakter.leieforhold, MAX(kontrakter.fradato) AS fradato, MAX(kontrakter.tildato) AS tildato, kontrakter.leieobjekt",
		'orderfields'	=>	"MAX(kontrakter.tildato)",
		'groupfields'	=>	"kontrakter.leieforhold, kontrakter.leieobjekt"
	));
	$b = array();
	$c = array();
	
	foreach($a->data as $kontrakt) {
		if(!$this->oppsagt($kontrakt->kontraktnr) and $this->sluttdato($kontrakt->kontraktnr) < $framtildato) {
			if($this->sluttdato($kontrakt->kontraktnr) < time()){
				$b[$kontrakt->kontraktnr] = $kontrakt;
			}
			else{
				$c[$kontrakt->kontraktnr] = $kontrakt;
			}
		}
	}

	if(count($b) > 2) {
		$this->advarsler[1][] = array(
			'oppsummering'	=> count($b) . " leieavtaler har utløpt",
			'tekst'			=> count($b) . " leieavtaler har utløpt uten å ha blitt registrert fornyet eller avsluttet.<br />Klikk <u><a href=\'index.php?oppslag=oversikt_fornyelser\'>her</a></u> for å åpne oversikten over leieavtaler som skulle vært fornyet."
		);
	}
	else {
		foreach($b as $d) {
			$this->advarsler[1][] = array(
				'oppsummering'	=> $this->liste($this->kontraktpersoner($d->kontraktnr)) . " sin leieavtale i " . $this->leieobjekt($d->leieobjekt, 1) . " har utløpt",
				'tekst'			=> $this->liste($this->kontraktpersoner($d->kontraktnr)) . " sin leieavtale i " . $this->leieobjekt($d->leieobjekt, true) . " har utløpt uten å ha blitt registrert fornyet eller avsluttet.<br />Klikk <u><a href=\'index.php?oppslag=leieforholdkort&id={$this->leieforhold($d->kontraktnr)}\'>her</a></u> for å åpne leieavtalen slik at denne kan fornyes eller sies opp."
			);
		}
	}
	if(count($c) > 2){
		$this->advarsler[2][] = array(
			'oppsummering'	=> "Flere leieavtaler bør fornyes",
			'tekst'			=> "Flere leieavtaler er i ferd med å utløpe, og bør fornyes så snart som mulig.<br />Klikk <u><a href=\'index.php?oppslag=oversikt_fornyelser\'>her</a></u> for å åpne oversikten over leieavtaler som utløper i nærmeste framtid."
		);
	}
	else{
		foreach($c as $d){
			$this->advarsler[2][] = array(
				'oppsummering'	=> $this->liste($this->kontraktpersoner($d->kontraktnr)) . " sin leieavtale i " . $this->leieobjekt($d->leieobjekt, 1) . " bør fornyes innen " . date('d.m.Y', strtotime($d->tildato) + 86400),
				'tekst'			=> $this->liste($this->kontraktpersoner($d->kontraktnr)) . " sin leieavtale i " . $this->leieobjekt($d->leieobjekt, true) . " utløper den " . date('d.m.Y', strtotime($d->tildato)) . ".<br />Klikk <u><a href=\'index.php?oppslag=leieforholdkort&id={$this->leieforhold($d->kontraktnr)}\'>her</a></u> for å åpne leieavtalen slik at denne kan fornyes eller sies opp."
			);
		}
	}
}


// Kontrollrutinene tester leiebasen og ser etter viktige ting som bør taes hånd om.
public function kontrollrutiner() {
	$this->kontroller_cron();
	$this->kontrollerKredittbalanse();
	$this->kontroller_ocr();
	$this->kontrollerOcrInnbetalinger();
	$this->kontroller_utlop();
	$this->kontroller_innbetalinger();
	$this->kontroller_oppfølgingspåminnelser();
	$this->kontroller_giroutskrifter();
	$this->kontroller_adresseoppdateringer();
}


// Beregner kontrollsiffer i modulus 10, som utgjør siste siffer i et KID-nummer
/****************************************/
//	$kid:	(streng) KID-streng uten kontrollsiffer (siste siffer).
//	--------------------------------------
//	retur: kontrollsiffer som skal legges til KID-strengen
public function kontrollsiffer( $KID ) {

	// Det lages et array av KID-strengen, sånn at den kan
	// behandles fra høyre mot venstre
	$sifferliste = str_split( strrev( $KID ) );
	
	// Sifrene multipliseres med vekttallene 2 1 2 1 ... regnet fra høyre mot venstre
	foreach( $sifferliste as $plass => $verdi ){

		if( ( $plass/2 ) == (int)($plass/2) ) {
		
			// Siste siffer, og så annethvert siffer fra høyre mot venstre, dobles
			$sifferliste[ $plass ] = (string)( $verdi * 2 );
		}

		// Etter at enkelte siffer har blitt doblet
		// beregnes tverrsummen av hver posisjon
		$sifferliste[ $plass ] = $this->tverrsum( $sifferliste[ $plass ], false );
	}

	// Så beregnes tverrsummen av hele strengen
	$tverrsum = array_sum($sifferliste);

	// Entallsifferet i siffersummen trekkes fra 10 og resultatet blir kontrollsifferet
	$resultat = 10 - $tverrsum % 10;

	// Dersom Entallssifferet i siffersgummen blir 0, blir kontrollsifferet 0
	if($resultat == 10) {
		$resultat = 0;
	}

	return $resultat;
}


// Funksjon som formaterer en verdi som kroneverdi
/****************************************/
//	$verdi:	Verdien som skal formateres
//	$html:	(boolsk, normalt på) Om verdien skal formateres for $html
//	$prefiks:	(boolsk, normalt på) Setter kr foran beløpet
//	--------------------------------------
//	retur: tekststreng
public function kr( $verdi, $html = true, $prefiks = true ) {
	$resultat = str_replace(",00", ",–", ($prefiks ? "kr " : "") . number_format($verdi, 2, ",", " "));
	if($html) {
		return  "<span>" . str_replace(" ", "&nbsp;", $resultat) . "</span>";
	}
	else {
		return $resultat;
	}
}



// Henter alle aktuelle krav fra en gitt KID-streng
/****************************************/
//	$kid:	(streng) KID-streng
//	--------------------------------------
//	retur: array med alle ubetalte krav, eller false dersom KID har ugyldig lengde
public function kravFraKid( $kid ) {
	
	settype( $kid, 'string');
	
	// KID på 7 siffer
	if( strlen( $kid ) == 7 ) {

		// KID som begynner på 2 henviser til KID-nr på blankett
		if( substr( $kid, 0, 1 ) == 2 ) {
			return $this->mysqli->arrayData(array(
				'source'		=> "krav INNER JOIN giroer ON krav.gironr = giroer.gironr",
				'fields'		=> "krav.id",
				'class'			=> "Krav",
				'where'			=> "kid = '$kid'"
			))->data;
		}
	
		// KID som begynner på 1 inneholder leieforholdnummeret
		else if( substr( $kid, 0, 1 ) == 1 ) {
			return $this->mysqli->arrayData(array(
				'source'		=> "krav INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
				'fields'		=> "krav.id",
				'class'			=> "Krav",
				'where'			=> "leieforhold = '{$this->leieforholdFraKid( $kid )}'
									AND utestående <> 0"
			))->data;
		
		}
	
		else return array();
	}
	
	// KID på 13 siffer
	else if( strlen( $kid ) == 13 ) {

		// Dersom KID inneholder gironummer henvises til giroen med dette KID-nummeret
		if( intval(substr( $kid, 6, 6 )) ) {
			return $this->mysqli->arrayData(array(
				'source'		=> "krav INNER JOIN giroer ON krav.gironr = giroer.gironr",
				'fields'		=> "krav.id",
				'class'			=> "Krav",
				'where'			=> "kid = '$kid'"
			))->data;
		}
	
		// Dersom KID ikke inneholder gironummer brukes leieforhold og evt betalingstype
		else {
			$type = substr( $kid, 5, 1 );
			
			$filter = "";
			if( $type == 1 ) {
				$filter = "AND krav.type = 'Husleie'\n";
			}
			if( $type == 2 ) {
				$filter = "AND krav.type = 'Fellesstrøm'\n";
			}
		
			return $this->mysqli->arrayData(array(
				'source'		=> "krav INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
				'fields'		=> "krav.id",
				'orderfields'	=> "IF(krav.forfall, 0, 1), krav.forfall, krav.kravdato, krav.id",
				'class'			=> "Krav",
				'where'			=> "leieforhold = '{$this->leieforholdFraKid( $kid )}'
									AND utestående <> 0\n" . $filter
			))->data;
		
		}
	}

	else {
		// KID har ugyldig lengde
		return false;
	}
}


//	Lag utskriftsfil
//	Denne funksjonen samler giro-, purre- og andre objekter i et array,
//	og oppretter en pdf som lagres for utskrift.
/****************************************/
//	$param: liste med kontroller:
//		giroer:			sett med Giro-objekter som skal skrives ut
//		purringer:		sett med Purre-objekter som skal skrives ut
//		gebyrpurringer:	sett med id'ene for purringene som kan tillegges purregebyr
//		statusoversikter: sett med Leieforhold-objekter for statusoversikter
//		bruker: (streng) Navn på innlogget bruker
//		tidspunkt: 		DateTime-objekt
//	--------------------------------------
//	resultat: suksessangivelse
public function lagUtskriftsfil ($param) {
	settype($param,				'object');
	settype($param->giroer,		'array');
	settype($param->purringer,	'array');
	settype($param->gebyrpurringer,'array');
	settype($param->statusoversikter,'array');
	settype($param->bruker,		'string');
	settype($param->tidspunkt,	'object');
	
	$utskrift = array();

	$fil = "{$this->filarkiv}/giroer/_utskriftsbunke.pdf";
	if( file_exists( $fil ) ) {
		unlink( $fil );
	}

	foreach( $param->giroer as $gironr ) {
	
		// Se først etter alternative fakturaformater,
		// som eFaktura eller epostgiro
		$giro = $this->hent('Giro', $gironr );
		$leieforhold = $giro->hent('leieforhold');

		// Giroer som skal sendes med efaktura eller epost utelates
		if(
			 (!$this->valg['efaktura'] or !$leieforhold->hent('efakturaavtale') or $giro->hent('beløp') < 0)
			 and !$leieforhold->hent('epostgiro')
		 ) {
			$utskrift[] = $giro;
		}	
	}

	foreach( $param->purringer as $purring ) {
		$utskrift[] = $this->hent('Purring', $purring );
	}

	foreach( $param->statusoversikter as $leieforhold ) {
		$utskrift[] = $this->hent('Leieforhold', $leieforhold );
	}

	usort($utskrift, array($this, "utdelingsorden"));


	$pdf = new FPDF;
	$dato = new DateTime;
	$pdf->SetAutoPageBreak(false);
	foreach($utskrift as $side) {

		if($side instanceof Giro) {
			$side->gjengi('pdf_giro', array(
				'pdf' => $pdf,
				'utskriftsdato' => $param->tidspunkt
			));
		}

		elseif($side instanceof Purring) {
			$side->gjengi('pdf_purring', array(
				'pdf' => $pdf,
				'purregebyr'	=> (
					$this->valg['purregebyr']
					&& in_array($side->id, $param->gebyrpurringer)
				)
								? $this->valg['purregebyr']
								: false
			));
		}

		elseif($side instanceof Leieforhold) {
			$side->gjengi('pdf_statusoversikt', array(
				'pdf' => $pdf,
				'statusdato' => $param->tidspunkt
			));
		}

	}
	
	$pdf->Output( $fil, 'F');
	return true;

}



/*	Last tillegg
Laster knagger og metoder fra tilleggsmoduler
******************************************
$tillegg (array):	Listen over tillegg og konfigurasjoner
------------------------------------------
*/
public function lastTillegg( $tilleggsliste ) {
	settype($tilleggsliste, 'array');

	foreach( $tilleggsliste as $tillegg ) {
		$klasse = get_class($this);

		if( isset( $tillegg->modeller->{$klasse} ) ) {
			foreach(  $tillegg->modeller->{$klasse}->knagger as $stadium => $handlinger ) {
				$this->ved( $stadium, $handlinger );
			}
		}
	}
}



public function leggtilIntervall($timestamp, $intervall) {
	$enhet = substr($intervall, -1);
	$verdi = (int)substr($intervall, 1);
	$date_time_array = getdate($timestamp);
	$day = $date_time_array['mday'];
	$month = $date_time_array['mon'];
	$year = $date_time_array['year'];
	switch ($enhet) {
		case 'Y':
			$year += $verdi;
			break;
		case 'M':
			$month += $verdi;
			break;
		case 'D':
			$day += $verdi;
			break;
	}
	$timestamp = mktime(0, 0, 0, $month, $day, $year);
	return $timestamp;
}


// Finner leieforhold på grunnlag av kontraktnummer
/****************************************/
//	$kontrakt (heltall) kontraktnummeret
//	$objekt (boolsk, normalt av) Dersom på returneres Leieforhold-objektet istedetfor leieforholdnummeret
//	--------------------------------------
//	retur: (Heltall / Leieforhold-objekt)
// returnerer leieforholdnummeret for en leieavtale
public function leieforhold( $kontrakt, $somObjekt = false ) {
	if ($kontrakt instanceof Leieforhold and $kontrakt->hentId()) {
		return $kontrakt;
	}
	$kontrakt = intval(strval( $kontrakt ));
	
	$resultat = $this->mysqli->arrayData(array(
		'distinct'	=> true,
		'class'		=> $somObjekt ? "Leieobjekt" : null,
		'source'	=> "kontrakter",
		'fields'	=> "leieforhold",
		'where'		=> "kontraktnr = '$kontrakt'"
	));
	if( $resultat->success and $resultat->totalRows ) {
		if ($somObjekt) {
			return $this->hent('Leieforhold', $resultat->data[0]->leieforhold );
		}
		return $resultat->data[0]->leieforhold;
	}
	else {
		return false;
	}
}


// Finner leieforhold på grunnlag av eFaktura-referanse
/****************************************/
//	$ref (streng / heltall) eFaktura-referanse
//	--------------------------------------
//	retur: (Leieforhold-objekt)
public function leieforholdFraEfakturareferanse( $ref ) {
	$leieforhold = new Leieforhold( intval($ref) );
	if( !$leieforhold->hent( 'id' ) ) {
		return null;
	}
	return $leieforhold;
}


// Henter riktig leieforhold fra en gitt KID-streng
/****************************************/
//	$KID:	(streng) KID-streng
//	--------------------------------------
//	retur: Leieforhold-objekt, eller false dersom KID har ugyldig lengde
public function leieforholdFraKid( $kid ) {

	$kid = preg_replace( "/[^0-9]+/", "", $kid );	

	if( strlen( $kid ) == 7 ) {

		if(substr($kid, 0, 1) == 1) {
			return $this->hent( 'Leieforhold', intval( substr( $kid, 1, 5 ) ) );
		}
		
		else if(substr($kid, 0, 1) == 2) {

			$resultat = $this->mysqli->arrayData(array(
				'class'		=> "Leieforhold",
				'source'	=> "giroer",
				'fields'	=> "leieforhold AS id",
				'where'		=> "kid = '$kid'"
			));
			
			if( $resultat->totalRows ) {
				return $resultat->data[0];
			}
		}
		
		return false;

	}
	
	else if( strlen( $kid ) == 13 ) {
		return $this->hent( 'Leieforhold', intval( substr( $kid, 0, 5 ) ) );
	}
	
	return false;
}


// returnerer beskrivelse av oppgitt leieobjekt.
// Om inklnr er sann taes leieobjektnummeret med i beskrivelsen
// I kortversjonen taes vises kun 'bolig nr. XX' / 'lokale nr XX'
public function leieobjekt($leieobjektnr, $inklnr = false, $kortversjon = false) {
	$sql =	"SELECT * FROM leieobjekter WHERE leieobjektnr = '$leieobjektnr'";
	$a = $this->arrayData($sql);
	$a = $this->mysqli->arrayData(array(
		'source'	=> "leieobjekter",
		'where'		=> "leieobjektnr = '$leieobjektnr'"
	));

	$resultat = "";

	if( $a->totalRows ) {
		$a = $a->data[0];
		
		if($inklnr or $kortversjon) {
			$resultat = ($a->boenhet ? "bolig nr. " : "lokale nr. ") . $a->leieobjektnr;
			if($kortversjon) {
				return $resultat;
			}
			$resultat .= ": ";
		}
		$resultat .= ($a->navn ? ("{$a->navn} ") : "");
		$resultat .= $a->etg ? ($this->etasjerenderer($a->etg) . " ") : "";
		$resultat .= $a->beskrivelse ? ("{$a->beskrivelse} ") : "";
		$resultat .= $a->gateadresse;
	}

	return $resultat;
}


//	Lever epost
//	Sender ut 10 eposter ifra epostlageret
/****************************************/
//	--------------------------------------
//	resultat: suksessangivelse
public function leverEpost() {
	$eposter = $this->mysqli->arrayData(array(
		'source'	=> "epostlager",
		'orderfields'	=> "prioritet DESC, id ASC",
		'limit'	=> 10
	))->data;
	
	foreach( $eposter as $epost ) {
	
		$epost->innhold = wordwrap( $epost->innhold, 70, "\r\n" );
		$epost->til		= $epost->til
						? wordwrap( $epost->til, 70, "\r\n" )
						: null;
		$epost->hode	= $epost->hode
						? $epost->hode
						: null;
		$epost->param	= $epost->param
						? $epost->param
						: "";
						
		if( !$this->live ) {
 			$this->mysqli->query("DELETE FROM epostlager WHERE id = '{$epost->id}'");
 		}

		else if( $epost->param and mail( $epost->til, $epost->emne, $epost->innhold, $epost->hode, $epost->param ) ) {
			$this->mysqli->query("DELETE FROM epostlager WHERE id = '{$epost->id}'");
		}

		else if( !$epost->param and mail( $epost->til, $epost->emne, $epost->innhold, $epost->hode ) ) {
			$this->mysqli->query("DELETE FROM epostlager WHERE id = '{$epost->id}'");
		}
	}
}



// Setter sammen ei tekstliste over innholdet i et array
public function liste($array = array(), $skillestreng = ", ", $sisteskillestreng = " og ") {
	if(!is_array($array)) $array = array($array);
	$array = array_values($array);
	if(!is_array($array))
		return "";
	$streng = "";
	$ant = count($array);
	foreach($array as $nr=>$verdi) {
		$streng .= $verdi;
		if($nr < $ant-2) $streng .= $skillestreng;
		if($nr == $ant-2) $streng .= $sisteskillestreng;
	}
	return $streng;
}


public function navn($personid, $lenke = false) {
	if(is_array($personid)) {
		foreach($personid as $id) {
			$a = $this->mysqli->arrayData(array(
				'source'	=> "personer",
				'where'		=> "personid = '$id'"
			));
			
			if($a->totalRows) {
				$navn = $a->data[0]->er_org ? $a->data[0]->etternavn : "{$a->data[0]->fornavn} {$a->data[0]->etternavn}";
				$resultat[]	= $lenke ? "<a title=\"Klikk for å gå til adressekortet\" href=\"index.php?oppslag=personadresser_kort&id=$id>$navn</a>" : $navn;
			}

		}
		return $resultat;
	}

	$a = $this->mysqli->arrayData(array(
		'source'	=> "personer",
		'where'		=> "personid = '$personid'"
	));
	if( $a->totalRows ) {
		$navn = $a->data[0]->er_org ? $a->data[0]->etternavn : "{$a->data[0]->fornavn} {$a->data[0]->etternavn}";
		return	$lenke ? "<a title=\"Klikk for å gå til adressekortet\" href=\"index.php?oppslag=personadresser_kort&id=$personid>$navn</a>" : $navn;
	}
}



// Hent Efaktura-forsendelse fra NETS
// Kopler til NETS og overfører evt Efaktura-forsendelser til lokal server.
/****************************************/
//	$user (streng) Her er det mulig å oppgi eget Nets brukernavn, f.eks for testformål
//	--------------------------------------
//	resultat: false eller array med filbaner til nedlastede forsendelser
public function netsHentEfakturaForsendelser( $user = NETS_USER_EINVOICE ) {
 	set_include_path( PATH_TO_PHPSECLIB );
	require_once( PATH_TO_PHPSECLIB . 'Net/SSH2.php' );
	require_once( PATH_TO_PHPSECLIB . 'Net/SFTP.php' );
	require_once( PATH_TO_PHPSECLIB . 'Crypt/RSA.php' );

	define('NET_SFTP_LOGGING', NET_SFTP_LOG_COMPLEX);
	
	$key = new Crypt_RSA();
	$key->setPassword(NETS_KEY_PW_EINVOICE);
	$key->loadKey(file_get_contents(NETS_KEY_EINVOICE));

	$sftp = new Net_SFTP(NETS_IP_EINVOICE, NETS_PORT_EINVOICE, NETS_TIMEOUT_EINVOICE);
	$mnd = date('Y-m');

	$resultat = false;
	
	if ( !$this->live ) {
		$this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "valg",
			'where'		=> "innstilling = 'OCR_feilmelding'",
			'fields'	=> array(
				'verdi'	=>	"Forsøk på å hente Efaktura-forsendelse fra NETS<br />ble forhindret " . date('d.m.Y') . "  kl. " . date('H:i:s') . ",<br />fordi leiebasen er i testmodus."
			)
		));	
	}

	else if ($sftp->login(NETS_USER_EINVOICE, $key)) {
		$sftp->chdir("Inbound");
		$filer = $sftp->nlist();

		$this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "valg",
			'where'		=> "innstilling = 'OCR_feilmelding'",
			'fields'	=> array(
				'verdi'	=>	""
			)
		));

		foreach( $filer as $fil ) {

			if(!file_exists("{$this->filarkiv}/nets/inn/efaktura/{$mnd}")) {
				mkdir("{$this->filarkiv}/nets/inn/efaktura/{$mnd}", 0777);
			}
			
			if( $fil != "NOR984685556" ) {
				if( $sftp->get( $fil, "{$this->filarkiv}/nets/inn/efaktura/{$mnd}/{$fil}" ) ) {
					$resultat[] = "{$this->filarkiv}/nets/inn/efaktura/{$mnd}/{$fil}";
					// lagrer fila i filarkivet
				}
		
				else {
					$this->mysqli->saveToDb(array(
						'update'	=> true,
						'table'		=> "valg",
						'where'		=> "innstilling = 'OCR_feilmelding'",
						'fields'	=> array(
							'verdi'	=>	"Automatisk forsøk på å hente dataforsendelse fra NETS<br />mislyktes " . date('d.m.Y') . "  kl. " . date('H:i:s') . ".<br />Leiebasen klarte ikke laste ned følgende fil: {$fil}.<br />Denne fila må lastes ned manuelt fra NETS."
						)
					));
				}
			}
		}
	}
	
	else { // Klarte ikke logge inn hos NETS
		$this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "valg",
			'where'		=> "innstilling = 'OCR_feilmelding'",
			'fields'	=> array(
				'verdi'		=>	"Automatisk forsøk på å hente forsendelse fra NETS<br />"
				.	"mislyktes " . date('d.m.Y') . "  kl. " . date('H:i:s') . ".<br />"
				.	"Leiebasen klarte ikke logge inn hos NETS (adresse " . NETS_IP_EINVOICE . ":" . NETS_PORT_EINVOICE . " med bruker " . NETS_USER_EINVOICE . ").<br />"
				.	"Dersom problemet vedvarer må dataforsendelser hentes manuelt fra NETS."
			)
		));
	}
	
	return $resultat;
}



// Hent aktuelle forsendelser fra NETS
// Henter forsendelser i de aktuelle tjenestene fra NETS.
/****************************************/
//	--------------------------------------
//	resultat: array med filbaner til nedlastede forsendelser
public function netsHentForsendelser() {
	$resultat = array();

	if ( !$this->live ) {
		$this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "valg",
			'where'		=> "innstilling = 'OCR_feilmelding'",
			'fields'	=> array(
				'verdi'	=>	"Forsøk på å hente Efaktura-forsendelse fra NETS<br />ble forhindret " . date('d.m.Y') . "  kl. " . date('H:i:s') . ",<br />fordi leiebasen er i testmodus."
			)
		));
		return $resultat;
	}

	if ( $this->valg['ocr'] and $ocrForsendelse = $this->netsHentOcrForsendelser() ) {
		$resultat = array_merge( $resultat, $ocrForsendelse );
	}
	
	if ( $this->valg['efaktura'] and $efakturaForsendelse = $this->netsHentEfakturaForsendelser() ) {
		$resultat = array_merge( $resultat, $efakturaForsendelse );
	}	
	return $resultat;
}



// Hent OCR-forsendelse fra NETS
// Kopler til NETS og overfører evt OCR-forsendelser til lokal server.
/****************************************/
//	$user (streng) Her er det mulig å oppgi eget Nets brukernavn, f.eks for testformål
//	--------------------------------------
//	resultat: false eller array med filbaner til nedlastede forsendelser
public function netsHentOcrForsendelser( $user = NETS_USER_OCR ) {
 	set_include_path( PATH_TO_PHPSECLIB );
	require_once( PATH_TO_PHPSECLIB . 'Net/SSH2.php' );
	require_once( PATH_TO_PHPSECLIB . 'Net/SFTP.php' );
	require_once( PATH_TO_PHPSECLIB . 'Crypt/RSA.php' );

	define('NET_SFTP_LOGGING', NET_SFTP_LOG_COMPLEX);
	
	$key = new Crypt_RSA();
	$key->setPassword(NETS_KEY_PW_OCR);
	$key->loadKey(file_get_contents(NETS_KEY_OCR));

	$sftp = new Net_SFTP(NETS_IP_OCR, NETS_PORT_OCR, NETS_TIMEOUT_OCR);
	$mnd = date('Y-m');

	$resultat = false;
	
	if ( !$this->live ) {
		$this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "valg",
			'where'		=> "innstilling = 'OCR_feilmelding'",
			'fields'	=> array(
				'verdi'	=>	"Forsøk på å hente OCR-forsendelse fra NETS<br />ble forhindret " . date('d.m.Y') . "  kl. " . date('H:i:s') . ",<br />fordi leiebasen er i testmodus."
			)
		));
	
	}

	else if ($sftp->login(NETS_USER_OCR, $key)) {
		$sftp->chdir("Outbound");
		$filer = $sftp->nlist();

		$this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "valg",
			'where'		=> "innstilling = 'OCR_feilmelding'",
			'fields'	=> array(
				'verdi'	=>	""
			)
		));

		foreach( $filer as $fil ) {

			if(!file_exists("{$this->filarkiv}/nets/inn/ocr/{$mnd}")) {
				mkdir("{$this->filarkiv}/nets/inn/ocr/{$mnd}", 0777);
			}

			if( $sftp->get( $fil, "{$this->filarkiv}/nets/inn/ocr/{$mnd}/{$fil}" ) ) {
				$resultat[] = "{$this->filarkiv}/nets/inn/ocr/{$mnd}/{$fil}"; // lagrer fila i filarkivet
			}
		
			else {
				$this->mysqli->saveToDb(array(
					'update'	=> true,
					'table'		=> "valg",
					'where'		=> "innstilling = 'OCR_feilmelding'",
					'fields'	=> array(
						'verdi'	=>	"Automatisk forsøk på å hente dataforsendelse fra NETS<br />mislyktes " . date('d.m.Y') . "  kl. " . date('H:i:s') . ".<br />Leiebasen klarte ikke laste ned følgende fil: {$fil}.<br />Denne fila må lastes ned manuelt fra NETS."
					)
				));

			}
	
		}
		
		
		// Se også etter kvitteringsfiler for AvtaleGiro trekkoppgaver
		$sftp->chdir("../Inbound");
		$filer = $sftp->nlist();

		foreach( $filer as $fil ) {

			if(!file_exists("{$this->filarkiv}/nets/inn/AG-kvitteringer/{$mnd}")) {
				mkdir("{$this->filarkiv}/nets/inn/AG-kvitteringer/{$mnd}", 0777);
			}

			$sftp->get( $fil, "{$this->filarkiv}/nets/inn/AG-kvitteringer/{$mnd}/{$fil}" );
		}
	}
	
	else { // Klarte ikke logge inn hos NETS
		$this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "valg",
			'where'		=> "innstilling = 'OCR_feilmelding'",
			'fields'	=> array(
				'verdi'		=>	"Automatisk forsøk på å hente forsendelse fra NETS<br />"
				.	"mislyktes " . date('d.m.Y') . "  kl. " . date('H:i:s') . ".<br />"
				.	"Leiebasen klarte ikke logge inn hos NETS (adresse " . NETS_IP_OCR . ":" . NETS_PORT_OCR . " med bruker " . NETS_USER_OCR . ").<br />"
				.	"Dersom problemet vedvarer må dataforsendelser hentes manuelt fra NETS."
			)
		));
	}
	
	return $resultat;
}



//	Lag AvtaleGiro forsendelse til NETS
/****************************************/
//	$oppdrag: liste med stdClass NETS-oppdrag:
//	$prefiks (streng) 'Dirrem' for Avtalegiro-oppdrag
//	--------------------------------------
//	resultat: Suksessangivelse
public function netsLagAvtalegiroForsendelse ( $oppdrag = array(), $prefiks = 'Dirrem' ) {
	$tp = $this->mysqli->table_prefix;

	if( !is_array( $oppdrag ) ) {
		$oppdrag = array( $oppdrag );
	}

	$forsendelse = new NetsForsendelse;
	$forsendelse->dataavsender = $this->valg['nets_kundeenhetID'];
	$forsendelse->datamottaker = 8080;
	$forsendelse->forsendelsesnummer = $this->netsOpprettForsendelsesnummer();
	$forsendelse->produksjon = $this->live;
	$forsendelse->oppdrag = $oppdrag;

	if(!$filarray = $forsendelse->skriv()) {
		return $filarray;
	}

	// Lag en ny undermappe i filarkivet hver måned
	$mnd = date('Y-m');
	if(!file_exists("{$this->filarkiv}/nets/ut/{$mnd}")) {
		mkdir("{$this->filarkiv}/nets/ut/{$mnd}", 0777);
	}
	
	if(
		file_put_contents(
			"{$this->filarkiv}/nets/ut/{$mnd}/{$prefiks}" . date('Ymd') . ".txt",
			mb_convert_encoding(implode( "\n", $filarray ), 'ISO-8859-1', 'UTF-8') 
		)
		and
		$this->netsSendOcr("{$mnd}/{$prefiks}" . date('Ymd') . ".txt") 
	) {
		// Forsendelsen er sendt til nets

		// Oppdater datoen for siste trekkrav i innstillingene
		$this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "{$tp}valg",
			'where'		=> array(
				'innstilling'	=> "siste_fbo_trekkrav"
			),
			'fields'	=> array(
				'verdi'		=> date('Y-m-d')
			)
		));
		$this->hentValg();

		
		foreach( $forsendelse->oppdrag as $sendtOppdrag ) {
		
			// Før på forsendelsesnummer i fbo_trekkrav-tabellen
			if( $sendtOppdrag->tjeneste == 21 and $sendtOppdrag->oppdragstype  == 0) {
				$this->mysqli->saveToDb(array(
					'table'		=> "{$tp}fbo_trekkrav AS fbo_trekkrav",
					'update'	=>true,
					'where'		=> array(
						'oppdrag'	=> $sendtOppdrag->oppdragsnr
					),
					'fields'	=> array(
						'forsendelse'	=> $forsendelse->forsendelsesnummer
					)
				));
			}
		}
	
		return $forsendelse;
	}
	else {
		return false;
	}
}



//	Lag Efaktura forsendelse til NETS
/****************************************/
//	$oppdrag: liste med stdClass NETS-oppdrag:
//	$prefiks (streng) 'Prodefakbbs' for eFaktura
//	--------------------------------------
//	resultat: Suksessangivelse
public function netsLagEfakturaForsendelse ( $oppdrag = array(), $prefiks = 'prodefakbbs' ) {
	$tp = $this->mysqli->table_prefix;

	if( !is_array( $oppdrag ) ) {
		$oppdrag = array( $oppdrag );
	}

	$forsendelse = new NetsForsendelse;
	$forsendelse->dataavsender = $this->valg['nets_kundeenhetID'];
	$forsendelse->datamottaker = 8080;
	$forsendelse->forsendelsesnummer = $this->netsOpprettForsendelsesnummer();
	$forsendelse->produksjon = $this->live;
	$forsendelse->oppdrag = $oppdrag;

	if(!$filarray = $forsendelse->skriv()) {
		return $filarray;
	}

	// Lag en ny undermappe i filarkivet hver måned
	$mnd = date('Y-m');
	if(!file_exists("{$this->filarkiv}/nets/ut/{$mnd}")) {
		mkdir("{$this->filarkiv}/nets/ut/{$mnd}", 0777);
	}
	
	if(
		file_put_contents(
			"{$this->filarkiv}/nets/ut/{$mnd}/{$prefiks}" . date('Y') . str_pad($forsendelse->forsendelsesnummer, 7, '0', STR_PAD_LEFT) . ".txt",
			mb_convert_encoding(implode( "\n", str_replace(array('–'), array('-'), $filarray) ), 'ISO-8859-1', 'UTF-8') 
		)
		and
		$this->netsSendEfaktura("{$mnd}/{$prefiks}" . date('Y') . str_pad($forsendelse->forsendelsesnummer, 7, '0', STR_PAD_LEFT) . ".txt") 
	) {
		// Forsendelsen er sendt til nets
		return $forsendelse;
	}
	else {
		return false;
	}
}



//	Lag e-Fakturaoppdrag av giroer
//	Lager et oppdrag for inkludering i NETS-forsendelse.
/****************************************/
//	$giroer: liste med Giro-objekter:
//	--------------------------------------
//	resultat: stdClass-objekt
public function netsLagEfakturaOppdrag ( $giroer ) {

	if( !is_array( $giroer ) ) {
		$giroer = array( $giroer );
	}

	$oppdrag = (object)array(
		'tjeneste'					=> 42,
		'oppdragstype'				=> 03,
		'oppdragsnr'				=> $this->netsOpprettOppdragsnummer(),
		'oppdragskonto'				=> preg_replace('/[^0-9]+/', '', $this->valg['bankkonto']),
		'referanseFakturautsteder'	=> $this->valg['efaktura_referansenummer'],
		'transaksjoner'				=> array()
	);
	
	$antallFakturaer = 0;
	$sumBeløp = 0;
	$førsteForfall = null;
	$sisteForfall = null;

	foreach( $giroer as $indeks => $giro ) {
		$leieforhold = $giro->hent('leieforhold');
		
		if ($leieforhold->hent('efakturaavtale') ) {			
 			$oppdrag->transaksjoner[] = $giro;
		}
	}
	
	if( !$oppdrag->transaksjoner ) {
		return false;
	}
	
	return $oppdrag;
}



/*	Nets Neste forsendelse
Returnerer tidspunktet for neste planlagte forsendelse til NETS
*****************************************/
//	--------------------------------------
//	retur: (DateTime-objekt) Neste normerte NETS-forsendelse
public function netsNesteForsendelse() {
	$this->hentValg();
	$resultat = new DateTime;

	if( date('H') >= 14 or $this->valg['siste_fbo_trekkrav'] == date('Y-m-d') ) {
		$resultat->add(new DateInterval('P1D'));
	}
	$bankfridager = $this->bankfridager();
	while(
		in_array( $resultat->format('m-d'), $bankfridager )
		or $resultat->format('N') > 5 
	) {
		$resultat->add(new DateInterval('P1D'));
	}
	
	$resultat->setTime(14,0,0);
	return $resultat;
}



// Oppretter et nytt løpenummer for bruk i en NETS-forsendelse
// forsendelsesnummeret er i formatet måned dato løpenr (1231001)
// og er unikt innenfor en ettårsperiode.
/****************************************/
//	--------------------------------------
//	retur: (int) Forsendelsesnummer
public function netsOpprettForsendelsesnummer() {
	$forsendelsesnummer = $this->valg['nets_siste_forsendelsesnr'];
	
	if (substr($forsendelsesnummer, 0, -3) == date('md') ) {
		$forsendelsesnummer = date('md') . $this->fastStrenglengde(substr($forsendelsesnummer, 4) + 1, 3, "0", STR_PAD_LEFT);
	}
	else {
		$forsendelsesnummer = date('md') . "001";
	}
	$this->mysqli->saveToDb(array(
		'update'	=> true,
		'table'		=> "valg",
		'where'		=> "innstilling = 'nets_siste_forsendelsesnr'",
		'fields'	=> array(
			'verdi'		=> $forsendelsesnummer
		)
	));
	$this->hentValg();
	return $forsendelsesnummer;
}



// Oppretter et nytt oppdragsnummer for bruk i en NETS-forsendelse
// oppdragsnummeret er i formatet år (1 siffer) måned dato løpenr (2 siffer) (9123199)
// og er unikt innenfor en 10-årsperiode.
/****************************************/
//	--------------------------------------
//	retur: (int) Forsendelsesnummer
public function netsOpprettOppdragsnummer() {
	$oppdragsnummer = $this->valg['nets_siste_oppdragsnr'];
	$y = substr( date('y'), -1); // Siste sifferet i årstallet

	if (substr($oppdragsnummer, 0, -2) == date("md{$y}") ) {
		$oppdragsnummer
			= date("md{$y}")
			. $this->fastStrenglengde(substr($oppdragsnummer, -2) + 1, 2, "0", STR_PAD_LEFT);
	}
	else {
		$oppdragsnummer = date("md{$y}01");
	}
	$this->mysqli->saveToDb(array(
		'update'	=> true,
		'table'		=> "valg",
		'where'		=> "innstilling = 'nets_siste_oppdragsnr'",
		'fields'	=> array(
			'verdi'		=> $oppdragsnummer
		)
	));
	$this->hentValg();
	return $oppdragsnummer;
}



/*	Send Efaktura til NETS
Kopler til NETS og overfører lokal efakturafil fra filarkivet til NETS' server.
Fila som skal overføres må allerede finnes i filarkivet i nets/ut/.
Filbenevnelsen må inneholde dato-undermappe
	F.eks "2017-01/prodefakbbs20170116002.txt"
******************************************
$fil (streng):	navn på fila som skal overføres.
------------------------------------------
(bool) suksessangivelse
*/
public function netsSendEfaktura($fil) {
 	set_include_path( PATH_TO_PHPSECLIB );
	require_once( PATH_TO_PHPSECLIB . 'Net/SSH2.php' );
	require_once( PATH_TO_PHPSECLIB . 'Net/SFTP.php' );
	require_once( PATH_TO_PHPSECLIB . 'Crypt/RSA.php' );

	define('NET_SFTP_LOGGING', NET_SFTP_LOG_COMPLEX);
	
	$key = new Crypt_RSA();
	$key->setPassword(NETS_KEY_PW_EINVOICE);
	$key->loadKey(file_get_contents(NETS_KEY_EINVOICE));

	$sftp = new Net_SFTP(NETS_IP_EINVOICE, NETS_PORT_EINVOICE, NETS_TIMEOUT_EINVOICE);
	
	if ($sftp->login(NETS_USER_EINVOICE, $key)) {
		$sftp->chdir("Inbound");
		
		return $sftp->put( basename($fil), "{$this->filarkiv}/nets/ut/{$fil}", NET_SFTP_LOCAL_FILE);
	}
	
	else {
		return false;
	}
}



// Overfører Nets-forsendelse med OCR-brukeren
// Kopler til NETS og overfører lokal fil til NETS' server.
/****************************************/
//	--------------------------------------
//	resultat: boolsk suksessangivelse
public function netsSendOcr($fil) {
 	set_include_path( PATH_TO_PHPSECLIB );
	require_once( PATH_TO_PHPSECLIB . 'Net/SSH2.php' );
	require_once( PATH_TO_PHPSECLIB . 'Net/SFTP.php' );
	require_once( PATH_TO_PHPSECLIB . 'Crypt/RSA.php' );

	define('NET_SFTP_LOGGING', NET_SFTP_LOG_COMPLEX);
	
	$key = new Crypt_RSA();
	$key->setPassword(NETS_KEY_PW_OCR);
	$key->loadKey(file_get_contents(NETS_KEY_OCR));

	$sftp = new Net_SFTP(NETS_IP_OCR, NETS_PORT_OCR, NETS_TIMEOUT_OCR);
	
	if ($sftp->login(NETS_USER_OCR, $key)) {
		$sftp->chdir("Inbound");
		
		return $sftp->put( basename($fil), "{$this->filarkiv}/nets/ut/{$fil}", NET_SFTP_LOCAL_FILE);
	}
	
	else {
		return false;
	}
}



/*	Nets Slett usendte Avtalegiroer
Sletter Avtalegiroer som ikke har blitt overført (pga feil) ifra fbo_trekkrav-tabellen
******************************************
------------------------------------------
*/
public function netsSlettUsendteAvtalegiroer() {
	$tp = $this->mysqli->table_prefix;

	return $this->mysqli->query("DELETE FROM {tp}fbo_trekkrav WHERE forsendelse IS NULL");
}



public function noterUtskriftsdato($giroer = array()) {
	$sql =	"UPDATE krav INNER JOIN giroer ON krav.gironr = giroer.gironr\n"
	.		"SET krav.utskriftsdato = '" . date('Y-m-d H:i:s') . "', giroer.utskriftsdato = '" . date('Y-m-d H:i:s') . "'\n"
	.		"WHERE krav.utskriftsdato IS NULL AND (krav.gironr = '" . implode("' OR krav.gironr = '", $giroer) . "')\n";
	$resultat = $this->mysqli->query($sql);
	if($resultat) {
		foreach($giroer as $giro) {
			$this->skrivGiro(array($giro), false, null, 'F');			
		}
	}
	
	return $resultat;
}



/*	Ny forfalldato
Returnerer en ny forfallsdato som tilfredstiller kravene
som er satt i innstillingene for leiebasen.
*****************************************/
//	--------------------------------------
//	retur: DateTime-objekt) Ny forfallsdato
public function nyForfallsdato() {
	$forfallsfrist = new DateInterval( $this->valg['forfallsfrist'] );
	$fastForfallsdag =  str_pad(
		$this->valg['forfallsdato'],
		2,
		'0',
		STR_PAD_LEFT
	);
	
	$forfallsdato = new DateTime();
	$forfallsdato->setTime(0, 0, 0);
	$forfallsdato->add( $forfallsfrist );
	
	// Dersom det er angitt fast månedlig dag for forfall,
	//	justeres forfallsdato i hht til denne.
	if( $fastForfallsdag ) {
		$minForfallsdato = clone $forfallsdato;
		$forfallsmåned = new DateTime( $forfallsdato->format("Y-m-01 00:00:00") );
		
		// Forfallsdato angitt i innstillingene som 28 oppfattes som siste dag i måneden.
		if ( $fastForfallsdag > 27 ) {
			$fastForfallsdag = 't';
		}
		
		// Så lenge forfallsdato ikke opprettholder fristen
		// forskyves den én måned
		while(
			"{$forfallsmåned->format('Y-m')}-{$fastForfallsdag}" < $minForfallsdato->format('Y-m-d') 
		) {
			$forfallsmåned->add( new DateInterval('P1M') );
		}		

		$forfallsdato = new DateTime(
			"{$forfallsmåned->format('Y-m')}-{$fastForfallsdag} 00:00:00"
		);
	}


	return $forfallsdato;
}



/*	Oppdaterer utestående på samtlige krav
******************************************
$utelattInnbetaling (heltall): innbetalingsid for evt delbeløp som skal ignoreres
------------------------------------------
*/
public function oppdaterUbetalt($utelattInnbetaling = "") {

	if(!$this->mysqli->query("TRUNCATE TABLE innbetalt"))
		return false;
	if(!$this->mysqli->query("INSERT INTO innbetalt "
		. "SELECT krav, SUM(beløp) AS sum "
		. "FROM innbetalinger "
		. "WHERE krav IS NOT NULL AND innbetalingsid <> '$utelattInnbetaling' "
		. "GROUP BY krav"))
			return false;
	if(!$this->mysqli->query("UPDATE krav LEFT JOIN innbetalt ON krav.id = innbetalt.krav "
		. "SET krav.utestående = krav.beløp - IFNULL(innbetalt.sum, 0)"))
			return false;
	else return true;
}



public function oppdrag($oppdrag = "") {
	if ($oppdrag == "taimotskjema") {
		$this->taimotSkjema( isset($_GET['skjema']) ? $_GET['skjema'] : null);
	}
	if ($oppdrag == "hentdata") {
		echo $this->hentData( isset($_GET['data']) ? $_GET['data'] : null);
	}
	if ($oppdrag == "lagpdf") {
		$this->lagPDF(isset( $_GET['pdf']) ? (int)$_GET['pdf'] : null);
	}
	if ($oppdrag == "manipuler") {
		$this->manipuler( isset($_GET['data']) ? $_GET['data'] : null);
	}
	if ($oppdrag == "utskrift") {
		$this->mal = "_utskrift.php";
		$this->skrivHTML();
	}
	if ($oppdrag == "oppgave") {
		$this->oppgave($_GET['oppgave']);
	}
}



// Oppretter et nytt database-objekt som lagres
/****************************************/
//	$type: (streng) Class-navnet på objektet som skal opprettes. Det må være arvtaker av 'DatabaseObjekt'
//	$egenskaper: (array) Egenskaper som sendes til opprett i den aktuelle klassen
//	--------------------------------------
//	retur: (objekt) Objektet som ble opprettet, eller false dersom det ikke kunne opprettes
public function opprett( $type, $egenskaper = array() ) {
	if ( !is_a( $type, 'DatabaseObjekt', true ) ) {
		return false;
	}
	$objekt = new $type;
	
	if ( $objekt->opprett( $egenskaper ) === false ) {
		return false;
	}
	return $objekt;
}



/*	Opprett Leiekrav
Erstatter eller oppretter nye leiekrav (forfall) i alle leieforhold som ikke er oppsagt.
For tidsbegrensede kontrakter opprettes krav fram til utløpsdato.
Om denne har utløpt opprettes kun en termin ad gangen,
men kun dersom alle eksisterende terminer har passert.
For ikke tidsbegrensede kontrakter settes kun hele terminer,
og ikke senere enn oppsigelsestiden beregnet ifra dagens dato.
For oppsagte kontrakter opprettes krav fram til oppsigelsestiden opphører,
men bare dersom leia ikke er beregna fram til leieavtalen er oppsagt.
*****************************************/
//	$fradato (DateTime, normalt null):	Dersom $fradato er angitt vil eksisterende krav slettes fra denne datoen før nye legges til
//	$løsneInnbetalinger (boolsk, normalt usann):	Dersom denne er sann vil alle innbetalinger løsnes før kravene slettes, med mindre det allerede er skrevet ut giro
//	$ukedag (heltall):	Ukedag (1-7) terminene skal starte på. Angitt i hht ISO-8601. 1=mandag, 7=søndag
//	$kalenderdag (heltall):	Dag i måneden (1-31) terminene skal starte på. Alt over 27 regnes som siste dagen i måneden 
//	$fastDato (streng):		Dato i formatet 'm-d' som starter en ny termin
//	--------------------------------------
//	retur: liste over alle nyopprettede leiekrav
public function opprettLeiekrav( $fradato = null, $løsneInnbetalinger = false, $ukedag = 0, $kalenderdag = 0, $fastDato = false ) {
	$tp = $this->mysqli->table_prefix;
	$resultat = array();
	
	foreach( $leieforhold = $this->mysqli->arrayData(array(
		'distinct'	=> true,
		'class'		=> "Leieforhold",
		'source'	=>	"{$tp}kontrakter AS kontrakter LEFT JOIN {$tp}oppsigelser AS oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold",
		'fields'	=> "kontrakter.leieforhold AS id",
		'where'		=>	"oppsigelser.leieforhold IS NULL\n"
	))->data as $leieforhold ) {
		$resultat = array_merge( $resultat, $leieforhold->opprettLeiekrav(
			$fradato,
			$løsneInnbetalinger,
			$ukedag,
			$kalenderdag,
			$fastDato 
		));
	}
	return $resultat;
}



public function opprettPolett() {
 	$p = session_id() . time();
 	$this->mysqli->query("DELETE FROM poletter WHERE utløper < " . time());
 	if($this->mysqli->query("INSERT INTO poletter SET polett = '$p', utløper = " . (time() + 6 * 3600))) return $p;
 	else return false;
}
 

// returnerer fristillelsesdatoen dersom er leieforhold er oppsagt, og NULL om det ikke er det.
public function oppsagt($kontraktnr) {
	$sql =	"SELECT fristillelsesdato\n"
	.		"FROM oppsigelser\n"
	.		"WHERE leieforhold = '" . $this->leieforhold($kontraktnr) . "'";
	$a = $this->arrayData($sql);
	
	if( isset( $a['data'][0]['fristillelsesdato'] ) ) {
		return strtotime($a['data'][0]['fristillelsesdato']);
	}
	else {
		return null;
	}
}


public function oppsigelsestidrenderer($v) {
	$periode = substr($v, -1);
	$antall = (int)substr($v, 1);
	
	if(!$antall) {
		return "ingen oppsigelsestid";
	}
	if ($periode == 'Y') $periode = 'år';
	if ($periode == 'M') $periode = 'måned';
	if ($periode == 'D') $periode = 'dag';
	if ($periode == 'dag' and ($antall/7) == (int)($antall/7)) {
		$antall = $antall / 7;
		$periode = 'uke';
	}
	if($antall > 1 and $periode == 'uke') $periode = 'uker';
	if($antall > 1 and $periode == 'dag') $periode = 'dager';
	if($antall > 1 and $periode == 'måned') $periode = 'måneder';
	return "$antall $periode";
}



// Funksjon som formaterer en tidsperiode
/****************************************/
//	$periode:	Verdien som skal formateres
//	$somInterval_spec (boolsk, normalt av) På for å returnere perioden som interval_spec
//	--------------------------------------
//	retur: tekststreng
public function periodeformat( $periode, $somInterval_spec = false ) {
	
	if( !is_a( $periode, 'DateInterval')) {
		$periode = new DateInterval( $periode );
	}
	
	if( $somInterval_spec ) {
		
		if(!$periode->y && !$periode->m && !$periode->d && !$periode->h && !$periode->i && !$periode->s ) {
			return 'P0M';
		}
	
		return "P"
		.	($periode->invert ? "-" : "")
		.	($periode->y ? "{$periode->y}Y" : "")
		.	($periode->m ? "{$periode->m}M" : "")
		.	($periode->d ? "{$periode->d}D" : "")
		.	( ($periode->h || $periode->i || $periode->s) ? "T" : "")
		.	($periode->h ? "{$periode->h}H" : "")
		.	($periode->i ? "{$periode->i}M" : "")
		.	($periode->s ? "{$periode->s}S" : "");
	}
	
	$resultat = array();
	
	if( $periode->y ) {
		$resultat[] = "{$periode->y} år";
	}

	if( $periode->m > 1) {
		$resultat[] = "{$periode->m} måneder";
	}
	else if( $periode->m) {
		$resultat[] = "1 måned";
	}

	if( $periode->d == 7) {
		$resultat[] = "1 uke";
	}
	else if($periode->d and $periode->d % 7 == 0 ) {
		$resultat[] = ($periode->d / 7) . " uker";
	}
	else if( $periode->d > 1) {
		$resultat[] = "{$periode->d} dager";
	}
	else if( $periode->d) {
		$resultat[] = "1 dag";
	}

	if( $periode->h > 1) {
		$resultat[] = "{$periode->h} timer";
	}
	else if( $periode->h) {
		$resultat[] = "1 time";
	}

	if( $periode->i > 1) {
		$resultat[] = "{$periode->i} minutter";
	}
	else if( $periode->i) {
		$resultat[] = "1 minutt";
	}

	if( $periode->s > 1) {
		$resultat[] = "{$periode->s} sekunder";
	}
	else if( $periode->s) {
		$resultat[] = "1 sekund";
	}
	
	return $this->liste( $resultat );
}



/*	Pre HTML
Funksjon som smetter inn behandling umiddelbart før HTML sendes ut
Denne funksjonen kan påvirke malen som skal brukes for å sende ut oppslaget
Dersom funksjonen returnerer usann vil den stoppe utsendelsen av malen
******************************************
------------------------------------------
retur (boolsk) Sann for å skrive ut HTML-malen, usann for å stoppe den
*/
public function preHTML() {
	return true;
}



// Funksjon som formaterer en verdi som prosenter
/****************************************/
//	$verdi:	Verdien som skal formateres
//	$antallDesimaler (heltall, normalt 1)
//	$html:	(boolsk, normalt på) Om verdien skal formateres for $html
//	--------------------------------------
//	retur: tekststreng
public function prosent( $verdi, $antallDesimaler = 1, $html = true ) {
	$resultat = number_format(round($verdi * 100, $antallDesimaler), $antallDesimaler, ",", " " ) . "%";
	if($html) {
		return  "<span>" . str_replace(" ", "&nbsp;", $resultat) . "</span>";
	}
	else {
		return $resultat;
	}
}



// Funksjon som oppretter ei betaling på grunnlag av en OCR-transaksjon
// og forsøker å utlikne denne mot et krav
/****************************************/
//	$transaksjon: stdclass-objekt fra OCR konteringsforsendelse fra NETS
//	--------------------------------------
//	retur: (bool) Suksess ??
public function registrerBetaling( $transaksjon ) {

	$restbeløp = $transaksjon->beløp;
	$this->oppdaterUbetalt();

	// sjekk om giroen med dette KIDnummeret er ubetalt
	$test = $this->mysqli->arrayData(array(
		'fields'		=> "SUM(utestående) AS utestående",
		'source'		=> "krav INNER JOIN giroer ON krav.gironr = giroer.gironr",
		'where'			=> "kid = '{$transaksjon->kid}'"
	));

	if( isset( $test->data[0] ) and $test->data[0]->utestående >= $restbeløp) {
	// Hele beløpet kan puttes på den giroen som er angitt i KID:

		// Finn alle kravene med det aktuelle kid-nummeret,
		// og sorter disse etter hvilket som matcher resterende
		// av det innbetalte beløpet best.
		// Deretter sorteres det eldste kravet først
		$b = $this->mysqli->arrayData(array(
			'fields'		=> "krav.id, krav.utestående",
			'source'		=> "krav INNER JOIN giroer ON krav.gironr = giroer.gironr",
			'where'			=> "kid = '{$transaksjon->kid}'",
			'orderfields'	=> "ABS(krav.utestående - $restbeløp),
								krav.forfall,
								krav.kravdato"
		));
		foreach($b->data as $krav) {
			$subtrahend = min($krav->utestående, $restbeløp);
			if( $this->mysqli->saveToDb(array(
				'insert'	=> true,
				'table'		=> "innbetalinger",
				'fields'	=> array(
					'innbetaling'	=>	$transaksjon->oppgjørsdato->format('Y-m-d')
									.	"-"
									.	substr(md5(
										"{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}"
										.	"{$transaksjon->debetKonto}{$transaksjon->transaksjonsnr}"
									), 0, 4),
					'krav'			=> $krav->id,
					'betaler'		=> $transaksjon->debetKonto,
					'leieforhold'	=> $this->leieforholdFraKid($transaksjon->kid),
					'beløp'			=> $subtrahend,
					'konto'			=> 'OCR-giro',
					'OCRtransaksjon' => $transaksjon->id,
					'ref'			=> "{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}",
					'dato'			=> $transaksjon->oppgjørsdato
										->format('Y-m-d'),
					'registrerer'	 => $this->bruker['navn']
				)
			))->success ) {
				$restbeløp = bcsub($restbeløp, $subtrahend, 2);
			}
		}
	}


/*
	if( $restbeløp > 0 ) {
	// Klarte ikke putte hele beløpet på giroen som angitt i KID,
	// muligens fordi den allerede var betalt
	// (eller fordi ingen spesifikk giro var angitt).
	// Dermed løsnes alle ikke-OCR betalinger som er nyere enn tre mnd før også
	// det resterende av innbetalinga utliknes mot angitt giro

		// Finn alle kravene med det aktuelle kid-nummeret,
		// og sorter disse etter hvilket som matcher resterende
		// av det innbetalte beløpet best.
		// Deretter sorteres det eldste kravet først
		$b = $this->mysqli->arrayData(array(
			'fields'		=> "krav.id, krav.beløp",
			'source'		=> "krav INNER JOIN giroer ON krav.gironr = giroer.gironr",
			'where'			=> "kid = '{$transaksjon->kid}'",
			'orderfields'	=> "ABS(krav.beløp - $restbeløp),
								krav.forfall,
								krav.kravdato"
		));

		foreach($b->data as $krav){

			$this->mysqli->saveToDb(array(
				'update'	=> true,
				'table'		=> "innbetalinger",
				'where'		=> "konto <> 'OCR-giro'
								AND dato > DATE_SUB('{$transaksjon->oppgjørsdato->format('Y-m-d')}', INTERVAL 3 MONTH)
								AND krav = '{$krav->id}'",
				'fields'	=> array(
					'krav'		=> null
				)
			));
	
			$this->oppdaterUbetalt();

			$subtrahend = min( abs(
				$this->mysqli->arrayData(array(
					'source'	=> "krav",
					'fields'	=> "utestående",
					'where'		=> "id = '{$krav->id}'"
				))->data[0]->utestående),
				abs( $restbeløp )
			);

			if( $subtrahend and $this->mysqli->saveToDb(array(
				'insert'	=> true,
				'table'		=> "innbetalinger",
				'fields'	=> array(
					'innbetaling'	=>	$transaksjon->oppgjørsdato->format('Y-m-d')
									.	"-"
									.	substr(md5(
										"{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}"
										.	"{$transaksjon->debetKonto}{$transaksjon->transaksjonsnr}"
									), 0, 4),
					'krav'			=> $krav->id,
					'betaler'		=> $transaksjon->debetKonto,
					'leieforhold'	=> $this->leieforholdFraKid($transaksjon->kid),
					'beløp'			=> $subtrahend,
					'konto'			=> 'OCR-giro',
					'OCRtransaksjon' => $transaksjon->id,
					'ref'			=> "{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}",
					'dato'			=> $transaksjon->oppgjørsdato->format('Y-m-d'),
					'registrerer'	=> $this->bruker['navn']
				)
			))->success ) {
				$restbeløp = bcsub($restbeløp, $subtrahend, 2);
			}
		}
	}
*/

	// Spesifikk KID ignoreres, og krav i dette leieforholdet søkes opp
/*

	// Ser etter enkeltkrav som matcher restbeløpet
	if( $restbeløp > 0 ) {
			$this->oppdaterUbetalt();
			$krav = $this->mysqli->arrayData(array(
				'source'	=> "krav LEFT JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
				'fields'	=> "krav.*",
				'where'	=> "leieforhold = '{$this->leieforholdFraKid($transaksjon->kid)}'
							AND utestående = '$restbeløp'",
				'fields'	=> "krav.*",
				'orderfields' => "if(forfall IS NULL, 1, 0), forfall, kravdato",
				'limit'		=> 1
			));
			// $krav inneholder det eldste kravet som matcher beløpet
			
			if(count($krav->data)) {
				$krav = $krav->data[0];

				if( $this->mysqli->saveToDb(array(
					'insert'	=> true,
					'table'		=> "innbetalinger",
					'fields'	=> array(
						'innbetaling'	=>	$transaksjon->oppgjørsdato->format('Y-m-d')
										.	"-"
										.	substr(md5(
											"{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}"
											.	"{$transaksjon->debetKonto}{$transaksjon->transaksjonsnr}"
										), 0, 4),
						'krav'			=> $krav->id,
						'betaler'		=> $transaksjon->debetKonto,
						'leieforhold'	=> $this->leieforholdFraKid($transaksjon->kid),
						'beløp'			=> $restbeløp,
						'konto'			=> 'OCR-giro',
						'OCRtransaksjon' => $transaksjon->id,
						'ref'			=> "{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}",
						'dato'			=> $transaksjon->oppgjørsdato
											->format('Y-m-d'),
						'registrerer'	=> $this->bruker['navn']
					)
				))->success ) {
					$restbeløp = 0;
				}					
			}
		}

	// Nå søkes det opp en kombinasjon av utestående krav
	// som samlet matcher det resterende beløpet
	// Når det finnes en match, registreres betalingene og søket avsluttes.

	// Første søk sorterer etter forfall og kravdato				
	if( $restbeløp > 0 ) {

		$this->oppdaterUbetalt();

		$query = array(
			'source'		=> "krav LEFT JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
			'fields'		=> "krav.*",
			'orderfields'	=> "if(forfall IS NULL, 1, 0) , forfall, gironr, kravdato",
			'where'			=> "krav.forfall IS NOT NULL
								AND kontrakter.leieforhold = '" . $this->leieforholdFraKid( $transaksjon->kid ) . "'
								AND utestående > 0
								AND utestående <= '$restbeløp'"
		);

		$b = $this->mysqli->arrayData( $query );
		// $b inneholder alle utestående krav som hver for seg kan dekkes med beløpet

		$d = 1;
		while( $d <= $b->totalRows ) {
			$query['limit'] = $d;
			$b = $this->mysqli->arrayData($query); // $b inneholder alle utestående krav som kan dekkes med beløpet
			$sum = 0;
			foreach($b->data as $krav) {
				$sum = bcadd($sum, $krav->utestående, 2);
			}
	
			// Dersom denne kombinasjonen av et eller flere krav matcher med det
			// resterende innbetalingsbeløpet, utliknes beløpet mot disse,
			// og kravsøket avsluttes:
			if($sum > 0 and $sum == $restbeløp) {

				foreach($b->data as $krav) {
					$subtrahend = min($krav->utestående, $restbeløp);

					if( $this->mysqli->saveToDb(array(
						'insert'	=> true,
						'table'		=> "innbetalinger",
						'fields'	=> array(
							'innbetaling'	=>	$transaksjon->oppgjørsdato->format('Y-m-d')
											.	"-"
											.	substr(md5(
												"{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}"
												.	"{$transaksjon->debetKonto}{$transaksjon->transaksjonsnr}"
											), 0, 4),
							'krav'			=> $krav->id,
							'betaler'		=> $transaksjon->debetKonto,
							'leieforhold'	=> $this->leieforholdFraKid($transaksjon->kid),
							'beløp'			=> $subtrahend,
							'konto'			=> 'OCR-giro',
							'OCRtransaksjon' => $transaksjon->id,
							'ref'			=> "{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}",
							'dato'			=> $transaksjon->oppgjørsdato
												->format('Y-m-d'),
							'registrerer'	=> $this->bruker['navn']
						)
					))->success ) {
						$restbeløp = bcsub( $restbeløp, $subtrahend, 2 );
					}					
				}
			}
	
			if($sum >= $restbeløp) {
				$d = $b->totalRows;
			}
	
			$d++;
		}
	}
		
	// Neste søk ser kun etter husleie, sorterer etter beløp				
	if( $restbeløp > 0 ) {

		$this->oppdaterUbetalt();

		$query = array(
			'source'		=> "krav LEFT JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
			'fields'		=> "krav.*",
			'orderfields'	=> "utestående, if(forfall IS NULL, 1, 0), forfall, kravdato",
			'where'			=> "krav.forfall IS NOT NULL
								AND type = 'Husleie'
								AND kontrakter.leieforhold = '" . $this->leieforholdFraKid( $transaksjon->kid ) . "'
								AND utestående > 0
								AND utestående <= '$restbeløp'"
		);

		$b = $this->mysqli->arrayData( $query );
		// $b inneholder alle utestående krav som hver for seg kan dekkes med beløpet

		$d = 1;
		while( $d <= $b->totalRows ) {
			$query['limit'] = $d;
			$b = $this->mysqli->arrayData($query); // $b inneholder alle utestående krav som kan dekkes med beløpet
			$sum = 0;
			foreach($b->data as $krav) {
				$sum = bcadd($sum, $krav->utestående, 2);
			}
	
			// Dersom denne kombinasjonen av et eller flere krav matcher med det
			// resterende innbetalingsbeløpet, utliknes beløpet mot disse,
			// og kravsøket avsluttes:
			if($sum > 0 and $sum == $restbeløp) {

				foreach($b->data as $krav) {
					$subtrahend = min($krav->utestående, $restbeløp);

					if( $this->mysqli->saveToDb(array(
						'insert'	=> true,
						'table'		=> "innbetalinger",
						'fields'	=> array(
							'innbetaling'	=>	$transaksjon->oppgjørsdato->format('Y-m-d')
											.	"-"
											.	substr(md5(
												"{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}"
												.	"{$transaksjon->debetKonto}{$transaksjon->transaksjonsnr}"
											), 0, 4),
							'krav'			=> $krav->id,
							'betaler'		=> $transaksjon->debetKonto,
							'leieforhold'	=> $this->leieforholdFraKid($transaksjon->kid),
							'beløp'			=> $subtrahend,
							'konto'			=> 'OCR-giro',
							'OCRtransaksjon' => $transaksjon->id,
							'ref'			=> "{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}",
							'dato'			=> $transaksjon->oppgjørsdato
												->format('Y-m-d'),
							'registrerer'	=> $this->bruker['navn']
						)
					))->success ) {
						$restbeløp = bcsub( $restbeløp, $subtrahend, 2 );
					}					
				}
			}
	
			if($sum >= $restbeløp) {
				$d = $b->totalRows;
			}
	
			$d++;
		}
	}

	// Neste søk ser kun etter fellesstrøm, og sorterer etter dato
	if( $restbeløp > 0 ) {

		$this->oppdaterUbetalt();

		$query = array(
			'source'		=> "krav LEFT JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
			'fields'		=> "krav.*",
			'orderfields'	=> "if(forfall IS NULL, 1, 0), forfall, kravdato",
			'where'			=> "krav.forfall IS NOT NULL
								AND type = 'Fellesstrøm'
								AND kontrakter.leieforhold = '" . $this->leieforholdFraKid( $transaksjon->kid ) . "'
								AND utestående > 0
								AND utestående <= '$restbeløp'"
		);

		$b = $this->mysqli->arrayData( $query );
		// $b inneholder alle utestående krav som hver for seg kan dekkes med beløpet

		$d = 1;
		while( $d <= $b->totalRows ) {
			$query['limit'] = $d;
			$b = $this->mysqli->arrayData($query); // $b inneholder alle utestående krav som kan dekkes med beløpet
			$sum = 0;
			foreach($b->data as $krav) {
				$sum = bcadd($sum, $krav->utestående, 2);
			}
	
			// Dersom denne kombinasjonen av et eller flere krav matcher med det
			// resterende innbetalingsbeløpet, utliknes beløpet mot disse,
			// og kravsøket avsluttes:
			if($sum > 0 and $sum == $restbeløp) {

				foreach($b->data as $krav) {
					$subtrahend = min($krav->utestående, $restbeløp);

					if( $this->mysqli->saveToDb(array(
						'insert'	=> true,
						'table'		=> "innbetalinger",
						'fields'	=> array(
							'innbetaling'	=>	$transaksjon->oppgjørsdato->format('Y-m-d')
											.	"-"
											.	substr(md5(
												"{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}"
												.	"{$transaksjon->debetKonto}{$transaksjon->transaksjonsnr}"
											), 0, 4),
							'krav'			=> $krav->id,
							'betaler'		=> $transaksjon->debetKonto,
							'leieforhold'	=> $this->leieforholdFraKid($transaksjon->kid),
							'beløp'			=> $subtrahend,
							'konto'			=> 'OCR-giro',
							'OCRtransaksjon' => $transaksjon->id,
							'ref'			=> "{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}",
							'dato'			=> $transaksjon->oppgjørsdato
												->format('Y-m-d'),
							'registrerer'	=> $this->bruker['navn']
						)
					))->success ) {
						$restbeløp = bcsub( $restbeløp, $subtrahend, 2 );
					}					
				}
			}
	
			if($sum >= $restbeløp) {
				$d = $b->totalRows;
			}
	
			$d++;
		}
	}

	// Neste søk sorterer etter utestående beløp, i stigende orden				
	if( $restbeløp > 0 ) {

		$this->oppdaterUbetalt();

		$query = array(
			'source'		=> "krav LEFT JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
			'fields'		=> "krav.*",
			'orderfields'	=> "utestående, if(forfall IS NULL, 1, 0), forfall, kravdato",
			'where'			=> "krav.forfall IS NOT NULL
								AND kontrakter.leieforhold = '" . $this->leieforholdFraKid( $transaksjon->kid ) . "'
								AND utestående > 0
								AND utestående <= '$restbeløp'"
		);

		$b = $this->mysqli->arrayData( $query );
		// $b inneholder alle utestående krav som hver for seg kan dekkes med beløpet

		$d = 1;
		while( $d <= $b->totalRows ) {
			$query['limit'] = $d;
			$b = $this->mysqli->arrayData($query); // $b inneholder alle utestående krav som kan dekkes med beløpet
			$sum = 0;
			foreach($b->data as $krav) {
				$sum = bcadd($sum, $krav->utestående, 2);
			}
	
			// Dersom denne kombinasjonen av et eller flere krav matcher med det
			// resterende innbetalingsbeløpet, utliknes beløpet mot disse,
			// og kravsøket avsluttes:
			if($sum > 0 and $sum == $restbeløp) {

				foreach($b->data as $krav) {
					$subtrahend = min($krav->utestående, $restbeløp);

					if( $this->mysqli->saveToDb(array(
						'insert'	=> true,
						'table'		=> "innbetalinger",
						'fields'	=> array(
							'innbetaling'	=>	$transaksjon->oppgjørsdato->format('Y-m-d')
											.	"-"
											.	substr(md5(
												"{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}"
												.	"{$transaksjon->debetKonto}{$transaksjon->transaksjonsnr}"
											), 0, 4),
							'betaler'		=> $transaksjon->debetKonto,
							'leieforhold'	=> $this->leieforholdFraKid($transaksjon->kid),
							'beløp'			=> $subtrahend,
							'konto'			=> 'OCR-giro',
							'OCRtransaksjon' => $transaksjon->id,
							'ref'			=> "{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}",
							'dato'			=> $transaksjon->oppgjørsdato
												->format('Y-m-d'),
							'registrerer'	=> $this->bruker['navn']
						)
					))->success ) {
						$restbeløp = bcsub( $restbeløp, $subtrahend, 2 );
					}					
				}
			}
	
			if($sum >= $restbeløp) {
				$d = $b->totalRows;
			}
	
			$d++;
		}
	}
*/
	// Det er ikke funnet noen eksakt match, så beløpet utliknes mot
	// det eldste kravet				
	if( $restbeløp > 0 ) {
		$this->oppdaterUbetalt();

		foreach($this->mysqli->arrayData(array(
			'source'		=> "krav LEFT JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
			'fields'		=> "krav.*",
			'where'			=> "leieforhold = '{$this->leieforholdFraKid($transaksjon->kid)}'\n"
			.	"AND utestående",
			'orderfields'	=> "if(forfall IS NULL, 1, 0), forfall, kravdato"
		))->data as $krav) {
		
			if( $restbeløp > 0 ) {

				// Subtrahend er det beløpet som skal fordeles mot hvert krav
				$subtrahend = min( abs($krav->utestående ), abs( $restbeløp ) );
				
				if( $this->mysqli->saveToDb(array(
					'insert'	=> true,
					'table'		=> "innbetalinger",
					'fields'	=> array(
						'innbetaling'	=>	$transaksjon->oppgjørsdato->format('Y-m-d')
											.	"-"
											.	substr(md5(
												"{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}"
												.	"{$transaksjon->debetKonto}{$transaksjon->transaksjonsnr}"
											), 0, 4),
						'krav'			=> $krav->id,
						'betaler'		=> $transaksjon->debetKonto,
						'leieforhold'	=> $this->leieforholdFraKid($transaksjon->kid),
						'beløp'			=> $subtrahend,
						'konto'			=> 'OCR-giro',
						'OCRtransaksjon' => $transaksjon->id,
						'ref'			=> "{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}",
						'dato'			=> $transaksjon->oppgjørsdato
											->format('Y-m-d'),
						'registrerer'	=> $this->bruker['navn']
					)
				))->success ) {
					$restbeløp = bcsub( $restbeløp, $subtrahend, 2 );	
				}
			}
		}
	}					


	// Resterende beløp krediteres leieforhold, men utliknes ikke
	if( $restbeløp > 0 ) {
		$this->mysqli->saveToDb(array(
			'insert'	=> true,
			'table'		=> "innbetalinger",
			'fields'	=> array(
				'innbetaling'	=>	$transaksjon->oppgjørsdato->format('Y-m-d')
									.	"-"
									.	substr(md5(
										"{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}"
										.	"{$transaksjon->debetKonto}{$transaksjon->transaksjonsnr}"
									), 0, 4),
				'krav'			=> null,
				'betaler'		=> $transaksjon->debetKonto,
				'leieforhold'	=> $this->leieforholdFraKid($transaksjon->kid),
				'beløp'			=> $restbeløp,
				'konto'			=> 'OCR-giro',
				'OCRtransaksjon' => $transaksjon->id,
				'ref'			=> "{$transaksjon->forsendelsesnummer}-{$transaksjon->løpenr}",
				'dato'			=> $transaksjon->oppgjørsdato
									->format('Y-m-d'),
				'registrerer'	=> $this->bruker['navn']
			)
		));
	}
}



// Funksjon som skanner en NETS-forsendelse for nye efakturaavtaler,
// og behandler disse
/****************************************/
//	$forsendelse NetsForsendelse-objekt	eller SimpleXml-objekt
//	--------------------------------------
//	retur: stdclass response-oppdrag som skal returneres til NETS
public function registrerEfakturaForespørsler( $forsendelse ) {
	$tp = $this->mysqli->table_prefix;
	$response = false;

	$oppdragskonto = preg_replace('/[^0-9]+/', '', $this->valg['bankkonto']);
	if( $forsendelse instanceof NetsForsendelse ) {
	
		if ( 
			$forsendelse->dataavsender != NETS_ID
			or !$forsendelse->valider()
		) {
			return false;
		}
		
		
		foreach ($forsendelse->oppdrag as $oppdrag ) {
			if(
				$oppdrag->tjeneste == 42
				and $oppdrag->oppdragstype == 94
				and $oppdrag->referanseFakturautsteder == $this->valg['efaktura_referansenummer']
			) {
				
				foreach( $oppdrag->transaksjoner as $transaksjon ) {

					if ($transaksjon->avtalevalg == "D") {
						// D = DELETE
					
						$this->mysqli->query("
							DELETE FROM {$tp}efaktura_avtaler
							WHERE efakturareferanse = '{$transaksjon->efakturaRef}'
						");
					}
					else {

						// Dersom avtalestaus i requesten er P(ending)
						// skal det lages en response på requesten
						if( $transaksjon->avtalestatus == "P" ) {

							// Det må lages en response om den ikke finnes fra før
							if( $response === false ) {				
								$response = (object)array(
									'oppdragskonto'				=> $oppdragskonto,
									'referanseFakturautsteder'	=> $this->valg['efaktura_referansenummer'],
									'transaksjoner'				=> array(),
									'tjeneste'					=> 42,
									'oppdragstype'				=> 94,
									'oppdragsnr'				=> $this->netsOpprettOppdragsnummer(),
									'antallRecords'				=> 0,
									'antallTransaksjoner'		=> 0
								);
								$transaksjonsnr = 0;
							}


							$transaksjonsnr ++;						
							$response->transaksjoner[] = (object)array(
								'efakturaRef'		=> $transaksjon->efakturaRef,
								'brukerId'			=> $transaksjon->brukerId,
								'avtalestatus'		=> $this->behandleEfakturaAvtaler( $transaksjon )->status,
								'avtalevalg'		=> $transaksjon->avtalevalg,
								'feilkode'			=> $this->behandleEfakturaAvtaler( $transaksjon )->kode,
								'transaksjonstype'	=> $transaksjon->transaksjonstype,
								'transaksjonsnr'	=> $transaksjonsnr
							);
							$response->antallTransaksjoner ++;
						}


						// Databasen skal oppdateres utenom ved ubekreftede endringsrequester
						if(
							($transaksjon->avtalestatus != "P" && $transaksjon->avtalevalg != "D")
							|| $transaksjon->avtalevalg == "A" 
						) {
							$resultat = $this->mysqli->saveToDb(array(
								'insert'		=> ( $transaksjon->avtalevalg == "A" ? true : false ),
													// A = ADD
								'updateOnDuplicateKey'	=> ( $transaksjon->avtalevalg == "A" ? true : false ),
													// A = ADD
								'update'		=> ( $transaksjon->avtalevalg == "C" ? true : false ),
													// C = CHANGE
								'where'			=> (
									$transaksjon->avtalevalg == "C"
									? "efakturareferanse = '{$transaksjon->efakturaRef}'"
									: null 
								),
								'table'			=> "{$tp}efaktura_avtaler",
								'returnQuery'	=> true,
								'fields'	=> array(
									'avtalestatus'	=> $transaksjon->avtalestatus,
														// P = Pending
														// A = Aktiv
														// D = Deleted
														// N = NoActive (Avtalen er ikke godkjent)
									'avtalevalg'	=> $transaksjon->avtalevalg,
									'nets_brukerid'	=> $transaksjon->brukerId,
									'efakturareferanse'	=> $transaksjon->efakturaRef,
									'leieforhold'	=> $this->leieforholdFraEfakturareferanse(
															$transaksjon->efakturaRef
														),
									'fornavn'		=> $transaksjon->fornavn,
									'etternavn'		=> $transaksjon->etternavn,
									'adresse1'		=> $transaksjon->forbruker->adresse1,
									'adresse2'		=> $transaksjon->forbruker->adresse2,
									'postnr'		=> $transaksjon->forbruker->postnr,
									'poststed'		=> $transaksjon->forbruker->poststed,
									'land'			=> $transaksjon->forbruker->landskode,
									'telefon'		=> $transaksjon->forbruker->telefon,
									'email'			=> $transaksjon->forbruker->email,
									'feilkode'		=> isset($transaksjon->feilkode)
														? $transaksjon->feilkode
														: null
								)
							));
						}
					}
				}
			}
		}
	}
	
	else if( $forsendelse instanceof SimpleXMLElement ) {

	}
	
	return $response;
}



// Funksjon som skanner en NETS-forsendelse for kvitteringer
//	for innsendte eFakturaer, og håndterer disse
/****************************************/
//	$forsendelse NetsForsendelse-objekt	eller SimpleXml-objekt
//	--------------------------------------
public function registrerEfakturaKvitteringer( $forsendelse ) {

	$oppdragskonto = preg_replace('/[^0-9]+/', '', $this->valg['bankkonto']);
	if( $forsendelse instanceof NetsForsendelse ) {
	
		if ( 
			$forsendelse->dataavsender != NETS_ID
			or !$forsendelse->valider()
		) {
			return false;
		}
		
		
		foreach ($forsendelse->oppdrag as $oppdrag ) {
			/*
			Oppdragstype 4:	Status for mottatt forsendelse
				Statusalternativer:
					0 = Forsendelsen er mottatt av NETS (men ikke ferdig behandlet).
						Feilkoden vil også være 0.
					2 = Forsendelsen er i sin helhet forkastet.
						I tillegg gis forklaring i form av feilkode.

			Oppdragstype 5: Status for prosessert forsendelse
				Statusalternativer:
					1 = Forsendelsen er prosessert av NETS (men oppdrag og transaksjoner kan være forkastet).
					2 = Forsendelsen er i sin helhet forkastet.
						I tillegg gis forklaring i form av feilkode.

			Oppdragstype 6: Status for prosessert oppdrag
				Statusalternativer:
					0 = Oppdraget er godkjent.
					1 = Oppdraget er i sin helhet forkastet
						I tillegg gis forklaring i form av feilkode
				Feilkode på fakturanivå for avviste fakturaer i prosessert oppdrag
					
			*/

			if(
				$oppdrag->tjeneste == 42
				and (
					$oppdrag->oppdragstype == 4
					|| $oppdrag->oppdragstype == 5 
				)
				// Oppdragstype 4 kvitterer for forsendelse mottatt av NETS
				// Oppdragstype 5 kvitterer for forsendelse ferdig prosessert av NETS
			) {
				$oppdrag->dataavsender;		// Opprinnelig dataavsender (Vil muligens være blanket)
				$oppdrag->datamottaker;		// Opprinnelig datamottaker (=8080 NETS)
				$oppdrag->referanseFakturautsteder;
				$oppdrag->forsendelsesnr;	// Forsendelsen det kvitteres for
				$oppdrag->statusForsendelse;
				// 00 = Forsendelsen er mottatt i BBS (men ikke ferdig behandlet)
				// 01 = Forsendelsen i seg selv er ferdig behandlet
				// 02 = Forsendelsen er i sin helhet forkastet

				$oppdrag->feilkode;		// Feilkode dersom statusForsendelse = 2
				$oppdrag->feilmelding;	// Forklaring på feilkode
				
				if( $oppdrag->referanseFakturautsteder != $this->valg['efaktura_referansenummer'] ) {
					// Denne kvitteringen er feilsendt.

					$melding = "Et kvitteringsoppdrag for eFaktura er mottatt fra NETS med feil eFaktura-referansenummer.<br />Forsendelse {$forsendelse->forsendelsesnummer} mottatt den {$forsendelse->dato->format('d.m.Y')} har oppgitt eFaktura referanse {$oppdrag->referanseFakturautsteder}. Desse stemmer ikke med referansenummeret som er oppgitt i innstillingene for leiebasen<br />Oppdraget er ignorert.<br />\n";
					
					$this->sendMail( array(
						'testcopy'	=> true,
						'auto'		=> true,
						'to'		=> $this->valg['epost'],
						'priority'	=> 90,
						'subject'	=> "Problemer med eFakturakvittering",
						'html'		=> $melding
					) );
					
					$this->mysqli->saveToDb(array(
						'insert'		=> true,
						'table'			=> "internmeldinger",
						'fields'		=> array(
							'tekst'		=> $melding,
							'drift'		=> true
						)
					));
				
					return false;
				}


				/*****************************************
				Dersom forsendelsesstatus = 2,
				skal hele forsendelsen med alle efakturaene i den
				forkastes
				*/
				if( $oppdrag->statusForsendelse == 2 ) {
				
					// Hele forsendelsen er forkastet.
					// Alle efakturaene i forsendelsen må regnes som ikke utskrevet,
					// dvs at utskriftsdato må nulles:
					
					$giroer = $this->mysqli->arrayData(array(
						'source'		=> "efakturaer",
						'class'			=> "Giro",
						'distinct'		=> true,
						'fields'		=> "giro as id",
						'where'			=> "forsendelse = '{$oppdrag->forsendelsesnr}' and status != 'ok'"
					));
					
					foreach( $giroer as $giro ) {
						$giro->opprettEfaktura( null ); // Sletter efakturaen fra efakturatabellen
						$giro->sett('utskriftsdato', null);						
					}
					
					// Skriver et statusvarsel på driftsforsiden,
					// og sender en epost som forklarer feilen
					$melding = "En eFakturaforsendelse har blitt forkastet i sin helhet av NETS.<br />\nAlle eFakturaene i denne forsendelsen må derfor sendes på nytt når feilen er rettet.<br /><br /><b>Feilkode {$oppdrag->feilkode} fra NETS:</b><br />{$oppdrag->feilmelding}<br />\n";
					
					$this->sendMail( array(
						'testcopy'	=> true,
						'auto'		=> true,
						'to'		=> $this->valg['epost'],
						'priority'	=> 90,
						'subject'	=> "Problemer med eFakturaforsendelse",
						'html'		=> $melding
					) );
					
					$this->mysqli->saveToDb(array(
						'insert'		=> true,
						'table'			=> "internmeldinger",
						'fields'		=> array(
							'tekst'		=> $melding,
							'drift'		=> true
						)
					));
				
					
				}


				/*****************************************
				Dersom forsendelsesstatus = 0,
				er efakturaene ikke ferdig behandlet,
				men vil få status 'mottatt'
				*/
				else if( !$oppdrag->statusForsendelse ) {
					// Den aktuelle forsendelsen er ikke ferdig behandlet.
					// De efakturaene i forsendelsen får status 'mottatt',
					
					$this->mysqli->saveToDb(array(
						'table'		=> "efakturaer",
						'update'		=> true,
						'distinct'		=> true,
						'fields'		=> array(
							'kvittert_dato'	=> $forsendelse->dato->format('Y-m-d'),
							'kvitteringsforsendelse'	=> $forsendelse->forsendelsesnummer,
							'status'		=> "mottatt"
						),
						'where'			=> "forsendelse = '{$oppdrag->forsendelsesnr}'\n"
										.	"and status != 'ok'\n"
					));
				
				}		
			
				/*****************************************
				Dersom oppdragstype = 5,
				vil oppdraget inneholde kvittering på hvert enkelt
				av de innsendte efakturaoppdragene
				*/
				foreach($oppdrag->oppdrag as $efakturaOppdrag) {
					$efakturaOppdrag->oppdragsnr;		// Opprinnelig oppdragsnummer
					$efakturaOppdrag->oppdragskonto;	// Opprinnelig oppdragskonto
					$efakturaOppdrag->feilkode;		// Feilkode dersom statusOppdrag = 1
					$efakturaOppdrag->feilmelding;	// Forklaring på feilkode
					$efakturaOppdrag->antGodkjenteFakturaer;
					$efakturaOppdrag->antAvvisteFakturaer;
					$efakturaOppdrag->statusOppdrag;
						// 0 = Oppdraget i seg selv er godkjent
						// 1 = Oppdraget er i sin helhet forkastet
				
					if( $efakturaOppdrag->statusOppdrag == 1 ) {
						// Det aktuelle oppdraget er forkastet i sin helhet.
						// Alle efakturaene i oppdraget må regnes som ikke utkrevet,
						// dvs at utskriftsdato må nulles.
						
						$giroer = $this->mysqli->arrayData(array(
							'source'		=> "efakturaer",
							'class'			=> "Giro",
							'distinct'		=> true,
							'fields'		=> "giro as id",
							'where'			=> "forsendelse = '{$oppdrag->forsendelsesnr}'\n"
											.	"and oppdrag = '{$efakturaOppdrag->oppdragsnr}'\n"
											.	"and status != 'ok'\n"
						));
					
						foreach( $giroer as $giro ) {
							$giro->opprettEfaktura( null ); // Sletter efakturaen
							$giro->sett('utskriftsdato', null);						
						}
					
						// Skriver et statusvarsel på driftsforsiden,
						// og sender en epost som forklarer feilen
						$melding = "Et eFakturaoppdrag har blitt forkastet i sin helhet av NETS.<br />\nAlle eFakturaene i dette oppdraget må derfor sendes på nytt når feilen er rettet.<br /><br /><b>Feilkode {$efakturaOppdrag->feilkode} fra NETS:</b><br />{$efakturaOppdrag->feilmelding}<br />\n";
						
						$this->sendMail( array(
							'testcopy'	=> true,
							'auto'		=> true,
							'to'		=> $this->valg['epost'],
							'priority'	=> 90,
							'subject'	=> "Problemer med eFakturaoppdrag",
							'html'		=> $melding
						) );
						
						$this->mysqli->saveToDb(array(
							'insert'		=> true,
							'table'			=> "internmeldinger",
							'fields'		=> array(
								'tekst'		=> $melding,
								'drift'		=> true
							)
						));
					
					}
		
					// Oppdraget ser ut til å være ok.
					//	Slett evt avviste enkelttransaksjoner
					//	før de gjenværende transaksjonene godkjennes
					foreach( $efakturaOppdrag->transaksjoner as $transaksjon ) {

						$transaksjon->forfallsdato; 	// Som oppgitt i originalfila
						$transaksjon->kid; 				// Som oppgitt i originalfila
						$transaksjon->efakturaRef;		// Som oppgitt i originalfila
						$transaksjon->feilkode;			// Feilkode for faktura
						$transaksjon->feilmelding;		// Forklaring på feilkode
						$transaksjon->feilreferanse;	// Data fra felt med feil
				
						// Den aktuelle eFakturaen har feil og er forkastet.
						// efakturaen må regnes som ikke utkrevet,
						// dvs at utskriftsdato må nulles.
						// Gå gjennom feilkoden for å finne og rette feilen
						
						$giroer = $this->mysqli->arrayData(array(
							'source'		=> "giroer INNER JOIN efakturaer ON giroer.gironr = efakturaer.giro",
							'class'			=> "Giro",
							'distinct'		=> true,
							'fields'		=> "giroer.gironr as id",
							'where'			=> "efakturaer.forsendelse = '{$oppdrag->forsendelsesnr}'\n"
											.	"and efakturaer.oppdrag = '{$efakturaOppdrag->oppdragsnr}'\n"
											.	"and giroer.kid = '{$transaksjon->kid}'\n"
											.	"and status != 'ok'\n"
						));
				
						foreach( $giroer->data as $giro ) {

							switch( $transaksjon->feilkode ) {
						
							case 0:
								$giro->kvitterEfaktura(array(
									'dato'	=> new Date,
									'kvitteringsforsendelse' => $oppdrag->forsendelsesnr,
									'status'	=> 'ok'
								));
								break;
							case 040:
								// Putt inn et statusvarsel på driftsforsiden,
								// og send en epost som forklarer feilen
							default:

								break;
							}
						
							// Skriver et statusvarsel på driftsforsiden,
							// og sender en epost som forklarer feilen
							$melding = "eFaktura {$giro->hent('gironr')} har blitt forkastet av NETS.<br />\neFakturaen må derfor sendes på nytt når feilen er rettet.<br /><br /><b>Feilkode {$transaksjon->feilkode} fra NETS:</b><br />{$transaksjon->feilmelding}: <b>{$transaksjon->feilreferanse}</b>\n";
					
							$this->sendMail( array(
								'testcopy'	=> true,
								'auto'		=> true,
								'to'		=> $this->valg['epost'],
								'priority'	=> 90,
								'subject'	=> "Problemer med eFakturaoppdrag",
								'html'		=> $melding
							) );
					
							$this->mysqli->saveToDb(array(
								'insert'		=> true,
								'table'			=> "internmeldinger",
								'fields'		=> array(
									'tekst'		=> $melding,
									'drift'		=> true
								)
							));
				
							$giro->opprettEfaktura( null ); // Sletter efakturaen fra efakturatabellen
							$giro->sett('utskriftsdato', null);						
						}
					}

					if( !$efakturaOppdrag->statusOppdrag ) {
						// Det aktuelle oppdraget er ok i sin helhet.
						// De resterende efakturaene i oppdraget får status 'ok',
						
						$this->mysqli->saveToDb(array(
							'table'		=> "efakturaer",
							'update'		=> true,
							'distinct'		=> true,
							'fields'		=> array(
								'kvittert_dato'
									=> $forsendelse->dato->format('Y-m-d'),
								'kvitteringsforsendelse'
									=> $forsendelse->forsendelsesnummer,
								'status'
									=> "ok"
							),
							'where'			=> "forsendelse = '{$oppdrag->forsendelsesnr}'\n"
											.	"and oppdrag = '{$efakturaOppdrag->oppdragsnr}'\n"
											.	"and status != 'ok'\n"
						));
					
					}		
				}
				
			}
		}
	}
	
	else if( $forsendelse instanceof SimpleXMLElement ) {

	}
	
	return true;
}



// Funksjon som skanner en NETS-forsendelse for nye faste betalingsoppdrag
// (AvtaleGiro) og behandler disse
/****************************************/
//	$forsendelse NetsForsendelse-objekt	eller SimpleXml-objekt
//	--------------------------------------
public function registrerFbo( $forsendelse ) {

	$oppdragskonto = preg_replace('/[^0-9]+/', '', $this->valg['bankkonto']);
	if( $forsendelse instanceof NetsForsendelse ) {
	
		if ( 
			$forsendelse->datamottaker != $this->valg['nets_kundeenhetID']
			or $forsendelse->dataavsender != NETS_ID
			or !$forsendelse->valider()
		) {
			return false;
		}
		
		
		foreach ($forsendelse->oppdrag as $oppdrag ) {
			if(
				$oppdrag->tjeneste == 21
				and $oppdrag->oppdragstype == 24
				and $oppdrag->oppdragskonto == $oppdragskonto
			) {
				
				// registreringstype: 0 = Oppdatering av hele FBO-oversikten
				if (
					isset( $oppdrag->transaksjoner[0] )
					and $oppdrag->transaksjoner[0]->registreringstype == 0
				) {
					//	For synking klargjøres alle for sletting med mindre de er med i oversikten fra NETS
					$this->mysqli->saveToDb(array(
						'table'		=> "fbo",
						'update'	=> true,
						'where'		=> 1,
						'fields'	=> array(
							'slettet'		=> 1
						)
					));
				}

				foreach( $oppdrag->transaksjoner as $transaksjon ) {

					$leieforhold = $this->leieforholdFraKid( $transaksjon->kid );
					$type = substr( $transaksjon->kid, 5, 1 );
					$skriftligVarsel =  $transaksjon->skriftligVarsel == "N" ? 0 : 1;

					if ($transaksjon->registreringstype == 2) {
						// registreringstype: 2 = Slettet
					
						$this->mysqli->query("
							DELETE FROM fbo
							WHERE leieforhold = '{$leieforhold}'
						");
					}

					if ( $transaksjon->registreringstype < 2 ) {
						// registreringstype: 0 = Bekreftelse (synkronisering av avtaler)
						// registreringstype: 1 = Ny avtale / endring av eksisterende avtale
					
						$this->mysqli->query("
							INSERT INTO fbo (leieforhold, type, varsel, slettet)
							VALUES ('{$leieforhold}', '{$type}', '{$skriftligVarsel}', 0)
							ON DUPLICATE KEY UPDATE varsel = '{$skriftligVarsel}', slettet = 0
						");
					}
				}

				// Alle avtaler som ikke er oppdater slettes hvis de er forberedt for det
				$this->mysqli->query("
					DELETE FROM fbo
					WHERE slettet
				");
			}
		}
	}
	
	else if( $forsendelse instanceof SimpleXMLElement ) {

	}
	
	return true;
}



// Funksjon som skanner en NETS-forsendelse for OCR-konteringsdata,
// og registrerer evt nye transaksjoner som innbetalinger
/****************************************/
//	$forsendelse NetsForsendelse-objekt	eller SimpleXml-objekt
//	--------------------------------------
//	retur: (bool) Suksess ??
public function registrerOcrKonteringsdata( $forsendelse ) {

	if( $forsendelse instanceof NetsForsendelse ) {
	
		if ( 
			$forsendelse->datamottaker != $this->valg['nets_kundeenhetID']
			or $forsendelse->dataavsender != NETS_ID
			or !$forsendelse->valider()
		) {
			return false;
		}
		
		foreach ($forsendelse->oppdrag as $oppdrag ) {
			if(
				$oppdrag->tjeneste == 9
				and $oppdrag->avtaleId == $this->valg['nets_avtaleID_ocr']
			) {
				if( !$this->mysqli->arrayData(array(
					'source'	=> "ocr_filer",
					'where'		=> "forsendelsesnummer = '{$forsendelse->forsendelsesnummer}'"
				))->totalRows ) {
		
					$filid = $this->mysqli->saveToDb(array(
						'insert'	=> true,
						'table'		=> "ocr_filer",
						'fields'	=> array(
							'forsendelsesnummer'	=> $forsendelse->forsendelsesnummer,
							'oppgjørsdato'			=> $oppdrag->oppgjørsdato->format('Y-m-d'),
							'registrerer'			=> $this->bruker['navn'],
							'registrert'			=> date('Y-m-d H:i:s'),
							'OCR'					=> $forsendelse
						)
					))->id;
		
					if( $filid ) {
						foreach( $oppdrag->transaksjoner as $transaksjon ) {
				
							$detaljID = $this->mysqli->saveToDb(array(
								'insert'	=> true,
								'table'		=> "OCRdetaljer",
								'fields'	=> array(
									'arkivreferanse'		=> $transaksjon->arkivreferanse,
									'avtaleid'				=> $oppdrag->avtaleId,
									'bankdatasentral'		=> $transaksjon->sentralId,
									'beløp'					=> $transaksjon->beløp,
									'blankettnummer'		=> $transaksjon->blankettnummer,
									'debetkonto'			=> $transaksjon->debetKonto,
									'delavregningsnummer'	=> $transaksjon->delavregningsnr,
									'filID'					=> $filid,
									'forsendelsesnummer'	=> $forsendelse->forsendelsesnummer,
									'fritekst'				=> $transaksjon->fritekstmelding,
									'kid'					=> $transaksjon->kid,
									'løpenummer'			=> $transaksjon->løpenr,
									'oppdragsdato'			=> $transaksjon->oppdragsdato
																->format('Y-m-d'),
									'oppdragskonto'			=> $oppdrag->oppdragskonto,
									'oppdragsnummer'		=> $oppdrag->oppdragsnr,
									'oppgjørsdato'			=> $transaksjon->oppgjørsdato
																->format('Y-m-d'),
									'transaksjonsnummer'	=> $transaksjon->transaksjonsnr,
									'transaksjonstype'		=> $transaksjon->transaksjonstype
								)
							))->id;
	
					
							// OCR-fil og -detaljer er lagret. Nå opprettes en betaling
							// på grunnlag av transaksjonen:
							
							$transaksjon->id = $detaljID;
							$transaksjon->forsendelsesnummer = $forsendelse->forsendelsesnummer;
							$this->registrerBetaling( $transaksjon );
						}
					}
				}		
			}
		}
	}
	
	else if( $forsendelse instanceof SimpleXMLElement ) {

	}
	
}



// Registrerer utskriftsforsøket som er lagret i innstillinene
// Denne funksjonen registrerer utskriftsdato og oppretter purregebyr,
// og sletter innholdet i utskriftsforsøk-innstillingen.
//	--------------------------------------
//	resultat: suksessangivelse
public function registrerUtskrift () {

	if ( !$this->valg['utskriftsforsøk'] ) {
		return false;
	}
	
	$utskriftsforsøk = unserialize($this->valg['utskriftsforsøk']);
	
	$efakturagiroer = array();
	
	if( is_array( $utskriftsforsøk->giroer ) ) {
		foreach( $utskriftsforsøk->giroer as $giro ) {
			if( !is_a( $giro, 'Giro' ) ) {
				$giro = $this->hent('Giro', $giro );
			}
			
			$leieforhold = $giro->hent('leieforhold');
			$fbo = $leieforhold->hent('fbo');
			$fboTrekkrav = $giro->hent('fboTrekkrav');
			

			// Utskriftsdato settes, og evt. efakturaer oversendes NETS.
			//
			//	Dersom det brukes kombinasjon av fbo (avtalegiro) og efaktura
			//	skal ikke eFaktura sendes nå men om to dager.
			//	I disse tilfellene skal det heller ikke påføres utskriftsdato nå.

			$kombi 
				= ($this->valg['efaktura']
				&& $leieforhold->hent('efakturaavtale')
				&& $this->valg['avtalegiro']
				&& $fbo
				);

			if(
				$giro->hent('utskriftsdato') === null 
				&& !$kombi
			) {

				$giro->sett( 'utskriftsdato', $utskriftsforsøk->tidspunkt );
				$giro->sett( 'format', 'papir' );

				// Samle eFaktura i egen bunke
				// men ikke dersom leieforholdet også har avtalegiro (fbo)
				if(
					( $this->valg['efaktura'] and $leieforhold->hent('efakturaavtale') and $giro->hent('beløp') > 0)
					and !$kombi
				) {
					$efakturagiroer[] = $giro;
				}				
			}
		}
	}
	

	// Lag eFakturaforsendelse til NETS
	if( $efakturagiroer ) {

		$efakturaoppdrag = $this->netsLagEfakturaOppdrag( $efakturagiroer );
		$efakturaforsendelse = $this->netsLagEfakturaForsendelse( $efakturaoppdrag );

		if( $efakturaforsendelse ) {
			foreach( $efakturagiroer as $giro ) {
				$giro->opprettEfaktura( array(
					'forsendelsesdato'	=> new DateTime,
					'forsendelse'		=> $efakturaforsendelse->forsendelsesnummer,
					'oppdrag'			=> $efakturaoppdrag->oppdragsnr
				) );
			}
		}
	}


	
	if( is_array( $utskriftsforsøk->purringer ) ) {
		foreach( $utskriftsforsøk->purringer as &$purring ) {
			if( !is_a( $purring, 'Purring' ) ) {
				$purring = $this->hent('Purring', $purring );
			}
			if( in_array( $purring, $utskriftsforsøk->gebyrpurringer ) ) {
				$purring->opprettGebyr(array());
			}
		}
		$this->epostpurring( $utskriftsforsøk->purringer );
	}
	
	$resultat = $this->mysqli->saveToDb(array(
		'update'	=> true,
		'table'		=> "valg",
		'where'		=> "innstilling = 'utskriftsforsøk'",
		'fields'	=> array(
			'verdi'	=> ""
		)
	))->success;	

	$this->hentValg();
	return $resultat;
}



/*	Sammenlikn egenskaper
Funksjon som sammenlikner to objekters egenskaper for sortering.
Egenskapene som skal sammenliknes settes i $this->sorteringsegenskap
Flere sorteringsegenskaper kan settes for finsortering
******************************************
$objekt1 (objekt): Ett av to objekter som skal sammenliknes
$objekt2 (objekt): Det andre av to objekter som skal sammenliknes
------------------------------------------
retur: (heltall) < 0 dersom $objekt1 er minst,
		> 0 dersom $objekt1 er størst,
		og 0 dersom $objekt1 og $objekt2 er like
*/
public function sammenliknEgenskaper( $objekt1, $objekt2 ) {
	settype($this->sorteringsegenskap, 'array');
	settype($objekt1, 'object');
	settype($objekt2, 'object');

	foreach( $this->sorteringsegenskap as $egenskap ) {
		$verdi1 = @$objekt1->$egenskap;
		$verdi2 = @$objekt2->$egenskap;

		//	Strengverdier sorteres etter andre verdier
		if( is_string($verdi1) xor is_string($verdi2) ) {
			return intval(is_string($verdi1)) - intval(is_string($verdi2));
		}
		
		if( !is_numeric($verdi1) and  !is_numeric($verdi2) ) {
			if( function_exists('collator_compare') ) {
				return collator_compare( new Collator('no_NB'), $verdi1, $verdi2);
			}
			else {
				return strcasecmp($verdi1, $verdi2);
			}
		}
		
		if( $verdi1 <  $verdi2 ) {
			return -1;
		}
		
		if( $verdi1 >  $verdi2 ) {
			return 1;
		}
	}
	return 0;
}



/*	Sammenlikn kravs oppsigelser
Sorter kravene for oppsagte leieforhold
først etter utløp av oppsigelsestiden,
og deretter etter utflyttingsdato
Brukes i usort()
*****************************************/
//	$krav1 (Krav):	
//	$krav2 (Krav):	
//	--------------------------------------
//	retur: (heltall) <0 dersom $krav1 er mindre enn $krav2,
//			>0 dersom $krav1 er større enn $krav2,
//			og 0 dersom $krav1 og $krav2 er like
public static function sammenliknKravsOppsigelser( $krav1, $krav2 ) {
	$oppsigelse1 = $krav1->hent('leieforhold')->hent('oppsigelse');
	$oppsigelse2 = $krav2->hent('leieforhold')->hent('oppsigelse');

	if ($oppsigelse1->oppsigelsestidSlutt == $oppsigelse2->oppsigelsestidSlutt) {
		return	$oppsigelse1->fristillelsesdato->format('U')
			- $oppsigelse2->fristillelsesdato->format('U');
	}

	else {
		return	$oppsigelse1->oppsigelsestidSlutt->format('U')
			- $oppsigelse2->oppsigelsestidSlutt->format('U');
	}
}



/*	Sammenlikn leieobjekters ledighet
Brukes i usort() for å sortere leieobjekter etter ledighet
*****************************************/
//	$leieobjektA (Leieobjekt):	
//	$leieobjektB (Leieobjekt):	
//	--------------------------------------
//	retur: (heltall) <0 dersom $leieobjektA er mindre enn $leieobjektB,
//			>0 dersom $leieobjektA er større enn $leieobjektB,
//			og 0 dersom $leieobjektA og $leieobjektB er like
public static function sammenliknLeieobjektersLedighet( $leieobjektA, $leieobjektB ) {
	 $differanse = round( $leieobjektB->tilgjengelighet * 100 ) - round( $leieobjektA->tilgjengelighet * 100 );
	 if($differanse == 0) {
		 $differanse = round( $leieobjektB->andelSomVilFristilles * 100 ) - round( $leieobjektA->andelSomVilFristilles * 100 );
	 }
	 if($differanse == 0) {
		 $differanse = $leieobjektA->leieobjektnr - $leieobjektB->leieobjektnr;
	 }
	 return $differanse;
}



/*	Sammenlikn transaksjonsdatoer i krav og innbetalinger
Brukes i usort() for å sortere transaksjoner etter dato
*****************************************/
//	$transaksjon1 (stdClass, Innbetaling eller Krav-objekt)
//	$transaksjon2 (stdClass, Innbetaling eller Krav-objekt)
//	--------------------------------------
//	retur: (heltall) <0 dersom $transaksjon1 er mindre enn $transaksjon2,
//			>0 dersom $transaksjon1 er større enn $transaksjon2,
//			og 0 dersom $transaksjon1 og $transaksjon2 er like
public function sammenliknTransaksjonsdatoer( $transaksjon1, $transaksjon2 ) {
	
	if( $transaksjon1 instanceof stdClass ) {
		$dato1 = @$transaksjon1->dato;
	}
	else {
		$dato1 = $transaksjon1->hent('dato');
	}
	
	if( $transaksjon2 instanceof stdClass ) {
		$dato2 = @$transaksjon2->dato;
	}
	else {
		$dato2 = $transaksjon2->hent('dato');
	}

	if( $dato1 < $dato2 ) {
		return -1;
	}
	if( $dato1 > $dato2 ) {
		return 1;
	}
	
	// Dersom datoene er like vil innbetalingene sorteres før kravene
	if ( ($transaksjon1 instanceof Innbetaling or $transaksjon1 instanceof stdClass) and $transaksjon2 instanceof Krav ) {
		return -1;
	}
	if ( $transaksjon1 instanceof Krav and ($transaksjon2 instanceof Innbetaling or $transaksjon2 instanceof stdClass) ) {
		return 1;
	}
	
	// Innenfor samme dato vil to transaksjoner av samme type sorteres etter id
	if (
		( $transaksjon1 instanceof Krav and $transaksjon2 instanceof Krav )
		or ( $transaksjon1 instanceof Innbetaling and $transaksjon2 instanceof Innbetaling )
	) {
		return $transaksjon1->hentId() - $transaksjon2->hentId();
	}
	
	return 0;
}



//	$this->live slår på alle epostsendinger
// Sender mail som HTML eller tekst. $config er et objekt som består av:
//	to
//	cc
//	bcc
//	from
//	auto		boolsk (normalt sann) meldingen er automatisk generert
//	testcopy	boolsk (normalt usann) sender blindkopi til kyegil@gmail.com
//	reply
//	subject
//	html HTML-versjonen av meldingen
//	text Ren tekst-versjon av meldingen
//  priority (heltall) Epostens prioritet 0 = lav prioritet, 100 = høy
public function sendMail($config) {
	settype( $config, 'array');
	if (!isset( $config['auto'] ) ) {
		$config['auto'] = true;
	}
	settype( $config['to'], 'string');
	settype( $config['from'], 'string');
	settype( $config['cc'], 'string');
	settype( $config['bcc'], 'string');
	settype( $config['reply'], 'string');
	settype( $config['subject'], 'string');
	settype( $config['html'], 'string');
	settype( $config['text'], 'string');
	settype( $config['priority'], 'integer');
	settype( $config['auto'], 'boolean');
	settype( $config['testcopy'], 'boolean');

	$random_hash = md5(date('r'));
	
	$header = "";
	$header .= "Auto-Submitted: " . ($config['auto'] ? "auto-generated": "no") . "\r\n";
//	$header .= "Return-Path: {$this->valg['autoavsender']}\r\n";
		
	$header .= $config['cc'] ? "Cc: {$config['cc']}\r\n" : "";
	
	$header .= ($config['bcc'] or $config['testcopy']) ? "Bcc: " : "";
	$header .= $config['bcc'];
	$header .= ($config['bcc'] and $config['testcopy']) ? ", " : "";
	$header .= $config['testcopy'] ? "kyegil@gmail.com" : "";
	$header .= ($config['bcc'] or $config['testcopy']) ? "\r\n" : "";
	
	$header .= $config['from'] ? "From: {$config['from']}\r\n" : "From: {$this->valg['autoavsender']}\r\n";
	
	$header .= $config['reply'] ? "Reply-To: {$config['reply']}\r\n" : "";
	$header .= "X-Mailer: PHP/" . phpversion() . "\r\n";
	$header .= "MIME-Version: 1.0\r\n";
	$header .= $config['html'] ? "Content-Type: multipart/alternative; boundary=\"Leiebasemail-alt-$random_hash\"\r\n" : "Content-type: text/plain; charset=UTF-8\r\n";
	
	// Dersom teksten bare er oppgitt som HTML og ikke som ren tekst, opprettes en ren tekst-versjon av den HTML-formaterte.
	if($config['html'] and !$config['text']) {

		$config['text'] = html_entity_decode( strip_tags( str_ireplace(
			array("&nbsp;","<br />","<br>","<br/>"),
			array(" ","\r\n","\r\n","\r\n"),
			$config['html']
		) ) ); 
	}


	if($config['html']) {
		$innhold = "--Leiebasemail-alt-$random_hash\r\nContent-type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n{$config['text']}\n" .	"--Leiebasemail-alt-$random_hash\r\nContent-type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n{$config['html']}\n--Leiebasemail-alt-$random_hash--";
	}
	else {
		$innhold = $config['text'];
	}

	return $this->mysqli->saveToDb(array(
		'insert'	=> true,
		'table'		=> "epostlager",
		'fields'	=> array(
			'prioritet'	=>	$config['priority'],
			'til'		=>	$config['to'],
			'emne'		=>	$config['subject'],
			'innhold'	=>	$innhold,
			'hode'		=>	$header,
			'param'		=>	""
//				'param'		=>	"-f {$this->valg['autoavsender']}"
		)
	))->success;
}


//	Returnerer siste kontraktnr i et leieforhold. $kontraktnr kan være hvilken som helst kontrakt i leieforholdet.
public function sistekontrakt( $kontraktnr ) {
	$kontraktnr = intval(strval( $kontraktnr ));
	
	$a = $this->arrayData("SELECT MAX(kontraktnr) AS kontraktnr FROM kontrakter WHERE leieforhold = '" . $this->leieforhold($kontraktnr) . "'");
	if($a['data'][0]['kontraktnr']) return $a['data'][0]['kontraktnr'];
	else return false;
}


/*	Skje
Utfører evt handlinger knagget til dette stadiet
******************************************
$stadium (streng): stadiet som utløser handlingene
------------------------------------------
retur (boolsk):
*/
protected function skje() {
	$antallArgumenter = func_num_args();
	$argumenter = func_get_args();
	$resultat = true;
	
	if( $antallArgumenter < 1 ) {
		trigger_error("Funksjon eller metode er ikke oppgitt", E_USER_ERROR);
    }
	
	$stadium = array_shift($argumenter);

	$handlinger =  @$this->knagger[ $stadium ];
	if( !$handlinger ) {
		return true;
	}
	
	foreach( $handlinger as $handling ) {
		if( is_callable( $handling ) ) {
			$resultat &= call_user_func_array($handling, $argumenter);
		}
		else {
			throw new Exception( "Kan ikke utføre " . print_r($handling, true) . "");
		}
	}
	return;
}


public function skrivHeader( $title = "" ) {
	if( $this->skje( 'skrivHeader', $title ) === false) {
		return;
	}
	
$bibliotek = $this->http_host."/".$this->ext_bibliotek;
?>
	<meta charset="utf-8" />
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW" />
	<title><?php echo $title ? $title : $this->tittel;?></title>
	
	<link rel="stylesheet" type="text/css" href="<?php echo $bibliotek;?>/resources/css/ext-all.css" media="screen" />
<?if($this->ext_bibliotek != 'ext-3.4.0'):?>
	<link rel="stylesheet" type="text/css" href="<?php echo $bibliotek;?>/resources/css/ext-all-gray.css" media="screen" />
<?else:?>
	<link rel="stylesheet" type="text/css" href="<?php echo $bibliotek;?>/resources/css/xtheme-gray.css" media="screen" />
<?endif?>
	<link rel="stylesheet" type="text/css" href="<?php echo $this->http_host;?>/leiebase.css" media="screen" />
	
<?if($this->ext_bibliotek == 'ext-3.4.0'):?>
	<script language="JavaScript" type="text/javascript" src="<?php echo $bibliotek;?>/adapter/ext/ext-base.js"></script>
<?endif?>

	<script language="JavaScript" type="text/javascript" src="<?php echo $bibliotek;?>/ext-all.js"></script>
<?if($this->ext_bibliotek == 'ext-3.4.0'):?>
	<script language="JavaScript" type="text/javascript" src="<?php echo $this->http_host;?>/plugins/GroupSummary.js"></script>
	<script language="JavaScript" type="text/javascript" src="<?php echo $this->http_host;?>/ext-ux/RowExpander.js"></script>
	<script language="JavaScript" type="text/javascript" src="<?php echo $bibliotek;?>/src/locale/ext-lang-no_NB.js"></script>
<?else:?>
	<script language="JavaScript" type="text/javascript" src="<?php echo $bibliotek;?>/locale/ext-lang-no_NB.js"></script>
<?endif?>
	<script language="JavaScript" type="text/javascript" src="<?php echo $this->http_host;?>/fellesfunksjoner.js"></script>

	<script language="JavaScript" type="text/javascript">
	<?php $this->skript();?>
</script>
<?
	$this->skje( 'skrivHeaderUtført', $title );
}


/*	Skriv HTML
Laster malen for siden og sender innholdet ut til nettleseren
Utsendelsen kan stoppes ved at preHTML() returneres usann
******************************************
------------------------------------------
*/
public function skrivHTML() {
	if( !$this->preHTML() ) {
		return false;
	}
	if(!include($this->mal)) {
		throw new Exception("Finner ikke fila '{$this->mal}' i '{$this->katalog($_SERVER['PHP_SELF'])}'");
	}
	return true;
}



// SKAL BEHOLDES 2015-04-22:
// Slett ubrukte giroer
// Gjenoppløser krav som er samlet til giro, men som fortsatt ikke har blitt skrevet ut
// Selve gironummerene slettes ikke, fordi de inneholder viktig kopling mellom KID og leieforhold.
/****************************************/
//	$kontroll: liste med kontroller:
//		giroer: valgfri, liste over gironr som skal slettes.
//				Dersom denne er angitt, slettes kun disse giroene.
//	--------------------------------------
//	resultat: suksessangivelse
public function slettUbrukteGiroer($kontroll = array()) {
	if ( !is_array($kontroll) && !is_object($kontroll) ) {
		$kontroll = array($kontroll);
	}
	settype($kontroll, 'object');
	settype($kontroll->giroer, 'array');
	
	foreach( $kontroll->giroer as &$giro) {
		settype($giro, 'integer');
	}
	
	$sql = "
		UPDATE krav INNER JOIN giroer on krav.gironr = giroer.gironr
		SET krav.gironr = NULL, krav.utskriftsdato = NULL
		WHERE giroer.utskriftsdato IS NULL
		AND krav.utestående > 0
	";
	if($kontroll->giroer) {
		$sql .= "AND (krav.gironr = '" . implode("' OR krav.gironr = '", $kontroll->giroer) . "')";
	}
	
	return $this->mysqli->query($sql);
}


// returnerer dagen før fristillelsesdato for et leieforhold dersom leieforholdet er oppsagt.
// returnerer sluttdatoen dersom leieforholdet er tidsbegrenset, og NULL dersom det ikke er tidsbegrenset
public function sluttdato($kontraktnr) {
	if($b = $this->oppsagt($kontraktnr))
		return $b - 24*3600;
		
	$sql = "SELECT *\n"
	.		"FROM `kontrakter` AS a INNER JOIN kontrakter AS b ON a.leieforhold = b.leieforhold\n"
	.		"WHERE a.kontraktnr = $kontraktnr AND a.tildato IS NULL";
	$a = $this->arrayData($sql);
	if (count($a['data']) > 0)
		return null;
		
	$sql =	"SELECT MAX(a.tildato) AS sluttdato\n"
	.		"FROM `kontrakter` AS a INNER JOIN kontrakter AS b ON a.leieforhold = b.leieforhold\n"
	.		"WHERE b.kontraktnr = $kontraktnr";
	$a = $this->arrayData($sql);
	
	if( isset($a['data'][0]['sluttdato'])) {
		return strtotime($a['data'][0]['sluttdato']);
	}
	else {
		return null;
	}
}


/*	Sorter objekter
Funksjon som sorterer ett array av objekter basert på gitte egenskaper
******************************************
$objekter (array): Arrayet med objekter som skal sorteres
$egenskap (streng): Egenskapen objektene skal sorteres etter
$synkende (boolsk): Sorteres i synkende rekkefølge (normalt av for stigende)
------------------------------------------
retur: (array) sortert array
*/
public function sorterObjekter( $objekter, $egenskap, $synkende = false, $type = false ) {
	settype($egenskap, 'array');
	$this->sorteringsegenskap = (array)$egenskap;
	
	if(!usort($objekter, array($this, 'sammenliknEgenskaper'))) {
		throw new Exception("Kunne ikke sammenlikne etter " . json_encode($this->sorteringsegenskap));
	}
	
	if( $synkende ) {
		return $objekter = array_reverse( $objekter );
	}
	
	return $objekter;
}



public function storebokstaver($tekst) {
	$tekst = htmlentities($tekst, ENT_QUOTES);

	$resultat = preg_replace_callback(
		'/&([a-z])(uml|acute|circ|tilde|ring|elig|grave|slash|horn|cedil|th);/', 
		function($treff) {
			return "'&'.strtoupper('{$treff[1]}').'{$treff[2]}'.';'";
		},
		$tekst
	);
	
	// convert from entities back to characters
	$htmltabell = get_html_translation_table(HTML_ENTITIES);
	foreach($htmltabell as $nr => $verdi) {
		$resultat = str_replace( addslashes($verdi), $nr, $resultat );
	}
	return( strtoupper($resultat));
}


public function strengellernull($streng, $hermetegn = "'") {
	if($streng !="" and $streng !=null)
		return $hermetegn.$streng.$hermetegn;
	else return 'NULL';
}


public function terminlengde($ant_terminer) {
	switch($ant_terminer) {
		case 0:
		case ($ant_terminer>365):
			return false;
		case 1:
		case 2:
		case 3:
		case 4:
		case 6:
		case 12:
			return "P" . (12 / $ant_terminer) . "M";
		case 13:
		case 26:
		case 52:
			return "P" . (364 / $ant_terminer) . "D";
		default:
			return "P" . (int)(365 / $ant_terminer) . "D";
	}
}


// Formaterer en desimalverdi som brøk
/****************************************/
//	$verdi: (nummer) desimalverdien
//	--------------------------------------
//	retur: verdien som desimal
public function tilBrøk( $verdi ) {

	// Rund av verdien til maks 6 desimaler
	$heltall = (int)$verdi;
	$desimal = bcsub( bcadd($verdi, '0.0000005', 6), $heltall, 6);

	// Loop gjennom for å finne en match, opp til og med 120-deler
	for( $nevner = 2; $nevner < 121; $nevner++ ) {
		$teller = round(($desimal * $nevner), 4);
		
		if ((int)$teller and $teller == (int)$teller ) {
			return ($heltall ? "{$heltall} " : "") . (int)$teller . "/{$nevner}";
		}
	}
	
	// Alle brøkmuligheter til og med 120-deler har blitt forsøkt
	//	uten å finne en som kan representere desimaltallet
	//	Derfor returneres tallet i prosent med to desimaler
	return bcmul( $verdi, 100, 2) . "%";
}


/*	Returnerer tidligst mulige kravdato i hht innstillingene i leiebasen
******************************************
------------------------------------------
retur (boolsk / DateTime):	Siste mulige kravdato, eller false dersom denne ikke begrenset
*/
public function tidligstMuligeKravdato() {
	$this->hentValg();
	$sperredato = $this->valg['sperredato_for_etterregistrering_av_krav'];
	
	if(!$sperredato) {
		return false;
	}
	$tidligstekravdato = new DateTime( date('Y-m-01') );
	
	if($sperredato > date('j')) {
		$tidligstekravdato->sub( new DateInterval('P1M') );
	}
	return $tidligstekravdato;
}


///// AVLEGS !!!! Skal ikke brukes
public function tolkDato( $dato ) {
//	throw new Exception('Avlegs funksjon Leiebase::tolkDato()');
	if( strtotime( $dato ) ) {
		return date('Y-m-d', strtotime($dato));
	}
	else {
		return null;
	}
}


// Skriver ut tekststreng med krav og leieforhold fra KID
/****************************************/
//	$kid (streng) KID
//	--------------------------------------
//	retur:	(tekst)
public function tolkKid( $kid ) {

	$leieforhold = $this->leieforholdFraKid( $kid );
	
	$a = array();
	
	if( strlen( $kid ) == 7 ) {
	
		if( substr( $kid, 0, 1 ) == 2 ) {
			$kravsett = $this->kravFraKid($kid);
			foreach($kravsett as $krav) {
				$a[] = $krav->hent('tekst');
			}
		}
	}
	
	if( strlen( $kid ) == 13 ) {
	
		if( intval( substr( $kid, 6, 6 ) ) ) {
			$kravsett = $this->kravFraKid($kid);
			foreach($kravsett as $krav) {
				$a[] = $krav->hent('tekst');
			}
		}
	}
	
	return $this->liste( $a )
		. " (Leieforhold " . $this->leieforholdFraKid( $kid ) . ": "
		. $leieforhold->hent('navn')
		. " i " . $this->leieobjekt( $leieforhold->hent('leieobjekt'), true) . ")";
}



// Beregner tverrsum, evt minste tverrsum av et tall
/****************************************/
//	$verdi:	(heltall) Tallet det skal beregnes tverrsum av.
//	$minste_tverrsum:	(boolsk) Sann for å fortsette til minste tverrsum (et siffer).
//	--------------------------------------
//	retur: (Heltall) Tverrsum
public function tverrsum( $verdi, $minste_tverrsum = true ) {
	
	settype( $verdi, 'string' );
	
	$sifferliste = str_split( strrev( $verdi ) );
	$resultat = array_sum( $sifferliste );

	if(($resultat > 9) and $minste_tverrsum) {
		$resultat = $this->tverrsum( $resultat );
	}

	return $resultat;
}



// Funksjon for å sammenlikne to objekters utdelingsorden
/****************************************/
//	--------------------------------------
//	retur:	>0 om $a er størst, <0 om $b er størst, og 0 om de er like
public function utdelingsorden($a, $b) {
	return $a->hent('utskriftsposisjon', array(
			'rute' => $this->valg['utdelingsrute']
		))
		- $b->hent('utskriftsposisjon', array(
			'rute' => $this->valg['utdelingsrute']
		));
}



//	Returnerer info om hvor stor del av leieobjektet som er utleid, som et objekt med følgende nøkler:
// ['sum']: Hvor stor del av leieobjektet som er utleid, oppgitt som desimaltall med 12 desimaler
// ['ledig']: Hvor stor del av leieobjektet som er ledig, oppgitt som desimaltall med 12 desimaler
// ['kontrakter']: (array) Alle kontraktene oppgitt som $kontraktnr => array('andel', 'fradato', 'tildato')
public function utleie($leieobjektnr, $dato = 0, $tildato = 0, $inkl_oppsigelsestid = false) {
	if(!$dato) $dato = time();

	$resultat['kontrakter'] = array();
	$resultat['ledig'] = 1;
	$resultat['sum'] = 0;

	if(!$tildato) {
		// Finn alle leieforhold i leieobjektet som har vært påbegynt før $dato
		$sql =	"SELECT MAX(kontrakter.kontraktnr) AS kontraktnr, kontrakter.leieforhold, DATE_SUB(fristillelsesdato, INTERVAL 1 DAY) AS tildato\n"
			.	"FROM kontrakter LEFT JOIN oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold\n"
			.	"WHERE leieobjekt = '$leieobjektnr'\n"
			.	"AND fradato <= '" . date('Y-m-d', $dato) . "'\n"
			.	"AND (fristillelsesdato IS NULL OR fristillelsesdato > '" . date('Y-m-d', $dato) . "')\n"
			.	"GROUP BY kontrakter.leieforhold";
			
		if($inkl_oppsigelsestid){
			$sql =	"SELECT MAX(kontrakter.kontraktnr) AS kontraktnr, kontrakter.leieforhold, MAX(krav.tom) AS tildato\n"
				.	"FROM kontrakter INNER JOIN krav ON kontrakter.kontraktnr = krav.kontraktnr\n"
				.	"WHERE krav.type = 'Husleie'\n"
				.	"AND kontrakter.leieobjekt = '$leieobjektnr'\n"
				.	"AND fradato <= '" . date('Y-m-d', $dato) . "'\n"
				.	"GROUP BY kontrakter.leieforhold\n"
				.	"HAVING MAX(krav.tom) > '" . date('Y-m-d', $dato) . "'\n";
		}
		
		$a = $this->arrayData($sql);
		
		// Henter andelen i hver kontrakt
		foreach($a['data'] as $leieforhold) {
			$sql =	"SELECT kontraktnr, fradato, andel\n"
				.	"FROM kontrakter\n"
				.	"WHERE kontraktnr = '{$leieforhold['kontraktnr']}'\n";
			$b = $this->arrayData($sql);
			$resultat['kontrakter'][$b['data'][0]['kontraktnr']]['andel'] = $this->evaluerAndel($b['data'][0]['andel']);
			$resultat['kontrakter'][$b['data'][0]['kontraktnr']]['kontraktnr'] = $b['data'][0]['kontraktnr'];
			$resultat['kontrakter'][$b['data'][0]['kontraktnr']]['fra'] = $b['data'][0]['fradato'];
			$resultat['kontrakter'][$b['data'][0]['kontraktnr']]['til'] = $leieforhold['tildato'];
			$resultat['sum'] += $this->evaluerAndel($b['data'][0]['andel']);
			$resultat['ledig'] -= $this->evaluerAndel($b['data'][0]['andel']);
		}
	
		$resultat['sum'] = round($resultat['sum'], 12);
		$resultat['ledig'] = round($resultat['ledig'], 12);
	}
	else {
		// Hent alle datoer hvor det har vært endring
		$sql =	"SELECT fradato AS dato\n"
			.	"FROM kontrakter\n"
			.	"WHERE fradato > '" . date('Y-m-d', $dato) . "'\n"
			.	"AND fradato < '" . date('Y-m-d', $tildato) . "'\n"
			.	"AND leieobjekt = '$leieobjektnr'\n"
			.	"UNION\n"
			.	"SELECT '" . date('Y-m-d', $dato) . "' AS dato\n"
			.	"UNION\n"
			.	"SELECT '" . date('Y-m-d', $tildato) . "' AS dato\n"
			.	"ORDER BY dato\n";
		$a = $this->arrayData($sql);
		foreach($a['data'] as $test) {
			$b = $this->utleie($leieobjektnr, strtotime($test['dato']));
			foreach($b['kontrakter'] as $kontraktnr=>$andel) {
				$resultat['kontrakter'][$kontraktnr] = $andel;
				$resultat['sum'] = max($resultat['sum'], $b['sum']);
				$resultat['ledig'] = min($resultat['ledig'], $b['ledig']);
			}
		}
		
	}
	return $resultat;
}



/***************************/
/*Forbedret versjon av utleiefunksjonen*/
//	Returnerer stdClass objekt med følgende egenskaper:
	//	leieforhold: array av stdClass objekter med følgende egenskaper:
		//	leieforhold, kontraktnr(maks), andel, fradato, tildato(fristillelsesdato - 1)
	//	oppsigelsestid: array av leieforhold som er i oppsigelsestiden, i form av stdClass objekter med følgende egenskaper:
		//	leieforhold, kontraktnr(maks), andel, fradato, tildato(fristillelsesdato - 1)
	//	sum: Hvor stor del av leieobjektet som er utleid ihht kontraktene, oppgitt som desimaltall med 12 desimaler. Oppsigelsestid regnes som ledig
	// ledig: Hvor stor del av leieobjektet som er ledig, oppgitt som desimaltall med 12 desimaler. Oppsigelsestid regnes som ledig
	//	leiekrav: array av leiekrav som stdClass objekter, sortert etter leieforhold, fom og tom
	//	leiedekning: Hvor stor del av leieobjektet som er dekt av leiekrav, oppgitt som desimaltall med 12 desimaler. Leia skal dekkes i oppsigelsestida
public function utleie_ny($leieobjektnr, $dato = 0, $tildato = 0) {
	$resultat = new stdclass;

	if(!strtotime($dato)) $dato = date('Y-m-d');
	if(!strtotime($tildato)) $tildato = $dato;

	$resultat->leieforhold = $this->mysqli->arrayData(array(
		'source' => "kontrakter LEFT JOIN oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold",
		'fields' => "MAX(kontrakter.kontraktnr) AS kontraktnr, kontrakter.andel, kontrakter.leieforhold, MIN(kontrakter.fradato) DATE_SUB(fristillelsesdato, INTERVAL 1 DAY) AS tildato",
		'where' => "kontrakter.leieobjekt = '{leieobjektnr}' AND fradato <= '{$tildato}' AND (fristillelsesdato IS NULL OR fristillelsesdato > '{$dato}')",
		'groupfields' => "kontrakter.leieforhold, kontrakter.andel",
		'orderfields' => "kontrakter.leieforhold"
	))->data;
	
	$resultat->sum = 0;
	if($dato == $tildato) {
		foreach($resultat->leieforhold as $leieforhold) {
			$resultat->sum += $this->evaluerAndel($leieforhold->andel);
		}
	}
	else {
		$sum = array();
		foreach($resultat->leieforhold as $leieforhold) {
			$sum[] = $this->utleie_ny($leieobjektnr, max($dato, $leieforhold->fradato))->sum;
		}
		$resultat->sum = max($sum);
	}
	$resultat->sum = (string)round($resultat->sum, 12);
	$resultat->ledig = max(1 - $resultat->sum, 0);

	$resultat->oppsigelsestid = $this->mysqli->arrayData(array(
		'source' => "kontrakter INNER JOIN oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold",
		'fields' => "MAX(kontrakter.kontraktnr) AS kontraktnr, kontrakter.andel, kontrakter.leieforhold, MIN(kontrakter.fradato) DATE_SUB(fristillelsesdato, INTERVAL 1 DAY) AS tildato, DATE_SUB(oppsigelsestid_slutt, INTERVAL 1 DAY) AS oppsigelsestid_tom",
		'where' => "kontrakter.leieobjekt = '{leieobjektnr}' AND fristillelsesdato <= '{$tildato}' AND oppsigelsestid_slutt > '{$dato}'",
		'groupfields' => "kontrakter.leieforhold, kontrakter.andel"
	))->data;

	$resultat->leiekrav = $this->mysqli->arrayData(array(
		'source' => "kontrakter INNER JOIN krav ON kontrakter.kontraktnr = krav.kontraktnr",
		'fields' => "krav.*, kontrakter.leieforhold",
		'where' => "krav.type = 'Husleie' AND krav.leieobjekt = '{leieobjektnr}' AND krav.fom <= '{$tildato}' AND krav.tom > '{$dato}'"
	))->data;
	
	$resultat->leiedekning	 = 0;
	if($dato == $tildato) {
		foreach($resultat->leiekrav as $krav) {
			$resultat->leiedekning += $this->evaluerAndel($krav->andel);
		}
	}
	else {
		$sum = array();
		foreach($resultat->leiekrav as $krav) {
			$sum[] = $this->utleie_ny($leieobjektnr, max($dato, $leieforhold->fom))->leiedekning;
		}
		$resultat->leiedekning = max($sum);
	}
	$resultat->leiedekning = (string)round($resultat->leiedekning, 12);

	return $resultat;
}



//	Returnerer info om hvor stor del av leieobjektet som er utleid, som et objekt med følgende nøkler:
// ['sum']: Hvor stor del av leieobjektet som er utleid, oppgitt som desimaltall med 4 desimaler
// ['ledig']: Hvor stor del av leieobjektet som er ledig, oppgitt som desimaltall med 4 desimaler
// ['andel']: (array) Alle leieforholdene oppgitt som $kontraktnr => andel (desimaltall)
public function utleiegrad($leieobjektnr, $dato = '', $kravellerkontrakter = 'kontrakter', $ignorer_tildato = true) {
	if(!$dato) $dato = time();
	
	if($kravellerkontrakter == 'kontrakter') {
		$sql =	"SELECT leieforhold\n"
			.	"FROM kontrakter\n"
			.	"WHERE leieobjekt = $leieobjektnr\n"
			.	"AND fradato <= '" . date('Y-m-d', $dato) . "'\n"
			.	(!$ignorer_tildato ? ("AND tildato >= '" . date('Y-m-d', $dato) . "'\n") : "\n")
			.	"GROUP BY leieforhold";
		$a = $this->arrayData($sql);
		
		$resultat['andel'] = array();
		$resultat['ledig'] = 1;
		foreach($a['data'] as $leieforhold) {
			if(!$this->oppsagt($leieforhold['leieforhold']) or $this->oppsagt($leieforhold['leieforhold']) > $dato) {
				$sql =	"SELECT kontraktnr, andel\n"
					.	"FROM kontrakter\n"
					.	"WHERE leieforhold = " . $leieforhold['leieforhold'] . "\n"
					.	"AND fradato <= '" . date('Y-m-d', $dato) . "'\n"
					.	(!$ignorer_tildato ? ("AND tildato >= '" . date('Y-m-d', $dato) . "'\n") : "\n")
					.	"ORDER BY fradato DESC\n"
					.	"LIMIT 0,1";
				$b = $this->arrayData($sql);
				$resultat['andel'][$b['data'][0]['kontraktnr']] = $this->evaluerAndel($b['data'][0]['andel']);
				$resultat['sum'] += $this->evaluerAndel($b['data'][0]['andel']);
				$resultat['ledig'] -= $this->evaluerAndel($b['data'][0]['andel']);
				
			}
		}
	}
	else { // dvs $kravellerkontrakter == 'krav'
		$sql =	"SELECT kontraktnr, andel\n"
			.	"FROM krav\n"
			.	"WHERE type = 'Husleie'\n"
			.	"AND leieobjekt = $leieobjektnr\n"
			.	"AND fom <= '" . date('Y-m-d', $dato) . "'\n"
			.	"AND tom >= '" . date('Y-m-d', $dato) . "'";
		$a = $this->arrayData($sql);
		$resultat['andel'] = array();
		$resultat['ledig'] = 1;
	
		foreach($a['data'] as $kontrakt) {
			$resultat['andel'][$kontrakt['kontraktnr']] = $this->evaluerAndel($kontrakt['andel']);
			$resultat['sum'] += $this->evaluerAndel($kontrakt['andel']);
			
			if(!$this->oppsagt($kontrakt['kontraktnr']) or $this->oppsagt($kontrakt['kontraktnr']) > $dato) {
				$resultat['ledig'] -= $this->evaluerAndel($kontrakt['andel']);
			}
		}
	}

	$resultat['sum'] = round($resultat['sum'], 4);
	$resultat['ledig'] = round($resultat['ledig'], 4);
	return $resultat;
}


// ERSTATTET MED Leieforhold::gjengiAvtaletekst( $utfylt = true )
// returnerer utfylt leieavtale.
public function utfyltKontrakt($kontraktnr){
	$sql =	"SELECT *\n"
		.	"FROM kontrakter LEFT JOIN leieobjekter ON kontrakter.leieobjekt = leieobjekter.leieobjektnr\n"
		.	"WHERE kontrakter.kontraktnr = $kontraktnr";
	$kontrakt = $this->arrayData($sql);

	$startdato = $this->arrayData("SELECT min(fradato) AS startdato FROM kontrakter WHERE leieforhold = " . $kontrakt['data'][0]['leieforhold']);
	$kontrakt['data'][0]['startdato'] = $startdato['data'][0]['startdato'];

	$innehaver = $this->arrayData("SELECT kontraktpersoner.*, fornavn, etternavn, fødselsdato, er_org, personnr FROM `kontraktpersoner` left join personer on kontraktpersoner.person = personer.personid WHERE kontrakt = $kontraktnr");
	$kontrakt['data'][0]['innehaver'] = $innehaver['data'];
	
	$utleierfelt = $this->valg['utleier'] . '<br />' . $this->valg['adresse'] . '<br />' . $this->valg['postnr'] . ' ' . $this->valg['poststed'] . '<br />org. nr. ' . $this->valg['orgnr'];
	
	$andel = "";
	$bofellesskap = "";
	if( round( eval( 'return '
		.	str_replace(
				array(',', '%'),
				array('.', '/100'),
				$kontrakt['data'][0]['andel']
			)
		. ';'
	), 1 ) <> 1) {
		$andel = $kontrakt['data'][0]['andel'] . ' av ';
		$bofellesskap = "i bofellesskap";
	}

	$bad = $kontrakt['data'][0]['bad'] ? 'Leieobjektet har tilgang til dusj/bad. ' : 'Leieobjektet har ikke tilgang til dusj/bad. ';

	switch($kontrakt['data'][0]['toalett_kategori']){
		case '2': $toalett = 'Leieobjektet har eget toalett.';
			break;
		case '1': $toalett = 'Det er tilgang til felles toalett i samme bygning/oppgang.';
			break;
		default:
			$toalett = 'Leieobjektet har ikke tilgang til eget toalett, eller har utedo.';
			$toalett .= $kontrakt['data'][0]['toalett'] ? (' (' . $kontrakt['data'][0]['toalett'] . ')') : "";
	}

	$innehavere = "";
	foreach ($kontrakt['data'][0]['innehaver'] as $linje => $opplysninger) {
		$navn = $kontrakt['data'][0]['innehaver'][$linje]['slettet'] ? '<span style="text-decoration: line-through;">' : '';
		if(!$kontrakt['data'][0]['innehaver'][$linje]['etternavn']) // Adressekortet ser ut til å ha blitt slettet
			$navn .= $kontrakt['data'][0]['innehaver'][$linje]['leietaker']; // Så vi henter leietakernavnet som er ført på koplinga til adressekortet
		else {	
			$navn .= $kontrakt['data'][0]['innehaver'][$linje]['er_org'] ? $kontrakt['data'][0]['innehaver'][$linje]['etternavn'] : $kontrakt['data'][0]['innehaver'][$linje]['fornavn'] . ' ' . $kontrakt['data'][0]['innehaver'][$linje]['etternavn'];
			if($kontrakt['data'][0]['innehaver'][$linje]['fødselsdato'] and !$kontrakt['data'][0]['innehaver'][$linje]['er_org'])
				$navn .= ' f. ' . date('d.m.Y', strtotime($kontrakt['data'][0]['innehaver'][$linje]['fødselsdato']));
			if($kontrakt['data'][0]['innehaver'][$linje]['personnr'])
				$navn .= $kontrakt['data'][0]['innehaver'][$linje]['er_org'] ? ' org. nr.' : ' ' .$kontrakt['data'][0]['innehaver'][$linje]['personnr'];
		}
		$navn .= $kontrakt['data'][0]['innehaver'][$linje]['slettet'] ? '</span> Slettet ' . date('d.m.Y', strtotime($kontrakt['data'][0]['innehaver'][$linje]['slettet'])) : '';
		$navn .='<br />';
		$innehavere .= $navn;
	}
	
	$innehavere .= $this->kontraktadresse($kontrakt['data'][0]['kontraktnr']);

	switch($kontrakt['data'][0]['ant_terminer']){
		case '2':
			$terminlengde = '6 måneder';
			break;
		case '3':
			$terminlengde = '4 måneder';
			break;
		case '4':
			$terminlengde = '3 måneder';
			break;
		case '12':
			$terminlengde = 'én måned';
			break;
		case '13':
			$terminlengde = '4 uker';
			break;
		case '26':
			$terminlengde = '2 uker';
			break;
		case '52':
			$terminlengde = 'én uke';
		default:
			$terminlengde = '(' . $kontrakt['data'][0]['ant_terminer'] . ' terminer per år)';
			break;
	}


	$variabler = array(
		"{kontraktnr}",
		"{utleier}",
		"{utleierfelt}",
		"{leietaker}",
		"{leietakerfelt}",
		"{andel}",
		"{bofellesskap}",
		"{leieobjekt}",
		"{leieobjektadresse}",
		"{antallrom}",
		"{areal}",
		"{bad}",
		"{toalett}",
		"{terminleie}",
		"{årsleie}",
		"{terminlengde}",
		"{solidaritetsfondet}",
		"{er_fornyelse}",
		"{startdato}",
		"{fradato}",
		"{tildato}",
		"{oppsigelsestid}"
	);
	$erstatningstekst = array(
		$kontrakt['data'][0]['kontraktnr'],
		$this->valg['utleier'],
		$utleierfelt,
		$this->liste($this->kontraktpersoner($kontrakt['data'][0]['kontraktnr'])),
		$innehavere,
		$andel,
		$bofellesskap,
		$kontrakt['data'][0]['leieobjektnr'],
		$this->leieobjekt($kontrakt['data'][0]['leieobjektnr']),
		$kontrakt['data'][0]['ant_rom'],
		$kontrakt['data'][0]['areal'] . 'm&#178;',
		$bad,
		$toalett,
		str_replace(' ', '&nbsp;', number_format($kontrakt['data'][0]['leiebeløp'] , 2, ',', ' ')),
		str_replace(' ', '&nbsp;', number_format($kontrakt['data'][0]['leiebeløp'] * $kontrakt['data'][0]['ant_terminer'] , 2, ',', ' ')),
		$terminlengde,
		str_replace(' ', '&nbsp;', number_format($kontrakt['data'][0]['solfondbeløp'] * $kontrakt['data'][0]['ant_terminer'], 2, ',', ' ')),
		$kontrakt['data'][0]['er_fornyelse'] ? ('Leieavtalen er fornyelse av tidligere leieavtaler. Leieforholdet ble påbegynt '. date('d.m.Y', strtotime($kontrakt['data'][0]['startdato'])) . '<br />') : '',
		date('d.m.Y', strtotime($kontrakt['data'][0]['startdato'])),
		date('d.m.Y', strtotime($kontrakt['data'][0]['fradato'])),
		(strtotime($kontrakt['data'][0]['tildato']) ? date('d.m.Y', strtotime($kontrakt['data'][0]['tildato'])) : ""),
		$this->oppsigelsestidrenderer($kontrakt['data'][0]['oppsigelsestid'])
	);
	return str_replace($variabler, $erstatningstekst, $kontrakt['data'][0]['tekst']);
}



/*	Utskrift
Utskriftsbehandlingen må defineres i hvert enkelt oppslag.
******************************************
------------------------------------------
*/
public function utskrift() {
	throw new Exception("Docu::utskrift er ikke definert.");
}


// Denne funksjonen henter alle leieforholdene i den pågående utskriften
// som ikke har intern levering men må sendes i posten
//	--------------------------------------
//	resultat: suksessangivelse
public function utskriftsadresser () {
	$tp = $this->mysqli->table_prefix;

	if ( !$this->valg['utskriftsforsøk'] ) {
		return false;
	}
	
	$utskriftsforsøk = unserialize($this->valg['utskriftsforsøk']);
	
	settype( $utskriftsforsøk->giroer, 'array' );
	settype( $utskriftsforsøk->purringer, 'array' );
	settype( $utskriftsforsøk->statusoversikter, 'array' );
	
	$resultat = $this->mysqli->arrayData(array(
		'distinct'	=> true,
		'class'		=>	'Leieforhold',
		'source'	=> "{$tp}kontrakter AS kontrakter
						LEFT JOIN {$tp}krav AS krav ON kontrakter.kontraktnr = krav.kontraktnr
						LEFT JOIN {$tp}purringer AS purringer ON purringer.krav = krav.id",
		
		'fields'	=>	"kontrakter.leieforhold AS id",
		
		'where'		=> "
			(krav.gironr = '" . implode("' OR krav.gironr = '", $utskriftsforsøk->giroer) . "'		
			OR purringer.blankett = '" . implode("' OR purringer.blankett = '", $utskriftsforsøk->purringer) . "'
			OR kontrakter.leieforhold = '" . implode("' OR kontrakter.leieforhold = '", $utskriftsforsøk->statusoversikter) . "')
			AND !kontrakter.regning_til_objekt
		"
	));
	
	if( $resultat->success ) {
		return $resultat->data;
	}
	else return false;
}



public function varsleForfall() {
	$tp = $this->mysqli->table_prefix;

	$this->hentValg();
	$sisteVarsel = date_create_from_format('U', $this->valg['varselstempel_forfall']);	

	$frist = new DateTime();
	$frist->setTimezone(new DateTimeZone('UTC') );
	$frist->add( new DateInterval( $this->valg['forfallsvarsel_innen'] ) );

	// Lag et leieforholdsett som et array,
	//	med ett element per leieforhold
	//	og der forfallskravene, i form av et nytt array, er verdien:
	$leieforholdsett = array();
	foreach($this->mysqli->arrayData(array(
		'distinct'		=> true,
		'class'			=> 'Krav',
		'fields'		=> "krav.id",
		'orderfields'	=> "kontrakter.leieforhold, krav.forfall, krav.fom, krav.tom, krav.kravdato",
		'source'		=> "{$tp}krav as krav INNER JOIN {$tp}kontrakter as kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
		'where'			=> "forfall > '{$sisteVarsel->format('Y-m-d')}'\n"
						.	"AND forfall <= '{$frist->format('Y-m-d')}'\n"
						.	"AND utestående > 0"
	))->data as $krav) {
		$leieforhold	= $krav->hent('leieforhold');
		$giro			= $krav->hent('giro');
		
		settype( $leieforholdsett[ $leieforhold->hentId() ], 'array' );		
		$leieforholdsett[ $leieforhold->hentId() ][] = $krav;
	}
	
	foreach( $leieforholdsett as $leieforholdId => $kravsett ) {
		$krav = reset($kravsett);
		$leieforhold = $krav->hent('leieforhold');
		
		$html = $leieforhold->gjengi(
			'epost_forfallsvarsel_html',
			array(
				'krav'	=> $kravsett
		)
		);
		
		$tekst = $leieforhold->gjengi(
			'epost_forfallsvarsel_txt',
			array(
				'krav'	=> $kravsett
		)
		);

		$brukerepost = $leieforhold->hent('brukerepost', array('forfallsvarsel' => true));
		if ($brukerepost) {
		
			$this->sendMail(array(
				'to' 		=> implode(',', $brukerepost),
				'subject'	=> "Påminnelse om forfall",
				'html'		=> $html,
				'text'		=> $tekst,
				'testcopy'	=> false
			));
		}

	}

	$this->mysqli->saveToDb(array(
		'update'	=> true,
		'table'		=> "{$tp}valg",
		'where'		=> "innstilling = 'varselstempel_forfall'",
		'fields'	=> array('verdi'	=> $frist->format('U'))
	));

	$this->hentValg();
	return true;
}



public function varsleFornying(){
	$this->hentValg();

	$kontrakter = $this->mysqli->arrayData(array(
		'fields'	=> "MAX(kontrakter.kontraktnr) AS kontraktnr, MAX(kontrakter.tildato) AS utløpsdato, kontrakter.leieforhold",
		'source'	=> "kontrakter LEFT JOIN oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold",
		'where'		=> "oppsigelser.leieforhold IS NULL",
		'groupfields' => "kontrakter.leieforhold",
		'having'	=> "utløpsdato > '" . date('Y-m-d H:i:s', $this->valg['varselstempel_kontraktutløp']) . "'\n"
					.	"AND utløpsdato <= '" . date('Y-m-d H:i:s', $this->leggtilIntervall(time(), "P1M")) . "'"
	))->data;


	foreach($kontrakter as $kontrakt) {
		if($adressefelt = $this->epostmottaker($kontrakt->kontraktnr)){
			$emne =	"Påminnelse om at din leieavtale må fornyes";
			
		    $html = str_ireplace(
				array("<br />","<br>","<br/>"),
				"<br />\r\n",
				str_replace(
					array(
						'{leieobjekt}',
						'{tildato}'),
					array(
						$this->leieobjekt($this->kontraktobjekt($kontrakt->kontraktnr), true),
						date('d.m.Y', strtotime($kontrakt->utløpsdato))
					),
					$this->valg['utløpsvarseltekst']
				)
		    ); 

			$this->sendMail(array(
				'to' 		=> $adressefelt,
				'testcopy'	=> false,
				'subject'	=> "Påminnelse om at din leieavtale må fornyes",
				'html'		=> $html
			));
		}
	}
	$this->mysqli->query("UPDATE valg SET verdi = '" . $this->leggtilIntervall(time(), "P1M") . "' WHERE innstilling = 'varselstempel_kontraktutløp'");
	$this->hentValg();
	return true;
}



// Sender ut kvitteringer for nyregistrerte innbetalinger
/****************************************/
//	$tid (int):	Unix tidsstempel for første betaling som kan varsles.
//				Normalt 'nå', som betyr at ingen nye betalinger er registrert.
//	--------------------------------------
//	retur: (bool) Sant for utført varsling
public function varsleNyeInnbetalinger($tid = 0) {
	$tp = $this->mysqli->table_prefix;

	settype( $tid, 'int' );
	if(!$tid) {
		$tid = time();
	}

	if( $tid < $this->valg['varselstempel_innbetalinger'] ) {
		return false;
	}

	$this->oppdaterUbetalt();

	$innbetalinger = $this->mysqli->arrayData(array(
		'source'	=> "{$tp}innbetalinger AS innbetalinger",
		'distinct'	=> true,
		'class'		=> 'Innbetaling',
		'fields'	=> "{$tp}innbetalinger.innbetaling AS id",
		'where'	=> "innbetalinger.leieforhold IS NOT NULL\n"
				.	"AND konto != '0'\n"
				.	"AND UNIX_TIMESTAMP(innbetalinger.registrert) > '{$this->valg['varselstempel_innbetalinger']}'\n"
				.	"AND UNIX_TIMESTAMP(innbetalinger.registrert) < '{$tid}'\n"
	));

	foreach( $innbetalinger->data as $innbetaling ) {
		$innbetaling->sendKvitteringsepost();
	}
	
	$this->mysqli->saveToDb(array(
		'update'	=> true,
		'table'		=> "{$tp}valg",
		'where'		=> "innstilling = 'varselstempel_innbetalinger'",
		'fields'	=> array('verdi'	=> $tid)
	));

	$this->hentValg();
	return true;
}



public function varsleNyeKrav(){
	$this->hentValg();

	$kontrakter = $this->mysqli->arrayData(array(
		'source' => "krav LEFT JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
		'fields' => "kontrakter.kontraktnr, kontrakter.leieforhold, kontrakter.leieobjekt",
		'where' => "krav.type != 'Husleie' AND krav.opprettet > '" . date('Y-m-d H:i:s', $this->valg['varselstempel_krav']) . "'",
		'groupfields' => "kontrakter.kontraktnr",
	));

	foreach( $kontrakter->data as $kontrakt ) {
		if($adressefelt = $this->epostmottaker($kontrakt->kontraktnr)){
			$emne =	"Nytt krav fra {$this->valg['utleier']}";
			$html =	"<p>Det har kommet til et nytt betalingskrav i leieforhold {$kontrakt->leieforhold} (" . $this->liste($this->kontraktpersoner($kontrakt->kontraktnr)) . " i " . $this->leieobjekt($kontrakt->leieobjekt, true) . ") med {$this->valg['utleier']}:<br/><br/>\n";			
			$tekst =	"Det har kommet til et nytt betalingskrav i leieforhold {$kontrakt->leieforhold} (" . $this->liste($this->kontraktpersoner($kontrakt->kontraktnr)) . " i " . $this->leieobjekt($kontrakt->leieobjekt, true) . ") med {$this->valg['utleier']}\n";
			
			$sql = "SELECT type\n"
				.	"FROM krav\n"
				.	"WHERE krav.type != 'Husleie'\n"
				.	"AND opprettet > '" . date('Y-m-d H:i:s', $this->valg['varselstempel_krav']) . "'\n"
				.	"AND kontraktnr = '{$kontrakt->kontraktnr}'\n"
				.	"GROUP BY type";
			$kravtyper = $this->arrayData($sql);
			
			foreach($kravtyper['data'] as $kravtype){
				$html .=	($kravtype['type'] != 'Annet' ? "<b>{$kravtype['type']}:</b><br />\n" : "");
				$tekst .=	"\n{$kravtype['type']}\n";
				
				$sql =	"SELECT *\n"
					.	"FROM krav\n"
					.	"WHERE krav.type != 'Husleie'\n"
					.	"AND beløp > 0\n"
					.	"AND opprettet > '" . date('Y-m-d H:i:s', $this->valg['varselstempel_krav']) . "'\n"
					.	"AND kontraktnr = '{$kontrakt->kontraktnr}'\n"
					.	"AND type = '{$kravtype['type']}'\n"
					.	"ORDER BY id\n";
				
				$kravliste = $this->arrayData($sql);
				
				foreach($kravliste['data'] as $krav){
					$html .=	"{$krav['tekst']} kr.&nbsp;" . str_replace(" ", "&nbsp;", number_format($krav['beløp'], 2, ",", " ")) . "<br />";
					$tekst .=	"{$krav['tekst']} kr. " . number_format($krav['beløp'], 2, ",", " ") . "\n";
					if($krav['forfall'])
						$html .=	"<i>forfaller til betaling " . date('d.m.Y', strtotime($krav['forfall'])) . "</i><br />";
						$tekst .=	"forfaller til betaling " . date('d.m.Y', strtotime($krav['forfall'])) . "\n";
					$html .=	"<br />\n";
					$tekst .=	"\n";
				}
			}
			$html .=	"</p>\n";
			$tekst .=	"\n";

			$html .=	"<p>Betaling kan skje til konto <b>{$this->valg['bankkonto']}</b><br />\n";
			$tekst .=	"Betaling kan skje til konto {$this->valg['bankkonto']}\n";
			if($this->valg['ocr']){
				$html .=	"Fast KID <b>" . $this->genererKid($kontrakt->leieforhold) . "</b> kan brukes ved alle betalinger for dette leieforholdet</p>\n";
				$tekst .=	"Fast KID " . $this->genererKid($kontrakt->leieforhold) . " kan brukes ved alle betalinger for dette leieforholdet\n\n";
			}
			else{
				$html .=	"Husk å merke alle betalinger med leieforhold <b>{$kontrakt->leieforhold}</b></p>\n";
				$tekst .=	"Husk å merke alle betalinger med leieforhold {$kontrakt->leieforhold}\n\n";
			}

			$html .= $this->valg['eposttekst'];

		    $tekst .= strip_tags( str_ireplace(
				array("<br />","<br>","<br/>"),
				"\r\n",
				$this->valg['eposttekst']
		    ) ); 

			$this->sendMail(array(
				'to' => $adressefelt,
				'subject' => $emne,
				'html' => $html,
				'text' => $tekst,
				'testcopy' => false
			));
		}
	}
	$this->mysqli->query("UPDATE valg SET verdi = '" . time() . "' WHERE innstilling = 'varselstempel_krav'");
	$this->hentValg();
	return true;
}



/*	Ved
Knagger en handling til et stadium
******************************************
$stadium (streng):	stadiet det skal knagges en handling til
$handling (div):	Handlingene som skal legges til
------------------------------------------
*/
public function ved( $stadium, $handlinger ) {
	settype($handlinger, 'array');
	
	foreach( $handlinger as $handling ) {
		if( is_callable( $handling ) ) {
			$this->knagger[$stadium][] = $handling;
		}
		else {
			throw new Exception( "Kan ikke utføre " . print_r($handling, true) . "");
		}
	}
	
}



}