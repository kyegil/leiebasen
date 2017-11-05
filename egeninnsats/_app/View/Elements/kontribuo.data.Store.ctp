<?
/**********************************************
Kontribuo
de Kay-Egil Hauan
**********************************************/

// variabloj:
// 
// pageSize
// url
// totalRows
// groupField
// autoLoad

if(!isset($pageSize))		$pageSize		= 200;
if(!isset($totalProperty))	$totalProperty	= "totalRows";
if(!isset($autoLoad))		$autoLoad		= true;

?>

Ext.define('kontribuo.data.Store', {
	extend: 'Ext.data.Store',
	pageSize: <? echo $pageSize;?>,
	remoteSort: true,
	proxy: {
		type: 'ajax',
		simpleSortMode: true,
		url: '<? echo $url;?>',
		reader: {
			type: 'json',
			<? echo (isset($record) ? "record: '{$record}',\n" : "");?>
			root: 'data',
			actionMethods: {
				read: 'POST'
			},
			totalProperty: '<? echo $totalProperty;?>'
		}
	},
	sorters: [{
		property: 'id',
		direction: 'ASC'
	}],
	<? echo (isset($groupField) ? "groupField: '{$groupField}',\n" : "");?>
	autoLoad: <? echo ($autoLoad ? 'true' : 'false');?>
});

