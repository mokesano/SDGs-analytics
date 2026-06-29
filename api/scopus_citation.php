<?php
declare(strict_types=1);

/**
 * @file scopus_citations.php
 * 
 * Copyright (c) 2024 Sangia Wizdam Team
 * This software is released under the MIT License.
 * For full license information, see LICENSE file in the project root.
 * 
 * Mencari kutipan untuk artikel baik yang terindeks maupun tidak terindeks Scopus.
 * 
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

// Terima DOI artikel
$doi = isset($_GET['doi']) ? trim($_GET['doi']) : '';

if (empty($doi)) {
    http_response_code(400);
    echo json_encode(array('error' => 'Parameter doi diperlukan'));
    exit;
}

// -----------------------------------------------------------------
// CONFIG API Scopus
// -----------------------------------------------------------------
$api_key = '73e21cba2e777a3093e24a781e0ee1a9'; // Ganti dengan API key Anda
$headers = array(
    'Accept: application/json',
    'X-ELS-APIKey: ' . $api_key
);

/**
 * Fungsi untuk melakukan request ke API Scopus
 */
function scopus_api_request($url, $headers) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return array(
        'http_code' => $http_code,
        'response' => $response
    );
}

/**
 * Fungsi untuk memproses data artikel dari Scopus
 */
function process_scopus_article($article_data) {
    $title = isset($article_data['dc:title']) ? $article_data['dc:title'] : '';
    $journal = isset($article_data['prism:publicationName']) ? $article_data['prism:publicationName'] : '';
    $volume = isset($article_data['prism:volume']) ? $article_data['prism:volume'] : '';
    $year = isset($article_data['prism:coverDate']) ? substr($article_data['prism:coverDate'], 0, 4) : '';
    $pages = isset($article_data['prism:pageRange']) ? $article_data['prism:pageRange'] : '';
    $doi = isset($article_data['prism:doi']) ? $article_data['prism:doi'] : '';
    $eid = isset($article_data['eid']) ? $article_data['eid'] : '';
    
    $article = array(
        'title' => $title,
        'journal' => $journal,
        'volume' => $volume,
        'year' => $year,
        'pages' => $pages,
        'authors' => '',
        'doi' => $doi,
        'scopus_id' => '',
        'eid' => $eid
    );
    
    // Ekstrak Scopus ID jika ada
    if (isset($article_data['dc:identifier'])) {
        if (preg_match('/SCOPUS_ID:(\d+)/', $article_data['dc:identifier'], $matches)) {
            $article['scopus_id'] = $matches[1];
        }
    }
    
    // Proses penulis
    $authors = array();
    if (isset($article_data['authors']) && isset($article_data['authors']['author'])) {
        $author_list = $article_data['authors']['author'];
        if (!isset($author_list[0])) {
            $author_list = array($author_list);
        }
        
        foreach ($author_list as $author) {
            $name = '';
            if (isset($author['ce:given-name']) && isset($author['ce:surname'])) {
                $name = $author['ce:given-name'] . ' ' . $author['ce:surname'];
            } elseif (isset($author['dc:creator'])) {
                $name = $author['dc:creator'];
            }
            if (!empty($name)) {
                $authors[] = $name;
            }
        }
        
        $article['authors'] = implode(', ', $authors);
    } elseif (isset($article_data['dc:creator'])) {
        $article['authors'] = $article_data['dc:creator'];
    }
    
    return $article;
}

// 1. Cek apakah artikel terindeks di Scopus
$abstract_url = 'https://api.elsevier.com/content/abstract/doi/' . urlencode($doi) . '?apiKey=' . $api_key;
$abstract_result = scopus_api_request($abstract_url, $headers);

$is_in_scopus = ($abstract_result['http_code'] == 200);
$article_info = array();
$citation_count = 0;
$citing_articles = array();

