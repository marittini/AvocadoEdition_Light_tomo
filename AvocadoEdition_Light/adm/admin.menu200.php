<?php
// 가입 승인 대기 인원(있으면 메뉴 이름 옆에 표시). 승인 필드가 아직 없으면 0.
$tomo_pending = sql_fetch("SELECT COUNT(*) AS n FROM `{$g5['member_table']}` WHERE mb_tomo_approval = 'pending' AND mb_leave_date = ''", false);
$tomo_pending = (int)($tomo_pending['n'] ?? 0);
$menu['menu200'] = array (
    array('200000', '회원관리', G5_ADMIN_URL.'/member_list.php', ''),
    array('200100', '회원관리', G5_ADMIN_URL.'/member_list.php', ''),
    array('200110', '가입 승인'.($tomo_pending ? ' ('.$tomo_pending.')' : ''), G5_ADMIN_URL.'/member_approval.php', ''),
    array('200800', '접속자집계', G5_ADMIN_URL.'/visit_list.php', ''),
    array('200820', '접속자로그삭제', G5_ADMIN_URL.'/visit_delete.php', '')
);
?>