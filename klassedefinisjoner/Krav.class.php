<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

class Krav extends DatabaseObjekt {

protected	$tabell = "krav";	// Hvilken tabell i databasen som inneholder primærnøkkelen for dette objektet
protected	$idFelt = "id";		// Hvilket felt i tabellen som lagrer primærnøkkelen for dette objektet
protected	$data;				// DB-verdiene lagret som et objekt Null betyr at verdiene ikke er lastet
protected	$delkrav;			// Liste med stdClass-objekter. Null betyr at verdiene ikke er lastet
protected	$purringer;			// Array over alle purringene på dette kravet. Null betyr at purringene ikke er lastet.
protected	$purrestatistikk;	// Objekt med oversikt over siste purring, siste gebyr, antall purringer og antall gebyr. Null betyr at purringene ikke er lastet.
protected	$utskriftsposisjon = array();	// Utskriftsposisjonen for hver enkelt rute, sortert som et array med rutenummeret som nøkkel
public		$id;				// Unikt id-heltall for dette objektet


//	Constructor
/****************************************/
//	$param
//		id	(heltall) gironummeret	
//	--------------------------------------
public function __construct( $param = null ) {
	parent::__construct( $param );
}


// Last kravets kjernedata fra databasen
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
		
		'fields' =>			"{$this->tabell}.*,\n"
						.	"leieforhold.leieforhold,
							leieforhold.leieobjekt,
							leieforhold.regningsperson,
							leieforhold.regning_til_objekt,
							leieforhold.regningsobjekt,
							leieforhold.regningsadresse1,
							leieforhold.regningsadresse2,
							leieforhold.postnr,
							leieforhold.poststed,
							leieforhold.land",
						
		'source' => 		"{$tp}{$this->tabell} AS {$this->tabell}\n"
						.	"LEFT JOIN {$tp}kontrakter AS leieforhold ON {$this->tabell}.kontraktnr = leieforhold.kontraktnr\n"
						.	"LEFT JOIN {$tp}leieobjekter AS leieobjekter ON leieforhold.leieobjekt = leieobjekter.leieobjektnr\n",
						
		'where'			=>	"{$tp}{$this->tabell}.{$this->idFelt} = '$id'"
	));

	if( $resultat->totalRows ) {
		$this->data = $resultat->data[0];
		$this->id = $id;

		$this->data->leieforhold	= $this->leiebase->hent('Leieforhold', $this->data->leieforhold );
		$this->data->kravdato		= new DateTime( $this->data->kravdato );
		$this->data->dato			= $this->data->kravdato;
		$this->data->opprettet		= new DateTime( $this->data->opprettet );

		if( $this->data->gironr ) {
			$this->data->giro = $this->leiebase->hent('Giro', $this->data->gironr );
		}
		else {
			$this->data->giro = null;
		}

		if( $this->data->fom ) {
			$this->data->fom = new DateTime( $this->data->fom );
		}

		if( $this->data->tom ) {
			$this->data->tom = new DateTime( $this->data->tom );
		}

		if( $this->data->utskriftsdato ) {
			$this->data->utskriftsdato = new DateTime( $this->data->utskriftsdato );
		}

		if( $this->data->forfall ) {
			$this->data->forfall = new DateTime( $this->data->forfall );
		}
		
		return true;
	}
	else {
		$this->id = null;
		$this->data = null;
		
		return false;
	}

}


// Last alle delkravene på kravet fra databasen
/****************************************/
//	--------------------------------------
//	retur: Boolsk suksessangivelse
protected function lastDelkrav() {
	$tp = $this->mysqli->table_prefix;

	if ( !$id = $this->id ) {
		$this->delkrav = null;
		return false;
	}

	$resultat = $this->mysqli->arrayData(array(
		'returnQuery'	=> true,
		'fields'		=> "delkravtyper.id, delkravtyper.kode, delkravtyper.navn, delkravtyper.beskrivelse, delkrav.beløp\n",
		'source'		=> "{$tp}delkrav AS delkrav\n"
						. "INNER JOIN {$tp}delkravtyper AS delkravtyper ON delkrav.delkravtype = delkravtyper.id\n",
		'where'			=> "delkrav.kravid = '$id'",
		'orderfields'	=> "delkravtyper.orden, delkrav.id"
	));

	$this->delkrav = $resultat->data;
	return true;
}


