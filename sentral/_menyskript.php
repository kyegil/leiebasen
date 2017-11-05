<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/
If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');
?>

	var bruker = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.menu.Menu', {<?else:?>new Ext.menu.Menu({<?endif?>
	
		id: 'bruker',
		items: [
			{
				text: 'Hjem',
				handler: function(){
					window.location = "index.php";
				}
			},
			'-',
			{
				text: 'Brukerprofil',
				handler: function(){
					window.location = "index.php?oppslag=profil_skjema&id=<?=$this->bruker['id']?>&returi=default";
				}
			},
			{
				text: 'Gå til',
				hideOnClick: false,
				menu: {
					items: [
						{
							text: 'Beboersider',
							handler: function(){
								window.location = "../beboersider/index.php";
							}
						},
						{
							text: 'Drift',
							handler: function(){
								window.location = "../drift/index.php";
							}
						},
						{
							text: 'Egeninnsats',
							handler: function(){
								window.location = "../egeninnsats/kontoj/";
							}
						},
						{
							text: 'Flyko',
							handler: function(){
								window.location = "../flyko/index.php";
							}
						}
					]
				}
			},
			'-',
			{
				text: 'Logg av',
				handler: function(){
					window.location = "../index.php?oppdrag=avslutt";
				}
			},
			'-'
		]
	});


	var menylinje = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.toolbar.Toolbar', {<?else:?>new Ext.Toolbar({<?endif?>

		renderTo: 'menylinje',
		items: [{
			text: '<span style="font-weight:bold;">BRUKER</span>',
			hideOnClick: false,
			menu: bruker
		}]
	});
	
<?if($this->ext_bibliotek == 'ext-3.4.0'):?>
	menylinje.render('menylinje');
	menylinje.doLayout();

<?endif?>
