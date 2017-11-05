<?
/**********************************************
Kontribuo
de Kay-Egil Hauan
**********************************************/

if(!isset($layout))			$layout			= false;
if(!isset($region))			$region			= 'center';
if(!isset($bodyPadding))	$bodyPadding	= 5;
if(!isset($layout))			$layout			= false;
if(!isset($formBind))		$formBind		= true;
if(!isset($html)) 			$html			= false;
if(!isset($title)) 			$title			= "";
if(!isset($loader)) 		$loader			= false;
if(!isset($renderTo)) 		$renderTo		= false;
if(!isset($height)) 		$height			= false;
if(!isset($width)) 			$width			= false;
if(!isset($reveno)) 		$reveno			= false;
if(!isset($url)) 			$url			= false;
if(!isset($ricevilo)) 		$ricevilo		= false;

?>

Ext.define('kontribuo.form.Panel', {
	extend: 'Ext.form.Panel',
	region: '<?=$region;?>',
	bodyPadding: '<?=$bodyPadding;?>',
	<?=($layout ? "layout: " . ($layout) . ",\n" : "")?>
	<?=($loader ? "loader: " . ($loader) . ",\n" : "")?>
	<?=($height ? "height: {$height},\n" : "")?>
	<?=($ricevilo ? "url: '{$ricevilo}',\n" : "")?>
	<?=($html ? "html: {$html},\n" : "")?>
	<?=($width ? "width: {$width},\n" : "")?>
	<?=($renderTo ? "renderTo: {$renderTo},\n" : "")?>
	
	autoScroll: true,
	buttons: [{
		text: '<? echo __('konfirmu');?>',
		formBind: <? echo ($formBind ? 'true' : 'false'); ?>, //only enabled once the form is valid
		disabled: <? echo ($formBind ? 'true' : 'false'); ?>,
		handler: function() {
			var form = this.up('form').getForm();
			if (form.isValid()) {
				form.submit({
					success: function(form, action) {
						Ext.Msg.alert('<? echo __('sukceso');?>', action.result.msg);
						<? echo ($reveno ? "window.location = '$reveno';" : ""); ?>

				},
					failure: function(form, action) {
						if(!action.result) {
							Ext.Msg.alert('<? echo __('fiasko');?>', '<? echo __("La peto estis sendita al la servilo, sed la servilo ne sukcesis konfirmi la ekzekuton. La ligo al la servilo eble ne perdiĝus.");?>');
						}
						else if(!action.result.msg) {
							Ext.Msg.alert('<? echo __("problemo");?>', ('<? echo __("La peto estis sendita al la servilo, sed la servilo ne sukcesis konfirmi la ekzekuton. La servilo donis la sekvan respondon:");?><br />' + response.status + ' ' + response. statusText));
						}
						else {
							Ext.Msg.alert('<? echo __('fiasko');?>', action.result.msg);
						}
					}
				});
			}
		}
	}],
	title: '<?=$title?>'

});
