<?php
/*********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
*********************************************/

/********************************************/
//	$leiebase

//	$logotekst
//	$avsender
//	$avsenderadresse
//	$avsenderEpost
//	$avsenderOrgNr
//	$avsenderTelefon
//	$avsenderBankkonto
//	$avsenderHjemmeside
//	$mottaker
//	$mottakerAdresse
//	$leieforholdnr
//	$leieforholdbeskrivelse
//	$gironr
//	$kid
//	$dato
//	$forfall
//	$kravsett (array av Krav-objekter):
//		->id
//		->tekst
//		->beløp
//		->utestående
//	$girobeløp
//	$fradrag
//	$utestående

//	$avtalegiro
//	$efaktura

/********************************************/

?>
<div><img src="<?php echo "{$leiebase->http_host}/bilder/fakturalogo_web_450_75.png";?>" alt="<?php echo $logotekst;?>"></div>
<h1>Regning nr. <?php echo $gironr;?></h1>
<div><?php echo $mottaker;?><br><?php echo $mottakeradresse;?></div>
<div>Leieforhold nr. <?php echo $leieforholdnr;?><br>
<?php echo $leieforholdbeskrivelse;?></div>

<div>Dato: <?php echo $dato;?></div>
<div>Forfall: <?php echo $forfall;?></div>
<div>KID: <?php echo $kid;?></div>

<table>
<?php foreach($detaljer as $krav):?>
	<tr>
		<td><?php echo $krav->tekst;?></td>
		<td><?php echo $krav->beløp;?></td>
	</tr>
<?php endforeach;?>
	<tr>
		<td><strong>Sum</strong></td>
		<td><strong><?php echo $girobeløp;?></strong></td>
	</tr>
<?php if($girobeløp - $utestående != 0):?>
	<tr>
		<td>Tidligere innbetalinger til fradrag</td>
		<td><?php echo $fradrag;?></td>
	</tr>
<?php endif;?>
	<tr>
		<td><strong>Å betale:</strong></td>
		<td><strong><?php echo $utestående;?></strong></td>
	</tr>
<?php if($fbo):?>
	<tr>
		<td colspan="2">Beløpet trekkes automatisk med AvtaleGiro</td>
	</tr>
<?php endif;?>
</table>

<table>
	<tr>
		<td><?php if( $efaktura ):?><img src="<?php echo "{$leiebase->http_host}/bilder/eFaktura_print_4000_943.png";?>" alt="eFaktura"><br>Motta regningene rett i nettbanken.<?php echo $efakturareferanse;?><?php endif;?></td>
		<td><?php if( $avtalegiro ):?><img src="<?php echo "{$leiebase->http_host}/bilder/AvtaleGiro_print_4000_764.png";?>" alt="AvtaleGiro"><br>Få regningene betalt automatisk på forfall.<?php endif;?></td>
	</tr>
</table>
<div><?php echo $bunntekst;?></div>

<div><?php echo $avsenderadresse;?></div>
<div><?php echo "Org. nr. {$avsenderOrgNr}";?></div>
<table>
	<tr>
		<td>Telefon:</td>
		<td><?php echo $avsenderTelefon;?></td>
	</tr>
	<tr>
		<td>Bankkonto:</td>
		<td><?php echo $avsenderBankkonto;?></td>
	</tr>
	<tr>
		<td>E-post:</td>
		<td><?php echo $avsenderEpost;?></td>
	</tr>
	<tr>
		<td>Hjemmeside:</td>
		<td><?php echo $avsenderHjemmeside;?></td>
	</tr>
</table>


