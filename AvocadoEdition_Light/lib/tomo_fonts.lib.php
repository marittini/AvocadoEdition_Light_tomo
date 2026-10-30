<?php
if (!defined('_GNUBOARD_')) exit;

function tomo_html($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Compare family names without changing the legacy stored CSS expression. */
function tomo_font_family_names(string $value): array
{
    $names = []; $token = ''; $quote = ''; $length = strlen($value);
    for ($i=0; $i<$length; $i++) {
        $c = $value[$i];
        if ($c === '\\' && $i+1<$length) { $token .= $c.$value[++$i]; continue; }
        if ($quote !== '') { $token .= $c; if ($c === $quote) $quote = ''; continue; }
        if ($c === '"' || $c === "'") { $quote = $c; $token .= $c; continue; }
        if ($c === ',') { $names[] = strtolower(trim(trim($token), "\"'")); $token = ''; continue; }
        $token .= $c;
    }
    $names[] = strtolower(trim(trim($token), "\"'"));
    return $names;
}

function tomo_fonts(): array
{
    global $config;
    $rows = json_decode($config['cf_tomo_fonts'] ?? '[]', true);
    return is_array($rows) ? $rows : [];
}

/** Receives raw, unescaped values. common.php SQL-escapes POST recursively. */
function tomo_validate_fonts(array $rows): array
{
    if (count($rows) > 50) throw new InvalidArgumentException('웹폰트는 최대 50개까지 등록할 수 있습니다.');
    $fonts = []; $families = []; $ids = [];
    foreach ($rows as $row) {
        if (!is_array($row)) throw new InvalidArgumentException('폰트 등록 형식을 확인해 주세요.');
        foreach (['id','label','family','css'] as $key) {
            if (isset($row[$key]) && !is_string($row[$key])) throw new InvalidArgumentException('폰트 입력값을 확인해 주세요.');
        }
        $label = trim($row['label'] ?? ''); $family = trim($row['family'] ?? ''); $css = trim($row['css'] ?? '');
        if ($label === '' && $family === '' && $css === '') continue;
        if ($label === '' || $family === '' || strlen($label)>240 || strlen($family)>240 || preg_match('/[;{}<>\\x00-\\x1f]/', $family)) {
            throw new InvalidArgumentException('폰트 표시 이름과 올바른 font-family를 입력해 주세요.');
        }
        $key = implode(',', tomo_font_family_names($family));
        if (isset($families[$key])) throw new InvalidArgumentException('동일한 font-family를 중복 등록할 수 없습니다.');
        $families[$key] = true;
        $id = $row['id'] ?? '';
        if ($id === '') $id = bin2hex(random_bytes(8));
        if (!preg_match('/^[a-f0-9]{16}$/', $id) || isset($ids[$id])) throw new InvalidArgumentException('폰트 등록 ID를 확인해 주세요.');
        $ids[$id] = true;
        $fonts[] = ['id'=>$id, 'label'=>$label, 'family'=>$family, 'css'=>$css];
    }
    if (strlen(json_encode($fonts, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) > 60000) throw new InvalidArgumentException('등록한 폰트 데이터는 합계 60KB 이하여야 합니다.');
    return $fonts;
}

function tomo_post_fonts(): array
{
    $rows = $_POST['tomo_fonts'] ?? [];
    if (!is_array($rows)) throw new InvalidArgumentException('폰트 목록 형식을 확인해 주세요.');
    return tomo_validate_fonts(array_map_deep('stripslashes', $rows));
}

/** Prevent removing a registered face still selected in one of our three design slots. */
function tomo_check_font_removal(array $before, array $after, array $selected): void
{
    $ids = array_column($after, 'family', 'id');
    foreach ($before as $font) {
        $primary = tomo_font_family_names($font['family'])[0];
        $used = false;
        foreach ($selected as $family) {
            if ($primary !== '' && in_array($primary, tomo_font_family_names($family), true)) $used = true;
        }
        if (($ids[$font['id']] ?? null) !== $font['family'] && $used) {
            throw new InvalidArgumentException('사용 중인 폰트는 적용 글꼴을 먼저 변경한 뒤 삭제하거나 font-family를 수정해 주세요.');
        }
    }
}

/** Hoist complete @import rules before any @font-face/style rules. Do not fetch remote CSS. */
function tomo_font_css(string $legacy, array $fonts): string
{
    $css = $legacy."\n".implode("\n", array_column($fonts, 'css'));
    // Tokenize comments, quoted strings and parenthesized URLs so delimiters inside URLs are retained.
    $imports = []; $body = ''; $length = strlen($css); $i = 0;
    while ($i < $length) {
        if (substr($css, $i, 2) === '/*') {
            $end = strpos($css, '*/', $i+2); $end = $end === false ? $length : $end+2;
            $body .= substr($css, $i, $end-$i); $i = $end; continue;
        }
        if ($css[$i] === '"' || $css[$i] === "'") {
            $start=$i; $quote=$css[$i++];
            while ($i<$length) { if ($css[$i]==='\\') {$i+=2;continue;} if ($css[$i++]===$quote) break; }
            $body.=substr($css,$start,$i-$start); continue;
        }
        if (strncasecmp(substr($css,$i,7), '@import',7)===0 && !preg_match('/[a-zA-Z0-9_-]/',$css[$i+7] ?? '')) {
            $start=$i; $quote=''; $depth=0; $i+=7;
            for (;$i<$length;$i++) {
                $c=$css[$i];
                if ($quote === '' && substr($css,$i,2) === '/*') {
                    $end = strpos($css,'*/',$i+2);
                    $i = $end === false ? $length : $end+1;
                    continue;
                }
                if ($c==='\\') {$i++;continue;}
                if ($quote!=='') {if($c===$quote)$quote='';continue;}
                if ($c==='"'||$c==="'") {$quote=$c;continue;}
                if ($c==='(') $depth++;
                if ($c===')') $depth--;
                if ($c===';'&&$depth===0) {$i++;break;}
            }
            $imports[]=substr($css,$start,$i-$start); continue;
        }
        $body.=$css[$i++];
    }
    return implode("\n",$imports)."\n".$body;
}

/** Preserve the legacy named input, including custom font stacks. JS adds the picker. */
function tomo_font_input(string $name, string $value): string
{
    return '<span class="tomo-font-choice"><select aria-label="등록한 웹폰트 선택" hidden></select><input type="text" name="'.tomo_html($name).'" value="'.tomo_html($value).'" size="40" aria-label="font-family 직접 입력"></span>';
}
