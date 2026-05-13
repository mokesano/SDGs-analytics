<?php

declare(strict_types=1);

namespace Wizdam\Services;

/**
 * Researcher Identity Service
 * 
 * Mengekstrak dan memvalidasi berbagai identifier peneliti dari data ORCID,
 * termasuk Scopus Author ID, ResearcherID (Web of Science), Publons, dll.
 * 
 * @package Wizdam\Services
 * @author Wizdam Team
 * @version 1.0.0
 */
class ResearcherIdentityService
{
    /**
     * Tipe-tipe identifier eksternal yang didukung
     */
    private const SUPPORTED_TYPES = [
        'scopus-author-id' => 'Scopus Author ID',
        'researcherid' => 'Web of Science ResearcherID',
        'publon' => 'Publons',
        'google-scholar' => 'Google Scholar',
        'orcid' => 'ORCID',
        'isni' => 'ISNI',
        'viaf' => 'VIAF',
    ];

    /**
     * Ekstrak semua identifier eksternal dari data profil ORCID
     * 
     * @param array $personData Data profil dari ORCID API
     * @return array Associative array dengan tipe ID sebagai key
     */
    public function extractAllIdentifiers(array $personData): array
    {
        $identifiers = [];
        
        // Ekstrak dari external-identifiers
        if (!empty($personData['external-identifiers']['external-identifier'])) {
            foreach ($personData['external-identifiers']['external-identifier'] as $extId) {
                $type = isset($extId['external-id-type']) 
                    ? strtolower(trim($extId['external-id-type'])) 
                    : null;
                $value = isset($extId['external-id-value']) 
                    ? trim($extId['external-id-value']) 
                    : null;
                $url = isset($extId['external-id-url']['value']) 
                    ? trim($extId['external-id-url']['value']) 
                    : null;

                if ($type && $value) {
                    $normalizedType = $this->normalizeIdentifierType($type);
                    $identifiers[$normalizedType] = [
                        'type' => $normalizedType,
                        'original_type' => $type,
                        'value' => $value,
                        'url' => $url,
                        'display_name' => self::SUPPORTED_TYPES[$normalizedType] ?? ucfirst($type),
                        'is_supported' => array_key_exists($normalizedType, self::SUPPORTED_TYPES),
                    ];
                }
            }
        }

        // Ekstrak dari researcher-urls (untuk ID yang mungkin ada di URL)
        if (!empty($personData['researcher-urls']['researcher-url'])) {
            foreach ($personData['researcher-urls']['researcher-url'] as $urlData) {
                $urlName = isset($urlData['url-name']) ? trim($urlData['url-name']) : '';
                $urlValue = isset($urlData['url']['value']) ? trim($urlData['url']['value']) : '';
                
                $extractedIds = $this->extractIdsFromUrl($urlName, $urlValue);
                foreach ($extractedIds as $type => $data) {
                    if (!isset($identifiers[$type])) {
                        $identifiers[$type] = $data;
                    }
                }
            }
        }

        return $identifiers;
    }

    /**
     * Dapatkan Scopus Author ID dari data ORCID
     * 
     * @param array $personData Data profil dari ORCID API
     * @return string|null Scopus Author ID atau null jika tidak ditemukan
     */
    public function getScopusAuthorId(array $personData): ?string
    {
        $identifiers = $this->extractAllIdentifiers($personData);
        return $identifiers['scopus-author-id']['value'] ?? null;
    }

    /**
     * Dapatkan Web of Science ResearcherID dari data ORCID
     * 
     * @param array $personData Data profil dari ORCID API
     * @return string|null ResearcherID atau null jika tidak ditemukan
     */
    public function getResearcherId(array $personData): ?string
    {
        $identifiers = $this->extractAllIdentifiers($personData);
        return $identifiers['researcherid']['value'] ?? null;
    }

    /**
     * Dapatkan Publons ID dari data ORCID
     * 
     * @param array $personData Data profil dari ORCID API
     * @return string|null Publons ID atau null jika tidak ditemukan
     */
    public function getPublonsId(array $personData): ?string
    {
        $identifiers = $this->extractAllIdentifiers($personData);
        return $identifiers['publon']['value'] ?? null;
    }

    /**
     * Dapatkan Google Scholar ID dari data ORCID
     * 
     * @param array $personData Data profil dari ORCID API
     * @return string|null Google Scholar ID atau null jika tidak ditemukan
     */
    public function getGoogleScholarId(array $personData): ?string
    {
        $identifiers = $this->extractAllIdentifiers($personData);
        return $identifiers['google-scholar']['value'] ?? null;
    }

