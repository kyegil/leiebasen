<?
/**********************************************
Kontribuo
de Kay-Egil Hauan
**********************************************/

if(!isset($var))			$var			= 'novaKonto';
if(!isset($title)) 			$title			= __('nova konto');
if(!isset($height)) 		$height			= false;
if(!isset($width)) 			$width			= false;
if(!isset($reveno)) 		$reveno			= false;
if(!isset($url)) 			$url			= false;
if(!isset($callback)) 		$callback			= false;
if(!isset($ricevilo)) 		$ricevilo		= $this->Html->url(array(
	'controller' => 'kontoj',
	'action' => 'stoku',
	'ext'	=>	'json',
	0
	));

?>

var <?=$var;?>Panelo = Ext.create('Ext.form.Panel', {
	bodyPadding: 5,
	<?=($ricevilo ? "url: '{$ricevilo}',\n" : "")?>
	
	autoScroll: true,
	items: [{
		xtype: 'textfield',
		allowBlank:	false,
		emptyText:	'<? echo __('nomo');?>',
		name: 'nomo'
	}],
	buttons: [{
		text: '<? echo __('konfirmu');?>',
		handler: function() {
			var form = this.up('form').getForm();
			if (form.isValid()) {
				form.submit({
					success: function(form, action) {
						Ext.Msg.alert('<? echo __('sukceso');?>', action.result.msg);
						<?=($callback ? "{$callback}\n" : "")?>
						<?=$var;?>.close();

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
	}]

});
var <?=$var;?> = Ext.create('Ext.window.Window', {
	closeAction: 'hide',
//	width: '50%',
	<?=($height ? "height: {$height},\n" : "")?>
	<?=($width ? "width: {$width},\n" : "")?>
	title: '<?=$title;?>',
	items: [<?=$var;?>Panelo]
});

