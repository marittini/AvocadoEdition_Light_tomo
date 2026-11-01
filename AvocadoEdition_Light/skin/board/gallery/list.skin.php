<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once G5_LIB_PATH.'/thumbnail.lib.php';
include_once __DIR__.'/gallery.lib.php';
gl_assets($board_skin_url);

$gl_width = (int)($board['bo_table_width'] ?? 0);
$gl_protect = gl_has_protect_column($write_table);
$gl_search = trim((string)$stx) !== '';
// 분류를 쓰면 분류마다 접는 폴더(쪽 나눔 없음). 검색 중이거나 분류를 쓰지 않으면 그누보드 목록(쪽 나눔)을 그대로 쓴다.
$gl_folders = $is_category && !$gl_search;

/** 카드 한 장. */
$gl_card = static function (array $row) use ($bo_table): string {
	$id = (int)$row['wr_id'];
	$open = gl_can_read($row);
	$kind = gl_kind($row);
	$vis = gl_visibility($row);
	$title = get_text($row['wr_subject']);
	$h = '<li><a href="'.gl_url($bo_table, ['wr_id' => $id]).'" class="gl-card is-'.($open ? $kind : 'locked').'" data-gl-open>';
	if (!$open) {
		$h .= '<span class="gl-sheet"><i class="material-icons" aria-hidden="true">'.(GL_VISIBILITY_ICON[$vis] ?? 'lock').'</i><span>'.GL_VISIBILITY[$vis].'</span></span>';
	} elseif ($kind === 'art') {
		$thumb = gl_thumb($bo_table, $id);
		$h .= $thumb !== ''
			? '<span class="gl-thumb"><img src="'.gl_e($thumb).'" alt="" loading="lazy"></span>'
			: '<span class="gl-sheet"><i class="material-icons" aria-hidden="true">image</i></span>';
	} else {
		$h .= '<span class="gl-sheet gl-page"><span class="gl-kind">글</span><span class="gl-excerpt">'.gl_e(gl_excerpt((string)$row['wr_content'])).'</span>'
			.'<span class="gl-count">'.number_format(gl_char_count((string)$row['wr_content'])).'자</span></span>';
	}
	$h .= '<span class="gl-cap">';
	if ($vis !== '' && $open) $h .= '<i class="material-icons" aria-hidden="true">'.GL_VISIBILITY_ICON[$vis].'</i><span class="sound_only">'.GL_VISIBILITY[$vis].'</span>';
	return $h.'<span class="gl-title">'.gl_e($title).'</span></span></a></li>';
};

if ($gl_folders) {
	$gl_cols = 'wr_id, wr_num, ca_name, wr_subject, wr_content, wr_option, wr_secret, mb_id, wr_2'.($gl_protect ? ', wr_protect' : '');
	$gl_groups = [];
	foreach (array_filter(array_map('trim', explode('|', (string)$board['bo_category_list'])), 'strlen') as $name) $gl_groups[$name] = [];
	$res = sql_query("SELECT {$gl_cols} FROM `{$write_table}` WHERE wr_is_comment = 0 AND wr_reply = '' ORDER BY wr_num, wr_reply");
	while ($row = sql_fetch_array($res)) $gl_groups[(string)$row['ca_name'] !== '' ? (string)$row['ca_name'] : ''][] = $row;
	if (isset($gl_groups['']) && !$gl_groups['']) unset($gl_groups['']);
}
?>

