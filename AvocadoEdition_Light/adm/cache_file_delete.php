<?php
$sub_menu = '900200';
include_once './_common.php';
if ($is_admin !== 'super') alert('최고관리자만 접근 가능합니다.', G5_URL);
$g5['title'] = '최신글 캐시 비우기';
$maintenance_kind = 'cache';
include './maintenance_form.inc.php';
