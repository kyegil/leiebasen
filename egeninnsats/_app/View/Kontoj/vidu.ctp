<?
	$this->start('title');
	echo __('konto');
	$this->end();

	$this->start('skripto');
?>
Ext.Loader.setConfig('enabled', true); // enable the Ext.Loader

Ext.require([
 	'Ext.data.*',
 	'Ext.form.field.*',
    'Ext.layout.container.Border',
 	'Ext.grid.*'
]);

Ext.onReady(function() {

    Ext.tip.QuickTipManager.init();
	Ext.form.Field.prototype.msgTarget = 'side';
	Ext.Loader.setConfig({enabled:true});
	
<?
	echo $this->element('kontribuo.navigado.Navigado', array(
		'uzanto' => $this->Session->read('Auth.User')
	));
	echo $this->element('kontribuo.data.Store', array(
		'pageSize'	=>	200,
		'url'		=>	$this->Html->url(array(
			'action' => 'akiruRekordojn',
			'ext'	=>	'json',
			$konto['Konto']['id']
		))
	));
	echo $this->element('kontribuo.tree.KategoriojnSelektadoArbo');
	echo $this->element('Kontribuo.form.field.SpecoKombo');
	echo $this->element('kontribuo.form.field.Date');
	echo $this->element('kontribuo.grid.Panel', array(
		'title'		=> __("rekordoj")
	));
	echo $this->element('kontribuo.funkcioj.Forvisu', array(
		'url'	=> $this->Html->url(array(
			'controller' =>	'rekordoj',
			'action' => 'forvisu',
			'ext'	=>	'json'
		))
	));
	echo $this->element('kontribuo.button.Reen', array(
		'uri'	=> $this->Html->url(array(
			'action' => 'index'
		))
	));
	echo $this->element('kontribuo.container.Viewport');

?>

Ext.define('Rekordo', {
	extend: 'Ext.data.Model',
	idProperty: 'id',
	fields: [	//	http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
				//	type: auto | string | int | float | boolean | date
		{name: 'id', type: 'int', mapping: 'Rekordo.id'},
		{name: 'komencis_tempo', type: 'date', dateFormat: 'Y-m-d H:i:s', mapping: 'Rekordo.komencis_tempo'},
		{name: 'komencis_tempo_formatita', type: 'string', mapping: 'Rekordo.komencis_tempo_formatita'},
		{name: 'finis_tempo', type: 'date', dateFormat: 'Y-m-d H:i:s', mapping: 'Rekordo.finis_tempo'},
		{name: 'finis_tempo_formatita', type: 'string', mapping: 'Rekordo.finis_tempo_formatita'},
		{name: 'kvanto', type: 'float', mapping: 'Rekordo.kvanto'},
		{name: 'speco_id', type: 'int', mapping: 'Rekordo.speco_id'},
		{name: 'speco', type: 'string', mapping: 'Speco.nomo'},
		{name: 'unuo', type: 'string', mapping: 'Speco.unuo'},
		{name: 'priskribo', type: 'string', mapping: 'Rekordo.priskribo'},
		{name: 'rowexpander', type: 'string', mapping: 'html'},
		{name: 'matrikulisto', type: 'string', mapping: 'Matrikulisto.nomo'},
		{name: 'tempo_de_enskribo', type: 'date', dateFormat: 'Y-m-d H:i:s', mapping: 'Rekordo.tempo_de_enskribo'},
		{name: 'tempo_de_enskribo_formatita', type: 'string', mapping: 'Rekordo.tempo_de_enskribo_formatita'}
	]
});

var stoko = Ext.create('Ext.data.Store', {
	storeId: 'stoko',
	model: 'Rekordo',
	pageSize: 200,
	remoteSort: true,
	proxy: {
		type: 'ajax',
		simpleSortMode: true,
		url: '<? echo $this->Html->url(array(
			'action' => 'akiruRekordojn',
			'ext'	=>	'json',
			$konto['Konto']['id']
		));?>',
		reader: {
			type: 'json',
			root: 'data'
		}
	},
	sorters: [{
		property: 'id',
		direction: 'ASC'
	}],
	autoLoad: true
});


