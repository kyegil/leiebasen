<?
	$this->start('title');
	echo __('specoj de kontribuo');
	$this->end();
	$this->start('skripto');
?>
Ext.Loader.setConfig('enabled', true); // enable the Ext.Loader
// Ext.Loader.setPath('Ext.ux', "/egeninnsats/js/examples/ux");
// Ext.Loader.setPath('Bancha','Bancha/js'); // path to Bancha files
// Ext.syncRequire('Bancha.Initializer'); // load

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
	echo $this->element('kontribuo.navigado.Navigado', array());

	echo $this->element('kontribuo.data.Store', array(
		'record'	=>	"Speco",
		'pageSize'	=>	200,
		'url'		=>	$this->Html->url(array(
			'action' => 'index',
			'ext'	=>	'json'
		))
	));

	echo $this->element('kontribuo.grid.Panel', array(
		'var'	=> "krado",
		'title'		=> __("specoj de kontribuo")
	));
	
	echo $this->element('kontribuo.funkcioj.Forvisu');
	
?>

Ext.define('Speco', {
	extend: 'Ext.data.Model',
	idProperty: 'id',
	fields: [
		{name: 'id', type: 'float'},
		{name: 'nomo', type: 'string'},
		{name: 'unuo', type: 'string'}
	]
});


var stokado = Ext.create('kontribuo.data.Store', {
	model: 'Speco'
});


var agadoj = Ext.create('Ext.grid.column.Action', {
	dataIndex: id,
	items: [{
		icon: '<?php echo $this->webroot; ?>img/vidu.png',
		tooltip: '<? echo __("vidu")?>',
		handler: function(view, rowIndex, colIndex, item, event, record, row) {
			window.location = '<?php echo $this->Html->url(array('action' => 'vidu')); ?>/' + record.get('id');
		}
	}, {
		icon: '<?php echo $this->webroot; ?>img/sangu.png',
		tooltip: '<? echo __("ŝanĝi ĝin")?>',
		handler: function(view, rowIndex, colIndex, item, event, record, row) {
			window.location = '<?php echo $this->Html->url(array('action' => 'sangu')); ?>/' + record.get('id');
		}
	}, {
		icon: '<?php echo $this->webroot; ?>img/forvisu.png',
		tooltip: '<? echo __("forviŝi")?>',
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
				'sortable'	=> true,
				'text'	=> __("ensalutilo")
			),
			array(
				'var'	=> "nomo",
				'sortable'	=> true,
				'text'	=> __("speco")
			),
			array(
				'var'	=> "unuo",
				'sortable'	=> true,
				'text'	=> __("unuo")
			),
			'agadoj'
		)
	));
	
?>


var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
	clicksToEdit: 1
});

var rowEditing = Ext.create('Ext.grid.plugin.RowEditing', {
	autoCancel: false,
	listeners: {
		beforeedit: function (grid, e, eOpts) {
			return e.column.xtype !== 'actioncolumn';
		},
	},
});

var krado = Ext.create('kontribuo.grid.Panel', {
	title: '<? echo __("specoj de kontribuo");?>',
	columns: kolumnoj,
	store: stokado
});


var ujo = Ext.create('Ext.container.Viewport', {
	items: [krado],
	layout: 'fit'
});

});
<?
	$this->end();
?>