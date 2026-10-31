<?php
if (!defined('_GNUBOARD_')) exit;
include_once __DIR__.'/pixiv.lib.php';
include_once G5_LIB_PATH.'/thumbnail.lib.php';
add_stylesheet('<link rel="stylesheet" href="'.pixiv_asset_url($board_skin_url, 'style.css').'">',0);
$images=pixiv_files($bo_table,(int)$wr_id,$write['wr_3']??'');
$cover=pixiv_cover_file($images,$write['wr_3']??'');
$note=pixiv_note($write);$tags=pixiv_tags($write['wr_2']??'');$isNovel=($write['wr_1']??'')==='pixiv:novel';$isManga=($write['wr_1']??'')==='pixiv:manga';
$px_base=G5_BBS_URL.'/board.php?bo_table='.rawurlencode($bo_table);

// Series context: position, previous and next readable work.
$px_series=pixiv_series_of_work($bo_table,(int)$wr_id);
$px_prev=$px_next=null;$px_no=0;$px_total=0;
if($px_series){
    $px_works=pixiv_series_works($bo_table,$write_table,(int)$px_series['ps_id']);
    $px_total=count($px_works);
    foreach($px_works as $i=>$px_w) if((int)$px_w['wr_id']===(int)$wr_id){$px_no=(int)$px_w['px_no'];$px_prev=$px_works[$i-1]??null;$px_next=$px_works[$i+1]??null;}
}
$px_thumb=static function($row) use($bo_table){
    if(str_contains($row['wr_option'],'secret')) return '';
    $f=pixiv_files($bo_table,(int)$row['wr_id'],$row['wr_3']);
    return $f?pixiv_cover_url($bo_table,pixiv_cover_file($f,$row['wr_3'])):'';
};
$px_next_href=$px_next?$px_base.'&amp;wr_id='.(int)$px_next['wr_id']:'';
?>
<noscript><p>작품을 읽으려면 자바스크립트를 활성화해 주세요.</p></noscript>
<article id="bo_v" class="px-detail">
<?php if(!empty($board['bo_content_head'])){?><div class="board-notice-box"><?=stripslashes($board['bo_content_head'])?></div><?php } ?>
<a class="px-back" href="<?=$list_href?>">← 목록</a>
<div class="px-detail-layout"><div class="px-detail-cover">
<div class="px-stage">
<button type="button" class="px-cover" id="px-open" aria-label="작품 보기"><?php if($images){?><img src="<?=$cover['url']?>" alt="작품 표지"><?php } else { ?><span><?=$isNovel?'소설 읽기':'작품 보기'?></span><?php } ?><?php if(count($images)>1){?><span class="px-count"><?=count($images)?></span><?php } ?></button>
</div>
<?php if(count($images)>1 || $isNovel){?><button type="button" class="px-view-all" data-open><?=$isNovel?'소설 읽기':'모두 보기'?><?php if(count($images)>1){?> <span><?=count($images)?>장</span><?php } ?></button><?php } ?>
</div><div class="px-detail-info">
<?php if($px_series){?><a class="px-series-chip px-series-crumb" href="<?=pixiv_series_url($bo_table,(int)$px_series['ps_id'])?>"><span class="px-kind"><?=pixiv_series_kind_label($px_series['ps_kind'])?></span> <?=pixiv_escape($px_series['ps_title'])?><?php if($px_no){?> <b>#<?=$px_no?></b><?php } ?></a><?php } ?>
<h1><?=pixiv_escape($view['wr_subject'])?></h1>
<time class="px-upload-date" datetime="<?=pixiv_escape(str_replace(' ','T',$view['wr_datetime']))?>"><?=pixiv_escape(substr($view['wr_datetime'],0,16))?></time>
<?php if($note!==''){?><div class="px-note"><?=nl2br(pixiv_escape($note))?></div><?php } ?>
<div class="px-tags"><?php foreach($tags as $tag){?><a href="<?=$px_base?>&amp;tag[]=<?=rawurlencode($tag)?>">#<?=pixiv_escape($tag)?></a><?php } ?></div>
<?php if($px_series){?>
<nav class="px-series-nav" aria-label="시리즈 이동">
	<div class="px-series-nav-head"><a href="<?=pixiv_series_url($bo_table,(int)$px_series['ps_id'])?>"><strong><?=pixiv_escape($px_series['ps_title'])?></strong></a><span><?=$px_no?>/<?=$px_total?>화</span><a class="px-series-all" href="<?=pixiv_series_url($bo_table,(int)$px_series['ps_id'])?>">전체 보기 ›</a></div>
	<div class="px-series-nav-items">
	<?php foreach(['prev'=>[$px_prev,'‹ 이전 화'],'next'=>[$px_next,'다음 화 ›']] as $px_dir=>[$px_row,$px_label]){?>
		<?php if($px_row){$px_img=$px_thumb($px_row);?><a class="px-series-step is-<?=$px_dir?>" href="<?=$px_base?>&amp;wr_id=<?=(int)$px_row['wr_id']?>" rel="<?=$px_dir?>"><span class="px-series-step-thumb"><?php if($px_img){?><img src="<?=$px_img?>" alt="" loading="lazy"><?php } ?></span><span class="px-series-step-text"><small><?=$px_label?></small><span>#<?=(int)$px_row['px_no']?> <?=pixiv_escape($px_row['wr_subject'])?></span></span></a>
		<?php } else { ?><span class="px-series-step is-<?=$px_dir?> is-empty"><small><?=$px_dir==='prev'?'첫 화입니다':'최신화입니다'?></small></span><?php } ?>
	<?php } ?>
	</div>
</nav>
<?php } ?>
<div class="btn_confirm"><?php if($update_href){?><a href="<?=$update_href?>" class="ui-btn">수정</a><?php } ?><?php if($delete_href){?><a href="<?=$delete_href?>" onclick="del(this.href); return false;" class="ui-btn">삭제</a><?php } ?><a href="<?=$list_href?>" class="ui-btn">목록</a></div>
<?php include G5_BBS_PATH.'/view_comment.php'; ?>
</div></div>
</article>
<?php
// Shown at the end of every viewer: continue the series without closing the viewer first.
ob_start(); ?>
<div class="px-end">
	<p class="px-end-title"><?=$px_series?pixiv_escape($px_series['ps_title']).' #'.$px_no.' 끝':'마지막입니다'?></p>
	<div class="px-end-actions">
	<?php if($px_next){?><a class="ui-btn point" href="<?=$px_next_href?>">다음 화 · #<?=(int)$px_next['px_no']?> <?=pixiv_escape($px_next['wr_subject'])?></a><?php } ?>
	<?php if($px_series){?><a class="ui-btn" href="<?=pixiv_series_url($bo_table,(int)$px_series['ps_id'])?>">시리즈 목록</a><?php } ?>
	<button type="button" class="ui-btn" data-end="close">닫기</button>
	</div>
</div>
<?php $px_end=ob_get_clean(); ?>
<div class="px-reader theme-box" id="px-reader" hidden role="dialog" aria-modal="true" aria-label="작품 읽기" data-kind="<?=$isManga?'manga':($isNovel?'novel':'illustration')?>">
<header><?php if($isNovel){?><button type="button" data-action="settings" aria-expanded="false" aria-controls="px-reader-options">Aa</button><?php } elseif(count($images)>1) { ?><button type="button" data-action="paged" title="한 장씩 보기">한 장씩</button><?php } else { ?><span aria-hidden="true"></span><?php } ?><span><?=pixiv_escape($view['wr_subject'])?></span><button type="button" data-action="close">✕ 닫기</button></header>
<?php if($isNovel){?><div class="px-reader-options" id="px-reader-options" hidden>
	<div class="px-segment" role="group" aria-label="보기 방식"><button type="button" data-action="scroll" aria-pressed="true">스크롤</button><button type="button" data-action="pages" aria-pressed="false">페이지 넘김</button></div>
	<label>글자 크기 <input type="range" min="14" max="28" value="18" data-font></label><label>줄 간격 <input type="range" min="14" max="26" value="19" data-line></label>
</div><?php } ?>
<div class="px-reader-scroll"><?php if($isNovel){?><div class="px-novel" id="bo_v_con"><?=get_view_thumbnail($view['content'])?></div><?php } else { ?><div class="px-images"><?php foreach($images as $i=>$img){?><button type="button" class="px-page" data-page="<?=$i?>" aria-label="<?=($i+1)?>쪽부터 한 장씩 보기"><img src="<?=$img['url']?>" alt="<?=($i+1)?>쪽" loading="lazy"></button><?php } ?></div><?php if(trim(strip_tags($view['content']))!==''){?><div class="px-content" id="bo_v_con"><?=get_view_thumbnail($view['content'])?></div><?php } } ?><?=$px_end?></div>
<footer hidden><button type="button" data-action="prev">‹ 이전</button><span data-progress></span><button type="button" data-action="next">다음 ›</button></footer>
</div>
<div class="px-comic theme-box" id="px-comic" hidden role="dialog" aria-modal="true" aria-label="한 장씩 보기">
<header><button type="button" data-comic="vertical" title="세로로 이어 보기">세로 보기</button><span><?=pixiv_escape($view['wr_subject'])?></span><button type="button" data-comic="close">✕ 닫기</button></header>
<div class="px-comic-stage"><button type="button" class="px-comic-prev" data-comic="prev" aria-label="이전 페이지">‹</button><img class="px-comic-image" alt=""><button type="button" class="px-comic-next" data-comic="next" aria-label="다음 페이지">›</button>
<div class="px-comic-end" hidden><?=$px_end?></div></div>
<footer><button type="button" data-comic="grid" aria-expanded="false" aria-controls="px-comic-grid">목록</button><input type="range" min="1" max="<?=max(1,count($images))?>" value="1" data-comic-range aria-label="페이지 이동"><span data-comic-progress aria-live="polite"></span></footer>
<div class="px-comic-grid" id="px-comic-grid" hidden><?php foreach($images as $i=>$img){?><button type="button" data-goto="<?=$i?>"><img src="<?=pixiv_cover_url($bo_table,$img)?>" alt="" loading="lazy"><span><?=($i+1)?></span></button><?php } ?></div>
</div>
<script src="<?=pixiv_asset_url($board_skin_url, 'viewer.js')?>" defer></script>