var komencoFiltrilo = Ext.create('kontribuo.form.field.Date', {
	emptyText: '<? echo __('laŭ dato')?>',
	name: 'lau',
	listeners: {
		change: function() {
			sarguRekordo();
		}
	}
});
var finoFiltrilo = Ext.create('kontribuo.form.field.Date', {
	emptyText: '<? echo __('antaŭ dato')?>',
	name: 'antau',
	listeners: {
		change: function() {
			sarguRekordo();
		}
	}
});
var specoFiltrilo = Ext.create('Kontribuo.form.field.SpecoKombo', {
	allowBlank: true,
	listeners: {
		change: function() {
			sarguRekordo();
		}
	}
});
var kategorioArbo = Ext.create('kontribuo.tree.KategoriojnSelektadoArbo', {
	region: 'east',
    listeners: {
    	checkchange: function( node, checked, eOpts ) {
    		sarguRekordo();
		}
    }
});
var serco = Ext.create('Ext.form.field.Text', {
	emptyText: '<? echo __('serĉo')?>',
	name: 'serco',
	listeners: {
		change: function() {
			sarguRekordo();
		}
	}
});


function sarguRekordo() {
	var records = kategorioArbo.getView().getChecked();
	var kategorioj = [];
			   
	Ext.Array.each(records, function(rec){
		var v = rec.get('kategorio_id');
		if(!Ext.Array.contains(kategorioj, v)) {
			kategorioj.push(v);
		}
	});

	stoko.load({
		params: {
			komenco: komencoFiltrilo.getValue(),
			fino: finoFiltrilo.getValue(),
			speco: specoFiltrilo.getValue(),
			serco: serco.getValue(),
			kategorioj: kategorioj.join(',')
		}
	});
	return;
}


var agadoj = Ext.create('Ext.grid.column.Action', {
	dataIndex: id,
	width: 70,
	items: [{
		icon: '<?php echo $this->Html->url('/img/sangu.png'); ?>',
		tooltip: '<? echo __("ŝanĝu ĝin")?>',
		handler: function(view, rowIndex, colIndex, item, event, record, row) {
			window.location = '<?php echo $this->Html->url(array('controller' => 'rekordoj', 'action' => 'sangu')); ?>/' + record.get('id');
		}
	}, {
		icon: '<?php echo $this->Html->url('/img/forvisu.png'); ?>',
		tooltip: '<? echo __("forviŝu")?>',
		handler: function(view, rowIndex, colIndex, item, event, record, row) {
			kontribuo.funkcioj.Forvisu(record);
		}
	}]
});

<?
	echo $this->element('Ext.grid.columns', array(
		'kolumnoj'		=> array(
			array(
				'var'	=> "id",
				'width' => 50,
				'sortable'	=> true,
				'text'	=> __("ensalutilo")
			),
			array(
				'var'	=> "komencis_tempo",
				'width' => 100,
				'sortable'	=> true,
				'text'	=> __("komencis tempo"),
				'renderer'	=> "return record.get('komencis_tempo_formatita');"
			),
			array(
				'var'	=> "finis_tempo",
				'width' => 100,
				'sortable'	=> true,
				'text'	=> __("finis tempo"),
				'renderer'	=> "return record.get('finis_tempo_formatita');"
			),
			array(
				'var'	=> "kvanto",
				'sortable'	=> true,
				'text'	=> __("kvanto"),
				'renderer'	=> "return record.get('kvanto') + ' ' + record.get('unuo');"
			),
			array(
				'var'	=> "speco_id",
				'sortable'	=> true,
				'text'	=> __("speco"),
				'renderColumn'	=> "speco"
			),
			array(
				'var'	=> "priskribo",
				'sortable'	=> true,
				'flex'	=> 1,
				'text'	=> __("priskribo")
			),
			array(
				'var'	=> "matrikulisto",
				'sortable'	=> true,
				'hidden' => true,
				'text'	=> __("matrikulisto"),
				'renderColumn'	=>	"matrikulisto"
			),
			array(
				'var'	=> "tempo_de_enskribo",
				'width' => 100,
				'sortable'	=> true,
				'hidden' => true,
				'text'	=> __("tempo de enskribo"),
				'renderer'	=> "return record.get('tempo_de_enskribo_formatita');"
			),
			'agadoj'
		)
	));
	
?>


var krado = Ext.create('kontribuo.grid.Panel', {
	title: '',
	region:'center',
	tbar: [komencoFiltrilo, finoFiltrilo, specoFiltrilo, serco],
	columns: kolumnoj,
	store: stoko,
	buttons: [{
		text: '<? echo __("registru novan kontribuo");?>',
		handler: function(view, rowIndex, colIndex, item, event, record, row) {
			window.location = '<? echo $this->Html->url(array(
				'controller' => 'rekordoj',
				'action' => 'aldonu'
			));?>';
		}
	}]
});


var ujo = Ext.create('kontribuo.container.Viewport', {
	layout: 'border',
	items: [
		kontribuo.navigado.Navigado,
		krado,
		kategorioArbo
	]
});

var reen = Ext.create('kontribuo.button.Reen');

// ujo.down('toolbar').add(reen);


});

<?
	$this->end();
?>