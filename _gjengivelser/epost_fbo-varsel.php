<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

/*********************************************/
//	$leieforholdnr
//	$leieforholdbeskrivelse
//	$gironr
//	$beløp
//	$forfallsdato
//	$kravsett (array av objekter):
//		->id
//		->tekst
//		->beløp
//		->utestående
//	$eposttekst


/*********************************************/
/*********************************************/

?>
<div><img src="<?php echo "{$this->http_host}/bilder/fakturalogo_web_450_75.png";?>"></div>
<p>Leieforhold nr. <?php echo $leieforholdnr;?>, i <?php echo $leieforholdbeskrivelse;?>.</p>
<p>Regning nr. <?php echo $gironr;?> pålydende kr. <?php echo $beløp;?> vil bli trukket fra din konto via AvtaleGiro den <?php echo $forfallsdato;?>.<br />
<p>Beløpet gjelder:</p>
	<table>
		<tr>
			<td style="padding: 0px 10px;">Hva</td>
			<td style="padding: 0px 10px;">Beløp</td>
		</tr>

		<?foreach($kravsett as $krav):?>
		<tr>
			<td style="padding: 0px 10px;"><?php echo $krav->tekst;?></td>
			<td style="padding: 0px 10px; text-align: right; font-weight: bold;"><?php echo $krav->utestående;?></td>
		</tr>
		<?endforeach;?>
	</table>
	
<div><?php echo $eposttekst;?></div>