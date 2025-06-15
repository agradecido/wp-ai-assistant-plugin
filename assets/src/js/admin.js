jQuery(document).ready(function($) {
	let threadId = null;
	const chatInput = document.getElementById("chat-input");
	const chatSubmit = document.getElementById("chat-submit");
	const chatOutput = document.getElementById("chat-output");
	const chatSpinner = document.getElementById("chat-spinner");

	function sendChat() {
		let userInput = chatInput.value.trim();
		if (userInput === "") return;

		addUserMessage(userInput);
		chatSpinner.style.display = "block";

		let data = {
			// Actualizar el nombre de la acción AJAX
			action: 'wp_ai_assistant_admin_test',
			query: userInput,
			// Usar el objeto global correcto
			_ajax_nonce: wpAIAssistant.nonce
		};

		if (threadId) {
			data.thread_id = threadId;
		}

		chatInput.value = "";

		// Usar el objeto global correcto
		$.post(wpAIAssistant.ajaxurl, data, function(response) {
			chatSpinner.style.display = "none";
			
			if (response.success && response.data) {
				// Handle standard success response with data
				if (response.data.message) {
					// Direct response format
					addAssistantMessage(response.data.message);
					if (response.data.thread_id) {
						threadId = response.data.thread_id;
					}
				} else if (response.data.text) {
					// Legacy format
					addAssistantMessage(response.data.text);
					if (response.data.thread_id) {
						threadId = response.data.thread_id;
					}
				} else {
					// console.error('Unknown response format:', response);
					addAssistantMessage(wpAIAssistantAdminStrings.unknownResponseFormat);
				}
			} else {
				// Handle error responses.
				addAssistantMessage(wpAIAssistantAdminStrings.couldNotGetResponse + (response.data?.message ? ": " + response.data.message : ""));
			}
		}).fail(function() {
			chatSpinner.style.display = "none";
			addAssistantMessage(wpAIAssistantAdminStrings.couldNotConnectToServer);
		});
	}

	function addUserMessage(message) {
		let userMessage = document.createElement("div");
		userMessage.classList.add("chat-message", "user");
		userMessage.textContent = message;
		chatOutput.appendChild(userMessage);
		chatOutput.scrollTop = chatOutput.scrollHeight;
	}

	function addAssistantMessage(message) {
		let assistantMessage = document.createElement("div");
		assistantMessage.classList.add("chat-message", "assistant");
		assistantMessage.innerHTML = message;
		chatOutput.appendChild(assistantMessage);
		chatOutput.scrollTop = chatOutput.scrollHeight;
	}

	chatSubmit.addEventListener("click", sendChat);
	chatInput.addEventListener("keydown", function(e) {
		if (e.key === "Enter" && e.ctrlKey) {
			e.preventDefault();
			sendChat();
		}
	});
});
