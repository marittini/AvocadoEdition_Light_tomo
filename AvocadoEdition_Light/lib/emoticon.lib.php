<?php
if (!defined('_GNUBOARD_')) exit;
/*
 * 이모티콘(관리자 > 게시판관리 > 이모티콘관리에 등록한 것)을 스킨에서 쓰는 공용 함수.
 *  - emoticon_items(): 등록된 이모티콘 [['code','url'], …] (긴 코드부터)
 *  - emoticon_html(): 본문 HTML의 글자 부분에서 코드를 이미지로 바꾼다(태그 안은 건드리지 않음)
 *  - emoticon_button(): 입력칸 옆 '이모티콘' 버튼(누르면 목록 창 bbs/emoticon.php가 새 창으로 열리고, 고르면 커서 자리에 코드)
 * 저장되는 글은 코드 그대로이고, 보여 줄 때만 바꾼다.
 */

if (empty($g5['emoticon_table'])) $g5['emoticon_table'] = G5_TABLE_PREFIX.'emoticon';

function emoticon_items(): array
{
    global $g5;
    static $items = null;
    if ($items !== null) return $items;
    $items = [];
    if (!sql_query("DESCRIBE `{$g5['emoticon_table']}`", false)) return $items;
    $res = sql_query("SELECT me_text, me_img FROM `{$g5['emoticon_table']}` WHERE me_text <> '' AND me_img <> '' ORDER BY me_text", false);
    while ($res && ($row = sql_fetch_array($res))) {
        $items[] = ['code' => (string)$row['me_text'], 'url' => G5_URL.$row['me_img']];
    }
    usort($items, static fn($a, $b) => mb_strlen($b['code']) <=> mb_strlen($a['code']));
    return $items;
}

/** HTML의 글자 부분에서 이모티콘 코드를 이미지로 바꾼다. */
function emoticon_html($html): string
{
    $html = (string)$html;
    $items = emoticon_items();
    if (!$items || $html === '') return $html;
    $map = [];
    foreach ($items as $item) {
        $img = '<img src="'.htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8').'" alt="'.htmlspecialchars($item['code'], ENT_QUOTES, 'UTF-8').'" class="emoticon" loading="lazy">';
        $map[htmlspecialchars($item['code'], ENT_NOQUOTES, 'UTF-8')] = $img; // 본문에 들어간 모양(이스케이프된 글자)
        $map[$item['code']] = $img;
    }
    $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    if ($parts === false) return $html;
    foreach ($parts as $i => $part) {
        if ($i % 2 || $part === '') continue; // 태그는 그대로
        $parts[$i] = strtr($part, $map); // 긴 코드부터 한 번에 바꾼다
    }
    return implode('', $parts);
}

/**
 * 이모티콘 버튼. $target: 'textarea' (같은 폼의 wr_content 입력칸) 또는 'editor:필드id' (사이트 공통 편집기).
 * 등록된 이모티콘이 없으면 아무것도 출력하지 않는다.
 */
function emoticon_button(string $target = 'textarea', string $class = 'ui-btn small etc'): string
{
    if (!emoticon_items()) return '';
    return emoticon_assets().'<button type="button" class="'.htmlspecialchars($class, ENT_QUOTES, 'UTF-8').' emoticon-open" data-emoticon-open="'
        .htmlspecialchars($target, ENT_QUOTES, 'UTF-8').'" data-emoticon-url="'.G5_BBS_URL.'/emoticon.php" aria-haspopup="dialog" aria-expanded="false" title="이모티콘 목록 창 열기"><i class="material-icons" aria-hidden="true">mood</i> 이모티콘</button>';
}

/** 팝업 스크립트 · 스타일 · 목록 데이터(한 화면에 한 번). */
function emoticon_assets(): string
{
    static $done = false;
    if ($done) return '';
    $done = true;
    $data = json_encode(emoticon_items(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $css = G5_PATH.'/css/emoticon-picker.css';
    $js = G5_PATH.'/js/emoticon-picker.js';
    return '<link rel="stylesheet" href="'.G5_CSS_URL.'/emoticon-picker.css?v='.substr((string)@md5_file($css), 0, 10).'">'
        .'<script>window.tomoEmoticons='.$data.';</script>'
        .'<script src="'.G5_JS_URL.'/emoticon-picker.js?v='.substr((string)@md5_file($js), 0, 10).'" defer></script>';
}
