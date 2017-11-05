<?
$this->start('title');
echo __('rekordo');
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
			$rekordo['Rekordo']['id']
		))
	));
	
	echo $this->element('Kontribuo.form.field.KontoKombo');
	
	echo $this->element('Kontribuo.form.field.SpecoKombo');
	
	echo $this->element('kontribuo.form.Panel', array(
		'title'		=> __("rekordo"),
		'ricevilo'	=> $this->Html->url(array(
			'action' => 'stoku',
			'ext'	=>	'json',
			$rekordo['Rekordo']['id']
		)),
		'reveno'	=> $this->Html->url(array(
			'action' => 'index'
		))
	));
	
	echo $this->element('kontribuo.container.Viewport');

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
		var katrender = '<ul class="kategorioEtikedo">';
				   
		Ext.Array.each(records, function(rec){
			var katid = rec.get('kategorio_id');
			var katnomo = rec.get('nomo');
			if(!Ext.Array.contains(kat, katid)) {
				kat.push(katid);
				katrender += '<li>' + katnomo + '</li>';
			}
		});
		katrender += '</ul>';
		kategorioj.setValue(kat.join(','));
		katlisto.update( katrender, loadScripts = true, callback = null);
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
	var katrender = '<ul class="kategorioEtikedo">';
			   
	Ext.Array.each(records, function(rec){
		var katid = rec.get('kategorio_id');
		var katnomo = rec.get('nomo');
		if(!Ext.Array.contains(kat, katid)) {
			kat.push(katid);
			katrender += '<li>' + katnomo + '</li>';
		}
	});
	katrender += '</ul>';
	kategorioj.setValue(kat.join(','));
	katlisto.update( katrender, loadScripts = true, callback = null);
	return kat;
}




var konto = Ext.create('Kontribuo.form.field.KontoKombo', {
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
	fieldLabel:	'<? echo __('detaloj');?>',
	name: 'detaloj',
	
	height: 150,
	width: 500
});


var kategorioj = Ext.create('Ext.form.field.Hidden', {
	allowBlank:	true,
	name: 'kategorioj'
});


var katlisto = Ext.create('Ext.panel.Panel', {
	height: 150,
	width: 300,
	autoScroll: true,
	border: false,
	padding: 5,
	title:	'<? echo __('kategorioj');?>'
});


var komencis_tempo = Ext.create('Ext.form.field.Date', {
	allowBlank:	false,
	fieldLabel:	'<? echo __('komencis tempo');?>',
//	value: new Date,
	name: 'komencis_tempo',
	format: 'd.m.Y',
	altFormats: 'Y-m-d|Y-m-d H:i:s',
	submitFormat: 'Y-m-d'
});
var finis_tempo = Ext.create('Ext.form.field.Date', {
	allowBlank:	true,
	fieldLabel:	'<? echo __('finis tempo');?>',
//	value: new Date,
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
var speco = Ext.create('Kontribuo.form.field.SpecoKombo', {
	allowBlank:	false,
	fieldLabel:	'<? echo __('speco');?>',
	name: 'speco_id'
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
	disabled: true,
//	width: '100%',
	items: [
		{
			xtype: 'fieldset',
			items: [konto, priskribo, tempo, kvantoKajSpeco, detaloj, kategorioj]
		}
	]
});


panelo.getForm().load({
	url: '<? echo $this->Html->url(array(
			'action' => 'sarguFormo',
			'ext'	=>	'json',
			$rekordo['Rekordo']['id']
		))?>',
	waitMsg: '<? echo __('formo estas estante ŝarĝita');?>',
	success: function(form, action) {
		konto.getStore().load();		
		speco.getStore().load(function(records, operation, success) {
			panelo.enable();
		});
	}
});


var ujo = Ext.create('Ext.container.Viewport', {
	layout: {
		type: 'border',
		align: 'stretch'
	},
	flex: 1,
	items: [{
			region: 'west',
			items: [katlisto, kategoriojnSelektadoArbo]
		},
		kontribuo.navigado.Navigado,
		panelo
	]
});

var reen = Ext.create('kontribuo.button.Reen');

// 	ujo.down('toolbar').add(reen);

	
});

<?
$this->end();
?>