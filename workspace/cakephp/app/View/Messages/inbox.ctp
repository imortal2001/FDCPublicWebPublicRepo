<div class="p-3">
	<span class="h5">
		Message Board
	</span>
	<div id="messagesList">
		<!-- render the users who sent you a message -->
	</div>
</div>



<style>
	.message-item {
		display: flex;
		align-items: center;
		margin-bottom: 10px;
	}

	.profile-pic {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		margin-right: 10px;
	}

	.message-info {
		display: flex;
		flex-direction: column;
	}

	.sender-name {
		font-weight: bold;
	}

	.last-message,
	.last-message-time {
		font-size: 0.9em;
		color: #666;
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