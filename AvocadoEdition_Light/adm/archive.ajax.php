<?php
include_once './_common.php';
include_once G5_LIB_PATH.'/archive.lib.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($is_admin !== 'super' || $_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(403); exit; }
if (!isset($_POST['archive_token']) || !hash_equals($_SESSION['archive_token'] ?? '', (string)$_POST['archive_token'])) { http_response_code(403); exit; }
try {
    $action = $_POST['action'] ?? '';
    if ($action === 'disk_start') $_SESSION['archive_scan'] = archive_disk_start(G5_PATH);
    if (in_array($action, ['disk_start','disk_step','disk_status'], true)) {
        if ($action !== 'disk_status' && isset($_SESSION['archive_scan']) && !$_SESSION['archive_scan']['finished']) {
            $_SESSION['archive_scan'] = archive_disk_step($_SESSION['archive_scan']);
        }
        $state = $_SESSION['archive_scan'] ?? null;
        if ($state) { unset($state['root'], $state['queue']); }
        echo json_encode(['scan'=>$state], JSON_UNESCAPED_UNICODE); exit;
    }
    if ($action !== 'preview') throw new RuntimeException('지원하지 않는 작업입니다.');
    $kind = $_POST['kind'] ?? '';
    $escape = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $css = '';
    $body = '';
    if ($kind === 'design') {
        $archive_preview_css = [];
        foreach (($_POST['cs_name'] ?? []) as $i => $name) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) continue;
            $archive_preview_css[$name] = [];
            for ($n=0; $n<=20; $n++) {
                $value = $n === 0 ? ($_POST['cs_value'][$i] ?? '') : ($_POST['cs_etc_'.$n][$i] ?? '');
                if (is_array($value)) $value = '||'.implode('||', $value).'||';
                $archive_preview_css[$name][$n] = stripslashes((string)$value);
                if ($n === 0 && !empty($_FILES['cs_value_file']['tmp_name'][$i])) {
                    $tmp = $_FILES['cs_value_file']['tmp_name'][$i];
                    $image = @getimagesize($tmp);
                    if (!is_uploaded_file($tmp) || !$image || !in_array($image[2], [1,2,3,18], true) || filesize($tmp) > 2097152) throw new RuntimeException('미리보기 이미지 제한');
                    $archive_preview_css[$name][0] = 'data:'.$image['mime'].';base64,'.base64_encode(file_get_contents($tmp));
                }
            }
        }
        include_once G5_LIB_PATH.'/tomo_fonts.lib.php';
        $tomo_render_fonts = isset($_POST['tomo_fonts_present']) ? tomo_post_fonts() : tomo_fonts();
        $tomo_legacy_font_css = stripslashes((string)($_POST['cf_add_fonts'] ?? ''));
        ob_start(); include './design_form_css.php'; $css = ob_get_clean();
        // Reuse the existing guide's representative components, not private post contents.
        ob_start(); include './design_guide_preview.php'; $guide = ob_get_clean();
        preg_match('~<body[^>]*>(.*)</body>~is', $guide, $match);
        $body = $match[1] ?? '<p>디자인 예시를 불러오지 못했습니다.</p>';
    } elseif ($kind === 'main') {
        $body = stripslashes((string)($_POST['main_content'] ?? ''));
        $css = (string)@file_get_contents(G5_DATA_PATH.'/css/_design.config.css');
    } elseif ($kind === 'menu') {
        $body = '<nav aria-label="메뉴 미리보기">';
        $items = [];
        foreach (($_POST['me_name'] ?? []) as $i=>$name) {
            if (empty($_POST['me_use'][$i])) continue;
            $items[] = ['order'=>(float)($_POST['me_order'][$i] ?? 0),'name'=>$name];
        }
        usort($items, static fn($a,$b) => $a['order'] <=> $b['order']);
        foreach ($items as $item) $body .= '<span class="preview-menu">'.$escape(stripslashes($item['name'])).'</span>';
        $body .= '</nav><p>정렬·표시 이름 예시입니다. 실제 스킨의 하위 메뉴 모양은 저장 후 확인하세요.</p>';
    } else throw new RuntimeException('미리보기 대상을 확인해 주세요.');
    // Defense in depth. The browser additionally uses a sandbox without scripts or same-origin.
    $body = preg_replace('~<(script|iframe|object|embed|form|base|meta|link)\b[^>]*>.*?</\1\s*>~is', '', $body);
    $body = preg_replace('~<(script|iframe|object|embed|form|base|meta|link)\b[^>]*?/?>~is', '', $body);
    $inline_image = static function ($url) {
        $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
        $prefix = G5_DATA_URL.'/';
        if (!str_starts_with($url, $prefix)) return '';
        $relative = rawurldecode(substr($url, strlen($prefix)));
        if (str_contains($relative, '..') || str_contains($relative, "\0")) return '';
        $path = realpath(G5_DATA_PATH.'/'.$relative);
        if (!$path || !str_starts_with($path, realpath(G5_DATA_PATH).'/') || !is_file($path) || filesize($path) > 2097152) return '';
        $image = @getimagesize($path);
        if (!$image || !in_array($image[2], [1,2,3,18], true)) return '';
        return 'data:'.$image['mime'].';base64,'.base64_encode(file_get_contents($path));
    };
    $body = preg_replace_callback('~(<img\b[^>]*\bsrc=["\'])([^"\']+)(["\'])~i', static fn($m) => $m[1].$inline_image($m[2]).$m[3], $body);
    $css = preg_replace_callback('~url\(["\']?('.preg_quote(G5_DATA_URL, '~').'/[^)"\']+)["\']?\)~i', static fn($m) => 'url("'.$inline_image($m[1]).'")', $css);
    if (strlen($body) + strlen($css) > 12582912) throw new RuntimeException('미리보기 데이터가 너무 큽니다.');
    $css = str_ireplace('</style', '<\\/style', $css);
    $origin = parse_url(G5_URL);
    $host = ($origin['scheme'] ?? 'https').'://'.($origin['host'] ?? '').(isset($origin['port']) ? ':'.$origin['port'] : '');
    $csp = "default-src 'none'; script-src 'none'; style-src 'unsafe-inline'; img-src data: blob:; font-src data:; form-action 'none'; base-uri 'none'; object-src 'none'";
    $doc = '<!doctype html><html lang="ko"><head><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="'.$escape($csp).'"><meta name="viewport" content="width=device-width,initial-scale=1">';
    $doc .= '<style>body{margin:20px;font:16px/1.6 sans-serif}.preview-menu{display:inline-block;padding:10px 15px;border:1px solid #ddd}a{pointer-events:none}'.$css.'</style></head><body><p>저장 전 미리보기 · 링크·스크립트·외부 폰트는 실행하지 않습니다.</p>'.$body.'</body></html>';
    echo json_encode(['html'=>$doc,'scope'=>$kind === 'design' ? '디자인 예시 구성요소 기준' : '현재 편집 영역 기준'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $e) {
    error_log('Archive admin operation: '.$e->getMessage());
    http_response_code(422);
    echo json_encode(['error'=>'작업을 완료하지 못했습니다. 입력은 유지됩니다. 서버 로그와 지원 범위를 확인해 주세요.'], JSON_UNESCAPED_UNICODE);
}