// Last alle purringene på kravet fra databasen
/****************************************/
//	$param
//	--------------------------------------
protected function lastPurringer() {
	$tp = $this->mysqli->table_prefix;
	if ( !$id = $this->id ) {
		$this->purringer = null;
		return false;
	}

	$resultat = $this->mysqli->arrayData(array(
		'distinct'		=> true,
		'returnQuery'	=> true,
		'class' 		=> "Purring",
		'fields'		=> "purringer.blankett AS id\n",
		'source'		=> "{$tp}krav AS krav\n"
						. "INNER JOIN purringer ON purringer.krav = krav.id\n",
		'where'			=> "{$tp}krav.id = '$id'",
		'orderfields'	=> "purringer.purredato, purringer.blankett, purringer.nr"
	));

	$this->purringer = $resultat->data;

	// Lagre antall og siste purring og gebyr
	$this->purrestatistikk = (object)array(
		'sistePurring'		=> end($this->purringer),
		'antallPurringer'	=> count($this->purringer),
		'sisteGebyr'		=> null,
		'antallGebyr'		=> 0
	);
	foreach( $this->purringer as $purring ) {
		if( $gebyr = $purring->hent('purregebyr') ) {
			$this->purrestatistikk->sisteGebyr = $gebyr;
			$this->purrestatistikk->antallGebyr += 1;
		}
	}

}


// Last utskriftsposisjonen for kravet
/****************************************/
//	$param
//		rute	(heltall) utdelingsruten som bestemmer utskriftsrekkefølgen
//	--------------------------------------
protected function lastUtskriftsposisjon($rute = null) {
	settype($rute, 'integer');
	if ( !$id = $this->id ) {
		$this->purringer = null;
		return false;
	}
	
	if( $this->hent('regning_til_objekt') ) {
		$this->utskriftsposisjon[$rute] = 1000000 * ($this->mysqli->arrayData(array(
			'returnQuery'	=> true,
		
			'fields' =>			"utdelingsorden.plassering\n",
			'source' => 		"{$tp}utdelingsorden AS utdelingsorden\n",
			'where'			=>	"utdelingsorden.leieobjekt = '{$this->hent('regningsobjekt')}' AND rute = '{$rute}'"
		))->data[0]->plassering)
		+ $this->hent('leieforhold');
	}
	else {
		$this->utskriftsposisjon[$rute] = intval((string)$this->hent('leieforhold'));
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

	case $this->idFelt: {
		if ( $this->data === null and !$this->last()) {
			return false;
		}		
		return $this->data->$egenskap;
		break;
	}

	case "andel":
	case "anleggsnr":
	case "beløp":
	case "dato":
	case "fom":
	case "forfall":
	case "giro":
	case "gironr":
	case "kravdato":
	case "leieforhold":
	case "leieobjekt":
	case "oppretter":
	case "opprettet":
	case "regning_til_objekt":
	case "regningsobjekt":
	case "tekst":
	case "termin":
	case "tom":
	case "type":
	case "utestående":
	case "utskriftsdato": {
		if ( $this->data === null and !$this->last()) {
			throw new Exception("Klarte ikke laste Krav({$this->id})");
		}		
		return $this->data->$egenskap;
		break;
	}

	case "utskriftsposisjon": {
		if ( $this->data == null ) {
			$this->last();
		}
		
		if ( !$this->utskriftsposisjon[$param['rute']] ) {
			$this->lastUtskriftsposisjon($param['rute']);
		}
		return $this->utskriftsposisjon[$param['rute']];
		break;
	}

	/*
	retur: (serie) stdClass-objekter med egenskapene
		id	(heltall):	Id for denne delkravtypen
		kode (streng):	Koden for denne delkravtypen
		navn (streng): Navnet på denne delkravtypen
		beskrivelse (streng): Beskrivelse av denne delkravtypen
		beløp (tall): Beløpet
	*/
	case "delkrav": {
		if ( $this->delkrav === null ) {
			$this->lastDelkrav();
		}		
		return $this->delkrav;
		break;
	}

	case "solidaritetsfondbeløp": {
		return $this->hentDel(1);
		break;
	}

	case "purringer": {
		if ( $this->purringer === null ) {
			$this->lastPurringer();
		}		
		return $this->purringer;
		break;
	}

	case "antallGebyr":
	case "antallPurringer":
	case "sisteGebyr":
	case "sistePurring": {
		if ( $this->purringer === null ) {
			$this->lastPurringer();
		}		
		return $this->purrestatistikk->$egenskap;
		break;
	}

	/*
	retur: (DateTime-objekt eller false) Evt dato da kravet ble betalt ned
	*/
	case "betalt": {
		if ( $this->hent('utestående') != 0 ) {
			return false;
		}
		$betalinger = $this->hentBetalinger();
		if(!$betalinger) {
			return $this->hent('dato');
		}
		return end($betalinger)->hent('dato');
		break;
	}

	default: {
		return null;
		break;
	}
	}

}



