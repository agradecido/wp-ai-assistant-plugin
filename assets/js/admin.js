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
			action: 'chatbot_gpt_admin_test',
			query: userInput,
			_ajax_nonce: chatbotGPT.nonce // Usar el nonce pasado desde PHP
		};

		if (threadId) {
			data.thread_id = threadId;
		}

		chatInput.value = "";

		$.post(chatbotGPT.ajaxurl, data, function(response) { // Usar la URL AJAX pasada desde PHP
			chatSpinner.style.display = "none";

			if (response.success) {
				addAssistantMessage(response.data.text);
				if (response.data.thread_id) {
					threadId = response.data.thread_id;
				}
			} else {
				addAssistantMessage("<strong>Error:</strong> " + (response.data.message || "No se pudo obtener respuesta"));
			}
		}).fail(function() {
			chatSpinner.style.display = "none";
			addAssistantMessage("<strong>Error:</strong> No se pudo conectar con el servidor");
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
