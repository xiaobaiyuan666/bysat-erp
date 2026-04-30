<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$command = $argv[1] ?? '';
if (!in_array($command, ['dump', 'import-file'], true)) {
    fwrite(STDERR, "Usage: php mysql_utf8_tool.php dump|import-file --host=... --port=... --user=... --password=... --database=... --file=...\n");
    exit(1);
}

$options = parseOptions(array_slice($argv, 2));
$host = requireOption($options, 'host');
$port = requireOption($options, 'port');
$user = requireOption($options, 'user');
$password = (string) ($options['password'] ?? '');
$database = requireOption($options, 'database');
$file = requireOption($options, 'file');

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database),
    $user,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
    ]
);

if ($command === 'dump') {
    dumpDatabase($pdo, $database, $file);
    exit(0);
}

importSqlFile($pdo, $file);
exit(0);

/**
 * @param array<int, string> $args
 * @return array<string, string>
 */
function parseOptions(array $args): array
{
    $options = [];
    foreach ($args as $arg) {
        if (strpos($arg, '--') !== 0 || strpos($arg, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', substr($arg, 2), 2);
        $options[$key] = $value;
    }

    return $options;
}

/**
 * @param array<string, string> $options
 */
function requireOption(array $options, string $key): string
{
    $value = trim((string) ($options[$key] ?? ''));
    if ($value === '') {
        fwrite(STDERR, "Missing option --{$key}\n");
        exit(1);
    }

    return $value;
}

function dumpDatabase(PDO $pdo, string $database, string $file): void
{
    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Failed to create directory: ' . $dir);
    }

    $sql = [];
    $sql[] = "SET NAMES utf8mb4;";
    $sql[] = "SET FOREIGN_KEY_CHECKS = 0;";
    $sql[] = "";

    $tables = fetchTables($pdo, $database);
    foreach ($tables as $table) {
        $sql[] = sprintf("DROP TABLE IF EXISTS `%s`;", $table);
        $createRow = $pdo->query(sprintf("SHOW CREATE TABLE `%s`", $table))->fetch(PDO::FETCH_ASSOC);
        $createSql = (string) ($createRow['Create Table'] ?? '');
        if ($createSql === '') {
            throw new RuntimeException('Unable to fetch CREATE TABLE for ' . $table);
        }
        $sql[] = $createSql . ';';
        $sql[] = '';

        foreach (buildInsertStatements($pdo, $table) as $insert) {
            $sql[] = $insert;
        }

        $sql[] = '';
    }

    $sql[] = "SET FOREIGN_KEY_CHECKS = 1;";
    $content = implode("\n", $sql) . "\n";
    file_put_contents($file, $content);
}

function importSqlFile(PDO $pdo, string $file): void
{
    if (!is_file($file)) {
        throw new RuntimeException('SQL file not found: ' . $file);
    }

    $sql = (string) file_get_contents($file);
    if ($sql === '') {
        throw new RuntimeException('SQL file is empty: ' . $file);
    }

    $pdo->exec($sql);
}

/**
 * @return array<int, string>
 */
function fetchTables(PDO $pdo, string $database): array
{
    $statement = $pdo->prepare(
        'SELECT table_name FROM information_schema.tables WHERE table_schema = :schema AND table_type = :type ORDER BY table_name'
    );
    $statement->execute([
        ':schema' => $database,
        ':type' => 'BASE TABLE',
    ]);

    return $statement->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

/**
 * @return array<int, string>
 */
function buildInsertStatements(PDO $pdo, string $table): array
{
    $rows = $pdo->query(sprintf("SELECT * FROM `%s`", $table))->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        return [];
    }

    $columns = array_keys($rows[0]);
    $columnSql = implode(', ', array_map(static fn(string $column): string => sprintf('`%s`', $column), $columns));
    $statements = [];
    $batch = [];

    foreach ($rows as $row) {
        $values = [];
        foreach ($columns as $column) {
            $value = $row[$column];
            if ($value === null) {
                $values[] = 'NULL';
                continue;
            }

            if (is_bool($value)) {
                $values[] = $value ? '1' : '0';
                continue;
            }

            if (is_int($value) || is_float($value)) {
                $values[] = (string) $value;
                continue;
            }

            $values[] = $pdo->quote((string) $value);
        }

        $batch[] = '(' . implode(', ', $values) . ')';
        if (count($batch) >= 100) {
            $statements[] = sprintf("INSERT INTO `%s` (%s) VALUES\n%s;", $table, $columnSql, implode(",\n", $batch));
            $batch = [];
        }
    }

    if (!empty($batch)) {
        $statements[] = sprintf("INSERT INTO `%s` (%s) VALUES\n%s;", $table, $columnSql, implode(",\n", $batch));
    }

    return $statements;
}
