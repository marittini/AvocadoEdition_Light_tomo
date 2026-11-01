<?php
if (!defined('_GNUBOARD_')) exit;
$ott_token = $_POST['ott_write_token'] ?? '';
if (!is_string($ott_token) || empty($_SESSION['ott_write_token']) || !hash_equals($_SESSION['ott_write_token'], $ott_token)) {
    alert('글쓰기 요청을 확인할 수 없습니다. 작성 화면을 다시 열어 주세요.');
}
$ott_column = sql_fetch("SHOW COLUMNS FROM `{$write_table}` LIKE 'wr_protect'", false);
if (empty($ott_column['Field'])) alert('스킨 초기 설정이 완료되지 않았습니다.');
include_once __DIR__.'/ott.lib.php';
if (!empty($_POST['wr_is_comment']) && $w !== 'u') {
    $ott_parent_id = (int)($_POST['pa_wr_id'] ?? 0);
    $ott_parent = get_write($write_table, $ott_parent_id);
    if (!$ott_parent_id || empty($ott_parent['wr_id']) || !empty($ott_parent['wr_is_comment']) || !ott_can_access_board() || !ott_can_read_post($ott_parent)) alert('원본 글에 답변할 권한이 없습니다.');
    $_POST['pa_wr_num'] = $ott_parent['wr_num'];
}
$ott_visibility = $_POST['set_secret'] ?? '';
if (!is_string($ott_visibility) || !in_array($ott_visibility, ['', 'secret', 'member', 'protect'], true)) alert('공개 설정을 확인해 주세요.');
if (in_array($ott_visibility, ['member', 'protect'], true) && !$is_member) alert('회원만 사용할 수 있는 공개 설정입니다.');
if ($ott_visibility === 'secret' && !$is_admin && empty($board['bo_use_secret'])) alert('비밀글을 사용할 수 없는 게시판입니다.');
if ($ott_visibility === 'protect') {
    $ott_password = $_POST['wr_protect'] ?? '';
    if (!is_string($ott_password) || strlen(stripslashes($ott_password)) > 20 || ($ott_password === '' && ($w !== 'u' || empty($wr['wr_protect'])))) alert('보호글 비밀번호를 입력해 주세요.');
}
if ($ott_visibility === 'secret') $secret = 'secret';
// Keep newly inserted or updated restricted posts private while uploads are processed.
if (in_array($ott_visibility, ['member', 'protect'], true)) {
    $secret = 'secret';
    $wr_secret = 1;
}
