<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
Denne fila ble sist oppdatert 2016-02-01
**********************************************/

class Leieforhold extends DatabaseObjekt {

protected	$tabell = "kontrakter";	// Hvilken tabell i databasen som inneholder primærnøkkelen for dette objektet
protected	$idFelt = "leieforhold";	// Hvilket felt i tabellen som lagrer primærnøkkelen for dette objektet
protected	$data;			// DB-verdiene lagret som et objekt. Null om de ikke er lastet

protected	$delkravtyper;	// Alle delkravtyper som hører til leieforholdet. Null om de ikke er lastet

//	Efaktura og fbo bor ikke mellomlagres, fordi betalingstype skal kunne angis ved lasting
// protected	$efakturaavtale; // Evt efakturaavtale registrert på leieforholdet
// protected	$fbo; 			// Evt faste betalingsoppdrag (AvtaleGiro) registrert på leieforholdet
protected	$leietakere;	// Liste over leietakeren(e) som inngår i leieavtalen
protected	$slettedeLeietakere = array();	// Liste over leietakere som er slettet fra leieavtalen
protected	$leietakerfelt;	// Streng som lister alle leietakerne med fødsels- og personnummer
protected	$innbetalinger;	// Liste med StdClass-objekter med innbetalingsobjekt, DateTime-objekt og delbeløp
protected	$krav;			// Alle betalingskrav i leieforholdet
protected	$leiekrav;		// Alle husleiekrav som er opprettet i leieavtalen
protected	$navn;			// Navn på leietakeren(e) som inngår i leieavtalen
protected	$kortnavn;		// Forkortet navn på leietakeren(e)
protected	$adresse;		// stdClass-objekt med adresseelementene
protected	$adressefelt;	// Adressefelt for utskrift
protected	$brukerepost;	// Liste over brukerepostadresser
protected	$oppsigelse;	// stdClass-objekt med oppsigelse. False dersom ikke oppsagt, null dersom ikke lastet
protected	$fremtidigeKrav;	// Array over alle registrerte fremtidige krav. Null om de ikke er lastet
protected	$ubetalteKrav;	// Array over alle ubetalte krav. Null om de ikke er lastet
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


/*	Callback-funksjoner for bruk i preg_replace_callback()
Avtaleteksten returneres utfylt med variabler.
*****************************************/
//	$treff (array):	Treffene som er sendt fra preg_replace_callback()
//	--------------------------------------
//	retur: (streng) Ny vtaleteksten

protected function callBackForHverLeietaker( $treff ) {
	$resultat = array();
	$malDelkrav = $treff[1];
	
	$variablerLeietakere = array(
		'{leietaker.id}',
		'{leietaker.navn}',
		'{leietaker.postadresse}',
		'{leietaker.fødselsdato}',
		'{leietaker.fødselsnummer}',
		'{leietaker.epost}'
	);
	
	foreach( $this->hent('leietakere') as $leietaker ) {
		$erstatning = array(
			$leietaker->hent('id'),
			$leietaker->hent('navn'),
			nl2br( $leietaker->hent('postadresse') ),
			(
				$leietaker->hent('fødselsdato')
				? $leietaker->hent('fødselsdato')->format('d.m.Y')
				: ""
			),
			$leietaker->hent('fødselsnummer'),
			$leietaker->hent('epost')
		);
	
		$resultat[] = str_replace($variablerDelkrav, $erstatning, $malDelkrav);
	}
	return implode('', $resultat);
}

protected function callBackForHvertDelkrav( $treff ) {
	$resultat = array();
	$malDelkrav = $treff[1];
	
	$variablerDelkrav = array(
		'{delkrav.id}',
		'{delkrav.kode}',
		'{delkrav.navn}',
		'{delkrav.beskrivelse}',
		'{delkrav.beløp}'
	);
	
	foreach( $this->hent('delkravtyper') as $delkravtype ) {
		if( !$delkravtype->selvstendig_tillegg ) {
			$erstatning = array(
				$delkravtype->id,
				$delkravtype->kode,
				$delkravtype->navn,
				$delkravtype->beskrivelse,
				$this->leiebase->kr($delkravtype->beløp)
			);
		
			$resultat[] = str_replace($variablerDelkrav, $erstatning, $malDelkrav);
		}
	}
	return implode('', $resultat);
}

protected function callBackForHvertTillegg( $treff ) {
	$resultat = array();
	$malDelkrav = $treff[1];
	
	$variablerDelkrav = array(
		'{tillegg.id}',
		'{tillegg.kode}',
		'{tillegg.navn}',
		'{tillegg.beskrivelse}',
		'{tillegg.beløp}'
	);
	
	foreach( $this->hent('delkravtyper') as $delkravtype ) {
		if( $delkravtype->selvstendig_tillegg ) {
			$erstatning = array(
				$delkravtype->id,
				$delkravtype->kode,
				$delkravtype->navn,
				$delkravtype->beskrivelse,
				$this->leiebase->kr($delkravtype->beløp)
			);
		
			$resultat[] = str_replace($variablerDelkrav, $erstatning, $malDelkrav);
		}
	}
	return implode('', $resultat);
}

protected function callBackForDersomBad( $treff ) {
	return $this->hent('leieobjekt')->hent('bad') ? $treff[1] : "";
}

protected function callBackForDersomIkkeBad( $treff ) {
	return !$this->hent('leieobjekt')->hent('bad') ? $treff[1] : "";
}

protected function callBackForDersomBofellesskap( $treff ) {
	return ( $this->hent('andel') != 1 ) ? $treff[1] : "";
}

protected function callBackForDersomIkkeBofellesskap( $treff ) {
	return ( $this->hent('andel') == 1 ) ? $treff[1] : "";
}

protected function callBackForDersomTidsbestemt( $treff ) {
	return ( $this->hent('tildato') ) ? $treff[1] : "";
}

protected function callBackForDersomIkkeTidsbestemt( $treff ) {
	return ( !$this->hent('tildato') ) ? $treff[1] : "";
}

protected function callBackForDersomOppsigelsestid( $treff ) {
	return ( $this->leiebase->periodeformat($this->hent('oppsigelsestid'), true) != "P0M") ? $treff[1] : "";
}

protected function callBackForDersomIkkeOppsigelsestid( $treff ) {
	return ( $this->leiebase->periodeformat($this->hent('oppsigelsestid'), true) == "P0M") ? $treff[1] : "";
}

protected function callBackForDersomFornyelse( $treff ) {
	return ( $this->hent('kontraktnr') != $this->hentId() ) ? $treff[1] : "";
}

protected function callBackForDersomIkkeFornyelse( $treff ) {
	return ( $this->hent('kontraktnr') == $this->hentId() ) ? $treff[1] : "";
}



// Last leieforholdets kjernedata fra databasen
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
		
		'fields' =>		"{$this->tabell}.leieforhold AS id,\n"
					.	"{$this->tabell}.leieforhold,\n"
					.	"{$this->tabell}.kontraktnr,\n"
					.	"{$this->tabell}.tekst,\n"
					.	"{$this->tabell}.andel,\n"
					.	"{$this->tabell}.leieobjekt,\n"
					.	"{$this->tabell}.fradato,\n"
					.	"{$this->tabell}.tildato,\n"
					.	"{$this->tabell}.oppsigelsestid,\n"
					.	"{$this->tabell}.årlig_basisleie,\n"
					.	"{$this->tabell}.ant_terminer,\n"
					.	"{$this->tabell}.frosset,\n"
					.	"{$this->tabell}.stopp_oppfølging,\n"
					.	"{$this->tabell}.avvent_oppfølging,\n"
					.	"{$this->tabell}.regningsperson,\n"
					.	"{$this->tabell}.regning_til_objekt,\n"
					.	"{$this->tabell}.regningsobjekt,\n"
					.	"{$this->tabell}.regningsadresse1,\n"
					.	"{$this->tabell}.regningsadresse2,\n"
					.	"{$this->tabell}.postnr,\n"
					.	"{$this->tabell}.poststed,\n"
					.	"{$this->tabell}.land\n",
						
		'source' => 		"{$tp}{$this->tabell} AS {$this->tabell}\n",
						
		'where'			=>	"{$tp}{$this->tabell}.{$this->idFelt} = '$id'",
		
		'orderfields'	=>	"{$this->tabell}.kontraktnr DESC"
	));
	if( $resultat->totalRows ) {
		$this->data = $resultat->data[0];
		$this->id = $id;
		
		foreach($resultat->data as $kontrakt) {
			$this->data->kontrakter[$kontrakt->kontraktnr] = (object)array(
				'dato'			=> new DateTime($kontrakt->fradato),
				'tildato'		=> ($kontrakt->tildato ? new DateTime($kontrakt->tildato) : null),
				'kontraktnr'	=> $kontrakt->kontraktnr,
				'tekst'			=> $kontrakt->tekst
			);
		}
		
		$this->data->fradato = new DateTime( end($resultat->data)->fradato );

		$this->data->leieobjekt = $this->leiebase->hent('Leieobjekt', $this->data->leieobjekt );
		
		$this->data->oppsigelsestid = new DateInterval( $this->data->oppsigelsestid );
		
		if( $this->data->andel == '100%' or $this->data->andel == '1/1') {
			$this->data->andel = "1";
		}

		if( $this->data->regning_til_objekt ) {
			$this->data->regningsobjekt = $this->leiebase->hent('Leieobjekt', $this->data->regningsobjekt );
		}
		else {
			$this->data->regningsobjekt = null;
		}

		if( $this->data->regningsperson ) {
			$this->data->regningsperson = $this->leiebase->hent('Person', $this->data->regningsperson );
		}
		else {
			$this->data->regningsperson = null;
		}

		if( $this->data->tildato ) {
			$this->data->tildato = new DateTime( $this->data->tildato );
		}
		else {
			$this->data->tildato = null;
		}
		if( $this->data->avvent_oppfølging ) {
			$this->data->avvent_oppfølging
				= new DateTime( $this->data->avvent_oppfølging );
		}

		if(!$this->data->ant_terminer) {
			$this->data->ant_terminer = 1;
		}
		switch($this->data->ant_terminer) {
		case 1:
			$this->data->terminlengde = new DateInterval("P1Y");
			break;
		case 2:
		case 3:
		case 4:
		case 6:
		case 12:
			$this->data->terminlengde = new DateInterval("P" . (12 / $this->data->ant_terminer) . "M");
			break;
		case 13:
		case 26:
		case 52:
			$this->data->terminlengde = new DateInterval("P" . (364 / $this->data->ant_terminer) . "D");
		default:
			$this->data->terminlengde = new DateInterval("P" . round(365 / $this->data->ant_terminer) . "D");
		}

		return true;
	}
	else {
		$this->id = null;
		$this->data = null;
		return false;
	}

}



