<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
// 배너는 따로 읽는 화면이 없으므로 저장 뒤 목록으로
goto_url(G5_HTTP_BBS_URL.'/board.php?bo_table='.urlencode($bo_table).($sca !== '' ? '&sca='.urlencode((string)$sca) : ''));
