<?php
// A real DB test using a connection-local TEMPORARY table, never the installed config table.
require __DIR__.'/../AvocadoEdition_Light/config.php';
require __DIR__.'/../AvocadoEdition_Light/lib/common.lib.php';
require __DIR__.'/../AvocadoEdition_Light/lib/tomo_settings.lib.php';
if (!getenv('MARIADB_DATABASE') || !getenv('MARIADB_USER')) throw new RuntimeException('Set disposable MARIADB_* credentials.');
$g5['connect_db'] = new mysqli(getenv('TEST_DB_HOST') ?: 'tomo-test-db',getenv('MARIADB_USER'),getenv('MARIADB_PASSWORD'),getenv('MARIADB_DATABASE'));
$g5['config_table']='tomo_settings_migration_test';
sql_query("CREATE TEMPORARY TABLE tomo_settings_migration_test (cf_title VARCHAR(255), cf_add_fonts TEXT)");
sql_query("INSERT INTO tomo_settings_migration_test VALUES ('기존 사이트', '@font-face {font-family:Old}')");
$is_admin='super';$config=[];
tomo_settings_upgrade();tomo_settings_upgrade();
if ($config['cf_title']!=='기존 사이트' || $config['cf_add_fonts']!=='@font-face {font-family:Old}' || tomo_webp_quality()!==95 || tomo_protection_mask()!==0) throw new RuntimeException('Upgrade lost existing values or defaults.');
sql_query("UPDATE tomo_settings_migration_test SET cf_tomo_fonts='[]', cf_tomo_webp_quality=0, cf_tomo_protection=7");
tomo_settings_upgrade();
if (tomo_webp_quality()!==0 || tomo_protection_mask()!==7 || $config['cf_tomo_fonts']!=='[]') throw new RuntimeException('Repeated migration reset settings.');
$is_admin='';try {tomo_settings_upgrade();throw new LogicException('Missing owner restriction.');} catch (RuntimeException $e) {if($e instanceof LogicException)throw $e;}
echo "PASS additive migration twice, legacy values preserved, zero quality retained, non-owner rejected\n";
