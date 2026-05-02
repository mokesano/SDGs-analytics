<?php
/**
 * Localization Helper for Wizdam AI-sikola
 * 
 * Provides translation functionality using .po/.mo files
 * Supports multiple locales: en_US, id_ID
 */

class LocaleHelper {
    
    private static $instance = null;
    private $currentLocale = 'en_US';
    private $translations = [];
    private $localePath;
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): LocaleHelper {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - initialize locale system
     */
    private function __construct() {
        $this->localePath = dirname(__DIR__) . '/locale';
        $this->detectLocale();
        $this->loadTranslations();
    }
    
    /**
     * Detect user's preferred locale from session, cookie, or browser
     */
    private function detectLocale(): void {
        // Check session first
        if (isset($_SESSION['locale'])) {
            $this->currentLocale = $_SESSION['locale'];
            return;
        }
        
        // Check cookie
        if (isset($_COOKIE['locale'])) {
            $this->currentLocale = $_COOKIE['locale'];
            return;
        }
        
        // Check browser accept-language header
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            if ($browserLang === 'id') {
                $this->currentLocale = 'id_ID';
            }
        }
        
        // Default to en_US
        $this->currentLocale = 'en_US';
    }
    
    /**
     * Load translations for current locale
     */
    private function loadTranslations(): void {
        $poFile = $this->localePath . "/{$this->currentLocale}/LC_MESSAGES/messages.po";
        
        if (!file_exists($poFile)) {
            // Fallback to English
            $poFile = $this->localePath . '/en_US/LC_MESSAGES/messages.po';
        }
        
        if (file_exists($poFile)) {
            $this->translations = $this->parsePOFile($poFile);
        }
    }
    
    /**
     * Parse .po file and extract translations
     */
    private function parsePOFile(string $filePath): array {
        $translations = [];
        $content = file_get_contents($filePath);
        
        // Pattern to match msgid and msgstr pairs
        $pattern = '/msgid\s+"([^"]+)"\s*\nmsgstr\s+"([^"]+)"/';
        
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = $match[1];
                $value = $this->unescapeString($match[2]);
                $translations[$key] = $value;
            }
        }
        
        return $translations;
    }
    
    /**
     * Unescape special characters in PO strings
     */
    private function unescapeString(string $str): string {
        $replacements = [
            '\\"' => '"',
            '\\n' => "\n",
            '\\t' => "\t",
            '\\\\' => '\\',
        ];
        return str_replace(array_keys($replacements), array_values($replacements), $str);
    }
    
    /**
     * Translate a key to current locale
     * 
     * @param string $key Translation key (e.g., "login.page_title")
     * @param array $params Optional parameters for placeholder replacement
     * @return string Translated text or key if not found
     */
    public function translate(string $key, array $params = []): string {
        $text = $this->translations[$key] ?? $key;
        
        // Replace placeholders {0}, {1}, {name} etc.
        foreach ($params as $index => $value) {
            $placeholder = is_numeric($index) ? '{' . $index . '}' : '{' . $index . '}';
            $text = str_replace($placeholder, (string)$value, $text);
        }
        
        return $text;
    }
    
    /**
     * Alias for translate() - convenience method
     */
    public function t(string $key, array $params = []): string {
        return $this->translate($key, $params);
    }
    
    /**
     * Set the current locale
     */
    public function setLocale(string $locale): bool {
        $validLocales = ['en_US', 'id_ID'];
        
        if (!in_array($locale, $validLocales)) {
            return false;
        }
        
        $this->currentLocale = $locale;
        
        // Save to session
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['locale'] = $locale;
        }
        
        // Save to cookie (30 days)
        setcookie('locale', $locale, time() + (30 * 24 * 60 * 60), '/');
        
        // Reload translations
        $this->loadTranslations();
        
        return true;
    }
    
    /**
     * Get current locale
     */
    public function getCurrentLocale(): string {
        return $this->currentLocale;
    }
    
    /**
     * Get all available locales
     */
    public function getAvailableLocales(): array {
        return [
            'en_US' => 'English (US)',
            'id_ID' => 'Bahasa Indonesia',
        ];
    }
    
    /**
     * Get locale display name
     */
    public function getLocaleDisplayName(string $locale): string {
        $locales = $this->getAvailableLocales();
        return $locales[$locale] ?? $locale;
    }
    
    /**
     * Check if current locale is RTL (right-to-left)
     */
    public function isRTL(): bool {
        return false; // Neither en_US nor id_ID are RTL
    }
    
    /**
     * Get date format for current locale
     */
    public function getDateFormat(): string {
        return $this->currentLocale === 'id_ID' ? 'd/m/Y' : 'm/d/Y';
    }
    
    /**
     * Format date according to current locale
     */
    public function formatDate(string|DateTime $date, string $format = null): string {
        if (is_string($date)) {
            $date = new DateTime($date);
        }
        
        $format = $format ?? $this->getDateFormat();
        return $date->format($format);
    }
    
    /**
     * Format number according to current locale
     */
    public function formatNumber(float $number, int $decimals = 2): string {
        if ($this->currentLocale === 'id_ID') {
            return number_format($number, $decimals, ',', '.');
        }
        return number_format($number, $decimals, '.', ',');
    }
    
    /**
     * Pluralization helper
     */
    public function pluralize(int $count, string $singular, string $plural = null): string {
        if ($plural === null) {
            $plural = $singular . 's';
        }
        
        return $count === 1 ? $singular : $plural;
    }
}

/**
 * Global translation function shortcut
 */
function __($key, array $params = []): string {
    return LocaleHelper::getInstance()->translate($key, $params);
}

/**
 * Alternative shorthand
 */
function _t(string $key, array $params = []): string {
    return LocaleHelper::getInstance()->translate($key, $params);
}
