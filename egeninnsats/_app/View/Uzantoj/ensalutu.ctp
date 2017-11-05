<div class="users form">
<?php echo $this->Session->flash('auth'); ?>
<?php echo $this->Form->create('Uzanto'); ?>
    <fieldset>
        <legend>
            <?php echo __('Bonvolu tajpi vian salutnomon kaj pasvorton'); ?>
        </legend>
        <?php echo $this->Form->input('uzanto', array(
        	'label' => __('uzanto')
        ));
        echo $this->Form->input('pasvorto', array(
        	'label' => __('pasvorto'),
        	'type' => "password"
        ));
    ?>
    </fieldset>
<?php echo $this->Form->end(__('Ensalutu')); ?>
</div>
