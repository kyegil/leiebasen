<?
/**********************************************
Kontribuo
de Kay-Egil Hauan
**********************************************/

if(!isset($layout)) 		$layout			= "border";

?>

Ext.define('kontribuo.container.Viewport', {
//	autoScroll: true,
	extend: 'Ext.container.Viewport',
	layout: '<?=$layout?>'
});

