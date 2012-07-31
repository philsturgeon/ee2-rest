<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
	<thead>
		<tr>
			<th>ID</th>
			<th><?php echo lang('rest_name'); ?></th>
			<th><?php echo lang('rest_url'); ?></th>
			<th><?php echo lang('rest_verb'); ?></th>
			<th><?php echo lang('rest_format'); ?></th>
			<th><?php echo lang('delete'); ?></th>
		</tr>
	</thead>
	<tbody>

		<?php if(!empty($rest_requests)): ?>
		
			<?php foreach($rest_requests as $request): ?>
			<tr>
				<td><?php echo $request->id; ?></td>
				<td>
					<a href="<?php echo $base.AMP.'method=edit&request_id=' . $request->id; ?>" class="button"><?php echo $request->name; ?></a>
				</td>
				<td><?php echo anchor($request->url, NULL, 'target="_blank"'); ?></td>
				<td><?php echo strtoupper($request->verb); ?></td>
				<td><?php echo $request->format; ?></td>
				<td>
					<a href="<?php echo $base.AMP.'method=delete&request_id=' . $request->id; ?>" class="button"><?php echo lang('delete'); ?></a>
				</td>
			</tr>
			<?php endforeach; ?>

		<?php else: ?>
			<tr>
				<td colspan="5"><?php echo lang('no_requests'); ?></td>
			</tr>
		<?php endif; ?>
			
	</tbody>
</table>