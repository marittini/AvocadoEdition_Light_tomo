<?php
if (!defined('_GNUBOARD_')) exit;

/** Additive migration, invoked only from authenticated owner settings pages/saves. */
function tomo_settings_upgrade(): void
{
    global $g5, $config, $is_admin;
    if ($is_admin !== 'super') throw new RuntimeException('최고관리자만 설정을 갱신할 수 있습니다.');
    $columns = [];
    $result = sql_query("SHOW COLUMNS FROM `{$g5['config_table']}`");
    while ($row = sql_fetch_array($result)) $columns[$row['Field']] = true;
    foreach (['cf_tomo_fonts'=>'TEXT NULL', 'cf_tomo_webp_quality'=>'TINYINT UNSIGNED NOT NULL DEFAULT 95', 'cf_tomo_protection'=>'TINYINT UNSIGNED NOT NULL DEFAULT 0'] as $name=>$definition) {
        if (!isset($columns[$name])) {
            // Another administrator may have opened the page concurrently.
            if (!sql_query("ALTER TABLE `{$g5['config_table']}` ADD `$name` $definition", false)) {
                if (!sql_query("SELECT `$name` FROM `{$g5['config_table']}` LIMIT 1", false)) throw new RuntimeException('설정 필드 갱신에 실패했습니다. DB 권한을 확인해 주세요.');
            }
        }
    }
    $config = sql_fetch("SELECT * FROM `{$g5['config_table']}`");
}

function tomo_webp_quality(): int
{
    global $config;
    $value = $config['cf_tomo_webp_quality'] ?? 95;
    return filter_var($value, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0, 'max_range'=>100]]) !== false ? (int)$value : 95;
}

function tomo_validate_quality($value): int
{
    if (!is_scalar($value) || filter_var($value, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0, 'max_range'=>100]]) === false) {
        throw new InvalidArgumentException('WebP 품질은 0~100 사이의 정수로 입력해 주세요.');
    }
    return (int)$value;
}

function tomo_protection_mask(): int
{
    global $config;
    return ((int)($config['cf_tomo_protection'] ?? 0)) & 7;
}
