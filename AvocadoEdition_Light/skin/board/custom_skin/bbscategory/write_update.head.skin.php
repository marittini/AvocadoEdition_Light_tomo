<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/bbscategory.lib.php';
// 원본 스킨은 코어 bbs/write_update.php의 분류 검사를 지우도록 안내했지만,
// 이 이식본은 게시판 '분류'를 사용하지 않고 여기서 말머리를 ca_name에 넣으므로 코어 수정이 필요 없다.

$bbscat_token = $_POST['bbscat_token'] ?? '';
if (!is_string($bbscat_token) || empty($_SESSION['bbscat_write_token']) || !hash_equals($_SESSION['bbscat_write_token'], $bbscat_token)) {
    alert('글쓰기 요청을 확인할 수 없습니다. 작성 화면을 다시 열어 주세요.');
}
if (!empty($board['bo_use_category'])) alert('이 스킨은 제목의 말머리로 카테고리를 만듭니다. 게시판 설정에서 분류 사용을 꺼 주세요.');

// 말머리) 제목 → ca_name
[$bbscat_genre] = bbscat_split_subject(stripslashes((string)$wr_subject));
$ca_name = sql_real_escape_string(mb_substr($bbscat_genre, 0, 40));

// 공개 범위: '' 전체공개 / secret 비밀글 / member 멤버공개 / protect 보호글
$bbscat_visibility = $_POST['set_secret'] ?? '';
if (!is_string($bbscat_visibility) || !in_array($bbscat_visibility, ['', 'secret', 'member', 'protect'], true)) alert('공개 설정을 확인해 주세요.');
if (in_array($bbscat_visibility, ['member', 'protect'], true) && !$is_member) alert('회원만 사용할 수 있는 공개 설정입니다.');
if ($bbscat_visibility === 'secret' && !$is_admin && empty($board['bo_use_secret'])) alert('비밀글을 사용할 수 없는 게시판입니다.');
if ($bbscat_visibility === 'protect') {
    if (!bbscat_has_protect_column($write_table)) alert('보호글 기능이 준비되지 않았습니다. 최고관리자가 글쓰기 화면을 한 번 열어 주세요.');
    $bbscat_pw = $_POST['wr_protect'] ?? '';
    if (!is_string($bbscat_pw) || strlen(stripslashes($bbscat_pw)) > 20 || ($bbscat_pw === '' && ($w !== 'u' || empty($wr['wr_protect'])))) alert('보호글 비밀번호를 입력해 주세요.');
}
if ($bbscat_visibility === 'secret') $secret = 'secret';
// 멤버공개·보호글은 저장과 첨부 처리가 끝날 때까지 비밀글로 두었다가 write_update.skin.php에서 확정한다.
$wr_secret = in_array($bbscat_visibility, ['member', 'protect'], true) ? 1 : 0;
if ($wr_secret) $secret = 'secret';
