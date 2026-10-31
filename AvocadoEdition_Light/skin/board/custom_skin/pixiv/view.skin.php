<?php
if (!defined('_GNUBOARD_')) exit;
include_once __DIR__.'/pixiv.lib.php';
include_once G5_LIB_PATH.'/thumbnail.lib.php';
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css?v=1">',0);
$images=pixiv_files($bo_table,(int)$wr_id,$write['wr_3']??'');
$note=pixiv_note($write);$tags=pixiv_tags($write['wr_2']??'');$isNovel=($write['wr_1']??'')==='pixiv:novel';
?>
<noscript><p>작품을 읽으려면 자바스크립트를 활성화해 주세요.</p></noscript>
<article id="bo_v" class="px-detail">
<?php if(!empty($board['bo_content_head'])){?><div class="board-notice-box"><?=stripslashes($board['bo_content_head'])?></div><?php } ?>
<a href="<?=$list_href?>">← 목록</a>
<button type="button" class="px-cover" id="px-open" aria-label="작품 보기"><?php if($images){?><img src="<?=pixiv_cover_url($bo_table,$images[0])?>" alt="작품 표지"><?php } else { ?><span><?=$isNovel?'소설 읽기':'작품 보기'?></span><?php } ?><?php if(count($images)>1){?><span class="px-count"><?=count($images)?></span><?php } ?></button>
<h1><?=pixiv_escape($view['wr_subject'])?></h1>
<div class="px-tags"><?php foreach($tags as $tag){?><a href="<?=G5_BBS_URL?>/board.php?bo_table=<?=rawurlencode($bo_table)?>&amp;tag[]=<?=rawurlencode($tag)?>">#<?=pixiv_escape($tag)?></a><?php } ?></div>
<?php if($note!==''){?><div class="px-note"><?=nl2br(pixiv_escape($note))?></div><?php } ?>
<?php include G5_BBS_PATH.'/view_comment.php'; ?>
<div class="btn_confirm"><?php if($update_href){?><a href="<?=$update_href?>" class="ui-btn">수정</a><?php } ?><?php if($delete_href){?><a href="<?=$delete_href?>" onclick="del(this.href); return false;" class="ui-btn">삭제</a><?php } ?><a href="<?=$list_href?>" class="ui-btn">목록</a></div>
</article>
<div class="px-reader theme-box" id="px-reader" hidden role="dialog" aria-modal="true" aria-label="작품 읽기">
<header><button type="button" data-action="close">← 상세</button><span><?=pixiv_escape($view['wr_subject'])?></span><?php if($isNovel){?><button type="button" data-action="settings" aria-expanded="false">Aa</button><?php } else { ?><span></span><?php } ?></header>
<?php if($isNovel){?><div class="px-reader-options" hidden><button type="button" data-action="scroll">스크롤</button><button type="button" data-action="pages">페이지</button><label>글자 크기 <input type="range" min="14" max="28" value="18" data-font></label><label>줄 간격 <input type="range" min="14" max="26" value="19" data-line></label></div><?php } ?>
<div class="px-reader-scroll"><?php if($isNovel){?><div class="px-novel"><?=get_view_thumbnail($view['content'])?></div><?php } else { ?><div class="px-images"><?php foreach($images as $i=>$img){?><button type="button" data-page="<?=$i?>" aria-label="<?=($i+1)?>쪽부터 만화 보기"><img src="<?=$img['url']?>" alt="<?=($i+1)?>쪽" loading="lazy"></button><?php } ?></div><?php if(trim(strip_tags($view['content']))!==''){?><div class="px-content" id="bo_v_con"><?=get_view_thumbnail($view['content'])?></div><?php } } ?></div>
<footer hidden><button type="button" data-action="prev">이전</button><span data-progress></span><button type="button" data-action="next">다음</button></footer>
</div>
<div class="px-comic theme-box" id="px-comic" hidden role="dialog" aria-modal="true" aria-label="만화 보기">
<header><button type="button" data-comic="close">← 본문</button><span data-comic-progress></span><button type="button" data-comic="grid">페이지</button></header>
<button type="button" class="px-comic-prev" data-comic="prev" aria-label="이전 페이지">‹</button><img class="px-comic-image" alt=""><button type="button" class="px-comic-next" data-comic="next" aria-label="다음 페이지">›</button>
<div class="px-comic-grid" hidden></div><footer><input type="range" min="1" max="<?=max(1,count($images))?>" value="1" aria-label="페이지 선택" data-comic-range></footer>
</div>
<script src="<?=$board_skin_url?>/viewer.js?v=1" defer></script>
