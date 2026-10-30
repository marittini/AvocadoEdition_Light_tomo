<?php
if (!defined('_GNUBOARD_')) exit;

function editor_html($id, $content, $is_dhtml_editor=true)
{
    global $board, $config;
    include_once G5_LIB_PATH.'/tomo_fonts.lib.php';
    static $loaded = false;
    $safe = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
    $html = '';
    if ($is_dhtml_editor && !$loaded) {
        $url = G5_EDITOR_URL.'/tiptap';
        $font_css = tomo_font_css((string)($config['cf_add_fonts'] ?? ''), tomo_fonts());
        $html .= '<script type="application/json" id="tomo-editor-font-css">'.json_encode($font_css, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE).'</script>';

        foreach (['editor.css'] as $file) $html .= '<link rel="stylesheet" href="'.$url.'/'.$file.'?v=3">';
        foreach (['editor.bundle.js'] as $file) $html .= '<script src="'.$url.'/'.$file.'?v=3"></script>';
        $loaded = true;
    }
    if ($is_dhtml_editor) {
        if (empty($_SESSION['tomo_editor_token'])) $_SESSION['tomo_editor_token'] = bin2hex(random_bytes(32));
        $upload = G5_EDITOR_URL.'/tiptap/upload.php?bo_table='.rawurlencode($board['bo_table'] ?? '');
        $font_data = htmlspecialchars(json_encode(tomo_fonts(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG), ENT_QUOTES, 'UTF-8');
        $html .= '<div class="tomo-editor" data-fonts="'.$font_data.'" data-upload="'.htmlspecialchars($upload, ENT_QUOTES, 'UTF-8').'" data-token="'.htmlspecialchars($_SESSION['tomo_editor_token'], ENT_QUOTES, 'UTF-8').'">';
    }
    $html .= '<textarea id="'.$safe.'" name="'.$safe.'" class="'.($is_dhtml_editor?'tomo-rich-editor':'').'" style="width:100%;min-height:300px" maxlength="65536">'.$content.'</textarea>';
    if ($is_dhtml_editor) $html .= '<p class="tomo-editor-status" role="status" aria-live="polite"></p></div>';
    return $html;
}

function get_editor_js($id, $is_dhtml_editor=true)
{
    $key = json_encode($id, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    return $is_dhtml_editor ? "window.tomoEditorSync($key);\n" : "var {$id}_editor = document.getElementById($key);\n";
}

function chk_editor_js($id, $is_dhtml_editor=true)
{
    $key = json_encode($id, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    if ($is_dhtml_editor) return "if (!window.tomoEditorValidate($key)) return false;\n";
    return "if (!document.getElementById($key).value.trim()) { alert('내용을 입력해 주세요.'); document.getElementById($key).focus(); return false; }\n";
}
