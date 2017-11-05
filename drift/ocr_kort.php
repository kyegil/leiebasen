<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'leiebasen';
public $ext_bibliotek = 'ext-4.2.1.883';


function __construct() {
	parent::__construct();

	if(!$id = (int)$_GET['id']) header("Location: index.php?oppslag=ocr_liste");

	$this->tittel = "OCR innbetalingsoppdrag";

	$tp = $this->mysqli->table_prefix;
	$this->hoveddata = $this->mysqli->arrayData(array(
		'source'	=> "{$tp}ocr_filer AS ocr_filer",
		'fields'	=> "ocr_filer.*",
		'where'		=> "ocr_filer.filID = '$id'"
	))->data[0];
}


function skript() {
	if( isset( $_GET['returi'] ) && $_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	
	$forsendelse = new NetsForsendelse( $this->hoveddata->OCR );
	
	if( !$forsendelse->valider() ){
		$html = "Klarte ikke lese fila pga. ukjent feil";
	}
	else {
		$html = addslashes("<table><tr><td><h1>OCR forsendelse nr. {$forsendelse->forsendelsesnummer} den {$forsendelse->dato->format('d.m.Y')}</h1></td>"
		.	"<td>Registrert av {$this->hoveddata->registrerer} den " . date('d.m.Y H:i:s', strtotime( $this->hoveddata->registrert )) . "<br />"
		.	"Antall oppdrag i forsendelsen: " . count($forsendelse->oppdrag)) . "</td></tr></table";
	}

?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	var indrepanel = Ext.create('Ext.panel.Panel', {
		autoLoad: 'index.php?oppslag=<?=$_GET["oppslag"]?>&oppdrag=hentdata&id=<?=$_GET["id"]?>',
        autoScroll: true,
        bodyStyle: 'padding: 5px;',
		title: '',
		frame: false,
		height: 400,
		plain: false
	});

	var panel = Ext.create('Ext.panel.Panel', {
		renderTo: 'panel',
		items: [
			{
				xtype: 'displayfield',
				value: '<?=$html?>'
			},
			indrepanel
		],
        autoScroll: false,
        bodyStyle: 'padding: 5px;',
		title: '',
		frame: true,
		height: 500,
		plain: false,
		width: 900,
		buttons: [{
			handler: function() {
				window.location = '<?=$this->returi->get();?>';				
			},
			text: 'Tilbake'
		}, {
			handler: function() {
				window.location = "index.php?oppslag=ocr_fil&id=<?=(int)$_GET['id']?>";
			},
			text: 'Last ned som OCR-fil'
		}]
	});


});
<?
}

function design() {
?>
<div id="panel"></div>
<?
}

