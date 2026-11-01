<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/guestbook.lib.php';
include_once G5_CAPTCHA_PATH.'/captcha.lib.php';
gb_assets($board_skin_url);

$gb_can_comment = $member['mb_level'] >= $board['bo_comment_level'];
$gb_width = (int)($board['bo_table_width'] ?? 0);

// 삭제 토큰(그누보드 delete.php가 확인)
$gb_delete_token = '';
$gb_del_href = static function (array $row) use ($bo_table, $member, $is_admin, $page, &$gb_delete_token): string {
	if ($is_admin || (!empty($member['mb_id']) && $member['mb_id'] === $row['mb_id'])) {
		if ($gb_delete_token === '') set_session('ss_delete_token', $gb_delete_token = uniqid(time()));
		return G5_BBS_URL.'/delete.php?bo_table='.$bo_table.'&amp;wr_id='.(int)$row['wr_id'].'&amp;token='.$gb_delete_token.'&amp;page='.(int)$page;
	}
	return $row['mb_id'] ? '' : G5_BBS_URL.'/password.php?w=d&amp;bo_table='.$bo_table.'&amp;wr_id='.(int)$row['wr_id'].'&amp;page='.(int)$page;
};
$gb_is_mine = static fn(array $row): bool => $is_admin || (!empty($member['mb_id']) && $member['mb_id'] === $row['mb_id']);

