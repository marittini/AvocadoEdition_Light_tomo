<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/gallery.lib.php';

$gl_token = $_POST['gl_token'] ?? '';
if (!is_string($gl_token) || empty($_SESSION['gl_write_token']) || !hash_equals($_SESSION['gl_write_token'], $gl_token)) {
    alert('글쓰기 요청을 확인할 수 없습니다. 작성 화면을 다시 열어 주세요.');
}

// 종류(wr_2): art 그림 / text 글
$wr_2 = ($_POST['gl_kind'] ?? '') === 'text' ? 'text' : 'art';
// 출처(wr_1): 일반 텍스트
$wr_1 = sql_real_escape_string(mb_substr(trim(strip_tags(stripslashes((string)($_POST['wr_1'] ?? '')))), 0, 255));

// 공개 범위: '' 전체공개 / member 멤버공개 / protect 보호글 / secret 비밀글
$gl_visibility = $_POST['set_secret'] ?? '';
if (!is_string($gl_visibility) || !isset(GL_VISIBILITY[$gl_visibility])) alert('공개 설정을 확인해 주세요.');
if (in_array($gl_visibility, ['member', 'protect'], true) && !$is_member) alert('회원만 사용할 수 있는 공개 설정입니다.');
if ($gl_visibility === 'secret' && !$is_admin && empty($board['bo_use_secret'])) alert('비밀글을 사용할 수 없는 게시판입니다.');
if ($gl_visibility === 'protect') {
    if (!gl_has_protect_column($write_table)) alert('보호글 기능이 준비되지 않았습니다. 최고관리자가 글쓰기 화면을 한 번 열어 주세요.');
    $gl_pw = $_POST['wr_protect'] ?? '';
    if (!is_string($gl_pw) || strlen(stripslashes($gl_pw)) > 20 || ($gl_pw === '' && ($w !== 'u' || empty($wr['wr_protect'])))) alert('보호글 비밀번호를 입력해 주세요.');
}
// 멤버공개 · 보호글은 저장과 첨부 처리가 끝날 때까지 비밀글로 두었다가 write_update.skin.php에서 확정한다.
$wr_secret = in_array($gl_visibility, ['member', 'protect'], true) ? 1 : 0;
if ($gl_visibility === 'secret' || $wr_secret) $secret = 'secret';
