<?php
/**********************************************
universala rajtigisto interfaco
de Kay-Egil Hauan
Denne versjon 2015-10-05
**********************************************/

require_once("Rajtigisto.klaso.php");

session_name(LEIEBASEN_COOKIE_NAME);
session_start();

class RajtigistoTester extends rajtigisto {


public $konekto = false;


public function __construct() {
}


public function akiruId() {
	return 105;
}


public function akiruNomo() {
	return 'Tester / programmerer';
}


public function akiruRetpostadreso() {
	return 'kayegil.hauan@svartlamon.org';
}


public function akiruUzantoNomo() {
	return 'tester';
}


// Aldonu Uzanto
/****************************************/
//	$agordoj (array):
//		id (int) identiganta entjero por la uzanto
//		uzanto (str) uzantonomo
//		nomo (str) la uzanto plena nomoj
//		retpostadreso (str) la uzanto retpoŝto
//		pasvorto: la uzanto pasvorto
//	--------------------------------------
//	return: (bool) indiko de sukceso
public function aldonuUzanto($agordoj = array()) {
}


public function cuEnsalutinta() {
	return true;
}


public function cuHavasPermeson($agordoj) {
	return false;
}


public function cuHavasRolon() {
	return false;
}


public function cuLaPasvortoEstasValida($pasvorto) {
	if (strlen($pasvorto) >= 8) {
		return true;
	}
	else {
		return false;
	}
}


public function cuLaRetpostadresoEstasDisponebla($retpostadreso, $uzanto = "") {
	return false;
}


public function cuLaUzantoEkzistas($uzanto) {
	return false;
}


public function cuLaUzantonomoEstasDisponebla($uzantoNomo, $uzanto = "") {
	return false;
}


public function donuPermeson() {
	return false;
}


public function donuRolon() {
	return false;
}


public function elsalutu() {
	return false;
}


public function ensalutu() {
	return false;
}


public function postuluIdentigon($agordoj = array()) {
	$spuro = LEIEBASEN_INSTALL_URI . $_SERVER['PHP_SELF'] . ($_SERVER['QUERY_STRING'] != "" ? "?{$_SERVER['QUERY_STRING']}" : "");
	if(!$this->cuEnsalutinta()) {
		header("Location: http://boligstiftelsen.svartlamon.org/egeninnsats/uzantoj/ensalutu?url=" . rawurlencode($spuro));
	}
}


public function revokuPermeson() {
	return false;
}


public function revokuRolon() {
	return false;
}


public function sanguNomo($uzanto, $nomo) {
	return false;
}


public function sanguPasvorto($uzanto, $pasvorto) {
	return false;
}


public function sanguRetpostadreso($uzanto, $retpoŝtadreso) {
	return false;
}


public function sanguUzantoNomo($uzanto, $uzantoNomo) {
	return false;
}


public function trovuNomo($uzanto) {
	return false;
}


public function trovuRetpostadreso($uzanto) {
	return false;
}


public function trovuUzantoNomo($uzanto) {
	return "Ikke tilgjengelig via RajtigistoTester";
}


}

?>