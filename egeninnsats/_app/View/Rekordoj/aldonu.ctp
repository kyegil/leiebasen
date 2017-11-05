<?
	$this->start('title');
	echo __('aldonu rekordo');
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

	echo $this->element('kontribuo.tree.KategoriojnSelektadoArbo', array(
		'url'	=> $this->Html->url(array(
			'controller' => 'Rekordoj',
			'action' => 'akiruArbon',
			'ext'	=>	'json',
			0
		))
	));
	
	echo $this->element('Kontribuo.form.field.KontoKombo');
	echo $this->element('Kontribuo.form.field.SpecoKombo');
	
	echo $this->element('kontribuo.form.Panel', array(
		'formBind'	=> false,
		'title'		=> __("rekordo"),
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


var kategoriojnSelektadoArbo = Ext.create('kontribuo.tree.KategoriojnSelektadoArbo', {
	region: 'west',
    listeners: {
    	checkchange: function( node, checked, eOpts ) {
			establiKategorio(node, checked, eOpts);
		}
    }
});
kategoriojnSelektadoArbo.getStore().on({
	load: function (){
		var records = kategoriojnSelektadoArbo.getView().getChecked();
		var kat = [];
		var kategorioPrezentilo = '<ul class="kategorioEtikedo">';
				   
		Ext.Array.each(records, function(rec){
			var katid = rec.get('kategorio_id');
			var katnomo = rec.get('nomo');
			if(!Ext.Array.contains(kat, katid)) {
				kat.push(katid);
				kategorioPrezentilo += '<li>' + katnomo + '</li>';
			}
		});
		kategorioPrezentilo += '</ul>';
		kategorioj.setValue(kat.join(','));
//		katlisto.update( kategorioPrezentilo, loadScripts = true, callback = null);
		katlisto.setValue( kategorioPrezentilo );
	}
})

function establiKategorio(node, checked, eOpts) {
	if(checked) {
		var rootNode = node.getOwnerTree().getRootNode();

		var parentCheck = function(n) {
			var p = n.parentNode;
			if(p !== rootNode) {
				p.set('checked', true);
				parentCheck(p);
			}
		};
		parentCheck(node);
		
	}
	var records = kategoriojnSelektadoArbo.getView().getChecked();
	var kat = [];
	var kategorioPrezentilo = '<ul class="kategorioEtikedo">';
			   
	Ext.Array.each(records, function(rec){
		var katid = rec.get('kategorio_id');
		var katnomo = rec.get('nomo');
		if(!Ext.Array.contains(kat, katid)) {
			kat.push(katid);
			kategorioPrezentilo += '<li>' + katnomo + '</li>';
		}
	});
	kategorioPrezentilo += '</ul>';
	kategorioj.setValue(kat.join(','));
//	katlisto.update( kategorioPrezentilo, loadScripts = true, callback = null);
	katlisto.setValue( kategorioPrezentilo );
	return kat;
}


<? echo $this->element('Ext.window.NovaKonto', array(
	'callback' => "kontoKombo.getStore().load({callback: function(records, operation, success) {kontoKombo.setValue(action.result.data.Konto.id.toString());}});"
));?>


var kontoKombo = Ext.create('Kontribuo.form.field.KontoKombo', {
	formBind: false,
	hideTrigger: false,
	allowBlank:	false,
	fieldLabel:	'<? echo __('konto');?>',
	name: 'konto_id',
	width: 500
});


var unuo = Ext.create('Ext.form.field.Display', {
	name: 'unuo'
});


var priskribo = Ext.create('Ext.form.field.Text', {
	allowBlank:	false,
	fieldLabel:	'<? echo __('priskribo');?>',
	name: 'priskribo',
	width: 500
});


var detaloj = Ext.create('Ext.form.field.HtmlEditor', {
	allowBlank:	true,
	labelAlign: 'top',
	fieldLabel:	'<? echo __('detaloj');?>',
	name: 'detaloj',
	
	height: 150,
	width: 500
});


var kategorioj = Ext.create('Ext.form.field.Hidden', {
	allowBlank:	true,
	name: 'kategorioj'
});


// var katlisto = Ext.create('Ext.panel.Panel', {
// 	height: 150,
// 	width: 300,
// 	autoScroll: true,
// 	border: false,
// 	padding: 5,
// 	title:	'<? echo __('kategorioj');?>'
// });
// 
var katlisto = Ext.create('Ext.form.field.Display');


var speco = Ext.create('Kontribuo.form.field.SpecoKombo', {
	formBind: false,
	allowBlank:	false,
	margin: '0 5 0 5',
	fieldLabel:	'<? echo __('speco');?>',
	name: 'speco_id'
});

var komencis_tempo = Ext.create('Ext.form.field.Date', {
	allowBlank:	false,
	margin: '0 5 0 5',
	fieldLabel:	'<? echo __('komencis tempo');?>',
	value: new Date,
	name: 'komencis_tempo',
	format: 'd.m.Y',
	altFormats: 'Y-m-d|Y-m-d H:i:s',
	width: 250,
	submitFormat: 'Y-m-d'
});
var finis_tempo = Ext.create('Ext.form.field.Date', {
	allowBlank:	true,
	margin: '0 5 0 5',
	fieldLabel:	'<? echo __('finis tempo');?>',
	value: new Date,
	name: 'finis_tempo',
	format: 'd.m.Y',
	altFormats: 'Y-m-d|Y-m-d H:i:s',
	submitFormat: 'Y-m-d'
});
var tempo = {
	xtype: 'fieldset',
	border: false,
	layout: 'column',
	items: [komencis_tempo, finis_tempo]
}


var kvanto = Ext.create('Ext.form.field.Number', {
	allowBlank:	false,
	fieldLabel:	'<? echo __('kvanto');?>',
	name: 'kvanto',
	minValue: 0,
	hideTrigger: true
});
var kvantoKajSpeco = {
	xtype: 'fieldset',
	border: false,
	layout: 'column',
	items: [speco, kvanto, unuo]
}


var panelo = Ext.create('kontribuo.form.Panel', {
	layout: {
		type: 'fit',
		align: 'stretch'
	},
	title: '',
	items: [
		{
			xtype: 'fieldset',
			border: false,
			items: [kontoKombo, priskribo, tempo, kvantoKajSpeco, detaloj, katlisto, kategorioj]
		}
	]
});


var ujo = Ext.create('Ext.container.Viewport', {
		layout: {
			type: 'border',
			align: 'stretch'
		},
		flex: 1,
		items: [
			kategoriojnSelektadoArbo,
			kontribuo.navigado.Navigado,
			panelo
		]
});

var reen = Ext.create('kontribuo.button.Reen');

// panelo.down('toolbar').add(reen);
	
});
<?
	$this->end();
?>