// Last adressefeltet
/****************************************/
//	--------------------------------------
protected function lastAdressefelt() {
	$tp = $this->mysqli->table_prefix;

	// Grunndetaljene, som angir adressealternativene, må lastes først
	if ( $this->data === null ) {
		$this->last();
	}		
	// Leietakerne må også lastes
	if ( $this->leietakere === null ) {
		$this->lastLeietakere();
	}		
	
	// Opprett adresseobjektet
	$this->adresse = (object)array(
		'navn'		=> null,
		'adresse1'	=> null,
		'adresse2'	=> null,
		'postnr'	=> null,
		'poststed'	=> null,
		'land'		=> null
	);
	
	$adressefelt = "";
	
	if( $this->data->regning_til_objekt ) {
		$leieobjekt = $this->hent('leieobjekt');

		if( $leieobjekt->hent('navn') ) {
			$this->adresse->adresse1 = $leieobjekt->hent('navn');
			$this->adresse->adresse2 = $leieobjekt->hent('gateadresse');
		}
		else {
		}

		$this->adresse->navn = $this->hent('navn');
		$this->adresse->postnr = $leieobjekt->hent('postnr');
		$this->adresse->poststed = $leieobjekt->hent('poststed');
		$this->adresse->land = "";
		$adressefelt = $leieobjekt->hent('adresse');
	}
	
	else if( $this->data->regningsperson ) {
		$this->adresse->navn = $this->data->regningsperson->hent('navn');
		$this->adresse->adresse1 = $this->data->regningsperson->hent('adresse1');
		$this->adresse->adresse2 = $this->data->regningsperson->hent('adresse2');
		$this->adresse->postnr = $this->data->regningsperson->hent('postnr');
		$this->adresse->poststed = $this->data->regningsperson->hent('poststed');
		$this->adresse->land = $this->data->regningsperson->hent('land');
		$adressefelt = $this->data->regningsperson->hent('postadresse');
	}
	
	else if( !$this->data->regningsperson ) {
		$this->adresse->navn = $this->hent('navn');
		$this->adresse->adresse1 = $this->data->regningsadresse1;
		$this->adresse->adresse2 = $this->data->regningsadresse2;
		$this->adresse->postnr = $this->data->postnr;
		$this->adresse->poststed = $this->data->poststed;
		$this->adresse->land = $this->data->land;
		$adressefelt .= (
			$this->data->regningsadresse1
			? "{$this->data->regningsadresse1}\n"
			: ""
		);
		$adressefelt .= (
			$this->data->regningsadresse2
			? "{$this->data->regningsadresse2}\n"
			: ""
		);
		$adressefelt .= "{$this->data->postnr} {$this->data->poststed}\n";
		$adressefelt .= (
			($this->data->land != "Norge" && $this->data->land != "")
			? "{$this->data->land}"
			: ""
		);
	}
	
	else {
		$regningsperson = $this->leietakere[ $this->data->regningsperson ];
		$this->adresse->navn =	$regningsperson->hent('navn');
		$this->adresse->adresse1 = $regningsperson->hent('adresse1');
		$this->adresse->adresse2 = $regningsperson->hent('adresse2');
		$this->adresse->postnr = $regningsperson->hent('postnr');
		$this->adresse->poststed = $regningsperson->hent('poststed');
		$this->adresse->land = $regningsperson->hent('land');
		$adressefelt .= $regningsperson->hent('postadresse');
	}
	
	$this->adressefelt = $adressefelt;


}



// Last alle betalinger i leieforholdet
/****************************************/
//	--------------------------------------
protected function lastBetalinger() {
	$tp = $this->mysqli->table_prefix;
	$resultat = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}innbetalinger AS innbetalinger\n",
		'distinct'		=> true,
		'fields'		=> "innbetalinger.innbetaling, innbetalinger.dato, SUM(innbetalinger.beløp) AS beløp",
		'orderfields'	=> "innbetalinger.dato, innbetalinger.ref",
		'groupfields'	=> "innbetalinger.innbetaling",
		'where'			=> "innbetalinger.konto != '0'\n"
						.	"AND innbetalinger.leieforhold = '{$this->hentId()}'"
	));
	
	foreach($resultat->data as $delbeløp) {
		$delbeløp->innbetaling = $this->leiebase->hent('Innbetaling', $delbeløp->innbetaling);		
		$delbeløp->dato = new DateTime("{$delbeløp->dato} 00:00:00");		
	}
	
	$this->innbetalinger = $resultat->data;
}


// Last epostadressen for alle brukerne som har adgang til leieforholdet
/****************************************/
//	--------------------------------------
protected function lastBrukerepost() {
	$tp = $this->mysqli->table_prefix;
	$this->brukerepost = (object)array(
		'generelt' => array(),
		'innbetalingsbekreftelse' => array(),
		'forfallsvarsel' => array()
	);
	
	$resultat = $this->mysqli->arrayData(array(
		'source'	=> "{$tp}adganger AS adganger INNER JOIN {$tp}personer AS personer ON adganger.personid = personer.personid",
		'where'		=> "adganger.leieforhold = '{$this->hentId()}' AND epostvarsling AND adgang = 'beboersider'",
		'fields'	=> "adganger.innbetalingsbekreftelse,
						adganger.forfallsvarsel,
						personer.fornavn,
						personer.etternavn,
						personer.er_org,
						personer.epost"
	));
	foreach( $resultat->data as $person ) {
		$adresse = ($person->er_org ? $person->etternavn : "{$person->fornavn} {$person->etternavn}" ) . " <{$person->epost}>";
		$this->brukerepost->generelt[] = $adresse;
		if( $person->innbetalingsbekreftelse ) {
			$this->brukerepost->innbetalingsbekreftelse[] = $adresse;
		}
		if( $person->forfallsvarsel ) {
			$this->brukerepost->forfallsvarsel[] = $adresse;
		}
	}
}



// Last alle delkravtyper som er finnes på leieforholdet
/****************************************/
//	--------------------------------------
protected function lastDelkravtyper() {
	$tp = $this->mysqli->table_prefix;
	$this->delkravtyper = $this->mysqli->arrayData(array(
		'returnQuery'	=> true,
		'source'	=> "{$tp}delkravtyper AS delkravtyper\n"
					.	"INNER JOIN {$tp}leieforhold_delkrav AS leieforhold_delkrav ON delkravtyper.id = leieforhold_delkrav.delkravtype",
		'fields'	=> "delkravtyper.id AS id,\n"
					.	"delkravtyper.navn AS navn,\n"
					.	"delkravtyper.kode AS kode,\n"
					.	"delkravtyper.kravtype AS kravtype,\n"
					.	"leieforhold_delkrav.selvstendig_tillegg AS selvstendig_tillegg,\n"
					.	"delkravtyper.beskrivelse AS beskrivelse,\n"
					.	"leieforhold_delkrav.relativ AS relativ,\n"
					.	"leieforhold_delkrav.sats AS sats,\n"
					.	"ROUND(IF(leieforhold_delkrav.relativ, (leieforhold_delkrav.sats * '" . $this->hent('årlig_basisleie') . "'), (leieforhold_delkrav.sats))) AS beløp\n",
		'where'		=> "leieforhold_delkrav.leieforhold = '{$this->hentId()}'"
	))->data;
}


// Last evt betalingskrav i leieforholdet
/****************************************/
//	--------------------------------------
protected function lastKrav() {
	$tp = $this->mysqli->table_prefix;
	$resultat = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}krav AS krav\n"
						.	"INNER JOIN {$tp}kontrakter AS kontrakter ON kontrakter.kontraktnr = krav.kontraktnr",
		'fields'		=> "krav.id AS id",
		'orderfields'	=> "krav.kravdato, krav.id",
		'class'			=> "Krav",
		'where'			=> "kontrakter.leieforhold = '{$this->hentId()}'"
	));
	
	$this->krav = $resultat->data;
	$this->leiekrav = array();
	
	foreach($this->krav as $krav) {
		if($krav->hent('type') == "Husleie") {
			$this->leiekrav[] = $krav;
		}
	}
}


// Last leietakerne
/****************************************/
//	$param
//	--------------------------------------
protected function lastLeietakere() {
	$tp = $this->mysqli->table_prefix;

	if ( !$id = $this->id ) {
		$this->navn = null;
		return false;
	}

	$leietakere = $this->mysqli->arrayData(array(
		'returnQuery'	=> true,
		
		'class'	=>			"Person",
		'fields' =>			"kontraktpersoner.person AS id, kontraktpersoner.slettet, kontraktpersoner.leietaker\n",
		'orderfields' =>	"kontraktpersoner.kopling ASC\n",
		'source' => 		"{$tp}kontraktpersoner AS kontraktpersoner\n",
		'where'			=>	"kontraktpersoner.kontrakt = '{$this->hent('kontraktnr')}'"
	));
	
	$ant = $leietakere->totalRows;
	$kortnavn = array();
	$navn = array();
	$this->leietakere = array();
	$this->slettedeLeietakere = array();
	$this->leietakerfelt = "";
	foreach( $leietakere->data as $person ) {
	
		// Dersom personen fortsatt er leietaker og har adressekort
		if ( !$person->slettet && ($personid = $person->hent('id')) ) {
			$this->leietakere[ $personid ] = $person;
		
			$navn[]	= $person->hent('navn');
			
			$fødselsnr = $person->hent('fødselsnummer');
			$fødselsdato = $person->hent('fødselsdato');
			$this->leietakerfelt .= $person->hent('navn')
			.	(
					$fødselsnr
					? " f.nr.&nbsp;{$fødselsnr}"
					: (
						$fødselsdato
						? " f.&nbsp;{$fødselsdato->format('d.m.Y')}"
						: ""
					)
				)
			. "<br />\n";
		}
		
		// Dersom personen fortsatt er leietaker, men mangler adressekort
		else if ( !$person->slettet ) {
			$navn[]	= $person->leietaker;
			$this->leietakerfelt .= "{$person->leietaker}<br />\n";
		}
		
		// Dersom personen er slettet som leietaker, og har adressekort
		else if ( $person->slettet  && ($personid = $person->hent('id'))) {
			$this->slettedeLeietakere[] = $person;
			
			$this->leietakerfelt .= "<del>";
			$this->leietakerfelt .= $person->hent('navn')
			. "</del> Slettet&nbsp;" . date('d.m.Y', strtotime( $person->slettet )) . "<br />\n";
		}
		
		// Dersom personen er slettet som leietaker, og mangler adressekort
		else {
			$this->leietakerfelt .= "<del>{$person->leietaker}</del><br />\n";
		}
	}
	
	$this->navn = "";
	$this->kortnavn = "";
	$ant = count( $navn );

	foreach( $navn as $nr => $verdi ) {
		$this->navn .= $verdi;
		if($nr < $ant-2) $this->navn .= ", ";
		if($nr == $ant-2) $this->navn .= " og ";

		$this->kortnavn .= mb_substr($verdi, 0, (int)((11-$ant) / $ant), 'UTF-8');
		if($nr <= $ant-2) {
			$this->kortnavn .= "&";
		}
	}
}


// Laster evt. oppsigelse
/****************************************/
//	--------------------------------------
protected function lastOppsigelse() {
	$tp = $this->mysqli->table_prefix;
	if ( !$id = $this->id ) {
		$this->oppsigelse = false;
		return false;
	}

	$resultat = $this->mysqli->arrayData(array(
		'returnQuery'	=> true,
		'fields'		=>	"oppsigelsesdato, fristillelsesdato, oppsigelsestid_slutt, ref, merknad, oppsagt_av_utleier\n",
		'source'		=> 	"{$tp}oppsigelser AS oppsigelser\n",
		'where'			=>	"{$tp}oppsigelser.{$this->idFelt} = '$id'"
	));

	if( $resultat->totalRows ) {
		$this->oppsigelse = $resultat->data[0];
		$this->oppsigelse->oppsigelsesdato = new DateTime( $this->oppsigelse->oppsigelsesdato . " 00:00:00" );
		$this->oppsigelse->fristillelsesdato = new DateTime( $this->oppsigelse->fristillelsesdato . " 00:00:00" );
		$this->oppsigelse->oppsigelsestidSlutt = new DateTime( $this->oppsigelse->oppsigelsestid_slutt . " 00:00:00" );
		$this->oppsigelse->oppsagtAvUtleier = (bool)$this->oppsigelse->oppsagt_av_utleier;
	}
	
	else {
		$this->oppsigelse = false;
	}
	return true;
}


