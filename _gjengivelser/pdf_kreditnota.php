<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

/*********************************************/
//	$fpdf (FPDF-objekt)
//	$avsender
//	$avsenderAdresse
//	$avsenderGateadresse
//	$avsenderPostnr
//	$avsenderPoststed
//	$avsenderOrgNr
//	$avsenderTelefon
//	$avsenderTelefax
//	$avsenderMobil
//	$avsenderEpost
//	$avsenderHjemmeside
//	$avtalegiro
//	$efaktura
//	$mottakerAdresse
//	$leieforhold
//	$leieforholdBeskrivelse
//	$fastKid
//	$kid
//	$efakturareferanse
//	$gironr
//	$utskriftsdato
//	$forfall
//	$kravsett (array av Krav-objekter):
//		->id
//		->tekst
//		->beløp
//		->utestående
//	$girobeløp
//	$utestående
//	$tidligereUbetalt (objekt):
//		->kravsett (array av Krav-objekter):
//			->id
//			->tekst
//			->beløp
//			->utestående
//		->utestående
//	$sisteInnbetalinger (array av objekter):
//		->dato
//		->betaler
//		->beløp
//		->ref
//	$girotekst
//	$bankkonto
//	$blankettBetalingsinfo
//	$blankettbeløp
//	$blankettbeløpKroner
//	$blankettbeløpØre
//	$ocrKid
//	$ocrKontonummer
//	$kontrollsiffer


/*********************************************/
/*********************************************/

$pdf->AddPage();

$rammer = false;	// Kan brukes for layoututvikling


$pdf->AddFont('DIN Black','','DINBla.php');
$pdf->AddFont('DIN Light','','DINLig.php');
$pdf->AddFont('OCR-B-10','','ocrb10.php');

/*
Lag ei rute som viser hvor det gule feltet på kvitteringsslippen begynner
*/
if($rammer) {
	$pdf->setXY(0, 174);
	$pdf->Cell(
		0,			// bredde i mm (normalt 0)
		21,			// høyde i mm (normalt 0)
		'',
		$rammer,	// innramming? Boolsk eller 'LRTB' (normalt false)
		0,			// Neste markørposisjon: 0 = til høyre, 1 = til begynnelsen av neste linje, 2 = ned (normalt 0)
		'L',		// Justering: 'L', 'C' eller 'R' (normalt 'L')
		false		// bakgrunnsfarge eller ikke (normalt false)
	);
}

//		Logo
// $pdf->Image(
// 	"../bilder/Fakturalogo_print_4000_707.png",
// 	120,	// x-posisjon i mm for øverste venstre hjørne
// 	15,		// y-posisjon i mm for øverste venstre hjørne
// 	80,		// bredde i mm eller 0 for automatisk
// 	0,		// høyde i mm eller 0 for automatisk
// 	'png'	//
// );
//
//		Posisjon 92mm fra venstre 15mm ned
$pdf->setXY(92, 15);
$pdf->SetFont('DIN Black','',13);
$pdf->Write(5, 'SVARTLAMOEN ');
$pdf->SetFont('DIN Light','',13);
$pdf->Write(5, 'BOLIGSTIFTELSE');
$pdf->SetFont('DIN Light','',8);

	
//		Brevhode adressefelt
$pdf->setXY(126, 20);
$pdf->MultiCell(
	40,			// bredde i mm (normalt 0)
	4,			// høyde i mm (normalt 0)
	utf8_decode(mb_strtoupper($avsenderGateadresse, 'UTF-8')) . "\n" . utf8_decode(mb_strtoupper($avsenderPostnr, 'UTF-8')) . " " . utf8_decode(mb_strtoupper($avsenderPoststed, 'UTF-8')),
	$rammer,	// innramming? Boolsk eller 'LRTB' (normalt false)
	'L',		// Justering: 'L', 'C' eller 'R' (normalt 'L')
	false		// bakgrunnsfarge eller ikke (normalt false)
);

		
//		Brevhode kontaktinfo
$pdf->setXY(105, 30);
$pdf->SetFont('DIN Light','',7);
$pdf->MultiCell(
	20,			// bredde i mm (normalt 0)
	4,			// høyde i mm (normalt 0)
	utf8_decode(mb_strtoupper("org.nr.\ntelefon\nmobil\ntelefax\nbankkonto\ne-post\nhjemmeside", 'UTF-8')),
	$rammer,	// innramming? Boolsk eller 'LRTB' (normalt false)
	'R',		// Justering: 'L', 'C' eller 'R' (normalt 'L')
	false		// bakgrunnsfarge eller ikke (normalt false)
);

