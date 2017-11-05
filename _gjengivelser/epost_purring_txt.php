<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

/*********************************************/
//	$kontraktperson
//	$leieforholdnr
//	$leieforholdbeskrivelse
//	$kravsett (array av objekter):
//		->id
//		->tekst
//		->beløp
//		->forfall
//		->utestående
//	$purregebyr
//	$purretotal
//	$bankkonto
//	$ocr
//	$kid
//	$fastKid
//	$sisteInnbetaling (objekt):
//		->dato
//		->betaler
//		->beløp
//	$eposttekst


/*********************************************/
/*********************************************/

?>
<div><img src="<?php echo "{$leiebase->http_host}/bilder/fakturalogo_web_450_75.png";?>"></div>
Dette er en betalingspåminnelse for <?php echo $kontraktperson;?> sitt leieforhold nr. <?php echo $leieforholdnr?>, i <?php echo $leieforholdbeskrivelse;?>.
Følgende har forfalt, men er ikke registrert betalt:

<?foreach($kravsett as $krav):?>

	<?php echo $krav->tekst;?>
	Forfallsdato: <?php echo $krav->forfall;?>
	Utestående:<?php echo $krav->utestående;?>

<?endforeach;?>

<?if($purregebyr):?>
	Et purregebyr på <?php echo $purregebyr;?> kommer i tillegg som følge av denne betalingspåminnelsen.
<?endif;?>
	
Du bør snarest betale forfalt saldo, <?php echo $purretotal;?>

Betaling kan skje til konto <?php echo $bankkonto;?>

<?if($ocr):?>
	<?if($kid):?>Bruk KID <?php echo $kid;?> for å betale denne giroen spesifikt, eller KID <?php echo $fastKid;?> som kan brukes fast for alle betalinger til dette leieforholdet.
	<?else:?>Bruk KID <?php echo $fastKid;?> som kan brukes fast for alle betalinger til dette leieforholdet.
	<?endif;?>
<?else:?>Merk innbetalinga med leieforhold <?php echo $leieforholdnr;?>.
<?endif;?>


<?if($sisteInnbetaling->beløp):?>
	Siste innbetaling som er registrert til dette leieforholdet var <?php echo $sisteInnbetaling->beløp;?>
	<?if($sisteInnbetaling->betaler):?>
		 fra <?php echo $sisteInnbetaling->betaler;?>
 	<?endif;?>
	 den <?php echo $sisteInnbetaling->dato->format('d.m.Y');?>
<?endif;?>

<?php echo $eposttekst;?>