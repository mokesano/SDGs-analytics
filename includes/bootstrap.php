<?php
/**
 * bootstrap.php — SQLite Database Initializer
 *
 * Opens (or creates) the SQLite database, applies the schema DDL,
 * and exposes a global $db PDO instance plus a getDb() helper.
 *
 * Must be required AFTER config.php (which defines PROJECT_ROOT).
 */

// Ensure PROJECT_ROOT is defined (config.php should have set it already)
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

// Path to the SQLite database file
if (!defined('DB_PATH')) {
    define('DB_PATH', PROJECT_ROOT . '/database/wizdam.db');
}

// Create the database directory if it doesn't exist
$_db_dir = dirname(DB_PATH);
if (!is_dir($_db_dir)) {
    mkdir($_db_dir, 0755, true);
}
unset($_db_dir);

// Open PDO connection
$db = new PDO('sqlite:' . DB_PATH);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Performance / integrity pragmas
$db->exec('PRAGMA journal_mode=WAL');
$db->exec('PRAGMA foreign_keys=ON');

// Bootstrap schema (CREATE TABLE IF NOT EXISTS — safe to run every time)
$_schema_file = PROJECT_ROOT . '/database/schema.sql';
if (file_exists($_schema_file)) {
    $db->exec(file_get_contents($_schema_file));
}
unset($_schema_file);

// Make the connection globally accessible
$GLOBALS['db'] = $db;

/**
 * Returns the shared PDO (SQLite) connection.
 *
 * @return PDO
 * @throws RuntimeException if the database was never initialised
 */
function getDb(): PDO {
    if (!isset($GLOBALS['db']) || !($GLOBALS['db'] instanceof PDO)) {
        throw new RuntimeException('Database connection not initialised. Require bootstrap.php first.');
    }
    return $GLOBALS['db'];
}

// i18n and auth helpers (order matters: i18n before auth)
require_once PROJECT_ROOT . '/includes/i18n.php';
i18nInit();

require_once PROJECT_ROOT . '/includes/auth.php';

return $db;
