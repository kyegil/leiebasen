<?
/**********************************************
Kontribuo
de Kay-Egil Hauan
**********************************************/

if(!isset($uri)) 			$uri			= "";

?>

Ext.define('kontribuo.button.Reen', {
	extend: 'Ext.button.Button',
		text: '<? echo __("reen"); ?>',
		handler: function() {
			window.location = '<? echo $uri; ?>';
		}
});

