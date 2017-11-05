<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/

If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

public $tittel = 'Strømregninger for fordeling blant beboere';
public $ext_bibliotek = 'ext-4.2.1.883';
	

function __construct() {
	parent::__construct();
}

function skript() {
	if( isset( $_GET['returi'] ) && $_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
	$tp = $this->mysqli->table_prefix;
?>
Ext.Loader.setConfig({
	enabled: true
});
Ext.Loader.setPath('Ext.ux', '<?=$this->http_host . "/" . $this->ext_bibliotek . "/examples/ux"?>');

Ext.require([
 	'Ext.data.*',
 	'Ext.form.field.*',
    'Ext.layout.container.Border',
 	'Ext.grid.*',
    'Ext.ux.RowExpander'
]);

Ext.onReady(function() {

    Ext.tip.QuickTipManager.init();
	Ext.form.Field.prototype.msgTarget = 'side';
	Ext.Loader.setConfig({enabled:true});
	
<?
	include_once("_menyskript.php");
?>

	Ext.define('Faktura', {
		extend: 'Ext.data.Model',
		idProperty: 'id',
		fields: [ // http://docs.sencha.com/extjs/4.2.2/#!/api/Ext.data.Field
			{name: 'id'},
			{name: 'fakturanummer'},
			{name: 'fakturabeløp', type: 'float', useNull: true},
			{name: 'anleggsnr'},
			{name: 'bruk'},
			{name: 'fradato', type: 'date', dateFormat: 'Y-m-d', useNull: true},
			{name: 'tildato', type: 'date', dateFormat: 'Y-m-d', useNull: true},
			{name: 'kWh', type: 'float'},
			{name: 'termin'},
			{name: 'varslet', type: 'date', dateFormat: 'Y-m-d H:i:s', useNull: true},
			{name: 'lagt_inn_av'},
			{name: 'html'}
		]
	});
	
	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
		clicksToEdit: 2
	});
	
	var rowEditing = Ext.create('Ext.grid.plugin.RowEditing', {
		autoCancel: false,
		listeners: {
			beforeedit: function (grid, e, eOpts) {
				return e.column.xtype !== 'actioncolumn';
			},
		},
	});


	function tastetrykk(bokstavtrykk) {
		selectionModel = rutenett.getSelectionModel();
		
		if(bokstavtrykk.getKey() == 113) { // =F2

			celle = selectionModel.getCurrentPosition();
			rad = celle.row;
			kolonne = celle.column;

			if(rad > 0) {
				feltnavn = rutenett.headerCt.getHeaderAtIndex(kolonne).dataIndex;
				forrigeLinje = datasett.getAt(rad - 1);
				denneLinje = datasett.getAt(rad);
				verdi = forrigeLinje.data[feltnavn];
				if(forrigeLinje.data[feltnavn] instanceof Date) {
					verdi = verdi.format('Y-m-d');
				}
				originalverdi = denneLinje.data[feltnavn];
				denneLinje.set(feltnavn, verdi);
 
				if(kolonne < 8) {
					cellEditing.startEditByPosition({
						row: rad,
						column: kolonne + 1
					});
				}
				else {
					cellEditing.startEditByPosition({
						row: rad + 1,
						column: 2
					});
				}
 
//				objekt = new Array();
				var objekt = {
					value:			verdi,
					record: 		denneLinje,
					field:			feltnavn,
					originalValue:	originalverdi
				};
				lagreEndringer(rutenett, objekt);
			}
			return true;
		}
	}


	function oppdaterDetaljer(record) {
		var detaljfelt = Ext.getCmp('detaljfelt');
		var detaljer = Ext.create('Ext.Template', [
			'Anlegg nr <a title="Klikk her for å gå til dette anlegget" href="index.php?oppslag=fs_anlegg_kort&anleggsnummer={anleggsnr}">{anleggsnr}</a>: <br />{bruk}<br /><br />{html}'
		]);
		if(record) {
			detaljer.overwrite(detaljfelt.body, record.data);
		}
		else {
			detaljer.overwrite(detaljfelt.body, {
				html: ""
			});
		}
	}


	var datasett = Ext.create('Ext.data.Store', {
		model: 'Faktura',
		pageSize: 300,
		remoteSort: true,
		proxy: {
			type: 'ajax',
			simpleSortMode: true,
			url: 'index.php?oppdrag=hentdata&oppslag=fs_fakturaer',
			reader: {
				type: 'json',
				root: 'data',
				actionMethods: {
					read: 'POST'
				},
				totalProperty: 'totalRows'
			}
		},
		sorters: [{
			property: 'id',
			direction: 'DESC'
		}],
		autoLoad: {
			start: 0,
			limit: 300
		}
    });


	var lastData = function() {
		datasett.baseParams = {};
		datasett.load({
			params: {
				start: 0,
				limit: 100
			},
			callback: function(records, operation, success) {
				var valgtFaktura =  rutenett.getSelectionModel().getSelection();
				var valgtLinje = null;
				if(valgtFaktura && valgtFaktura.length) {
					var valgtLinje =  datasett.getById( valgtFaktura[0].get('id') );
				}
				oppdaterDetaljer(valgtLinje);
			}
		});
	}


	var strømfordelingstekst = Ext.create('Ext.form.field.TextArea', {
		hideLabel: true,
		height: '100%',
		name: 'strømfordelingstekst',
		value: <?=json_encode($this->valg['strømfordelingstekst'])?>,
		width: '100%'
	});


	var strømfordelingsvindu = Ext.create('Ext.window.Window', {
		title: 'Skriv inn ei melding som følger varslene',
		closeAction: 'hide',
		height: 200,
		width: 800,
		items: [
			strømfordelingstekst
		],
		buttons: [
			{
				text: 'Send',
				handler: function(button, event) {
					varslefordeling();
				}
			}
		]
	});


	var id = {
		dataIndex: 'id',
		text: 'id',
		hidden: true,
		id: 'id',
		sortable: false,
		width: 50
	};

	var vis = {
		dataIndex: 'id',
		text: 'Vis',
		hidden: false,
		sortable: false,
		renderer: function(value, metaData, record, rowIndex, colIndex, store) {
			if(!record.data.id) return "";
			return "<a title=\"Vis denne fakturaen\" href=\"index.php?oppslag=fs_faktura_kort&id=" + record.data.id + "\"><img src=\"../bilder/detaljer_lite.png\" /></a>";
		},
		width: 30
	};

	var varslet = {
		dataIndex: 'varslet',
		text: 'Varslet',
		id: 'varslet',
//		renderer: Ext.util.Format.hake, 
		renderer: function(value, metaData, record, rowIndex, colIndex, store) {
			if(value) {
				return '<span title="' + Ext.util.Format.date(value, 'd.m.Y') + '">' + Ext.util.Format.hake(value) + '</span>';
			}
		},
		sortable: false,
		width: 40
	};

	var fakturanummer = {
		align: 'right',
		dataIndex: 'fakturanummer',
		editor: Ext.create('Ext.form.field.Text', {
			allowBlank: false,
			selectOnFocus: true,
			listeners: {
			   render: function(c) {
				  c.getEl().on({
					keydown: tastetrykk,
					scope: c
				  });
				}
			},
			disabled: false
		}),
		text: 'Faktura',
		renderer: function(value, metaData, record, rowIndex, colIndex, store) {
			if(value) return value;
		},
		sortable: true,
		width: 70
	};

	var fakturabeløp = {
		align: 'right',
		dataIndex: 'fakturabeløp',
		editor: Ext.create('Ext.form.field.Number', {
			listeners: {
			   render: function(c) {
				  c.getEl().on({
					keydown: tastetrykk,
					scope: c
				  });
				}
			},
			allowBlank: false,
			allowDecimals: true,
			allowNegative: true,
			blankText: 'Du må angi et beløp',
			decimalPrecision: 2,
			decimalSeparator: ',',

			hideTrigger: true,
			keyNavEnabled: false,
			mouseWheelEnabled: false,
			selectOnFocus: true
		}),
		text: 'Beløp',
		renderer: function(v) {
			if(v != null) {
				return Ext.util.Format.noMoney(v);
			}
			else {
				return null;
			}
		}, 
		sortable: true,
		width: 70
	};

	var anleggsnr = {
		align: 'right',
		dataIndex: 'anleggsnr',
		editor: Ext.create('Ext.form.field.ComboBox', {
			width: 200,
			matchFieldWidth: false,
			listConfig: {
				width: 500
			},

			store: Ext.create('Ext.data.JsonStore', {
				storeId: 'leieobjektliste',
		
				autoLoad: true,
				proxy: {
					type: 'ajax',
					url: "index.php?oppslag=fs_fakturaer&oppdrag=hentdata&data=anleggsnummer",
					reader: {
						type: 'json',
						root: 'data',
						idProperty: 'anleggsnummer'
					}
				},
			
				fields: [
					{name: 'anleggsnummer'},
					{name: 'anlegg'}
				]
			}),
			queryMode: 'remote',
			displayField: 'anlegg',
			valueField: 'anleggsnummer',
			minChars: 0,
			queryDelay: 1000,

			allowBlank: true,
			typeAhead: true,
			editable: false,
			selectOnFocus: true,
			forceSelection: true,
	
			listeners: {
			   render: function(c) {
				  c.getEl().on({
					keydown: tastetrykk,
					scope: c
				  });
				}
			}
		}),
		text: 'Anleggsnr',
		flex: 1,
		sortable: true,
		width: 100
	};

	var fradato = {
		dataIndex: 'fradato',
		editor: Ext.create('Ext.form.field.Date', {
			maxValue: new Date(),
			maxText: 'Fradato kan ikke være framover i tid.',
			listeners: {
			   render: function(c) {
				  c.getEl().on({
					keydown: tastetrykk,
					scope: c
				  });
				}
			},
			allowBlank: false,
			format: 'd.m.Y',
			submitFormat: 'Y-m-d'
		}),
		text: 'Fra dato',
		renderer: Ext.util.Format.dateRenderer('d.m.Y'), 
		sortable: true,
		width: 80
	};

	var tildato = {
		dataIndex: 'tildato',
		editor: Ext.create('Ext.form.field.Date', {
			maxValue: new Date(),
			maxText: 'Tildato kan ikke være framover i tid.',
			listeners: {
			   render: function(c) {
				  c.getEl().on({
					keydown: tastetrykk,
					scope: c
				  });
				}
			},
			allowBlank: false,
			format: 'd.m.Y',
			submitFormat: 'Y-m-d'
		}),
		text: 'Til dato',
		renderer: Ext.util.Format.dateRenderer('d.m.Y'), 
		sortable: true,
		width: 80
	};

	var termin = {
		dataIndex: 'termin',
		editor: Ext.create('Ext.form.field.Text', {
			listeners: {
			   render: function(c) {
				  c.getEl().on({
					keydown: tastetrykk,
					scope: c
				  });
				}
			},
			allowBlank: false
		}),
		text: 'termin',
		flex: 1,
		id: 'termin',
		sortable: true,
		width: 50
	};

	var kWh = {
		align: 'right',
		dataIndex: 'kWh',
		editor: Ext.create('Ext.form.field.Number', {
			listeners: {
			   render: function(c) {
				  c.getEl().on({
					keydown: tastetrykk,
					scope: c
				  });
				}
			},
			selectOnFocus: true,
			allowBlank: true,
			allowDecimals: true,
			allowNegative: false,
			decimalPrecision: 0,
			decimalSeparator: ',',

			hideTrigger: true,
			keyNavEnabled: false,
			mouseWheelEnabled: false,
			selectOnFocus: true
		}),
		text: 'kWh',
		sortable: true,
		renderer: function(value, metaData, record, rowIndex, colIndex, store) {
			if(value) return value;
		},
		width: 50
	};
	
	
	var slett = {
		dataIndex: 'id',
		renderer: function(value, metaData, record, rowIndex, colIndex, store) {
			if(value) {
				return "<a style=\"cursor: pointer\" title=\"Slett denne fakturaen\" onClick=\"bekreftSletting('" + record.data.fakturanummer + "')\"><img src=\"../bilder/slett.png\" /></a>";
			}
		},
		sortable: false,
		width: 30
	};


	fordelFaktura = function(faktura) {
		Ext.Ajax.request({
			params: {
				faktura: faktura
			},
			waitMsg: 'Vent litt...',
			url: 'index.php?oppslag=fs_fakturaer&oppdrag=hentdata&data=manuelle_deler',
			
			failure: function(response,options) {
				Ext.MessageBox.alert('Whoops! Problemer...','Oppnår ikke kontakt med databasen! Prøv igjen senere.');
			},
			
			success: function( response, options ) {
				var tilbakemelding = Ext.JSON.decode(response.responseText);
				
				if(tilbakemelding['success'] == true) {
					var deler = tilbakemelding.data;
					var fastdel = {};
					var skjemainnhold = [];
					skjemainnhold.push( Ext.create('Ext.form.field.Display', {
						hideLabel: true,
						value: '<b>Følgende beløp må fastsettes manuelt</b> (foreslåtte beløp er fra forrige fordeling):<br /><br />'
					}));

					for (var i = 0; i < tilbakemelding.data.length; i++) {

						skjemainnhold.push( Ext.create('Ext.form.field.Display', {
							hideLabel: true,
							value: (deler[i].beskrivelse) + ':<br />Faktura ' + deler[i].fakturanummer + '. Fakturabeløp som skal fordeles: ' + Ext.util.Format.noMoney(deler[i].fakturabeløp) + '.<br />'
						}));

						var fastdel = Ext.create('Ext.form.field.Number', {
							name: 'fastdel' + deler[i].fordeling,
							fieldLabel: 'Beregnet andel',
							allowBlank: false,
							allowDecimals: false,
							minValue: deler[i].minbeløp,
							maxValue: deler[i].maksbeløp,
							allowNegative: true,
							value: deler[i].fastbeløp,
							blankText: 'Du må angi et beløp',
							decimalPrecision: 2,
							decimalSeparator: ',',
							width: 500,

							hideTrigger: true,
							keyNavEnabled: false,
							mouseWheelEnabled: false
						});
						skjemainnhold.push( fastdel );
					}
				}
				var fasteAndeler = {};
				
				var fastandelsvindu = Ext.create('Ext.window.Window', {
					title: 'Oppgi manuelt beregnet andel',
					layout: 'form',
					items: skjemainnhold,
					height: 400,
					width: 600,
					modal: true,
					autoScroll: true,
				
					buttons: [{
						scale:		'large',
						text:		'Avbryt',
						handler:	function(btn, pressed) {
							fastandelsvindu.close();
						}
					}, {
						scale:		'large',
						text:		'Fordel',
						tooltip:	'',
						handler:	function(btn, pressed) {
							for (var i = 0; i < skjemainnhold.length; i++) {
								if(skjemainnhold[i].getName().substring(0, 7) == 'fastdel') {
									fasteAndeler[skjemainnhold[i].getName()] = skjemainnhold[i].getValue();
								}
							}
							fastandelsvindu.close();
							sendTilFordeling(faktura, fasteAndeler);
						}
					}]
				});
				if( tilbakemelding.data.length ) {
					fastandelsvindu.show();
				}
				else {
					sendTilFordeling(faktura);
				}
			}
		});
	}


	sendTilFordeling = function(faktura, fasteAndeler) {
		if (fasteAndeler === undefined ) {
			fasteAndeler = {};
		}
		fasteAndeler.faktura = faktura;

		Ext.Ajax.request({
			url: 'index.php?oppslag=fs_fakturaer&oppdrag=manipuler&data=fordeling',
			waitMsg: 'Vent litt...',

			params: fasteAndeler,

			failure: function( response, options ) {
				Ext.MessageBox.alert('Whoops! Problemer...','Oppnår ikke kontakt med databasen! Prøv igjen senere.');
			},
			
			success: function( response,options ) {
				var tilbakemelding = Ext.JSON.decode(response.responseText);
				if(tilbakemelding['success'] == true) {
					lastData();
				}
				var responsvindu = Ext.create('Ext.window.Window', {
					title: 'Utført',
					html: tilbakemelding.msg,
					height: 300,
					width: 600,
					autoScroll: true,
					
					buttons: [
						{
							text: 'Lukk',
							handler: function(button, event) {
								responsvindu.close();
							}
						}
					]

				});
				
//				oppdaterDetaljer( rutenett.getSelectionModel().getSelection()[0] );
				responsvindu.show();
			}
		});
	}


	varslefordeling = function(faktura) {
		Ext.Ajax.request({
			params: {
			'faktura': faktura,
			'strømfordelingstekst': strømfordelingstekst.getValue()
			},
			waitMsg: 'Vent litt...',
			url: 'index.php?oppslag=fs_fakturaer&oppdrag=manipuler&data=meldfordeling',
			failure:function(response, options) {
				Ext.MessageBox.alert('Whoops! Problemer...','Oppnår ikke kontakt med databasen! Prøv igjen senere.');
			},
			success:function(response, options) {
				var tilbakemelding = Ext.JSON.decode(response.responseText);
				if(tilbakemelding['success'] == true) {
					Ext.MessageBox.alert('Utført', tilbakemelding.msg, function() {
						datasett.load({
							params: {
								start: 0,
								limit: 100
							}
						});
					});
				}
				else {
					Ext.MessageBox.alert('Hmm..',tilbakemelding['msg']);
				}
			}
		});
		strømfordelingsvindu.hide();
	}


	krevfordeling = function(faktura) {
		Ext.Ajax.request({
			params: {
				'faktura': faktura
			},
			waitMsg: 'Vent litt...',
			url: 'index.php?oppslag=fs_fakturaer&oppdrag=manipuler&data=krevfordeling',
			failure:function(response, options) {
				Ext.MessageBox.alert('Whoops! Problemer...', 'Oppnår ikke kontakt med databasen! Prøv igjen senere.');
			},
			success: function(response,options) {
				var tilbakemelding = Ext.JSON.decode(response.responseText);
				if(tilbakemelding['success'] == true) {
					Ext.MessageBox.alert('Utført', tilbakemelding.msg, function() {
						datasett.load({
							params: {
								start: 0,
								limit: 100
							}
						});
					});
				}
				else {
					Ext.MessageBox.alert('Hmm..', tilbakemelding['msg']);
				}
			}
		});
	}


	slettfordeling = function(faktura) {
		Ext.Ajax.request({
			params: {'faktura': faktura},
			waitMsg: 'Vent litt...',
			url: 'index.php?oppslag=fs_fakturaer&oppdrag=manipuler&data=slettfordeling',
			failure:function(response, options) {
				Ext.MessageBox.alert('Whoops! Problemer...', 'Oppnår ikke kontakt med databasen! Prøv igjen senere.');
			},
			success:function(response, options) {
				var tilbakemelding = Ext.JSON.decode(response.responseText);
				if(tilbakemelding['success'] == true) {
					Ext.MessageBox.alert('Utført', tilbakemelding.msg, function() {
						lastData();
					});
				}
				else {
					Ext.MessageBox.alert('Hmm..',tilbakemelding['msg']);
				}
			}
		});
	}


	bekreftSletting = function(faktura) {
		Ext.Msg.show({
			title: 'Bekreft',
			id: id,
			msg: 'Er du sikker på at du vil slette faktura ' + faktura + '?',
			buttons: Ext.Msg.OKCANCEL,
			fn: function(buttonId, text, opt) {
				if(buttonId == 'ok') {
					slettfaktura(faktura);
				}
			},
			animEl: 'elId',
			icon: Ext.MessageBox.QUESTION
		});
	}


	slettfaktura = function(faktura) {
		Ext.Ajax.request({
			params: {
				'faktura': faktura
			},
			waitMsg: 'Vent litt...',
			url: 'index.php?oppslag=fs_fakturaer&oppdrag=manipuler&data=slettfaktura',
			failure:function(response, options) {
				Ext.MessageBox.alert('Whoops! Problemer...', 'Oppnår ikke kontakt med databasen! Prøv igjen senere.');
			},
			success: function(response, options) {
				var tilbakemelding = Ext.JSON.decode(response.responseText);
				if(tilbakemelding['success'] == true) {
					Ext.MessageBox.alert('Utført', tilbakemelding.msg, function() {
						datasett.load({
							params: {
								start: 0,
								limit: 100
							}
						});
					oppdaterDetaljer();
					});
				}
				else {
					Ext.MessageBox.alert('Hmm..',tilbakemelding['msg']);
				}
			}
		});
	}


	function lagreEndringer(grid, e) {
		if(
			e.originalValue != null
			&& e.value.toString() == e.originalValue.toString()
		) {
			return true;
		}
		
		if (false && e.value instanceof Date) {
			var verdi = e.value.toString('Y-m-d H:i:s');
			if(e.record.modified[e.field]) {
				var opprinnelig = Ext.util.Format.date(e.record.modified[e.field], 'Y-m-d H:i:s');
			}
		}
		else {
			var verdi = e.value;
			var opprinnelig = e.originalValue;
		}
		var felt = e.field;
		
		Ext.Ajax.request({
				waitMsg: 'Feltet lagres...',
				url: 'index.php?oppslag=fs_fakturaer&oppdrag=taimotskjema&skjema=oppdatering',
				params: {
					id: e.record.get('id'),
					felt: felt,
					verdi: verdi,
					opprinnelig: opprinnelig
				},
				
				failure:function(response, options) {
					Ext.MessageBox.alert('Whoops! Problemer...', 'Klarte ikke å lagre endringen.<br />Kan du ha mistet nettforbindelsen?');
				},
				
				success: function(response, options) {
					var tilbakemelding = Ext.JSON.decode(response.responseText);
					if(tilbakemelding['success'] == true) {
						if(!tilbakemelding['fradato']) {
							e.record.set('fradato', null);
						}
						else {
							e.record.set('fradato', new Date(tilbakemelding['fradato']));
						}
						if(!tilbakemelding['tildato']) {
							e.record.set('tildato', null);
						}
						else {
							e.record.set('tildato', new Date(tilbakemelding['tildato']));
						}
						e.record.set('id', tilbakemelding['id']);
						e.record.set('fakturanummer', tilbakemelding['fakturanummer']);
						e.record.set('fakturabeløp', tilbakemelding['fakturabeløp']);
						e.record.set('anleggsnr', tilbakemelding['anleggsnr']);
						e.record.set('bruk', tilbakemelding['bruk']);
						e.record.set('termin', tilbakemelding['termin']);
						e.record.set('kWh', tilbakemelding['kWh']);
						e.record.set('html', tilbakemelding['html']);
						e.record.set('varslet', tilbakemelding['varslet']);
						e.record.set('varslet', tilbakemelding['varslet']);
						e.record.set(tilbakemelding['felt'], tilbakemelding['verdi']);

						datasett.commitChanges();
						oppdaterDetaljer(e.record);
						if(tilbakemelding.msg) {
							Ext.MessageBox.alert('Obs!', tilbakemelding.msg);
						}
					}
					else {
						Ext.MessageBox.alert('Advarsel!',tilbakemelding['msg']);
						
					}
				}
			}
		);
	};


	var rutenett = Ext.create('Ext.grid.Panel', {
		autoScroll: true,
		layout: 'border',
		renderTo: 'panel',

		plugins: [cellEditing],
		selType: 'cellmodel',
		
		viewConfig: {
		},

		store: datasett,
		title: '<?=$this->tittel?>',
		
		dockedItems: [{
			xtype: 'pagingtoolbar',
			store: datasett,
			dock: 'bottom',
			displayInfo: true,
			displayMsg: 'Klikk [F2] for å kopiere en verdi fra linjen over. Viser linje {0} - {1} av {2}',
		}],

		buttons: [{
			scale: 'large',
			text: 'Legg til<br />blanke linjer',
			tooltip: 'Klikk her for å få flere blanke linjer å registrere nye strømregninger på.',
			handler: function(btn, pressed) {
				datasett.add({}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {});
			}
		}, 
		{
			scale: 'large',
			text: 'Lag forslag til fordeling<br />av nye fakturaer',
			tooltip: 'Klikk her for å få leiebasen til å foreslå fordeling i henhold til fordelingsnøklene.<br />Bare fakturaer som mangler fordelingsforslag vil bli berørt.',
			handler: function(button, event) {
				fordelFaktura();
			}
		}, 
		{
			scale: 'large',
			text: 'Send epost med nye<br />forslag til deltakerne',
			tooltip: 'Klikk her for å sende epost til alle berørte beboere om hvordan fakturaene er foreslått fordelt.<br />Gjelder kun beboere som har brukerprofil i leiebasen, og som har valgt å motta epostvarsler.<br />Det vil kun sendes <i>én</i> epost til hver beboer, selv om du klikker flere ganger, med mindre fordelinga forkastes og beregnes på nytt.',
			handler: function(button, event) {
				strømfordelingsvindu.show();
			}
		}, 
		{
			scale: 'large',
			text: 'Skriv rapport over<br />nye fordelinger',
			tooltip: 'Klikk her for å lage skrive ut oversikter over hvordan fakturaene er foreslått fordelt.',
			handler: function() {
				window.open("index.php?oppslag=fs_fakturaer&oppdrag=manipuler&data=skrivfordeling");
			}
		}, 
		{
			scale: 'large',
			text: 'Godta alle forslag og<br />krev inn andelene',
			tooltip: 'Klikk her for å kreve inn fordelingene som er beregnet. Fakturaene flyttes til arkivet og kan ikke lenger endres eller fordeles på nytt.<br >Det kan være lurt å vente med å bekrefte fordelingene til beboerne er varslet og hatt mulighet til å komme med tilbakemeldinger, for å forhindre feilfordelinger.',
			handler: function(button, event) {
				krevfordeling();
			}
		}],
		
		columns: [
			id,
			vis,
			fakturanummer,
			fakturabeløp,
			anleggsnr,
			fradato,
			tildato,
			termin,
			kWh,
			varslet,
			slett
		],
		
		height: 500,
		width: 620,
		stripeRows: true
    });
	cellEditing.on('edit', lagreEndringer);

	// Oppretter detaljpanelet
	var detaljpanel = Ext.create('Ext.panel.Panel', {
		frame: false,
		height: 500,
		items: [
			{
				autoScroll: true,
				id: 'detaljfelt',
				region: 'center',
				bodyStyle: {
					background: '#ffffff',
					padding: '7px'
				},
				html: 'Velg en faktura i listen til venstre for å se fordelingen.'
			}
		],
		layout: 'border',
		renderTo: 'detaljpanel',
		title: '',
		width: 280
	})


	// Hva skjer når du klikker på ei linje i rutenettet?:
	rutenett.getSelectionModel().on('selectionchange', function(selectionModel, selected, eOpts) {
		oppdaterDetaljer(selected[0]);
	});
	
	lastData();


});
<?
}