// Last alle ubetalte krav fra databasen
/****************************************/
//	$param
//		id	(heltall) gironummeret	
//	--------------------------------------
protected function lastFremtidigeKrav() {
	$tp = $this->mysqli->table_prefix;
	if ( !$id = $this->id ) {
		$this->fremtidigeKrav = null;
		return false;
	}

	$resultat = $this->mysqli->arrayData(array(
		'returnQuery'	=> true,
		'class' 		=>	"Krav",
		'fields'		=>	"krav.id\n",
		'orderfields'	=>	"krav.kravdato, krav.id",
		'source'		=> 	"{$tp}krav AS krav INNER JOIN {$tp}{$this->tabell} AS {$this->tabell} ON krav.kontraktnr = {$this->tabell}.kontraktnr\n",
		'where'			=>	"{$this->tabell}.{$this->idFelt} = '$id'
							AND krav.kravdato > NOW()"
	));

	$this->fremtidigeKrav = $resultat->data;

}


// Last alle ubetalte krav fra databasen
/****************************************/
//	$param
//		id	(heltall) gironummeret	
//	--------------------------------------
protected function lastUbetalteKrav() {
	$tp = $this->mysqli->table_prefix;
	if ( !$id = $this->id ) {
		$this->ubetalteKrav = null;
		return false;
	}

	$resultat = $this->mysqli->arrayData(array(
		'returnQuery'	=> true,
		'class' 		=>	"Krav",
		'fields'		=>	"krav.id\n",
		'orderfields'	=>	"krav.kravdato, krav.id",
		'source'		=> 	"{$tp}krav AS krav INNER JOIN {$tp}{$this->tabell} AS {$this->tabell} ON krav.kontraktnr = {$this->tabell}.kontraktnr\n",
		'where'			=>	"{$this->tabell}.{$this->idFelt} = '$id'
							AND krav.utestående > 0
							AND krav.kravdato <= NOW()"
	));

	$this->ubetalteKrav = $resultat->data;

}


// Last utskriftsposisjonen for leieforholdet
/****************************************/
//	$param
//		rute	(heltall) utdelingsruten som bestemmer utskriftsrekkefølgen
//	--------------------------------------
protected function lastUtskriftsposisjon($rute = null) {
	$tp = $this->mysqli->table_prefix;
	
	if( $rute === null ) {
		$rute = $this->leiebase->valg['utdelingsrute'];
	}
	settype($rute, 'integer');
	if ( !$id = $this->id ) {
		$this->utskriftsposisjon = array();
		return false;
	}
	
	$this->utskriftsposisjon[$rute] = intval((string)$this->hentId());
	
	if( $this->hent('regning_til_objekt') ) {
		$posisjon = $this->mysqli->arrayData(array(
			'returnQuery'	=> true,
		
			'fields' =>			"utdelingsorden.plassering\n",
			'source' => 		"{$tp}utdelingsorden AS utdelingsorden\n",
			'where'			=>	"{$tp}utdelingsorden.leieobjekt = '{$this->hent('regningsobjekt')}' AND rute = '{$rute}'"
		));
		if( $posisjon->totalRows ) {
			$this->utskriftsposisjon[$rute] = 1000000 * $posisjon->data[0]->plassering
			+ (string)$this->hentId();
		}
	}
}


// Oppdater terminleiebeløpet i databasen.
//	Denne operasjonen må kjøres hver gang leiebeløpet, antall terminer eller
//	delkravene har blitt endret.
/****************************************/
//	--------------------------------------
protected function oppdaterLeie() {
	$tp = $this->mysqli->table_prefix;
	$this->nullstill();
	
	$årsleie = $this->hent('årlig_basisleie');

	foreach($this->hent('delkravtyper') AS $delkravtype) {
		if($delkravtype->kravtype == "Husleie" and !$delkravtype->selvstendig_tillegg) {
			$årsleie += $delkravtype->beløp;
		}
	}
	return $this->mysqli->saveToDb(array(
		'update'	=> true,
		'table'		=> "{$tp}{$this->tabell} as {$this->tabell}",
		'where'		=> "{$this->tabell}.{$this->idFelt} = '{$this->id}'",
		'fields'	=> array(
			"{$this->tabell}.leiebeløp"	=> round(bcdiv($årsleie, $this->hent('ant_terminer'), 1))
		)
	))->success;
}


/*
Registrer en oppsigelse av leieforholdet.
*****************************************/
//	$oppsigelsesdato (streng/DateTime) Datoen oppsigelsen er meldt
//	$fristillelsesdato (streng/DateTime) Datoen leieobjektet er fraflyttet og ledig
//	$oppsigelsestidSlutt (streng/DateTime) Datoen da oppsigelsestiden ikke lenger har innflytelse
//	$ref (streng) Referanse for oppsigelsen
//	$merknad (streng) Merknader knyttet til oppsigelsen
//	$oppsagtAvUtleier (boolsk, normalt feil) Om leieforholdet er oppsagt av utleier eller leietaker
//	--------------------------------------
//	retur: Boolsk suksessangivelse

public function avslutt(
	$oppsigelsesdato,
	$fristillelsesdato = null,
	$oppsigelsestidSlutt = null,
	$ref = '',
	$merknad = '',
	$oppsagtAvUtleier = false
) {
	$tp = $this->mysqli->table_prefix;

	if( !is_a( $oppsigelsesdato, 'DateTime')) {
		$oppsigelsesdato = new DateTime( $oppsigelsesdato );
	}
	
	if ( $fristillelsesdato === null ) {
		$fristillelsesdato = clone $oppsigelsesdato;
	}
	if( !is_a( $fristillelsesdato, 'DateTime')) {
		$fristillelsesdato = new DateTime( $fristillelsesdato );
	}
	
	if( $oppsigelsestidSlutt === null ) {
		$oppsigelsestidSlutt = clone $oppsigelsesdato;
		$oppsigelsestidSlutt->add( $this->hent('oppsigelsestid') );
	}
	if( !is_a( $oppsigelsestidSlutt, 'DateTime')) {
		$oppsigelsestidSlutt = new DateTime( $oppsigelsestidSlutt );
	}
	
	settype($oppsagtAvUtleier, 'boolean');
	
	// Tving fram oppdatering
	$this->oppsigelse = null;
	
	$resultat = $this->mysqli->saveToDb(array(
		'insert'	=> true,
		'table'		=> "{$tp}oppsigelser",
		'fields'	=> array(
			'kontraktnr'			=> $this->hent('kontraktnr'),
			'leieforhold'			=> $this->hentId(),
			'oppsigelsesdato'		=> $oppsigelsesdato->format('Y-m-d'),
			'fristillelsesdato'		=> $fristillelsesdato->format('Y-m-d'),
			'oppsigelsestid_slutt'	=> $oppsigelsestidSlutt->format('Y-m-d'),
			'ref'					=> $ref,
			'merknad'				=> $merknad,
			'oppsagt_av_utleier'	=> $oppsagtAvUtleier
		)
	))->success;
	
	// Opprett manglende leiekrav for resten av leieperioden
	$this->opprettLeiekrav( $fristillelsesdato, true );
	
	return $resultat;
}



