<?php
/**
 * API Data Peneliti dengan ORCID - ORCID_Profile_API.php (High-Performance Version)
 * 
 * API untuk menampilkan karya ilmiah peneliti berdasarkan Data ORCID.
 * @author Rochmady
 * @version 2.1.0 (PHP 5.4+ Compatible)
 * @license none
 * last update 2025-05-29
 */

// -----------------------------------------------------------------
// BAGIAN #1: PEMERIKSAAN MONITORING (UP/DOWN)
// -----------------------------------------------------------------
// Cek apakah TIDAK ADA parameter GET yang dikirim.
if (empty($_GET)) {
    
    // Set header HTTP 200 OK (ini sudah default, tapi baik untuk eksplisit)
    http_response_code(200); 
    
    // Set tipe konten
    header('Content-Type: application/json');
    
    // Kirim balasan status "UP"
    echo json_encode([
        'status' => 'up',
        'message' => 'Endpoint is operational'
    ]);
    
    // Selesai. Hentikan eksekusi skrip agar tidak lanjut ke logika API.
    exit;
}

// Mengatur header response sebagai JSON dengan encoding UTF-8
header("Content-Type: application/json; charset=utf-8");

// ==============================================
// KONFIGURASI CACHE & PERFORMANCE
// ==============================================

// Menentukan direktori cache di dalam folder aplikasi
define('CACHE_DIR', __DIR__ . '/cache');
// Durasi cache 24 jam dalam detik (86400 detik = 24 jam)
define('CACHE_TTL', 86400);
// Cache abstrak lebih lama (7 hari) karena jarang berubah
define('ABSTRACT_CACHE_TTL', 604800);
// Prefix untuk nama file cache agar mudah diidentifikasi
define('CACHE_PREFIX', 'orcid_');
// Timeout yang lebih pendek untuk performance
define('API_TIMEOUT', 5);

// Membuat direktori cache jika belum ada
if (!file_exists(CACHE_DIR)) {
    // Membuat direktori dengan permission 755 (read/write/execute untuk owner, read/execute untuk group dan other)
    mkdir(CACHE_DIR, 0755, true);
    // Menambahkan file .htaccess untuk mencegah akses langsung ke direktori cache dari web
    file_put_contents(CACHE_DIR . '/.htaccess', "Deny from all");
}

// ==============================================
// FUNGSI CACHE - OPTIMIZED & PHP 5.4+ COMPATIBLE
// ==============================================

/**
 * Membuat key unik untuk cache berdasarkan ORCID ID
 * @param string $orcid - ID ORCID peneliti
 * @return string - Key cache yang unik
 */
function getCacheKey($orcid) {
    // Menggunakan kombinasi prefix, hash MD5 (8 karakter pertama), dan ORCID ID
    // untuk membuat key yang unik dan tidak mudah ditebak
    return CACHE_PREFIX . substr(md5($orcid), 0, 8) . '_' . $orcid;
}

/**
 * Menyimpan data ke cache dengan kompresi gzip (optimized)
 * @param string $key - Key cache
 * @param array $data - Data yang akan disimpan
 * @param int $ttl - Time to live (default menggunakan CACHE_TTL)
 */
function saveToCache($key, $data, $ttl = CACHE_TTL) {
    $cache_file = CACHE_DIR . '/' . $key . '.json.gz';
    
    try {
        // Membuat struktur data cache dengan metadata
        $cache_data = array(
            'data' => $data,              // Data utama
            'timestamp' => time(),        // Waktu pembuatan cache
            'expires' => time() + $ttl    // Waktu kadaluwarsa cache
        );
        
        // Menyimpan data dalam format JSON yang dikompres dengan gzip untuk menghemat storage
        // Menggunakan level kompresi 6 untuk balance antara speed dan size
        $json_data = json_encode($cache_data);
        if ($json_data !== false) {
            file_put_contents($cache_file, gzencode($json_data, 6));
        }
    } catch (Exception $e) {
        // Silent error - cache failure tidak boleh mengganggu response utama
        error_log("Cache save error: " . $e->getMessage());
    }
}

/**
 * Memuat data dari cache jika masih valid dengan smart detection (optimized)
 * @param string $key - Key cache
 * @param string $orcid - ORCID ID untuk smart detection
 * @return mixed - Data cache jika valid, false jika tidak ada/expired/perlu update
 */
