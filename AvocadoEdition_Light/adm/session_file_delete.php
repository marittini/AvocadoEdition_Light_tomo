<?php
$sub_menu = '900100';
include_once './_common.php';
if ($is_admin !== 'super') alert('최고관리자만 접근 가능합니다.', G5_URL);
$g5['title'] = '오래된 세션 정리';
$maintenance_kind = 'session';
include './maintenance_form.inc.php';
