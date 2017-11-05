<?
/**********************************************
Kontribuo
de Kay-Egil Hauan
**********************************************/

if(!isset($url)) 			$url			= $this->Html->url(array(
												'controller' => 'KategorioPetskribojn',
												'action' => 'akiruArbon',
												'ext'	=>	'json'
											));
if(!isset($title)) 			$title			= false;

?>

Ext.define('TreeModel', {
	extend: 'Ext.data.Model',
	fields: [
		{name: 'id', mapping: 'KategorioPetskribon.id'},
		{name: 'kategorio_id', mapping: 'Kategorio.id'},
		{name: 'nomo', mapping: 'Kategorio.nomo'},
		{name: 'checked', type: 'boolean'}, 
		{name: 'leaf', type: 'boolean'}
	]
});

Ext.define('kontribuo.tree.KategoriojnSelektadoArbo', {
	extend: 'Ext.tree.Panel',
	requires: ['Ext.data.TreeStore'],
	xtype: 'check-tree',

	displayField: 'nomo',
	rootVisible: false,
	useArrows: true,
	border: false,
	frame: false,
	title: '<? echo $title;?>',
	width: '30%',
	
	initComponent: function(){
		Ext.apply(this, {
			store: Ext.create('Ext.data.TreeStore', {
				model: 'TreeModel',
				proxy: {
					type: 'ajax',
					url: '<? echo $url;?>'
				}
			})
		});
		this.callParent();
	}
})

