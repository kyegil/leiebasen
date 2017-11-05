<?php
class Kategorio extends AppModel {

	public $hasAndBelongsToMany = array(
		'Rekordo' => array(
			'className' => 'Rekordo'
		)
	);

	public $hasMany = array(
		'KategorioPetskribon' => array(
			'className' => 'KategorioPetskribon',
//			'order' => '',
			'dependent' => true,
			'counterCache' => true
		)
	);

}
?>