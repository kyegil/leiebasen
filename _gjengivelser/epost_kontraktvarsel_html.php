<div><img src="<?php echo "{$leiebase->http_host}/bilder/fakturalogo_web_450_75.png";?>" alt="<?php echo $logotekst;?>"></div>
<div>Leieforhold <?php echo $leieforholdnr;?></div>
<div>

<p style="font-weight: bold;">Følgende leieavtale er opprettet:</p>
<table>
	<tr>
		<td style="padding: 0px 10px;">Leieavtale nr.</td>
		<td style="padding: 0px 10px;"><?php echo $kontraktnr;?></td>
	</tr>
	<tr>
		<td style="padding: 0px 10px;">Leietaker(e):</td>
		<td style="padding: 0px 10px; font-weight: bold;"><?php echo $leietakere;?></td>
	</tr>
	<tr>
		<td style="padding: 0px 10px;">Leieobjekt:</td>
		<td style="padding: 0px 10px; font-weight: bold;"><?php echo $leieobjektbeskrivelse;?></td>
	</tr>
	<tr>
		<td style="padding: 0px 10px;">Andel:</td>
		<td style="padding: 0px 10px; font-weight: bold;"><?php echo $andel;?></td>
	</tr>

	<?php if( $leieforholdnr != $kontraktnr ):?>
	<tr>
		<td colspan="2" style="padding: 0px 10px;">Leieavtalen er fornyelse av tidligere avtale.</td>
	</tr>
	<?php endif;?>
		
	<tr>
		<td style="padding: 0px 10px;"><br /></td>
		<td style="padding: 0px 10px;"><br /></td>
	</tr>
	<tr>
		<td style="padding: 0px 10px;">Avtalen gjelder fra:</td>
		<td style="padding: 0px 10px;"><?php echo $fradato;?></td>
	</tr>

	<?php if( $tildato ):?>
	<tr>
		<td style="padding: 0px 10px;">Avtalen utløper uten oppsigelse:</td>
		<td style="padding: 0px 10px;"><?php echo $tildato;?></td>
	</tr>
	<?php endif;?>
		
	<tr>
		<td style="padding: 0px 10px;">Oppsigelsestid innenfor leieperioden: </td>
		<td style="padding: 0px 10px;"><?php echo $oppsigelsestid;?></td>
	</tr>
	<tr>
		<td style="padding: 0px 10px;">Antall årlige betalingsterminer:</td>
		<td style="padding: 0px 10px;"><?php echo $antallTerminer;?></td>
	</tr>
	<tr>
		<td style="padding: 0px 10px;">Terminbeløp: </td>
		<td style="padding: 0px 10px;"><?php echo $terminbeløp;?></td>
	</tr>
</table>

<div>
	<p>Innbetalinger kan gjøres til konto nr. <?php echo $bankkonto;?>.</p>

	<?php if( $fastKid ):?>
	<p>Fast KID-nummer for alle innbetalinger til dette leieforholdet er <?php echo $fastKid;?></p>
	<?php else:?>
	<p>Oppgi leieforhold nr <?php echo $leieforhold;?></p>
	<?php endif;?>
</div>

<div><?php echo $bunntekst;?></div>
