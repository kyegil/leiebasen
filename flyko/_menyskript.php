<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/
If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');
?>

	var flyko = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.menu.Menu', {<?else:?>new Ext.menu.Menu({<?endif?>

		id: 'flyko',
		items: [
			{
				text: 'Forsiden',
				handler: function(){
					window.location = "index.php";
				}
			},
			'-',
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
							text: 'Oppfølging',
							handler: function(){
								window.location = "../oppfolging/index.php";
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


	var framleie = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.menu.Menu', {<?else:?>new Ext.menu.Menu({<?endif?>

		id: 'framleie',
		items: [
			{
				text: 'Framleieavtaler',
				handler: function(){
					window.location = "index.php?oppslag=framleie_liste";
				}
			},
			'-',
			{
				text: 'Registrer ny framleie',
				handler: function(){
					window.location = "index.php?oppslag=framleie_skjema&id=*";
				}
			},
			'-',
			{
				text: 'Adresseliste',
				handler: function(){
					window.location = "index.php?oppslag=adresser&returi=default";
				}
			}
		]
	});


	var leiligheter = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.menu.Menu', {<?else:?>new Ext.menu.Menu({<?endif?>

		id: 'leiligheter',
		items: [
			{
				text: 'Leiligheter og lokaler',
				handler: function(){
					window.location = "index.php?oppslag=leieobjekt_liste";
				}
			},
			{
				text: 'Ledige leiligheter',
				handler: function(){
					window.location = "index.php?oppslag=oversikt_ledigheter";
				}
			}
		]
	});


	var rapporter = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.menu.Menu', {<?else:?>new Ext.menu.Menu({<?endif?>

		id: 'rapporter',
		items: [
			{
				text: 'Utleietabell',
				handler: function(){
					window.location = "index.php?oppslag=oversikt_utleie_krysstabell";
				}
			}
		]
	});


	var menylinje = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.toolbar.Toolbar', {<?else:?>new Ext.Toolbar({<?endif?>

		renderTo: 'menylinje',
		items: [{
			text:'<span style="font-weight:bold;">FLYKO</span>',
			hideOnClick: false,
			menu: flyko
		},
		'-',
		{
			text:'<span style="font-weight:bold;">FRAMLEIE</span>',
			hideOnClick: false,
			menu: framleie
		},
		'-',
		{
			text:'<span style="font-weight:bold;">LEIEOBJEKTER</span>',
			hideOnClick: false,
			menu: leiligheter
		},
		'-',
		{
			text:'<span style="font-weight:bold;">RAPPORTER</span>',
			hideOnClick: false,
			menu: rapporter
		}
		]
	});
	
<?if($this->ext_bibliotek == 'ext-3.4.0'):?>
	menylinje.render('menylinje');
	menylinje.doLayout();

<?endif?>
