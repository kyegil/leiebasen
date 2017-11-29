<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

define('LEGAL',true);
require_once('config.php');
require_once('klassedefinisjoner/index.php');
require_once('tillegg/index.php');
set_time_limit(0);

$tid = time();

$mysqliConnection = new MysqliConnection;
$leiebase = new Leiebase;


//	Send bekreftelse på alle nye innbetalinger som er mer en 2 timer gamle
$leiebase->varsleNyeInnbetalinger(time() - 2 * 3600);

//	Send påminnelse om alle ubetalte krav som er i ferd med å forfalle
$leiebase->varsleForfall();


//	Variabelen $avtalegiroOppdragTilNets samler opp AvtaleGiro-oppdrag til NETS,
//	sånn at disse blir overført samlet.
$avtalegiroOppdragTilNets = array();
$efakturaOppdragTilNets = array();


//	Last PhpSecLib, som brukes til å opprette sFTP-forbindelse mot NETS
if( !file_exists(PATH_TO_PHPSECLIB) ) {
	$leiebase->mysqli->saveToDb(array(
		'update'	=> true,
		'table'		=> "valg",
		'where'		=> "innstilling = 'OCR_feilmelding'",
		'fields'	=> array(
			'verdi'	=>	"Automatisk forsøk på å hente forsendelse fra NETS<br />mislyktes " . date('d.m.Y') . "  kl. " . date('H:i:s') . ".<br />Leiebasen klarte ikke å laste phpseclib, som er nødvendig for å hente betalingsinformasjon fra Nets, pga. feilkonfigurering i fila cron.php linje 19.<br />. Filbanen " . PATH_TO_PHPSECLIB . " finnes ikke<br />Betalingsinformasjon må hentes manuelt fra NETS inntil feilen er reparert."
		)
	));
}


//	Ingen er logget inn, så det opprettes en egen profil for de automatiske prossessene
$leiebase->bruker = array(
	'navn' => 'automatisk cron-skript',
	'id' => '',
	'brukernavn' => 'crontab',
	'epost' => ''
);


//	Opprett leiekrav i alle leieforhold hvor slike mangler
$leiebase->opprettLeiekrav();


// Hent NETS-forsendelser og lagre på lokal tjener:
$post = $leiebase->netsHentForsendelser();

//	Kan brukes for manuell innlesing av fil:
// $post = array('/home/svartlam/boligstiftelsen_filarkiv/nets/inn/ocr/2016-06/OCR.D270616');


// Gå gjennom oppdragene i evt forsendelser og utfør disse:
if ( $post ) {
	foreach( $post as $forsendelse ) {
	
		$innhold =  file_get_contents( $forsendelse );
		$forsendelse = new NetsForsendelse(
			mb_convert_encoding(
				$innhold,
				'UTF-8',
				mb_detect_encoding( $innhold , 'UTF-8, ISO-8859-1', true)
			)
		);
		unset($innhold);

		if(!$forsendelse->valider()) {
			// Forsendelsen er ugyldig
			
			$leiebase->mysqli->saveToDb(array(
				'update'	=> true,
				'table'		=> "valg",
				'where'		=> "innstilling = 'OCR_feilmelding'",
				'fields'	=> array(
					'verdi'	=>	"Automatisk forsøk på å hente forsendelse fra NETS<br />mislyktes "
					. date('d.m.Y') . "  kl. " . date('H:i:s')
					. ".<br />Dataforsendelsen inneholder feil:<br />"
					. nl2br( $forsendelse )
				)
			));
		}
		
		else {
			// Forsendelsen er OK
		
			// Behandle alle konteringsoppdrag i forsendelsen:
			$leiebase->registrerOcrKonteringsdata( $forsendelse );

			// Se etter nye faste betalingsoppdrag (avtalegiro-avtaler)
			if( $leiebase->valg['avtalegiro'] ) {
				$leiebase->registrerFbo( $forsendelse );
			}


			// Behandle eFaktura
			if( $leiebase->valg['efaktura'] ) {
			
				// Behandle evt. eFaktura-kvitteringer i forsendelsen:
				$leiebase->registrerEfakturaKvitteringer( $forsendelse );

				// Behandle efakturaforespørselene i forsendelsen,
				// og send responsefil tilbake til NETS:			
				if ( $response = $leiebase->registrerEfakturaForespørsler( $forsendelse ) ) {

					$efakturaOppdragTilNets[] = $response;
				}
			}
		}
	}
}


if( $leiebase->valg['avtalegiro'] ) {

	// En gang per dag mellom klokka 1330 og 14
	// lages det en avtalegiro filforsendelse til NETS
	if(
		date('Hm') >= 1330
		and $leiebase->valg['siste_fbo_trekkrav'] < date('Y-m-d')
	) {
		$leiebase->netsSlettUsendteAvtalegiroer();

		if( $sletteoppdrag = $leiebase->fboSlettTrekkrav() ) {
			$avtalegiroOppdragTilNets[] = $sletteoppdrag;
		}
	
		if( $kravoppdrag = $leiebase->fboSendTrekkrav() ) {
			$avtalegiroOppdragTilNets[] = $kravoppdrag;
		}
	}
		
	// Send varsel om betalingsoppkrav som har blitt sendt til innkreving
	$leiebase->fboVarsle();

}

// Dersom det er laget oppdrag til NETS
// Så sendes disse i en forsendelse
if( $avtalegiroOppdragTilNets ) {
	$avtalegiroForsendelseTilNets
		= $leiebase->netsLagAvtalegiroForsendelse( $avtalegiroOppdragTilNets );
}

if( $efakturaOppdragTilNets ) {
	$efakturaForsendelseTilNets
		= $leiebase->netsLagEfakturaForsendelse( $efakturaOppdragTilNets );
}


$leiebase->leverEpost();

$leiebase->mysqli->saveToDb(array(
	'update'	=> true,
	'table'		=> "valg",
	'where'		=> "innstilling = 'cronsuksess'",
	'fields'	=> array(
		'verdi'	=>	time()
	)
));
$leiebase->hentValg();

?>