$pdf->setXY(126, 30);
$pdf->SetFont('DIN Black','',7);
$pdf->MultiCell(
	0,			// bredde i mm (normalt 0)
	4,			// høyde i mm (normalt 0)
	utf8_decode(mb_strtoupper($avsenderOrgNr, 'UTF-8'))
	. "\n" . utf8_decode(mb_strtoupper($avsenderTelefon, 'UTF-8')) . "\n"
	. utf8_decode(mb_strtoupper($avsenderMobil, 'UTF-8')) . "\n"
	. utf8_decode(mb_strtoupper($avsenderTelefax, 'UTF-8')) . "\n"
	. utf8_decode(mb_strtoupper($bankkonto, 'UTF-8')) . "\n"
	. utf8_decode(mb_strtoupper($avsenderEpost, 'UTF-8')) . "\n"
	. utf8_decode(mb_strtoupper($avsenderHjemmeside, 'UTF-8')),
	$rammer,	// innramming? Boolsk eller 'LRTB' (normalt false)
	'L',		// Justering: 'L', 'C' eller 'R' (normalt 'L')
	false		// bakgrunnsfarge eller ikke (normalt false)
);


//		Adressefelt mottaker for konvoluttvindu
//		Posisjon 15mm fra venstre 30mm ned
$pdf->SetFont('Arial','',9);
$pdf->setY(30);
$pdf->setX(15);
$pdf->MultiCell(
	75,
	3.5,
	utf8_decode(mb_strtoupper($mottakerAdresse, 'UTF-8')),
	$rammer,
	'L'
);

$X = 10;
$Y = 65;
		
//		Leieforhold
$pdf->setX($X);
$pdf->setY($Y);
$pdf->Cell(
	35,			// bredde i mm (normalt 0)
	4,			// høyde i mm (normalt 0)
	utf8_decode("Leieforhold nr. {$leieforhold}"),
	$rammer,	// innramming? Boolsk eller 'LRTB' (normalt false)
	0,			// Neste markørposisjon: 0 = til høyre, 1 = til begynnelsen av neste linje, 2 = ned (normalt 0)
	'L',		// Justering: 'L', 'C' eller 'R' (normalt 'L')
	false		// bakgrunnsfarge eller ikke (normalt false)
);


//		KID-nummer
if($fastKid) {
	$pdf->setY($Y);
	$pdf->setX($X+35);
	$pdf->Cell(
		65,			// bredde i mm (normalt 0)
		4,			// høyde i mm (normalt 0)
		utf8_decode("Fast KID: {$fastKid}"),
		$rammer,	// innramming? Boolsk eller 'LRTB' (normalt false)
		0,			// Neste markørposisjon: 0 = til høyre, 1 = til begynnelsen av neste linje, 2 = ned (normalt 0)
		'L',		// Justering: 'L', 'C' eller 'R' (normalt 'L')
		false		// bakgrunnsfarge eller ikke (normalt false)
	);
}


//		Leieforholdbeskrivelse
$pdf->SetFont('Arial','',7.5);
$pdf->setX($X);
$pdf->setY($Y+4);
$pdf->Cell(
	100,		// bredde i mm (normalt 0)
	4,			// høyde i mm (normalt 0)
	utf8_decode($leieforholdBeskrivelse),
	$rammer,	// innramming? Boolsk eller 'LRTB' (normalt false)
	0,			// Neste markørposisjon: 0 = til høyre, 1 = til begynnelsen av neste linje, 2 = ned (normalt 0)
	'L',		// Justering: 'L', 'C' eller 'R' (normalt 'L')
	false		// bakgrunnsfarge eller ikke (normalt false)
);


