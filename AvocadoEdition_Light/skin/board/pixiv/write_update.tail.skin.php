<?php
if (!defined('_GNUBOARD_')) exit;
include_once __DIR__.'/pixiv.lib.php';
// $pixiv_series_plan is prepared by write_update.head.skin.php. Comments never reach here.
if (!in_array($w, ['', 'u', 'r'], true) || empty($wr_id)) return;
$px_t = pixiv_series_tables();
if (!$px_t) return;
$px_bo = sql_real_escape_string($bo_table);
$px_now = G5_TIME_YMDHIS;
$px_current = pixiv_series_of_work($bo_table, (int)$wr_id);
if (!$pixiv_series_plan) {
    if ($px_current) sql_query("DELETE FROM `{$px_t['works']}` WHERE bo_table='{$px_bo}' AND wr_id=".(int)$wr_id);
    return;
}
if ($pixiv_series_plan['new']) {
    sql_query("INSERT INTO `{$px_t['series']}` SET bo_table='{$px_bo}', mb_id='".sql_real_escape_string($pixiv_series_plan['owner'])."',
        ps_kind='".sql_real_escape_string($pixiv_series_plan['kind'])."', ps_title='".sql_real_escape_string($pixiv_series_plan['title'])."',
        ps_caption='', ps_datetime='{$px_now}', ps_update='{$px_now}'");
    $px_series_id = (int)sql_insert_id();
} else {
    $px_series_id = (int)$pixiv_series_plan['id'];
}
if ($px_series_id < 1) return;
if (!$px_current || (int)$px_current['ps_id'] !== $px_series_id) {
    // A newly added work becomes the latest episode.
    $px_last = sql_fetch("SELECT MAX(sw_order) AS n FROM `{$px_t['works']}` WHERE bo_table='{$px_bo}' AND ps_id={$px_series_id}");
    $px_order = (int)($px_last['n'] ?? 0) + 1;
    sql_query("REPLACE INTO `{$px_t['works']}` SET bo_table='{$px_bo}', wr_id=".(int)$wr_id.", ps_id={$px_series_id}, sw_order={$px_order}");
}
sql_query("UPDATE `{$px_t['series']}` SET ps_update='{$px_now}' WHERE ps_id={$px_series_id}");
