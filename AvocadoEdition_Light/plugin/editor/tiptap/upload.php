<?php
include_once '../../../common.php';
include_once G5_LIB_PATH.'/archive.lib.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function tomo_upload_fail(int $status, string $message): never { http_response_code($status);echo json_encode(['error'=>$message], JSON_UNESCAPED_UNICODE);exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($_POST['editor_token'] ?? null) || empty($_SESSION['tomo_editor_token']) || !hash_equals($_SESSION['tomo_editor_token'], $_POST['editor_token'])) tomo_upload_fail(403, '업로드 인증이 만료되었습니다. 작성 내용을 보관한 뒤 화면을 다시 열어 주세요.');
if (!$is_admin && (empty($member['mb_id']) || empty($board['bo_table']) || $member['mb_level'] < max($board['bo_write_level'], $board['bo_html_level']) || !$board['bo_use_dhtml_editor'])) tomo_upload_fail(403, '이미지를 업로드할 권한이 없습니다.');
$file = $_FILES['image'] ?? [];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) tomo_upload_fail(422, '파일을 받지 못했습니다. 서버 업로드 한도와 파일 크기를 확인해 주세요.');
if ($file['size'] > 20 * 1024 * 1024) tomo_upload_fail(422, '이미지는 20MB 이하로 첨부해 주세요.');
$info = @getimagesize($file['tmp_name']);
$extension = [IMAGETYPE_JPEG=>'jpg', IMAGETYPE_PNG=>'png', IMAGETYPE_GIF=>'gif', IMAGETYPE_WEBP=>'webp'][$info[2] ?? 0] ?? null;
if (!$extension || $info[0] * $info[1] > 24000000) tomo_upload_fail(422, 'JPG·PNG·GIF·WebP 이미지를 첨부해 주세요. 최대 2,400만 화소까지 지원합니다.');
$directory = G5_DATA_PATH.'/editor/'.date('ym', G5_SERVER_TIME);
if (!is_dir($directory) && !@mkdir($directory, G5_DIR_PERMISSION, true) && !is_dir($directory)) tomo_upload_fail(422, '이미지 폴더를 만들지 못했습니다. 저장 공간과 폴더 쓰기 권한을 확인해 주세요.');
$destination = $directory.'/'.bin2hex(random_bytes(16)).'.'.$extension;
if (!@move_uploaded_file($file['tmp_name'], $destination)) tomo_upload_fail(422, '이미지를 저장하지 못했습니다. 저장 공간과 폴더 쓰기 권한을 확인해 주세요.');
$notice = '';
try { $result = archive_convert_saved_upload($destination); $destination = $result['path']; }
catch (Throwable $e) { error_log('Editor conversion: '.$e->getMessage());$notice = '이미지 변환을 사용할 수 없어 원본으로 첨부했습니다.'; }
@chmod($destination, G5_FILE_PERMISSION);
echo json_encode(['url'=>G5_DATA_URL.'/editor/'.date('ym', G5_SERVER_TIME).'/'.basename($destination),'notice'=>$notice], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
