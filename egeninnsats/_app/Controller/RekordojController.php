<?php
App::uses('AppController', 'Controller');

class RekordojController extends AppController {
public $helpers = array('Html', 'Form', 'Js');


public function akiru($id = null) {
	if (!$id) {
		throw new NotFoundException(__('la rekordo ne estas specifita'));
	}

	$rekordo = $this->Rekordo->findById($id);
	if (!$rekordo) {
		throw new NotFoundException(__('nekonata rekordo'));
	}
	$this->set('rekordo', $rekordo);
}


public function akiruArbon($id) {
	$rekordo = $this->Rekordo->findById($id);
	
	$config = array();
	if($id) {
		$config['checked'] = $this->Rekordo->idsAsArray($rekordo['Kategorio']);
	}

	$data = $this->Rekordo->Kategorio->KategorioPetskribon->extFormattedCheckTree(null, $config);
	$this->set('rezulto', $data);
	$this->render('rezulto');
}


public function aldonu() {
}


public function aldonuGrupoKontribuoj() {
}


public function forvisu() {
	$id = $this->request->data['id'];
	$rezulto = array();
	$rezulto['success'] = false;

	if (!$id) {
		$rezulto['msg'] = __('la rekordo ne estas specifita');
		$this->set('rezulto', $rezulto);
		$this->render('rezulto');
		return;
	}
	
	$rekordo = $this->Rekordo->findById($id);
	if (!$rekordo) {
		$rezulto['msg'] = __('nekonata rekordo');
		$this->set('rezulto', $rezulto);
		$this->render('rezulto');
		return;
	}
	
	if ($this->request->is(array('post', 'put'))) {
		$this->Rekordo->id = $id;
		if ($rezulto['success'] = $this->Rekordo->delete($id)) {
			$rezulto['msg'] = __('la rekordon estas forviŝita');
		}
		else {
			$rezulto['msg'] = __('forigo fiaskis');
		}
	}
	
	if (!$this->request->data) {
		$this->request->data = $rekordo;
	}
	$this->set('rezulto', $rezulto);
	$this->render('rezulto');
}


public function index() {
	$rezulto = array();
	$rezulto['success'] = true;

	$order = array();
	$limit = $page = $offset = null;
	if(isset($this->request->data['sort'])) {
		$order["Rekordo.{$this->request->data['sort']}"] = "desc";
		if($this->request->data['dir'] == 'ASC') {
			$order["Rekordo.{$this->request->data['sort']}"] = "asc";
		}
	}
	if(isset($this->request->data['limit'])) {
		$limit = $this->request->data['limit'];
	}
	if(isset($this->request->data['page'])) {
		$page = $this->request->data['page'];
	}
	if(isset($this->request->data['start'])) {
		$offset = $this->request->data['start'];
	}
	$rezulto['data'] = $this->Rekordo->find('all', array(
		'contain' => array('Konto', 'Speco', 'Matrikulisto'),
		'order' => $order,
		'limit' => $limit,
		'page' => $page,
		'offset' => $offset
	));
	
	$this->set('rezulto', $rezulto);
}


public function sangu($id = null) {
	if (!$id) {
		throw new NotFoundException(__('nevalidan rekordon'));
	}

	$rekordo = $this->Rekordo->findById($id);
	if (!$rekordo) {
		throw new NotFoundException(__('nevalidan rekordon'));
	}
	$this->set('rekordo', $rekordo);
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
	
	$trovaĵo = $this->Rekordo->findById($id);
	if (!$trovaĵo) {
		$rezulto['msg'] = __('nekonata uzanto');
		$this->set('rezulto', $rezulto);
		$this->render('rezulto');
		return;
	}
	
	$rezulto['data'] = $trovaĵo['Rekordo'];
	$rezulto['success'] = true;
	$this->set('rezulto', $rezulto);
	$this->render('rezulto');
}


public function stoku($id = null) {
	$rezulto = array();
	$rezulto['success'] = false;
	if ($id == null) {
		$rezulto['msg'] = __('la rekordo ne estas specifita');
		$this->set('rezulto', $rezulto);
		$this->render('rezulto');
		return;
	}
	
	$rekordo = $this->Rekordo->findById($id);
	if (!$rekordo and $id) {
		$rezulto['msg'] = __('nekonata rekordo');
		$this->set('rezulto', $rezulto);
		$this->render('rezulto');
		return;
	}
	
	$data['Rekordo'] = $this->request->data;

	if ($this->request->is(array('post', 'put'))) {
		if($id == '0') {
			$this->Rekordo->create();
			$data['Rekordo']['matrikulisto'] = $this->Auth->user('id');
			$data['Rekordo']['tempo_de_enskribo'] = date('Y-m-d H:i:s');
		}
		else {
			$this->Rekordo->id = $id;
		}
		$data['Rekordo']['id'] = $this->Rekordo->id;
		
		$kategorioj = explode(',', $data['Rekordo']['kategorioj']);
		foreach($kategorioj as $kategorio) {
			$data['Kategorio'][]['id'] = $kategorio;
		}
		unset($data['Rekordo']['kategorioj']);

// die(json_encode($data));
		
		if ($rezulto['success'] = $this->Rekordo->saveAssociated($data)) {
			$rezulto['msg'] = __('la rekordo estas konservitaj');
		}
		else {
			$rezulto['msg'] = __('konservo fiaskis');
		}
	}
	
	$this->set('rezulto', $rezulto);
	$this->render('rezulto');
}


public function stokuGrupoKontribuoj() {
	$rezulto = array();
	$rezulto['success'] = true;
	$rezulto['msg'] = __('la rekordo estas konservitaj');
	
	if ($this->request->is(array('post', 'put'))) {

		$partoprenantoj = json_decode($this->request->data['partoprenantoj'], true);
		
		foreach($partoprenantoj as $partoprenanto) {
			if($partoprenanto['konto']) {
				$this->Rekordo->create();
				
				$data['Rekordo'] = $partoprenanto;
				$data['Rekordo']['matrikulisto'] = $this->Auth->user('id');
				$data['Rekordo']['tempo_de_enskribo'] = date('Y-m-d H:i:s');
				$data['Rekordo']['konto_id'] = $partoprenanto['konto'];
				
				foreach($data['Rekordo']['kategorioj'] as $kategorio) {
					$data['Kategorio'][]['id'] = $kategorio;
				}

				$data['Rekordo'] = array_merge($this->request->data, $data['Rekordo']);
				unset($data['Rekordo']['id']);
				unset($data['Rekordo']['kategorioj']);
				unset($data['Rekordo']['rowexpander']);
				unset($data['Rekordo']['partoprenantoj']);
	
				if ($rezulto['success']) {
					$rezulto['success'] = $this->Rekordo->saveAssociated($data);
				}
				else {
					$rezulto['msg'] = __('konservo provo malsukcesis');
				}
			}
		}
	}
	
	$this->set('rezulto', $rezulto);
	$this->render('rezulto');
}


public function vidu($id = null) {
	if (!$id) {
		throw new NotFoundException(__('nevalidan rekordon'));
	}

	$rekordo = $this->Rekordo->findById($id);
	if (!$rekordo) {
		throw new NotFoundException(__('nevalidan rekordon'));
	}
	$this->set('rekordo', $rekordo);
}


}
?>