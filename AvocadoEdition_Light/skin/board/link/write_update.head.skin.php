<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/link.lib.php';

// 배너 저장: 요청 확인, 주소 · 색 검사(그 밖의 값은 비운다), 새 배너는 맨 뒤 순서
if (!lk_check_token($_POST['lk_token'] ?? '')) alert('배너 저장 요청을 확인할 수 없습니다. 쓰기 화면을 다시 열어 주세요.');
$lk_raw = static fn(string $key, int $max = 1000): string => mb_substr(trim(stripslashes((string)($_POST[$key] ?? ''))), 0, $max);

$lk_name = $lk_raw('wr_1', 60);
if ($lk_name === '') alert('이름을 입력해 주세요.');
$wr_1 = sql_real_escape_string($lk_name);
$wr_subject = $wr_1;
$wr_link1 = sql_real_escape_string(lk_url($lk_raw('wr_link1')));
$wr_link2 = sql_real_escape_string(lk_url($lk_raw('wr_link2')));
$wr_2 = sql_real_escape_string($lk_raw('wr_2', 200));
$wr_3 = lk_color($lk_raw('wr_3'));
$wr_4 = lk_color($lk_raw('wr_4'));
if (!$is_admin) $wr_5 = $w === 'u' ? sql_real_escape_string((string)($wr['wr_5'] ?? '')) : ''; // '내 배너'는 관리자만
else $wr_5 = $lk_raw('wr_5') === '1' ? '1' : '';
if ($w === '' || $w === 'r') {
    $lk_last = sql_fetch("SELECT MAX(wr_10 + 0) AS n FROM `{$write_table}` WHERE wr_is_comment = 0");
    $wr_10 = (string)((int)($lk_last['n'] ?? 0) + 1);
} else {
    $wr_10 = (string)(int)($wr['wr_10'] ?? 0);
}