function loadFromCache($key, $orcid = null) {
    $cache_file = CACHE_DIR . '/' . $key . '.json.gz';
    
    try {
        // Cek apakah file cache ada dan readable
        if (!is_readable($cache_file)) {
            return false;
        }
        
        $content = file_get_contents($cache_file);
        if ($content === false) {
            return false;
        }
        
        // Dekompres file gzip
        $decoded = gzdecode($content);
        if ($decoded === false) {
            return false;
        }
        
        // Parse JSON menjadi array PHP
        $data = json_decode($decoded, true);
        if (!$data) {
            return false;
        }
        
        // Cek apakah data masih valid (belum expired)
        $current_time = time();
        if ($current_time >= $data['expires']) {
            // Cache expired, hapus file lama secara async
            @unlink($cache_file);
            return false;
        }
        
        // Smart detection: cek apakah perlu update berdasarkan aktivitas terbaru
        // Hanya jalankan jika cache sudah berumur lebih dari 6 jam
        if ($orcid && ($current_time - $data['timestamp']) > 21600) {
            if (smartDetectionUpdate($data['data'], $orcid)) {
                return false; // Force refresh karena ada indikasi update
            }
        }
        
        return $data['data'];  // Return data utama saja
    } catch (Exception $e) {
        error_log("Cache load error: " . $e->getMessage());
        return false;
    }
}

/**
 * Smart detection untuk menentukan apakah data perlu di-update (optimized)
 * @param array $cached_data - Data yang tersimpan di cache
 * @param string $orcid - ORCID ID peneliti
 * @return bool - True jika perlu update, false jika tidak
 */
function smartDetectionUpdate($cached_data, $orcid) {
    try {
        // Ambil ringkasan aktivitas terbaru dari ORCID
        $url = "https://pub.orcid.org/v3.0/{$orcid}/activities/summary";
        
        // Menggunakan cURL dengan optimisasi
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3, // Timeout sangat pendek untuk quick check
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_USERAGENT => 'ORCID-Smart-Detector/2.0',
            CURLOPT_HTTPHEADER => array('Accept: application/json'),
            CURLOPT_FOLLOWLOCATION => false, // Tidak follow redirect untuk speed
            CURLOPT_SSL_VERIFYPEER => false // Skip SSL verification untuk speed (hanya untuk development)
        ));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code != 200 || !$response) {
            return false; // Jika error, gunakan cache yang ada
        }

        $current_summary = json_decode($response, true);
        if (!$current_summary) {
            return false;
        }
        
        // Bandingkan jumlah works, employments, educations, dan fundings
        $cached_counts = array(
            'works' => isset($cached_data['activities']['works']) ? count($cached_data['activities']['works']) : 0,
            'employments' => isset($cached_data['activities']['employments']) ? count($cached_data['activities']['employments']) : 0,
            'educations' => isset($cached_data['activities']['educations']) ? count($cached_data['activities']['educations']) : 0,
            'fundings' => isset($cached_data['activities']['fundings']) ? count($cached_data['activities']['fundings']) : 0
        );
        
        $current_counts = array(
            'works' => isset($current_summary['works']['group']) ? count($current_summary['works']['group']) : 0,
            'employments' => isset($current_summary['employments']['affiliation-group']) ? count($current_summary['employments']['affiliation-group']) : 0,
            'educations' => isset($current_summary['educations']['affiliation-group']) ? count($current_summary['educations']['affiliation-group']) : 0,
            'fundings' => isset($current_summary['fundings']['group']) ? count($current_summary['fundings']['group']) : 0
        );
        
        // Jika ada perbedaan jumlah, berarti ada update
        return $cached_counts !== $current_counts;
        
    } catch (Exception $e) {
        // Jika terjadi error, gunakan cache yang ada
        error_log("Smart detection error: " . $e->getMessage());
        return false;
    }
}

/**
 * Membersihkan cache yang sudah expired secara otomatis (optimized)
 */
