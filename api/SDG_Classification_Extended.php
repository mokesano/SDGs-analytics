<?php
/**
 * api/SDG_Classification_Extended.php
 *
 * Extends SDG_Classification_API.php with additional abstract and citation
 * sources without modifying the original file.
 *
 * Load AFTER the base file:
 *   require_once 'SDG_Classification_API.php';
 *   require_once 'SDG_Classification_Extended.php';
 *
 * New functions added
 * ───────────────────
 * Citation sources:
 *   fetchCitationsFromOpenCitations($doi)      → array of citing/cited pairs
 *   fetchCitationCountFromOpenCitations($doi)  → int
 *
 * Abstract sources:
 *   fetchAbstractFromUnpaywall($doi [, $email])
 *   fetchAbstractFromEuropePMC($doi)
 *   fetchAbstractFromPubMed($doi)
 *   fetchAbstractFromDataCite($doi)
 *   fetchAbstractFromCORE($doi [, $apiKey])
 *
 * Orchestrator (all sources, priority order):
 *   fetchAbstractAllSources($doi [, $coreApiKey])
 *
 * CORE requires a free API key from https://core.ac.uk/services/api
 * Set it via the constant CORE_API_KEY or pass as the second argument.
 */

defined('PROJECT_ROOT') || define('PROJECT_ROOT', dirname(__DIR__));

if (!defined('_SDG_EXT_UA'))      define('_SDG_EXT_UA',      'SDG-Classifier/5.2 (mailto:wizdam@sangia.org)');
if (!defined('_SDG_EXT_TIMEOUT')) define('_SDG_EXT_TIMEOUT', 10);
if (!defined('_SDG_EXT_CONNECT')) define('_SDG_EXT_CONNECT', 4);

// ─── Internal cURL helper ────────────────────────────────────────────────────

if (!function_exists('_sdgExtCurl')) {
    /**
     * Performs a GET request and returns [body, http_code, curl_errno].
     * Extra curl options in $extra override the defaults.
     */
    function _sdgExtCurl(string $url, array $extra = []): array
    {
        $defaults = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => _SDG_EXT_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => _SDG_EXT_CONNECT,
            CURLOPT_USERAGENT      => _SDG_EXT_UA,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ];
        $ch = curl_init($url);
        // $extra takes precedence over defaults
        curl_setopt_array($ch, $extra + $defaults);
        $body     = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno    = curl_errno($ch);
        curl_close($ch);
        return [$body, $httpCode, $errno];
    }
}

