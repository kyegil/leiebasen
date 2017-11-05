<?
	$this->start('title');
	echo __('uzantoj');
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
		'record'	=>	"Uzanto",
		'pageSize'	=>	200,
		'url'		=>	$this->Html->url(array(
			'action' => 'index',
			'ext'	=>	'json'
		))
	));

	echo $this->element('kontribuo.grid.Panel', array(
		'var'	=> "krado",
		'title'		=> __("uzantoj")
	));
	
	echo $this->element('kontribuo.funkcioj.Forvisu');
	
?>

Ext.define('Uzanto', {
	extend: 'Ext.data.Model',
	idProperty: 'id',
	fields: [	//	http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
				//	type: auto | string | int | float | boolean | date
		{name: 'id', type: 'int'},
		{name: 'uzanto', type: 'string'},
		{name: 'rolo', type: 'string'},
		{name: 'tempo_de_kreo', type: 'date', dateFormat: 'Y-m-d H:i:s'},
		{name: 'konto_id', type: 'int'},
		{name: 'tempozono', type: 'string'}
	]
});


var stokado = Ext.create('kontribuo.data.Store', {
	model: 'Uzanto'
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
				'var'	=> "uzanto",
				'sortable'	=> true,
				'text'	=> __("uzanto")
			),
			array(
				'var'	=> "rolo",
				'sortable'	=> true,
				'text'	=> __("rolo")
			),
			array(
				'var'	=> "tempo_de_kreo",
				'sortable'	=> true,
				'text'	=> __("tempo de kreo")
			),
			array(
				'var'	=> "konto_id",
				'sortable'	=> true,
				'text'	=> __("ĉefa konto")
			),
			array(
				'var'	=> "tempozono",
				'sortable'	=> true,
				'text'	=> __("tempozono")
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
	title: '<? echo __("uzantoj");?>',
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