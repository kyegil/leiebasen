<?php
/*********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
*********************************************/

/********************************************/
//	$beløp	nummer
//	$forfallsdato DateTime-objekt
//	$forkortetNavn
//	$fremmedreferanse
//	$kid
//	$smsNummer
//	$spesifikasjon
//	$transaksjonsnummer
//	$transaksjonstype	
//						2 = Transaksjon uten varsling fra bank
//						21 = Transaksjon med varsling fra bank


/********************************************/

$forkortetNavn = $leiebase->fastStrenglengde(
	$forkortetNavn,
	10, " ", STR_PAD_RIGHT
);

$fremmedreferanse = $leiebase->fastStrenglengde(
	$fremmedreferanse,
	25, " ", STR_PAD_RIGHT
);

$kid	= $leiebase->fastStrenglengde(
	$kid,
	25, " ", STR_PAD_LEFT
);

$smsNummer = $leiebase->fastStrenglengde(
	$smsNummer,
	8, " ", STR_PAD_LEFT
);

$spesifikasjon = array_slice(
	explode(
		"\n",
		wordwrap( $spesifikasjon, 80, "\n", true )
	),
	0, 42
);

$transaksjonsnummer = $leiebase->fastStrenglengde(
	$transaksjonsnummer,
	7, "0", STR_PAD_LEFT
);

$transaksjonstype = $leiebase->fastStrenglengde(
	$transaksjonstype,
	2, "0", STR_PAD_LEFT
);

/********************************************/


/********************************************/
/*	Beløpspost 1 (rec.type 30)				*/
/*	Beløpspost 1 og 2 må̊ fylles ut 
for at transaksjonen skal være gyldig.		*/
echo  "NY" /* (formatkode) */
	. "21" /* (tjenestekode		= 21 avtalegiro) */
	. $transaksjonstype
	. "30"  /* (recordtype) */
	. $transaksjonsnummer
	. $forfallsdato->format('dmy')
	. str_repeat(" ", 3)  /* (filler) */
	. $smsNummer
	. $leiebase->fastStrenglengde(
		intval( $beløp * 100 ),
		17, "0", STR_PAD_LEFT
	)
	. $kid
	. str_repeat("0", 6)  /* (filler) */
	. "\n";


/********************************************/
/*	Beløpspost 2 (rec.type 31)				*/
/*	Beløpspost 1 og 2 må̊ fylles ut 
for at transaksjonen skal være gyldig.		*/
echo  "NY" /* (formatkode) */
	. "21" /* (tjenestekode		= 21 avtalegiro) */
	. $transaksjonstype
	. "31"  /* (recordtype) */
	. $transaksjonsnummer
	. $forkortetNavn
	. str_repeat(" ", 25)  /* (filler) */
	. $fremmedreferanse
	. str_repeat("0", 5)  /* (filler) */
	. "\n";


foreach( $spesifikasjon as $linjenr => $tekstlinje) {
	
	if( trim( $tekstlinje ) ) {

		echo  "NY" /* (formatkode) */
			. "21" /* (tjenestekode) */
			. $transaksjonstype
			. "49"  /* (Spesifikasjonsrecord) */
			. $transaksjonsnummer
			. "4" /* (betalingsvarsel) */
			. $leiebase->fastStrenglengde(
				$linjenr + 1,
				3, "0", STR_PAD_LEFT
			)
			. "1" /* (kolonne) */
			. $leiebase->fastStrenglengde(
				substr( $tekstlinje, 0, 40 ),
				40, " ", STR_PAD_RIGHT
			)
			. str_repeat("0", 20)  /* (filler) */
			. "\n";

	}
	
	if( strlen( trim( $tekstlinje ) ) > 40 ) {

		echo  "NY" /* (formatkode) */
			. "21" /* (tjenestekode) */
			. $transaksjonstype
			. "49"  /* (Spesifikasjonsrecord) */
			. $transaksjonsnummer
			. "4" /* (betalingsvarsel) */
			. $leiebase->fastStrenglengde(
				$linjenr + 1,
				3, "0", STR_PAD_LEFT
			)
			. "2" /* (kolonne) */
			. $leiebase->fastStrenglengde(
				substr( $tekstlinje, 40, 40 ),
				40, " ", STR_PAD_RIGHT
			)
			. str_repeat("0", 20)  /* (filler) */
			. "\n";

	}
}


?>