// 이 쪽 글들의 댓글을 한 번에
$gb_comments = [];
$gb_ids = array_map(static fn($r) => (int)$r['wr_id'], $list);
if ($gb_ids) {
	$res = sql_query("SELECT wr_id, wr_parent, mb_id, wr_name, wr_content, wr_datetime FROM `{$write_table}`
		WHERE wr_is_comment = 1 AND wr_parent IN (".implode(',', $gb_ids).") ORDER BY wr_comment, wr_comment_reply");
	while ($row = sql_fetch_array($res)) $gb_comments[(int)$row['wr_parent']][] = $row;
}
$gb_date = static fn(string $dt): string => date('y.m.d H:i', strtotime($dt));
?>

<!-- 방명록 { -->
<div class="gb"<?php if ($gb_width > 0) { ?> style="max-width:<?=$gb_width?><?=$gb_width > 100 ? 'px' : '%'?>;"<?php } ?>>
	<?php if ($board['bo_content_head']) { ?><div class="board-notice-box"><?=stripslashes($board['bo_content_head'])?></div><?php } ?>

	<?php if ($write_href) { ?>
	<section class="gb-paper gb-compose theme-box" aria-label="방명록 쓰기">
		<?=gb_form(['kind' => 'post', 'w' => '', 'id' => 'gb-write'])?>
	</section>
	<?php } ?>

	<section class="gb-paper gb-entries theme-box" aria-label="방명록">
		<?php if ($list) { ?>
		<ol class="gb-list">
		<?php foreach ($list as $row) {
			$id = (int)$row['wr_id'];
			$open = gb_can_read($row);
			$secret = strpos($row['wr_option'], 'secret') !== false;
			$mine = $gb_is_mine($row);
			$del = $gb_del_href($row);
			$comments = $open ? ($gb_comments[$id] ?? []) : [];
		?>
			<li class="gb-entry<?=$row['is_notice'] ? ' is-notice' : ''?>" id="gb-<?=$id?>">
				<header class="gb-head">
					<span class="gb-no"><?=$row['is_notice'] ? '공지' : 'No.'.(int)$row['num']?></span>
					<b class="gb-name"><?=gb_e(get_text($row['wr_name']))?></b>
					<?php if ($secret) { ?><i class="material-icons gb-lock" aria-hidden="true">lock</i><span class="sound_only">비밀글</span><?php } ?>
					<time class="gb-date" datetime="<?=gb_e(date('c', strtotime($row['wr_datetime'])))?>"><?=$gb_date($row['wr_datetime'])?></time>
				</header>

				<?php if ($open) { ?>
				<div class="gb-body" data-gb-view><?=gb_format((string)$row['wr_content'], $bo_table)?></div>
				<?php if ($mine) { ?>
				<div class="gb-editor" data-gb-editor hidden>
					<?=gb_form(['kind' => 'post', 'w' => 'u', 'wr_id' => $id, 'content' => $row['wr_content'], 'secret' => $secret, 'notice' => $row['is_notice'], 'cancel' => true, 'id' => 'gb-edit-'.$id])?>
				</div>
				<?php } ?>
				<?php } else { ?>
				<p class="gb-body gb-locked">비밀글입니다.<?php if (!$row['mb_id']) { ?> <a href="<?=G5_BBS_URL?>/password.php?w=s&amp;bo_table=<?=$bo_table?>&amp;wr_id=<?=$id?>&amp;page=<?=(int)$page?>" class="ui-btn small">비밀번호 입력</a><?php } ?></p>
				<?php } ?>

				<footer class="gb-foot">
					<?php if ($open && ($comments || $gb_can_comment)) { ?>
					<button type="button" class="gb-toggle" data-gb-toggle aria-expanded="false" aria-controls="gb-<?=$id?>-c">
						<i class="material-icons" aria-hidden="true">chat_bubble_outline</i>
						<span>댓글<?php if ($comments) { ?> <b><?=count($comments)?></b><?php } ?></span>
					</button>
					<?php } else { ?><span></span><?php } ?>
					<?php if (($open && $mine) || $del) { ?>
					<span class="gb-tools">
						<?php if ($open && $mine) { ?><button type="button" class="ui-btn small etc" data-gb-edit>수정</button><?php }
						elseif (!$row['mb_id'] && $open) { ?><a href="<?=G5_BBS_URL?>/password.php?w=u&amp;bo_table=<?=$bo_table?>&amp;wr_id=<?=$id?>&amp;page=<?=(int)$page?>" class="ui-btn small etc">수정</a><?php } ?>
						<?php if ($del) { ?><a href="<?=$del?>" class="ui-btn small" data-gb-confirm="이 글을 삭제할까요?">삭제</a><?php } ?>
					</span>
					<?php } ?>
				</footer>

				<?php if ($open && ($comments || $gb_can_comment)) { ?>
				<div class="gb-comments" id="gb-<?=$id?>-c" hidden>
					<?php if ($comments) { ?>
					<ol class="gb-clist">
					<?php foreach ($comments as $c) {
						$cid = (int)$c['wr_id'];
						$cmine = $gb_is_mine($c);
						if ($cmine) set_session('ss_delete_comment_'.$cid.'_token', $ctoken = uniqid(time()));
						$cdel = $cmine ? G5_BBS_URL.'/delete_comment.php?bo_table='.$bo_table.'&amp;comment_id='.$cid.'&amp;token='.$ctoken.'&amp;page='.(int)$page
							: (!$c['mb_id'] ? G5_BBS_URL.'/password.php?w=x&amp;bo_table='.$bo_table.'&amp;comment_id='.$cid.'&amp;page='.(int)$page : '');
					?>
						<li class="gb-comment" id="c_<?=$cid?>">
							<div class="gb-chead">
								<b class="gb-name"><?=gb_e(get_text($c['wr_name']))?></b>
								<time class="gb-date"><?=$gb_date($c['wr_datetime'])?></time>
								<?php if ($cmine || $cdel) { ?>
								<span class="gb-tools">
									<?php if ($cmine) { ?><button type="button" class="ui-btn small etc" data-gb-edit>수정</button><?php } ?>
									<?php if ($cdel) { ?><a href="<?=$cdel?>" class="ui-btn small" data-gb-confirm="이 댓글을 삭제할까요?">삭제</a><?php } ?>
								</span>
								<?php } ?>
							</div>
							<div class="gb-body" data-gb-view><?=gb_format((string)$c['wr_content'], $bo_table)?></div>
							<?php if ($cmine) { ?>
							<div class="gb-editor" data-gb-editor hidden><?=gb_form(['kind' => 'comment', 'w' => 'cu', 'wr_id' => $id, 'comment_id' => $cid, 'content' => $c['wr_content'], 'cancel' => true, 'id' => 'gb-cedit-'.$cid])?></div>
							<?php } ?>
						</li>
					<?php } ?>
					</ol>
					<?php } ?>
					<?php if ($gb_can_comment) echo gb_form(['kind' => 'comment', 'w' => 'c', 'wr_id' => $id, 'id' => 'gb-cform-'.$id]); ?>
				</div>
				<?php } ?>
			</li>
		<?php } ?>
		</ol>
		<?php } else { ?>
		<p class="gb-empty"><?=$stx !== '' ? '찾는 글이 없습니다.' : '아직 남겨진 글이 없습니다.'?></p>
		<?php } ?>
	</section>

	<?=$write_pages?>

	<div class="gb-bottom">
		<form class="gb-search" method="get" action="<?=G5_BBS_URL?>/board.php" role="search">
			<input type="hidden" name="bo_table" value="<?=gb_e($bo_table)?>">
			<input type="hidden" name="sfl" value="wr_content">
			<label class="sound_only" for="gb-stx">검색어</label>
			<input type="text" name="stx" id="gb-stx" class="frm_input" value="<?=gb_e(stripslashes((string)$stx))?>" placeholder="검색">
			<button type="submit" class="ui-btn small">검색</button>
			<?php if ($stx !== '') { ?><a href="<?=gb_url($bo_table)?>" class="ui-btn small etc">전체</a><?php } ?>
		</form>
		<?php if ($admin_href) { ?><a href="<?=$admin_href?>" class="ui-btn small etc" target="_blank">관리자</a><?php } ?>
	</div>

	<?php // 자동등록방지(비회원)는 한 벌만 두고, 쓰고 있는 폼 아래로 옮겨 쓴다.
	if ($is_guest && ($write_href || $gb_can_comment)) { ?><div class="gb-captcha" id="gb-captcha"><?=captcha_html()?></div><?php } ?>
</div>
<?=gb_js($board_skin_url)?>
<!-- } 방명록 -->
