<!-- Conversation Page -->


<div class="container">
	<h2>Open Conversation</h2>
	<div class="mb-2">
		<?php echo $this->Form->textarea('searchMessage', [
			'class' => 'form-control',
			'rows' => '1',
			'name' => 'body',
			'id' => 'searchMessage', // Added an ID for the search box
			'placeholder' => 'Search in conversation'
		]); ?>
	</div>
	<div>
		<?php
		echo $this->Form->create(null, ['id' => 'replyMessageForm', 'url' => ['controller' => 'messages', 'action' => 'replyMessage']]);
		echo $this->Form->textarea('sentMessage', ['class' => 'form-control', 'rows' => '5', 'name' => 'body', 'id' => 'message', 'placeholder' => 'Input your message reply here...']);
		echo $this->Form->hidden('conversationId', ['value' => $conversationId]);
		echo $this->Form->hidden('user_id', ['value' => $receiverId]);
		echo $this->Form->button('Send Message', ['type' => 'submit', 'class' => 'btn btn-success mt-2 w-100']);
		echo $this->Form->end();
		?>
	</div>
	<div id="messageExchange" class="mb-4">

		<?php if (!empty($messages)): ?>
			<?php foreach ($messages as $message): ?>
				<div class="message-card mb-2">
					<div class="message-sender">
						<?php if ($message['Message']['senderId'] === AuthComponent::user('id')): ?>
							<!-- User's own message (aligned to the right) -->
							<div class="text-end">
								<div class="alert alert-primary float-end deleteMessage" data-message-id="<?php echo h($message['Message']['id']); ?>">
									<strong><?php echo h($message['Sender']['name']); ?></strong>
									<p><?php echo h($message['Message']['body']); ?></p>
									<small class="text-muted"><?php echo h($message['Message']['createdAt']); ?></small>
								</div>
							</div>
						<?php else: ?>
							<!-- Other user's message (aligned to the left) -->
							<div class="text-start">
								<div class="alert alert-secondary">
									<strong><?php echo h($message['Sender']['name']); ?></strong>
									<p><?php echo h($message['Message']['body']); ?></p>
									<small class="text-muted"><?php echo h($message['Message']['createdAt']); ?></small>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		<?php else: ?>
			<div>No messages in this conversation yet.</div>
		<?php endif; ?>
	</div>

	<!-- Load More Messages Button -->
	<?php if ($totalMessages > 5): ?>
		<button id="loadMore" class="btn btn-secondary" data-offset="5">
			Load More Messages
		</button>
	<?php endif; ?>

</div>
<script>
	$(document).ready(function() {
		$(document).on('click', '.deleteMessage', function() {
			var messageId = $(this).data('message-id');

			if (confirm('Are you sure you want to delete this message?')) {
				$.ajax({
					url: '<?php echo $this->Html->url(['controller' => 'messages', 'action' => 'deleteMessage']); ?>',
					method: 'POST', // Change to POST to match your delete method
					data: {
						id: messageId // Send the message ID in the data
					},
					dataType: 'json',
					success: function(response) {
						if (response.success) {
							alert(response.message); // Optional: Display success message
							// Remove the message from the DOM
							$('button[data-message-id="' + messageId + '"]').closest('.message-card').fadeOut(400, function() {
								$(this).remove()
							});
						} else {
							alert(response.error); // Display error message if deletion fails
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX Error:', error);
						alert('An error occurred while deleting the message.');
					}
				});
			}
		});


		$('#searchMessage').on('input', function() {
			var searchText = $(this).val(); // Get the input value
			var conversationId = '<?php echo $conversationId; ?>'; // Pass the conversation ID

			$.ajax({
				url: '<?php echo $this->Html->url(['controller' => 'messages', 'action' => 'searchMessages', $conversationId]); ?>',
				method: 'GET',
				data: {
					body: searchText // Correct key to match the controller
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						$('#messageExchange').html(response.html); // Update message display
						$('#noResults').hide(); // Hide no results message
					}
				},
				error: function(xhr, status, error) {
					console.error('AJAX Error:', error);
					alert('An error occurred while searching messages.');
				}
			});
		});



		$('#loadMore').on('click', function() {
			var offset = $(this).data('offset'); // Get the current offset
			$.ajax({
				url: '<?php echo $this->Html->url(['controller' => 'messages', 'action' => 'loadMoreMessages', $conversationId]); ?>/' + offset,
				method: 'GET',
				dataType: 'json',
				success: function(response) {
					console.log(response); // Debugging log to see the response
					if (response.success) {
						// Append the new messages HTML to the message exchange area
						$('#messageExchange').append(response.html);
						// Update the offset for the next request
						$('#loadMore').data('offset', offset + 5);

						// Hide the button if no more messages
						if (response.noMoreMessages) {
							$('#loadMore').hide();
						}
					} else {
						alert('Unable to load more messages. Please try again.');
					}
				},
				error: function(xhr, status, error) {
					console.error('AJAX Error:', error);
					alert('An error occurred while loading more messages.');
				}
			});
		});

		$('#replyMessageForm').on('submit', function(e) {
			e.preventDefault(); // Prevent default form submission

			$.ajax({
				url: '<?php echo $this->Html->url(['controller' => 'messages', 'action' => 'replyMessage']); ?>',
				method: 'POST',
				data: $(this).serialize(), // Serialize form data
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						// Append the new message HTML to the message exchange area
						$('#messageExchange').prepend(response.html);
						// Scroll to the bottom of the message exchange area
						$('#messageExchange').scrollTop($('#messageExchange')[0].scrollHeight);
						// Optionally clear the input field
						$('#message').val('');
					} else {
						// Handle validation errors if any
						console.log(response.errors);
						alert('Unable to send message. Please try again.');
					}
				},
				error: function(xhr, status, error) {
					console.error('AJAX Error:', error);
					alert('An error occurred while sending the message.');
				}
			});
		});
	});
</script>