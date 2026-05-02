<?php
/**
 * FAQ Page - Frequently Asked Questions
 * 
 * This page displays FAQs with content loaded from markdown files
 * and translated using the locale system.
 */

require_once __DIR__ . '/../../includes/markdown_parser.php';
require_once __DIR__ . '/../../includes/locale.php';

// Get current locale
$locale = get_current_locale();

// Load FAQ content from markdown
$contentFile = __DIR__ . '/../../content/markdown/faq_content.md';
$faqContent = parseMarkdownContent($contentFile);

// Page metadata
$pageTitle = __('faq_page_title');
$pageDescription = __('faq_page_description');

// Use main layout
include __DIR__ . '/../../layouts/main.php';
