<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright	 Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link		  http://cakephp.org CakePHP(tm) Project
 * @package	   app.Controller
 * @since		 CakePHP(tm) v 0.2.9
 * @license	   http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');
// App::uses('L10n', 'I18n');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package		app.Controller
 * @link		http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {

	public $components = array(
		'Session',
		'RequestHandler',
		'Auth' => array(
			'loginAction' => array(
				'controller' => 'uzantoj',
				'action' => 'ensalutu'
			),
			'loginRedirect' => array(
				'controller' => 'kontoj',
				'action' => 'index'
			),
			'logoutRedirect' => array(
				'controller' => 'pages',
				'action' => 'display',
				'home'
			),
			'authenticate' => array(
				'Form' => array(
					'passwordHasher' => 'Blowfish',
					'userModel' => 'Uzanto',
					'fields' => array(
						'username' => 'uzanto',
						'password' => 'pasvorto'
					)
				)
			)
		)
	);


function afterFilter() {
	// if in mobile mode, check for a valid view and use it
	if (isset($this->is_mobile) && $this->is_mobile) {
		$view_file = new File( VIEWS . $this->name . DS . 'mobile/' . $this->action . '.ctp' );
		$this->render($this->action, 'mobile', ($view_file->exists() ? 'mobile/' : '') . $this->action);
	}
}


function beforeFilter() {
	if ($this->RequestHandler->isMobile()) {
		$this->is_mobile = true;
		$this->set('is_mobile', true );
		$this->autoRender = false;
	}
	
	setlocale(LC_ALL, Configure::read('kontribuo.lokaĵaro'));
//	$l10n = new L10n();
//	$this->locale = $l10n->map(Configure::read('Config.language'));
}



}