<?php
class Rekordo extends AppModel {

public $actsAs = array('Containable');

public $belongsTo = array(
	'Konto' => array(
		'className' => 'Konto',
		'counterCache' => true
	),
	'Speco' => array(
		'className' => 'Speco'
	),
	'Matrikulisto' => array(
		'className' => 'Uzanto',
		'foreignKey' => 'matrikulisto'
	)
);

public $hasAndBelongsToMany = array(
	'Kategorio' => array(
		'className' => 'Kategorio'
	)
);


}
?>