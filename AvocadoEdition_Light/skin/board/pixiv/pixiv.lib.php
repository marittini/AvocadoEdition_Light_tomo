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

/** 소설 글자 수(공백 제외). 본문 HTML의 태그와 문자 참조를 걷어 낸 뒤 센다. */
function pixiv_char_count(string $content): int {
    $text = html_entity_decode(strip_tags(preg_replace('~<(br|/p|/div)\b[^>]*>~i', "\n", $content)), ENT_QUOTES|ENT_HTML5, 'UTF-8');
    return mb_strlen(preg_replace('/\s+/u', '', $text));
}

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
    if (!in_array($file, ['style.css', 'viewer.js', 'write.js', 'series.js'], true)) return '';
    if (!isset($versions[$file])) $versions[$file] = substr(hash_file('sha256', __DIR__.'/'.$file), 0, 16);
    return $skin_url.'/'.$file.'?v='.$versions[$file];
}

/* ---------------------------------------------------------------
 * Series
 * A work belongs to at most one series. Series metadata and the
 * work order live in two small tables created on first use, the
 * same way the character skin creates its body-image table.
 * ------------------------------------------------------------- */

function pixiv_series_tables(): array {
    static $ready = null;
    $series = G5_TABLE_PREFIX.'pixiv_series';
    $works = G5_TABLE_PREFIX.'pixiv_series_work';
    if ($ready === null) {
        $ready = (bool)sql_query("DESC `{$series}`", false) && (bool)sql_query("DESC `{$works}`", false);
        if (!$ready) {
            sql_query("CREATE TABLE IF NOT EXISTS `{$series}` (
                `ps_id` int(11) NOT NULL AUTO_INCREMENT,
                `bo_table` varchar(20) NOT NULL DEFAULT '',
                `mb_id` varchar(20) NOT NULL DEFAULT '',
                `ps_kind` varchar(10) NOT NULL DEFAULT 'comic',
                `ps_title` varchar(255) NOT NULL DEFAULT '',
                `ps_caption` text NOT NULL,
                `ps_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                `ps_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                PRIMARY KEY (`ps_id`),
                KEY `board_update` (`bo_table`,`ps_update`)
            ) DEFAULT CHARSET=utf8mb4", false);
            sql_query("CREATE TABLE IF NOT EXISTS `{$works}` (
                `bo_table` varchar(20) NOT NULL DEFAULT '',
                `wr_id` int(11) NOT NULL DEFAULT 0,
                `ps_id` int(11) NOT NULL DEFAULT 0,
                `sw_order` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`bo_table`,`wr_id`),
                KEY `series_order` (`ps_id`,`sw_order`)
            ) DEFAULT CHARSET=utf8mb4", false);
            $ready = (bool)sql_query("DESC `{$series}`", false) && (bool)sql_query("DESC `{$works}`", false);
        }
    }
    return $ready ? ['series'=>$series, 'works'=>$works] : [];
}

/** Illustrations and manga share comic series; novels have their own, as on pixiv. */
function pixiv_series_kind(string $type): string {
    return in_array($type, ['novel','pixiv:novel'], true) ? 'novel' : 'comic';
}

function pixiv_series_kind_label(string $kind): string {
    return $kind === 'novel' ? '소설 시리즈' : '만화 시리즈';
}

/** SQL that hides secret posts from everyone except admins and their authors. */
function pixiv_visible_sql(string $alias = ''): string {
    global $is_admin, $member;
    if ($is_admin) return '1';
    $prefix = $alias !== '' ? $alias.'.' : '';
    $sql = "({$prefix}wr_option NOT LIKE '%secret%'";
    if (!empty($member['mb_id'])) $sql .= " OR {$prefix}mb_id='".sql_real_escape_string($member['mb_id'])."'";
    return $sql.')';
}

function pixiv_series_get(string $bo_table, int $ps_id): ?array {
    $t = pixiv_series_tables();
    if (!$t || $ps_id < 1) return null;
    $row = sql_fetch("SELECT * FROM `{$t['series']}` WHERE ps_id={$ps_id} AND bo_table='".sql_real_escape_string($bo_table)."'");
    return !empty($row['ps_id']) ? $row : null;
}

