<?php
include_once __DIR__.'/_common.php';
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(403);
    exit('게시판을 통해 이용해 주세요.');
}
include_once __DIR__.'/ott.lib.php';
include_once G5_LIB_PATH.'/emoticon.lib.php'; // 이모티콘(관리자 > 이모티콘관리)

include_once($board_skin_path.'/list.skin.script.php');

$ott_attachments = [];
foreach ($list[$i]['file'] as $slot => $entry) {
    if (is_int($slot) && !empty($entry['file'])) $ott_attachments[$slot] = ott_file_url($entry, $bo_table);
}
$ott_external = [];
for ($ii=0; $ii<4; $ii++) {
    $url = ott_safe_image_url($list[$i]['wr_'.($ii+2)] ?? '');
    if ($url !== '') $ott_external[$ii] = $url;
}
$file_count = count($ott_attachments) + count($ott_external);

// 내용 콘텐츠 [파일*] 및 [이미지*] 변환
$ott_rich_content = strpos($list[$i]['wr_option'] ?? '', 'html') !== false;
$list_content = $ott_rich_content ? conv_content($list[$i]['wr_content'], 1) : htmlspecialchars((string)$list[$i]['wr_content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$file_pattern = '/\[파일\d+\]/';
$img_pattern = '/\[이미지\d+\]/';

// 첨부파일 변환
if (preg_match($file_pattern, $list[$i]['wr_content'])) {
    foreach ($ott_attachments as $j => $_file_src) {
        $file_pattern2 = '/\[파일'.($j+1).'\]/';
        $_img_tag = '<img src="'.$_file_src.'" class="thread_img" alt="">';
        $list_content = $_file_src ? preg_replace($file_pattern2, $_img_tag, $list_content) : $list_content;
    }

    $file_count = 0;
}

// 외부링크 이미지 변환
if (preg_match($img_pattern, $list[$i]['wr_content'])) {
    foreach ($ott_external as $j => $_img_src) {
        $img_pattern2 = '/\[이미지'.($j+1).'\]/';
        $_img_tag = '<img src="'.$_img_src.'" class="thread_img" alt="">';
        $list_content = $_img_src ? preg_replace($img_pattern2, $_img_tag, $list_content) : $list_content;
    }
    $file_count = 0;
}

// 게시글 상세(원문)를 볼 때만 유튜브 자동재생 (목록/타래 미리보기/답글에서는 자동재생 안 함)
$_ott_autoplay_yt = ($is_view && !$list[$i]['wr_is_comment']);
if (!$ott_rich_content) {
$list_content = convertYouTubeLinks($list_content, $_ott_autoplay_yt);
$list_content = convertXLinks($list_content);
$list_content = url_auto_link2($list_content);
$list_content = markup_content($list_content); // 텍스트 서식
$list_content = textggu_change($list_content); // 텍스트 꾸미기
$list_content = emote_ev($list_content); // 이모티콘 관련
$toggle_pattern = '/토글\[(.*?)\]\s*\n\s*\[(.*?)\]/s';
$toggle_replacement = '<details><summary>$1</summary>$2</details>';
$list_content= preg_replace($toggle_pattern, $toggle_replacement, $list_content);



} else {
    $list_content = emoticon_html($list_content); // 편집기로 쓴 글도 이모티콘 코드를 그림으로
}

// 좋아요 관련
$good_href = $board_skin_url.'/good.php?bo_table='.$bo_table.'&amp;wr_id='.$list[$i]['wr_id'].'&amp;good=good';
$good_id = $member['mb_id']? $member['mb_id'] : $_SERVER['REMOTE_ADDR'];
$good_sql = sql_fetch("SELECT count(*) AS cnt FROM {$g5['board_good_table']} WHERE bo_table ='{$bo_table}' AND wr_id = '{$list[$i]['wr_id']}' AND mb_id = '{$good_id}'");
?>
<!-- ******** 타래 본문 ******** -->
<div class="thread_main thread_main<?=$list[$i]['wr_id'];?><?=($list[$i]['wr_1']) ? ' spoiler' : '';?>">
<?php  if ($file_count > 0) { ?>
    <div class="img_box_wrap img_box_<?=$file_count;?>">
    <?php 
        foreach (array_merge(array_values($ott_attachments), array_values($ott_external)) as $file_src) {
    ?>
    <div class="img_box">
        <img src="<?=$file_src; ?>" class="thread_img" alt="">
    </div>
    <?php  } ?>
    </div>
<?php } ?>
<?php  if($list_content) { ?>
    <div class="thread_content"><?=$ott_rich_content ? $list_content : nl2br($list_content); ?></div>
<?php  } ?>
<?php  if($_like_use == 'y') {?>
<div class="wr_good<?=($good_sql['cnt']>0) ? ' is_good' : '';?>">
    <a href="javascript:void(0)" class="bo_v_good" data-wr_id="<?=$list[$i]['wr_id']?>"><i class="fa-solid fa-heart" aria-hidden="true"></i><span class="sound_only">좋아요</span></a>
    <span class="wr_good_cnt"><?=$list[$i]['wr_good'] ? number_format($list[$i]['wr_good']) : ''; ?></span>      
</div>
<?php  } ?>
<?php 
$reply_cnt = sql_fetch("SELECT count(*) as cnt FROM $write_table where wr_parent = '{$list[$i]['wr_id']}' and wr_reply != ''");
if ($board['bo_comment_level'] <= $member['mb_level']) {
?>
<div class="thread_reply">
    <button type="button" class="txt-point" onclick="openReplyComment(<?=$list[$i]['wr_id'];?>)"><i class="fa-solid fa-reply"></i></button>
</div>
<?php  } ?>
</div>
<!-- ******** 타래 본문 ******** -->
<div class="thread_reply_write thread_reply_write<?=$list[$i]['wr_id'];?>">
    <?php  include($board_skin_path.'/view_comment.php'); ?>
</div>

<?php  if ($list[$i]['wr_1']) { ?>
    <div class="spoiler_sec spoiler_sec<?=$list[$i]['wr_id'];?>">
        <p><i class="fa-solid fa-circle-exclamation"></i> 스포일러 주의</p>
        <button type="button" onclick="spoilerThreadShow(<?=$list[$i]['wr_id'];?>);">확인</button>
    </div>
<?php  } ?>

<?php  if ($_ott_autoplay_yt && strpos($list_content, 'ott_yt_autoplay') !== false) { ?>
<script>
(function(){
    // 유튜브 자동재생(음소거)이 시작되면 사이트 배경음악(BGM)을 정지합니다.
    // 사이트 테마에 window.pauseSiteBGM() 함수가 정의되어 있다면 그것을 우선 호출하고,
    // 그렇지 않다면 페이지 내 <audio>/<video> 요소를 일반적으로 정지시킵니다.
    if (typeof window.pauseSiteBGM === 'function') {
        try { window.pauseSiteBGM(); } catch (e) {}
    }
    document.querySelectorAll('audio, video').forEach(function(el){
        if (!el.closest('.ott_yt_wrap')) {
            try { el.pause(); } catch (e) {}
        }
    });
    // 사이트 테마에서 커스텀 방식으로 BGM을 제어 중이라면 아래 이벤트를 구독해 정지 처리할 수 있습니다.
    // document.addEventListener('ott:youtube-autoplay', function(){ /* BGM 정지 코드 */ });
    document.dispatchEvent(new CustomEvent('ott:youtube-autoplay'));

    // 음소거 해제 토글 버튼 (Netflix식 예고편 음소거 자동재생 → 클릭 시 소리 켜기)
    document.querySelectorAll('.ott_yt_mute_toggle').forEach(function(btn){
        if (btn.dataset.ottBound) return;
        btn.dataset.ottBound = '1';
        var muted = true;
        btn.addEventListener('click', function(){
            var frame = document.getElementById(btn.getAttribute('data-target'));
            if (!frame || !frame.contentWindow) return;
            muted = !muted;
            frame.contentWindow.postMessage(JSON.stringify({event:'command', func: muted ? 'mute' : 'unMute', args:[]}), '*');
            btn.innerHTML = muted ? '<i class="fa-solid fa-volume-xmark"></i>' : '<i class="fa-solid fa-volume-high"></i>';
        });
    });
})();
</script>
<?php  } ?>
