<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/gallery.lib.php';
// 공개 범위 확정: 멤버공개(wr_secret=1), 보호글(wr_secret=1 + 비밀번호 해시), 비밀글(wr_option secret)
if ($w === 'c' || $w === 'cu' || empty($wr_id)) return;
$gl_visibility = (string)($_POST['set_secret'] ?? '');
$gl_secret = ($gl_visibility === 'secret' || (!$is_admin && (int)$board['bo_use_secret'] === 2)) ? 'secret' : '';
$gl_member = in_array($gl_visibility, ['member', 'protect'], true) ? 1 : 0;
$gl_set = "wr_option = '".sql_real_escape_string(implode(',', [$html, $gl_secret, $mail]))."', wr_secret = '{$gl_member}'";
if (gl_has_protect_column($write_table)) {
    $gl_hash = '';
    if ($gl_visibility === 'protect') {
        $gl_pw = stripslashes((string)($_POST['wr_protect'] ?? ''));
        $gl_hash = $gl_pw !== '' ? password_hash($gl_pw, PASSWORD_DEFAULT) : (string)($wr['wr_protect'] ?? '');
    }
    $gl_set .= ", wr_protect = '".sql_real_escape_string($gl_hash)."'";
}
sql_query("UPDATE `{$write_table}` SET {$gl_set} WHERE wr_id = '".(int)$wr_id."'");
