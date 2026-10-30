<?php
include_once './_common.php';
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
function archive_deny_file() { http_response_code(403); exit; }
if (empty($config['cf_open']) && !$is_member) archive_deny_file();
$kind = $_GET['archive_kind'] ?? '';
$relative = $_GET['archive_path'] ?? '';
if (!is_string($relative) || !in_array($kind, ['file','editor'], true) || str_contains($relative, '..') || str_contains($relative, "\0") || str_ends_with($relative, '.archive.json')) archive_deny_file();
$base = realpath(G5_DATA_PATH.'/'.$kind);
$file = realpath(G5_DATA_PATH.'/'.$kind.'/'.$relative);
if (!$base || !$file || !str_starts_with($file, $base.'/') || !is_file($file)) { http_response_code(404); exit; }
$can_read = static function ($b, $post) use ($g5, $member, $is_admin) {
    if (!$b || !$post) return false;
    if ($is_admin === 'super') return true;
    if ((int)($member['mb_level'] ?? 1) < (int)$b['bo_read_level']) return false;
    $group = sql_fetch("select * from {$g5['group_table']} where gr_id='".sql_real_escape_string($b['gr_id'])."'", false);
    $admin = !empty($member['mb_id']) && ($member['mb_id'] === ($b['bo_admin'] ?? '') || $member['mb_id'] === ($group['gr_admin'] ?? ''));
    if (!empty($group['gr_use_access']) && !$admin) {
        $grant = sql_fetch("select gm_id from {$g5['group_member_table']} where gr_id='".sql_real_escape_string($b['gr_id'])."' and mb_id='".sql_real_escape_string($member['mb_id'] ?? '')."'", false);
        if (!$grant) return false;
    }
    if (str_contains($post['wr_option'] ?? '', 'secret') || !empty($post['wr_secret'])) {
        if (!$admin && (empty($member['mb_id']) || $post['mb_id'] !== $member['mb_id']) && !get_session('ss_secret_'.$b['bo_table'].'_'.$post['wr_num'])) return false;
    }
    return true;
};
$allowed = $is_admin === 'super';
if (!$allowed && $kind === 'file') {
    $segments = explode('/', $relative);
    if (count($segments) !== 2 || !preg_match('/^[a-zA-Z0-9_]{1,20}$/', $segments[0])) archive_deny_file();
    $board_id = $segments[0]; $name = $segments[1];
    $board = sql_fetch("select * from {$g5['board_table']} where bo_table='".sql_real_escape_string($board_id)."'", false);
    $attachments = sql_query("select bf_file, wr_id from {$g5['board_file_table']} where bo_table='".sql_real_escape_string($board_id)."'", false);
    while ($attachment = sql_fetch_array($attachments)) {
        $stored = $attachment['bf_file'];
        $stem = pathinfo($stored, PATHINFO_FILENAME);
        $related = $name === $stored || $stored === $name.'.webp' || (bool)preg_match('~^thumb-'.preg_quote($stem, '~').'_[0-9]+x[0-9]+\.(gif|jpe?g|png|webp)$~i', $name);
        if (!$related) continue;
        $post = sql_fetch("select * from {$g5['write_prefix']}{$board_id} where wr_id=".(int)$attachment['wr_id'], false);
        if ($can_read($board, $post)) { $allowed = true; break; }
    }
}
if (!$allowed && $kind === 'editor') {
    // Editor files are readable only when referenced by at least one readable post.
    $boards = sql_query("select * from {$g5['board_table']}", false);
    $needle = sql_real_escape_string('/'.G5_DATA_DIR.'/editor/'.$relative);
    while ($b = sql_fetch_array($boards)) {
        if (!preg_match('/^[a-zA-Z0-9_]{1,20}$/', $b['bo_table'])) continue;
        $posts = sql_query("select * from {$g5['write_prefix']}{$b['bo_table']} where INSTR(wr_content, '$needle') > 0", false);
        while ($post = sql_fetch_array($posts)) {
            if ($can_read($b, $post)) { $allowed = true; break 2; }
        }
    }
    // Existing public main/content pages may reference editor images too.
    if (!$allowed) {
        $content = sql_fetch("select co_id from {$g5['content_table']} where INSTR(co_content, '$needle') > 0 limit 1", false);
        $allowed = !empty($content);
    }
}
if (!$allowed) archive_deny_file();
$info = @getimagesize($file);
if ($info && in_array($info[2], [1,2,3,18], true)) header('Content-Type: '.$info['mime']);
else { header('Content-Type: application/octet-stream'); header('Content-Disposition: attachment; filename="download"'); }
header('Content-Length: '.filesize($file));
readfile($file);
