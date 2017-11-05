<?php
class Spuro extends AppModel {


var $mysql_host 	= DB_SERVER;
var $db 			= DB_NAME;
var $mysql_username	= DB_USER;
var $mysql_pw		= DB_PASSWORD;
var $mysql_table	= RETURI_LOG_TABLE;
var $default_uri	= RETURI_LOG_DEFAULT_URI;
var $cookie			= RETURI_LOG_COOKIE;
var $expiry			= RETURI_LOG_EXPIRY;
var $session = "";



function deteminuAdreson() {
	return $Router::url( $this->here, true );
//	return $_SERVER['REQUEST_URI'];
}


function determine_uri() {
	$result = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://" . $_SERVER['HTTP_HOST'] . (($_SERVER["SERVER_PORT"] != "80") ? ":{$_SERVER['SERVER_PORT']}" : "") . $_SERVER['REQUEST_URI'];
	return $result;
}


function determinuSesio() {
	$this->sesio = $this->Session->read();
}


function determine_session() {
	if(isset($_COOKIE[$this->cookie])) {
		$this->session = $_COOKIE[$this->cookie];
	}
	else {
		$this->session = $_SERVER['REMOTE_ADDR'] . " " . $_SERVER['HTTP_USER_AGENT'];
	}
}


function metu($uri = "", $sesio = "") {
	if(!$uri) $uri = $this->determine_uri();
	if(!$sesio) $sesio = $this->sesio;
	
	if($uri == $this->default_uri) {
		$this->reset();
	}
	else {
		$sql = "DELETE `{$this->mysql_table}`.*
			FROM `{$this->mysql_table}`
			INNER JOIN
			(
				SELECT MIN( id ) AS id 
				FROM `{$this->mysql_table}`
				WHERE uri = '" . mysql_real_escape_string($uri) . "'
				AND `sesio` = '" . mysql_real_escape_string($sesio) . "'
			) AS first
			ON `{$this->mysql_table}`.id >= first.id";
		mysql_query($sql);
	}
	
	$sql = "INSERT INTO `{$this->mysql_table}` SET `sesio` = '" . mysql_real_escape_string($sesio) . "', `uri` = '" . mysql_real_escape_string($uri) . "'";
	return mysql_query($sql);
}


function set($uri = "", $session = "") {
	if(!$uri) $uri = $this->determine_uri();
	if(!$session) $session = $this->session;
	
	$this->connect();

	if($uri == $this->default_uri) {
		$this->reset();
	}
	else {
		$sql = "DELETE `{$this->mysql_table}`.*
			FROM `{$this->mysql_table}`
			INNER JOIN
			(
				SELECT MIN( id ) AS id 
				FROM `{$this->mysql_table}`
				WHERE uri = '" . mysql_real_escape_string($uri) . "'
				AND `session` = '" . mysql_real_escape_string($session) . "'
			) AS first
			ON `{$this->mysql_table}`.id >= first.id";
		mysql_query($sql);
	}
	
	$sql = "INSERT INTO `{$this->mysql_table}` SET `session` = '" . mysql_real_escape_string($session) . "', `uri` = '" . mysql_real_escape_string($uri) . "'";
	return mysql_query($sql);
}


function reset($session = "") {
	if(!$session) $session = $this->session;
	
	$this->connect();
	$sql = "DELETE FROM `{$this->mysql_table}` WHERE `session` = '" . mysql_real_escape_string($session) . "'";
	return mysql_query($sql);
}


function clear() {
	$this->connect();
	$sql = "DELETE FROM `{$this->mysql_table}` WHERE `time` < DATE_SUB(NOW(), INTERVAL {$this->expiry})";
	return mysql_query($sql);
}


function akiru($saltu = 0, $adreso = "", $sesio = "") {
	if(!$adreso) $adreso = $this->determine_uri();
	if(!$sesio) $sesio = $this->sesio;

	
	$sql = "SELECT `uri`
		FROM `{$this->mysql_table}`
		WHERE uri != '" . mysql_real_escape_string($uri) . "'
		AND `session` = '" . mysql_real_escape_string($session) . "'
		ORDER BY id DESC
		LIMIT " . (int)$skip . ",1
		";
	$row = mysql_fetch_assoc(mysql_query($sql));
	if($row['uri']) return $row['uri'];
	else return $this->default_uri;
}


function get($skip = 0, $uri = "", $session = "") {
	if(!$uri) $uri = $this->determine_uri();
	if(!$session) $session = $this->session;
	
	$this->connect();
	$sql = "SELECT `uri`
		FROM `{$this->mysql_table}`
		WHERE uri != '" . mysql_real_escape_string($uri) . "'
		AND `session` = '" . mysql_real_escape_string($session) . "'
		ORDER BY id DESC
		LIMIT " . (int)$skip . ",1
		";
	$row = mysql_fetch_assoc(mysql_query($sql));
	if($row['uri']) return $row['uri'];
	else return $this->default_uri;
}



}
?>