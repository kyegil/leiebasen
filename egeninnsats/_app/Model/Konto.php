<?php
class Konto extends AppModel {

	public $belongsTo = array(
		'Mastro' => array(
			'className' => 'Uzanto',
			'foreignKey' => 'mastro'
		)
	);

	public $hasMany = array(
		'Rekordo' => array(
			'className' => 'Rekordo',
			'order' => 'Rekordo.finis_tempo DESC, Rekordo.komencis_tempo DESC',
			'dependent' => true
		)
	);

}
?>