<?php
// 이모티콘 목록 창: 스킨의 '이모티콘' 버튼이 새 창으로 연다. 누르면 원래 페이지의 입력칸 커서 자리에 코드가 들어간다.
include_once('./_common.php');
include_once(G5_LIB_PATH.'/emoticon.lib.php');

$g5['title'] = '이모티콘';
include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/emoticon-picker.css?v='.substr((string)@md5_file(G5_PATH.'/css/emoticon-picker.css'), 0, 10).'">', 0);
$emoticon_items = emoticon_items();
usort($emoticon_items, static fn($a, $b) => strcmp($a['code'], $b['code']));
?>
<main class="emoticon-window">
	<header class="emoticon-window-head">
		<h1>이모티콘</h1>
		<p>누르면 글에 들어갑니다. 여러 개를 이어서 넣을 수 있어요.</p>
	</header>
	<?php if ($emoticon_items) { ?>
	<ul class="emoticon-pop-list">
		<?php foreach ($emoticon_items as $item) { ?>
		<li><button type="button" data-emoticon-code="<?=htmlspecialchars($item['code'], ENT_QUOTES, 'UTF-8')?>" title="<?=htmlspecialchars($item['code'], ENT_QUOTES, 'UTF-8')?>">
			<img src="<?=htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8')?>" alt="" width="48" height="48" loading="lazy">
			<span><?=htmlspecialchars($item['code'], ENT_QUOTES, 'UTF-8')?></span>
		</button></li>
		<?php } ?>
	</ul>
	<?php } else { ?>
	<p class="emoticon-window-empty">등록된 이모티콘이 없습니다.</p>
	<?php } ?>
	<p class="emoticon-window-status" role="status" aria-live="polite"></p>
	<div class="emoticon-window-foot"><button type="button" class="ui-btn small" data-emoticon-close>닫기</button></div>
</main>
<script>
(function () {
	var status = document.querySelector(".emoticon-window-status");
	var timer = 0;
	document.addEventListener("click", function (e) {
		if (e.target.closest("[data-emoticon-close]")) return window.close();
		var b = e.target.closest("[data-emoticon-code]");
		if (!b) return;
		var code = b.getAttribute("data-emoticon-code");
		if (window.opener && !window.opener.closed) {
			window.opener.postMessage({ tomoEmoticon: code }, location.origin);
			status.textContent = code + " 넣었어요";
		} else {
			// 원래 페이지가 닫혔으면 코드만 복사해 둔다
			if (navigator.clipboard) navigator.clipboard.writeText(code).catch(function () {});
			status.textContent = "글쓰기 창이 닫혀 있어 " + code + " 를 복사했어요";
		}
		clearTimeout(timer);
		timer = setTimeout(function () { status.textContent = ""; }, 1500);
	});
	document.addEventListener("keydown", function (e) { if (e.key === "Escape") window.close(); });
})();
</script>
<?php include_once(G5_PATH.'/tail.sub.php'); ?>
