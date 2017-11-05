<?php
/*********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
*********************************************/

/********************************************/
//	$avsender
//	$avsenderadresse1
//	$avsenderadresse2
//	$avsenderPostnr
//	$avsenderPoststed
//	$avsenderLandskode
//	$avsenderEpost
//	$avsenderOrgNr
//	$avsenderTelefon
//	$avsenderBankkonto
//	$beløp	nummer
//	$detaljoverskrift
//	$efakturareferanse (f.eks kundenummer)
//	$fakturadato DateTime-objekt
//	$fakturadetaljer		array med inntil 999 stdClass-objekter. Hvert objekt utgjør en tekstlinje
//		->a		Kolonne 1
//		->b		Kolonne 2
//		->c		Kolonne 3	
//	$fakturanummer
//	$fakturatype		Påkrevd frifelt (eks. Faktura/Invoice)
//	$betaler
//	$betaleradresse1
//	$betaleradresse2
//	$betalerPostnr
//	$betalerPoststed
//	$betalerLandskode
//	$forbrukerNavn
//	$forbrukeradresse1
//	$forbrukeradresse2
//	$forbrukerPostnr
//	$forbrukerPoststed
//	$forbrukerLandskode
//	$forfallsdato DateTime-objekt
//	$forkortetNavn
//	$fremmedreferanse
//	$fritekst1		array med inntil 5 stdClass-objekter. Hvert objekt utgjør en tekstlinje
//		->a		Kolonne 1
//		->b		Kolonne 2
//		->c		Kolonne 3					
//	$fritekst1		array med inntil 5 stdClass-objekter. Hvert objekt utgjør en tekstlinje
//		->a		Kolonne 1
//		->b		Kolonne 2
//		->c		Kolonne 3					
//	$kid
//	$leieforhold
//	$leieobjekt
//	$mal				efaktura-mal 1 eller 2
//	$overskriftFakturakunde
//	$overskriftFakturabetaler
//	$reklame			Reklame eller ikke
//	$spesifikasjoner	array med stdClass spesifikasjonsobjekter
//		->spesifikasjon
//			1 = Faste felter,
//			2 = Variable felter,
//			3 = Fritekst før Fakturadetaljer,
//			4 = Overskrifter,
//			5 = Fakturalinjer,
//			6 = Fritekst etter Fakturaspesifikasjon
//		->linje			Se tekniske spesifikasjoner fra NETS
//		->kolonne		Se tekniske spesifikasjoner fra NETS
//		->tekst			Se tekniske spesifikasjoner fra NETS
//	$summaryType		Vanlig faktura = 0, AvtaleGiro = 1
//	$tekstfelter	array med inntil 5 tekstfelter, angitt som objekter med ledetekst og verdi.
//	$transaksjonsnummer


/********************************************/

$avsender = $leiebase->fastStrenglengde(
	$avsender,
	30, " ", STR_PAD_RIGHT
);

$avsenderadresse1 = $leiebase->fastStrenglengde(
	$avsenderadresse1,
	30, " ", STR_PAD_RIGHT
);

$avsenderadresse2 = $leiebase->fastStrenglengde(
	$avsenderadresse2,
	30, " ", STR_PAD_RIGHT
);

$avsenderEpost = $leiebase->fastStrenglengde(
	$avsenderEpost,
	64, " ", STR_PAD_RIGHT
);

$avsenderOrgNr = $leiebase->fastStrenglengde(
	$avsenderOrgNr,
	18, " ", STR_PAD_RIGHT
);

$avsenderPostnr = $leiebase->fastStrenglengde(
	$avsenderPostnr,
	7, " ", STR_PAD_RIGHT
);

$avsenderPoststed = $leiebase->fastStrenglengde(
	$avsenderPoststed,
	25, " ", STR_PAD_RIGHT
);

$avsenderLandskode = $leiebase->fastStrenglengde(
	$avsenderLandskode,
	3, " ", STR_PAD_RIGHT
);

$avsenderTelefon = $leiebase->fastStrenglengde(
	$avsenderTelefon,
	20, " ", STR_PAD_RIGHT
);

$avsenderTelefaks = $leiebase->fastStrenglengde(
	$avsenderTelefaks,
	20, " ", STR_PAD_RIGHT
);

$transaksjonsnummer = $leiebase->fastStrenglengde(
	$transaksjonsnummer,
	7, "0", STR_PAD_LEFT
);

$kid	= $leiebase->fastStrenglengde(
	$kid,
	25, " ", STR_PAD_LEFT
);

$leieforhold	= $leiebase->fastStrenglengde(
	$leieforhold,
	40, " ", STR_PAD_LEFT
);