function cleanExpiredCache() {
    try {
        // Jalankan cleanup maksimal 10 file per call untuk menghindari blocking
        $cache_files = glob(CACHE_DIR . '/' . CACHE_PREFIX . '*.json.gz');
        if (!$cache_files) return;
        
        $current_time = time();
        $cleaned = 0;
        
        foreach ($cache_files as $file) {
            if ($cleaned >= 10) break; // Limit cleanup per execution
            
            $stat = @stat($file);
            if ($stat && ($current_time - $stat['mtime']) > CACHE_TTL) {
                @unlink($file);
                $cleaned++;
            }
        }
    } catch (Exception $e) {
        error_log("Cache cleanup error: " . $e->getMessage());
    }
}

// ==============================================
// FUNGSI EKSTRAKSI DATA - OPTIMIZED & PHP 5.4+ COMPATIBLE
// ==============================================

/**
 * Mengekstrak informasi dasar peneliti dari data ORCID (optimized)
 * @param array $person - Data person dari ORCID API
 * @return array - Informasi dasar yang sudah diformat
 */
function extractBasicInfo($person) {
    // Inisialisasi struktur data dasar dengan pengecekan yang kompatibel PHP 5.4+
    $info = array(
        'name' => array(
            'given' => isset($person['name']['given-names']['value']) ? $person['name']['given-names']['value'] : null,
            'family' => isset($person['name']['family-name']['value']) ? $person['name']['family-name']['value'] : null,
            'credit' => isset($person['name']['credit-name']['value']) ? $person['name']['credit-name']['value'] : null
        ),
        'biography' => isset($person['biography']['content']) ? $person['biography']['content'] : null,
        'emails' => array(),
        'external_ids' => array(),
        'urls' => array()
    );

    // Ekstrak email addresses jika ada (optimized loop)
    if (!empty($person['emails']['email'])) {
        foreach ($person['emails']['email'] as $email) {
            if (!empty($email['email'])) {
                $info['emails'][] = $email['email'];
            }
        }
    }

    // Ekstrak external identifiers (optimized loop)
    if (!empty($person['external-identifiers']['external-identifier'])) {
        foreach ($person['external-identifiers']['external-identifier'] as $ext_id) {
            $info['external_ids'][] = array(
                'type' => isset($ext_id['external-id-type']) ? $ext_id['external-id-type'] : null,
                'value' => isset($ext_id['external-id-value']) ? $ext_id['external-id-value'] : null,
                'url' => isset($ext_id['external-id-url']) ? $ext_id['external-id-url'] : null
            );
        }
    }

    // Ekstrak researcher URLs (optimized loop)
    if (!empty($person['researcher-urls']['researcher-url'])) {
        foreach ($person['researcher-urls']['researcher-url'] as $url) {
            $info['urls'][] = array(
                'name' => isset($url['url-name']) ? $url['url-name'] : null,
                'url' => isset($url['url']['value']) ? $url['url']['value'] : null
            );
        }
    }

    return $info;
}

/**
 * Mengekstrak aktivitas peneliti dari data ORCID dengan detail afiliasi (optimized)
 * @param array $activities_summary - Data activities-summary dari ORCID API
 * @return array - Data aktivitas yang sudah diformat
 */
