<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/otaku.lib.php';
// 프로필 내용은 글 번호가 정해진 뒤 저장한다(새 글·AU 포함).
if (isset($otaku_data) && in_array($w, ['', 'u', 'r'], true) && !empty($wr_id)) otaku_save($bo_table, (int)$wr_id, $otaku_data);