$leieobjekt	= $leiebase->fastStrenglengde(
	$leieobjekt,
	40, " ", STR_PAD_LEFT
);

$forkortetNavn = $leiebase->fastStrenglengde(
	$forkortetNavn,
	10, " ", STR_PAD_RIGHT
);

$fremmedreferanse = $leiebase->fastStrenglengde(
	$fremmedreferanse,
	25, " ", STR_PAD_RIGHT
);

$forbrukerNavn = $leiebase->fastStrenglengde(
	$forbrukerNavn,
	30, " ", STR_PAD_RIGHT
);

$forbrukeradresse1 = $leiebase->fastStrenglengde(
	$forbrukeradresse1,
	30, " ", STR_PAD_RIGHT
);

$forbrukeradresse2 = $leiebase->fastStrenglengde(
	$forbrukeradresse2,
	30, " ", STR_PAD_RIGHT
);

// Norsk postnr bruker 4 felter.
// Internasjonalt postnr bruker 7 felter
$forbrukerPostnr = $leiebase->fastStrenglengde(
	$forbrukerPostnr,
	7, " ", STR_PAD_RIGHT
);

$forbrukerPoststed = $leiebase->fastStrenglengde(
	$forbrukerPoststed,
	25, " ", STR_PAD_RIGHT
);

$forbrukerLandskode = $leiebase->fastStrenglengde(
	$forbrukerLandskode,
	3, " ", STR_PAD_RIGHT
);

$betaler = $leiebase->fastStrenglengde(
	$betaler,
	30, " ", STR_PAD_RIGHT
);

$betaleradresse1 = $leiebase->fastStrenglengde(
	$betaleradresse1,
	30, " ", STR_PAD_RIGHT
);

$betaleradresse2 = $leiebase->fastStrenglengde(
	$betaleradresse2,
	30, " ", STR_PAD_RIGHT
);

// Norsk postnr bruker 4 felter.
// Internasjonalt postnr bruker 7 felter
$betalerPostnr = $leiebase->fastStrenglengde(
	$betalerPostnr,
	7, " ", STR_PAD_RIGHT
);

$betalerPoststed = $leiebase->fastStrenglengde(
	$betalerPoststed,
	25, " ", STR_PAD_RIGHT
);

$betalerLandskode = $leiebase->fastStrenglengde(
	$betalerLandskode,
	3, " ", STR_PAD_RIGHT
);

$fakturatype = $leiebase->fastStrenglengde(
	$fakturatype,
	35, " ", STR_PAD_RIGHT
);

$efakturareferanse = $leiebase->fastStrenglengde(
	$efakturareferanse,
	31, " ", STR_PAD_RIGHT
);

// Vanlig faktura = 0, AvtaleGiro = 1
$summaryType = $leiebase->fastStrenglengde(
	$summaryType,
	1, "0", STR_PAD_LEFT
);

// efaktura-mal 1 eller 2
$mal = $leiebase->fastStrenglengde(
	$mal,
	2, "0", STR_PAD_LEFT
);

// Reklame eller ikke
$reklame = $leiebase->fastStrenglengde(
	$reklame,
	1, "0", STR_PAD_LEFT
);

$fakturanummer = $leiebase->fastStrenglengde(
	$fakturanummer,
	25, " ", STR_PAD_RIGHT
);

$avsenderBankkonto = $leiebase->fastStrenglengde(
	$avsenderBankkonto,
	11, "0", STR_PAD_LEFT
);

$overskriftFakturakunde = $leiebase->fastStrenglengde(
	$overskriftFakturakunde,
	40, " ", STR_PAD_RIGHT
);

$overskriftFakturabetaler = $leiebase->fastStrenglengde(
	$overskriftFakturabetaler,
	40, " ", STR_PAD_RIGHT
);

$detaljoverskrift = (object)array(
	'a'	=> $leiebase->fastStrenglengde( mb_substr ($detaljoverskrift, 0, 40, 'UTF-8'), 40),
	'b'	=> $leiebase->fastStrenglengde( mb_substr ($detaljoverskrift, 40, 40, 'UTF-8'), 40),
	'c'	=> $leiebase->fastStrenglengde( mb_substr ($detaljoverskrift, 80, 16, 'UTF-8'), 16)
);




$fasteTekster = array(
	'forfallBeløp'	=> $leiebase->fastStrenglengde( "Å betale", 20, " ", STR_PAD_RIGHT ),
	'fakturadato'	=> $leiebase->fastStrenglengde( "Dato", 20, " ", STR_PAD_RIGHT ),
	'fakturanr'	=> $leiebase->fastStrenglengde( "Regningsnr", 20, " ", STR_PAD_RIGHT ),
	'forfall'	=> $leiebase->fastStrenglengde( "Forfallsdato", 20, " ", STR_PAD_RIGHT )
);

