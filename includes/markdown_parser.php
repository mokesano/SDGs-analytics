<?php
/**
 * Markdown Content Parser
 * 
 * Parses markdown files with localization support
 * Replaces {{key}} placeholders with translated strings
 */

class MarkdownParser {
    
    /**
     * Parse markdown file with localization
     * 
     * @param string $filePath Path to .md file
     * @return string Parsed HTML content
     */
    public static function parse(string $filePath): string {
        if (!file_exists($filePath)) {
            return '<p class="error">Content file not found.</p>';
        }
        
        $content = file_get_contents($filePath);
        
        // Replace localization placeholders {{key}} with translated strings
        $content = preg_replace_callback('/\{\{([a-zA-Z0-9_.]+)\}\}/', function($matches) {
            $key = $matches[1];
            return __($key);
        }, $content);
        
        // Convert markdown to HTML
        $content = self::markdownToHtml($content);
        
        return $content;
    }
    
    /**
     * Simple markdown to HTML converter
     */
    private static function markdownToHtml(string $markdown): string {
        // Headers
        $markdown = preg_replace('/^###### (.*+)$/m', '<h6>$1</h6>', $markdown);
        $markdown = preg_replace('/^##### (.*+)$/m', '<h5>$1</h5>', $markdown);
        $markdown = preg_replace('/^#### (.*+)$/m', '<h4>$1</h4>', $markdown);
        $markdown = preg_replace('/^### (.*+)$/m', '<h3>$1</h3>', $markdown);
        $markdown = preg_replace('/^## (.*+)$/m', '<h2>$1</h2>', $markdown);
        $markdown = preg_replace('/^# (.*+)$/m', '<h1>$1</h1>', $markdown);
        
        // Bold and italic
        $markdown = preg_replace('/\*\*\*(.+?)\*\*\*/s', '<strong><em>$1</em></strong>', $markdown);
        $markdown = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $markdown);
        $markdown = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $markdown);
        
        // Links
        $markdown = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $markdown);
        
        // Unordered lists
        $markdown = preg_replace_callback('/^- (.*+)$/m', function($matches) {
            return '<li>' . $matches[1] . '</li>';
        }, $markdown);
        $markdown = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $markdown);
        
        // Horizontal rule
        $markdown = preg_replace('/^---$/m', '<hr>', $markdown);
        
        // Paragraphs (simple approach: wrap lines that aren't already HTML tags)
        $lines = explode("\n", $markdown);
        $processedLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed && !preg_match('/^<(h[1-6]|ul|ol|li|hr|div|p|blockquote|pre|code)/i', $trimmed)) {
                $line = '<p>' . $trimmed . '</p>';
            }
            $processedLines[] = $line;
        }
        $markdown = implode("\n", $processedLines);
        
        // Clean up multiple empty paragraphs
        $markdown = preg_replace('/(<p>\s*<\/p>)+/', '', $markdown);
        
        return $markdown;
    }
    
    /**
     * Parse markdown and wrap in content div
     */
    public static function render(string $filePath, array $extraData = []): string {
        $html = self::parse($filePath);
        
        // Extract variables for template if provided
        if (!empty($extraData)) {
            extract($extraData);
        }
        
        return '<div class="markdown-content">' . $html . '</div>';
    }
}

/**
 * Helper function for quick markdown rendering
 */
function render_markdown(string $filePath, array $data = []): string {
    return MarkdownParser::render($filePath, $data);
}