/*	Hent Betalinger
Se etter betalinger ført mot dette kravet.
******************************************
--------------------------------------
retur: (liste av Innbetalingsobjekter) Innbetalingene
*/
public function hentBetalinger() {
	$tp = $this->mysqli->table_prefix;
	
	$innbetalinger = $this->mysqli->arrayData(array(
		'source'		=>	"{$tp}innbetalinger as innbetalinger",
		'where'			=> "innbetalinger.krav = '{$this->hentId()}'",
		'distinct'		=> true,
		'fields'		=> "innbetalinger.innbetaling as id",
		'orderfields'	=> "innbetalinger.dato, innbetalinger.innbetaling",
		'class'			=> "Innbetaling"
	));

	return $innbetalinger->data;
}



/*	Hent del
Hent et bestemt delbeløp i kravet.
*****************************************/
//	$delkrav (heltall/streng):	Angi delkravtypen som id eller kode
//	Dersom delkravtypen ikke er angitt returneres alle delkravene som et objekt
//	med delkravkodene som egenskaper
//	--------------------------------------
//	retur: (nummer/objekt) De(t) aktuelle delbeløpe(-t/-ne)

public function hentDel($delkravtype = null) {
	$resultat = new stdclass;
	
	foreach( $this->hent('delkrav') as $delkrav ) {
		if(
			$delkravtype === null
			|| $delkravtype == $delkrav->id
			|| $delkravtype == $delkrav->kode
		) {
			if(
				$delkravtype !== null
			) {
				return $delkrav->beløp;
			}
			
			$resultat->{$delkrav->kode} = $delkrav->beløp;
		}
	}
	
	//	Ingen treff
	if(
		$delkravtype !== null
	) {
		return 0;
	}
	
	return $resultat;
}



/*	Hent Krediteringer
Se etter evt kredittkrav opprettet for å motvirke dette kravet.
******************************************
--------------------------------------
retur: (liste av Krav-objekter) Kredittene
*/
public function hentKrediteringer() {
	$tp = $this->mysqli->table_prefix;
	
	if($this->hent('beløp') < 0) {
		return array();
	}
	
	$kredittkrav = $this->mysqli->arrayData(array(
		'source'	=>	"{$tp}krav as krav INNER JOIN {$tp}innbetalinger as innbetalinger ON krav.id = innbetalinger.krav",
		'where'		=> "innbetalinger.konto = '0' AND krav.beløp < 0 AND innbetalinger.ref = '{$this->hentId()}'",
		'distinct'	=> true,
		'fields'	=> "krav.id",
		'class'		=> "Krav"
	));

	return $kredittkrav->data;
}



/*	Hent Kreditert krav
Dersom dette er kreditt hentes kravet som evt ble kreditert.
******************************************
--------------------------------------
retur: (Krav-objekt eller false) Det aktuelle kravet
*/
public function hentKreditertKrav() {
	$tp = $this->mysqli->table_prefix;
	
	if($this->hent('beløp') > 0) {
		return false;
	}
	
	$krav = $this->mysqli->arrayData(array(
		'source'	=>	"{$tp}krav as krav INNER JOIN {$tp}innbetalinger as innbetalinger ON krav.id = innbetalinger.ref",
		'where'		=> "innbetalinger.konto = '0' AND innbetalinger.krav = '{$this->hentId()}'",
		'distinct'	=> true,
		'fields'	=> "krav.id",
		'class'		=> "Krav"
	));

	return reset( $krav->data );
}



