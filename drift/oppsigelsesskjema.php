<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

function __construct() {
	parent::__construct();
	$this->ext_bibliotek = 'ext-3.4.0';
	if(!$id = (int)$_GET['id']) die("Ugyldig oppslag: ID ikke angitt for kontrakt");
	$this->hoveddata = "SELECT * FROM kontrakter WHERE kontraktnr = $id";
}

function skript() {
	if(@$_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	$tp = $this->mysqli->table_prefix;

	$leieforhold = $this->leieforhold((int)@$_GET['id'], true);
	$kontrakt = $this->kontrakt((int)$_GET['id']);
	
	$oppsigelsesdato = date('d.m.Y');
	$fristillelsesdato = $this->leggtilintervall(time(), $kontrakt['oppsigelsestid']);

	if($sluttdato = $this->sluttdato($kontrakt['kontraktnr'])) {
		$fristillelsesdato = min($fristillelsesdato, ($sluttdato + 24 * 3600));
	}
	
	$sql =	"SELECT min(fradato) AS fradato\n"
	.		"FROM kontrakter\n"
	.		"WHERE leieforhold = '{$leieforhold}'";
	$fradato = $this->arrayData($sql);
	$fradato = $fradato['data'][0]['fradato'];

	$sql =	"SELECT min(fom) AS fristillelsesdato\n"
	.		"FROM krav INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr\n"
	.		"WHERE fom >= '" . date('Y-m-d', $fristillelsesdato) . "' AND leieforhold = " . $kontrakt['leieforhold'];
	$a = $this->arrayData($sql);
	if($a['data'][0]['fristillelsesdato']) {
		$fristillelsesdato = strtotime($a['data'][0]['fristillelsesdato']);
	}
	$fristillelsesdato = date('d.m.Y', $fristillelsesdato);
	
	$html =	"Leieavtale nummer <b>" . $_GET['id'] . ":</b> " . $this->liste($this->kontraktpersoner($_GET['id'])) . " i " . $this->leieobjekt($this->kontraktobjekt($_GET['id'])) . ". ";
	if($this->oppsagt($kontrakt['kontraktnr']))
		$html .= "Leieavtalen er sagt opp " . date('d.m.Y', $this->oppsagt($kontrakt['kontraktnr']));
	else if($this->sluttdato($kontrakt['kontraktnr']))
		$html .= "Leieavtalen er tidsbestemt og utløper den <b>" . date('d.m.Y', $this->sluttdato($kontrakt['kontraktnr'])) . "</b>";
	else
		$html .= "<b>Leieavtalen er ikke tidsbegrenset</b>";
	$html .= "<br />";
	$html .= "Oppsigelsestid: <b>" . $this->oppsigelsestidrenderer($kontrakt['oppsigelsestid']) . "</b>";
	$html .= "<br />";
	$html .= "<br />";
	
?>

Ext.onReady(function() {
<?
	include_once("_menyskript.php");
?>

	Ext.QuickTips.init();
	Ext.form.Field.prototype.msgTarget = 'side';

	function sendParametere(){
		Ext.Ajax.request({
			url: 'index.php?oppslag=oppsigelsesskjema&oppdrag=hentdata&data=datoer&id=<?=$_GET['id']?>',
			params: {
				oppsigelsesdato: oppsigelsesdato.getValue(),
				fristillelsesdato: fristillelsesdato.getValue()
			},
			 success : function(result) {
				oppsigelsestid_slutt.setValue(Ext.decode(result.responseText).oppsigelsestid_slutt);
            }
		});
	}
	
	var html = {
		html: "<?=$html?>"};
	
	var oppsigelsesdato = new Ext.form.DateField({
		allowBlank: false,
		altFormats: "j.n.y|j.n.Y|j/n/y|j/n/Y|j-n-y|j-n-Y|j. M y|j. M -y|j. M. Y|j. F -y|j. F y|j. F Y|j/n|j-n|dm|dmy|dmY|d|Y-m-d",
		fieldLabel: 'Oppsigelsesdato (Datoen da oppsigelsen er levert / kunngjort)',
		format: 'd.m.Y',
		listeners: {
			valid: sendParametere
		},
		name: 'oppsigelsesdato',
		value: '<?=$oppsigelsesdato;?>',
		width: 190
	});


	var fristillelsesdato = new Ext.form.DateField({
		allowBlank: false,
		altFormats: "j.n.y|j.n.Y|j/n/y|j/n/Y|j-n-y|j-n-Y|j. M y|j. M -y|j. M. Y|j. F -y|j. F y|j. F Y|j/n|j-n|dm|dmy|dmY|d|Y-m-d",
		fieldLabel: 'Ledig fra og med dato (dagen da leieobjektet skal være fraflyttet og disponibelt for <?=$this->valg['utleier']?> eller nye leietakere)',
		format: 'd.m.Y',
		listeners: {
			valid: sendParametere
		},
		name: 'fristillelsesdato',
		minValue: '<?=$fradato;?>',
		value: '<?=$fristillelsesdato;?>',
		width: 190
	});


	var ignorer_oppsigelsestid = new Ext.form.Checkbox({
		fieldLabel: 'Ignorer oppsigelsestiden',
		boxLabel: 'Sett kryss her dersom leieavtalen heves uten oppsigelsestid.',
		hideLabel: true,
		name: 'ignorer_oppsigelsestid',
		checked: false,
		inputValue: 1,
		uncheckedValue: 0
	});


	var oppsigelsestid_slutt = new Ext.form.Field({
		altFormats: "j.n.y|j.n.Y|j/n/y|j/n/Y|j-n-y|j-n-Y|j. M y|j. M -y|j. M. Y|j. F -y|j. F y|j. F Y|j/n|j-n|dm|dmy|dmY|d|Y-m-d",
		fieldLabel: 'Oppsigelsestiden utløpt (Datoen da leieavtalen og leieforpliktelsene har opphørt.)',
		disabled: true,
		format: 'd.m.Y',
		name: 'oppsigelsestid_slutt',
		value: '<?=$fristillelsesdato;?>',
		width: 190
	});


	var ref = new Ext.form.Field({
		fieldLabel: 'Referanse til arkivert (papirversjon) av oppsigelsen.',
		name: 'ref',
		value: '<?=$kontrakt['leieforhold'];?>',
		width: 190
	});


	var merknad = new Ext.form.TextArea({
		fieldLabel: 'Merknad (Evt. sitert oppsigelsestekst)',
		name: 'merknad',
		width: 600
	});
	
	var skjema = new Ext.FormPanel({
		labelAlign: 'top',
		frame:true,
		title: 'Oppsigelse av leieavtale',
		bodyStyle:'padding:5px 5px 0',
		standardSubmit: false,
		width: 900,
		items: [html<?=!$this->oppsagt($kontrakt['kontraktnr'])?", oppsigelsesdato, fristillelsesdato, oppsigelsestid_slutt, ignorer_oppsigelsestid, ref, merknad, {
			xtype: 'radiogroup',
			fieldLabel: 'Leieavtalen oppsagt av',
			itemCls: 'x-check-group-alt',
			columns: 1,
			items: [
				{boxLabel: 'Leietaker', name: 'oppsagt_av_utleier', inputValue: 0, checked: true},
				{boxLabel: 'Utleier', name: 'oppsagt_av_utleier', inputValue: 1}
			]
		}":""?>],
		buttons: [{
			text: 'Avbryt',
			handler: function() {
				window.location = '<?php echo $this->returi->get();?>';
			}
		}, {
			text: 'Registrer denne oppsigelsen og slett overskytende leie',
			disabled: <?php echo $this->oppsagt($kontrakt['kontraktnr']) ? "true" : "false" ;?>,
			handler: function() {
				oppsigelsestid_slutt.enable();
				skjema.getForm().getEl().dom.action = 'index.php?oppslag=oppsigelsesskjema&oppdrag=taimotskjema&id=<?=$_GET['id']?>';
				skjema.getForm().submit({
					waitMsg:'Registrerer oppsigelse..'
				});
			}
		}]
	});
	
	skjema.render('panel');

	skjema.on({
		actioncomplete: function(form, action){

			if(action.type == 'submit'){
				if(action.response.responseText == '') {
					Ext.MessageBox.alert('Problem', 'Mottok ikke bekreftelsesmelding fra tjeneren  i JSON-format som forventet');
				} else {
					Ext.MessageBox.alert('Ferdig', action.result.msg, function(){
						window.location = '<?="index.php?oppslag=leieforhold_adressekort&id={$leieforhold}";?>';
					});
				}
			}
		},
		actionfailed: function(form,action){
			if(action.type == 'submit') {
				var result = Ext.decode(action.response.responseText);
				if(result && result.msg) {			
					Ext.MessageBox.alert('Registreringen feilet:', result.msg);
				}
				else {
					Ext.MessageBox.alert('Problem:', 'Lagring av data mislyktes av ukjent grunn. Action type='+action.type+', failure type='+action.failureType);
				}
			}
		}
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
	switch ($data) {
		case "datoer":
			$kontrakt = $this->arrayData($this->hoveddata);
			$kontrakt = $kontrakt['data'][0];

			$oppsigelsesdato = strtotime($_POST['oppsigelsesdato']);
			$fristillelsesdato = strtotime($_POST['fristillelsesdato']);
			if(!$fristillelsesdato)
				$fristillelsesdato = ($this->sluttdato($kontrakt['kontraktnr']) + 24 * 3600);
			$oppsigelsestid_slutt = max($fristillelsesdato, $this->leggtilintervall($oppsigelsesdato, $kontrakt['oppsigelsestid']));

			if($sluttdato = $this->sluttdato($kontrakt['kontraktnr'])) {
				$oppsigelsestid_slutt = max($fristillelsesdato, min($oppsigelsestid_slutt, ($sluttdato + 24 * 3600)));
			}
	
			$sql =	"SELECT min(fom) AS oppsigelsestid_slutt\n"
			.		"FROM krav INNER JOIN kontrakter ON krav.kontraktnr = kontrakter.kontraktnr\n"
			.		"WHERE fom >= '" . date('Y-m-d', $oppsigelsestid_slutt) . "' AND leieforhold = " . $kontrakt['leieforhold'];
			$a = $this->arrayData($sql);
			if($a['data'][0]['oppsigelsestid_slutt']) {
				$oppsigelsestid_slutt = strtotime($a['data'][0]['oppsigelsestid_slutt']);
			}
			$resultat['oppsigelsestid_slutt'] = date('d.m.Y', $oppsigelsestid_slutt);
			$resultat['success'] = true;

			return json_encode($resultat);			
			break;
		default:
			return json_encode($this->arrayData($this->hoveddata));
	}
}



function taimotSkjema() {
	$tp = $this->mysqli->table_prefix;

	if(!@$_POST['oppsigelsestid_slutt']) {
		$resultat['success'] = false;
		$resultat['msg'] = "Klarte ikke lese skjemaet. Prøv gjen.";
		echo json_encode($resultat);
		return;
	}
	
	$resultat = (object)array(
		'success'	=> false,
		'msg'		=> ''
	);

	$leieforhold = $this->leieforhold( (int)@$_GET['id'], true );
	$oppsigelsesdato		= new DateTime($_POST['oppsigelsesdato']);
	$fristillelsesdato		= new DateTime($_POST['fristillelsesdato']);
	$oppsigelsestidSlutt	= new DateTime($_POST['oppsigelsestid_slutt']);
	$ignorerOppsigelsestid	= (bool)@$_POST['ignorer_oppsigelsestid'];
	$ref					= $_POST['ref'];
	$merknad				= $_POST['merknad'];
	$oppsagtAvUtleier		= (bool)@$_POST['oppsagt_av_utleier'];
	
	if( $leieforhold->hent('oppsigelser') ) {
		echo json_encode( array(
			'success'	=> false,
			'msg'		=> "Denne leieavtalen er allerede sagt opp."
		) );
		return;
	}
	
	$resultat->success = $leieforhold->avslutt(
		$oppsigelsesdato,
		$fristillelsesdato,
		$ignorerOppsigelsestid ? $fristillelsesdato : $oppsigelsestidSlutt,
		$ref,
		$merknad,
		$oppsagtAvUtleier
	);
	
	if( !$resultat->success ) {
		$resultat->msg = "Klarte ikke registrere oppsigelsen.";
		echo json_encode($resultat);
		return;
	}
	else {
		$resultat->msg = "Oppsigelsen er registrert.<br />";
	}


	// Avslutter levering til denne adressen hvis oppsigelsesdato er mindre enn 15 dager fram i tid
	if( strtotime($_POST['oppsigelsesdato']) < (time() + 15 * 24 * 3600) ) {
		$this->mysqli->saveToDb(array(
			'table'		=>	"{$tp}kontrakter",
			'update'	=> true,
			'where'		=> "leieforhold = '{$leieforhold}' AND regningsobjekt = leieobjekt",
			'fields'	=> array(
				'regning_til_objekt'	=> false
			)
		));
	}

	if( $resultat->success ) {
		$this->returi->set( "{$this->http_host}/drift/index.php?oppslag=leieforholdkort&id={$leieforhold}" );
		$this->returi->set( "{$this->http_host}/drift/index.php?oppslag=leieforhold_leveringsadresse&id={$leieforhold}" );
		$this->returi->set( "{$this->http_host}/drift/index.php?oppslag=leieforhold_adressekort&id={$leieforhold}" );

	

	}

	echo json_encode($resultat);
}

}
?>