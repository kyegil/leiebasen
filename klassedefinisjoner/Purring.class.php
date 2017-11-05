<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
Denne fila ble sist oppdatert 2016-02-01
**********************************************/

class Purring extends DatabaseObjekt {

protected	$tabell = "purringer";	// Hvilken tabell i databasen som inneholder primærnøkkelen for dette objektet
protected	$idFelt = "blankett";	// Hvilket felt i tabellen som lagrer primærnøkkelen for dette objektet
protected	$data;				// DB-verdiene lagret som et objekt. Null betyr at verdiene ikke er lastet
protected	$krav;				// Array over alle kravene i denne purringen. Null betyr at kravene ikke er lastet.
protected	$utskriftsposisjon = array();	// Utskriftsposisjonen for hver enkelt rute, sortert som et array med rutenummeret som nøkkel
public		$id;				// Unik id-streng for denne purresiden


//	Constructor
/****************************************/
//	$param
//		id	(heltall) gironummeret	
//	--------------------------------------
public function __construct( $param = null ) {
	parent::__construct( $param );
}



// Last giroens kjernedata fra databasen
/****************************************/
//	$param
//		id	(heltall) gironummeret	
//	--------------------------------------
protected function last($id = 0) {
	$tp = $this->mysqli->table_prefix;
	
	settype($id, 'integer');
	if( !$id ) {
		$id = $this->id;
	}
	
	$resultat = $this->mysqli->arrayData(array(
		'returnQuery'	=> true,
		'distinct'		=> true,
		'limit'			=> 1,
		
		'fields' =>			"{$this->tabell}.{$this->idFelt} AS id,
							{$this->tabell}.purredato,
							{$this->tabell}.purremåte,
							{$this->tabell}.purrer,
							{$this->tabell}.purregebyr,
							{$this->tabell}.purreforfall,
							SUM(krav.utestående) AS purretotal,
							kontrakter.leieforhold,
							kontrakter.leieobjekt,
							MAX(kontrakter.regning_til_objekt) AS regning_til_objekt,
							MAX(kontrakter.regningsobjekt) AS regningsobjekt\n",
						
		'groupfields' =>	"{$this->tabell}.{$this->idFelt},
							{$this->tabell}.purredato,
							{$this->tabell}.purremåte,
							{$this->tabell}.purrer,
							{$this->tabell}.purregebyr,
							{$this->tabell}.purreforfall,
							kontrakter.leieforhold,
							kontrakter.leieobjekt\n",
						
		'source' => 		"{$tp}{$this->tabell} AS {$this->tabell}\n"
						.	"LEFT JOIN {$tp}krav AS krav ON {$this->tabell}.krav = krav.id\n"
						.	"LEFT JOIN {$tp}kontrakter AS kontrakter ON krav.kontraktnr = kontrakter.kontraktnr\n",

		'where'			=>	"{$tp}{$this->tabell}.{$this->idFelt} = '$id'
							AND type !='Purregebyr'"
	));
	if( isset( $resultat->data[0] ) ) {
		$this->data = $resultat->data[0];
		$this->id = $id;
		
		$this->data->leieforhold = new Leieforhold( $this->data->leieforhold );

		if ( $this->data->purregebyr ) {
			$this->data->purregebyr = new Krav( $this->data->purregebyr );
		}

		if( $this->data->purredato ) {
			$this->data->purredato = new DateTime( $this->data->purredato );
		}

		if( $this->data->purreforfall ) {
			$this->data->purreforfall = new DateTime( $this->data->purreforfall );
		}
	}
	else {
		$this->id = null;
		$this->data = null;
	}

}



// Last kravene som er purret fra databasen
/****************************************/
//	$param
//		id	(heltall) gironummeret	
//	--------------------------------------
protected function lastKrav() {
	$tp = $this->mysqli->table_prefix;
	if ( !$id = $this->id ) {
		$this->krav = null;
		return false;
	}

	$resultat = $this->mysqli->arrayData(array(
		'returnQuery'	=> true,
		'class' 		=>	"Krav",
		'fields'		=>	"krav.id\n",
		'orderfields'	=>	"krav.gironr ASC, krav.id ASC",
		'source'		=> 	"{$tp}{$this->tabell} AS {$this->tabell} INNER JOIN {$tp}krav AS krav ON {$this->tabell}.krav = krav.id\n",
		'where'			=>	"{$tp}{$this->tabell}.{$this->idFelt} = '$id'"
	));

	$this->krav = $resultat->data;

}



// Last utskriftsposisjonen for giroen
/****************************************/
//	$param
//		rute	(heltall) utdelingsruten som bestemmer utskriftsrekkefølgen
//	--------------------------------------
protected function lastUtskriftsposisjon($rute = null) {
	$tp = $this->mysqli->table_prefix;
	settype($rute, 'integer');

	if ( !$id = $this->id ) {
		$this->utskriftsposisjon = array();
		return false;
	}
	
	$this->utskriftsposisjon[$rute] = intval((string)$this->hent('leieforhold'));
	
	if( $this->hent('regning_til_objekt') ) {
		$posisjon = $this->mysqli->arrayData(array(
			'returnQuery'	=> true,
		
			'fields' =>			"utdelingsorden.plassering\n",
			'source' => 		"{$tp}utdelingsorden AS utdelingsorden\n",
			'where'			=>	"{$tp}utdelingsorden.leieobjekt = '{$this->hent('regningsobjekt')}' AND rute = '{$rute}'"
		));
		if( $posisjon->totalRows ) {
			$this->utskriftsposisjon[$rute] = 1000000 * $posisjon->data[0]->plassering
			+ (string)$this->hent('leieforhold');
		}
	}
}



// Gjengi purringen som utskrift e.l.
/****************************************/
//	$param
//		mal	(streng) gjengivelsesmalen
//		purregebyr (tall) evt purregebyr som kommer i tillegg til kravoversikten
//	--------------------------------------
public function gjengi($mal, $param = array()) {
	settype( $param, 'array');
	$leiebase = $this->leiebase;

	
	switch($mal) {
	
	case "pdf_purring":
	{
		if( !is_a(@$param['purredato'], 'DateTime') and !is_a($this->hent('purredato'), 'DateTime') ) {
			throw New Exception('Purredato mangler! ');
		}
	
		$avsenderadresse = "{$leiebase->valg['utleier']}\n{$leiebase->valg['adresse']}\n{$leiebase->valg['postnr']} {$leiebase->valg['poststed']}";
		$leieforhold = $this->hent('leieforhold');
		$sisteInnbetalinger = $this->mysqli->arrayData(array(
			'source' => "innbetalinger",
			'fields' => "dato, betaler, SUM(beløp) AS beløp, ref",
			'where' => "leieforhold = '{$this->hent('leieforhold')}'
						AND dato <= " . (
						isset( $param['purredato'] )
						? $param['purredato']->format('Y-m-d')
						: "'{$this->hent('purredato')->format('Y-m-d')}'"
						),
			'groupfields' => "dato, betaler, ref",
			'orderfields' => "dato DESC",
			'limit'	=>	"0, 3"
		))->data;


		$this->gjengivelsesdata = array(
			'avsenderAdresse'		=> $avsenderadresse,
			'mottakerAdresse'		=> ($leieforhold->hent('navn') . "\n" . $leieforhold->hent('adressefelt')),
			
			'kreditor'				=> $leiebase->valg['utleier'],
			'avsenderGateadresse'	=> $leiebase->valg['adresse'],
			'avsenderPostnr'		=> $leiebase->valg['postnr'],
			'avsenderPoststed'		=> $leiebase->valg['poststed'],
			'avsenderOrgNr'			=> $leiebase->valg['orgnr'],
			'avsenderTelefon'		=> $leiebase->valg['telefon'],
			'avsenderTelefax'		=> $leiebase->valg['telefax'],
			'avsenderMobil'			=> $leiebase->valg['mobil'],
			'avsenderEpost'			=> $leiebase->valg['epost'],
			'avsenderHjemmeside'	=> $leiebase->valg['hjemmeside'],
			'bankkonto'				=> $leiebase->valg['bankkonto'],
			'girotekst'				=> $leiebase->valg['girotekst'],
			'purregebyr'			=> isset( $param['purregebyr'] )
										? $param['purregebyr']
										: false,

			'efaktura'				=> (bool)$leiebase->valg['efaktura'],
			'avtalegiro'			=> (bool)$leiebase->valg['avtalegiro'],
			
			'leieforhold'			=> $this->hent('leieforhold'),
			'efakturareferanse'		=> $leieforhold->hent('efakturareferanse'),
			'purredato'				=> $this->hent('purredato'),
			
			'leieforholdBeskrivelse'=> $leiebase->leieobjekt( $this->hent('leieobjekt'), true ),

			'purreforfall'			=> $this->hent('purreforfall'),
			'kravsett'				=> $this->hent('krav'), // array
			'girobeløp'				=> $this->hent('beløp'),
			'purretotal'			=> $this->hent('purretotal'),
			'blankettbeløp'			=> $this->hent('purretotal'),
			'sisteInnbetalinger'	=> $sisteInnbetalinger
		);

		$this->gjengivelsesdata = array_merge($this->gjengivelsesdata, $param);

		// HER BEREGNES VERDIER BASERT pÅ ALLEREDE ETABLERTE DATA
		// Beregn fast KID:
		$this->gjengivelsesdata['fastKid']			= $leiebase->genererKid($this->gjengivelsesdata['leieforhold']);

		// Beregn blankettbeløp:
		$this->gjengivelsesdata['blankettbeløp'] = $this->gjengivelsesdata['purretotal'] + $this->gjengivelsesdata['purregebyr'];

		// Beregn krone- og ørebeløp;
		if(strpos($beløp = $this->gjengivelsesdata['blankettbeløp'], '.') === false) {
			$this->gjengivelsesdata['blankettbeløpKroner']
			= $beløp;
			$this->gjengivelsesdata['blankettbeløpØre']
			= '00';
		}
		else {
			$this->gjengivelsesdata['blankettbeløpKroner']
			= strstr($beløp, ".", true);
			
			$this->gjengivelsesdata['blankettbeløpØre']
			= str_pad( round( substr( strstr( $beløp, ".", false ), 0, 4 ) * 100 ), 2, STR_PAD_LEFT );
		}

		// Beregn kontrollsiffer
		$this->gjengivelsesdata['kontrollsiffer']
		= $leiebase->kontrollsiffer($this->gjengivelsesdata['blankettbeløpKroner']
		. $this->gjengivelsesdata['blankettbeløpØre']);

		if( !is_a($param['pdf'], 'FPDF')) {
			$this->gjengivelsesdata['pdf'] = new FPDF;
		}

		break;
	}


	case "epost_purring_html":
	{
		$leieforhold = $this->hent('leieforhold');
		$leieforholdbeskrivelse = "Leieobjekt {$leieforhold->hent('leieobjekt')}";

		
		$sisteInnbetaling = $this->mysqli->arrayData(array(
			'source' => "innbetalinger",
			'fields' => "dato, betaler, SUM(beløp) AS beløp, ref",
			'where' => "leieforhold = '{$this->hent('leieforhold')}'
						AND dato <= " . (
						isset( $param['purredato'] )
						? $param['purredato']->format('Y-m-d')
						: "'{$this->hent('purredato')->format('Y-m-d')}'"
						),
			'groupfields' => "dato, betaler, ref",
			'orderfields' => "dato DESC",
			'limit'	=>	"1"
		));
		if( $sisteInnbetaling->totalRows > 0 ) {
			$sisteInnbetaling = $sisteInnbetaling->data[0];
			$sisteInnbetaling->dato = date_create_from_format('Y-m-d', $sisteInnbetaling->dato);
			$sisteInnbetaling->beløp = $this->leiebase->kr( $sisteInnbetaling->beløp );
		}
		else {
			$sisteInnbetaling = (object)array(
				'beløp'		=> null,
				'betaler'	=> null,
				'dato'		=> null
			);
		}
		
		$kravsett = array();
		$purrekrav = $this->hent('krav');
 		foreach ($purrekrav as $krav) {
			$kravsett[] = (object)array(
				'id'		=> $krav->hentId(),
				'tekst'		=> $krav->hent('tekst'),
				'beløp'		=> $this->leiebase->kr($krav->hent('beløp')),
				'forfall'	=> $krav->hent('forfall')->format('d.m.Y'),
				'utestående'	=> $this->leiebase->kr($krav->hent('utestående'))
			);
 		}
		
		$fastKid = $this->leiebase->genererKid( (string)$leieforhold );

		$this->gjengivelsesdata = array(
			'leiebase'				=> $this->leiebase,
			'kontraktperson'		=> $leieforhold->hent('navn'),
			'leieforholdnr'			=> (string)$leieforhold,	
			'leieforholdbeskrivelse'=> $this->leiebase->leieobjekt( $this->hent('leieobjekt'), true ),
			'kravsett'				=> $kravsett, // array
			'purregebyr'			=> ($this->leiebase->valg['purregebyr'] ? $this->leiebase->kr($this->leiebase->valg['purregebyr']) : ""),
			'purretotal'			=> $this->leiebase->kr($this->hent('purretotal')),
			'bankkonto'				=> $this->leiebase->valg['bankkonto'],
			'ocr'					=> (bool)$this->leiebase->valg['ocr'],
			'kid'					=> "",
			'fastKid'				=> $fastKid,
			'eposttekst'			=> $this->leiebase->valg['eposttekst'],
			'avsenderTelefax'		=> $this->leiebase->valg['telefax'],
			'sisteInnbetaling'		=> $sisteInnbetaling
		);

		$this->gjengivelsesdata = array_merge($this->gjengivelsesdata, $param);
		break;
	}


