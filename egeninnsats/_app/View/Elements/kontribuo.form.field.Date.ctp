<?
/**********************************************
Kontribuo
de Kay-Egil Hauan
**********************************************/

?>

Ext.define('kontribuo.form.field.Date', {
	extend: 'Ext.form.field.Date',
	width: 200,
	emptyText: '<? echo __('dato')?>',
	name: 'dato',
	submitFormat: 'Y-m-d',
	format: 'd.m.Y',
	altFormats: 'Y-m-d|Y-m-d H:i:s'
});

