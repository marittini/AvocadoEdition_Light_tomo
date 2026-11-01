<?php
// 배너 관리(관리자): 순서 · 이름 · 그림 주소 · 주소 · 툴팁 · 분류 · 내 배너를 한 화면에서 고치고, 표시 방식(한 줄 개수 · 홈 화면)을 정한다.
include_once __DIR__.'/_common.php';
include_once __DIR__.'/link.lib.php';
if (empty($board['bo_table']) || ($board['bo_skin'] ?? '') !== 'link') alert('링크 게시판이 아닙니다.', G5_URL);
if (!$is_admin) alert('관리자만 배너를 한꺼번에 관리할 수 있습니다.', G5_BBS_URL.'/board.php?bo_table='.urlencode($bo_table));

$lk_settings = lk_settings($board);
$lk_banners = lk_banners($bo_table, $write_table);
$lk_cats = !empty($board['bo_use_category']) ? array_values(array_filter(array_map('trim', explode('|', (string)$board['bo_category_list'])), 'strlen')) : [];
$board_skin_url = G5_SKIN_URL.'/board/link';
$g5['title'] = $board['bo_subject'].' · 배너 관리';
include_once G5_PATH.'/head.sub.php';
echo lk_assets($board_skin_url);
?>
<div class="lk lk-manage theme-box">
	<header class="lk-manage-head">
		<h1 class="lk-title"><?=lk_e($board['bo_subject'])?> · 배너 관리</h1>
		<?php if (!empty($_GET['saved'])) { ?><p class="lk-saved" role="status">저장했습니다.</p><?php } ?>
		<p class="lk-help">끌어서(또는 ↑ ↓) 순서를 바꾸고, 칸을 고친 뒤 저장하세요. 그림 파일을 바꾸거나 툴팁 색을 고르는 것은 각 배너의 수정 화면에서 합니다.</p>
	</header>
	<form method="post" action="<?=$board_skin_url?>/manage_update.php" id="lk-manage-form">
		<input type="hidden" name="bo_table" value="<?=lk_e($bo_table)?>">
		<input type="hidden" name="lk_token" value="<?=lk_e(lk_token())?>">

		<fieldset class="lk-settings">
			<legend class="sound_only">표시 방식</legend>
			<span class="lk-settings-title" aria-hidden="true">표시 방식</span>
			<label>PC 한 줄 <select name="lk_pc"><option value="0"<?=$lk_settings['pc'] ? '' : ' selected'?>>원래 크기대로</option><?php for ($n = 1; $n <= 8; $n++) { ?><option value="<?=$n?>"<?=$lk_settings['pc'] === $n ? ' selected' : ''?>><?=$n?>개</option><?php } ?></select></label>
			<label>휴대폰 한 줄 <select name="lk_mobile"><option value="0"<?=$lk_settings['mobile'] ? '' : ' selected'?>>원래 크기대로</option><?php for ($n = 1; $n <= 4; $n++) { ?><option value="<?=$n?>"<?=$lk_settings['mobile'] === $n ? ' selected' : ''?>><?=$n?>개</option><?php } ?></select></label>
			<label class="lk-check"><input type="checkbox" name="lk_home" value="1"<?=$lk_settings['home'] ? ' checked' : ''?>> 홈 화면에도 보이기</label>
			<small class="lk-help">홈 화면 내용(관리자 &gt; 메인 화면)에 <code>[배너]</code>를 적으면 그 자리에, 없으면 내용 아래에 나옵니다.</small>
		</fieldset>

		<?php if ($lk_banners) { ?>
		<ol class="lk-rows" id="lk-rows">
			<?php foreach ($lk_banners as $b) { $id = $b['id']; ?>
			<li class="lk-row" draggable="true" data-id="<?=$id?>">
				<input type="hidden" name="ids[]" value="<?=$id?>">
				<span class="lk-handle" title="끌어서 순서 바꾸기" aria-hidden="true"><i class="material-icons">drag_indicator</i></span>
				<span class="lk-row-img"><?php if ($b['img'] !== '') { ?><img src="<?=lk_e($b['img'])?>" alt=""><?php } else { ?><i class="material-icons" aria-hidden="true">image</i><?php } ?></span>
				<div class="lk-row-fields">
					<label><span>이름</span><input type="text" name="name[<?=$id?>]" class="frm_input" value="<?=lk_e($b['name'])?>" maxlength="60" required></label>
					<label><span>주소</span><input type="text" inputmode="url" name="href[<?=$id?>]" class="frm_input" value="<?=lk_e($b['href'])?>" maxlength="1000" placeholder="https://"></label>
					<label><span>그림 주소</span><input type="text" inputmode="url" name="img[<?=$id?>]" class="frm_input" value="<?=lk_e($b['uploaded'] ? '' : $b['img'])?>" maxlength="1000" placeholder="<?=$b['uploaded'] ? '올린 그림 사용 중' : 'https://'?>"></label>
					<label><span>툴팁</span><input type="text" name="tip[<?=$id?>]" class="frm_input" value="<?=lk_e($b['tip'])?>" maxlength="200"></label>
					<?php if ($lk_cats) { ?><label><span>분류</span><select name="cat[<?=$id?>]"><option value="">분류 없음</option><?php foreach ($lk_cats as $c) { ?><option value="<?=lk_e($c)?>"<?=$b['category'] === $c ? ' selected' : ''?>><?=lk_e($c)?></option><?php } ?></select></label><?php } ?>
					<label class="lk-check"><input type="checkbox" name="mine[<?=$id?>]" value="1"<?=$b['mine'] ? ' checked' : ''?>> 내 배너</label>
				</div>
				<span class="lk-row-move">
					<button type="button" class="ui-btn small" data-lk-move="-1" aria-label="위로"><i class="material-icons" aria-hidden="true">arrow_upward</i></button>
					<button type="button" class="ui-btn small" data-lk-move="1" aria-label="아래로"><i class="material-icons" aria-hidden="true">arrow_downward</i></button>
				</span>
			</li>
			<?php } ?>
		</ol>
		<?php } else { ?><p class="lk-empty">아직 배너가 없습니다. 목록에서 '배너 추가'로 올려 주세요.</p><?php } ?>

		<div class="lk-actions">
			<a href="<?=G5_BBS_URL?>/board.php?bo_table=<?=urlencode($bo_table)?>" class="ui-btn">목록으로</a>
			<button type="submit" class="ui-btn point">저장</button>
		</div>
	</form>
</div>
<?php include_once G5_PATH.'/tail.sub.php'; ?>
