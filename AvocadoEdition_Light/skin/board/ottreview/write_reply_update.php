<?php
include_once __DIR__.'/_common.php';
include_once __DIR__.'/ott.lib.php';

// 토큰체크
$comment_token = trim(get_session('ss_comment_token'));
set_session('ss_comment_token', '');
if (!$comment_token || !isset($_POST['token']) || !is_string($_POST['token']) || !hash_equals($comment_token, $_POST['token'])) {
    alert('댓글 요청을 확인할 수 없습니다. 다시 시도해 주세요.');
}

$w = $_POST["w"] ?? '';
$wr_name  = trim((string)($_POST['wr_name'] ?? ''));
$html = $wr_subject = $wr_secret = '';
$wr_noname = !empty($_POST['wr_noname']) ? '1' : '0';
$is_view = !empty($_POST['is_view']);
$wr_email = '';
if (!empty($_POST['wr_email']))
    $wr_email = get_email_address(trim($_POST['wr_email']));

// 비회원의 경우 이름이 누락되는 경우가 있음
if ($is_guest) {
    if ($wr_name == '')
        alert('이름은 필히 입력하셔야 합니다.'); 
}

if ($w == "r" || $w == "ru") {
    if ($member['mb_level'] < $board['bo_comment_level'])
        alert('댓글을 쓸 권한이 없습니다.');
}
else
    alert('w 값이 제대로 넘어오지 않았습니다.');

if ($w == 'r' && (int)($_SESSION['ss_datetime'] ?? 0) >= (G5_SERVER_TIME - $config['cf_delay_sec']) && !$is_admin)
    alert('너무 빠른 시간내에 게시물을 연속해서 올릴 수 없습니다.');

set_session('ss_datetime', G5_SERVER_TIME);

$wr = get_write($write_table, $wr_id);
    if (!$wr['wr_id']) {
        alert("글이 존재하지 않습니다.\\n글이 삭제되었거나 이동하였을 수 있습니다.");
}
$ott_parent = !empty($wr['wr_is_comment']) ? get_write($write_table, (int)$wr['wr_parent']) : $wr;
if (!ott_can_access_board() || empty($ott_parent['wr_id']) || !ott_can_read_post($ott_parent) || !ott_can_read_post($wr)) alert('댓글을 쓸 권한이 없습니다.');
if ($w === 'ru') {
    $ott_comment = get_write($write_table, (int)$comment_id);
    if (empty($ott_comment['wr_id']) || empty($ott_comment['wr_is_comment']) || (int)$ott_comment['wr_parent'] !== (int)$wr_id) alert('수정할 댓글이 없습니다.');
}

if ($is_member)
{
    $mb_id = $member['mb_id'];
    // 4.00.13 - 실명 사용일때 댓글에 닉네임으로 입력되던 오류를 수정
    $wr_name = addslashes(clean_xss_tags($board['bo_use_name'] ? $member['mb_name'] : $member['mb_nick']));
    $wr_password = $member['mb_password'];
    $wr_email = addslashes($member['mb_email']);
    $wr_homepage = addslashes(clean_xss_tags($member['mb_homepage']));
}
else
{
    $mb_id = '';
    $wr_password = get_encrypt_string($wr_password);
}

$wr_content = '';
if (isset($_POST['wr_content'])) {
	$wr_content = $_POST['wr_content'];
    $wr_content = preg_replace("#[\\\]+$#", "", $wr_content);
}

if (substr_count($wr_content, '&#') > 50) {
    alert('내용에 올바르지 않은 코드가 다수 포함되어 있습니다.');
    exit;
}

for ($i=1; $i<=10; $i++) {
    $var = "wr_$i";
    $$var = "";
    if (isset($_POST['wr_'.$i]) && settype($_POST['wr_'.$i], 'string')) {
        $$var = trim($_POST['wr_'.$i]);
    }
}

$secret = '';
if (isset($_POST['secret']) && $_POST['secret']) {
    if(preg_match('#secret#', strtolower($_POST['secret']), $matches))
        $secret = $matches[0];
}

