<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/guestbook.lib.php';
gb_check_token();
if (trim((string)($_POST['wr_content'] ?? '')) === '') alert('댓글 내용을 입력해 주세요.');
// 읽을 수 없는 비밀글에는 댓글을 달 수 없다.
$gb_parent = sql_fetch("SELECT wr_id, wr_num, mb_id, wr_option FROM `{$write_table}` WHERE wr_id = ".(int)($_POST['wr_id'] ?? 0)." AND wr_is_comment = 0");
if (empty($gb_parent['wr_id']) || !gb_can_read($gb_parent)) alert('댓글을 달 수 없는 글입니다.');
