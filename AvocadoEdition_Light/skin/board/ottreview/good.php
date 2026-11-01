<?php
include_once __DIR__.'/_common.php';
header('Content-Type: text/plain; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$is_member) exit('로그인 후 가능합니다.');
$token = $_POST['ott_token'] ?? '';
if (!is_string($token) || empty($_SESSION['ott_action_token']) || !hash_equals($_SESSION['ott_action_token'], $token)) exit('요청을 확인할 수 없습니다.');
if (empty($board['bo_table']) || empty($write['wr_id']) || (int)$member['mb_level'] < (int)$board['bo_read_level']) exit('글을 볼 권한이 없습니다.');
if (!empty($group['gr_use_access']) && !$is_admin) {
    $access = sql_fetch("SELECT gm_id FROM {$g5['group_member_table']} WHERE gr_id='".sql_real_escape_string($group['gr_id'])."' AND mb_id='".sql_real_escape_string($member['mb_id'])."'", false);
    if (empty($access['gm_id'])) exit('글을 볼 권한이 없습니다.');
}
if ((str_contains($write['wr_option'] ?? '', 'secret') || !empty($write['wr_secret']) || !empty($write['wr_protect'])) && !$is_admin && $write['mb_id'] !== $member['mb_id'] && !get_session('ss_secret_'.$bo_table.'_'.$write['wr_num'])) exit('글을 볼 권한이 없습니다.');
if ($write['mb_id'] === $member['mb_id']) exit('자신의 글에는 좋아요 할 수 없습니다.');
$mid = sql_real_escape_string($member['mb_id']);
$id = (int)$write['wr_id'];
$row = sql_fetch("SELECT bg_flag FROM {$g5['board_good_table']} WHERE bo_table='{$bo_table}' AND wr_id='{$id}' AND mb_id='{$mid}' AND bg_flag='good'", false);
if (!empty($row['bg_flag'])) exit('이미 좋아요 하신 글입니다.');
if (!sql_query("INSERT INTO {$g5['board_good_table']} SET bo_table='{$bo_table}', wr_id='{$id}', mb_id='{$mid}', bg_flag='good', bg_datetime='".G5_TIME_YMDHIS."'", false)) exit('좋아요를 처리하지 못했습니다.');
sql_query("UPDATE {$write_table} SET wr_good=wr_good+1 WHERE wr_id='{$id}'");
echo 'y';
