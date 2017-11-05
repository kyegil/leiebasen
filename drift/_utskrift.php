<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/
If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');
?>
<!DOCTYPE html>
<html lang="no">

<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?=$this->tittel;?></title>
	<link rel="stylesheet" type="text/css" href="/<?=$this->ext_bibliotek?>/resources/css/ext-all.css" />
	<link rel="stylesheet" type="text/css" href="/leiebase.css" />
</head>

<body class="dataload">
	<?$this->utskrift();?>
</body>
</html>
