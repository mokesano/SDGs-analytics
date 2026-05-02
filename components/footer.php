<?php
// components/footer.php - Footer component
?>

<!-- Main Footer -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-main">
            <!-- Company Information -->
            <div class="footer-brand">
                <div class="footer-logo">
                    <div class="footer-logo-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="footer-logo-text">Wizdam AI</div>
                    <p>Version <?php echo VERSION; ?></p>
                </div>
                <p class="footer-description">
                    Advanced AI-powered platform for analyzing research contributions to Sustainable Development Goals. 
                    Empowering researchers and institutions with intelligent classification and insights.
                </p>
                <div class="footer-social">
                    <a href="https://twitter.com/wizdamai" target="_blank" rel="noopener noreferrer" aria-label="Follow us on Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://linkedin.com/company/wizdam-ai" target="_blank" rel="noopener noreferrer" aria-label="Connect with us on LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="https://github.com/wizdam-ai" target="_blank" rel="noopener noreferrer" aria-label="View our projects on GitHub">
                        <i class="fab fa-github"></i>
                    </a>
                    <a href="https://youtube.com/@wizdamai" target="_blank" rel="noopener noreferrer" aria-label="Subscribe to our YouTube channel">
                        <i class="fab fa-youtube"></i>
                    </a>
                    <a href="mailto:contact@wizdam.ai" aria-label="Send us an email">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>

            <!-- Platform Links -->
            <div class="footer-section">
                <h4>Platform</h4>
                <ul class="footer-links">
                    <li><a href="?page=home"><i class="fas fa-home"></i>Home</a></li>
                    <li><a href="?page=analitics-dashboard"><i class="fas fa-chart-line"></i>Analytics Dashboard</a></li>
                    <li><a href="?page=bulk-analysis"><i class="fas fa-tasks"></i>Bulk Analysis</a></li>
                    <li><a href="?page=api-access"><i class="fas fa-key"></i>API Access</a></li>
                    <li><a href="?page=integration-tools"><i class="fas fa-plug"></i>Integration Tools</a></li>
                </ul>
            </div>

            <!-- Resources Links -->
            <div class="footer-section">
                <h4>Resources</h4>
                <ul class="footer-links">
                    <li><a href="?page=documentation"><i class="fas fa-book"></i>Documentation</a></li>
                    <li><a href="?page=tutorials"><i class="fas fa-graduation-cap"></i>Tutorials</a></li>
                    <li><a href="?page=api-reference"><i class="fas fa-code"></i>API Reference</a></li>
                    <li><a href="?page=research-papers"><i class="fas fa-file-alt"></i>Research Papers</a></li>
                    <li><a href="?page=help"><i class="fas fa-question-circle"></i>Help Center</a></li>
                </ul>
            </div>

            <!-- Community Links -->
            <div class="footer-section">
                <h4>Community</h4>
                <ul class="footer-links">
                    <li><a href="?page=community-forum"><i class="fas fa-comments"></i>Forum</a></li>
                    <li><a href="?page=blog"><i class="fas fa-rss"></i>Blog & Updates</a></li>
                    <li><a href="?page=teams"><i class="fas fa-users"></i>Teams</a></li>
                    <li><a href="?page=partners"><i class="fas fa-handshake"></i>Partners</a></li>
                    <li><a href="?page=careers"><i class="fas fa-briefcase"></i>Careers</a></li>
                </ul>
            </div>

            <!-- Company Links -->
            <div class="footer-section">
                <h4>Company</h4>
                <ul class="footer-links">
                    <li><a href="?page=about"><i class="fas fa-info-circle"></i>About Us</a></li>
                    <li><a href="?page=contact"><i class="fas fa-envelope"></i>Contact</a></li>
                    <li><a href="?page=press-kit"><i class="fas fa-newspaper"></i>Press Kit</a></li>
                    <li><a href="?page=privacy-policy"><i class="fas fa-shield-alt"></i>Privacy Policy</a></li>
                    <li><a href="?page=archived"><i class="fas fa-archive"></i>Archive</a></li>
                </ul>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <p class="footer-copyright">&copy; <?php echo date('Y'); ?> Wizdam by PT. Sangia Research Media and Publishing. All rights reserved.</p>
            <div class="footer-bottom-links">
                <a href="?page=privacy-policy">Privacy Policy</a>
                <a href="?page=terms-of-service">Terms of Service</a>
                <a href="?page=cookie-policy">Cookie Policy</a>
                <a href="?page=accessibility">Accessibility</a>
                <a href="?page=sitemap">Sitemap</a>
            </div>
        </div>
    </div>