function extractActivities($activities_summary) {
    // Inisialisasi struktur data aktivitas
    $result = array(
        'employments' => array(),
        'educations' => array(),
        'works' => array(),
        'fundings' => array(),
        'current_affiliations' => array()
    );

    // Ekstrak riwayat pekerjaan dan afiliasi saat ini (optimized)
    if (!empty($activities_summary['employments']['affiliation-group'])) {
        foreach ($activities_summary['employments']['affiliation-group'] as $group) {
            $emp = $group['summaries'][0]['employment-summary'];
            
            $employment_data = array(
                'organization' => isset($emp['organization']['name']) ? $emp['organization']['name'] : null,
                'department' => isset($emp['department-name']) ? $emp['department-name'] : null,
                'role' => isset($emp['role-title']) ? $emp['role-title'] : null,
                'start_date' => formatDateFromORCID(isset($emp['start-date']) ? $emp['start-date'] : null),
                'end_date' => formatDateFromORCID(isset($emp['end-date']) ? $emp['end-date'] : null),
                'organization_address' => extractAddress(isset($emp['organization']['address']) ? $emp['organization']['address'] : null)
            );
            
            $result['employments'][] = $employment_data;
            
            // Jika tidak ada end_date, berarti masih aktif
            if (!$employment_data['end_date']) {
                $result['current_affiliations'][] = array(
                    'type' => 'employment',
                    'organization' => $employment_data['organization'],
                    'department' => $employment_data['department'],
                    'role' => $employment_data['role'],
                    'address' => $employment_data['organization_address'],
                    'start_date' => $employment_data['start_date']
                );
            }
        }
    }

    // Ekstrak riwayat pendidikan (optimized)
    if (!empty($activities_summary['educations']['affiliation-group'])) {
        foreach ($activities_summary['educations']['affiliation-group'] as $group) {
            $edu = $group['summaries'][0]['education-summary'];
            
            $education_data = array(
                'organization' => isset($edu['organization']['name']) ? $edu['organization']['name'] : null,
                'department' => isset($edu['department-name']) ? $edu['department-name'] : null,
                'degree' => isset($edu['role-title']) ? $edu['role-title'] : null,
                'start_date' => formatDateFromORCID(isset($edu['start-date']) ? $edu['start-date'] : null),
                'end_date' => formatDateFromORCID(isset($edu['end-date']) ? $edu['end-date'] : null),
                'organization_address' => extractAddress(isset($edu['organization']['address']) ? $edu['organization']['address'] : null)
            );
            
            $result['educations'][] = $education_data;
            
            // Jika sedang menempuh pendidikan
            if (!$education_data['end_date']) {
                $result['current_affiliations'][] = array(
                    'type' => 'education',
                    'organization' => $education_data['organization'],
                    'department' => $education_data['department'],
                    'degree' => $education_data['degree'],
                    'address' => $education_data['organization_address'],
                    'start_date' => $education_data['start_date']
                );
            }
        }
    }

    // Ekstrak karya ilmiah/publikasi dengan abstrak (sequential processing)
    if (!empty($activities_summary['works']['group'])) {
        foreach ($activities_summary['works']['group'] as $group) {
            $work = $group['work-summary'][0];
            $doi = extractDOI(isset($work['external-ids']['external-id']) ? $work['external-ids']['external-id'] : array());
            
            $work_data = array(
                'title' => isset($work['title']['title']['value']) ? $work['title']['title']['value'] : null,
                'type' => isset($work['type']) ? $work['type'] : null,
                'publication_date' => isset($work['publication-date']['year']['value']) ? $work['publication-date']['year']['value'] : null,
                'doi' => $doi,
                'url' => isset($work['url']['value']) ? $work['url']['value'] : null,
                'journal' => isset($work['journal-title']['value']) ? $work['journal-title']['value'] : null,
                'abstract_info' => null
            );
            
            // Ambil abstrak jika ada DOI (sequential, tidak batch)
            if ($doi) {
                $work_data['abstract_info'] = getAbstractWithCache($doi);
            }
            
            $result['works'][] = $work_data;
        }
    }

    // Ekstrak pendanaan penelitian (optimized)
    if (!empty($activities_summary['fundings']['group'])) {
        foreach ($activities_summary['fundings']['group'] as $group) {
            $fund = $group['funding-summary'][0];
            $result['fundings'][] = array(
                'title' => isset($fund['title']['title']['value']) ? $fund['title']['title']['value'] : null,
                'agency' => isset($fund['organization']['name']) ? $fund['organization']['name'] : null,
                'grant_number' => extractGrantNumber(isset($fund['external-ids']['external-id']) ? $fund['external-ids']['external-id'] : array()),
                'start_date' => formatDateFromORCID(isset($fund['start-date']) ? $fund['start-date'] : null),
                'end_date' => formatDateFromORCID(isset($fund['end-date']) ? $fund['end-date'] : null),
                'agency_address' => extractAddress(isset($fund['organization']['address']) ? $fund['organization']['address'] : null)
            );
        }
    }

    return $result;
}

// ==============================================
// HELPER FUNCTIONS - OPTIMIZED & PHP 5.4+ COMPATIBLE
// ==============================================

/**
 * Format tanggal dari array ORCID ke string yang proper (fixed)
 * @param array|null $date_array - Array tanggal dari ORCID
 * @return string|null - String tanggal yang diformat (YYYY-MM-DD atau YYYY-MM atau YYYY)
 */
