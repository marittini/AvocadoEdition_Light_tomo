<?php
// 배너 관리 저장(관리자, POST + 토큰). 순서는 받은 차례대로 1, 2, 3 …
include_once __DIR__.'/_common.php';
include_once __DIR__.'/link.lib.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') alert('올바른 방법으로 이용해 주세요.');
if (empty($board['bo_table']) || ($board['bo_skin'] ?? '') !== 'link') alert('링크 게시판이 아닙니다.', G5_URL);
if (!$is_admin) alert('관리자만 배너를 한꺼번에 관리할 수 있습니다.');
if (!lk_check_token($_POST['lk_token'] ?? '')) alert('저장 요청을 확인할 수 없습니다. 관리 화면을 다시 열어 주세요.');

$raw = static fn($value, int $max = 1000): string => is_string($value) ? mb_substr(trim(stripslashes($value)), 0, $max) : '';
$esc = static fn(string $v): string => sql_real_escape_string($v);
$cats = !empty($board['bo_use_category']) ? array_values(array_filter(array_map('trim', explode('|', (string)$board['bo_category_list'])), 'strlen')) : [];

// 표시 방식(게시판 여분 필드)
$pc = max(0, min(8, (int)($_POST['lk_pc'] ?? 0)));
$mobile = max(0, min(4, (int)($_POST['lk_mobile'] ?? 0)));
$home = !empty($_POST['lk_home']) ? 'home' : '';
sql_query("UPDATE {$g5['board_table']} SET bo_1 = '{$pc}', bo_2 = '{$mobile}', bo_3 = '{$home}' WHERE bo_table = '".$esc($bo_table)."'");

$ids = is_array($_POST['ids'] ?? null) ? array_values(array_unique(array_map('intval', $_POST['ids']))) : [];
$order = 0;
foreach ($ids as $id) {
    if ($id < 1) continue;
    $row = sql_fetch("SELECT wr_id FROM `{$write_table}` WHERE wr_id = {$id} AND wr_is_comment = 0");
    if (empty($row['wr_id'])) continue;
    $order++;
    $name = $raw($_POST['name'][$id] ?? '', 60);
    $set = ["wr_10 = '{$order}'"];
    if ($name !== '') { $set[] = "wr_1 = '".$esc($name)."'"; $set[] = "wr_subject = '".$esc($name)."'"; }
    $set[] = "wr_link2 = '".$esc(lk_url($raw($_POST['href'][$id] ?? '')))."'";
    if (array_key_exists($id, (array)($_POST['img'] ?? []))) $set[] = "wr_link1 = '".$esc(lk_url($raw($_POST['img'][$id])))."'";
    $set[] = "wr_2 = '".$esc($raw($_POST['tip'][$id] ?? '', 200))."'";
    $set[] = "wr_5 = '".(!empty($_POST['mine'][$id]) ? '1' : '')."'";
    if ($cats) {
        $cat = $raw($_POST['cat'][$id] ?? '', 255);
        $set[] = "ca_name = '".$esc(in_array($cat, $cats, true) ? $cat : '')."'";
    }
    sql_query("UPDATE `{$write_table}` SET ".implode(', ', $set)." WHERE wr_id = {$id}");
}
goto_url(G5_SKIN_URL.'/board/link/manage.php?bo_table='.urlencode($bo_table).'&saved=1');