$tekstfelter = array_slice($tekstfelter, 0, 5);



/********************************************/


/********************************************/
/*	Beløpspost 1 (rec.type 30)				*/
/*	Denne record må̊ fylles ut for at transaksjonen
skal være gyldig.							*/
echo  "NY" /* (formatkode) */
	. "42" /* (tjenestekode		= 42 eFaktura) */
	. "03" /* (transaksjonstype	= 03 eFaktura) */
	. "30"  /* (recordtype) */
	. $transaksjonsnummer
	. $forfallsdato->format('dmy')
	. str_repeat(" ", 11)  /* (filler) */
	. $leiebase->fastStrenglengde(
		intval( $beløp * 100 ),
		17, "0", STR_PAD_LEFT
	)
	. $kid
	. str_repeat("0", 6)  /* (filler) */
	. "\n";


/********************************************/
/*	Beløpspost 2 (rec.type 31)				*/
/*	Denne record er tatt med for å vise mulig fremtidig bruk.
Legger man til denne recorden, vil nets kunne trekke et komplett avtalegiro-
oppdrag ut fra efakturaen, slik at man slipper å sende inn avtalegiro-oppdraget
som en egen forsendelse.					*/
echo  "NY" /* (formatkode) */
	. "42" /* (tjenestekode		= 42 eFaktura) */
	. "03" /* (transaksjonstype	= 03 eFaktura) */
	. "31"  /* (recordtype) */
	. $transaksjonsnummer
	. $forkortetNavn
	. str_repeat(" ", 25)  /* (filler) */
	. $fremmedreferanse
	. str_repeat("0", 5)  /* (filler) */
	. "\n";


/********************************************/
/*	Adressepost 1 Fakturautsteder (navn/postnr/sted) (rec.type 40, melding = 1)	*/
/*	Adressepost 1 og Adressepost 2 må̊ fylles ut.	*/
echo  "NY" /* (formatkode) */
	. "42" /* (tjenestekode		= 42 eFaktura) */
	. "03" /* (transaksjonstype	= 03 eFaktura) */
	. "40"  /* (recordtype) */
	. $transaksjonsnummer
	. "1"  /* (Melding: 1 = Fakturautsteder, 2 = Forbruker, 3 = Annen betaler) */
	. $avsender
	. $avsenderPostnr
	. $avsenderPoststed
	. str_repeat("0", 2)  /* (filler) */
	. "\n";


/********************************************/
/*	Adressepost 2 Fakturautsteder (postboks/gate/vei) (rec.type 41, melding = 1)	*/
/*	Adressepost 1 og Adressepost 2 må̊ fylles ut.	*/
echo  "NY" /* (formatkode) */
	. "42" /* (tjenestekode		= 42 eFaktura) */
	. "03" /* (transaksjonstype	= 03 eFaktura) */
	. "41"  /* (recordtype) */
	. $transaksjonsnummer
	. "1"  /* (Melding: 1 = Fakturautsteder, 2 = Forbruker, 3 = Annen betaler) */
	. $avsenderadresse1
	. $avsenderadresse2
	. $avsenderLandskode
	. str_repeat("0", 1)  /* (filler) */
	. "\n";


/********************************************/
/*	Adressepost 3 Fakturautsteder (telefon/telefaks) (rec.type 22)	*/
/*	Denne record er valgfri å̊ benytte.
Informasjonen kan sendes inn for hver faktura
eller ligge fast i Nets (GIF).				*/
echo  "NY" /* (formatkode) */
	. "42" /* (tjenestekode		= 42 eFaktura) */
	. "03" /* (transaksjonstype	= 03 eFaktura) */
	. "22"  /* (recordtype) */
	. $transaksjonsnummer
	. "1" /* (melding	= 1 fakturautsteder) */
	. $avsenderTelefon
	. $avsenderTelefaks
	. str_repeat("0", 24)  /* (filler) */
	. "\n";


/********************************************/
/*	Adressepost 4 Fakturautsteder (eMail) (rec.type 23)	*/
/*	Denne record er tatt med for å vise mulig fremtidig bruk.
Email-adressen skal sendes nets på̊ email.
Recorden er valgfri å̊ benytte.
Informasjonen kan sendes inn for hver faktura
eller ligge fast i Nets (GIF).				*/
echo  "NY" /* (formatkode) */
	. "42" /* (tjenestekode		= 42 eFaktura) */
	. "03" /* (transaksjonstype	= 03 eFaktura) */
	. "23"  /* (recordtype) */
	. $transaksjonsnummer
	. "1" /* (melding	= 1 fakturautsteder) */
	. $avsenderEpost
	. "\n";