if ($is_in_scopus) {
    $abstract_data = json_decode($abstract_result['response'], true);
    
    if (isset($abstract_data['abstracts-retrieval-response']['coredata'])) {
        $coredata = $abstract_data['abstracts-retrieval-response']['coredata'];
        $article_info = process_scopus_article($coredata);
        
        // Dapatkan jumlah kutipan
        $citation_count = isset($coredata['citedby-count']) ? (int)$coredata['citedby-count'] : 0;
        
        // Jika ada kutipan, dapatkan daftar artikel yang mengutip
        if ($citation_count > 0 && isset($coredata['dc:identifier'])) {
            // Ekstrak Scopus ID
            if (preg_match('/SCOPUS_ID:(\d+)/', $coredata['dc:identifier'], $matches)) {
                $scopus_id = $matches[1];
                
                // Dapatkan artikel yang mengutip
                $citing_url = 'https://api.elsevier.com/content/search/scopus?query=ref(' . $scopus_id . ')';
                $citing_url .= '&field=dc:title,prism:publicationName,prism:volume,prism:coverDate,prism:pageRange,authors,prism:doi,dc:identifier,eid';
                $citing_url .= '&count=25&apiKey=' . $api_key;
                
                $citing_result = scopus_api_request($citing_url, $headers);
                
                if ($citing_result['http_code'] == 200) {
                    $citing_data = json_decode($citing_result['response'], true);
                    
                    if (isset($citing_data['search-results']['entry']) && !empty($citing_data['search-results']['entry'])) {
                        foreach ($citing_data['search-results']['entry'] as $entry) {
                            $citing_articles[] = process_scopus_article($entry);
                        }
                    }
                }
            }
        }
    }
}

// 2. Cari kutipan menggunakan REFDOI dan REFERENCE (untuk artikel yang belum tentu terindeks)
$search_methods = array(
    'REFDOI' => 'refdoi(' . $doi . ')',
    'REFERENCE' => 'reference(' . $doi . ')'
);

$search_results = array();
$all_citing_articles = $citing_articles;
$processed_dois = array();

// Tandai DOI yang sudah diproses dari daftar citation API
foreach ($citing_articles as $article) {
    if (!empty($article['doi'])) {
        $processed_dois[strtolower($article['doi'])] = true;
    }
}

foreach ($search_methods as $method => $query) {
    $search_url = 'https://api.elsevier.com/content/search/scopus?query=' . urlencode($query);
    $search_url .= '&field=dc:title,prism:publicationName,prism:volume,prism:coverDate,prism:pageRange,authors,prism:doi,dc:identifier,eid';
    $search_url .= '&count=25&apiKey=' . $api_key;
    
    $search_result = scopus_api_request($search_url, $headers);
    
    if ($search_result['http_code'] == 200) {
        $search_data = json_decode($search_result['response'], true);
        $count = isset($search_data['search-results']['opensearch:totalResults']) ? (int)$search_data['search-results']['opensearch:totalResults'] : 0;
        $articles = array();
        
        if (isset($search_data['search-results']['entry']) && !empty($search_data['search-results']['entry'])) {
            foreach ($search_data['search-results']['entry'] as $entry) {
                $article = process_scopus_article($entry);
                
                // Tambahkan hanya jika belum ada
                $doi_key = strtolower($article['doi']);
                if (empty($article['doi']) || !isset($processed_dois[$doi_key])) {
                    $articles[] = $article;
                    if (!empty($article['doi'])) {
                        $processed_dois[$doi_key] = true;
                    }
                }
            }
        }
        
        $search_results[$method] = array(
            'count' => $count,
            'articles' => $articles
        );
        
        $all_citing_articles = array_merge($all_citing_articles, $articles);
    }
}

// Hitung jumlah kutipan total yang unik
$total_citation_count = count($all_citing_articles);

// Jika artikel tidak terindeks tapi ditemukan kutipan, update citation count
if (!$is_in_scopus && $total_citation_count > 0) {
    $citation_count = $total_citation_count;
    
    // Coba dapatkan info dasar artikel dari salah satu referensi
    if (empty($article_info) && !empty($all_citing_articles[0]['title'])) {
        $article_info = array(
            'title' => $all_citing_articles[0]['title'], // Judul mungkin tidak akurat
            'doi' => $doi,
            'scopus_id' => ''
        );
    }
}

// Output hasil
$output = array(
    'doi' => $doi,
    'is_in_scopus' => $is_in_scopus,
    'article_info' => $article_info,
    'citation_count' => $citation_count,
    'citation_counts_by_method' => array(
        'citations_api' => count($citing_articles),
        'REFDOI_search' => isset($search_results['REFDOI']['count']) ? $search_results['REFDOI']['count'] : 0,
        'REFERENCE_search' => isset($search_results['REFERENCE']['count']) ? $search_results['REFERENCE']['count'] : 0,
        'total_unique' => $total_citation_count
    ),
    'citing_articles' => $all_citing_articles,
    'status' => ($total_citation_count > 0) ? 'has_citations' : 'no_citations',
    'timestamp' => date('Y-m-d H:i:s')
);

echo json_encode($output);
?>