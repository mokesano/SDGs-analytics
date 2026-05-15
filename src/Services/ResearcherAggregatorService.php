<?php

declare(strict_types=1);

namespace Wizdam\Services;

/**
 * Multi-Database Researcher Aggregator Service
 * 
 * Mengagregasi data peneliti dari berbagai sumber (ORCID, Scopus, Web of Science)
 * dengan menggunakan identifier yang diekstrak dari profil ORCID.
 * 
 * Alur kerja:
 * 1. Ekstrak Scopus ID, ResearcherID, dll dari profil ORCID
 * 2. Gunakan ID tersebut untuk mengambil data dari masing-masing database
 * 3. Agregasikan semua data menjadi satu profil lengkap
 * 
 * @package Wizdam\Services
 * @author Wizdam Team
 * @version 1.0.0
 */
class ResearcherAggregatorService
{
    private ResearcherIdentityService $identityService;
    private OrcidProfileService $orcidService;
    private ?ScopusResearcherService $scopusService;
    private ?WosResearcherService $wosService;
    private SdgClassificationService $sdgService;
    private string $cacheDir;
    private int $cacheTtl;

    /**
     * Constructor
     * 
     * @param string|null $scopusApiKey Scopus API Key (opsional)
     * @param string|null $wosApiKey Web of Science API Key (opsional)
     * @param string $cacheDir Direktori cache
     * @param int $cacheTtl TTL cache dalam detik
     */
    public function __construct(
        ?string $scopusApiKey = null,
        ?string $wosApiKey = null,
        string $cacheDir = __DIR__ . '/../../api/cache',
        int $cacheTtl = 604800
    ) {
        $this->identityService = new ResearcherIdentityService();
        $this->orcidService = new OrcidProfileService('', $cacheDir, $cacheTtl);
        $this->sdgService = new SdgClassificationService();
        
        // Inisialisasi services jika API key tersedia atau gunakan fallback
        // Parameter order: ScopusResearcherService($apiKey, $projectRoot, $cacheTTL)
        try {
            $this->scopusService = new ScopusResearcherService($scopusApiKey ?: null, '', $cacheTtl);
        } catch (\Exception $e) {
            $this->scopusService = null;
        }
        
        try {
            $this->wosService = new WosResearcherService($wosApiKey, $cacheDir, $cacheTtl);
        } catch (\Exception $e) {
            $this->wosService = null;
        }
        
        $this->cacheDir = $cacheDir;
        $this->cacheTtl = $cacheTtl;
        
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
    }

    /**
     * Dapatkan profil lengkap peneliti dari semua sumber
     * 
     * @param string $orcid ORCID peneliti
     * @return array Profil lengkap dengan data dari semua sumber
     */
    public function getCompleteProfile(string $orcid): array
    {
        // Validasi ORCID
        if (!$this->orcidService->isValidOrcid($orcid)) {
            return [
                'success' => false,
                'error' => 'Invalid ORCID format',
                'orcid' => $orcid,
            ];
        }

        // 1. Ambil data dasar dari ORCID
        $orcidProfile = $this->orcidService->getProfile($orcid);
        
        if (!$orcidProfile || empty($orcidProfile['name'])) {
            return [
                'success' => false,
                'error' => 'Failed to fetch ORCID profile',
                'orcid' => $orcid,
            ];
        }

        // 2. Ekstrak semua identifier dari profil ORCID
        $identifiers = $this->identityService->extractAllIdentifiers($orcidProfile);
        $identifierSummary = $this->identityService->getIdentifierSummary($orcidProfile);

        // 3. Siapkan struktur profil agregat
        $profile = [
            'success' => true,
            'orcid' => $orcid,
            'basic_info' => [
                'name' => $orcidProfile['name'] ?? 'Unknown',
                'given_names' => $orcidProfile['given_names'] ?? [],
                'family_name' => $orcidProfile['family_name'] ?? '',
                'keywords' => $orcidProfile['keywords'] ?? [],
                'urls' => $orcidProfile['urls'] ?? [],
            ],
            'identifiers' => [
                'summary' => $identifierSummary,
                'all_identifiers' => $identifiers,
                'scopus_author_id' => $this->identityService->getScopusAuthorId($orcidProfile),
                'researcher_id' => $this->identityService->getResearcherId($orcidProfile),
                'publons_id' => $this->identityService->getPublonsId($orcidProfile),
                'google_scholar_id' => $this->identityService->getGoogleScholarId($orcidProfile),
            ],
            'scopus_data' => null,
            'wos_data' => null,
            'publications' => [],
            'metrics' => [
                'total_publications' => 0,
                'total_citations' => 0,
                'h_index' => 0,
                'sources' => [],
            ],
            'sdg_analysis' => null,
        ];

        // 4. Ambil data dari Scopus jika tersedia Scopus ID
        $scopusId = $profile['identifiers']['scopus_author_id'];
        if ($scopusId && $this->scopusService) {
            try {
                $scopusProfile = $this->scopusService->getResearcherProfile($scopusId);
                
                if ($scopusProfile) {
                    $profile['scopus_data'] = $scopusProfile;
                    
                    // Merge metrics dari Scopus
                    if (!empty($scopusProfile['metrics'])) {
                        $profile['metrics']['h_index'] = max(
                            $profile['metrics']['h_index'],
                            $scopusProfile['metrics']['h_index'] ?? 0
                        );
                        $profile['metrics']['total_citations'] = max(
                            $profile['metrics']['total_citations'],
                            $scopusProfile['metrics']['cited_by_count'] ?? 0
                        );
                        $profile['metrics']['sources'][] = 'Scopus';
                    }
                    
                    // Ambil publikasi dari Scopus menggunakan Scopus Author ID
                    // (getPublications() membutuhkan ORCID, bukan Scopus ID)
                    $scopusPubs = $this->scopusService->getPublicationsByScopusId($scopusId, 25);
                    $profile['publications'] = array_merge(
                        $profile['publications'],
                        $this->normalizePublications($scopusPubs, 'Scopus')
                    );
                }
            } catch (\Exception $e) {
                error_log("Error fetching Scopus data: " . $e->getMessage());
                $profile['scopus_data'] = ['error' => $e->getMessage()];
            }
        }

        // 5. Ambil data dari Web of Science jika tersedia ResearcherID
        $researcherId = $profile['identifiers']['researcher_id'];
        if ($researcherId && $this->wosService) {
            try {
                $wosProfile = $this->wosService->getResearcherProfile($researcherId);
                
                if ($wosProfile) {
                    $profile['wos_data'] = $wosProfile;
                    
                    // Merge metrics dari WoS
                    if (!empty($wosProfile['metrics'])) {
                        $profile['metrics']['h_index'] = max(
                            $profile['metrics']['h_index'],
                            $wosProfile['metrics']['h_index'] ?? 0
                        );
                        $profile['metrics']['total_citations'] = max(
                            $profile['metrics']['total_citations'],
                            $wosProfile['metrics']['total_citations'] ?? 0
                        );
                        $profile['metrics']['sources'][] = 'Web of Science';
                    }
                    
                    // Ambil publikasi dari WoS
                    $wosPubs = $this->wosService->getPublications($researcherId, 25);
                    $profile['publications'] = array_merge(
                        $profile['publications'],
                        $this->normalizePublications($wosPubs, 'WoS')
                    );
                }
            } catch (\Exception $e) {
                error_log("Error fetching WoS data: " . $e->getMessage());
                $profile['wos_data'] = ['error' => $e->getMessage()];
            }
        }

        // 6. Jika tidak ada publikasi dari Scopus/WoS, ambil dari ORCID
        if (empty($profile['publications'])) {
            $orcidWorks = $this->orcidService->getWorks($orcid);
            if (!empty($orcidWorks)) {
                $profile['publications'] = $this->normalizePublications($orcidWorks, 'ORCID');
            }
        }

        // Hitung total publikasi
        $profile['metrics']['total_publications'] = count($profile['publications']);

        // 7. Lakukan analisis SDG untuk publikasi
        if (!empty($profile['publications'])) {
            $profile['sdg_analysis'] = $this->sdgService->analyzeWorks($profile['publications']);
        }

        return $profile;
    }

