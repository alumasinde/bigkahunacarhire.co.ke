<?php
/**
 * bin/migrate.php — Database migration runner for Big Kahuna Car Hire.
 *
 * Usage (SSH into your server / open a terminal in the project root):
 *   php bin/migrate.php
 *
 * What it does:
 *   - Connects using the same .env credentials the app already uses.
 *   - Creates a `schema_migrations` tracking table if it doesn't exist.
 *   - Runs every .sql file in /database in filename order, ONLY if it
 *     isn't already recorded as applied — so it's always safe to run
 *     this again later (new migration files get picked up, already-run
 *     ones are skipped).
 *   - Migration files are numbered (001_schema.sql, 002_..., 003_...) so
 *     plain alphabetical order IS run order — no special-casing needed
 *     to keep the base schema first. Number new migrations 004_, 005_,
 *     and so on, in the order they should run.
 *   - 001_schema.sql and 002_migrate-terms-license.sql are special-cased:
 *     if your database already has their end result (e.g. they were
 *     applied by hand before this tool existed), they're recorded as
 *     applied instead of being re-run and erroring on "table already
 *     exists" / "duplicate column".
 *   - Skips any CREATE DATABASE / USE statements found in a migration
 *     file (001_schema.sql has these hardcoded to a placeholder database
 *     name) — this always runs against whatever database your .env
 *     already points at.
 *   - Stops immediately on the first error, before marking that file as
 *     applied, so a failed migration will be retried next time you run
 *     this script (once you've fixed the cause).
 *
 * No Composer, no dependencies — matches the rest of the app.
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

require_once __DIR__ . '/../config/config.php';

$pdo = Database::connection();

// The app's Database class disables emulated prepares, which puts MySQL
// queries in unbuffered mode by default — a result set has to be fully
// drained (or explicitly closed) before another query can run on the
// same connection. This script issues several small lookup queries
// interleaved with the actual migration statements, so force buffered
// queries here to avoid "unbuffered queries are active" errors.
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
$databaseDir = dirname(__DIR__) . '/database';

// ---------------------------------------------------------------
// 1. Migration tracking table
// ---------------------------------------------------------------
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        migration VARCHAR(255) NOT NULL PRIMARY KEY,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB'
);

$applied = array_flip(
    $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN)
);

// ---------------------------------------------------------------
// 2. Discover .sql files. They're numbered (001_, 002_, 003_...) so
//    plain alphabetical sort already puts them in the right run order.
// ---------------------------------------------------------------
$files = glob($databaseDir . '/*.sql');
sort($files);

if (empty($files)) {
    echo "No .sql files found in {$databaseDir}.\n";
    exit(0);
}

// ---------------------------------------------------------------
// Files that add things non-idempotently (e.g. plain ADD COLUMN with
// no existence guard) need a targeted "did this already happen?"
// check, so a database that already has the end result — because it
// was applied by hand before this tool existed — gets marked as
// applied instead of erroring (e.g. "Duplicate column name").
// Map: filename => callable(PDO): bool
// ---------------------------------------------------------------
$alreadyAppliedChecks = [
    '001_schema.sql'                     => 'baseTablesAlreadyExist',
    '002_migrate-terms-license.sql'      => 'termsLicenseAlreadyApplied',
];

echo "Big Kahuna Car Hire — running migrations\n";
echo str_repeat('-', 42) . "\n";

$ranAny = false;

foreach ($files as $path) {
    $name = basename($path);

    if (isset($applied[$name])) {
        echo "  skip   {$name} (already applied)\n";
        continue;
    }

    if (isset($alreadyAppliedChecks[$name]) && $alreadyAppliedChecks[$name]($pdo)) {
        markApplied($pdo, $name);
        echo "  mark   {$name} (already present — recorded without running)\n";
        continue;
    }

    echo "  run    {$name} ... ";

    $sql = file_get_contents($path);
    if ($sql === false) {
        echo "FAILED (could not read file)\n";
        exit(1);
    }

    try {
        foreach (splitSqlStatements($sql) as $statement) {
            if ($statement === '' || isDatabaseSelectionStatement($statement)) {
                continue;
            }
            // query() + closeCursor() rather than exec(): some statements in
            // these files (PREPARE/EXECUTE of a dynamically-built SELECT, in
            // their "already applied, skipping" branches) return a result
            // set. exec() doesn't drain that, which leaves the connection
            // in a state where the next statement fails with "Cannot
            // execute queries while other unbuffered queries are active".
            $result = $pdo->query($statement);
            $result->closeCursor();
        }
        markApplied($pdo, $name);
        echo "OK\n";
        $ranAny = true;
    } catch (Throwable $e) {
        echo "FAILED\n\n";
        echo '  ' . $e->getMessage() . "\n\n";
        echo "Stopped before recording {$name} as applied. Fix the error above,\n";
        echo "then run `php bin/migrate.php` again — already-applied files will\n";
        echo "be skipped and it will pick up where it left off.\n";
        exit(1);
    }
}

echo str_repeat('-', 42) . "\n";
echo $ranAny ? "Migrations complete.\n" : "Already up to date — nothing to run.\n";

// =================================================================
// Helpers
// =================================================================

function markApplied(PDO $pdo, string $migration): void
{
    $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:m)')
        ->execute([':m' => $migration]);
}

/**
 * 001_schema.sql opens with CREATE DATABASE / USE statements hardcoded
 * to the name "bigkahuna_carhire". That breaks on any host where the real
 * database is named differently (e.g. shared hosting where the DB was
 * pre-created as part of the hosting account, like albatech_bigkahuna)
 * — it either fails on "access denied to create database", or silently
 * runs the rest of the script against the wrong database via USE.
 * This connection is already pointed at the correct database (DB_NAME
 * from .env), so these statements are simply skipped everywhere.
 */
