<?php
$sub_menu = '900400';
include_once './_common.php';
if ($is_admin !== 'super') alert('최고관리자만 접근 가능합니다.');
$g5['title'] = '디스크 사용량';
include_once './admin.head.php';
?>
<section class="archive-disk" aria-labelledby="disk-title">
<h2 id="disk-title">디스크 사용량</h2>
<p>이 프로그램이 읽을 수 있는 폴더의 논리 파일 크기입니다. 닷홈 계정 전체 사용량·잔여량과 다를 수 있습니다. DB는 파일 합계에 포함하지 않습니다.</p>
<p>닷홈 계정 한도와 DB 포함 기준은 호스팅 관리에서 확인하세요. 측정하는 동안 변경된 파일은 다음 재측정에 반영될 수 있습니다.</p>
<button type="button" id="disk-start">다시 측정</button>
<button type="button" id="disk-pause" hidden>일시 정지</button>
<button type="button" id="disk-resume" hidden>이어서 측정</button>
<p id="disk-status" role="status" aria-live="polite">아직 측정하지 않았습니다.</p>
<dl id="disk-summary"></dl>
<table><caption>측정 범위 내 파일 분류</caption><thead><tr><th>분류</th><th>크기</th></tr></thead><tbody id="disk-categories"></tbody></table>
<p>자동 파일 삭제는 수행하지 않습니다. 기존 세션·캐시 관리 기능은 그대로 사용할 수 있습니다. 심볼릭 링크는 따라가지 않습니다.</p>
</section>
<?php include_once './admin.tail.php'; ?>