    /**
     * Normalisasi tipe identifier menjadi format standar
     * 
     * @param string $type Tipe identifier asli dari ORCID
     * @return string Tipe identifier yang dinormalisasi
     */
    private function normalizeIdentifierType(string $type): string
    {
        $type = strtolower(trim($type));
        
        $mapping = [
            'scopus-author-id' => 'scopus-author-id',
            'scopus author id' => 'scopus-author-id',
            'scopus' => 'scopus-author-id',
            'scopus_author_id' => 'scopus-author-id',
            'researcherid' => 'researcherid',
            'researcher id' => 'researcherid',
            'web of science researcherid' => 'researcherid',
            'wos-researcher-id' => 'researcherid',
            'publon' => 'publon',
            'publons' => 'publon',
            'google-scholar' => 'google-scholar',
            'google scholar' => 'google-scholar',
            'google_scholar' => 'google-scholar',
            'orcid' => 'orcid',
            'isni' => 'isni',
            'viaf' => 'viaf',
        ];

        return $mapping[$type] ?? $type;
    }

    /**
     * Ekstrak identifier dari URL researcher
     * 
     * @param string $urlName Nama URL dari ORCID
     * @param string $urlValue URL lengkap
     * @return array Array identifier yang ditemukan
     */
    private function extractIdsFromUrl(string $urlName, string $urlValue): array
    {
        $ids = [];
        
        // Cek Scopus dari URL - support berbagai format URL Scopus
        if (preg_match('/scopus\.elsevier\.com.*?[Aa]uthor[IDi][Dd]?=(\d+)/', $urlValue, $matches) ||
            preg_match('/scopus\.com.*?[Aa]uthor[IDi][Dd]?=(\d+)/', $urlValue, $matches)) {
            $ids['scopus-author-id'] = [
                'type' => 'scopus-author-id',
                'original_type' => 'url-extracted',
                'value' => $matches[1],
                'url' => $urlValue,
                'display_name' => 'Scopus Author ID',
                'is_supported' => true,
            ];
        }

        // Cek ResearcherID dari URL
        if (preg_match('/researcherid\.com\/rid\/([A-Z0-9\-]+)/', $urlValue, $matches)) {
            $ids['researcherid'] = [
                'type' => 'researcherid',
                'original_type' => 'url-extracted',
                'value' => $matches[1],
                'url' => $urlValue,
                'display_name' => 'Web of Science ResearcherID',
                'is_supported' => true,
            ];
        }

        // Cek Publons dari URL
        if (preg_match('/publons\.com\/researcher\/(\d+)/', $urlValue, $matches)) {
            $ids['publon'] = [
                'type' => 'publon',
                'original_type' => 'url-extracted',
                'value' => $matches[1],
                'url' => $urlValue,
                'display_name' => 'Publons',
                'is_supported' => true,
            ];
        }

        // Cek Google Scholar dari URL
        if (preg_match('/scholar\.google\.com\/citations\?user=([a-zA-Z0-9_-]+)/', $urlValue, $matches)) {
            $ids['google-scholar'] = [
                'type' => 'google-scholar',
                'original_type' => 'url-extracted',
                'value' => $matches[1],
                'url' => $urlValue,
                'display_name' => 'Google Scholar',
                'is_supported' => true,
            ];
        }

        return $ids;
    }

    /**
     * Validasi format Scopus Author ID
     * Scopus Author ID adalah angka 10-11 digit
     * 
     * @param string $scopusId Scopus Author ID
     * @return bool True jika valid
     */
    public function isValidScopusAuthorId(string $scopusId): bool
    {
        return preg_match('/^\d{10,11}$/', $scopusId) === 1;
    }

    /**
     * Validasi format ResearcherID (Web of Science)
     * Format: huruf kapital + angka + dash, contoh: A-1234-2008
     * 
     * @param string $researcherId ResearcherID
     * @return bool True jika valid
     */
    public function isValidResearcherId(string $researcherId): bool
    {
        return preg_match('/^[A-Z]{1,2}-\d{4}-\d{4}$/', $researcherId) === 1;
    }

    /**
     * Dapatkan ringkasan identifier yang tersedia
     * 
     * @param array $personData Data profil dari ORCID API
     * @return array Ringkasan identifier
     */
    public function getIdentifierSummary(array $personData): array
    {
        $allIds = $this->extractAllIdentifiers($personData);
        
        return [
            'has_scopus_id' => isset($allIds['scopus-author-id']),
            'has_researcher_id' => isset($allIds['researcherid']),
            'has_publons_id' => isset($allIds['publon']),
            'has_google_scholar_id' => isset($allIds['google-scholar']),
            'total_identifiers' => count($allIds),
            'supported_identifiers' => array_filter(
                $allIds,
                fn($id) => $id['is_supported'] ?? false
            ),
            'unsupported_identifiers' => array_filter(
                $allIds,
                fn($id) => !($id['is_supported'] ?? false)
            ),
        ];
    }
}
