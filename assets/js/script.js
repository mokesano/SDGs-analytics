// Main JavaScript - assets/js/script.js

document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let isSubmitting = false;
    let currentTimeoutId = null;
    let progressInterval = null;
    let currentProgress = 0;

    // ===============================
    // NAVBAR FUNCTIONALITY
    // ===============================
    
    // Navbar scroll effect
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // Mobile menu toggle
    const navbarToggle = document.querySelector('.navbar-toggle');
    const navbarMenu = document.querySelector('.navbar-menu');
    
    if (navbarToggle && navbarMenu) {
        navbarToggle.addEventListener('click', function() {
            navbarToggle.classList.toggle('active');
            navbarMenu.classList.toggle('show');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!navbarToggle.contains(e.target) && !navbarMenu.contains(e.target)) {
                navbarToggle.classList.remove('active');
                navbarMenu.classList.remove('show');
            }
        });

        // Close menu when clicking on a link
        navbarMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                navbarToggle.classList.remove('active');
                navbarMenu.classList.remove('show');
            });
        });
    }

    // ===============================
    // FORM FUNCTIONALITY
    // ===============================
    
    // Input validation and status
    function updateInputStatus(value) {
        const inputField = document.getElementById('input_value');
        const statusElement = document.querySelector('.input-status');
        
        if (!inputField || !statusElement) return;

        const trimmedValue = value.trim();
        
        if (trimmedValue === '') {
            inputField.classList.remove('valid', 'invalid');
            statusElement.textContent = '';
            statusElement.classList.remove('valid', 'invalid');
            return;
        }

        // Check if it's ORCID or DOI
        const isOrcid = validateOrcid(trimmedValue);
        const isDoi = validateDoi(trimmedValue);

        if (isOrcid) {
            inputField.classList.remove('invalid');
            inputField.classList.add('valid');
            statusElement.innerHTML = '<i class="fas fa-check-circle"></i> Valid ORCID format';
            statusElement.classList.remove('invalid');
            statusElement.classList.add('valid');
        } else if (isDoi) {
            inputField.classList.remove('invalid');
            inputField.classList.add('valid');
            statusElement.innerHTML = '<i class="fas fa-check-circle"></i> Valid DOI format';
            statusElement.classList.remove('invalid');
            statusElement.classList.add('valid');
        } else {
            inputField.classList.remove('valid');
            inputField.classList.add('invalid');
            statusElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> Invalid format. Please enter a valid ORCID ID or DOI';
            statusElement.classList.remove('valid');
            statusElement.classList.add('invalid');
        }
    }

    // Validate ORCID format
    function validateOrcid(orcid) {
        const cleanOrcid = orcid.replace(/https?:\/\/(www\.)?orcid\.org\//, '').trim();
        const orcidPattern = /^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/;
        
        if (!orcidPattern.test(cleanOrcid)) {
            return false;
        }

        // Validate checksum
        const digits = cleanOrcid.replace(/-/g, '').slice(0, -1);
        const checkDigit = cleanOrcid.slice(-1);
        
        let total = 0;
        for (let i = 0; i < digits.length; i++) {
            total = (total + parseInt(digits[i])) * 2;
        }
        
        const remainder = total % 11;
        const result = (12 - remainder) % 11;
        const expectedCheckDigit = result === 10 ? 'X' : result.toString();
        
        return checkDigit === expectedCheckDigit;
    }

    // Validate DOI format
    function validateDoi(doi) {
        const cleanDoi = doi.replace(/https?:\/\/(dx\.)?doi\.org\//, '').trim();
        const doiPattern = /^10\.\d{4,}\/[^\s]+$/;
        return doiPattern.test(cleanDoi);
    }

    // Update submit button state
    function updateSubmitButtonState(value) {
        const submitBtn = document.getElementById('submit_btn');
        if (!submitBtn) return;

        const trimmedValue = value.trim();
        const isValid = validateOrcid(trimmedValue) || validateDoi(trimmedValue);
        
        submitBtn.disabled = !isValid || isSubmitting;
        
        if (isValid && !isSubmitting) {
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        } else {
            submitBtn.style.opacity = '0.7';
            submitBtn.style.cursor = 'not-allowed';
        }
    }

    // Initialize input handlers
    function initializeInputHandlers() {
        const inputField = document.getElementById('input_value');
        if (inputField) {
            inputField.addEventListener('input', function() {
                updateInputStatus(this.value);
                updateSubmitButtonState(this.value);
            });

            inputField.addEventListener('paste', function() {
                setTimeout(() => {
                    updateInputStatus(this.value);
                    updateSubmitButtonState(this.value);
                }, 10);
            });

            // Initial validation
            updateInputStatus(inputField.value);
            updateSubmitButtonState(inputField.value);
        }
    }

    // Progress counter for loading
    function startProgressCounter() {
        currentProgress = 0;
        const progressText = document.querySelector('.loading-subtext');
        
        progressInterval = setInterval(() => {
            currentProgress += Math.random() * 15;
            if (currentProgress > 95) currentProgress = 95;
            
            if (progressText) {
                progressText.textContent = `Processing... ${Math.round(currentProgress)}%`;
            }
        }, 1000);
    }

    function stopProgressCounter() {
        if (progressInterval) {
            clearInterval(progressInterval);
            progressInterval = null;
        }
        
        const progressText = document.querySelector('.loading-subtext');
        if (progressText) {
            progressText.textContent = 'Analysis complete!';
        }
    }

    // Form submission handler
    const analysisForm = document.getElementById('analysisForm');
    if (analysisForm) {
        analysisForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (isSubmitting) return;
            
            const inputValue = document.getElementById('input_value').value.trim();
            if (!inputValue || (!validateOrcid(inputValue) && !validateDoi(inputValue))) {
                alert('Please enter a valid ORCID ID or DOI');
                return;
            }

            isSubmitting = true;
            
            // Show loading overlay
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'flex';
                startProgressCounter();
            }

            // Clear any existing timeout
            if (currentTimeoutId) {
                clearTimeout(currentTimeoutId);
            }

            // Set timeout for long requests
            currentTimeoutId = setTimeout(() => {
                const loadingText = document.querySelector('.loading-text');
                const loadingSubtext = document.querySelector('.loading-subtext');
                
                if (loadingText) {
                    loadingText.textContent = 'This is taking longer than usual...';
                }
                if (loadingSubtext) {
                    loadingSubtext.textContent = 'Please wait while we process your request.';
                }

                // Extended timeout
                setTimeout(() => {
                    if (loadingText) {
                        loadingText.textContent = 'Still processing...';
                    }
                    if (loadingSubtext) {
                        loadingSubtext.textContent = 'Large datasets may take up to 5 minutes. Please try again later.';
                    }
                }, 130000);
            }, 30000);

            // Submit the form
            this.submit();
        });
    }

    // ===============================
    // RESULTS FUNCTIONALITY
    // ===============================
    
    // Show/hide analysis details
    window.toggleAnalysis = function(index) {
        const details = document.getElementById('analysis-details-' + index);
        const button = document.querySelector(`[onclick="toggleAnalysis(${index})"]`);
        
        if (details && button) {
            if (details.classList.contains('show')) {
                details.classList.remove('show');
                button.innerHTML = '<i class="fas fa-chart-bar"></i> Show Details';
            } else {
                details.classList.add('show');
                button.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Details';
            }
        }
    };

    // ===============================
    // BACK TO TOP FUNCTIONALITY
    // ===============================
    
    const backToTopBtn = document.getElementById('backToTop');
    if (backToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });

        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // ===============================
    // CHATBOT FUNCTIONALITY
    // ===============================
    
    const chatbotBtn = document.getElementById('chatbotBtn');
    const chatbotModal = document.getElementById('chatbotModal');
    const chatbotClose = document.getElementById('chatbotClose');
    const chatbotInput = document.getElementById('chatbotInput');
    const chatbotSend = document.getElementById('chatbotSend');
    const chatbotBody = document.getElementById('chatbotBody');
    const chatbotTyping = document.getElementById('chatbotTyping');

    // Predefined responses for the chatbot
    const chatbotResponses = {
        'hello': 'Hello! How can I help you with SDG analysis today?',
        'help': 'I can assist you with: SDG classification results, ORCID/DOI formats, analysis interpretation, and platform features.',
        'orcid': 'ORCID format should be: 0000-0000-0000-0000. Make sure all digits are correct and the checksum is valid.',
        'doi': 'DOI format example: 10.1038/nature12373. You can also paste the full DOI URL.',
        'sdg': 'SDGs are the 17 Sustainable Development Goals. Our system analyzes how research contributes to these goals.',
        'analysis': 'The analysis uses 4 components: Keywords (30%), Similarity (30%), Substantive (20%), and Causal (20%).',
        'confidence': 'Confidence scores show how certain the AI is about SDG classification. Higher scores mean stronger evidence.',
        'error': 'For errors, check your input format first. ORCID needs valid checksum, DOI needs proper structure.',
        'default': 'I understand you need help. Could you be more specific? Ask about ORCID, DOI, SDGs, analysis, or errors.'
    };

    function getKeywords(message) {
        const msg = message.toLowerCase();
        if (msg.includes('hello') || msg.includes('hi') || msg.includes('hey')) return 'hello';
        if (msg.includes('help') || msg.includes('assist')) return 'help';
        if (msg.includes('orcid')) return 'orcid';
        if (msg.includes('doi')) return 'doi';
        if (msg.includes('sdg') || msg.includes('sustainable')) return 'sdg';
        if (msg.includes('analysis') || msg.includes('analyze')) return 'analysis';
        if (msg.includes('confidence') || msg.includes('score')) return 'confidence';
        if (msg.includes('error') || msg.includes('problem') || msg.includes('issue')) return 'error';
        return 'default';
    }

    if (chatbotBtn && chatbotModal) {
        chatbotBtn.addEventListener('click', function() {
            chatbotModal.classList.toggle('show');
            if (chatbotModal.classList.contains('show')) {
                chatbotInput.focus();
                
                // Add welcome message if chat is empty
                if (chatbotBody.children.length === 1) { // Only typing indicator
                    addChatMessage('Hello! I\'m here to help you with SDG analysis. Ask me about ORCID, DOI, or analysis results!', false);
                }
            }
        });
    }

    if (chatbotClose) {
        chatbotClose.addEventListener('click', function() {
            chatbotModal.classList.remove('show');
        });
    }

    function addChatMessage(message, isUser = false) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${isUser ? 'user' : 'bot'}`;
        messageDiv.textContent = message;
        
        chatbotBody.insertBefore(messageDiv, chatbotTyping);
        chatbotBody.scrollTop = chatbotBody.scrollHeight;
    }

    function showTypingIndicator() {
        chatbotTyping.style.display = 'block';
        chatbotBody.scrollTop = chatbotBody.scrollHeight;
    }

    function hideTypingIndicator() {
        chatbotTyping.style.display = 'none';
    }

    function sendChatMessage() {
        const message = chatbotInput.value.trim();
        if (!message) return;

        // Add user message
        addChatMessage(message, true);
        chatbotInput.value = '';

        // Show typing indicator
        showTypingIndicator();

        // Simulate bot response delay
        setTimeout(() => {
            hideTypingIndicator();
            
            const keyword = getKeywords(message);
            const response = chatbotResponses[keyword];
            addChatMessage(response, false);
        }, 1000 + Math.random() * 1000);
    }

    if (chatbotSend) {
        chatbotSend.addEventListener('click', sendChatMessage);
    }

    if (chatbotInput) {
        chatbotInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendChatMessage();
            }
        });
    }

    // ===============================
    // CHARTS INITIALIZATION
    // ===============================
    
    // Initialize charts if data is available
    if (typeof sdgData !== 'undefined' && typeof sdgDefinitions !== 'undefined') {
        initializeCharts();
    }

    function initializeCharts() {
        // SDG Distribution Chart
        const sdgLabels = Object.keys(sdgData).map(sdg => 
            sdgDefinitions[sdg] && sdgDefinitions[sdg].title ? sdgDefinitions[sdg].title : sdg
        );
        const sdgValues = Object.values(sdgData).map(item => item.work_count);
        const sdgColors = Object.keys(sdgData).map(sdg => 
            sdgDefinitions[sdg] && sdgDefinitions[sdg].color ? sdgDefinitions[sdg].color : '#667eea'
        );

        const ctx1 = document.getElementById('sdgChart');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: sdgLabels,
                    datasets: [{
                        data: sdgValues,
                        backgroundColor: sdgColors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        }

        // SDG Impact Timeline Chart
        const ctx2 = document.getElementById('timelineChart');
        if (ctx2 && typeof timelineData !== 'undefined') {
            new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: timelineData.labels,
                    datasets: timelineData.datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Publications'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Year'
                            }
                        }
                    }
                }
            });
        }
    }

    // ===============================
    // UTILITY FUNCTIONS
    // ===============================
    
    // Reinitialize input handlers after AJAX content load
    window.reinitializeInputHandlers = function() {
        initializeInputHandlers();
    };

    // Test input handler function
    window.testInputHandler = function() {
        const inputField = document.getElementById('input_value');
        if (inputField) {
            updateInputStatus(inputField.value);
        }
    };

    // Smooth scroll to results
    function scrollToResults() {
        const resultsSection = document.getElementById('resultsSection');
        if (resultsSection) {
            setTimeout(() => {
                resultsSection.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }, 500);
        }
    }

    // ===============================
    // INITIALIZATION
    // ===============================
    
    // Initialize all components
    initializeInputHandlers();
    
    // Handle results section if it exists
    if (document.getElementById('resultsSection')) {
        scrollToResults();
        
        // Stop loading overlay and progress counter
        isSubmitting = false;
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
        stopProgressCounter();
        
        // Reinitialize handlers after a delay
        setTimeout(() => {
            initializeInputHandlers();
        }, 1000);
    }

    // Global event listeners for dynamic content
    document.addEventListener('input', function(e) {
        if (e.target && e.target.id === 'input_value') {
            updateInputStatus(e.target.value);
            updateSubmitButtonState(e.target.value);
        }
    }, true);

    // ===============================
    // ADDITIONAL FEATURES
    // ===============================
    
    // Tooltip functionality
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'custom-tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                document.body.removeChild(this._tooltip);
                this._tooltip = null;
            }
        });
    });

    // Print functionality
    window.printResults = function() {
        const printContent = document.getElementById('resultsSection');
        if (printContent) {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>SDG Analysis Results</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .no-print { display: none; }
                        @media print { .no-print { display: none !important; } }
                    </style>
                </head>
                <body>
                    ${printContent.innerHTML}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
    };

    // Copy to clipboard functionality
    window.copyToClipboard = function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification('Copied to clipboard!', 'success');
            }).catch(() => {
                fallbackCopyToClipboard(text);
            });
        } else {
            fallbackCopyToClipboard(text);
        }
    };

    function fallbackCopyToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showNotification('Copied to clipboard!', 'success');
        } catch (err) {
            showNotification('Failed to copy to clipboard', 'error');
        }
        
        document.body.removeChild(textArea);
    }

    // Show notification
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 10000;
            animation: slideInRight 0.3s ease;
        `;
        
        switch (type) {
            case 'success':
                notification.style.background = '#28a745';
                break;
            case 'error':
                notification.style.background = '#dc3545';
                break;
            case 'warning':
                notification.style.background = '#ffc107';
                notification.style.color = '#212529';
                break;
            default:
                notification.style.background = '#17a2b8';
        }
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    // Add CSS for notification animations
    const notificationStyles = document.createElement('style');
    notificationStyles.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .custom-tooltip {
            position: absolute;
            background: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            z-index: 10000;
            pointer-events: none;
            white-space: nowrap;
        }
        
        .custom-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #333;
        }
    `;
    document.head.appendChild(notificationStyles);

    // Lazy loading for images
    const observerOptions = {
        root: null,
        rootMargin: '50px',
        threshold: 0.1
    };

    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            }
        });
    }, observerOptions);

    // Observe all images with data-src attribute
    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });

    // Performance optimization - debounce scroll events
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Debounced scroll handler for better performance
    const debouncedScrollHandler = debounce(() => {
        // Handle scroll-dependent features here
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Update navbar scroll state
        if (navbar) {
            if (scrollTop > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }
        
        // Update back to top button
        if (backToTopBtn) {
            if (scrollTop > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        }
    }, 10);

    // Replace existing scroll listeners with debounced version
    window.removeEventListener('scroll', debouncedScrollHandler);
    window.addEventListener('scroll', debouncedScrollHandler, { passive: true });

    // Keyboard accessibility improvements
    document.addEventListener('keydown', function(e) {
        // Escape key closes modals
        if (e.key === 'Escape') {
            if (chatbotModal && chatbotModal.classList.contains('show')) {
                chatbotModal.classList.remove('show');
            }
        }
        
        // Enter key on buttons
        if (e.key === 'Enter' && e.target.tagName === 'BUTTON') {
            e.target.click();
        }
    });

    // Add focus management for accessibility
    const focusableElements = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
    
    function trapFocus(element) {
        const focusableContent = element.querySelectorAll(focusableElements);
        const firstFocusable = focusableContent[0];
        const lastFocusable = focusableContent[focusableContent.length - 1];

        element.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable) {
                        lastFocusable.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastFocusable) {
                        firstFocusable.focus();
                        e.preventDefault();
                    }
                }
            }
        });
    }

    // Apply focus trap to chatbot modal
    if (chatbotModal) {
        trapFocus(chatbotModal);
    }

    console.log('SDG Frontend JavaScript initialized successfully');
});

// Global utility functions (available outside DOMContentLoaded)
window.SDGUtils = {
    validateOrcid: function(orcid) {
        const cleanOrcid = orcid.replace(/https?:\/\/(www\.)?orcid\.org\//, '').trim();
        const orcidPattern = /^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/;
        
        if (!orcidPattern.test(cleanOrcid)) {
            return false;
        }

        const digits = cleanOrcid.replace(/-/g, '').slice(0, -1);
        const checkDigit = cleanOrcid.slice(-1);
        
        let total = 0;
        for (let i = 0; i < digits.length; i++) {
            total = (total + parseInt(digits[i])) * 2;
        }
        
        const remainder = total % 11;
        const result = (12 - remainder) % 11;
        const expectedCheckDigit = result === 10 ? 'X' : result.toString();
        
        return checkDigit === expectedCheckDigit;
    },
    
    validateDoi: function(doi) {
        const cleanDoi = doi.replace(/https?:\/\/(dx\.)?doi\.org\//, '').trim();
        const doiPattern = /^10\.\d{4,}\/[^\s]+$/;
        return doiPattern.test(cleanDoi);
    },
    
    formatNumber: function(num) {
        return new Intl.NumberFormat().format(num);
    },
    
    formatDate: function(date) {
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }).format(new Date(date));
    }
};