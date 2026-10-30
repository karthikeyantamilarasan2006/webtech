<?php
/**
 * Campus Assistant Chatbot HTML Component
 * Project: Student Feedback and Course Evaluation System
 */
?>
<!-- Floating Chatbot Launcher Button -->
<button id="campusChatbotTrigger" type="button" aria-label="Open Campus Assistant Chatbot" title="Chat with Campus Assistant">
    <i class="bi bi-chat-dots-fill"></i>
    <span class="chatbot-pulse-dot"></span>
</button>

<!-- Chatbot Window -->
<div id="campusChatbotWindow" role="dialog" aria-labelledby="chatbotHeaderTitle" aria-modal="true">
    <!-- Header -->
    <div class="chatbot-header">
        <div class="chatbot-header-info">
            <div class="chatbot-avatar">
                <i class="bi bi-robot"></i>
            </div>
            <div>
                <div class="chatbot-title" id="chatbotHeaderTitle">Campus Assistant</div>
                <div class="chatbot-status">
                    <span class="status-dot"></span>
                    <span>Online &bull; Academic AI Guide</span>
                </div>
            </div>
        </div>
        <button type="button" id="campusChatbotClose" class="chatbot-close-btn" aria-label="Close Chat">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Suggested Question Pills -->
    <div id="campusChatbotSuggestions" class="chatbot-suggestions-container">
        <button type="button" class="chatbot-suggestion-pill" data-question="How does anonymous feedback protect my identity?">
            🔒 Anonymous Privacy
        </button>
        <button type="button" class="chatbot-suggestion-pill" data-question="What are the 7 criteria in the 5-star rating system?">
            ⭐ 5-Star Rating Rubric
        </button>
        <button type="button" class="chatbot-suggestion-pill" data-question="How do I submit an evaluation for my course?">
            📝 Evaluation Steps
        </button>
        <button type="button" class="chatbot-suggestion-pill" data-question="How do I register a new student account?">
            🎓 Student Registration
        </button>
        <button type="button" class="chatbot-suggestion-pill" data-question="What are the default test login credentials?">
            🔑 Test Credentials
        </button>
    </div>

    <!-- Scrollable Messages Container -->
    <div id="campusChatbotMessages" class="chatbot-messages">
        <!-- Messages dynamically rendered here by assets/js/chatbot.js -->
    </div>

    <!-- Input Footer -->
    <div class="chatbot-footer">
        <form id="campusChatbotForm" class="chatbot-form" autocomplete="off">
            <input type="text" id="campusChatbotInput" class="chatbot-input" placeholder="Ask a question about the system..." maxlength="400" required>
            <button type="submit" id="campusChatbotSendBtn" class="chatbot-send-btn" aria-label="Send Message" title="Send">
                <i class="bi bi-send-fill" style="transform: rotate(45deg); display: inline-block; margin-left: -2px;"></i>
            </button>
        </form>
        <div class="chatbot-disclaimer">
            <i class="bi bi-shield-lock me-1"></i>Official Academic Assistant &bull; Student Data Never Shared
        </div>
    </div>
</div>

