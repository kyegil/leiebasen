<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
Denne fila ble sist oppdatert 2016-03-21
**********************************************/

class Giro extends DatabaseObjekt {

protected	$tabell = "giroer";	// Hvilken tabell i databasen som inneholder primærnøkkelen for dette objektet
protected	$idFelt = "gironr";	// Hvilket felt i tabellen som lagrer primærnøkkelen for dette objektet
protected	$data;				// DB-verdiene lagret som et objekt Null betyr at verdiene ikke er lastet
protected	$krav;				// Array over alle kravene på denne giroen. Null betyr at kravene ikke er lastet.
protected	$efaktura;			// stdClass-objekt. Usann betyr at efaktura ikke finnes. Null betyr at verdiene ikke er lastet.
protected	$fboTrekkrav;		// stdClass-objekt. Usann betyr at krav ikke finnes. Null betyr at verdiene ikke er lastet.
protected	$purringer;				// Array over alle purringene på denne giroen. Null betyr at purringene ikke er lastet.
protected	$purrestatistikk;		// Objekt med oversikt over siste purring, siste gebyr, antall purringer og antall gebyr. Null betyr at purringene ikke er lastet.
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


/*	FBO Oppdragsfrist
Returnerer siste mulige anledning til å sende krav om trekk via AvtaleGiro
og opprettholde NETS' frister for varsling.
Dersom forfallsdato ikke er fastsatt vil fristen settes til null
*****************************************/
//	$forfall (DateTime): Om denne er gitt vil den overstyre giroens forfallsdato
//	--------------------------------------
//	retur: (DateTime-objekt) Siste mulighet for NETS-forsendelse
public function fboOppdragsfrist( $forfall = null ) {
	if( $forfall === null ) {
		$forfall = $this->hent('forfall');
	}
	else if( !is_a($forfall, DateTime) ) {
		$forfall = new DateTime( $forfall );
	}

	if( !is_a($forfall, 'DateTime') ) {
		return null;
	}
	
	$resultat = clone $forfall;
	
	$leieforhold = $this->hent('leieforhold');
	$fbo = $leieforhold->hent('fbo');
	if( !$fbo ) {
		return null;
	}
	$bankvarsel = $fbo->varsel
		&& !$leieforhold->hent('brukerepost')
		&& $leieforhold->hent('efakturaavtale');
	
	if( $bankvarsel ) {
		if( $resultat->format('d') > 14 ) {
			$resultat->sub( new DateInterval('P1M') );
		}
		else {
			$resultat->sub( new DateInterval('P2M') );
		}
		$resultat = new DateTime( $resultat->format('Y-m-t') );
	}

	else {
		$resultat->sub( new DateInterval('P9D') );
	}

	$bankfridager = $this->leiebase->bankfridager( $resultat->format('Y') );
	while(
		in_array( $resultat->format('m-d'), $bankfridager )
		or $resultat->format('N') > 5
	) {
		$resultat->sub(new DateInterval('P1D'));
	}
		
	$resultat->setTime(14, 0, 0);

	return $resultat;
}


// Skriv ut giroen etter en angitt mal
/****************************************/
//	$mal	(streng) gjengivelsesmalen
//	$param
//	--------------------------------------
public function gjengi($mal, $param = array()) {
	settype( $param, 'array');
	$leiebase = $this->leiebase;

	switch($mal) {
	
	case "efaktura":
	{
		// Parametre:
		//		transaksjonsnummer (heltall):	påkrevd
		//		utskriftsdato (DateTime-objekt):	Overstyring av evt lagret dato
		//		forfall (DateTime-objekt):	Overstyring av evt lagret forfall
		//		summaryType (boolsk): Normalt av. Settes på dersom efakturaen trekkes som avtalegiro.
	
		$leieforhold = $this->hent('leieforhold');
		$leieobjekt = $leieforhold->hent('leieobjekt');
		$kravsett = $this->hent('krav');
		
		$fakturamal = 2;	// 01 = NETS' efakturamal 1
							// 02 = NETS' efakturamal 2

		
		$fakturatype = "eFaktura regning"; // Maks 35 tegn
		$fremmedreferanse = "Regning nr. {$this->hentId()}"; // Maks 25 tegn
		
		// Formater fakturadetaljene
		$fakturadetaljer = array();
		$kolonnebredde = ($fakturamal-1) ? 96 : 80;
		foreach ( $kravsett  as $krav ) {
			$delstreng
				= $leiebase->fastStrenglengde( $krav->hent('tekst'), $kolonnebredde - 20 )
				. $leiebase->fastStrenglengde( "kr. " . number_format($krav->hent('beløp'), 2, ",", " "), 20, " ", STR_PAD_LEFT );
			$fakturadetaljer[] = (object)array(
				'a'	=> $leiebase->fastStrenglengde( mb_substr ($delstreng, 0, 40, 'UTF-8'), 40),
				'b'	=> $leiebase->fastStrenglengde( mb_substr ($delstreng, 40, 40, 'UTF-8'), 40),
				'c'	=> $leiebase->fastStrenglengde( mb_substr ($delstreng, 80, 16, 'UTF-8'), 40)
			);
		}
		
		$delstreng
			= $leiebase->fastStrenglengde( "Totalt", $kolonnebredde - 20 )
			. $leiebase->fastStrenglengde( "kr. " . number_format(($this->hent('beløp')), 2, ",", " "), 20, " ", STR_PAD_LEFT );
		$fakturadetaljer[] = (object)array(
			'a'	=> $leiebase->fastStrenglengde( mb_substr ($delstreng, 0, 40, 'UTF-8'), 40),
			'b'	=> $leiebase->fastStrenglengde( mb_substr ($delstreng, 40, 40, 'UTF-8'), 40),
			'c'	=> $leiebase->fastStrenglengde( mb_substr ($delstreng, 80, 16, 'UTF-8'), 40)
		);

		if( $this->hent('beløp') != $this->hent('utestående') ) {
			$delstreng
				= $leiebase->fastStrenglengde( "Betalinger til fradrag", $kolonnebredde - 20 )
				. $leiebase->fastStrenglengde( "kr. " . number_format(($this->hent('utestående') - $this->hent('beløp')), 2, ",", " "), 20, " ", STR_PAD_LEFT );
			$fakturadetaljer[] = (object)array(
				'a'	=> $leiebase->fastStrenglengde( mb_substr ($delstreng, 0, 40, 'UTF-8'), 40),
				'b'	=> $leiebase->fastStrenglengde( mb_substr ($delstreng, 40, 40, 'UTF-8'), 40),
				'c'	=> $leiebase->fastStrenglengde( mb_substr ($delstreng, 80, 16, 'UTF-8'), 40)
			);
		}

		$detaljoverskrift
			= $leiebase->fastStrenglengde( "Detaljer", $kolonnebredde - 20 )
			. $leiebase->fastStrenglengde( "         Beløp", 20, " ", STR_PAD_RIGHT );


		// Fritekst 1 kan være maks. 5 linjer.
		$kolonnebredde = ($fakturamal-1) ? 96 : 80;
		$fritekst1 = array();
		$fritekst =  preg_split('/\n|\r\n?/', wordwrap($leiebase->valg['efaktura_tekst1'], $kolonnebredde, "\n", true));
		$fritekst =  array_slice($fritekst, 0, 5);
		
		foreach( $fritekst as $tekstlinje ) {
			$fritekst1[] = (object)array(
				'a'	=> $leiebase->fastStrenglengde( mb_substr ($tekstlinje, 0, 40, 'UTF-8'), 40),
				'b'	=> $leiebase->fastStrenglengde( mb_substr ($tekstlinje, 40, 40, 'UTF-8'), 40),
				'c'	=> $leiebase->fastStrenglengde( mb_substr ($tekstlinje, 80, 16, 'UTF-8'), 40)
			);
		}
		

		// Fritekst 2 kan være maks. 5 linjer.
		$kolonnebredde = ($fakturamal-1) ? 96 : 80;
		$fritekst2 = array();
		$fritekst =  preg_split('/\n|\r\n?/', wordwrap($leiebase->valg['efaktura_tekst2'], $kolonnebredde, "\n", true));
		$fritekst =  array_slice($fritekst, 0, 5);
		
		foreach( $fritekst as $tekstlinje ) {
			$fritekst2[] = (object)array(
				'a'	=> $leiebase->fastStrenglengde( mb_substr ($tekstlinje, 0, 40, 'UTF-8'), 40),
				'b'	=> $leiebase->fastStrenglengde( mb_substr ($tekstlinje, 40, 40, 'UTF-8'), 40),
				'c'	=> $leiebase->fastStrenglengde( mb_substr ($tekstlinje, 80, 16, 'UTF-8'), 40)
			);
		}
		

		$avsenderBankkonto	= preg_replace('/[^0-9]+/', '', $leiebase->valg['bankkonto']);
		$avsenderOrgNr		= preg_replace('/[^0-9]+/', '', $leiebase->valg['orgnr']);
		
		$forbrukerPostnr = preg_replace('/[^0-9]+/', '', $leieforhold->hent('adresse')->postnr);
		$forbrukerLandskode = $leieforhold->hent('land');
		if( !$forbrukerLandskode or $forbrukerLandskode == "Norge" ) {
			$forbrukerLandskode == "";
			$forbrukerPostnr = substr($forbrukerPostnr, 0, 4) . "   ";
		}
		else {
			$forbrukerPostnr = substr($forbrukerPostnr, 7);
		}
		
		$betalerPostnr = preg_replace('/[^0-9]+/', '', $leieforhold->hent('adresse')->postnr);
		$betalerLandskode = $leieforhold->hent('land');
		if( !$betalerLandskode or $betalerLandskode == "Norge" ) {
			$betalerLandskode == "";
			$betalerPostnr = substr($betalerPostnr, 0, 4) . "   ";
		}
		else {
			$betalerPostnr = substr($betalerPostnr, 7);
		}
		
		$avsenderPostnr = $leiebase->valg['postnr'];

		$this->gjengivelsesdata = array(
			'leiebase'			=> $leiebase,
			
			'avsender'			=> $leiebase->valg['utleier'],
			'avsenderadresse1'	=> $leiebase->valg['adresse'],
			'avsenderadresse2'	=> "",
			'avsenderPostnr'	=> $leiebase->valg['postnr'],
			'avsenderPoststed'	=> $leiebase->valg['poststed'],
			'avsenderLandskode'	=> "",
			'avsenderTelefon'	=> $leiebase->valg['telefon'],
			'avsenderTelefaks'	=> $leiebase->valg['telefax'],
			'avsenderEpost'		=> $leiebase->valg['epost'],
			'avsenderOrgNr'		=> $avsenderOrgNr,
			'avsenderBankkonto'	=> $avsenderBankkonto,
			'betaler'			=> "",
			'betaleradresse1'	=> "",
			'betaleradresse2'	=> "",
			'betalerPostnr'		=> "",
			'betalerPoststed'	=> "",
			'betalerLandskode'	=> "",
			'transaksjonsnummer'=> $param['transaksjonsnummer'],
			'leieforhold'		=> (string) $leieforhold,
			'leieobjekt'		=> $leiebase->leieobjekt( $this->hent('leieobjekt'), true, true ),
			'kid'				=> $this->hent('kid'),
			'forkortetNavn'		=> $leieforhold->hent('kortnavn'),
			'fremmedreferanse'	=> $fremmedreferanse,
			'forbrukerNavn'		=> $leieforhold->hent('navn'),
			'forbrukeradresse1'	=> $leieforhold->hent('adresse')->adresse1,
			'forbrukeradresse2'	=> $leieforhold->hent('adresse')->adresse2,
			'forbrukerPostnr'	=> $forbrukerPostnr,
			'forbrukerPoststed'	=> $leieforhold->hent('adresse')->poststed,
			'forbrukerLandskode'=> $forbrukerLandskode,
			'fakturatype'		=> $fakturatype,
			'efakturareferanse'	=> $leieforhold->hent('efakturareferanse'),
			'summaryType'		=> isset($param['summaryType'] ) && $param['summaryType'] ? 1 : 0,
										// 0 = eFaktura
										// 1 = avtalegiro
			'mal'				=> $fakturamal,	// 01 = NETS' efakturamal 1
												// 02 = NETS' efakturamal 2
			'reklame'			=> 0,	// 0 = Ingen reklame
										// 1 = Reklame
			'fakturanummer'		=> $this->hentId(),
			'forfallsdato'		=> $this->hent('forfall'),
			'beløp'				=> $this->hent('utestående'),
			'fakturadato'		=> (
									isset($param['utskriftsdato'] )
									? $param['utskriftsdato']
									: (
										$this->hent('utskriftsdato')
										? $this->hent('utskriftsdato')
										: new DateTime
									)
			),
			
			// Angi inntil 5 valgfrie tekstfelter
			'tekstfelter'		=> array(
				(object)array(
					'ledetekst'			=>	"Leieforhold",
					'verdi'				=>	"{$leieforhold}: {$leieforhold->hent('navn')}"
				),
				(object)array(
					'ledetekst'			=>	"Leieobjekt",
					'verdi'				=>	"{$leieobjekt->hent('type')} {$leieobjekt}"
				),
				(object)array(
					'ledetekst'			=>	"&nbsp;",
					'verdi'				=>	"{$leieobjekt->hent('beskrivelse')}"
				),
			),
			
			'fritekst1'			=> $fritekst1,

			'fritekst2'			=> $fritekst2,

			'overskriftFakturakunde'	=> "Leietaker",
			'overskriftFakturabetaler'	=> "Betaler",
			'detaljoverskrift'	=> $detaljoverskrift,
			'fakturadetaljer'	=> $fakturadetaljer
			
		);

		break;
	}

	case "epostfaktura_html":
	{
		$leieforhold = $this->hent('leieforhold');
		$leieobjekt = $leieforhold->hent('leieobjekt');
		
		$detaljer = array();
		
		foreach( $this->hent('krav') as $krav) {
			$detaljer[] = (object)array(
				'id'			=> $krav->hentId(),
				'tekst'			=> $krav->hent('tekst'),
				'beløp'			=> $this->leiebase->kr($krav->hent('beløp')),
				'utestående'	=> $this->leiebase->kr($krav->hent('utestående'))
			);
		}

		$this->gjengivelsesdata = array(
			'leiebase'			=> $this->leiebase,
			
			'logotekst'			=> $leiebase->valg['utleier'],
			'avsender'			=> $leiebase->valg['utleier'],
			'avsenderadresse'	=> $leiebase->valg['adresse'] . "<br>" . $leiebase->valg['postnr'] . "&nbsp;" . $leiebase->valg['poststed'],
			'avsenderTelefon'	=> $leiebase->valg['telefon'],
			'avsenderHjemmeside' => $leiebase->valg['hjemmeside'],
			'avsenderEpost'		=> $leiebase->valg['epost'],
			'avsenderOrgNr'		=> $leiebase->valg['orgnr'],
			'avsenderBankkonto'	=> $leiebase->valg['bankkonto'],
			'mottaker'			=> $leieforhold->hent('navn'),
			'mottakeradresse'	=> nl2br($leieforhold->hent('adressefelt')),
			'leieforholdnr'		=> (string) $leieforhold,
			'leieforholdbeskrivelse' => $leieobjekt->hent('beskrivelse'),
			'gironr'			=> $this->hentId(),
			'kid'				=> $this->hent('kid'),
			'dato'				=> (
									$this->hent('utskriftsdato')
									? $this->hent('utskriftsdato')->format('d.m.Y')
									: date_create()->format('d.m.Y')
								),
			'forfall'			=> $this->hent('forfall')->format('d.m.Y'),
			'girobeløp'			=> $this->leiebase->kr($this->hent('beløp')),
			'utestående'		=> $this->leiebase->kr($this->hent('utestående')),
			'fradrag'			=> $this->leiebase->kr($this->hent('beløp') - $this->hent('utestående')),
			'fbo'				=> $leieforhold->hent('fbo') ? true : false,
			'efaktura'			=> $leieforhold->hent('efaktura') ? true : false,
			'avtalegiro'		=> $leieforhold->hent('fboTrekkrav') ? true : false,
			'efakturareferanse'	=> $leieforhold->hent('efakturareferanse'),
			'efakturareferanse'	=> $leieforhold->hent('efakturareferanse'),
			'bunntekst'			=> $this->leiebase->valg['eposttekst'],
			'detaljer'			=> $detaljer
		);

		break;

	}
	
	case "epostfaktura_txt":
	{
		$leieforhold = $this->hent('leieforhold');
		$leieobjekt = $leieforhold->hent('leieobjekt');
		
		$detaljer = array();
		
		foreach( $this->hent('krav') as $krav) {
			$detaljer[] = (object)array(
				'id'			=> $krav->hentId(),
				'tekst'			=> $krav->hent('tekst'),
				'beløp'			=> $this->leiebase->kr($krav->hent('beløp')),
				'utestående'	=> $this->leiebase->kr($krav->hent('utestående'))
			);
		}

		$this->gjengivelsesdata = array(
			'leiebase'			=> $this->leiebase,
			
			'logotekst'			=> $leiebase->valg['utleier'],
			'avsender'			=> $leiebase->valg['utleier'],
			'avsenderadresse'	=> $leiebase->valg['adresse'] . "<br>" . $leiebase->valg['postnr'] . "&nbsp;" . $leiebase->valg['poststed'],
			'avsenderTelefon'	=> $leiebase->valg['telefon'],
			'avsenderHjemmeside' => $leiebase->valg['hjemmeside'],
			'avsenderEpost'		=> $leiebase->valg['epost'],
			'avsenderOrgNr'		=> $leiebase->valg['orgnr'],
			'avsenderBankkonto'	=> $leiebase->valg['bankkonto'],
			'mottaker'			=> $leieforhold->hent('navn'),
			'mottakeradresse'	=> nl2br($leieforhold->hent('adressefelt')),
			'leieforholdnr'		=> (string) $leieforhold,
			'leieforholdbeskrivelse' => $leieobjekt->hent('beskrivelse'),
			'gironr'			=> $this->hentId(),
			'kid'				=> $this->hent('kid'),
			'dato'				=> (
									$this->hent('utskriftsdato')
									? $this->hent('utskriftsdato')->format('d.m.Y')
									: date_create()->format('d.m.Y')
								),
			'forfall'			=> $this->hent('forfall')->format('d.m.Y'),
			'girobeløp'			=> $this->leiebase->kr($this->hent('beløp')),
			'utestående'		=> $this->leiebase->kr($this->hent('utestående')),
			'fradrag'			=> $this->leiebase->kr($this->hent('beløp') - $this->hent('utestående')),
			'fbo'				=> $leieforhold->hent('fbo') ? true : false,
			'efaktura'			=> $leieforhold->hent('efaktura') ? true : false,
			'avtalegiro'		=> $leieforhold->hent('avtalegiro') ? true : false,
			'efakturareferanse'	=> $leieforhold->hent('efakturareferanse'),
			'efakturareferanse'	=> $leieforhold->hent('efakturareferanse'),
			'bunntekst'			=> $this->leiebase->valg['eposttekst'],
			'detaljer'			=> $detaljer
		);

		break;

	}

	case "fbo-krav":
	{
		// Parametre:
		//		transaksjonsnummer heltall, påkrevd ved eFaktura
		//		utskriftsdato (DateTime-objekt) Overstyring av evt lagret dato
		//		forfall (DateTime-objekt) Overstyring av evt lagret forfall
	
		$leieforhold = $this->hent('leieforhold');
		$fbo		= $leieforhold->hent('fbo');
		
		$kravsett = $this->hent('krav');
		
		$smsNummer	= @$fbo->mobilnr;
					
		// Egenvarsel skal være sann i alle tilfeller hvor banken ikke trenger sende varsel:
		// - Dersom betaler ikke ønsker varsel
		// - Dersom betaler ønsker varsel sendt via SMS
		// - Dersom betaler har efaktura-avtale
		// - Dersom leiebasen har epostadresse på leieforholdet
		$egenvarsel = (
			!@$fbo->varsel
			or ($leiebase->valg['avtalegiro_sms'] and $smsNummer )
			or $leieforhold->hent('efakturaavtale')
			or $leieforhold->hent('brukerepost')
		) ? true : false;
	
		$fremmedreferanse = "Giro nr. {$this->hentId()}"; // Maks 25 tegn
		
		// Formater fakturadetaljene
		$fakturadetaljer = array();
		
		$spesifikasjon = "";
		foreach ( $kravsett  as $krav ) {
			$spesifikasjonslinje = $krav->hent('tekst');
			$beløpsfelt = "kr. " . number_format( $krav->hent('beløp'), 2, ',', ' ' );

			// Legg på linjeskift før hver ny spesifikasjon
			if( $spesifikasjon ) {
				$spesifikasjon .="\n";
			}
			
			$spesifikasjon .= "{$spesifikasjonslinje} {$beløpsfelt}";
		}

		$this->gjengivelsesdata = array(
			'leiebase'			=> $leiebase,
			
			'beløp'				=> $this->hent('utestående'),
			'forfallsdato'		=> $this->hent('forfall'),
			'forkortetNavn'		=> $leieforhold->hent('kortnavn'),
			'fremmedreferanse'	=> $fremmedreferanse,
			'kid'				=> $this->hent('kid'),
			'smsNummer'			=> $smsNummer,
			'spesifikasjon'		=> $spesifikasjon,
			'transaksjonsnummer'=> $param['transaksjonsnummer'],
			'transaksjonstype'	=> $egenvarsel ? 2 : 21
			
		);

		break;
	}

	case "pdf_giro":
	{
		
		// Dersom beløpet er negativt, skrives det istedet ut kreditnota
		if( $this->hent('beløp') < 0 ) {
			return $this->gjengi('pdf_kreditnota', $param);
		}
		
		$leieforhold = $this->hent('leieforhold');
		$fbo		= $leieforhold->hent('fbo');
		
		$avsenderadresse = "{$leiebase->valg['utleier']}\n{$leiebase->valg['adresse']}\n{$leiebase->valg['postnr']} {$leiebase->valg['poststed']}";

		$ubetalte = $this->mysqli->arrayData(array(
			'source' => "krav
						INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
			'fields' => "krav.id, krav.tekst, krav.beløp, krav.utestående",
			'where' => "leieforhold = '{$leieforhold}'
						AND utestående <> 0
						AND forfall < '" . date('Y-m-d') . "'"
		))->data;

		$gjeld = $this->mysqli->arrayData(array(
			'source' => "krav
						INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
			'fields' => "SUM(krav.utestående) AS utestående",
			'where' => "leieforhold = '{$leieforhold}'
						AND utestående <> 0
						AND forfall < '" . date('Y-m-d') . "'"
		))->data[0]->utestående;
		
		$sisteInnbetalinger = $this->mysqli->arrayData(array(
			'source' => "innbetalinger",
			'fields' => "dato, betaler, SUM(beløp) AS beløp, ref",
			'where' => "leieforhold = '{$leieforhold}'
						AND dato <= " . ( isset( $param['utskriftsdato'] ) ? $param['utskriftsdato']->format('Y-m-d') : ( $this->hent('utskriftsdato') ? "'{$this->hent('utskriftsdato')->format('Y-m-d')}'" : "NOW()") ),
			'groupfields' => "dato, betaler, ref",
			'orderfields' => "dato DESC",
			'limit'	=>	"0, 3"
		))->data;


		$this->gjengivelsesdata = array(
			'avsenderAdresse'		=> $avsenderadresse,
			'mottakerAdresse'		=> ($leieforhold->hent('navn') . "\n" . $leieforhold->hent('adressefelt')),
			
			'avsender'				=> $leiebase->valg['utleier'],
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
			
			'leieforhold'			=> $leieforhold,
			'kid'					=> $this->hent('kid'),
			'efakturareferanse'		=> $leieforhold->hent('efakturareferanse'),
			'gironr'				=> $this->hent('gironr'),
			'utskriftsdato'			=> ( isset($param['utskriftsdato'] ) ? $param['utskriftsdato'] : $this->hent('utskriftsdato') ),
			
			'leieforholdBeskrivelse'=> $leiebase->leieobjekt( $this->hent('leieobjekt'), true ),

			'forfall'				=> ( isset($param['forfall'] ) ? $param['forfall'] : $this->hent('forfall') ),
			'kravsett'				=> $this->hent('krav'), // array
			'girobeløp'				=> $this->hent('beløp'),
			'utestående'			=> $this->hent('utestående'),
			'blankettBetalingsinfo'	=> "",
			'ocrKid'				=> $this->hent('kid'),
			'ocrKontonummer'		=> $leiebase->valg['bankkonto'],
			'blankettbeløp'			=> $this->hent('utestående'),
			'tidligereUbetalt'		=> (object)array(
				'kravsett' => $ubetalte,
				'utestående' => $gjeld
			),
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

		if( $leiebase->valg['avtalegiro'] and $fbo ) {
			$this->gjengivelsesdata['blankettBetalingsinfo']
			=	"Du trenger ikke betale denne giroen.\nBeløpet trekkes fra kontoen din med avtalegiro på forfallsdato.";
			$this->gjengivelsesdata['ocrKid'] = "Skal ikke betales manuelt.";
			$this->gjengivelsesdata['kontrollsiffer'] = "";
			$this->gjengivelsesdata['ocrKontonummer'] = "Trekkes med AvtaleGiro.";
		}

		if( !is_a($param['pdf'], 'FPDF')) {
			$this->gjengivelsesdata['pdf'] = new FPDF;
		}

		break;
	}

	case "pdf_kreditnota":
	{
		$leieforhold = $this->hent('leieforhold');
		$fbo		= $leieforhold->hent('fbo');
		
		$avsenderadresse = "{$leiebase->valg['utleier']}\n{$leiebase->valg['adresse']}\n{$leiebase->valg['postnr']} {$leiebase->valg['poststed']}";

		$ubetalte = $this->mysqli->arrayData(array(
			'source' => "krav
						INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
			'fields' => "krav.id, krav.tekst, krav.beløp, krav.utestående",
			'where' => "leieforhold = '{$leieforhold}'
						AND utestående <> 0
						AND forfall < '" . date('Y-m-d') . "'"
		))->data;

		$gjeld = $this->mysqli->arrayData(array(
			'source' => "krav
						INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr",
			'fields' => "SUM(krav.utestående) AS utestående",
			'where' => "leieforhold = '{$leieforhold}'
						AND utestående <> 0
						AND forfall < '" . date('Y-m-d') . "'"
		))->data[0]->utestående;
		
		$sisteInnbetalinger = $this->mysqli->arrayData(array(
			'source' => "innbetalinger",
			'fields' => "dato, betaler, SUM(beløp) AS beløp, ref",
			'where' => "leieforhold = '{$leieforhold}'
						AND dato <= " . ( isset( $param['utskriftsdato'] ) ? $param['utskriftsdato']->format('Y-m-d') : ( $this->hent('utskriftsdato') ? "'{$this->hent('utskriftsdato')->format('Y-m-d')}'" : "NOW()") ),
			'groupfields' => "dato, betaler, ref",
			'orderfields' => "dato DESC",
			'limit'	=>	"0, 3"
		))->data;


		$this->gjengivelsesdata = array(
			'avsenderAdresse'		=> $avsenderadresse,
			'mottakerAdresse'		=> ($leieforhold->hent('navn') . "\n" . $leieforhold->hent('adressefelt')),
			
			'avsender'				=> $leiebase->valg['utleier'],
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
			
			'leieforhold'			=> $leieforhold,
			'kid'					=> $this->hent('kid'),
			'efakturareferanse'		=> $leieforhold->hent('efakturareferanse'),
			'gironr'				=> $this->hent('gironr'),
			'utskriftsdato'			=> ( isset($param['utskriftsdato'] ) ? $param['utskriftsdato'] : $this->hent('utskriftsdato') ),
			
			'leieforholdBeskrivelse'=> $leiebase->leieobjekt( $this->hent('leieobjekt'), true ),

			'forfall'				=> ( isset($param['forfall'] ) ? $param['forfall'] : $this->hent('forfall') ),
			'kravsett'				=> $this->hent('krav'), // array
			'girobeløp'				=> $this->hent('beløp'),
			'utestående'			=> $this->hent('utestående'),
			'blankettBetalingsinfo'	=> "",
			'ocrKid'				=> $this->hent('kid'),
			'ocrKontonummer'		=> $leiebase->valg['bankkonto'],
			'blankettbeløp'			=> $this->hent('utestående'),
			'tidligereUbetalt'		=> (object)array(
				'kravsett' => $ubetalte,
				'utestående' => $gjeld
			),
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

		if( $leiebase->valg['avtalegiro'] and $fbo ) {
			$this->gjengivelsesdata['blankettBetalingsinfo']
			=	"Du trenger ikke betale denne giroen.\nBeløpet trekkes fra kontoen din med avtalegiro på forfallsdato.";
			$this->gjengivelsesdata['ocrKid'] = "Skal ikke betales manuelt.";
			$this->gjengivelsesdata['kontrollsiffer'] = "";
			$this->gjengivelsesdata['ocrKontonummer'] = "Trekkes med AvtaleGiro.";
		}

		if( !is_a($param['pdf'], 'FPDF')) {
			$this->gjengivelsesdata['pdf'] = new FPDF;
		}

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

	case $this->idFelt:
	{
		if ( $this->data === null and !$this->last()) {
			return false;
		}		
		return $this->data->$egenskap;
		break;
	}

	case "utskriftsdato":
	case "kid":
	case "leieforhold":
	case "leieobjekt":
	case "format":
	case "forfall":
	case "beløp":
	case "utestående":
	case "regning_til_objekt":
	case "regningsobjekt":
	{
		if ( $this->data === null and !$this->last()) {
			throw new Exception("Klarte ikke laste Giro({$this->id})");
		}		
		return $this->data->$egenskap;
		break;
	}

	case "utskriftsposisjon":
	{
		if ( $this->data == null ) {
			$this->last();
		}
		
		if ( !isset($this->utskriftsposisjon[$param['rute']]) ) {
			$this->lastUtskriftsposisjon($param['rute']);
		}
		return $this->utskriftsposisjon[$param['rute']];
		break;
	}

	case "krav":
	{
		if ( $this->krav === null ) {
			$this->lastKrav();
		}		
		return $this->krav;
		break;
	}

	case "betalt":
	{
		if ( $this->hent('utestående') != 0 ) {
			return false;
		}
		$betalinger = $this->hentBetalinger();
		if(!$betalinger) {
			return $this->hent('forfall');
		}
		return end($betalinger)->hent('dato');
		break;
	}

	case "purringer":
	{
		if ( $this->purringer === null ) {
			$this->lastPurringer();
		}		
		return $this->purringer;
		break;
	}

	case "antallGebyr":
	case "antallPurringer":
	case "sisteGebyr":
	case "sisteGebyrpurring":
	case "sistePurring":
	{
		if ( $this->purringer === null ) {
			$this->lastPurringer();
		}		
		return $this->purrestatistikk->$egenskap;
		break;
	}

	case "sisteForfall":
	{
		if( ( !$this->hent('sistePurring') )) {
			return $this->hent('forfall');
		}
		else {
			return $this->hent('sistePurring')->hent('purreforfall');
		}
		break;
	}

	case "fboTrekkrav":
	{
		if ( $this->fboTrekkrav === null ) {
			$this->lastFboTrekkrav();
		}		
		return $this->fboTrekkrav;
		break;
	}

	case "efaktura":
	{
		if ( $this->efaktura === null ) {
			$this->lastEfaktura();
		}		
		return $this->efaktura;
		break;
	}

	default:
	{
		return null;
		break;
	}
	}

}


/*	Hent Betalinger
Se etter betalinger ført mot denne giroen.
******************************************
--------------------------------------
retur: (liste av Innbetalingsobjekter) Innbetalingene
*/
public function hentBetalinger() {
	$tp = $this->mysqli->table_prefix;
	
	$innbetalinger = $this->mysqli->arrayData(array(
		'source'		=>	"{$tp}innbetalinger as innbetalinger INNER JOIN {$tp}krav AS krav ON innbetalinger.krav = krav.id",
		'where'			=> "krav.gironr = '{$this->hentId()}'",
		'distinct'		=> true,
		'fields'		=> "innbetalinger.innbetaling as id",
		'orderfields'	=> "innbetalinger.dato, innbetalinger.innbetaling",
		'class'			=> "Innbetaling"
	));

	return $innbetalinger->data;
}



// Lagre giroen som PDF på tjeneren
/****************************************/
//	$param
//		navn	(streng) filnavnet, standardverdi er gironr.pdf
//		gjengivelsesfil		(streng) malen som brukes for utskriften
//		erstatt (boolsk) overskriv eksisterende fil
//	--------------------------------------
public function lagrePdf( $param = array() ) {
	settype( $param,					'array' );
	settype( $param['erstatt'],			'boolean' );
	settype( $param['navn'],			'string' );
	settype( $param['gjengivelsesfil'],	'string' );

	$param['gjengivelsesfil'] = $param['gjengivelsesfil'] ? $param['gjengivelsesfil'] : "pdf_giro";

	if( !$this->hent('utskriftsdato') ) {
		return false;
	}

	if( !$navn = $param['navn'] ) {
		$navn = $this->hentId() . ".pdf";
	}

	$fil = LEIEBASEN_FILARKIV . "/giroer/{$navn}";
	if( $param['erstatt'] or !file_exists( $fil ) ) {

		$pdf = new FPDF;
		$pdf->SetAutoPageBreak(false);
		$this->gjengi( $param['gjengivelsesfil'], array(
			'pdf' => $pdf
		) );
		if ( file_exists( $fil )) {
			unlink( $fil );
		}
		$pdf->Output( $fil, 'F' );

	}

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
		
		'fields' =>			"{$this->tabell}.{$this->idFelt} AS id,
							{$this->tabell}.*,\n"
						.	"MIN(krav.forfall) AS forfall,
							SUM(krav.beløp) AS beløp,
							SUM(krav.utestående) AS utestående,\n"
						.	"leieforhold.leieobjekt,
							leieforhold.regningsperson,
							leieforhold.regning_til_objekt,
							leieforhold.regningsobjekt,
							leieforhold.regningsadresse1,
							leieforhold.regningsadresse2,
							leieforhold.postnr,
							leieforhold.poststed,
							leieforhold.land",
						
		'source' => 		"{$tp}{$this->tabell} AS {$this->tabell}\n"
						.	"LEFT JOIN {$tp}krav AS krav ON {$this->tabell}.{$this->idFelt} = krav.gironr\n"
						.	"LEFT JOIN (
	SELECT kontrakter.*
	FROM (
		SELECT MAX(kontraktnr) as kontraktnr, leieforhold
		FROM kontrakter
		GROUP BY leieforhold
	) as sistekontrakt
	INNER JOIN kontrakter on sistekontrakt.kontraktnr = kontrakter.kontraktnr
) AS leieforhold ON {$this->tabell}.leieforhold = leieforhold.leieforhold\n"
						.	"LEFT JOIN {$tp}leieobjekter AS leieobjekter ON leieforhold.leieobjekt = leieobjekter.leieobjektnr\n",
						
		'groupfields'	=>	"{$this->tabell}.{$this->idFelt}",
		'where'			=>	"{$tp}{$this->tabell}.{$this->idFelt} = '$id'"
	));
	if( $this->data = @$resultat->data[0] ) {
		$this->id = $id;

		$this->data->leieforhold = new Leieforhold( $this->data->leieforhold );

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


// Last evt efakturadetaljer fra databasen
/****************************************/
//	--------------------------------------
protected function lastEfaktura() {
	$tp = $this->mysqli->table_prefix;
	if ( !$id = $this->id ) {
		$this->efaktura = false;
		return false;
	}

	$resultat = $this->mysqli->arrayData(array(
		'returnQuery'	=> true,
		'fields'		=>	"efakturaer.id,
							efakturaer.forsendelsesdato,
							efakturaer.forsendelse,
							efakturaer.oppdrag,
							efakturaer.transaksjon,
							efakturaer.kvittert_dato,
							efakturaer.kvitteringsforsendelse,
							efakturaer.status",
		'source'		=> 	"{$tp}efakturaer AS efakturaer\n",
		'where'			=>	"efakturaer.giro = '$id'"
	));

	if( $resultat->totalRows ) {
		$this->efaktura = $resultat->data[0];
		$this->efaktura->forsendelsesdato = date_create_from_format('Y-m-d', $this->efaktura->forsendelsesdato);
		$this->efaktura->kvittertDato = date_create_from_format('Y-m-d', $this->efaktura->kvittert_dato);
	}
	else {
		$this->efaktura = false;
	}
	return true;
}


// Last evt fbo trekk-krav (avtalegiro) fra databasen
/****************************************/
//	--------------------------------------
protected function lastFboTrekkrav() {
	$tp = $this->mysqli->table_prefix;
	if ( !$id = $this->id ) {
		$this->fboTrekkrav = false;
		return false;
	}

	$resultat = $this->mysqli->arrayData(array(
		'returnQuery'	=> true,
		'fields'		=>	"fbo_trekkrav.id,
							fbo_trekkrav.overføringsdato,
							fbo_trekkrav.varslet,
							fbo_trekkrav.egenvarsel,
							fbo_trekkrav.mobilnr",
		'source'		=> 	"{$tp}fbo_trekkrav AS fbo_trekkrav\n",
		'where'			=>	"fbo_trekkrav.gironr = '$id'"
	));

	if( $resultat->totalRows ) {
		$this->fboTrekkrav = $resultat->data[0];
		$this->fboTrekkrav->overføringsdato = date_create_from_format('Y-m-d', $this->fboTrekkrav->overføringsdato);
	}
	else {
		$this->fboTrekkrav = false;
	}
	return true;
}


// Last kravene i giroen fra databasen
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
		'source'		=> 	"{$tp}krav AS krav\n",
		'where'			=>	"krav.gironr = '$id'",
		'orderfields'	=>	"IF(krav.forfall IS NULL, 1, 0), krav.forfall, krav.kravdato, krav.type, krav.id"
	));