function hentData($data = "") {
	$tp = $this->mysqli->table_prefix;

	switch ($data) {

	default:
		$kundeenhetID = $this->valg['nets_kundeenhetID'];
		$avtaleid = $this->valg['nets_avtaleID_ocr'];
	
		$forsendelse = new NetsForsendelse( $this->hoveddata->OCR );
		
		if($forsendelse->datamottaker != $kundeenhetID ) {
			echo "Forsendelsen har feil kundeenhet-id nr. {$forsendelse->datamottaker}";							
		}
		
		else {
			foreach($forsendelse->oppdrag as $oppdrag) {
				if( $oppdrag->tjeneste != 9 ) {
					echo "<div>Oppdrag {$oppdrag->oppdragsnr} tilhører tjeneste {$oppdrag->tjeneste}";
					if( $oppdrag->tjeneste == 21) {		
						echo " (AvtaleGiro)";
					}
					if( $oppdrag->tjeneste == 42) {		
						echo " (eFaktura)";
					}
					echo "</div>";
				}
				else if( $oppdrag->avtaleId != $avtaleid ) {
					echo	"<div>Forsendelsen har feil avtale-id: {$oppdrag->avtaleId}</div>";				
				}
				else {
					echo	"Oppdrag nr. {$oppdrag->oppdragsnr} inneholder ";
					echo	"{$oppdrag->antallTransaksjoner} ocr-transaksjon" . ($oppdrag->antallTransaksjoner > 1 ? "er": "") . " i Nets-avtale {$oppdrag->avtaleId} ({$this->kr($oppdrag->sumTransaksjoner)}):<br /><br />";
					
					echo	"<table style=\"vertical-align:top;\">";

					foreach($oppdrag->transaksjoner as $transaksjon) {
						$innbetaling = $this->mysqli->arrayData(array(
							'source'	=> "{$tp}innbetalinger
											INNER JOIN OCRdetaljer ON OCRdetaljer.id = innbetalinger.OCRtransaksjon",
							'fields'	=> "innbetalinger.innbetaling AS id",
							'class'		=> "Innbetaling",
							'distinct'	=> true,
							'where'		=> "OCRdetaljer.filID = '{$this->hoveddata->filID}'
											AND OCRdetaljer.transaksjonsnummer = '{$transaksjon->transaksjonsnr}'"
						))->data;
						$innbetaling = reset($innbetaling);
			
						$betalingsmåte = '';
						if ($transaksjon->transaksjonstype == 10 ) {
							$betalingsmåte = 'Giro&nbsp;belastet&nbsp;konto';
						}
						if ($transaksjon->transaksjonstype == 11 ) {
							$betalingsmåte = 'Faste&nbsp;Oppdrag';
						}
						if ($transaksjon->transaksjonstype == 12 ) {
							$betalingsmåte = 'Direkte&nbsp;Remittering';
						}
						if ($transaksjon->transaksjonstype == 13 ) {
							$betalingsmåte = 'BTG (Bedrifts&nbsp;Terminal&nbsp;Giro)';
						}
						if ($transaksjon->transaksjonstype == 14 ) {
							$betalingsmåte = 'SkrankeGiro';
						}
						if ($transaksjon->transaksjonstype == 15 ) {
							$betalingsmåte = 'AvtaleGiro';
						}
						if ($transaksjon->transaksjonstype == 16 ) {
							$betalingsmåte = 'TeleGiro';
						}
						if ($transaksjon->transaksjonstype == 17 ) {
							$betalingsmåte = 'Giro&nbsp;betalt&nbsp;kontant';
						}
						if ($transaksjon->transaksjonstype == 18 ) {
							$betalingsmåte = 'Betalingsterminal/nettbetaling reversering&nbsp;m/KID';
						}
						if ($transaksjon->transaksjonstype == 19 ) {
							$betalingsmåte = 'Betalingsterminal/nettbetaling&nbsp;m/KID';
						}
						if ($transaksjon->transaksjonstype == 20 ) {
							$betalingsmåte = 'Betalingsterminal/nettbetaling reversering&nbsp;m/fritekst';
						}
						if ($transaksjon->transaksjonstype == 21 ) {
							$betalingsmåte = 'Betalingsterminal/nettbetaling m/fritekst';
						}


						echo	"<tr style=\"vertical-align:top; padding: 5;\">";

						echo	"<td width=170>";
						echo	"<b>Transaksjon nr. {$transaksjon->transaksjonsnr}</b><br />";
						echo	"Oppgjørsdato: {$transaksjon->oppgjørsdato->format('d.m.Y')}<br />";
						echo	"Delavregningsnummer: {$transaksjon->delavregningsnr}<br />";
						echo	"Løpenummer: {$transaksjon->løpenr}<br />";
						echo	"Beløp: {$this->kr($transaksjon->beløp)}<br />";
						echo	"Betalingsmåte: {$betalingsmåte}<br />";
						echo	"KID: <b title=\"{$this->tolkKid($transaksjon->kid)}\">{$transaksjon->kid}</b><br />";
						echo	($transaksjon->debetKonto ? "Debetkonto: {$transaksjon->debetKonto}<br />" : "");
						echo	($transaksjon->fritekstmelding ? "Fritekst: {$transaksjon->fritekstmelding}<br />" : "");
						echo	($transaksjon->blankettnummer ? "Blankettnummer: {$transaksjon->blankettnummer}<br />" : "");
						echo	"Bankens arkivreferanse: {$transaksjon->arkivreferanse}<br />";
						echo	"<br />";
						echo	"</td>";

						echo	"<td width=340>";
						echo	"<br />";
						echo	"<b>Betalingen gjelder ifølge KID {$transaksjon->kid}:</b><br />" . $this->tolkKid($transaksjon->kid) . "<br />";
						echo	"<br />";
						echo	"</td>";

						echo	"<td width=390>";
						echo	"<br />";
						
						$utlikningssum = 0;
						if(!$innbetaling) {
							echo "<div style=\"color:red;\"><strong>Denne transaksjonen har ikke blitt registrert som innbetaling i leiebasen!!</strong></div>";
						}
						else {
							$deler = $innbetaling->hent('delbeløp');
							echo	"<b>Faktisk behandling av betaling:</b>&nbsp;&nbsp;&nbsp;&nbsp;"
							.	"<a "
								.	"title=\"Klikk her for å vise alle detaljene i denne betalingen for seg selv\" "
								.	"href=\"index.php?"
									.	"oppslag=innbetalingskort"
									.	"&id={$innbetaling}"
								.	"\">"
							.	"[Vis innbetalingen]</a><br />"
							.	"<table>";
						
							foreach($deler as $delbeløp) {
								$leieforhold = $delbeløp->leieforhold;
								$krav = $delbeløp->krav;
								echo "<tr><td width=\"280px\">"
								. ($krav ? $krav->hent('tekst') : "<i>ikke utliknet</i>")
								. (
									$leieforhold
									? " <span title=\"{$leieforhold->hent('navn')}\">(Leieforhold&nbsp;{$leieforhold})</span>"
									: ""
								)
								. "</td><td style=\"text-align:right;\">{$this->kr($delbeløp->beløp)}</td></tr>";
								$utlikningssum += $delbeløp->beløp;
							}
							echo	"<tr><td width=\"280px\"><b>Sum</b></td><td><b" . (($utlikningssum != $transaksjon->beløp) ? " style=\"color:red;\" title=\"Det er {$this->kr(abs($utlikningssum - $transaksjon->beløp), false)} avvik i mellom transaksjonsbeløpet\nog summen av registrerte innbetalinger\"" : "") . ">{$this->kr($utlikningssum)}</b>";
						}
									
						echo "</td></tr></table>";
						echo	"</td></tr>";
					}
					echo	"</table>";
				}
			}
		
		}

		break;
	}
}

}
?>