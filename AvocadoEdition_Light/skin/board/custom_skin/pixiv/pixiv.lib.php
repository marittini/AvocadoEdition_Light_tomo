<?php
if (!defined('_GNUBOARD_')) exit;
function pixiv_tags($raw) {
    if (!is_string($raw)) return [];
    $raw = trim($raw, '|');
    $out = [];
    foreach (explode('|', $raw) as $part) {
        $part = trim($part);
        if ($part !== '' && !in_array($part, $out, true)) $out[] = $part;
    }
    return $out;
}
function pixiv_note($write) {
    $out = '';
    for ($i=4;$i<=10;$i++) $out .= $write['wr_'.$i] ?? '';
    return $out;
}
function pixiv_files($board_id, $post_id, $order) {
    $files = get_file($board_id, $post_id);
    $keys = [];
    foreach (explode(',', explode(';', (string)$order, 2)[0]) as $id) if (ctype_digit($id)) $keys[] = (int)$id;
    foreach ($files as $key => $item) if (is_int($key) && !in_array($key,$keys,true)) $keys[]=$key;
    $out=[];
    foreach (array_unique($keys) as $key) {
        $entry=$files[$key] ?? null;
        if (!$entry || empty($entry['file']) || !in_array((int)$entry['image_type'], [1,2,3,18],true)) continue;
        $entry['index']=$key;
        $entry['url']=G5_BBS_URL.'/archive_file.php?archive_kind=file&amp;archive_path='.rawurlencode($board_id.'/'.$entry['file']);
        $out[]=$entry;
    }
    return $out;
}
function pixiv_escape($value) { return htmlspecialchars((string)$value, ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }

function pixiv_cover_url($board_id, $entry) {
    include_once G5_LIB_PATH.'/thumbnail.lib.php';
    $filename=basename($entry['file']);
    $path=G5_DATA_PATH.'/file/'.$board_id;
    $thumb=thumbnail($filename,$path,$path,320,320,false,true);
    return $thumb ? G5_BBS_URL.'/archive_file.php?archive_kind=file&amp;archive_path='.rawurlencode($board_id.'/'.$thumb) : $entry['url'];
}

/** Missing/deleted cover falls back to the first readable image. */
function pixiv_cover_file(array $files, $order): ?array {
    if (preg_match('/(?:^|;)c:(\d+)(?:;|$)/', (string)$order, $match)) {
        foreach ($files as $entry) if ($entry['index'] === (int)$match[1]) return $entry;
    }
    return $files[0] ?? null;
}

/** Version assets by bytes, so consecutive updates cannot reuse a stale cache key. */
function pixiv_asset_url(string $skin_url, string $file): string {
    static $versions = [];
    if (!in_array($file, ['style.css', 'viewer.js', 'write.js'], true)) return '';
    if (!isset($versions[$file])) $versions[$file] = substr(hash_file('sha256', __DIR__.'/'.$file), 0, 16);
    return $skin_url.'/'.$file.'?v='.$versions[$file];
}
