<?
	$this->start('title');
	echo __('aldonu grupo kontribuoj');
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
	
	echo $this->element('kontribuo.grid.Panel');
	echo $this->element('Kontribuo.form.field.KontoKombo');
	echo $this->element('Kontribuo.form.field.SpecoKombo');
	
	echo $this->element('kontribuo.form.Panel', array(
		'formBind'	=> false,
		'title'		=> __("rekordo"),
		'ricevilo'	=> $this->Html->url(array(
			'action' => 'stokuGrupoKontribuoj',
			'ext'	=>	'json',
			'0'
		)),
		'reveno'	=> $this->Html->url(array(
			'action' => 'index'
		))
	));
	
?>


Ext.define('Partoprenantoj', {
	extend: 'Ext.data.Model',
	idProperty: 'id',
	fields: [	//	http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
				// param: convert, dateFormat, dateReadFormat, dateWriteFormat, defaultValue, mapping, name, persist, serialize, sortDir, sortType, type, useNull
				//	type: auto | string | int | float | boolean | date
		{name: 'id', type: 'int', useNull: true},
		{name: 'konto', type: 'int', useNull: true},
		{name: 'kvanto', type: 'float'},
		{name: 'kategorioj', type: 'auto'},
		{name: 'rowexpander', type: 'string'}
	]
});


var stoko = Ext.create('Ext.data.Store', {
	storeId: 'stoko',
	model: 'Partoprenantoj',
	pageSize: 200
});

stoko.add({}, {});

var kategoriojnSelektadoArbo = Ext.create('kontribuo.tree.KategoriojnSelektadoArbo', {
//	region: 'west',
	width: '100%',
	title: '',
    listeners: {
    	checkchange: function( node, checked, eOpts ) {
			establiKategorio(node, checked, eOpts);
		}
    }
});


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
	if(aktualaRekordo == null) {
		normaKategorioj = kat;
		katlisto.setValue( kategorioPrezentilo );
		stoko.each(function(record) {
			record.set('kategorioj', kat);
			record.set('rowexpander', kategorioPrezentilo);
		});
	}
	else {
		aktualaRekordo.set('kategorioj', kat);
		aktualaRekordo.set('rowexpander', kategorioPrezentilo);
		krado.getPlugin('rowexpander').toggleRow(stoko.indexOf(aktualaRekordo), aktualaRekordo);
	}
	return kat;
}


var aktualaRekordo;
var normaKategorioj = [];


<? echo $this->element('Ext.window.NovaKonto', array(
	'callback' => "kontoKombo.getStore().load({callback: function(records, operation, success) {aktualaRekordo.set('kontoKombo', action.result.data.Konto.id.toString());}});"
));?>


var kontoKombo = Ext.create('Kontribuo.form.field.KontoKombo', {
	formBind: false,
	allowBlank:	false
});


var unuo = Ext.create('Ext.form.field.Display', {
	name: 'unuo'
});


var priskribo = Ext.create('Ext.form.field.Text', {
	allowBlank:	false,
	emptyText:	'<? echo __('priskribo');?>',
	name: 'priskribo',
	width: '90%'
});


var detaloj = Ext.create('Ext.form.field.HtmlEditor', {
	allowBlank:	true,
	labelAlign: 'top',
	fieldLabel:	'<? echo __('detaloj');?>',
	name: 'detaloj',
	
	height: 150,
	width: '90%'
});


var katlisto = Ext.create('Ext.form.field.Display');
var kategorioButono = Ext.create('Ext.button.Button', {
		text: '<? echo __('Elektu kategorioj');?>',
		handler: function(button, event) {
			aktualaRekordo = null;
			kategoriojFenestro.show();
		}
	});


var kategoriojFenestro = Ext.create('Ext.window.Window', {
	closeAction: 'hide',
	width: '50%',
	height: '50%',
//	minWidth: 200,
//	maxWidth: 500,
	title: '<? echo __('Elektu kategorioj');?>',
	items: [kategoriojnSelektadoArbo]
});

