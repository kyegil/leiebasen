<?php
/**********************************************
Leiebase for Svartlamoen boligstiftelse
av Kay-Egil Hauan
**********************************************/
If(!defined('LEGAL')) die('Ingen adgang - No access!<br />Sjekk at adressen du har oppgitt er riktig.');

class oppsett extends leiebase {

var $sql = "";

function __construct() {
	parent::__construct();
	$this->ext_bibliotek = 'ext-3.4.0';
	$this->kontrollrutiner();
}

function skript() {
	if($_GET['returi'] == "default") $this->returi->reset();
	$this->returi->set();
?>

Ext.onReady(function() {

	var kort = new Ext.Panel({
		height: 500,
		items: [
			{
			layout: 'column',
			border: false,
			items: [
				{
				layout: 'form',
				border: false,
				columnWidth: 0.5,
				items: [
					{
					border: false,
					html: [
						'<p style="vertical-align: top; margin: 6px;"><a href=index.php?oppslag=oversikt_ledigheter><img src="" style="float: left; clear: left; margin: 6px;" />Ledige leieobjekter</a><hr style="clear: left; visibility: hidden; height: 0px;"/></p>'
					]
					}
				]
				},
				{
				layout: 'form',
				border: false,
				columnWidth: 0.5,
				items: [
					{
					border: false,
					html: [
						'<p style="vertical-align: top; margin: 6px;"><a href=index.php?oppslag=oversikt_innbetalinger><img src="" style="float: left; clear: left; margin: 6px;" />Krav og innbetalingsstatistikk</a><hr style="clear: left; visibility: hidden; height: 0px;"/></p>'
						]
					}
				]
				}
			]
			},
			{
			layout: 'column',
			border: false,
			items: [
				{
				layout: 'form',
				border: false,
				columnWidth: 0.5,
				items: [
					{
					border: false,
					html: ''
					}
				]
				},
				{
				layout: 'form',
				border: false,
				columnWidth: 0.5,
				items: [
					{
					border: false,
					html: ''
					}
				]
				}
			]
			}
			],
		title: 'Utdrag og statistikk',
		width: 900
	});

    kort.render('panel');

});
<?
}

function design() {
?>


<div id="panel"></div>
<?
}

}
?>