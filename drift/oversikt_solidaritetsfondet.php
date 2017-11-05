<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

function __construct() {
	parent::__construct();
	$this->mal = "_utskrift.php";
}

function skript() {
	if($_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
}

function design() {
}

function utskrift() {
	$tp = $this->mysqli->table_prefix;
	$fra = $this->GET['fra'];
	$til = $this->GET['til'];
	$beløp = 0;
	$kravsett = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}innbetalinger AS innbetalinger INNER JOIN {$tp}krav AS krav ON innbetalinger.krav = krav.id",
		'fields'		=> "MAX(innbetalinger.dato) as betalt, krav.id\n",
		'where'			=> "!utestående\n",
		'having'		=> "betalt >= '{$fra}' AND betalt <= '{$til}'",
		'groupfields'	=> "krav.id",
		'class'			=> "Krav"
	))->data;
	
	foreach( $kravsett as $krav ) {
		$beløp += $krav->hentDel(1);
	}

?>
<h1 style="text-align: center;">Innbetalinger til solidaritetsfondet i tidsrommet <?=date('d.m.Y', strtotime($_GET['fra'])) . " - " . date('d.m.Y', strtotime($_GET['til']))?></h1>
<p style="text-align: center; font-size: large; font-weight: bold;"><?php echo $this->kr($beløp);?></p>
<p style="text-align: center; font-size: medium;">Beløpet overføres til Svartlamon solidaritetsfond: Konto nr. 1254&nbsp;06&nbsp;06674</p>

<table>
<?php foreach( $kravsett as $krav ):?>
	<?php if($krav->hentDel(1) != 0):?>
		<tr style="font-size:0.8em;">
			<td><?php echo $krav->hent('leieforhold')->hent('beskrivelse');?></td>
			<td><?php echo $krav->hent('tekst');?></td>
			<td><?php echo $this->kr($krav->hentDel(1));?></td>
		</tr>
	<?php endif;?>
<?php endforeach;?>
</table>

<script type="text/javascript">
//	window.print();
</script>
<?php
}


function taimotSkjema() {
	echo json_encode($resultat);
}

function hentData($data = "") {
	switch ($data) {
		default:
			$resultat = $this->arrayData($this->hoveddata);
			return json_encode($resultat);
	}
}

}
?>