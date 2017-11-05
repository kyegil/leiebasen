<div><img src="<?php echo "{$leiebase->http_host}/bilder/fakturalogo_web_450_75.png";?>" alt="<?php echo $logotekst;?>"></div>
<div>Leieforhold <?php echo $leieforholdnr;?><br /><?php echo $leieforholdbeskrivelse;?></div>
<div>
	<p>Følgende er i ferd med å forfalle til betaling:</p>

	<table>
		<tr>
			<th style="padding: 0 10px;">Forfallsdato</th>
			<th style="padding: 0 10px;">&nbsp;</th>
			<th style="padding: 0 10px;">Opprinnelig beløp</th>
			<th style="padding: 0 10px;">Utestående</th>
			<th style="padding: 0 10px;">KID</th>
		</tr>
	
		<?php foreach( $giroer as $giro ):?>
			<?php foreach( $giro->kravsett as $krav ):?>
			<tr>
				<td style="padding: 0 10px;"><?php echo $krav->forfall;?></td>
				<td style="padding: 0 10px;"><?php echo $krav->tekst;?></td>
				<td style="padding: 0 10px;"><?php echo $krav->beløp;?></td>
				<td style="padding: 0 10px;"><?php echo $krav->utestående;?></td>
				<td style="padding: 0 10px;"><?php echo $krav->kid;?> <?php echo $krav->ag;?></td>
			</tr>
			<?php endforeach;?>
		<tr>
			<td style="padding: 0 10px;">&nbsp;</td>
			<td style="padding: 0 10px;"><a href="<?php echo "{$leiebase->http_host}/beboersider/index.php?oppslag=giro&oppdrag=lagpdf&pdf={$giro->nr}";?>">Last ned giro <?php echo $giro->nr;?> som pdf</a></td>
			<td style="padding: 0 10px;">&nbsp;</td>
			<td style="padding: 0 10px;"><b><?php echo $giro->utestående;?></b></td>
			<td style="padding: 0 10px;">&nbsp;</td>
		</tr>
		<?php endforeach;?>

	</table>
</div>

<p>Å betale: <?php echo $sum;?></p>


<?php if($fbo):?>
	<p><strong>Betales med AvtaleGiro</strong></p>
	<p>Beløpet vil automatisk trekkes fra din konto på forfallsdato med AvtaleGiro, forutsatt at du ikke har overstyrt dette i nettbanken din, og at det er dekning på konto.</p>
	<p>Beløp som <i>ikke</i> trekkes med AvtaleGiro kan betales inn til konto <?php echo $bankkonto;?></p>
<?php else:?>
	<p>Betaling kan skje til konto <?php echo $bankkonto;?></p>
<?php endif;?>

<?php if($ocr):?>
	<p>Bruk KID som oppgitt for hvert krav over.<br />
	Ved samlebetalinger kan du også bruke dette leieforholdets generelle KID-nummer: <b><?php echo $fastKid;?></b></p>
<?php else:?>
	<p>Husk å merke innbetalinga med <b>leieforhold <?php echo $leieforholdnr;?></b></p>
<?php endif;?>

<div><?php echo $bunntekst;?></div>
