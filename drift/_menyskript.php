<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/
If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');
?>

	var drift = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.menu.Menu', {<?else:?>new Ext.menu.Menu({<?endif?>
	
		id: 'drift',
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
					window.location = "index.php?oppslag=profil_skjema&id=<?=$this->bruker['id']?>&returi=default";
				}
			},
			{
				text: 'Gå til',
				hideOnClick: false,
				menu: {        // <-- submenu by nested config object
					items: [
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
				text: 'Adganger',
				handler: function(){
					window.location = "index.php?oppslag=adgang_liste&returi=default";
				}
			},
			'-',
			{
				text: 'Innstillinger',
				handler: function(){
					window.location = "index.php?oppslag=valg_skjema&returi=default";
				}
			},
			'-',
			{
				text: 'Brukerveiledning',
				handler: function(){
					window.location = "../dokumentasjon.pdf";
				}
			},
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
			{
				text: 'Leieavtaler',
				hideOnClick: false,
				menu: {
					items: [
						{
							text: 'Alle leieavtaler',
							handler: function(){
								window.location = "index.php?oppslag=leieforhold_liste&returi=default";
							}
						},
						{
							text: 'Leieavtaler som må fornyes',
							handler: function(){
								window.location = "index.php?oppslag=oversikt_fornyelser&returi=default";
							}
						}
					]
				}
			},
			{
				text: 'Oppsigelser',
				handler: function(){
					window.location = "index.php?oppslag=oppsigelse_liste&returi=default";
				}
			},
			{
				text: 'Framleie',
				handler: function(){
					window.location = "index.php?oppslag=framleie_liste&returi=default";
				}
			},
			'-',
			{
				text: 'Leie- og kravoversikt',
				handler: function(){
					window.location = "index.php?oppslag=krav_liste&returi=default";
				}
			},
			{
				text: 'Adresser',
				handler: function(){
					window.location = "index.php?oppslag=adresser&returi=default";
				}
			},
			{
				text: 'eFaktura',
				hideOnClick: false,
				menu: {
					items: [
						{
							text: 'Avtaler',
							handler: function(){
								window.location = "index.php?oppslag=efaktura-avtaler&returi=default";
							}
						},
						{
							text: 'Fakturaer',
							handler: function(){
								window.location = "index.php?oppslag=efakturaliste&returi=default";
							}
						}
					]
				}

			},
			{
				text: 'Gjeldsoversikt',
				handler: function(){
					window.location = "index.php?oppslag=oversikt_gjeld&returi=default";
				}
			},
			{
				text: 'Oppfølging',
				handler: function(){
					window.location = "../oppfolging/index.php";
				}
			},
			'-',
			{
				text: 'Opprett ny leieavtale',
				handler: function(){
					window.location = "index.php?oppslag=leieforhold_opprett-1&returi=default";
				}
			},
			'-',
			{
				text: 'Leieregulering',
				handler: function(){
					window.location = "index.php?oppslag=leieregulering&returi=default";
				}
			},
			'-',
			{
				text: 'Skriv ut giroer',
				handler: function(){
					window.location = "index.php?oppslag=utskriftsmeny&returi=default";
				}
			}
		]
	});


	var innbetalinger = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.menu.Menu', {<?else:?>new Ext.menu.Menu({<?endif?>

		id: 'innbetalinger',
		items: [
			{
				text: 'Vis / Søk i innbetalinger',
				handler: function(){
					window.location = "index.php?oppslag=innbetaling_liste&returi=default";
				}
			},
			{
				text: 'Registrer betalinger',
				hideOnClick: false,
				menu: {
					items: [
						{
							text: 'Registrer ny betaling',
							handler: function() {
								window.location = "index.php?oppslag=betalingsskjema&id=*&returi=default";
							}
						},
						{
							text: 'Registrer/endre flere innbetalinger',
							handler: function() {
								window.location = "index.php?oppslag=innbetalinger&returi=default";
							}
						}
					]
				}
			},
			{
				text: 'Utlikning av innbetalinger mot betalingskrav',
				handler: function(){
					window.location = "index.php?oppslag=utlikninger_skjema&returi=default";
				}
			},
			{
				text: 'OCR-filer',
				handler: function(){
					window.location = "index.php?oppslag=ocr_liste&returi=default";
				}
			},
			{
				text: 'Faste betalingsoppdrag',
				hideOnClick: false,
				menu: {
					items: [
						{
							text: 'Registrerte avtaler',
							handler: function(){
								window.location = "index.php?oppslag=fboliste&returi=default";
							}
						},
						{
							text: 'Trekkoppdrag for AvtaleGiro',
							handler: function(){
								window.location = "index.php?oppslag=fbo-kravliste&returi=default";
							}
						}
					]
				}

			},
			{
				text: 'Månedlige kontobevegelser (innbetalinger)',
				handler: function(){
					window.location = "index.php?oppslag=oversikt_kontobevegelser&returi=default";
				}
			}
		]
	});


	var leiligheter = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.menu.Menu', {<?else:?>new Ext.menu.Menu({<?endif?>

		id: 'leiligheter',
		items: [
			{
				text: 'Bygninger',
				handler: function(){
					window.location = "index.php?oppslag=bygningsliste&returi=default";
				}
			},
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
			},
			{
				text: 'Strømfakturaer og fordeling',
				handler: function(){
					window.location = "index.php?oppslag=fs_fakturaer&returi=default";
				}
			},
			{
				text: 'Arkiverte fordelinger',
				handler: function(){
					window.location = "index.php?oppslag=fs_fakturaer_arkiv&returi=default";
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
					window.location = "index.php?oppslag=oversikt-utleietabell&returi=default";
				}
			},
			{
				text: 'Månedsvis oppsummering',
				handler: function(){
					window.location = "index.php?oppslag=oversikt_innbetalinger&returi=default";
				}
			},
			{
				text: 'Denne måneds ...',
				hideOnClick: false,
				menu: {
					items: [
						{
							text: 'innbetalinger',
							handler: function(){
								window.location = "index.php?oppslag=oversikt_kontobevegelser&returi=default";
							}
						},
						{
							text: 'krav om betaling',
							handler: function(){
								window.location = "index.php?oppslag=oversikt_krav&returi=default";
							}
						},
						{
							text: 'husleiekrav',
							handler: function(){
								window.location = "index.php?oppslag=oversikt_husleiekrav&returi=default";
							}
						}
					]
				}
			},
			{
				text: 'Inntekter per bygning',
				handler: function(){
					window.location = "index.php?oppslag=oversikt_bygningsinntekter&returi=default";
				}
			},
			{
				text: 'Diagrammer',
				hideOnClick: false,
				menu: {
					items: [
						{
							text: 'Utestående krav over tid',
							handler: function(){
								window.location = "index.php?oppslag=statistikk_gjeldshistorikk&returi=default";
							}
						},
						{
							text: 'Diagram over krav og innbetalinger',
							handler: function(){
								window.location = "index.php?oppslag=statistikk_innbetalinger&returi=default";
							}
						}
					]
				}
			},
			{
				text: 'Eksport av data',
				handler: function(){
					window.location = "index.php?oppslag=eksport&returi=default";
				}
			},
			{
				text: 'Rapporter',
				hideOnClick: false,
				menu: {
					items: [
						{
							text: 'Regnskapsrapport',
							handler: function(){
								window.location = "index.php?oppslag=rapport_regnskap&returi=default";
							}
						},
						{
							text: 'Kontoforløp per leieforhold',
							handler: function(){
								window.location = "index.php?oppslag=rapport_kontoutskrift_leieforhold&returi=default";
							}
						},
						{
							text: 'Rapport over innbetalinger mot krav fra bestemt tidsrom',
							handler: function(){
								window.location = "index.php?oppslag=rapport_gjeldsnedbetaling&returi=default";
							}
						},
						{
							text: 'Rapport over utestående husleie på bestemt dato',
							handler: function(){
								window.location = "index.php?oppslag=rapport_avstemming&returi=default";
							}
						},
						{
							text: 'Oversikt over anvendte gironummerserier',
							handler: function(){
								window.location = "index.php?oppslag=rapport-anvendte-gironummer&returi=default";
							}
						}
					]
				}
			}
		]
	});


	var menylinje = <?if($this->ext_bibliotek != 'ext-3.4.0'):?>Ext.create('Ext.toolbar.Toolbar', {<?else:?>new Ext.Toolbar({<?endif?>

		renderTo: 'menylinje',
		layout: {
			type: 'hbox',
		},
		border: false,
		padding: '0',
		items: [{
			text:'<span style="font-weight:bold;">DRIFT</span>',
			hideOnClick: false,
			menu: drift
		},
		'-',
		{
			text:'<span style="font-weight:bold;">LEIEFORHOLD</span>',
			hideOnClick: false,
			menu: leieforhold
		},
		'-',
		{
			text:'<span style="font-weight:bold;">INNBETALINGER</span>',
			hideOnClick: false,
			menu: innbetalinger
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
		}
		]
	});
	
