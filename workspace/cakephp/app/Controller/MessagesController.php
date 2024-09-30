<?php
App::uses('AppController', 'Controller');
/**
 * Messages Controller
 */
class MessagesController extends AppController
{
	/**
	 * Scaffold
	 *
	 * @var mixed
	 */
	public $scaffold;
	public $uses = ['User', 'Message'];

	public function generateConversationId($senderId, $receiverId)
	{
		// Create a unique string by concatenating the IDs
		return md5(min($senderId, $receiverId) . max($senderId, $receiverId));
	}

	public function inbox()
	{
		//to render the inbox.ctp
	}

	public function getAllMessage()
	{
		if ($this->request->is('ajax') && $this->request->is('get')) {

			$this->autoRender = false; // Disable view rendering for AJAX requests
			$userId = AuthComponent::user('id');

			$allMessage = $this->Message->find('all', [
				'conditions' => [
					'OR' => [
						'Message.receiverId' => $userId,
						'Message.senderId' => $userId
					]
				],
				'contain' => ['Sender', 'Receiver', 'Conversation'],
				'order' => ['Message.createdAt' => 'DESC'],
			]);

			$groupedMessages = [];
			foreach ($allMessage as $message) {
				$conversationId = $message['Message']['conversationId'];
				if (!isset($groupedMessages[$conversationId])) {
					$groupedMessages[$conversationId] = [
						'conversationId' => $conversationId,
						'messages' => [],
						'lastMessage' => '',
						'lastMessageTime' => null,
						'senderId' => null,
						'senderName' => null,
						'profilePic' => null,
					];
				}
				$groupedMessages[$conversationId]['messages'][] = $message['Message'];

				if (
					empty($groupedMessages[$conversationId]['lastMessageTime']) ||
					strtotime($message['Message']['createdAt']) > ($groupedMessages[$conversationId]['lastMessageTime'])
				) {
					// Update last message details
					$groupedMessages[$conversationId]['lastMessage'] = $message['Message']['body'];
					$groupedMessages[$conversationId]['lastMessageTime'] = $message['Message']['createdAt'];

					// Determine the sender and receiver
					if ($message['Message']['senderId'] === $userId) {
						// If the current user is the sender, use the receiver's info
						$groupedMessages[$conversationId]['senderId'] = $message['Message']['receiverId'];
						$groupedMessages[$conversationId]['senderName'] = $message['Receiver']['name'];
						$groupedMessages[$conversationId]['profilePic'] = $message['Receiver']['profilePic'];
					} else {
						// If the current user is the receiver, use the sender's info
						$groupedMessages[$conversationId]['senderId'] = $message['Message']['senderId'];
						$groupedMessages[$conversationId]['senderName'] = $message['Sender']['name'];
						$groupedMessages[$conversationId]['profilePic'] = $message['Sender']['profilePic'];
					}
				}
			}

			// Prepare unique senders for the view from the grouped messages
			$uniqueSenders = [];
			foreach ($groupedMessages as $conversation) {
				$uniqueSenders[] = [
					'id' => $conversation['senderId'],
					'name' => $conversation['senderName'],
					'profilePic' => $conversation['profilePic'],
					'conversationId' => $conversation['conversationId'],
					'lastMessage' => $conversation['lastMessage'],
					'lastMessageTime' => $conversation['lastMessageTime'],
				];
			}

			// Return JSON response
			$response = [
				'status' => 'success',
				'uniqueSenders' => $uniqueSenders
			];

			return json_encode($response);
		}
	}