/*	Hent Kredittkopling
Dersom dette er kreditt hentes koplingen som er lagret som innbetaling.
******************************************
--------------------------------------
retur: (innbetalingsobjekt eller false) Den aktuelle koplingen
*/
public function hentKredittkopling() {
	$tp = $this->mysqli->table_prefix;
	
	if($this->hent('beløp') > 0) {
		return false;
	}
	
	$kopling = $this->mysqli->arrayData(array(
		'source'	=>	"{$tp}innbetalinger as innbetalinger",
		'where'		=> "innbetalinger.konto = '0'
						AND innbetalinger.krav = '{$this->hentId()}'",
		'distinct'	=> true,
		'fields'	=> "innbetalinger.innbetaling AS id",
		'class'		=> "Innbetaling"
	));

	return reset($kopling->data);
}



/*	Hent Utlikninger
Se etter delbetalinger ført mot dette kravet.
******************************************
--------------------------------------
retur: (liste) Objekter med egenskapene:
	id:	(heltall) Id'en for dette delbeløpet
	beløp: (tall) Delbeløpet som er ført mot dette kravet
	innbetaling: (Innbetaling-objekt) Innbetalinga
*/
public function hentUtlikninger() {
	$tp = $this->mysqli->table_prefix;
	
	$innbetalinger = $this->mysqli->arrayData(array(
		'source'		=>	"{$tp}innbetalinger as innbetalinger",
		'where'			=> "innbetalinger.krav = '{$this->hentId()}'",
		'distinct'		=> true,
		'fields'		=> "innbetalinger.innbetalingsid as id, innbetalinger.innbetaling, innbetalinger.beløp",
		'orderfields'	=> "innbetalinger.dato, innbetalinger.innbetaling",
		'class'			=> "stdClass"
	));
	foreach( $innbetalinger->data as $del ) {
		$del->innbetaling = $this->leiebase->hent('Innbetaling', $del->innbetaling);
	}

	return $innbetalinger->data;
}



/*	Gjengi
Kravet gjengis i for skjerm- utskrift- eller filgjengivelse.
*****************************************/
//	$mal (tekst):	Gjengivelsesmalen som skal brukes. Må være ei fil som befinner seg i '_gjengivelser'
//	$param (array/objekt) Eksterne parametere som skal brukes i gjengivelsen
//	--------------------------------------
//	retur: (streng) Gjengivelse av kravet

public function gjengi($mal, $param = array()) {
	settype( $param, 'array');

	
	switch($mal) {

	default: {
		break;
	}
	}
	
	$this->gjengivelsesfil = "{$mal}.php";
	return $this->_gjengi( (array)$param );
}



/*	Krediter
Krediterer kravet ved å opprette et identisk krav med negativt beløp.
Bare krav med positivt beløp kan krediteres, og kun i sin helhet.
*****************************************
$beløp (tall, normalt null):	Beløpet som krediteres.
------------------------------------------
retur (objekt/usann): Det nye kredittkravet dersom kravet blir kreditert, ellers usann
*/
public function krediter() {
	$tp = $this->mysqli->table_prefix;
	$this->last();
	$id = $this->hentId();
	$leieforhold = $this->hent('leieforhold');
	$tidligstMuligeKravdato = $this->leiebase->tidligstMuligeKravdato();
	
	$delkravsett = array();
	
	if( $this->hentKrediteringer() or $this->hentKreditertKrav() ) {
		return false;
	}

	foreach( $this->hent('delkrav') as $delkrav ) {
		$delkravsett[] = (object)array(
			'type'	=> $delkrav->id,
			'beløp'	=> $delkrav->beløp * (-1)
		);
	}
	
	$kreditt = $this->leiebase->opprett('Krav', array(
		'kravdato'		=> max(date_create(), $this->data->kravdato),
		'kontraktnr'	=> $this->data->kontraktnr,
		'type'			=> $this->data->type,
		'leieobjekt'	=> $this->data->leieobjekt,
		'fom'			=> $this->data->fom,
		'tom'			=> $this->data->tom,
		'tekst'			=> "Kreditering av " . $this->data->tekst,
		'termin'		=> $this->data->termin,
		'beløp'			=> $this->data->beløp * (-1),
		'andel'			=> "-" . $this->data->andel,
		'anleggsnr'		=> $this->data->anleggsnr,
		'delkrav'		=> $delkravsett
	));
	
	if( $kreditt ) {

		$kreditt->hentKredittkopling()->sett( 'ref', $this->hentId() );		

		// Fordel overskytende kredittbeløp om mulig
		$kreditt->hentKredittkopling()->fordel( $this );
	}
	
	$this->data = null;
	$this->leiebase->oppdaterUbetalt();
	
	return $kreditt;
}



