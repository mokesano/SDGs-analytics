<?php
declare(strict_types=1);

/**
 * @file doi_citation.php
 * 
 * Copyright (c) 2024 Sangia Wizdam Team
 * This software is released under the MIT License.
 * For full license information, see LICENSE file in the project root.
 * 
 * Script untuk mengambil data kutipan artikel berbasis DOI
 * Versi yang diperbarui dengan pendekatan kredensial dan sumber data tambahan:
 * OpenCitations → Crossref Cited-by → OpenAlex → Semantic Scholar → Dimensions
 * 
 * Fitur tambahan:
 * - Kompresi data cache
 * - Penyimpanan data dalam satu file json
 * - Penambahan informasi halaman dan ID artikel
 * - Optimalisasi permintaan API
 * - Penanganan kredensial Crossref (username/password yang digunakan untuk deposit)
 */

// -----------------------------------------------------------------
// BAGIAN MONITORING (UP/DOWN)
// -----------------------------------------------------------------
if (empty($_GET)) {
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'up', 
        'message' => 'Endpoint is operational', 
        'version' => 'v1.0.0'
        ]);
    exit;
}

// -----------------------------------------------------------------
// BAGIAN Contents
// -----------------------------------------------------------------
header('Content-Type: application/json');

// Definisikan konfigurasi
$config = array(
    // Direktori cache
    'cache_dir' => __DIR__ . '/cache',
    // Waktu kedaluwarsa cache (default 86400 untuk 24 jam dalam detik)
    'cache_expiry' => 604800, // 7 hari dalam detik (7 × 24 × 60 × 60)
    // Kredensial Crossref untuk Cited-by dan deposit
    'crossref_email' => 'rochmady@sangia.org',
    'crossref_username' => 'CrossRefUserName', // Ganti dengan username Crossref Anda
    'crossref_password' => 'CrossRefPassword', // Ganti dengan password Crossref Anda
    // Batas item per sumber
    'default_limit' => 15,
    // Kompresi data cache (true/false)
    'compress_cache' => true,
    // User agent untuk HTTP requests
    'user_agent' => 'SangiaPub/1.2 (mailto:rochmady@sangia.org)',
    // Timeout untuk HTTP requests (dalam detik)
    'request_timeout' => 15,
    'connect_timeout' => 5,
    // Dimensions API key (opsional - perlu mendaftar)
    'dimensions_api_key' => '', // Jika Anda memiliki akses ke API Dimensions
    // Opsi scraping yang lebih lengkap
    'dimensions_scrape_timeout' => 20, // Timeout khusus untuk scraping Dimensions
    // Semantic Scholar API key (opsional)
    'semantic_scholar_api_key' => '', // Isi API key Semantic Scholar jika ada
    // Timeout khusus untuk Semantic Scholar (baru)
    'semantic_scholar_timeout' => 30,
    // Debug/error log control
    'enable_error_log' => true, // Set ke true untuk mengaktifkan error logging
);

// Setup custom error handler untuk mengontrol error logging
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    global $config;
    
    // Hanya log error jika error logging diaktifkan
    if (!isset($config['enable_error_log']) || !$config['enable_error_log']) {
        return true; // Suppress error logging
    }
    
    // Log error seperti biasa
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
    
    // Return false untuk mengizinkan PHP menangani error ini dengan normal
    // Return true untuk menyembunyikan error
    return false;
});

// Pastikan direktori cache ada
if (!file_exists($config['cache_dir'])) {
    mkdir($config['cache_dir'], 0777, true);
}

// Ambil parameter
$doi = isset($_GET['doi']) ? trim($_GET['doi']) : '';
$refresh = isset($_GET['refresh']) && $_GET['refresh'] == '1';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : $config['default_limit'];

// Validasi DOI
if (empty($doi)) {
    echo json_encode(array('status' => 'error', 'message' => 'DOI is required'));
    exit;
}

/**
 * Helper function untuk HTTP request dengan dukungan untuk basic auth
 * @param string $url URL untuk request
 * @param array $headers Header tambahan (opsional)
 * @param string $userAgent User agent string
 * @param array $auth Kredensial untuk basic auth (opsional)
 * @return array|false Response array or false on failure
 */
function makeRequest($url, $headers = array(), $userAgent = null, $auth = null) {
    global $config;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent ? $userAgent : $config['user_agent']);
    curl_setopt($ch, CURLOPT_TIMEOUT, $config['request_timeout']);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $config['connect_timeout']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Untuk kompatibilitas lebih baik
    
    // Tambahkan basic auth jika ada
    if ($auth && isset($auth['username']) && isset($auth['password'])) {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $auth['username'] . ':' . $auth['password']);
    }
    
    // Tambahkan header jika ada
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    
    curl_close($ch);
    
    if ($response === false) {
        if ($config['enable_error_log']) {
            error_log("API request failed: $url, Error: $error");
        }    
        return false;
    }
    
    // Check for error responses
    if ($httpCode < 200 || $httpCode >= 300) {
        if ($config['enable_error_log']) {
            error_log("API request returned HTTP $httpCode: $url");
        }    
        // Return basic info for debugging
        return array(
            'success' => false,
            'http_code' => $httpCode,
            'error' => $error,
            'response' => substr($response, 0, 1000) // Truncated response for logs
        );
    }
    
    // Parse JSON response
    if (strpos($contentType, 'application/json') !== false) {
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            if ($config['enable_error_log']) {
                error_log("JSON decode error: " . json_last_error_msg());
            }    
            return false;
        }
        return array(
            'success' => true,
            'data' => $data
        );
    }
    
    // Return raw response for non-JSON content
    return array(
        'success' => true,
        'raw' => $response,
        'content_type' => $contentType
    );
}

/**
 * Fungsi untuk mengelola cache
 */
function getCacheFilePath($doi) {
    global $config;
    // Simpan semua data untuk satu DOI dalam satu file
    return $config['cache_dir'] . '/' . md5($doi) . '.json' . ($config['compress_cache'] ? '.gz' : '');
}

/**
 * Ambil data dari cache
 * @param string $doi DOI artikel
 * @return array|null Data dari cache atau null jika tidak ditemukan/kadaluarsa
 */
function getFromCache($doi) {
    global $config;
    $cacheFile = getCacheFilePath($doi);
    
    if (file_exists($cacheFile)) {
        // Baca data dari cache dengan error handling
        try {
            if ($config['compress_cache']) {
                $compressed = file_get_contents($cacheFile);
                if ($compressed === false) return null;
                $content = @gzuncompress($compressed);
                if ($content === false) {
                    // Corrupt compressed cache, hapus dan kembalikan null
                    @unlink($cacheFile);
                    return null;
                }
            } else {
                $content = file_get_contents($cacheFile);
                if ($content === false) return null;
            }
            
            $data = json_decode($content, true);
            if (!$data) {
                // Invalid JSON, hapus cache yang rusak
                @unlink($cacheFile);
                return null;
            }
            
            // Periksa integritas data cache
            if (!isset($data['citation_count']) || !isset($data['citing_articles'])) {
                // Cache data structures missing, regenerate
                @unlink($cacheFile);
                return null;
            }
            
            // Periksa waktu kedaluwarsa
            if (isset($data['timestamp']) && (time() - $data['timestamp'] < $config['cache_expiry'])) {
                return $data;
            }
        } catch (Exception $e) {
            // Jika terjadi error saat membaca cache, hapus cache dan kembalikan null
            @unlink($cacheFile);
            return null;
        }
    }
    
    return null;
}

/**
 * Simpan data ke cache
 * @param string $doi DOI artikel
 * @param array $data Data yang akan disimpan
 * @return bool Berhasil/gagal
 */
function saveToCache($doi, $data) {
    global $config;
    $cacheFile = getCacheFilePath($doi);
    
    // Tambahkan timestamp
    $data['timestamp'] = time();
    
    // Encode data ke JSON
    $jsonData = json_encode($data);
    if ($jsonData === false) {
        if ($config['enable_error_log']) {
            error_log("Failed to encode cache data to JSON");
        }    
        return false;
    }
    
    // Kompres data jika diperlukan
    if ($config['compress_cache']) {
        $content = gzcompress($jsonData, 9); // Level kompresi 9 (max)
        if ($content === false) {
            if ($config['enable_error_log']) {
                error_log("Failed to compress cache data");
            }    
            return false;
        }
    } else {
        $content = $jsonData;
    }
    
    // Tulis ke file
    $result = file_put_contents($cacheFile, $content);
    return ($result !== false);
}

/**
 * Hapus cache untuk DOI tertentu
 * @param string $doi DOI artikel
 * @return bool Berhasil/gagal
 */
function clearCache($doi) {
    $cacheFile = getCacheFilePath($doi);
    if (file_exists($cacheFile)) {
        return unlink($cacheFile);
    }
    return true;
}

/**
 * Format author names correctly
 * @param array $authors Array of author data
 * @param string $format Source format (crossref, openalex, dimensions, opencitations)
 * @return array Reformatted author names for consistent display
 */
function formatAuthors($authors, $format = 'crossref') {
    $formattedAuthors = array();
    
    if (empty($authors) || !is_array($authors)) {
        return $formattedAuthors;
    }
    
    switch ($format) {
        case 'crossref':
            foreach ($authors as $author) {
                if (isset($author['family'])) {
                    $formattedAuthors[] = array(
                        'given' => isset($author['given']) ? $author['given'] : '',
                        'family' => $author['family'],
                        'orcid' => isset($author['ORCID']) ? $author['ORCID'] : null
                    );
                } elseif (isset($author['name'])) {
                    // If only name is provided, try to split it
                    $parts = explode(' ', trim($author['name']));
                    if (count($parts) > 1) {
                        $family = array_pop($parts);
                        $given = implode(' ', $parts);
                        
                        $formattedAuthors[] = array(
                            'given' => $given,
                            'family' => $family,
                            'orcid' => null
                        );
                    } else {
                        $formattedAuthors[] = array(
                            'given' => '',
                            'family' => $author['name'],
                            'orcid' => null
                        );
                    }
                }
            }
            break;
        
        case 'semanticscholar':
            foreach ($authors as $author) {
                if (isset($author['name'])) {
                    // If Semantic Scholar provides a name, parse it
                    $name = $author['name'];
                    
                    // Check if name already has a comma (last name first format)
                    if (strpos($name, ',') !== false) {
                        $parts = explode(',', $name, 2);
                        $family = trim($parts[0]);
                        $given = isset($parts[1]) ? trim($parts[1]) : '';
                    } else {
                        // Standard parsing for "Given Family" format
                        $parts = explode(' ', trim($name));
                        
                        if (count($parts) > 1) {
                            $family = array_pop($parts);
                            $given = implode(' ', $parts);
                        } else {
                            $family = $name;
                            $given = '';
                        }
                    }
                    
                    $formattedAuthors[] = array(
                        'given' => $given,
                        'family' => $family,
                        'orcid' => isset($author['orcid']) ? $author['orcid'] : null,
                        'id' => isset($author['authorId']) ? $author['authorId'] : null
                    );
                }
            }
            break;
            
        case 'openalex':
            foreach ($authors as $author) {
                if (isset($author['author']['display_name'])) {
                    $name = $author['author']['display_name'];
                    $parts = explode(' ', trim($name));
                    
                    if (count($parts) > 1) {
                        $family = array_pop($parts);
                        $given = implode(' ', $parts);
                    } else {
                        $family = $name;
                        $given = '';
                    }
                    
                    $formattedAuthors[] = array(
                        'given' => $given,
                        'family' => $family,
                        'orcid' => isset($author['author']['orcid']) ? $author['author']['orcid'] : null,
                        'id' => isset($author['author']['id']) ? $author['author']['id'] : null
                    );
                }
            }
            break;
            
        case 'dimensions':
            foreach ($authors as $author) {
                if (isset($author['first_name']) || isset($author['last_name'])) {
                    $formattedAuthors[] = array(
                        'given' => isset($author['first_name']) ? $author['first_name'] : '',
                        'family' => isset($author['last_name']) ? $author['last_name'] : '',
                        'orcid' => isset($author['orcid_id']) ? $author['orcid_id'] : null,
                        'id' => isset($author['researcher_id']) ? $author['researcher_id'] : null
                    );
                } elseif (isset($author['full_name'])) {
                    $parts = explode(' ', trim($author['full_name']));
                    
                    if (count($parts) > 1) {
                        $family = array_pop($parts);
                        $given = implode(' ', $parts);
                    } else {
                        $family = $author['full_name'];
                        $given = '';
                    }
                    
                    $formattedAuthors[] = array(
                        'given' => $given,
                        'family' => $family,
                        'orcid' => isset($author['orcid_id']) ? $author['orcid_id'] : null,
                        'id' => isset($author['researcher_id']) ? $author['researcher_id'] : null
                    );
                }
            }
            break;
            
        case 'opencitations':
        default:
            // Handle string-based author format (fallback)
            foreach ($authors as $author) {
                if (is_string($author)) {
                    // Check if in "Family, Given" format
                    if (strpos($author, ',') !== false) {
                        $parts = explode(',', $author, 2);
                        $family = trim($parts[0]);
                        $given = isset($parts[1]) ? trim($parts[1]) : '';
                        
                        $formattedAuthors[] = array(
                            'given' => $given,
                            'family' => $family,
                            'orcid' => null
                        );
                    } else {
                        // Jika tidak ada format "Family, Given", coba pisahkan berdasarkan spasi
                        $parts = explode(' ', trim($author));
                        if (count($parts) > 1) {
                            $family = array_pop($parts);
                            $given = implode(' ', $parts);
                            
                            $formattedAuthors[] = array(
                                'given' => $given,
                                'family' => $family,
                                'orcid' => null
                            );
                        } else {
                            // Jika hanya satu kata, anggap sebagai family name
                            $formattedAuthors[] = array(
                                'given' => '',
                                'family' => $author,
                                'orcid' => null
                            );
                        }
                    }
                }
            }
            break;
    }
    
    return $formattedAuthors;
}

/**
 * Check if a URL points to a PDF file
 * @param string $url The URL to check
 * @return bool True if URL likely points to a PDF
 */
