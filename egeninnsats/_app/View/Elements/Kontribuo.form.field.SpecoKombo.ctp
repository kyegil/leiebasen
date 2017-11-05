<?
/**********************************************
Kontribuo
de Kay-Egil Hauan
**********************************************/

?>

Ext.define('Kontribuo.form.field.SpecoKombo', {
	extend: 'Ext.form.field.ComboBox',
	
	name: 'speco',
	width: 200,
	emptyText: '<? echo __('elektu specon');?>',

	queryMode: 'remote',
	valueField: 'id',
	displayField: 'nomo',
	formBind: true,
	
	store: Ext.create('Ext.data.JsonStore', {
		storeId: 'stokoDeSpecoj',
	
		proxy: {
			type: 'ajax',
			url: '<? echo $this->Html->url(array(
				'controller' => 'specoj',
				'action' => 'index',
				'ext'	=>	'json'
			));?>',
			reader: {
				type: 'json',
				root: 'data',
				idProperty: 'id'
			}
		},
		autoLoad: true,
		fields: [
			{name:'id', type: 'string', mapping: 'Speco.id'},
			{name:'nomo', type:'string', mapping: 'Speco.nomo'},
			{name:'unuo', type:'string', mapping: 'Speco.unuo'}
		]
	}),

	allowBlank: false,
	forceSelection: true,
	selectOnFocus: true,
	typeAhead: true,
	
	matchFieldWidth: false,
	minChars: 1,
	queryDelay: 1000,
	listConfig: {
		loadingText: '<? echo __('serĉo en progreso');?>',
		emptyText: '<? echo __('neniu sukcesoj');?>',
		maxHeight: 600,
		width: 600
	}
	
});

