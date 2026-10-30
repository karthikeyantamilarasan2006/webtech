/**
 * Campus Assistant AI Chatbot Frontend Controller
 * Project: Student Feedback and Course Evaluation System
 */

document.addEventListener('DOMContentLoaded', function () {
    const triggerBtn     = document.getElementById('campusChatbotTrigger');
    const chatWindow     = document.getElementById('campusChatbotWindow');
    const closeBtn       = document.getElementById('campusChatbotClose');
    const chatForm       = document.getElementById('campusChatbotForm');
    const chatInput      = document.getElementById('campusChatbotInput');
    const sendBtn        = document.getElementById('campusChatbotSendBtn');
    const messagesBox    = document.getElementById('campusChatbotMessages');
    const suggestionsBox = document.getElementById('campusChatbotSuggestions');

    if (!triggerBtn || !chatWindow) {
        return; // Chatbot elements not present on this page
    }

    // Determine correct relative endpoint for api/chat.php based on current path depth
    const pathName = window.location.pathname.replace(/\\/g, '/');
    let apiEndpoint = 'api/chat.php';
    if (pathName.includes('/student/') || pathName.includes('/auth/') || pathName.includes('/admin/') || pathName.includes('/config/')) {
        apiEndpoint = '../api/chat.php';
    }

    // In-memory conversation state for OpenAI history context
    let conversationHistory = [];

    // Helper: Format Current Time (e.g., 02:45 PM)
    function getCurrentTimeString() {
        const now = new Date();
        let hours = now.getHours();
        let minutes = now.getMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        minutes = minutes < 10 ? '0' + minutes : minutes;
        return `${hours}:${minutes} ${ampm}`;
    }

    // Helper: Simple Markdown-to-HTML parser (handles bold, code, lists, and linebreaks)
    function formatMessageText(text) {
        let safe = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        // Bold: **text**
        safe = safe.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

        // Inline Code: `code`
        safe = safe.replace(/`([^`]+)`/g, '<code class="bg-light px-1 rounded text-dark">$1</code>');

        // Bullet points: • or - at line start
        const lines = safe.split('\n');
        let inList = false;
        let formatted = '';

        lines.forEach(line => {
            const trimmed = line.trim();
            if (trimmed.startsWith('• ') || trimmed.startsWith('- ')) {
                if (!inList) {
                    formatted += '<ul class="mb-1 ps-3">';
                    inList = true;
                }
                formatted += `<li>${trimmed.substring(2)}</li>`;
            } else if (/^\d+\.\s/.test(trimmed)) {
                if (!inList) {
                    formatted += '<ol class="mb-1 ps-3">';
                    inList = true;
                }
                const content = trimmed.replace(/^\d+\.\s*/, '');
                formatted += `<li>${content}</li>`;
            } else {
                if (inList) {
                    formatted += inList === 'ul' ? '</ul>' : '</ol>';
                    inList = false;
                }
                if (trimmed !== '') {
                    formatted += `<p class="mb-1">${line}</p>`;
                }
            }
        });

        if (inList) {
            formatted += '</ul>';
        }

        return formatted;
    }

    // Scroll chat messages to bottom
    function scrollToBottom() {
        if (messagesBox) {
            messagesBox.scrollTop = messagesBox.scrollHeight;
        }
    }

    // Render User Message Bubble
    function appendUserMessage(text) {
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble chat-bubble-user';
        bubble.innerHTML = `
            <div>${text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</div>
            <span class="chat-time text-light opacity-75">${getCurrentTimeString()}</span>
        `;
        messagesBox.appendChild(bubble);
        scrollToBottom();

        conversationHistory.push({ role: 'user', content: text });
    }

    // Render Assistant Message Bubble
    function appendAssistantMessage(text) {
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble chat-bubble-assistant';
        bubble.innerHTML = `
            <div>${formatMessageText(text)}</div>
            <span class="chat-time text-muted">${getCurrentTimeString()}</span>
        `;
        messagesBox.appendChild(bubble);
        scrollToBottom();

        conversationHistory.push({ role: 'assistant', content: text });
    }

    // Render Animated Typing Indicator
    function showTypingIndicator() {
        const typingEl = document.createElement('div');
        typingEl.id = 'campusChatbotTyping';
        typingEl.className = 'typing-indicator';
        typingEl.innerHTML = `
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
        `;
        messagesBox.appendChild(typingEl);
        scrollToBottom();
    }

    // Remove Typing Indicator
    function removeTypingIndicator() {
        const typingEl = document.getElementById('campusChatbotTyping');
        if (typingEl) {
            typingEl.remove();
        }
    }

    // Send Message to PHP Backend
    function handleSendMessage(messageText) {
        const text = messageText || chatInput.value.trim();
        if (!text) return;

        appendUserMessage(text);
        chatInput.value = '';
        sendBtn.disabled = true;

        showTypingIndicator();

        fetch(apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: text,
                history: conversationHistory
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server returned HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            removeTypingIndicator();
            sendBtn.disabled = false;

            if (data.success && data.reply) {
                appendAssistantMessage(data.reply);
            } else {
                appendAssistantMessage(data.error || "I am currently unable to process your request. Please try again in a moment.");
            }
        })
        .catch(err => {
            console.error('Chatbot error:', err);
            removeTypingIndicator();
            sendBtn.disabled = false;
            appendAssistantMessage("I encountered a connection error. Please make sure Apache and MySQL are running in XAMPP, and try again.");
        });
    }

    // Event: Toggle Open / Close
    triggerBtn.addEventListener('click', function () {
        const isActive = chatWindow.classList.contains('active');
        if (isActive) {
            chatWindow.classList.remove('active');
        } else {
            chatWindow.classList.add('active');
            chatInput.focus();
            scrollToBottom();
        }
    });

    closeBtn.addEventListener('click', function () {
        chatWindow.classList.remove('active');
    });

    // Event: Form Submit
    chatForm.addEventListener('submit', function (e) {
        e.preventDefault();
        handleSendMessage();
    });

    // Event: Suggested Question Pills Click
    if (suggestionsBox) {
        suggestionsBox.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('chatbot-suggestion-pill')) {
                const question = e.target.getAttribute('data-question') || e.target.textContent;
                handleSendMessage(question);
            }
        });
    }

    // Welcome Greeting Initializer
    if (messagesBox && messagesBox.children.length === 0) {
        appendAssistantMessage(
            "👋 Welcome to the **Student Feedback & Course Evaluation System**!\n\n" +
            "I am your **Campus Assistant**. I can help you evaluate your instructors, explain our 5-star criteria, or clarify our anonymous privacy protection.\n\n" +
            "How can I help you today?"
        );
    }
});

