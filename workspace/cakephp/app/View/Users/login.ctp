<div class="users form">
	<?php echo $this->Form->create(null, ['id' => 'loginForm', 'url' => ['action' => 'login']]); ?>
	<fieldset>
		<legend><?php echo __('Login User'); ?></legend>
		<?php
		echo $this->Form->input('email');
		echo $this->Form->input('password');
		?>
	</fieldset>
	<?php echo $this->Form->end(__('Submit')); ?>
</div>


<script>
	$(document).ready(function() {
		// Login AJAX form submit
		$('#loginForm').submit(function(event) {
			event.preventDefault();
			var formData = $(this).serialize();
			var actionUrl = $(this).attr('action');
			ajax_request('POST', actionUrl, formData, 'loginForm');
		});

		// AJAX request function
		function ajax_request(type, url, payload, id) {
			$.ajax({
				type: type,
				url: url,
				dataType: 'json',
				data: payload,
				success: function(response) {
					console.log(response);
					if (response.success) {
						alert(response.message);
						window.location.href = "<?php echo $this->Html->url(['controller' => 'messages', 'action' => 'inbox'], true); ?>";
					} else {
						// Show the error message
						var messages = '<div id="validation-alert" class="alert alert-danger"><ul style="list-style-type: none;">';
						messages += '<li>' + response.message + '</li>';
						messages += '</ul></div>';

						$('#validation-messages').html(messages);

						setTimeout(() => {
							$('#validation-alert').fadeOut('slow', function() {
								$(this).remove();
							});
						}, 3000);
					}
				},
				error: function(xhr, status, error) {
					console.error('Error:', error);
					alert('An error occurred. Please try again.');
				}
			});
		}
	});
</script>