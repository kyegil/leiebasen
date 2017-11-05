<?
	$this->start('title');
	echo __('kontoj');
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
			'action' => 'index',
			'ext'	=>	'json'
		))
	));

	echo $this->element('kontribuo.container.Viewport');

	echo $this->element('kontribuo.grid.Panel', array(
		'var'	=> "krado",
		'title'		=> __("kontoj")
	));
	
	echo $this->element('kontribuo.funkcioj.Forvisu');
	
?>

Ext.define('Konto', {
	extend: 'Ext.data.Model',
	idProperty: 'id',
	fields: [	//	http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
				//	type: auto | string | int | float | boolean | date
		{name: 'id', type: 'int', mapping: 'Konto.id'},
		{name: 'nomo', type: 'string', mapping: 'Konto.nomo'},
		{name: 'mastro', type: 'string', mapping: 'Mastro.nomo'}
	]
});


var stoko = Ext.create('kontribuo.data.Store', {
	model: 'Konto'
});


var agadoj = Ext.create('Ext.grid.column.Action', {
	dataIndex: id,
	width: 70,
	items: [{
		icon: '<?php echo $this->Html->url('/img/vidu.png'); ?>',
		tooltip: '<? echo __("vidu")?>',
		handler: function(view, rowIndex, colIndex, item, event, record, row) {
			window.location = '<?php echo $this->Html->url(array('action' => 'vidu')); ?>/' + record.get('id');
		}
	}, {
		icon: '<?php echo $this->Html->url('/img/sangu.png'); ?>',
		tooltip: '<? echo __("ŝanĝu konton")?>',
		handler: function(view, rowIndex, colIndex, item, event, record, row) {
			window.location = '<?php echo $this->Html->url(array('action' => 'sangu')); ?>/' + record.get('id');
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
				'var'	=> "nomo",
				'sortable'	=> true,
				'flex'	=> 1,
				'text'	=> __("nomo")
			),
			array(
				'var'	=> "mastro",
				'sortable'	=> true,
				'flex'	=> 1,
				'text'	=> __("mastro")
			),
			'agadoj'
		)
	));
	
?>


var krado = Ext.create('kontribuo.grid.Panel', {
	title: '',
	region:'center',
	columns: kolumnoj,
	store: stoko
});


var ujo = Ext.create('kontribuo.container.Viewport', {
	items: [
		kontribuo.navigado.Navigado,
		krado
	]
});

});
<?
	$this->end();
?>