// Gjengivelse av leieforholdet, f.eks i en rapport
/****************************************/
//	$mal (streng): Malen som skal brukes for gjengivelse
//	$param (array) nødvendige parametere
//	--------------------------------------
public function gjengi($mal, $param = array()) {
	settype( $param, 'array');
	$leiebase = $this->leiebase;
	
	switch($mal) {

	case "epost_forfallsvarsel_html":
	{
		//	Parametere
		//	$krav (array) Kravene som er i ferd med å forfalle
		
		$giroer = array();
		$sum = 0;
		
		// Gå gjennom alle oversendte krav for å gruppere disse giro for giro
		foreach( $param['krav'] as $krav ) {
			if( $krav->hent('leieforhold')->hentId() == $this->id ) {
				$giro = $krav->hent('giro');

				if( $giro ) {
					$fboTrekkrav = $giro->hent('fboTrekkrav');
				}
				else {
					$fboTrekkrav = false;
				}
			
				if( !isset($giroer[ (int)strval($giro) ]) ) {
					$giroer[ (int)strval($giro) ] = (object)array(
						'nr'			=> "{$giro}",
						'uteståendeTall'=> 0,
						'utestående'	=> '',
						'kravsett'		=> array(),
					);
				}
				
				$giroer[ (int)strval($giro) ]->kravsett[] = (object)array(
					'forfall'		=> $krav->hent('forfall') ? $krav->hent('forfall')->format('d.m.Y') : "",
					'tekst'			=> $krav->hent('tekst'),
					'beløp'			=> $this->leiebase->kr($krav->hent('beløp')),
					'utestående'	=> $this->leiebase->kr($krav->hent('utestående')),
					'kid'			=> ($giro ? $giro->hent('kid') : ''),
					'ag'			=> $fboTrekkrav ? "Beløpet trekkes med AvtaleGiro." : ''
				);
			
				$giroer[ (int)strval($giro) ]->uteståendeTall += $krav->hent('utestående');
				$giroer[ (int)strval($giro) ]->utestående
					= $this->leiebase->kr( $giroer[(int)strval($giro)]->uteståendeTall );
				$sum += $krav->hent('utestående');
			}
		}
		
		$this->gjengivelsesdata = array(
			'leiebase'			=> $this->leiebase,
			
			'logotekst'			=> $this->leiebase->valg['utleier'],
			'leieforholdnr'		=> $this->hentId(),
			'leieforholdbeskrivelse'	=> $this->hent('beskrivelse'),
			'giroer'			=> $giroer,
			'sum'				=> $this->leiebase->kr($sum),
			'fbo'				=> $this->hent('fbo'),
			'bankkonto'			=> $this->leiebase->valg['bankkonto'],
			'ocr'				=> $this->leiebase->valg['ocr'],
			'fastKid'			=> $this->hent('kid'),
			'bunntekst'			=> $this->leiebase->valg['eposttekst']
		);

		break;
	}


	case "epost_forfallsvarsel_txt":
	{
		//	Parametere
		//	$krav (array) Kravene som er i ferd med å forfalle
		
		$giroer = array();
		$sum = 0;
		
		// Gå gjennom alle oversendte krav for å gruppere disse giro for giro
		foreach( $param['krav'] as $krav ) {
			if( $krav->hent('leieforhold')->hentId() == $this->id ) {
				$giro = $krav->hent('giro');

				if( $giro ) {
					$fboTrekkrav = $giro->hent('fboTrekkrav');
				}
				else {
					$fboTrekkrav = false;
				}
			
			
				if( !isset($giroer[ (int)strval($giro) ]) ) {
					$giroer[ (int)strval($giro) ] = (object)array(
						'nr'			=> "{$giro}",
						'uteståendeTall'=> 0,
						'utestående'	=> '',
						'kravsett'		=> array(),
					);
				}
				
				$giroer[ (int)strval($giro) ]->kravsett[] = (object)array(
					'forfall'		=> $krav->hent('forfall') ? $krav->hent('forfall')->format('d.m.Y') : "",
					'tekst'			=> $krav->hent('tekst'),
					'beløp'			=> $this->leiebase->kr($krav->hent('beløp'), false),
					'utestående'	=> $this->leiebase->kr($krav->hent('utestående'), false),
					'kid'			=> ($giro ? $giro->hent('kid') : ''),
					'ag'			=> $fboTrekkrav ? "Beløpet trekkes med AvtaleGiro." : ''
				);
			
				$giroer[ (int)strval($giro) ]->uteståendeTall += $krav->hent('utestående');
				$giroer[ (int)strval($giro) ]->utestående
					= $this->leiebase->kr( $giroer[(int)strval($giro)]->uteståendeTall );
				$sum += $krav->hent('utestående');
			}
		}
		
		$this->gjengivelsesdata = array(
			'leiebase'			=> $this->leiebase,
			
			'logotekst'			=> $this->leiebase->valg['utleier'],
			'leieforholdnr'		=> $this->hentId(),
			'giroer'			=> $giroer,
			'sum'				=> $this->leiebase->kr($sum, false),
			'fbo'				=> $this->hent('fbo'),
			'bankkonto'			=> $this->leiebase->valg['bankkonto'],
			'ocr'				=> $this->leiebase->valg['ocr'],
			'fastKid'			=> $this->hent('kid'),
			'bunntekst'			=>  strip_tags( str_ireplace(
										array("<br />","<br>","<br/>"),
										"\r\n",
										$this->leiebase->valg['eposttekst']
									) )
		);

		break;
	}


	case "epost_kontraktvarsel_html":
	{
		//	Parametere
		//	$kontraktnr (heltall) (valgfritt) Kontrakten som skal oversendes. Normalt siste kontrakt
		
		$kontrakter = $this->hent('kontrakter');
		
		if($param['kontraktnr']) {
			$kontrakt = $kontrakter[ $param['kontraktnr'] ];
		}
		if( !@$kontrakt ) {
			$kontrakt = end( $kontrakter );
		}
		
		$this->gjengivelsesdata = array(
			'leiebase'			=> $this->leiebase,
			
			'logotekst'			=> $this->leiebase->valg['utleier'],
			'leieforholdnr'		=> $this->hentId(),
			'kontraktnr'		=> $kontrakt->kontraktnr,
			'leietakere'		=> $this->hent('navn'),
			'leieobjektbeskrivelse'	=> $this->hent('leieobjekt')->hent('beskrivelse'),
			'andel'				=> (
				($this->fraBrøk($this->hent('andel')) != 1)
				? $this->hent('andel')
				: 'Hele leieobjektet'
			),
			'fradato'			=> $kontrakt->dato->format('d.m.Y'),
			'tildato'			=> (
				$kontrakt->tildato
				? $kontrakt->tildato->format('d.m.Y')
				: null
			),
			'oppsigelsestid'	=> $this->leiebase->periodeformat($kontrakt->hent('oppsigelsestid')),
			'antallTerminer'	=> $this->hent('ant_terminer'),
			'terminbeløp'		=> $this->leiebase->kr($this->hent('leiebeløp')),
			'bankkonto'			=> $this->leiebase->valg['bankkonto'],
			'fastKid'			=> (
				$this->leiebase->valg['ocr']
				? $this->hent('kid')
				: ''
			),
			'bunntekst'			=> $this->leiebase->valg['eposttekst']
		);

		break;
	}


	case "pdf_statusoversikt":
	{
		$avsenderadresse = "{$leiebase->valg['utleier']}\n{$leiebase->valg['adresse']}\n{$leiebase->valg['postnr']} {$leiebase->valg['poststed']}";

		$gjeld = $this->mysqli->arrayData(array(
			'source' => "krav
						INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
			'fields' => "SUM(krav.utestående) AS utestående",
			'where' => "leieforhold = '{$this->hent('leieforhold')}'
						AND krav.utestående > 0
						AND krav.kravdato <= '" . date('Y-m-d') . "'"
		))->data[0]->utestående;
		
		$sisteInnbetalinger = $this->mysqli->arrayData(array(
			'source' => "innbetalinger",
			'fields' => "dato, betaler, SUM(beløp) AS beløp, ref",
			'where' => "leieforhold = '{$this->hent('leieforhold')}'
						AND dato <= " . ( isset( $param['utskriftsdato'] ) ? $param['utskriftsdato']->format('Y-m-d') : ( $this->hent('utskriftsdato') ? "'{$this->hent('utskriftsdato')->format('Y-m-d')}'" : "NOW()") ),
			'groupfields' => "dato, betaler, ref",
			'orderfields' => "dato DESC",
			'limit'	=>	"0, 3"
		))->data;


		$this->gjengivelsesdata = array(
			'avsenderAdresse'		=> $avsenderadresse,
			'mottakerAdresse'		=> ($this->hent('navn') . "\n" . $this->hent('adressefelt')),
			
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

			'efaktura'				=> (bool)$leiebase->valg['efaktura'],
			'avtalegiro'			=> (bool)$leiebase->valg['avtalegiro'],
			'efakturareferanse'		=> $this->hent('efakturareferanse'),

			'statusdato'			=> date_create(),
			
			'leieforhold'			=> $this->hent('leieforhold'),
			
			'leieforholdBeskrivelse'=> $leiebase->leieobjekt( $this->hent('leieobjekt'), true ),

			'kravsett'				=> $this->hent('ubetalteKrav'), // array
			'utestående'			=> $gjeld,
			'blankettbeløp'			=> $gjeld,
			'sisteInnbetalinger'	=> $sisteInnbetalinger
		);

		$this->gjengivelsesdata = array_merge($this->gjengivelsesdata, $param);

		// HER BEREGNES VERDIER BASERT pÅ ALLEREDE ETABLERTE DATA
		// Beregn fast KID;
		$this->gjengivelsesdata['fastKid']			= $leiebase->genererKid($this->gjengivelsesdata['leieforhold']);

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

	default: {
		break;
	}
	}
	
	$this->gjengivelsesfil = "{$mal}.php";
	return $this->_gjengi( (array)$param );
}



/*	Gjengi avtaletekst
Gjengir avtaletekst i siste leieavtale,
eller i en bestemt avtale dersom kontraktnr er oppgitt.
*****************************************/
//	$utfylt (Boolsk, normalt sann):	Avtaleteksten returneres utfylt med variabler
//	$kontraktnr (heltall eller usant, normalt usant):	Kontraktnr for avtaleteksten som skal returneres
//	$mal (streng):	Evt. ny tekst som skal fylles med kontraktverdiene
//	--------------------------------------
//	retur: (streng) Avtaleteksten

public function gjengiAvtaletekst( $utfylt = true, $kontraktnr = false, $mal = "" ) {
	$leieobjekt = $this->hent('leieobjekt');
	$delkravtyper = $this->hent('delkravtyper');
	$kontrakter = $this->hent('kontrakter');
	$kontraktdato = false;
	
	//	Dersom en bestemt kontrakt er oppgitt og tilhører dette leieforholdet,
	//	hentes tekstmalen, kontraktnummer, til- og fradato fra denne
	if($kontraktnr) {
		foreach( $kontrakter as $kontrakt ) {
			if( $kontrakt->kontraktnr == $kontraktnr ) {
				$mal = $mal ? $mal : $kontrakt->tekst;
				$kontraktdato = $kontrakt->dato;
				$tildato = $kontrakt->tildato;
			}
		}
	}
	
	// Det ble ikke funnet noen kontrakt etter angitt kontraktnummer,
	//	så tekstmalen, kontraktnummeret, til- og fradato hentes fra den siste kontrakten i leieforholdet
	if( !$kontraktdato ) {
		$kontraktnr = $this->hent('kontraktnr');
		$kontrakt = reset($kontrakter);
		$mal = $mal ? $mal : $kontrakt->tekst;
		$kontraktdato = $kontrakt->dato;
		$tildato = $kontrakt->tildato;
	}
	
	if( !$utfylt ) {
		return $mal;
	}

	$utleierfelt = "{$this->leiebase->valg['utleier']}<br />\n"
				. "{$this->leiebase->valg['adresse']}<br />\n"
				. "{$this->leiebase->valg['postnr']} {$this->leiebase->valg['poststed']}<br />\n"
				. "org. nr. {$this->leiebase->valg['orgnr']}";
				

	switch( $leieobjekt->hent('toalettkategori') ){
		case '2': $toalett = 'Leieobjektet har eget toalett.';
			break;
		case '1': $toalett = 'Det er tilgang til felles toalett i samme bygning/oppgang.';
			break;
		default:
			$toalett = 'Leieobjektet har ikke tilgang til eget toalett, eller har utedo.';
			$toalett .= $leieobjekt->hent('toalett') ? " ({$leieobjekt->hent('toalett')})" : "";
	}

	switch($this->hent('ant_terminer')) {
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
			$terminlengde = "({$this->hent('ant_terminer')} terminer per år)";
			break;
	}

	$solfondbeløp = 0;
	
	foreach( $delkravtyper as $delkravtype ) {
		if( $delkravtype->id == 1 ) {
			$solfondbeløp = $this->leiebase->kr( $delkravtype->beløp, true, false );
		}
	}
	
	
	// Lag loop for hver leietaker
	$mal = preg_replace_callback(
		'/{for hver leietaker}(.+?){\/for hver leietaker}/',
		array( $this, 'callBackForHverLeietaker' ),
		$mal
	);


	// Lag loop for hver delkravtype
	$mal = preg_replace_callback(
		'/{for hvert delkrav}(.+?){\/for hvert delkrav}/',
		array( $this, 'callBackForHvertDelkrav' ),
		$mal
	);


	// Lag loop for hvert leietillegg
	$mal = preg_replace_callback(
		'/{for hvert tillegg}(.+?){\/for hvert tillegg}/',
		array( $this, 'callBackForHvertTillegg' ),
		$mal
	);


	// Erstatt dersom-variablene
	$mal = preg_replace_callback(
		'/{dersom bad}(.+?){\/dersom bad}/',
		array( $this, 'callBackForDersomBad' ),
		$mal
	);

	$mal = preg_replace_callback(
		'/{dersom ikke bad}(.+?){\/dersom ikke bad}/',
		array( $this, 'callBackForDersomIkkeBad' ),
		$mal
	);

	$mal = preg_replace_callback(
		'/{dersom bofellesskap}(.+?){\/dersom bofellesskap}/',
		array( $this, 'callBackForDersomBofellesskap' ),
		$mal
	);

	$mal = preg_replace_callback(
		'/{dersom ikke bofellesskap}(.+?){\/dersom ikke bofellesskap}/',
		array( $this, 'callBackForDersomIkkeBofellesskap' ),
		$mal
	);

	$mal = preg_replace_callback(
		'/{dersom tidsbestemt}(.+?){\/dersom tidsbestemt}/',
		array( $this, 'callBackForDersomTidsbestemt' ),
		$mal
	);

	$mal = preg_replace_callback(
		'/{dersom ikke tidsbestemt}(.+?){\/dersom ikke tidsbestemt}/',
		array( $this, 'callBackForDersomIkkeTidsbestemt' ),
		$mal
	);

	$mal = preg_replace_callback(
		'/{dersom oppsigelsestid}(.+?){\/dersom oppsigelsestid}/',
		array( $this, 'callBackForDersomOppsigelsestid' ),
		$mal
	);

	$mal = preg_replace_callback(
		'/{dersom ikke oppsigelsestid}(.+?){\/dersom ikke oppsigelsestid}/',
		array( $this, 'callBackForDersomIkkeOppsigelsestid' ),
		$mal
	);

	$mal = preg_replace_callback(
		'/{dersom fornyelse}(.+?){\/dersom fornyelse}/',
		array( $this, 'callBackForDersomFornyelse' ),
		$mal
	);

	$mal = preg_replace_callback(
		'/{dersom ikke fornyelse}(.+?){\/dersom ikke fornyelse}/',
		array( $this, 'callBackForDersomIkkeFornyelse' ),
		$mal
	);


	$variabler = array(
		'{kontraktnr}',
		'{leieforhold}',
		'{utleier}',
		'{utleierfelt}',
		'{utleiers organisajonsnummer}',
		'{leietaker}',
		'{leietakerfelt}',
		'{andel}',
		'{bofellesskap}',
		'{leieobjekt}',
		'{leieobjektadresse}',
		'{antallrom}',
		'{areal}',
		'{bad}',
		'{toalett}',
		'{terminleie}',
		'{årsleie}',
		'{terminer}',
		'{terminlengde}',
		'{solidaritetsfondet}',
		'{er_fornyelse}',
		'{startdato}',
		'{fradato}',
		'{tildato}',
		'{oppsigelsestid}'
	);
	$erstatningstekst = array(
		$kontraktnr,
		$this->hentId(),
		$this->leiebase->valg['utleier'],
		$utleierfelt,
		$this->leiebase->valg['orgnr'],
		$this->hent('navn'),
		$this->hent('leietakerfelt') . nl2br($this->hent('adressefelt')),
		$this->fraBrøk($this->hent('andel')) < 1 ? "{$this->hent('andel')} av " : "",
		$this->fraBrøk($this->hent('andel')) < 1 ? " i bofellesskap" : "",
		strval( $leieobjekt ),
		$leieobjekt->hent('beskrivelse'),
		$leieobjekt->hent('antRom'),
		$leieobjekt->hent('areal') ? "{$leieobjekt->hent('areal')}m&#178;" : "",
		$leieobjekt->hent('bad') ? 'Leieobjektet har tilgang til dusj/bad. ' : 'Leieobjektet har ikke tilgang til dusj/bad. ',
		$toalett,
		$this->leiebase->kr( $this->hent('leiebeløp') ),
		$this->leiebase->kr( $this->hent('leiebeløp') * $this->hent('ant_terminer') ),
		$this->hent('ant_terminer'),
		$terminlengde,
		$solfondbeløp,
		$this->hentId() != $kontraktnr ? "Leieavtalen er fornyelse av tidligere leieavtaler. Leieforholdet ble påbegynt {$this->hent('fradato')->format('d.m.Y')}<br />" : "",
		$this->hent('fradato')->format('d.m.Y'),
		$kontraktdato->format('d.m.Y'),
		$tildato ? $tildato->format('d.m.Y') : "",
		$this->leiebase->periodeformat( $this->hent('oppsigelsestid') )
	);
	return str_replace($variabler, $erstatningstekst, $mal);


}


/*
Hent egenskaper
*****************************************/
//	$egenskap (streng) Egenskapen som skal hentes
//	$param (array/objekt) Objekt med ekstra parametere
//	--------------------------------------
//	retur: Angitt egenskap

public function hent($egenskap, $param = array()) {
	$tp = $this->mysqli->table_prefix;
	
	if( !$this->id ) {
		return null;
	}
	
	switch( $egenskap ) {

	case "id":
	case "andel":
	case "ant_terminer":
	case "terminlengde":
	case "avvent_oppfølging":
	case "fradato":
	case "frosset":
	case "kontrakter":
	case "kontraktnr":
	case "årlig_basisleie":
	case "leieforhold":
	case "leieobjekt":
	case "oppsigelsestid":
	case "regning_til_objekt":
	case "regningsobjekt":
	case "regningsperson":
	case "stopp_oppfølging":
	case "tildato":
	{
		if ( ( $this->data === null || @$param['oppdater'] ) and !$this->last() ) {
			return null;
		}		
		return $this->data->$egenskap;
		break;
	}

	case "krav":
	case "leiekrav":
	{
		if ( $this->krav === null || @$param['oppdater'] ) {
			$this->lastKrav();
		}		
		return $this->$egenskap;
		break;
	}

	case "innbetalinger":
	{
		/*
		Retur: stdClass objekt:
			->innbetaling: Innbetalingsobjektet
			->beløp (tall): Delbeløpet som er kreditert dette leieforholdet
			->dato	(DateTime) Dato for sortering
		*/
		if ( $this->innbetalinger === null || @$param['oppdater'] ) {
			$this->lastBetalinger();
		}
		
		if( !is_array( $this->innbetalinger ) ) {
			throw new Exception('Leieforhold::innbetalinger = ' . print_r( $this->innbetalinger, true ));
		}
		
		return $this->innbetalinger;
		break;
	}

	case "transaksjoner":
	{
		$resultat = array_merge(
			$this->hent('krav'),
			$this->hent('innbetalinger')
		);
		usort($resultat, array($this->leiebase, 'sammenliknTransaksjonsdatoer'));
		return $resultat;
		break;
	}

	case "delkravtyper":
	{
		if ( $this->delkravtyper === null || @$param['oppdater'] ) {
			$this->lastDelkravtyper();
		}		
		return $this->$egenskap;
		break;
	}

	case "leiebeløp": // Beregnet leiebeløp per termin
	{
		$årsleie = $this->hent('årlig_basisleie');
		foreach($this->hent('delkravtyper') AS $delkravtype) {
			if($delkravtype->kravtype == "Husleie" and !$delkravtype->selvstendig_tillegg) {
				$årsleie += $delkravtype->beløp;
			}
		}
		return round(bcdiv($årsleie, $this->hent('ant_terminer'), 1));
		break;
	}

	case "efakturareferanse":
	{
		return str_pad($this->id, 5, '0', STR_PAD_LEFT);
		break;
	}

	case "kortnavn":
	case "leietakere":
	case "leietakerfelt":
	case "slettedeLeietakere":
	case "navn":
	{
		if ( $this->leietakere === null || @$param['oppdater'] ) {
			$this->lastLeietakere();
		}		
		return $this->$egenskap;
		break;
	}

	case "beskrivelse": {
		$leieobjekt = $this->hent('leieobjekt');
		return $this->hent('navn') . ' i ' . $leieobjekt->hent('beskrivelse');
		break;
	}

	case "oppsigelse": {
		if ( $this->oppsigelse === null || @$param['oppdater'] ) {
			$this->lastOppsigelse();
		}		
		return $this->oppsigelse;
		break;
	}

	case "fremtidigeKrav": {
		if ( $this->fremtidigeKrav === null || @$param['oppdater'] ) {
			$this->lastFremtidigeKrav();
		}		
		return $this->fremtidigeKrav;
		break;
	}

	case "ubetalteKrav": {
		if ( $this->ubetalteKrav === null || @$param['oppdater'] ) {
			$this->lastUbetalteKrav();
		}		
		return $this->ubetalteKrav;
		break;
	}

	case "utestående": {
		if ( $this->ubetalteKrav === null || @$param['oppdater'] ) {
			$this->lastUbetalteKrav();
		}
		$utestående = 0;
		foreach( $this->ubetalteKrav as $krav ) {
			$utestående += $krav->hent('utestående');
		}
		return $utestående;
		break;
	}

	case "forfalt": {
		if ( $this->ubetalteKrav === null || @$param['oppdater'] ) {
			$this->lastUbetalteKrav();
		}
		$forfalt = 0;
		foreach( $this->ubetalteKrav as $krav ) {
			if( $krav->hent('forfall') <= new DateTime ) {
				$forfalt += $krav->hent('utestående');
			}
		}
		return $forfalt;
		break;
	}

	case "kid": {
		return $this->leiebase->genererKid($this->hentId());
		break;
	}

	case "adresse": {
		if ( $this->adressefelt === null || @$param['oppdater'] ) {
			$this->lastAdressefelt();
		}		
		return $this->adresse;
		break;
	}

	case "adressefelt": {
		if ( $this->adressefelt === null || @$param['oppdater'] ) {
			$this->lastAdressefelt();
		}		
		return $this->adressefelt;
		break;
	}

	/*	Hent epostadresser til brukere tilknyttet leieforholdet
	Returnerer et array med epostadresser som strenger i formatet 'Navn <epostadresse>'
	******************************************
	Parametere:
		innbetalingsbekreftelse (boolsk, normalt ikke)
		forfallsvarsel (boolsk, normalt ikke)
	------------------------------------------
	retur: (array) liste med epostadresser
	*/
	case "brukerepost": {
		settype($param['innbetalingsbekreftelse'], 'boolean');
		settype($param['forfallsvarsel'], 'boolean');

		if ( $this->brukerepost === null ) {
			$this->lastBrukerepost();
		}
		if ($param['innbetalingsbekreftelse']) {
			return $this->brukerepost->innbetalingsbekreftelse;
		}
		if ($param['forfallsvarsel']) {
			return $this->brukerepost->forfallsvarsel;
		}
		else {
			return $this->brukerepost->generelt;
		}
		break;
	}

//	Parametere:
//		rute:	heltall, utskriftsrute posisjonen skal hentes fra
	case "utskriftsposisjon": {
		if ( $this->data === null ) {
			$this->last();
		}
		
		if ( !isset( $this->utskriftsposisjon[$param['rute']] ) ) {
			$this->lastUtskriftsposisjon($param['rute']);
		}
		return $this->utskriftsposisjon[$param['rute']];
		break;
	}

	case "efakturaavtale": {
		$resultat = $this->mysqli->arrayData(array(
			'source'		=> "{$tp}efaktura_avtaler",
			'where'			=> "leieforhold = '{$this->id}'\n
								AND avtalestatus = 'A'"
		));
		if($resultat->totalRows) {
			$resultat->data[0]->registrert = new DateTime($resultat->data[0]->registrert);
			return $resultat->data[0];
		}
		else {
			return false;
		}
		break;
	}

//	Faste betalingsoppdrag (= avtale om AvtaleGiro)
//	Parametere:
//		type:	heltall, normalt 0, angir evt bestemt betalingstype
//				1 = Husleie
//				2 = Fellesstrøm
	case "fbo": {
		settype($param['type'], 'integer');
		$resultat = $this->mysqli->arrayData(array(
			'returnQuery'	=> true,
			'source'		=> "{$tp}fbo",
			'orderfields'	=> "type ASC",
			'where'			=> "leieforhold = '{$this->id}'
								AND (type = '0' OR type = '{$param['type']}')"
		));
		if($resultat->totalRows) {
			$resultat->data[0]->registrert = new DateTime($resultat->data[0]->registrert);
			return $resultat->data[0];
		}
		else {
			return false;
		}
		break;
	}

	case "epostgiro": {
		return false;
		break;
	}

	default: {
		return null;
		break;
	}

	}

}


/*	Hent Saldo per dato
Henter leieforholdets saldo ved utløpet av en gitt dato
En negativ saldo betyr gjeld, mens en positiv saldo er penger tilgode
*****************************************/
//	$dato (DateTime eller streng som 'Y-m-d'):
//	--------------------------------------
//	retur: (tall) Saldo

public function hentSaldoPerDato( $dato ) {
	$tp = $this->mysqli->table_prefix;
	
	if( $dato instanceof DateTime ) {
		$dato = $dato->format('Y-m-d');
	}
	
	$kredit = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}innbetalinger AS innbetalinger",
		'where'			=> "innbetalinger.leieforhold = '{$this->id}'\n"
						.	"AND innbetalinger.dato <= '{$dato}'\n",
		'fields'		=>	"SUM(innbetalinger.beløp) AS sum"
	));
	if($kredit->totalRows) {
		$kredit = reset($kredit->data)->sum;
	}
	else {
		$kredit = 0;
	}
	
	$debet = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}{$this->tabell} AS {$this->tabell}\n"
						.	"INNER JOIN {$tp}krav AS krav ON {$this->tabell}.kontraktnr = krav.kontraktnr\n",
		'where'			=> "{$this->tabell}.{$this->idFelt} = '{$this->id}'\n"
						.	"AND krav.kravdato <= '{$dato}'\n",
		'fields'		=>	"SUM(krav.beløp) AS sum"
	));
	if($debet->totalRows) {
		$debet = reset($debet->data)->sum;
	}
	else {
		$debet = 0;
	}
	
	return round( $kredit - $debet, 2 );
}


