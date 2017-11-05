<?
	$this->start('title');
	echo __('rekordoj');
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
	echo $this->element('kontribuo.navigado.Navigado', array());

	echo $this->element('kontribuo.data.Store', array(
		'pageSize'	=>	200,
		'url'		=>	$this->Html->url(array(
			'action' => 'index',
			'ext'	=>	'json'
		))
	));

	echo $this->element('kontribuo.grid.Panel', array(
		'var'	=> "krado",
		'title'		=> __("rekordoj")
	));
	
	echo $this->element('kontribuo.funkcioj.Forvisu');
	
?>

Ext.define('Rekordo', {
	extend: 'Ext.data.Model',
	idProperty: 'id',
	fields: [	//	http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
				//	type: auto | string | int | float | boolean | date
		{name: 'id', type: 'int', mapping: 'Rekordo.id'},
		{name: 'konto_id', type: 'int', mapping: 'Rekordo.konto_id'},
		{name: 'konto', type: 'string', mapping: 'Konto.nomo'},
		{name: 'komencis_tempo', type: 'date', dateFormat: 'Y-m-d H:i:s', mapping: 'Rekordo.komencis_tempo'},
		{name: 'finis_tempo', type: 'date', dateFormat: 'Y-m-d H:i:s', mapping: 'Rekordo.finis_tempo'},
		{name: 'kvanto', type: 'float', mapping: 'Rekordo.kvanto'},
		{name: 'speco_id', type: 'int', mapping: 'Rekordo.speco_id'},
		{name: 'speco', type: 'string', mapping: 'Speco.nomo'},
		{name: 'unuo', type: 'string', mapping: 'Speco.unuo'},
		{name: 'priskribo', type: 'string', mapping: 'Rekordo.priskribo'},
		{name: 'detaloj', type: 'string', mapping: 'Rekordo.detaloj'},
		{name: 'matrikulisto', type: 'string', mapping: 'Matrikulisto.uzanto'},
		{name: 'tempo_de_enskribo', type: 'date', dateFormat: 'Y-m-d H:i:s', mapping: 'Rekordo.tempo_de_enskribo'}
	]
});


var stokado = Ext.create('kontribuo.data.Store', {
	model: 'Rekordo'
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
				'var'	=> "konto_id",
				'sortable'	=> true,
				'text'	=> __("konto"),
				'renderColumn'	=>	"konto"
			),
			array(
				'var'	=> "komencis_tempo",
				'sortable'	=> true,
				'text'	=> __("komencis tempo")
			),
			array(
				'var'	=> "finis_tempo",
				'sortable'	=> true,
				'text'	=> __("finis tempo")
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
				'text'	=> __("priskribo")
			),
			array(
				'var'	=> "detaloj",
				'sortable'	=> true,
				'text'	=> __("detaloj")
			),
			array(
				'var'	=> "matrikulisto",
				'sortable'	=> true,
				'text'	=> __("matrikulisto"),
				'renderColumn'	=>	"matrikulisto"
			),
			array(
				'var'	=> "tempo_de_enskribo",
				'sortable'	=> true,
				'text'	=> __("tempo de enskribo")
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
	title: '<? echo __("rekordoj");?>',
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