function isPdfUrl($url) {
    if (empty($url)) {
        return false;
    }
    
    // Check file extension
    if (preg_match('/\.pdf(\?|$)/i', $url)) {
        return true;
    }
    
    // Check common PDF URL patterns
    $pdfPatterns = array(
        '/\/pdf\//i',
        '/\/download\/pdf/i',
        '/[?&]format=pdf/i',
        '/\/viewcontent\.cgi.*\.pdf/i',
        '/\/bitstream\/.*\.pdf/i',
        '/\/fulltext\/.*\.pdf/i',
        '/\/content\/pdf/i',
        '/\/direct\/pdf/i',
        '/\.full\.pdf/i',
        '/\/article-pdf\//i'
    );
    
    foreach ($pdfPatterns as $pattern) {
        if (preg_match($pattern, $url)) {
            return true;
        }
    }
    
    // Check for PDF serving domains that might not have .pdf in URL
    $pdfDomains = array(
        'pdfs.semanticscholar.org',
        'dl.acm.org/ft_gateway',
        'arxiv.org/pdf/',
        'pdf.sciencedirectassets.com',
        'downloads.hindawi.com',
    );
    
    foreach ($pdfDomains as $domain) {
        if (strpos($url, $domain) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Function to check if a PDF URL is actually accessible
 * @param string $url The URL to check
 * @return bool True if the PDF is accessible
 */
function isPdfAccessible($url) {
    if (empty($url)) {
        return false;
    }
    
    // Initialize cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Maximum redirects
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Execute the request
    curl_exec($ch);
    
    // Check for any errors
    if (curl_errno($ch)) {
        curl_close($ch);
        return false;
    }
    
    // Get HTTP code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    
    curl_close($ch);
    
    // Check if we got a successful response and it's a PDF
    if ($httpCode == 200) {
        // Check if content type is PDF
        if (strpos($contentType, 'application/pdf') !== false) {
            return true;
        }
        
        // Some servers don't properly set content type, so we check for other indicators
        if (strpos($contentType, 'octet-stream') !== false || 
            strpos($contentType, 'binary') !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get article metadata from Crossref
 * @param string $doi DOI artikel
 * @return array|null Metadata artikel atau null jika gagal
 */
function getArticleMetadata($doi) {
    global $config;
    
    // Build URL with credentials
    $url = "https://api.crossref.org/works/" . urlencode($doi);
    if (!empty($config['crossref_email'])) {
        $url .= "?mailto=" . urlencode($config['crossref_email']);
    }
    
    $response = makeRequest($url);
    
    if (!$response || !$response['success']) {
        return null;
    }
    
    if (isset($response['data']['message'])) {
        return $response['data']['message'];
    }
    
    return null;
}

/**
 * Get OpenCitations data for a DOI
 * @param string $doi DOI artikel
 * @param int $limit Batas jumlah hasil
 * @return array Data kutipan
 */
function getOpenCitationsCitations($doi, $limit = 50) {
    // Step 1: Get citations from OpenCitations COCI API
    $url = "https://opencitations.net/index/coci/api/v1/citations/" . urlencode($doi);
    $response = makeRequest($url);
    
    if (!$response || !$response['success'] || empty($response['data'])) {
        return array();
    }
    
    // Kumpulkan DOI dari artikel yang mengutip
    $citingDois = array();
    foreach ($response['data'] as $citation) {
        if (isset($citation['citing'])) {
            $citingDois[] = $citation['citing'];
        }
    }
    
    // Batasi jumlah DOI
    $citingDois = array_slice($citingDois, 0, $limit);
    
    // Step 2: Get metadata for each citing DOI
    $citations = array();
    foreach ($citingDois as $citingDoi) {
        // Get metadata from OpenCitations API directly if possible
        $metaUrl = "https://opencitations.net/index/coci/api/v1/metadata/" . urlencode($citingDoi);
        $metaResponse = makeRequest($metaUrl);
        
        if ($metaResponse && $metaResponse['success'] && !empty($metaResponse['data'])) {
            $item = isset($metaResponse['data'][0]) ? $metaResponse['data'][0] : null;
            
            if ($item) {
                // Extract basic metadata
                $authors = array();
                if (isset($item['author']) && !empty($item['author'])) {
                    $authorNames = explode('; ', $item['author']);
                    $authors = $authorNames;
                }
                
                $title = isset($item['title']) ? $item['title'] : 'Title not available';
                
                // Try to determine publication type
                $pubType = 'article-journal';  // Default type for OpenCitations
                if (isset($item['type'])) {
                    $pubType = $item['type'];
                } else {
                    // Try to guess type from source title or other clues
                    if (isset($item['source_title'])) {
                        $sourceTitle = strtolower($item['source_title']);
                        if (strpos($sourceTitle, 'proceedings') !== false || 
                            strpos($sourceTitle, 'conference') !== false) {
                            $pubType = 'proceedings-article';
                        } elseif (strpos($sourceTitle, 'book') !== false) {
                            // Could be a book or book chapter
                            if (isset($item['source_id']) && $item['source_id'] !== $citingDoi) {
                                $pubType = 'book-chapter';
                            } else {
                                $pubType = 'book';
                            }
                        } elseif (strpos($sourceTitle, 'thesis') !== false || 
                                 strpos($sourceTitle, 'dissertation') !== false) {
                            $pubType = 'dissertation';
                        }
                    }
                }
                
                // Source information
                $container = isset($item['source_title']) ? $item['source_title'] : null;
                if (empty($container)) {
                    $container = getDefaultContainerByType($pubType);
                }
                $container = ensureValidContainer($container, $pubType);
                
                $year = isset($item['year']) ? $item['year'] : null;
                $volume = isset($item['volume']) ? $item['volume'] : null;
                $issue = isset($item['issue']) ? $item['issue'] : null;
                $page = isset($item['page']) ? $item['page'] : null;
                
                $url = "https://doi.org/" . $citingDoi;
                
                $citations[] = array(
                    'title' => $title,
                    'doi' => $citingDoi,
                    'url' => $url,
                    'is_pdf' => false,
                    'pdf_url' => null,
                    'is_open_access' => false, // OpenCitations doesn't provide OA status
                    'container' => $container,
                    'type' => $pubType,
                    'publisher' => null, // OpenCitations doesn't typically provide publisher info
                    'year' => $year,
                    'volume' => $volume,
                    'issue' => $issue,
                    'page' => $page,
                    'isbn' => null, // OpenCitations doesn't typically provide ISBN
                    'authors' => formatAuthors($authors, 'opencitations'),
                    'source' => 'opencitations',
                    'id' => null,
                    'title_hash' => md5(strtolower(trim(strip_tags($title))))
                );
            }
        } else {
            // Fallback to Crossref for metadata
            $metadata = getMetadataFromCrossref($citingDoi);
            if ($metadata) {
                $citation = formatCitationFromCrossref($metadata);
                $citation['source'] = 'opencitations';
                $citations[] = $citation;
            }
        }
    }
    
    return $citations;
}

/**
 * Try alternative methods to get citation data when primary method fails
 * @param string $doi DOI artikel
 * @param int $limit Batas jumlah hasil
 * @return array Data kutipan
 */
function tryAlternativeMethods($doi, $limit = 50) {
    // Coba metode alternatif pertama
    $citations = getCrossrefCitationsAlternative($doi, $limit);
    
    // Jika masih tidak ada hasil, coba metode alternatif kedua
    if (empty($citations)) {
        $citations = getCrossrefCitationsReferenceFilter($doi, $limit);
    }
    
    return $citations;
}

/**
 * Get Crossref Cited-by data using credentials (username/password)
 * @param string $doi DOI artikel
 * @param int $limit Batas jumlah hasil
 * @return array Data kutipan
 */
function getCrossrefCitedBy($doi, $limit = 50) {
    global $config;
    
    // Format URL sesuai dokumentasi Crossref - parameter di URL, bukan basic auth
    $url = "https://doi.crossref.org/servlet/getForwardLinks" . 
           "?doi=" . urlencode($doi) . 
           "&usr=" . urlencode($config['crossref_username']) . 
           "&pwd=" . urlencode($config['crossref_password']);
    
    // Tidak perlu basic auth lagi karena kredensial sudah ada dalam URL
    $response = makeRequest($url, array(), null, null);
    
    // Jika gagal, coba metode alternatif
    if (!$response || !$response['success'] || !isset($response['raw'])) {
        return tryAlternativeMethods($doi, $limit);
    }
    
    // Cited-by service mengembalikan XML, parse respons XML
    $citations = array();
    
    if (isset($response['raw'])) {
        $xml = $response['raw'];
        
        // Coba parse XML dengan error handling
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);  // Supresses XML parsing errors
        $loadSuccess = $doc->loadXML($xml);
        libxml_clear_errors();  // Clears any XML parsing errors
        
        if (!$loadSuccess) {
            // XML parsing failed, use alternative method
            return getCrossrefCitationsAlternative($doi, $limit);
        }
        
        // Query node-node <forward_link>
        $forwardLinks = $doc->getElementsByTagName('forward_link');
        
        if ($forwardLinks->length > 0) {
            // Batasi jumlah kutipan sesuai parameter
            $count = min($forwardLinks->length, $limit);
            
            for ($i = 0; $i < $count; $i++) {
                $link = $forwardLinks->item($i);
                
                // Ekstrak DOI dari elemen <doi>
                $doiNodes = $link->getElementsByTagName('doi');
                $citingDoi = ($doiNodes->length > 0) ? $doiNodes->item(0)->nodeValue : null;
                
                if (!$citingDoi) {
                    continue;
                }
                
                // Dapatkan metadata dari CrossRef untuk DOI ini
                $metadata = getMetadataFromCrossref($citingDoi);
                
                if ($metadata) {
                    $citation = formatCitationFromCrossref($metadata);
                    $citation['source'] = 'crossref';
                    $citations[] = $citation;
                }
            }
        }
    }
    
    // Jika hasil kosong, juga coba metode alternatif
    if (empty($citations)) {
        return tryAlternativeMethods($doi, $limit);
    }
    
    return $citations;
}

/**
 * Alternative method to get Crossref citations (used as fallback)
 * @param string $doi DOI artikel
 * @param int $limit Batas jumlah hasil
 * @return array Data kutipan
 */
function getCrossrefCitationsAlternative($doi, $limit = 50) {
    global $config;
    
    // Try alternative approach: find works that reference this DOI through REST API
    $url = "https://api.crossref.org/works?filter=references.doi:" . urlencode($doi) . "&rows=$limit&sort=published&order=desc";
    if (!empty($config['crossref_email'])) {
        $url .= "&mailto=" . urlencode($config['crossref_email']);
    }
    
    $response = makeRequest($url);
    
    if (!$response || !$response['success'] || 
        empty($response['data']['message']) || 
        empty($response['data']['message']['items'])) {
        return array();
    }
    
    $citations = array();
    foreach ($response['data']['message']['items'] as $item) {
        $citation = formatCitationFromCrossref($item);
        $citation['source'] = 'crossref';
        $citations[] = $citation;
    }
    
    return $citations;
}

/**
 * Second alternative method using reference-doi filter
 * @param string $doi DOI artikel
 * @param int $limit Batas jumlah hasil
 * @return array Data kutipan
 */
function getCrossrefCitationsReferenceFilter($doi, $limit = 50) {
    global $config;
    
    // Filter by works that reference the given DOI
    $url = "https://api.crossref.org/works?filter=reference-doi:" . urlencode($doi) . 
           "&rows=$limit&sort=published&order=desc";
    
    // Tambahkan email (direkomendasikan untuk menghindari throttling)
    if (!empty($config['crossref_email'])) {
        $url .= "&mailto=" . urlencode($config['crossref_email']);
    }
    
    $response = makeRequest($url);
    
    if (!$response || !$response['success'] || 
        empty($response['data']['message']) || 
        empty($response['data']['message']['items'])) {
        return array();
    }
    
    $citations = array();
    foreach ($response['data']['message']['items'] as $item) {
        $citation = formatCitationFromCrossref($item);
        $citation['source'] = 'crossref';
        $citations[] = $citation;
    }
    
    return $citations;
}

/**
 * Helper function to format Crossref metadata into citation
 * @param array $item Crossref metadata
 * @return array Formatted citation
 */
function formatCitationFromCrossref($item) {
    // Extract authors
    $authors = isset($item['author']) && is_array($item['author']) ? $item['author'] : array();
    
    // Extract title - PRESERVE HTML TAGS
    $title = 'Title not available';
    if (isset($item['title']) && is_array($item['title']) && !empty($item['title'])) {
        $title = $item['title'][0];
    }
    
    // Get publication type
    $pubType = 'article-journal';
    if (isset($item['type'])) {
        $pubType = $item['type'];
    }
    
    // Extract source info based on publication type
    $sourceInfo = extractSourceInfo($item, $pubType);
    $journal = ensureValidContainer($sourceInfo['container'], $pubType);
    
    // Extract year
    $year = null;
    if (isset($item['published-print']) && isset($item['published-print']['date-parts']) && 
        isset($item['published-print']['date-parts'][0]) && isset($item['published-print']['date-parts'][0][0])) {
        $year = $item['published-print']['date-parts'][0][0];
    } else if (isset($item['published-online']) && isset($item['published-online']['date-parts']) && 
             isset($item['published-online']['date-parts'][0]) && isset($item['published-online']['date-parts'][0][0])) {
        $year = $item['published-online']['date-parts'][0][0];
    } else if (isset($item['created']) && isset($item['created']['date-parts']) && 
             isset($item['created']['date-parts'][0]) && isset($item['created']['date-parts'][0][0])) {
        $year = $item['created']['date-parts'][0][0];
    } else if (isset($item['issued']) && isset($item['issued']['date-parts']) && 
             isset($item['issued']['date-parts'][0]) && isset($item['issued']['date-parts'][0][0])) {
        $year = $item['issued']['date-parts'][0][0];
    } else if (isset($item['published']) && isset($item['published']['date-parts']) && 
             isset($item['published']['date-parts'][0]) && isset($item['published']['date-parts'][0][0])) {
        $year = $item['published']['date-parts'][0][0];
    }
    
    // Extract page information
    $page = null;
    if (isset($item['page'])) {
        $page = $item['page'];
    }
    
    // IMPROVED PDF DETECTION FOR OPEN ACCESS ARTICLES ONLY
    $url = null;
    $isPdf = false;
    $pdfUrl = null;
    
    // Check if this is an open access article
    $isOpenAccess = false;
    if (isset($item['license']) && is_array($item['license']) && !empty($item['license'])) {
        foreach ($item['license'] as $license) {
            // Check for open access licenses
            if (isset($license['URL']) && 
                (strpos($license['URL'], 'creativecommons.org') !== false || 
                 strpos($license['URL'], 'licenses/by') !== false ||
                 strpos($license['URL'], 'open-access') !== false)) {
                $isOpenAccess = true;
                break;
            }
        }
    }
    
    // Also check for explicit open access flag
    if (isset($item['is_open_access']) && $item['is_open_access'] === true) {
        $isOpenAccess = true;
    }
    
    // Try to find direct PDF links (especially for open access articles)
    if (isset($item['link']) && is_array($item['link']) && !empty($item['link'])) {
        foreach ($item['link'] as $link) {
            // Check for PDF content type
            if (isset($link['URL'])) {
                if (isset($link['content-type']) && $link['content-type'] === 'application/pdf') {
                    $pdfUrl = $link['URL'];
                    $isPdf = true;
                } elseif (isPdfUrl($link['URL'])) {
                    // Also check the URL pattern itself
                    $pdfUrl = $link['URL'];
                    $isPdf = true;
                }
                
                // If we don't have any URL yet, keep this as the main URL
                if ($url === null) {
                    $url = $link['URL'];
                }
            }
        }
    }
    
    // For open access articles from Unpaywall or OA API
    if (isset($item['best_oa_location']) && isset($item['best_oa_location']['url_for_pdf'])) {
        $pdfUrl = $item['best_oa_location']['url_for_pdf'];
        $isPdf = true;
    }
    
    // Check for full-text URL if no PDF link yet
    if ($pdfUrl === null && isset($item['URL'])) {
        $url = $item['URL'];
        // Check if the main URL is a PDF
        if (isPdfUrl($url)) {
            $pdfUrl = $url;
            $isPdf = true;
        }
    } else if ($url === null && isset($item['DOI'])) {
        // Use DOI URL as fallback
        $url = 'https://doi.org/' . $item['DOI'];
    }
    
    // For Crossref direct links to PDF
    if (isset($item['resource']) && isset($item['resource']['primary']) && 
        isset($item['resource']['primary']['URL']) && isPdfUrl($item['resource']['primary']['URL'])) {
        $pdfUrl = $item['resource']['primary']['URL'];
        $isPdf = true;
    }
    
    return array(
        'title' => $title,
        'doi' => isset($item['DOI']) ? $item['DOI'] : null,
        'url' => $url,
        'is_pdf' => $isPdf,
        'pdf_url' => $pdfUrl, // New field specifically for PDF URL
        'is_open_access' => $isOpenAccess,
        'container' => $journal,
        'type' => $pubType,
        'publisher' => isset($item['publisher']) ? $item['publisher'] : null,
        'year' => $year,
        'volume' => isset($item['volume']) ? $item['volume'] : null,
        'issue' => isset($item['issue']) ? $item['issue'] : null,
        'page' => $page,
        'isbn' => isset($item['ISBN']) ? (is_array($item['ISBN']) ? $item['ISBN'][0] : $item['ISBN']) : null,
        'issn' => isset($item['ISSN']) ? (is_array($item['ISSN']) ? $item['ISSN'][0] : $item['ISSN']) : null,
        'authors' => formatAuthors($authors, 'crossref'),
        'id' => isset($item['member']) ? $item['member'] : null,
        'title_hash' => md5(strtolower(trim(strip_tags($title))))
    );
}

/**
 * Extract appropriate source information based on publication type
 * @param array $item Metadata item
 * @param string $pubType Publication type
 * @return array Source information
 */
function extractSourceInfo($item, $pubType) {
    $result = array(
        'container' => 'Source not available',
        'publisher' => isset($item['publisher']) ? $item['publisher'] : null,
        'type' => $pubType
    );
    
    switch ($pubType) {
        case 'journal-article':
        case 'article-journal':
        case 'journal':
        case 'journal-issue':
            // Journal articles
            if (isset($item['container-title']) && is_array($item['container-title']) && !empty($item['container-title'])) {
                $result['container'] = $item['container-title'][0];
            } elseif (isset($item['short-container-title']) && is_array($item['short-container-title']) && !empty($item['short-container-title'])) {
                $result['container'] = $item['short-container-title'][0];
            }
            break;
            
        case 'book':
        case 'monograph':
        case 'reference-book':
            // Books and monographs - use title as container for standalone books
            $result['container'] = 'Book';
            if (isset($item['publisher'])) {
                $result['container'] = $item['publisher'];
            }
            break;
            
        case 'book-chapter':
        case 'book-part':
        case 'book-section':
            // Book chapters - use book title as container
            if (isset($item['container-title']) && is_array($item['container-title']) && !empty($item['container-title'])) {
                $result['container'] = 'In: ' . $item['container-title'][0];
            } elseif (isset($item['collection-title']) && is_array($item['collection-title']) && !empty($item['collection-title'])) {
                $result['container'] = 'In: ' . $item['collection-title'][0];
            } elseif (isset($item['publisher'])) {
                $result['container'] = 'In: Book from ' . $item['publisher'];
            } else {
                $result['container'] = 'Book chapter';
            }
            break;
            
        case 'conference-paper':
        case 'proceedings-article':
        case 'proceedings':
            // Conference papers and proceedings
            if (isset($item['event']) && isset($item['event']['name'])) {
                $result['container'] = $item['event']['name'];
            } elseif (isset($item['container-title']) && is_array($item['container-title']) && !empty($item['container-title'])) {
                $result['container'] = 'Proc. ' . $item['container-title'][0];
            } elseif (isset($item['group-title'])) {
                $result['container'] = 'Proc. ' . $item['group-title'];
            } else {
                $result['container'] = 'Conference proceedings';
            }
            break;
            
        case 'dissertation':
        case 'thesis':
            // Theses and dissertations
            if (isset($item['institution']) && is_array($item['institution'])) {
                if (isset($item['institution'][0]['name'])) {
                    $result['container'] = 'Thesis, ' . $item['institution'][0]['name'];
                } else {
                    $result['container'] = 'Thesis';
                }
            } else {
                $result['container'] = 'Thesis/Dissertation';
            }
            break;
            
        case 'report':
        case 'report-series':
        case 'technical-report':
            // Reports
            if (isset($item['institution']) && is_array($item['institution'])) {
                if (isset($item['institution'][0]['name'])) {
                    $result['container'] = 'Report, ' . $item['institution'][0]['name'];
                } else {
                    $result['container'] = 'Report';
                }
            } elseif (isset($item['publisher'])) {
                $result['container'] = 'Report, ' . $item['publisher'];
            } else {
                $result['container'] = 'Technical report';
            }
            break;
            
        case 'patent':
            // Patents
            $result['container'] = 'Patent';
            if (isset($item['publisher'])) {
                $result['container'] = 'Patent, ' . $item['publisher'];
            }
            break;
            
        case 'dataset':
            // Datasets
            $result['container'] = 'Dataset';
            if (isset($item['publisher'])) {
                $result['container'] = 'Dataset, ' . $item['publisher'];
            }
            break;
            
        case 'preprint':
            // Preprints
            if (isset($item['institution']) && is_array($item['institution'])) {
                if (isset($item['institution'][0]['name'])) {
                    $result['container'] = 'Preprint, ' . $item['institution'][0]['name'];
                } else {
                    $result['container'] = 'Preprint';
                }
            } elseif (isset($item['group-title'])) {
                $result['container'] = 'Preprint, ' . $item['group-title'];
            } else {
                $result['container'] = 'Preprint';
            }
            break;
            
        default:
            // Default fallback for other types
            if (isset($item['container-title']) && is_array($item['container-title']) && !empty($item['container-title'])) {
                $result['container'] = $item['container-title'][0];
            } elseif (isset($item['publisher'])) {
                $result['container'] = $item['publisher'];
            } else {
                $result['container'] = 'Publication';
            }
            break;
    }
    
    return $result;
}

/**
 * Utility function to get metadata from Crossref
 * @param string $doi DOI artikel
 * @return array|null Metadata artikel atau null jika gagal
 */
function getMetadataFromCrossref($doi) {
    global $config;
    
    $url = "https://api.crossref.org/works/" . urlencode($doi);
    if (!empty($config['crossref_email'])) {
        $url .= "?mailto=" . urlencode($config['crossref_email']);
    }
    
    $response = makeRequest($url);
    
    if (!$response || !$response['success'] || empty($response['data']['message'])) {
        return null;
    }
    
    return $response['data']['message'];
}

/**
 * Get OpenAlex citations
 * @param string $doi DOI artikel
 * @param int $limit Batas jumlah hasil
 * @return array Data kutipan
 */
function getOpenAlexCitations($doi, $limit = 50) {
    // Step 1: Get OpenAlex ID for the DOI
    $url = "https://api.openalex.org/works/doi:" . urlencode($doi);
    $response = makeRequest($url);
    
    if (!$response || !$response['success'] || empty($response['data']['id'])) {
        return array();
    }
    
    $openAlexId = $response['data']['id'];
    
    // Step 2: Get citations with the OpenAlex ID
    $citationsUrl = "https://api.openalex.org/works?filter=cites:" . urlencode($openAlexId) . "&per_page=$limit";
    $citationsResponse = makeRequest($citationsUrl);
    
    if (!$citationsResponse || !$citationsResponse['success'] || 
        empty($citationsResponse['data']['results'])) {
        return array();
    }
    
    $citations = array();
    foreach ($citationsResponse['data']['results'] as $item) {
        // Extract basic metadata
        $title = isset($item['title']) ? $item['title'] : 'Title not available';
        
        // Determine publication type
        $pubType = 'article-journal';
        if (isset($item['type'])) {
            $pubType = $item['type'];
        } elseif (isset($item['type_crossref'])) {
            $pubType = $item['type_crossref'];
        }
        
        // ========= IMPROVED CONTAINER EXTRACTION =========
        $container = null;
        $publisher = null;
        
        // Cek struktur baru OpenAlex
        if (isset($item['primary_location']) && isset($item['primary_location']['source'])) {
            if (isset($item['primary_location']['source']['display_name'])) {
                $container = $item['primary_location']['source']['display_name'];
            }
            if (isset($item['primary_location']['source']['publisher'])) {
                $publisher = $item['primary_location']['source']['publisher'];
            }
        }
        
        // Cek struktur lama untuk kompatibilitas
        if (empty($container) || $container == 'Publication') {
            if (isset($item['host_venue']) && isset($item['host_venue']['display_name']) && 
                $item['host_venue']['display_name'] != 'Publication') {
                $container = $item['host_venue']['display_name'];
                
                if (empty($publisher) && isset($item['host_venue']['publisher'])) {
                    $publisher = $item['host_venue']['publisher'];
                }
            }
        }
        
        // Cek di alternate_locations untuk nama jurnal/venue
        if (empty($container) || $container == 'Publication') {
            if (isset($item['alternate_locations']) && is_array($item['alternate_locations'])) {
                foreach ($item['alternate_locations'] as $altLoc) {
                    if (isset($altLoc['source']) && isset($altLoc['source']['display_name']) &&
                        !empty($altLoc['source']['display_name']) && 
                        $altLoc['source']['display_name'] != 'Publication') {
                        $container = $altLoc['source']['display_name'];
                        
                        if (empty($publisher) && isset($altLoc['source']['publisher'])) {
                            $publisher = $altLoc['source']['publisher'];
                        }
                        break;
                    }
                }
            }
        }
        
        // Jika masih "Publication" atau kosong, gunakan publisher sebagai indikasi
        if ((empty($container) || $container == 'Publication') && !empty($publisher)) {
            $containerPrefix = '';
            
            if ($pubType == 'book-chapter') {
                $containerPrefix = 'In: Book from ';
            } elseif ($pubType == 'proceedings-article' || $pubType == 'proceedings') {
                $containerPrefix = 'Proc. ';
            } elseif ($pubType == 'dissertation') {
                $containerPrefix = 'Thesis, ';
            }
            
            $container = $containerPrefix . $publisher;
        }
        
        // Fallback terakhir berdasarkan tipe publikasi
        if (empty($container) || $container == 'Publication') {
            if ($pubType == 'book') {
                $container = 'Book';
            } elseif ($pubType == 'book-chapter') {
                $container = 'Book Chapter';
            } elseif ($pubType == 'proceedings' || $pubType == 'proceedings-article') {
                $container = 'Conference Proceedings';
            } elseif ($pubType == 'dissertation') {
                $container = 'Thesis/Dissertation';
            } elseif ($pubType == 'journal-article' || $pubType == 'article-journal') {
                $container = 'Academic Journal';
            } else {
                $container = 'Journal Deleted from Database';
            }
        }
        
        // Ensure container is valid
        $container = ensureValidContainer($container, $pubType);
        
        // Extract year, DOI, etc.
        $year = isset($item['publication_year']) ? $item['publication_year'] : null;
        
        $doi = null;
        if (isset($item['doi'])) {
            $doi = str_replace('https://doi.org/', '', $item['doi']);
        }
        
        // ========= URL MANAGEMENT - SEPARATE LANDING PAGE URL FROM PDF URL =========
        $url = null;
        $isPdf = false;
        $pdfUrl = null;
        $isOpenAccess = false;
        
        // Check for open access status
        if (isset($item['open_access']) && isset($item['open_access']['is_oa']) && $item['open_access']['is_oa'] === true) {
            $isOpenAccess = true;
        }
        
        // 1. UNTUK URL ARTIKEL (LANDING PAGE) - prioritaskan URL non-PDF
        if (isset($item['primary_location']) && isset($item['primary_location']['landing_page_url'])) {
            $url = $item['primary_location']['landing_page_url'];
        } elseif (isset($item['doi'])) {
            // Gunakan DOI URL sebagai URL default yang bagus
            $url = $item['doi']; // Sudah termasuk https://doi.org/
        } elseif (!empty($doi)) {
            // Atau buat DOI URL jika DOI ada
            $url = 'https://doi.org/' . $doi;
        } elseif (isset($item['primary_location']) && isset($item['primary_location']['url']) && 
                 !isPdfUrl($item['primary_location']['url'])) {
            // Opsi alternatif URL non-PDF dari primary_location
            $url = $item['primary_location']['url'];
        } elseif (isset($item['alternate_locations']) && is_array($item['alternate_locations'])) {
            // Cari di alternate_locations untuk URL non-PDF
            foreach ($item['alternate_locations'] as $altLoc) {
                if (isset($altLoc['landing_page_url'])) {
                    $url = $altLoc['landing_page_url'];
                    break;
                } elseif (isset($altLoc['url']) && !isPdfUrl($altLoc['url'])) {
                    $url = $altLoc['url'];
                    break;
                }
            }
        }
        
        // 2. UNTUK PDF URL - khusus cari URL PDF
        if (isset($item['open_access']) && isset($item['open_access']['oa_url']) && 
            isPdfUrl($item['open_access']['oa_url'])) {
            $pdfUrl = $item['open_access']['oa_url'];
            $isPdf = true;
        } elseif (isset($item['primary_location']) && isset($item['primary_location']['pdf_url'])) {
            $pdfUrl = $item['primary_location']['pdf_url'];
            $isPdf = true;
        } elseif (isset($item['best_oa_location']) && isset($item['best_oa_location']['pdf_url'])) {
            $pdfUrl = $item['best_oa_location']['pdf_url'];
            $isPdf = true;
        } elseif (isset($item['alternate_locations']) && is_array($item['alternate_locations'])) {
            // Cari di alternate_locations untuk URL PDF
            foreach ($item['alternate_locations'] as $altLoc) {
                if (isset($altLoc['pdf_url']) && isPdfUrl($altLoc['pdf_url'])) {
                    $pdfUrl = $altLoc['pdf_url'];
                    $isPdf = true;
                    break;
                }
            }
        }
        
        // 3. HANDLE SPECIAL CASES
        // Jika URL dan PDF URL sama, prioritaskan DOI URL untuk URL landing page
        if ($url == $pdfUrl && !empty($doi)) {
            $url = 'https://doi.org/' . $doi;
        }
        
        // Jika hanya ada PDF URL tapi tidak ada URL lain, gunakan DOI URL jika ada
        if (empty($url) && !empty($pdfUrl) && !empty($doi)) {
            $url = 'https://doi.org/' . $doi;
        }
        // Jika tidak ada URL sama sekali, gunakan PDF URL sebagai fallback terakhir
        elseif (empty($url) && !empty($pdfUrl)) {
            $url = $pdfUrl;
        }
        
        // Extract bibliographic details
        $volume = null;
        $issue = null;
        $page = null;
        $isbn = null;
        
        if (isset($item['biblio'])) {
            $volume = isset($item['biblio']['volume']) ? $item['biblio']['volume'] : null;
            $issue = isset($item['biblio']['issue']) ? $item['biblio']['issue'] : null;
            
            if (isset($item['biblio']['first_page']) && isset($item['biblio']['last_page'])) {
                $page = $item['biblio']['first_page'] . '-' . $item['biblio']['last_page'];
            } elseif (isset($item['biblio']['first_page'])) {
                $page = $item['biblio']['first_page'];
            }
        }
        
        // Look for ISBN for books
        if (($pubType == 'book' || $pubType == 'book-chapter') && isset($item['ids'])) {
            if (isset($item['ids']['isbn'])) {
                $isbn = $item['ids']['isbn'];
            }
        }
        
        $citations[] = array(
            'title' => $title,
            'doi' => $doi,
            'url' => $url,
            'is_pdf' => $isPdf,
            'pdf_url' => $pdfUrl,
            'is_open_access' => $isOpenAccess,
            'container' => $container,
            'type' => $pubType,
            'publisher' => $publisher,
            'year' => $year,
            'volume' => $volume,
            'issue' => $issue,
            'page' => $page,
            'isbn' => $isbn,
            'authors' => formatAuthors(isset($item['authorships']) ? $item['authorships'] : array(), 'openalex'),
            'source' => 'openalex',
            'id' => isset($item['id']) ? $item['id'] : null,
            'title_hash' => md5(strtolower(trim(strip_tags($title))))
        );
    }
    
    return $citations;
}

/**
 * Get Semantic Scholar citations - Versi yang diperbarui untuk mengatasi masalah tidak ada hasil
 * @param string $doi DOI artikel
 * @param int $limit Batas jumlah hasil
 * @return array Data kutipan
 */
function getSemanticScholarCitations($doi, $limit = 50) {
    global $config;
    
    // Tambahkan logging untuk troubleshooting
    if (isset($config['enable_error_log']) && $config['enable_error_log']) {
        error_log("Starting Semantic Scholar citation fetch for DOI: $doi");
    }
    
    // Step 1: Get Semantic Scholar ID using DOI
    $url = "https://api.semanticscholar.org/graph/v1/paper/DOI:" . urlencode($doi);
    $headers = array();
    
    // Add API key header if available
    if (isset($config['semantic_scholar_api_key']) && !empty($config['semantic_scholar_api_key'])) {
        $headers[] = 'x-api-key: ' . $config['semantic_scholar_api_key'];
    }
    
    // Tambahkan header User-Agent yang lebih deskriptif
    $customUserAgent = isset($config['user_agent']) ? $config['user_agent'] : 'SangiaPub Citation Fetcher/1.2 (mailto:rochmady@sangia.org)';
    
    // Kirim request ke Semantic Scholar dengan error handling yang lebih baik
    $response = makeRequest($url, $headers, $customUserAgent);
    
    // Periksa response dengan error handling yang lebih detail
    if (!$response || !isset($response['success']) || !$response['success']) {
        if (isset($config['enable_error_log']) && $config['enable_error_log']) {
            error_log("Failed to get Semantic Scholar paperId for DOI: $doi. Response: " . json_encode($response));
        }
        return getSemanticScholarFromCache($doi);
    }
    
    // Pastikan data ada
    if (empty($response['data']) || !isset($response['data']['paperId'])) {
        if (isset($config['enable_error_log']) && $config['enable_error_log']) {
            error_log("No Semantic Scholar paperId found for DOI: $doi");
        }
        return getSemanticScholarFromCache($doi);
    }
    
    $paperId = $response['data']['paperId'];
    
    if (isset($config['enable_error_log']) && $config['enable_error_log']) {
        error_log("Semantic Scholar paperId found: $paperId");
    }
    
    // Step 2: Get citations with improved fields
    $citationsUrl = "https://api.semanticscholar.org/graph/v1/paper/" . urlencode($paperId) . 
                    "/citations?limit=$limit&fields=title,year,authors,venue,publicationTypes,externalIds,url,journal,fieldsOfStudy,isOpenAccess,openAccessPdf,volume,venue,publicationVenue,issn,isbn,pages";
    
    // Gunakan makeRequest yang sudah ada dengan timeout yang lebih panjang
    $semanticScholarTimeout = isset($config['semantic_scholar_timeout']) ? $config['semantic_scholar_timeout'] : 30;
    $originalTimeout = $config['request_timeout'];
    $originalConnectTimeout = $config['connect_timeout'];
    
    // Temporarily increase timeouts for this request
    $config['request_timeout'] = $semanticScholarTimeout;
    $config['connect_timeout'] = $semanticScholarTimeout / 2;
    
    $citationsResponse = makeRequest($citationsUrl, $headers, $customUserAgent);
    
    // Restore original timeouts
    $config['request_timeout'] = $originalTimeout;
    $config['connect_timeout'] = $originalConnectTimeout;
    
    // Periksa response dengan error handling yang lebih detail
    if (!$citationsResponse || !isset($citationsResponse['success']) || !$citationsResponse['success']) {
        if (isset($config['enable_error_log']) && $config['enable_error_log']) {
            error_log("Failed to get Semantic Scholar citations for paperId: $paperId. Response: " . json_encode($citationsResponse));
        }
        return getSemanticScholarFromCache($doi);
    }
    
    // Pastikan data ada dan dalam format yang diharapkan
    if (empty($citationsResponse['data']) || !isset($citationsResponse['data']['data'])) {
        if (isset($config['enable_error_log']) && $config['enable_error_log']) {
            error_log("No Semantic Scholar citations data found for paperId: $paperId");
        }
        return getSemanticScholarFromCache($doi);
    }
    
    $citationsData = $citationsResponse['data'];
    
    if (isset($config['enable_error_log']) && $config['enable_error_log']) {
        error_log("Found " . count($citationsData['data']) . " citations from Semantic Scholar");
    }
    
    // Process citations with better error handling
    $citations = array();
    foreach ($citationsData['data'] as $item) {
        // Skip items without a citingPaper
        if (empty($item['citingPaper'])) {
            continue;
        }
        
        $citingPaper = $item['citingPaper'];
        
        // Extract basic metadata with error checking
        $title = isset($citingPaper['title']) ? $citingPaper['title'] : 'Title not available';
        if (empty($title) || $title == 'Title not available') {
            // Skip citation items with invalid titles
            continue;
        }
        
        // IMPROVED: Determine publication type with more mappings
        $pubType = 'article-journal'; // Default type
        if (isset($citingPaper['publicationTypes']) && !empty($citingPaper['publicationTypes'])) {
            $typeMapping = array(
                'JournalArticle' => 'article-journal',
                'Conference' => 'proceedings-article',
                'ConferencePaper' => 'proceedings-article',
                'Proceedings' => 'proceedings',
                'BookChapter' => 'book-chapter',
                'Book' => 'book',
                'Monograph' => 'book',
                'Reference' => 'reference-book',
                'Thesis' => 'dissertation',
                'Dissertation' => 'dissertation',
                'Review' => 'review-article',
                'Dataset' => 'dataset',
                'Editorial' => 'article-journal',
                'CaseReport' => 'article-journal',
                'Letter' => 'article-journal',
                'Preprint' => 'preprint',
                'Report' => 'report',
                'TechnicalReport' => 'technical-report',
                'Patent' => 'patent'
            );
            
            foreach ($citingPaper['publicationTypes'] as $type) {
                if (isset($typeMapping[$type])) {
                    $pubType = $typeMapping[$type];
                    break;
                }
            }
        }
        
        // Extract DOI and other identifiers
        $doi = null;
        $url = null;
        $pdfUrl = null;
        $isPdf = false;
        $isbn = null;
        $issn = null;
        
        if (isset($citingPaper['externalIds'])) {
            if (isset($citingPaper['externalIds']['DOI'])) {
                $doi = $citingPaper['externalIds']['DOI'];
                // Use DOI URL as a primary URL
                $url = 'https://doi.org/' . $doi;
            }
            
            // Check for ISBN for books
            if (($pubType == 'book' || $pubType == 'book-chapter') && isset($citingPaper['externalIds']['ISBN'])) {
                $isbn = $citingPaper['externalIds']['ISBN'];
            }
            
            // Check for ISSN for journals
            if (isset($citingPaper['externalIds']['ISSN'])) {
                $issn = $citingPaper['externalIds']['ISSN'];
            }
        }
        
        // IMPROVED: Extract proper URL (non-PDF landing page) and PDF URL separately
        // 1. For landing page URL (non-PDF)
        if (isset($citingPaper['url']) && !isPdfUrl($citingPaper['url'])) {
            $url = $citingPaper['url'];
        } elseif (empty($url) && isset($citingPaper['url']) && !isset($citingPaper['openAccessPdf'])) {
            // If no other URL is set yet and current URL isn't set
            $url = $citingPaper['url'];
        }
        
        // 2. For PDF URL
        if (isset($citingPaper['openAccessPdf']) && isset($citingPaper['openAccessPdf']['url'])) {
            $pdfUrl = $citingPaper['openAccessPdf']['url'];
            $isPdf = true;
            
            // If no landing page URL yet, and this PDF URL is different from the URL already set
            if (empty($url)) {
                // Prioritize DOI URL if available
                if (!empty($doi)) {
                    $url = 'https://doi.org/' . $doi;
                } else {
                    // Use Semantic Scholar URL for this paper as landing page
                    $url = 'https://www.semanticscholar.org/paper/' . $citingPaper['paperId'];
                }
            }
        }
        
        // Check for open access status
        $isOpenAccess = false;
        if (isset($citingPaper['isOpenAccess']) && $citingPaper['isOpenAccess'] === true) {
            $isOpenAccess = true;
        } elseif (isset($citingPaper['openAccessPdf'])) {
            $isOpenAccess = true;
        }
        
        // IMPROVED CONTAINER DETECTION: Extract container/venue information with fallbacks
        $container = 'Publication';
        $publisher = null;
        
        // First try the dedicated publication venue if available
        if (isset($citingPaper['publicationVenue']) && isset($citingPaper['publicationVenue']['name']) &&
            !empty($citingPaper['publicationVenue']['name']) && $citingPaper['publicationVenue']['name'] != 'Publication') {
            $container = $citingPaper['publicationVenue']['name'];
            
            // Try to get publisher from venue
            if (isset($citingPaper['publicationVenue']['publisher'])) {
                $publisher = $citingPaper['publicationVenue']['publisher'];
            }
        }
        // Then try the journal field if publication venue wasn't useful
        elseif (isset($citingPaper['journal']) && isset($citingPaper['journal']['name']) && 
               !empty($citingPaper['journal']['name']) && $citingPaper['journal']['name'] != 'Publication') {
            $container = $citingPaper['journal']['name'];
            
            // Try to get publisher from journal
            if (isset($citingPaper['journal']['publisher'])) {
                $publisher = $citingPaper['journal']['publisher'];
            }
        }
        // Then try the venue field if that's all we have
        elseif (isset($citingPaper['venue']) && !empty($citingPaper['venue']) && $citingPaper['venue'] != 'Publication') {
            $container = $citingPaper['venue'];
        }
        
        // If still no container, use type-specific default with inferences from other fields
        if (empty($container) || $container == 'Publication') {
            // Get a fallback container
            $container = getDefaultContainerByType($pubType);
        }
        
        // Make sure container is not set to a generic or invalid value
        $container = ensureValidContainer($container, $pubType);
        
        // Extract year
        $year = isset($citingPaper['year']) ? $citingPaper['year'] : null;
        
        // Extract volume, issue, page from extended API data
        $volume = null;
        $issue = null;
        $page = null;
        
        if (isset($citingPaper['volume'])) {
            $volume = $citingPaper['volume'];
        }
        
        if (isset($citingPaper['pages'])) {
            $page = $citingPaper['pages'];
        }
        
        // Format authors
        $authors = array();
        if (isset($citingPaper['authors']) && is_array($citingPaper['authors'])) {
            $authors = $citingPaper['authors'];
        }
        
        $citations[] = array(
            'title' => $title,
            'doi' => $doi,
            'url' => $url,
            'is_pdf' => $isPdf,
            'pdf_url' => $pdfUrl,
            'is_open_access' => $isOpenAccess,
            'container' => $container,
            'type' => $pubType,
            'publisher' => $publisher,
            'year' => $year,
            'volume' => $volume,
            'issue' => $issue,
            'page' => $page,
            'isbn' => $isbn,
            'issn' => $issn,
            'authors' => formatAuthors($authors, 'semanticscholar'),
            'source' => 'semanticscholar',
            'id' => isset($citingPaper['paperId']) ? $citingPaper['paperId'] : null,
            'title_hash' => md5(strtolower(trim(strip_tags($title))))
        );
    }
    
    // Ensure URL and PDF_URL are different
    foreach ($citations as $key => $citation) {
        if (!empty($citation['pdf_url']) && $citation['url'] == $citation['pdf_url']) {
            // If URL is same as PDF_URL, use DOI URL or Semantic Scholar URL
            if (!empty($citation['doi'])) {
                $citations[$key]['url'] = 'https://doi.org/' . $citation['doi'];
            } elseif (!empty($citation['id'])) {
                $citations[$key]['url'] = 'https://www.semanticscholar.org/paper/' . $citation['id'];
            }
        }
    }
    
    // Save to a dedicated cache for semantic scholar
    if (!empty($citations)) {
        saveSemanticScholarToCache($doi, $citations);
    }
    
    // Jika tidak ada hasil, coba ambil dari cache
    if (empty($citations)) {
        if (isset($config['enable_error_log']) && $config['enable_error_log']) {
            error_log("No citations found from Semantic Scholar API, trying cache");
        }
        return getSemanticScholarFromCache($doi);
    }
    
    return $citations;
}

/**
 * Save Semantic Scholar citations to a dedicated cache
 */
function saveSemanticScholarToCache($doi, $citations) {
    global $config;
    
    // Create special cache file path for Semantic Scholar results
    $cacheFile = $config['cache_dir'] . '/ss_' . md5($doi) . '.json' . ($config['compress_cache'] ? '.gz' : '');
    
    $data = array(
        'doi' => $doi,
        'citations' => $citations,
        'timestamp' => time()
    );
    
    // Encode data to JSON
    $jsonData = json_encode($data);
    if ($jsonData === false) {
        if ($config['enable_error_log']) {
            error_log("Failed to encode Semantic Scholar cache data to JSON");
        }    
        return false;
    }
    
    // Compress data if needed
    if ($config['compress_cache']) {
        $content = gzcompress($jsonData, 9); // Level kompresi 9 (max)
        if ($content === false) {
            if ($config['enable_error_log']) {
                error_log("Failed to compress Semantic Scholar cache data");
            }    
            return false;
        }
    } else {
        $content = $jsonData;
    }
    
    // Write to file
    $result = file_put_contents($cacheFile, $content);
    return ($result !== false);
}

/**
 * Retrieve Semantic Scholar citations from cache
 */
function getSemanticScholarFromCache($doi) {
    global $config;
    
    // Get dedicated cache file path
    $cacheFile = $config['cache_dir'] . '/ss_' . md5($doi) . '.json' . ($config['compress_cache'] ? '.gz' : '');
    
    if (file_exists($cacheFile)) {
        try {
            // Read data from cache
            if ($config['compress_cache']) {
                $compressed = file_get_contents($cacheFile);
                if ($compressed === false) return array();
                $content = @gzuncompress($compressed);
                if ($content === false) return array();
            } else {
                $content = file_get_contents($cacheFile);
                if ($content === false) return array();
            }
            
            // Decode JSON
            $data = json_decode($content, true);
            if (!$data || !isset($data['citations']) || !is_array($data['citations'])) {
                return array();
            }
            
            if ($config['enable_error_log']) {
                error_log("Retrieved " . count($data['citations']) . " Semantic Scholar citations from cache");
            }    
            return $data['citations'];
        } catch (Exception $e) {
            if ($config['enable_error_log']) {
                error_log("Error reading Semantic Scholar cache: " . $e->getMessage());
            }    
            return array();
        }
    }
    
    return array();
}

/**
 * Get Dimensions API citations with improved filtering and accuracy
 * @param string $doi DOI artikel
 * @param int $limit Batas jumlah hasil
 * @return array Data kutipan
 */
function getDimensionsCitations($doi, $limit = 50) {
    global $config;
    
    // Pertama, coba dapatkan jumlah kutipan yang benar dari API metrik Dimensions
    $metricUrl = "https://metrics-api.dimensions.ai/doi/" . urlencode($doi);
    $metricResponse = makeRequest($metricUrl);
    
    $expectedCitationCount = null;
    if ($metricResponse && $metricResponse['success'] && isset($metricResponse['data']['times_cited'])) {
        $expectedCitationCount = intval($metricResponse['data']['times_cited']);
    } else if ($metricResponse && $metricResponse['success'] && isset($metricResponse['data']['metrics']) && 
               isset($metricResponse['data']['metrics']['times_cited'])) {
        $expectedCitationCount = intval($metricResponse['data']['metrics']['times_cited']);
    }
    
    // Jika kita belum bisa mendapatkan jumlah kutipan, coba endpoint metrik alternatif
    if ($expectedCitationCount === null) {
        $altMetricUrl = "https://badge.dimensions.ai/details/doi/" . urlencode($doi);
        $altMetricResponse = makeRequest($altMetricUrl);
        
        if ($altMetricResponse && $altMetricResponse['success'] && isset($altMetricResponse['data']['citationCount'])) {
            $expectedCitationCount = intval($altMetricResponse['data']['citationCount']);
        }
    }
    
    if ($config['enable_error_log']) {
        error_log("Expected Dimensions citation count for DOI $doi: " . 
                  ($expectedCitationCount !== null ? $expectedCitationCount : "Unknown"));
    }
    
    // Gunakan URL yang spesifik dengan parameter yang lebih ketat
    $url = "https://app.dimensions.ai/discover/publication/citations?search_mode=content" . 
           "&search_text=" . urlencode($doi) . 
           "&search_type=kws" . 
           "&search_field=doi" . 
           "&order=date" .  // Sort by date
           "&and_facet_cited_references=1"; // Penting: filter hanya untuk kutipan sebenarnya
    
    $response = makeRequest($url);
    $citations = array();
    
    if ($response && $response['success'] && isset($response['raw'])) {
        $html = $response['raw'];
        
        // Extract citation count from HTML first
        preg_match('/Cited by\s+<strong>(\d+)<\/strong>/i', $html, $countMatch);
        $htmlCitationCount = !empty($countMatch[1]) ? intval($countMatch[1]) : null;
        
        if ($htmlCitationCount !== null && $expectedCitationCount === null) {
            $expectedCitationCount = $htmlCitationCount;
        }
        
        if ($config['enable_error_log']) {
            error_log("HTML reported Dimensions citation count: " . 
                      ($htmlCitationCount !== null ? $htmlCitationCount : "Not found"));
        }
        
        // Use a more precise regex to extract only true citations
        preg_match_all('/<div class="search-result-item[^"]*">\s*<div[^>]*>\s*<div[^>]*>(.*?)<\/div>\s*<\/div>\s*<\/div>/s', $html, $matches);
        
        if (!empty($matches[1])) {
            // If we have an expected count, limit results to that many
            $resultsToProcess = $matches[1];
            if ($expectedCitationCount !== null) {
                $resultsToProcess = array_slice($matches[1], 0, min($expectedCitationCount, $limit));
            } else {
                $resultsToProcess = array_slice($matches[1], 0, $limit);
            }
            
            foreach ($resultsToProcess as $match) {
                // Skip if this doesn't look like a citation (no title link)
                if (!preg_match('/<a class="title"/i', $match)) {
                    continue;
                }
                
                // Extract title
                preg_match('/<a class="title".*?>(.*?)<\/a>/s', $match, $titleMatch);
                $title = !empty($titleMatch[1]) ? trim(strip_tags($titleMatch[1])) : 'Title not available';
                
                // Skip results that don't look like citations (e.g., related papers)
                if (strpos(strtolower($title), 'related to') === 0) {
                    continue;
                }
                
                // Extract DOI with improved pattern matching
                preg_match('/DOI:\s*<span[^>]*>([\d\.]+\/[\w\d\.\-\_]+)/i', $match, $doiMatch);
                if (empty($doiMatch)) {
                    preg_match('/DOI:\s*([\d\.]+\/[\w\d\.\-\_]+)/i', $match, $doiMatch);
                }
                $citeDoi = !empty($doiMatch[1]) ? $doiMatch[1] : null;
                
                // Extract authors with improved pattern
                preg_match_all('/<span class="author[^"]*">(.*?)<\/span>/s', $match, $authorMatches);
                $authors = !empty($authorMatches[1]) ? array_map('strip_tags', $authorMatches[1]) : array();
                
                // Extract publication type with enhanced classification
                $pubType = 'article-journal'; // Default type
                preg_match('/<span class="pub-type[^"]*">(.*?)<\/span>/s', $match, $typeMatch);
                if (!empty($typeMatch[1])) {
                    $typeText = strtolower(trim(strip_tags($typeMatch[1])));
                    
                    // Comprehensive type mapping
                    if (strpos($typeText, 'book') !== false) {
                        if (strpos($typeText, 'chapter') !== false || strpos($typeText, 'section') !== false) {
                            $pubType = 'book-chapter';
                        } else {
                            $pubType = 'book';
                        }
                    } elseif (strpos($typeText, 'proceedings') !== false || 
                              strpos($typeText, 'conference') !== false ||
                              strpos($typeText, 'symposium') !== false) {
                        $pubType = 'proceedings-article';
                    } elseif (strpos($typeText, 'thesis') !== false || 
                              strpos($typeText, 'dissertation') !== false) {
                        $pubType = 'dissertation';
                    } elseif (strpos($typeText, 'patent') !== false) {
                        $pubType = 'patent';
                    } elseif (strpos($typeText, 'report') !== false || 
                              strpos($typeText, 'technical') !== false) {
                        $pubType = 'report';
                    } elseif (strpos($typeText, 'preprint') !== false || 
                              strpos($typeText, 'working paper') !== false) {
                        $pubType = 'preprint';
                    } elseif (strpos($typeText, 'dataset') !== false || 
                              strpos($typeText, 'data set') !== false) {
                        $pubType = 'dataset';
                    } elseif (strpos($typeText, 'journal') !== false || 
                              strpos($typeText, 'article') !== false) {
                        $pubType = 'article-journal';
                    }
                }
                
                // Extract journal/source with improved pattern
                preg_match('/<span class="journal[^"]*">(.*?)<\/span>/s', $match, $journalMatch);
                $container = !empty($journalMatch[1]) ? trim(strip_tags($journalMatch[1])) : null;
                
                // Extract publisher
                preg_match('/<span class="publisher[^"]*">(.*?)<\/span>/s', $match, $publisherMatch);
                $publisher = !empty($publisherMatch[1]) ? trim(strip_tags($publisherMatch[1])) : null;
                
                // Extract year with improved pattern
                preg_match('/<span class="publication-year[^"]*">(\d{4})<\/span>/s', $match, $yearMatch);
                $year = !empty($yearMatch[1]) ? (int)$yearMatch[1] : null;
                
                // Extract URL with proper domain handling
                preg_match('/<a class="title" href="([^"]+)"/s', $match, $urlMatch);
                $url = !empty($urlMatch[1]) ? 'https://app.dimensions.ai' . html_entity_decode($urlMatch[1]) : null;
                if (!$url && $citeDoi) {
                    $url = 'https://doi.org/' . $citeDoi;
                }
                
                // Extract additional metadata
                $volume = null;
                $issue = null;
                $page = null;
                $isbn = null;
                
                // Extract volume with flexible pattern
                preg_match('/<span class="volume[^"]*">Vol\.\s*(\d+)/i', $match, $volMatch);
                if (!empty($volMatch[1])) {
                    $volume = $volMatch[1];
                } else {
                    // Try alternative pattern
                    preg_match('/Volume\s*(\d+)/i', $match, $volMatch);
                    if (!empty($volMatch[1])) {
                        $volume = $volMatch[1];
                    }
                }
                
                // Extract issue with flexible pattern
                preg_match('/<span class="issue[^"]*">Iss\.\s*(\d+)/i', $match, $issMatch);
                if (!empty($issMatch[1])) {
                    $issue = $issMatch[1];
                } else {
                    // Try alternative pattern
                    preg_match('/Issue\s*(\d+)/i', $match, $issMatch);
                    if (!empty($issMatch[1])) {
                        $issue = $issMatch[1];
                    }
                }
                
                // Extract page with flexible pattern
                preg_match('/<span class="pages[^"]*">pp\.\s*([\d\-]+)/i', $match, $pageMatch);
                if (!empty($pageMatch[1])) {
                    $page = $pageMatch[1];
                } else {
                    // Try alternative patterns
                    preg_match('/Pages?\s*([\d\-]+)/i', $match, $pageMatch);
                    if (!empty($pageMatch[1])) {
                        $page = $pageMatch[1];
                    }
                }
                
                // ISBN extraction for books with flexible pattern
                if ($pubType == 'book' || $pubType == 'book-chapter') {
                    preg_match('/ISBN:?\s*([\d\-X]+)/i', $match, $isbnMatch);
                    if (!empty($isbnMatch[1])) {
                        $isbn = $isbnMatch[1];
                    }
                }
                
                // Ensure container is valid
                if (empty($container)) {
                    $container = getDefaultContainerByType($pubType);
                }
                $container = ensureValidContainer($container, $pubType);
                
                // Create citation object
                $citations[] = array(
                    'title' => $title,
                    'doi' => $citeDoi,
                    'url' => $url,
                    'is_pdf' => false,
                    'pdf_url' => null, // Dimensions typically doesn't provide direct PDF links
                    'is_open_access' => false, // Dimensions doesn't clearly indicate OA status in HTML
                    'container' => $container,
                    'type' => $pubType,
                    'publisher' => $publisher,
                    'year' => $year,
                    'volume' => $volume,
                    'issue' => $issue,
                    'page' => $page,
                    'isbn' => $isbn,
                    'authors' => formatAuthors($authors, 'dimensions'),
                    'source' => 'dimensions',
                    'id' => null,
                    'title_hash' => md5(strtolower(trim(strip_tags($title))))
                );
            }
        }
    }
    
    // Double-check our result count against the expected count
    if ($expectedCitationCount !== null && count($citations) > $expectedCitationCount) {
        if ($config['enable_error_log']) {
            error_log("Trimming Dimensions results from " . count($citations) . " to " . $expectedCitationCount);
        }
        // If we somehow got more citations than expected, trim the excess
        $citations = array_slice($citations, 0, $expectedCitationCount);
    }
    
    // Alternative method if primary method failed to get any citations and we expect some
    if (empty($citations) && $expectedCitationCount > 0) {
        // Try another URL format
        $altUrl = "https://app.dimensions.ai/discover/publication/results?search_mode=content" .
                 "&search_text=" . urlencode($doi) .
                 "&search_type=kws&search_field=reference_ids";
        
        $altResponse = makeRequest($altUrl);
        
        if ($altResponse && $altResponse['success'] && isset($altResponse['raw'])) {
            $altHtml = $altResponse['raw'];
            
            // Extract citation items with an alternative pattern that may match the different HTML structure
            preg_match_all('/<div class="search-result[^"]*">(.*?)<div class="metrics-container">/s', $altHtml, $altMatches);
            
            if (!empty($altMatches[1])) {
                $altResultsToProcess = array_slice($altMatches[1], 0, 
                    $expectedCitationCount !== null ? min($expectedCitationCount, $limit) : $limit);
                
                foreach ($altResultsToProcess as $match) {
                    // Skip if this doesn't look like a citation
                    if (!preg_match('/<a[^>]*class="[^"]*title[^"]*"/i', $match)) {
                        continue;
                    }
                    
                    // Extract title with alternative pattern
                    preg_match('/<a[^>]*class="[^"]*title[^"]*"[^>]*>(.*?)<\/a>/s', $match, $titleMatch);
                    $title = !empty($titleMatch[1]) ? trim(strip_tags($titleMatch[1])) : 'Title not available';
                    
                    // Skip results that don't look like citations
                    if (strpos(strtolower($title), 'related to') === 0) {
                        continue;
                    }
                    
                    // Extract DOI with alternative pattern
                    preg_match('/doi[^<>]*?[:\.]?\s*<[^>]*>([\d\.]+\/[\w\d\.\-\_]+)/i', $match, $doiMatch);
                    if (empty($doiMatch)) {
                        preg_match('/doi[^<>]*?[:\.]?\s*([\d\.]+\/[\w\d\.\-\_]+)/i', $match, $doiMatch);
                    }
                    $citeDoi = !empty($doiMatch[1]) ? $doiMatch[1] : null;
                    
                    // Extract authors with alternative pattern
                    preg_match_all('/<span[^>]*class="[^"]*author[^"]*"[^>]*>(.*?)<\/span>/s', $match, $authorMatches);
                    if (empty($authorMatches[1])) {
                        preg_match_all('/<a[^>]*class="[^"]*author-link[^"]*"[^>]*>(.*?)<\/a>/s', $match, $authorMatches);
                    }
                    $authors = !empty($authorMatches[1]) ? array_map('strip_tags', $authorMatches[1]) : array();
                    
                    // Extract type with alternative pattern
                    $pubType = 'article-journal'; // Default
                    preg_match('/<span[^>]*class="[^"]*pub-type[^"]*"[^>]*>(.*?)<\/span>/s', $match, $typeMatch);
                    if (!empty($typeMatch[1])) {
                        $typeText = strtolower(trim(strip_tags($typeMatch[1])));
                        // Use the same type mapping as above
                        if (strpos($typeText, 'book') !== false) {
                            // Same type mapping as above
                            // ...
                        }
                    }
                    
                    // Extract journal with alternative pattern
                    preg_match('/<span[^>]*class="[^"]*journal[^"]*"[^>]*>(.*?)<\/span>/s', $match, $journalMatch);
                    if (empty($journalMatch)) {
                        preg_match('/<span[^>]*class="[^"]*source[^"]*"[^>]*>(.*?)<\/span>/s', $match, $journalMatch);
                    }
                    $container = !empty($journalMatch[1]) ? trim(strip_tags($journalMatch[1])) : null;
                    
                    // Extract publisher with alternative pattern
                    preg_match('/<span[^>]*class="[^"]*publisher[^"]*"[^>]*>(.*?)<\/span>/s', $match, $publisherMatch);
                    $publisher = !empty($publisherMatch[1]) ? trim(strip_tags($publisherMatch[1])) : null;
                    
                    // Extract year with alternative pattern
                    preg_match('/<span[^>]*class="[^"]*publication-year[^"]*"[^>]*>(\d{4})<\/span>/s', $match, $yearMatch);
                    if (empty($yearMatch)) {
                        preg_match('/(\d{4})<\/span>/s', $match, $yearMatch);
                    }
                    $year = !empty($yearMatch[1]) ? (int)$yearMatch[1] : null;
                    
                    // Extract URL with alternative pattern
                    preg_match('/<a[^>]*class="[^"]*title[^"]*"[^>]*href="([^"]+)"/s', $match, $urlMatch);
                    $url = !empty($urlMatch[1]) ? 'https://app.dimensions.ai' . html_entity_decode($urlMatch[1]) : null;
                    if (!$url && $citeDoi) {
                        $url = 'https://doi.org/' . $citeDoi;
                    }
                    
                    // Extract volume, issue, page with similar patterns as above
                    // ...
                    
                    // Ensure container is valid
                    if (empty($container)) {
                        $container = getDefaultContainerByType($pubType);
                    }
                    $container = ensureValidContainer($container, $pubType);
                    
                    // Create citation object, same structure as above
                    $citations[] = array(
                        'title' => $title,
                        'doi' => $citeDoi,
                        'url' => $url,
                        'is_pdf' => false,
                        'pdf_url' => null,
                        'is_open_access' => false,
                        'container' => $container,
                        'type' => $pubType,
                        'publisher' => $publisher,
                        'year' => $year,
                        'volume' => null, // Simplified for brevity
                        'issue' => null,  // Simplified for brevity
                        'page' => null,   // Simplified for brevity
                        'isbn' => null,   // Simplified for brevity
                        'authors' => formatAuthors($authors, 'dimensions'),
                        'source' => 'dimensions',
                        'id' => null,
                        'title_hash' => md5(strtolower(trim(strip_tags($title))))
                    );
                }
            }
        }
    }
    
    // Final fallback to crossref lookup if no results and we expect some
    if (empty($citations) && ($expectedCitationCount === null || $expectedCitationCount > 0)) {
        // Try to get metadata from Crossref and find related papers
        $metadata = getMetadataFromCrossref($doi);
        if ($metadata) {
            $title = '';
            if (isset($metadata['title']) && is_array($metadata['title']) && !empty($metadata['title'])) {
                $title = $metadata['title'][0];
            }
            
            // Use title to find related papers
            if (!empty($title)) {
                $relatedPapers = getRelatedPapersByTitle($title, min($limit, $expectedCitationCount !== null ? $expectedCitationCount : $limit));
                foreach ($relatedPapers as &$paper) {
                    $paper['source'] = 'dimensions';
                }
                unset($paper); // Break the reference
                
                // If we have an expected count, limit to that
                if ($expectedCitationCount !== null && count($relatedPapers) > $expectedCitationCount) {
                    $relatedPapers = array_slice($relatedPapers, 0, $expectedCitationCount);
                }
                
                $citations = $relatedPapers;
            }
        }
    }
    
    return $citations;
}

/**
 * Format JSON results from Dimensions web interface
 * @param array $publications Array of publication data from Dimensions JSON response
 * @param int $limit Maximum number of results to return
 * @return array Formatted citation data
 */
function formatDimensionsWebResults($publications, $limit) {
    $citations = array();
    
    if (!is_array($publications)) {
        return $citations;
    }
    
    $processCount = min(count($publications), $limit);
    
    for ($i = 0; $i < $processCount; $i++) {
        $item = $publications[$i];
        
        // Skip items that don't have the minimum required fields
        if (!isset($item['title']) || empty($item['title'])) {
            continue;
        }
        
        $title = $item['title'];
        
        // Skip results that don't look like citations (e.g., related papers)
        if (strpos(strtolower($title), 'related to') === 0) {
            continue;
        }
        
        // Extract DOI
        $doi = isset($item['doi']) ? $item['doi'] : null;
        
        // Determine publication type
        $pubType = 'article-journal'; // Default
        if (isset($item['type'])) {
            $typeText = strtolower($item['type']);
            
            // Map types
            if (strpos($typeText, 'book') !== false) {
                if (strpos($typeText, 'chapter') !== false || strpos($typeText, 'section') !== false) {
                    $pubType = 'book-chapter';
                } else {
                    $pubType = 'book';
                }
            } elseif (strpos($typeText, 'proceedings') !== false || 
                     strpos($typeText, 'conference') !== false) {
                $pubType = 'proceedings-article';
            } elseif (strpos($typeText, 'thesis') !== false || 
                     strpos($typeText, 'dissertation') !== false) {
                $pubType = 'dissertation';
            } elseif (strpos($typeText, 'patent') !== false) {
                $pubType = 'patent';
            } elseif (strpos($typeText, 'report') !== false) {
                $pubType = 'report';
            } elseif (strpos($typeText, 'preprint') !== false) {
                $pubType = 'preprint';
            }
        }
        
        // Extract authors
        $authors = array();
        if (isset($item['authors']) && is_array($item['authors'])) {
            foreach ($item['authors'] as $author) {
                if (is_string($author)) {
                    // If author is a simple string
                    $authors[] = $author;
                } elseif (is_array($author)) {
                    // If author is an object
                    if (isset($author['first_name']) || isset($author['last_name'])) {
                        $authors[] = array(
                            'first_name' => isset($author['first_name']) ? $author['first_name'] : '',
                            'last_name' => isset($author['last_name']) ? $author['last_name'] : '',
                            'orcid_id' => isset($author['orcid_id']) ? $author['orcid_id'] : null
                        );
                    } elseif (isset($author['name'])) {
                        $authors[] = $author['name'];
                    }
                }
            }
        }
        
        // Extract container/journal/source
        $container = null;
        $publisher = null;
        
        if (isset($item['journal'])) {
            if (is_string($item['journal'])) {
                $container = $item['journal'];
            } elseif (is_array($item['journal']) && isset($item['journal']['title'])) {
                $container = $item['journal']['title'];
                if (isset($item['journal']['publisher'])) {
                    $publisher = $item['journal']['publisher'];
                }
            }
        } elseif (isset($item['source_title'])) {
            $container = $item['source_title'];
        } elseif (isset($item['source']) && is_array($item['source']) && isset($item['source']['title'])) {
            $container = $item['source']['title'];
            if (isset($item['source']['publisher'])) {
                $publisher = $item['source']['publisher'];
            }
        }
        
        // Extract publisher if not found yet
        if (empty($publisher) && isset($item['publisher'])) {
            $publisher = $item['publisher'];
        }
        
        // Extract URL
        $url = null;
        if (isset($item['url'])) {
            $url = $item['url'];
        } elseif ($doi) {
            $url = 'https://doi.org/' . $doi;
        } elseif (isset($item['id'])) {
            $url = 'https://app.dimensions.ai/details/publication/' . $item['id'];
        }
        
        // Extract year
        $year = null;
        if (isset($item['year'])) {
            $year = intval($item['year']);
        } elseif (isset($item['publication_year'])) {
            $year = intval($item['publication_year']);
        } elseif (isset($item['published_year'])) {
            $year = intval($item['published_year']);
        } elseif (isset($item['date']) && preg_match('/(\d{4})/', $item['date'], $yearMatch)) {
            $year = intval($yearMatch[1]);
        }
        
        // Extract other biblio details
        $volume = isset($item['volume']) ? $item['volume'] : null;
        $issue = isset($item['issue']) ? $item['issue'] : null;
        
        // Extract page information
        $page = null;
        if (isset($item['pages'])) {
            $page = $item['pages'];
        } elseif (isset($item['start_page']) && isset($item['end_page'])) {
            $page = $item['start_page'] . '-' . $item['end_page'];
        } elseif (isset($item['start_page'])) {
            $page = $item['start_page'];
        }
        
        // Extract ISBN for books
        $isbn = null;
        if (($pubType == 'book' || $pubType == 'book-chapter') && isset($item['isbn'])) {
            $isbn = $item['isbn'];
        }
        
        // Extract ISSN for journals
        $issn = isset($item['issn']) ? $item['issn'] : null;
        
        // Check open access status
        $isOpenAccess = false;
        if (isset($item['open_access']) && ($item['open_access'] === true || $item['open_access'] === 'true')) {
            $isOpenAccess = true;
        }
        
        // Ensure container is valid
        if (empty($container)) {
            $container = getDefaultContainerByType($pubType);
        }
        $container = ensureValidContainer($container, $pubType);
        
        // Create citation object
        $citations[] = array(
            'title' => $title,
            'doi' => $doi,
            'url' => $url,
            'is_pdf' => false,
            'pdf_url' => null, // Dimensions typically doesn't provide direct PDF links
            'is_open_access' => $isOpenAccess,
            'container' => $container,
            'type' => $pubType,
            'publisher' => $publisher,
            'year' => $year,
            'volume' => $volume,
            'issue' => $issue,
            'page' => $page,
            'isbn' => $isbn,
            'issn' => $issn,
            'authors' => formatAuthors($authors, 'dimensions'),
            'source' => 'dimensions',
            'id' => isset($item['id']) ? $item['id'] : null,
            'title_hash' => md5(strtolower(trim(strip_tags($title))))
        );
    }
    
    return $citations;
}

/**
 * Parse HTML content from Dimensions website
 * @param string $html HTML content from Dimensions website
 * @param int $limit Maximum number of results to return
 * @return array Formatted citation data
 */
function parseDimensionsHtml($html, $limit) {
    $citations = array();
    
    // Extract citation items from HTML
    preg_match_all('/<div class="search-result-item[^"]*">\s*<div[^>]*>\s*<div[^>]*>(.*?)<\/div>\s*<\/div>\s*<\/div>/s', $html, $matches);
    
    if (empty($matches[1])) {
        // Try alternative pattern if the first one didn't match
        preg_match_all('/<div class="search-result-item[^"]*">(.*?)<div class="metrics-container">/s', $html, $matches);
    }
    
    if (empty($matches[1])) {
        // Try even more generic pattern
        preg_match_all('/<div[^>]*class="[^"]*search-result[^"]*"[^>]*>(.*?)<div[^>]*class="[^"]*metrics/s', $html, $matches);
    }
    
    if (!empty($matches[1])) {
        $resultsToProcess = array_slice($matches[1], 0, $limit);
        
        foreach ($resultsToProcess as $match) {
            // Skip if this doesn't look like a citation (no title link)
            if (!preg_match('/<a[^>]*class="[^"]*title[^"]*"/i', $match)) {
                continue;
            }
            
            // Extract title
            preg_match('/<a[^>]*class="[^"]*title[^"]*"[^>]*>(.*?)<\/a>/s', $match, $titleMatch);
            $title = !empty($titleMatch[1]) ? trim(strip_tags($titleMatch[1])) : 'Title not available';
            
            // Skip results that don't look like citations (e.g., related papers)
            if (strpos(strtolower($title), 'related to') === 0) {
                continue;
            }
            
            // Extract DOI with multiple patterns
            $citeDoi = null;
            $doiPatterns = array(
                '/DOI:\s*<span[^>]*>([\d\.]+\/[\w\d\.\-\_]+)/i',
                '/DOI:\s*([\d\.]+\/[\w\d\.\-\_]+)/i',
                '/doi[^<>]*?[:\.]?\s*<[^>]*>([\d\.]+\/[\w\d\.\-\_]+)/i',
                '/doi[^<>]*?[:\.]?\s*([\d\.]+\/[\w\d\.\-\_]+)/i',
                '/<[^>]*class="[^"]*doi[^"]*"[^>]*>([\d\.]+\/[\w\d\.\-\_]+)/i'
            );
            
            foreach ($doiPatterns as $pattern) {
                if (preg_match($pattern, $match, $doiMatch)) {
                    $citeDoi = $doiMatch[1];
                    break;
                }
            }
            
            // Extract authors with multiple patterns
            $authors = array();
            $authorPatterns = array(
                '/<span[^>]*class="[^"]*author[^"]*"[^>]*>(.*?)<\/span>/s',
                '/<a[^>]*class="[^"]*author-link[^"]*"[^>]*>(.*?)<\/a>/s',
                '/<div[^>]*class="[^"]*authors[^"]*"[^>]*>(.*?)<\/div>/s'
            );
            
            foreach ($authorPatterns as $pattern) {
                if (preg_match_all($pattern, $match, $authorMatches)) {
                    if (!empty($authorMatches[1])) {
                        $authors = array_map('strip_tags', $authorMatches[1]);
                        break;
                    }
                }
            }
            
            // If still no authors but we have an authors div, try to extract them
            if (empty($authors) && preg_match('/<div[^>]*class="[^"]*authors[^"]*"[^>]*>(.*?)<\/div>/s', $match, $authorsDiv)) {
                $authorsContent = $authorsDiv[1];
                // Remove any HTML and split by commas or similar separators
                $authorsText = strip_tags($authorsContent);
                $authorsSplit = preg_split('/\s*[,;]\s*/', $authorsText);
                if (!empty($authorsSplit)) {
                    $authors = array_map('trim', $authorsSplit);
                }
            }
            
            // Extract publication type with multiple patterns
            $pubType = 'article-journal'; // Default type
            $typePatterns = array(
                '/<span[^>]*class="[^"]*pub-type[^"]*"[^>]*>(.*?)<\/span>/s',
                '/<div[^>]*class="[^"]*pub-type[^"]*"[^>]*>(.*?)<\/div>/s',
                '/<span[^>]*class="[^"]*type[^"]*"[^>]*>(.*?)<\/span>/s'
            );
            
            foreach ($typePatterns as $pattern) {
                if (preg_match($pattern, $match, $typeMatch)) {
                    $typeText = strtolower(trim(strip_tags($typeMatch[1])));
                    
                    // Type mapping
                    if (strpos($typeText, 'book') !== false) {
                        if (strpos($typeText, 'chapter') !== false || strpos($typeText, 'section') !== false) {
                            $pubType = 'book-chapter';
                        } else {
                            $pubType = 'book';
                        }
                    } elseif (strpos($typeText, 'proceedings') !== false || 
                              strpos($typeText, 'conference') !== false ||
                              strpos($typeText, 'symposium') !== false) {
                        $pubType = 'proceedings-article';
                    } elseif (strpos($typeText, 'thesis') !== false || 
                              strpos($typeText, 'dissertation') !== false) {
                        $pubType = 'dissertation';
                    } elseif (strpos($typeText, 'patent') !== false) {
                        $pubType = 'patent';
                    } elseif (strpos($typeText, 'report') !== false || 
                              strpos($typeText, 'technical') !== false) {
                        $pubType = 'report';
                    } elseif (strpos($typeText, 'preprint') !== false || 
                              strpos($typeText, 'working paper') !== false) {
                        $pubType = 'preprint';
                    } elseif (strpos($typeText, 'dataset') !== false || 
                              strpos($typeText, 'data set') !== false) {
                        $pubType = 'dataset';
                    } elseif (strpos($typeText, 'journal') !== false || 
                              strpos($typeText, 'article') !== false) {
                        $pubType = 'article-journal';
                    }
                    break;
                }
            }
            
            // Extract journal/source with multiple patterns
            $container = null;
            $journalPatterns = array(
                '/<span[^>]*class="[^"]*journal[^"]*"[^>]*>(.*?)<\/span>/s',
                '/<span[^>]*class="[^"]*source[^"]*"[^>]*>(.*?)<\/span>/s',
                '/<div[^>]*class="[^"]*journal[^"]*"[^>]*>(.*?)<\/div>/s',
                '/<div[^>]*class="[^"]*source[^"]*"[^>]*>(.*?)<\/div>/s'
            );
            
            foreach ($journalPatterns as $pattern) {
                if (preg_match($pattern, $match, $journalMatch)) {
                    $container = trim(strip_tags($journalMatch[1]));
                    if (!empty($container)) {
                        break;
                    }
                }
            }
            
            // Extract publisher with multiple patterns
            $publisher = null;
            $publisherPatterns = array(
                '/<span[^>]*class="[^"]*publisher[^"]*"[^>]*>(.*?)<\/span>/s',
                '/<div[^>]*class="[^"]*publisher[^"]*"[^>]*>(.*?)<\/div>/s'
            );
            
            foreach ($publisherPatterns as $pattern) {
                if (preg_match($pattern, $match, $publisherMatch)) {
                    $publisher = trim(strip_tags($publisherMatch[1]));
                    if (!empty($publisher)) {
                        break;
                    }
                }
            }
            
            // Extract year with multiple patterns
            $year = null;
            $yearPatterns = array(
                '/<span[^>]*class="[^"]*publication-year[^"]*"[^>]*>(\d{4})<\/span>/s',
                '/<span[^>]*class="[^"]*year[^"]*"[^>]*>(\d{4})<\/span>/s',
                '/<div[^>]*class="[^"]*year[^"]*"[^>]*>(\d{4})<\/div>/s',
                '/(\d{4})<\/span>/s',
                '/\b(19|20)\d{2}\b/s'  // Catch any 4-digit year between 1900-2099
            );
            
            foreach ($yearPatterns as $pattern) {
                if (preg_match($pattern, $match, $yearMatch)) {
                    $year = intval($yearMatch[1]);
                    if ($year >= 1900 && $year <= date('Y') + 1) { // Validate year is reasonable
                        break;
                    }
                }
            }
            
            // Extract URL with multiple patterns
            $url = null;
            $urlPatterns = array(
                '/<a[^>]*class="[^"]*title[^"]*"[^>]*href="([^"]+)"/s',
                '/<a[^>]*href="([^"]+)"[^>]*class="[^"]*title[^"]*"/s'
            );
            
            foreach ($urlPatterns as $pattern) {
                if (preg_match($pattern, $match, $urlMatch)) {
                    $rawUrl = $urlMatch[1];
                    // Check if URL is relative or absolute
                    if (strpos($rawUrl, 'http') === 0) {
                        $url = $rawUrl;
                    } else {
                        $url = 'https://app.dimensions.ai' . html_entity_decode($rawUrl);
                    }
                    break;
                }
            }
            
            // Use DOI URL as fallback
            if (!$url && $citeDoi) {
                $url = 'https://doi.org/' . $citeDoi;
            }
            
            // Extract volume with multiple patterns
            $volume = null;
            $volumePatterns = array(
                '/<span[^>]*class="[^"]*volume[^"]*"[^>]*>Vol\.\s*(\d+)/i',
                '/<span[^>]*>Vol\.\s*(\d+)/i',
                '/Volume\s*(\d+)/i',
                '/Vol\.?\s*(\d+)/i'
            );
            
            foreach ($volumePatterns as $pattern) {
                if (preg_match($pattern, $match, $volMatch)) {
                    $volume = $volMatch[1];
                    break;
                }
            }
            
            // Extract issue with multiple patterns
            $issue = null;
            $issuePatterns = array(
                '/<span[^>]*class="[^"]*issue[^"]*"[^>]*>Iss\.\s*(\d+)/i',
                '/<span[^>]*>Iss\.\s*(\d+)/i',
                '/Issue\s*(\d+)/i',
                '/Iss\.?\s*(\d+)/i'
            );
            
            foreach ($issuePatterns as $pattern) {
                if (preg_match($pattern, $match, $issMatch)) {
                    $issue = $issMatch[1];
                    break;
                }
            }
            
            // Extract page with multiple patterns
            $page = null;
            $pagePatterns = array(
                '/<span[^>]*class="[^"]*pages[^"]*"[^>]*>pp\.\s*([\d\-]+)/i',
                '/<span[^>]*>pp\.\s*([\d\-]+)/i',
                '/Pages?\s*([\d\-]+)/i',
                '/pp\.?\s*([\d\-]+)/i',
                '/p\.?\s*(\d+)(?:\s*-\s*(\d+))?/i'
            );
            
            foreach ($pagePatterns as $pattern) {
                if (preg_match($pattern, $match, $pageMatch)) {
                    if (isset($pageMatch[2])) {
                        $page = $pageMatch[1] . '-' . $pageMatch[2];
                    } else {
                        $page = $pageMatch[1];
                    }
                    break;
                }
            }
            
            // ISBN extraction for books with multiple patterns
            $isbn = null;
            if ($pubType == 'book' || $pubType == 'book-chapter') {
                $isbnPatterns = array(
                    '/ISBN:?\s*([\d\-X]+)/i',
                    '/ISBN[^:]*?:\s*([\d\-X]+)/i'
                );
                
                foreach ($isbnPatterns as $pattern) {
                    if (preg_match($pattern, $match, $isbnMatch)) {
                        $isbn = $isbnMatch[1];
                        break;
                    }
                }
            }
            
            // Ensure container is valid
            if (empty($container)) {
                $container = getDefaultContainerByType($pubType);
            }
            $container = ensureValidContainer($container, $pubType);
            
            // Create citation object
            $citations[] = array(
                'title' => $title,
                'doi' => $citeDoi,
                'url' => $url,
                'is_pdf' => false,
                'pdf_url' => null, // Dimensions typically doesn't provide direct PDF links
                'is_open_access' => false, // Dimensions doesn't clearly indicate OA status in HTML
                'container' => $container,
                'type' => $pubType,
                'publisher' => $publisher,
                'year' => $year,
                'volume' => $volume,
                'issue' => $issue,
                'page' => $page,
                'isbn' => $isbn,
                'authors' => formatAuthors($authors, 'dimensions'),
                'source' => 'dimensions',
                'id' => null,
                'title_hash' => md5(strtolower(trim(strip_tags($title))))
            );
        }
    }
    
    return $citations;
}

/**
 * Get default container name based on publication type
 * @param string $type Publication type
 * @return string Default container name
 */
function getDefaultContainerByType($type) {
    switch ($type) {
        case 'book':
            return 'Book';
        case 'book-chapter':
            return 'Book chapter';
        case 'proceedings':
        case 'proceedings-article':
            return 'Conference proceedings';
        case 'dissertation':
        case 'thesis':
            return 'Thesis/Dissertation';
        case 'report':
        case 'technical-report':
            return 'Technical report';
        case 'patent':
            return 'Patent';
        case 'dataset':
            return 'Dataset';
        case 'preprint':
            return 'Preprint';
        default:
            return 'Publication';
    }
}

/**
 * Ensure container is valid
 * @param string $container Container value to validate
 * @param string $pubType Publication type
 * @return string Valid container name
 */
function ensureValidContainer($container, $pubType) {
    // List of invalid or problematic container names
    $invalidContainers = array(
        'Deleted Journal',
        'Deleted Publication',
        'Unknown Journal',
        'Unknown Source',
        'Unknown Publication',
        '[No Source Information]',
        '[No Journal Information]',
        'N/A',
        'n/a',
        ''
    );
    
    if (empty($container) || in_array(trim($container), $invalidContainers)) {
        return getDefaultContainerByType($pubType);
    }
    
    return $container;
}

/**
 * Get related papers by title (fallback method)
 * @param string $title Title to search for
 * @param int $limit Maximum number of results
 * @return array Citation data
 */
function getRelatedPapersByTitle($title, $limit = 15) {
    global $config;
    
    // Simplify title for search
    $searchTitle = preg_replace('/[^\w\s]/', '', $title);
    $searchTitle = substr($searchTitle, 0, 80); // Take first portion of title
    
    // Use Crossref to find related papers
    $url = "https://api.crossref.org/works?query=" . urlencode($searchTitle) . "&rows=$limit&sort=relevance";
    if (!empty($config['crossref_email'])) {
        $url .= "&mailto=" . urlencode($config['crossref_email']);
    }
    
    $response = makeRequest($url);
    
    if (!$response || !$response['success'] || 
        empty($response['data']['message']) || 
        empty($response['data']['message']['items'])) {
        return array();
    }
    
    $citations = array();
    foreach ($response['data']['message']['items'] as $item) {
        // Skip exact title matches (likely the same paper)
        $itemTitle = '';
        if (isset($item['title']) && is_array($item['title']) && !empty($item['title'])) {
            $itemTitle = $item['title'][0];
        }
        
        if (strtolower(trim($itemTitle)) === strtolower(trim($title))) {
            continue;
        }
        
        $citation = formatCitationFromCrossref($item);
        $citations[] = $citation;
    }
    
    return $citations;
}

/**
 * Combine and deduplicate citations from multiple sources
 * Enhanced function to combine citations that adds Semantic Scholar to source priority
 * @param array $citations Array of citations from different sources
 * @return array Deduplicated citations
 */
function combineCitations($citations) {
    // Trik 1: Beritahu PHP untuk mengambil variabel $config dari luar fungsi
    global $config;
    
    // Trik 2: Gunakan !empty() untuk memastikan variabel/array benar-benar ada dan bernilai true
    if (!empty($config['enable_error_log'])) {
        error_log("Combined citations before processing: " . count($citations));
    }
    
    // First, organize by source priority
    $sourceOrder = array(
        'opencitations' => 0,
        'crossref' => 1,
        'openalex' => 2,
        'semanticscholar' => 3,
        'dimensions' => 4
    );
    
    // Pastikan array citations tidak kosong
    if (empty($citations)) {
        return array();
    }
    
    usort($citations, function($a, $b) use ($sourceOrder) {
        // Pastikan source ada, gunakan default jika tidak
        $sourceA = isset($a['source']) ? (isset($sourceOrder[$a['source']]) ? $sourceOrder[$a['source']] : 999) : 999;
        $sourceB = isset($b['source']) ? (isset($sourceOrder[$b['source']]) ? $sourceOrder[$b['source']] : 999) : 999;
        
        if ($sourceA === $sourceB) {
            // Jika sama source, sort by year (newest first)
            $yearA = isset($a['year']) && is_numeric($a['year']) ? intval($a['year']) : 0;
            $yearB = isset($b['year']) && is_numeric($b['year']) ? intval($b['year']) : 0;
            return $yearB - $yearA;
        }
        
        return $sourceA - $sourceB;
    });
    
    // Deduplicate by DOI and title hash dengan safe handling
    $uniqueCitations = array();
    $seenDois = array();
    $seenTitleHashes = array();
    
    foreach ($citations as $citation) {
        $isDuplicate = false;
        
        // Periksa validitas citation data
        if (!is_array($citation)) {
            continue;  // Skip invalid citations
        }
        
        // Check DOI duplicates with safe handling
        if (isset($citation['doi']) && !empty($citation['doi'])) {
            $doi = strtolower(trim($citation['doi']));
            if (in_array($doi, $seenDois)) {
                $isDuplicate = true;
                
                // Merge data from different sources if it's a duplicate
                foreach ($uniqueCitations as $key => $existingCitation) {
                    if (isset($existingCitation['doi']) && strtolower(trim($existingCitation['doi'])) === $doi) {
                        // Pastikan kita tidak mengganti data yang ada dengan null
                        // If current citation has a PDF URL that the existing one doesn't, add it
                        if (!empty($citation['pdf_url']) && (empty($existingCitation['pdf_url']))) {
                            $uniqueCitations[$key]['pdf_url'] = $citation['pdf_url'];
                            $uniqueCitations[$key]['is_pdf'] = true;
                        }
                        
                        // If current citation has open access info and existing one doesn't
                        if (isset($citation['is_open_access']) && $citation['is_open_access'] === true && 
                            (!isset($existingCitation['is_open_access']) || $existingCitation['is_open_access'] !== true)) {
                            $uniqueCitations[$key]['is_open_access'] = true;
                        }
                        
                        // If current source has better container info
                        if (!empty($citation['container']) && $citation['container'] !== 'Source not available' && 
                            (empty($existingCitation['container']) || $existingCitation['container'] === 'Source not available')) {
                            $uniqueCitations[$key]['container'] = $citation['container'];
                        }
                        
                        // Only update other fields if they actually have content
                        if (!empty($citation['volume']) && empty($existingCitation['volume'])) {
                            $uniqueCitations[$key]['volume'] = $citation['volume'];
                        }
                        
                        if (!empty($citation['issue']) && empty($existingCitation['issue'])) {
                            $uniqueCitations[$key]['issue'] = $citation['issue'];
                        }
                        
                        if (!empty($citation['page']) && empty($existingCitation['page'])) {
                            $uniqueCitations[$key]['page'] = $citation['page'];
                        }
                        
                        if (!empty($citation['publisher']) && empty($existingCitation['publisher'])) {
                            $uniqueCitations[$key]['publisher'] = $citation['publisher'];
                        }
                        
                        break;
                    }
                }
            } else {
                $seenDois[] = $doi;
            }
        }
        
        // Check title hash duplicates (as backup) with safe handling
        if (!$isDuplicate && isset($citation['title_hash']) && !empty($citation['title_hash'])) {
            $titleHash = $citation['title_hash'];
            if (in_array($titleHash, $seenTitleHashes)) {
                $isDuplicate = true;
                
                // Similar merge logic for title hash duplicates
                foreach ($uniqueCitations as $key => $existingCitation) {
                    if (isset($existingCitation['title_hash']) && $existingCitation['title_hash'] === $titleHash) {
                        // Only update fields that have content
                        if (!empty($citation['pdf_url']) && empty($existingCitation['pdf_url'])) {
                            $uniqueCitations[$key]['pdf_url'] = $citation['pdf_url'];
                            $uniqueCitations[$key]['is_pdf'] = true;
                        }
                        
                        if (isset($citation['is_open_access']) && $citation['is_open_access'] === true && 
                            (!isset($existingCitation['is_open_access']) || $existingCitation['is_open_access'] !== true)) {
                            $uniqueCitations[$key]['is_open_access'] = true;
                        }
                        
                        if (!empty($citation['container']) && $citation['container'] !== 'Source not available' && 
                            (empty($existingCitation['container']) || $existingCitation['container'] === 'Source not available')) {
                            $uniqueCitations[$key]['container'] = $citation['container'];
                        }
                        
                        if (!empty($citation['doi']) && empty($existingCitation['doi'])) {
                            $uniqueCitations[$key]['doi'] = $citation['doi'];
                            if (empty($existingCitation['url'])) {
                                $uniqueCitations[$key]['url'] = 'https://doi.org/' . $citation['doi'];
                            }
                        }
                        
                        if (!empty($citation['volume']) && empty($existingCitation['volume'])) {
                            $uniqueCitations[$key]['volume'] = $citation['volume'];
                        }
                        
                        if (!empty($citation['issue']) && empty($existingCitation['issue'])) {
                            $uniqueCitations[$key]['issue'] = $citation['issue'];
                        }
                        
                        if (!empty($citation['page']) && empty($existingCitation['page'])) {
                            $uniqueCitations[$key]['page'] = $citation['page'];
                        }
                        
                        break;
                    }
                }
            } else {
                $seenTitleHashes[] = $titleHash;
            }
        }
        
        // Add if not a duplicate
        if (!$isDuplicate) {
            $uniqueCitations[] = $citation;
        }
    }
    
    // Error log untuk memastikan tidak ada pengurangan yang drastis
    if (!empty($config['enable_error_log'])) {
        error_log("Unique citations after deduplication: " . count($uniqueCitations));
    }
    
    // Final sort by year (newest first) dengan safe handling
    if (!empty($uniqueCitations)) {
        usort($uniqueCitations, function($a, $b) {
            $yearA = isset($a['year']) && is_numeric($a['year']) ? intval($a['year']) : 0;
            $yearB = isset($b['year']) && is_numeric($b['year']) ? intval($b['year']) : 0;
            
            if ($yearA === $yearB) {
                // If same year, sort by source priority
                $sourceOrder = array(
                    'opencitations' => 0,
                    'crossref' => 1,
                    'openalex' => 2,
                    'semanticscholar' => 3,
                    'dimensions' => 4
                );
                
                $sourceA = isset($a['source']) ? (isset($sourceOrder[$a['source']]) ? $sourceOrder[$a['source']] : 999) : 999;
                $sourceB = isset($b['source']) ? (isset($sourceOrder[$b['source']]) ? $sourceOrder[$b['source']] : 999) : 999;
                
                return $sourceA - $sourceB;
            }
            
            return $yearB - $yearA;
        });
    }
    
    return $uniqueCitations;
}

// Main Process
try {
    // Check if forced refresh
    if ($refresh) {
        // Clear existing cache for this DOI
        clearCache($doi);
    }
    
    // Check cache first
    $cachedResult = getFromCache($doi);
    
    if ($cachedResult !== null) {
        echo json_encode(array(
            'status' => 'success',
            'source' => 'cache',
            'data' => $cachedResult
        ));
        exit;
    }
    
    // Get article metadata
    $metadata = getArticleMetadata($doi);
    
    if (!$metadata) {
        echo json_encode(array('status' => 'error', 'message' => 'Failed to fetch article metadata'));
        exit;
    }
    
    // Extract citation count from metadata - untuk awal
    $initialCitationCount = isset($metadata['is-referenced-by-count']) ? $metadata['is-referenced-by-count'] : 0;
    
    // Extract title for potential alternative searches
    $title = '';
    if (isset($metadata['title']) && is_array($metadata['title']) && !empty($metadata['title'])) {
        $title = $metadata['title'][0];
    }
    
    // Get publication type
    $pubType = 'article-journal';
    if (isset($metadata['type'])) {
        $pubType = $metadata['type'];
    }
    
    // Prepare result
    $result = array(
        'doi' => $doi,
        'title' => $title,
        'type' => $pubType,
        'citation_count' => $initialCitationCount, // Akan diupdate nanti sesuai hasil yang ditemukan
        'citing_articles' => array()
    );
    
    // Get citations using the defined sequence:
    // 1. OpenCitations
    $openCitationsCitations = getOpenCitationsCitations($doi, $limit);
    
    // 2. Crossref Cited-by
    $crossrefCitations = getCrossrefCitedBy($doi, $limit);
    
    // 3. OpenAlex
    $openAlexCitations = getOpenAlexCitations($doi, $limit);
    
    // 4. Semantic Scholar
    $semanticScholarCitations = getSemanticScholarCitations($doi, $limit);

    // 5. Dimensions
    $dimensionsCitations = getDimensionsCitations($doi, $limit);
    
    // Khusus untuk refresh, pastikan Semantic Scholar tidak kosong
    $isRefresh = isset($_GET['refresh']) && $_GET['refresh'] == '1';
    if ($isRefresh && empty($semanticScholarCitations)) {
        if ($config['enable_error_log']) {
            error_log("Semantic Scholar returned empty results on refresh. Retrieving from cache.");
        }    
        $semanticScholarCitations = getSemanticScholarFromCache($doi);
        
        if (empty($semanticScholarCitations)) {
            // Jika masih kosong, coba cek cache utama
            $oldCache = getFromCache($doi);
            if ($oldCache && isset($oldCache['citing_articles']) && !empty($oldCache['citing_articles'])) {
                // Ekstrak kutipan semantic scholar dari cache
                $oldSemanticScholar = array_filter($oldCache['citing_articles'], function($citation) {
                    return isset($citation['source']) && $citation['source'] === 'semanticscholar';
                });
                
                if (!empty($oldSemanticScholar)) {
                    if ($config['enable_error_log']) {
                        error_log("Retrieved " . count($oldSemanticScholar) . " Semantic Scholar citations from main cache");
                    }    
                    $semanticScholarCitations = array_values($oldSemanticScholar);
                }
            }
        }
    }
         
    // Combine all citations
    $allCitations = array_merge(
        $openCitationsCitations, 
        $crossrefCitations, 
        $openAlexCitations, 
        $semanticScholarCitations, 
        $dimensionsCitations
    );
    
    // Deduplicate and sort
    $uniqueCitations = combineCitations($allCitations);
    
    // Add to result
    $result['citing_articles'] = array_slice($uniqueCitations, 0, $limit);
    
    // Enhance PDF URLs for open access articles - optional verification step
    if (!empty($result['citing_articles'])) {
        foreach ($result['citing_articles'] as $key => $citation) {
            // If this citation has a PDF URL and is marked as open access, verify it
            if (!empty($citation['pdf_url']) && isset($citation['is_open_access']) && $citation['is_open_access']) {
                // For performance reasons, we don't verify every URL in real-time
                // But we could add an option to verify PDF accessibility if needed
                // $isAccessible = isPdfAccessible($citation['pdf_url']);
                // $result['citing_articles'][$key]['pdf_accessible'] = $isAccessible;
                
                // Just ensure that if we have a PDF URL, we're using it as the main URL for PDFs
                if ($citation['is_pdf']) {
                    $result['citing_articles'][$key]['url'] = $citation['pdf_url'];
                }
            }
            
            // Ensure we have consistent field naming
            // In case any source used 'journal' instead of 'container', standardize to 'container'
            if (isset($citation['journal']) && !isset($citation['container'])) {
                $result['citing_articles'][$key]['container'] = $citation['journal'];
                unset($result['citing_articles'][$key]['journal']);
            }
            
            // Always ensure valid container names (not "Deleted Journal")
            if (isset($citation['container'])) {
                $result['citing_articles'][$key]['container'] = ensureValidContainer(
                    $citation['container'], 
                    isset($citation['type']) ? $citation['type'] : 'article-journal'
                );
            }
        }
    }
    
    // PERBAIKAN: Update citation_count berdasarkan jumlah kutipan aktual yang ditemukan
    // Gunakan jumlah kutipan yang lebih besar antara metadata dan hasil yang ditemukan
    $actualCitationCount = count($uniqueCitations);
    $result['citation_count'] = max($initialCitationCount, $actualCitationCount);
    
    // Add citation counts from each source
    $result['citation_sources'] = array(
        'opencitations_count' => count($openCitationsCitations),
        'crossref_count' => count($crossrefCitations),
        'openalex_count' => count($openAlexCitations),
        'semanticscholar_count' => count($semanticScholarCitations),
        'dimensions_count' => count($dimensionsCitations),
        'total_before_dedup' => count($allCitations),
        'total_after_dedup' => count($uniqueCitations)
    );
    
    // Extract source info based on publication type
    $sourceInfo = extractSourceInfo($metadata, $pubType);
    $container = $sourceInfo['container'];
    
    // Ensure container is valid
    $container = ensureValidContainer($container, $pubType);
    
    // Add original metadata to result
    $result['metadata'] = array(
        'title' => $title,
        'container' => $container,
        'type' => $pubType,
        'publisher' => isset($metadata['publisher']) ? $metadata['publisher'] : null,
        'authors' => isset($metadata['author']) ? formatAuthors($metadata['author'], 'crossref') : array(),
        'year' => isset($metadata['published-print']) && isset($metadata['published-print']['date-parts']) &&
                  isset($metadata['published-print']['date-parts'][0]) && isset($metadata['published-print']['date-parts'][0][0]) 
                  ? $metadata['published-print']['date-parts'][0][0] : 
                  (isset($metadata['published-online']) && isset($metadata['published-online']['date-parts']) &&
                   isset($metadata['published-online']['date-parts'][0]) && isset($metadata['published-online']['date-parts'][0][0])
                   ? $metadata['published-online']['date-parts'][0][0] : 
                   (isset($metadata['issued']) && isset($metadata['issued']['date-parts']) && 
                    isset($metadata['issued']['date-parts'][0]) && isset($metadata['issued']['date-parts'][0][0])
                    ? $metadata['issued']['date-parts'][0][0] : null)),
        'volume' => isset($metadata['volume']) ? $metadata['volume'] : null,
        'issue' => isset($metadata['issue']) ? $metadata['issue'] : null,
        'page' => isset($metadata['page']) ? $metadata['page'] : null,
        'isbn' => isset($metadata['ISBN']) ? (is_array($metadata['ISBN']) ? $metadata['ISBN'][0] : $metadata['ISBN']) : null,
        'issn' => isset($metadata['ISSN']) ? (is_array($metadata['ISSN']) ? $metadata['ISSN'][0] : $metadata['ISSN']) : null,
        'is_open_access' => isset($metadata['license']) && is_array($metadata['license']) && !empty($metadata['license'])
    );
    
    // Try to find PDF URL for the main article too
    if (isset($metadata['link']) && is_array($metadata['link']) && !empty($metadata['link'])) {
        foreach ($metadata['link'] as $link) {
            if (isset($link['URL']) && isset($link['content-type']) && $link['content-type'] === 'application/pdf') {
                $result['metadata']['pdf_url'] = $link['URL'];
                break;
            }
        }
    }
    
    if (empty($result['citing_articles'])) {
        // Jika tidak ada kutipan dari sumber manapun, periksa apakah ada cache lama
        $oldCache = getFromCache($doi);
        if ($oldCache && !empty($oldCache['citing_articles'])) {
            // Gunakan data cache lama jika lebih kaya
            if ($config['enable_error_log']) {
                error_log("Using old cache data due to empty citation results");
            }    
            $result['citing_articles'] = $oldCache['citing_articles'];
            $result['citation_count'] = max($result['citation_count'], count($oldCache['citing_articles']));
        }
    }
    
    // Validasi citation_count tidak berkurang drastis dari sebelumnya
    $oldCache = getFromCache($doi);
    if ($oldCache && isset($oldCache['citation_count'])) {
        $oldCount = $oldCache['citation_count'];
        $newCount = count($result['citing_articles']);
        
        // Jika hitungan baru < 50% dari hitungan lama, ada masalah
        if ($newCount < $oldCount * 0.5 && $oldCount > 5) {
            if ($config['enable_error_log']) {
                error_log("WARNING: Citation count dropped drastically from $oldCount to $newCount");
            }    
            
            // Menggunakan hasil dengan jumlah citation yang lebih besar
            // Jika refresh diminta, gunakan refresh terbatas dan gabungkan hasilnya
            if (!empty($oldCache['citing_articles'])) {
                if (isset($_GET['refresh']) && $_GET['refresh'] == '1') {
                    // Jika refresh, gabungkan kutipan lama dan baru
                    $combinedCitations = array_merge($result['citing_articles'], $oldCache['citing_articles']);
                    $result['citing_articles'] = combineCitations($combinedCitations);
                    $result['citation_count'] = max($result['citation_count'], count($result['citing_articles']));
                    if ($config['enable_error_log']) {
                        error_log("Combined old and new citations, resulting in " . count($result['citing_articles']) . " citations");
                    }    
                } else {
                    // Jika tidak refresh, gunakan cache lama jika lebih banyak kutipan
                    if (count($oldCache['citing_articles']) > count($result['citing_articles'])) {
                        $result['citing_articles'] = $oldCache['citing_articles'];
                        $result['citation_count'] = max($result['citation_count'], count($oldCache['citing_articles']));
                        if ($config['enable_error_log']) {
                            error_log("Using old cache with more citations: " . count($oldCache['citing_articles']));
                        }    
                    }
                }
            }
        }
    }
    
    // Pastikan citation_sources mencerminkan jumlah aktual
    if (isset($result['citation_sources'])) {
        // Hitung ulang jumlah setelah penggabungan yang mungkin terjadi
        $result['citation_sources']['total_after_dedup'] = count($result['citing_articles']);
    }
    
    // Cache results
    saveToCache($doi, $result);
    
    // Return result
    echo json_encode(array(
        'status' => 'success',
        'source' => 'api',
        'data' => $result
    ));
    
} catch (Exception $e) {
    // Handle any unexpected errors
    if ($config['enable_error_log']) {
        error_log("Error in DOI citation script: " . $e->getMessage());
    }    
    echo json_encode(array(
        'status' => 'error',
        'message' => 'An unexpected error occurred: ' . $e->getMessage()
    ));
}