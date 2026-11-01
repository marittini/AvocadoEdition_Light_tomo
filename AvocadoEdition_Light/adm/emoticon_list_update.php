<?php
$sub_menu = '300700';
include_once('./_common.php');
include_once(G5_ADMIN_PATH.'/emoticon.lib.php');

check_demo();
$act = (string)($_POST['act_button'] ?? '');
auth_check($auth[$sub_menu] ?? '', $act === '선택삭제' ? 'd' : 'w');
check_admin_token();
if (!emoticon_ready()) alert('이모티콘 테이블을 만들 수 없습니다.');

$chk = is_array($_POST['chk'] ?? null) ? $_POST['chk'] : [];
if (!$chk) alert($act.' 하실 이모티콘을 하나 이상 선택하세요.');
$ids = is_array($_POST['me_id'] ?? null) ? $_POST['me_id'] : [];
$texts = is_array($_POST['me_text'] ?? null) ? $_POST['me_text'] : [];

foreach ($chk as $k) {
    if (!is_scalar($k) || !isset($ids[$k])) continue;
    $id = (int)$ids[$k];
    $row = sql_fetch("SELECT * FROM `{$g5['emoticon_table']}` WHERE me_id = {$id}", false);
    if (empty($row['me_id'])) continue;

    if ($act === '선택수정') {
        $code = emoticon_clean_code($texts[$k] ?? '');
        if ($code === '') alert('코드를 확인해 주세요: '.get_text($row['me_text']));
        if (emoticon_code_taken($code, $id)) alert('이미 등록된 코드입니다: '.get_text($code));
        sql_query("UPDATE `{$g5['emoticon_table']}` SET me_text = '".sql_real_escape_string($code)."' WHERE me_id = {$id}");
    } elseif ($act === '선택삭제') {
        sql_query("DELETE FROM `{$g5['emoticon_table']}` WHERE me_id = {$id}");
        emoticon_delete_file((string)$row['me_img']);
    } else {
        alert('올바른 방법으로 이용해 주십시오.');
    }
}
goto_url('./emoticon_list.php');
