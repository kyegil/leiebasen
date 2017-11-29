<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

class Innbetaling extends DatabaseObjekt {

protected	$tabell = "innbetalinger";	// Hvilken tabell i databasen som inneholder primærnøkkelen for dette objektet
protected	$idFelt = "innbetaling";	// Hvilket felt i tabellen som lagrer primærnøkkelen for dette objektet
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



/*	Fordel
Denne brukes av metoden fordel()
*****************************************
$del (objekt):		Delbeløpet som skal fordeles.
$motkravsett (array):		Liste over krav eller betalinger delbeløpet kan utliknes mot.
$maksbeløp (tall, normalt null):	Maksimalt beløp som skal brukes (ut ifra 0).
					Beløpet kommer i tillegg til evt eksisterende delbeløp
					Dersom null vil hele den ikke-konterte delen av betalinga føres på det angitte leieforholdet.
					Maksbeløp brukes kun dersom motkrav er oppgitt.
$angittLeieforhold (Leieforhold-objekt):	Dersom $motkrav er null eller et Innbetalingsobjekt,
					angis her hvilket leieforhold fordelingen skal foregå i.
					Dersom leieforhold er null vil alle eksisterende leieforhold i betalinga bli fordelt.
					Ved utlikning mot betalinger er denne påkrevd
--------------------------------------
retur (tall): det fordelte beløpet
*/
protected function _fordel( $del, $motkravsett, $maksbeløp ) {
	$tp = $this->mysqli->table_prefix;
	$resultat = 0;
	$gjenbruk = true;
	$restAvDelbeløp = $del->beløp;

	foreach( $motkravsett as $motkrav ) {
		if( $restAvDelbeløp != 0 ) {
		
			// Avgjør motkravets beløp
			if($motkrav instanceof Innbetaling) {
				$motkrav = "0";
				$motkravbeløp = $restAvDelbeløp;
			}
			else {
				$motkravbeløp = $motkrav->hent('utestående');
			}
			
			
			// Motkravet må ha riktig fortegn
			if($del->beløp * $motkravbeløp > 0) {

				// Utlikningsbeløpet må være innenfor kravbeløpet og delbeløpet
				$utlikningsbeløp	
					= abs($motkravbeløp) < abs($del->beløp)
					? $motkravbeløp
					: $del->beløp;
				
				// Dersom motkrav er angitt, og maksbeløp,
				// vil utlikningsbeløpet reduserers til maksbeløpet
				if($maksbeløp !== null) {
					$utlikningsbeløp
						= abs($utlikningsbeløp) < abs($maksbeløp)
						? $utlikningsbeløp
						: $maksbeløp;
				}
		
				//	enten
				// ->oppdater delbetalinga med $utlikningsbeløp
				if( $gjenbruk ) {
					$this->mysqli->saveToDb(array(
						'table'		=> "{$tp}{$this->tabell}",
						'update'	=> true,
						'where'		=> "{$this->tabell}.{$this->idFelt} = '{$this->id}' AND innbetalingsid = '{$del->id}'\n",
						'fields'	=> array(
							'beløp'			=> $utlikningsbeløp,
							'krav'			=> $motkrav
						)
					));
					$resultat += $utlikningsbeløp;
				}
		
				// eller
				// -> sett inn nye delbetalinger med $utlikningsbeløp
				else {
					$this->mysqli->saveToDb(array(
						'table'		=> "{$tp}{$this->tabell}",
						'insert'	=> true,
						'fields'	=> array(
							"innbetaling"		=> $this->hentId(),
							"konto"				=> $this->hent('konto'),
							"OCRtransaksjon"	=> $this->data->OCRtransaksjon, // Denne ble lastet ved forrige hent()
							"ref"				=> $this->hent('ref'),
							"dato"				=> $this->hent('dato')->format('Y-m-d'),
							"merknad"			=> $this->hent('merknad'),
							"betaler"			=> $this->hent('betaler'),
							"registrerer"		=> $this->hent('registrerer'),
							"registrert"		=> $this->hent('registrert')->format('Y-m-d H:i:s'),
							"leieforhold"		=> $del->leieforhold,
							"beløp"				=> $utlikningsbeløp,
							"krav"				=> $motkrav
						)
					));
					$resultat += $utlikningsbeløp;
				}

				$gjenbruk = false;
				$restAvDelbeløp -= $utlikningsbeløp;
				
				if( $motkrav == '0' ) {
					foreach( $this->leiebase->kontrollerBetalingsutlikninger() as $ubalanse ) {
						if( $ubalanse->sisteBetaling->hentId() == $this->hentId() ) {
							$this->_kopleMotbetalinger( $ubalanse->leieforhold, $ubalanse->balanse );
						}
					}
				}
				
			}
		}			
	}
	
	// -> Dersom det opprinnelige beløpet er endret
	//		settes inn ei ny delbetaling med evt restbeløp
	if( !$gjenbruk and $restAvDelbeløp) {
		$this->mysqli->saveToDb(array(
			'table'		=> "{$tp}{$this->tabell}",
			'insert'	=> true,
			'fields'	=> array(
				"innbetaling"		=> $this->hentId(),
				"konto"				=> $this->hent('konto'),
				"OCRtransaksjon"	=> $this->data->OCRtransaksjon, // Denne ble lastet ved forrige hent()
				"ref"				=> $this->hent('ref'),
				"dato"				=> $this->hent('dato')->format('Y-m-d'),
				"merknad"			=> $this->hent('merknad'),
				"betaler"			=> $this->hent('betaler'),
				"registrerer"		=> $this->hent('registrerer'),
				"registrert"		=> $this->hent('registrert')->format('Y-m-d H:i:s'),
				"leieforhold"		=> $del->leieforhold,
				"beløp"				=> $restAvDelbeløp,
				"krav"				=> null
			)
		));
	}
	
	$this->leiebase->oppdaterUbetalt();
	
	$restAvDelbeløp = 0;
	return $resultat;
}



/*	frakople motbetalinger
Denne brukes kun av metoden fordel()
Denne metoden påvirker ikke denne betalinga, kun andre betalinger med motsatt fortegn
*****************************************
$leieforhold (Leieforhold-objekt eller id)
$beløp (tall):		Beløpet som skal koples til denne betalinga.
--------------------------------------
retur (tall): det frakoplede beløpet
*/
protected function _frakopleMotbetalinger( $leieforhold, $beløp ) {
	$tp = $this->mysqli->table_prefix;
	$resultat = 0;
	$gjenbruk = true;

	if(!is_numeric($beløp)) {
		throw new Exception('$beløp må være numerisk');
	}
	$beløp = -$beløp;
	
	$motbetalinger = $this->mysqli->arrayData(array(
		'source'	=> "{$tp}innbetalinger as innbetalinger",
		'where'		=> "innbetalinger.leieforhold = {$leieforhold}\n"
					.	"AND innbetalinger.beløp " . ($beløp > 0 ? ">" : "<") . " 0\n"
					.	"AND krav ='0'"
	));

	$beløpsrest = $beløp;
	foreach( $motbetalinger->data as $motbetaling ) {
	
		if( abs($motbetaling->beløp) <= abs($beløpsrest) ) {
			// Delbeløpet kan frakoples i sin helhet

			$this->mysqli->saveToDb(array(
				'table'		=> "{$tp}{$this->tabell}",
				'update'	=> true,
				'where'		=> "innbetalingsid = '{$motbetaling->innbetalingsid}'\n",
				'fields'	=> array(
					'krav'			=> null
				)
			));
			$beløpsrest	-= $motbetaling->beløp;
			$resultat	+= $motbetaling->beløp;
		}
		
		else if( $beløpsrest ) {
			// Delbeløpet må splittes så en del av det kan koples fra
			$this->mysqli->saveToDb(array(
				'table'		=> "{$tp}{$this->tabell}",
				'update'	=> true,
				'where'		=> "innbetalingsid = '{$motbetaling->innbetalingsid}'\n",
				'fields'	=> array(
					'beløp'			=> $beløpsrest,
					'krav'			=> null
				)
			));
			$this->mysqli->saveToDb(array(
				'table'		=> "{$tp}{$this->tabell}",
				'insert'	=> true,
				'fields'	=> array(
					"innbetaling"		=> $motbetaling->innbetaling,
					"konto"				=> $motbetaling->konto,
					"OCRtransaksjon"	=> $motbetaling->OCRtransaksjon,
					"ref"				=> $motbetaling->ref,
					"dato"				=> $motbetaling->dato,
					"merknad"			=> $motbetaling->merknad,
					"betaler"			=> $motbetaling->betaler,
					"registrerer"		=> $motbetaling->registrerer,
					"registrert"		=> $motbetaling->registrert,
					"leieforhold"		=> $motbetaling->leieforhold,
					"beløp"				=> $motbetaling->beløp - $beløpsrest,
					"krav"				=> '0'
				)
			));
			$beløpsrest	= 0;
			$resultat	+= $beløpsrest;
		}
	}
	return $resultat;
}



/*	kople motbetalinger
Denne brukes kun av metoden fordel()
Denne metoden påvirker ikke denne betalinga, kun andre betalinger med motsatt fortegn
*****************************************
$leieforhold (Leieforhold-objekt eller id)
$beløp (tall):		Beløpet som skal koples til denne betalinga.
--------------------------------------
retur (tall): det fordelte beløpet
*/
protected function _kopleMotbetalinger( $leieforhold, $beløp ) {
	$tp = $this->mysqli->table_prefix;
	$resultat = 0;
	$gjenbruk = true;

	if(!is_numeric($beløp)) {
		throw new Exception('$beløp må være numerisk');
	}
	$beløp = -$beløp;
	
	$motbetalinger = $this->mysqli->arrayData(array(
		'source'	=> "{$tp}innbetalinger as innbetalinger",
		'where'		=> "innbetalinger.leieforhold = {$leieforhold}\n"
					.	"AND innbetalinger.beløp " . ($beløp > 0 ? ">" : "<") . " 0\n"
					.	"AND krav IS NULL"
	));

	$beløpsrest = $beløp;
	foreach( $motbetalinger->data as $motbetaling ) {
	
		if( abs($motbetaling->beløp) <= abs($beløpsrest) ) {
			// Delbeløpet kan koples i sin helhet

			$this->mysqli->saveToDb(array(
				'table'		=> "{$tp}{$this->tabell}",
				'update'	=> true,
				'where'		=> "innbetalingsid = '{$motbetaling->innbetalingsid}'\n",
				'fields'	=> array(
					'krav'			=> "0"
				)
			));
			$beløpsrest	-= $motbetaling->beløp;
			$resultat	+= $motbetaling->beløp;
		}
		
		else if( $beløpsrest ) {
			// Delbeløpet må splittes så en del av det kan koples
			$this->mysqli->saveToDb(array(
				'table'		=> "{$tp}{$this->tabell}",
				'update'	=> true,
				'where'		=> "innbetalingsid = '{$motbetaling->innbetalingsid}'\n",
				'fields'	=> array(
					'beløp'			=> $beløpsrest,
					'krav'			=> "0"
				)
			));
			$this->mysqli->saveToDb(array(
				'table'		=> "{$tp}{$this->tabell}",
				'insert'	=> true,
				'fields'	=> array(
					"innbetaling"		=> $motbetaling->innbetaling,
					"konto"				=> $motbetaling->konto,
					"OCRtransaksjon"	=> $motbetaling->OCRtransaksjon,
					"ref"				=> $motbetaling->ref,
					"dato"				=> $motbetaling->dato,
					"merknad"			=> $motbetaling->merknad,
					"betaler"			=> $motbetaling->betaler,
					"registrerer"		=> $motbetaling->registrerer,
					"registrert"		=> $motbetaling->registrert,
					"leieforhold"		=> $motbetaling->leieforhold,
					"beløp"				=> $motbetaling->beløp - $beløpsrest,
					"krav"				=> null
				)
			));
			$beløpsrest	= 0;
			$resultat	+= $beløpsrest;
		}
	}
	return $resultat;
}



// Last giroens kjernedata fra databasen
/****************************************/
//	$param
//		id	(heltall) betalingsid-en	
//	--------------------------------------
protected function last($id = null) {
	$tp = $this->mysqli->table_prefix;
	
	if( !$id ) {
		$id = $this->id;
	}
	
	$resultat = $this->mysqli->arrayData(array(
		'returnQuery'	=> true,
		
		'fields' =>			"{$this->tabell}.{$this->idFelt} AS id,
							{$this->tabell}.innbetalingsid,
							{$this->tabell}.innbetaling,
							{$this->tabell}.konto,
							IFNULL(kontoer.kode, {$this->tabell}.konto) AS kontokode,
							IFNULL(kontoer.navn, {$this->tabell}.konto) AS kontonavn,
							{$this->tabell}.OCRtransaksjon,
							{$this->tabell}.ref,
							{$this->tabell}.dato,
							{$this->tabell}.merknad,
							{$this->tabell}.betaler,
							{$this->tabell}.registrerer,
							{$this->tabell}.registrert,
							{$this->tabell}.beløp,
							{$this->tabell}.leieforhold,
							{$this->tabell}.krav\n",
						
		'source' => 		"{$tp}{$this->tabell} AS {$this->tabell}\n"
						.	"LEFT JOIN {$tp}kontoer AS kontoer ON {$this->tabell}.konto = CONCAT(kontoer.id)\n",

		'where'			=>	"{$tp}{$this->tabell}.{$this->idFelt} = '$id'",
		
		'orderfields'	=>	"IF(krav IS NULL, 1, 0), IF(krav = '0', 1, 0), krav, IF(leieforhold IS NULL, 1, 0), leieforhold"
		
	));
	if( isset( $resultat->data[0] ) ) {
		$this->data = (object)array(
			'id			'		=> $resultat->data[0]->id,
			'innbetaling'		=> $resultat->data[0]->id,
			'konto'				=> $resultat->data[0]->konto,
			'kontokode'			=> $resultat->data[0]->kontokode,
			'kontonavn'			=> $resultat->data[0]->kontonavn,
			'OCRtransaksjon'	=> $resultat->data[0]->OCRtransaksjon,
			'beløp'				=> 0,
			'betaler'			=> $resultat->data[0]->betaler,
			'ref'				=> $resultat->data[0]->ref,
			'merknad'			=> $resultat->data[0]->merknad,
			'registrerer'		=> $resultat->data[0]->registrerer,
			'dato'				=> new DateTime( $resultat->data[0]->dato ),
			'registrert'		=> new DateTime( $resultat->data[0]->registrert ),
			'delbeløp'			=> array(),
			'leieforhold'		=> array()
		);
		$this->id = $id;
		
		foreach( $resultat->data as $delbeløp ) {
			
			$this->data->beløp = bcadd( $this->data->beløp, $delbeløp->beløp, 6 );
			
			$this->data->delbeløp[] = (object)array(
				'id'			=>	$delbeløp->innbetalingsid,
				'beløp'			=>	$delbeløp->beløp,
				'registrerer'	=>	$delbeløp->registrerer,
				'registrert'	=>	new DateTime( $delbeløp->registrert ),
				'leieforhold'	=>	$delbeløp->leieforhold
									? $this->leiebase->hent('Leieforhold', $delbeløp->leieforhold )
									: null,
				'krav'			=>	$delbeløp->krav > 0
									? $this->leiebase->hent('Krav', $delbeløp->krav )
									: $delbeløp->krav
			);
			
			if($delbeløp->leieforhold) {
				$this->data->leieforhold[$delbeløp->leieforhold]
					= $this->leiebase->hent('Leieforhold', $delbeløp->leieforhold );
			}
		}
	}
	else {
		$this->id = null;
		$this->data = null;
	}

}



// Last giroens kjernedata fra databasen
/****************************************/
//	$param
//		id	(heltall) gironummeret	
//	--------------------------------------
protected function lastOcr() {
	$tp = $this->mysqli->table_prefix;
	
	if ( $this->data == null ) {
		$this->last();
	}		

	$resultat = $this->mysqli->arrayData(array(
		'returnQuery'	=> true,
		
		'fields' =>			"ocr_filer.registrert,
							ocr_filer.registrerer,
							ocr_filer.filID,
							OCRdetaljer.forsendelsesnummer,
							OCRdetaljer.oppdragsnummer,
							OCRdetaljer.transaksjonstype,
							OCRdetaljer.transaksjonsnummer,
							OCRdetaljer.oppgjørsdato,
							OCRdetaljer.delavregningsnummer,
							OCRdetaljer.løpenummer,
							OCRdetaljer.beløp,
							OCRdetaljer.kid,
							OCRdetaljer.blankettnummer,
							OCRdetaljer.arkivreferanse,
							OCRdetaljer.oppdragsdato,
							OCRdetaljer.debetkonto,
							OCRdetaljer.fritekst\n",
						
		'source' => 		"{$tp}OCRdetaljer AS OCRdetaljer\n"
						.	"LEFT JOIN {$tp}ocr_filer AS ocr_filer ON OCRdetaljer.filID = ocr_filer.filID\n",

		'where'			=>	"{$tp}OCRdetaljer.id = '{$this->data->OCRtransaksjon}'"
		
	));
	if( $resultat->totalRows ) {
		$this->data->ocr = $resultat->data[0];
		
		$this->data->ocr->registrert	= $this->data->ocr->registrert
										? new DateTime( $this->data->ocr->registrert )
										: null;

		$this->data->ocr->oppgjørsdato	= new DateTime( $this->data->ocr->oppgjørsdato );
		$this->data->ocr->oppdragsdato	= new DateTime( $this->data->ocr->oppdragsdato );
		
		switch( $this->data->ocr->transaksjonstype ) {
		
		case "10":
			$this->data->ocr->transaksjonsbeskrivelse = "Giro belastet konto";
			break;
		
		case "11":
			$this->data->ocr->transaksjonsbeskrivelse = "Faste Oppdrag";
			break;
		
		case "12":
			$this->data->ocr->transaksjonsbeskrivelse = "Direkte Remittering";
			break;
		
		case "13":
			$this->data->ocr->transaksjonsbeskrivelse = "BTG (Bedrifts Terminal Giro)";
			break;

		case "14":
			$this->data->ocr->transaksjonsbeskrivelse = "SkrankeGiro";
			break;

		case "15":
			$this->data->ocr->transaksjonsbeskrivelse = "AvtaleGiro";
			break;

		case "16":
			$this->data->ocr->transaksjonsbeskrivelse = "TeleGiro";
			break;

		case "17":
			$this->data->ocr->transaksjonsbeskrivelse = "Giro - betalt kontant";
			break;

		case "18":
			$this->data->ocr->transaksjonsbeskrivelse = "Reversering med KID";
			break;

		case "19":
			$this->data->ocr->transaksjonsbeskrivelse = "Kjøp med KID";
			break;

		case "20":
			$this->data->ocr->transaksjonsbeskrivelse = "Reversering med fritekst";
			break;

		case "21":
			$this->data->ocr->transaksjonsbeskrivelse = "Kjøp med fritekst";
			break;
		
		default:
			$this->data->ocr->transaksjonsbeskrivelse = "";
		}

	}
	else {
		$this->data->ocr = null;
	}

}



// Skriv ut innbetalingen etter en angitt mal
/****************************************/
//	$mal	(streng) gjengivelsesmalen
//	$param
//		leieforhold (heltall/Leieforhold-objekt):	Evt bestemt leieforhold som skal varsles
//	--------------------------------------
public function gjengi($mal, $param = array()) {
	settype( $param, 'object');
	$leiebase = $this->leiebase;

	switch($mal) {
	
	case "epost_betalingskvittering_html": {
		// Parametre:
		//		leieforhold (Leieforhold-objekt):	påkrevd
		
		if( !is_a($param->leieforhold, 'Leieforhold') ) {
			throw new Exception('Leieforhold ikke angitt');
		}
		
		$ocr = $this->hent('ocr');
		$beløp = 0;
		
		$this->samle();
		$delbeløp = array();
		
		foreach( $this->hent('delbeløp') as $del ) {
			if( strval($del->leieforhold) ==  strval($param->leieforhold)) {
				$beløp += $del->beløp;
				
				$delbeløp[] = (object)array(
					'id'				=> $del->id,
					'beløp'				=> $this->leiebase->kr($del->beløp),
					'utlikningstekst'	=> ($del->krav ? $del->krav->hent('tekst') : "<i>ikke utliknet</i>"),
					'opprinneligBeløp'	=> ($del->krav ? $this->leiebase->kr($del->krav->hent('beløp')) : ""),
					'utestående'		=> ($del->krav ? $this->leiebase->kr($del->krav->hent('utestående')) : ""),
				);
			}
		}


		$this->gjengivelsesdata = array(
			'leiebase'			=> $this->leiebase,
			'leieforholdnr'		=> $param->leieforhold->hentId(),
			'leieforholdbeskrivelse'	=> $param->leieforhold->hent('beskrivelse'),
			'betalingsdato'		=> $this->hent('dato')->format('d.m.Y'),
			'beløp'				=> $this->leiebase->kr($beløp),
			'transaksjonsmåte'	=> ($ocr ? $ocr->transaksjonsbeskrivelse : ""),
			'betaler'			=> $this->hent('betaler'),
			'kid'				=> ($ocr ? $ocr->kid : ""),
			'referanse'			=> $this->hent('ref'),
			'bunntekst'			=> $this->leiebase->valg['eposttekst'],
			'delbeløp'			=> $delbeløp
		);

		break;
	}
	
	case "epost_betalingskvittering_txt": {
		// Parametre:
		//		leieforhold (Leieforhold-objekt):	påkrevd
		
		if( !is_a($param->leieforhold, 'Leieforhold') ) {
			throw new Exception('Leieforhold ikke angitt');
		}
		
		$ocr = $this->hent('ocr');
		$beløp = 0;
		
		$this->samle();
		$delbeløp = array();
		
		foreach( $this->hent('delbeløp') as $del ) {
			if( strval($del->leieforhold) ==  strval($param->leieforhold)) {
				$beløp += $del->beløp;
				
				$delbeløp[] = (object)array(
					'id'				=> $del->id,
					'beløp'				=> $this->leiebase->kr($del->beløp, false),
					'utlikningstekst'	=> ($del->krav ? $del->krav->hent('tekst') : "ikke utliknet"),
					'opprinneligBeløp'	=> ($del->krav ? $this->leiebase->kr($del->krav->hent('beløp'), false) : ""),
					'utestående'		=> ($del->krav ? $this->leiebase->kr($del->krav->hent('utestående'), false) : ""),
				);
			}
		}


		$this->gjengivelsesdata = array(
			'leieforholdnr'		=> $param->leieforhold->hentId(),
			'leieforholdbeskrivelse'	=> $param->leieforhold->hent('beskrivelse'),
			'betalingsdato'		=> $this->hent('dato')->format('d.m.Y'),
			'beløp'				=> $this->leiebase->kr($beløp, false),
			'transaksjonsmåte'	=> ($ocr ? $ocr->transaksjonsbeskrivelse : ""),
			'betaler'			=> $this->hent('betaler'),
			'kid'				=> ($ocr ? $ocr->kid : ""),
			'referanse'			=> $this->hent('ref'),
			'bunntekst'			=>  strip_tags( str_ireplace(
										array("<br />","<br>","<br/>"),
										"\r\n",
										$this->leiebase->valg['eposttekst']
									) ),
			'delbeløp'			=> $delbeløp
		);

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

	case "beløp":
	case "betaler":
	case "dato":
	case "delbeløp":
		// Hvert delbeløp er et objekt med egenskapene:
		//	id (heltall)
		//	beløp (tall)
		//	registrerer (streng)
		//	registrert (DateTime-objekt)
		//	leieforhold (Leieforhold-objekt eller null)
		//	krav (Krav-objekt eller null)
	case "id":
	case "innbetaling":
	case "konto":
	case "kontokode":
	case "kontonavn":
	case "leieforhold":	
	case "merknad":
	case "OCRtransaksjon":
	case "ref":
	case "registrerer":
	case "registrert":
	{
		if ( $this->data == null ) {
			$this->last();
		}		
		return $this->data->$egenskap;
		break;
	}
	
	case "ikkeKontert": {
		$ikkeKontert = 0;
		foreach( $this->hent('delbeløp') as $del ) {
			if($del->leieforhold === null) {
				$ikkeKontert += $del->beløp;
			}
		}
		return $ikkeKontert;
		break;
	}
	
	case "ikkeFordelt": {
		$ikkeFordelt = 0;
		foreach( $this->hent('delbeløp') as $del ) {
			if($del->krav === null) {
				$ikkeFordelt += $del->beløp;
			}
		}
		return $ikkeFordelt;
		break;
	}
	
	case "ocr": {
		if ( !isset( $this->data->ocr ) ) {
			$this->lastOcr();
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



/*	Balanser betalingsutlikninger
Tilser at summen av betalinger balansert mot hverandre summert blir 0,
og at den negative delen tilsvarer kredittkrav-beløpet
******************************************
------------------------------------------
*/
public function balanserKreditt() {
	$tp = $this->mysqli->table_prefix;
	$resultat = false;
	$kredittkrav = $this->hentKredittkrav();	
	if(!$kredittkrav) {
		return false;
	}
	
	$kravbeløp = $kredittkrav->hent('beløp');
	$delbeløp = $this->hent('delbeløp');
	$kreditt = array();
	$totalKreditt = 0;
	$debet = array();
	
	foreach( $delbeløp as $del ) {
	
		if( $del->beløp < 0) {
			// Det skal alltid være bare én debet-del i betalinga
			if( count($debet) < 1) {
				$debet[] = $del;
			}
			else {
				$this->mysqli->query("
					DELETE
					FROM {$tp}{$this->tabell}
					WHERE {$tp}{$this->tabell}.{$this->idFelt} = '{$this->hentId()}'
						AND konto = '0'
						AND innbetalingsid = '{$del->id}'
				");
			}
		}
		
		else {
			$kreditt[] = $del;
			$totalKreditt += $del->beløp;
		}
	}
	
	$debet = reset($debet);
	
	if( $debet->beløp != $kravbeløp ) {
		$this->mysqli->saveToDb(array(
			'update'	=>	true,
			'table'		=>	"{$tp}{$this->tabell}",
			'where'		=>	"{$tp}{$this->tabell}.{$this->idFelt} = '{$this->hentId()}' AND {$tp}{$this->tabell}.innbetalingsid = '{$debet->id}'",
			'fields'	=> array(
				'beløp'		=> $kravbeløp
			)
		));
	}
	
	$differanse =  -$kravbeløp - $totalKreditt;
	
	if( $differanse == 0 ) {
		return true;
	}
	
	else if( $differanse > 0 ) {
		$this->leggTilDelbeløp( $differanse, $kredittkrav->hent('leieforhold') );
	}
	
	else {
		foreach( $kreditt as $del ) {
			$differanse -= $this->korrigerDelbeløp( $del->id, $differanse );
		}
	}

	if( $differanse != 0 ) {
		return false;
	}

	// Tving ny lasting av data:
	$this->data = null;
	
	return true;
}



/*	Frakople ei innbetaling fra ei bestemt utlikning
Frigjør innbetalinga fra ei bestemt utlikning.
*****************************************
$motkrav (objekt, null eller av, normalt av):	Motkravet som betalinga skal koples fra.
	Motkravet kan være et Krav-objekt, eller et vilkårlig Innbetalingsobjekt.
	Det kan også være et Leieforhold-objekt,
	noe som innebærer at alle kravene som tilhører dette leieforholdet frakoples.
	Dersom motkravet er null, vil bare ufordelte deler av betalinga berøres, for å frakople leieforhold.
$frakopleLeieforhold (boolsk, normalt sann):	Dersom sann vil betalinga
	ikke bare løsnes fra det enkelte kravet eller motbetalinga,
	men også frakoples fra leieforholdet sånn at det kan krediteres et annet leieforhold
$leieforhold (Leieforhold-objekt):	Dersom $motkrav er et Innbetalingsobjekt,
	må det angis her hvilket leieforhold den delen av betalinga som skal frakoples tilhører.
	Om denne er null vil all utlikning mot andre innbetalinger frakoples.
--------------------------------------
retur (boolsk): sann dersom vellykket, ellers usann
*/
public function frakople( $motkrav = false, $frakopleLeieforhold = false, $leieforhold = null ) {
	$tp = $this->mysqli->table_prefix;
	$resultat = false;
	
	$felter = array( 'krav' => null );
	if($frakopleLeieforhold) {
		$felter['leieforhold'] = null;
	}
	
	if( $motkrav instanceof Krav ) {
		$filter = "{$this->tabell}.krav = '{$motkrav}'";
	}
	else if( $motkrav instanceof Leieforhold ) {
		$filter = "{$this->tabell}.leieforhold = '{$motkrav}'";
	}
	else if( $motkrav instanceof Innbetaling ) {
		$filter = "{$this->tabell}.krav = '0'" . ($leieforhold ? " AND leieforhold = '{$leieforhold}'" : "");
	}
	else if( $motkrav === null ) {
		$filter = "{$this->tabell}.krav IS NULL" . ($leieforhold ? " AND leieforhold = '{$leieforhold}'" : "");
	}
	else if( $motkrav === false ) {
		$filter = "1";
	}
	else {
		throw new Exception("Ugyldig første argument i Innbetaling::frakople(). " . gettype($motkrav) . " forsøkt gitt.");
	}
	$beløp = floatval($this->mysqli->arrayData(array(
		'source'		=> "{$tp}{$this->tabell} as {$this->tabell}",
		'fields'		=> "SUM(beløp) AS beløp",
		'where'			=> "{$this->tabell}.{$this->idFelt} = '{$this->id}'\n"
						.	"AND {$filter}\n"
	))->data[0]->beløp);
				
	$resultat = $this->mysqli->saveToDb(array(
		'table'		=> "{$tp}{$this->tabell} as {$this->tabell}",
		'update'		=> true,
		'fields'		=> $felter,
		'where'			=> "{$this->tabell}.{$this->idFelt} = '{$this->id}'\n"
						.	"AND {$filter}\n"
		
						// Den negative delen av innbetalingselementet av en kreditt
						// må aldri løsnes fra kredittkravet
						.	"AND (konto !='0' OR beløp > '0')\n"
	))->success;
				
	if( $motkrav instanceof Innbetaling ) {
		$this->_frakopleMotbetalinger( $leieforhold, $beløp );
	}
	

	$this->data = null;
	return $resultat;
}



/*	Fordel
Fordeler deler av eller hele betalinga mot krav eller motsatte innbetalinger.
Dersom et motkrav angis vil betalinga utliknes mot dette kravet,
inntil maksbeløpet eller til betalinga er oppbrukt.
Dersom et hvilket som helst betalingsobjekt angis som motkrav,
vil betalinga utliknes mot vilkårlige motbetalinger i leieforholdet som er oppgitt.
Dersom motkrav ikke er oppgitt vil de løse delbeløpene av betalinga utliknes etter beste evne
*****************************************
$motkrav (objekt):		Motkravet som betalinga skal brukes mot.
					Motkravet kan være et Krav-objekt eller et (vilkårlig) Innbetalingsobjekt
$maksbeløp (tall, normalt null):	Maksimalt beløp som skal brukes (ut ifra 0).
					Beløpet kommer i tillegg til evt eksisterende delbeløp
					Dersom null vil hele den ikke-konterte delen av betalinga føres på det angitte leieforholdet.
					Maksbeløp brukes kun dersom motkrav er oppgitt.
$leieforhold (Leieforhold-objekt):	Dersom $motkrav er null eller et Innbetalingsobjekt,
					angis her hvilket leieforhold fordelingen skal foregå i.
					Dersom leieforhold er null vil alle eksisterende leieforhold i betalinga bli fordelt.
					Ved utlikning mot betalinger er denne påkrevd
--------------------------------------
retur (tall): det fordelte beløpet
*/
public function fordel( $motkrav = null, $maksbeløp = null, $leieforhold = null ) {
	$tp = $this->mysqli->table_prefix;
	$resultat = 0;
	$ocr = $this->hent('ocr');
	$kid = $ocr ? $ocr->kid : null;
	$kidKrav = $kid ? $this->leiebase->kravFraKid($kid) : array();

	// Maksbeløp skal kun brukes dersom motkrav er angitt
	if( $motkrav === null ) {
		$maksbeløp = null;
	}

	// Kontroller at maksbeløpet har rett fortegn
	if( ($maksbeløp !== null and $this->hent('beløp') * $maksbeløp) < 0) {
		throw new Exception("Feil fortegn gitt på argument nr. 2 (maksbeløp) i Innbetaling::fordel().");
	}
	
	// Kontroller at leieforhold er oppgitt dersom utlikning mot andre betalinger
	if( $motkrav instanceof Innbetaling and !$leieforhold) {
		throw new Exception("Leieforhold er påkrevd i Innbetaling::fordel() ved utlikning mot betaling.");
	}


	// Ved utlikning mot krav hentes leieforholdet fra dette kravet
	if( $motkrav instanceof Krav) {
		$leieforhold = $motkrav->hent('leieforhold');
	}
	
	// Rydd opp før fordeling
	$this->samle();
	
	foreach( $this->hent('delbeløp') as $del ) {
	
		// Fordelinga utføres kun mot de delene av betalinga som er kontert på dette leieforholdet
		//	men som ikke allerede er fordelt
		if(
			$del->krav === null
			&& $del->leieforhold
			&& ( (strval($del->leieforhold) == strval($leieforhold)) or $leieforhold === null )
		) {
			if( $motkrav ) {
				$motkravsett = array( $motkrav );
			}
			else {
				$motkravsett = array();
				if( strval($this->leiebase->leieforholdFraKid($kid)) == strval($del->leieforhold) ) {
					$motkravsett = $kidKrav;
				}
				$motkravsett = array_merge($motkravsett, $del->leieforhold->hent('ubetalteKrav'));
			}
			
			$resultat += $this->_fordel( $del, $motkravsett, ($maksbeløp ? $maksbeløp - $resultat : null) );
		}
	}
	
	$this->data = null;
	
	return $resultat;	
}



/*	Henter ett bestemt delbeløp
*****************************************
$id (heltall): Id-nummeret for delbeløpet
//	--------------------------------------
retur (stdClass-objekt eller false): Dersom delbeløpet finnes resturneres følgende objekt:
	id (heltall): Delbeløpets id-nummer
	beløp (tall)
	registrerer (streng)
	registrert (DateTime-objekt)
	leieforhold (Leieforhold-objekt eller null)
	krav (Krav-objekt eller null)
*/
public function hentDelbeløp( $id ) {
	settype( $id, 'integer' );
	foreach( $this->hent('delbeløp') as $delbeløp ) {
		if($delbeløp->id == $id ) {
			return $delbeløp;
		}
	}
	return false;
}



/*	Henter kredittkravet dersom betalinga tilhører kreditt
*****************************************
//	--------------------------------------
retur (Krav-objekt eller false): Dersom transaksjonen tilhører kreditt vil kredittkravet returneres
*/
public function hentKredittkrav() {
	if( $this->hent('konto') != '0') {
		return false;
	}
	
	foreach( $this->hent('delbeløp') as $delbeløp ) {
		if($delbeløp->beløp < 0) {
			return $delbeløp->krav;
		}
	}
	unset($delbeløp);
	
	throw new Exception("Fant ikke kreditt tilhørende transaksjon '{$this->hentId()}'");
}



/*	Konter
Konterer deler av eller hele betalinga mot et bestemt leieforhold.
Dersom ei betaling skal flyttes fra et leieforhold til et annet
må den eksisterende konteringa frakoples først med Innbetaling::frakople().
*****************************************/
//	$leieforhold(-objekt eller id):	Leieforholdet som beløpet skal konteres mot, i tillegg til tidligere konteringer
//	$maksbeløp (tall, normalt null):	Maksimalt beløp som skal konteres mot dette leieforholdet (ut ifra 0).
//						Beløpet kommer i tillegg til evt tidligere konterte delbeløp
//						Dersom null vil hele den ikke-konterte delen av betalinga føres på det angitte leieforholdet.
//	--------------------------------------
//	retur: boolsk Sann dersom deler av betalinga har blitt kontert. Usann ellers.
public function konter( $leieforhold, $maksbeløp = null ) {
	$tp = $this->mysqli->table_prefix;
	
	if( ($maksbeløp !== null and $this->hent('beløp') * $maksbeløp) < 0) {
		throw new Exception("Feil fortegn gitt på maksbeløp i Innbetaling::konter().");
	}
	if(!strval($leieforhold)) {
		throw new Exception("Ugyldig leieforhold: " . var_export($leieforhold, true));
	}
	
	$this->samle();
	
	if( $maksbeløp === null ) {
		$this->mysqli->saveToDb(array(
			'table'			=> "{$tp}{$this->tabell}",
			'update'		=> true,
			'where'			=> "{$this->tabell}.{$this->idFelt} = '{$this->id}'\n"
							.	"AND leieforhold IS NULL\n",
			'fields'		=> array(
				'leieforhold'	=> $leieforhold
			)
		));

		$this->data = null;
		$this->samle();
		return true;
	}
	
	$beløp = 0;
	foreach( $this->hent('delbeløp') as $del ) {
		if($del->leieforhold === null and ($del->beløp * $maksbeløp >=0) ) {
			$beløp += $del->beløp;
		}
	}

	if( $beløp == 0 || $maksbeløp == 0 ) {
		return false;
	}
	else if( $maksbeløp < 0 ) {
		$maksbeløp = max($maksbeløp, $beløp);
	}
	else {
		$maksbeløp = min($maksbeløp, $beløp);
	}	
	
	
	foreach( $this->hent('delbeløp') as $del ) {
		if( !$del->leieforhold and ($del->beløp * $maksbeløp >=0)) {
			$this->mysqli->saveToDb(array(
				'table'			=> "{$tp}{$this->tabell}",
				'update'		=> true,
				'where'			=> "{$this->tabell}.{$this->idFelt} = '{$this->id}'\n"
								.	"AND innbetalingsid = '{$del->id}'\n",
				'fields'		=> array(
					'beløp'			=> $maksbeløp,
					'leieforhold'	=> $leieforhold
				)
			));
			// Om nødvendig må delbeløpet splittes
			if( $maksbeløp != $beløp ) {
				$this->mysqli->saveToDb(array(
					'table'		=> "{$tp}{$this->tabell}",
					'insert'	=> true,
					'fields'	=> array(
						"innbetaling"		=> $this->hentId(),
						"konto"				=> $this->hent('konto'),
						"OCRtransaksjon"	=> $this->data->OCRtransaksjon, // Denne ble lastet ved forrige hent()
						"ref"				=> $this->hent('ref'),
						"dato"				=> $this->hent('dato')->format('Y-m-d'),
						"merknad"			=> $this->hent('merknad'),
						"betaler"			=> $this->hent('betaler'),
						"registrerer"		=> $this->hent('registrerer'),
						"registrert"		=> $this->hent('registrert')->format('Y-m-d H:i:s'),
						"beløp"				=> $beløp - $maksbeløp
					)
				));
			}
	
			$this->data = null;
			$this->samle();
			return true;
		}
	}
	return false;
	
}



/*	Korriger delbeløp
Forsøker å øke eller redusere et delbeløp i betalinga.
Funksjonen returnerer korrigeringa som ble utført
******************************************
$id (heltall) Id-nummeret for delbeløpet som skal endres
$beløp (tall) Ideelt beløp delbeløpet skal korrigeres med
------------------------------------------
retur: (tall) Den faktiske korrigeringen som ble foretatt
*/
public function korrigerDelbeløp( $id, $beløp ) {
	$tp = $this->mysqli->table_prefix;
	settype( $id, 'integer' );
	settype( $beløp, 'float' );
	
	if( $beløp == 0 ) {
		return 0;
	}
	
	$this->last();
	
	foreach( $this->data->delbeløp as $del ) {
		if( $del->id == $id ) {
			if( $del->beløp == 0 ) {
				return 0;
			}
			if( ($del->beløp * $beløp) > 0 ) {
				return $beløp * $this->leggTilDelbeløp( $beløp, $del->leieforhold );
			}
			else {
				$beløp = (abs( $beløp ) <= abs( $del->beløp	)) ? $beløp : -$del->beløp;
				
				if( $this->mysqli->saveToDb(array(
					'update'	=> true,
					'table'		=> "{$tp}{$this->tabell}",
					'where'		=> "{$tp}{$this->tabell}.{$this->idFelt} = '{$this->hentId()}' AND {$tp}{$this->tabell}.innbetalingsid = '{$del->id}'",
					'fields'	=> array(
						'beløp'	=> $del->beløp + $beløp
					)
				))->success ) {
					$this->data = null;
					return $beløp;
				}
				else {
					return 0;
				}				
			}
		}
	}
	
	$this->samle();
}



/*	Legg til delbeløp
Legg til et delbeløp på denne betalinga
******************************************
$beløp (tall) beløpet som skal legges til
$leieforhold (Leieforhold-objekt/heltall) Evt. leieforhold delbeløpet skal knyttes mot
$krav (Leieforhold-objekt/heltall) Evt. krav delbeløpet skal utliknes mot
------------------------------------------
retur: (bool) Suksessangivelse
*/
public function leggTilDelbeløp( $beløp, $leieforhold = null, $krav = null ) {
	$tp = $this->mysqli->table_prefix;
	
	$result = $this->mysqli->saveToDb(array(
		'insert'	=>	true,
		'table'		=>	"{$tp}{$this->tabell}",
		'fields'	=> array(
			'innbetaling'		=> $this->hentId(),
			'konto'				=> $this->hent('konto'),
			'OCRtransaksjon'	=> $this->hent('OCRtransaksjon'),
			'ref'				=> $this->hent('ref'),
			'dato'				=> $this->hent('dato')->format('Y-m-d'),
			'merknad'			=> $this->hent('merknad'),
			'betaler'			=> $this->hent('betaler'),
			'registrerer'		=> $this->leiebase->bruker['navn'],
			'beløp'				=> $beløp,
			'leieforhold'		=> $leieforhold,
			'krav'				=> $krav,
		)
	))->success;

	// Tving ny lasting av data:
	$this->data = null;
	
	return $result;
}



// Oppretter en ny innbetaling i databasen og tildeler egenskapene til dette objektet
/****************************************/
//	$egenskaper (array/objekt) Alle egenskapene det nye objektet skal initieres med
//	--------------------------------------
public function opprett($egenskaper = array()) {
	$tp = $this->mysqli->table_prefix;
	settype( $egenskaper, 'array');
	
	if( $this->id ) {
		throw new Exception('Nytt Innbetaling-objekt forsøkt opprettet, men det eksisterer allerede');
		return false;
	}
	
	if( !@$egenskaper['dato'] ) {
		throw new Exception('Nytt Innbetaling-objekt forsøkt opprettet, men mangler dato');
		return false;
	}
	
	$databasefelter = array(
		'registrerer'	=> $this->leiebase->bruker['navn']
	);
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

		case "dato":
		case "beløp":
		case "betaler":
		case "ref":
		case "merknad":
		case "konto":
			$databasefelter[$egenskap] = $verdi;
			break;

		default:
			$resterendeFelter[$egenskap] = $verdi;
			break;
		}		
	}
		
	$id = $this->mysqli->saveToDb(array(
		'insert'	=> true,
		'table'		=> "{$tp}{$this->tabell}",
		'fields'	=> $databasefelter
	))->id;
	
	$this->id = $this->mysqli->arrayData(array(
		'source'	=> "{$tp}{$this->tabell} as {$this->tabell}",
		'where'		=> "{$this->tabell}.innbetalingsid = '{$id}'",
		'fields'	=>	"concat(dato, '-', left(md5(concat(ref, '-', betaler, '-', OCRtransaksjon)), 4)) AS id"
	))->data[0]->id;
	$this->mysqli->saveToDb(array(
		'update'	=> true,
		'returnQuery'	=> true,
		'table'		=> "{$tp}{$this->tabell} as {$this->tabell}",
		'where'		=> "{$this->tabell}.innbetalingsid = '{$id}'",
		'fields'	=> array(
			"{$this->tabell}.{$this->idFelt}"	=> $this->id
		)
	));

	if( !$this->hentId() ) {
		throw new Exception('Nytt Innbetaling-objekt forsøkt opprettet, men metoden er ikke ferdigutviklet. Egenskaper: ' . var_export( $egenskaper, true ));
		return false;
	}

	foreach( $resterendeFelter as $egenskap => $verdi ) {
		$this->sett($egenskap, $verdi);
	}
	
	return $this;
}



/*	Samle
Rydder opp i unødig fragmentering ved å samle delbeløp til ett større
der dette er hensiktsmessig.
Samlede delbeløp beholder den id'en
*****************************************/
//	--------------------------------------
//	retur (boolsk): Om innbetalingen ble endret eller ikke
public function samle() {
	$tp = $this->mysqli->table_prefix;
	$resultat = false;
	
	$delbeløpsett = $this->hent('delbeløp');
	
	$a = array();
	foreach( $delbeløpsett as $delbeløp ) {
		settype(
			$a[strval($delbeløp->leieforhold)],
			'array'
		);
		
		// Siden null som nøkkel formes som en tom streng, vil null som krav behandles annerledes enn 0
		//	null => '' og '0' => 0
		// For å unngå at betalinger med blandet transaksjonsretning kollapser
		//	(f.eks kredittlinker med et positivt og et negativt beløp)
		//	så må disse beløpene behandles adskilt.
		// Dette angis med positive og negative kravnøkler
		$fortegn = '';
		if( $delbeløp->beløp < 0) {
			$fortegn = '-';
		}
		
		settype(
			$a [strval($delbeløp->leieforhold)] ["{$fortegn}{$delbeløp->krav}"],
			'array'
		);
		$a [strval($delbeløp->leieforhold)] ["{$fortegn}{$delbeløp->krav}"] [$delbeløp->id]
			= $delbeløp->beløp;
	}
	
	$beløp = 0;
	foreach( $a as $kravsett ) {
		foreach( $kravsett as $krav ) {
			if( count($krav) > 1) {
				$resultat = true;
				$beløp = array_sum( $krav );
				$id = min(array_keys($krav));
				$this->mysqli->saveToDb(array(
					'table'		=> "{$tp}{$this->tabell} as innbetalinger",
					'update'	=> true,
					'where'		=> "{$tp}{$this->tabell}.{$this->idFelt} = '{$this->hentId()}' AND {$tp}{$this->tabell}.innbetalingsid = '{$id}'",
					'fields'	=> array(
						'beløp'		=> $beløp
					)
				));
				foreach($krav as $delid => $beløp) {
					if($delid != $id) {
						$this->mysqli->query("
							DELETE
							FROM {$tp}{$this->tabell}
							WHERE {$tp}{$this->tabell}.{$this->idFelt} = '{$this->hentId()}'
								AND {$tp}{$this->tabell}.innbetalingsid = '{$delid}'
						");
					}
				}
			}
		}
	}
	
	$this->data = null;
	
	return $resultat;
}



/*	Send epostkvittering til leietakerne om at betalingen er mottatt
*****************************************/
//	--------------------------------------
//	retur: boolsk suksessangivelse
public function sendKvitteringsepost() {
	$tp = $this->mysqli->table_prefix;
	
	$this->samle();
	$leieforholdsett = $this->hent('leieforhold');
	
	foreach( $leieforholdsett as $leieforhold ) {
		$emne =	"Melding om registrert innbetaling";
		$html = $this->gjengi('epost_betalingskvittering_html', array(
			'leieforhold'	=> $leieforhold
		));
		$tekst = $this->gjengi('epost_betalingskvittering_txt', array(
			'leieforhold'	=> $leieforhold
		));

		$adressefelt = $leieforhold->hent('brukerepost', array('innbetalingsbekreftelse' => true));
		if ($adressefelt) {
			$this->leiebase->sendMail(array(
				'to'		=> implode(',', $adressefelt),
				'subject'	=> $emne,
				'html'		=> $html,
				'text'		=> $tekst,
				'testcopy'	=> false
			));
		}
	}
}



/*	Skriv en verdi
Returnerer en ny forfallsdato som tilfredstiller kravene
som er satt i innstillingene for leiebasen.
*****************************************/
//	$egenskap		streng. Egenskapen som skal endres
//	$verdi			Ny verdi
//	--------------------------------------
//	retur: boolsk suksessangivelse
public function sett($egenskap, $verdi = null) {
	$tp = $this->mysqli->table_prefix;
	$resultat = false;
	
	if( !$this->id ) {
		return false;
	}
	
	if(
		$egenskap == "dato"
	||	$egenskap == "betaler"
	||	$egenskap == "konto"
	||	$egenskap == "ref"
	||	$egenskap == "merknad"
	) {
		if ($verdi instanceof DateTime) {
			$verdi = $verdi->format('Y-m-d');
		}

		$resultat = $this->mysqli->saveToDb(array(
			'update'	=> true,
			'returnQuery'	=> true,
			'table'		=> "{$tp}{$this->tabell} as {$this->tabell}",
			'where'		=> "{$this->tabell}.{$this->idFelt} = '{$this->id}'",
			'fields'	=> array(
				"{$this->tabell}.{$egenskap}"	=> $verdi
			)
		))->success;
		
		
		//	!!OBS!! Objektet endrer id
		$id = $this->mysqli->arrayData(array(
			'source'	=> "{$tp}{$this->tabell} as {$this->tabell}",
			'where'		=> "{$this->tabell}.{$this->idFelt} = '{$this->id}'",
			'fields'	=>	"concat(dato, '-', left(md5(concat(ref, '-', betaler, '-', OCRtransaksjon)), 4)) AS id"
		))->data[0]->id;
		$this->mysqli->saveToDb(array(
			'update'	=> true,
			'returnQuery'	=> true,
			'table'		=> "{$tp}{$this->tabell} as {$this->tabell}",
			'where'		=> "{$this->tabell}.{$this->idFelt} = '{$this->id}'",
			'fields'	=> array(
				"{$this->tabell}.{$this->idFelt}"	=> $id
			)
		));
		$this->id = $id;


		// Tving ny lasting av data:
		$this->data = null;
	}
	
	if(
		$egenskap == "beløp"
	) {
		$tidligereVerdi = $this->hent('beløp');
		
		// Dersom beløpet er uendret er saken biff
		if ($verdi == $tidligereVerdi) {
			$resultat = true;
		}
		
		// Dersom beløpet endres, må det legges til eller trekkes ifra delbeløp
		else {
			$rest = $verdi;
			$reduksjon = $verdi - $tidligereVerdi;
			
			// Dersom beløpet skal endres i retning mot 0
			//	(dvs opprinnnlig verdi og endringsdifferansen har ulike fortegn)
			//	må overskytende delbeløp trekkes ifra.
			if( ( $tidligereVerdi * $reduksjon ) < 0 ) {
				foreach( $this->hent('delbeløp') as $delbeløp ) {
					
					// Delbeløpene skal endres i retning 0.
					//	Dersom rest og delbeløp befinner seg på hver sin side av 0
					//	så må delbeløpet fjernes.
					//	Resten forblir uendret.
						
					//	Dersom rest og delbeløp befinner seg på samme side av 0
					//	men absoluttverdien av resten er mindre enn absoluttverdien av delbeløpet
					//	så må delbeløpet reduseres til resten.
					//	Resten blir 0.

					$krysser0 = ($rest * $delbeløp->beløp) < 0;
					if(
						$krysser0
					||	abs($rest) < abs($delbeløp->beløp)
					) {
						$this->mysqli->saveToDb(array(
							'update'	=> true,
							'returnQuery'	=> true,
							'table'		=> "{$tp}{$this->tabell} as {$this->tabell}",
							'where'		=> "{$tp}{$this->tabell}.{$this->idFelt} = '{$this->hentId()}'
											AND {$this->tabell}.innbetalingsid = '{$delbeløp->id}'",
							'fields'	=> array(
								"{$this->tabell}.beløp"		=> ($krysser0 ? 0 : $rest)
							)
						));
						$rest = $krysser0 ? $rest : 0;
					}
				
					//	Dersom rest og delbeløp befinner seg på samme side av 0
					//	og absoluttverdien av resten er lik eller større enn absoluttverdien av delbeløpet
					//	så er delbeløpet innenfor resten
					//	og kan beholdes uendret.
					//	Resten redusereres med delbeløpet
					
					else {
						$rest = round($rest - $delbeløp->beløp, 2);
					}
				}
			}
			
			//	Dersom beløper endres ut fra 0
			//	(Det negative eller positive beløpet øker)
			//	så legges det til et nytt delbeløp med økningen
			else {
				$rest = round($rest - $this->hent('beløp'), 2);
			}

			if (
				$rest != 0
			&&	$this->mysqli->saveToDb(array(
					'insert'	=> true,
					'returnQuery'	=> true,
					'table'		=> "{$tp}{$this->tabell}",
					'fields'	=> array(
						"innbetaling"		=> $this->hentId(),
						"konto"				=> $this->hent('konto'),
						"OCRtransaksjon"	=> $this->data->OCRtransaksjon, // Denne ble lastet ved forrige hent()
						"ref"				=> $this->hent('ref'),
						"dato"				=> $this->hent('dato')->format('Y-m-d'),
						"merknad"			=> $this->hent('merknad'),
						"betaler"			=> $this->hent('betaler'),
						"registrerer"		=> $this->hent('registrerer'),
						"registrert"		=> $this->hent('registrert')->format('Y-m-d H:i:s'),
						"beløp"				=> $rest,
					)
				))->success
			) {
				$rest = 0;
			}

			$resultat = !$rest;
		}
	}

	// Tving ny lasting av data:
	$this->data = null;

	$this->samle();
	return $resultat;
}



}?>