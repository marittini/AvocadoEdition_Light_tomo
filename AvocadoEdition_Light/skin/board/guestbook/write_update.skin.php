<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/guestbook.lib.php';
gb_return($bo_table, $write_table, (int)$wr_id);
