<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

define('LEGAL',true);
require_once('../config.php');
require_once('../klassedefinisjoner/index.php');

if( isset($_GET['oppslag']) && file_exists($_GET['oppslag'].".php")) {
	include_once($_GET['oppslag'].".php");
}
else {
	include_once("forsiden.php");
}

$tillegg = array();
require_once('../tillegg/index.php');

$mysqliConnection = new MysqliConnection;
$leiebase = new oppsett;

if(!$leiebase->adgang($leiebase->område['område'] = $leiebase->katalog(__FILE__))) {
	die("Du er ikke tildelt adgang til dette området.");
}

$leiebase->returi->default_uri = $leiebase->http_host . "/" . $leiebase->katalog($_SERVER['PHP_SELF']) . "/index.php";

if(isset($_GET['oppdrag'])) {
	$leiebase->oppdrag($_GET['oppdrag']);
}
else {
	$leiebase->skrivHTML();
}
?>