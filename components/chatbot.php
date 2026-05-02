<?php
/**
 * Chatbot Component - Wizdam SDG Assistant
 * 
 * Features:
 * - Context-aware responses based on database content
 * - Integration with application documentation
 * - Dynamic FAQ lookup from database
 * - Multi-language support (EN/ID)
 * 
 * @version 2.0.0
 */

// Get current locale
$current_locale = $_SESSION['locale'] ?? 'en_US';
?>

<!-- Chatbot Button -->
<button id="chatbotBtn" class="chatbot-button" aria-label="Open chatbot" title="<?= __('chatbot.title', 'Need help? Chat with our AI assistant') ?>">
    <i class="fas fa-comments"></i>
    <span class="chatbot-badge"><?= $unread_count ?? 0 ?></span>
</button>

<!-- Chatbot Modal -->
<div id="chatbotModal" class="chatbot-modal" role="dialog" aria-labelledby="chatbotTitle" aria-hidden="true">
    <!-- Chatbot Header -->
    <div class="chatbot-header">
        <div class="chatbot-branding">
            <i class="fas fa-robot"></i>
            <div>
                <h3 id="chatbotTitle" class="chatbot-title"><?= __('chatbot.name', 'Wizdam SDG Assistant') ?></h3>
                <span class="chatbot-status"><span class="status-dot online"></span> <?= __('chatbot.online', 'Online') ?></span>
            </div>
        </div>
        <button id="chatbotClose" class="chatbot-close" aria-label="<?= __('chatbot.close', 'Close chatbot') ?>">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Chatbot Body -->
    <div id="chatbotBody" class="chatbot-body" role="log" aria-live="polite">
        <!-- Welcome Message -->
        <div class="chatbot-welcome">
            <div class="welcome-avatar">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <p><?= __('chatbot.welcome', 'Hello! I\'m your Wizdam SDG Assistant. I can help you with:') ?></p>
            <ul class="welcome-features">
                <li><i class="fas fa-check"></i> <?= __('chatbot.feature1', 'Understanding SDG classification results') ?></li>
                <li><i class="fas fa-check"></i> <?= __('chatbot.feature2', 'ORCID and DOI format validation') ?></li>
                <li><i class="fas fa-check"></i> <?= __('chatbot.feature3', 'Platform features navigation') ?></li>
                <li><i class="fas fa-check"></i> <?= __('chatbot.feature4', 'Research analytics interpretation') ?></li>
            </ul>
        </div>

        <!-- Quick Action Buttons -->
        <div class="chatbot-quick-actions">
            <button class="quick-action-btn" onclick="sendQuickMessage('how_to_analyze')">
                <i class="fas fa-chart-line"></i> <?= __('chatbot.action.analyze', 'How to Analyze') ?>
            </button>
            <button class="quick-action-btn" onclick="sendQuickMessage('orcid_format')">
                <i class="fas fa-id-card"></i> <?= __('chatbot.action.orcid', 'ORCID Format') ?>
            </button>
            <button class="quick-action-btn" onclick="sendQuickMessage('doi_format')">
                <i class="fas fa-link"></i> <?= __('chatbot.action.doi', 'DOI Format') ?>
            </button>
            <button class="quick-action-btn" onclick="sendQuickMessage('sdg_explanation')">
                <i class="fas fa-globe"></i> <?= __('chatbot.action.sdg', 'What are SDGs?') ?>
            </button>
        </div>

        <!-- Typing Indicator -->
        <div id="chatbotTyping" class="chatbot-typing">
            <div class="typing-dots">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
            <span><?= __('chatbot.typing', 'Assistant is typing...') ?></span>
        </div>
    </div>

    <!-- Chatbot Input -->
    <div class="chatbot-input-group">
        <input 
            type="text" 
            id="chatbotInput" 
            placeholder="<?= __('chatbot.placeholder', 'Type your question here...') ?>" 
            maxlength="500"
            aria-label="<?= __('chatbot.input.label', 'Chat message input') ?>"
        class="chatbot-input" />
        <button id="chatbotSend" class="chatbot-send" aria-label="<?= __('chatbot.send', 'Send message') ?>">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<!-- Chatbot Knowledge Base (injected from database/docs) -->
