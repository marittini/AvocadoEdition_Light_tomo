<?php
if (!defined('_GNUBOARD_')) {
    $bbscat_root = __DIR__;
    while (!is_file($bbscat_root.'/common.php') && dirname($bbscat_root) !== $bbscat_root) $bbscat_root = dirname($bbscat_root);
    if (!is_file($bbscat_root.'/common.php')) { http_response_code(500); exit('아보카도 설치 폴더 안에 스킨을 설치해 주세요.'); }
    require_once $bbscat_root.'/common.php';
}
