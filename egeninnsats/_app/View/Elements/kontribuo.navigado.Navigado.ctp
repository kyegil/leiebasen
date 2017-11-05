<?
/**********************************************
Kontribuo
de Kay-Egil Hauan
**********************************************/

if(!isset($uzanto))			$uzanto			= array('konto_id' => 0);

?>

var kontribuo = kontribuo || {};
kontribuo.navigado = kontribuo.navigado || {};

kontribuo.navigado.Kontribuo = Ext.create('Ext.menu.Menu', {	
	id: 'kontribuo',
	items: [
		{
			text: '<? echo __('hejmo')?>',
			handler: function() {
				window.location = '<?=$this->Html->url(array(
					'controller' =>	'kontoj',
					'action' => 'vidu',
					$uzanto['konto_id']
				));?>';
			}
		},
		{
			text: '<? echo __('listo de kontoj')?>',
			handler: function() {
				window.location = '<?=$this->Html->url(array(
					'controller' =>	'kontoj',
					'action' => 'index'
				));?>';
			}
		},
	<?if($uzanto['konto_id']):?>
		{
			text: '<? echo __('mia kontribuo')?>',
			handler: function() {
				window.location = '<?=$this->Html->url(array(
					'controller' =>	'kontoj',
					'action' => 'vidu',
					$uzanto['konto_id']
				));?>';
			}
		},
	<?endif;?>
		{
			text: '<? echo __('registru kontribuo')?>',
			handler: function() {
				window.location = '<?=$this->Html->url(array(
					'controller' =>	'rekordoj',
					'action' => 'aldonu'
				));?>';
			}
		},
		{
			text: '<? echo __('aliĝu grupon kontribuo')?>',
			handler: function() {
				window.location = '<?=$this->Html->url(array(
					'controller' =>	'rekordoj',
					'action' => 'aldonuGrupoKontribuoj'
				));?>';
			}
		},
		{
			text: '<? echo __('eliru')?>',
			handler: function() {
				window.location = '<?=$this->Html->url(array(
					'controller' =>	'uzantoj',
					'action' => 'elsalutu'
				));?>';
			}
		}
	]
});


kontribuo.navigado.Navigado = Ext.create('Ext.toolbar.Toolbar', {
//	renderTo: 'navigado',
	region: 'north',
	items: [{
		text:'<? echo __('kontribuo')?>',
		hideOnClick: false,
		menu: kontribuo.navigado.Kontribuo
	}]
});