/********************************************/
/*	Adressepost 1 Forbruker (navn/postnr/sted) (rec.type 40, melding = 2)	*/
/*	Adressepost 1 og Adressepost 2 må̊ fylles ut.	*/
echo  "NY" /* (formatkode) */
	. "42" /* (tjenestekode		= 42 eFaktura) */
	. "03" /* (transaksjonstype	= 03 eFaktura) */
	. "40"  /* (recordtype) */
	. $transaksjonsnummer
	. "2"  /* (Melding: 1 = Fakturautsteder, 2 = Forbruker, 3 = Annen betaler) */
	. $forbrukerNavn
	. $forbrukerPostnr
	. $forbrukerPoststed
	. str_repeat("0", 2)  /* (filler) */
	. "\n";


/********************************************/
/*	Adressepost 2 Forbruker (postboks/gate/vei) (rec.type 41, melding = 2)	*/
/*	Adressepost 1 og Adressepost 2 må̊ fylles ut.	*/
echo  "NY" /* (formatkode) */
	. "42" /* (tjenestekode		= 42 eFaktura) */
	. "03" /* (transaksjonstype	= 03 eFaktura) */
	. "41"  /* (recordtype) */
	. $transaksjonsnummer
	. "2"  /* (Melding: 1 = Fakturautsteder, 2 = Forbruker, 3 = Annen betaler) */
	. $forbrukeradresse1
	. $forbrukeradresse2
	. $forbrukerLandskode
	. str_repeat("0", 1)  /* (filler) */
	. "\n";


if(trim($betaler)) {
	/********************************************/
	/*	Adressepost 1 Annen betaler (navn/postnr/sted) (rec.type 40, melding = 3)	*/
	/*	Adressepost 1 og Adressepost 2 må̊ fylles ut.	*/
	echo  "NY" /* (formatkode) */
		. "42" /* (tjenestekode		= 42 eFaktura) */
		. "03" /* (transaksjonstype	= 03 eFaktura) */
		. "40"  /* (recordtype) */
		. $transaksjonsnummer
		. "3"  /* (Melding: 1 = Fakturautsteder, 2 = Forbruker, 3 = Annen betaler) */
		. $betaler
		. $betalerPostnr
		. $betalerPoststed
		. str_repeat("0", 2)  /* (filler) */
		. "\n";


	/********************************************/
	/*	Adressepost 2 Annen betaler (postboks/gate/vei) (rec.type 41, melding = 3)	*/
	/*	Adressepost 1 og Adressepost 2 må̊ fylles ut.	*/
	echo  "NY" /* (formatkode) */
		. "42" /* (tjenestekode		= 42 eFaktura) */
		. "03" /* (transaksjonstype	= 03 eFaktura) */
		. "41"  /* (recordtype) */
		. $transaksjonsnummer
		. "3"  /* (Melding: 1 = Fakturautsteder, 2 = Forbruker, 3 = Annen betaler) */
		. $betaleradresse1
		. $betaleradresse2
		. $betalerLandskode
		. str_repeat("0", 1)  /* (filler) */
		. "\n";


}


/********************************************/
/*	eFaktura-referanser 1 (rec.type 34)	*/
/*	Denne record må fylles ut for at
transaksjonen skal være gyldig.				*/
echo  "NY" /* (formatkode) */
	. "42" /* (tjenestekode		= 42 eFaktura) */
	. "03" /* (transaksjonstype	= 03 eFaktura) */
	. "34"  /* (recordtype) */
	. $transaksjonsnummer
	. $forfallsdato->format('d.m.Y')
	. $leiebase->fastStrenglengde(
		number_format( $beløp, 2, ",", "." ),
		20, " ", STR_PAD_RIGHT
	)
	. $fakturatype
	. "\n";

/********************************************/
/*	eFaktura-referanser 2 (rec.type 35)	*/
/*	Denne record må fylles ut for at
transaksjonen skal være gyldig.				*/
echo  "NY" /* (formatkode) */
	. "42" /* (tjenestekode		= 42 eFaktura) */
	. "03" /* (transaksjonstype	= 03 eFaktura) */
	. "35"  /* (recordtype) */
	. $transaksjonsnummer
	. $efakturareferanse
	. (int)$summaryType
	. $mal
	. (int)$reklame
	. $avsender
	. "\n";


/********************************************/
/*	eFaktura-referanser 3 (rec.type 36)	*/
/*	Denne record må fylles ut for at
transaksjonen skal være gyldig.				*/
echo  "NY" /* (formatkode) */
	. "42" /* (tjenestekode		= 42 eFaktura) */
	. "03" /* (transaksjonstype	= 03 eFaktura) */
	. "36"  /* (recordtype) */
	. $transaksjonsnummer
	. $fakturanummer
	. $fakturadato->format('d.m.Y')
	. $avsenderBankkonto
	. $avsenderOrgNr
	. str_repeat("0", 1)  /* (filler) */
	. "\n";


