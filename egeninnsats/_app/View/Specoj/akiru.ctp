<h1><?php echo ($speco['Speco']['nomo']); ?></h1>

<table><tbody>
	<tr>
		<td><?echo __("ensalutilo");?>:</td>
		<td><strong><?php echo h($speco['Speco']['id']); ?></strong></td>
	</tr>
	<tr>
		<td><?echo __("speco");?>:</td>
		<td><strong><?php echo h($speco['Speco']['nomo']); ?></strong></td>
	</tr>
	<tr>
		<td><?echo __("unuo");?>:</td>
		<td><strong><?php echo h($speco['Speco']['unuo']); ?></strong></td>
	</tr>
</tbody></table>