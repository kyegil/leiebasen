<?php
App::uses('AppController', 'Controller');

class SpecojController extends AppController {

	public $helpers = array('Html', 'Form', 'Js');

 	public function akiru($id = null) {
		if (!$id) {
			throw new NotFoundException(__('la speco ne estas specifita'));
		}
	
		$speco = $this->Speco->findById($id);
		if (!$speco) {
			throw new NotFoundException(__('nekonata speco'));
		}
		$this->set('speco', $speco);
	}


	public function aldonu() {
	}


	public function forvisu() {
		$id = $this->request->data['id'];
		$rezulto = array();
		$rezulto['success'] = false;

		if (!$id) {
			$rezulto['msg'] = 'la speco ne estas specifita';
			$this->set('rezulto', $rezulto);
			$this->render('rezulto');
			return;
		}
		
		$speco = $this->Speco->findById($id);
		if (!$speco) {
			$rezulto['msg'] = 'nekonata speco';
			$this->set('rezulto', $rezulto);
			$this->render('rezulto');
			return;
		}
		
		if ($this->request->is(array('post', 'put'))) {
			$this->Speco->id = $id;
			if ($rezulto['success'] = $this->Speco->delete($id)) {
				$rezulto['msg'] = 'la specon de kontribuo estas forviŝita';
			}
			else {
				$rezulto['msg'] = 'forigo provo malsukcesis';
			}
		}
		
		if (!$this->request->data) {
			$this->request->data = $speco;
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
			$order["Speco.{$this->request->query['sort']}"] = "desc";
			if($this->request->query['dir'] == 'ASC') {
				$order["Speco.{$this->request->query['sort']}"] = "asc";
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
		$rezulto['data'] = $this->Speco->find('all', array(
			'order' => $order,
			'limit' => $limit,
			'page' => $page,
			'offset' => $offset
		));
		
		$this->set('rezulto', $rezulto);
	}


 	public function sangu($id = null) {
		if (!$id) {
			throw new NotFoundException(__('nevalidan specon'));
		}
	
		$speco = $this->Speco->findById($id);
		if (!$speco) {
			throw new NotFoundException(__('nevalidan specon'));
		}
		$this->set('speco', $speco);
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
		
		$trovaĵo = $this->Speco->findById($id);
		if (!$trovaĵo) {
			$rezulto['msg'] = 'nekonata uzanto';
			$this->set('rezulto', $rezulto);
			$this->render('rezulto');
			return;
		}
		
		$rezulto['data'] = $trovaĵo['Speco'];
		$rezulto['success'] = true;
		$this->set('rezulto', $rezulto);
		$this->render('rezulto');
	}


	public function stoku($id = null) {
		$rezulto = array();
		$rezulto['success'] = false;
		if ($id == null) {
			$rezulto['msg'] = 'la speco ne estas specifita';
			$this->set('rezulto', $rezulto);
			$this->render('rezulto');
			return;
		}
		
		$speco = $this->Speco->findById($id);
		if (!$speco and $id) {
			$rezulto['msg'] = 'nekonata speco';
			$this->set('rezulto', $rezulto);
			$this->render('rezulto');
			return;
		}
		
		if ($this->request->is(array('post', 'put'))) {
			if($id == '0') {
				$this->Speco->create();
			}
			else {
				$this->Speco->id = $id;
			}
			$rezulto['data'] = $this->request->data;
			
			if ($rezulto['success'] = $this->Speco->save($rezulto['success'] = $this->request->data)) {
				$rezulto['msg'] = 'la speco de kontribuo estas konservitaj';
			}
			else {
				$rezulto['msg'] = 'konservo provo malsukcesis';
			}
		}
		
		if (!$this->request->data) {
			$this->request->data = $speco;
		}
		$this->set('rezulto', $rezulto);
		$this->render('rezulto');
	}
	

 	public function vidu($id = null) {
		if (!$id) {
			throw new NotFoundException(__('nevalidan specon'));
		}
	
		$speco = $this->Speco->findById($id);
		if (!$speco) {
			throw new NotFoundException(__('nevalidan specon'));
		}
		$this->set('speco', $speco);
	}


}
?>