<?php
if (!defined('_GNUBOARD_')) exit;

function ott_can_read_post(array $post): bool
{
    global $is_admin, $member, $is_member, $bo_table;
    if ($is_admin || (!empty($member['mb_id']) && ($post['mb_id'] ?? '') === $member['mb_id'])) return true;
    $unlocked = (bool)get_session('ss_secret_'.$bo_table.'_'.($post['wr_num'] ?? ''));
    if (!empty($post['wr_protect'])) return $unlocked;
    if (strpos($post['wr_option'] ?? '', 'secret') !== false) return $unlocked;
    if (!empty($post['wr_secret'])) return (bool)$is_member;
    return true;
}

function ott_can_access_board(): bool
{
    global $board, $group, $g5, $member, $is_admin, $is_member, $config;
    if (empty($board['bo_table']) || (int)($member['mb_level'] ?? 1) < (int)$board['bo_read_level']) return false;
    if (!empty($group['gr_use_access']) && !$is_admin) {
        $grant = sql_fetch("SELECT gm_id FROM {$g5['group_member_table']} WHERE gr_id='".sql_real_escape_string($group['gr_id'])."' AND mb_id='".sql_real_escape_string($member['mb_id'] ?? '')."'", false);
        if (empty($grant['gm_id'])) return false;
    }
    if (!empty($config['cf_cert_use']) && !$is_admin && !empty($board['bo_use_cert'])) {
        $cert = $board['bo_use_cert'];
        if (!$is_member || ($cert === 'cert' && empty($member['mb_certify'])) || ($cert === 'adult' && empty($member['mb_adult'])) || ($cert === 'hp-cert' && ($member['mb_certify'] ?? '') !== 'hp') || ($cert === 'hp-adult' && (empty($member['mb_adult']) || ($member['mb_certify'] ?? '') !== 'hp'))) return false;
    }
    return true;
}

function ott_file_url(array $file, string $bo_table): string
{
    if (empty($file['file'])) return '';
    return G5_BBS_URL.'/archive_file.php?archive_kind=file&amp;archive_path='.rawurlencode($bo_table.'/'.$file['file']);
}

function ott_delete_token(int $id): string
{
    if (empty($_SESSION['ott_delete_tokens'][$id])) $_SESSION['ott_delete_tokens'][$id] = bin2hex(random_bytes(32));
    return $_SESSION['ott_delete_tokens'][$id];
}

function ott_password_matches(string $password, string $stored): bool
{
    if ($stored === '') return false;
    if (password_get_info($stored)['algo'] !== null) return password_verify($password, $stored);
    return hash_equals($stored, $password); // Compatibility with existing protected posts.
}

function ott_safe_image_url($url): string
{
    $url = trim((string)$url);
    if (!preg_match('~^https?://~i', $url) || preg_match('~[\x00-\x20\x7f"\x27()<>\\\\]~', $url)) return '';
    return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
