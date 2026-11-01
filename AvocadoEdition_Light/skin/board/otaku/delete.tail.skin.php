<?php
if (!defined('_GNUBOARD_')) exit;
include_once __DIR__.'/otaku.lib.php';
// 지워진 글의 프로필 내용을 정리한다.
otaku_cleanup($bo_table, $write_table);