function formatDateFromORCID($date_array) {
    if (!is_array($date_array) || empty($date_array)) {
        return null;
    }
    
    // Filter hanya nilai yang valid (tidak null dan tidak kosong)
    $date_parts = array();
    
    if (isset($date_array['year']['value']) && !empty($date_array['year']['value'])) {
        $date_parts[] = str_pad($date_array['year']['value'], 4, '0', STR_PAD_LEFT);
    }
    
    if (isset($date_array['month']['value']) && !empty($date_array['month']['value'])) {
        $date_parts[] = str_pad($date_array['month']['value'], 2, '0', STR_PAD_LEFT);
    }
    
    if (isset($date_array['day']['value']) && !empty($date_array['day']['value'])) {
        $date_parts[] = str_pad($date_array['day']['value'], 2, '0', STR_PAD_LEFT);
    }
    
    return !empty($date_parts) ? implode('-', $date_parts) : null;
}

/**
 * Ekstrak alamat dari data ORCID (optimized & PHP 5.4+ compatible)
 * @param array|null $address - Data alamat dari ORCID
 * @return array - Array alamat yang diformat
 */
function extractAddress($address) {
    if (!is_array($address)) {
        return array('city' => null, 'region' => null, 'country' => null);
    }
    
    return array(
        'city' => isset($address['city']) ? $address['city'] : null,
        'region' => isset($address['region']) ? $address['region'] : null,
        'country' => isset($address['country']) ? $address['country'] : null
    );
}

/**
 * Ekstrak DOI dari external identifiers (optimized)
 * @param array $external_ids - Array external identifiers
 * @return string|null - DOI yang ditemukan
 */
function extractDOI($external_ids) {
    if (!is_array($external_ids)) {
        return null;
    }
    
    foreach ($external_ids as $ext_id) {
        if (isset($ext_id['external-id-type']) && 
            strtolower($ext_id['external-id-type']) === 'doi' &&
            !empty($ext_id['external-id-value'])) {
            return $ext_id['external-id-value'];
        }
    }
    
    return null;
}

/**
 * Ekstrak grant number dari external identifiers (optimized & PHP 5.4+ compatible)
 * @param array $external_ids - Array external identifiers
 * @return string|null - Grant number yang ditemukan
 */
function extractGrantNumber($external_ids) {
    if (!is_array($external_ids) || empty($external_ids)) {
        return null;
    }
    
    return isset($external_ids[0]['external-id-value']) ? $external_ids[0]['external-id-value'] : null;
}

// ==============================================
// ABSTRACT FETCHING - HIGH PERFORMANCE & PHP 5.4+ COMPATIBLE
// ==============================================

/**
 * Mengambil abstrak dengan caching yang sangat efisien
 * @param string $doi - DOI artikel
 * @return array|null - Data abstrak dengan cache
 */
function getAbstractWithCache($doi) {
    if (!$doi) return null;
    
    $cache_key = 'abstract_' . md5($doi);
    $cache_file = CACHE_DIR . '/' . $cache_key . '.json.gz';
    
    try {
        // Cek cache abstrak dengan file existence check yang cepat
        if (is_readable($cache_file)) {
            $stat = stat($cache_file);
            if ($stat && (time() - $stat['mtime']) < ABSTRACT_CACHE_TTL) {
                $content = file_get_contents($cache_file);
                if ($content !== false) {
                    $decoded = gzdecode($content);
                    if ($decoded !== false) {
                        $data = json_decode($decoded, true);
                        if ($data && $data['data']) {
                            return $data['data'];
                        }
                    }
                }
            } else {
                @unlink($cache_file); // Hapus cache expired
            }
        }
        
        // Ambil abstrak dari API (sequential fallback)
        $abstract_data = fetchAbstractSequential($doi);
        
        if ($abstract_data && !empty($abstract_data['abstract'])) {
            // Cache abstrak
            $cache_data = array(
                'data' => $abstract_data,
                'timestamp' => time(),
                'expires' => time() + ABSTRACT_CACHE_TTL
            );
            $json_cache = json_encode($cache_data);
            if ($json_cache !== false) {
                @file_put_contents($cache_file, gzencode($json_cache, 6));
            }
        }
        
        return $abstract_data;
    } catch (Exception $e) {
        error_log("Abstract cache error: " . $e->getMessage());
        return null;
    }
}

/**
 * Mengambil abstrak dengan fallback sequential ke multiple sources
 * @param string $doi - DOI artikel
 * @return array|null - Data abstrak
 */
