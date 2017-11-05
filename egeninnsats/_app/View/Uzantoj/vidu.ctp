<?
	$this->start('title');
	echo __('uzanto');
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

	echo $this->element('kontribuo.panel.Panel', array(
		'url'	=> $this->Html->url(array(
			'action' => 'akiru',
			$uzanto['Uzanto']['id']
		)),
		'title'		=> __("uzanto")
	));
	
	echo $this->element('kontribuo.container.Viewport', array(
		'items'	=> array(
			'panelo'
		)
	));

?>
	var panelo = Ext.create('kontribuo.panel.Panel');

	var ujo = Ext.create('kontribuo.container.Viewport', {
		items: [panelo],
		layout: 'fit'
	});
	
 	var reen = Ext.create('kontribuo.button.Reen');

	panelo.down('toolbar').add(reen);
	
	
});
<?
	$this->end();
?>