<h1><?echo __("Specoj de kontribuo");?></h1>
<p><?php echo $this->Html->link(__("aldonu novan specon de kontribuo"), array(
	'action' => 'add'
)); ?></p>
<table>
	<tr>
		<th><?echo __("ensalutilo");?></th>
		<th><?echo __("speco");?></th>
		<th><?echo __("unuo");?></th>
		<th><?echo __("forviŝi");?></th>
	</tr>

	<?php foreach ($kinds as $kind): ?>
	<tr>
		<td>
			<?php
				echo $this->Html->link(
					$kind['Kind']['id'],
					array(
						'action' => 'view', $kind['Kind']['id']
					)
				);
			?>
		</td>
		<td>
			<?php echo $kind['Kind']['name']; ?>
		</td>
		<td>
			<?php echo $kind['Kind']['unit']; ?>
		</td>
        <td>
			<?php echo $this->Form->postLink(
				'forviŝi',
				array('action' => 'delete', $kind['Kind']['id']),
				array('confirm' => 'ĉu vi certas, ke vi volas forviŝi tiun specon?')
//				array('confirm' => 'ĉu vi certas?')
			);
			?>
        </td>
	</tr>
	<?php endforeach; ?>

</table>
