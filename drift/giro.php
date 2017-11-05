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
}



function skript() {}



function design() {
}



function lagPDF( $giro = null ) {

	if( $giro ) {
		// Gironummeret er oppgitt for en enkelt giro.
		
		$giro = $this->hent('Giro', $giro);
		$giro->nedlastPdf();

	}
	
	else if( isset( $_GET['gironr'] ) ) {

		$giro = $this->hent('Giro', $_GET['gironr']);
		$giro->nedlastPdf();

	}
	
	else {
		// Dette er ikke en enkeltgiro, men siste utskriftsbunke
	$fil = "{$this->filarkiv}/giroer/_utskriftsbunke.pdf";
	
	header('Content-type: application/pdf');
	header('Content-Disposition: inline; filename="giroutskrift ' . date('Y-m-d H:i:s') . '.pdf"');
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: ' . filesize($fil));
	header('Accept-Ranges: bytes');

	@readfile( $fil );
	}
	
}

}
?>