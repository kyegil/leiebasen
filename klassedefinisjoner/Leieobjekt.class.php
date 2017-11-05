<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
Oppdatert 2015-10-02
**********************************************/

class Leieobjekt extends DatabaseObjekt {

protected	$tabell = "leieobjekter";	// Hvilken tabell i databasen som inneholder primærnøkkelen for dette objektet
protected	$idFelt = "leieobjektnr";	// Hvilket felt i tabellen som lagrer primærnøkkelen for dette objektet
protected	$data;				// DB-verdiene lagret som et objekt. Null betyr at verdiene ikke er lastet
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



// Last Leieobjektets kjernedata fra databasen
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
		
		'fields' =>			"{$this->tabell}.{$this->idFelt} AS id,
							{$this->tabell}.leieobjektnr,
							{$this->tabell}.bygning,
							{$this->tabell}.navn,
							{$this->tabell}.gateadresse,
							{$this->tabell}.postnr,
							{$this->tabell}.poststed,
							{$this->tabell}.etg,
							{$this->tabell}.beskrivelse,
							{$this->tabell}.bilde,
							{$this->tabell}.areal,
							{$this->tabell}.ant_rom,
							{$this->tabell}.bad,
							{$this->tabell}.toalett,
							{$this->tabell}.boenhet,
							{$this->tabell}.leieberegning,
							{$this->tabell}.merknader,
							{$this->tabell}.toalett_kategori,
							{$this->tabell}.ikke_for_utleie,\n"
							
						.	"leieberegning.nr AS leieberegningsnr,
							leieberegning.navn AS leieberegningsnavn,
							leieberegning.beskrivelse AS leieberegningsbeskrivelse,
							leieberegning.leie_objekt,
							leieberegning.leie_kontrakt,
							leieberegning.leie_kvm,
							leieberegning.leie_var_bad,
							leieberegning.leie_var_fellesdo,
							leieberegning.leie_var_egendo,\n"
							
						.	"bygninger.kode AS bygningskode,
							bygninger.navn AS bygningsnavn,
							bygninger.bilde AS bygningsbilde\n",
						
		'source' => 		"{$tp}{$this->tabell} AS {$this->tabell}\n"
						.	"LEFT JOIN {$tp}leieberegning AS leieberegning ON {$this->tabell}.leieberegning = leieberegning.nr\n"
						.	"LEFT JOIN {$tp}bygninger AS bygninger ON {$this->tabell}.bygning = bygninger.id\n",

		'where'			=>	"{$tp}{$this->tabell}.{$this->idFelt} = '$id'"
		
	));
	
	if( isset( $resultat->data[0] ) ) {
		$this->data = (object)array(
			'id'				=> $resultat->data[0]->id,
			'leieobjektnr'		=> $resultat->data[0]->leieobjektnr,
			'bygning'			=> $resultat->data[0]->bygning,
			'navn'				=> $resultat->data[0]->navn,
			'gateadresse'		=> $resultat->data[0]->gateadresse,
			'postnr'			=> $resultat->data[0]->postnr,
			'poststed'			=> $resultat->data[0]->poststed,
			'etg'				=> $resultat->data[0]->etg,
			'beskrivelse'		=> $resultat->data[0]->beskrivelse,
			'bilde'				=> $resultat->data[0]->bilde,
			'areal'				=> $resultat->data[0]->areal,
			'antRom'			=> $resultat->data[0]->ant_rom,
			'bad'				=> $resultat->data[0]->bad,
			'toalett'			=> $resultat->data[0]->toalett,
			'boenhet'			=> (bool)$resultat->data[0]->boenhet,
			'leieberegning'		=> (object)array(
				'nr'				=> $resultat->data[0]->leieberegningsnr,
				'navn'				=> $resultat->data[0]->leieberegningsnavn,
				'beskrivelse'		=> $resultat->data[0]->leieberegningsbeskrivelse,
				'perLeieobjekt'		=> $resultat->data[0]->leie_objekt,
				'perLeieforhold'	=> $resultat->data[0]->leie_kontrakt,
				'perKvm'			=> $resultat->data[0]->leie_kvm,
				'tilleggBad'		=> $resultat->data[0]->leie_var_bad,
				'tilleggFellesDo'	=> $resultat->data[0]->leie_var_fellesdo,
				'tilleggEgenDo'		=> $resultat->data[0]->leie_var_egendo
			),
			'merknader'			=> $resultat->data[0]->merknader,
			'toalettkategori'	=> $resultat->data[0]->toalett_kategori,
			'ikkeForUtleie'		=> (bool)$resultat->data[0]->ikke_for_utleie
		);

		if($this->data->etg === '+') {
			$this->data->etg = "loft";
		}
		else if($this->data->etg === '0') {
			$this->data->etg = "sokkel";
		}
		else if($this->data->etg === '-1') {
			$this->data->etg = "kjeller";
		}
		else if((int)$this->data->etg) {
			$this->data->etg .= ". etg.";
		}

		$this->data->type
			= $this->data->boenhet
			? "bolig"
			: "lokale"
		;
		
		$this->data->adresse
				= (
				$this->data->navn
				? "{$this->data->navn}\n"
				: ""
			)
			. "{$this->data->gateadresse}\n"
			. "{$this->data->postnr} {$this->data->poststed}\n";
		
		$this->data->beskrivelse
			= ( $this->data->navn ? "{$this->data->navn}, " : "" )
			. ( $this->data->etg ? "{$this->data->etg} " : "" )
			. ( $this->data->beskrivelse ? "{$this->data->beskrivelse} " : "" )
			. $this->data->gateadresse;
		$this->id = $id;
	}
	else {
		$this->id = null;
		$this->data = null;
	}

}



