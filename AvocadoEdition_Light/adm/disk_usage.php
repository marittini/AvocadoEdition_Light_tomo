<?php
$sub_menu = '900400';
include_once './_common.php';
if ($is_admin !== 'super') alert('최고관리자만 접근 가능합니다.');
$g5['title'] = '디스크 사용량';
include_once './admin.head.php';
?>
<section class="archive-disk" aria-labelledby="disk-title" data-quota-bytes="500000000">
<div class="archive-disk-heading"><div><span class="archive-eyebrow">닷홈 무료호스팅</span><h2 id="disk-title">저장 공간</h2><p>기본 용량 500MB 기준</p></div><button type="button" id="disk-start">다시 측정</button></div>
<div class="archive-storage-card">
<div class="archive-storage-value"><strong id="disk-used">측정 전</strong><span>/ 500 MB</span><b id="disk-percent">—</b></div>
<div id="disk-meter" class="archive-storage-meter" role="progressbar" aria-label="닷홈 무료 용량 대비 측정 사용량" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span id="disk-fill"></span></div>
<p id="disk-remaining">사이트 파일 사용량을 확인합니다. DB 크기는 별도로 표시합니다.</p>
<div class="archive-storage-legend"><span>파일 <b id="disk-file-bytes">—</b></span><span>DB 추정 <b id="disk-db-bytes">—</b></span></div>
</div>
<div class="archive-disk-actions"><button type="button" id="disk-pause" hidden>일시 정지</button><button type="button" id="disk-resume" hidden>이어서 측정</button><p id="disk-status" role="status" aria-live="polite">사용량을 불러오고 있습니다.</p></div>
<div class="archive-disk-details"><div><h3>측정 정보</h3><dl id="disk-summary"></dl></div><div><h3>파일 분류</h3><table><caption class="sound_only">측정 범위 내 파일 분류</caption><thead><tr><th>분류</th><th>크기</th></tr></thead><tbody id="disk-categories"></tbody></table></div></div>
<p class="archive-disk-note">그래프는 사이트 폴더의 파일 크기를 500MB 기준으로 비교합니다. DB는 별도 추정값으로 표시하며 그래프에 합산하지 않습니다. 다른 사이트·메일·백업 등은 빠질 수 있어 닷홈 관리 화면의 실제 사용량과 다를 수 있습니다. 요금제나 추가 용량을 변경했다면 기준 용량도 다릅니다.</p>
</section>
<?php include_once './admin.tail.php'; ?>
