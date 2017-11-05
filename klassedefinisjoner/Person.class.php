<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

class Person extends DatabaseObjekt {

protected	$tabell = "personer";	// Hvilken tabell i databasen som inneholder primærnøkkelen for dette objektet
protected	$idFelt = "personid";	// Hvilket felt i tabellen som lagrer primærnøkkelen for dette objektet
protected	$data;			// DB-verdiene lagret som et objekt. Null om de ikke er lastet
//	protected	$navn;			// Navn på leietakeren(e) som inngår i leieavtalen
//	protected	$kortnavn;		// Forkortet navn på leietakeren(e)
//	protected	$adresse;		// stdClass-objekt med adresseelementene
//	protected	$adressefelt;	// Adressefelt for utskrift
//	protected	$brukerepost;	// Liste over brukerepostadresser
//	protected	$oppsigelse;	// stdClass-objekt med oppsigelse. False dersom ikke oppsagt, null dersom ikke lastet
//	protected	$utskriftsposisjon = array();	// Utskriftsposisjonen for personen
protected	$leieforhold;		// Array med datoer, hvor hver dato inneholder alle leieforholdene personen har inngått i. Null dersom leieforholdene ikke er lastet
public		$id;				// Unikt id-heltall for dette objektet


//	Constructor
/****************************************/
//	$param
//		id	(heltall) personid'en	
//	--------------------------------------
public function __construct( $param = null ) {
	parent::__construct( $param );
}


// Last personens kjernedata fra databasen
/****************************************/
//	$param
//		id	(heltall) id-nummeret	
//	--------------------------------------
protected function last($id = 0) {
	$tp = $this->mysqli->table_prefix;
	
	settype($id, 'integer');
	if( !$id ) {
		$id = $this->id;
	}
	
	$resultat = $this->mysqli->arrayData(array(
		'returnQuery'	=> true,
		
		'fields' =>		"{$this->tabell}.personid AS id,\n"
					.	"{$this->tabell}.personid,\n"
					.	"{$this->tabell}.fornavn,\n"
					.	"{$this->tabell}.etternavn,\n"
					.	"{$this->tabell}.er_org,\n"
					.	"{$this->tabell}.fødselsdato,\n"
					.	"{$this->tabell}.personnr,\n"
					.	"{$this->tabell}.adresse1,\n"
					.	"{$this->tabell}.adresse2,\n"
					.	"{$this->tabell}.postnr,\n"
					.	"{$this->tabell}.poststed,\n"
					.	"{$this->tabell}.land,\n"
					.	"{$this->tabell}.telefon,\n"
					.	"{$this->tabell}.mobil,\n"
					.	"{$this->tabell}.epost\n",
						
		'source' => 		"{$tp}{$this->tabell} AS {$this->tabell}\n",
						
		'where'			=>	"{$tp}{$this->tabell}.{$this->idFelt} = '$id'"
	));
	if( $resultat->totalRows ) {
		$this->data = $resultat->data[0];
		$this->id = $id;
		
		$this->data->postadresse
			= ( $this->data->adresse1 ? "{$this->data->adresse1}\n" : "")
			. ( $this->data->adresse2 ? "{$this->data->adresse2}\n" : "")
			. "{$this->data->postnr} {$this->data->poststed}"
			. ( ($this->data->land and $this->data->land != "Norge") ? "\n{$this->data->land}" : "");

		$this->data->fødselsnummer = null;
		$this->data->orgNr = null;
		
		if( $this->data->fødselsdato ) {
			$this->data->fødselsdato = new DateTime( $this->data->fødselsdato );
		}

		$this->data->org = (bool)$this->data->er_org;
		
		if( $this->data->org ) {
			$this->data->navn = $this->data->etternavn;
			$this->data->orgNr = $this->data->personnr;
		}
		else {
			if( $this->data->fornavn ) {
				$this->data->navn = $this->data->fornavn . " " . $this->data->etternavn;
			}
			else {
				$this->data->navn = $this->data->etternavn;
			}

			if( $this->data->fødselsdato && $this->data->personnr ) {
				$this->data->fødselsnummer = $this->data->fødselsdato->format('dmy') . $this->data->personnr;
			}	
		}

		return true;
	}
	else {
		$this->id = null;
		$this->data = null;
		return false;
	}

}


// Last personens leieforhold fra databasen.
/****************************************/
//	$param
//		id	(heltall) id-nummeret	
//	--------------------------------------
protected function lastLeieforhold() {
	$tp = $this->mysqli->table_prefix;

	$id = (int)$this->id;
	$this->leieforhold = array();
	
	foreach( $this->mysqli->arrayData(array(
		'returnQuery'	=> true,
		'distinct'		=> true,
		
		'fields'		=> "MAX(kontraktpersoner.slettet) AS slettet, MIN(kontrakter.fradato) AS fradato, kontrakter.leieforhold AS leieforhold, oppsigelser.fristillelsesdato AS fristillelsesdato",
						
		'groupfields'		=> "kontrakter.leieforhold",
						
		'source'		=> "{$tp}kontrakter AS kontrakter\n"
						.	"INNER JOIN {$tp}kontraktpersoner AS kontraktpersoner ON kontraktpersoner.kontrakt = kontrakter.kontraktnr\n"
						.	"LEFT JOIN {$tp}oppsigelser AS oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold",
						
		'where'			=>	"kontraktpersoner.person = '$id'"
	))->data as $leieforhold ) {
		$leieforhold->leieforhold = $this->leiebase->hent('Leieforhold', $leieforhold->leieforhold);
		$leieforhold->fradato = new DateTime( $leieforhold->fradato );
		if( $leieforhold->slettet ) {
			$leieforhold->tildato = new DateTime( $leieforhold->slettet );
		}
		else if( $leieforhold->fristillelsesdato ) {
			$leieforhold->tildato = new DateTime( $leieforhold->fristillelsesdato );
		}
		else{
			$leieforhold->tildato = null;
		}
		
		unset($leieforhold->slettet, $leieforhold->fristillelsesdato);
		$this->leieforhold[] = $leieforhold;
	}
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

	case "id":
	case "adresse1":
	case "adresse2":
	case "epost":
	case "etternavn":
	case "fødselsdato":
	case "fødselsnummer":
	case "fornavn":
	case "land":
	case "mobil":
	case "navn":
	case "org":
	case "orgNr":
	case "personid":
	case "personnr":
	case "postadresse":
	case "postnr":
	case "poststed":
	case "telefon":
	{
		if ( $this->data === null and !$this->last() ) {
			return null;
		}		
		return $this->data->$egenskap;
		break;
	}

	default: {
		return null;
		break;
	}

	}

}