	case "epost_purring_txt":
	{
		$leieforhold = $this->hent('leieforhold');
		$leieforholdbeskrivelse = "Leieobjekt {$leieforhold->hent('leieobjekt')}";

		
		$sisteInnbetaling = $this->mysqli->arrayData(array(
			'source' => "innbetalinger",
			'fields' => "dato, betaler, SUM(beløp) AS beløp, ref",
			'where' => "leieforhold = '{$this->hent('leieforhold')}'
						AND dato <= " . (
						isset( $param['purredato'] )
						? $param['purredato']->format('Y-m-d')
						: "'{$this->hent('purredato')->format('Y-m-d')}'"
						),
			'groupfields' => "dato, betaler, ref",
			'orderfields' => "dato DESC",
			'limit'	=>	"1"
		));
		if( $sisteInnbetaling->totalRows > 0 ) {
			$sisteInnbetaling = $sisteInnbetaling->data[0];
			$sisteInnbetaling->dato = date_create_from_format('Y-m-d', $sisteInnbetaling->dato);
			$sisteInnbetaling->beløp = $this->leiebase->kr( $sisteInnbetaling->beløp, false );
		}
		else {
			$sisteInnbetaling = (object)array(
				'beløp'		=> null,
				'betaler'	=> null,
				'dato'		=> null
			);
		}
		
