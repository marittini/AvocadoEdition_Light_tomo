<?php
include_once './_common.php';
include_once G5_LIB_PATH.'/archive.lib.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($is_admin !== 'super' || $_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(403); exit; }
if (!isset($_POST['archive_token']) || !hash_equals($_SESSION['archive_token'] ?? '', (string)$_POST['archive_token'])) { http_response_code(403); exit; }
try {
    $action = $_POST['action'] ?? '';
    if ($action === 'disk_start') $_SESSION['archive_scan'] = archive_disk_start(G5_PATH);
    if (in_array($action, ['disk_start','disk_step','disk_status'], true)) {
        if ($action !== 'disk_status' && isset($_SESSION['archive_scan']) && !$_SESSION['archive_scan']['finished']) {
            $_SESSION['archive_scan'] = archive_disk_step($_SESSION['archive_scan']);
        }
        $state = $_SESSION['archive_scan'] ?? null;
        if ($state) {
            unset($state['root'], $state['queue']);
            $state['db_bytes'] = null;
            try {
                $db = sql_fetch('SELECT SUM(DATA_LENGTH + INDEX_LENGTH) AS bytes FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()', false);
                if (isset($db['bytes']) && is_numeric($db['bytes'])) $state['db_bytes'] = (int)$db['bytes'];
            } catch (Throwable $e) { error_log('Disk DB estimate: '.$e->getMessage()); }
        }
        echo json_encode(['scan'=>$state], JSON_UNESCAPED_UNICODE); exit;
    }
    http_response_code(400);
    echo json_encode(['error'=>'지원하지 않는 작업입니다.'], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    error_log('Archive admin operation: '.$e->getMessage());
    http_response_code(422);
    echo json_encode(['error'=>'작업을 완료하지 못했습니다. 입력은 유지됩니다. 서버 로그와 지원 범위를 확인해 주세요.'], JSON_UNESCAPED_UNICODE);
}
