<?php if (!empty($error)): ?>
	<p><?php echo $error; ?></p>
<?php endif; ?>

<?php echo form_open($form_action, '', array('request_id' => isset($request['id']) ? $request['id'] : '')); ?>

<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
	<tbody>

		<tr class="odd">
			<td style='text-align:right; width: 20%'><label for="name"><?php echo lang('rest_name'); ?>:</label></td>
			<td><?php echo form_input('name', set_value('name', isset($request['name']) ? $request['name'] : ''), 'class="fullfield"'); ?></td>
		</tr>

		<tr class="even">
			<td style='text-align:right;'><label for="url"><?php echo lang('rest_url'); ?>:</label></td>
			<td><?php echo form_input('url', set_value('url', isset($request['url']) ? $request['url'] : ''), 'class="fullfield"'); ?></td>
		</tr>

		<tr class="odd">
			<td style='text-align:right;'><label for="verb"><?php echo lang('rest_verb'); ?>:</label></td>
			<td>
				<?php echo form_dropdown('verb', array(
					'get' => 'GET',
					'post' => 'POST',
					'put' => 'PUT',
					'delete' => 'DELETE'
				), set_value('verb', isset($request['verb']) ? $request['verb'] : '')); ?>
			</td>
		</tr>

		<tr class="even">
			<td style='text-align:right;'><label for="format"><?php echo lang('rest_format'); ?>:</label></td>
			<td>
				<?php echo form_dropdown('format', array(
					'xml' => 'XML',
					'atom' => 'Atom',
					'rss' => 'RSS',
					'json' => 'JSON',
					'serialize' => 'Serialize',
					'php' => 'PHP',
					'csv' => 'CSV',
					'other' => 'Other'
				), set_value('format', isset($request['format']) ? $request['format'] : '')); ?>

				<span id="format-other">
					<?php echo lang('rest_mime_type') . ': ' . form_input('format_other', set_value('format_other', isset($request['format']) ? $request['format'] : ''), 'style="width: 15em;"'); ?>
				</span>
			</td>
		</tr>

		<tr class="odd">
			<td valign="top" style='text-align:right;'><label for="params"><?php echo lang('rest_params'); ?>:</label></td>
			<td>
				<div class="params">

					<?php if (!empty($request['params'])): ?>

						<?php foreach($request['params'] as $name => $value): ?>
							<div class="param" style="padding:0.5em">
								<?php echo lang('rest_param_name') . ': ' . form_input('param_names[]', $name, 'style="width: 15em;"'); ?>
								<?php echo lang('rest_param_value') . ': ' . form_input('param_values[]', $value, 'style="width: 35em;"'); ?>
								<a href="#" class="remove-param"><?php echo lang('rest_remove_param'); ?></a>
							</div>
						<?php endforeach;?>

					<?php else: ?>
						<div class="param" style="padding:0.5em">
							<?php echo lang('rest_param_name') . ': ' . form_input('param_names[]', '', 'style="width: 15em;"'); ?>
							<?php echo lang('rest_param_value') . ': ' . form_input('param_values[]', '', 'style="width: 35em;"'); ?>
							<a href="#" class="remove-param"><?php echo lang('rest_remove_param'); ?></a>
						</div>
					<?php endif; ?>

				</div>

				<p><button class="add-param submit"><?php echo lang('rest_add_param'); ?></button></p>

			</td>
		</tr>

		<tr class="even">
			<td style='text-align:right;'><label for="record_type"><?php echo lang('rest_record_type'); ?>:</label></td>
			<td>
				<?php echo form_dropdown('record_type', array(
					'm' => 'Multiple',
					's' => 'Single',
				), set_value('record_type', isset($request['record_type']) ? $request['record_type'] : '')); ?>
			</td>
		</tr>

	</tbody>
</table>

<p class="centerSubmit">
	<input type="submit" value="<?php echo lang('save')?>" class="submit" />
</p>

<?php echo form_close(); ?>

<script type="text/javascript">
	$(function(){

		// Watch URL bar to detect extension
		$('input[name=url]').bind('keyup blur', function(){

			// Detect possible format from the URL
			format = $(this).val().match(/\.(json|xml|csv)$/);

			// If URL contains .json, .xml or .csv
			if ( format )
			{
				$('select[name=format]').val(format[1]).change();
			}
		});

		// Watch URL bar to detect extension
		$('select[name=format]').change(function(){

			this.value == 'other'
				? $('span#format-other').show()
				: $('span#format-other').hide().find('input').removeAttr('value');

		}).change();

		// Add param
		$('button.add-param').click(function(){

			new_param = $('div.param:first-child').clone().show();
			new_param.find('input').removeAttr('value');

			new_param.appendTo('.params');

			return false;
		});

		// Remove param
		$('a.remove-param').live('click', function(){

			// Only one left, hide it so we can clone later
			if ( $('.param').length == 1 )
			{
				$(this).parent('.param').hide().find('input').removeAttr('value');
			}

			// Plenty left to clone from, delete this one
			else
			{
				$(this).parent('.param').remove();
			}
			return false;
		});
	});
</script>