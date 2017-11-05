Leieforhold <?php echo $leieforholdnr;?>
<?php echo $leieforholdbeskrivelse;?>


Følgende er i ferd med å forfalle til betaling:
<?php foreach( $giroer as $giro ):?>
	<?php foreach( $giro->kravsett as $krav ):?>

	<?php echo $krav->tekst;?>
	
	Forfallsdato: <?php echo $krav->forfall;?>

	Opprinnelig beløp: <?php echo $krav->beløp;?>

	Utestående: <?php echo $krav->utestående;?>

	KID: <?php echo $krav->kid;?>
	
	<?php echo $krav->ag;?>

	<?php endforeach;?>
<?php endforeach;?>

Å betale: <?php echo $sum;?>


<?php if($fbo):?>
Beløpet vil automatisk trekkes fra din konto på forfallsdato med AvtaleGiro, forutsatt at du ikke har overstyrt dette i nettbanken din, og at det er dekning på konto.
Beløp som ikke trekkes med AvtaleGiro kan betales inn til konto <?php echo $bankkonto;?>

<?php else:?>
Betaling kan skje til konto <?php echo $bankkonto;?>

<?php endif;?>

<?php if($ocr):?>
Bruk KID som oppgitt for hvert krav over.
Ved samlebetalinger kan du også bruke dette leieforholdets generelle KID-nummer: <?php echo $fastKid;?>

<?php else:?>
Husk å merke innbetalinga med leieforhold <?php echo $leieforholdnr;?>

<?php endif;?>

<?php echo $bunntekst;?>
