<?php
App::uses('AppController', 'Controller');

class UzantojController extends AppController {

	public $helpers = array('Html', 'Form', 'Js');

	
	public function akiru($id = null) {
		if (!$id) {
			throw new NotFoundException(__('la uzanto ne estas specifita'));
		}
	
		$uzanto = $this->Uzanto->findById($id);
		if (!$uzanto) {
			throw new NotFoundException(__('nekonata uzanto'));
		}
		$this->set('uzanto', $uzanto);
	}


	public function aldonu() {
	}


	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('aldonu', 'stoku', 'elsalutu');
	}

	
	public function ensalutu() {
		if ($this->request->is('post')) {
			$uzanto = $this->Uzanto->findByUzanto($this->request->data['Uzanto']['uzanto']);
			
			$md5 = false;
			if(Security::hash($this->request->data['Uzanto']['pasvorto'], 'md5', '') == $uzanto['Uzanto']['pasvorto']) {
				$this->Uzanto->id = $uzanto['Uzanto']['id'];
				$this->Uzanto->saveField('pasvorto', $this->request->data['Uzanto']['pasvorto']);
				$md5 = true;
			}
			
			if((substr($uzanto['Uzanto']['pasvorto'], 0, 2) == "$2" || $md5) && $this->Auth->login()) {
				return $this->redirect($this->Auth->redirect());
			}
			$this->Session->setFlash(__('Nevalida salutnomo aŭ pasvorto, reprovu.'));
		}
	}

	
	public function elsalutu() {
		return $this->redirect($this->Auth->logout());
	}


	public function forvisu() {
		$id = $this->request->data['id'];
		$rezulto = array();
		$rezulto['success'] = false;

		if (!$id) {
			$rezulto['msg'] = 'la uzanto ne estas specifita';
			$this->set('rezulto', $rezulto);
			$this->render('rezulto');
			return;
		}
		
		$uzanto = $this->Uzanto->findById($id);
		if (!$uzanto) {
			$rezulto['msg'] = 'nekonata uzanto';
			$this->set('rezulto', $rezulto);
			$this->render('rezulto');
			return;
		}
		
		if ($this->request->is(array('post', 'put'))) {
			$this->Uzanto->id = $id;
			if ($rezulto['success'] = $this->Uzanto->delete($id)) {
				$rezulto['msg'] = 'la uzanton estas forviŝita';
			}
			else {
				$rezulto['msg'] = 'forigo provo malsukcesis';
			}
		}
		
		if (!$this->request->data) {
			$this->request->data = $uzanto;
		}
		$this->set('rezulto', $rezulto);
		$this->render('rezulto');
	}
	

	public function index() {
		$rezulto = array();
		$rezulto['success'] = true;

		$order = array();
		$limit = $page = $offset = null;
				if(isset($this->request->query['sort'])) {
			$order["Uzanto.{$this->request->query['sort']}"] = "desc";
			if($this->request->query['dir'] == 'ASC') {
				$order["Uzanto.{$this->request->query['sort']}"] = "asc";
			}
		}
		if(isset($this->request->query['limit'])) {
			$limit = $this->request->query['limit'];
		}
		if(isset($this->request->query['page'])) {
			$page = $this->request->query['page'];
		}
		if(isset($this->request->query['start'])) {
			$offset = $this->request->query['start'];
		}
		$rezulto['data'] = $this->Uzanto->find('all', array(
			'order' => $order,
			'limit' => $limit,
			'page' => $page,
			'offset' => $offset
		));
		
		$this->set('rezulto', $rezulto);
	}


 	public function sangu($id = null) {
		if (!$id) {
			throw new NotFoundException(__('nevalidan uzanton'));
		}
	
		$uzanto = $this->Uzanto->findById($id);
		if (!$uzanto) {
			throw new NotFoundException(__('nevalidan uzanton'));
		}
		$this->set('uzanto', $uzanto);
	}


 	public function sargiFormo($id = null) {
		$rezulto = array();
		$rezulto['success'] = false;

		if (!$id) {
			$rezulto['msg'] = 'la uzanto ne estas specifita';
			$this->set('rezulto', $rezulto);
			$this->render('rezulto');
			return;
		}
		
		$trovaĵo = $this->Uzanto->findById($id);
		if (!$trovaĵo) {
			$rezulto['msg'] = 'nekonata uzanto';
			$this->set('rezulto', $rezulto);
			$this->render('rezulto');
			return;
		}
		
		$rezulto['data'] = $trovaĵo['Uzanto'];
		$rezulto['success'] = true;
		$this->set('rezulto', $rezulto);
		$this->render('rezulto');
	}


	public function stoku($id = null) {
		$rezulto = array();
		$rezulto['success'] = false;
		if ($id == null) {
			$rezulto['msg'] = 'la uzanto ne estas specifita';
			$this->set('rezulto', $rezulto);
			$this->render('rezulto');
			return;
		}
		
		$uzanto = $this->Uzanto->findById($id);
		if (!$uzanto and $id) {
			$rezulto['msg'] = 'nekonata uzanto';
			$this->set('rezulto', $rezulto);
			$this->render('rezulto');
			return;
		}
		
		if ($this->request->is(array('post', 'put'))) {
			if($id == '0') {
				$this->Uzanto->create();
			}
			else {
				$this->Uzanto->id = $id;
			}
			$rezulto['data'] = $this->request->data;
			
			if ($rezulto['success'] = $this->Uzanto->save($rezulto['success'] = $this->request->data)) {
				$rezulto['msg'] = 'la uzanto estas konservitaj';
			}
			else {
				$rezulto['msg'] = 'konservo provo malsukcesis';
			}
		}
		
		if (!$this->request->data) {
			$this->request->data = $uzanto;
		}
		$this->set('rezulto', $rezulto);
		$this->render('rezulto');
	}
	

 	public function vidu($id = null) {
		if (!$id) {
			throw new NotFoundException(__('nevalidan uzanton'));
		}
	
		$uzanto = $this->Uzanto->findById($id);
		if (!$uzanto) {
			throw new NotFoundException(__('nevalidan uzanton'));
		}
		$this->set('uzanto', $uzanto);
	}


}
?>