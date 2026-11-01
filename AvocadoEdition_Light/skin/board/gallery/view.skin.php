<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once G5_LIB_PATH.'/thumbnail.lib.php';
include_once __DIR__.'/gallery.lib.php';
gl_assets($board_skin_url);

// 멤버공개 · 보호글 확인(비밀글은 그누보드 view.php가 먼저 확인한다)
if (!gl_can_read($view)) {
	if (!empty($view['wr_protect'])) goto_url($board_skin_url.'/protect.php?bo_table='.rawurlencode($bo_table).'&amp;wr_id='.(int)$view['wr_id']);
	alert('멤버공개 글입니다. 로그인 후 이용해 주세요.', G5_BBS_URL.'/login.php?url='.urlencode(G5_BBS_URL.'/board.php?bo_table='.$bo_table.'&wr_id='.(int)$view['wr_id']));
}

$gl_kind = gl_kind($view);
$gl_vis = gl_visibility($view);
$gl_width = (int)($board['bo_table_width'] ?? 0);

// 같은 분류 안의 이전 · 다음 글(오래된 글 → 새 글 순서. 연재 글을 차례로 읽을 수 있다)
$gl_where = "wr_is_comment = 0 AND wr_reply = ''".($is_category ? " AND ca_name = '".sql_real_escape_string((string)$view['ca_name'])."'" : '');
$gl_older = sql_fetch("SELECT wr_id, wr_subject FROM `{$write_table}` WHERE {$gl_where} AND wr_num > ".(int)$view['wr_num']." ORDER BY wr_num ASC LIMIT 1");
$gl_newer = sql_fetch("SELECT wr_id, wr_subject FROM `{$write_table}` WHERE {$gl_where} AND wr_num < ".(int)$view['wr_num']." ORDER BY wr_num DESC LIMIT 1");
$gl_back = gl_url($bo_table, ['page' => (int)$page, 'sca' => $is_category ? (string)$view['ca_name'] : '']);

$gl_images = '';
$gl_files = [];
for ($i = 0; $i < count($view['file']); $i++) {
	if (!empty($view['file'][$i]['view'])) $gl_images .= get_view_thumbnail($view['file'][$i]['view']);
	elseif (!empty($view['file'][$i]['source'])) $gl_files[] = $view['file'][$i];
}
?>
<script src="<?=G5_JS_URL?>/viewimageresize.js?v=2"></script>

<div class="gl gl-page-view"<?php if ($gl_width > 0) { ?> style="max-width:<?=$gl_width?><?=$gl_width > 100 ? 'px' : '%'?>;"<?php } ?>>
<div class="gl-paper theme-box">
<article id="bo_v" class="gl-view is-<?=$gl_kind?>" data-gl-view data-gl-id="<?=(int)$view['wr_id']?>">
	<header class="gl-v-head">
		<p class="gl-v-meta">
			<?php if ($is_category && $view['ca_name'] !== '') { ?><span><?=gl_e($view['ca_name'])?></span><?php } ?>
			<?php if ($gl_kind === 'text') { ?><span>글</span><?php } ?>
			<?php if ($gl_vis !== '') { ?><span class="gl-v-vis"><i class="material-icons" aria-hidden="true"><?=GL_VISIBILITY_ICON[$gl_vis]?></i><?=GL_VISIBILITY[$gl_vis]?></span><?php } ?>
		</p>
		<h1 class="gl-v-title"><?=gl_e(get_text($view['wr_subject']))?></h1>
		<p class="gl-v-sub">
			<?php if (trim((string)$view['wr_1']) !== '') { ?><span><?=gl_e($view['wr_1'])?></span><?php } ?>
			<time datetime="<?=gl_e(date('c', strtotime($view['wr_datetime'])))?>"><?=date('Y.m.d', strtotime($view['wr_datetime']))?></time>
			<?php if ($gl_kind === 'text') { ?><span><?=number_format(gl_char_count((string)$view['wr_content']))?>자</span><?php } ?>
		</p>
	</header>

	<?php if ($gl_images !== '') { ?><div class="gl-v-images" id="bo_v_img"><?=$gl_images?></div><?php } ?>
	<div class="gl-v-body" id="bo_v_con"><?=get_view_thumbnail($view['content'])?></div>

	<?php if ($gl_files) { ?>
	<ul class="gl-v-files">
		<?php foreach ($gl_files as $f) { ?><li><a href="<?=$f['href']?>" class="view_file_download"><i class="material-icons" aria-hidden="true">attach_file</i> <?=$f['source']?> <span>(<?=$f['size']?>)</span></a></li><?php } ?>
	</ul>
	<?php } ?>

	<?php if (!empty($gl_older['wr_id']) || !empty($gl_newer['wr_id'])) { ?>
	<nav class="gl-v-nav" aria-label="이전 · 다음 글">
		<?php if (!empty($gl_older['wr_id'])) { ?><a href="<?=gl_url($bo_table, ['wr_id' => (int)$gl_older['wr_id']])?>" class="is-prev" data-gl-open><small>이전 글</small><span><?=gl_e(get_text($gl_older['wr_subject']))?></span></a><?php } else { ?><span></span><?php } ?>
		<?php if (!empty($gl_newer['wr_id'])) { ?><a href="<?=gl_url($bo_table, ['wr_id' => (int)$gl_newer['wr_id']])?>" class="is-next" data-gl-open><small>다음 글</small><span><?=gl_e(get_text($gl_newer['wr_subject']))?></span></a><?php } ?>
	</nav>
	<?php } ?>

	<div class="gl-v-actions">
		<?php if ($update_href) { ?><a href="<?=$update_href?>" class="ui-btn small etc">수정</a><?php } ?>
		<?php if ($delete_href) { ?><a href="<?=$delete_href?>" class="ui-btn small" onclick="del(this.href); return false;">삭제</a><?php } ?>
		<a href="<?=$gl_back?>" class="ui-btn small gl-back">목록</a>
	</div>

	<?php include_once G5_BBS_PATH.'/view_comment.php'; ?>
</article>
</div>
</div>
<?=gl_js($board_skin_url)?>
