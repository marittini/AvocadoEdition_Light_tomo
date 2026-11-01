<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/bbscategory.lib.php';
// 공개 범위 확정: 멤버공개(wr_secret=1), 보호글(wr_secret=1 + 비밀번호 해시), 비밀글(wr_option secret)
if ($w === 'c' || $w === 'cu' || empty($wr_id)) return;
$bbscat_visibility = (string)($_POST['set_secret'] ?? '');
$bbscat_secret = ($bbscat_visibility === 'secret' || (!$is_admin && (int)$board['bo_use_secret'] === 2)) ? 'secret' : '';
$bbscat_member = in_array($bbscat_visibility, ['member', 'protect'], true) ? 1 : 0;
$bbscat_set = "wr_option = '".sql_real_escape_string(implode(',', [$html, $bbscat_secret, $mail]))."', wr_secret = '{$bbscat_member}'";
if (bbscat_has_protect_column($write_table)) {
    $bbscat_hash = '';
    if ($bbscat_visibility === 'protect') {
        $bbscat_pw = stripslashes((string)($_POST['wr_protect'] ?? ''));
        $bbscat_hash = $bbscat_pw !== '' ? password_hash($bbscat_pw, PASSWORD_DEFAULT) : (string)($wr['wr_protect'] ?? '');
    }
    $bbscat_set .= ", wr_protect = '".sql_real_escape_string($bbscat_hash)."'";
}
sql_query("UPDATE {$write_table} SET {$bbscat_set} WHERE wr_id = '".(int)$wr_id."'");
// 작성자는 방금 쓴 보호글을 바로 볼 수 있다(본인 확인은 bbscat_can_read에서도 처리).
