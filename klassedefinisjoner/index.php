<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

if(defined('LEGAL')) {
	require_once('MysqliConnection.class.php');

	require_once('Leiebase.class.php');

	require_once('DatabaseObjekt.class.php');

	require_once('NetsForsendelse.class.php');

	require_once('Innbetaling.class.php');
	require_once('Person.class.php');
	require_once('Leieobjekt.class.php');
	require_once('Leieforhold.class.php');
	require_once('Krav.class.php');
	require_once('Giro.class.php');
	require_once('Purring.class.php');

	require_once('fpdf/fpdf.php');

	require_once('Returi.class.php');
}

else {
	throw new Exception("Illegal access to directory");
}

?>