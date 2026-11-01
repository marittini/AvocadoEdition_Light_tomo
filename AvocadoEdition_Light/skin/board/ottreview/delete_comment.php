<?php
// 코멘트 삭제
include_once __DIR__.'/_common.php';
include_once G5_LIB_PATH.'/archive.lib.php';

$comment_id = (int) $comment_id;

$delete_comment_token = get_session('ss_delete_comment_'.$comment_id.'_token');
set_session('ss_delete_comment_'.$comment_id.'_token', '');

if (!($token && $delete_comment_token == $token))
    alert('토큰 에러로 삭제 불가합니다.');

// 4.1
@include_once($board_skin_path.'/delete_comment.head.skin.php');

$write = sql_fetch(" select * from {$write_table} where wr_id = '{$comment_id}' ");

if (!$write['wr_id'] || !$write['wr_is_comment'])
    alert('등록된 코멘트가 없거나 코멘트 글이 아닙니다.');

if ($is_admin == 'super') // 최고관리자 통과
    ;
else if ($is_admin == 'group') { // 그룹관리자
    $mb = get_member($write['mb_id']);
    if ($member['mb_id'] === $group['gr_admin']) { // 자신이 관리하는 그룹인가?
        if ($member['mb_level'] >= $mb['mb_level']) // 자신의 레벨이 크거나 같다면 통과
            ;
        else
            alert('그룹관리자의 권한보다 높은 회원의 코멘트이므로 삭제할 수 없습니다.');
    } else
        alert('자신이 관리하는 그룹의 게시판이 아니므로 코멘트를 삭제할 수 없습니다.');
} else if ($is_admin === 'board') { // 게시판관리자이면
    $mb = get_member($write['mb_id']);
    if ($member['mb_id'] === $board['bo_admin']) { // 자신이 관리하는 게시판인가?
        if ($member['mb_level'] >= $mb['mb_level']) // 자신의 레벨이 크거나 같다면 통과
            ;
        else
            alert('게시판관리자의 권한보다 높은 회원의 코멘트이므로 삭제할 수 없습니다.');
    } else
        alert('자신이 관리하는 게시판이 아니므로 코멘트를 삭제할 수 없습니다.');
} else if ($member['mb_id']) {
    if ($member['mb_id'] !== $write['mb_id'])
        alert('자신의 글이 아니므로 삭제할 수 없습니다.');
} else {
    if (!check_password($wr_password, $write['wr_password']))
        alert('비밀번호가 틀립니다.');
}

$go_wr_id = $write['wr_parent'];
if (($write['wr_reply'] ?? '') === '') {
    $children = sql_fetch("SELECT COUNT(*) AS cnt FROM {$write_table} WHERE wr_parent=".(int)$comment_id." AND wr_is_comment=1", false);
    if (!empty($children['cnt'])) alert('답글이 있는 타래는 답글을 먼저 삭제해 주세요.');
}
$ott_files = sql_query("SELECT bf_file FROM {$g5['board_file_table']} WHERE bo_table='".sql_real_escape_string($bo_table)."' AND wr_id=".(int)$comment_id, false);
while ($ott_files && $ott_file = sql_fetch_array($ott_files)) {
    $name = $ott_file['bf_file'];
    if ($name !== '' && basename($name) === $name) {
        $path = G5_DATA_PATH.'/file/'.$bo_table.'/'.$name;
        archive_delete_companions($path);
        @unlink($path);
        delete_board_thumbnail($bo_table, $name);
    }
}
sql_query("DELETE FROM {$g5['board_file_table']} WHERE bo_table='".sql_real_escape_string($bo_table)."' AND wr_id=".(int)$comment_id);

// 코멘트 삭제
sql_query(" delete from {$write_table} where wr_id = '{$comment_id}' ");
if (($write['wr_reply'] ?? '') !== '') sql_query("UPDATE {$write_table} SET wr_comment=GREATEST(wr_comment-1,0) WHERE wr_id=".(int)$go_wr_id);
sql_query("UPDATE {$g5['board_table']} SET bo_count_comment=GREATEST(bo_count_comment-1,0) WHERE bo_table='".sql_real_escape_string($bo_table)."'");


// 새글 삭제
sql_query(" delete from {$g5['board_new_table']} where bo_table = '{$bo_table}' and wr_id = '{$comment_id}' ");

// 사용자 코드 실행
@include_once($board_skin_path.'/delete_comment.skin.php');
@include_once($board_skin_path.'/delete_comment.tail.skin.php');

delete_cache_latest($bo_table);

if (!empty($_REQUEST['is_view'])) {
    goto_url(G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table.'&amp;wr_id='.$go_wr_id.$qstr);
} else {
    goto_url(G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table.$qstr);
}
?>