function design() {
?>
<table style="text-align: left; width: 900px;" border="0" cellpadding="2" cellspacing="0">
<tbody>
<tr>
<td style="vertical-align: top;">
<div id="panel"></div>
<td style="vertical-align: top;">
<div id="detaljpanel"></div>
</td>
</tr>
</tbody>
</table>
<?
}


function hentData($data = "") {
	$tp = $this->mysqli->table_prefix;
	$sort		= @$_GET['sort'];
	$synkende	= @$_GET['dir'] == "DESC" ? true : false;
	$start		= (int)@$_GET['start'];
	$limit		= @$_GET['limit'];

	switch ($data) {

	case "anleggsnummer": {
		return json_encode( $this->mysqli->arrayData(array(
			'source'	=> "fs_fellesstrømanlegg",
			'fields'	=> "anleggsnummer, CONCAT(anleggsnummer, ' (målernr: ', målernummer, '): ', formål) AS anlegg",
			'where'		=>	isset( $_GET['query'] )
							? "anleggsnummer LIKE '%{$this->GET['query']}%'
							   OR målernummer  LIKE '%{$this->GET['query']}%'"
							: null
		)) );
		break;
	}

	case "manuelle_deler": {
		$resultat =  $this->mysqli->arrayData(array(
			'distinct'	=> true,
			'source'	=> "{$tp}fs_fordelingsnøkler AS fs_fordelingsnøkler\n
							INNER JOIN {$tp}fs_fellesstrømanlegg AS fs_fellesstrømanlegg ON fs_fordelingsnøkler.anleggsnummer = fs_fellesstrømanlegg.anleggsnummer\n
							INNER JOIN {$tp}fs_originalfakturaer AS fs_originalfakturaer ON fs_fellesstrømanlegg.anleggsnummer = fs_originalfakturaer.anleggsnr\n",

			'fields'	=> "concat(fs_originalfakturaer.id, '-', fs_fordelingsnøkler.nøkkel) AS fordeling,
							fs_fordelingsnøkler.nøkkel,
							fs_fordelingsnøkler.følger_leieobjekt,
							fs_fordelingsnøkler.leieobjekt,
							fs_fordelingsnøkler.leieforhold,
							fs_fordelingsnøkler.fastbeløp,
							fs_fordelingsnøkler.forklaring,
							fs_originalfakturaer.fakturanummer,
							fs_originalfakturaer.fakturabeløp,
							fs_originalfakturaer.fradato,
							fs_originalfakturaer.tildato",
							
			'where'		=>	"fs_fordelingsnøkler.fordelingsmåte = 'Fastbeløp'\n"
						.	"AND\n"
						.	"!fs_originalfakturaer.fordelt\n"
						.	(
							@$_POST['faktura']
							? "AND fs_originalfakturaer.fakturanummer = '{$this->POST['faktura']}'\n"
							: "AND !fs_originalfakturaer.beregnet\n"
						)
		));
		
		foreach( $resultat->data as $andel ) {
			if( $andel->følger_leieobjekt ) {
				$leieobjekt = $this->hent('Leieobjekt', $andel->leieobjekt );
				$utleie = $leieobjekt->hentUtleie( $andel->fradato, $andel->tildato )->faktiskeLeieforhold;
				$navn = array();
				foreach( $utleie as $leieforhold ) {
					$navn[] = $leieforhold->hent('navn');
				}
				$andel->beskrivelse = ucfirst(
					"{$leieobjekt->hent('type')} nr.&nbsp;{$leieobjekt}; {$leieobjekt->hent('beskrivelse')} (" . $this->liste( $navn ) . ")"
					.	( $andel->forklaring ? "<br />{$andel->forklaring}" : "" )
				);
				$andel->minbeløp = min(0, $andel->fakturabeløp);
				$andel->maksbeløp = max(0, $andel->fakturabeløp);
			}

			else {
				$leieforhold = $this->hent('Leieforhold', $andel->leieforhold );
				$andel->beskrivelse = ucfirst(
					"Leieforhold nr.&nbsp;{$leieforhold}; {$leieforhold->hent('beskrivelse')}"
					.	( $andel->forklaring ? "<br />{$andel->forklaring}" : "" )
				);
			}
		}
		
		return json_encode( $resultat );
		
		break;
	}

	default: {
		$sort	= @$_POST['sort' ];
		$start	= @$_POST['start'];
		$limit	= @$_POST['limit'];
		
		$resultat = $this->mysqli->arrayData(array(
			'distinct'		=> true,
			'source'		=> "{$tp}fs_originalfakturaer AS fs_originalfakturaer\n
							LEFT JOIN {$tp}fs_fellesstrømanlegg AS fs_fellesstrømanlegg ON fs_originalfakturaer.anleggsnr = fs_fellesstrømanlegg.anleggsnummer\n
							LEFT JOIN {$tp}fs_andeler AS fs_andeler ON fs_originalfakturaer.id = fs_andeler.faktura_id\n",
			'where'			=> "!fordelt",
			'fields'		=> "fs_originalfakturaer.*, fs_fellesstrømanlegg.formål AS bruk, MAX(fs_andeler.epostvarsel) AS varslet",
			'groupfields'	=> "fs_originalfakturaer.id",
			'orderfields'	=> (
				$sort
				? "CAST({$this->POST['sort']} AS SIGNED) {$this->POST['dir']}, {$this->POST['sort']} {$this->POST['dir']}\n"
				: "id DESC"
			),
			'limit'		=> (
				$start
				? (
					$limit
					? ("{$this->POST['start']}, {$this->POST['limit']}")
					: $this->POST['start']
				)
				: ""
			)
		));
					

		foreach($resultat->data as $faktura) {
			settype($faktura->id, 'integer');
			$faktura->fordeltsum = 0;
			$faktura->fordeltsum_html = "";
			$faktura->html = "";
			$tabell = "";
			
			$faktura->fordeling = $this->mysqli->arrayData(array(
				'source'	=> "fs_andeler",
				'where'		=> "faktura_id = {$faktura->id}",
				'fields'	=> "kontraktnr, SUM(beløp) as beløp, COUNT(epostvarsel) AS epostvarsel",
				'groupfields'	=> "kontraktnr",
				'orderfields'	=> "kontraktnr",
			))->data;
			
			
			$tabell = "";
			foreach($faktura->fordeling as $del) {
			
				$faktura->fordeltsum += ($del->kontraktnr ? $del->beløp : 0);
				$faktura->fordeltsum_html = "kr.&nbsp;" . str_replace(' ', '&nbsp;', number_format($faktura->fordeltsum, 2, ",", " "));
				
				$del->beløp_html = "kr.&nbsp;" . str_replace(' ', '&nbsp;', number_format($del->beløp, 2, ",", " "));
				$del->personer = ($del->kontraktnr ? $this->liste($this->kontraktpersoner($del->kontraktnr)) : "<i>Kreves ikke inn</i>");
			
				$tabell .= "<tr>\n"
					.	"\t<td>{$del->personer}</td>\n"
					.	"\t<td style=\"text-align: right; width: 100px;\">{$del->beløp_html}</td>"
					.	"</tr>\n";
			}
			$tabell .= "";
			
			
			$faktura->fakturabeløp_html = "kr.&nbsp;" . str_replace(' ', '&nbsp;', number_format($faktura->fakturabeløp, 2, ",", " "));
			
			
			$faktura->fordelingstekst = "<b>Fordeling</b> (av {$faktura->fakturabeløp_html})\n"
				.	"<table style=\"width: 100%;\"><tbody>\n"
				.	$tabell
				.	"</tbody>\n<tfooter><tr><td>Sum</td><td style=\"width: 100px; font-weight: bold; text-align: right;\">{$faktura->fordeltsum_html}</td></tr></tfooter></table>";

			
			if(count($faktura->fordeling)) {
				$faktura->html
				.= "<a style=\"cursor: pointer; text-decoration: underline;\" onClick=\"slettfordeling('{$faktura->fakturanummer}')\" title=\"Klikk her for å slette fordelingsberegninga av faktura {$faktura->fakturanummer}\">Avvis forslaget</a><br />"
				.	"<a style=\"cursor: pointer; text-decoration: underline;\" onClick=\"fordelFaktura('{$faktura->fakturanummer}')\" title=\"Klikk her for å beregne fordelinga av faktura {$faktura->fakturanummer} på nytt.\">Beregn fordelinga på nytt</a><br /><br />"
				.	"<a title=\"Klikk for å sende dette forslaget med e-post til de registrerte beboere som berøres.\" style=\"cursor: pointer; text-decoration: underline;\" onClick=\"varslefordeling('{$faktura->fakturanummer}')\">Send fordelingsforslaget som e-post</a><br />"
				.	"<a style=\"cursor: pointer; text-decoration: underline;\" onClick=\"window.open('index.php?oppslag=fs_fakturaer&oppdrag=manipuler&data=skrivfordeling&faktura={$faktura->fakturanummer}');\" title=\"Vis PDF av fordelinga av faktura {$faktura->fakturanummer} for utskrift.\">Vis forslagene som PDF</a><br /><br />"
				.	"<a style=\"cursor: pointer; text-decoration: underline;\" onClick=\"krevfordeling('{$faktura->fakturanummer}')\" title=\"Klikk her for å låse fordelinga av faktura {$faktura->fakturanummer} og kreve inn andelene fra beboerne.\">Godta forslaget og krev inn</a><br />\n"
				.	"Fakturaen foreslås fordelt som følger:<br /><br />"
				.	"{$faktura->fordelingstekst}<br />"
				;
			}
			else if($faktura->id) {
				$faktura->html .= (
					($faktura->fakturanummer and $faktura->anleggsnr and $faktura->fradato and $faktura->tildato)
					? "<a style=\"cursor: pointer; text-decoration: underline;\" onClick=\"fordelFaktura('{$faktura->fakturanummer}')\">Lag forslag til fordeling av fakturaen</a><br />"
					: ""
				);
			}
			
		}
		
		$resultat->data =  $this->sorterObjekter($resultat->data, $sort, $synkende);
		return json_encode($resultat);
		break;
	}
	}
}


