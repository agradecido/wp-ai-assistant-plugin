/**
 * History functionality for WP AI Assistant plugin
 */
document.addEventListener("DOMContentLoaded", function() {
    /**
     * Initialize view thread functionality
     */
    function initViewThreadButtons() {
        const viewButtons = document.querySelectorAll('.wpai-view-thread');
        if (viewButtons.length === 0) return;
        
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const threadContainer = this.closest('.wpai-thread');
                const messagesContainer = threadContainer.querySelector('.wpai-thread-messages');
                
                if (messagesContainer.style.display === 'block') {
                    messagesContainer.style.display = 'none';
                    this.textContent = 'Ver conversación completa';
                } else {
                    messagesContainer.style.display = 'block';
                    this.textContent = 'Ocultar conversación';
                }
            });
        });
    }
    
    /**
     * Initialize continue chat functionality
     */
    function initContinueChatButtons() {
        const continueButtons = document.querySelectorAll('.wpai-continue-chat');
        if (continueButtons.length === 0) return;
        
        continueButtons.forEach(button => {
            button.addEventListener('click', function() {
                const threadId = this.getAttribute('data-thread-id');
                if (!threadId) return;
                
                // Save thread ID to session storage
                try {
                    sessionStorage.setItem('wpai_current_thread', threadId);
                } catch (e) {
                    console.log('Session storage not available');
                }
                
                // Find chatbot container on page
                const chatbotContainer = document.getElementById('chatbot-container');
                
                if (chatbotContainer) {
                    // Set thread ID on container
                    chatbotContainer.setAttribute('data-thread-id', threadId);
                    
                    // Add message to chat output
                    const outputEl = chatbotContainer.querySelector('#chat-output');
                    if (outputEl) {
                        const botMessage = document.createElement('div');
                        botMessage.className = 'chat-message assistant';
                        botMessage.innerHTML = '<p>Continuando conversación anterior... ¿En qué más puedo ayudarte?</p>';
                        outputEl.appendChild(botMessage);
                        outputEl.style.display = 'block';
                        outputEl.scrollTop = outputEl.scrollHeight;
                    }
                    
                    // Update input placeholder
                    const inputEl = chatbotContainer.querySelector('#chat-input');
                    if (inputEl) {
                        inputEl.setAttribute('placeholder', 'Continuar conversación...');
                        inputEl.focus();
                    }
                    
                    // Scroll to chatbot
                    chatbotContainer.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                } else {
                    // If chatbot not on page, check for redirect URL
                    const redirectUrl = this.getAttribute('data-redirect-url');
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    } else {
                        alert('El chatbot no está disponible en esta página. Ve a una página con el chatbot para continuar la conversación.');
                    }
                }
            });
        });
    }
    
    // Initialize functionality
    initViewThreadButtons();
    initContinueChatButtons();
});