if ($w == 'r') // 댓글 입력
{

$sql = " select max(wr_reply) as max_reply from $write_table
                    where wr_parent = '$wr_id' and wr_reply != '' ";
$row = sql_fetch($sql);
$row['max_reply'] = (int)($row['max_reply'] ?? 0) + 1;
$tmp_comment_reply = $row['max_reply'];


$sql = " insert into $write_table
                set ca_name = '{$wr['ca_name']}',
                     wr_option = '$html,$secret',
                     wr_num = '{$wr['wr_num']}',
                     wr_reply = '$tmp_comment_reply',
                     wr_parent = '$wr_id',
                     wr_is_comment = '1',
                     wr_comment = '0',
                     wr_comment_reply = '',
                     wr_subject = '$wr_subject',
                     wr_content = '$wr_content',
                     mb_id = '$mb_id',
                     wr_password = '$wr_password',
                     wr_name = '$wr_name',
                     wr_email = '$wr_email',
                     wr_homepage = '$wr_homepage',
                     wr_datetime = '".G5_TIME_YMDHIS."',
                     wr_last = '',
                     wr_ip = '{$_SERVER['REMOTE_ADDR']}', 

					 wr_noname = '$wr_noname', 

                     wr_1 = '$wr_1',
                     wr_2 = '$wr_2',
                     wr_3 = '$wr_3',
                     wr_4 = '$wr_4',
                     wr_5 = '$wr_5',
                     wr_6 = '$wr_6',
                     wr_7 = '$wr_7',
                     wr_8 = '$wr_8',
                     wr_9 = '$wr_9',
                     wr_10 = '$wr_10' ";
sql_query($sql);

$reply_id = sql_insert_id();
$comment_id = $reply_id;
sql_query("UPDATE {$write_table} SET wr_comment=wr_comment+1, wr_last='".G5_TIME_YMDHIS."' WHERE wr_id=".(int)$wr_id);
sql_query("UPDATE {$g5['board_table']} SET bo_count_comment=bo_count_comment+1 WHERE bo_table='".sql_real_escape_string($bo_table)."'");

} else if ($w == 'ru') // 댓글 수정
{
    $sql = " select mb_id, wr_password, wr_comment, wr_comment_reply from $write_table
                where wr_id = '$comment_id' ";
    $comment = $reply_array = sql_fetch($sql);

    if ($is_admin == 'super') // 최고관리자 통과
        ;
    else if ($is_admin == 'group') { // 그룹관리자
        $mb = get_member($comment['mb_id']);
        if ($member['mb_id'] == $group['gr_admin']) { // 자신이 관리하는 그룹인가?
            if ($member['mb_level'] >= $mb['mb_level']) // 자신의 레벨이 크거나 같다면 통과
                ;
            else
                alert('그룹관리자의 권한보다 높은 회원의 댓글이므로 수정할 수 없습니다.');
        } else
            alert('자신이 관리하는 그룹의 게시판이 아니므로 댓글을 수정할 수 없습니다.');
    } else if ($is_admin == 'board') { // 게시판관리자이면
        $mb = get_member($comment['mb_id']);
        if ($member['mb_id'] == $board['bo_admin']) { // 자신이 관리하는 게시판인가?
            if ($member['mb_level'] >= $mb['mb_level']) // 자신의 레벨이 크거나 같다면 통과
                ;
            else
                alert('게시판관리자의 권한보다 높은 회원의 댓글이므로 수정할 수 없습니다.');
        } else
            alert('자신이 관리하는 게시판이 아니므로 댓글을 수정할 수 없습니다.');
    } else if ($member['mb_id']) {
        if ($member['mb_id'] != $comment['mb_id'])
            alert('자신의 글이 아니므로 수정할 수 없습니다.');
    } else {
        if(!check_password((string)($_POST['wr_password'] ?? ''), $comment['wr_password']))
            alert('댓글을 수정할 권한이 없습니다.');
    }

    $sql_ip = "";
    if (!$is_admin)
        $sql_ip = " , wr_ip = '{$_SERVER['REMOTE_ADDR']}' ";

    $sql_secret = "";
    if ($wr_secret)
		$secret=$wr_secret;
       // $sql_secret = " , wr_option = '$wr_secret' ";

    $sql = " update $write_table
                set wr_subject = '$wr_subject',
                     wr_content = '$wr_content',
                     wr_1 = '$wr_1',
                     wr_2 = '$wr_2',
                     wr_3 = '$wr_3',
                     wr_4 = '$wr_4',
                     wr_5 = '$wr_5',
                     wr_6 = '$wr_6',
                     wr_7 = '$wr_7',
                     wr_8 = '$wr_8',
                     wr_9 = '$wr_9',
                     wr_10 = '$wr_10', 
                     wr_option = '$html,$secret'
                     $sql_ip
                     $sql_secret
              where wr_id = '$comment_id' ";
    sql_query($sql);
}

delete_cache_latest($bo_table);

if ($is_view) {
    goto_url(G5_BBS_URL.'/board.php?bo_table='.$bo_table.'&amp;wr_id='.$wr['wr_parent'].'&amp;'.$qstr.'&amp;#c_'.$comment_id);
} else {
    goto_url(G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table.$qstr.'&amp;#c_'.$comment_id);
}

?>
