<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/
If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');
?>

	var oppfølging = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.menu.Menu', {<?else:?>new Ext.menu.Menu({<?endif?>

		id: 'oppfølging',
		items: [
			{
				text: 'Gå til',
				hideOnClick: false,
				menu: {        // <-- submenu by nested config object
					items: [
						{
							text: 'Drift',
							handler: function(){
								window.location = "../drift/index.php";
							}
						},
						{
							text: 'Beboersider',
							handler: function(){
								window.location = "../beboersider/index.php";
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
			{
				text: 'Adgangskontroll',
				handler: function(){
					window.location = "index.php?oppslag=adgang_liste&returi=default";
				}
			},
			'-',
			{
				text: 'Logg av',
				handler: function(){
					window.location = "../index.php?oppdrag=avslutt";
				}
			}
		]
	});


	var menylinje = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.toolbar.Toolbar', {<?else:?>new Ext.Toolbar({<?endif?>

		renderTo: 'menylinje',
		items: [{
			text:'<span style="font-weight:bold;">MENY</span>',
			hideOnClick: false,
			menu: oppfølging
		},
		'-',
		{
			text:'<span style="font-weight:bold;">HENVENDELSER OG NOTATER</span>',
			handler: function(){
				window.location = "index.php";
			}
		},
		'-',
		{
			text:'<span style="font-weight:bold;">TILBAKE TIL DRIFT</span>',
			handler: function(){
				window.location = "../drift/index.php";
			}
		}]
	});
	
<?if($this->ext_bibliotek == 'ext-3.4.0'):?>
	menylinje.render('menylinje');
	menylinje.doLayout();

<?endif?>
