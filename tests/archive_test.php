<?php
define('_GNUBOARD_', true);
require __DIR__.'/../AvocadoEdition_Light/lib/archive.lib.php';
function expect($ok,$message){if(!$ok)throw new RuntimeException($message);echo "PASS $message\n";}
$root=sys_get_temp_dir().'/archive-tests-'.bin2hex(random_bytes(5));mkdir($root);mkdir($root.'/data');mkdir($root.'/data/file');mkdir($root.'/data/file/log');
$image=imagecreatetruecolor(17,13);imagealphablending($image,false);imagesavealpha($image,true);$clear=imagecolorallocatealpha($image,120,60,30,127);imagefill($image,0,0,$clear);imagesetpixel($image,3,3,imagecolorallocatealpha($image,40,50,60,0));
$source=$root.'/data/file/log/a.png';imagepng($image,$source);$original=hash_file('sha256',$source);$result=archive_convert_saved_upload($source);
expect($result['converted']&&getimagesize($result['path'])[2]===IMAGETYPE_WEBP,'PNG becomes valid WebP');expect(hash_file('sha256',$source)===$original,'source preserved exactly');
$decoded=imagecreatefromwebp($result['path']);expect((imagecolorat($decoded,0,0)>>24)===127,'transparent alpha preserved');expect((imagecolorat($decoded,3,3)&0xffffff)===(imagecolorat($image,3,3)&0xffffff),'lossless pixels preserved');
expect(!archive_convert_saved_upload($result['path'])['converted'],'existing WebP not recompressed');
file_put_contents($root.'/document.pdf','document');file_put_contents($root.'/data/file/log/not-image.png','not an image');expect(!archive_convert_saved_upload($root.'/document.pdf')['converted'],'document is not converted');
symlink('/etc',$root.'/outside');
$scan=archive_disk_start($root);$steps=0;do{$scan=archive_disk_step($scan,2);$steps++;}while(!$scan['finished']&&$steps<100);
$expected=0;foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS)) as $f){if(!$f->isLink()&&$f->isFile())$expected+=$f->getSize();}
expect($scan['finished']&&$steps>1,'scan resumes across bounded batches');expect($scan['bytes']===$expected,'scan exact logical file sum');expect($scan['skipped_links']===1,'external symlink skipped');expect(array_sum($scan['categories'])===$scan['bytes'],'categories disjoint');
archive_delete_companions($result['path']);expect(!file_exists($source)&&!file_exists($result['path'].'.archive.json'),'companion originals removed only on attachment deletion');
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST)as$f){if($f->isDir()&&!$f->isLink())rmdir($f->getPathname());else unlink($f->getPathname());}rmdir($root);
