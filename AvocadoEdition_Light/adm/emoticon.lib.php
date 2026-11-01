<?php
if (!defined('_GNUBOARD_')) exit;
// 이모티콘 관리 공용: 테이블 · 폴더 준비, 업로드 검사.

if (empty($g5['emoticon_table'])) $g5['emoticon_table'] = G5_TABLE_PREFIX.'emoticon';

/** 테이블이 없으면 만든다(라이트 설치본에서 빠진 경우). */
function emoticon_ready(): bool
{
    global $g5;
    if (sql_query("DESCRIBE `{$g5['emoticon_table']}`", false)) return true;
    sql_query("CREATE TABLE IF NOT EXISTS `{$g5['emoticon_table']}` (
        `me_id` int(11) NOT NULL AUTO_INCREMENT,
        `me_text` varchar(255) NOT NULL DEFAULT '',
        `me_img` varchar(255) NOT NULL DEFAULT '',
        PRIMARY KEY (`me_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4", false);
    return (bool)sql_query("DESCRIBE `{$g5['emoticon_table']}`", false);
}

/** 이모티콘 코드: 공백 · 따옴표 · 꺾쇠 없이 1~30자. */
function emoticon_clean_code($value): string
{
    $code = trim(stripslashes((string)$value));
    if ($code === '' || mb_strlen($code) > 30 || preg_match('/[\s<>"\'&\\\\]/u', $code) || !preg_match('//u', $code)) return '';
    return $code;
}

function emoticon_code_taken(string $code, int $except = 0): bool
{
    global $g5;
    $row = sql_fetch("SELECT me_id FROM `{$g5['emoticon_table']}` WHERE me_text = '".sql_real_escape_string($code)."' AND me_id <> {$except} LIMIT 1", false);
    return !empty($row['me_id']);
}

/** 업로드한 이미지를 data/emoticon에 무작위 이름으로 저장하고 '/data/emoticon/…' 경로를 돌려준다. */
function emoticon_store_upload(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) alert('이미지를 올려 주세요.');
    if ((int)$file['size'] > 2 * 1024 * 1024) alert('이모티콘 이미지는 2MB 이하만 올릴 수 있습니다.');
    $info = @getimagesize($file['tmp_name']);
    $ext = [IMAGETYPE_GIF => 'gif', IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_WEBP => 'webp'][$info[2] ?? 0] ?? '';
    if ($ext === '') alert('gif · png · jpg · webp 이미지만 올릴 수 있습니다.');
    $dir = G5_DATA_PATH.'/emoticon';
    if (!is_dir($dir)) { @mkdir($dir, G5_DIR_PERMISSION); @chmod($dir, G5_DIR_PERMISSION); }
    if (!is_dir($dir) || !is_writable($dir)) alert('data/emoticon 폴더를 만들 수 없습니다. data 폴더의 쓰기 권한을 확인해 주세요.');
    $name = date('ymd').'_'.bin2hex(random_bytes(8)).'.'.$ext;
    if (!move_uploaded_file($file['tmp_name'], $dir.'/'.$name)) alert('이미지를 저장하지 못했습니다.');
    @chmod($dir.'/'.$name, G5_FILE_PERMISSION);
    return '/'.G5_DATA_DIR.'/emoticon/'.$name;
}

/** 이 관리 화면이 올린 파일만 지운다(경로 확인). */
function emoticon_delete_file(string $img): void
{
    $prefix = '/'.G5_DATA_DIR.'/emoticon/';
    if (strpos($img, $prefix) !== 0) return;
    $name = basename($img);
    if ($name === '' || $name !== substr($img, strlen($prefix))) return;
    @unlink(G5_DATA_PATH.'/emoticon/'.$name);
}
