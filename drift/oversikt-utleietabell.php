<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Utleietabell';
public $ext_bibliotek = 'ext-4.2.1.883';


function __construct() {
 	parent::__construct();

	if(!$this->fra) {
		$this->fra = date('Y-01-01');
		if(date('m') < 4 ) {
			$this->fra = (date('Y') - 1) . '-07-01';
		}
		else if(date('m') > 9 ) {
			$this->fra = date('Y') . '-07-01';
		}
	}
	if(!$this->til) {
		$this->til = date('Y-12-31');
		if(date('m') < 4 ) {
			$this->til = date('Y-06-30');
		}
		else if(date('m') > 9 ) {
			$this->til = (date('Y') + 1) . '-06-30';
		}
	}
}



function skript() {

	$fra = new DateTime($this->fra);
	$til = new DateTime($this->til);
	
	$forrigeFra = clone $fra;
		$forrigeFra->sub(new DateInterval('P6M'));
	$forrigeTil = clone $forrigeFra;
		$forrigeTil->add(new DateInterval('P1Y'))->sub(new DateInterval('P1D'));
	$nesteFra = clone $fra;
		$nesteFra->add(new DateInterval('P6M'));
	$nesteTil = clone $nesteFra;
		$nesteTil->add(new DateInterval('P1Y'))->sub(new DateInterval('P1D'));

	if( isset( $_GET['returi'] ) && $_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
?>

Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?php echo $this->http_host . "/" . $this->ext_bibliotek . "/examples/ux"?>');

Ext.require([
 	'Ext.data.*',
 	'Ext.form.field.*',
    'Ext.layout.container.Border',
 	'Ext.grid.*',
    'Ext.grid.plugin.BufferedRenderer',
    'Ext.ux.RowExpander'
]);

Ext.onReady(function() {

    Ext.tip.QuickTipManager.init();
	Ext.form.Field.prototype.msgTarget = 'side';
	Ext.Loader.setConfig({enabled:true});
	
<?
	include_once("_menyskript.php");
?>

	var panel = Ext.create('Ext.panel.Panel', {
		title: 'Utleie i tidsrommet <?php echo "{$fra->format('d.m.Y')} – {$til->format('d.m.Y')}";?>',
		autoScroll: true,
		height: 500,
		renderTo: 'panel',
		bodyPadding: 5,
		border: false,

		loader: {
			url: 'index.php?oppslag=oversikt-utleietabell&oppdrag=hentdata&fra=<?php echo $this->fra;?>&til=<?php echo $this->til;?>',
			renderer: 'html',
			autoLoad: true
		},
		
		buttons: [
		{
			scale: 'medium',
			text: '<< 6 mnd. <<',
			handler: function() {
				window.location = 'index.php?oppslag=oversikt-utleietabell&fra=<?php echo $forrigeFra->format('Y-m-d');?>&til=<?php echo $forrigeTil->format('Y-m-d');?>';
			}
		},
		{
			scale: 'medium',
			text: '>> 6 mnd. >>',
			handler: function() {
				window.location = 'index.php?oppslag=oversikt-utleietabell&fra=<?php echo $nesteFra->format('Y-m-d');?>&til=<?php echo $nesteTil->format('Y-m-d');?>';
			}
		},
		{
			scale: 'medium',
			text: 'Skriv ut',
			handler: function() {
				window.open('index.php?oppslag=oversikt-utleietabell&oppdrag=utskrift&fra=<?php echo $this->fra;?>&til=<?php echo $this->til;?>');
			}
		},
		{
			scale: 'medium',
			text: 'Tilbake',
			handler: function() {
				window.location = '<?php echo $this->returi->get();?>';
			}
		}
		]
	});
	
});
<?
}



function design() {
?>
<div id="panel"></div>
<?
}



function hentData($data = "") {

	$tp = $this->mysqli->table_prefix;
	$sort		= @$_GET['sort'];
	$synkende	= @$_GET['dir'] == "DESC" ? true : false;
	$start		= (int)@$_GET['start'];
	$limit		= @$_GET['limit'];

	switch ($data) {
	
	default: {
	
		$bygningsliste = array();
		$datasett = array();
		$leieobjekthøyde = 90;
	
		$visUtleiegrad = true;
		$visBetalinger = true;
		
		// $fra og $til er første og siste dato i visningsspekteret
		$fra = new DateTime($this->fra);
		$til = new DateTime($this->til);
		
		$måned = clone $fra;
		$måneder = array();
		while( $måned < $til ) {
			$måneder[] = clone $måned;
			$måned->add(new DateInterval('P1M') );
		}

		$kravsett = $this->mysqli->arrayData(array(
			'source'		=> "{$tp}krav AS krav\n"
							.	"INNER JOIN {$tp}kontrakter AS kontrakter ON krav.kontraktnr = kontrakter.kontraktnr\n"
							.	"LEFT JOIN {$tp}oppsigelser as oppsigelser on kontrakter.leieforhold = oppsigelser.leieforhold",
			'fields'		=> "krav.id AS id",
			'distinct'		=> true,
			'class'			=> "Krav",

			'where'			=> "krav.type = 'Husleie'\n"
							.	"AND krav.beløp >=0\n"
							.	"AND (krav.fom <= '{$this->til}' AND krav.tom >= '{$this->fra}')\n",
						
			'orderfields'	=>	"kontrakter.leieobjekt,\n"
							.	"IF(kontrakter.fradato < '{$this->fra}' AND oppsigelser.fristillelsesdato > '{$this->til}', 0, 1),\n"
							.	"IF(oppsigelser.fristillelsesdato IS NOT NULL, oppsigelser.fristillelsesdato, kontrakter.fradato),\n"
							.	"kontrakter.leieforhold,\n"
							.	"krav.fom"
		));
	
		foreach( $kravsett->data as $krav ) {
			$leieforhold	= $krav->hent('leieforhold');
			$fom			= clone $krav->hent('fom');
			$nesteDag		= clone $krav->hent('tom');
			$nesteDag->add( new DateInterval('P1D'));
			$leieobjekt		= $leieforhold->hent('leieobjekt');
			$bygning		= $leieobjekt->hent('bygning');


			settype($datasett[strval($bygning)], 'array');
			settype( $datasett[ strval($bygning) ] [ strval($leieobjekt) ] , 'object');
			$datasett[ strval($bygning) ] [ strval($leieobjekt) ]->leieobjekt = $leieobjekt;

			settype( $datasett[ strval($bygning) ] [ strval($leieobjekt) ]->leieforhold, 'array');
			settype( $datasett[ strval($bygning) ] [ strval($leieobjekt) ]->leieforhold [ strval($leieforhold) ] , 'object');
			$datasett[ strval($bygning) ] [ strval($leieobjekt) ]->leieforhold [ strval($leieforhold) ]->leieforhold = $leieforhold;
			settype(
				$datasett[ strval($bygning) ] [ strval($leieobjekt) ]->leieforhold [ strval($leieforhold) ]->krav
			, 'array' );
			$datasett[ strval($bygning) ] [ strval($leieobjekt) ]->leieforhold [ strval($leieforhold) ]->krav[] = $krav;

			if( $visUtleiegrad ) {
				settype( $datasett[ strval($bygning) ] [ strval($leieobjekt) ]->perioder, 'array');
				$datasett[ strval($bygning) ] [ strval($leieobjekt) ]->perioder[ $fom->format('Ymd') ] = $fom;
				if( $nesteDag <= $til ) {
					$datasett[ strval($bygning) ] [ strval($leieobjekt) ]->perioder[ $nesteDag->format('Ymd') ] = $nesteDag;
				}
			}
		}
		
		if( $visUtleiegrad ) {
			foreach( $datasett as $leieobjekter ) {
				foreach( $leieobjekter as $leieobjekt ) {
					$leieobjekt->perioder[ $fra->format('Ymd') ] = clone $fra;
					
					rsort($leieobjekt->perioder);
					$slutt = clone $til;
					foreach( $leieobjekt->perioder as &$periode ) {
						$periode = (object)array(
							'start'	=> clone $periode,
							'slutt'	=> $slutt
						);
						$slutt = clone $periode->start;
						$slutt->sub( new DateInterval('P1D') );
					}
					$leieobjekt->perioder = array_reverse( $leieobjekt->perioder );
					
					foreach( $leieobjekt->perioder as &$periode ) {
						$periode->utleie = $leieobjekt->leieobjekt->hentUtleie( $periode->start, $periode->slutt );
					}			
				}
			}
			
		}
?>

<div id="utleietabell" style="width: <?php echo $til->diff($fra)->days * 2 + 157;?>px">

	<div class="overskriftsrad">
	<?php foreach($måneder as $måned):

		// $dager er antall dager perioden består av
		$dager	= $måned->format('t');

		// $w er bredden i antall pixler
		//	på perioden som skal vises
		$w		= $dager * 2;
		
		// $x er horisontal plassering av perioden
		//	oppgitt i antall pixler fra venstre
		$x = 155 + $måned->diff($fra)->days * 2;

		$stil = array(
			"left: {$x}px;",
			"width: {$w}px;",
		);

	?>

		<div style="<?php echo implode(' ', $stil);?>">
			<?php echo strftime('%b %y', $måned->getTimestamp());?>
			
		</div>
	<?php endforeach;?>
	
	</div>
	<?php foreach( $datasett as $bygning => $leieobjekter ):?>
	
	<div class="bygning">
		<?php foreach( $leieobjekter as $lo ):
			$leieobjekt = $lo->leieobjekt;?>

		<div class="leieobjekt">

			<div class="beskrivelse" title="<?php echo $leieobjekt->hent('beskrivelse');?>">
				<div>
					<a style="<?php echo !$visBetalinger ? "white-space: nowrap; width: 150px;" : "";?>" href="index.php?oppslag=leieobjekt_kort&id=<?php echo $leieobjekt;?>"><?php echo "#{$leieobjekt}: {$leieobjekt->hent('beskrivelse')}";?></a>
				</div>
			</div>
			<?php if( $visBetalinger ):?>
<?php
				foreach( $lo->leieforhold as $lf ):
					$leieforhold = $lf->leieforhold;
 					$andel = $leieforhold->hent('andel');
					$h = round($leieobjekthøyde * $this->fraBrøk( $andel ));
?>
					
			<div class="leieforhold" style="height:<?php echo $h;?>px;">
			
				<div class="beskrivelse" title="<?php echo $leieforhold->hent('beskrivelse');?>">
					<div>
						<a href="index.php?oppslag=leieforholdkort&id=<?php echo $leieforhold;?>"><?php echo "{$leieforhold} {$leieforhold->hent('navn')}";?></a>
					</div>
				</div>

				<?php foreach( $lf->krav as $termin ):
				
					// $fom og $tom er datoene leieterminen spenner mellom
					$fom	= $termin->hent('fom');
					$tom	= $termin->hent('tom');
					
					// $start og $slutt er første og siste dato
					//	som kommer innenfor visningsspekteret i tabellen
					$start	= max($fom, $fra);
					$slutt	= min($tom, $til);
					
					// $dager er antall dager leieterminen består av
					$dager	= $slutt->diff( $start )->days + 1;
					
					// $w er bredden i antall pixler
					//	på (delen av) leieterminen som skal vises
					$w		= $dager * 2;
					
					// $x er horisontal plassering av leieterminen
					//	oppgitt i antall pixler fra venstre
					$x = 90 + $start->diff($fra)->days * 2;

					$stil = array(
						"height: {$h}px;",
						"left: {$x}px;",
						"width: {$w}px;",
					);

					if( $fom == $fra ) {
						$stil[] = "border-left: 1px solid gray;";
					}
					if( $h < 25 ) {
						$stil[] = "white-space: nowrap;";
					}
					if( $tom <= $til ) {
						$stil[] = "border-right: 1px solid gray;";
					}
					
					if( $visBetalinger ) {
						$beløp				= $termin->hent('beløp');
						$utestående			= $termin->hent('utestående');
						$forfall			= $termin->hent('forfall');
						$antallPurringer	= $termin->hent('antallPurringer');
						
						if( $forfall <= new DateTime ) {
							if( $utestående == 0 ) {
								$stil[] = "background-color: palegreen;";
							}
							else if( $beløp == $utestående and $antallPurringer > 2 ) {
								$stil[] = "background-color: darkred;";
							}
							else if( $beløp == $utestående and $antallPurringer ) {
								$stil[] = "background-color: darkred;";
							}
							else if( $beløp == $utestående ) {
								$stil[] = "background-color: #ee2607;";
							}
							else {
								$stil[] = "background-color: orange;";
							}
						}
						else {
							if( $utestående == 0 ) {
								$stil[] = "background-color: palegreen;";
							}
							else if ( $beløp != $utestående ) {
								$stil[] = "background-color: orange;";
							}
						}
					}
					
				?>
				<?php if( !$termin->hentKrediteringer() ):?>
				
				<div title="<?php echo "{$termin->hent('tekst')}\n{$this->kr($beløp, false)}" . ( ($utestående > 0 and ($utestående != $beløp )) ? "\nRest: {$this->kr($utestående, false)}" : "") . ( ($utestående > 0 and $antallPurringer) ? "\nAnt Purringer: {$antallPurringer}" : "");?>" class="termin" style="<?php echo implode(' ', $stil);?>">
					<div>
						<a href="index.php?oppslag=krav_kort&id=<?php echo $termin;?>">
							<?php echo $termin->hent('termin');?><br />
							<?php echo $this->kr($beløp);?>

						</a>
					</div>
				</div>
				<?php endif;?>
				<?php endforeach;?>

			</div>
			<?php endforeach;?>
			<?php endif;?>

			<?php if( $visUtleiegrad ):?>

			<div class="leiegrad">
				<div class="" style="left: 60px; width: 25px; text-align: right;">
					Utleid:
				</div>
				<?php foreach( $lo->perioder as $periode ):

					// $start og $slutt er første og siste dato
					//	som kommer innenfor visningsspekteret i tabellen
					$start	= max($periode->start, $fra);
					$slutt	= min($periode->slutt, $til);
					
					// $dager er antall dager perioden består av
					$dager	= $slutt->diff( $start )->days + 1;

					// $w er bredden i antall pixler
					//	på perioden som skal vises
					$w		= $dager * 2;
					
					// $x er horisontal plassering av perioden
					//	oppgitt i antall pixler fra venstre
					$x = 90 + $start->diff($fra)->days * 2;

					$forklaring = "{$this->tilBrøk($periode->utleie->grad)} utleid";
					$stil = array(
						"left: {$x}px;",
						"width: {$w}px;",
					);

					if( $periode->utleie->grad > 1 ) {
						$stil[] = "background-color: black;";
					}
					else if( $periode->utleie->grad == 1 ) {
						$stil[] = "background-color: green;";
						$forklaring = "Utleid";
					}
					else if( $periode->utleie->grad == 0 ) {
						$stil[] = "background-color: #ee2607;";
						$forklaring = "Ikke utleid";
					}
					else {
						$stil[] = "background-color: orange;";
					}
				?>

				<div class="" title="<?php echo $forklaring;?>" style="<?php echo implode(' ', $stil);?>">
					<?php echo ($periode->utleie->grad == 0 or $periode->utleie->grad == 1) ? "&nbsp;" : $this->tilBrøk($periode->utleie->grad);?>
					
				</div>
				<?php endforeach;?>
				
			</div>
			<?php endif;?>

		</div>
		<?php endforeach;?>
		
	</div>
	<?php endforeach;?>
	
</div>
<?php
			break;
		}
	}
}



function manipuler( $data ) {
	switch ( $data ) {
	
	default:
		echo json_encode($resultat);
		break;
	}
}



function taimotSkjema() {

	echo json_encode($resultat);

}


function utskrift() {
	$this->hentData();
?>
<script type="text/javascript">
	window.print();
</script>
<?php
}



}
?>