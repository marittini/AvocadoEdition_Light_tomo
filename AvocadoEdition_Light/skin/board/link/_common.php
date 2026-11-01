<?php
if (!defined('_GNUBOARD_')) {
    $lk_root = __DIR__;
    while (!is_file($lk_root.'/common.php') && dirname($lk_root) !== $lk_root) $lk_root = dirname($lk_root);
    if (!is_file($lk_root.'/common.php')) { http_response_code(500); exit('아보카도 설치 폴더 안에 스킨을 설치해 주세요.'); }
    require_once $lk_root.'/common.php';
}
