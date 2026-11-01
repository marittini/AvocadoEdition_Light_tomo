<?php
$sub_menu = '300700';
include_once('./_common.php');
include_once(G5_LIB_PATH.'/tomo_settings.lib.php');

check_demo();
auth_check($auth[$sub_menu] ?? '', 'w');
check_admin_token();
if (!array_key_exists('cf_tomo_emoticon_size', $config)) {
    if ($is_admin !== 'super') alert('최고관리자가 이모티콘관리 화면을 한 번 열어 주세요.');
    tomo_settings_upgrade();
}
$size = max(0, min(400, (int)($_POST['emoticon_size'] ?? 0)));
sql_query("UPDATE `{$g5['config_table']}` SET cf_tomo_emoticon_size = {$size}");
goto_url('./emoticon_list.php');