// Foreslå husleie for leieobjektet
/****************************************/
//	$andel (tall) Oppgis som brøk eller desimaltall
//	--------------------------------------
//	resultat: (tall) Foreslått husleie per år
public function beregnLeie( $andel = 1 ) {
	$leieberegning = $this->hent('leieberegning');
	
	if( !$leieberegning->nr ) {
		return 0;
	}
	
	$resultat = 12 * (
		$leieberegning->perLeieforhold
		+
		$this->fraBrøk($andel) * (
			$leieberegning->perLeieobjekt
			+
			$leieberegning->perKvm * $this->data->areal
			+
			$leieberegning->tilleggBad * (int)$this->data->bad
			+
			(($this->data->toalettkategori == 1) ? $leieberegning->tilleggFellesDo : 0)
			+
 			(($this->data->toalettkategori == 2) ? $leieberegning->tilleggEgenDo : 0)
		)
	);

	return $resultat;
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
	case "adresse":
	case "antRom":
	case "areal":
	case "bad":
	case "beskrivelse":
	case "bilde":
	case "boenhet":
	case "bygning":
	case "etg":
	case "gateadresse":
	case "ikkeForUtleie":
	case "leieberegning":
	case "leieobjektnr":
	case "merknader":
	case "navn":
	case "postnr":
	case "poststed":
	case "toalett":
	case "toalettkategori":
	case "type":
		if ( $this->data == null ) {
			$this->last();
		}		
		return $this->data->$egenskap;
		break;

//	Parametre:
//		dato	(DateTime-objekt) Datoen det spørres for	
//	--------------------------------------
	case "leietakere":
	case "beboere":
		if( !isset($param['dato']) ) {
			$dato = new DateTime();
		}
		else if( !is_a($param['dato'], "DateTime") ) {
			$dato = new DateTime($param['dato']);
		}
		else {
			$dato = $param['dato'];
		}
		
		$leietakere = array();
		$beboere = "";
		
		$leieforhold = $this->hentUtleie( $dato )->faktiskeLeieforhold;
		
		$ant = count( $leieforhold );

		foreach( $leieforhold as $nr => $lf) {
			array_push( $leietakere, $lf->hent('leietakere'));
			
			$beboere .= $lf->hent('navn');
			if($nr < $ant-2) $beboere .= ", ";
			if($nr == $ant-2) $beboere .= " og ";
		}
		
		return $$egenskap;
		break;

	default:
		return null;
		break;
	}
}



