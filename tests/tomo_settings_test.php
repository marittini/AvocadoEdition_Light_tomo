<?php
define('_GNUBOARD_', true);
require __DIR__.'/../AvocadoEdition_Light/lib/tomo_settings.lib.php';
require __DIR__.'/../AvocadoEdition_Light/lib/tomo_fonts.lib.php';
require __DIR__.'/../AvocadoEdition_Light/lib/archive.lib.php';
function check($ok,$message){if(!$ok)throw new RuntimeException($message);echo "PASS $message\n";}
function rejected(callable $f): bool {try {$f();return false;}catch(InvalidArgumentException $e){return true;}}
$config=[];check(tomo_webp_quality()===95&&tomo_protection_mask()===0,'upgrade defaults are 95 and protection off');
foreach([0,95,100]as$q)check(tomo_validate_quality((string)$q)===$q,'quality accepts '.$q);
foreach(['',-1,101,'95.5','abc',[],null]as$q)check(rejected(fn()=>tomo_validate_quality($q)),'invalid quality rejected');
$fonts=tomo_validate_fonts([['label'=>'테스트 폰트','family'=>"'Test Font', sans-serif",'css'=>'@font-face {font-family:"Test Font";src:url("/font.woff2")}']]);
check(count($fonts)===1&&strlen($fonts[0]['id'])===16,'registered face gets stable ID');
check(tomo_validate_fonts($fonts)===$fonts,'registry round trip');
check(rejected(fn()=>tomo_validate_fonts(array_merge($fonts,$fonts))),'duplicate face rejected');
check(rejected(fn()=>tomo_validate_fonts([['label'=>'bad','family'=>'x;}body{color:red}']])),'family cannot inject CSS');
check(rejected(fn()=>tomo_check_font_removal($fonts,[],[$fonts[0]['family']])),'selected font deletion rejected');
check(rejected(fn()=>tomo_check_font_removal($fonts,[],['Arial, "Test Font", serif'])),'font used inside a custom stack cannot be removed');
tomo_check_font_removal($fonts,[],['serif']);check(true,'unused font can be removed');
$css=tomo_font_css('/* old */ @font-face{font-family:Old;src:url("/old.woff2")}',$fonts+[]);
check(str_contains($css,'/old.woff2')&&str_contains($css,'/font.woff2'),'legacy and registered CSS preserved');
$css=tomo_font_css('@font-face {font-family:Old;src:url("/old.woff")} /* @import "ignore"; */', [['css'=>'@import url("https://fonts.example/a;b.css") screen; @font-face {font-family:New;src:url("/new.woff")}']]);
check(str_starts_with($css,'@import url("https://fonts.example/a;b.css") screen;'),'imports precede font-face without splitting quoted URL');
check(str_contains($css,'/* @import "ignore"; */'),'comment content preserved');
$commented=tomo_font_css('', [['css'=>'@import /* a;b */ url("/a.css"); @font-face{font-family:A}']]);
check(str_starts_with($commented,'@import /* a;b */ url("/a.css");'),'comments inside import retained');
$dir=sys_get_temp_dir().'/tomo-quality-'.bin2hex(random_bytes(5));mkdir($dir);
$image=imagecreatetruecolor(80,80);for($y=0;$y<80;$y++)for($x=0;$x<80;$x++)imagesetpixel($image,$x,$y,imagecolorallocate($image,($x*7)%256,($y*9)%256,($x*$y)%256));
imagepng($image,$dir.'/source.png');imagewebp($image,$dir.'/expected.webp',95);
$r=archive_webp($dir.'/source.png',$dir.'/actual.webp');check(hash_file('sha256',$r['path'])===hash_file('sha256',$dir.'/expected.webp'),'default encoder uses quality 95');
$config['cf_tomo_webp_quality']=35;imagewebp($image,$dir.'/expected35.webp',35);archive_webp($dir.'/source.png',$dir.'/actual35.webp');
check(hash_file('sha256',$dir.'/actual35.webp')===hash_file('sha256',$dir.'/expected35.webp'),'configured quality reaches encoder');
check(hash_file('sha256',$dir.'/actual35.webp')!==hash_file('sha256',$dir.'/actual.webp'),'quality changes encoded output');
archive_webp($dir.'/source.png',$dir.'/lossless.webp',IMG_WEBP_LOSSLESS);$decoded=imagecreatefromwebp($dir.'/lossless.webp');check((imagecolorat($decoded,20,20)&0xffffff)===(imagecolorat($image,20,20)&0xffffff),'explicit lossless helper remains supported');
define('G5_DIR_PERMISSION',0755);define('G5_FILE_PERMISSION',0644);
require __DIR__.'/../AvocadoEdition_Light/lib/thumbnail.lib.php';
$config['cf_tomo_webp_quality']=95;
$thumb=thumbnail('actual.webp',$dir,$dir,30,30,true);
$first=hash_file('sha256',$dir.'/'.$thumb);
// A cache newer than its source is reused even when the quality setting changes.
touch($dir.'/'.$thumb,time()+10);$config['cf_tomo_webp_quality']=20;
thumbnail('actual.webp',$dir,$dir,30,30,false);
check(hash_file('sha256',$dir.'/'.$thumb)===$first,'existing thumbnail not reencoded on quality change');
thumbnail('actual.webp',$dir,$dir,30,30,true);
check(hash_file('sha256',$dir.'/'.$thumb)!==$first,'new thumbnail uses configured quality');
foreach(glob($dir.'/*')as$f)unlink($f);rmdir($dir);
