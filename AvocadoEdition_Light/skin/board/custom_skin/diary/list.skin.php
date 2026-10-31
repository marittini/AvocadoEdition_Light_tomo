<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/diary.lib.php';
diary_assets($board_skin_url);

// 연도·월 탐색: 글이 있는 연도만, 기본은 가장 최근 연도
$diary_cal = diary_calendar($write_table);
$diary_years = array_keys($diary_cal);
$diary_year = (int)($_GET['yr'] ?? 0);
if (!isset($diary_cal[$diary_year])) $diary_year = $diary_years[0] ?? (int)substr(G5_TIME_YMD, 0, 4);
$diary_month = (int)($_GET['mo'] ?? 0);
if ($diary_month < 1 || $diary_month > 12 || empty($diary_cal[$diary_year][$diary_month])) $diary_month = 0;
$diary_pos = array_search($diary_year, $diary_years, true);
$diary_newer = $diary_pos !== false && $diary_pos > 0 ? $diary_years[$diary_pos - 1] : 0;
$diary_older = $diary_pos !== false && isset($diary_years[$diary_pos + 1]) ? $diary_years[$diary_pos + 1] : 0;

// 선택한 기간의 글 (최신순, 페이지 나눔)
[$diary_from, $diary_to] = diary_range($diary_year, $diary_month);
$diary_where = "wr_is_comment = 0 AND wr_datetime >= '{$diary_from}' AND wr_datetime < '{$diary_to}'";
$diary_total = (int)(sql_fetch("SELECT COUNT(*) AS n FROM `{$write_table}` WHERE {$diary_where}")['n'] ?? 0);
$diary_rows = max(1, (int)(G5_IS_MOBILE ? $board['bo_mobile_page_rows'] : $board['bo_page_rows']) ?: 20);
$diary_pages = max(1, (int)ceil($diary_total / $diary_rows));
$diary_page = min($diary_pages, max(1, (int)($_GET['page'] ?? 1)));
$diary_res = sql_query("SELECT wr_id, wr_subject, wr_datetime, wr_option, wr_comment FROM `{$write_table}`
    WHERE {$diary_where} ORDER BY wr_datetime DESC, wr_id DESC LIMIT ".(($diary_page - 1) * $diary_rows).", {$diary_rows}");
$diary_list = [];
while ($row = sql_fetch_array($diary_res)) $diary_list[] = $row;

$diary_months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$diary_width = (int)($board['bo_table_width'] ?? 0);
$diary_paging_url = diary_url($bo_table, ['yr' => $diary_year, 'mo' => $diary_month]).'&amp;page=';
?>

<!-- 다이어리 목록 { -->
<div class="diary diary-list"<?php if ($diary_width > 0) { ?> style="max-width:<?=$diary_width?><?=$diary_width > 100 ? 'px' : '%'?>;"<?php } ?>>
	<?php if ($board['bo_content_head']) { ?><div class="board-notice-box"><?=stripslashes($board['bo_content_head'])?></div><?php } ?>

	<div class="diary-paper theme-box">
		<nav class="diary-year" aria-label="연도">
			<?php if ($diary_older) { ?><a href="<?=diary_url($bo_table, ['yr' => $diary_older])?>" class="diary-year-step" aria-label="<?=$diary_older?>년"><i class="material-icons" aria-hidden="true">chevron_left</i></a><?php } else { ?><span class="diary-year-step" aria-hidden="true"></span><?php } ?>
			<h2 class="diary-year-title"><a href="<?=diary_url($bo_table, ['yr' => $diary_year])?>"<?=$diary_month ? '' : ' aria-current="page"'?>><?=$diary_year?></a></h2>
			<?php if ($diary_newer) { ?><a href="<?=diary_url($bo_table, ['yr' => $diary_newer])?>" class="diary-year-step" aria-label="<?=$diary_newer?>년"><i class="material-icons" aria-hidden="true">chevron_right</i></a><?php } else { ?><span class="diary-year-step" aria-hidden="true"></span><?php } ?>
		</nav>

		<nav class="diary-months" aria-label="<?=$diary_year?>년 월">
			<ul>
				<?php for ($m = 1; $m <= 12; $m++) { $has = !empty($diary_cal[$diary_year][$m]); $on = $diary_month === $m; ?>
				<li><?php if ($has) { ?><a href="<?=diary_url($bo_table, ['yr' => $diary_year, 'mo' => $on ? 0 : $m])?>"<?=$on ? ' aria-current="page"' : ''?> title="<?=$diary_year?>년 <?=$m?>월 <?=$diary_cal[$diary_year][$m]?>편"><?=$diary_months[$m]?></a><?php } else { ?><span aria-disabled="true"><?=$diary_months[$m]?></span><?php } ?></li>
				<?php } ?>
			</ul>
		</nav>

		<p class="diary-count"><?=$diary_month ? $diary_month.'월' : '한 해'?>의 기록 <b><?=$diary_total?></b></p>

		<?php if ($diary_list) { ?>
		<ol class="diary-entries">
			<?php foreach ($diary_list as $row) { $secret = strpos($row['wr_option'], 'secret') !== false; ?>
			<li>
				<a href="<?=diary_url($bo_table, ['wr_id' => (int)$row['wr_id'], 'yr' => $diary_year, 'mo' => $diary_month])?>">
					<time datetime="<?=diary_e(date('c', strtotime($row['wr_datetime'])))?>"><span class="day"><?=date('m.d', strtotime($row['wr_datetime']))?></span><span class="wd"><?=diary_weekday($row['wr_datetime'])?></span></time>
					<span class="subject"><?php if ($secret) { ?><i class="material-icons" aria-hidden="true">lock</i><span class="sound_only">비밀글</span><?php } ?><span class="title"><?=diary_e(get_text($row['wr_subject']) !== '' ? get_text($row['wr_subject']) : '제목 없음')?></span><?php if ((int)$row['wr_comment']) { ?><span class="cmt"><?=(int)$row['wr_comment']?><span class="sound_only">개 댓글</span></span><?php } ?></span>
				</a>
			</li>
			<?php } ?>
		</ol>
		<?php } else { ?>
		<p class="diary-empty">아직 남긴 기록이 없습니다.</p>
		<?php } ?>
	</div>

	<?php if ($diary_pages > 1) echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $diary_page, $diary_pages, $diary_paging_url); ?>

	<?php if ($write_href || $admin_href) { ?>
	<div class="diary-actions">
		<?php if ($admin_href) { ?><a href="<?=$admin_href?>" class="ui-btn etc" target="_blank">관리자</a><?php } ?>
		<?php if ($write_href) { ?><a href="<?=$write_href?>" class="ui-btn point">글쓰기</a><?php } ?>
	</div>
	<?php } ?>
</div>
<!-- } 다이어리 목록 -->