/*	Hent siste betaling
Returnerer siste inn- eller utbetaling før dagens dato
******************************************
------------------------------------------
retur: (Krav-objekt) siste krav
*/
public function hentSisteBetaling( ) {
	$innbetalinger =  $this->hent('innbetalinger');
	if( !is_array($innbetalinger) ) {
		throw new Exception("Leieforhold({$this->id})::hent('innbetalinger') gir " . var_export($innbetalinger, true));
	}
	
	$innbetalinger = array_reverse( $this->hent('innbetalinger') );
	$dato = new DateTime;

	foreach( $innbetalinger as $innbetaling ) {
		if( $innbetaling->dato < $dato ) {
			return $innbetaling;
		}
	}
	return null;
}


/*	Hent siste krav
Returnerer siste krav eller kreditt før dagens dato
******************************************
------------------------------------------
retur: (Krav-objekt) siste krav
*/
public function hentSisteKrav( ) {
	$kravsett = array_reverse( $this->hent('krav') );
	$dato = new DateTime;
	if(!count($kravsett)) {
		return null;
	}
	foreach( $kravsett as $krav ) {
		if( $krav->hent('kravdato') < $dato ) {
			return $krav;
		}
	}
}


/*	Hent siste transaksjon
Returnerer siste krav eller innbetaling før dagens dato
******************************************
------------------------------------------
retur: (Krav-objekt) siste krav
*/
public function hentSisteTransaksjon( ) {
	$sisteKrav = $this->hentSisteKrav();
	$sisteBetaling = $this->hentSisteBetaling();
	if(!$sisteBetaling) {
		return $sisteKrav;
	}
	if(!$sisteKrav) {
		return $sisteBetaling;
	}
	if ($sisteBetaling->dato > $sisteKrav->hent('dato')) {
		return $sisteBetaling;
	}
	return $sisteKrav;
}


