<?php if(!empty($error)): ?>
	<p><?php echo $error; ?></p>
<?php endif; ?>

<?php echo form_open($form_action, ''); ?>

	<?php echo form_hidden('request_id', $request['id']); ?>

	<p><?php echo sprintf(lang('confirm_delete_message'), $request['name']); ?></p>

<p class="centerSubmit">
	<input name="confirm" type="submit" value="<?php echo lang('delete')?>" class="submit" />
	<input name="cancel" type="submit" value="<?php echo lang('cancel')?>" class="submit" />
</p>

<?php echo form_close(); ?>