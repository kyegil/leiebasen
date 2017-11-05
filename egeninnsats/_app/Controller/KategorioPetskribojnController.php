<?php
App::uses('AppController', 'Controller');

class KategorioPetskribojnController extends AppController {


public function akiruArbon() {
	$data = $this->KategorioPetskribon->extFormattedCheckTree();
	$this->set('rezulto', $data);
	$this->render('rezulto');
}


}
?>