	$this->krav = $resultat->data;

}


// Last alle purringene på giroen fra databasen
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
		'where'			=> "{$tp}krav.gironr = '$id'",
		'orderfields'	=> "purringer.purredato, purringer.blankett, purringer.nr"
	));

	$this->purringer = $resultat->data;

	// Lagre antall og siste purring og gebyr
	$this->purrestatistikk = (object)array(
		'sistePurring'		=> null,
		'antallPurringer'	=> count($this->purringer),
		'sisteGebyrpurring'	=> null,
		'sisteGebyr'		=> null,
		'antallGebyr'		=> 0
	);
	foreach( $this->purringer as $purring ) {
		$this->purrestatistikk->sistePurring = $purring;
		if( $gebyr = $purring->hent('purregebyr') ) {
			$this->purrestatistikk->sisteGebyrpurring = $purring;
			$this->purrestatistikk->sisteGebyr = $gebyr;
			$this->purrestatistikk->antallGebyr += 1;
		}
	}

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


// Oppdaterer kvitteringsdata og status i eFaktura-tabellen
/****************************************/
//	$param		Konfigurasjoner:
//		dato:	påkrevd DateTime-objekt
//		kvitteringsforsendelse:	påkrevd heltall oppdragsnr
//		status:	streng 'mottatt', 'ok'
//	--------------------------------------
//	retur: bools suksessangivelse
public function kvitterEfaktura( $param ) {
	$tp = $this->mysqli->table_prefix;
	
	settype( $param, 'object' );
	
	if(
		!is_a( $param->dato, DateTime )
		|| !$param->kvitteringsforsendelse
		|| !$param->status
	) {
		return false;
	}
	
	return $this->mysqli->saveToDb(array(
		'update'		=> true,
		'table'			=> "efakturaer",
		'where'			=> "giro = '{$this->hent('id')}'",
		'fields'		=> array(
			'kvittert_dato'		=> $param->dato->format('Y-m-d'),
			'kvitteringsforsendelse' => $param->kvitteringsforsendelse,
			'status'			=> $param->status
		)
	));
}


