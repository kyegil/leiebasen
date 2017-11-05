<?
/**********************************************
Kontribuo
de Kay-Egil Hauan
**********************************************/

if(!isset($var)) 			$var			= "kolumnoj";
if(!isset($kolumnoj)) 		$kolumnoj		= array();

$listo = array();
foreach($kolumnoj as $indekso => $kolumno) {
	if(is_array($kolumno)) {
		if(!isset($kolumno['var']))			$kolumno['var']			= "kolumno_" . ($indekso+1);
		if(!isset($kolumno['xtype']))		$kolumno['xtype']		= "gridcolumn";
		if(!isset($kolumno['dataIndex']))	$kolumno['dataIndex']	= $kolumno['var'];
		if(!isset($kolumno['editor']))		$kolumno['editor']		= false;
		if(!isset($kolumno['text']))		$kolumno['text']		= false;
		if(!isset($kolumno['hidden']))		$kolumno['hidden']		= false;
		if(!isset($kolumno['minWidth']))	$kolumno['minWidth']	= false;
		if(!isset($kolumno['width']))		$kolumno['width']		= false;
			else							$kolumno['flex']		= 0;
		if(!isset($kolumno['flex']))		$kolumno['flex']		= 0;
		if(!isset($kolumno['sortable']))	$kolumno['sortable']	= false;
		if(!isset($kolumno['renderer']))	$kolumno['renderer']	= false;
		if(!isset($kolumno['renderColumn']))	$kolumno['renderColumn']	= false;
		if(!isset($kolumno['renderFunction']))	$kolumno['renderFunction']	= false;
?>

var <?=$kolumno['var']?> = Ext.create('Ext.widget', {
	<?=($kolumno['text'] ? "text: '{$kolumno['text']}',\n" : "")?>
	<?=($kolumno['editor'] ? "editor: {$kolumno['editor']},\n" : "")?>
	<?=($kolumno['hidden'] ? "hidden: true,\n" : "")?>
	<?=($kolumno['minWidth'] ? "minWidth: {$kolumno['minWidth']},\n" : "")?>
	<?=($kolumno['width'] ? "width: {$kolumno['width']},\n" : "")?>
<?=($kolumno['renderColumn'] ? "renderer: function(value, metadata, record, rowIndex, colIndex, store, view, ret) {\n\t\treturn record.get('{$kolumno['renderColumn']}');\n\t},\n" : "")?>
	xtype: '<?=$kolumno['xtype']?>',
<?=($kolumno['renderFunction'] ? "renderer: {$kolumno['renderFunction']},\n" : "")?>
<?=($kolumno['renderer'] ? "renderer: function(value, metadata, record, rowIndex, colIndex, store, view, ret) {\n\t\t{$kolumno['renderer']}\n\t},\n" : "")?>
	xtype: '<?=$kolumno['xtype']?>',
	dataIndex: '<?=$kolumno['dataIndex']?>',
	flex: <?=($kolumno['flex'])?>,
	sortable: <?=($kolumno['sortable'] ? "true" : "false")?>
});

<?
		$listo[] = $kolumno['var'];
	}
	else {
		$listo[] = $kolumno;	
	}
}

echo "var {$var} = [
	" . implode(",\n\t", $listo) . "
];\n";
?>

