<?php
// 바뀌지 않는 배너 그림 주소: banner.php?bo_table=…&id=…
// 다른 사이트에 붙인 '내 배너'가 그림을 새로 올려도 같은 주소로 보이게, 지금 그림을 내보낸다(올린 그림이 없으면 그림 주소로 보낸다).
// 비공개 사이트여도 배너 그림은 보여야 하므로 로그인으로 보내지 않는다(아래 상수).
define('TOMO_PUBLIC_ASSET', true);
include_once __DIR__.'/_common.php';
include_once __DIR__.'/link.lib.php';

$lk_bo = preg_replace('/[^a-z0-9_]/i', '', (string)($_GET['bo_table'] ?? ''));
$lk_id = (int)($_GET['id'] ?? 0);
$lk_board = $lk_bo !== '' ? (sql_fetch("SELECT bo_table, bo_skin FROM {$g5['board_table']} WHERE bo_table = '".sql_real_escape_string($lk_bo)."'") ?: []) : [];
if (empty($lk_board['bo_table']) || ($lk_board['bo_skin'] ?? '') !== 'link' || $lk_id < 1) { http_response_code(404); exit; }
$lk_row = sql_fetch("SELECT wr_id, wr_option, wr_5, wr_link1 FROM `{$g5['write_prefix']}{$lk_bo}` WHERE wr_id = {$lk_id} AND wr_is_comment = 0");
// 공개된 '내 배너'만(비밀글 · 일반 배너 그림은 이 주소로 내보내지 않는다)
if (empty($lk_row['wr_id']) || strpos((string)$lk_row['wr_option'], 'secret') !== false || (string)$lk_row['wr_5'] !== '1') { http_response_code(404); exit; }

$lk_file = sql_fetch("SELECT bf_file FROM {$g5['board_file_table']} WHERE bo_table = '".sql_real_escape_string($lk_bo)."' AND wr_id = {$lk_id} AND bf_no = 0 AND bf_file <> '' AND bf_type IN (1, 2, 3, 18)");
$lk_path = !empty($lk_file['bf_file']) ? G5_DATA_PATH.'/file/'.$lk_bo.'/'.basename((string)$lk_file['bf_file']) : '';
if ($lk_path !== '' && is_file($lk_path)) {
    $lk_type = (new finfo(FILEINFO_MIME_TYPE))->file($lk_path);
    if (!in_array($lk_type, ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) { http_response_code(404); exit; }
    $lk_etag = '"'.md5($lk_path.filemtime($lk_path).filesize($lk_path)).'"';
    header('Content-Type: '.$lk_type);
    header('Cache-Control: public, max-age=600'); // 그림을 바꾸면 10분 안에 반영
    header('ETag: '.$lk_etag);
    header('X-Content-Type-Options: nosniff');
    if (trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $lk_etag) { http_response_code(304); exit; }
    header('Content-Length: '.filesize($lk_path));
    readfile($lk_path);
    exit;
}
$lk_url = lk_url($lk_row['wr_link1'] ?? '');
if ($lk_url !== '') { header('Location: '.$lk_url, true, 302); exit; }
http_response_code(404);
