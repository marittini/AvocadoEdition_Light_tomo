<?php
if (!defined('_GNUBOARD_')) {
    $ott_root = __DIR__;
    while (!is_file($ott_root.'/common.php') && dirname($ott_root) !== $ott_root) $ott_root = dirname($ott_root);
    if (!is_file($ott_root.'/common.php')) { http_response_code(500); exit('아보카도 설치 폴더 안에 스킨을 설치해 주세요.'); }
    require_once $ott_root.'/common.php';
}
