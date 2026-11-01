<?php
if (!defined('_GNUBOARD_')) {
    $gl_root = __DIR__;
    while (!is_file($gl_root.'/common.php') && dirname($gl_root) !== $gl_root) $gl_root = dirname($gl_root);
    if (!is_file($gl_root.'/common.php')) { http_response_code(500); exit('아보카도 설치 폴더 안에 스킨을 설치해 주세요.'); }
    require_once $gl_root.'/common.php';
}