// Hent Leiekrav og inndekning for et bestemt tidsrom
/****************************************/
//	$param
//		fra:	(DateTime eller 'Y-m-d'-streng) starten på tidsrommet leien skal returneres for
//		til:	(DateTime eller 'Y-m-d'-streng), normalt $fra, slutt på tidsrommet utleien skal returneres for
//		seBortFraLeieforhold:	(Leieforhold-objekt eller heltall), evt. leieforhold som skal utelates
//	--------------------------------------
//	resultat: stdClass-objekt med egenskapene:
//		leie: liste med alle leie-Kravobjekter, både bestemte og i oppsigelsestid
//		oppsigelsestid: liste med leie-Kravobjekter i oppsigelsestida
//		grad: (desimalstreng med 12 desimaler) Hvor stor del av leieobjektet som er utleid
//		ledig: (desimalstreng med 12 desimaler) Hvor stor del av leieobjektet som står ledig
public function hentLeiekrav($fra, $til = null, $seBortFraLeieforhold = null) {
	$tp = $this->mysqli->table_prefix;
	$resultat = (object)array(
		'leie'				=> array(),
		'oppsigelsestid'	=> array(),
		'grad'				=> 0,
		'ledig'				=> 0
	);
	
	// Gjør om datoene til strenger
	if( $fra instanceof DateTime ) {
		$fra = $fra->format('Y-m-d');
	}
	settype( $fra, 'string' );

	if( $til instanceof DateTime ) {
		$til = $til->format('Y-m-d');
	}
	if ($til === null) {
		$til = $fra;
	}
	settype( $til, 'string' );

	settype( $seBortFraLeieforhold, 'string' );
	
	
	// Hent alle leieforhold som spenner over det aktuelle tidsrommet
	$aktuelle = $this->mysqli->arrayData(array(
//		'distinct'	=> true,
		'returnQuery'	=> true,
		'source'	=> "{$tp}krav AS krav\n"
					.   "LEFT JOIN {$tp}kontrakter AS kontrakter ON krav.kontraktnr = kontrakter.kontraktnr\n"
					.   "LEFT JOIN {$tp}oppsigelser AS oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold\n",
		'distinct'		=> true,
		'fields'		=> "krav.id,\n"
						.	"krav.fom,\n"
						.	"krav.tom,\n"
						.	"krav.andel,\n"
						.	"krav.beløp,\n"
						.	"DATE_ADD(krav.tom, INTERVAL 1 DAY) AS påfølgende,\n"
						.	"oppsigelser.fristillelsesdato\n",
		'orderfields'	=> "fom ASC\n",
		'where'			=> "krav.leieobjekt = '{$this->id}'\n"
						.	"AND kontrakter.leieforhold != '" . (int)$seBortFraLeieforhold . "'\n"
						.	"AND krav.type = 'Husleie'\n"
						.	"AND krav.fom <= '$til'\n"
						.	"AND krav.tom >= '$fra'\n"
	));
	
	// Sjekk om leiekravene inneholder kreditt
	$inneholderKreditt = false;
	foreach( $aktuelle->data as $krav) {
		if( $krav->beløp < 0 ) {
			$inneholderKreditt = true;
		}
	
	}
	
	// Loop gjennom resultatet første gang
	//	for å opprette krav-objektene
	//	og for å fastsette datoer
	$datoer = array( $fra => 0 );
	foreach( $aktuelle->data as $krav) {
		$leiekrav = $this->leiebase->hent('Krav', $krav->id );
		
		$motkrav = false;
		if($inneholderKreditt and  $krav->beløp < 0 ) {
			$motkrav = $leiekrav->hentKreditertKrav();
			$motkrav = reset($motkrav);
		}
		else if($inneholderKreditt ) {
			$motkrav = $leiekrav->hentKrediteringer();
			$motkrav = reset($motkrav);
		}
		
		if( !$inneholderKreditt or !$motkrav or ($krav->beløp + $motkrav->hent('beløp') != 0 ) ) {	
			// Putt kravet i beholderne leie og evt oppsigelsestid
			$resultat->leie[] = $leiekrav;
		
			if ($krav->fristillelsesdato and $krav->tom >= $krav->fristillelsesdato ) {
				// Kravet dekker et tidsrom etter utflytting, og skal derfor i beholderen 'oppsigelsestid'
				$resultat->oppsigelsestid[] = $leiekrav;
			}
		}
		// Legg kontrolldatoene i $datoer
		$krav->fom = max($krav->fom, $fra);
		settype( $datoer[$krav->fom], 'string' );

		$krav->tom = min($krav->tom, $til);
		if($krav->fristillelsesdato <= $til) {
			settype( $datoer[$krav->fristillelsesdato], 'string' );		
		}
		
		if ($krav->påfølgende <= $til) {
			settype( $datoer[$krav->påfølgende], 'string' );		
		}
	}
	
	// Loop gjennom resultatet andre gang
	//	for å beregne utleiegrad per dato.
	//	Den høyeste verdien returneres som utleiegrad
	foreach( $datoer as $dato => &$andel) {
		foreach( $aktuelle->data as $krav) {
			$utleie = $this->fraBrøk( $krav->andel );
			
			if( $krav->fom <= $dato and $krav->tom >= $dato ) {
				$andel = bcadd( $andel, $utleie, 12);
			}
		}
	}
	
	// Sorter kravene for oppsagte leieforhold
	//	først etter utløp av oppsigelsestiden,
	//	og deretter etter utflyttingsdato
	if( !usort(
		$resultat->oppsigelsestid, array( $this->leiebase, 'sammenliknKravsOppsigelser' )
	)) {
		throw new Exception("Klarte ikke sortere kravene i oppsagte leieforhold");
	}
	
	$resultat->grad = round(max($datoer),10);
	$resultat->ledig = max(0, round(bcsub("1", max($datoer), 12), 10));
	
	return $resultat;
}



