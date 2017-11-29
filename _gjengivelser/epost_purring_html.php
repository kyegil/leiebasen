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
<p>Dette er en betalingspåminnelse for <?php echo $kontraktperson;?> sitt leieforhold nr. <?php echo $leieforholdnr?>, i <?php echo $leieforholdbeskrivelse;?>.<br />
Følgende har forfalt, men er ikke registrert betalt:</p>
	<table>
		<tr>
			<td style="padding: 0px 10px;">Forfalt</td>
			<td style="padding: 0px 10px;">&nbsp;</td>
			<td style="padding: 0px 10px;">Opprinnelig beløp</td>
			<td style="padding: 0px 10px;">Utestående</td>
		</tr>

		<?foreach($kravsett as $krav):?>
		<tr>
			<td style="padding: 0px 10px;"><?php echo $krav->forfall;?></td>
			<td style="padding: 0px 10px;"><?php echo $krav->tekst;?></td>
			<td style="padding: 0px 10px; text-align: right;"><?php echo $krav->beløp;?></td>
			<td style="padding: 0px 10px; text-align: right; font-weight: bold;"><?php echo $krav->utestående;?></td>
		</tr>
		<?endforeach;?>
	</table>
	
	<?if($purregebyr):?>
		<p>Et purregebyr på <?php echo $purregebyr;?> kommer i tillegg som følge av denne betalingspåminnelsen.</p>
	<?endif;?>
	
	<p style="font-weight: bold;">Du bør snarest betale forfalt saldo, <?php echo $purretotal;?></p>
	<p>Betaling kan skje til konto <?php echo $bankkonto;?><br />

	<?if($ocr):?>
		<?if($kid):?>Bruk KID <?php echo $kid;?> for å betale denne giroen spesifikt, eller KID <?php echo $fastKid;?> som kan brukes fast for alle betalinger til dette leieforholdet.
		<?else:?>Bruk KID <?php echo $fastKid;?> som kan brukes fast for alle betalinger til dette leieforholdet.
		<?endif;?>
	<?else:?>Merk innbetalinga med leieforhold <?php echo $leieforholdnr;?>.
	<?endif;?>
	</p>

<?if($sisteInnbetaling->beløp):?>
	<p>Siste innbetaling som er registrert til dette leieforholdet var <?php echo $sisteInnbetaling->beløp;?>
	<?if($sisteInnbetaling->betaler):?>
		 fra <?php echo $sisteInnbetaling->betaler;?>
 	<?endif;?>
	 den <?php echo $sisteInnbetaling->dato->format('d.m.Y');?></p>
<?endif;?>

<div><?php echo $eposttekst;?></div>