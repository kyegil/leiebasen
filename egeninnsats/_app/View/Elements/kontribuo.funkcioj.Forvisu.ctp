<?
/**********************************************
Kontribuo
de Kay-Egil Hauan
**********************************************/

if(!isset($title)) 		$title		= __("bonvolu konfirmi");
if(!isset($msg)) 		$msg		= __("Ĉu vi vere volas forigi ĉi?");
if(!isset($url)) 		$url		= $this->Html->url(array(
										'action' => 'forvisu',
										'ext'	=>	'json'
									));

?>
var kontribuo = kontribuo || {};
kontribuo.funkcioj = kontribuo.funkcioj || {};

kontribuo.funkcioj.Forvisu = function (rekordo) {
	Ext.Msg.show({
		title: '<?=$title?>',
		msg: '<?=$msg?>',
		buttons: Ext.Msg.OKCANCEL,
		fn: function(buttonId, text, opt){
			if(buttonId == 'ok') {
				Ext.Ajax.request({
					waitMsg: '<? echo __('bonvolu atendi') ;?>',
					url: '<?=$url?>',
					params: {
						id: rekordo.data.id
					},
					success: function(response, options) {
						var result = Ext.JSON.decode(response.responseText);
						if(result['success'] == true) {
							Ext.MessageBox.alert('<? echo __("forigita");?>', result.msg, function() {
								stoko.load();
							});
						}
						else {
							Ext.MessageBox.alert('<? echo __("fiasko");?>', result['msg']);
						}
					},
					failure: function(response, opts) {
						if(!response.status) {
							Ext.MessageBox.alert('<? echo __("problemo");?>', '<? echo __("La peto estis sendita al la servilo, sed la servilo ne sukcesis konfirmi la ekzekuton. La ligo al la servilo eble ne perdiĝus. Provu reŝarĝi la retumilo fenestro.");?>');
						}
						else {
							Ext.MessageBox.alert('<? echo __("problemo");?>', ('<? echo __("La peto estis sendita al la servilo, sed la servilo ne sukcesis konfirmi la ekzekuton. La servilo donis la sekvan respondon:");?><br />' + response.status + ' ' + response. statusText));
						}
					}
				});
			}
		},
		animEl: 'elId',
		icon: Ext.MessageBox.QUESTION
	});
}


