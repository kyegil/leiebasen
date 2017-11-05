<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

/*********************************************/
//	$leieforholdnr
//	$leieforholdbeskrivelse
//	$betalingsdato
//	$beløp
//	$transaksjonsmåte
//	$betaler
//	$kid
//	$referanse
//	$bunntekst
//	$delbeløp (array av objekter):
//		->id
//		->beløp
//		->utlikningstekst
//		->opprinneligBeløp
//		->utestående


/*********************************************/
/*********************************************/

?>
Følgende innbetaling er registrert på ditt leieforhold
Leieforhold: <?php echo $leieforholdnr;?> - <?php echo $leieforholdbeskrivelse;?>

Dato:				<?php echo $betalingsdato;?>

Beløp:				<?php echo $beløp;?>

Transaksjonsmåte:	<?php echo $transaksjonsmåte;?>

Innbetalt av:		<?php echo $betaler;?>

Referanse:			<?php echo $referanse;?>

<?php if($kid):?>Anvendt KID:		<?php echo $kid;?><?php endif;?>



Utlikning:
<?php foreach($delbeløp as $del):?>
<?php echo $del->beløp;?>: <?php echo $del->utlikningstekst;?><?php if($del->utestående):?> (Utestående <?php echo $del->utestående;?>)<?php endif;?>

<?php endforeach;?>

<?php echo $bunntekst;?>