		$kravsett = array();
		$purrekrav = $this->hent('krav');
 		foreach ($purrekrav as $krav) {
			$kravsett[] = (object)array(
				'id'		=> $krav->hentId(),
				'tekst'		=> $krav->hent('tekst'),
				'beløp'		=> $this->leiebase->kr($krav->hent('beløp'), false),
				'forfall'	=> $krav->hent('forfall')->format('d.m.Y'),
				'utestående'	=> $this->leiebase->kr($krav->hent('utestående'), false)
			);
 		}
		
		$fastKid = $this->leiebase->genererKid( (string)$leieforhold );

		$this->gjengivelsesdata = array(
			'leiebase'				=> $this->leiebase,
			'kontraktperson'		=> $leieforhold->hent('navn'),
			'leieforholdnr'			=> (string)$leieforhold,	
			'leieforholdbeskrivelse'=> $this->leiebase->leieobjekt( $this->hent('leieobjekt'), true ),
			'kravsett'				=> $kravsett, // array
			'purregebyr'			=> ($this->leiebase->valg['purregebyr'] ? $this->leiebase->kr($this->leiebase->valg['purregebyr'], false) : ""),
			'purretotal'			=> $this->leiebase->kr($this->hent('purretotal'), false),
			'bankkonto'				=> $this->leiebase->valg['bankkonto'],
			'ocr'					=> (bool)$this->leiebase->valg['ocr'],
			'kid'					=> "",
			'fastKid'				=> $fastKid,
			'eposttekst'			=> $this->leiebase->valg['eposttekst'],
			'avsenderTelefax'		=> $this->leiebase->valg['telefax'],
			'sisteInnbetaling'		=> $sisteInnbetaling
		);

