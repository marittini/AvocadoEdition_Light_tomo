<?php
define('_GNUBOARD_', true); define('G5_BBS_URL', '/bbs');
function get_file($board, $post) { return ['count'=>2, 0=>['file'=>'first.png','image_type'=>3], 1=>['file'=>'second.png','image_type'=>3]]; }
require __DIR__.'/../AvocadoEdition_Light/skin/board/custom_skin/pixiv/pixiv.lib.php';
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); echo "PASS $message\n"; }
$legacy=pixiv_files('test',1,'1,0');
check(array_column($legacy,'index')===[1,0], 'old saved order is retained');
check(pixiv_cover_file($legacy,'1,0')['index']===1, 'old posts default to the first ordered image');
$files=pixiv_files('test',1,'1,0;c:0');
check(array_column($files,'index')===[1,0], 'cover metadata does not change page order');
check(pixiv_cover_file($files,'1,0;c:0')['index']===0, 'cover can differ from first page');
check(pixiv_cover_file($files,'1,0;c:9')['index']===1, 'missing cover falls back to first image');
check(pixiv_cover_file([], '')===null, 'empty works have no cover');