/*
Sjekker om denne personen eller organisasjonen er eller var leietaker hos utleier på en gitt dato.
*****************************************/
//	$dato (DateTime, normalt dagens dato):	Datoen som skal sjekkes
//	--------------------------------------
//	retur: liste over alle leieforhold med personen på angitt dato

public function hentLeieforhold( $dato = null ) {
	$resultat = array();

	if ( $dato !== null and !is_a($dato, 'DateTime') ) {
		throw new Exception('Forventet argument $dato er ikke DateTime-objekt: ' . print_r($dato, true));
	}
	if ( !is_array( $this->leieforhold ) ) {
		$this->lastLeieforhold();
	}		
	
	foreach( $this->leieforhold as $leieforhold ) {
		if(
			$dato === null
			or (
				$leieforhold->fradato <= $dato
				and (
					$leieforhold->tildato === null
					or $leieforhold->tildato >= $dato
				)
			)
		) {
			$resultat[] = $leieforhold->leieforhold;
		}
	}
	
	return $resultat;
}


// Oppretter et nytt adressekort i databasen og tildeler egenskapene til dette objektet
/****************************************/
//	$egenskaper (array/objekt) Alle egenskapene det nye objektet skal initieres med
//	--------------------------------------
public function opprett($egenskaper = array()) {
	$tp = $this->mysqli->table_prefix;
	settype( $egenskaper, 'array');
	
	$org	= (boolean)@$egenskaper['org'];
	
	if( $org ) {
		$egenskaper['etternavn'] = $egenskaper['navn'];
		unset($egenskaper['fornavn']);
	}

	if( $this->id ) {
		return false;
	}
	
	if( !$egenskaper['etternavn'] ) {
		throw new Exception('Nytt Person-objekt forsøkt opprettet, men mangler navn');
		return false;
	}
		
	
	$databasefelter = array();
	$resterendeFelter = array();
	
	foreach($egenskaper as $egenskap => $verdi) {
		if($egenskap == "org") {
			$egenskap = "er_org";
		}
		
		if ( $verdi instanceof DateTime ) {
			$verdi = $verdi->format('Y-m-d');
		}

		switch( $egenskap ) {

		case "navn": // Ignoreres
			break;

		case "er_org":
		case "fornavn":
		case "etternavn":
		case "fødselsdato":
		case "personnr":
		case "adresse1":
		case "adresse2":
		case "postnr":
		case "poststed":
		case "land":
		case "telefon":
		case "mobil":
		case "epost":
			$databasefelter[$egenskap] = $verdi;
			break;

		default:
			$resterendeFelter[$egenskap] = $verdi;
			break;
		}		
	}
		
	$this->id = $this->mysqli->saveToDb(array(
		'insert'	=> true,
		'id'		=> $this->idFelt,
		'table'		=> "{$tp}{$this->tabell}",
		'fields'	=> $databasefelter
	))->id;
	
	if( !$this->hentId() ) {
		throw new Exception('Nytt Person-objekt forsøkt opprettet, men kunne ikke lagres til databasen');
		return false;
	}

	foreach( $resterendeFelter as $egenskap => $verdi ) {
		$this->sett($egenskap, $verdi);
	}
	
	return $this;
}


