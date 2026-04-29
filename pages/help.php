<?php
// pages/help.php - Help Center page
$page_title = "Help Center";
$page_description = "Find answers to common questions, troubleshooting guides, and step-by-step tutorials for the Wizdam AI SDG Classification platform.";
?>

<div class="header">
    <h1><i class="fas fa-question-circle"></i> Help Center</h1>
    <p>Find answers, get support, and learn how to make the most of our platform</p>
</div>

<div class="container">
    <!-- Search Section -->
    <div class="help-search-section">
        <div class="search-box">
            <input type="text" id="helpSearch" placeholder="Search for help articles, tutorials, or FAQs..." />
            <button type="button" id="searchBtn">
                <i class="fas fa-search"></i>
            </button>
        </div>
        <div class="popular-searches">
            <span>Popular searches:</span>
            <button class="search-tag" onclick="searchHelp('ORCID format')">ORCID format</button>
            <button class="search-tag" onclick="searchHelp('confidence scores')">Confidence scores</button>
            <button class="search-tag" onclick="searchHelp('API integration')">API integration</button>
            <button class="search-tag" onclick="searchHelp('export results')">Export results</button>
        </div>
    </div>

    <!-- Quick Help Categories -->
    <div class="help-categories">
        <div class="category-card" onclick="showCategory('getting-started')">
            <div class="category-icon">
                <i class="fas fa-play"></i>
            </div>
            <h3>Getting Started</h3>
            <p>Learn the basics of using our SDG classification platform</p>
            <span class="article-count">8 articles</span>
        </div>

        <div class="category-card" onclick="showCategory('analysis-guide')">
            <div class="category-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3>Analysis Guide</h3>
            <p>Understand how our AI analyzes and classifies research</p>
            <span class="article-count">12 articles</span>
        </div>

        <div class="category-card" onclick="showCategory('api-help')">
            <div class="category-icon">
                <i class="fas fa-code"></i>
            </div>
            <h3>API & Integration</h3>
            <p>Technical documentation and integration guides</p>
            <span class="article-count">15 articles</span>
        </div>

        <div class="category-card" onclick="showCategory('troubleshooting')">
            <div class="category-icon">
                <i class="fas fa-wrench"></i>
            </div>
            <h3>Troubleshooting</h3>
            <p>Solutions to common issues and error messages</p>
            <span class="article-count">10 articles</span>
        </div>

        <div class="category-card" onclick="showCategory('account-billing')">
            <div class="category-icon">
                <i class="fas fa-user-cog"></i>
            </div>
            <h3>Account & Billing</h3>
            <p>Manage your account, subscription, and billing</p>
            <span class="article-count">6 articles</span>
        </div>

        <div class="category-card" onclick="showCategory('advanced-features')">
            <div class="category-icon">
                <i class="fas fa-cogs"></i>
            </div>
            <h3>Advanced Features</h3>
            <p>Bulk analysis, custom integrations, and enterprise features</p>
            <span class="article-count">9 articles</span>
        </div>
    </div>

    <!-- Frequently Asked Questions -->
    <div class="info-general">
        <h2><i class="fas fa-question-circle"></i> Frequently Asked Questions</h2>
        
        <div class="faq-categories">
            <button class="faq-tab active" onclick="showFAQCategory('general')">General</button>
            <button class="faq-tab" onclick="showFAQCategory('technical')">Technical</button>
            <button class="faq-tab" onclick="showFAQCategory('billing')">Billing</button>
            <button class="faq-tab" onclick="showFAQCategory('api')">API</button>
        </div>

        <div id="general" class="faq-category active">
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>What is SDG classification and how does it work?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>SDG classification is the process of analyzing research publications to determine their relevance to the 17 United Nations Sustainable Development Goals. Our AI system uses four analysis components: keyword matching, semantic similarity, substantive analysis, and causal inference to provide comprehensive classification with confidence scores.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>What input formats do you support?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>We support two main input types:</p>
                    <ul>
                        <li><strong>ORCID IDs:</strong> Format 0000-0000-0000-0000 for analyzing all publications by a researcher</li>
                        <li><strong>DOIs:</strong> Format 10.xxxx/xxxxx for analyzing individual articles</li>
                    </ul>
                    <p>You can paste these directly or include the full URLs (e.g., https://orcid.org/0000-0000-0000-0000).</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>How accurate is the SDG classification?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Our AI system achieves over 95% accuracy in SDG classification when tested against manually curated datasets. We provide confidence scores to indicate the reliability of each classification, with scores above 70% considered highly reliable.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>Is there a limit to how many publications I can analyze?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>For individual ORCID analysis, we can process researchers with up to 1000 publications. For larger datasets or institutional analysis, we offer bulk processing capabilities through our enterprise plans.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>Can I export or download my analysis results?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Yes! You can export results in multiple formats including CSV (for data analysis), PDF (for reports), and JSON (for technical integration). Export options are available after completing your analysis.</p>
                </div>
            </div>
        </div>

        <div id="technical" class="faq-category">
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>Why is my ORCID ID not being recognized?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Common issues include:</p>
                    <ul>
                        <li>Incorrect format - ensure it follows 0000-0000-0000-0000 pattern</li>
                        <li>Invalid checksum digit - the last character must be mathematically correct</li>
                        <li>Private profile - your ORCID profile must be set to public</li>
                        <li>New ORCID - recently created IDs may take 24-48 hours to appear in our system</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>What should I do if analysis takes too long?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Analysis typically takes 1-5 minutes depending on the number of publications. If it takes longer:</p>
                    <ul>
                        <li>Wait patiently - large publication sets (100+ works) can take up to 10 minutes</li>
                        <li>Check your internet connection</li>
                        <li>Try again during off-peak hours</li>
                        <li>Contact support if the issue persists beyond 15 minutes</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>Why do I get "No results found" for some researchers?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>This can happen when:</p>
                    <ul>
                        <li>The researcher's work is not related to any SDGs</li>
                        <li>Publications lack sufficient text content for analysis</li>
                        <li>Works are behind paywalls and abstracts are insufficient</li>
                        <li>The ORCID profile has very few or no publications</li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="billing" class="faq-category">
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>Is the basic service free to use?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Yes! Our basic SDG classification service is free for individual researchers and includes:</p>
                    <ul>
                        <li>Up to 50 analyses per month</li>
                        <li>ORCID and DOI analysis</li>
                        <li>Basic visualizations</li>
                        <li>Standard export options</li>
                    </ul>
                    <p>For higher usage limits and advanced features, we offer paid plans starting at $29/month.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>What's included in the premium plans?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Premium plans include:</p>
                    <ul>
                        <li>Unlimited monthly analyses</li>
                        <li>Priority processing</li>
                        <li>Advanced visualizations and reports</li>
                        <li>API access</li>
                        <li>Bulk analysis tools</li>
                        <li>Custom export formats</li>
                        <li>Priority email support</li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="api" class="faq-category">
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>How do I get API access?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>API access is available with our Professional plan ($99/month) and higher. To get started:</p>
                    <ol>
                        <li>Sign up for a Professional or Enterprise plan</li>
                        <li>Visit the API Access page in your dashboard</li>
                        <li>Generate your API keys</li>
                        <li>Review our API documentation</li>
                        <li>Start integrating with our RESTful endpoints</li>
                    </ol>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>What are the API rate limits?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Rate limits depend on your plan:</p>
                    <ul>
                        <li><strong>Professional:</strong> 100 requests/minute, 10,000/month</li>
                        <li><strong>Enterprise:</strong> 500 requests/minute, 100,000/month</li>
                        <li><strong>Custom:</strong> Tailored limits based on your needs</li>
                    </ul>
                    <p>Contact our sales team for higher limits or custom arrangements.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Support Section -->
    <div class="support-section">
        <div class="support-card">
            <div class="support-icon">
                <i class="fas fa-comments"></i>
            </div>
            <h3>Live Chat</h3>
            <p>Get instant help from our support team</p>
            <button class="support-btn" onclick="document.getElementById('chatbotBtn').click()">
                Start Chat
            </button>
        </div>

        <div class="support-card">
            <div class="support-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <h3>Email Support</h3>
            <p>Send us detailed questions or feedback</p>
            <a href="?page=contact" class="support-btn">
                Contact Us
            </a>
        </div>

        <div class="support-card">
            <div class="support-icon">
                <i class="fas fa-users"></i>
            </div>
            <h3>Community Forum</h3>
            <p>Connect with other users and experts</p>
            <a href="?page=community-forum" class="support-btn">
                Join Forum
            </a>
        </div>

        <div class="support-card">
            <div class="support-icon">
                <i class="fas fa-book"></i>
            </div>
            <h3>Documentation</h3>
            <p>Comprehensive guides and tutorials</p>
            <a href="?page=documentation" class="support-btn">
                Read Docs
            </a>
        </div>
    </div>

    <!-- Quick Tutorials -->
    <div class="info-general">
        <h2><i class="fas fa-play-circle"></i> Quick Start Tutorials</h2>
        <div class="tutorial-grid">
            <div class="tutorial-card">
                <div class="tutorial-thumbnail">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="tutorial-content">
                    <h4>Analyzing Your Research Profile</h4>
                    <p>Learn how to analyze all your publications using your ORCID ID</p>
                    <div class="tutorial-meta">
                        <span><i class="fas fa-clock"></i> 3 min</span>
                        <span><i class="fas fa-user"></i> Beginner</span>
                    </div>
                    <a href="#tutorial-orcid" class="tutorial-link">Start Tutorial</a>
                </div>
            </div>

            <div class="tutorial-card">
                <div class="tutorial-thumbnail">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="tutorial-content">
                    <h4>Single Article Analysis</h4>
                    <p>Analyze individual research papers using DOI identifiers</p>
                    <div class="tutorial-meta">
                        <span><i class="fas fa-clock"></i> 2 min</span>
                        <span><i class="fas fa-user"></i> Beginner</span>
                    </div>
                    <a href="#tutorial-doi" class="tutorial-link">Start Tutorial</a>
                </div>
            </div>

            <div class="tutorial-card">
                <div class="tutorial-thumbnail">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="tutorial-content">
                    <h4>Understanding Results</h4>
                    <p>Interpret confidence scores, SDG classifications, and analysis components</p>
                    <div class="tutorial-meta">
                        <span><i class="fas fa-clock"></i> 5 min</span>
                        <span><i class="fas fa-user"></i> Intermediate</span>
                    </div>
                    <a href="#tutorial-results" class="tutorial-link">Start Tutorial</a>
                </div>
            </div>

            <div class="tutorial-card">
                <div class="tutorial-thumbnail">
                    <i class="fas fa-download"></i>
                </div>
                <div class="tutorial-content">
                    <h4>Exporting Your Data</h4>
                    <p>Export analysis results in various formats for reports and further analysis</p>
                    <div class="tutorial-meta">
                        <span><i class="fas fa-clock"></i> 3 min</span>
                        <span><i class="fas fa-user"></i> Beginner</span>
                    </div>
                    <a href="#tutorial-export" class="tutorial-link">Start Tutorial</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Page Link -->
    <div class="info-general">
        <h2><i class="fas fa-heartbeat"></i> System Status</h2>
        <div class="status-info">
            <div class="status-indicator">
                <div class="status-dot operational"></div>
                <span>All systems operational</span>
            </div>
            <p>Check our real-time system status and any ongoing maintenance or issues.</p>
            <a href="https://status.wizdam.ai" target="_blank" class="status-link">
                <i class="fas fa-external-link-alt"></i> View Status Page
            </a>
        </div>
    </div>
</div>

<!-- Help Page Styles -->
<style>
.help-search-section {
    background: white;
    border-radius: 20px;
    padding: 40px;
    margin-bottom: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    text-align: center;
}

.search-box {
    position: relative;
    max-width: 600px;
    margin: 0 auto 20px;
}

.search-box input {
    width: 100%;
    padding: 18px 60px 18px 20px;
    border: 2px solid #e9ecef;
    border-radius: 50px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.search-box input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    background: white;
}

.search-box button {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-box button:hover {
    transform: translateY(-50%) scale(1.05);
}

.popular-searches {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}

.popular-searches span {
    color: #666;
    font-weight: 600;
    margin-right: 10px;
}

.search-tag {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    color: #667eea;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-tag:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.help-categories {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 50px;
}

.category-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    cursor: pointer;
    border: 2px solid transparent;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.12);
    border-color: #667eea;
}

