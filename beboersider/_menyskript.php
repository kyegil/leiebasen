<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/
If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

	$sql =	"
		SELECT *
		FROM adganger
		WHERE adgang = 'beboersider' AND personid = '{$this->bruker['id']}'
	";
	$menyadganger = $this->arrayData($sql);
?>

	var beboersider = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.menu.Menu', {<?else:?>new Ext.menu.Menu({<?endif?>

		id: 'beboersider',
		items: [
			{
				text: 'Forsiden',
				handler: function(){
					window.location = "index.php";
				}
			},
			'-',
			{
				text: 'Brukerprofil',
				handler: function(){
					window.location = "index.php?oppslag=profil_skjema&returi=default";
				}
			},
			{
				text: 'Gå til',
				hideOnClick: false,
				menu: {        // <-- submenu by nested config object
					items: [
						{
							text: 'Egeninnsats',
							handler: function(){
								window.location = "../egeninnsats/kontoj/";
							}
						},
						{
							text: 'Drift',
							handler: function(){
								window.location = "../drift/index.php";
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
					window.location = "index.php?oppslag=adgang_skjema&returi=default";
				}
			},
			'-',
			{
				text: 'Feilrapportering og forbedringsforslag',
				handler: function(){
					window.location = "index.php?oppslag=tilbakemelding_skjema&returi=default";
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


	var leieforhold = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.menu.Menu', {<?else:?>new Ext.menu.Menu({<?endif?>

		id: 'leieforhold',
		items: [
<?
	foreach($menyadganger['data'] as $adgang) {
?>
			{
				text: '<?= $this->liste($this->kontraktpersoner($this->sistekontrakt($adgang['leieforhold']))) . " i " . $this->leieobjekt($this->kontraktobjekt($adgang['leieforhold']), true)?>',
				handler: function(){
					window.location = "index.php?oppslag=leieforholdkort&id=<?php echo $adgang['leieforhold'];?>&returi=default";
				}
			},
<?
	}
?>
			{
				text: 'Adresser',
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
					window.location = "index.php?oppslag=leieobjekt_liste&returi=default";
				}
			},
			{
				text: 'Ledige leiligheter',
				handler: function(){
					window.location = "index.php?oppslag=oversikt_ledigheter&returi=default";
				}
			},
			{
				text: 'Skaderegister',
				handler: function(){
					window.location = "index.php?oppslag=skade_liste&returi=default";
				}
			}
		]
	});


	var fellesstrøm = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.menu.Menu', {<?else:?>new Ext.menu.Menu({<?endif?>

		id: 'fellesstrøm',
		items: [
			{
				text: 'Anleggsoversikt med fordelingsnøkler',
				handler: function(){
					window.location = "index.php?oppslag=fs_anlegg&returi=default";
				}
			}
// 			{
// 				text: 'Strømfakturaer og fordeling',
// 				handler: function(){
// 					window.location = "index.php?oppslag=fs_fakturaer";
// 				}
// 			}
		]
	});


	var rapporter = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.menu.Menu', {<?else:?>new Ext.menu.Menu({<?endif?>

		id: 'rapporter',
		items: [
			{
				text: 'Månedsvis oppsummering',
				handler: function(){
					window.location = "index.php?oppslag=oversikt_innbetalinger&returi=default";
				}
			}
// 			{
// 				text: 'Utleietabell',
// 				handler: function(){
// 					window.location = "index.php?oppslag=oversikt_utleie_krysstabell&returi=default";
// 				}
// 			}
		]
	});


	var egeninnsats = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.menu.Menu', {<?else:?>new Ext.menu.Menu({<?endif?>

		id: 'egeninnsats',
		items: [
			{
				text: 'Registrer egeninnsats',
				handler: function(){
					window.location = "../egeninnsats/rekordoj/aldonu/";
				}
			},
			{
				text: 'Registrer dugnad',
				handler: function(){
					window.location = "../egeninnsats/rekordoj/aldonuGrupoKontribuoj/";
				}
			}
		]
	});


	var menylinje = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.toolbar.Toolbar', {<?else:?>new Ext.Toolbar({<?endif?>

		renderTo: 'menylinje',
		items: [{
			text:'<span style="font-weight:bold;">HJEM</span>',
			hideOnClick: false,
			menu: beboersider
		},
		'-',
		{
			text:'<span style="font-weight:bold;">LEIEFORHOLD</span>',
			hideOnClick: false,
			menu: leieforhold
		},
		'-',
		{
			text:'<span style="font-weight:bold;">LEIEOBJEKTER</span>',
			hideOnClick: false,
			menu: leiligheter
		},
		'-',
		{
			text:'<span style="font-weight:bold;">FELLESSTRØM</span>',
			hideOnClick: false,
			menu: fellesstrøm
		},
		'-',
		{
			text:'<span style="font-weight:bold;">RAPPORTER OG STATISTIKK</span>',
			hideOnClick: false,
			menu: rapporter
		},
		'-',
		{
			text:'<span style="font-weight:bold;">EGENINNSATS</span>',
			hideOnClick: false,
			menu: egeninnsats
		},
		'-'
		]
	});
	
<?if($this->ext_bibliotek == 'ext-3.4.0'):?>
	menylinje.render('menylinje');
	menylinje.doLayout();

<?endif?>