	public function send_message()
	{
		if ($this->request->is('ajax')) {
			$this->autoRender = false;


			$data = $this->request->data;


			$senderId = $this->Auth->user('id');
			$receiverId = $data['Message']['receiverId'];
			$sentMessage = $data['Message']['sentMessage'];

			// Generate the conversation ID using the senderId and receiverId
			$conversationId = $this->generateConversationId($senderId, $receiverId);

			$message = [
				'Message' => [
					'senderId' => $senderId,
					'receiverId' => $receiverId,
					'body' => $sentMessage,
					'conversationId' => $conversationId,
					'createdAt' => date('Y-m-d H:i:s'),
				]
			];


			// Attempt to save the message
			if ($this->Message->save($message)) {
				$response = [
					'status' => 'success',
					'message' => 'Message sent successfully'
				];
			} else {
				$response = [
					'status' => 'error',
					'message' => 'Error sending message',
					'errors' => $this->Message->validationErrors // Include validation errors if save fails
				];
			}

			// Return JSON response
			echo json_encode($response);
			return;
		}

		$users = $this->User->find('list', [
			'fields' => ['User.id', 'User.name'],
			'order' => ['User.name' => 'ASC']
		]);

		$this->set(compact('users'));
	}

	public function replyMessage()
	{
		$this->autoRender = false;

		if ($this->request->is('ajax')) {
			$this->Message->create();
			$senderId = AuthComponent::user('id');
			$receiverId = null;
			$conversationId = isset($this->request->data['User']['conversationId']) ? $this->request->data['User']['conversationId'] : null;

			if (!empty($conversationId)) {
				$existingMessage = $this->Message->find('first', [
					'conditions' => ['Message.conversationId' => $conversationId],
					'fields' => ['Message.senderId', 'Message.receiverId']
				]);

				if (!empty($existingMessage)) {
					$receiverId = $existingMessage['Message']['senderId'] === $senderId ? $existingMessage['Message']['receiverId'] : $existingMessage['Message']['senderId'];
				} else {
					$receiverId = $this->request->data['User']['user_id'];
				}
			} else {
				// New conversation
				$receiverId = $this->request->data['User']['user_id'];
				$conversationId = $this->generateConversationId($senderId, $receiverId); // New conversation ID
			}

			// Prepare the message data
			$data = [
				'Message' => [
					'senderId' => $senderId,
					'receiverId' => $receiverId,
					'body' => $this->request->data['body'],
					'conversationId' => $conversationId,
					'createdAt' => date('Y-m-d H:i:s'),
				]
			];

			if ($this->Message->save($data)) {
				// Return only the necessary data as JSON
				echo json_encode([
					'success' => true,
					'senderId' => $senderId,
					'receiverId' => $receiverId,
					'body' => $data['Message']['body'],
					'createdAt' => date('Y-m-d H:i:s'),
					'senderName' => AuthComponent::user('name') // Return the current user's name
				]);
			} else {
				$errors = $this->Message->validationErrors;
				echo json_encode([
					'success' => false,
					'errors' => $errors
				]);
			}
		}
	}


	public function view_conversation($conversationId, $offset = 0)
	{
		// Set the number of messages to fetch
		$limit = 5;

		// Fetch messages based on the offset and limit
		$messages = $this->Message->find('all', [
			'conditions' => ['Message.conversationId' => $conversationId],
			'contain' => ['Users'],
			'order' => ['Message.createdAt' => 'DESC'],
			'limit' => $limit,
			'offset' => $offset
		]);

		// Fetch the total count of messages in this conversation
		$totalMessages = $this->Message->find('count', [
			'conditions' => ['Message.conversationId' => $conversationId]
		]);

		// Fetch the first message to get sender and receiver info
		if (!empty($messages)) {
			$firstMessage = $messages[0];
			$receiverId = $firstMessage['Message']['senderId'] === $this->Auth->user('id')
				? $firstMessage['Message']['receiverId']
				: $firstMessage['Message']['senderId'];

			// Fetch the other user's details
			$otherUser = $this->User->findById($receiverId);
			$otherUserName = !empty($otherUser) ? $otherUser['User']['name'] : 'Unknown User';
			$otherUserProfilePic = !empty($otherUser) ? $otherUser['User']['profilePic'] : 'default_profile_pic.jpg'; // Use a default picture if not set

			// Pass the conversationId, receiverId, messages, totalMessages, otherUserName, and otherUserProfilePic to the view
			$this->set(compact('conversationId', 'receiverId', 'messages', 'totalMessages', 'otherUserName', 'otherUserProfilePic'));
		} else {
			// Handle case where there are no messages
			$this->Flash->error(__('No messages found for this conversation.'));
			return $this->redirect(['action' => 'inbox']); // Redirect to an appropriate action
		}
	}



