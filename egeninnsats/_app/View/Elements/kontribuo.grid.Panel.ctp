<?
/**********************************************
Kontribuo
de Kay-Egil Hauan
**********************************************/

if(!isset($layout)) 		$layout			= "fit";
if(!isset($renderTo)) 		$renderTo		= false;
if(!isset($height)) 		$height			= false;
if(!isset($width)) 			$width			= false;
if(!isset($rowexpander)) 	$rowexpander	= false;
if(!isset($rowediting)) 	$rowediting	= false;

?>

Ext.define('kontribuo.grid.Panel', {
	extend: 'Ext.grid.Panel',
	<?=($height ? "height: {$height},\n" : "")?>
	<?=($width ? "width: {$width},\n" : "")?>
	<?=($renderTo ? "renderTo: {$renderTo},\n" : "")?>
	layout: '<?=$layout?>',
	plugins: [{
		ptype: '<?= ($rowediting? "rowediting" : "cellediting");?>',
		clicksToEdit: 1
	}, {
		pluginId: 'rowexpander',
		ptype: 'rowexpander',
		rowBodyTpl : ['{rowexpander}']
	}],
	buttons: [{
		text: '<? echo __("aldonu alian");?>',
		handler: function(view, rowIndex, colIndex, item, event, record, row) {
			window.location = '<? echo $this->Html->url(array('action' => 'aldonu'));?>';
		}
	}],
	autoScroll: true
});

