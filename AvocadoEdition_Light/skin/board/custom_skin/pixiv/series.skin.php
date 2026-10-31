<?php
if (!defined('_GNUBOARD_')) exit;
// Included from list.skin.php when ?px_series=ID is present.
include_once __DIR__.'/pixiv.lib.php';
$px_id = (int)$_GET['px_series'];
$px_s = pixiv_series_get($bo_table, $px_id);
$px_base = G5_BBS_URL.'/board.php?bo_table='.rawurlencode($bo_table);
$px_works = $px_s ? pixiv_series_works($bo_table, $write_table, $px_id) : [];
$px_can_edit = pixiv_series_can_edit($px_s);
if (!$px_s || (!$px_works && !$px_can_edit)) {
    echo '<div class="px-board"><p class="px-empty">시리즈를 찾을 수 없습니다.</p><p class="px-empty"><a class="ui-btn" href="'.$px_base.'&amp;px_type=series">시리즈 목록</a></p></div>';
    return;
}
$px_edit = $px_can_edit && ($_GET['px_edit'] ?? '') === '1';
$px_desc = ($_GET['px_sort'] ?? '') === 'desc';
$px_cover = pixiv_series_cover($bo_table, $px_works);
$px_tags = [];
foreach ($px_works as $px_w) if (!str_contains($px_w['wr_option'], 'secret')) foreach (pixiv_tags($px_w['wr_2']) as $px_t) $px_tags[$px_t] = ($px_tags[$px_t] ?? 0) + 1;
arsort($px_tags);
$px_tags = array_slice(array_keys($px_tags), 0, 12);
$px_first = $px_works[0] ?? null;
$px_latest = $px_works ? $px_works[count($px_works)-1] : null;
$px_list = $px_desc ? array_reverse($px_works) : $px_works;
$px_href = static fn($row) => $px_base.'&amp;wr_id='.(int)$row['wr_id'];
?>
<div class="px-board px-series-page">
<?php if (!empty($board['bo_content_head'])) { ?><div class="board-notice-box"><?=stripslashes($board['bo_content_head'])?></div><?php } ?>
<a class="px-back" href="<?=$px_base?>&amp;px_type=series">← 시리즈 목록</a>