function fetchAbstractSequential($doi) {
    if (!$doi) return null;
    
    // Bersihkan DOI
    $clean_doi = preg_replace('/^(https?:\/\/)?(dx\.)?doi\.org\//', '', $doi);
    
    $abstract_data = array(
        'abstract' => null,
        'source' => null,
        'metadata' => array()
    );
    
    // 1. Coba CrossRef API (paling comprehensive dan reliable)
    $crossref_data = fetchFromCrossRefOptimized($clean_doi);
    if ($crossref_data && !empty($crossref_data['abstract'])) {
        return $crossref_data;
    }
    
    // 2. Coba OpenAlex API (open access database)
    $openalex_data = fetchFromOpenAlexOptimized($clean_doi);
    if ($openalex_data && !empty($openalex_data['abstract'])) {
        return $openalex_data;
    }
    
    // 3. Coba Semantic Scholar API (khusus untuk CS dan biomedical)
    $s2_data = fetchFromSemanticScholarOptimized($clean_doi);
    if ($s2_data && !empty($s2_data['abstract'])) {
        return $s2_data;
    }
    
    // 4. Coba CORE API (repository papers)
    $core_data = fetchFromCOREOptimized($clean_doi);
    if ($core_data && !empty($core_data['abstract'])) {
        return $core_data;
    }
    
    return $abstract_data; // Return struktur kosong jika tidak ada yang berhasil
}

/**
 * Fetch dari CrossRef dengan optimisasi maksimal
 * @param string $doi - DOI artikel
 * @return array|null - Data abstrak atau null
 */
function fetchFromCrossRefOptimized($doi) {
    try {
        $url = "https://api.crossref.org/works/" . urlencode($doi);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => API_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_USERAGENT => 'ORCID-Abstract-Fetcher/2.0 (mailto:researcher@example.com)',
            CURLOPT_HTTPHEADER => array('Accept: application/json'),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false
        ));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['message']['abstract']) && !empty($data['message']['abstract'])) {
                return array(
                    'abstract' => strip_tags($data['message']['abstract']),
                    'source' => 'CrossRef',
                    'metadata' => array(
                        'publisher' => isset($data['message']['publisher']) ? $data['message']['publisher'] : null,
                        'journal' => isset($data['message']['container-title'][0]) ? $data['message']['container-title'][0] : null,
                        'published_date' => isset($data['message']['published-print']['date-parts'][0]) ? 
                                          implode('-', $data['message']['published-print']['date-parts'][0]) : null
                    )
                );
            }
        }
    } catch (Exception $e) {
        error_log("CrossRef fetch error: " . $e->getMessage());
    }
    
    return null;
}

/**
 * Fetch dari OpenAlex dengan optimisasi maksimal
 * @param string $doi - DOI artikel
 * @return array|null - Data abstrak atau null
 */
function fetchFromOpenAlexOptimized($doi) {
    try {
        $url = "https://api.openalex.org/works/doi:" . urlencode($doi);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => API_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_USERAGENT => 'ORCID-Abstract-Fetcher/2.0',
            CURLOPT_HTTPHEADER => array('Accept: application/json'),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false
        ));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['abstract_inverted_index']) && !empty($data['abstract_inverted_index'])) {
                $abstract = reconstructAbstractFromInvertedIndex($data['abstract_inverted_index']);
                if ($abstract) {
                    return array(
                        'abstract' => $abstract,
                        'source' => 'OpenAlex',
                        'metadata' => array(
                            'open_access' => isset($data['open_access']['is_oa']) ? $data['open_access']['is_oa'] : false,
                            'journal' => isset($data['primary_location']['source']['display_name']) ? $data['primary_location']['source']['display_name'] : null,
                            'published_date' => isset($data['publication_date']) ? $data['publication_date'] : null
                        )
                    );
                }
            }
        }
    } catch (Exception $e) {
        error_log("OpenAlex fetch error: " . $e->getMessage());
    }
    
    return null;
}

/**
 * Fetch dari Semantic Scholar dengan optimisasi
 * @param string $doi - DOI artikel
 * @return array|null - Data abstrak atau null
 */