<!-- 갤러리 { -->
<div class="gl gl-list"<?php if ($gl_width > 0) { ?> style="max-width:<?=$gl_width?><?=$gl_width > 100 ? 'px' : '%'?>;"<?php } ?> data-gl-board="<?=gl_e($bo_table)?>">
	<?php if ($board['bo_content_head']) { ?><div class="board-notice-box"><?=stripslashes($board['bo_content_head'])?></div><?php } ?>

	<?php if ($write_href || $admin_href) { ?>
	<div class="gl-bar">
		<?php if ($admin_href) { ?><a href="<?=$admin_href?>" class="ui-btn small etc" target="_blank">관리자</a><?php } ?>
		<?php if ($write_href) { ?><a href="<?=$write_href?>" class="ui-btn small point">글쓰기</a><?php } ?>
	</div>
	<?php } ?>

	<?php if ($gl_folders) { ?>
	<div class="gl-folders">
		<?php $n = 0; foreach ($gl_groups as $name => $rows) { $n++; $fid = 'gl-f'.$n; $open = (string)$sca !== '' && (string)$sca === (string)$name; ?>
		<section class="gl-folder theme-box" data-gl-folder="<?=gl_e($name === '' ? '-' : $name)?>">
			<h2 class="gl-folder-head">
				<button type="button" aria-expanded="<?=$open ? 'true' : 'false'?>" aria-controls="<?=$fid?>" data-gl-toggle>
					<i class="material-icons gl-folder-icon" aria-hidden="true"><?=$open ? 'folder_open' : 'folder'?></i>
					<span class="gl-folder-name"><?=gl_e($name === '' ? '분류 없음' : $name)?></span>
					<span class="gl-folder-count"><?=count($rows)?></span>
					<i class="material-icons gl-chev" aria-hidden="true">expand_more</i>
				</button>
			</h2>
			<div class="gl-folder-body" id="<?=$fid?>"<?=$open ? '' : ' hidden'?>>
				<?php if ($rows) { ?><ul class="gl-grid"><?php foreach ($rows as $row) echo $gl_card($row); ?></ul>
				<?php } else { ?><p class="gl-empty">아직 글이 없습니다.</p><?php } ?>
			</div>
		</section>
		<?php } ?>
		<?php if (!$gl_groups) { ?><p class="gl-empty theme-box">분류가 없습니다. 게시판 설정에서 분류를 추가해 주세요.</p><?php } ?>
	</div>
	<?php } else { ?>
	<section class="gl-paper theme-box">
		<?php if ($gl_search) { ?><p class="gl-result">‘<?=gl_e(stripslashes((string)$stx))?>’ 찾은 글 <b><?=number_format((int)$total_count)?></b></p><?php } ?>
		<?php if ($list) { ?><ul class="gl-grid"><?php foreach ($list as $row) echo $gl_card($row); ?></ul>
		<?php } else { ?><p class="gl-empty"><?=$gl_search ? '찾는 글이 없습니다.' : '아직 글이 없습니다.'?></p><?php } ?>
	</section>
	<?=$write_pages?>
	<?php } ?>

	<form class="gl-search" method="get" action="<?=G5_BBS_URL?>/board.php" role="search">
		<input type="hidden" name="bo_table" value="<?=gl_e($bo_table)?>">
		<input type="hidden" name="sfl" value="wr_subject||wr_content">
		<label class="sound_only" for="gl-stx">검색어</label>
		<input type="text" name="stx" id="gl-stx" class="frm_input" value="<?=gl_e(stripslashes((string)$stx))?>" placeholder="제목 · 내용 검색">
		<button type="submit" class="ui-btn small">검색</button>
		<?php if ($gl_search) { ?><a href="<?=gl_url($bo_table)?>" class="ui-btn small etc">전체</a><?php } ?>
	</form>

	<div class="gl-modal" id="gl-modal" hidden>
		<div class="gl-modal-backdrop" data-gl-close></div>
		<div class="gl-modal-panel theme-box" role="dialog" aria-modal="true" aria-label="글 보기" tabindex="-1">
			<button type="button" class="ui-btn small gl-modal-close" data-gl-close aria-label="닫기"><i class="material-icons" aria-hidden="true">close</i></button>
			<div class="gl-modal-body" aria-live="polite"></div>
		</div>
	</div>
</div>
<?=gl_js($board_skin_url)?>
<!-- } 갤러리 -->