.category-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.8rem;
    margin-bottom: 20px;
}

.category-card h3 {
    margin-bottom: 10px;
    color: #333;
    font-size: 1.3rem;
}

.category-card p {
    color: #666;
    line-height: 1.5;
    margin-bottom: 15px;
}

.article-count {
    display: inline-block;
    background: #e9ecef;
    color: #495057;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.faq-categories {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    border-bottom: 1px solid #e9ecef;
}

.faq-tab {
    padding: 12px 20px;
    background: none;
    border: none;
    color: #666;
    font-weight: 600;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
}

.faq-tab.active,
.faq-tab:hover {
    color: #667eea;
    border-bottom-color: #667eea;
}

.faq-category {
    display: none;
}

.faq-category.active {
    display: block;
}

.faq-item {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.faq-item:hover {
    border-color: #667eea;
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
    background: white;
}

.faq-answer.show {
    padding: 20px;
    max-height: 500px;
}

.faq-answer ul,
.faq-answer ol {
    margin: 10px 0;
    padding-left: 20px;
}

.faq-answer li {
    margin-bottom: 5px;
}

.support-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
    margin: 40px 0;
}

.support-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.support-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.12);
    border-color: #667eea;
}

.support-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    margin: 0 auto 20px;
}

.support-card h3 {
    margin-bottom: 10px;
    color: #333;
}

