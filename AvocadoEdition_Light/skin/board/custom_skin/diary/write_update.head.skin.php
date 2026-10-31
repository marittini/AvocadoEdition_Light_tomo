<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
// 코어 write_update.php는 글쓰기 토큰 검사가 꺼져 있어, 스킨 작성 화면에서 온 요청인지 확인한다.
$diary_token = $_POST['diary_token'] ?? '';
if (!is_string($diary_token) || empty($_SESSION['diary_write_token']) || !hash_equals($_SESSION['diary_write_token'], $diary_token)) {
    alert('글쓰기 요청을 확인할 수 없습니다. 작성 화면을 다시 열어 주세요.');
}