var speco = Ext.create('Kontribuo.form.field.SpecoKombo', {
	formBind: false,
	allowBlank:	false,
	margin: '0 5 0 5',
	fieldLabel:	'<? echo __('speco');?>',
	name: 'speco_id',
	width: 250,
	listeners: {
		change: function( kombo, newValue, oldValue, eOpts ) {
			krado.getView().refresh();
		}
	}
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
	items: [komencis_tempo, finis_tempo, speco]
}
var partoprenantoj = Ext.create('Ext.form.field.Hidden', {
	name: 'partoprenantoj'
});


var agadoj = Ext.create('Ext.grid.column.Action', {
	dataIndex: id,
	items: [{
		icon: '<?php echo $this->webroot; ?>img/kategorio.png',
		altText: '<? echo __("Elektu kategorioj")?>',
		tooltip: '<? echo __("Elektu kategorioj")?>',
		handler: function(view, rowIndex, colIndex, item, event, record, row) {
			aktualaRekordo = record;
			kategoriojFenestro.show();
		}
	}]
});

<?
	echo $this->element('Ext.grid.columns', array(
		'kolumnoj'		=> array(
			array(
				'var'	=> "id",
				'hidden' => true,
				'sortable'	=> true,
				'text'	=> __("ensalutilo")
			),
			array(
				'var'	=> "kvanto",
				'editor' => "Ext.create('Ext.form.field.Number', {
					hideTrigger: true,
				})",
				'sortable'	=> true,
				'width' => 70,
				'text'	=> __("kontribuo"),
				'renderer'	=> "if(value) return value + (speco.getValue() ? (' ' + speco.getStore().findRecord('id', speco.getValue()).get('unuo')) : '');"
			),
			array(
				'var'	=> "konto",
				'editor' => "kontoKombo",
				'flex' => 1,
				'sortable'	=> true,
				'text'	=> __("partoprenanto"),
				'renderer'	=> "if(value) return kontoKombo.getStore().findRecord('id', value).get('nomo');"
			),
// 			array(
// 				'var'	=> "kategorioj",
// 				'flex' => 1,
// 				'sortable'	=> false,
// 				'text'	=> __("kategorioj"),
// 				'renderer'	=> "return record.get('rowexpander');"
// 			),
			'agadoj'
		)
	));
	
?>


var krado = Ext.create('kontribuo.grid.Panel', {
	title: '<? echo __("partoprenantoj");?>',
	columns: kolumnoj,
	store: stoko,
	listeners: {
		select: function( krado, record, index, eOpts ) {
			aktualaRekordo = record;
			if(stoko.count() - index == 1) {
				stoko.add({kategorioj: normaKategorioj, rowexpander: katlisto.getValue()});
			}
		}
	},
	buttons: []
});


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
			items: [priskribo, tempo, detaloj, katlisto, kategorioButono, krado, partoprenantoj]
		}
	]
});
panelo.on({
	beforeaction: function( panel, action, eOpts ) {
		var preta = true;
		
		if(action.type == 'submit') {
			var a = [];
			stoko.each(function(record) {
				if(record.data.konto) {
					a.push(record.data);
				}
			})
			partoprenantoj.setValue(Ext.JSON.encode(a));

			if(normaKategorioj.length == 0) {
				preta = false;
				Ext.Msg.confirm('<?=__("neniu kategorioj estas selektitaj");?>',
					'<?=__("Ĉu vi certas ke vi volas submetiĝi tiuj enskriboj sen selektante unu aŭ pli kategorioj?<br>La kategorioj povas esti elektitaj el la maldekstra kolumno.");?>',
					function( butono, teksto, agordoj ) {
						preta = false;
						if(butono == 'yes') {
							panelo.getForm().suspendEvent('beforeaction');
							panelo.getForm().submit();
							panelo.getForm().resumeEvent('beforeaction');
						}
						else {
							kategoriojFenestro.show();
						}
					});
			}
			return preta;
		}
	}
});


var ujo = Ext.create('Ext.container.Viewport', {
		layout: {
			type: 'border',
			align: 'stretch'
		},
		flex: 1,
		items: [
//			kategoriojnSelektadoArbo,
			kontribuo.navigado.Navigado,
			panelo
		]
});

var reen = Ext.create('kontribuo.button.Reen');
priskribo.focus(false, 200);
//panelo.down('buttons').add(reen);
	
});
<?
	$this->end();
?>