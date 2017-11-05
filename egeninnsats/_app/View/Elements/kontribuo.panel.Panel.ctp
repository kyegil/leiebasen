<?
/**********************************************
Kontribuo
de Kay-Egil Hauan
**********************************************/

if(!isset($layout)) 		$layout			= false;
if(!isset($html)) 			$html			= false;
if(!isset($title)) 			$title			= "";
if(!isset($loader)) 		$loader			= false;
if(!isset($renderTo)) 		$renderTo		= false;
if(!isset($height)) 		$height			= false;
if(!isset($width)) 			$width			= false;
if(!isset($url)) 			$height			= false;
if(!isset($autoLoad)) 		$autoLoad		= true;

?>

Ext.define('kontribuo.panel.Panel', {
	extend: 'Ext.panel.Panel',
	<?=($url ? ("loader: {\n\t\turl: '{$url}',\n\t\tautoLoad: " . ($autoLoad ? 'true' : 'false') . "\n\t},\n") : "")?>
	<?=($layout ? "layout: " . ($layout) . ",\n" : "")?>
	<?=($loader ? "loader: " . ($loader) . ",\n" : "")?>
	<?=($height ? "height: {$height},\n" : "")?>
	<?=($html ? "html: {$html},\n" : "")?>
	<?=($width ? "width: {$width},\n" : "")?>
	<?=($renderTo ? "renderTo: {$renderTo},\n" : "")?>
	
	autoScroll: true,
	buttons: [],
	title: '<?=$title?>'

});

