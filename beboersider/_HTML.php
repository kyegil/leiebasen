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
	<?$this->skrivHeader("Beboersider - " . $this->valg['utleier']);?>
</head>

<body style="background-color: #eeeeee; background-image: url(../bilder/brodals.jpg);">
<table style="background-color: transparent; width: 912px; text-align: left; margin-left: auto; margin-right: auto;" border="0" cellpadding="0" cellspacing="0">
	<tbody>
		<tr>
			<td style="width: 10%;">
			</td>
			<td style="text-align: left;">

				<table width="900" border="0" cellpadding="0" cellspacing="0"
				style="margin-top: 1px; background-color: #228B22; border-bottom: 1px solid black">
				<tr style="height:47px">
				<td width="20px">&nbsp;</td>
				<td width="500" valign="bottom" style="text-align:left">
					<a href="index.php">
						<img style="border: 0px solid ; height: 25px;" alt="BEBOERSIDER" src="../bilder/beboersider.png" hspace="0" vspace="0" />
					</a>
				</td>
				<td align="right" valign="bottom" style="color:white;font-size:10px;font-weight:bold">Innlogget som <?=$this->navn($this->bruker['id']);?></td>
				<td align="right" valign="bottom" style="color: white; font-size: 10px; font-weight: bold">
					<a href="../index.php?oppdrag=avslutt">
						<img style="border: 0px solid ; width: 40px; height: 40px;" alt="Avslutt" title="Avslutt" src="../bilder/avslutt.png" hspace="0" vspace="2" />
					</a>
				</td>
				<td width="20px">&nbsp;</td>
				</tr>
				</table>
				
				<table style="height: 100%; text-align: left; margin-left: auto; margin-right: auto;" border="0" cellpadding="2" cellspacing="0">
				<tbody>
					<tr>
						<td style="text-align: left; width: 600px; background-color: #eeeeee;">
						<div id="menylinje"></div>
						<noscript>Beboersidene krever at du slår på JavaScript i nettleseren din!</noscript>
						<?$this->design();?>
						</td>
					</tr>
				</tbody>
				</table>
			</td>
			<td style="width: 10%;">
			</td>
		</tr>
	</tbody>
</table>
</body>
</html>
