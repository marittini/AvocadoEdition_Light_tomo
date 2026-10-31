<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/otaku.lib.php';
otaku_assets($board_skin_url);

// AU(답글) 주소로 들어오면 원본 글에서 그 AU를 펼쳐 보여 준다.
if (($view['wr_reply'] ?? '') !== '') {
	$otaku_root = sql_fetch("SELECT wr_id FROM `{$write_table}` WHERE wr_num='".(int)$view['wr_num']."' AND wr_reply='' AND wr_is_comment=0");
	if (!empty($otaku_root['wr_id'])) goto_url(str_replace('&amp;', '&', otaku_url($bo_table, ['wr_id' => (int)$otaku_root['wr_id'], 'au' => (int)$view['wr_id'], 'page' => (int)$page])));
}

$otaku_base_photos = otaku_photos($bo_table, (int)$view['wr_id']);
$otaku_delete_token = '';

/** 수정·삭제 링크(view.php와 같은 규칙). */
$otaku_links = static function (array $post) use ($bo_table, $member, $is_admin, $page, &$otaku_delete_token): array {
	$id = (int)$post['wr_id'];
	if (($member['mb_id'] && $member['mb_id'] === $post['mb_id']) || $is_admin) {
		if ($otaku_delete_token === '') {
			$otaku_delete_token = (string)get_session('ss_delete_token');
			if ($otaku_delete_token === '') set_session('ss_delete_token', $otaku_delete_token = uniqid(time()));
		}
		return [G5_BBS_URL.'/write.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$id.'&amp;page='.(int)$page,
			G5_BBS_URL.'/delete.php?bo_table='.$bo_table.'&amp;wr_id='.$id.'&amp;token='.$otaku_delete_token.'&amp;page='.(int)$page];
	}
	if (!$post['mb_id']) return [G5_BBS_URL.'/password.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$id.'&amp;page='.(int)$page,
		G5_BBS_URL.'/password.php?w=d&amp;bo_table='.$bo_table.'&amp;wr_id='.$id.'&amp;page='.(int)$page];
	return ['', ''];
};

// 판: 원본 + 읽을 수 있는 AU
$otaku_versions = [];
$otaku_versions[0] = ['id' => 0, 'post' => $view, 'label' => '원본', 'secret' => false,
	'data' => otaku_load($bo_table, (int)$view['wr_id']), 'photos' => $otaku_base_photos,
	'links' => [$update_href, $delete_href]];
$otaku_au_count = 0;
$res = sql_query("SELECT * FROM `{$write_table}` WHERE wr_num='".(int)$view['wr_num']."' AND wr_reply<>'' AND wr_is_comment=0 ORDER BY wr_reply");
while ($row = sql_fetch_array($res)) {
	$otaku_au_count++;
	if (!otaku_can_read($row)) continue;
	$id = (int)$row['wr_id'];
	$otaku_versions[$id] = ['id' => $id, 'post' => $row, 'label' => get_text($row['wr_subject']) ?: 'AU',
		'secret' => strpos($row['wr_option'], 'secret') !== false,
		'data' => otaku_load($bo_table, $id),
		'photos' => otaku_photos($bo_table, $id) + $otaku_base_photos, // AU에 사진이 없으면 원본 사진
		'links' => $otaku_links($row)];
}
$otaku_current = (int)($_GET['au'] ?? 0);
if (!isset($otaku_versions[$otaku_current])) $otaku_current = 0;
$otaku_can_au = $reply_href && $otaku_au_count < 26;

// 관계에 이어진 글의 사진(첨부 칸 0)
$otaku_linked = [];
foreach ($otaku_versions as $v) foreach ($v['data']['people'] as $p) foreach ($p['rels'] as $r) {
	if ($r['to'] && !isset($otaku_linked[$r['to']])) $otaku_linked[$r['to']] = null;
}
if ($otaku_linked) {
	$otaku_titles = otaku_entries($write_table);
	foreach (array_keys($otaku_linked) as $to) {
		if (!isset($otaku_titles[$to])) { unset($otaku_linked[$to]); continue; }
		$ph = otaku_photos($bo_table, $to);
		$ph = $ph[OTAKU_SLOTS['photo'][0]] ?? $ph[OTAKU_SLOTS['cover']] ?? null;
		$otaku_linked[$to] = ['title' => $otaku_titles[$to], 'photo' => $ph ? otaku_thumb($bo_table, $ph, 96, 96) : ''];
	}
}