function fetchFromSemanticScholarOptimized($doi) {
    try {
        $url = "https://api.semanticscholar.org/graph/v1/paper/DOI:" . urlencode($doi) . "?fields=abstract,journal,year";
        
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => API_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_USERAGENT => 'ORCID-Abstract-Fetcher/2.0',
            CURLOPT_HTTPHEADER => array('Accept: application/json'),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false
        ));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['abstract']) && !empty($data['abstract'])) {
                return array(
                    'abstract' => $data['abstract'],
                    'source' => 'Semantic Scholar',
                    'metadata' => array(
                        'journal' => isset($data['journal']['name']) ? $data['journal']['name'] : null,
                        'published_year' => isset($data['year']) ? $data['year'] : null
                    )
                );
            }
        }
    } catch (Exception $e) {
        error_log("Semantic Scholar fetch error: " . $e->getMessage());
    }
    
    return null;
}

/**
 * Fetch dari CORE dengan optimisasi
 * @param string $doi - DOI artikel
 * @return array|null - Data abstrak atau null
 */
function fetchFromCOREOptimized($doi) {
    try {
        $url = "https://api.core.ac.uk/v3/search/works?q=doi:" . urlencode($doi) . "&limit=1";
        
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => API_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_USERAGENT => 'ORCID-Abstract-Fetcher/2.0',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json'
                // Note: Tambahkan 'Authorization: Bearer YOUR_CORE_API_KEY' jika punya API key
            ),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false
        ));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['results'][0]['abstract']) && !empty($data['results'][0]['abstract'])) {
                return array(
                    'abstract' => $data['results'][0]['abstract'],
                    'source' => 'CORE',
                    'metadata' => array(
                        'repository' => isset($data['results'][0]['dataProvider']['name']) ? $data['results'][0]['dataProvider']['name'] : null,
                        'published_date' => isset($data['results'][0]['publishedDate']) ? $data['results'][0]['publishedDate'] : null
                    )
                );
            }
        }
    } catch (Exception $e) {
        error_log("CORE fetch error: " . $e->getMessage());
    }
    
    return null;
}

/**
 * Merekonstruksi abstrak dari inverted index format (optimized)
 * @param array $inverted_index - Inverted index data
 * @return string|null - Abstrak yang sudah direkonstruksi
 */
function reconstructAbstractFromInvertedIndex($inverted_index) {
    try {
        if (!is_array($inverted_index) || empty($inverted_index)) {
            return null;
        }
        
        $words = array();
        
        foreach ($inverted_index as $word => $positions) {
            if (is_array($positions)) {
                foreach ($positions as $position) {
                    $words[$position] = $word;
                }
            }
        }
        
        if (empty($words)) {
            return null;
        }
        
        ksort($words, SORT_NUMERIC);
        $abstract = implode(' ', $words);
        
        return strlen($abstract) > 20 ? $abstract : null;
    } catch (Exception $e) {
        error_log("Abstract reconstruction error: " . $e->getMessage());
        return null;
    }
}

// ==============================================
// ORCID API FETCHING - OPTIMIZED & PHP 5.4+ COMPATIBLE
// ==============================================

/**
 * Mengambil data dari ORCID API dengan optimisasi maksimal
 * @param string $orcid - ID ORCID peneliti
 * @return array - Data JSON yang dikembalikan dari ORCID API
 * @throws Exception - Jika terjadi error dalam pemanggilan API
 */
function fetchOrcidData($orcid) {
    // URL endpoint ORCID API v3.0 untuk mengambil full record
    $url = "https://pub.orcid.org/v3.0/{$orcid}/record";
    
    // Inisialisasi cURL dengan optimisasi
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => API_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_USERAGENT => 'ORCID-Data-Fetcher/2.0',
        CURLOPT_HTTPHEADER => array('Accept: application/json'),
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => false
    ));

    // Eksekusi request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Cek apakah terjadi error cURL
    if ($curl_error) {
        throw new Exception("cURL Error: " . $curl_error);
    }

    // Cek apakah request berhasil (HTTP 200)
    if ($http_code != 200) {
        throw new Exception("ORCID API returned HTTP {$http_code}");
    }

    if (!$response) {
        throw new Exception("Empty response from ORCID API");
    }

    // Parse JSON response menjadi array PHP
    $data = json_decode($response, true);
    if (!$data) {
        throw new Exception("Invalid JSON response from ORCID API");
    }

    return $data;
}

