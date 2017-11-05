<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'leiebasen';
public $ext_bibliotek = 'ext-4.2.1.883';


public function __construct() {
	parent::__construct();
}



/*	Pre HTML
Dersom leieforholdet ikke eksisterer vil du bli videresendt til oppslaget leieforhold_liste
******************************************
------------------------------------------
retur (boolsk) Sann for å skrive ut HTML-malen, usann for å stoppe den
*/
public function preHTML() {
	$leieforhold = $this->hent('Leieforhold', (int)@$_GET['id']);
	if( !$leieforhold->hent('id') ) {
		$leieforhold = $this->hent('Leieforhold', $this->leieforhold( (int)@$_GET['id'] ) );
	}
	
	if( !$leieforhold->hent('id') ) {
		header("Location: index.php?oppslag=leieforhold_liste");
		return false;
	}
	
	else{
		$this->tittel = "Leieforhold $leieforhold: " . $leieforhold->hent('navn') . " i " . $leieforhold->hent('leieobjekt')->hent('beskrivelse') . " | Leiebasen";
		return true;
	}
}



public function skript() {
}



public function design() {
?>
<div id="panel"></div>
<?
}



function utskrift() {
	$leieforhold = $this->hent('Leieforhold', (int)@$_GET['id']);
	if( !$leieforhold->hent('id') ) {
		$leieforhold = $this->hent('Leieforhold', $this->leieforhold( (int)@$_GET['id'] ) );
	}

	$transaksjoner = $leieforhold->hent('transaksjoner');
	$resultat = (object)array(
		'success'	=> true,
		'data'		=> array(),
		'totalRows'	=> 0
	);
	$saldo = 0;
	$bakgrunn = "";
	foreach( $transaksjoner as $transaksjon ) {
		$utlikninger = array();
	
		if( $transaksjon instanceof Krav ) {

			// Ikke ta med framtidige krav i oversikten
			if( $transaksjon->hent('dato') <= date_create()) {
				$betalinger		= $transaksjon->hentUtlikninger();
			
				foreach( $betalinger as $betaling ) {
				
					// Betalinger
					if( $betaling->innbetaling->hent('konto') != '0' ) {
						
						// Innbetalinger på krav
						if( $betaling->beløp > 0 ) {
							$utlikninger[] = (object)array(
								'beløp'	=> $betaling->beløp,
								'tekst'	=> "betalt {$betaling->innbetaling->hent('dato')->format('d.m.Y')} ref. {$betaling->innbetaling->hent('ref')}"
							);
						}
						
						// Utbetaling av kreditt
						else {
							$utlikninger[] = (object)array(
								'beløp'	=> -$betaling->beløp,
								'tekst'	=> "tilbakeført {$betaling->innbetaling->hent('dato')->format('d.m.Y')} ref. {$betaling->innbetaling->hent('ref')}"
							);
						}
					}
				
					// Kreditt
					else {
						if( $betaling->beløp > 0 ) {
							$utlikninger[] = (object)array(
								'beløp'	=> $betaling->beløp,
								'tekst'	=> "kreditert {$betaling->innbetaling->hent('dato')->format('d.m.Y')}"
							);
						}
					}
				}
			
				if($transaksjon->hent('utestående') != 0) {
					$utlikninger[] = (object)array(
						'beløp'	=> '',
						'tekst'	=> "<span style=\"color:red;\">Utestående per " . date('d.m.Y') . ": {$this->kr($transaksjon->hent('utestående'))}</span>"
					);
				}

				$saldo -= $transaksjon->hent('beløp');
				$resultat->data[] = (object)array(
					'id'						=> (int)strval($transaksjon),
					'dato'						=> $transaksjon->hent('dato')->format('d.m.Y'),
					'tekst'						=> $transaksjon->hent('tekst'),
					'beløp'						=> $this->kr(-$transaksjon->hent('beløp')),
					'husleie'					=> ($transaksjon->hent('type') == "Husleie") ? $this->kr($transaksjon->hent('beløp')) : null,
					'fellesstrøm'				=> ($transaksjon->hent('type') == "Fellesstrøm") ? $this->kr($transaksjon->hent('beløp')) : null,
					'annet'						=> ($transaksjon->hent('type') != "Husleie" and $transaksjon->hent('type') != "Fellesstrøm") ? $this->kr($transaksjon->hent('beløp')) : null,
					'innbetalt'					=> null,
					'saldo'						=> $saldo,
					'utlikninger'				=> $utlikninger
				);
			}
		}
		else {
			$delbeløp = $transaksjon->innbetaling->hent('delbeløp');
			
			foreach( $delbeløp as $del ) {
				if(
					$del->leieforhold
					and $del->leieforhold->hentId() == $leieforhold->hentId()
					and $del->krav
					and $del->beløp > 0
				) {
					$utlikninger[] = (object)array(
						'beløp'	=> $del->beløp,
						'tekst'	=> "betaling for {$del->krav->hent('tekst')}"
					);
				}
			}
		
			$saldo += $transaksjon->beløp;
			$ocr = $transaksjon->innbetaling->hent('ocr');
			$betaler = $transaksjon->innbetaling->hent('betaler');
			$retning = ($transaksjon->beløp > 0) ? "innbetaling" : "utbetaling";
			
			if ($transaksjon->beløp > 0) {
				$retning = "innbetaling" . ($betaler ? " fra {$betaler}": "");
			}
			else {
				$retning = "utbetaling" . ($betaler ? " til {$betaler}" : "");
			}
			$resultat->data[] = (object)array(
				'id'						=> strval($transaksjon->innbetaling),
				'dato'						=> $transaksjon->innbetaling->hent('dato')->format('d.m.Y'),
				'tekst'						=> ucfirst(($ocr ? "{$ocr->transaksjonsbeskrivelse} " : '') . "{$retning}"),
				'beløp'						=> $this->kr($transaksjon->beløp),
				'husleie'					=> null,
				'fellesstrøm'				=> null,
				'annet'						=> null,
				'innbetalt'					=> $this->kr($transaksjon->beløp),
				'saldo'						=> $saldo,
				'utlikninger'				=> $utlikninger
			);
		}
	
	}

?>
<h1>Kontoforløp leieforhold nr. <?php echo $leieforhold;?></h1>

<p><?php echo $leieforhold->hent('beskrivelse');?></p>
<table width="100%">
<tbody>
	<tr>
		<th>Dato</th>
		<th style="min-width: 40px;"></th>
		<th></th>
		<th class="value">Husleie</th>
		<th class="value">Strøm</th>
		<th class="value">Annet</th>
		<th class="value">Innbetalt</th>
		<th class="value">Saldo</th>
	</tr>
	
	<?php foreach( $resultat->data as $linje ):?>
	<?php $bakgrunn = $bakgrunn ? "" : ' background-color: #E0E0E0;';?>

	<tr style="border-top-style: solid; border-width: thin;<?php echo $bakgrunn;?>">
		<td><?php echo $linje->dato;?></td>
		<td colspan="2"><?php echo $linje->tekst;?></td>
		<td class="value"><?php echo $linje->husleie;?></td>
		<td class="value"><?php echo $linje->fellesstrøm;?></td>
		<td class="value"><?php echo $linje->annet;?></td>
		<td class="value"><?php echo $linje->innbetalt;?></td>
		<td class="bold value" style="<?php echo $linje->saldo < 0 ? "color:red;" : "color:black;";?>"><?php echo $this->kr($linje->saldo);?></td>
	</tr>

		<?php foreach($linje->utlikninger as $utlikning):?>
		<tr style="<?php echo $bakgrunn;?>">
			<td>&nbsp;</td>
			<td class="value" style="font-size: xx-small; font-style:italic;"><?php echo $utlikning->beløp ? $this->kr($utlikning->beløp) : "&nbsp;";?></td>
			<td colspan="6" style="font-size: xx-small; font-style:italic;"><?php echo $utlikning->tekst;?></td>
		</tr>
		<?php endforeach;?>

	<?php endforeach;?>
</tbody>
</table>
<script type="text/javascript">
	window.print();
</script>
<?php

}


public function hentData($data = "") {
	switch ($data) {
		default:
			$resultat = $this->arrayData($this->hoveddata);
			return json_encode($resultat);
	}
}

public function taimotSkjema() {

	echo json_encode($resultat);

}

}
?>