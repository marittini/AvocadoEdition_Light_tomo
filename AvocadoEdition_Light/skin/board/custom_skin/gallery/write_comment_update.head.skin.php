<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/gallery.lib.php';
$gl_token = $_POST['gl_token'] ?? '';
if (!is_string($gl_token) || empty($_SESSION['gl_write_token']) || !hash_equals($_SESSION['gl_write_token'], $gl_token)) {
    alert('요청을 확인할 수 없습니다. 글을 새로 고친 뒤 다시 써 주세요.');
}
// 읽을 수 없는 글(멤버공개 · 보호글 · 비밀글)에는 댓글을 달 수 없다.
$gl_parent = sql_fetch("SELECT * FROM `{$write_table}` WHERE wr_id = ".(int)($_POST['wr_id'] ?? 0)." AND wr_is_comment = 0");
if (empty($gl_parent['wr_id']) || !gl_can_read($gl_parent)) alert('댓글을 달 수 없는 글입니다.');