function taimotSkjema($skjema = "") {

	switch ($skjema) {
	
	case "oppdatering":
		$id = (int)$_POST['id'];
		$felt = $this->POST['felt'];
		$verdi = $this->POST['verdi'];
		$msg = "";

		$opprinnelig = $this->arrayData("SELECT * FROM fs_originalfakturaer WHERE id = '$id'");
		if($id) {
			$opprinnelig = $opprinnelig['data'][0];
		}
		else {
			$opprinnelig = array(
				'fordelt'	=> false
			);
		}
		
		// Slett evt tidligere fordeling
		if($id) {
			$sql =	"
				DELETE fs_andeler
				FROM fs_andeler LEFT JOIN fs_originalfakturaer ON fs_andeler.faktura = fs_originalfakturaer.fakturanummer
				WHERE fs_andeler.krav IS NULL AND !fs_originalfakturaer.fordelt AND fs_andeler.faktura = '{$opprinnelig['fakturanummer']}'";
			$this->mysqli->query($sql);
		}

		// sjekk om fakturanummeret er brukt fra før
		if($felt == 'fakturanummer') {
			$kontroll = $this->arrayData("SELECT * FROM fs_originalfakturaer WHERE fakturanummer = '$verdi'" . ($id ? " AND id != '$id'" : ""));
			if(count($kontroll['data'])) {
				$resultat['success'] = false;
				$resultat['msg'] = "Kan ikke lagre.<br />Faktura '$verdi' er allerede registrert.<br />";
				echo json_encode($resultat);
				break;
			}
		}

		if(!isset($_POST['id']) or !$felt) {
			$resultat['success'] = false;
			$resultat['msg'] = "Lagring feilet. Databasen fikk ikke beskjed om enten hvilken kolonne eller hvilken linje som skulle oppdateres. Prøv igjen. Om problemet gjentar seg bør du gi beskjed til programansvarlig.";
			echo json_encode($resultat);
			break;
		}
		if (!$id) {
			$oppdateringssql = "INSERT INTO fs_originalfakturaer";
		}
		else {
			$oppdateringssql = "UPDATE fs_originalfakturaer";
			if($this->mysqli->arrayData(array(
				'source' =>	"fs_originalfakturaer",
				'where' =>	"id = '{$this->POST['id']}'"
			))->totalRows != 1) {
				$resultat['success'] = false;
				$resultat['msg'] = "Linjen du prøver å lagre ser ut til å ha blitt slettet. Prøv å laste listen på nytt.<br />$oppdateringssql";
				echo json_encode($resultat);
				break;
			}
		}
		$oppdateringssql .= " SET fs_originalfakturaer.{$this->POST['felt']} = " . $this->strengellernull($verdi);
		if((int)$id == 0)
			$oppdateringssql .= ", fs_originalfakturaer.lagt_inn_av = '{$this->bruker['navn']}'";
			
		if ($id) {
			$oppdateringssql .= " WHERE fs_originalfakturaer.id = '$id' AND !fs_originalfakturaer.fordelt";
		}

		if(!$this->mysqli->query($oppdateringssql)) {
			$resultat['success'] = false;
			$resultat['msg'] = "Klarte ikke å lagre denne verdien i databasen.<br />
			'$oppdateringssql'<br /><br />
			Feilmeldingen fra databasen lyder:<br />" . $this->mysqli->error;
			echo json_encode($resultat);
			break;
		}
		if($opprinnelig['fordelt']) {
			$msg = "Denne oppføringa kan ikke redigeres fordi den allerede har blitt fordelt på beboerne";
		}
		if ((int)$id == 0) {
			$id = $this->mysqli->insert_id;
		}
		
		$resultat = $this->arrayData("
			SELECT fs_originalfakturaer.*, fs_fellesstrømanlegg.formål AS bruk
			FROM fs_originalfakturaer LEFT JOIN fs_fellesstrømanlegg ON fs_originalfakturaer.anleggsnr = fs_fellesstrømanlegg.anleggsnummer
			WHERE id = $id
		");
		foreach($resultat['data'] as $index=>$linje) {
			$html = "";
			$sql = "SELECT kontraktnr, SUM(beløp) as beløp, COUNT(epostvarsel) AS epostvarsel FROM fs_andeler WHERE faktura = '{$linje['fakturanummer']}' GROUP BY kontraktnr ORDER BY kontraktnr";
			$fordeling = $this->arrayData($sql);
			$sum = 0;
			$fordelingstekst = "<b>Fordeling</b> (av kr. " . number_format($linje['fakturabeløp'], 2, ",", " ") . ")\n<table><tbody style=\"width: 100%;\">";
			foreach($fordeling['data'] as $del) {
				$fordelingstekst .= "<tr><td>" . $this->liste($this->kontraktpersoner($del['kontraktnr'])) . ($del['epostvarsel'] ? " <span title=\"\">✉</span>" : "") . "</td><td style=\"text-align: right; width: 100px;\">" . ($linje['fordelt'] ? "": "<a>") . "kr. " . number_format($del['beløp'], 2, ",", " ") . ($linje['fordelt'] ? "": "</a>") . "</td></tr>\n";
				$sum += $del['beløp'];
			}
			$fordelingstekst .= "</tbody>\n<tfooter><tr><td>Sum</td><td style=\"width: 100px; font-weight: bold; text-align: right;\">kr. " . number_format($sum, 2, ",", " ") . "</td></tr></tfooter></table>";
			if($linje['fordelt']) {
				$html .= "Fakturaen er fordelt og kreves inn fra beboerne.<br />\nFordelingen kan ikke endres.<br /><br />\n$fordelingstekst<br /><a style=\"cursor: pointer; text-decoration: underline;\" onClick=\"window.open('index.php?oppslag=fs_fakturaer&oppdrag=manipuler&data=skrivfordeling&faktura={$linje['fakturanummer']}');\">Vis fordelingen som PDF</a><br />\n";
			}
			else if(count($fordeling['data'])) {
				$html .= "Fakturaen foreslås fordelt som vist under.<br /><br />$fordelingstekst<br /><a style=\"cursor: pointer; text-decoration: underline;\" onClick=\"slettfordeling('{$linje['fakturanummer']}')\" title=\"Klikk her for å slette fordelingsberegningen av faktura {$linje['fakturanummer']}\">Fjern fordelingsforslaget</a><br /><a style=\"cursor: pointer; text-decoration: underline;\" onClick=\"fordelFaktura('{$linje['fakturanummer']}')\">Beregn fordelinga på nytt</a><br /><br /><a title=\"Klikk for å sende dette forslaget med e-post til de registrerte beboere som berøres.\" style=\"cursor: pointer; text-decoration: underline;\" onClick=\"varslefordeling('{$linje['fakturanummer']}')\">Send fordelingsforslaget som e-post</a><br /><a style=\"cursor: pointer; text-decoration: underline;\" onClick=\"window.open('index.php?oppslag=fs_fakturaer&oppdrag=manipuler&data=skrivfordeling&faktura={$linje['fakturanummer']}');\">Vis forslagene som PDF</a><br />\n";
			}
			else{
				$html .= (($linje['fakturanummer'] and $linje['anleggsnr'] and $linje['fradato'] and $linje['tildato']) ? "<a style=\"cursor: pointer; text-decoration: underline;\" onClick=\"fordelFaktura('{$linje['fakturanummer']}')\">Beregn fordeling av fakturaen</a>.<br /><br />" : "");
			}
			$resultat['data'][$index]['html'] = $html;
		}
		$resultat = $resultat['data'][0];
		$resultat['sql'] = $oppdateringssql;
		$resultat['felt'] = $felt;
		$resultat['verdi'] = $resultat[$felt];
		$resultat['opprinnelig'] = $opprinnelig;
		$resultat['msg'] = $msg;

		$resultat['success'] = true;
		echo json_encode($resultat);
		break;
	}
}


function manipuler( $data ) {
	$tp = $this->mysqli->table_prefix;

	switch ( $data ) {
	
	case "fordeling":
		// Dersom det ikke er angitt en bestemt faktura som skal fordeles,
		//	så hentes alle fakturaer som ikke er foreslått fordelt.
		//	Fakturanummerene lagres i arrayet $sett.
		$sett = $this->mysqli->arrayData(array(
			'distinct'	=>	true,
			'flat'		=>	true,
			'source'	=>	"{$tp}fs_originalfakturaer AS fs_originalfakturaer\n",
			'fields'	=>	"fs_originalfakturaer.id",
			'where'		=>	"!fordelt\n"
						.	(
							@$_POST['faktura']
							? "AND fakturanummer = '{$this->POST['faktura']}'"
							: "AND !fs_originalfakturaer.beregnet\n"
						)
		))->data;
		// Arrayet $sett inneholder nå alle fakturaer som skal fordeles.

		$manuelleBeløp = array();
		foreach($_POST as $parameter => $verdi) {
			if( strpos($parameter, 'fastdel') === 0) {
				$parameter = explode('-', substr($parameter, 7));
				$manuelleBeløp[ $parameter[0] ][ $parameter[1] ] = $verdi;
			}
		}
		
		$resultat = $this->fsLagFordelingsforslag( $sett, $manuelleBeløp );

		if(count( $resultat->fordelt and !$resultat->msg )) {
//				$resultat->msg = "Faktura " . $this->liste($resultat->fordelt) . " er fordelt.";
		}
		else if(!$resultat->msg) {
			$resultat->msg = "Ingen strømregninger kunne fordeles";
			$resultat->success = false;
		}
		
		echo json_encode($resultat);
		break;
		
	case "meldfordeling":
		if(isset($_POST['strømfordelingstekst'])) {
			$this->mysqli->query("UPDATE valg SET verdi = '{$this->POST['strømfordelingstekst']}' WHERE innstilling = 'strømfordelingstekst'");
			$this->hentValg();
		}
		
		if(@$_POST['faktura']) {
			$sett = array($_POST['faktura']);
		}
		else{
			$sett = $this->mysqli->arrayData(array(
				'flat'		=> true,
				'distinct'	=> true,
				'source'	=>	"{$tp}fs_originalfakturaer AS fs_originalfakturaer",
				'fields'	=>	"fakturanummer",
				'where'		=> "beregnet and !fordelt"
			))->data;			
		}
		
		if( count($sett) ) {
			$this->fs_meldFordelingsforslag($sett);
			$resultat['msg'] = "Epostmeldinger er sendt";
			$resultat['success'] = true;
		}
		else{
			$resultat['msg'] = "Ingen meldinger er sendt";
			$resultat['success'] = false;
		}
		echo json_encode($resultat);
		break;
		
	case "skrivfordeling":
		if(@$_POST['faktura']) {
			$sett = array($_POST['faktura']);
		}
		else{
			$sett = $this->mysqli->arrayData(array(
				'flat'		=> true,
				'distinct'	=> true,
				'source'	=>	"{$tp}fs_originalfakturaer AS fs_originalfakturaer",
				'fields'	=>	"fakturanummer",
				'where'		=> "beregnet and !fordelt"
			))->data;			
		}
		
		$this->fs_skrivFordelingsforslag( $sett );
		break;
		
	case "krevfordeling":
		if(@$_POST['faktura']) {
			$sett = array($_POST['faktura']);
		}
		else{
			$sett = $this->mysqli->arrayData(array(
				'flat'		=> true,
				'distinct'	=> true,
				'source'	=>	"{$tp}fs_originalfakturaer AS fs_originalfakturaer",
				'fields'	=>	"fakturanummer",
				'where'		=> "beregnet and !fordelt"
			))->data;			
		}
		
		if( count($sett) ) {
			$resultat['msg'] = "Opprettet " . $this->fs_krevFordelingsforslag($sett) . " krav.";
			$resultat['success'] = true;
		}
		else{
			$resultat['msg'] = "Ingen nye fordelinger å legge inn krav for";
			$resultat['success'] = false;
		}
		echo json_encode($resultat);
		break;
		
	case "slettfordeling":
		$sql =	"DELETE FROM fs_andeler WHERE krav IS NULL AND faktura = '" . $this->POST['faktura'] . "'";
		if($this->mysqli->query($sql)) {

			// Registrer at fakturaene ikke er fordelt
			$this->mysqli->saveToDb(array(
				'table'		=> "fs_originalfakturaer",
				'fields'	=> array(
					'beregnet'	=> 0
				),
				'where'		=> "fs_originalfakturaer.fordelt = 0 AND (fs_originalfakturaer.fakturanummer = '{$this->POST['faktura']}')",
				'update'	=> true
			));

			$resultat['msg'] = "Fordelingen av faktura " . $this->POST['faktura'] . " har blitt slettet";
			$resultat['success'] = true;
		}
		else{
			$resultat['msg'] = "Klarte ikke slette fordelingen. Meldingen fra database lyder:<br />" . $this->mysqli->error;
			$resultat['success'] = false;
		}
		echo json_encode($resultat);
		break;
		
	case "slettfaktura":
		$sql =	"DELETE fs_originalfakturaer, fs_andeler FROM fs_originalfakturaer LEFT JOIN fs_andeler ON fs_originalfakturaer.id = fs_andeler.faktura_id WHERE !fordelt AND fakturanummer = '{$this->POST['faktura']}';\n";
		if($this->mysqli->query($sql)) {
			$resultat['msg'] = "Fakturaen har blitt slettet";
			$resultat['success'] = true;
		}
		else{
			$resultat['msg'] = "<b>Klarte ikke slette fakturaen.<br />Sendte følgende spørring til databasen:</b><br />$sql<br /><br /><b>og fikk dette svaret:</b><br />" . $this->mysqli->error;
			$resultat['success'] = false;
		}
		echo json_encode($resultat);
		break;

	}
}


}
?>