// Nedlast giroen som PDF fra tjeneren
/****************************************/
//	$param
//	--------------------------------------
public function nedlastPdf( $param = array() ) {

	if( !$this->hent('utskriftsdato') ) {
		return false;
	}

	$this->lagrePdf( );

	$fil = LEIEBASEN_FILARKIV . "/giroer/{$this->hentId()}.pdf";
	
	header('Content-type: application/pdf');
	header('Content-Disposition: inline; filename="' . $this->hentId() . '.pdf"');
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: ' . filesize($fil));
	header('Accept-Ranges: bytes');

	@readfile( $fil );

}




// Oppretter en ny giro i databasen og tildeler egenskapene til dette objektet
/****************************************/
//	$egenskaper (array/objekt) Alle egenskapene det nye objektet skal initieres med
//	--------------------------------------
public function opprett($egenskaper = array()) {
	throw new Exception('Ny Giro forsøkt opprettet via Giro::opprett(), men metoden er ennå ikke utviklet og klar for bruk');
	return false;

}



// Kopler giroen til en oppføring i eFaktura-tabellen
/****************************************/
//	$param		Konfigurasjoner:
//		forsendelsesdato:	påkrevd DateTime-objekt
//		forsendelse:		påkrevd streng
//		oppdrag:			påkrevd heltall oppdragsnr
//		transaksjon:		påkrevd heltall
//		status:				null, 'mottatt', 'ok'
//	--------------------------------------
//	retur: bools suksessangivelse
public function opprettEfaktura( $param = array() ) {
	$tp = $this->mysqli->table_prefix;
	
	if( $param === null ) {
		return $this->mysqli->query("DELETE FROM efakturaer WHERE giro = '{$this->hent('id')}'");
	}
	
	settype( $param, 'object' );
	
	if(
		!is_a( $param->forsendelsesdato, 'DateTime' )
		|| !$param->forsendelse
		|| !$param->oppdrag
	) {
		return false;
	}
	
	if( $this->mysqli->arrayData(array(
		'source'	=> "{$tp}efakturaer",
		'where'		=> "giro = '{$this->hent('id')}'"
	))->totalRows ) {
		return false;
	}
	
	$resultat = $this->mysqli->saveToDb(array(
		'insert'		=> true,
		'returnQuery'	=> true,
		'table'			=> "{$tp}efakturaer",
		'fields'		=> array(
			'giro'		=> $this->hent('gironr'),
			'forsendelsesdato'	=> $param->forsendelsesdato->format('Y-m-d'),
			'forsendelse'		=> $param->forsendelse,
			'oppdrag'			=> $param->oppdrag,
			'transaksjon'		=> @$param->transaksjon,
			'status'			=> isset( $param->status ) ? $param->status : ""
		)
	));
	
	if( $resultat->success ) {
		$this->sett('format', 'eFaktura');
	}

	return $resultat->success;
}


