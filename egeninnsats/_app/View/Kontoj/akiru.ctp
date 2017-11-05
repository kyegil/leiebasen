<h1><?php echo ($konto['Konto']['nomo']); ?></h1>

<table><tbody>
	<tr>
		<td><?echo __("ensalutilo");?>:</td>
		<td><strong><?php echo h($konto['Konto']['id']); ?></strong></td>
	</tr>
	<tr>
		<td><?echo __("mastro");?>:</td>
		<td><strong><?php echo h($konto['Konto']['mastro']); ?></strong></td>
	</tr>
	<tr>
		<td><?echo __("nomo");?>:</td>
		<td><strong><?php echo h($konto['Konto']['nomo']); ?></strong></td>
	</tr>
	<tr>
		<td><?echo __("nombro de rekordo");?>:</td>
		<td><strong><?php echo h($konto['Konto']['rekordo_count']); ?></strong></td>
	</tr>
</tbody></table>