// ==============================================
// EKSEKUSI UTAMA - HIGH PERFORMANCE & PHP 5.4+ COMPATIBLE
// ==============================================
try {
    // Waktu mulai untuk performance tracking
    $start_time = microtime(true);
    
    // Ambil parameter ORCID dari query string dan bersihkan whitespace
    $orcid = isset($_GET['orcid']) ? trim($_GET['orcid']) : '';
    // Cek apakah user meminta refresh cache (bypass cache)
    $force_refresh = isset($_GET['refresh']) && $_GET['refresh'] == 'true';

    // Validasi format ORCID ID menggunakan regex
    // Format valid: api/orcid?orcid=0000-0000-0000-0000
    // Bypass cache: api/orcid?orcid=0000-0000-0000-0000&refresh=true
    if (!preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', $orcid)) {
        throw new Exception("Invalid ORCID ID format. Expected format: yourdomain.com/api/orcid?orcid=0000-0000-0000-0000");
    }

    // Generate cache key berdasarkan ORCID
    $cache_key = getCacheKey($orcid);

    // Bersihkan cache yang expired secara otomatis (jalankan secara random untuk performa)
    if (rand(1, 100) <= 2) { // 2% kemungkinan cleanup untuk mengurangi overhead
        cleanExpiredCache();
    }

    // Cek cache jika tidak diminta refresh
    if (!$force_refresh) {
        $cached_data = loadFromCache($cache_key, $orcid);
        if ($cached_data !== false) {
            // Update processing time untuk cached response
            $cached_data['cache_info']['performance']['processing_time'] = round((microtime(true) - $start_time) * 1000, 2);
            $cached_data['cache_info']['is_cached'] = true;
            
            // Jika ada cache yang valid, langsung return tanpa hit API
            echo json_encode($cached_data);
            exit;
        }
    }

    // Jika tidak ada cache atau diminta refresh, ambil data dari ORCID API
    $orcid_data = fetchOrcidData($orcid);

    // Strukturkan data final yang akan dikembalikan
    $profile = array(
        'orcid' => $orcid, // Menggunakan 'orcid' bukan 'orcid_id'
        // Ekstrak informasi dasar peneliti
        'basic_info' => extractBasicInfo($orcid_data['person']),
        // Ekstrak aktivitas peneliti (pekerjaan, pendidikan, publikasi, funding)
        'activities' => extractActivities($orcid_data['activities-summary']),
        // Metadata cache untuk debugging dan monitoring
        'cache_info' => array(
            'generated_at' => date('c'),        // Timestamp ISO 8601
            'cache_key' => $cache_key,          // Key cache yang digunakan
            'is_cached' => false,               // Flag apakah data dari cache
            'performance' => array(
                'abstract_fetched' => 0,        // Jumlah abstrak yang berhasil diambil
                'total_works' => 0,             // Total publikasi
                'processing_time' => 0          // Waktu pemrosesan dalam ms
            )
        )
    );

    // Hitung statistik performa
    $total_works = count($profile['activities']['works']);
    $abstract_fetched = 0;
    foreach ($profile['activities']['works'] as $work) {
        if (!empty($work['abstract_info']['abstract'])) {
            $abstract_fetched++;
        }
    }

    // Hitung waktu pemrosesan
    $processing_time = round((microtime(true) - $start_time) * 1000, 2); // dalam milliseconds

    $profile['cache_info']['performance']['total_works'] = $total_works;
    $profile['cache_info']['performance']['abstract_fetched'] = $abstract_fetched;
    $profile['cache_info']['performance']['processing_time'] = $processing_time;

    // Simpan data ke cache untuk request berikutnya
    saveToCache($cache_key, $profile);
    $profile['cache_info']['is_cached'] = true;  // Update flag karena sudah di-cache

    // Return response dalam format JSON
    echo json_encode($profile);

} catch (Exception $e) {
    // Log error untuk debugging
    error_log("ORCID API Error: " . $e->getMessage());
    
    // Jika terjadi error, set HTTP status 500 dan return error message
    http_response_code(500);
    echo json_encode(array(
        'error' => $e->getMessage(),
        'orcid' => isset($orcid) ? $orcid : null,  // Include ORCID untuk debugging
        'timestamp' => date('c'),
        'processing_time' => isset($start_time) ? round((microtime(true) - $start_time) * 1000, 2) : 0
    ));
}
?>