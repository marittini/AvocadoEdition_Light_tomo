<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
// 배너 한 개를 따로 보는 화면은 없다. 목록으로 보낸다(주소를 직접 연 경우).
goto_url(G5_BBS_URL.'/board.php?bo_table='.urlencode($bo_table));
