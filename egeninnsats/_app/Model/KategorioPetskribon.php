<?php
class KategorioPetskribon extends AppModel {

public $actsAs = array(
	'Tree' => array(
		'parent' => 'patro_id',
		'left'  => 'maldekstren',
		'right' => 'dekstren'
	)
);

public $belongsTo = array(
	'Kategorio' => array(
		'className' => 'Kategorio',
		'foreignKey' => 'kategorio_id'
	)
);



public $findMethods = array(
//		'tree' =>  true
);


public function categoryTree() {
	return $this->find('threaded', array(
		'fields' => array('id', 'patro_id', 'Kategorio.id', 'Kategorio.nomo'),
		'order' => array('maldekstren ASC')
	));
}


// Formats a category Tree for ExtJS requirements
/****************************************/
//	$config:	Config array/object with the following possible keys/properties:
//		thread:	(array) Result form find('threaded')
//		checked: (int/array) Categories that should be checked in checktree
//	--------------------------------------
//	return: MultiDimensional array as find('threaded'), but with these added keys:
//	children, checked, leaf and cls:
public function extFormattedCheckTree($thread = null, $config = array()) {
	if($thread === null) {
		$thread = $this->categoryTree();
	}
	settype($config, 'array');
	settype($config['checked'], 'array');
	
	foreach($thread as $index => &$branch) {
		if (isset($branch['children'])) {
			$branch['checked'] = false;
			$branch['expanded'] = false;
			if(in_array($branch['Kategorio']['id'], $config['checked'])) {
				$thread[$index]['expanded'] = true;
				$branch['checked'] = true;
			}
			if(count($branch['children'])) {
				$branch['leaf'] = false;
				$branch['children'] = $this->extFormattedCheckTree($branch['children'], $config);
			}
			else {
				$branch['leaf'] = true;
				$branch['cls'] = 'folder';
			}
		}
	}
	return $thread;
}


}
?>