// Oppretter et nytt krav i databasen og tildeler egenskapene til dette objektet
/****************************************/
//	$egenskaper (array/objekt) Alle egenskapene det nye objektet skal initieres med
//	--------------------------------------
public function opprett($egenskaper = array()) {
	$tp = $this->mysqli->table_prefix;
	settype( $egenskaper, 'array');
	
	if( $this->id ) {
		throw new Exception('Nytt Krav-objekt forsøkt opprettet, men det eksisterer allerede');
		return false;
	}
	
	if( !@$egenskaper['kontraktnr'] ) {
		throw new Exception('Nytt Krav-objekt forsøkt opprettet, men mangler kontraktnr');
		return false;
	}
	
	if( !@$egenskaper['kravdato'] ) {
		$egenskaper['kravdato'] = new DateTime;
	}
	if( !@$egenskaper['type'] ) {
		$egenskaper['type'] = "Husleie";
	}
	if( !@$egenskaper['oppretter'] ) {
		$egenskaper['oppretter'] = $this->leiebase->bruker['navn'];
	}
	
	$tidligstMuligeKravdato = $this->leiebase->tidligstMuligeKravdato();
	if( $tidligstMuligeKravdato and $tidligstMuligeKravdato > $egenskaper['kravdato'] ) {
		$egenskaper['kravdato'] = new DateTime;
	}
	
	$databasefelter = array();
	$resterendeFelter = array();
	
	foreach($egenskaper as $egenskap => &$verdi) {
	
		if ( $verdi instanceof DateTime ) {

			switch( $egenskap ) {
			case "utskriftsdato":
				$verdi = $verdi->format('Y-m-d H:i:s');
				break;

			default:
				$verdi = $verdi->format('Y-m-d');
				break;
			}		
		}

		switch( $egenskap ) {
		case "delkrav":
			break;

		case "type":
		case "kontraktnr":
		case "gironr":
		case "leieobjekt":
		case "kravdato":
		case "fom":
		case "tom":
		case "utskriftsdato":
		case "tekst":
		case "termin":
		case "beløp":
		case "andel":
		case "anleggsnr":
		case "oppretter":
			$databasefelter[$egenskap] = $verdi;
			break;

		default:
			$resterendeFelter[$egenskap] = $verdi;
			break;
		}		
	}
	$databasefelter['utestående'] = $databasefelter['beløp'];
	$databasefelter['opprettet'] = date('Y-m-d H:i:s');
	
	$this->id = $this->mysqli->saveToDb(array(
		'insert'	=> true,
		'id'		=> $this->idFelt,
		'table'		=> "{$tp}{$this->tabell}",
		'fields'	=> $databasefelter
	))->id;
	
	if( !$this->hentId() ) {
		throw new Exception('Nytt Krav forsøkt lagret, men det kunne ikke lastes igjen etterpå');
		return false;
	}

	if(is_array(@$egenskaper['delkrav'])) {
		foreach($egenskaper['delkrav'] as $delkrav) {
			$this->mysqli->saveToDb(array(
			'insert'	=> true,
			'table'		=> "{$tp}delkrav",
			'fields'	=> array(
				'kravid'		=> $this,
				'delkravtype'	=> $delkrav->type,
				'beløp'			=> $delkrav->beløp
			)
		));
		}
	}

	foreach( $resterendeFelter as $egenskap => $verdi ) {
		$this->sett($egenskap, $verdi);
	}
	
	
	// Dersom kravet er negativt (kreditt) må også betalingsdelen lagres.
	if( $egenskaper['beløp'] < 0 ) {
	
		$debetkopling = $this->leiebase->opprett('Innbetaling', array(
			'dato'			=> $egenskaper['kravdato'],
			'beløp'			=> -$egenskaper['beløp'],
			'betaler'		=> $this->hent('leieforhold'),
			'ref'			=> md5($this->hentId()),
			'merknad'		=> $egenskaper['tekst'],
			'konto'			=> "0"
		));

		$kredittkopling = $this->leiebase->opprett('Innbetaling', array(
			'dato'			=> $egenskaper['kravdato'],
			'beløp'			=> $egenskaper['beløp'],
			'betaler'		=> $this->hent('leieforhold'),
			'ref'			=> md5($this->hentId()),
			'merknad'		=> $egenskaper['tekst'],
			'konto'			=> "0"
		));
		
		$debetkopling->konter( $this->hent('leieforhold') );
		$kredittkopling->konter( $this->hent('leieforhold') );
		
		// Fordel den negative delen av koplinga mot kredittkravet
		$debetkopling->fordel( $this );

	}	
	return $this;
}



