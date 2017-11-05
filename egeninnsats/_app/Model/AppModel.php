<?php
/**
 * Application model for CakePHP.
 *
 * This file is application-wide model file. You can put all
 * application-wide model-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Model
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Model', 'Model');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AppModel extends Model {

// 	public $belongsTo = array(
// 		'Model' => array(
// 			'className' => 'Model'
// 		),
// 		'AnotherModel' => array(
// 			'className' => 'AnotherModel'
// 		)
// 	);
// 
// 	public $hasMany = array(
// 		'Model' => array(
// 			'className' => 'Model',
// 			'order' => 'orderfield DESC, anothertable.anotherfield DESC',
// 			'dependent' => true,
// 			'counterCache' => true
// 		)
// 	);
// 
// 	public $hasAndBelongsToMany = array(
// 		'Model' => array(
// 			'className' => 'Model'
//			'joinTable' => 'model_this',
//			'foreignKey' => 'this_id',
//			'associationForeignKey' => 'model_id',
//			'unique' => true, // defaults to true
//			'conditions' => array(),
//			'fields' => '',
//			'order' => array(),
//			'limit' => '',
//			'offset' => '',
//			'finderQuery' => '',
//			'with' => ''
// 		)
// 	);


public function getLastQuery() {
    $dbo = $this->getDatasource();
    $logs = $dbo->getLog();
    $lastLog = end($logs['log']);
    return $lastLog['query'];
}


public function idsAsArray($array) {
	$result = array();
	foreach($array as $element) {
		$result[] = $element['id'];
	}
	return $result;
}


public function saveAssociated($data = null, $options = array()) {

	foreach ($data as $alias => $modelData) {
	
		if (!empty($this->hasAndBelongsToMany[$alias])) {
			
			$habtm = array();
			
			$Model = ClassRegistry::init($this->hasAndBelongsToMany[$alias]['className']);
			
			foreach ($modelData as $modelDatum) {
			
				if (empty($modelDatum['id'])) {
				
					$Model->create();
					
				}
				
				$Model->save($modelDatum);
				
				$habtm[] = empty($modelDatum['id']) ? $Model->getInsertID() : $modelDatum['id'];                    
				
			}
			
			$data[$alias] = array($alias => $habtm);
			
		}
		
	}
	
	return parent::saveAssociated($data, $options);
	
}


}