	public function loadMoreMessages($conversationId, $offset)
	{
		$this->autoRender = false; // Prevent view rendering
		$limit = 5;

		// Fetch all messages for the given conversationId with limit and offset
		$messages = $this->Message->find('all', [
			'conditions' => ['Message.conversationId' => $conversationId],
			'contain' => ['Sender'], // Ensure user data is loaded
			'order' => ['Message.createdAt' => 'DESC'], // Order by creation time
			'limit' => $limit,
			'offset' => $offset
		]);

		// Check if there are more messages to load
		$noMoreMessages = count($messages) < $limit;

		// Prepare response data
		$responseMessages = [];
		if (!empty($messages)) {
			foreach ($messages as $message) {
				$responseMessages[] = [
					'senderId' => $message['Sender']['id'],
					'senderName' => h($message['Sender']['name']),
					'body' => h($message['Message']['body']),
					'createdAt' => h($message['Message']['createdAt']),
				];
			}
		}

		// Return JSON response
		if ($this->request->is('ajax')) {
			$this->response->type('application/json'); // Set content type to JSON
			$response = [
				'success' => true,
				'messages' => $responseMessages,
				'noMoreMessages' => $noMoreMessages,
			];
			$this->response->body(json_encode($response)); // Set the response body
			return $this->response; // Return the response
		} else {
			// If not an AJAX request, redirect or handle accordingly
			return $this->redirect(['action' => 'index']);
		}
	}


	public function searchMessages($conversationId = null)
	{
		$this->autoRender = false; // Prevent view rendering
		$searchQuery = $this->request->query('body'); // Get search term from query string

		// Fetch messages based on the conversation ID and search term
		$messages = $this->Message->find('all', [
			'conditions' => [
				'Message.conversationId' => $conversationId,
				'Message.body LIKE' => '%' . $searchQuery . '%'
			],
			'contain' => ['Users'],
			'order' => ['Message.createdAt' => 'DESC']
		]);

		if (!empty($messages)) {
			// Prepare JSON response with message data
			$data = [];
			foreach ($messages as $message) {
				$data[] = [
					'senderId' => $message['Message']['senderId'],
					'senderName' => $message['Sender']['name'],
					'body' => $message['Message']['body'],
					'createdAt' => $message['Message']['createdAt']
				];
			}

			echo json_encode([
				'success' => true,
				'messages' => $data
			]);
		} else {
			echo json_encode([
				'success' => false,
				'messages' => [],
				'message' => 'No messages found.'
			]);
		}
	}

	public function deleteMessage()
	{
		$this->autoRender = false;

		// Check if the request is AJAX
		if ($this->request->is('ajax')) {
			$id = $this->request->data('id'); // Get the ID from POST data

			// Fetch the message to check if it exists
			$message = $this->Message->findById($id);

			// Check if the message exists and if the user is either the sender or the recipient
			if ($message) {
				$currentUserId = $this->Auth->user('id');
				// Assuming the recipient ID is stored in the message model
				if ($message['Message']['senderId'] === $currentUserId || $message['Message']['receiverId'] === $currentUserId) {
					// Attempt to delete the message
					if ($this->Message->delete($id)) {
						// Set the response type to JSON
						$this->response->type('json');
						// Return success response
						$this->response->body(json_encode(['success' => true, 'message' => 'Message deleted successfully.']));
					} else {
						// Handle deletion failure
						$this->response->type('json');
						$this->response->body(json_encode(['success' => false, 'error' => 'Could not delete the message.']));
					}
				} else {
					// Handle unauthorized deletion
					$this->response->type('json');
					$this->response->body(json_encode(['success' => false, 'error' => 'You are not authorized to delete this message.']));
				}
			} else {
				// Handle non-existent message
				$this->response->type('json');
				$this->response->body(json_encode(['success' => false, 'error' => 'Message not found.']));
			}
		} else {
			// Handle non-AJAX request (optional)
			$this->Flash->error(__('Invalid request.'));
			return $this->redirect(['action' => 'index']);
		}
	}


	public function deleteConversation($conversation = null) {}
}
