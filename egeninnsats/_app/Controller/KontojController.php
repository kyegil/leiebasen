<?php
App::uses('AppController', 'Controller');

class KontojController extends AppController {

	public $helpers = array('Html', 'Form', 'Js', 'Time');

 	public function akiruRekordojn($id = null) {
		$rezulto = array();
		$rezulto['success'] = true;

		$conditions = array();
		$order = array();
		
		$limit = $page = $offset = null;
		if(isset($this->request->query['sort'])) {
			$order["Rekordo.{$this->request->query['sort']}"] = "desc";
			if($this->request->query['dir'] == 'ASC') {
				$order["Rekordo.{$this->request->query['sort']}"] = "asc";
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
		
		$conditions['Rekordo.konto_id'] = $id;
		
		if(isset($this->request->query['komenco']) && $this->request->query['komenco']) {			$conditions[]['OR'] = array(
				'Rekordo.finis_tempo >=' => $this->request->query['komenco'],
				'Rekordo.finis_tempo'	=> null
			);
		}
		if(isset($this->request->query['fino']) && $this->request->query['fino']) {
			$conditions[]['OR'] = array(
				'Rekordo.komencis_tempo <=' => $this->request->query['fino'],
				'Rekordo.komencis_tempo'	=> null
			);
		}
		if(isset($this->request->query['speco']) && $this->request->query['speco']) {
			$conditions['Rekordo.speco_id'] = $this->request->query['speco'];
		}
		if(isset($this->request->query['kategorioj']) && $this->request->query['kategorioj']) {
			$kategorioj = explode(',', $this->request->query['kategorioj']);
			$conditions['Kategorioj.kategorio_id'] = $kategorioj;
			$group = array('Rekordo.id HAVING count(Kategorioj.id) = ' . count($kategorioj));
			$joins = array(
				array(
					'table'	=> 'kategorioj_rekordoj',
					'alias'	=> 'Kategorioj',
					'type'	=> 'inner',
					'conditions' => array(
						'Rekordo.id = Kategorioj.rekordo_id'
					)
				)
			);
		}
		else {
			$group = array();
			$joins = array();
		}

		if(isset($this->request->query['serco'])) {
			$conditions[]['OR'] = array(
				'Rekordo.priskribo LIKE' => "%{$this->request->query['serco']}%",
				'Rekordo.detaloj LIKE'	=> "%{$this->request->query['serco']}%"
			);
		}
		
		$rezulto['data'] = $this->Konto->Rekordo->find('all', array(
//			'contain' => array('Speco', 'Matrikulisto', 'Kategorio'),
			'conditions' => $conditions,
			'fields'	=> array(
				'Rekordo.*',
				'Speco.*',
				'Matrikulisto.nomo'
			),
			'joins'	=> $joins,
			'group'	=> $group,
			'order' => $order,
			'limit' => $limit,
			'page' => $page,
			'offset' => $offset
		));
		
//		$rezulto['sql'] = $this->Konto->Rekordo->getLastQuery();

		$modelo = new Uzanto;
		$uzanto = $modelo->findById($this->Auth->user('id'));
		$tempozono = $uzanto['Uzanto']['tempozono'];

		$this->set('tempozono', $tempozono);
		$this->set('rezulto', $rezulto);
	}


	public function aldonu() {
	}


	public function forvisu() {
		$id = $this->request->data['id'];
		$rezulto = array();
		$rezulto['success'] = false;

		if (!$id) {
			$rezulto['msg'] = __('la konto ne estas specifita');
			$this->set('rezulto', $rezulto);
			$this->render('rezulto');
			return;
		}
		
		$konto = $this->Konto->findById($id);
		if (!$konto) {
			$rezulto['msg'] = __('nekonata konto');
			$this->set('rezulto', $rezulto);
			$this->render('rezulto');
			return;
		}
		
		if ($this->request->is(array('post', 'put'))) {
			$this->Konto->id = $id;
			if ($rezulto['success'] = $this->Konto->delete($id)) {
				$rezulto['msg'] = __('la konton estas forviŝita');
			}
			else {
				$rezulto['msg'] = __('forigo fiaskis');
			}
		}
		
		if (!$this->request->data) {
			$this->request->data = $konto;
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
			$order["Konto.{$this->request->query['sort']}"] = "desc";
			if($this->request->query['dir'] == 'ASC') {
				$order["Konto.{$this->request->query['sort']}"] = "asc";
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
		$rezulto['data'] = $this->Konto->find('all', array(
			'order' => $order,
			'limit' => $limit,
			'page' => $page,
			'offset' => $offset
		));
		
		$this->set('rezulto', $rezulto);
	}


 	public function sangu($id = null) {
		if (!$id) {
			throw new NotFoundException(__('nevalidan konton'));
		}
	
		$konto = $this->Konto->findById($id);
		if (!$konto) {
			throw new NotFoundException(__('nevalidan konton'));
		}
		$this->set('konto', $konto);
	}


 	public function sarguFormo($id = null) {
		$rezulto = array();
		$rezulto['success'] = false;

		if (!$id) {
			$rezulto['msg'] = __('la uzanto ne estas specifita');
			$this->set('rezulto', $rezulto);
			$this->render('rezulto');
			return;
		}
		
		$trovaĵo = $this->Konto->findById($id);
		if (!$trovaĵo) {
			$rezulto['msg'] = __('nekonata uzanto');
			$this->set('rezulto', $rezulto);
			$this->render('rezulto');
			return;
		}
		
		$rezulto['data'] = $trovaĵo['Konto'];
		$rezulto['success'] = true;
		$this->set('rezulto', $rezulto);
		$this->render('rezulto');
	}


	public function stoku($id = null) {
		$rezulto = array();
		$rezulto['success'] = false;
		if ($id == null) {
			$rezulto['msg'] = __('la konto ne estas specifita');
			$this->set('rezulto', $rezulto);
			$this->render('rezulto');
			return;
		}
		
		$konto = $this->Konto->findById($id);
		if (!$konto and $id) {
			$rezulto['msg'] = __('nekonata konto');
			$this->set('rezulto', $rezulto);
			$this->render('rezulto');
			return;
		}
		
		if ($this->request->is(array('post', 'put'))) {
			if($id == '0') {
				$this->Konto->create();
			}
			else {
				$this->Konto->id = $id;
			}
			
			if ($rezulto['success'] = (bool)$rezulto['data'] = $this->Konto->save($rezulto['success'] = $this->request->data)) {
				$rezulto['msg'] = __('la konto estas konservitaj');
			}
			else {
				$rezulto['msg'] = __('konservo fiaskis');
			}
		}
		
		if (!$this->request->data) {
			$this->request->data = $konto;
		}
		$this->set('rezulto', $rezulto);
		$this->render('rezulto');
	}
	

 	public function vidu($id = null) {
		if (!$id) {
			throw new NotFoundException(__('nevalidan konton'));
		}
	
		$konto = $this->Konto->findById($id);
		if (!$konto) {
			throw new NotFoundException(__('nevalidan konton'));
		}
		$this->set('konto', $konto);
	}


}
?>