// Hent Leieforhold og utleiegrad for et bestemt tidsrom
/****************************************/
//	$param
//		fra:	(DateTime eller 'Y-m-d'-streng) starten på tidsrommet utleien skal returneres for
//		til:	(DateTime eller 'Y-m-d'-streng), normalt null (=uendelighet), slutten på tidsrommet utleien skal returneres for
//		seBortFraLeieforhold:	(Leieforhold-objekt eller heltall), evt. leieforhold som skal utelates
//	--------------------------------------
//	resultat: stdClass-objekt med egenskapene:
//		faktiskeLeieforhold: liste med Leieforhold-objekter eksklusive avsluttede leieforhold
//		proFormaLeieforhold: liste med Leieforhold-objekter inklusive oppsigelsestid
//		grad: (desimalstreng med 12 desimaler) Hvor stor del av leieobjektet som er utleid
//		ledig: (desimalstreng med 12 desimaler) Hvor stor del av leieobjektet som står ledig
public function hentUtleie($fra, $til = null, $seBortFraLeieforhold = null) {
	$tp = $this->mysqli->table_prefix;
	$resultat = (object)array(
		'faktiskeLeieforhold'	=> array(),
		'proFormaLeieforhold'	=> array(),
		'grad'					=> null,
		'ledig'					=> null
	);
	
	// Gjør om datoene til strenger
	if( $fra instanceof DateTime ) {
		$fra = $fra->format('Y-m-d');
	}
	settype( $fra, 'string' );
	if( $til instanceof DateTime ) {
		$til = $til->format('Y-m-d');
	}
	else if( !strtotime($til) ) {
		$til = '';
	}
	settype( $til, 'string' );
	settype( $seBortFraLeieforhold, 'string' );
	
	
	// Hent alle leieforhold som spenner over det aktuelle tidsrommet
	$aktuelle = $this->mysqli->arrayData(array(
		'returnQuery'	=> true,
		'source'	=> "{$tp}kontrakter AS kontrakter\n"
					.   "LEFT JOIN {$tp}oppsigelser AS oppsigelser ON kontrakter.leieforhold = oppsigelser.leieforhold\n",
		'fields'		=> "kontrakter.leieforhold,\n"
						.	"MIN(fradato) AS fradato,\n"
						.	"MAX(kontrakter.andel) AS andel,\n"
						.	"DATE_SUB(oppsigelser.fristillelsesdato, INTERVAL 1 DAY) AS tildato,\n"
						.	"oppsigelser.fristillelsesdato,\n"
						.	"oppsigelser.oppsigelsestid_slutt\n",
		'groupfields'	=> "kontrakter.leieforhold,\n"
						.	"oppsigelser.fristillelsesdato,\n"
						.	"oppsigelser.oppsigelsestid_slutt",
		'orderfields'	=> "MIN(fradato) ASC\n",
		'where'			=> "kontrakter.leieobjekt = '{$this->id}'\n"
						.	"AND kontrakter.leieforhold != '" . (int)$seBortFraLeieforhold . "'\n"
						.	"AND kontrakter.kontraktnr = kontrakter.leieforhold\n"
						.	($til ? "AND kontrakter.fradato <= '$til'\n" : "")
						.	"AND (oppsigelser.oppsigelsestid_slutt > '$fra' OR oppsigelser.oppsigelsestid_slutt IS NULL)\n"
	));
	
	// Loop gjennom resultatet første gang
	//	for å opprette Leieforhold-objektene
	//	og for å fastsette datoer hvor det kan være endringer i utleiegraden
	$datoer = array();
	settype( $datoer[$fra], 'string' );

	foreach( $aktuelle->data as $lf) {
	
		// Putt leieforholdet i beholderne proFormaLeieforhold og evt faktiskeLeieforhold
		$leieforhold = new Leieforhold( $lf->leieforhold );
		$resultat->proFormaLeieforhold[] = $leieforhold;
		
		if ($lf->tildato >= $fra or !$lf->tildato ) {
			// Leieobjektet ble ikke oppsagt før det angitte tidsrommet
			$resultat->faktiskeLeieforhold[] = $leieforhold;
		}

		// Legg kontrolldatoene i $datoer
		//	Leieforholdets fradato skal taes med dersom det er innenfor det angitte tidsrommet
		$lf->fradato = max($lf->fradato, $fra);
		settype( $datoer[$lf->fradato], 'string' );

		//	Leieforholdets fristillelsesdato skal taes med dersom det er innenfor det angitte tidsrommet
		if($til and $lf->tildato) {
			$lf->tildato = min($lf->tildato, $til);
			if($lf->fristillelsesdato <= $til) {
				settype( $datoer[$lf->fristillelsesdato], 'string' );		
			}
		}
		else {
			$lf->tildato = $til;
		}

	}
	
	// Loop gjennom resultatet andre gang
	//	for å beregne utleiegrad per dato.
	//	Den høyeste verdien returneres som utleiegrad
	foreach( $datoer as $dato => &$andel) {
		foreach( $aktuelle->data as $lf) {
			$utleie = $this->fraBrøk( $lf->andel );
			
			if( $lf->fradato <= $dato and (!$lf->tildato or $lf->tildato >= $dato) ) {
				$andel = bcadd( $andel, $utleie, 12);
			}
		}
	}
	
	$resultat->grad = round(max($datoer),10);
	$resultat->ledig = max(0, round(bcsub("1", max($datoer), 12), 10));
	
	return $resultat;
}