// Skriv en verdi
/****************************************/
//	$egenskap		streng. Egenskapen som skal endres
//	$verdi			Ny verdi
//	--------------------------------------
public function sett($egenskap, $verdi = null) {
	$tp = $this->mysqli->table_prefix;
	$giro = $this->hent('giro');
	
	if( !$this->id ) {
		return null;
	}
	
	// Krav sendt ut på giro kan ikke endres
	if( $giro and $egenskap != 'forfall' ) {
		return false;
	}
	
	if( $egenskap == 'type' or $egenskap == 'fom' or $egenskap == 'tom' or $egenskap == 'andel' ) {
		$type	= ( $egenskap == 'type' )	? $verdi : $this->hent('type');
		$fom	= ( $egenskap == 'fom' )	? $verdi : $this->hent('fom');
		$tom	= ( $egenskap == 'tom' )	? $verdi : $this->hent('tom');
		$andel	= ( $egenskap == 'andel' )	? $verdi : $this->hent('andel');
		
		if( $type == "Husleie" ) {
			$leieforhold = $this->hent('leieforhold');
			$leieobjekt = $leieforhold->hent('leieobjekt');
			
			$desimalAndel = $this->leiebase->fraBrøk($andel);

			$ledig = $leieobjekt->hentLeiekrav($fom, $tom, $leieforhold)->ledig;
			
			if($desimalAndel > $ledig) {
				return false;
			}
		}
	}
	
	switch( $egenskap ) {
	
	case "type":
	case "andel":
	case "tekst":
	case "termin":
	case "anleggsnr": {

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
		$this->delkrav = null;

		return $resultat;
		break;
	}

	case "beløp": {
		if($verdi == 0) {
			return false;
		}
		if( ($this->hent('beløp') * $verdi) < 0) {
			return false;
		}

		if( $this->hent('beløp') == $verdi ) {
			return true;
		}

		$resultat = $this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "{$tp}{$this->tabell} as {$this->tabell}",
			'where'		=> "{$this->tabell}.{$this->idFelt} = '{$this->id}'",
			'fields'	=> array(
				"{$this->tabell}.{$egenskap}"	=> $verdi
			)
		))->success;
		
		// Behandling av koplinger for kreditt
		if( $resultat and ($verdi < 0) ) {
			$kredittUtlikning = $this->hentKredittkopling();
			// Tving ny lasting av data:
			$this->data = null;
			$this->delkrav = null;
			
			$kredittUtlikning->balanserKreditt();
		}

		// Tving ny lasting av data:
		$this->data = null;
		$this->delkrav = null;

		return $resultat;
		break;
	}

	case "dato":
	case "kravdato": {
		if ( !is_a($verdi, 'DateTime') ) {
			throw new Exception('Kravdato må være i form av DateTime-objekt');
		}
	}

	case "fom":
	case "tom": {
		if( $egenskap == 'dato' ) {
			$egenskap = 'kravdato';
		}
		if ( $verdi instanceof DateTime ) {
			$verdi = clone $verdi;
		}
		else if ( $verdi ) {
			$verdi = new DateTime( $verdi );
		}
		
		$resultat = $this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "{$tp}{$this->tabell} as {$this->tabell}",
			'where'		=> "{$this->tabell}.{$this->idFelt} = '{$this->id}'",
			'fields'	=> array(
				"{$this->tabell}.{$egenskap}"	=> ( $verdi ? $verdi->format('Y-m-d') : null )
			)
		))->success;

		// Tving ny lasting av data:
		$this->data = null;
		$this->delkrav = null;

		return $resultat;
		break;
	}

	case "forfall": {
		if ( $verdi instanceof DateTime ) {
			$verdi = clone $verdi;
		}
		else if ( $verdi ) {
			$verdi = new DateTime( $verdi );
		}
		
		if( $verdi ) {
			$bankfridager = $this->leiebase->bankfridager( $verdi->format('Y') );
			while(
				in_array( $verdi->format('m-d'), $bankfridager )
				or $verdi->format('N') > 5 
			) {
				$verdi->add(new DateInterval('P1D'));
			}
		}
		
		// Dersom kravet tilhører en giro, må forfall endres gjennom denne.
		if( $giro ) {
			$resultat = $giro->sett('forfall', $verdi );
		}
		
		else {
			$resultat = $this->mysqli->saveToDb(array(
				'update'	=> true,
				'table'		=> "{$tp}{$this->tabell} as {$this->tabell}",
				'where'		=> "{$this->tabell}.{$this->idFelt} = '{$this->id}'",
				'fields'	=> array(
					"{$this->tabell}.forfall"	=> ( $verdi ? $verdi->format('Y-m-d') : null )
				)
			))->success;
		}

		// Tving ny lasting av data:
		$this->data = null;
		$this->delkrav = null;

		return $resultat;
		break;
	}

	default: {
		return false;
		break;
	}

	}

}