    /**
     * Normalisasi publikasi dari berbagai sumber ke format standar
     * 
     * @param array $publications Daftar publikasi mentah
     * @param string $source Sumber publikasi
     * @return array Publikasi ternormalisasi
     */
    private function normalizePublications(array $publications, string $source): array
    {
        $normalized = [];

        foreach ($publications as $pub) {
            $normalized[] = [
                'title' => $pub['title'] ?? $pub['dc:title'] ?? 'Unknown Title',
                'year' => $pub['publication_year'] ?? $pub['cover_date'] ?? $pub['year'] ?? null,
                'doi' => $pub['doi'] ?? null,
                'journal' => $pub['publication_name'] ?? $pub['journal'] ?? null,
                'abstract' => $pub['abstract'] ?? $pub['dc:description'] ?? null,
                'authors' => $pub['authors'] ?? [],
                'citations' => $pub['cited_by_count'] ?? $pub['citation_count'] ?? $pub['citations'] ?? 0,
                'source' => $source,
                'url' => $pub['url'] ?? $pub['scopus_url'] ?? null,
                'original_data' => $pub,
            ];
        }

        return $normalized;
    }

    /**
     * Hanya ekstrak identifier dari ORCID tanpa mengambil data lain
     * 
     * @param string $orcid ORCID peneliti
     * @return array Ringkasan identifier
     */
    public function extractIdentifiersOnly(string $orcid): array
    {
        if (!$this->orcidService->isValidOrcid($orcid)) {
            return ['success' => false, 'error' => 'Invalid ORCID format'];
        }

        $profile = $this->orcidService->getProfile($orcid);
        
        if (!$profile) {
            return ['success' => false, 'error' => 'Failed to fetch ORCID profile'];
        }

        return [
            'success' => true,
            'orcid' => $orcid,
            'identifiers' => $this->identityService->extractAllIdentifiers($profile),
            'summary' => $this->identityService->getIdentifierSummary($profile),
        ];
    }

    /**
     * Update API keys setelah object dibuat
     * 
     * @param string|null $scopusApiKey Scopus API Key
     * @param string|null $wosApiKey Web of Science API Key
     */
    public function setApiKeys(?string $scopusApiKey = null, ?string $wosApiKey = null): void
    {
        if ($scopusApiKey && $this->scopusService) {
            $this->scopusService->setApiKey($scopusApiKey);
        }
        
        if ($wosApiKey && $this->wosService) {
            $this->wosService->setApiKey($wosApiKey);
        }
    }

    /**
     * Clear semua cache untuk peneliti tertentu
     * 
     * @param string $orcid ORCID peneliti
     */
    public function clearCache(string $orcid): void
    {
        $this->orcidService->clearCache($orcid);
        
        $profile = $this->orcidService->getProfile($orcid);
        if ($profile) {
            $scopusId = $this->identityService->getScopusAuthorId($profile);
            $researcherId = $this->identityService->getResearcherId($profile);
            
            if ($scopusId && $this->scopusService) {
                $this->scopusService->clearCache($scopusId);
            }
            
            if ($researcherId && $this->wosService) {
                $this->wosService->clearCache($researcherId);
            }
        }
    }
}
