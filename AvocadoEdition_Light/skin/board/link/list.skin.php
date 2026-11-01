<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/link.lib.php';

$lk_settings = lk_settings($board);
$lk_all = lk_banners($bo_table, $write_table);
$lk_mine = array_values(array_filter($lk_all, static fn($b) => $b['mine']));
$lk_rest = array_values(array_filter($lk_all, static fn($b) => !$b['mine']));
$lk_cats = $is_category ? array_values(array_filter(array_map('trim', explode('|', (string)$board['bo_category_list'])), 'strlen')) : [];
$lk_width = (int)($board['bo_table_width'] ?? 0);

/** 수정 · 삭제 링크(그누보드 기본 규칙: 글쓴이 · 관리자는 바로, 비회원 글은 비밀번호 확인). */
$lk_links = static function (array $b) use ($bo_table, $member, $is_admin): array {
    if ($is_admin || (!empty($member['mb_id']) && $member['mb_id'] === $b['mb_id'])) {
        $token = (string)get_session('ss_delete_token');
        if ($token === '') { $token = uniqid((string)time()); set_session('ss_delete_token', $token); }
        return ['edit' => G5_BBS_URL.'/write.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$b['id'],
            'delete' => G5_BBS_URL.'/delete.php?bo_table='.$bo_table.'&amp;wr_id='.$b['id'].'&amp;token='.$token];
    }
    if ($b['mb_id'] === '') return ['edit' => G5_BBS_URL.'/password.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$b['id'],
        'delete' => G5_BBS_URL.'/password.php?w=d&amp;bo_table='.$bo_table.'&amp;wr_id='.$b['id']];
    return [];
};
echo lk_assets($board_skin_url);
?>

<!-- 링크 · 배너 목록 { -->
<div class="lk lk-list theme-box"<?php if ($lk_width > 0) { ?> style="max-width:<?=$lk_width?><?=$lk_width > 100 ? 'px' : '%'?>;"<?php } ?>>
	<?php if ($board['bo_content_head']) { ?><div class="board-notice-box"><?=stripslashes($board['bo_content_head'])?></div><?php } ?>

	<?php if ($lk_mine) { ?>
	<section class="lk-mine" aria-label="내 배너">
		<?php foreach ($lk_mine as $b) {
			$site = $b['href'] !== '' ? $b['href'] : G5_URL;
			$img = $b['uploaded'] ? str_replace('&amp;', '&', lk_stable_img($bo_table, $b['id'], $board_skin_url)) : $b['img'];
			$code = '<a href="'.$site.'" target="_blank"><img src="'.$img.'" alt="'.lk_e($b['name']).'"></a>';
		?>
		<div class="lk-mine-item">
			<?=lk_grid([$b], ['pc' => 0, 'mobile' => 0], $lk_links)?>
			<div class="lk-code">
				<label class="sound_only" for="lk-code-<?=$b['id']?>">배너 코드</label>
				<input type="text" id="lk-code-<?=$b['id']?>" class="frm_input" value="<?=lk_e($code)?>" readonly data-tomo-allow-copy>
				<button type="button" class="ui-btn small point" data-lk-copy="lk-code-<?=$b['id']?>">배너 코드 복사</button>
			</div>
		</div>
		<?php } ?>
		<p class="lk-help">다른 사이트의 링크 게시판에 이 코드를 붙이면 배너가 걸립니다.<?php if (array_filter($lk_mine, static fn($b) => $b['uploaded'])) { ?> 올린 배너 그림을 바꿔도 코드 속 그림 주소는 그대로입니다.<?php } ?></p>
	</section>
	<?php } ?>

	<?php if ($lk_cats) {
		foreach ($lk_cats as $cat) {
			$in = array_values(array_filter($lk_rest, static fn($b) => $b['category'] === $cat));
			if (!$in) continue; ?>
	<section class="lk-group">
		<h2 class="lk-group-title"><?=lk_e($cat)?></h2>
		<?=lk_grid($in, $lk_settings, $lk_links)?>
	</section>
	<?php }
		$lk_rest = array_values(array_filter($lk_rest, static fn($b) => !in_array($b['category'], $lk_cats, true)));
		if ($lk_rest) { ?><section class="lk-group"><h2 class="lk-group-title">기타</h2><?=lk_grid($lk_rest, $lk_settings, $lk_links)?></section><?php }
	} else { ?>
	<?=lk_grid($lk_rest, $lk_settings, $lk_links)?>
	<?php } ?>

	<?php if (!$lk_all) { ?><p class="lk-empty">아직 걸린 배너가 없습니다.</p><?php } ?>

	<?php if ($board['bo_content_tail']) { ?><div class="board-notice-box"><?=stripslashes($board['bo_content_tail'])?></div><?php } ?>

	<?php if ($write_href || $is_admin) { ?>
	<div class="lk-actions">
		<?php if ($is_admin) { ?>
		<a href="<?=$board_skin_url?>/manage.php?bo_table=<?=urlencode($bo_table)?>" class="ui-btn"><i class="material-icons" aria-hidden="true">tune</i> 배너 관리</a>
		<?php if ($admin_href) { ?><a href="<?=$admin_href?>" class="ui-btn etc" target="_blank">게시판 설정</a><?php } ?>
		<?php } ?>
		<?php if ($write_href) { ?><a href="<?=$write_href?>" class="ui-btn point">배너 추가</a><?php } ?>
	</div>
	<?php } ?>
</div>
<!-- } 링크 · 배너 목록 -->