/********************************************/
/*	Spesifikasjonsrecord (rec.type 49)	*/
/*	Spesifisering av faste felter på faktura.
Dette er ledeteksten til de påkrevde feltene i fakturahodet og må således alltid spesifiseres.
Hvis ledeteksten ikke spesifiseres, vil tekstene vist i Fakturamal 1 komme som standard.
Kolonne 2 og 3 må være blanke.				*/
echo  "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "1"  /* (Spesifikasjon 1 = Spesifisering av faste felter) */
	. "001"  /* (Plassering / Linje) */
			//	001: ledetekst foran Beløp til forfall
	. "1"  /* (Plassering / Kolonne) */
			//	For faste felter brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. $fasteTekster["forfallBeløp"]
	. str_repeat(" ", 20)  /* (filler) */
	. str_repeat("0", 20)  /* (filler) */
	. "\n"
	. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "1"  /* (Spesifikasjon 1 = Spesifisering av faste felter) */
	. "001"  /* (Plassering / Linje) */
			//	001: ledetekst foran Beløp til forfall
	. "2"  /* (Plassering / Kolonne) */
			//	For faste felter brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. str_repeat(" ", 40)  /* (filler) */
	. str_repeat("0", 20)  /* (filler) */
	. "\n"
	. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "1"  /* (Spesifikasjon 1 = Spesifisering av faste felter) */
	. "001"  /* (Plassering / Linje) */
			//	001: ledetekst foran Beløp til forfall
	. "3"  /* (Plassering / Kolonne) */
			//	For faste felter brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. str_repeat(" ", 40)  /* (filler) */
	. str_repeat("0", 20)  /* (filler) */
	. "\n";

echo  "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "1"  /* (Spesifikasjon 1 = Spesifisering av faste felter) */
	. "002"  /* (Plassering / Linje) */
			//	002: ledetekst foran Betalingsfrist
	. "1"  /* (Plassering / Kolonne) */
			//	For faste felter brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. $fasteTekster["forfall"]
	. str_repeat(" ", 20)  /* (filler) */
	. str_repeat("0", 20)  /* (filler) */
	. "\n"
	. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "1"  /* (Spesifikasjon 1 = Spesifisering av faste felter) */
	. "002"  /* (Plassering / Linje) */
			//	002: ledetekst foran Betalingsfrist
	. "2"  /* (Plassering / Kolonne) */
			//	For faste felter brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. str_repeat(" ", 40)  /* (filler) */
	. str_repeat("0", 20)  /* (filler) */
	. "\n"
	. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "1"  /* (Spesifikasjon 1 = Spesifisering av faste felter) */
	. "002"  /* (Plassering / Linje) */
			//	002: ledetekst foran Betalingsfrist
	. "3"  /* (Plassering / Kolonne) */
			//	For faste felter brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. str_repeat(" ", 40)  /* (filler) */
	. str_repeat("0", 20)  /* (filler) */
	. "\n";

echo  "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "1"  /* (Spesifikasjon 1 = Spesifisering av faste felter) */
	. "003"  /* (Plassering / Linje) */
			//	003: ledetekst foran Fakturadato
	. "1"  /* (Plassering / Kolonne) */
			//	For faste felter brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. $fasteTekster["fakturadato"]
	. str_repeat(" ", 20)  /* (filler) */
	. str_repeat("0", 20)  /* (filler) */
	. "\n"
	. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "1"  /* (Spesifikasjon 1 = Spesifisering av faste felter) */
	. "003"  /* (Plassering / Linje) */
			//	003: ledetekst foran Fakturadato
	. "2"  /* (Plassering / Kolonne) */
			//	For faste felter brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. str_repeat(" ", 40)  /* (filler) */
	. str_repeat("0", 20)  /* (filler) */
	. "\n"
	. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "1"  /* (Spesifikasjon 1 = Spesifisering av faste felter) */
	. "003"  /* (Plassering / Linje) */
			//	003: ledetekst foran Fakturadato
	. "3"  /* (Plassering / Kolonne) */
			//	For faste felter brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. str_repeat(" ", 40)  /* (filler) */
	. str_repeat("0", 20)  /* (filler) */
	. "\n";