		$this->gjengivelsesdata = array_merge($this->gjengivelsesdata, $param);
		break;
	}

	}
	
	$this->gjengivelsesfil = "{$mal}.php";
	return $this->_gjengi( (array)$param );
}



// Hent egenskaper
/****************************************/
//	$param
//		id	(heltall) gironummeret	
//	--------------------------------------
public function hent($egenskap, $param = array()) {
	$tp = $this->mysqli->table_prefix;
	
	if( !$this->id ) {
		return null;
	}
	
	switch( $egenskap ) {
		case "leieforhold":
		case "leieobjekt":
		case "purredato":
		case "purreforfall":
		case "purregebyr":
		case "purremåte":
		case "purrer":
		case "purretotal":
		case "regning_til_objekt":
		case "regningsobjekt":
			if ( $this->data == null ) {
				$this->last();
			}		
			return $this->data->$egenskap;
			break;
		case "utskriftsposisjon":
			if ( $this->data == null ) {
				$this->last();
			}
			
			if ( !isset( $this->utskriftsposisjon[$param['rute']] ) ) {
				$this->lastUtskriftsposisjon($param['rute']);
			}
			return $this->utskriftsposisjon[$param['rute']];
			break;
		case "krav":
			if ( $this->krav === null ) {
				$this->lastKrav();
			}		
			return $this->krav;
			break;
		default:
			return null;
			break;
	}
}