</footer>

<!-- Chatbot Component -->
<?php include 'chatbot.php'; ?>

<!-- Back to Top Button -->
<button id="backToTop" class="back-to-top" aria-label="Back to top">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Cookie Consent Banner -->
<div id="cookieConsent" class="cookie-consent" style="display: none;">
    <div class="cookie-content">
        <div class="cookie-text">
            <i class="fas fa-cookie-bite"></i>
            <span>We use cookies to enhance your experience and analyze site usage. By continuing to use this site, you consent to our use of cookies.</span>
        </div>
        <div class="cookie-actions">
            <button id="acceptCookies" class="cookie-btn cookie-accept">Accept All</button>
            <button id="customizeCookies" class="cookie-btn cookie-customize">Customize</button>
            <button id="declineCookies" class="cookie-btn cookie-decline">Decline</button>
        </div>
    </div>
</div>

<!-- Local JavaScript -->
<script src="assets/js/script.js" defer></script>
<script src="assets/js/charts.js" defer></script>
<script src="assets/js/scroll-reveal.js" defer></script>

<!-- Additional Page-Specific Scripts -->
<?php if (isset($additional_scripts)): ?>
    <?php foreach ($additional_scripts as $script): ?>
        <script src="<?php echo $script; ?>" defer></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Inline JavaScript -->
<?php if (isset($inline_scripts)): ?>
    <script><?php echo $inline_scripts; ?></script>
<?php endif; ?>