// Kopler giroen til en oppføring i fbo trekkrav-tabellen
/****************************************/
//	$param		Konfigurasjoner:
//		overføringsdato:	påkrevd DateTime-objekt
//		mobilnr:			streng, Mobilnummer for SMS-varsling
//		egenvarsel:			boolsk, Usann for å be banken sende varsel om trekk
//	--------------------------------------
//	retur: bools suksessangivelse
public function opprettFboTrekkrav( $param = array() ) {
	$tp = $this->mysqli->table_prefix;
	
	if( $param === null ) {
		return $this->mysqli->query("DELETE FROM fbo_trekkrav WHERE gironr = '{$this->hent('id')}'");
	}
	
	settype( $param, 'object' );
	
	if(
		!is_a( $param->overføringsdato, 'DateTime' )
	) {
		return false;
	}
	
	if( $this->mysqli->arrayData(array(
		'source'	=> "{$tp}fbo_trekkrav",
		'where'		=> "gironr = '{$this->hent('id')}'"
	))->totalRows ) {
		return false;
	}
	
	$resultat = $this->mysqli->saveToDb(array(
		'insert'		=> true,
		'returnQuery'	=> true,
		'table'			=> "{$tp}fbo_trekkrav",
		'fields'		=> array(
			'gironr'		=> $this->hent('gironr'),
			'leieforhold'	=> $this->hent('leieforhold'),
			'beløp'			=> $this->hent('utestående'),
			'forfallsdato'	=> $this->hent('forfall')->format('Y-m-d'),
			'mobilnr'		=> @$param->mobilnr,
			'egenvarsel'	=> isset( $param->egenvarsel ) ? $param->egenvarsel : false
		)
	));
	
	return $resultat->success;
}