echo  "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "1"  /* (Spesifikasjon 1 = Spesifisering av faste felter) */
	. "004"  /* (Plassering / Linje) */
			//	004: ledetekst foran Fakturanummer
	. "1"  /* (Plassering / Kolonne) */
			//	For faste felter brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. $fasteTekster["fakturanr"]
	. str_repeat(" ", 20)  /* (filler) */
	. str_repeat("0", 20)  /* (filler) */
	. "\n"
	. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "1"  /* (Spesifikasjon 1 = Spesifisering av faste felter) */
	. "004"  /* (Plassering / Linje) */
			//	004: ledetekst foran Fakturanummer
	. "2"  /* (Plassering / Kolonne) */
			//	For faste felter brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. str_repeat(" ", 40)  /* (filler) */
	. str_repeat("0", 20)  /* (filler) */
	. "\n"
	. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "1"  /* (Spesifikasjon 1 = Spesifisering av faste felter) */
	. "004"  /* (Plassering / Linje) */
			//	004: ledetekst foran Fakturanummer
	. "3"  /* (Plassering / Kolonne) */
			//	For faste felter brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. str_repeat(" ", 40)  /* (filler) */
	. str_repeat("0", 20)  /* (filler) */
	. "\n";

	
/********************************************/
/*	Spesifikasjonsrecord (rec.type 49)	*/
/*	Spesifisering av variable felter på faktura.
Denne recorden er valgfri å benytte. Fakturautsteder kan fylle ut inntil 5 felter med innledende tekst og innhold på høyre side av fakturaen. Dette kan for eksempel være fakturanummer, faktura utstedt, betalingsdato, kundenummer etc. Se Fakturamal 1 hvor feltene kommer på fakturaen og eksempel på innhold som feltene kan ha. Dersom ingen felter fylles ut, kommer ingen tekst på fakturaen.
Kolonne 3 må være blank.				*/
$linje = 0;
foreach( $tekstfelter as $tekstfelt ) {
	$linje ++;
	echo  "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
		. $transaksjonsnummer
		. "2"  /* (Spesifikasjon 2 = Spesifisering av variable felter) */
		. "00" . $linje  /* (Linjenr) */
		. "1"  /* (Plassering / Kolonne) */
				//	For variable felter vil kolonne 1 inneholde ledetekst
		. $leiebase->fastStrenglengde( $tekstfelt->ledetekst, 20, " ", STR_PAD_RIGHT )
		. str_repeat(" ", 20)  /* (filler) */
		. str_repeat("0", 20)  /* (filler) */
		. "\n"
		. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
		. $transaksjonsnummer
		. "2"  /* (Spesifikasjon 2 = Spesifisering av variable felter) */
		. "00" . $linje  /* (Linjenr) */
		. "2"  /* (Plassering / Kolonne) */
				//	For variable felter vil kolonne 2 inneholde feltverdien
		. $leiebase->fastStrenglengde( $tekstfelt->verdi, 40, " ", STR_PAD_RIGHT )
		. str_repeat("0", 20)  /* (filler) */
		. "\n"
		. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
		. $transaksjonsnummer
		. "2"  /* (Spesifikasjon 2 = Spesifisering av variable felter) */
		. "00" . $linje  /* (Linjenr) */
		. "3"  /* (Plassering / Kolonne) */
				//	For variable felter brukes kolonne 1 og 2, mens kolonne 3 må sendes blank
		. str_repeat(" ", 40)  /* (filler) */
		. str_repeat("0", 20)  /* (filler) */
		. "\n";
	
}
unset( $linje );


/********************************************/
/*	Spesifikasjonsrecord (rec.type 49)	*/
/*	Spesifisering av fritekst før fakturadetaljer.
All tekst formateres på forhånd, og fylles ut i løpende linjenr, 3 kolonner pr. linje. NB! For hver record med recordtype 49, må det alltid sendes 3 kolonner pr linje, selv om de er blanke (spaces). Alle blanke linjer blir tatt bort på HTML-siden.				*/
foreach( $fritekst1 as $linje => $tekst ) {
	echo  "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
		. $transaksjonsnummer
		. "3"  /* (Spesifikasjon 3 = Spesifisering av fritekst før fakturadetaljer) */
		. "00" . ( $linje + 1 )  /* (Linjenr) */
		. "1"  /* (Kolonne) */
		. $leiebase->fastStrenglengde( $tekst->a, 40, " ", STR_PAD_RIGHT )
		. str_repeat("0", 20)  /* (filler) */
		. "\n"
		. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
		. $transaksjonsnummer
		. "3"  /* (Spesifikasjon 3 = Spesifisering av fritekst før fakturadetaljer) */
		. "00" . ( $linje + 1 )  /* (Linjenr) */
		. "2"  /* (Plassering / Kolonne) */
				//	For variable felter vil kolonne 2 inneholde feltverdien
		. $leiebase->fastStrenglengde( $tekst->b, 40, " ", STR_PAD_RIGHT )
		. str_repeat("0", 20)  /* (filler) */
		. "\n"
		. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
		. $transaksjonsnummer
		. "3"  /* (Spesifikasjon 3 = Spesifisering av fritekst før fakturadetaljer) */
		. "00" . ( $linje + 1 )  /* (Linjenr) */
		. "3"  /* (Plassering / Kolonne) */
				//	For variable felter vil kolonne 2 inneholde feltverdien
		. $leiebase->fastStrenglengde( $tekst->c, 16, " ", STR_PAD_RIGHT )
		. str_repeat(" ", 24)  /* (filler) */
		. str_repeat("0", 20)  /* (filler) */
		. "\n";
}