//		Gironummer, kid-nummer, utskriftsdato og forfall
//		Posisjon 10mm fra venstre 59mm ned
$pdf->SetFont('Arial','',8);
$Y = 80;
$X = 10;

$pdf->setY($Y);
$pdf->setX($X);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(
	120,		// bredde i mm (normalt 0)
	4,			// høyde i mm (normalt 0)
	"KREDITNOTA",
	$rammer,	// innramming? Boolsk eller 'LRTB' (normalt false)
	0,			// Neste markørposisjon: 0 = til høyre, 1 = til begynnelsen av neste linje, 2 = ned (normalt 0)
	'L',		// Justering: 'L', 'C' eller 'R' (normalt 'L')
	false		// bakgrunnsfarge eller ikke (normalt false)
);


$pdf->setY($Y+5.5);
$pdf->setX($X);
$pdf->SetFont('Arial','B',9);
$pdf->Cell(
	120,		// bredde i mm (normalt 0)
	4,			// høyde i mm (normalt 0)
	"nr. {$gironr}       dato: {$utskriftsdato->format('d.m.Y')}",
	$rammer,	// innramming? Boolsk eller 'LRTB' (normalt false)
	0,			// Neste markørposisjon: 0 = til høyre, 1 = til begynnelsen av neste linje, 2 = ned (normalt 0)
	'L',		// Justering: 'L', 'C' eller 'R' (normalt 'L')
	false		// bakgrunnsfarge eller ikke (normalt false)
);


$pdf->setY($Y + 11);
$pdf->setX($X + 90);
$pdf->SetFont('Arial','B',8);
$pdf->setX($X + 105);
$pdf->Cell(
	15,			// bredde i mm (normalt 0)
	3.5,		// høyde i mm (normalt 0)
	utf8_decode("Beløp"),
	$rammer,	// innramming? Boolsk eller 'LRTB' (normalt false)
	0,			// Neste markørposisjon: 0 = til høyre, 1 = til begynnelsen av neste linje, 2 = ned (normalt 0)
	'R',		// Justering: 'L', 'C' eller 'R' (normalt 'L')
	false		// bakgrunnsfarge eller ikke (normalt false)
);
$Y += 15;

	
$pdf->SetFont('Arial','',8);
foreach($kravsett as $krav) {
	$pdf->setY($Y);
	$pdf->setX($X);
	$pdf->Cell(90, 3.5, utf8_decode($krav->hent('tekst')), $rammer, 0, 'L');
	$pdf->setX($X + 105);
	$pdf->Cell(15, 3.5, number_format($krav->hent('beløp'), 2, ",", " "), $rammer, 0, 'R');
	$Y += 3.5;
	$resultat->utskrifter[] = $krav->id;
}

$pdf->SetFont('Arial','B',8);
$pdf->setY($Y);
$pdf->setX($X);
$pdf->Cell(105, 3.5, utf8_decode("Sum kreditert beløp:"), 'T', 0, 'L');
$pdf->setX($X + 105);
$pdf->Cell(15, 3.5, number_format(($girobeløp *(-1)), 2, ",", " "), 'T', 0, 'R');

$Y += 9;


// Høyre kolonne
$X = 145;
$Y = 65;

