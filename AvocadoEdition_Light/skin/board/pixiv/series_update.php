<?php
// Saves or deletes a series from the series edit form (series.skin.php).
// 설치 폴더의 common.php를 위로 찾아 올라간다(스킨 폴더 위치가 바뀌어도 동작).
$px_root = __DIR__;
while (!is_file($px_root.'/common.php') && dirname($px_root) !== $px_root) $px_root = dirname($px_root);
include_once $px_root.'/common.php';
include_once __DIR__.'/pixiv.lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($board['bo_table'])) alert('올바른 방법으로 이용해 주십시오.');
$token = $_POST['token'] ?? '';
if (!is_string($token) || empty($_SESSION['pixiv_series_token']) || !hash_equals($_SESSION['pixiv_series_token'], $token)) alert('편집 인증이 만료되었습니다. 편집 화면을 다시 열어 주세요.');
if (empty($member['mb_id'])) alert('로그인 후 이용해 주세요.');
if (!$is_admin && (int)$member['mb_level'] < (int)$board['bo_write_level']) alert('글쓰기 권한이 없습니다.');
$ps_id = (int)($_POST['ps_id'] ?? 0);
$series = pixiv_series_get($bo_table, $ps_id);
if (!pixiv_series_can_edit($series)) alert('이 시리즈를 편집할 수 없습니다.');
$t = pixiv_series_tables();
$bo = sql_real_escape_string($bo_table);
$back = html_entity_decode(pixiv_series_url($bo_table, $ps_id));

if (($_POST['mode'] ?? '') === 'delete') {
    sql_query("DELETE FROM `{$t['works']}` WHERE bo_table='{$bo}' AND ps_id={$ps_id}");
    sql_query("DELETE FROM `{$t['series']}` WHERE ps_id={$ps_id}");
    goto_url(G5_BBS_URL.'/board.php?bo_table='.rawurlencode($bo_table).'&px_type=series');
}

$title = trim(stripslashes((string)($_POST['ps_title'] ?? '')));
$caption = trim(str_replace("\r\n", "\n", stripslashes((string)($_POST['ps_caption'] ?? ''))));
if ($title === '' || !preg_match('//u', $title) || mb_strlen($title) > 60) alert('시리즈 제목을 60자 이내로 입력해 주세요.');
if (!preg_match('//u', $caption) || mb_strlen($caption) > 1000) alert('시리즈 설명은 1,000자 이내로 입력해 주세요.');
$order = (string)($_POST['order'] ?? '');
if ($order !== '' && !preg_match('/^\d+(,\d+)*$/D', $order)) alert('순서를 확인해 주세요.');

// Only works already in this series can be reordered; anything missing from the list leaves the series.
$members = [];
$res = sql_query("SELECT wr_id FROM `{$t['works']}` WHERE bo_table='{$bo}' AND ps_id={$ps_id}");
while ($row = sql_fetch_array($res)) $members[(int)$row['wr_id']] = true;
$keep = [];
foreach ($order === '' ? [] : explode(',', $order) as $id) if (isset($members[(int)$id]) && !in_array((int)$id, $keep, true)) $keep[] = (int)$id;
foreach (array_keys($members) as $id) if (!in_array($id, $keep, true)) sql_query("DELETE FROM `{$t['works']}` WHERE bo_table='{$bo}' AND wr_id={$id} AND ps_id={$ps_id}");
foreach ($keep as $i => $id) sql_query("UPDATE `{$t['works']}` SET sw_order=".($i+1)." WHERE bo_table='{$bo}' AND wr_id={$id} AND ps_id={$ps_id}");
sql_query("UPDATE `{$t['series']}` SET ps_title='".sql_real_escape_string($title)."', ps_caption='".sql_real_escape_string($caption)."' WHERE ps_id={$ps_id}");
goto_url($back);
