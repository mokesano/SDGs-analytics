<?php
// pages/contact.php - Contact page
$page_title = "Contact Us";
$page_description = "Get in touch with the Wizdam AI team. We're here to help with your SDG analysis needs, technical support, and partnership inquiries.";

// Handle form submission
$form_submitted = false;
$form_errors = [];
$form_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_form'])) {
    // Validate form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $organization = trim($_POST['organization'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $inquiry_type = $_POST['inquiry_type'] ?? '';
    
    // Validation
    if (empty($name)) {
        $form_errors[] = 'Name is required';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_errors[] = 'Valid email address is required';
    }
    
    if (empty($subject)) {
        $form_errors[] = 'Subject is required';
    }
    
    if (empty($message)) {
        $form_errors[] = 'Message is required';
    }
    
    if (strlen($message) < 10) {
        $form_errors[] = 'Message must be at least 10 characters long';
    }
    
    // If no errors, process the form
    if (empty($form_errors)) {
        // Here you would typically send the email or save to database
        // For now, we'll just mark it as successful
        $form_success = true;
        
        // Clear form data on success
        $name = $email = $organization = $subject = $message = $inquiry_type = '';
    }
    
    $form_submitted = true;
}
?>

<div class="header">
    <h1><i class="fas fa-envelope"></i> Contact Us</h1>
    <p>Get in touch with our team - we're here to help with your SDG analysis needs</p>
</div>

