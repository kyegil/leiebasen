<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

define('LEGAL',true);
require_once('config.php');
require_once('klassedefinisjoner/index.php');
require_once('tillegg/index.php');

$mysqliConnection = new MysqliConnection;
$leiebase = new Leiebase;

$autoriserer = new $leiebase->autoriserer;

if (isset($_GET['oppdrag']) && $_GET['oppdrag'] == "avslutt") {
	if( $autoriserer->elsalutu() ) {
		header("Location: ");
	}
}

else {
	header("Location: ");
}
?>