if (!function_exists('_sdgExtCleanDoi')) {
    /** Strips doi.org URL prefix, returns bare DOI string. */
    function _sdgExtCleanDoi(string $doi): string
    {
        return preg_replace('/^https?:\/\/(dx\.)?doi\.org\//i', '', trim($doi));
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// CITATION SOURCES
// ═══════════════════════════════════════════════════════════════════════════

// ─── OpenCitations (COCI) ───────────────────────────────────────────────────

if (!function_exists('fetchCitationsFromOpenCitations')) {
    /**
     * Returns all incoming citations for a DOI from OpenCitations COCI.
     *
     * Each element:
     *   ['citing_doi' => string, 'cited_doi' => string,
     *    'created' => string, 'timespan' => string]
     *
     * Returns [] on failure or when no citations are found.
     */
    function fetchCitationsFromOpenCitations(string $doi): array
    {
        $clean = _sdgExtCleanDoi($doi);
        [$body, $code, $err] = _sdgExtCurl(
            'https://opencitations.net/index/coci/api/v1/citations/' . rawurlencode($clean)
        );
        if ($err || $code !== 200 || !$body) return [];

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) return [];

        return array_map(static fn($c) => [
            'citing_doi' => $c['citing']   ?? '',
            'cited_doi'  => $c['cited']    ?? '',
            'created'    => $c['creation'] ?? '',
            'timespan'   => $c['timespan'] ?? '',
        ], $data);
    }
}

if (!function_exists('fetchCitationCountFromOpenCitations')) {
    /**
     * Returns the number of times a DOI has been cited according to
     * OpenCitations COCI. Returns 0 on failure.
     */
    function fetchCitationCountFromOpenCitations(string $doi): int
    {
        $clean = _sdgExtCleanDoi($doi);
        [$body, $code, $err] = _sdgExtCurl(
            'https://opencitations.net/index/coci/api/v1/citation-count/' . rawurlencode($clean)
        );
        if ($err || $code !== 200 || !$body) return 0;

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data[0]['count'])) return 0;

        return (int) $data[0]['count'];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ABSTRACT SOURCES
// ═══════════════════════════════════════════════════════════════════════════

// ─── 1. Unpaywall ───────────────────────────────────────────────────────────

if (!function_exists('fetchAbstractFromUnpaywall')) {
    /**
     * Locates the best open-access landing page via Unpaywall, then extracts
     * the abstract from common HTML <meta> tags on that page.
     *
     * Unpaywall's own JSON response does not contain an abstract field, so a
     * second HTTP request is made to the OA landing page (HTML).
     * A polite email address is required by Unpaywall's Terms of Service.
     */
    function fetchAbstractFromUnpaywall(string $doi, string $email = 'wizdam@sangia.org'): string
    {
        $clean = _sdgExtCleanDoi($doi);

        // Step 1 — resolve DOI to OA landing-page URL via Unpaywall
        [$body, $code, $err] = _sdgExtCurl(
            'https://api.unpaywall.org/v2/' . rawurlencode($clean)
            . '?email=' . rawurlencode($email)
        );
        if ($err || $code !== 200 || !$body) return '';

        $meta = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) return '';

        $landingUrl = $meta['best_oa_location']['url_for_landing_page']
            ?? $meta['best_oa_location']['url']
            ?? null;
        if (empty($landingUrl)) return '';

        // Step 2 — fetch the HTML landing page
        $ch = curl_init($landingUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => _SDG_EXT_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => _SDG_EXT_CONNECT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_USERAGENT      => _SDG_EXT_UA,
        ]);
        $html = curl_exec($ch);
        curl_close($ch);
        if (!$html) return '';

        // Step 3 — extract abstract from standard HTML meta patterns
        // Ordered from most specific (citation_abstract) to most generic
        foreach ([
            '/<meta\s[^>]*name=["\']citation_abstract["\']\s[^>]*content=["\'](.*?)["\']/is',
            '/<meta\s[^>]*content=["\'](.*?)["\']\s[^>]*name=["\']citation_abstract["\']/is',
            '/<meta\s[^>]*name=["\']DC\.Description["\']\s[^>]*content=["\'](.*?)["\']/is',
            '/<meta\s[^>]*property=["\']og:description["\']\s[^>]*content=["\'](.*?)["\']/is',
            '/<meta\s[^>]*name=["\']description["\']\s[^>]*content=["\'](.*?)["\']/is',
        ] as $pattern) {
            if (preg_match($pattern, $html, $m)) {
                $candidate = html_entity_decode(strip_tags(trim($m[1])), ENT_QUOTES, 'UTF-8');
                // Ignore generic site-level descriptions (< 80 chars)
                if (mb_strlen($candidate) >= 80) return $candidate;
            }
        }

        return '';
    }
}

// ─── 2. Europe PMC ──────────────────────────────────────────────────────────

if (!function_exists('fetchAbstractFromEuropePMC')) {
    /**
     * Fetches the abstract from Europe PMC (EBI).
     * Excellent coverage for biomedical, life-science, and health research.
     * No API key required.
     */
    function fetchAbstractFromEuropePMC(string $doi): string
    {
        $clean = _sdgExtCleanDoi($doi);
        $url   = 'https://www.ebi.ac.uk/europepmc/webservices/rest/search?'
               . http_build_query([
                   'query'      => 'DOI:"' . $clean . '"',
                   'format'     => 'json',
                   'resultType' => 'core',
                   'pageSize'   => 1,
               ]);

        [$body, $code, $err] = _sdgExtCurl($url);
        if ($err || $code !== 200 || !$body) return '';

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) return '';

        return trim($data['resultList']['result'][0]['abstractText'] ?? '');
    }
}

// ─── 3. PubMed / NCBI ───────────────────────────────────────────────────────

if (!function_exists('fetchAbstractFromPubMed')) {
    /**
     * Fetches the abstract from PubMed via NCBI E-utilities (two requests):
     *   esearch  — resolves the DOI to a PMID
     *   efetch   — retrieves the full XML record and extracts <AbstractText>
     *
     * Abstract sections (e.g. BACKGROUND, METHODS) are concatenated.
     * No API key required; tool/email identifies the caller per NCBI ToS.
     */
    function fetchAbstractFromPubMed(string $doi): string
    {
        $clean = _sdgExtCleanDoi($doi);
        $base  = ['tool' => 'SDG-Classifier', 'email' => 'wizdam@sangia.org'];

        // Step 1 — resolve DOI to PMID via esearch
        [$body, $code, $err] = _sdgExtCurl(
            'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?'
            . http_build_query($base + [
                'db'      => 'pubmed',
                'term'    => $clean . '[doi]',
                'retmode' => 'json',
                'retmax'  => 1,
            ])
        );
        if ($err || $code !== 200 || !$body) return '';

        $search = json_decode($body, true);
        $pmid   = $search['esearchresult']['idlist'][0] ?? null;
        if (empty($pmid)) return '';

        // Step 2 — fetch XML record and extract <AbstractText> sections
        [$body, $code, $err] = _sdgExtCurl(
            'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?'
            . http_build_query($base + [
                'db'      => 'pubmed',
                'id'      => $pmid,
                'rettype' => 'abstract',
                'retmode' => 'xml',
            ]),
            [CURLOPT_HTTPHEADER => ['Accept: application/xml, text/xml']]
        );
        if ($err || $code !== 200 || !$body) return '';

        preg_match_all('/<AbstractText[^>]*>(.*?)<\/AbstractText>/s', $body, $matches);
        if (empty($matches[1])) return '';

        return trim(implode(' ', array_map('strip_tags', $matches[1])));
    }
}

