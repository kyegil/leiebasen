<?
/**********************************************
Kontribuo
de Kay-Egil Hauan
**********************************************/

if(!isset($var)) 			$var			= "ujo";
if(!isset($layout)) 		$layout			= "fit";
if(!isset($items)) 			$items			= false;
	else settype($items, 'array');

?>

var <?=$var?> = Ext.create('Ext.container.Viewport', {
	<?=($items ? ("items: [" . implode(', ', $items) . "],\n") : "")?>
	layout: '<?=$layout?>'
});

