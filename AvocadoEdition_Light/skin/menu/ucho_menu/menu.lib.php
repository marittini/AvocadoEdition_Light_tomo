<?php
if (!defined('_GNUBOARD_')) exit;
function ucho_menu_escape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function ucho_menu_url($value): string {
    $value = trim((string)$value);
    if (preg_match('~[\x00-\x20\x7f]~', $value) || (preg_match('~^[a-z][a-z0-9+.-]*:~i', $value) && !preg_match('~^https?://~i', $value))) return '#';
    return ucho_menu_escape($value);
}
function ucho_menu_tree(array $rows): array {
    $menus = [];
    foreach ($rows as $row) if (strlen((string)$row['me_code']) === 2) { $row['sub'] = []; $menus[(string)$row['me_code']] = $row; }
    foreach ($rows as $row) if (strlen((string)$row['me_code']) === 4) {
        $parent = substr((string)$row['me_code'], 0, 2);
        if (isset($menus[$parent])) $menus[$parent]['sub'][] = $row;
    }
    return $menus;
}
