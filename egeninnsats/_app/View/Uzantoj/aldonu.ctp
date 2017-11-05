<?
	$this->start('title');
	echo __('aldonu uzanto');
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

	echo $this->element('kontribuo.button.Reen', array(
		'uri'	=> $this->Html->url(array(
			'action' => 'index'
		))
	));

	echo $this->element('kontribuo.form.Panel', array(
		'formBind'	=> false,
		'title'		=> __("uzanto"),
		'ricevilo'	=> $this->Html->url(array(
			'action' => 'stoku',
			'ext'	=>	'json',
			'0'
		)),
		'reveno'	=> $this->Html->url(array(
			'action' => 'index'
		))
	));
	
?>


var uzanto = Ext.create('Ext.form.field.Text', {
	allowBlank:	false,
	fieldLabel:	'<? echo __('uzanto');?>',
	name: 'uzanto'
});


var pasvorto = Ext.create('Ext.form.field.Text', {
	allowBlank:	false,
	fieldLabel:	'<? echo __('pasvorto');?>',
	name: 'pasvorto'
});


var rolo = Ext.create('Ext.form.field.Text', {
	allowBlank:	false,
	fieldLabel:	'<? echo __('rolo');?>',
	name: 'rolo'
});


var panelo = Ext.create('kontribuo.form.Panel', {
	items: [uzanto, pasvorto, rolo]
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