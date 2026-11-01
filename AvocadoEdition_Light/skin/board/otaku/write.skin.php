<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/otaku.lib.php';
otaku_assets($board_skin_url);
if (empty($_SESSION['otaku_write_token'])) $_SESSION['otaku_write_token'] = bin2hex(random_bytes(32));

// 새 프로필 / 수정 / AU(원본의 답글)
$otaku_is_au = $w === 'r' || ($w === 'u' && ($write['wr_reply'] ?? '') !== '');
$otaku_parent = null;
if ($w === 'r') {
	if (($write['wr_reply'] ?? '') !== '') alert('AU는 원본 글에만 추가할 수 있습니다.');
	$otaku_parent = $write;
} elseif ($otaku_is_au) {
	$otaku_parent = sql_fetch("SELECT * FROM `{$write_table}` WHERE wr_num='".(int)$write['wr_num']."' AND wr_reply='' AND wr_is_comment=0");
}
// AU는 원본 내용을 복사해 시작한다. 달라진 부분만 고치면 된다.
$otaku = $w === 'r' ? otaku_load($bo_table, (int)$otaku_parent['wr_id']) : ($w === 'u' ? otaku_load($bo_table, (int)$wr_id) : otaku_blank());
$otaku_photos = $w === 'u' ? otaku_photos($bo_table, (int)$wr_id) : [];
$otaku_entries = otaku_entries($write_table);
unset($otaku_entries[(int)($otaku_parent['wr_id'] ?? $wr_id)]);

/** 이미지 한 칸 고르기: 미리보기 + 고르기 버튼 + (있으면) 지우기. 칸 번호를 그대로 bf_file[n]에 쓴다. */
$otaku_pick = static function (int $slot, string $label, string $class) use ($otaku_photos, $is_file): string {
	$photo = $otaku_photos[$slot] ?? null;
	$h = '<div class="otaku-pick '.$class.'"><div class="otaku-pick-preview" data-preview="'.$slot.'">'
		.($photo ? '<img src="'.$photo['url'].'" alt="">' : '<i class="material-icons" aria-hidden="true">add_photo_alternate</i>').'</div>';
	if ($is_file) {
		$h .= '<div class="otaku-pick-tools"><label class="ui-btn small"><input type="file" name="bf_file['.$slot.']" accept="image/*" class="sound_only" data-photo="'.$slot.'">'
			.$label.' '.($photo ? '바꾸기' : '고르기').'</label>';
		if ($photo) $h .= '<label class="otaku-check"><input type="checkbox" name="bf_file_del['.$slot.']" value="1"> 지우기</label>';
		$h .= '</div>';
	}
	return $h.'</div>';
};

