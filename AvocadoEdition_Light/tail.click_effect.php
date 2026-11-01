<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
/*
 * 클릭 효과: 누른 자리에 원이 퍼지며 사라지고, 효과음이 납니다. (tail.php에서 불러옴, 관리자 화면에는 나오지 않음)
 * 참고: https://www.postype.com/@0penhe4rt/post/20916421
 *
 * 아래 값만 바꾸면 됩니다.
 */
$click_effect = [
    'enabled' => true,                        // false: 클릭 효과를 모두 끔
    'sound'   => G5_URL.'/sound/click.mp3',   // 효과음 파일. '' 이면 소리 없이 원만
    'volume'  => 0.5,                         // 효과음 크기 0 ~ 1
    'color'   => '',                          // 원 색. '' 이면 디자인 설정의 포인트 색 (예: '#d0e3ff')
    'size'    => 120,                         // 원이 가장 커졌을 때 지름(px)
    'speed'   => 0.4,                         // 퍼지는 시간(초)
];

if (empty($click_effect['enabled'])) return;

$click_effect_color = trim((string)$click_effect['color']);
if ($click_effect_color === '') {
    $click_effect_row = sql_fetch("SELECT cs_value, cs_etc_1 FROM {$g5['css_table']} WHERE cs_name = 'color_point'", false);
    $click_effect_hex = trim((string)($click_effect_row['cs_value'] ?? ''));
    if (preg_match('/^#?[0-9a-f]{3}([0-9a-f]{3})?$/i', $click_effect_hex)) $click_effect_color = $click_effect_hex;
}
if (!preg_match('/^#?[0-9a-f]{3}([0-9a-f]{3})?$|^rgba?\([0-9.,\s%]+\)$/i', $click_effect_color)) $click_effect_color = 'currentColor';
elseif ($click_effect_color[0] !== '#' && $click_effect_color[0] !== 'r') $click_effect_color = '#'.$click_effect_color;

$click_effect_css = G5_PATH.'/css/click-effect.css';
$click_effect_js = G5_PATH.'/js/click-effect.js';
?>
<link rel="stylesheet" href="<?=G5_CSS_URL?>/click-effect.css?v=<?=substr(md5_file($click_effect_css), 0, 10)?>">
<style>:root{--click-effect-color:<?=$click_effect_color?>;--click-effect-size:<?=max(20, (int)$click_effect['size'])?>px;--click-effect-speed:<?=max(0.1, min(3, (float)$click_effect['speed']))?>s;}</style>
<script src="<?=G5_JS_URL?>/click-effect.js?v=<?=substr(md5_file($click_effect_js), 0, 10)?>"
	data-sound="<?=htmlspecialchars((string)$click_effect['sound'], ENT_QUOTES, 'UTF-8')?>"
	data-volume="<?=max(0, min(1, (float)$click_effect['volume']))?>" defer></script>