// Lag purring på denne giroen
/****************************************/
//	$param
//		blankett	(streng) blankettreferansen som skal knytte purringene sammen
//		purremåte	(streng) Purremåte
//		purregebyr	(Krav-objekt) Purregebyret
//		purredato	(DateTime-objekt) blankettreferansen som skal knytte purringene sammen
//	--------------------------------------
public function purr($param = array()) {
	$tp = $this->mysqli->table_prefix;
	$resultat = true;
	
	if ( !$blankett = $param['blankett'] ) {
		$blankett = time() . "-" . $this->hent('leieforhold') . "-" . $this->id;
	}
	
	if ( !$purremåte = $param['purremåte'] ) {
		$purremåte = "";
	}
	
	$purregebyr = null;
	if ( is_a( $param['purregebyr'], 'Krav' ) ) {
		$purregebyr = $param['purregebyr'];
	}
	
	if ( is_a( $param['purredato'], 'DateTime' ) ) {
		$purredato = $param['purredato'];
	}
	else {
		$purredato = new DateTime( $param['purredato'] );
	}
	
	$purreforfall = clone $purredato;
	$purreforfall->add( new DateInterval( $this->leiebase->valg['purreforfallsfrist'] ) );
	
	$sisteForfall = $this->hent('sisteForfall');
	if( $sisteForfall == null or $sisteForfall > $purredato ) {
		return false;
	}
	
	$girokrav = $this->hent('krav');
	
	foreach( $girokrav as $krav ) {
		$resultat = $resultat && $this->mysqli->saveToDb(array(
			'returnQuery'	=> true,
			'insert'	=> true,
			'table'		=> "{$tp}purringer",
			'fields'	=> array(
				'blankett'	=> $blankett,
				'krav'	=> $krav->id,
				'purredato'	=> $purredato->format('Y-m-d'),
				'purremåte'	=> $purremåte,
				'purrer'	=> $this->leiebase->bruker['navn'],
				'purregebyr'	=> $purregebyr,
				'purreforfall'	=> $purreforfall->format('Y-m-d')
			)
		))->success;
	}
	
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
	
	switch( $egenskap ) {
	
	case "utskriftsdato":
		if ( $verdi and !is_a($verdi, 'DateTime' ) ) {
			$verdi = new DateTime( $verdi );
		}		

		$resultat = $this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "{$tp}{$this->tabell} as {$this->tabell}
							INNER JOIN {$tp}krav as krav ON {$this->tabell}.gironr = krav.gironr",
			'where'		=> "{$this->tabell}.{$this->idFelt} = '{$this->id}'",
			'fields'	=> array(
				"{$this->tabell}.utskriftsdato"	=> ( $verdi ? $verdi->format('Y-m-d H:i:s') : null ),
				"krav.utskriftsdato"			=> ( $verdi ? $verdi->format('Y-m-d H:i:s') : null )
			)
		));

		$this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "{$tp}fbo_trekkrav as fbo_trekkrav",
			'where'		=> "gironr = '{$this->id}'",
			'fields'	=> array(
				'varslet'	=> ( $verdi ? $verdi->format('Y-m-d') : null )
			)
		));
		
		// Tving ny lasting av data:
		$this->data = null;
		$this->krav = null;

		return $resultat->success;
		break;

	case "forfall":
		if ( $verdi and !is_a($verdi, 'DateTime' ) ) {
			$verdi = new DateTime( $verdi );
		}		
		
		// Forfallsdato kan ikke nulles på giroer som allerede er skrevet ut
		if(!$verdi && $this->hent('utskriftsdato')) {
			return false;
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
		
		$resultat = $this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "{$tp}krav as krav",
			'where'		=> "krav.gironr = '{$this->id}'",
			'fields'	=> array(
				"krav.forfall"	=> ( $verdi ? $verdi->format('Y-m-d') : null )
			)
		));

		// Tving ny lasting av data:
		$this->data = null;
		$this->krav = null;

		return $resultat->success;
		break;

	case "format":
		return $this->mysqli->saveToDb(array(
			'update'	=> true,
			'table'		=> "{$tp}{$this->tabell} as {$this->tabell}",
			'where'		=> "{$this->tabell}.{$this->idFelt} = '{$this->id}'",
			'fields'	=> array(
				"{$this->tabell}.{$egenskap}"	=> $verdi
			)
		))->success;
		break;

	default:
		return false;
		break;

	}

}


// Registrerer at det har blitt sendt varsel om fbo trekkrav
/****************************************/
//	$param		Konfigurasjoner:
//		overføringsdato:	påkrevd DateTime-objekt
//		mobilnr:			streng, Mobilnummer for SMS-varsling
//		egenvarsel:			boolsk, Usann for å be banken sende varsel om trekk
//	--------------------------------------
//	retur: bools suksessangivelse
public function varsleFboTrekkrav( $tidspunkt = null ) {
	$tp = $this->mysqli->table_prefix;
	
	if( $tidspunkt === null ) {
		$tidspunkt = new DateTime();
	}
	
	if( !$this->mysqli->arrayData(array(
		'source'	=> "{$tp}fbo_trekkrav",
		'where'		=> "gironr = '{$this->hent('id')}'"
	))->totalRows ) {
		return false;
	}
	
	$resultat = $this->mysqli->saveToDb(array(
		'update'		=> true,
		'where'			=> "gironr = '{$this->hent('id')}'",
		'returnQuery'	=> true,
		'table'			=> "{$tp}fbo_trekkrav",
		'fields'		=> array(
			'varslet'		=> $tidspunkt->format('Y-m-d')
		)
	));
	
	return $resultat->success;
}


}?>