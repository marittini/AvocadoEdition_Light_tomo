<?php
include_once('./_common.php');

$g5['title'] = '이미지 크게보기';
include_once(G5_PATH.'/head.sub.php');

$filename = $_GET['fn'];
$bo_table = $_GET['bo_table'];

if(strpos($filename, 'data/editor')) {
    $editor_file = strstr($filename, 'editor');
    $filepath = G5_DATA_PATH.'/'.$editor_file;
} else if(strpos($filename, 'data/qa')) {
    $editor_file = strstr($filename, 'qa');
    $filepath = G5_DATA_PATH.'/'.$editor_file;
} else {
    $editor_file = '';
    $filepath = G5_DATA_PATH.'/file/'.$bo_table.'/'.$filename;
}

if(is_file($filepath)) {
    $size = @getimagesize($filepath);
    if(empty($size))
        alert_close('이미지 파일이 아닙니다.');

    $width = $size[0];
    $height = $size[1];

    if($editor_file)
        $fileurl = G5_DATA_URL.'/'.$editor_file;
    else
        $fileurl = G5_DATA_URL.'/file/'.$bo_table.'/'.$filename;

    // 그림 보호(관리자 > 우클릭 · 드래그 금지)를 켰으면 크게 보기 창에서도 막는다.
    // 보호 스크립트(defer)가 늦게 붙어도 처음부터 막히도록 그림에 직접 건다.
    include_once G5_LIB_PATH.'/tomo_settings.lib.php';
    $tomo_mask = function_exists('tomo_protection_mask') ? tomo_protection_mask() : 0;
    $guard = '';
    if ($tomo_mask & 1) $guard .= ' oncontextmenu="return false"';
    if ($tomo_mask & 2) $guard .= ' draggable="false" ondragstart="return false"';
    $img = '<img src="'.$fileurl.'" alt="" width="'.$width.'" height="'.$height.'" class="draggable"'.$guard.' style="position:relative;top:0;left:0;cursor:move;max-width:100%;height:auto;'.($tomo_mask & 1 ? '-webkit-touch-callout:none;-webkit-user-select:none;user-select:none;' : '').'">';
} else {
    alert_close('파일이 존재하지 않습니다.');
}
?>

<style>html,body { margin:0!important; padding:0!important; background:#111!important; background-image:none!important; min-height:100vh; } body>div { display:flex; justify-content:center; align-items:center; min-height:100vh; } body>div img { object-fit:contain; max-height:100vh; width:auto; height:auto!important; }</style>
<div<?php if ($tomo_mask & 1) echo ' oncontextmenu="return false"'; ?>><?php echo $img ?></div>

<script>
var win_w = <?php echo $width ?>;
var win_h = <?php echo $height ?> + 70;
var win_l = (screen.width - win_w) / 2;
var win_t = (screen.height - win_h) / 2;

if(win_w > screen.width) {
    win_l = 0;
    win_w = screen.width - 20;

    if(win_h > screen.height) {
        win_t = 0;
        win_h = screen.height - 40;
    }
}

if(win_h > screen.height) {
    win_t = 0;
    win_h = screen.height - 40;

    if(win_w > screen.width) {
        win_w = screen.width - 20;
        win_l = 0;
    }
}

window.moveTo(win_l, win_t);
window.resizeTo(win_w, win_h);

$(function() {
    var is_draggable = false;
    var x = y = 0;
    var pos_x = pos_y = 0;

    $(".draggable").mousemove(function(e) {
        if(is_draggable) {
            x = parseInt($(this).css("left")) - (pos_x - e.pageX);
            y = parseInt($(this).css("top")) - (pos_y - e.pageY);

            pos_x = e.pageX;
            pos_y = e.pageY;

            $(this).css({ "left" : x, "top" : y });
        }

        return false;
    });

    $(".draggable").mousedown(function(e) {
        pos_x = e.pageX;
        pos_y = e.pageY;
        is_draggable = true;
        return false;
    });

    $(".draggable").mouseup(function() {
        is_draggable = false;
        return false;
    });

    $(".draggable").dblclick(function() {
        window.close();
    });
});
</script>

<?php
include_once(G5_PATH.'/tail.sub.php');
?>