// Oppretter en ny purring i databasen og tildeler egenskapene til dette objektet
/****************************************/
//	$egenskaper (array/objekt) Alle egenskapene det nye objektet skal initieres med
//	--------------------------------------
public function opprett($egenskaper = array()) {
	throw new Exception('Ny Purring forsøkt opprettet, men metoden er ennå ikke ferdigutviklet og klar for bruk');
	return false;

}



// Opprett purregebyr for denne purringen
/****************************************/
//	$param
// 		anleggsnr
// 		beløp
// 		forfall
// 		gironr
// 		kontraktnr
// 		kravdato
// 		leieobjekt
// 		oppretter
// 		opprettet
// 		tekst
// 		utestående
// 		utskriftsdato
//	--------------------------------------
public function opprettGebyr($param = array()) {
	$tp = $this->mysqli->table_prefix;
	settype($param, 'object');
	if( !isset( $param->beløp ) )	$param->beløp = $this->leiebase->valg['purregebyr'];
	if( !isset( $param->tekst ) )	$param->tekst = "Purregebyr for betalingspåminnelse den {$this->hent('purredato')->format('d.m.Y')}";
	
	if( !$this->id ) {
		return false;
	}
	
	if( $this->hent('purregebyr') ) {
		return false;
	}
	
	$kravsett = $this->hent('krav');
	$gironr = $kravsett[count($kravsett)-1]->hent('gironr');
	$kontraktnr = $kravsett[count($kravsett)-1]->hent('kontraktnr');
	
	$resultat = $this->mysqli->saveToDb(array(
		'insert'		=>	true,
		'returnQuery'	=> true,
		'table'			=> "{$tp}krav",
		'fields'	=> array(
			'beløp'			=> $param->beløp,
			'forfall'		=> $this->hent('purreforfall')->format('Y-m-d'),
			'gironr'		=> $gironr,
			'kontraktnr'	=> $this->hent('leieforhold'),
			'kravdato'		=> $this->hent('purredato')->format('Y-m-d'),
			'oppretter'		=> $this->hent('purrer'),
			'opprettet'		=> $this->hent('purredato')->format('Y-m-d'),
			'tekst'			=> $param->tekst,
			'type'			=> 'Purregebyr',
			'utestående'	=> $param->beløp,
			'utskriftsdato'	=> $this->hent('purredato')->format('Y-m-d H:i:s'),
		)		
	));

	$gebyr = new Krav( $resultat->id );
	$this->sett('purregebyr', $gebyr);

	return $gebyr;
	
}



// Skriv en verdi
/****************************************/
//	$egenskap		Leiebase-objekt
//	$verdi
//	--------------------------------------
public function sett($egenskap, $verdi = null) {
	$tp = $this->mysqli->table_prefix;
	
	if( !$this->id ) {
		return null;
	}
	
	switch( $egenskap ) {
		case "purregebyr":

			$resultat = $this->mysqli->saveToDb(array(
				'update'	=> true,
				'table'		=> "{$tp}{$this->tabell} as {$this->tabell}",
				'where'		=> "{$this->tabell}.{$this->idFelt} = '{$this->id}'",
				'fields'	=> array(
					$egenskap	=> $verdi
				)
			));
			if ( $resultat->success ) {
				$this->data->$egenskap = clone $verdi;
			}
			else {
				$this->data = null;
			}
			return $resultat->success;
			break;
		default:
			return false;
			break;
	}

}



}?>