<?php
if (!defined('_GNUBOARD_')) exit;
include_once __DIR__.'/pixiv.lib.php';
$token = $_POST['pixiv_token'] ?? '';
if (!is_string($token) || empty($_SESSION['pixiv_write_token']) || !hash_equals($_SESSION['pixiv_write_token'], $token)) alert('작성 인증이 만료되었습니다. 작성 화면을 다시 열어 주세요.');
if ($w === 'u' && !in_array($wr['wr_1'] ?? '', ['pixiv:illustration','pixiv:manga','pixiv:novel'], true)) alert('기존 스킨 게시물은 이 스킨에서 수정할 수 없습니다. 기존 스킨으로 수정해 주세요.');
$postedContent = $_POST['wr_content'] ?? '';
if (!is_string($postedContent) || strlen($postedContent) > 65536) alert('본문이 너무 깁니다. 나누어 작성해 주세요.');
$type = $_POST['pixiv_type'] ?? '';
if (!is_string($type) || !in_array($type,['illustration','manga','novel'],true)) alert('작품 분류를 선택해 주세요.');
if ($type === 'novel' && tomo_normalize_editor_html(stripslashes($postedContent)) === '') alert('소설 본문을 입력해 주세요.');
$raw = $_POST['pixiv_tags'] ?? '';
if (!is_string($raw)) alert('태그를 확인해 주세요.');
$tags=[];
foreach (preg_split('/[,，\n]+/u', stripslashes($raw)) as $tag) {
    $tag = trim(ltrim(trim($tag), '#'));
    if ($tag === '') continue;
    if (!preg_match('/^[\p{L}\p{N}_-]{1,30}$/u',$tag)) alert('태그는 글자, 숫자, 밑줄과 하이픈만 사용할 수 있습니다.');
    if (!in_array($tag,$tags,true)) $tags[]=$tag;
}
$encoded = $tags ? '|'.implode('|',$tags).'|' : '';
if (strlen($encoded)>240 || count($tags)>20) alert('태그가 너무 많습니다.');
$order = $_POST['pixiv_order'] ?? '';
if (!is_string($order) || strlen($order)>250 || ($order !== '' && !preg_match('/^\d+(,\d+)*$/D',$order))) alert('이미지 순서를 확인해 주세요.');
$note = $_POST['pixiv_note'] ?? '';
if (!is_string($note)) alert('설명을 확인해 주세요.');
$note = stripslashes($note);
if (strlen($note)>1600 || !preg_match('//u',$note)) alert('설명은 한글 기준 약 500자 이내로 입력해 주세요.');
$wr_1=sql_real_escape_string('pixiv:'.$type);
$wr_2=sql_real_escape_string($encoded);
// The cover slot is checked after the final attachment set is known.
$cover=$_POST['pixiv_cover'] ?? '';
if (!is_string($cover) || ($cover !== '' && !ctype_digit($cover))) alert('대표 이미지를 확인해 주세요.');
$parts=array_fill(4,7,'');$i=4;
foreach (preg_split('//u',$note,-1,PREG_SPLIT_NO_EMPTY) as $ch) {
    if (strlen($parts[$i].$ch)>240) $i++;
    if ($i>10) alert('설명이 너무 깁니다.');
    $parts[$i].=$ch;
}
for($i=4;$i<=10;$i++){ $var='wr_'.$i; $$var=sql_real_escape_string($parts[$i]); }

// Validate uploads before the engine writes the post or removes any old file.
$existing = $w === 'u' ? get_file($bo_table, (int)$wr_id) : [];
$slots=[];
foreach ($existing as $key=>$file) {
    if (!is_int($key) || empty($file['file'])) continue;
    if (!in_array((int)$file['image_type'], [1,2,3,18], true)) alert('이미지가 아닌 첨부파일이 있는 글은 이 스킨에서 수정할 수 없습니다.');
    if (empty($_POST['bf_file_del'][$key])) $slots[$key]=true;
}
$upload=$_FILES['bf_file'] ?? [];
if (isset($upload['name']) && !is_array($upload['name'])) alert('첨부파일 형식을 확인해 주세요.');
foreach (($upload['name'] ?? []) as $key=>$filename) {
    if ($filename === '') continue;
    $err=$upload['error'][$key] ?? UPLOAD_ERR_NO_FILE;
    $tmp=$upload['tmp_name'][$key] ?? '';
    if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) alert('이미지 업로드에 실패했습니다. 용량과 파일을 확인해 주세요.');
    $info=@getimagesize($tmp);
    if (!$info || !in_array((int)$info[2],[IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_GIF,IMAGETYPE_WEBP],true)) alert('JPG, PNG, GIF, WebP 이미지 파일만 첨부할 수 있습니다.');
    if (!preg_match('/\.(jpe?g|png|gif|webp)$/i',$filename)) alert('이미지 확장자를 확인해 주세요.');
    if (!$is_admin && (int)$upload['size'][$key] > (int)$board['bo_upload_size']) alert('게시판의 파일 크기 제한을 초과했습니다.');
    $slots[(int)$key]=true;
}
if (count($slots)>(int)$board['bo_upload_count']) alert('게시판의 첨부 가능 개수를 초과했습니다.');
if ($type!=='novel' && !$slots) alert('이미지를 1장 이상 첨부해 주세요.');

if ($cover !== '' && !isset($slots[(int)$cover])) alert('선택한 대표 이미지를 확인해 주세요.');
$storedOrder = $order.($cover !== '' ? ';c:'.(int)$cover : '');
if (strlen($storedOrder)>250) alert('이미지 순서가 너무 깁니다.');
$wr_3=sql_real_escape_string($storedOrder);