/********************************************/
/*	Spesifikasjonsrecord (rec.type 49)	*/
/*	Spesifisering av overskrift for fakturakunde (40 tegn).				*/
echo  "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "4"  /* (Spesifikasjon 4 = Spesifisering av overskrifter) */
	. "001"  /* (Plassering) */
			//	001: overskrift over Fakturakunde
	. "1"  /* (Plassering / Kolonne) */
			//	For overskrift over fakturakunde brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. $overskriftFakturakunde
	. str_repeat("0", 20)  /* (filler) */
	. "\n"
	. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "4"  /* (Spesifikasjon 4 = Spesifisering av overskrifter) */
	. "001"  /* (Plassering) */
			//	001: overskrift over Fakturakunde
	. "2"  /* (Plassering / Kolonne) */
			//	For overskrift over fakturakunde brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. str_repeat(" ", 40)  /* (filler) */
	. str_repeat("0", 20)  /* (filler) */
	. "\n"
	. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "4"  /* (Spesifikasjon 4 = Spesifisering av overskrifter) */
	. "001"  /* (Plassering) */
			//	001: overskrift over Fakturakunde
	. "3"  /* (Plassering / Kolonne) */
			//	For overskrift over fakturakunde brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. str_repeat(" ", 40)  /* (filler) */
	. str_repeat("0", 20)  /* (filler) */
	. "\n";


/********************************************/
/*	Spesifikasjonsrecord (rec.type 49)	*/
/*	Spesifisering av overskrift for fakturabetaler (40 tegn).				*/
echo  "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "4"  /* (Spesifikasjon 4 = Spesifisering av overskrifter) */
	. "002"  /* (Plassering) */
			//	002: overskrift over Fakturabetaler
	. "1"  /* (Plassering / Kolonne) */
			//	For overskrift over fakturabetaler brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. $overskriftFakturabetaler
	. str_repeat("0", 20)  /* (filler) */
	. "\n"
	. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "4"  /* (Spesifikasjon 4 = Spesifisering av overskrifter) */
	. "002"  /* (Plassering) */
			//	002: overskrift over Fakturabetaler
	. "2"  /* (Plassering / Kolonne) */
			//	For overskrift over fakturabetaler brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. str_repeat(" ", 40)  /* (filler) */
	. str_repeat("0", 20)  /* (filler) */
	. "\n"
	. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "4"  /* (Spesifikasjon 4 = Spesifisering av overskrifter) */
	. "002"  /* (Plassering) */
			//	002: overskrift over Fakturabetaler
	. "3"  /* (Plassering / Kolonne) */
			//	For overskrift over fakturabetaler brukes kolonne 1, mens kolonne 2 og 3 må sendes blanke
	. str_repeat(" ", 40)  /* (filler) */
	. str_repeat("0", 20)  /* (filler) */
	. "\n";


/********************************************/
/*	Spesifikasjonsrecord (rec.type 49)	*/
/*	Spesifisering av overskrift for fakturaspesifikasjon (80 eller 96 tegn).				*/
echo  "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "4"  /* (Spesifikasjon 4 = Spesifisering av overskrifter) */
	. "003"  /* (Plassering) */
			//	003: overskrift over Fakturaspesifikasjon
	. "1"  /* (Plassering / Kolonne) */
	. $detaljoverskrift->a
	. str_repeat("0", 20)  /* (filler) */
	. "\n"
	. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "4"  /* (Spesifikasjon 4 = Spesifisering av overskrifter) */
	. "003"  /* (Plassering) */
			//	003: overskrift over Fakturaspesifikasjon
	. "2"  /* (Plassering / Kolonne) */
	. $detaljoverskrift->b
	. str_repeat("0", 20)  /* (filler) */
	. "\n"
	. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
	. $transaksjonsnummer
	. "4"  /* (Spesifikasjon 4 = Spesifisering av overskrifter) */
	. "003"  /* (Plassering) */
			//	003: overskrift over Fakturaspesifikasjon
	. "3"  /* (Plassering / Kolonne) */
	. $detaljoverskrift->c
	. str_repeat(" ", 24)  /* (filler) */
	. str_repeat("0", 20)  /* (filler) */
	. "\n";


