<?php
/**********************************************
universala rajtigisto interfaco
de Kay-Egil Hauan
**********************************************/

require_once("Rajtigisto.klaso.php");

require_once('access_user/access_user_class.php');

class rajtigisto_access_user extends rajtigisto {


public $access_user;


public function __construct() {
	$this->access_user = new Access_user;
	$this->access_user->get_user_info();
}


public function akiruId() {
	return $this->access_user->id;
}


public function akiruNomo() {
	return $this->access_user->user_full_name;
}


public function akiruRetpostadreso() {
	return $this->access_user->user_email;
}


public function akiruUzantoNomo() {
	return $this->access_user->user;
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
		$rezulto = mysql_query(sprintf(
			"
			INSERT INTO %s
			SET id = %d, login = %s, real_name = %s, email = %s, access_level = 2, active = 'y'
			",
			$this->access_user->table_name,
			$this->access_user->ins_string($agordoj['id']),
			$this->access_user->ins_string($agordoj['uzanto']),
			$this->access_user->ins_string($agordoj['nomo']),
			$this->access_user->ins_string($agordoj['retpostadreso'])
		));
	
		if($rezulto) {
			$id = mysql_insert_id();
		}
		else {
			return false;
		}
	}
	
	if($agordoj['pasvorto']) $this->sanguPasvorto($id, $agordoj['pasvorto']);

	return true;
}


public function cuEnsalutinta() {
	return $this->access_user->check_user();
}


public function cuHavasPermeson($agordoj) {
	return false;
}


public function cuHavasRolon() {
	return false;
}


public function cuLaPasvortoEstasValida($pasvorto) {
	return $this->access_user->check_new_password($pasvorto, $pasvorto);
}


public function cuLaRetpostadresoEstasDisponebla($retpostadreso, $uzanto = "") {
	$r = mysql_query(sprintf("SELECT id FROM %s WHERE email = %s AND id != %s",
		$this->access_user->table_name,
		$this->access_user->ins_string($retpostadreso),
		$this->access_user->ins_string($uzanto)
	));
	return !mysql_num_rows($r);
}


public function cuLaUzantoEkzistas($uzanto) {
	$r = mysql_query(sprintf("SELECT id FROM %s WHERE id = %s",
		$this->access_user->table_name,
		$this->access_user->ins_string($uzanto)
	));
	return (bool)mysql_num_rows($r);
}


public function cuLaUzantonomoEstasDisponebla($uzantoNomo, $uzanto = "") {
	$r = mysql_query(sprintf("SELECT id FROM %s WHERE login = %s AND id != %s",
		$this->access_user->table_name,
		$this->access_user->ins_string($uzantoNomo),
		$this->access_user->ins_string($uzanto)
	));
	return !mysql_num_rows($r);
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
	$this->access_user->access_page($_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']);
}


public function revokuPermeson() {
	return false;
}


public function revokuRolon() {
	return false;
}


public function sanguNomo($uzanto, $nomo) {
	mysql_query(sprintf("UPDATE %s SET real_name = %s WHERE id = %d",
		$this->access_user->table_name,
		$this->access_user->ins_string($nomo),
		(int)$uzanto));
	return (bool)mysql_affected_rows();
}


public function sanguPasvorto($uzanto, $pasvorto) {
	if(!$this->cuLaPasvortoEstasValida($pasvorto)) {
		return false;
	}
	mysql_query(sprintf("UPDATE %s SET pw = %s WHERE id = %d",
		$this->access_user->table_name,
		$this->access_user->ins_string(md5($pasvorto)),
		(int)$uzanto));
	return (bool)mysql_affected_rows();
}


public function sanguRetpostadreso($uzanto, $retpoŝtadreso) {
	mysql_query(sprintf("UPDATE %s SET email = %s WHERE id = %d",
		$this->access_user->table_name,
		$this->access_user->ins_string($retpoŝtadreso),
		(int)$uzanto));
	return (bool)mysql_affected_rows();
}


public function sanguUzantoNomo($uzanto, $uzantoNomo) {
	if(!$this->cuLaUzantonomoEstasDisponebla($uzantoNomo, $uzanto)) {
		return false;
	}
	mysql_query(sprintf("UPDATE %s SET login = %s WHERE id = %d",
		$this->access_user->table_name,
		$this->access_user->ins_string($uzantoNomo),
		(int)$uzanto));
	return (bool)mysql_affected_rows();
}


public function trovuNomo($uzanto) {
	$r = mysql_query(sprintf("SELECT real_name FROM %s WHERE id = %s",
		$this->access_user->table_name,
		$this->access_user->ins_string($uzanto)
	));
	return mysql_result($r, 0, "real_name");
}


public function trovuRetpostadreso($uzanto) {
	$r = mysql_query(sprintf("SELECT email FROM %s WHERE id = %s",
		$this->access_user->table_name,
		$this->access_user->ins_string($uzanto)
	));
	return mysql_result($r, 0, "email");
}


public function trovuUzantoNomo($uzanto) {
	$r = mysql_query(sprintf("SELECT login FROM %s WHERE id = %s",
		$this->access_user->table_name,
		$this->access_user->ins_string($uzanto)
	));
	return mysql_result($r, 0, "login");
}


}

?>