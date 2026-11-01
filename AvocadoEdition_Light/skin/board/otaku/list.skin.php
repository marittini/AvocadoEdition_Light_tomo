<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/otaku.lib.php';
otaku_assets($board_skin_url);

// 원본 글(페어)만 카드로. AU(답글)는 목록에 나오지 않는다.
$otaku_where = "wr_is_comment = 0 AND wr_reply = ''";
// 분류(자캐 · 드림 등) 모아 보기: 탭마다 개수
$otaku_cats = otaku_categories($board);
$otaku_cat = in_array((string)$sca, $otaku_cats, true) ? (string)$sca : '';
$otaku_cat_count = [];
$res = sql_query("SELECT ca_name, COUNT(*) AS n FROM `{$write_table}` WHERE {$otaku_where} GROUP BY ca_name");
while ($row = sql_fetch_array($res)) $otaku_cat_count[(string)$row['ca_name']] = (int)$row['n'];
$otaku_all = array_sum($otaku_cat_count);
if ($otaku_cat !== '') $otaku_where .= " AND ca_name = '".sql_real_escape_string($otaku_cat)."'";
$otaku_total = (int)(sql_fetch("SELECT COUNT(*) AS n FROM `{$write_table}` WHERE {$otaku_where}")['n'] ?? 0);
$otaku_rows = get_board_page_rows($board);
$otaku_pages = max(1, (int)ceil($otaku_total / $otaku_rows));
$otaku_page = min($otaku_pages, max(1, (int)($_GET['page'] ?? 1)));
$res = sql_query("SELECT wr_id, wr_num, wr_subject, wr_option, mb_id, ca_name
    FROM `{$write_table}` WHERE {$otaku_where} ORDER BY wr_num, wr_reply LIMIT ".(($otaku_page - 1) * $otaku_rows).", {$otaku_rows}");
$otaku_cards = [];
while ($row = sql_fetch_array($res)) {
	$open = otaku_can_read($row);
	$data = $open ? otaku_load($bo_table, (int)$row['wr_id']) : otaku_blank();
	$img = $open ? otaku_photos($bo_table, (int)$row['wr_id']) : [];
	$cover = $img[OTAKU_SLOTS['cover']] ?? null;
	$card = ['row' => $row, 'open' => $open, 'data' => $data,
		'cover' => $cover ? otaku_thumb($bo_table, $cover, 720, 405) : '', 'faces' => [], 'minis' => []];
	if (!$cover) foreach (OTAKU_SLOTS['photo'] as $slot) if (isset($img[$slot])) $card['faces'][] = otaku_thumb($bo_table, $img[$slot], 360, 405);
	foreach (OTAKU_SLOTS['mini'] as $i => $slot) if (isset($img[$slot])) $card['minis'][] = ['url' => $img[$slot]['url'], 'person' => $data['people'][$i]];
	$otaku_cards[] = $card;
}
$otaku_width = (int)($board['bo_table_width'] ?? 0);
?>

<!-- 페어 목록 { -->
<div class="otaku otaku-list"<?php if ($otaku_width > 0) { ?> style="max-width:<?=$otaku_width?><?=$otaku_width > 100 ? 'px' : '%'?>;"<?php } ?>>
	<?php if ($board['bo_content_head']) { ?><div class="board-notice-box"><?=stripslashes($board['bo_content_head'])?></div><?php } ?>

	<nav class="otaku-cats" aria-label="분류">
		<a href="<?=otaku_url($bo_table)?>" class="<?=$otaku_cat === '' ? 'is-on' : ''?>"<?=$otaku_cat === '' ? ' aria-current="page"' : ''?>>전체 <small><?=$otaku_all?></small></a>
		<?php foreach ($otaku_cats as $c) { $on = $otaku_cat === $c; ?>
		<a href="<?=otaku_url($bo_table, ['sca' => $c])?>" class="<?=$on ? 'is-on' : ''?>"<?=$on ? ' aria-current="page"' : ''?>><?=otaku_e($c)?> <small><?=$otaku_cat_count[$c] ?? 0?></small></a>
		<?php } ?>
	</nav>

	<?php if ($otaku_cards) { ?>
	<ul class="otaku-cards">
		<?php foreach ($otaku_cards as $c) { $row = $c['row']; $title = $c['open'] ? otaku_title($c['data']) : '비밀글'; $title = $title !== '' ? $title : get_text($row['wr_subject']); ?>
		<li>
			<a href="<?=otaku_url($bo_table, ['wr_id' => (int)$row['wr_id'], 'sca' => $otaku_cat, 'page' => $otaku_page > 1 ? $otaku_page : 0])?>" class="otaku-card">
				<span class="otaku-card-art<?=$c['faces'] ? ' is-faces' : ''?>">
					<span class="otaku-card-pics"><?php if ($c['cover']) { ?><img src="<?=$c['cover']?>" alt="" loading="lazy"><?php } else foreach ($c['faces'] as $f) { ?><img src="<?=$f?>" alt="" loading="lazy"><?php } ?></span>
					<?php if ($c['minis']) { ?>
					<span class="otaku-card-minis"><?php foreach ($c['minis'] as $m) { $quote = trim((string)$m['person']['quote']); $style = otaku_color_style($m['person']); ?><span class="otaku-mini<?=$quote !== '' ? ' has-quote' : ''?>"<?=$style !== '' ? ' style="'.$style.'"' : ''?>><img src="<?=$m['url']?>" alt="<?=otaku_e($m['person']['name'])?>" loading="lazy"><?php if ($quote !== '') { ?><span class="otaku-mini-quote" role="tooltip"><?=otaku_e($quote)?></span><?php } ?></span><?php } ?></span>
					<?php } ?>
				</span>
				<span class="otaku-card-text">
					<b><?php if (!$c['open'] || strpos($row['wr_option'], 'secret') !== false) { ?><i class="material-icons" aria-hidden="true">lock</i><?php } ?><?=otaku_e($title)?></b>
					<small><?=$c['open'] && $c['data']['title'] !== '' ? otaku_names_html($c['data']['people']) : ''?></small>
				</span>
			</a>
		</li>
		<?php } ?>
	</ul>
	<?php } else { ?>
	<div class="otaku-paper theme-box otaku-empty">
		<p><?=$otaku_cat !== '' ? otaku_e($otaku_cat).' 분류에 ' : '아직 '?>등록한 페어가 없습니다.</p>
		<?php if ($write_href) { ?><a href="<?=$write_href?><?=$otaku_cat !== '' ? '&amp;sca='.urlencode($otaku_cat) : ''?>" class="ui-btn point">새 페어</a><?php } ?>
	</div>
	<?php } ?>

	<?php if ($otaku_pages > 1) echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $otaku_page, $otaku_pages, otaku_url($bo_table, ['sca' => $otaku_cat]).'&amp;page='); ?>

	<?php if (($write_href && $otaku_cards) || $admin_href) { ?>
	<div class="otaku-actions">
		<?php if ($admin_href) { ?><a href="<?=$admin_href?>" class="ui-btn etc" target="_blank">관리자</a><?php } ?>
		<?php if ($write_href && $otaku_cards) { ?><a href="<?=$write_href?><?=$otaku_cat !== '' ? '&amp;sca='.urlencode($otaku_cat) : ''?>" class="ui-btn point">새 페어</a><?php } ?>
	</div>
	<?php } ?>
</div>
<!-- } 페어 목록 -->