/********************************************/
/*	Spesifikasjonsrecord (rec.type 49)	*/
/*	Spesifisering av fakturalinjer.
Fakturautsteder kan spesifisere innholdet i fakturaen i spesifikasjonsrecorden. Dersom blank linje ønskes, må det sendes linjer med spaces i hver kolonne. Alle tre kolonner må fylles ut hver gang.
Dersom kolonne 3 ikke benyttes, fordi fakturautsteder har valgt å bruke Mal 1, skal kolonne 3 fylles ut med blanke (spaces).				*/
foreach( $fakturadetaljer as $linje => $tekst ) {
	echo  "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
		. $transaksjonsnummer
		. "5"  /* (Spesifikasjon 5 = fakturadetaljer) */
		. $leiebase->fastStrenglengde( ( $linje + 1 ), 3, "0", STR_PAD_LEFT )  /* (Linjenr) */
		. "1"  /* (Kolonne) */
		. $leiebase->fastStrenglengde( $tekst->a, 40, " ", STR_PAD_RIGHT )
		. str_repeat("0", 20)  /* (filler) */
		. "\n";
	echo "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
		. $transaksjonsnummer
		. "5"  /* (Spesifikasjon 5 = fakturadetaljer) */
		. $leiebase->fastStrenglengde( ( $linje + 1 ), 3, "0", STR_PAD_LEFT )  /* (Linjenr) */
		. "2"  /* (Kolonne) */
				//	For variable felter vil kolonne 2 inneholde feltverdien
		. $leiebase->fastStrenglengde( $tekst->b, 40, " ", STR_PAD_RIGHT )
		. str_repeat("0", 20)  /* (filler) */
		. "\n";
	echo "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
		. $transaksjonsnummer
		. "5"  /* (Spesifikasjon 5 = fakturadetaljer) */
		. $leiebase->fastStrenglengde( ( $linje + 1 ), 3, "0", STR_PAD_LEFT )  /* (Linjenr) */
		. "3"  /* (Kolonne) */
				//	For variable felter vil kolonne 2 inneholde feltverdien
		. $leiebase->fastStrenglengde( $tekst->c, 16, " ", STR_PAD_RIGHT )
		. str_repeat(" ", 24)  /* (filler) */
		. str_repeat("0", 20)  /* (filler) */
		. "\n";
}


/********************************************/
/*	Spesifikasjonsrecord (rec.type 49)	*/
/*	Spesifisering av fritekst etter fakturadetaljer.
All tekst formateres på forhånd, og fylles ut i løpende linjenr, 3 kolonner pr. linje. NB! For hver record med recordtype 49, må det alltid sendes 3 kolonner pr linje, selv om de er blanke (spaces). Alle blanke linjer blir tatt bort på HTML-siden.				*/
foreach( $fritekst2 as $linje => $tekst ) {
	echo  "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
		. $transaksjonsnummer
		. "6"  /* (Spesifikasjon 6 = Spesifisering av fritekst etter fakturadetaljer) */
		. "00" . ( $linje + 1 )  /* (Linjenr) */
		. "1"  /* (Kolonne) */
		. $leiebase->fastStrenglengde( $tekst->a, 40, " ", STR_PAD_RIGHT )
		. str_repeat("0", 20)  /* (filler) */
		. "\n"
		. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
		. $transaksjonsnummer
		. "6"  /* (Spesifikasjon 6 = Spesifisering av fritekst etter fakturadetaljer) */
		. "00" . ( $linje + 1 )  /* (Linjenr) */
		. "2"  /* (Plassering / Kolonne) */
				//	For variable felter vil kolonne 2 inneholde feltverdien
		. $leiebase->fastStrenglengde( $tekst->b, 40, " ", STR_PAD_RIGHT )
		. str_repeat("0", 20)  /* (filler) */
		. "\n"
		. "NY420349" /* (formatkode NY, tjenestekode 42, transaksjonstype 03, recordtype49) */
		. $transaksjonsnummer
		. "6"  /* (Spesifikasjon 6 = Spesifisering av fritekst etter fakturadetaljer) */
		. "00" . ( $linje + 1 )  /* (Linjenr) */
		. "3"  /* (Plassering / Kolonne) */
				//	For variable felter vil kolonne 2 inneholde feltverdien
		. $leiebase->fastStrenglengde( $tekst->c, 16, " ", STR_PAD_RIGHT )
		. str_repeat(" ", 24)  /* (filler) */
		. str_repeat("0", 20)  /* (filler) */
		. "\n";
}

?>
