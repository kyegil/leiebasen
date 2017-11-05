<?php
foreach($rezulto['data'] as &$rekordo) {

	$rekordo['html'] =	"<div>{$rekordo['Rekordo']['detaloj']}</div>\n";
	$rekordo['html'] .=	$this->element('Kontribuo.kerno.kategorioEtikedo', array(
		'rekordo'	=> $rekordo
	));

	$rekordo['Rekordo']['komencis_tempo_formatita'] = $this->Time->format(
		'd.m.Y',
		$rekordo['Rekordo']['komencis_tempo'],
		null,
		$tempozono
	);

	$rekordo['Rekordo']['finis_tempo_formatita'] = $this->Time->format(
		'd.m.Y',
		$rekordo['Rekordo']['finis_tempo'],
		null,
		$tempozono
	);

	$rekordo['Rekordo']['tempo_de_enskribo_formatita'] = $this->Time->format(
		'd.m.Y',
		$rekordo['Rekordo']['tempo_de_enskribo'],
		null,
		$tempozono
	);

}

echo json_encode($rezulto);
?>