// Henter siste leiejustering for leieobjektet før angitt dato
/****************************************/
//	$dato
//	--------------------------------------
//	retur: (stdClass) objekt med parameterene:
//		dato (DateTime)
//		beløp (nytt leiebeløp)
public function hentSisteLeiejustering( $dato = false) {
	$tp = $this->mysqli->table_prefix;
	
	if( $dato instanceof DateTime ) {
		$dato = $dato->format('Y-m-d');
	}
	
	$resultat = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}krav AS krav\n"
						.	"LEFT JOIN {$tp}kontrakter AS kontrakter ON kontrakter.kontraktnr = krav.kontraktnr\n",
		'where'			=> "krav.type = 'Husleie'\n"
						.	( $dato ? "AND krav.fom < '$dato'\n" : "")
						.	"AND kontrakter.leieforhold = '{$this->hentId()}'\n",
		'groupfields'	=>	"krav.beløp",
		'orderfields'	=>	"krav.fom",
		'fields'		=>	"MIN(krav.fom) AS dato, krav.beløp"
	));
	
	if( $resultat->totalRows ) {
		$resultat = end($resultat->data);
		$resultat->dato = new DateTime( $resultat->dato );
		return $resultat;
	}
	else {
		return (object)array(
			'dato'	=>	$this->hent('fradato'),
			'beløp'	=>	$this->hent('leiebeløp')
		);
	}
}


/*	Hent Transaksjoner
Henter alle krav og innbetalinger for dette leieforholdet innenfor et gitt tidsrom
Dersom fra- eller til-dato ikke er angitt, returneres alle transaksjoner
*****************************************/
//	$fra (DateTime eller streng som 'Y-m-d'):
//	$til (DateTime eller streng som 'Y-m-d'):
//	--------------------------------------
//	retur: (liste) Innbetaling- og Krav-objekter sortert etter dato

public function hentTransaksjoner( $fra = null, $til = null ) {
	$tp = $this->mysqli->table_prefix;
	
	if( $fra instanceof DateTime ) {
		$fra = $fra->format('Y-m-d');
	}
	if( $til instanceof DateTime ) {
		$til = $til->format('Y-m-d');
	}
	
	$filter = array("innbetalinger.leieforhold = '{$this->id}'");
	if( $fra ) {
		$filter[] = "innbetalinger.dato >= '{$fra}'";
	}
	if( $til ) {
		$filter[] = "innbetalinger.dato <= '{$til}'";
	}
	$kredit = $this->mysqli->arrayData(array(
		'distinct'		=> true,
		'source'		=> "{$tp}innbetalinger AS innbetalinger",
		'where'			=> '(' . implode( ') AND (', $filter ) . ')',
		'fields'		=>	"innbetalinger.innbetaling AS id",
		'orderfields'	=> "innbetalinger.innbetaling",
		'class'			=> "Innbetaling"
	));
	
	
	$filter = array("kontrakter.leieforhold = '{$this->id}'");
	if( $fra ) {
		$filter[] = "krav.kravdato >= '{$fra}'";
	}
	if( $til ) {
		$filter[] = "krav.kravdato <= '{$til}'";
	}
	$debet = $this->mysqli->arrayData(array(
		'distinct'		=> true,
		'source'		=> "{$tp}krav AS krav\n"
						.	"INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
		'where'			=> '(' . implode( ') AND (', $filter ) . ')',
		'fields'		=>	"krav.id",
		'orderfields'	=> "krav.kravdato, krav.id",
		'class'			=> "Krav"
	));
	
	$resultat = array_merge($kredit->data, $debet->data);
	
	usort($resultat, array( $this->leiebase, 'sammenliknTransaksjonsdatoer' ));
	return $resultat;
}