<script id="chatbot-knowledge" type="application/json">
<?= json_encode([
    'locale' => $current_locale,
    'stats' => [
        'total_researchers' => $db->querySingle('SELECT COUNT(*) FROM researchers') ?: 0,
        'total_works' => $db->querySingle('SELECT COUNT(*) FROM works') ?: 0,
        'total_journals' => $db->querySingle('SELECT COUNT(*) FROM journals') ?: 0,
        'sdg_coverage' => $db->querySingle('SELECT COUNT(DISTINCT sdg_code) FROM work_sdgs') ?: 0
    ],
    'faq' => array_map(function($row) {
        return [
            'question' => $row['question'],
            'answer' => $row['answer'],
            'category' => $row['category'] ?? 'general',
            'keywords' => explode(',', strtolower($row['keywords'] ?? ''))
        ];
    }, $db->query('SELECT question, answer, category, keywords FROM faq ORDER BY category') ?: [])
]) ?>
</script>

<!-- Chatbot JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chatbot elements
    const chatbotBtn = document.getElementById('chatbotBtn');
    const chatbotModal = document.getElementById('chatbotModal');
    const chatbotClose = document.getElementById('chatbotClose');
    const chatbotInput = document.getElementById('chatbotInput');
    const chatbotSend = document.getElementById('chatbotSend');
    const chatbotBody = document.getElementById('chatbotBody');
    const chatbotTyping = document.getElementById('chatbotTyping');

    // Chatbot state
    let isOpen = false;
    let messageCount = 0;

    // Enhanced predefined responses with context awareness
    const chatbotResponses = {
        'hello': [
            'Hello! How can I help you with SDG analysis today?',
            'Hi there! I\'m here to assist you with the SDG Classification platform.',
            'Hey! What would you like to know about SDG research analysis?'
        ],
        'help': [
            'I can assist you with:\n• ORCID and DOI format validation\n• Understanding SDG classification results\n• Platform features and navigation\n• Analysis interpretation\n• Technical troubleshooting',
            'Here are the main things I can help with:\n• Input format requirements\n• How to read analysis results\n• Platform functionality\n• Common error solutions'
        ],
        'orcid': [
            'ORCID format should be: 0000-0000-0000-0000\n\nExample: 0000-0002-1825-0097\n\nMake sure all 16 digits are correct and the checksum is valid. You can also paste the full ORCID URL.',
            'An ORCID iD has 16 digits separated by hyphens in groups of four: 0000-0000-0000-0000. The last character can be a digit or \'X\'. Our system validates the checksum automatically.'
        ],
        'doi': [
            'DOI format example: 10.1038/nature12373\n\nYou can enter just the DOI or paste the full URL like https://doi.org/10.1038/nature12373',
            'A DOI typically starts with \'10.\' followed by a publisher code and article identifier. Example: 10.1371/journal.pone.0123456'
        ],
        'sdg': [
            'SDGs are the 17 Sustainable Development Goals adopted by the UN. Our AI system analyzes how research publications contribute to these goals using multiple analysis components.',
            'The 17 SDGs cover areas like poverty, hunger, health, education, gender equality, clean water, clean energy, and more. Our platform identifies which SDGs your research supports.'
        ],
        'analysis': [
            'Our analysis uses 4 main components:\n• Keywords (30%): Direct SDG-related terms\n• Similarity (30%): Semantic similarity to SDG themes\n• Substantive (20%): Depth of SDG engagement\n• Causal (20%): Evidence of causal relationships',
            'The confidence score shows how certain our AI is about the SDG classification. Higher scores (above 70%) indicate strong evidence of SDG relevance.'
        ],
        'confidence': [
            'Confidence scores range from 0-100%:\n• 90-100%: Very high confidence\n• 70-89%: High confidence\n• 50-69%: Moderate confidence\n• Below 50%: Low confidence\n\nHigher scores mean stronger evidence of SDG relevance.',
            'Confidence scores help you understand how reliable the SDG classification is. They\'re based on the strength and consistency of evidence found in the text.'
        ],
        'error': [
            'Common issues and solutions:\n• Invalid ORCID: Check the format and digit sequence\n• Invalid DOI: Ensure it starts with \'10.\'\n• No results: The research might not be SDG-related\n• Timeout: Large datasets may take longer to process',
            'If you\'re experiencing errors:\n1. Verify your input format\n2. Check your internet connection\n3. Try again in a few minutes\n4. Contact support if issues persist'
        ],
        'how': [
            'To analyze research:\n1. Enter a valid ORCID ID (for researcher analysis) or DOI (for single article)\n2. Click \'Analyze\'\n3. Wait for processing (may take 1-5 minutes)\n4. Review the SDG classification results\n5. Explore detailed breakdowns for each publication',
            'You can analyze either:\n• ORCID ID: Analyzes all publications by a researcher\n• DOI: Analyzes a specific article\n\nJust paste the ID in the search box and click Analyze!'
        ],
        'features': [
            'Platform features include:\n• Real-time SDG classification\n• Detailed confidence scoring\n• Visual charts and analytics\n• Bulk analysis capabilities\n• API access for integration\n• Export functionality',
            'Our platform offers comprehensive SDG analysis with intuitive visualizations, detailed breakdowns, and export options for your research data.'
        ],
        'default': [
            'I understand you need help. Could you be more specific? Try asking about:\n• ORCID or DOI formats\n• How to use the platform\n• Understanding results\n• SDG classifications',
            'I\'m here to help! You can ask me about input formats, how to interpret results, platform features, or any technical questions.',
            'Not sure what you\'re looking for? Try one of the quick action buttons above, or ask me about ORCID, DOI, or how to analyze research.'
        ]
    };

    // Conversation context tracking
    let conversationContext = [];
    let userIntent = null;

    // Initialize chatbot
    function initChatbot() {
        // Hide typing indicator initially
        if (chatbotTyping) {
            chatbotTyping.style.display = 'none';
        }

        // Load conversation history from localStorage
        loadConversationHistory();
    }

    // Load knowledge base from JSON script tag
    let knowledgeBase = { faq: [], stats: {}, locale: 'en_US' };
    try {
        const kbScript = document.getElementById('chatbot-knowledge');
        if (kbScript && kbScript.textContent) {
            knowledgeBase = JSON.parse(kbScript.textContent);
        }
    } catch (e) {
        console.warn('Could not load chatbot knowledge base:', e);
    }

    // Get response based on message with intelligent FAQ matching
    function getResponse(message) {
        const msg = message.toLowerCase().trim();
        
        // Update conversation context
        conversationContext.push({
            type: 'user',
            message: message,
            timestamp: Date.now()
        });

        // Keep only last 10 messages for context
        if (conversationContext.length > 10) {
            conversationContext = conversationContext.slice(-10);
        }

        // Try to match with FAQ from database first
        if (knowledgeBase.faq && knowledgeBase.faq.length > 0) {
            const faqMatch = findBestFAQMatch(msg, knowledgeBase.faq);
            if (faqMatch) {
                const response = faqMatch.answer;
                conversationContext.push({
                    type: 'bot',
                    message: response,
                    timestamp: Date.now()
                });
                return response;
            }
        }

        // Fallback to predefined responses
        let responseKey = 'default';
        
        if (msg.includes('hello') || msg.includes('hi') || msg.includes('hey')) {
            responseKey = 'hello';
        } else if (msg.includes('help') || msg.includes('assist')) {
            responseKey = 'help';
        } else if (msg.includes('orcid')) {
            responseKey = 'orcid';
        } else if (msg.includes('doi')) {
            responseKey = 'doi';
        } else if (msg.includes('sdg') || msg.includes('sustainable')) {
            responseKey = 'sdg';
        } else if (msg.includes('analysis') || msg.includes('analyze')) {
            responseKey = 'analysis';
        } else if (msg.includes('how')) {
            responseKey = 'how';
        } else if (msg.includes('confidence') || msg.includes('score')) {
            responseKey = 'confidence';
        } else if (msg.includes('error') || msg.includes('problem') || msg.includes('issue')) {
            responseKey = 'error';
        } else if (msg.includes('feature') || msg.includes('platform')) {
            responseKey = 'features';
        }

        // Get random response from the category
        const responses = chatbotResponses[responseKey];
        const response = responses[Math.floor(Math.random() * responses.length)];

        // Update conversation context
        conversationContext.push({
            type: 'bot',
            message: response,
            timestamp: Date.now()
        });

        return response;
    }

    // Find best FAQ match using keyword matching and simple NLP
    function findBestFAQMatch(userMessage, faqList) {
        const userWords = userMessage.split(/\s+/).filter(w => w.length > 2);
        let bestMatch = null;
        let bestScore = 0;

        for (const faq of faqList) {
            let score = 0;
            const question = faq.question.toLowerCase();
            const keywords = faq.keywords || [];
            
            // Score for exact question match
            if (question.includes(userMessage) || userMessage.includes(question)) {
                score += 10;
            }
            
            // Score for keyword matches
            for (const word of userWords) {
                if (question.includes(word)) score += 2;
                if (keywords.some(k => k.includes(word))) score += 3;
            }
            
            // Score for category-specific terms
            if (faq.category === 'orcid' && userMessage.includes('orcid')) score += 5;
            if (faq.category === 'doi' && userMessage.includes('doi')) score += 5;
            if (faq.category === 'sdg' && (userMessage.includes('sdg') || userMessage.includes('sustainable'))) score += 5;
            
            if (score > bestScore) {
                bestScore = score;
                bestMatch = faq;
            }
        }

        // Return match if score is above threshold
        return bestScore >= 3 ? bestMatch : null;
    }

    // Add message to chat
    function addChatMessage(message, isUser = false, animate = true) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${isUser ? 'user' : 'bot'}`;
        
        // Handle line breaks in bot messages
        if (!isUser && message.includes('\n')) {
            const parts = message.split('\n');
            parts.forEach((part, index) => {
                if (index > 0) {
                    messageDiv.appendChild(document.createElement('br'));
                }
                messageDiv.appendChild(document.createTextNode(part));
            });
        } else {
            messageDiv.textContent = message;
        }

        // Add timestamp
        const timestamp = document.createElement('div');
        timestamp.className = 'message-timestamp';
        timestamp.textContent = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        messageDiv.appendChild(timestamp);

        // Insert before typing indicator
        chatbotBody.insertBefore(messageDiv, chatbotTyping);
        
        // Animate if requested
        if (animate) {
            messageDiv.style.opacity = '0';
            messageDiv.style.transform = 'translateY(20px)';
            setTimeout(() => {
                messageDiv.style.transition = 'all 0.3s ease';
                messageDiv.style.opacity = '1';
                messageDiv.style.transform = 'translateY(0)';
            }, 50);
        }

        // Scroll to bottom
        scrollToBottom();
        
        // Increment message count
        messageCount++;

        // Save to localStorage
        saveConversationHistory();
    }

    // Show typing indicator
    function showTypingIndicator() {
        if (chatbotTyping) {
            chatbotTyping.style.display = 'flex';
            scrollToBottom();
        }
    }

    // Hide typing indicator
    function hideTypingIndicator() {
        if (chatbotTyping) {
            chatbotTyping.style.display = 'none';
        }
    }

    // Scroll chat to bottom
    function scrollToBottom() {
        if (chatbotBody) {
            chatbotBody.scrollTop = chatbotBody.scrollHeight;
        }
    }

    // Send message
    function sendMessage(message = null) {
        const text = message || (chatbotInput ? chatbotInput.value.trim() : '');
        
        if (!text) return;

        // Clear input
        if (chatbotInput && !message) {
            chatbotInput.value = '';
        }

        // Add user message
        addChatMessage(text, true);

        // Show typing indicator
        showTypingIndicator();

        // Simulate bot response delay
        const delay = 800 + Math.random() * 1200; // 0.8-2 seconds
        
        setTimeout(() => {
            hideTypingIndicator();
            const response = getResponse(text);
            addChatMessage(response, false);
        }, delay);
    }

    // Quick message function
    window.sendQuickMessage = function(message) {
        sendMessage(message);
    };

    // Save conversation to localStorage
    function saveConversationHistory() {
        try {
            const messages = Array.from(chatbotBody.querySelectorAll('.chatbot-message')).map(msg => ({
                text: msg.textContent.replace(/\d{1,2}:\d{2}/, '').trim(), // Remove timestamp
                isUser: msg.classList.contains('user')
            }));
            localStorage.setItem('chatbot_history', JSON.stringify(messages.slice(-20))); // Keep last 20 messages
        } catch (e) {
            console.warn('Could not save chatbot history:', e);
        }
    }

    // Load conversation from localStorage
    function loadConversationHistory() {
        try {
            const history = localStorage.getItem('chatbot_history');
            if (history) {
                const messages = JSON.parse(history);
                messages.forEach(msg => {
                    addChatMessage(msg.text, msg.isUser, false);
                });
            }
        } catch (e) {
            console.warn('Could not load chatbot history:', e);
        }
    }

    // Event listeners
    if (chatbotBtn) {
        chatbotBtn.addEventListener('click', function() {
            isOpen = !isOpen;
            
            if (isOpen) {
                chatbotModal.classList.add('show');
                chatbotModal.setAttribute('aria-hidden', 'false');
                
                // Focus on input
                setTimeout(() => {
                    if (chatbotInput) {
                        chatbotInput.focus();
                    }
                }, 300);

                // Add initial greeting if first time
                if (messageCount === 0) {
                    setTimeout(() => {
                        addChatMessage('Hello! I\'m your SDG Analysis Assistant. Feel free to ask me anything about the platform, input formats, or how to interpret your results!', false);
                    }, 500);
                }
            } else {
                chatbotModal.classList.remove('show');
                chatbotModal.setAttribute('aria-hidden', 'true');
            }
        });
    }

    if (chatbotClose) {
        chatbotClose.addEventListener('click', function() {
            chatbotModal.classList.remove('show');
            chatbotModal.setAttribute('aria-hidden', 'true');
            isOpen = false;
        });
    }

    if (chatbotSend) {
        chatbotSend.addEventListener('click', function() {
            sendMessage();
        });
    }

    if (chatbotInput) {
        chatbotInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Character count warning
        chatbotInput.addEventListener('input', function() {
            const remaining = 500 - this.value.length;
            if (remaining < 50) {
                this.style.borderColor = remaining < 10 ? '#dc3545' : '#ffc107';
            } else {
                this.style.borderColor = '#e9ecef';
            }
        });
    }

    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isOpen) {
            chatbotModal.classList.remove('show');
            chatbotModal.setAttribute('aria-hidden', 'true');
            isOpen = false;
        }
    });

    // Close when clicking outside
    chatbotModal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('show');
            this.setAttribute('aria-hidden', 'true');
            isOpen = false;
        }
    });

    // Initialize
    initChatbot();

    console.log('Chatbot component initialized successfully');
});
</script>

<!-- Additional Chatbot Styles -->
<style>
.message-timestamp {
    font-size: 10px;
    opacity: 0.6;
    margin-top: 4px;
    text-align: right;
}

.chatbot-message.user .message-timestamp {
    text-align: left;
}

.chatbot-welcome {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 15px;
    border-radius: 12px;
    background: linear-gradient(135deg, #f0f8ff 0%, #e6f3ff 100%);
    border: 1px solid #e6f3ff;
    margin-bottom: 15px;
    animation: fadeInUp 0.5s ease;
}

.chatbot-welcome i {
    color: #667eea;
    margin-right: 8px;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    .chatbot-message,
    .chatbot-welcome {
        animation: none;
        transition: none;
    }
    
    .typing-dot {
        animation: none;
    }
}

/* High contrast mode */
@media (prefers-contrast: high) {
    .chatbot-modal {
        border: 2px solid #000;
    }
    
    .chatbot-message.bot {
        border: 1px solid #000;
    }
    
    .chatbot-message.user {
        border: 1px solid #fff;
    }
}
</style>