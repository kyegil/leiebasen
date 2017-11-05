<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

function __construct() {
	parent::__construct();
	$this->ext_bibliotek = 'ext-3.4.0';

	$this->hoveddata =	array();
	$this->tittel = "Adresseutskrift";
//	$this->mal = "_utskrift.php";
}

function skript() {
}

function utskrift() {
}

function design() {
}

function lagPDF( $data ) {
	switch($data) {
		case "konvolutter":
			$leieforholdsett = explode(",", $_GET['leieforhold']);

			$pdf = new FPDF('L', 'mm', array(114, 229));
			$pdf->SetAutoPageBreak(false);
		
			foreach($leieforholdsett AS $leieforholdnr) {
				$leieforhold = $this->hent( 'Leieforhold', $leieforholdnr );
				$adresse = ($leieforhold->hent('navn') . "\n" . $leieforhold->hent('adressefelt'));
		
				$adresse = utf8_decode($adresse);
		
				$pdf->AddPage();
				$pdf->SetFont('Arial','',9);
				$pdf->setY($this->valg['konvolutt_marg_topp']);
				$pdf->setX($this->valg['konvolutt_marg_venstre']);
				$pdf->MultiCell(75, 3.5, $adresse, false, 'L');
			}

			$pdf->Output("konvoluttadresser.pdf", 'I');
			break;
		default:
			break;
	}
}

}
?>