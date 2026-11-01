<?php
$sub_menu = '300700';
include_once('./_common.php');
include_once(G5_ADMIN_PATH.'/emoticon.lib.php');

check_demo();
auth_check($auth[$sub_menu] ?? '', 'w');
check_admin_token();
if (!emoticon_ready()) alert('이모티콘 테이블을 만들 수 없습니다. DB 권한을 확인해 주세요.');

$code = emoticon_clean_code($_POST['me_text'] ?? '');
if ($code === '') alert('코드를 확인해 주세요. 1~30자, 띄어쓰기 · 따옴표 · 꺾쇠는 쓸 수 없습니다.');
if (emoticon_code_taken($code)) alert('이미 등록된 코드입니다.');
$img = emoticon_store_upload($_FILES['me_img'] ?? []);

sql_query("INSERT INTO `{$g5['emoticon_table']}` SET me_text = '".sql_real_escape_string($code)."', me_img = '".sql_real_escape_string($img)."'");
goto_url('./emoticon_list.php');