<?php if ($px_edit) { $px_token = pixiv_series_token(); ?>
<form class="px-series-edit theme-box" method="post" action="<?=$board_skin_url?>/series_update.php" id="px-series-form">
	<input type="hidden" name="bo_table" value="<?=pixiv_escape($bo_table)?>">
	<input type="hidden" name="ps_id" value="<?=$px_id?>">
	<input type="hidden" name="token" value="<?=pixiv_escape($px_token)?>">
	<input type="hidden" name="order" id="px-series-order">
	<h2>시리즈 편집</h2>
	<div class="px-write-fields">
		<label for="px-series-title">제목</label><input id="px-series-title" name="ps_title" class="frm_input" required maxlength="60" value="<?=pixiv_escape($px_s['ps_title'])?>">
		<label for="px-series-caption">설명</label><textarea id="px-series-caption" name="ps_caption" rows="4" maxlength="1000"><?=pixiv_escape($px_s['ps_caption'])?></textarea>
		<span class="px-field-label">종류</span><span><?=pixiv_series_kind_label($px_s['ps_kind'])?> <small>(종류는 바꿀 수 없습니다)</small></span>
	</div>
	<h3>화 순서</h3>
	<p class="px-help">끌어서 옮기거나 ↑↓ 버튼으로 순서를 바꿉니다. ‘빼기’는 작품을 지우지 않고 시리즈에서만 뺍니다.</p>
	<ol class="px-episode-sort" id="px-series-sort">
	<?php foreach (pixiv_series_works($bo_table, $write_table, $px_id) as $px_w) { ?>
		<li draggable="true" data-id="<?=(int)$px_w['wr_id']?>"><span class="px-drag-handle" aria-hidden="true">⋮⋮</span><span class="px-episode-no"></span><span class="px-episode-name"><?=pixiv_escape($px_w['wr_subject'])?></span><button type="button" class="ui-btn small" data-move="-1" aria-label="<?=pixiv_escape($px_w['wr_subject'])?> 앞으로">↑</button><button type="button" class="ui-btn small" data-move="1" aria-label="<?=pixiv_escape($px_w['wr_subject'])?> 뒤로">↓</button><button type="button" class="ui-btn small" data-remove aria-label="<?=pixiv_escape($px_w['wr_subject'])?> 시리즈에서 빼기">빼기</button></li>
	<?php } ?>
	</ol>
	<div class="btn_confirm">
		<button type="submit" class="ui-btn point" name="mode" value="save">저장</button>
		<a class="ui-btn" href="<?=pixiv_series_url($bo_table,$px_id)?>">취소</a>
		<button type="submit" class="ui-btn px-danger" name="mode" value="delete" data-confirm="시리즈를 삭제할까요? 작품은 삭제되지 않고 시리즈에서만 빠집니다.">시리즈 삭제</button>
	</div>
</form>
<script src="<?=pixiv_asset_url($board_skin_url, 'series.js')?>" defer></script>
<?php } else { ?>
<header class="px-series-head">
	<div class="px-series-cover"><?php if ($px_cover) { ?><img src="<?=$px_cover?>" alt=""><?php } else { ?><span class="px-placeholder"><span class="px-novel-preview"><?=pixiv_escape($px_s['ps_title'])?></span></span><?php } ?></div>
	<div class="px-series-info">
		<span class="px-kind"><?=pixiv_series_kind_label($px_s['ps_kind'])?></span>
		<h1><?=pixiv_escape($px_s['ps_title'])?></h1>
		<p class="px-series-stats"><strong><?=count($px_works)?>화</strong><?php if ($px_latest) { ?> · <time datetime="<?=pixiv_escape(str_replace(' ','T',$px_s['ps_update']))?>"><?=pixiv_escape(substr($px_s['ps_update'],0,10))?> 업데이트</time><?php } ?></p>
		<?php if (trim($px_s['ps_caption']) !== '') { ?><div class="px-note"><?=nl2br(pixiv_escape($px_s['ps_caption']))?></div><?php } ?>
		<?php if ($px_tags) { ?><div class="px-tags"><?php foreach ($px_tags as $px_t) { ?><a href="<?=$px_base?>&amp;tag[]=<?=rawurlencode($px_t)?>">#<?=pixiv_escape($px_t)?></a><?php } ?></div><?php } ?>
		<div class="px-series-actions">
			<?php if ($px_first) { ?><a class="ui-btn point" href="<?=$px_href($px_first)?>">처음부터 읽기</a><?php } ?>
			<?php if ($px_latest && $px_latest !== $px_first) { ?><a class="ui-btn" href="<?=$px_href($px_latest)?>">최신화 #<?=(int)$px_latest['px_no']?></a><?php } ?>
			<?php if ($px_can_edit) { ?><a class="ui-btn" href="<?=pixiv_series_url($bo_table,$px_id,['px_edit'=>1])?>">시리즈 편집</a><?php if ($write_href) { ?><a class="ui-btn" href="<?=$write_href?>&amp;px_series=<?=$px_id?>">+ 새 화 올리기</a><?php } } ?>
		</div>
	</div>
</header>

<section class="px-episodes" aria-labelledby="px-episodes-title">
	<div class="px-episodes-bar">
		<h2 id="px-episodes-title">작품 <?=count($px_works)?></h2>
		<nav class="px-sort" aria-label="정렬"><a href="<?=pixiv_series_url($bo_table,$px_id)?>" <?=!$px_desc?'aria-current="true"':''?>>1화부터</a><a href="<?=pixiv_series_url($bo_table,$px_id,['px_sort'=>'desc'])?>" <?=$px_desc?'aria-current="true"':''?>>최신화부터</a></nav>
	</div>
	<?php if (!$px_works) { ?><p class="px-empty">아직 작품이 없습니다. 글쓰기에서 이 시리즈를 선택해 주세요.</p><?php } ?>
	<ol class="px-episode-list">
	<?php foreach ($px_list as $px_w) {
		$px_secret = str_contains($px_w['wr_option'], 'secret');
		$px_files = $px_secret ? [] : pixiv_files($bo_table, (int)$px_w['wr_id'], $px_w['wr_3']);
		$px_c = pixiv_cover_file($px_files, $px_w['wr_3']); ?>
		<li><a class="px-episode" href="<?=$px_href($px_w)?>">
			<span class="px-episode-thumb"><?php if ($px_c) { ?><img src="<?=pixiv_cover_url($bo_table,$px_c)?>" alt="" loading="lazy"><?php } else { ?><span class="px-placeholder"><?=$px_secret?'🔒':($px_w['wr_1']==='pixiv:novel'?'소설':'')?></span><?php } ?><?php if (count($px_files)>1) { ?><span class="px-count"><?=count($px_files)?>장</span><?php } ?></span>
			<span class="px-episode-body">
				<span class="px-episode-no">#<?=(int)$px_w['px_no']?></span>
				<strong class="px-episode-name"><?php if ($px_secret) { ?><span class="px-lock" aria-label="비밀글">🔒</span><?php } ?><?=pixiv_escape($px_w['wr_subject'])?></strong>
				<?php if (!$px_secret && trim($px_w['wr_4']) !== '') { ?><span class="px-episode-note"><?=pixiv_escape(cut_str($px_w['wr_4'], 80, '…'))?></span><?php } ?>
				<time datetime="<?=pixiv_escape(str_replace(' ','T',$px_w['wr_datetime']))?>"><?=pixiv_escape(substr($px_w['wr_datetime'],0,10))?></time>
			</span>
		</a></li>
	<?php } ?>
	</ol>
</section>
<?php } ?>
</div>