/** 반복 행 하나. $spec: [필드 => [라벨, 종류, 예시]] */
$otaku_row = static function (string $base, array $spec, array $values = []) use ($otaku_entries): string {
	$h = '<li class="otaku-row" data-row><div class="otaku-row-fields">';
	foreach ($spec as $name => [$label, $type, $hint]) {
		// p[0][fact + _k → p[0][fact_k][] / tl + _d → tl_d[]
		$field = strpos($base, '[') !== false ? $base.'_'.$name.'][]' : $base.'_'.$name.'[]';
		$value = $values[$name] ?? '';
		$h .= '<label class="otaku-f otaku-f-'.$name.'"><span>'.$label.'</span>';
		if ($type === 'textarea') $h .= '<textarea name="'.$field.'" class="frm_input" rows="2" placeholder="'.otaku_e($hint).'" data-grow>'.otaku_e($value).'</textarea>';
		elseif ($type === 'entry') {
			$h .= '<select name="'.$field.'"><option value="0">직접 입력</option>';
			foreach ($otaku_entries as $id => $title) $h .= '<option value="'.$id.'"'.((int)$value === $id ? ' selected' : '').'>'.otaku_e($title).'</option>';
			$h .= '</select>';
		} elseif (is_array($type)) {
			$h .= '<select name="'.$field.'">';
			foreach ($type as $v => $t) $h .= '<option value="'.otaku_e($v).'"'.((string)$value === (string)$v ? ' selected' : '').'>'.otaku_e($t).'</option>';
			$h .= '</select>';
		} else $h .= '<input type="text" name="'.$field.'" class="frm_input" value="'.otaku_e($value).'" placeholder="'.otaku_e($hint).'">';
		$h .= '</label>';
	}
	return $h.'</div><div class="otaku-row-tools">'
		.'<button type="button" class="ui-btn small" data-move="-1" aria-label="위로"><i class="material-icons" aria-hidden="true">arrow_upward</i></button>'
		.'<button type="button" class="ui-btn small" data-move="1" aria-label="아래로"><i class="material-icons" aria-hidden="true">arrow_downward</i></button>'
		.'<button type="button" class="ui-btn small etc" data-remove aria-label="지우기"><i class="material-icons" aria-hidden="true">close</i></button>'
		.'</div></li>';
};
/** 행 목록 + 추가 버튼. $presets를 누르면 첫 칸이 채워진 행이 붙는다. */
$otaku_list = static function (string $base, array $spec, array $rows, array $presets, string $empty) use ($otaku_row): string {
	$h = '<div class="otaku-list" data-list><ol class="otaku-rows">';
	foreach ($rows as $r) $h .= $otaku_row($base, $spec, $r);
	$h .= '</ol><template>'.$otaku_row($base, $spec).'</template><div class="otaku-add">';
	foreach ($presets as $p) $h .= '<button type="button" class="ui-btn small" data-add="'.otaku_e($p).'">+ '.otaku_e($p).'</button>';
	return $h.'<button type="button" class="ui-btn small point" data-add>+ '.$empty.'</button></div></div>';
};
$spec_fact = ['k' => ['항목', 'text', '생일'], 'v' => ['내용', 'text', '3월 14일']];
$spec_intro = ['t' => ['소제목', 'text', '성격'], 'b' => ['내용', 'textarea', '조용하지만 잘 관찰한다.']];
$spec_rel = ['to' => ['이 게시판의 글', 'entry', ''], 'name' => ['이름', 'text', '목록에 없으면 이름'], 'kind' => ['관계', 'text', '친구'], 'text' => ['한 줄 설명', 'text', '예전 학교 친구. 편지를 주고받는다.']];
$who = ['all' => '두 사람'];
foreach ($otaku['people'] as $i => $p) $who[(string)$i] = $p['name'] !== '' ? $p['name'] : '인물 '.($i + 1);
$spec_tl = ['d' => ['언제', 'text', '초등학교 6학년'], 't' => ['일', 'text', '첫 만남'], 'b' => ['내용', 'textarea', '먼저 말을 건 건 이안이었다.']];
$spec_tl['who'] = ['누구의 일', $who, ''];
$otaku_width = (int)($board['bo_table_width'] ?? 0);
?>

