<?php
require_once dirname(__DIR__, 3).'/common.php';
include_once __DIR__.'/menu.lib.php';
if ($is_admin !== 'super') { http_response_code(403); alert('최고관리자만 메뉴 구조를 수정할 수 있습니다.'); }
if (empty($_SESSION['ucho_menu_token'])) $_SESSION['ucho_menu_token'] = bin2hex(random_bytes(32));
$rows = [];
$result = sql_query("SELECT me_id, me_code, me_name FROM {$g5['menu_table']} ORDER BY me_code, me_id");
while ($row = sql_fetch_array($result)) $rows[(int)$row['me_id']] = $row;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['ucho_menu_token'], $token)) alert('요청을 확인할 수 없습니다. 다시 열어 주세요.');
    $codes = $_POST['codes'] ?? null;
    if (!is_array($codes) || count($codes) !== count($rows)) alert('메뉴가 변경되었습니다. 새로고침 후 다시 설정해 주세요.');
    $seen = [];
    foreach ($codes as $id => $code) {
        if (!ctype_digit((string)$id) || (string)(int)$id !== (string)$id || !isset($rows[(int)$id]) || !is_string($code) || !preg_match('/^(?:[0-9]{2}|[0-9]{4})$/D', $code)) alert('메뉴 코드는 2자리 또는 4자리 숫자로 입력해 주세요.');
        if (isset($seen[$code])) alert('메뉴 코드가 중복되었습니다.');
        $seen[$code] = true;
    }
    foreach ($codes as $code) if (strlen($code) === 4 && !isset($seen[substr($code,0,2)])) alert('하위 메뉴의 부모 코드가 없습니다.');
    if ($codes) {
        $cases = []; $ids = [];
        foreach ($codes as $id => $code) { $id = (int)$id; $ids[] = $id; $cases[] = "WHEN {$id} THEN '{$code}'"; }
        sql_query("UPDATE {$g5['menu_table']} SET me_code=CASE me_id ".implode(' ', $cases)." END WHERE me_id IN (".implode(',', $ids).")");
    }
    goto_url(G5_URL.'/skin/menu/'.rawurlencode(basename(__DIR__)).'/setup.php?saved=1');
}
$g5['title'] = '메뉴 구조 설정';
include_once G5_PATH.'/head.sub.php';
?>
<style>.ucho-setup{max-width:760px;margin:24px auto;padding:20px;box-sizing:border-box}.ucho-setup table{width:100%}.ucho-setup th,.ucho-setup td{padding:10px}.ucho-setup input{max-width:100%;width:90px;box-sizing:border-box}.ucho-setup .btn_confirm{margin-top:20px}</style>
<main class="ucho-setup theme-box"><h1>메뉴 구조 설정</h1>
<p>대메뉴는 2자리(예: 10), 하위 메뉴는 부모 코드와 2자리를 합친 4자리(예: 1010)로 지정하세요.</p>
<?php if (isset($_GET['saved'])) { ?><p role="status">저장했습니다.</p><?php } ?>
<form method="post"><input type="hidden" name="token" value="<?=ucho_menu_escape($_SESSION['ucho_menu_token'])?>">
<table class="theme-form"><thead><tr><th>메뉴명</th><th>메뉴 코드</th></tr></thead><tbody>
<?php foreach ($rows as $id => $row) { ?><tr><th scope="row"><label for="menu-code-<?=$id?>"><?=ucho_menu_escape($row['me_name'])?></label></th><td><input class="frm_input" id="menu-code-<?=$id?>" name="codes[<?=$id?>]" value="<?=ucho_menu_escape($row['me_code'])?>" required pattern="[0-9]{2}|[0-9]{4}" maxlength="4" inputmode="numeric"></td></tr><?php } ?>
</tbody></table><div class="btn_confirm"><button class="ui-btn point" type="submit">구조 저장</button></div></form></main>
<?php include_once G5_PATH.'/tail.sub.php'; ?>
