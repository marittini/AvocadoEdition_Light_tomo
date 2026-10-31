<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
// 방명록은 글 하나만 따로 보는 화면이 없다. 목록의 그 글 자리로 보낸다(비밀번호 확인 뒤 등).
include_once __DIR__.'/guestbook.lib.php';
gb_return($bo_table, $write_table, (int)($view['wr_parent'] ?: $view['wr_id']));
