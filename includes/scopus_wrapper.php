<?php
/**
 * Scopus API Wrapper
 * 
 * Wrapper untuk integrasi Scopus Journal Checker API ke dalam sistem Wizdam
 * Menggunakan class ScopusAPI dari api/SCOPUS_Journal-Checker_API.php
 * 
 * @version 1.0.0
 * @author Wizdam Team
 */

// Load Scopus API class
require_once PROJECT_ROOT . '/api/SCOPUS_Journal-Checker_API.php';

class ScopusWrapper {
    
    private $scopusApi;
    private $db;
    
    public function __construct() {
        global $SCOPUS_API_KEY;
        $this->scopusApi = new ScopusAPI($SCOPUS_API_KEY);
        
        try {
            Database::initialize();
            $this->db = Database::getInstance();
        } catch (Exception $e) {
            error_log('ScopusWrapper DB Error: ' . $e->getMessage());
            $this->db = null;
        }
    }
    
    /**
     * Get journal data by ISSN
     * Cek database dulu, jika tidak ada fetch dari Scopus API
     * 
     * @param string $issn ISSN format: XXXX-XXXX
     * @return array|null Journal data atau null jika tidak ditemukan
     */
    public function getJournalByISSN($issn) {
        // Normalize ISSN
        $issn = $this->normalizeISSN($issn);
        
        if (!$issn) {
            return null;
        }
        
        // Cek cache di database
        if ($this->db) {
            $cached = $this->getCachedJournal($issn);
            if ($cached && !$this->isCacheExpired($cached)) {
                return $cached;
            }
        }
        
        // Fetch dari Scopus API
        $result = $this->scopusApi->searchByISSN($issn);
        
        if ($result && isset($result['success']) && $result['success']) {
            // Simpan ke database
            if ($this->db) {
                $this->saveJournalToDatabase($result);
            }
            
            // Map ke SDG
            $result['sdg_mapping'] = $this->mapSubjectsToSDG($result['subject_areas'] ?? []);
            
            return $result;
        }
        
        return null;
    }
    