.support-card p {
    color: #666;
    margin-bottom: 20px;
    line-height: 1.5;
}

.support-btn {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 25px;
    border: none;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    cursor: pointer;
}

.support-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.tutorial-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-top: 25px;
}

.tutorial-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.tutorial-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.12);
    border-color: #667eea;
}

.tutorial-thumbnail {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2.5rem;
}

.tutorial-content {
    padding: 20px;
}

.tutorial-content h4 {
    margin-bottom: 10px;
    color: #333;
    font-size: 1.1rem;
}

.tutorial-content p {
    color: #666;
    line-height: 1.5;
    margin-bottom: 15px;
    font-size: 14px;
}

.tutorial-meta {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 12px;
    color: #888;
}

.tutorial-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.tutorial-link {
    display: inline-block;
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    padding: 8px 16px;
    border: 1px solid #667eea;
    border-radius: 20px;
    transition: all 0.3s ease;
    font-size: 14px;
}

.tutorial-link:hover {
    background: #667eea;
    color: white;
}

.status-info {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
}

.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

.status-dot.operational {
    background: #28a745;
}

.status-dot.warning {
    background: #ffc107;
}

.status-dot.error {
    background: #dc3545;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.status-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    padding: 8px 16px;
    border: 1px solid #667eea;
    border-radius: 20px;
    transition: all 0.3s ease;
}