// Oppretter et nytt leieobjekt i databasen og tildeler egenskapene til dette objektet
/****************************************/
//	$egenskaper (array/objekt) Alle egenskapene det nye objektet skal initieres med
//	--------------------------------------
public function opprett($egenskaper = array()) {
	$tp = $this->mysqli->table_prefix;
	settype( $egenskaper, 'array');
	
	$dato	= (boolean)@$egenskaper['dato'];
	
	if( $this->id ) {
		throw new Exception('Nytt Leieobjekt-objekt forsøkt opprettet, men det eksisterer allerede');
		return false;
	}
	
	if( !$dato ) {
		throw new Exception('Nytt Leieobjekt-objekt forsøkt opprettet, men mangler dato');
		return false;
	}
	
	$databasefelter = array();
	$resterendeFelter = array();
	
	foreach($egenskaper as $egenskap => $verdi) {
		switch( $egenskap ) {

		case "dato":
			if ( $verdi instanceof DateTime )
				$verdi = $verdi->format('Y-m-d');
			else if ($verdi) {
				$verdi = date('Y-m-d', strtotime($verdi));
			}
			else {
				$verdi = null;
			}

		case "navn":
		case "gateadresse":
		case "postnr":
		case "...":
			$databasefelter[$egenskap] = $verdi;
			break;

		default:
			$resterendeFelter[$egenskap] = $verdi;
			break;
		}		
	}
	
	// Hent leieobjekt-nummer som er ledig
	$leieobjektsett = $this->mysqli->arrayData(array(
		'flat'				=> true,
		'fields' =>			"{$this->tabell}.{$this->idFelt} AS id",					
		'source' => 		"{$tp}{$this->tabell} AS {$this->tabell}\n"
		
	));
	$a = 1;
	while( in_array($a, $leieobjektsett->data) ) {
		$a++;
	}
	$databasefelter[$this->idFelt] = $a;

	$this->id = $this->mysqli->saveToDb(array(
		'id'		=> $this->idFelt,
		'table'		=> "{$tp}{$this->tabell}",
		'fields'	=> $databasefelter
	))->id;
	
	if( !$this->hentId ) {
		throw new Exception('Nytt Leieobjekt forsøkt opprettet, men metoden er ennå ikke ferdigutviklet og klar for bruk');
		return false;
	}

	foreach( $resterendeFelter as $egenskap => $verdi ) {
		$this->sett($egenskap, $verdi);
	}
	
	return $this;
}



}?>