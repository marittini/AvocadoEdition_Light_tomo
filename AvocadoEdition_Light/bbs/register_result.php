<?php
include_once('./_common.php');

if (isset($_SESSION['ss_mb_reg']))
    $mb = get_member($_SESSION['ss_mb_reg']);

// 회원정보가 없다면 초기 페이지로 이동
if (!$mb['mb_id'])
    goto_url(G5_URL);

$g5['title'] = '회원가입이 완료되었습니다.';
include_once('./_head.php');
if ($approval_message = tomo_member_approval_message($mb)) {
    echo '<div id="member_page"><h1 class="member-title">가입 신청 접수</h1><div class="member-contents"><p>'.get_text($approval_message).'</p><p>신청한 아이디: '.get_text($mb['mb_id']).'</p><a href="'.G5_BBS_URL.'/login.php" class="ui-btn">로그인 화면으로</a></div></div>';
} else include_once($member_skin_path.'/register_result.skin.php');
include_once('./_tail.php');
?>