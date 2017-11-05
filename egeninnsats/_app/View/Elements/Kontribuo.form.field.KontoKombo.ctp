<?
/**********************************************
Kontribuo
de Kay-Egil Hauan
**********************************************/

if(!isset($aldonu))			$aldonu			= 'novaKonto';

?>

Ext.define('Kontribuo.form.field.KontoKombo', {
	extend: 'Ext.form.field.ComboBox',
	
	name: 'konto',
	width: 200,
	emptyText: '<? echo __('elektu konton');?>',

	queryMode: 'remote',
	valueField: 'id',
	displayField: 'nomo',
	formBind: true,
	
	store: Ext.create('Ext.data.JsonStore', {
		storeId: 'stokoDeKontoj',
	
		proxy: {
			type: 'ajax',
			url: '<? echo $this->Html->url(array(
				'controller' => 'kontoj',
				'action' => 'index',
				'ext'	=>	'json'
			));?>',
			reader: {
				type: 'json',
				root: 'data',
				idProperty: 'id'
			}
		},
		autoLoad: true,

<?if($aldonu):?>
		listeners: {
			load: function ( stoko, rekordoj, sukcesa, eOpts ) {
				stoko.add({
					id: -1,
					nomo: '--  <?=__('kreu novan konton');?>  --'
				});
			}
		},

<?endif;?>
		fields: [
			{name:'id', type: 'string', mapping: 'Konto.id'},
			{name:'nomo', type:'string', mapping: 'Konto.nomo'}
		]

	}),

	allowBlank: false,
	forceSelection: true,
	selectOnFocus: true,
	typeAhead: true,

<?if($aldonu):?>
	listeners: {
		select: function( kombo, rekordoj, eOpts ) {
			if(kombo.getValue() == -1) {
				<?=$aldonu?>.show();
			}
		}
	},
	
<?endif;?>
	hideTrigger: true,
	matchFieldWidth: false,
	minChars: 1,
	queryDelay: 1000,
	listConfig: {
		loadingText: '<? echo __('serĉo en progreso');?>',
		emptyText: '<? echo __('neniu sukcesoj');?>',
		maxHeight: 600,
		width: 600
	}

});

