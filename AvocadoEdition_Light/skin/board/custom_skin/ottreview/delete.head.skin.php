<?php
if (!defined('_GNUBOARD_')) exit;
if ($is_member) {
    $ott_delete_id = (int)$wr_id;
    $ott_delete_token = $_GET['token'] ?? '';
    $ott_expected = $_SESSION['ott_delete_tokens'][$ott_delete_id] ?? '';
    if (!is_string($ott_delete_token) || !$ott_expected || !hash_equals($ott_expected, $ott_delete_token)) alert('삭제 요청을 확인할 수 없습니다.');
    unset($_SESSION['ott_delete_tokens'][$ott_delete_id]);
}
