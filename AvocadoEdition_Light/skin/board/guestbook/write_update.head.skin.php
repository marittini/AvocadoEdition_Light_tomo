<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/guestbook.lib.php';
gb_check_token();
// 본문은 일반 텍스트로만 저장한다. 제목은 목록·최신글에 쓰이도록 본문 앞부분으로 정한다.
$gb_plain = trim(str_replace("\r\n", "\n", stripslashes((string)($_POST['wr_content'] ?? ''))));
if ($gb_plain === '') alert('내용을 입력해 주세요.');
$gb_line = preg_replace('/\s+/u', ' ', $gb_plain);
$wr_subject = sql_real_escape_string(mb_strimwidth($gb_line, 0, 60, '…', 'UTF-8'));
$html = '';
