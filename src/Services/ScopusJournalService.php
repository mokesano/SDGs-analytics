<?php

declare(strict_types=1);

namespace Wizdam\Services;

use Exception;

/**
 * Scopus Journal Service
 * 
 * Wrapper class untuk SCOPUS_Journal-Checker_API.php yang menyediakan interface OOP
 * untuk pengecekan jurnal Scopus tanpa mengubah file API asli.
 * 
 * @version 1.0.0
 * @author Wizdam Team
 * @license MIT
 */
class ScopusJournalService
{
    private string $apiFilePath;
    private ?object $apiInstance = null;
    private string $apiKey;

    /**
     * Constructor
     */
    public function __construct(string $apiFilePath = '', string $apiKey = '')
    {
        $this->apiFilePath = $apiFilePath ?: PROJECT_ROOT . '/api/SCOPUS_Journal-Checker_API.php';
        
        if (!file_exists($this->apiFilePath)) {
            throw new Exception('Scopus Journal Checker API file not found: ' . $this->apiFilePath);
        }

        $this->apiKey = $apiKey ?: '2b2a63a2cd69bd0cfd7acc07addc140f';
        
        // Load API class without executing the whole file
        $this->loadApiClass();
    }

    /**
     * Load ScopusAPI class from file
     */
    private function loadApiClass(): void
    {
        if (class_exists('ScopusAPI', false)) {
            return;
        }

        $content = file_get_contents($this->apiFilePath);
        
        // Extract only the ScopusAPI class definition
        if (preg_match('/class\s+ScopusAPI\s*\{[^}]*\}/s', $content, $matches)) {
            // We need to extract more carefully with nested braces
            $classStart = strpos($content, 'class ScopusAPI');
            if ($classStart !== false) {
                // Find the opening brace
                $braceStart = strpos($content, '{', $classStart);
                if ($braceStart !== false) {
                    // Count braces to find the end of the class
                    $braceCount = 1;
                    $pos = $braceStart + 1;
                    while ($pos < strlen($content) && $braceCount > 0) {
                        if ($content[$pos] === '{') {
                            $braceCount++;
                        } elseif ($content[$pos] === '}') {
                            $braceCount--;
                        }
                        $pos++;
                    }
                    
                    $classCode = substr($content, $classStart, $pos - $classStart);
                    
                    // Replace global variable references with instance property
                    $classCode = str_replace(
                        ['$SCOPUS_API_KEY', '$this->apiKey'],
                        ['"' . $this->apiKey . '"', '$this->apiKey'],
                        $classCode
                    );
                    
                    eval('?>' . $classCode);
                }
            }
        }
    }

    /**
     * Search journal by ISSN
     * 
     * @param string $issn ISSN in format XXXX-XXXX
     * @return array Journal data or error
     */
    public function searchByIssn(string $issn): array
    {
        if (!$this->apiInstance) {
            $this->apiInstance = new \ScopusAPI($this->apiKey);
        }

        return $this->apiInstance->searchByISSN($issn);
    }

    /**
     * Validate ISSN format
     * 
     * @param string $issn ISSN to validate
     * @return bool True if valid
     */
    public function isValidIssn(string $issn): bool
    {
        $clean = preg_replace('/[^0-9X]/', '', strtoupper($issn));
        
        if (strlen($clean) !== 8) {
            return false;
        }

        // ISSN checksum validation
        if ($clean[7] === 'X') {
            $checkValue = 10;
        } else {
            $checkValue = (int)$clean[7];
        }

        $total = 0;
        for ($i = 0; $i < 7; $i++) {
            $total += (int)$clean[$i] * (8 - $i);
        }

        $remainder = $total % 11;
        $calculatedCheck = (12 - $remainder) % 11;

        return $calculatedCheck === $checkValue;
    }

    /**
     * Format ISSN to standard XXXX-XXXX format
     * 
     * @param string $issn Raw ISSN
     * @return string Formatted ISSN
     */
    public function formatIssn(string $issn): string
    {
        $clean = preg_replace('/[^0-9X]/', '', strtoupper($issn));
        return substr($clean, 0, 4) . '-' . substr($clean, 4, 4);
    }

    /**
     * Get API key
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Set API key
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
        $this->apiInstance = null; // Reset instance to use new key
    }
}
