<div class="container mt-2 p-3">
	<div class="d-flex justify-content-between">
		<span class="h5">Message Board</span>
		<?php echo $this->Html->link('Send Message', ['controller' => 'messages', 'action' => 'send_message'], ['class' => 'btn btn-info', 'type' => 'button']); ?>
	</div>
	<div id="messagesList" class="container mt-3"> <!-- Removed border for cleaner look -->
		<!-- render the users who sent you a message -->
	</div>
</div>

<style>
	/* General container styling */
	.container {
		max-width: 600px;
		margin: auto;
		padding: 20px;
		background-color: #f9f9f9;
		border-radius: 10px;
	}

	/* Message item styling */
	.message-item {
		display: flex;
		align-items: center;
		margin-bottom: 10px;
		padding: 10px;
		border: 1px solid #ddd;
		border-radius: 8px;
		background-color: #fff;
		transition: background-color 0.3s;
	}

	/* Remove underline from links */
	.message-item-link {
		text-decoration: none;
		color: inherit;
	}

	/* Hover effect for message item */
	.message-item:hover {
		background-color: #f0f0f0;
	}

	/* Profile picture styling */
	.profile-pic {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		margin-right: 10px;
	}

	/* Message info styling */
	.message-info {
		display: flex;
		flex-direction: column;
		flex-grow: 1;
	}

	/* Sender name styling */
	.sender-name {
		font-weight: bold;
		color: #333;
	}

	/* Last message styling */
	.last-message,
	.last-message-time {
		font-size: 0.9em;
		color: #666;
	}

	/* Truncate long last messages */
	.last-message {
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		width: 450px;
		max-width: 450px;
		display: inline-block;
	}

	/* Hover state should not underline text */
	.message-item-link:hover .sender-name,
	.message-item-link:hover .last-message,
	.message-item-link:hover .last-message-time {
		text-decoration: none;
	}
</style>

<script>
	$(document).ready(function() {
		// Send AJAX request to fetch all messages
		$.ajax({
			url: '<?php echo $this->Html->url(['controller' => 'messages', 'action' => 'getAllMessage']); ?>',
			method: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response.status === 'success') {
					let uniqueSenders = response.uniqueSenders;

					// Clear existing list
					$('#messagesList').empty();

					// Loop through the senders and display them
					uniqueSenders.forEach(function(sender) {
						console.log(sender);

						// Ensure profilePic contains either a path or a valid default value
						let profilePic = sender.profilePic.startsWith('http') || sender.profilePic.startsWith('img/') ?
							sender.profilePic :
							'img/' + sender.profilePic;

						// Create a clickable link to redirect to the conversation page
						let senderHtml = `
                        <a href="<?php echo $this->Html->url(['controller' => 'messages', 'action' => 'view_conversation']); ?>/${sender.conversationId}" class="message-item-link">
                            <div class="message-item">
                                <img src="${profilePic}" alt="${sender.name}" class="profile-pic" />
                                <div class="message-info">
                                    <span class="sender-name">${sender.name}</span>
                                    <span class="last-message">${sender.lastMessage}</span>
                                    <span class="last-message-time">${sender.lastMessageTime}</span>
                                </div>
                            </div>
                        </a>
                    `;
						$('#messagesList').append(senderHtml);
					});
				} else {
					console.log('Error fetching messages');
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', error);
			}
		});
	});
</script>