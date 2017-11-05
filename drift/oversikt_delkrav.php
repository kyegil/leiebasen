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
	if(!$id = $this->GET['id']) die("Ugyldig oppslag: ID ikke angitt for kravet");
}

function skript() {
	if($_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
}

function design() {
}

function utskrift() {
	$id = @$_GET['id'];
	$tp = $this->mysqli->table_prefix;
	$fra = $this->GET['fra'];
	$til = $this->GET['til'];
	$beløp = 0;
	
	$delkravtype = @$this->mysqli->arrayData(array(
		'source'	=> "{$tp}delkravtyper as delkravtyper",
		'where'		=> "delkravtyper.id = '{$id}'"
	))->data[0];
	
	if(!$delkravtype) {
		return;
	}
	
	$kravsett = $this->mysqli->arrayData(array(
		'source'		=> "{$tp}innbetalinger AS innbetalinger INNER JOIN {$tp}krav AS krav ON innbetalinger.krav = krav.id",
		'fields'		=> "MAX(innbetalinger.dato) as betalt, krav.id\n",
		'where'			=> "!utestående\n",
		'having'		=> "betalt >= '{$fra}' AND betalt <= '{$til}'",
		'groupfields'	=> "krav.id",
		'orderfields'	=> "betalt, krav.id",
		'class'			=> "Krav"
	))->data;
	
	foreach( $kravsett as $krav ) {
		$beløp += $krav->hentDel($id);
	}

?>
<h1 style="text-align: center;">Innbetalinger til <?php echo $delkravtype->navn;?> i tidsrommet <?=date('d.m.Y', strtotime($_GET['fra'])) . " - " . date('d.m.Y', strtotime($_GET['til']))?></h1>
<p style="text-align: center; font-size: large; font-weight: bold;"><?php echo $this->kr($beløp);?></p>
<p style="text-align: center; font-size: medium;"><?php echo $delkravtype->beskrivelse;?></p>

<table>
<?php foreach( $kravsett as $krav ):?>
	<?php if($krav->hentDel($id) != 0):?>
		<tr style="font-size:0.8em;">
			<td><?php echo $krav->hent('betalt')->format('d.m.Y');?></td>
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