.status-link:hover {
    background: #667eea;
    color: white;
}

@media (max-width: 768px) {
    .help-categories {
        grid-template-columns: 1fr;
    }
    
    .faq-categories {
        flex-wrap: wrap;
    }
    
    .support-section {
        grid-template-columns: 1fr;
    }
    
    .tutorial-grid {
        grid-template-columns: 1fr;
    }
    
    .popular-searches {
        flex-direction: column;
        gap: 15px;
    }
    
    .status-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}
</style>

<!-- Help Page JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Help search functionality
    const helpSearch = document.getElementById('helpSearch');
    const searchBtn = document.getElementById('searchBtn');
    
    function performSearch() {
        const query = helpSearch.value.toLowerCase().trim();
        if (query) {
            // In a real implementation, this would search through help articles
            console.log('Searching for:', query);
            // For now, just highlight matching FAQ items
            highlightSearchResults(query);
        }
    }
    
    if (searchBtn) {
        searchBtn.addEventListener('click', performSearch);
    }
    
    if (helpSearch) {
        helpSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }
    
    // Search highlighting
    function highlightSearchResults(query) {
        const faqItems = document.querySelectorAll('.faq-item');
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question span').textContent.toLowerCase();
            const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
            
            if (question.includes(query) || answer.includes(query)) {
                item.style.border = '2px solid #667eea';
                item.style.background = '#f0f8ff';
            } else {
                item.style.border = '1px solid #e9ecef';
                item.style.background = '';
            }
        });
    }
    
    // Search tags functionality
    window.searchHelp = function(term) {
        helpSearch.value = term;
        performSearch();
        helpSearch.focus();
    };
    
    // FAQ category switching
    window.showFAQCategory = function(categoryId) {
        // Hide all categories
        document.querySelectorAll('.faq-category').forEach(cat => {
            cat.classList.remove('active');
        });
        
        // Remove active from all tabs
        document.querySelectorAll('.faq-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Show selected category
        const category = document.getElementById(categoryId);
        if (category) {
            category.classList.add('active');
        }
        
        // Activate clicked tab
        event.target.classList.add('active');
    };
    
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
    
    // Category card functionality
    window.showCategory = function(categoryId) {
        // In a real implementation, this would navigate to the category page
        console.log('Showing category:', categoryId);
        // For now, just scroll to FAQ section
        document.querySelector('.info-general').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    };
    
    // Auto-expand FAQ if URL has hash
    const hash = window.location.hash.substring(1);
    if (hash) {
        const targetFAQ = document.querySelector(`[data-faq="${hash}"]`);
        if (targetFAQ) {
            setTimeout(() => {
                targetFAQ.click();
                targetFAQ.scrollIntoView({ behavior: 'smooth' });
            }, 500);
        }
    }
    
    // Live chat integration
    const liveChatBtns = document.querySelectorAll('.support-btn[onclick*="chatbot"]');
    liveChatBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Add contextual message to chatbot
            if (window.sendQuickMessage) {
                setTimeout(() => {
                    window.sendQuickMessage("I need help with using the platform");
                }, 1000);
            }
        });
    });
    
    console.log('Help page initialized successfully');
});
</script>