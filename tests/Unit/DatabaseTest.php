<?php

namespace Wizdam\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wizdam\Core\Database;
use PDO;

/**
 * Unit Test untuk Database Class
 * 
 * Menguji singleton pattern dan metode helper database
 */
class DatabaseTest extends TestCase
{
    /**
     * Test bahwa getInstance mengembalikan instance PDO
     */
    public function testGetInstanceReturnsPDO(): void
    {
        // Pastikan PROJECT_ROOT defined
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(__DIR__, 2));
        }
        
        $db = Database::getInstance();
        
        $this->assertInstanceOf(PDO::class, $db);
    }

    /**
     * Test bahwa getInstance mengembalikan instance yang sama (Singleton)
     */
    public function testGetInstanceReturnsSameInstance(): void
    {
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(__DIR__, 2));
        }
        
        $db1 = Database::getInstance();
        $db2 = Database::getInstance();
        
        $this->assertSame($db1, $db2);
    }

    /**
     * Test bahwa database menggunakan SQLite
     */
    public function testDatabaseIsSQLite(): void
    {
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(__DIR__, 2));
        }
        
        $db = Database::getInstance();
        
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->assertEquals('sqlite', $driver);
    }

    /**
     * Test bahwa error mode diset ke EXCEPTION
     */
    public function testErrorModeIsException(): void
    {
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(__DIR__, 2));
        }
        
        $db = Database::getInstance();
        
        $errorMode = $db->getAttribute(PDO::ATTR_ERRMODE);
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $errorMode);
    }

    /**
     * Test bahwa fetch mode default adalah ASSOC
     */
    public function testDefaultFetchModeIsAssoc(): void
    {
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(__DIR__, 2));
        }
        
        $db = Database::getInstance();
        
        $fetchMode = $db->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);
        $this->assertEquals(PDO::FETCH_ASSOC, $fetchMode);
    }

    /**
     * Test query method dengan SELECT sederhana
     */
    public function testQueryMethod(): void
    {
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(__DIR__, 2));
        }
        
        $db = Database::getInstance();
        
        // Buat tabel temporary untuk testing
        $db->exec('CREATE TEMPORARY TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
        $db->exec("INSERT INTO test_table (name) VALUES ('test1'), ('test2')");
        
        $result = Database::query('SELECT * FROM test_table ORDER BY id');
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('test1', $result[0]['name']);
        $this->assertEquals('test2', $result[1]['name']);
    }

    /**
     * Test query method dengan parameter binding
     */
    public function testQueryWithParameters(): void
    {
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(__DIR__, 2));
        }
        
        $db = Database::getInstance();
        
        // Buat tabel temporary untuk testing
        $db->exec('CREATE TEMPORARY TABLE test_params (id INTEGER PRIMARY KEY, name TEXT, value INTEGER)');
        $db->exec("INSERT INTO test_params (name, value) VALUES ('a', 1), ('b', 2), ('c', 3)");
        
        $result = Database::query(
            'SELECT * FROM test_params WHERE value > :min_value ORDER BY id',
            ['min_value' => 1]
        );
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('b', $result[0]['name']);
        $this->assertEquals('c', $result[1]['name']);
    }

    /**
     * Test execute method untuk INSERT/UPDATE/DELETE
     */
    public function testExecuteMethod(): void
    {
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(__DIR__, 2));
        }
        
        $db = Database::getInstance();
        
        // Buat tabel temporary untuk testing
        $db->exec('CREATE TEMPORARY TABLE test_execute (id INTEGER PRIMARY KEY, name TEXT)');
        
        // Test INSERT
        $affected = Database::execute(
            'INSERT INTO test_execute (name) VALUES (:name)',
            ['name' => 'test_insert']
        );
        
        $this->assertEquals(1, $affected);
        
        // Verify data inserted
        $result = Database::query('SELECT * FROM test_execute WHERE name = :name', ['name' => 'test_insert']);
        $this->assertCount(1, $result);
        $this->assertEquals('test_insert', $result[0]['name']);
    }

    /**
     * Test getLastInsertId method
     */
    public function testGetLastInsertId(): void
    {
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(__DIR__, 2));
        }
        
        $db = Database::getInstance();
        
        // Buat tabel temporary untuk testing
        $db->exec('CREATE TEMPORARY TABLE test_last_id (id INTEGER PRIMARY KEY, name TEXT)');
        
        Database::execute('INSERT INTO test_last_id (name) VALUES (:name)', ['name' => 'test_id']);
        
        $lastId = Database::getLastInsertId();
        
        $this->assertIsNumeric($lastId);
        $this->assertGreaterThan(0, $lastId);
    }

    /**
     * Test beginTransaction, commit, rollback
     */
    public function testTransactions(): void
    {
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(__DIR__, 2));
        }
        
        $db = Database::getInstance();
        
        // Buat tabel temporary untuk testing
        $db->exec('CREATE TEMPORARY TABLE test_transaction (id INTEGER PRIMARY KEY, name TEXT)');
        
        // Test successful transaction
        $this->assertTrue(Database::beginTransaction());
        
        Database::execute('INSERT INTO test_transaction (name) VALUES (:name)', ['name' => 'transaction_test']);
        
        $this->assertTrue(Database::commit());
        
        // Verify data committed
        $result = Database::query('SELECT * FROM test_transaction WHERE name = :name', ['name' => 'transaction_test']);
        $this->assertCount(1, $result);
        
        // Test rollback
        $this->assertTrue(Database::beginTransaction());
        Database::execute('INSERT INTO test_transaction (name) VALUES (:name)', ['name' => 'rollback_test']);
        $this->assertTrue(Database::rollback());
        
        // Verify data rolled back
        $result = Database::query('SELECT * FROM test_transaction WHERE name = :name', ['name' => 'rollback_test']);
        $this->assertCount(0, $result);
    }

    /**
     * Test inTransaction method
     */
    public function testInTransaction(): void
    {
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(__DIR__, 2));
        }
        
        $db = Database::getInstance();
        $db->exec('CREATE TEMPORARY TABLE test_in_transaction (id INTEGER PRIMARY KEY, name TEXT)');
        
        // Initially not in transaction
        $this->assertFalse(Database::inTransaction());
        
        // Start transaction
        Database::beginTransaction();
        $this->assertTrue(Database::inTransaction());
        
        // Commit
        Database::commit();
        $this->assertFalse(Database::inTransaction());
    }

    /**
     * Test fetchOne method
     */
    public function testFetchOne(): void
    {
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(__DIR__, 2));
        }
        
        $db = Database::getInstance();
        $db->exec('CREATE TEMPORARY TABLE test_fetch_one (id INTEGER PRIMARY KEY, name TEXT)');
        $db->exec("INSERT INTO test_fetch_one (name) VALUES ('first'), ('second')");
        
        $result = Database::fetchOne('SELECT * FROM test_fetch_one WHERE name = :name', ['name' => 'first']);
        
        $this->assertIsArray($result);
        $this->assertEquals('first', $result['name']);
    }

    /**
     * Test fetchOne returns null when no result
     */
    public function testFetchOneReturnsNullWhenNoResult(): void
    {
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(__DIR__, 2));
        }
        
        $db = Database::getInstance();
        $db->exec('CREATE TEMPORARY TABLE test_fetch_null (id INTEGER PRIMARY KEY, name TEXT)');
        
        $result = Database::fetchOne('SELECT * FROM test_fetch_null WHERE name = :name', ['name' => 'nonexistent']);
        
        $this->assertNull($result);
    }

    /**
     * Test fetchColumn method
     */
    public function testFetchColumn(): void
    {
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(__DIR__, 2));
        }
        
        $db = Database::getInstance();
        $db->exec('CREATE TEMPORARY TABLE test_fetch_column (id INTEGER PRIMARY KEY, name TEXT, value INTEGER)');
        $db->exec("INSERT INTO test_fetch_column (name, value) VALUES ('test', 42)");
        
        $result = Database::fetchColumn('SELECT value FROM test_fetch_column WHERE name = :name', ['name' => 'test']);
        
        $this->assertEquals(42, $result);
    }

    /**
     * Test tableExists method
     */
    public function testTableExists(): void
    {
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(__DIR__, 2));
        }

        // Use a unique table name to avoid conflicts
        $tableName = 'test_exists_' . uniqid();
        $db = Database::getInstance();
        $db->exec("CREATE TABLE {$tableName} (id INTEGER PRIMARY KEY)");

        try {
            $this->assertTrue(Database::tableExists($tableName), "Table {$tableName} should exist");
            $this->assertFalse(Database::tableExists('nonexistent_table_' . uniqid()), "Non-existent table should return false");
        } finally {
            // Cleanup
            $db->exec("DROP TABLE IF EXISTS {$tableName}");
        }
    }
}