//		Efaktura-logo
if ( $efaktura ) {

	//		Posisjon 145mm fra venstre 135mm ned
	//		Argumenter:
	//		string file [, float x [, float y [, float w [, float h [, string type [, mixed link]]]]]]
	$pdf->Image(
		"../bilder/eFaktura_print_4000_943.png",
		$X,		// x-posisjon i mm for øverste venstre hjørne
		$Y,		// y-posisjon i mm for øverste venstre hjørne
		23,		// bredde i mm eller 0 for automatisk
		0,		// høyde i mm eller 0 for automatisk
		'png'	//
	);
	
	//		Tekst om efaktura
	$pdf->setXY($X, $Y+5);
	$pdf->SetFont('Arial','',7);
	$pdf->multiCell(
		23,			// bredde i mm (normalt 0)
		4,			// høyde i mm (normalt 0)
		utf8_decode("Motta regningene rett i nettbanken."),
		$rammer,	// innramming? Boolsk eller 'LRTB' (normalt false)
		'C',		// Justering: 'L', 'C' eller 'R' (normalt 'L')
		false		// bakgrunnsfarge eller ikke (normalt false)
	);
	
	$pdf->setXY($X, $Y+14);
	$pdf->SetFont('Arial','',10);
	$pdf->Cell(
		55,			// bredde i mm (normalt 0)
		4,			// høyde i mm (normalt 0)
		"Efakturareferanse: {$efakturareferanse}",
		true,		// innramming? Boolsk eller 'LRTB' (normalt false)
		0,			// Neste markørposisjon: 0 = til høyre, 1 = til begynnelsen av neste linje, 2 = ned (normalt 0)
		'C',		// Justering: 'L', 'C' eller 'R' (normalt 'L')
		false		// bakgrunnsfarge eller ikke (normalt false)
	);
	$pdf->SetFont('Arial','',7);
}

//		AvtaleGiro-logo
if ( $avtalegiro ) {

	//		Argumenter:
	$pdf->Image(
		"../bilder/AvtaleGiro_print_4000_764.png",
		$X+55-28.4,// x-posisjon i mm for øverste venstre hjørne
		$Y,		// y-posisjon i mm for øverste venstre hjørne
		28.4,	// bredde i mm eller 0 for automatisk
		0,		// høyde i mm eller 0 for automatisk
		'png'	//
	);

	//		Tekst om avtaleGiro
	$pdf->setXY($X+55-28.4, $Y+5);
	$pdf->SetFont('Arial','',7);
	$pdf->multiCell(
		28.4,		// bredde i mm (normalt 0)
		4,			// høyde i mm (normalt 0)
		utf8_decode("Få regningene betalt automatisk på forfall."),
		$rammer,	// innramming? Boolsk eller 'LRTB' (normalt false)
		'C',		// Justering: 'L', 'C' eller 'R' (normalt 'L')
		false		// bakgrunnsfarge eller ikke (normalt false)
	);
}


if( $efaktura or $avtalegiro) {	
	$Y +=25;
}


//		Meldingstekst
//		Posisjon 145mm fra venstre 70mm ned
$pdf->SetFont('Arial','',7);
$pdf->setXY($X, $Y);
$pdf->MultiCell(
	55,			// bredde i mm (normalt 0)
	3.5,		// høyde i mm (normalt 0)
	utf8_decode($girotekst),
	$rammer,	// innramming? Boolsk eller 'LRTB' (normalt false)
	'L',		// Justering: 'L', 'C' eller 'R' (normalt 'L')
	false		// bakgrunnsfarge eller ikke (normalt false)
);
$Y += 40;



//		Kontonummer på kvitteringsslipp
//		Posisjon 15mm fra venstre 190mm ned
$pdf->SetFont('OCR-B-10','',10);
$pdf->setY(190);
$pdf->setX(15);
$pdf->Cell(35, 5, utf8_decode("Kreditnota"), $rammer, 0, 'L');


//		Maskinlesbart OCR-felt nederst på girodel
//		Posisjon 115mm fra venstre 235mm ned
$pdf->SetFont('OCR-B-10','',10);
$pdf->setY(274);
$pdf->setX(1);
if($rammer) {
	$pdf->Cell(8, 5, 'H', $rammer, 0, 'L');	// Bokstaven H for blankettjustering
}


//		KID-nr
$pdf->setX(10);
$pdf->Cell(65, 5, utf8_decode("Skal ikke betales"), $rammer, 0, 'R');


?>