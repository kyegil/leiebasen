<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

//	Basisdefinisjon av alle objekter som er lagret i databasen

abstract class DatabaseObjekt {

protected	$tabell;	// Hvilken tabell i databasen som inneholder primærnøkkelen for dette objektet
protected	$idFelt;	// Hvilket felt i tabellen som lagrer primærnøkkelen for dette objektet
protected	$leiebase;	// Leiebaseobjektet
protected	$mysqli;	// Databasetilkoplingen
protected	$data;		// DB-verdiene lagret som et objekt
protected	$gjengivelsesdata = array();	// assoc array med nøkler/verdier som brukes som variabler i visningsfilene
protected	$gjengivelsesfil;	// streng med navnet på gjengivelsesfila
public		$id;		// Unikt id-heltall for dette objektet
public		$stadier;	// Ulike fremdriftsstadier som handlinger kan knyttes til
public		$knagger = array();	// Ulike handlinger knyttet til framdriftsstadier
public		$tilleggsmetoder = array();	// Metoder lagt til av tillegg


abstract protected function last();


// Felles constructor for alle databaseobjekter
/****************************************/
//	$param
//		id	(heltall) objektidentifikator for databasen	
//	--------------------------------------
public function __construct( $param = null ) {

	global $mysqliConnection;
	if( !is_a($mysqliConnection, 'Mysqli') ) {
		throw new Exception('Ingen tilgang på mysqli-tilkopling. \$mysqliConnection = ' . var_export($mysqliConnection, true));
	}
	$this->mysqli = $mysqliConnection;
	
	global $leiebase;
	if( !is_a($leiebase, 'Leiebase') ) {
		throw new Exception('Ingen tilgang på Leiebase-objektet. \$leiebase = ' . var_export($leiebase, true));
	}
	$this->leiebase = $leiebase;

	global $tillegg;
	$this->lastTillegg( $tillegg );

	if($param !== null) {
		if( is_object( $param ) ) {
			settype($param, 'array');
		}
		if ( !is_array($param) ) {
			$param = array('id'	=> $param);
		}
	
		$this->id = $param['id'];
	}
	
}


// Når en ikke eksisterende egenskap forsøkes hentet, forsøkes den føst å hentes med hent().
//	Om dette mislykket sendes ei feilmelding
/****************************************/
//	--------------------------------------
//	retur:	Resultatet fra hent($name)
public function __get($name) {
	throw new Exception("Property " . $name . " is being retrieved wrongly.");
	$resultat = $this->hent( $name );
	if( $resultat ) {
		return $resultat;
	}
	else {
		throw new Exception("Property '{$name}' has not been declared and can not be retrieved.");
	}	
}


/*	Debug Info
Velger hvilke egenskaper som skal returneres med var_dump().
*****************************************/
//	--------------------------------------
public function __debugInfo() {
	return array('id'	=> $this->__toString());
}



// Når objektet behandles som streng vises id-verdien
/****************************************/
//	--------------------------------------
//	retur:	(int) id-heltallet for objektet
public function __toString() {
	return (string)$this->id;
}


// Laster gjengivelsesfila og fyller variablene i denne med verdier
/****************************************/
//	$param
//		id	(heltall) objektidentifikator for databasen	
//	--------------------------------------
protected function _gjengi() {
	extract($this->gjengivelsesdata, EXTR_SKIP);
	ob_start();
	include(__DIR__ . "/../_gjengivelser/{$this->gjengivelsesfil}");
	return ob_get_clean();
}


// Utløser hendelser knyttet til et bestemt stadium
/****************************************/
//	$param
//		id	(heltall) objektidentifikator for databasen	
//	--------------------------------------
protected function _utløs( $stadium ) {
	settype( $this->stadier, 'object' );
	
	if( isset( $this->stadier->$stadium ) ) {
		foreach( $this->stadier->$stadium as $handling ) {
			$handling->aksjon( $handling->parametere );
		}
	}
}


// Denne funksjonen må være definert i alle Classens Children
/****************************************/
//	--------------------------------------
//	$egenskap (streng) Egenskapen som skal hentes	
abstract public function hent($egenskap);


// Denne funksjonen må være definert i alle Classens Children
/****************************************/
//	--------------------------------------
//	$egenskaper (array) Verdier som angis for det nye objektet
abstract public function opprett($egenskaper);


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



// Forsøker å laste objektet, og returnerer primærnøkkel-verdien
/****************************************/
//	--------------------------------------
//	id-verdien
public function hentId() {
	return $this->hent($this->idFelt);
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
		$teller = bcmul($desimal, $nevner, 4);
		
		if ((int)$teller and $teller == (int)$teller ) {
			return ($heltall ? "{$heltall} " : "") . (int)$teller . "/{$nevner}";
		}
	}
	
	// Alle brøkmuligheter til og med 120-deler har blitt forsøkt
	//	uten å finne en som kan representere desimaltallet
	//	Derfor returneres tallet i prosent med to desimaler
	return bcmul( $verdi, 100, 2) . "%";
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