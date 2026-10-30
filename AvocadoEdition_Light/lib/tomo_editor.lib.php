<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * Remove editor-only empty paragraphs, line breaks and invisible whitespace.
 * Keep real text and non-text content byte-for-byte; this is not an HTML filter.
 */
function tomo_normalize_editor_html(string $html): string
{
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Preserve malformed UTF-8 rather than accidentally deleting existing content.
    if (preg_match('/[^\s\p{Z}\p{Cf}]/u', $text) !== 0) return $html;

    // These tags only format text. Unknown tags can be media, widgets or controls.
    $formatting = ['p','div','span','br','b','strong','i','em','u','s','strike','del',
        'font','h1','h2','h3','h4','h5','h6','ul','ol','li','blockquote','pre','code','a',
        'sup','sub','small','big','mark'];
    preg_match_all('/<\/?\s*([a-z][a-z0-9:-]*)\b/i', $html, $matches);
    foreach ($matches[1] as $tag) {
        if (!in_array(strtolower($tag), $formatting, true)) return $html;
    }
    // Empty layout widgets may have visible backgrounds or external CSS hooks.
    if (preg_match('/\b(?:id|class)\s*=|(?<![a-z-])(?:background-image|min-height|height|width)\s*:/i', $html)) return $html;
    return '';
}