// Legg til eller oppdater en delkravtype til leieforholdet
/****************************************/
//	$type (heltall) Delkravtypen
//	$relativ (boolsk) normalt av
//		Dersom ikke relativ vil satsen angi faktisk beløp
//		Dersom relativ vil satsen angis som faktor av basisleia
//	$sats (tall) beløpet eller prosentsatsen som skal legges til basisleia
//	--------------------------------------
//	retur: boolsk suksessangivelse
public function leggTilDelkravtype( $type, $relativ = false, $sats = 0, $selvstendig = false) {
	$resultat = array();
	$tp = $this->mysqli->table_prefix;

	settype($type, 'integer');
	settype($relativ, 'boolean');
	if(!$type or !is_numeric($sats) or !$this->hentId()) {
		return false;
	}
	$resultat = $this->mysqli->query("
		INSERT INTO {$tp}leieforhold_delkrav (leieforhold, delkravtype, relativ, sats, selvstendig_tillegg)
		VALUES ('{$this->hentId()}', '{$type}', '" . (int)$relativ . "', '{$sats}', '" . ($selvstendig ? 1 : 0) . "')
		ON DUPLICATE KEY UPDATE sats = '{$sats}'
	");
	
	$this->delkravtyper = null;
	return $resultat;
}



/*
Registrer en ny leieavtale i leieforholdet.
*****************************************/
//	$param (array/objekt) Parametere for kontrakten:
//		dato: (DateTime / streng) Fradato for den nye kontrakten
//		tildato: (null / DateTime / streng) Evt. Ny opphørsdato
//		tekst: (streng) Avtaleteksten
//		oppsigelsestid: (streng) Ny oppsigelsestid
//		leietakere: (liste med Person-objekter) Evt nye leietakere. Normalt eksisterende leietakere
//	--------------------------------------
//	retur: Nytt kontraktnr, eller usann dersom mislykket

public function leggTilKontrakt($param) {
	$tp = $this->mysqli->table_prefix;
	$resultat = false;
	settype($param, 'object');
	
	$kontrakter =  $this->hent('kontrakter');
	$sisteKontrakt = reset( $kontrakter );
	
	if( $param->dato instanceof DateTime ) {
		$param->dato = $param->dato->format('Y-m-d');
	}
	
	if( isset($param->tildato) ) {
		if( @$param->tildato instanceof DateTime ) {
			$param->tildato = $param->tildato->format('Y-m-d');
		}
	}
	else {
		$param->tildato = null;
	}
	
	if( $param->tildato and ( $param->dato >= $param->tildato ) ) {
		return false;
	}
	
	if( ( $param->dato <= $sisteKontrakt->dato->format('Y-m-d') ) ) {
		return false;
	}
	
	if( !is_array(@$param->leietakere) ) {
		$param->leietakere = array($param->leietakere);
	}	
	$leietakere	= $param->leietakere
						? $param->leietakere
						: $this->hent('leietakere');

	$felter = $this->mysqli->arrayData(array(
		'source'	=> "{$tp}{$this->tabell}",
		'where'		=> "{$tp}{$this->tabell}.kontraktnr = '{$this->hent('kontraktnr')}'",
	))->data[0];
	
	$felter->oppsigelsestid = isset( $param->oppsigelsestid )
						? $param->oppsigelsestid
						: $felter->oppsigelsestid;
	$felter->tekst = isset( $param->tekst )
						? $param->tekst
						: $felter->tekst;
	$felter->fradato = $param->dato;
	$felter->tildato = $param->tildato;
	unset( $felter->kontraktnr );

	$ny = @$this->mysqli->saveToDb(array(
		'insert'	=> true,
		'id'		=> $this->idFelt,
		'table'		=> "{$tp}{$this->tabell}",
		'fields'	=> $felter
	))->id;
	
	if(!$ny) {
		return false;
	}
	
	foreach( $leietakere as $leietaker) {
		if(!is_a($leietaker, 'Person')) {
			$leietaker = $this->leiebase->hent('Person', (int)$leietaker);
		}
		if($leietaker->hentId() and $this->mysqli->query("
			INSERT INTO {$tp}kontraktpersoner (person, kontrakt)
			VALUES ('{$leietaker}', '{$ny}')
			ON DUPLICATE KEY UPDATE slettet = null
		")) {
			$resultat[] = $leietaker;
		}
	}
	
	$this->nullstill();
	return $ny;	
}



// Legg til en eller flere leietakere i leieforholdet
/****************************************/
//	$leietakere (array/Person-objekt) Enten en person eller ei liste med personer
//	--------------------------------------
//	retur: liste over Person-objekter som har blitt lagt til.
public function leggTilLeietaker($leietakere) {
	$resultat = array();
	$tp = $this->mysqli->table_prefix;
	if( !is_array($leietakere) ) {
		$leietakere = array($leietakere);
	}
	
	foreach( $leietakere as $leietaker) {
		if(!is_a($leietaker, 'Person')) {
			$leietaker = $this->leiebase->hent('Person', (int)$leietaker);
		}
		if($leietaker->hentId() and $this->mysqli->query("
			INSERT INTO {$tp}kontraktpersoner (person, kontrakt)
			VALUES ('{$leietaker}', '{$this->id}')
			ON DUPLICATE KEY UPDATE slettet = null
		")) {
			$resultat[] = $leietaker;
		}
	}
	
	$this->leietakere = null;
	return $resultat;	
}



/*
Nullstiller alle egenskapene i leieforholdet untatt id,
sånn at de tvinges til å lastes på nytt.
*****************************************/
//	--------------------------------------
//	retur: boolsk sann

public function nullstill() {
	$this->data				= null;	// DB-verdiene lagret som et objekt. Null om de ikke er lastet
	$this->delkravtyper		= null;	// Alle delkravtyper som hører til leieforholdet
	$this->leietakere		= null;	// Liste over leietakeren(e) som inngår i leieavtalen
	$this->slettedeLeietakere = array();	// Liste over leietakere som er slettet fra leieavtalen
	$this->leietakerfelt	= null;	// Streng som lister alle leietakerne med fødsels- og personnummer
	$this->krav				= null;	// Alle betalingskrav som er opprettet i leieavtalen
	$this->leiekrav			= null;	// Alle husleiekrav som er opprettet i leieavtalen
	$this->navn				= null;	// Navn på leietakeren(e) som inngår i leieavtalen
	$this->kortnavn			= null;	// Forkortet navn på leietakeren(e)
	$this->adresse			= null;	// stdClass-objekt med adresseelementene
	$this->adressefelt		= null;	// Adressefelt for utskrift
	$this->brukerepost		= null;	// Liste over brukerepostadresser
	$this->oppsigelse		= null;	// stdClass-objekt med oppsigelse. Null dersom ikke lastet
	$this->ubetalteKrav		= null;	// Array over alle ubetalte krav. Null om de ikke er lastet
	$this->utskriftsposisjon	= array();	// Utskriftsposisjonen for hver enkelt rute
	return true;
}


// Oppretter et nytt leieforhold i databasen og tildeler egenskapene til dette objektet
/****************************************/
//	$egenskaper (array/objekt) Alle egenskapene det nye objektet skal initieres med
//	--------------------------------------
public function opprett($egenskaper = array()) {
	$tp = $this->mysqli->table_prefix;
	settype( $egenskaper, 'array');
	
	if( $this->id ) {
		throw new Exception('Nytt Leieforhold forsøkt lagret, men det eksisterer allerede');
		return false;
	}
	
	if( !is_a(@$egenskaper['leieobjekt'], 'Leieobjekt') ) {
		$egenskaper['leieobjekt'] = $this->leiebase->hent('Leieobjekt', (int)@$egenskaper['leieobjekt']);
	}
	
	if( !$egenskaper['leieobjekt']->hentId() ) {
		throw new Exception('Ugyldig leieobjekt');
		return false;
	}
	
	if( !@$egenskaper['leietakere'] ) {
		throw new Exception('Leietakere ikke oppgitt');
		return false;
	}
	
	if( !@$egenskaper['fradato'] ) {
		throw new Exception('Fra-dato ikke oppgitt');
		return false;
	}
	
	if( is_a(@$egenskaper['fradato'], 'DateTime') ) {
		$egenskaper['fradato'] = $egenskaper['fradato']->format('Y-m-d');
	}
	
	if( !@$egenskaper['tildato'] ) {
		$egenskaper['tildato'] = null;
	}
	else if( is_a(@$egenskaper['tildato'], 'DateTime') ) {
		$egenskaper['tildato'] = $egenskaper['tildato']->format('Y-m-d');
	}
	
	if( $this->fraBrøk( $egenskaper['andel'] ) > $egenskaper['leieobjekt']->hentUtleie($egenskaper['fradato'], $egenskaper['tildato'])->ledig ) {
		return false;
	}
	
	$databasefelter = array();
	$resterendeFelter = array();
	
	foreach($egenskaper as $egenskap => $verdi) {
		switch( $egenskap ) {

		case "leieobjekt":
		case "andel":
		case "fradato":
		case "tildato":
		case "oppsigelsestid":
		case "ant_terminer":
		case "tekst":
		case "regningsperson":
		case "regning_til_objekt":
		case "regningsobjekt":
			$databasefelter[$egenskap] = $verdi;
			break;

		case "leietakere":
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
	
	if($this->id) {
		$this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "{$tp}{$this->tabell}",
			'fields'	=> array(
				'leieforhold'	=> $this->id
			),
			'where'		=> "kontraktnr = '{$this->id}'"
		));
	}
	
	if( !$this->hentId() ) {
		throw new Exception('Nytt Leieforhold forsøkt lagret, men det kunne ikke lastes igjen etterpå');
		return false;
	}

	foreach( $egenskaper['leietakere'] as $leietaker ) {
		$this->leggTilLeietaker($leietaker);
	}
	
	foreach( $resterendeFelter as $egenskap => $verdi ) {
		$this->sett($egenskap, $verdi);
	}
	
	return $this;
}



/*
Erstatter eller oppretter nye leiekrav (forfall) i dette leieforholdet
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

public function opprettLeiekrav(
	$fradato = null,
	$løsneInnbetalinger = false,
	$ukedag = 0,
	$kalenderdag = 0,
	$fastDato = false
) {
	$tp = $this->mysqli->table_prefix;
	$resultat		= array();
	$oppsigelse		= $this->hent('oppsigelse');
	$tildato		= $this->hent('tildato');
	$terminlengde	= $this->hent('terminlengde');
	$oppsigelsestid	= $this->hent('oppsigelsestid');
	$leieobjekt		= $this->hent('leieobjekt');
	$andel			= $this->hent('andel');
	$kontraktnr		= $this->hent('kontraktnr');
	$basisleie		= $this->hent('årlig_basisleie');
	$antallTerminer	= $this->hent('ant_terminer');
	$terminleie		= $this->hent('leiebeløp');
	$delkravtyper	= array();
	$delkrav		= array();
	$tillegg		= array();
	$kravtyper		= array("Husleie");
	$startdato		= null;
	
	// Dersom leieforholdet er oppsagt, vil det kun opprettes leiekrav dersom $fradato er angitt
	if($oppsigelse and $fradato === null) {
		return $resultat;
	}
	
	
	if($kalenderdag === 't' || $kalenderdag > 27) {
		$kalenderdag = 't';
	}
	else {
		$kalenderdag = str_pad($kalenderdag, 2, '0', STR_PAD_LEFT);
	}

	// Last inn delkravtypene som er relevante for husleieberegninga
	foreach( $this->hent('delkravtyper') as $delkravtype ) {
		if($delkravtype->kravtype == "Husleie" and !$delkravtype->selvstendig_tillegg ) {
			$delkravtyper[] = $delkravtype;
		}
		else if($delkravtype->kravtype == "Husleie" ) {
			$tillegg[] = $delkravtype;
			$kravtyper[] = $delkravtype->kode;
		}
	}

	// Sørg for at $fradato er et DateTime-objekt
	if($fradato !== null && !is_a($fradato, 'DateTime')) {
		$fradato = new DateTime($fradato);
	}
	
	// Startdato er i utgangspunktet den datoen som er oppgitt som fradato
	if($fradato) {
		$startdato = clone $fradato;
	}
	
	
	// Spørring som finner det siste eksisterende leiekravet i leieforholdet.
	//	Starten flyttes til første mulige dato
	//	som tar hensyn til krav, giroer og evt innbetalinger
	//	Dersom fradato er oppgitt søkes det siste kravet som begynner før fradato,
	// evt det siste som er betalt.
	if($fradato) {
		$filter = "AND (fom < '{$fradato->format('Y-m-d')}'\n"
		. (
			$løsneInnbetalinger
			? ")\n"
			: "OR krav.utskriftsdato\nOR innbetalinger.krav)\n"
		);
	}
	else {
		$filter = "";
	}
	$eksisterende = $this->mysqli->arrayData(array(
		'fields'	=> "MAX(DATE_ADD(tom, INTERVAL 1 DAY)) AS fom",
		'source'	=> "{$tp}krav AS krav\n"
					.	"INNER JOIN {$tp}kontrakter AS kontrakter ON krav.kontraktnr = kontrakter.kontraktnr\n"
					.	"LEFT JOIN innbetalinger ON krav.id = innbetalinger.krav\n",
		'where'		=> "type = 'Husleie'\n"
					.	"AND kontrakter.leieforhold = '{$this->hentId()}'\n"
					.	$filter
	));
	
	// Dersom det allerede finnes leiekrav i leieforholdet
	if($eksisterende->totalRows and $eksisterende->data[0]->fom) {
		$startdato = new DateTime($eksisterende->data[0]->fom);
	}
	
	// Dersom det ikke finnes noen leiekrav i leieforholdet fra før
	else {
		$startdato = clone $this->hent('fradato');
	}
	// Startdato er etablert
	
	
	// Sluttdato er den første datoen hvor en ny termin ikke lenger kan påbegynnes
	// Sluttdato avhenger av flere forhold:
	//	For en vanlig avtale vil sluttdato være dagens dato pluss oppsigelsestiden
	//	For en tidsbegrenset leieavtale vil sluttdato være dagen etter at leieavtalen opphører.
	// Dersom opphørsdato har passert vil sluttdato være i morgen.
	// For en leieavtale som er oppsagt vil sluttdato være lik oppsigelsestidSlutt, evt tidligere hvis det ikke er plass i leieobjektet
	// Dersom leieforholdet er oppsagt vil det allikevel ikke opprettes flere krav med mindre $fradato er oppgitt
	
	// Dersom leieforholdet er avsluttet vil sluttdato settes til etter oppsigelsestiden
	if($oppsigelse) {
		$sluttdato = clone $oppsigelse->oppsigelsestidSlutt;
	}
	else {
	
		if( $tildato ) {
				
			// Dersom utløpsdato for et tidsbegrenset leieforhold er passert vil sluttdato være i morgen
			//	(Kun terminer som har begynt kan opprettes)
			if($tildato < date_create()) {
				$sluttdato = new DateTime();
				$sluttdato->add( new DateInterval("P1D") );			
			}
	
			// Om utløpsdato for et tidsbegrenset leieforhold ikke er passert,
			//	vil sluttdato settes til denne
			else {
				$sluttdato = clone $tildato;
				$sluttdato->add( new DateInterval('P1D') );
			}		
		}
	
		// Dersom leieforholdet ikke er tidsbegrenset er sluttdato lik oppsigelsestid fra i dag
		else {
			$sluttdato = new DateTime();
			$sluttdato->add($oppsigelsestid);
		}
	}	
	// Sluttdato er nå etablert


	// Dersom fradato er angitt skal alle eksisterende krav fra startdato slettes før de nye opprettes
	if($fradato) {
		$slettesett = $this->mysqli->arrayData(array(
			'source'	=> "{$tp}krav AS krav INNER JOIN {$tp}kontrakter AS kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
			'fields'	=> "krav.id",
			'class'		=> "Krav",
			'where'		=> "(krav.type = '" . implode("' OR krav.type = '", $kravtyper) . "') AND kontrakter.leieforhold = '{$this->hentId()}' AND krav.fom >= '{$startdato->format('Y-m-d')}'"
		));
		
		foreach($slettesett->data as $slettekrav) {
			$slettekrav->slett();
		}
	}
	
	
	// Krav for en og en termin opprettes fra startdato inntil sluttdato er nådd
	$fom = clone $startdato;
	while($fom < $sluttdato) {

		$termindel = 1 / $antallTerminer;
		$leie = $terminleie;
		$delkrav = array();


		/*
		Månedlige terminer kan risikere å hoppe over en kort måned dersom
		de begynner på slutten av en lengre måned
		*/
		if(
			$fom->format('j') > 27
			and $terminlengde->format('%m') // Kun dersom terminlengden er oppgitt i måneder
		) {
		
			$tom = new DateTime($fom->format('Y-m-01'));	// $tom flyttes til første dag i måneden
			$tom->add($terminlengde);						// Intervallet legges til
			$tom = new DateTime($tom->format('Y-m-t'));		// $tom flyttes til
			$tom->sub(new DateInterval("P1D"));				// nest siste dag i måneden
		}
		else {
			
			$tom = clone $fom;
			$tom->add($terminlengde)->sub(new DateInterval("P1D"));
		}
		
		// Terminen skal ikke fortsette utover oppsigelsestiden
		if( $oppsigelse and $tom >= $oppsigelse->oppsigelsestidSlutt ) {
			$tom = clone $oppsigelse->oppsigelsestidSlutt;
			$tom->sub( new DateInterval("P1D") );
		}

		// Datoene for normal terminlengde er nå angitt i $fom og $tom
	

		/*
		$sisteFørBrudd er siste datoen før et ny termin skal brytes gjennom som
		følge av angitt fast ukedag eller dato
		*/
		$sisteFørBrudd = clone $tom;

		/*
		Dersom terminene alltid skal begynne på en fast ukedag:
		*/
		if( $ukedag > 0) {
			$differanse = $tom->format('N') + 1 - (int)$ukedag;
			if($differanse < 0) {
				$differanse += 7;
			}
			$sisteFørBrudd = clone $tom;
			$sisteFørBrudd->sub(new DateInterval("P{$differanse}D"));
		}

		/*
		Dersom terminene alltid skal begynne på en fast dato hver måned:
		*/
		else if($kalenderdag == 't' or $kalenderdag > 0) {
			$sisteFørBrudd = new DateTime( $tom->format("Y-m-{$kalenderdag}") );
			$sisteFørBrudd->sub(new DateInterval("P1D"));
			if( $sisteFørBrudd > $tom ) {
				$sisteFørBrudd = new DateTime( $tom->format("Y-m-01"));
				$sisteFørBrudd->sub(new DateInterval("P1M"));
				$sisteFørBrudd = new DateTime( $sisteFørBrudd->format("Y-m-{$kalenderdag}"));
				$sisteFørBrudd->sub(new DateInterval("P1D"));
			}
		}

		/*
		Dersom nye terminer alltid skal begynne på en fast dato hvert år:
		*/
		else if( $fastDato ) {
			$sisteFørBrudd = new DateTime( $tom->format("Y-{$fastDato}") );
			$sisteFørBrudd->sub(new DateInterval("P1D"));
			if( $sisteFørBrudd > $tom ) {
				$sisteFørBrudd = new DateTime( $tom->format("Y-m-01"));
				$sisteFørBrudd->sub(new DateInterval("P1Y"));
				$sisteFørBrudd = new DateTime( $sisteFørBrudd->format("Y-{$fastDato}"));
				$sisteFørBrudd->sub(new DateInterval("P1D"));
			}
		}
		
		// Hva?:
		if( $fom > $sisteFørBrudd ) {
			$sisteFørBrudd = clone $tom;
		}
		
		// $tom flyttes som følge av ønsket forfallsdag
		if( $tom > $sisteFørBrudd ) {
			$tom = clone $sisteFørBrudd;
			$termindel = ( 1 + $fom->diff($tom)->format('%r%a') ) / 365;
			$leie = round( $terminleie * $antallTerminer * $termindel );
		}
				
		/*
		Sjekk om vi er i oppsigelse.
		I oppsigelsestida skal det ikke beregnes leie dersom leieobjektet allerede
		er utleid
		*/
		$ledig = true;
		if($oppsigelse and $dato >= $oppsigelse->fristillelsesdato) {
			$ledighet = $leieobjekt->hentUtleie($fom, $tom, $this)->ledig;
			$ledig = ($ledighet >= $this->fraBrøk($andel));
		}
		
		/*
		Delkravene for denne terminen etableres.
		*/
		foreach($delkravtyper AS $delkravtype) {
			$delkrav[] = (object)array(
				'type'	=> $delkravtype->id,
				'beløp'	=> $delkravtype->relativ
							? round( $delkravtype->sats * $basisleie * $termindel )
							: round($delkravtype->sats * $termindel)
			);
		}
		
		/*
		Terminangivelsen formateres.
		Dersom perioden dekker en kalendermåned skrives den som måned - år
		*/
		if(
			$fom->format('Y-m-t') == $tom->format('Y-m-d')
			&& $fom->format('Y-m-d') == $tom->format('Y-m-01')
		) {
			$terminbeskrivelse = strftime('%B %Y', $fom->format('U'));
		}
		
		//	Dersom perioden dekker ei uke skrives den som uke - år
		else if(
			$fom->format('Y-W-N') == $tom->format('Y-W-1')
			&& $fom->format('Y-W-7') == $tom->format('Y-W-N')
		) {
			$terminbeskrivelse = "Uke " . $fom->format('W Y');
		}
		//	ellers skrives perioden som fradato - tildato
		else {
			$terminbeskrivelse = $fom->format('d.m.Y') . " – " . $tom->format('d.m.Y');
		}
		
		
		if($ledig) {
			if( $krav = $this->leiebase->opprett('Krav', array(
				'oppretter'		=> $this->leiebase->bruker['navn'],
				'type'			=> "Husleie",
				'leieobjekt'	=> $leieobjekt,
				'kravdato'		=> $fom,
				'fom'			=> $fom,
				'tom'			=> $tom,
				'andel'			=> $andel,
				'delkrav'		=> $delkrav,
				'kontraktnr'	=> $kontraktnr,
				'beløp'			=> $leie,
				'termin'		=> $terminbeskrivelse,
				'tekst'			=> "Leie for #{$leieobjekt} {$terminbeskrivelse}",
				'forfall'		=> $fom				
			))) {
				$resultat[] = $krav;
			}
			
			// Opprett tilleggene
			foreach($tillegg as $tilleggskrav) {
				if( $krav = $this->leiebase->opprett('Krav', array(
					'oppretter'		=> $this->leiebase->bruker['navn'],
					'type'			=> $tilleggskrav->kode,
					'leieobjekt'	=> $leieobjekt,
					'kravdato'		=> $fom,
					'fom'			=> $fom,
					'tom'			=> $tom,
					'kontraktnr'	=> $kontraktnr,
					'beløp'			=> $tilleggskrav->relativ
										? round( $tilleggskrav->sats * $basisleie * $termindel )
										: round($tilleggskrav->sats * $termindel),
					'termin'		=> $terminbeskrivelse,
					'tekst'			=> "{$tilleggskrav->navn} {$terminbeskrivelse}",
					'forfall'		=> $fom				
				))) {
					$resultat[] = $krav;
				}
			
			}
		}
	
		// Skip til neste krav
		$fom = clone $tom;
		$fom->add(new DateInterval("P1D"));
	}
	
	// Tving leieforholdet til å laste kravene på nytt om de trengs
	$this->krav			= null;
	$this->ubetalteKrav	= null;

	return $resultat;
}


// Skriv en verdi
/****************************************/
//	$egenskap		streng. Egenskapen som skal endres
//	$verdi			Ny verdi
//	--------------------------------------
public function sett($egenskap, $verdi = null) {
	$tp = $this->mysqli->table_prefix;
	
	if( !$this->id ) {
		return null;
	}
	if( $egenskap == 'oppsigelsestid' and !$verdi ) {
		$verdi = 'P0M';
	}
	
	switch( $egenskap ) {
	
	//	Verdier som kun skal endres i siste kontrakt:
	case "tildato":
	{
		if ( $verdi instanceof DateTime ) {
			$verdi = $verdi->format('Y-m-d');
		}		
		if ( $verdi instanceof DateInterval ) {
			$verdi = $this->leiebase->periodeformat( $verdi, true );
		}		
		
		$resultat = $this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "{$tp}{$this->tabell} as {$this->tabell}",
			'where'		=> "{$this->tabell}.kontraktnr = '{$this->hent('kontraktnr')}'",
			'fields'	=> array(
				"{$this->tabell}.{$egenskap}"	=> $verdi
			)
		))->success;
		break;
	}

	//	Verdier som skal endres for hele leieforholdet:
	case "frosset":
	case "regningsperson":
	case "regningsobjekt":
	case "regning_til_objekt":
	case "regningsadresse1":
	case "regningsadresse2":
	case "postnr":
	case "poststed":
	case "land":
	case "ant_terminer":
	case "årlig_basisleie":
	case "oppsigelsestid":
	{
		if ( $verdi instanceof DateTime ) {
			$verdi = $verdi->format('Y-m-d');
		}		
		if ( $verdi instanceof DateInterval ) {
			$verdi = $this->leiebase->periodeformat( $verdi, true );
		}		
		
		$resultat = $this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "{$tp}{$this->tabell} as {$this->tabell}",
			'where'		=> "{$this->tabell}.{$this->idFelt} = '{$this->id}'",
			'fields'	=> array(
				"{$this->tabell}.{$egenskap}"	=> $verdi
			)
		))->success;
		break;
	}

	case "delkravtyper":
	case "tillegg":
	{
		$tillegg = $egenskap == "tillegg" ? "" : "!";
		if ( $verdi !== null) {
			throw new Exception("Delkravtypene i leieforhold {$this->id} forsøkt endret på feil måte.");
		}
		
		$resultat = $this->mysqli->query("DELETE FROM {$tp}leieforhold_delkrav WHERE {$tillegg}{$tp}leieforhold_delkrav.selvstendig_tillegg AND {$tp}leieforhold_delkrav.leieforhold = '{$this->hentId()}'");
		break;
	}

	default:
	{
		throw new Exception("Feil bruk av Leieforhold::sett(). {$egenskap} forsøkt satt til " . var_export($verdi, true));
		return false;
		break;
	}

	}
	
	
	// Tving ny lasting av data:
	switch( $egenskap ) {
	
	case "tildato":
	case "årlig_basisleie":
	case "ant_terminer":
	case "delkravtyper":
	{
		$this->oppdaterLeie();
		break;
	}

	case "regningsperson":
	case "regningsobjekt":
	case "regning_til_objekt":
	case "regningsadresse1":
	case "regningsadresse2":
	case "postnr":
	case "poststed":
	case "land":
	{
		$this->adresse		= null;
		$this->adressefelt	= null;
	}
	
	default:
	{
		$this->data	= null;
	}
	}

	return $resultat;
}


}?>