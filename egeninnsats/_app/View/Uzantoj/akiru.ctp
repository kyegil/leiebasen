<h1><?php echo ($uzanto['Uzanto']['uzanto']); ?></h1>

<table><tbody>
	<tr>
		<td><?echo __("ensalutilo");?>:</td>
		<td><strong><?php echo h($uzanto['Uzanto']['id']); ?></strong></td>
	</tr>
	<tr>
		<td><?echo __("uzanto");?>:</td>
		<td><strong><?php echo h($uzanto['Uzanto']['uzanto']); ?></strong></td>
	</tr>
	<tr>
		<td><?echo __("rolo");?>:</td>
		<td><strong><?php echo h($uzanto['Uzanto']['rolo']); ?></strong></td>
	</tr>
	<tr>
		<td><?echo __("tempo de kreo");?>:</td>
		<td><strong><?php echo h($uzanto['Uzanto']['tempo_de_kreo']); ?></strong></td>
	</tr>
	<tr>
		<td><?echo __("konto");?>:</td>
		<td><strong><?php echo h($uzanto['Uzanto']['konto_id']); ?></strong></td>
	</tr>
	<tr>
		<td><?echo __("tempozono");?>:</td>
		<td><strong><?php echo h($uzanto['Uzanto']['tempozono']); ?></strong></td>
	</tr>
</tbody></table>