// ─── 4. DataCite ────────────────────────────────────────────────────────────

if (!function_exists('fetchAbstractFromDataCite')) {
    /**
     * Fetches the abstract from DataCite REST API.
     * Covers datasets, software, conference papers, and other research outputs
     * registered with DataCite DOIs.
     * No API key required.
     */
    function fetchAbstractFromDataCite(string $doi): string
    {
        $clean = _sdgExtCleanDoi($doi);
        [$body, $code, $err] = _sdgExtCurl(
            'https://api.datacite.org/dois/' . rawurlencode($clean)
        );
        if ($err || $code !== 200 || !$body) return '';

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) return '';

        $descriptions = $data['data']['attributes']['descriptions'] ?? [];

        // Prefer descriptions with descriptionType == "Abstract"
        foreach ($descriptions as $desc) {
            if (
                !empty($desc['description']) &&
                strcasecmp($desc['descriptionType'] ?? '', 'Abstract') === 0
            ) {
                return trim(strip_tags($desc['description']));
            }
        }

        // Fallback: return first available description regardless of type
        return trim(strip_tags($descriptions[0]['description'] ?? ''));
    }
}

// ─── 5. CORE ────────────────────────────────────────────────────────────────

if (!function_exists('fetchAbstractFromCORE')) {
    /**
     * Fetches the abstract from CORE (aggregator of open-access repositories
     * worldwide — 200 M+ research outputs).
     *
     * Requires a free API key from https://core.ac.uk/services/api
     * Pass it as $apiKey or define the constant CORE_API_KEY before loading
     * this file.
     *
     * Returns '' if no key is configured.
     */
    function fetchAbstractFromCORE(string $doi, string $apiKey = ''): string
    {
        if (empty($apiKey)) {
            $apiKey = defined('CORE_API_KEY') ? CORE_API_KEY : '';
        }
        if (empty($apiKey)) return '';

        $clean = _sdgExtCleanDoi($doi);
        [$body, $code, $err] = _sdgExtCurl(
            'https://api.core.ac.uk/v3/works/doi:' . rawurlencode($clean),
            [CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $apiKey,
            ]]
        );
        if ($err || $code !== 200 || !$body) return '';

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) return '';

        return trim($data['abstract'] ?? '');
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ORCHESTRATOR — all sources in priority order
// ═══════════════════════════════════════════════════════════════════════════

if (!function_exists('fetchAbstractAllSources')) {
    /**
     * Queries every abstract source in priority order and returns the first
     * non-empty result.
     *
     * Priority:
     *   1. CrossRef + OpenAlex + Semantic Scholar  (base: fetchAbstractMultiSource)
     *   2. Europe PMC
     *   3. PubMed / NCBI
     *   4. DataCite
     *   5. Unpaywall OA landing page  (2 HTTP requests — deliberately late)
     *   6. CORE                       (skipped when no API key is configured)
     *
     * @param string $doi         Bare DOI or doi.org URL
     * @param string $coreApiKey  Optional CORE API key (overrides CORE_API_KEY constant)
     */
    function fetchAbstractAllSources(string $doi, string $coreApiKey = ''): string
    {
        if (empty(trim($doi))) return '';

        // 1. Base sources (CrossRef → OpenAlex → Semantic Scholar)
        if (function_exists('fetchAbstractMultiSource')) {
            $abstract = fetchAbstractMultiSource($doi);
            if (!empty($abstract)) return $abstract;
        }

        // 2. Europe PMC
        $abstract = fetchAbstractFromEuropePMC($doi);
        if (!empty($abstract)) return $abstract;

        // 3. PubMed / NCBI
        $abstract = fetchAbstractFromPubMed($doi);
        if (!empty($abstract)) return $abstract;

        // 4. DataCite
        $abstract = fetchAbstractFromDataCite($doi);
        if (!empty($abstract)) return $abstract;

        // 5. Unpaywall (2 HTTP requests: API + HTML landing page)
        $abstract = fetchAbstractFromUnpaywall($doi);
        if (!empty($abstract)) return $abstract;

        // 6. CORE (optional — silently skipped when no key provided)
        if (!empty($coreApiKey) || defined('CORE_API_KEY')) {
            $abstract = fetchAbstractFromCORE($doi, $coreApiKey);
            if (!empty($abstract)) return $abstract;
        }

        return '';
    }
}
