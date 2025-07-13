// Import styles
import '../scss/chatbot.scss';
import '../scss/history.scss';

// Import JS modules
import './chatbot.js';
import './history.js'; // Import the history module

document.addEventListener('DOMContentLoaded', () => {
    if (window.matchMedia('(max-width: 768px)').matches) {
        const chat = document.getElementById('chatbot-container');
        const history = document.getElementById('wpai-history');
        if (chat && history) {
            chat.parentNode.insertBefore(history, chat.nextSibling);
        }
    }
});