<div class="container">
    <!-- Contact Form Section -->
    <div class="contact-layout">
        <div class="contact-form-section">
            <div class="info-general">
                <h2><i class="fas fa-paper-plane"></i> Send us a Message</h2>
                
                <?php if ($form_submitted): ?>
                    <?php if ($form_success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong>Thank you!</strong> Your message has been sent successfully. We'll get back to you within 24 hours.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Please fix the following errors:</strong>
                            <ul>
                                <?php foreach ($form_errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <form method="POST" action="" class="contact-form" id="contactForm">
                    <input type="hidden" name="contact_form" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">
                                <i class="fas fa-user"></i> Full Name *
                            </label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($name ?? ''); ?>"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Email Address *
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="organization">
                                <i class="fas fa-building"></i> Organization
                            </label>
                            <input 
                                type="text" 
                                id="organization" 
                                name="organization" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($organization ?? ''); ?>"
                                placeholder="University, Company, Research Institute, etc."
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="inquiry_type">
                                <i class="fas fa-tag"></i> Inquiry Type
                            </label>
                            <select id="inquiry_type" name="inquiry_type" class="form-control">
                                <option value="">Select inquiry type</option>
                                <option value="general" <?php echo ($inquiry_type ?? '') === 'general' ? 'selected' : ''; ?>>General Question</option>
                                <option value="technical" <?php echo ($inquiry_type ?? '') === 'technical' ? 'selected' : ''; ?>>Technical Support</option>
                                <option value="partnership" <?php echo ($inquiry_type ?? '') === 'partnership' ? 'selected' : ''; ?>>Partnership</option>
                                <option value="api" <?php echo ($inquiry_type ?? '') === 'api' ? 'selected' : ''; ?>>API Access</option>
                                <option value="enterprise" <?php echo ($inquiry_type ?? '') === 'enterprise' ? 'selected' : ''; ?>>Enterprise Solutions</option>
                                <option value="research" <?php echo ($inquiry_type ?? '') === 'research' ? 'selected' : ''; ?>>Research Collaboration</option>
                                <option value="media" <?php echo ($inquiry_type ?? '') === 'media' ? 'selected' : ''; ?>>Media Inquiry</option>
                                <option value="other" <?php echo ($inquiry_type ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">
                            <i class="fas fa-comment"></i> Subject *
                        </label>
                        <input 
                            type="text" 
                            id="subject" 
                            name="subject" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($subject ?? ''); ?>"
                            placeholder="Brief description of your inquiry"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="message">
                            <i class="fas fa-edit"></i> Message *
                        </label>
                        <textarea 
                            id="message" 
                            name="message" 
                            class="form-control" 
                            rows="6" 
                            placeholder="Please provide details about your inquiry..."
                            required
                        ><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                        <div class="character-count">
                            <span id="charCount">0</span> / 1000 characters
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="newsletter" value="1">
                            <span class="checkmark"></span>
                            Subscribe to our newsletter for updates and insights
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="privacy" value="1" required>
                            <span class="checkmark"></span>
                            I agree to the <a href="?page=privacy-policy" target="_blank">Privacy Policy</a> *
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-submit">
                        <i class="fas fa-paper-plane"></i>
                        Send Message
                    </button>
                </form>
            </div>
        </div>

        <!-- Contact Information Section -->
        <div class="contact-info-section">
            <!-- Contact Details -->
            <div class="info-general">
                <h3><i class="fas fa-map-marker-alt"></i> Get in Touch</h3>
                
                <div class="contact-methods">
                    <div class="contact-method">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Email</h4>
                            <p><a href="mailto:contact@wizdam.ai">contact@wizdam.ai</a></p>
                            <p class="contact-note">General inquiries and support</p>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Phone</h4>
                            <p><a href="tel:+1234567890">+1 (234) 567-890</a></p>
                            <p class="contact-note">Monday - Friday, 9:00 AM - 6:00 PM PST</p>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Address</h4>
                            <p>
                                123 Innovation Drive<br>
                                Tech Valley, CA 94000<br>
                                United States
                            </p>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <div class="contact-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Live Chat</h4>
                            <p>Available 24/7</p>
                            <button onclick="document.getElementById('chatbotBtn').click()" class="btn-chat">
                                <i class="fas fa-comment"></i> Start Chat
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="info-general">
                <h3><i class="fas fa-external-link-alt"></i> Quick Links</h3>
                <div class="quick-links">
                    <a href="?page=help" class="quick-link">
                        <i class="fas fa-question-circle"></i>
                        <span>Help Center</span>
                    </a>
                    <a href="?page=documentation" class="quick-link">
                        <i class="fas fa-book"></i>
                        <span>Documentation</span>
                    </a>
                    <a href="?page=api-reference" class="quick-link">
                        <i class="fas fa-code"></i>
                        <span>API Reference</span>
                    </a>
                    <a href="?page=community-forum" class="quick-link">
                        <i class="fas fa-users"></i>
                        <span>Community Forum</span>
                    </a>
                </div>
            </div>

            <!-- Office Hours -->
            <div class="info-general">
                <h3><i class="fas fa-clock"></i> Office Hours</h3>
                <div class="office-hours">
                    <div class="hours-item">
                        <span class="day">Monday - Friday</span>
                        <span class="time">9:00 AM - 6:00 PM PST</span>
                    </div>
                    <div class="hours-item">
                        <span class="day">Saturday</span>
                        <span class="time">10:00 AM - 4:00 PM PST</span>
                    </div>
                    <div class="hours-item">
                        <span class="day">Sunday</span>
                        <span class="time">Closed</span>
                    </div>
                </div>
                
                <div class="timezone-note">
                    <i class="fas fa-globe"></i>
                    All times are in Pacific Standard Time (PST)
                </div>
            </div>

            <!-- Social Media -->
            <div class="info-general">
                <h3><i class="fas fa-share-alt"></i> Follow Us</h3>
                <p>Stay connected and get the latest updates</p>
                <div class="social-links">
                    <a href="https://twitter.com/wizdamai" target="_blank" class="social-link twitter">
                        <i class="fab fa-twitter"></i>
                        <span>Twitter</span>
                    </a>
                    <a href="https://linkedin.com/company/wizdam-ai" target="_blank" class="social-link linkedin">
                        <i class="fab fa-linkedin-in"></i>
                        <span>LinkedIn</span>
                    </a>
                    <a href="https://github.com/wizdam-ai" target="_blank" class="social-link github">
                        <i class="fab fa-github"></i>
                        <span>GitHub</span>
                    </a>
                    <a href="https://youtube.com/@wizdamai" target="_blank" class="social-link youtube">
                        <i class="fab fa-youtube"></i>
                        <span>YouTube</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="info-general">
        <h2><i class="fas fa-question-circle"></i> Frequently Asked Questions</h2>
        <div class="faq-container">
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>How quickly do you respond to inquiries?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>We typically respond to all inquiries within 24 hours during business days. For urgent technical issues, our support team aims to respond within 4 hours.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>Do you offer enterprise support?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Yes! We offer dedicated enterprise support with priority response times, custom integration assistance, and dedicated account management. Contact us to learn more about our enterprise plans.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>Can I schedule a demo or consultation?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Absolutely! We'd be happy to provide a personalized demo of our platform. Please mention "Demo Request" in your inquiry type and preferred time slots in your message.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>Do you provide API documentation and support?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Yes, we have comprehensive API documentation and provide full technical support for API integration. Visit our API Reference page or contact our technical team for assistance.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Page Styles -->
<style>
.contact-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
    margin-bottom: 40px;
}

.contact-form {
    max-width: none;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.form-group label i {
    color: #667eea;
    margin-right: 5px;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    background: white;
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

.character-count {
    text-align: right;
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
    font-size: 14px;
    line-height: 1.5;
}

.checkbox-label input[type="checkbox"] {
    display: none;
}

.checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid #e9ecef;
    border-radius: 4px;
    background: #f8f9fa;
    position: relative;
    transition: all 0.3s ease;
    flex-shrink: 0;
    margin-top: 2px;
}

.checkbox-label input[type="checkbox"]:checked + .checkmark {
    background: #667eea;
    border-color: #667eea;
}

.checkbox-label input[type="checkbox"]:checked + .checkmark::after {
    content: '\f00c';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 12px;
}

.btn-submit {
    width: 100%;
    padding: 15px;
    font-size: 16px;
    font-weight: 600;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}

.contact-methods {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.contact-method {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.contact-method:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.contact-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.contact-details h4 {
    margin-bottom: 8px;
    color: #333;
    font-size: 1.1rem;
}

.contact-details p {
    margin-bottom: 5px;
    color: #666;
}

.contact-details a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.contact-details a:hover {
    text-decoration: underline;
}

.contact-note {
    font-size: 12px;
    color: #999 !important;
}

.btn-chat {
    background: #667eea;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-chat:hover {
    background: #5a6fd8;
    transform: translateY(-1px);
}

.quick-links {
    display: grid;
    gap: 10px;
}

.quick-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
}

.quick-link:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
    transform: translateX(5px);
}

.quick-link i {
    width: 20px;
    color: #667eea;
}

.quick-link:hover i {
    color: white;
}

.office-hours {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.hours-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    background: white;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.day {
    font-weight: 600;
    color: #333;
}

.time {
    color: #666;
    font-size: 14px;
}

.timezone-note {
    margin-top: 15px;
    padding: 10px;
    background: #f0f8ff;
    border-radius: 8px;
    font-size: 12px;
    color: #667eea;
    text-align: center;
}

.social-links {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.social-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    color: white;
}

.social-link.twitter {
    background: #1da1f2;
}

.social-link.linkedin {
    background: #0077b5;
}

.social-link.github {
    background: #333;
}

.social-link.youtube {
    background: #ff0000;
}

.social-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert ul {
    margin: 5px 0 0 0;
    padding-left: 20px;
}

.faq-container {
    margin-top: 20px;
}

.faq-item {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 10px;
    overflow: hidden;
}

.faq-question {
    padding: 20px;
    background: #f8f9fa;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    transition: all 0.3s ease;
}

.faq-question:hover {
    background: #e9ecef;
}

.faq-question.active {
    background: #667eea;
    color: white;
}

.faq-question i {
    transition: transform 0.3s ease;
}

.faq-question.active i {
    transform: rotate(180deg);
}

.faq-answer {
    padding: 0 20px;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s ease;
}

.faq-answer.show {
    padding: 20px;
    max-height: 200px;
}

@media (max-width: 768px) {
    .contact-layout {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .social-links {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Contact Page JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character count for message textarea
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    
    if (messageTextarea && charCount) {
        function updateCharCount() {
            const count = messageTextarea.value.length;
            charCount.textContent = count;
            
            if (count > 800) {
                charCount.style.color = '#dc3545';
            } else if (count > 600) {
                charCount.style.color = '#ffc107';
            } else {
                charCount.style.color = '#666';
            }
        }
        
        messageTextarea.addEventListener('input', updateCharCount);
        updateCharCount(); // Initial count
    }
    
    // Form validation
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            const privacyCheckbox = this.querySelector('input[name="privacy"]');
            if (!privacyCheckbox.checked) {
                e.preventDefault();
                alert('Please accept the Privacy Policy to continue.');
                return false;
            }
        });
    }
    
    // FAQ toggle functionality
    window.toggleFAQ = function(element) {
        const faqItem = element.parentElement;
        const answer = faqItem.querySelector('.faq-answer');
        const isActive = element.classList.contains('active');
        
        // Close all other FAQs
        document.querySelectorAll('.faq-question.active').forEach(q => {
            q.classList.remove('active');
            q.parentElement.querySelector('.faq-answer').classList.remove('show');
        });
        
        // Toggle current FAQ
        if (!isActive) {
            element.classList.add('active');
            answer.classList.add('show');
        }
    };
    
    // Smooth scroll for quick links
    document.querySelectorAll('.quick-link').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href').startsWith('#')) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });
    
    console.log('Contact page initialized successfully');
});
</script>