<!-- Footer Styles -->
<style>
/* Back to Top Button */
.back-to-top {
    position: fixed;
    bottom: 100px;
    right: 30px;
    width: 50px;
    height: 50px;
    background: var(--brand, #ff5627);
    color: white;
    border: none;
    border-radius: 50%;
    font-size: 18px;
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px);
    transition: all 0.3s ease;
    z-index: 1000;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.back-to-top.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.back-to-top:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

/* Cookie Consent */
.cookie-consent {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.95);
    color: white;
    padding: 20px;
    z-index: 10000;
    border-top: 3px solid var(--brand, #ff5627);
}

.cookie-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.cookie-text {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}

.cookie-text i {
    font-size: 24px;
    color: var(--brand, #ff5627);
}

.cookie-actions {
    display: flex;
    gap: 10px;
}

.cookie-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
}

.cookie-accept {
    background: var(--brand, #ff5627);
    color: white;
}

.cookie-accept:hover {
    background: var(--brand-hover, #e0481d);
}

.cookie-customize {
    background: transparent;
    color: white;
    border: 1px solid var(--brand, #ff5627);
}

.cookie-customize:hover {
    background: var(--brand, #ff5627);
    color: white;
}

.cookie-decline {
    background: transparent;
    color: #ccc;
    border: 1px solid #666;
}

.cookie-decline:hover {
    background: #666;
    color: white;
}

@media (max-width: 768px) {
    .cookie-content {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .cookie-actions {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .cookie-btn {
        flex: 1;
        min-width: 100px;
    }
    
    .back-to-top {
        bottom: 120px;
        right: 20px;
        width: 45px;
        height: 45px;
        font-size: 16px;
    }
}
</style>

<!-- Footer JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Back to Top functionality
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

    // Cookie Consent functionality
    const cookieConsent = document.getElementById('cookieConsent');
    const acceptCookies = document.getElementById('acceptCookies');
    const customizeCookies = document.getElementById('customizeCookies');
    const declineCookies = document.getElementById('declineCookies');

    // Check if user has already made a cookie choice
    const cookieChoice = localStorage.getItem('cookieConsent');
    
    if (!cookieChoice) {
        // Show cookie banner after a delay
        setTimeout(() => {
            if (cookieConsent) {
                cookieConsent.style.display = 'block';
                cookieConsent.style.animation = 'slideUpFade 0.5s ease';
            }
        }, 2000);
    }

    if (acceptCookies) {
        acceptCookies.addEventListener('click', function() {
            localStorage.setItem('cookieConsent', 'accepted');
            hideCookieBanner();
            // Initialize analytics and tracking here
            initializeTracking();
        });
    }

    if (customizeCookies) {
        customizeCookies.addEventListener('click', function() {
            // Open cookie customization modal/page
            window.open('?page=cookie-settings', '_blank');
        });
    }

    if (declineCookies) {
        declineCookies.addEventListener('click', function() {
            localStorage.setItem('cookieConsent', 'declined');
            hideCookieBanner();
            // Disable non-essential tracking
            disableTracking();
        });
    }

    function hideCookieBanner() {
        if (cookieConsent) {
            cookieConsent.style.animation = 'slideDownFade 0.5s ease';
            setTimeout(() => {
                cookieConsent.style.display = 'none';
            }, 500);
        }
    }

    function initializeTracking() {
        // Initialize Google Analytics and other tracking scripts
        console.log('Tracking initialized');
        
        // Example: Enable Google Analytics
        if (typeof gtag === 'function') {
            gtag('consent', 'update', {
                'analytics_storage': 'granted'
            });
        }
    }

    function disableTracking() {
        // Disable tracking scripts
        console.log('Tracking disabled');
        
        // Example: Disable Google Analytics
        if (typeof gtag === 'function') {
            gtag('consent', 'update', {
                'analytics_storage': 'denied'
            });
        }
    }

    // Social media share functionality
    window.shareOnSocial = function(platform, url, text) {
        const shareUrl = url || window.location.href;
        const shareText = text || document.title;
        let shareLink = '';

        switch (platform) {
            case 'twitter':
                shareLink = `https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl)}&text=${encodeURIComponent(shareText)}`;
                break;
            case 'linkedin':
                shareLink = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl)}`;
                break;
            case 'facebook':
                shareLink = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`;
                break;
            case 'email':
                shareLink = `mailto:?subject=${encodeURIComponent(shareText)}&body=${encodeURIComponent(shareUrl)}`;
                break;
        }

        if (shareLink) {
            window.open(shareLink, '_blank', 'width=600,height=400');
        }
    };

    // Print functionality
    window.printPage = function() {
        window.print();
    };

    // Newsletter subscription (if implemented)
    const newsletterForm = document.getElementById('newsletterForm');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            
            // Add your newsletter subscription logic here
            console.log('Newsletter subscription:', email);
            
            // Show success message
            const successMsg = document.createElement('div');
            successMsg.className = 'alert alert-success';
            successMsg.textContent = 'Thank you for subscribing to our newsletter!';
            this.parentNode.insertBefore(successMsg, this.nextSibling);
            
            // Reset form
            this.reset();
            
            // Remove success message after 5 seconds
            setTimeout(() => {
                if (successMsg.parentNode) {
                    successMsg.parentNode.removeChild(successMsg);
                }
            }, 5000);
        });
    }

    // Accessibility improvements
    const socialLinks = document.querySelectorAll('.footer-social a');
    socialLinks.forEach(link => {
        link.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });

    // Lazy load footer images if any
    const footerImages = document.querySelectorAll('.footer img[data-src]');
    if (footerImages.length > 0 && 'IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });

        footerImages.forEach(img => imageObserver.observe(img));
    }

    console.log('Footer component initialized successfully');
});

// Add cookie consent animation styles
const cookieStyles = document.createElement('style');
cookieStyles.textContent = `
    @keyframes slideUpFade {
        from {
            transform: translateY(100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    @keyframes slideDownFade {
        from {
            transform: translateY(0);
            opacity: 1;
        }
        to {
            transform: translateY(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(cookieStyles);
</script>

<!-- Performance and SEO Enhancements -->
<script>
// Service Worker registration for PWA features
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
            .then(function(registration) {
                console.log('ServiceWorker registration successful');
            })
            .catch(function(err) {
                console.log('ServiceWorker registration failed');
            });
    });
}

// Web Vitals monitoring
if ('PerformanceObserver' in window) {
    // Largest Contentful Paint
    new PerformanceObserver((entryList) => {
        for (const entry of entryList.getEntries()) {
            console.log('LCP:', entry.startTime);
        }
    }).observe({entryTypes: ['largest-contentful-paint']});

    // First Input Delay
    new PerformanceObserver((entryList) => {
        for (const entry of entryList.getEntries()) {
            console.log('FID:', entry.processingStart - entry.startTime);
        }
    }).observe({entryTypes: ['first-input']});

    // Cumulative Layout Shift
    new PerformanceObserver((entryList) => {
        for (const entry of entryList.getEntries()) {
            if (!entry.hadRecentInput) {
                console.log('CLS:', entry.value);
            }
        }
    }).observe({entryTypes: ['layout-shift']});
}
</script>

</body>
</html>