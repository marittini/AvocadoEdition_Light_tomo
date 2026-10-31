<?php
// Run with PHP 8.4: php tests/ucho_menu_test.php
const _GNUBOARD_ = true;
require __DIR__.'/../AvocadoEdition_Light/skin/menu/ucho_menu/menu.lib.php';
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$tree = ucho_menu_tree([
    ['me_code'=>'1010', 'me_name'=>'Child'],
    ['me_code'=>'9999', 'me_name'=>'Orphan'],
    ['me_code'=>'20', 'me_name'=>'Second'],
    ['me_code'=>'10', 'me_name'=>'Parent'],
]);
check(count($tree) === 2, 'Only top-level entries belong to the root');
check(array_keys($tree) === [20,10], 'Preserve root sort order');
check($tree[10]['sub'][0]['me_name'] === 'Child', 'Attach children independently of query order');
check(ucho_menu_url('javascript:alert(1)') === '#', 'Reject executable links');
check(ucho_menu_url("java\nscript:alert(1)") === '#', 'Reject control characters');
check(ucho_menu_url('/bbs/board.php?a=1&b=2') === '/bbs/board.php?a=1&amp;b=2', 'Escape valid URLs');
check(ucho_menu_escape('<script>') === '&lt;script&gt;', 'Escape menu names');
echo "PASS ucho menu grouping and escaping\n";