$otaku_names = static fn(array $data): string => implode(' · ', array_map(static fn($p) => otaku_e($p['name']), $data['people']));
$otaku_photo_of = static fn(array $v, int $i) => $v['photos'][OTAKU_SLOTS['photo'][$i]] ?? null;
$otaku_who = static function (string $who, array $data): string {
	if ($who === 'all') return implode(' · ', array_map(static fn($p) => otaku_e($p['name']), $data['people']));
	return $who !== '' && isset($data['people'][(int)$who]) ? otaku_e($data['people'][(int)$who]['name']) : '';
};
$otaku_back = otaku_url($bo_table, ['page' => (int)$page]);
$otaku_width = (int)($board['bo_table_width'] ?? 0);
?>

<!-- 프로필 읽기 { -->
<div class="otaku otaku-view"<?php if ($otaku_width > 0) { ?> style="max-width:<?=$otaku_width?><?=$otaku_width > 100 ? 'px' : '%'?>;"<?php } ?>>
	<article id="bo_v" class="otaku-paper theme-box">
		<header class="otaku-top">
			<div>
				<?php foreach ($otaku_versions as $v) { $hidden = $v['id'] === $otaku_current ? '' : ' hidden'; ?>
				<div data-version-title="<?=$v['id']?>"<?=$hidden?>>
					<span class="otaku-label"><?=$v['data']['title'] !== '' ? $otaku_names($v['data']) : '페어'?><?=$v['id'] ? ' · '.otaku_e($v['label']) : ''?></span>
					<h1 class="otaku-title"><?=otaku_e(otaku_title($v['data']))?></h1>
				</div>
				<?php } ?>
			</div>
			<?php if (count($otaku_versions) > 1 || $otaku_can_au) { ?>
			<nav class="otaku-versions" aria-label="원본 / AU">
				<?php foreach ($otaku_versions as $v) { ?>
				<a href="<?=otaku_url($bo_table, ['wr_id' => (int)$view['wr_id'], 'au' => $v['id'], 'page' => (int)$page])?>" class="ui-btn small<?=$v['id'] === $otaku_current ? ' point' : ''?>" data-version-link="<?=$v['id']?>"<?=$v['id'] === $otaku_current ? ' aria-current="true"' : ''?>><?=otaku_e($v['label'])?><?php if ($v['secret']) { ?> <i class="material-icons" aria-hidden="true">lock</i><span class="sound_only">비밀</span><?php } ?></a>
				<?php } ?>
				<?php if ($otaku_can_au) { ?><a href="<?=$reply_href?>" class="ui-btn small etc">+ AU</a><?php } ?>
			</nav>
			<?php } ?>
		</header>

		<?php foreach ($otaku_versions as $v) {
			$data = $v['data'];
			$people = $data['people'];
			$has_rel = $data['bond'] !== '' || array_filter(array_column($people, 'rels'));
			$has_tl = !empty($data['timeline']);
			$key = 'v'.$v['id'];
		?>
		<div class="otaku-version" data-version="<?=$v['id']?>"<?=$v['id'] === $otaku_current ? '' : ' hidden'?>>
			<?php if ($has_rel || $has_tl) { ?>
			<div class="otaku-tabs" role="tablist" aria-label="보기">
				<button type="button" role="tab" id="<?=$key?>-tab-profile" aria-controls="<?=$key?>-profile" aria-selected="true" data-tab="profile">프로필</button>
				<?php if ($has_rel) { ?><button type="button" role="tab" id="<?=$key?>-tab-relation" aria-controls="<?=$key?>-relation" aria-selected="false" tabindex="-1" data-tab="relation">관계</button><?php } ?>
				<?php if ($has_tl) { ?><button type="button" role="tab" id="<?=$key?>-tab-timeline" aria-controls="<?=$key?>-timeline" aria-selected="false" tabindex="-1" data-tab="timeline">연표</button><?php } ?>
			</div>
			<?php } ?>

			<!-- 프로필 -->
			<section class="otaku-panel" id="<?=$key?>-profile" data-panel="profile" role="tabpanel" aria-labelledby="<?=$key?>-tab-profile">
				<div class="otaku-duo">
				<?php foreach ($people as $i => $p) { $photo = $otaku_photo_of($v, $i); ?>
					<section class="otaku-person">
						<div class="otaku-id">
							<?php if ($photo) { ?><a href="<?=$photo['url']?>" class="otaku-photo" target="_blank" rel="noopener"><img src="<?=otaku_thumb($bo_table, $photo, 480, 600)?>" alt="<?=otaku_e($p['name'])?>"></a><?php } ?>
							<div class="otaku-id-text">
								<h2 class="otaku-name"><?=otaku_e($p['name'])?></h2>
								<?php if ($p['line'] !== '') { ?><p class="otaku-line"><?=otaku_e($p['line'])?></p><?php } ?>
								<?php if ($p['quote'] !== '') { ?><p class="otaku-quote">“<?=otaku_e($p['quote'])?>”</p><?php } ?>
							</div>
						</div>
						<?php if ($p['facts']) { ?>
						<dl class="otaku-facts">
							<?php foreach ($p['facts'] as $f) { ?><dt><?=otaku_e($f['k'])?></dt><dd><?=otaku_text($f['v'])?></dd><?php } ?>
						</dl>
						<?php } ?>
						<?php foreach ($p['intro'] as $s) { ?>
						<?php if ($s['t'] !== '') { ?><h3 class="otaku-label"><?=otaku_e($s['t'])?></h3><?php } ?>
						<p class="otaku-text"><?=otaku_text($s['b'])?></p>
						<?php } ?>
					</section>
				<?php } ?>
				</div>
			</section>

			<?php if ($has_rel) { ?>
			<!-- 관계 -->
			<section class="otaku-panel" id="<?=$key?>-relation" data-panel="relation" role="tabpanel" aria-labelledby="<?=$key?>-tab-relation" hidden>
				<?php if ($data['bond'] !== '') { ?>
				<div class="otaku-bond"><span class="otaku-label">두 사람</span><p><?=otaku_text($data['bond'])?></p></div>
				<?php } ?>
				<div class="otaku-groups">
				<?php foreach ($people as $p) { if (!$p['rels']) continue; ?>
					<div>
						<h3 class="otaku-label"><?=otaku_e($p['name'])?>의 관계</h3>
						<ul class="otaku-rels">
						<?php foreach ($p['rels'] as $r) {
							$link = $r['to'] ? ($otaku_linked[$r['to']] ?? null) : null;
							$name = $r['name'] !== '' ? $r['name'] : ($link['title'] ?? '');
						?>
							<li>
								<span class="otaku-avatar" aria-hidden="true"><?php if (!empty($link['photo'])) { ?><img src="<?=$link['photo']?>" alt=""><?php } else { ?><?=otaku_e(mb_substr($name, 0, 1))?><?php } ?></span>
								<<?=$link ? 'a href="'.otaku_url($bo_table, ['wr_id' => $r['to']]).'"' : 'div'?> class="otaku-rel-text">
									<b><?=otaku_e($name)?></b><?php if ($r['kind'] !== '') { ?><span><?=otaku_e($r['kind'])?></span><?php } ?>
									<?php if ($r['text'] !== '') { ?><p><?=otaku_e($r['text'])?></p><?php } ?>
								</<?=$link ? 'a' : 'div'?>>
							</li>
						<?php } ?>
						</ul>
					</div>
				<?php } ?>
				</div>
			</section>
			<?php } ?>

			<?php if ($has_tl) { ?>
			<!-- 연표 -->
			<section class="otaku-panel" id="<?=$key?>-timeline" data-panel="timeline" role="tabpanel" aria-labelledby="<?=$key?>-tab-timeline" hidden>
				<ol class="otaku-tl">
				<?php foreach ($data['timeline'] as $t) { $who = $otaku_who((string)($t['who'] ?? ''), $data); ?>
					<li>
						<span class="otaku-tl-date"><?=otaku_e($t['d'])?></span>
						<div>
							<?php if ($t['t'] !== '' || $who !== '') { ?><b><?=otaku_e($t['t'])?></b><?php if ($who !== '') { ?><span class="otaku-who"><?=$who?></span><?php } ?><?php } ?>
							<?php if ($t['b'] !== '') { ?><p><?=otaku_text($t['b'])?></p><?php } ?>
						</div>
					</li>
				<?php } ?>
				</ol>
			</section>
			<?php } ?>
		</div>
		<?php } ?>
	</article>

	<div class="otaku-actions">
		<div>
			<?php if ($prev_href) { ?><a href="<?=$prev_href?>" class="ui-btn">이전글</a><?php } ?>
			<?php if ($next_href) { ?><a href="<?=$next_href?>" class="ui-btn">다음글</a><?php } ?>
		</div>
		<div>
			<?php foreach ($otaku_versions as $v) { [$edit, $del] = $v['links']; if (!$edit && !$del) continue; ?>
			<span class="otaku-version-tools" data-version-tools="<?=$v['id']?>"<?=$v['id'] === $otaku_current ? '' : ' hidden'?>>
				<?php if ($edit) { ?><a href="<?=$edit?>" class="ui-btn"><?=$v['id'] ? 'AU 수정' : '수정'?></a><?php } ?>
				<?php if ($del) { ?><a href="<?=$del?>" class="ui-btn etc" onclick="del(this.href); return false;"><?=$v['id'] ? 'AU 삭제' : '삭제'?></a><?php } ?>
			</span>
			<?php } ?>
			<a href="<?=$otaku_back?>" class="ui-btn">목록</a>
		</div>
	</div>
</div>
<?=otaku_js($board_skin_url, 'view.js')?>
<!-- } 프로필 읽기 -->
