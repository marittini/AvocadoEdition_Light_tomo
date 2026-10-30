<?php
$sub_menu = '100300';
include_once './_common.php';
check_demo();
if ($is_admin !== 'super') alert('최고관리자만 접근 가능합니다.');
check_admin_token();
require_once G5_LIB_PATH.'/tomo_settings.lib.php';
require_once G5_LIB_PATH.'/tomo_design.lib.php';
try {
    tomo_settings_upgrade();
    $result=(new TomoDesignStore())->save(array_map_deep('stripslashes',$_POST),$_FILES);
} catch (Throwable $e) {
    error_log('Design save: '.$e->getMessage());
    $message=$e instanceof TomoDesignConflict || $e instanceof InvalidArgumentException
        ? $e->getMessage()
        : '디자인을 저장하지 못했습니다. 입력은 유지됩니다. 복구가 완료되지 않은 경우 저장 폴더와 DB 권한을 확인해 주세요.';
    if ($e instanceof TomoDesignConflict && ($_SERVER['HTTP_X_ARCHIVE_SAVE'] ?? '') === '1') {
        while(ob_get_level())ob_end_clean();http_response_code(409);
        header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
        echo json_encode(['error'=>$message],JSON_UNESCAPED_UNICODE);exit;
    }
    alert($message);
}
tomo_settings_saved('./design_form.php',$result['fields'],$result['clear_files']);