    /**
     * Get cached journal from database
     */
    private function getCachedJournal($issn) {
        try {
            $stmt = $this->db->prepare("
                SELECT j.*, GROUP_CONCAT(js.subject, ', ') as subjects
                FROM journals j
                LEFT JOIN journal_subjects js ON j.id = js.journal_id
                WHERE j.issn = :issn OR j.eissn = :issn
                GROUP BY j.id
                LIMIT 1
            ");
            $stmt->execute([':issn' => $issn]);
            $result = $stmt->fetch();
            
            if ($result) {
                // Convert to standard format
                return [
                    'success' => true,
                    'issn' => $result['issn'],
                    'title' => $result['title'],
                    'publisher' => $result['publisher'],
                    'scopus_id' => $result['scopus_id'],
                    'subject_areas' => !empty($result['subjects']) ? explode(', ', $result['subjects']) : [],
                    'discontinued' => (bool)$result['discontinued'],
                    'citescore' => $result['citescore'],
                    'quartile' => $result['quartile'],
                    'sjr' => $result['sjr'],
                    'snip' => $result['snip'],
                    'h_index' => $result['h_index'],
                    'country' => $result['country'],
                    'open_access' => (bool)$result['open_access'],
                    'last_fetched' => $result['last_fetched']
                ];
            }
        } catch (Exception $e) {
            error_log('Get cached journal error: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Check if cache is expired (7 days TTL)
     */
    private function isCacheExpired($journal) {
        if (empty($journal['last_fetched'])) {
            return true;
        }
        
        $lastFetched = strtotime($journal['last_fetched']);
        $now = time();
        $sevenDays = 7 * 24 * 60 * 60;
        
        return ($now - $lastFetched) > $sevenDays;
    }
    
    /**
     * Save journal data to database
     */
    private function saveJournalToDatabase($data) {
        try {
            // Check if exists
            $checkStmt = $this->db->prepare("SELECT id FROM journals WHERE issn = :issn OR eissn = :issn");
            $checkStmt->execute([':issn' => $data['issn']]);
            $existing = $checkStmt->fetch();
            
            if ($existing) {
                // Update
                $updateStmt = $this->db->prepare("
                    UPDATE journals SET
                        title = :title,
                        publisher = :publisher,
                        scopus_id = :scopus_id,
                        citescore = :citescore,
                        quartile = :quartile,
                        sjr = :sjr,
                        snip = :snip,
                        h_index = :h_index,
                        country = :country,
                        open_access = :open_access,
                        discontinued = :discontinued,
                        last_fetched = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                
                $updateStmt->execute([
                    ':id' => $existing['id'],
                    ':title' => $data['title'] ?? null,
                    ':publisher' => $data['publisher'] ?? null,
                    ':scopus_id' => $data['scopus_id'] ?? null,
                    ':citescore' => $data['citescore'] ?? null,
                    ':quartile' => $data['quartile'] ?? null,
                    ':sjr' => $data['sjr'] ?? null,
                    ':snip' => $data['snip'] ?? null,
                    ':h_index' => $data['h_index'] ?? null,
                    ':country' => $data['country'] ?? null,
                    ':open_access' => !empty($data['open_access']) ? 1 : 0,
                    ':discontinued' => !empty($data['discontinued']) ? 1 : 0
                ]);
                
                $journalId = $existing['id'];
                
                // Clear old subjects
                $deleteStmt = $this->db->prepare("DELETE FROM journal_subjects WHERE journal_id = :journal_id");
                $deleteStmt->execute([':journal_id' => $journalId]);
                
            } else {
                // Insert
                $insertStmt = $this->db->prepare("
                    INSERT INTO journals (
                        issn, eissn, title, publisher, scopus_id,
                        citescore, quartile, sjr, snip, h_index,
                        country, open_access, discontinued, last_fetched
                    ) VALUES (
                        :issn, :eissn, :title, :publisher, :scopus_id,
                        :citescore, :quartile, :sjr, :snip, :h_index,
                        :country, :open_access, :discontinued, CURRENT_TIMESTAMP
                    )
                ");
                
                $insertStmt->execute([
                    ':issn' => $data['issn'] ?? null,
                    ':eissn' => $data['eissn'] ?? null,
                    ':title' => $data['title'] ?? null,
                    ':publisher' => $data['publisher'] ?? null,
                    ':scopus_id' => $data['scopus_id'] ?? null,
                    ':citescore' => $data['citescore'] ?? null,
                    ':quartile' => $data['quartile'] ?? null,
                    ':sjr' => $data['sjr'] ?? null,
                    ':snip' => $data['snip'] ?? null,
                    ':h_index' => $data['h_index'] ?? null,
                    ':country' => $data['country'] ?? null,
                    ':open_access' => !empty($data['open_access']) ? 1 : 0,
                    ':discontinued' => !empty($data['discontinued']) ? 1 : 0
                ]);
                
                $journalId = $this->db->lastInsertId();
            }
            
            // Insert subject areas
            if (!empty($data['subject_areas']) && is_array($data['subject_areas'])) {
                $subjectStmt = $this->db->prepare("
                    INSERT INTO journal_subjects (journal_id, subject, asjc_code)
                    VALUES (:journal_id, :subject, :asjc_code)
                ");
                
                foreach ($data['subject_areas'] as $subject) {
                    $subjectStmt->execute([
                        ':journal_id' => $journalId,
                        ':subject' => is_array($subject) ? ($subject['$'] ?? $subject) : $subject,
                        ':asjc_code' => null // ASJC code bisa ditambahkan nanti
                    ]);
                }
            }
            
        } catch (Exception $e) {
            error_log('Save journal to DB error: ' . $e->getMessage());
        }
    }
    
    /**
     * Map journal subject areas to SDG categories
     * 
     * @param array $subjects List of subject areas
     * @return array SDG mapping with scores
     */
    public function mapSubjectsToSDG($subjects) {
        // Subject to SDG mapping based on ASJC codes and common associations
        $subjectSdgMap = [
            // SDG 1: No Poverty
            'Economics, Econometrics and Finance' => ['SDG1', 'SDG8', 'SDG10'],
            'Development' => ['SDG1', 'SDG10'],
            
            // SDG 2: Zero Hunger
            'Food Science' => ['SDG2', 'SDG3'],
            'Agronomy and Crop Science' => ['SDG2', 'SDG15'],
            'Animal Science and Zoology' => ['SDG2', 'SDG15'],
            
            // SDG 3: Good Health and Well-being
            'Medicine' => ['SDG3'],
            'Nursing' => ['SDG3'],
            'Health Professions' => ['SDG3'],
            'Pharmacology, Toxicology and Pharmaceutics' => ['SDG3'],
            'Immunology and Microbiology' => ['SDG3'],
            'Neuroscience' => ['SDG3'],
            'Psychology' => ['SDG3'],
            
            // SDG 4: Quality Education
            'Education' => ['SDG4'],
            
            // SDG 5: Gender Equality
            'Gender Studies' => ['SDG5', 'SDG10'],
            
            // SDG 6: Clean Water and Sanitation
            'Water Science and Technology' => ['SDG6'],
            'Environmental Engineering' => ['SDG6', 'SDG9', 'SDG11'],
            
            // SDG 7: Affordable and Clean Energy
            'Renewable Energy, Sustainability and the Environment' => ['SDG7', 'SDG13'],
            'Energy Engineering and Power Technology' => ['SDG7', 'SDG9'],
            'Fuel Technology' => ['SDG7', 'SDG9'],
            
            // SDG 8: Decent Work and Economic Growth
            'Business, Management and Accounting' => ['SDG8'],
            'Economics, Econometrics and Finance' => ['SDG1', 'SDG8', 'SDG10'],
            
            // SDG 9: Industry, Innovation and Infrastructure
            'Engineering' => ['SDG9'],
            'Computer Science' => ['SDG9'],
            'Materials Science' => ['SDG9'],
            
            // SDG 10: Reduced Inequalities
            'Social Sciences' => ['SDG10', 'SDG11'],
            'Law' => ['SDG10', 'SDG16'],
            
            // SDG 11: Sustainable Cities and Communities
            'Urban Studies' => ['SDG11'],
            'Geography, Planning and Development' => ['SDG11', 'SDG13'],
            
            // SDG 12: Responsible Consumption and Production
            'Industrial and Manufacturing Engineering' => ['SDG9', 'SDG12'],
            'Management, Monitoring, Policy and Law' => ['SDG12', 'SDG16'],
            
            // SDG 13: Climate Action
            'Atmospheric Science' => ['SDG13'],
            'Global and Planetary Change' => ['SDG13'],
            'Environmental Science' => ['SDG13', 'SDG14', 'SDG15'],
            
            // SDG 14: Life Below Water
            'Oceanography' => ['SDG14'],
            'Aquatic Science' => ['SDG14'],
            
            // SDG 15: Life on Land
            'Ecology, Evolution, Behavior and Systematics' => ['SDG15'],
            'Nature and Landscape Conservation' => ['SDG15'],
            'Plant Science' => ['SDG15', 'SDG2'],
            
            // SDG 16: Peace, Justice and Strong Institutions
            'Political Science and International Relations' => ['SDG16'],
            'Law' => ['SDG10', 'SDG16'],
            
            // SDG 17: Partnerships for the Goals
            'Public Administration' => ['SDG17'],
        ];
        
        $sdgScores = [];
        
        foreach ($subjects as $subject) {
            $subjectName = is_array($subject) ? ($subject['$'] ?? $subject) : $subject;
            
            foreach ($subjectSdgMap as $keySubject => $sdgs) {
                if (stripos($subjectName, $keySubject) !== false) {
                    foreach ($sdgs as $sdg) {
                        if (!isset($sdgScores[$sdg])) {
                            $sdgScores[$sdg] = 0;
                        }
                        $sdgScores[$sdg]++;
                    }
                }
            }
        }
        
        // Sort by score
        arsort($sdgScores);
        
        // Format result
        $mapping = [];
        foreach ($sdgScores as $sdg => $score) {
            $mapping[] = [
                'sdg' => $sdg,
                'score' => $score,
                'relevance' => $score >= 3 ? 'high' : ($score >= 2 ? 'medium' : 'low')
            ];
        }
        
        return $mapping;
    }
    
    /**
     * Normalize ISSN format
     */
    private function normalizeISSN($issn) {
        // Remove spaces and dashes
        $clean = preg_replace('/[\s\-]/', '', $issn);
        
        // Validate length
        if (strlen($clean) !== 8) {
            return null;
        }
        
        // Format as XXXX-XXXX
        return substr($clean, 0, 4) . '-' . substr($clean, 4, 4);
    }
    
    /**
     * Get all archived journals
     */
    public function getAllJournals($limit = 50, $offset = 0) {
        if (!$this->db) {
            return [];
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT j.*, COUNT(js.id) as subject_count
                FROM journals j
                LEFT JOIN journal_subjects js ON j.id = js.journal_id
                WHERE j.last_fetched IS NOT NULL
                GROUP BY j.id
                ORDER BY j.last_fetched DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get all journals error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search journals by keyword
     */
    public function searchJournals($keyword, $limit = 20) {
        if (!$this->db) {
            return [];
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT j.*
                FROM journals j
                LEFT JOIN journal_subjects js ON j.id = js.journal_id
                WHERE (j.title LIKE :keyword OR j.publisher LIKE :keyword OR js.subject LIKE :keyword)
                  AND j.last_fetched IS NOT NULL
                LIMIT :limit
            ");
            $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Search journals error: ' . $e->getMessage());
            return [];
        }
    }
}
