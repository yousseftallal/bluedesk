<?php
/**
 * PDO database connection (singleton).
 */
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

function app_config(?string $key = null)
{
    static $config = null;
    if ($config === null) {
        $file = APP_ROOT . '/config/config.php';
        if (!is_file($file)) {
            $file = APP_ROOT . '/config/config.example.php';
        }
        $config = require $file;
    }
    if ($key === null) {
        return $config;
    }
    $value = $config;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return null;
        }
        $value = $value[$part];
    }
    return $value;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $cfg = app_config('db');
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $cfg['host'], $cfg['port'], $cfg['name'], $cfg['charset']);
    try {
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        // Keep SQL NOW()/CURRENT_TIMESTAMP aligned with the application's legacy
        // storage zone, regardless of the database host's operating-system zone.
        $appTimezone = new DateTimeZone((string) (app_config('app.timezone') ?: 'Africa/Cairo'));
        $offsetMinutes = (int) ((new DateTimeImmutable('now', $appTimezone))->getOffset() / 60);
        $offsetSign = $offsetMinutes >= 0 ? '+' : '-';
        $offsetValue = sprintf('%s%02d:%02d', $offsetSign, intdiv(abs($offsetMinutes), 60), abs($offsetMinutes) % 60);
        $pdo->exec('SET time_zone = ' . $pdo->quote($offsetValue));
    } catch (PDOException $ex) {
        error_log('DB connection failed: ' . $ex->getMessage());
        http_response_code(500);
        exit('Database connection error. Please check the configuration.');
    }
    return $pdo;
}

function db_query(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_fetch(string $sql, array $params = []): ?array
{
    $row = db_query($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function db_fetch_all(string $sql, array $params = []): array
{
    return db_query($sql, $params)->fetchAll();
}

function db_count(string $table, string $where = '1', array $params = []): int
{
    return (int) db_query("SELECT COUNT(*) FROM `$table` WHERE $where", $params)->fetchColumn();
}
