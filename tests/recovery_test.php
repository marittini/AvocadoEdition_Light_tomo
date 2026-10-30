<?php
// Only a disposable test database may be used. Requires CREATE DATABASE permission.
$database = getenv('TEST_DB');
if (!$database || !str_starts_with($database, 'avocado_')) throw new RuntimeException('Use a disposable avocado_* database.');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli('localhost', getenv('TEST_DB_USER') ?: 'root', getenv('TEST_DB_PASSWORD') ?: '', $database, null, getenv('TEST_SOCKET') ?: null);
$db->set_charset('utf8');
$target = 'avocado_restore_'.bin2hex(random_bytes(4));
$backup = [];
$tables = $db->query('SHOW TABLES');
while ($row = $tables->fetch_row()) {
    $table = $row[0];
    $create = $db->query('SHOW CREATE TABLE `'.$db->real_escape_string($table).'`')->fetch_row()[1];
    $inserts = [];
    $records = $db->query('SELECT * FROM `'.$db->real_escape_string($table).'`');
    while ($record = $records->fetch_assoc()) {
        $values = array_map(static fn($v) => $v === null ? 'NULL' : "'".$db->real_escape_string($v)."'", array_values($record));
        $inserts[] = 'INSERT INTO `'.$table.'` VALUES ('.implode(',', $values).')';
    }
    $backup[$table] = [$create,$inserts];
}
$db->query('CREATE DATABASE `'.$target.'` CHARACTER SET utf8');
try {
    $db->select_db($target); $db->query("SET SESSION sql_mode = ''");
    foreach ($backup as $table=>[$create,$inserts]) {
        $db->query($create);
        foreach ($inserts as $sql) $db->query($sql);
        $expected = count($inserts);
        $actual = (int)$db->query('SELECT count(*) FROM `'.$table.'`')->fetch_row()[0];
        if ($actual !== $expected) throw new RuntimeException('Row count mismatch: '.$table);
    }
    echo 'PASS SQL export/restore: '.count($backup).' tables into a separate database'.PHP_EOL;
} finally { $db->query('DROP DATABASE `'.$target.'`'); }
