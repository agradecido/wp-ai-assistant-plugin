const { Logger } = require("sass");

document.addEventListener("DOMContentLoaded", function () {
    let threadId = null;
    const chatInput = document.getElementById("chat-input");
    const chatSubmit = document.getElementById("chat-submit");
    const chatOutput = document.getElementById("chat-output");
    const chatSpinner = document.getElementById("chat-spinner");
    const chatbotContainer = document.getElementById("chatbot-container");

    if (!chatInput || !chatSubmit || !chatOutput || !chatSpinner || !chatbotContainer) {
        // console.error("Chatbot elements missing.");
        return;
    }

    const nonce = chatbotContainer.dataset.nonce;
    const assistantId = chatbotContainer.dataset.assistantId;
    const ajaxUrl = wpAIAssistant.ajax_url;

    /**
     * Sends the user's message to the server.
     */
    function sendChat() {
        let userInput = chatInput.value.trim();
        chatInput.value = "";
        chatInput.placeholder = "";

        if (userInput === "") return;

        userInput = sanitizeInput(userInput);
        addUserMessage(userInput);
        showSpinner(true);

        let formData = new URLSearchParams();
        formData.append("action", "wp_ai_assistant_request");
        formData.append("query", userInput);
        formData.append("assistant_id", assistantId);
        formData.append("_ajax_nonce", nonce);

        if (threadId) {
            formData.append("thread_id", threadId);
        }

        fetch(ajaxUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: formData.toString(),
        })
        .then(response => response.json())
        .then(data => handleResponse(data))
        .catch(() => handleError());
    }

    /**
     * Adds a user message to the chat output.
     * @param {string} message The message to display.
     */
    function addUserMessage(message) {
        let userMessage = document.createElement("div");
        userMessage.classList.add("chat-message", "user");
        userMessage.textContent = message;
        chatOutput.appendChild(userMessage);
        chatOutput.style.display = "block";
        scrollToBottom();
    }

    /**
     * Adds an assistant message to the chat output.
     * @param {string} message The message to display.
     */
    function addAssistantMessage(message) {
        let assistantMessage = document.createElement("div");
        assistantMessage.classList.add("chat-message", "assistant");
        assistantMessage.innerHTML = message;
        chatOutput.appendChild(assistantMessage);
        scrollToBottom();
    }

    /**
     * Handles the chatbot's response.
     * @param {Object} data The response data from the server.
     */
    function handleResponse(data) {
        showSpinner(false);

        // First, check if we have a valid response
        if (false === data.success) {
            addAssistantMessage(data.data.message);
            return;
        }
        
        // Then determine which field contains the message
        let message = null;
        if (data.message) {
            message = data.message;
        } else {
            addAssistantMessage("Error desconocido en la respuesta del chatbot.");
            return;
        }
        
        addAssistantMessage(message);

        if (data.thread_id) {
            threadId = data.thread_id;
        }
    }

    /**
     * Handles errors in the fetch request.
     */
    function handleError() {
        showSpinner(false);
        addAssistantMessage("<strong>Error:</strong> No se pudo obtener respuesta.");
    }

    /**
     * Shows or hides the loading spinner.
     * @param {boolean} show Whether to show or hide the spinner.
     */
    function showSpinner(show) {
        chatSpinner.style.display = show ? "block" : "none";
    }

    /**
     * Scrolls the chat output to the latest message.
     */
    function scrollToBottom() {
        chatOutput.scrollTop = chatOutput.scrollHeight;
    }

    /**
     * Sanitizes user input to prevent XSS.
     * @param {string} input The user input.
     * @returns {string} The sanitized input.
     */
    function sanitizeInput(input) {
        return input.replace(/<\/?[^>]+(>|$)/g, "");
    }

    // Event Listeners
    chatSubmit.addEventListener("click", sendChat);
    chatInput.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            sendChat();
        }
    });
});