function isDatabaseSelectionStatement(string $statement): bool
{
    return (bool) preg_match('/^\s*(CREATE\s+DATABASE|USE)\b/i', $statement);
}

/** Heuristic: has 001_schema.sql (or an equivalent manual import) already run? */
function baseTablesAlreadyExist(PDO $pdo): bool
{
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $exists = $stmt->fetch() !== false;
    $stmt->closeCursor();
    return $exists;
}

/**
 * Heuristic: has 002_migrate-terms-license.sql's effect already landed —
 * either by running it before, or because a newer 001_schema.sql already
 * ships bookings.id_number directly? Either way, the ALTER TABLE ADD
 * COLUMN in that file would fail with "Duplicate column name" if run
 * again, so detect and skip instead.
 */
function termsLicenseAlreadyApplied(PDO $pdo): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'id_number'"
    );
    $stmt->execute();
    $exists = ((int) $stmt->fetchColumn()) > 0;
    $stmt->closeCursor();
    return $exists;
}

/**
 * Split a .sql file into individual statements on ';', ignoring
 * semicolons and comment markers found inside quoted strings, and
 * skipping -- / # line comments (including inline trailing ones).
 * Good enough for plain DDL/DML — nothing here uses DELIMITER,
 * stored procedures, or triggers.
 *
 * @return string[]
 */
function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $inSingleQuote = false;
    $inDoubleQuote = false;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];

        // Backslash-escaped character inside a quoted string — copy
        // both characters through untouched, don't treat as a delimiter.
        if ($char === '\\' && ($inSingleQuote || $inDoubleQuote)) {
            $buffer .= $char . ($sql[$i + 1] ?? '');
            $i++;
            continue;
        }

        // Line comments ("-- " or "#"), only when not inside a string —
        // skip to end of line so any quote characters inside the comment
        // can't confuse the quote tracking below.
        if (!$inSingleQuote && !$inDoubleQuote) {
            $isDashComment = $char === '-' && ($sql[$i + 1] ?? '') === '-';
            if ($isDashComment || $char === '#') {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                $buffer .= "\n";
                continue;
            }
        }

        if ($char === "'" && !$inDoubleQuote) {
            $inSingleQuote = !$inSingleQuote;
        } elseif ($char === '"' && !$inSingleQuote) {
            $inDoubleQuote = !$inDoubleQuote;
        }

        if ($char === ';' && !$inSingleQuote && !$inDoubleQuote) {
            $statements[] = trim($buffer);
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    if (trim($buffer) !== '') {
        $statements[] = trim($buffer);
    }

    return $statements;
}
