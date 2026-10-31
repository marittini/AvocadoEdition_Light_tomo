<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가 
$wr_subject2 = sql_real_escape_string(trim(stripslashes((string)($_POST['wr_subject2'] ?? ''))));
$wr_is_comment = !empty($_POST['wr_is_comment']);
$pa_wr_id = (int)($_POST['pa_wr_id'] ?? 0);
$pa_wr_num = (int)($_POST['pa_wr_num'] ?? 0);
$set_secret = $_POST['set_secret'] ?? '';
$ott_password = stripslashes((string)($_POST['wr_protect'] ?? ''));
$wr_protect = '';
if ($set_secret === 'protect') {
    $wr_protect = $ott_password !== '' ? password_hash($ott_password, PASSWORD_DEFAULT) : (string)($wr['wr_protect'] ?? '');
}
$wr_protect = sql_real_escape_string($wr_protect);

$add_sql = '';

if ($wr_subject2) {
    $add_sql .= ", wr_subject = '{$wr_subject2}'";
} else {
    $add_sql .= ", wr_subject = ''";
}

if ($wr_is_comment && $w !='u') {
    $co_count = sql_fetch("SELECT wr_comment FROM {$write_table} WHERE wr_parent = '{$pa_wr_id}' and wr_is_comment = '1' ORDER BY wr_comment DESC");
    $co_cnt = (int)($co_count['wr_comment'] ?? 0) + 1;
    $add_sql .= ", wr_num = '{$pa_wr_num}'";
    $add_sql .= ", wr_parent = '{$pa_wr_id}'";
    $add_sql .= ", wr_is_comment = '1'";
    $add_sql .= ", wr_comment = '{$co_cnt}'";
}

if($w!='c' && $w!='cu'){

$temp_wr_id = $wr_id;

$sec = ($set_secret === 'secret' || (!$is_admin && (int)$board['bo_use_secret'] === 2)) ? 'secret' : '';
$mem=0;
$protect="";
if($set_secret) {

	if($set_secret=='secret'){
		$sec="secret";
	} 
	else if ($set_secret=='member'){
		$mem=1;
	}
	else if($set_secret == 'protect' && $wr_protect!=''){
		$protect=$wr_protect;
		$mem=1;
	}
}
	sql_query("update {$write_table} set wr_option='$html,$sec', wr_secret='{$mem}', wr_protect= '{$wr_protect}' {$add_sql} where wr_id='{$wr_id}'");
}

// 게시글 카운트 조정
if ($wr_is_comment && $w != 'u') {
    sql_query("update {$g5['board_table']} set bo_count_write = bo_count_write - 1, bo_count_comment = bo_count_comment + 1 where bo_table = '{$bo_table}'");
}

$new_wr_id = $pa_wr_id ? $pa_wr_id : $wr_id;

// 최신글 조정
sql_query("UPDATE {$g5['board_new_table']} SET wr_parent = '{$new_wr_id}' WHERE wr_id = '{$wr_id}' AND bo_table = '{$bo_table}'");

$go_wr_id = $pa_wr_id ? $pa_wr_id : $wr_id;

if (!empty($_REQUEST['is_view'])) {
    goto_url(G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table.'&amp;wr_id='.$go_wr_id.'&amp;bottom=true'.$qstr);
} else {
    goto_url(G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table.$qstr);
}

?>
