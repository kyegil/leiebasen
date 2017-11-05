<?
	$this->start('title');
	echo __('speco de kontribuo');
	$this->end();
	$this->start('skripto');
?>
Ext.Loader.setConfig('enabled', true); // enable the Ext.Loader
// Ext.Loader.setPath('Ext.ux', "js/examples/ux");
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

	echo $this->element('kontribuo.button.Reen', array(
		'uri'	=> $this->Html->url(array(
			'action' => 'index'
		))
	));

	echo $this->element('kontribuo.form.Panel', array(
		'title'		=> __("speco de kontribuo"),
		'items'		=> array('speco', 'unuo'),
		'ricevilo'	=> $this->Html->url(array(
			'action' => 'stoku',
			'ext'	=>	'json',
			$speco['Speco']['id']
		)),
		'reveno'	=> $this->Html->url(array(
			'action' => 'index'
		))
	));
	
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


var speco = Ext.create('Ext.form.field.Text', {
	allowBlank:	false,
	fieldLabel:	'<? echo __('nomo de la speco');?>',
	name: 'nomo'
});


var unuo = Ext.create('Ext.form.field.Text', {
	allowBlank:	false,
	fieldLabel:	'<? echo __('mezurunuo');?>',
	name: 'unuo'
});


var panelo = Ext.create('kontribuo.form.Panel', {
	items: [speco, unuo]
});


panelo.getForm().load({
	url: '<? echo $this->Html->url(array(
			'action' => 'sargiFormo',
			'ext'	=>	'json',
			$speco['Speco']['id']
		))?>',
	waitMsg: '<? echo __('formo estas estante ŝarĝita');?>'
});


	var ujo = Ext.create('Ext.container.Viewport', {
		items: [panelo],
		layout: 'fit'
	});
	
	var reen = Ext.create('kontribuo.button.Reen');

	panelo.down('toolbar').add(reen);
	
});
<?
	$this->end();
?>