// Skriv et delbeløp
/****************************************/
//	$delkravtype	heltall. id-nummeret for delkravtypen som skal settes
//	$verdi			Desimaltall
//	--------------------------------------
public function settDel($delkravtype, $verdi = 0) {
	$tp = $this->mysqli->table_prefix;
	settype($delkravtype,	'integer');
	settype($verdi, 		'float');
	
	$giro = $this->hent('giro');
	
	if( !$this->id ) {
		return null;
	}
	
	// Krav sendt ut på giro kan ikke endres
	if( $giro ) {
		return false;
	}
	
	$kravbeløp = $this->hent('beløp');
	if( ($kravbeløp * $verdi) < 0 ) {
		return false;
	}
	if( abs($kravbeløp) < abs($verdi) ) {
		return false;
	}
	
	$resultat = $this->mysqli->query("DELETE FROM {$tp}delkrav WHERE kravid = '{$this}' AND delkravtype = '{$delkravtype}'");
	
	if( $resultat and ($verdi != 0) ) {
		return $this->mysqli->saveToDb(array(
			'insert'	=> true,
			'table'		=> "{$tp}delkrav",
			'fields'	=> array(
				"kravid"		=> $this,
				"delkravtype"	=> $delkravtype,
				"beløp"			=> $verdi
			)
		))->success;
	}
	else {
		return $resultat;
	}
}


// Sletter dette kravet fra databasen
/****************************************/
//	--------------------------------------
public function slett() {
	$tp = $this->mysqli->table_prefix;
	$giro = $this->hent('giro');
	
	if( !(int)$this->id ) {
		return false;
	}
	
	// Krav sendt ut på giro kan ikke slettes, men må krediteres
	if( $giro ) {
		return (bool)$this->krediter();
	}
	
	$sql = "DELETE krav.*, delkrav.*, debetkopling.*, kredittkopling.*
		FROM
		{$tp}krav AS krav
		LEFT JOIN {$tp}delkrav AS delkrav ON krav.id = delkrav.kravid
		LEFT JOIN {$tp}innbetalinger AS debetkopling ON krav.id = debetkopling.krav AND debetkopling.konto = '0' AND debetkopling.beløp < 0
		LEFT JOIN {$tp}innbetalinger AS kredittkopling ON debetkopling.innbetaling = kredittkopling.innbetaling AND debetkopling.konto = kredittkopling.konto
		WHERE krav.id = '" . (int)$this->id . "'";
	
	$resultat = $this->mysqli->query( $sql );
	
	// Tøm alle mellomlagrede data i kravet
	if($resultat) {
		$this->id = null;
		$this->data = null;
		$this->purringer = null;
		$this->purrestatistikk = null;
	}
	
	return $resultat;
}


}?>