<?php

App::uses('AppModel', 'Model');
App::uses('BlowfishPasswordHasher', 'Controller/Component/Auth');

class Uzanto extends AppModel {
//	public $useDbConfig = 'uzantoj';
	
	public $validate = array(
		'uzanto' => array(
			'required' => array(
				'rule' => array('notEmpty'),
				'message' => 'Uzantonomon estas bezonata'
			)
		),
		'pasvorto' => array(
			'required' => array(
				'rule' => array('notEmpty'),
				'message' => 'Pasvorta postulas'
			)
		),
		'role' => array(
			'valid' => array(
				'rule' => array('inList', array('admin', 'author')),
				'message' => 'Bonvolu entajpi validan rolon',
				'allowEmpty' => false
			)
		)
	);


	public function beforeSave($options = array()) {
		if (isset($this->data[$this->alias]['pasvorto'])) {
			$passwordHasher = new BlowfishPasswordHasher();
			$this->data[$this->alias]['pasvorto'] = $passwordHasher->hash(
				$this->data[$this->alias]['pasvorto']
			);
		}
		return true;
	}


	public function trovuLaEnsalutintaUzanto() {
		$uzanto = $this->Auth->user('id');
		die($uzanto);
	}



}

?>