// Skriv en verdi
/****************************************/
//	$egenskap		streng. Egenskapen som skal endres
//	$verdi			Ny verdi
//	--------------------------------------
//	retur: boolsk suksessangivelse
public function sett($egenskap, $verdi = null) {
	$tp = $this->mysqli->table_prefix;
	
	if( !$this->id ) {
		return false;
	}
	
	if($egenskap == 'navn') {
		$egenskap = 'etternavn';
	}
	if($egenskap == 'org') {
		$egenskap = 'er_org';
	}
	if($egenskap == 'orgNr') {
		$egenskap = 'personnr';
	}
	if($egenskap == 'personnr') {
		$verdi = preg_replace('/[^0-9]+/', '', $verdi);
	}
	
	if($egenskap == 'fødselsdato' and !$verdi) {
		$verdi = null;
	}
	
	// Dersom personnummer er oppgitt med 11 siffer
	if($egenskap == 'personnr' and strlen($verdi) == 11) {
		$fødselsnr = substr($verdi, 0, 6);
		$personnr = substr($verdi, 6, 5);
		
		if( $fødselsdato = $this->hent('fødselsdato')) {
			if($fødselsdato->format('dmy') != $fødselsnummer) {
				return false;
			}
		}
		else {
			$this->sett('fødselsdato', date_create_from_format('dmy', $fødselsnr));
		}
	}
	
	switch( $egenskap ) {
	
	case "adresse1":
	case "adresse2":
	case "epost":
	case "etternavn":
	case "fødselsdato":
	case "fornavn":
	case "land":
	case "mobil":
	case "navn":
	case "er_org":
	case "orgNr":
	case "personnr":
	case "postnr":
	case "poststed":
	case "telefon":
		if ( $verdi instanceof DateTime ) {
			$verdi = $verdi->format('Y-m-d');
		}		
		
		$resultat = $this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "{$tp}{$this->tabell} as {$this->tabell}",
			'where'		=> "{$this->tabell}.{$this->idFelt} = '{$this->id}'",
			'fields'	=> array(
				"{$this->tabell}.{$egenskap}"	=> $verdi
			)
		))->success;

		// Tving ny lasting av data:
		$this->data = null;

		return $resultat;
		break;

	default:
		mail("kyegil@gmail.com", "Call to Leieforhold method sett()", "{$egenskap} forsøkt satt til" . var_export($verdi, true));
		return false;
		break;

	}

}


}?>