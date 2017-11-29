<?php
/**********************************************
universala rajtigisto interfaco
de Kay-Egil Hauan
**********************************************/

require_once("Rajtigisto.klaso.php");

require_once(__DIR__ . "/../../../Cake/app/" . "Config/database.php");
// Filbanen må oppgis relativt til mappa denne fila er i ( __DIR__ )

session_name(LEIEBASEN_COOKIE_NAME);
session_start();

class RajtigistoCakephp2 extends Rajtigisto {


public $konekto = false;


public function __construct() {
	$db = new DATABASE_CONFIG;
	$this->konekto = new Mysqli(
		$db->default['host'],
		$db->default['login'],
		$db->default['password'],
		$db->default['database']
	);
	
	if (mysqli_connect_error()) {
		throw new Exception($this->konekto->connect_error);
	}

	$this->konekto->set_charset( "utf8" );
}


public function akiruId() {
	return $_SESSION['Auth']['User']['id'];
}


public function akiruNomo() {
	return $_SESSION['Auth']['User']['nomo'];
}


public function akiruRetpostadreso() {
	return $_SESSION['Auth']['User']['retpoŝto'];
}


public function akiruUzantoNomo() {
	return $_SESSION['Auth']['User']['uzanto'];
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
	settype($agordoj['id'], 'int');

	if($agordoj['pasvorto'] and !$this->cuLaPasvortoEstasValida($agordoj['pasvorto'])) {
		return false;
	}

	if($agordoj['id'] and $this->cuLaUzantoEkzistas($agordoj['id'])) {
		if($agordoj['uzanto'])	$this->sanguUzantoNomo($agordoj['id'], $agordoj['uzanto']);
		if($agordoj['nomo']) $this->sanguNomo($agordoj['id'], $agordoj['nomo']);
		if($agordoj['retpostadreso']) $this->sanguRetpostadreso($agordoj['id'], $agordoj['retpostadreso']);
		$id = $agordoj['id'];
	}
	else {
		if(!$this->konekto) {
			return false;
		}

		$aserto = "INSERT INTO uzantoj SET\n";
		$aserto .=	($agordoj['id']
					? ("id = '{$agordoj['id']}',\n")
					: ""
		);
		$aserto .=	($agordoj['uzanto']
					? ("uzanto = '" . $this->konekto->real_escape_string($agordoj['uzanto']) . "',\n")
					: ""
		);
		$aserto .=	($agordoj['nomo']
					? ("nomo = '" . $this->konekto->real_escape_string($agordoj['nomo']) . "',\n")
					: ""
		);
		$aserto .=	($agordoj['retpostadreso']
					? ("retpoŝto = '" . $this->konekto->real_escape_string($agordoj['retpostadreso']) . "',\n")
					: ""
		);
		$aserto .=	($agordoj['pasvorto']
					? ("pasvorto = '" . $this->konekto->real_escape_string($agordoj['pasvorto']) . "',\n")
					: ""
		);
		$aserto .=	"tempo_de_kreo = NOW(),\n";
		$aserto .=	"tempozono = 'Europe/Oslo'\n";
//		die($aserto);
		$rezulto = $this->konekto->query($aserto);

		if($rezulto) {
			$id = $this->konekto->insert_id;
		}
		else {
			return false;
		}
	}
	
	if($agordoj['pasvorto']) {
		$this->sanguPasvorto($id, $agordoj['pasvorto']);
	}

	return $id;
}


public function cuEnsalutinta() {
	if(isset($_SESSION['Auth']['User']['id']) and $_SESSION['Config']['time'] > time()) {
		$_SESSION['Config']['time'] = time() + LEIEBASEN_SESSION_TIMEOUT;
		return true;
	}
	else {
		return false;
	}
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
	$aserto = $this->konekto->prepare("SELECT id FROM uzantoj WHERE retpoŝto = ? AND id != ?");
	$aserto->bind_param("si", $retpostadreso, $uzanto);
	$aserto->execute();
	$aserto->store_result();
	$rezulto = !$aserto->num_rows;
	$aserto->close();
	return $rezulto;
}


public function cuLaUzantoEkzistas($uzanto) {
	$aserto = $this->konekto->prepare("SELECT id FROM uzantoj WHERE id = ?");
	$aserto->bind_param("i", $uzanto);
	$aserto->execute();
	$aserto->store_result();
	$rezulto = (bool)$aserto->num_rows;
	$aserto->close();
	return $rezulto;
}


public function cuLaUzantonomoEstasDisponebla($uzantoNomo, $uzanto = "") {
	$aserto = $this->konekto->prepare("SELECT id FROM uzantoj WHERE uzanto = ? AND id != ?");
	$aserto->bind_param("si", $uzantoNomo, $uzanto);
	$aserto->execute();
	$aserto->store_result();
	$rezulto = !$aserto->num_rows;
	$aserto->close();
	return $rezulto;
}


public function donuPermeson() {
	return false;
}


public function donuRolon() {
	return false;
}


public function elsalutu() {
	unset( $_COOKIE[LEIEBASEN_COOKIE_NAME] );
	return setcookie(LEIEBASEN_COOKIE_NAME, '', time() - 3600);;
}


public function ensalutu() {
	return false;
}


public function postuluIdentigon($agordoj = array()) {
	$spuro = LEIEBASEN_INSTALL_URI . $_SERVER['PHP_SELF'] . ($_SERVER['QUERY_STRING'] != "" ? "?{$_SERVER['QUERY_STRING']}" : "");
	if(!$this->cuEnsalutinta()) {
		header("Location: https://boligstiftelsen.svartlamon.org/egeninnsats/uzantoj/ensalutu?url=" . rawurlencode($spuro));
	}
}


public function revokuPermeson() {
	return false;
}


public function revokuRolon() {
	return false;
}


public function sanguNomo($uzanto, $nomo) {
	$aserto = $this->konekto->prepare("UPDATE uzantoj SET nomo = ? WHERE id = ?");
	$aserto->bind_param("si", $nomo, $uzanto);
	$rezulto = $aserto->execute();
	$aserto->close();
	return $rezulto;
}


public function sanguPasvorto($uzanto, $pasvorto) {
	$aserto = $this->konekto->prepare("UPDATE uzantoj SET pasvorto = ? WHERE id = ?");
	$aserto->bind_param("si", md5($pasvorto), $uzanto);
	$rezulto = $aserto->execute();
	$aserto->close();
	return $rezulto;
}


public function sanguRetpostadreso($uzanto, $retpoŝtadreso) {
	$aserto = $this->konekto->prepare("UPDATE uzantoj SET retpoŝto = ? WHERE id = ?");
	$aserto->bind_param("si", $retpoŝtadreso, $uzanto);
	$rezulto = $aserto->execute();
	$aserto->close();
	return $rezulto;
}


public function sanguUzantoNomo($uzanto, $uzantoNomo) {
	$aserto = $this->konekto->prepare("UPDATE uzantoj SET uzanto = ? WHERE id = ?");
	$aserto->bind_param("si", $uzantoNomo, $uzanto);
	$rezulto = $aserto->execute();
	$aserto->close();
	return $rezulto;
}


public function trovuNomo($uzanto) {
	$aserto = $this->konekto->prepare("SELECT nomo FROM uzantoj WHERE id = ?");
	$aserto->bind_param("i", $uzanto);
	$aserto->execute();
	$aserto->bind_result($rezulto);
	$aserto->fetch();
	$aserto->close;

	return $rezulto;
}


public function trovuRetpostadreso($uzanto) {
	$aserto = $this->konekto->prepare("SELECT retpoŝto FROM uzantoj WHERE id = ?");
	$aserto->bind_param("i", $uzanto);
	$aserto->execute();
	$aserto->bind_result($rezulto);
	$aserto->fetch();
	$aserto->close;

	return $rezulto;
}


public function trovuUzantoNomo($uzanto) {
	$aserto = $this->konekto->prepare("SELECT uzanto FROM uzantoj WHERE id = ?");
	$aserto->bind_param("i", $uzanto);
	$aserto->execute();
	$aserto->bind_result($rezulto);
	$aserto->fetch();
	$aserto->close;

	return $rezulto;
}


}

?>