<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

namespace kyegil\Leiebasen\logg;

if(!defined('LEGAL')) {
	throw new Exception("Illegal access to directory");
}
require_once('funksjoner.php');

settype($tillegg, 'array');

$konfig = (object)array(
	'base'	=> 'logg',
	'navn'	=> 'logg',
	'knagger'	=> (object)array(),
	'modeller'	=> (object)array()
);
$tillegg[] = &$konfig;

$konfig->modeller->Oppsett = (object)array(
	'knagger'	=> (object)array(),
	'metoder'	=> array()
);