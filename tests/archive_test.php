<?php
define('_GNUBOARD_', true);
require __DIR__.'/../AvocadoEdition_Light/lib/archive.lib.php';
function expect($ok,$message){if(!$ok)throw new RuntimeException($message);echo "PASS $message\n";}
$root=sys_get_temp_dir().'/archive-tests-'.bin2hex(random_bytes(5));mkdir($root);mkdir($root.'/data');mkdir($root.'/data/file');mkdir($root.'/data/file/log');
$image=imagecreatetruecolor(17,13);imagealphablending($image,false);imagesavealpha($image,true);$clear=imagecolorallocatealpha($image,120,60,30,127);imagefill($image,0,0,$clear);imagesetpixel($image,3,3,imagecolorallocatealpha($image,40,50,60,0));
$source=$root.'/data/file/log/a.png';imagepng($image,$source);$original=hash_file('sha256',$source);$result=archive_convert_saved_upload($source);
expect($result['converted']&&getimagesize($result['path'])[2]===IMAGETYPE_WEBP,'PNG becomes valid WebP');expect(!file_exists($source),'source removed only after verified WebP conversion');expect(!file_exists($result['path'].'.archive.json'),'new uploads do not leave original metadata');
$decoded=imagecreatefromwebp($result['path']);expect((imagecolorat($decoded,0,0)>>24)===127,'transparent alpha preserved');expect((imagecolorat($decoded,3,3)&0xffffff)===(imagecolorat($image,3,3)&0xffffff),'lossless pixels preserved');
expect(!archive_convert_saved_upload($result['path'])['converted'],'existing WebP not recompressed');
$gif=$root.'/still.gif';imagegif($image,$gif);$gifData=file_get_contents($gif);$gifResult=archive_convert_saved_upload($gif);expect($gifResult['converted']&&!is_file($gif),'static GIF replaced by WebP');
$packed=ord($gifData[10]);$start=13+(($packed&128)?3*(1<<(($packed&7)+1)):0);
$animated=$root.'/animated.gif';file_put_contents($animated,substr($gifData,0,-1).substr($gifData,$start,-1)."\x3b");expect(archive_gif_is_animated($animated)&&!archive_convert_saved_upload($animated)['converted']&&is_file($animated),'animated GIF is not flattened or deleted');
$bmp=$root.'/picture.bmp';imagebmp($image,$bmp);$bmpResult=archive_convert_saved_upload($bmp);expect($bmpResult['converted']&&!is_file($bmp),'BMP replaced by WebP');
file_put_contents($root.'/document.pdf','document');file_put_contents($root.'/data/file/log/not-image.png','not an image');expect(!archive_convert_saved_upload($root.'/document.pdf')['converted'],'document is not converted');
symlink('/etc',$root.'/outside');
$scan=archive_disk_start($root);$steps=0;do{$scan=archive_disk_step($scan,2);$steps++;}while(!$scan['finished']&&$steps<100);
$expected=0;foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS)) as $f){if(!$f->isLink()&&$f->isFile())$expected+=$f->getSize();}
expect($scan['finished']&&$steps>1,'scan resumes across bounded batches');expect($scan['bytes']===$expected,'scan exact logical file sum');expect($scan['skipped_links']===1,'external symlink skipped');expect(array_sum($scan['categories'])===$scan['bytes'],'categories disjoint');
// Keep deletion compatibility for originals created by the previous release.
$legacy=$root.'/legacy.webp';file_put_contents($legacy,'derivative');file_put_contents($root.'/legacy.png','old original');file_put_contents($legacy.'.archive.json',json_encode(['original'=>'legacy.png']));
archive_delete_companions($legacy);expect(!file_exists($root.'/legacy.png')&&!file_exists($legacy.'.archive.json'),'old companion cleanup stays compatible');
$bad=$root.'/broken.png';file_put_contents($bad,substr(file_get_contents($result['path']),0,30));$before=hash_file('sha256',$bad);
try{archive_convert_saved_upload($bad);}catch(RuntimeException $e){}
expect(is_file($bad)&&hash_file('sha256',$bad)===$before,'failed/unsupported conversion never deletes source');
$collision=$root.'/collision.png';imagepng($image,$collision);file_put_contents($collision.'.webp','existing');$failed=false;try{archive_convert_saved_upload($collision);}catch(RuntimeException $e){$failed=true;}
expect($failed&&is_file($collision)&&file_get_contents($collision.'.webp')==='existing','failed conversion preserves source and existing destination');
$jpeg=$root.'/photo.jpg';imagejpeg($image,$jpeg,95);$jpegResult=archive_convert_saved_upload($jpeg);expect($jpegResult['converted']&&!is_file($jpeg)&&is_file($jpegResult['path']),'JPEG source replaced by WebP');
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST)as$f){if($f->isDir()&&!$f->isLink())rmdir($f->getPathname());else unlink($f->getPathname());}rmdir($root);