<div class="otaku otaku-write"<?php if ($otaku_width > 0) { ?> style="max-width:<?=$otaku_width?><?=$otaku_width > 100 ? 'px' : '%'?>;"<?php } ?>>
<section id="bo_w">
<form name="fwrite" id="fwrite" action="<?=$action_url?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
	<input type="hidden" name="uid" value="<?=get_uniqid()?>">
	<input type="hidden" name="otaku_token" value="<?=otaku_e($_SESSION['otaku_write_token'])?>">
	<input type="hidden" name="w" value="<?=otaku_e($w)?>">
	<input type="hidden" name="bo_table" value="<?=otaku_e($bo_table)?>">
	<input type="hidden" name="wr_id" value="<?=(int)$wr_id?>">
	<input type="hidden" name="sca" value="<?=otaku_e($sca)?>">
	<input type="hidden" name="sfl" value="<?=otaku_e($sfl)?>">
	<input type="hidden" name="stx" value="<?=otaku_e($stx)?>">
	<input type="hidden" name="spt" value="<?=otaku_e($spt)?>">
	<input type="hidden" name="sst" value="<?=otaku_e($sst)?>">
	<input type="hidden" name="sod" value="<?=otaku_e($sod)?>">
	<input type="hidden" name="page" value="<?=(int)$page?>">
	<?php if ($is_secret == 2 && !$is_admin) { ?><input type="hidden" name="secret" value="secret"><?php } ?>

	<div class="otaku-paper theme-box">
		<header class="otaku-top">
			<div>
				<span class="otaku-label"><?=otaku_e($board['bo_subject'])?></span>
				<h1 class="otaku-title"><?php
					if ($w === 'r') echo otaku_e(get_text($otaku_parent['wr_subject'])).' · AU 추가';
					elseif ($otaku_is_au) echo otaku_e(get_text($otaku_parent['wr_subject'] ?? '')).' · AU 수정';
					else echo $w === 'u' ? '프로필 수정' : '새 프로필';
				?></h1>
			</div>
		</header>

		<?php if ($otaku_is_au) { ?>
		<div class="otaku-au-name">
			<label class="otaku-f"><span>AU 이름</span><input type="text" name="wr_subject" class="frm_input" value="<?=$w === 'u' ? otaku_e(get_text($write['wr_subject'])) : ''?>" required maxlength="60" placeholder="현대 AU"></label>
			<?php if ($w === 'r') { ?><p class="otaku-help">원본 내용이 복사되어 있습니다. 달라진 부분만 고치세요. 사진을 올리지 않으면 원본 사진을 씁니다.</p><?php } ?>
		</div>
		<?php } else { ?><input type="hidden" name="wr_subject" id="wr_subject" value="<?=otaku_e(get_text($write['wr_subject'] ?? ''))?>"><?php } ?>

		<div class="otaku-tabs" role="tablist" aria-label="쓰기">
			<button type="button" role="tab" aria-selected="true" data-tab="profile">프로필</button>
			<button type="button" role="tab" aria-selected="false" data-tab="relation">관계</button>
			<button type="button" role="tab" aria-selected="false" data-tab="timeline">연표</button>
		</div>

		<!-- 프로필 -->
		<section class="otaku-panel" data-panel="profile">
			<?php if (!$otaku_is_au) { ?>
			<fieldset class="otaku-cover-edit">
				<legend class="otaku-label">목록 썸네일</legend>
				<div class="otaku-cover-grid">
				<?=$otaku_pick(OTAKU_SLOTS['cover'], '썸네일', 'is-cover')?>
				<div class="otaku-cover-fields">
					<label class="otaku-f"><span>페어 이름 <small>선택</small></span><input type="text" name="otaku_title" class="frm_input" value="<?=otaku_e($otaku['title'])?>" maxlength="60" placeholder="비우면 두 사람 이름"></label>
					<p class="otaku-help">썸네일은 가로 그림(16:9 정도)이 잘 맞습니다. 미니 캐릭터는 각 인물 칸에서 올리면 썸네일 아래 선 위에 나란히 섭니다.</p>
				</div>
				</div>
			</fieldset>
			<?php } else { ?><input type="hidden" name="otaku_title" value="<?=otaku_e($otaku['title'])?>"><?php } ?>
			<div class="otaku-duo">
			<?php foreach ($otaku['people'] as $i => $p) { ?>
				<fieldset class="otaku-person-edit">
					<legend>인물 <?=$i + 1?></legend>
					<div class="otaku-id-edit">
						<div class="otaku-picks">
							<?=$otaku_pick(OTAKU_SLOTS['photo'][$i], '사진', 'is-photo')?>
							<?php if (!$otaku_is_au) echo $otaku_pick(OTAKU_SLOTS['mini'][$i], '미니', 'is-mini'); ?>
						</div>
						<div class="otaku-id-fields">
							<label class="otaku-f"><span>이름 <b class="sound_only">필수</b></span><input type="text" name="p[<?=$i?>][name]" class="frm_input" value="<?=otaku_e($p['name'])?>" required maxlength="40" placeholder="<?=$i ? '강이안' : '윤서하'?>" data-name="<?=$i?>"></label>
							<div class="otaku-id-pair">
								<label class="otaku-f"><span>이름 읽는 법 <small>루비 · 선택</small></span><input type="text" name="p[<?=$i?>][ruby]" class="frm_input" value="<?=otaku_e($p['ruby'])?>" maxlength="60" placeholder="<?=$i ? 'カン・イアン' : 'ユン・ソハ'?>"></label>
								<?php $otaku_color = otaku_color($p['color']); ?>
								<div class="otaku-f otaku-color" data-color-field>
									<span id="otaku-color-<?=$i?>">지향색 <small>말풍선 색</small></span>
									<span class="otaku-color-row">
										<input type="color" value="<?=$otaku_color !== '' ? $otaku_color : '#999999'?>" aria-labelledby="otaku-color-<?=$i?>" data-color-pick<?=$otaku_color === '' ? ' class="is-empty"' : ''?>>
										<input type="text" name="p[<?=$i?>][color]" class="frm_input" value="<?=otaku_e($otaku_color)?>" maxlength="7" placeholder="없음" pattern="#?[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?" aria-labelledby="otaku-color-<?=$i?>" data-color-text>
										<button type="button" class="ui-btn small etc" data-color-clear aria-label="지향색 지우기"><i class="material-icons" aria-hidden="true">close</i></button>
									</span>
								</div>
							</div>
							<label class="otaku-f"><span>한 줄 소개</span><input type="text" name="p[<?=$i?>][line]" class="frm_input" value="<?=otaku_e($p['line'])?>" maxlength="80" placeholder="17 · 도서부 2학년"></label>
							<label class="otaku-f"><span>한마디</span><input type="text" name="p[<?=$i?>][quote]" class="frm_input" value="<?=otaku_e($p['quote'])?>" maxlength="300" placeholder="말로 하면 사라질 것 같아서, 적어 두는 편이 좋아."></label>
							<?php if (!$otaku_is_au) { ?><p class="otaku-help">한마디는 목록에서 미니 캐릭터에 마우스를 올리면 지향색 말풍선으로 나옵니다.</p><?php } ?>
						</div>
					</div>
					<h3 class="otaku-label">정보</h3>
					<?=$otaku_list("p[{$i}][fact", $spec_fact, $p['facts'], ['생일', '키', '좋아하는 것', '싫어하는 것'], '항목')?>
					<h3 class="otaku-label">소개</h3>
					<?=$otaku_list("p[{$i}][intro", $spec_intro, $p['intro'], ['성격', '외모', '배경'], '소제목')?>
				</fieldset>
			<?php } ?>
			</div>
		</section>

		<!-- 관계 -->
		<section class="otaku-panel" data-panel="relation" hidden>
			<label class="otaku-f otaku-bond-edit"><span>두 사람</span><textarea name="otaku_bond" class="frm_input" rows="3" maxlength="2000" placeholder="두 사람이 어떤 사이인지 한 단락으로" data-grow><?=otaku_e($otaku['bond'])?></textarea></label>
			<div class="otaku-duo">
			<?php foreach ($otaku['people'] as $i => $p) { ?>
				<div class="otaku-rel-edit">
					<h3 class="otaku-label"><span data-name-echo="<?=$i?>"><?=otaku_e($p['name'] !== '' ? $p['name'] : '인물 '.($i + 1))?></span>의 관계</h3>
					<?=$otaku_list("p[{$i}][rel", $spec_rel, $p['rels'], [], '관계')?>
				</div>
			<?php } ?>
			</div>
			<p class="otaku-help">이 게시판에 프로필이 있는 사람은 목록에서 고르면 사진과 링크가 이어집니다. 없으면 이름만 적으세요.</p>
		</section>

		<!-- 연표 -->
		<section class="otaku-panel" data-panel="timeline" hidden>
			<?=$otaku_list('tl', $spec_tl, $otaku['timeline'], [], '연표')?>
		</section>

		<?php if ($is_name || $is_password || $is_guest) { ?>
		<div class="otaku-guest">
			<?php if ($is_name) { ?><label class="otaku-f"><span>작성자</span><input type="text" name="wr_name" value="<?=$name?>" required class="frm_input required"></label><?php } ?>
			<?php if ($is_password) { ?><label class="otaku-f"><span>비밀번호</span><input type="password" name="wr_password" <?=$password_required?> class="frm_input <?=$password_required?>"></label><?php } ?>
			<?php if ($is_guest) echo $captcha_html; ?>
		</div>
		<?php } ?>

		<div class="otaku-options">
			<?php if ($is_notice) { ?><label><input type="checkbox" name="notice" value="1" <?=$notice_checked?>> 공지</label><?php } ?>
			<?php if ($is_secret && ($is_admin || $is_secret == 1)) { ?><label><input type="checkbox" name="secret" value="secret" <?=$secret_checked?>> 비밀글</label><?php } ?>
		</div>
	</div>

	<div class="otaku-actions">
		<a href="<?=$otaku_parent ? otaku_url($bo_table, ['wr_id' => (int)$otaku_parent['wr_id']]) : ($w === 'u' ? otaku_url($bo_table, ['wr_id' => (int)$wr_id]) : otaku_url($bo_table))?>" class="ui-btn">취소</a>
		<button type="submit" id="btn_submit" accesskey="s" class="ui-btn point"><?=$w === 'u' ? '저장' : '작성'?></button>
	</div>
</form>
<?=otaku_js($board_skin_url, 'write.js')?>
<script>
function fwrite_submit(f) {
	// 원본 글의 제목은 페어 이름, 없으면 두 사람 이름(서버에서도 같은 규칙으로 다시 정함).
	var title = f.querySelector("#wr_subject"), pairName = f.querySelector("[name=otaku_title]");
	if (title) title.value = (pairName && pairName.value.trim()) || Array.prototype.map.call(f.querySelectorAll("[data-name]"), function (i) { return i.value.trim(); }).filter(Boolean).join(" · ");
	var empty = Array.prototype.find.call(f.querySelectorAll("[data-name]"), function (i) { return !i.value.trim(); });
	if (empty) { window.otakuShowTab && window.otakuShowTab("profile"); alert("이름을 입력해 주세요."); empty.focus(); return false; }
	<?=$captcha_js ?? ''?>
	document.getElementById("btn_submit").disabled = "disabled";
	return true;
}
</script>
</section>
</div>