function pixiv_series_can_edit(?array $series): bool {
    global $is_admin, $member;
    if (!$series) return false;
    if ($is_admin) return true;
    return !empty($member['mb_id']) && $member['mb_id'] === $series['mb_id'];
}

/** Series the given author may attach a work to (owner's series; admins see every series of the board). */
function pixiv_series_options(string $bo_table, string $owner): array {
    global $is_admin;
    $t = pixiv_series_tables();
    if (!$t) return [];
    $where = "bo_table='".sql_real_escape_string($bo_table)."'";
    if (!$is_admin) $where .= " AND mb_id='".sql_real_escape_string($owner)."'";
    $out = [];
    $res = sql_query("SELECT ps_id, ps_kind, ps_title, mb_id FROM `{$t['series']}` WHERE {$where} ORDER BY ps_update DESC");
    while ($row = sql_fetch_array($res)) $out[] = $row;
    return $out;
}

/**
 * Ordered works of a series. Every work is numbered by its position in the
 * full series so that episode numbers stay stable; secret works are removed
 * from the returned list for readers who may not see them.
 */
function pixiv_series_works(string $bo_table, string $write_table, int $ps_id): array {
    $t = pixiv_series_tables();
    if (!$t) return [];
    $res = sql_query("SELECT w.wr_id, w.wr_subject, w.wr_content, w.wr_datetime, w.wr_option, w.mb_id, w.wr_1, w.wr_2, w.wr_3, w.wr_4,
            (".pixiv_visible_sql('w').") AS px_visible
        FROM `{$t['works']}` s JOIN `{$write_table}` w ON w.wr_id=s.wr_id AND w.wr_is_comment=0
        WHERE s.bo_table='".sql_real_escape_string($bo_table)."' AND s.ps_id={$ps_id}
        ORDER BY s.sw_order, w.wr_id");
    $out = []; $no = 0;
    while ($row = sql_fetch_array($res)) {
        $no++;
        if (!(int)$row['px_visible']) continue;
        $row['px_no'] = $no;
        $out[] = $row;
    }
    return $out;
}

function pixiv_series_of_work(string $bo_table, int $wr_id): ?array {
    $t = pixiv_series_tables();
    if (!$t || $wr_id < 1) return null;
    $row = sql_fetch("SELECT s.*, w.sw_order FROM `{$t['works']}` w JOIN `{$t['series']}` s ON s.ps_id=w.ps_id
        WHERE w.bo_table='".sql_real_escape_string($bo_table)."' AND w.wr_id={$wr_id}");
    return !empty($row['ps_id']) ? $row : null;
}

/** First readable image of the first visible work, used as the series cover. */
function pixiv_series_cover(string $bo_table, array $works): string {
    foreach ($works as $work) {
        if (str_contains((string)$work['wr_option'], 'secret')) continue;
        $files = pixiv_files($bo_table, (int)$work['wr_id'], $work['wr_3']);
        if ($files) return pixiv_cover_url($bo_table, pixiv_cover_file($files, $work['wr_3']));
    }
    return '';
}

/** Removes rows whose post was deleted or moved to another board. */
function pixiv_series_cleanup(string $bo_table, string $write_table): void {
    $t = pixiv_series_tables();
    if (!$t) return;
    sql_query("DELETE s FROM `{$t['works']}` s LEFT JOIN `{$write_table}` w ON w.wr_id=s.wr_id
        WHERE s.bo_table='".sql_real_escape_string($bo_table)."' AND w.wr_id IS NULL", false);
}

function pixiv_series_token(): string {
    if (empty($_SESSION['pixiv_series_token'])) $_SESSION['pixiv_series_token'] = bin2hex(random_bytes(32));
    return $_SESSION['pixiv_series_token'];
}

function pixiv_series_url(string $bo_table, int $ps_id, array $extra = []): string {
    return G5_BBS_URL.'/board.php?'.http_build_query(['bo_table'=>$bo_table, 'px_series'=>$ps_id] + $extra, '', '&amp;');
}
