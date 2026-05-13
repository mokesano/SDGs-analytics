<?php

declare(strict_types=1);

namespace Wizdam\Core;

use PDO;
use PDOException;
use Exception;

/**
 * Database Helper Class
 * PDO wrapper untuk SQLite dengan metode helper untuk operasi umum
 * 
 * @version 2.0.0 (PSR-4/PSR-12 Compliant)
 * @author Wizdam Team
 * @license MIT
 */
class Database
{
    private static ?PDO $instance = null;
    private static string $dbPath = '';

    /**
     * Inisialisasi koneksi database singleton
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            if (empty(self::$dbPath)) {
                self::$dbPath = PROJECT_ROOT . '/database/wizdam.db';
            }

            try {
                $dsn = 'sqlite:' . self::$dbPath;
                self::$instance = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);

                // Enable WAL mode untuk concurrent access yang lebih baik
                self::$instance->exec('PRAGMA journal_mode=WAL');

                // Set timeout untuk locking
                self::$instance->exec('PRAGMA busy_timeout=5000');

            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                throw new Exception('Database connection failed. Please check permissions.');
            }
        }

        return self::$instance;
    }

    /**
     * Initialize database schema jika belum ada
     */
    public static function initialize(): bool
    {
        try {
            $db = self::getInstance();
            $schemaFile = PROJECT_ROOT . '/database/schema.sql';

            if (!file_exists($schemaFile)) {
                error_log('Schema file not found: ' . $schemaFile);
                return false;
            }

            $sql = file_get_contents($schemaFile);
            $db->exec($sql);

            return true;
        } catch (Exception $e) {
            error_log('Database initialization failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Simpan peneliti ke database
     */
    public static function saveResearcher(
        string $orcid,
        string $name,
        array $institutions = [],
        int $totalWorks = 0
    ): ?int {
        try {
            $db = self::getInstance();
            $stmt = $db->prepare("
                INSERT INTO researchers (orcid, name, institutions, total_works, last_fetched)
                VALUES (:orcid, :name, :institutions, :total_works, :last_fetched)
                ON CONFLICT(orcid) DO UPDATE SET
                    name = excluded.name,
                    institutions = excluded.institutions,
                    total_works = excluded.total_works,
                    last_fetched = excluded.last_fetched
            ");

            $stmt->execute([
                ':orcid'         => $orcid,
                ':name'          => $name,
                ':institutions'  => json_encode($institutions, JSON_UNESCAPED_UNICODE),
                ':total_works'   => $totalWorks,
                ':last_fetched'  => date('Y-m-d H:i:s'),
            ]);

            // Fetch the actual ID after upsert, not lastInsertId() which can be stale
            $selectStmt = $db->prepare("SELECT id FROM researchers WHERE orcid = :orcid");
            $selectStmt->execute([':orcid' => $orcid]);
            $result = $selectStmt->fetch(PDO::FETCH_ASSOC);

            return $result ? (int)$result['id'] : null;
        } catch (Exception $e) {
            error_log('Failed to save researcher: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ambil peneliti by ORCID
     */
    public static function getResearcherByOrcid(string $orcid): ?array
    {
        try {
            $db = self::getInstance();
            $stmt = $db->prepare("SELECT * FROM researchers WHERE orcid = :orcid");
            $stmt->execute([':orcid' => $orcid]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (Exception $e) {
            error_log('Failed to get researcher: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Simpan karya ke database
     */
    public static function saveWork(int $researcherId, array $work): ?int
    {
        try {
            $db = self::getInstance();

            // If DOI is null/empty, use put_code as unique identifier instead
            if (empty($work['doi'])) {
                $stmt = $db->prepare("
                    INSERT INTO works (researcher_id, put_code, title, doi, abstract, authors, 
                                       journal, volume, issue, pages, year, keywords, work_type, url)
                    VALUES (:researcher_id, :put_code, :title, :doi, :abstract, :authors,
                            :journal, :volume, :issue, :pages, :year, :keywords, :work_type, :url)
                    ON CONFLICT(put_code) DO UPDATE SET
                        title = excluded.title,
                        abstract = excluded.abstract,
                        authors = excluded.authors,
                        journal = excluded.journal,
                        volume = excluded.volume,
                        issue = excluded.issue,
                        pages = excluded.pages,
                        year = excluded.year,
                        keywords = excluded.keywords,
                        work_type = excluded.work_type,
                        url = excluded.url
                ");
            } else {
                $stmt = $db->prepare("
                    INSERT INTO works (researcher_id, put_code, title, doi, abstract, authors, 
                                       journal, volume, issue, pages, year, keywords, work_type, url)
                    VALUES (:researcher_id, :put_code, :title, :doi, :abstract, :authors,
                            :journal, :volume, :issue, :pages, :year, :keywords, :work_type, :url)
                    ON CONFLICT(doi) DO UPDATE SET
                        title = excluded.title,
                        abstract = excluded.abstract,
                        authors = excluded.authors,
                        journal = excluded.journal,
                        volume = excluded.volume,
                        issue = excluded.issue,
                        pages = excluded.pages,
                        year = excluded.year,
                        keywords = excluded.keywords,
                        work_type = excluded.work_type,
                        url = excluded.url
                ");
            }

            $stmt->execute([
                ':researcher_id' => $researcherId,
                ':put_code'      => $work['put_code'] ?? null,
                ':title'         => $work['title'] ?? '',
                ':doi'           => $work['doi'] ?? null,
                ':abstract'      => $work['abstract'] ?? '',
                ':authors'       => json_encode($work['contributors'] ?? [], JSON_UNESCAPED_UNICODE),
                ':journal'       => $work['journal'] ?? '',
                ':volume'        => $work['volume'] ?? '',
                ':issue'         => $work['issue'] ?? '',
                ':pages'         => $work['pages'] ?? '',
                ':year'          => $work['pub_year'] ?? null,
                ':keywords'      => json_encode($work['keywords'] ?? [], JSON_UNESCAPED_UNICODE),
                ':work_type'     => $work['work_type'] ?? '',
                ':url'           => $work['url'] ?? '',
            ]);

            // Fetch the actual work ID after upsert
            if (!empty($work['doi'])) {
                $selectStmt = $db->prepare("SELECT id FROM works WHERE doi = :doi");
                $selectStmt->execute([':doi' => $work['doi']]);
            } else {
                $selectStmt = $db->prepare("SELECT id FROM works WHERE put_code = :put_code AND researcher_id = :researcher_id");
                $selectStmt->execute([
                    ':put_code' => $work['put_code'] ?? '',
                    ':researcher_id' => $researcherId
                ]);
            }
            $result = $selectStmt->fetch(PDO::FETCH_ASSOC);

            return $result ? (int)$result['id'] : null;
        } catch (Exception $e) {
            error_log('Failed to save work: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Simpan relasi work-SDG
     */
    public static function saveWorkSdg(
        int $workId,
        string $sdgCode,
        float $confidenceScore,
        string $contributorType,
        float $keywordScore = 0,
        float $similarityScore = 0,
        float $causalScore = 0,
        float $impactScore = 0
    ): ?int {
        try {
            $db = self::getInstance();
            $stmt = $db->prepare("
                INSERT INTO work_sdgs (work_id, sdg_code, confidence_score, contributor_type,
                                       keyword_score, similarity_score, causal_score, impact_score)
                VALUES (:work_id, :sdg_code, :confidence_score, :contributor_type,
                        :keyword_score, :similarity_score, :causal_score, :impact_score)
            ");

            $stmt->execute([
                ':work_id'           => $workId,
                ':sdg_code'          => $sdgCode,
                ':confidence_score'  => $confidenceScore,
                ':contributor_type'  => $contributorType,
                ':keyword_score'     => $keywordScore,
                ':similarity_score'  => $similarityScore,
                ':causal_score'      => $causalScore,
                ':impact_score'      => $impactScore,
            ]);

            return (int)$db->lastInsertId();
        } catch (Exception $e) {
            error_log('Failed to save work SDG: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ambil karya berdasarkan SDG code untuk peneliti tertentu
     */
    public static function getWorksBySdg(int $researcherId, string $sdgCode): array
    {
        try {
            $db = self::getInstance();
            $stmt = $db->prepare("
                SELECT w.*, ws.confidence_score, ws.contributor_type
                FROM works w
                JOIN work_sdgs ws ON w.id = ws.work_id
                WHERE w.researcher_id = :researcher_id AND ws.sdg_code = :sdg_code
                ORDER BY ws.confidence_score DESC
            ");
            $stmt->execute([
                ':researcher_id' => $researcherId,
                ':sdg_code'      => $sdgCode,
            ]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Failed to get works by SDG: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Simpan riwayat pencarian
     */
    public static function saveSearchHistory(?int $userId, string $inputType, string $inputValue): ?int
    {
        try {
            $db = self::getInstance();
            $stmt = $db->prepare("
                INSERT INTO search_history (user_id, input_type, input_value)
                VALUES (:user_id, :input_type, :input_value)
            ");
            $stmt->execute([
                ':user_id'     => $userId,
                ':input_type'  => $inputType,
                ':input_value' => $inputValue,
            ]);
            return (int)$db->lastInsertId();
        } catch (Exception $e) {
            error_log('Failed to save search history: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ambil semua peneliti dari arsip
     */
    public static function getArchivedResearchers(int $limit = 50): array
    {
        try {
            $db = self::getInstance();
            $stmt = $db->prepare("
                SELECT r.*, COUNT(DISTINCT ws.sdg_code) AS sdg_count
                FROM researchers r
                LEFT JOIN works w ON r.id = w.researcher_id
                LEFT JOIN work_sdgs ws ON w.id = ws.work_id
                WHERE r.last_fetched IS NOT NULL
                GROUP BY r.id
                ORDER BY r.last_fetched DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Failed to get archived researchers: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Reset instance (untuk testing)
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
        self::$dbPath = '';
    }

    /**
     * Execute a SELECT query with optional parameters
     */
    public static function query(string $sql, array $params = []): array
    {
        try {
            $db = self::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Query failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Execute an INSERT/UPDATE/DELETE query with parameters
     */
    public static function execute(string $sql, array $params = []): int
    {
        try {
            $db = self::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Execute failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get last inserted ID
     */
    public static function getLastInsertId(): int
    {
        $db = self::getInstance();
        return (int)$db->lastInsertId();
    }

    /**
     * Begin a transaction
     */
    public static function beginTransaction(): bool
    {
        $db = self::getInstance();
        return $db->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public static function commit(): bool
    {
        $db = self::getInstance();
        return $db->commit();
    }

    /**
     * Rollback a transaction
     */
    public static function rollback(): bool
    {
        $db = self::getInstance();
        return $db->rollBack();
    }

    /**
     * Check if in transaction
     */
    public static function inTransaction(): bool
    {
        $db = self::getInstance();
        return $db->inTransaction();
    }

    /**
     * Fetch single row
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params);
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Fetch single column value
     */
    public static function fetchColumn(string $sql, array $params = [])
    {
        try {
            $db = self::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('FetchColumn failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if table exists
     */
    public static function tableExists(string $tableName): bool
    {
        try {
            $db = self::getInstance();
            $stmt = $db->prepare(
                \"SELECT name FROM sqlite_master WHERE type='table' AND name=:name\"
            );
            $stmt->execute(['name' => $tableName]);
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }
}
