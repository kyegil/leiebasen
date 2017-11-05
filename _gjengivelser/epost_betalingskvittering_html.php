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
<div><img src="<?php echo "{$leiebase->http_host}/bilder/fakturalogo_web_450_75.png";?>" alt="<?php echo 'Svartlamoen boligstiftelse';?>"></div>
<p style="font-weight: bold;">Følgende innbetaling er registrert på ditt leieforhold</p>
<table style="padding: 10px; border: 1px solid grey;">
	<tr>
		<td>
			<table>
				<tr>
					<td style="padding: 0px 10px;">Leieforhold:</td>
					<td style="padding: 0px 10px;"><?php echo $leieforholdnr;?> - <?php echo $leieforholdbeskrivelse;?></td>
				</tr>
				<tr>
					<td style="padding: 0px 10px;">Dato:</td>
					<td style="padding: 0px 10px; font-weight: bold;"><?php echo $betalingsdato;?></td>
				</tr>
				<tr>
					<td style="padding: 0px 10px;">Beløp:</td>
					<td style="padding: 0px 10px; font-weight: bold;"><?php echo $beløp;?></td>
				</tr>
				<tr>
					<td style="padding: 0px 10px;">Transaksjonsmåte:</td>
					<td style="padding: 0px 10px;"><?php echo $transaksjonsmåte;?></td>
				</tr>
				<tr>
					<td style="padding: 0px 10px;">Innbetalt av:</td>
					<td style="padding: 0px 10px;"><?php echo $betaler;?></td>
				</tr>
				<tr>
					<td style="padding: 0px 10px;">Referanse:</td>
					<td style="padding: 0px 10px;"><?php echo $referanse;?></td>
				</tr>
				<?php if($kid):?>
					<tr>
						<td style="padding: 0px 10px;">Anvendt KID:</td>
						<td style="padding: 0px 10px;"><?php echo $kid;?></td>
					</tr>
				<?php endif;?>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<p>Utlikning:</p>
			<table>
				<tr>
					<th style="padding: 0px 10px; text-align: right;">Beløp</th>
					<th style="padding: 0px 10px; text-align: left;">Utliknet mot</th>
					<th style="padding: 0px 10px; text-align: right;">Oppr. beløp</th>
					<th style="padding: 0px 10px; text-align: right;">Utestående</th>
				</tr>
				
				<?php foreach($delbeløp as $del):?>
				<tr>
					<td style="padding: 0px 10px; text-align: right;"><?php echo $del->beløp;?></td>
					<td style="padding: 0px 10px; text-align: left;"><?php echo $del->utlikningstekst;?></td>
					<td style="padding: 0px 10px; text-align: right;"><?php echo $del->opprinneligBeløp;?></td>
					<td style="padding: 0px 10px; text-align: right;"><?php echo $del->utestående;?></td>
				</tr>
				<?php endforeach;?>
			</table>
		</td>
	</tr>
</table>
<div><?php echo $bunntekst;?></div>