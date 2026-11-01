<?php
$sub_menu = '300700';
include_once('./_common.php');
include_once(G5_ADMIN_PATH.'/emoticon.lib.php');

auth_check($auth[$sub_menu] ?? '', 'r');
if (!emoticon_ready()) alert('이모티콘 테이블을 만들 수 없습니다. DB 권한을 확인해 주세요.');

$g5['title'] = '이모티콘 관리';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$total = (int)(sql_fetch("SELECT COUNT(*) AS cnt FROM `{$g5['emoticon_table']}`")['cnt'] ?? 0);
$result = sql_query("SELECT * FROM `{$g5['emoticon_table']}` ORDER BY me_text");
$colspan = 4;
?>

<div class="local_ov01 local_ov">
	<span>등록된 이모티콘 <?=number_format($total)?>개</span>
</div>

<form name="femoticonform" id="femoticonform" action="./emoticon_form_update.php" method="post" enctype="multipart/form-data" onsubmit="return femoticonform_submit(this);">
<input type="hidden" name="token" value="">
<div class="tbl_frm01 tbl_wrap">
	<table>
	<caption>이모티콘 추가</caption>
	<colgroup><col style="width:120px;"><col></colgroup>
	<tbody>
	<tr>
		<th scope="row"><label for="me_text">코드<strong class="sound_only">필수</strong></label></th>
		<td>
			<?=help('글 · 댓글에 이 코드를 쓰면 이모티콘으로 바뀝니다(이모티콘을 지원하는 스킨). 예: (하트) :웃음: 등. 띄어쓰기 · 따옴표 · 꺾쇠는 쓸 수 없습니다.')?>
			<input type="text" name="me_text" id="me_text" required class="required frm_input" size="20" maxlength="30">
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="me_img">이미지<strong class="sound_only">필수</strong></label></th>
		<td>
			<?=help('gif · png · jpg · webp, 2MB 이하. data/emoticon 폴더에 저장됩니다.')?>
			<input type="file" name="me_img" id="me_img" required class="required frm_input" accept="image/gif,image/png,image/jpeg,image/webp">
			<img src="" alt="" id="me_img_preview" class="emoticon-preview" hidden>
		</td>
	</tr>
	</tbody>
	</table>
</div>
<div class="btn_confirm">
	<div class="btn">
		<span class="material-icons">add</span>
		<input type="submit" value="추가" title="이모티콘 추가">
	</div>
</div>
</form>

<br>

<form name="femoticonlist" id="femoticonlist" action="./emoticon_list_update.php" method="post" onsubmit="return femoticonlist_submit(this);">
<input type="hidden" name="token" value="">
<div class="tbl_head01 tbl_wrap">
	<table>
	<caption><?=$g5['title']?> 목록</caption>
	<colgroup><col style="width:45px;"><col style="width:100px;"><col><col style="width:120px;"></colgroup>
	<thead>
	<tr>
		<th scope="col"><label for="chkall" class="sound_only">전체 선택</label><input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)"></th>
		<th scope="col">이미지</th>
		<th scope="col">코드</th>
		<th scope="col">파일</th>
	</tr>
	</thead>
	<tbody>
	<?php for ($i = 0; $row = sql_fetch_array($result); $i++) { ?>
	<tr class="bg<?=$i % 2?>">
		<td class="td_chk">
			<input type="hidden" name="me_id[<?=$i?>]" value="<?=(int)$row['me_id']?>">
			<label for="chk_<?=$i?>" class="sound_only"><?=get_text($row['me_text'])?> 선택</label>
			<input type="checkbox" name="chk[]" value="<?=$i?>" id="chk_<?=$i?>">
		</td>
		<td class="td_img"><?php if ($row['me_img'] !== '') { ?><img src="<?=G5_URL.get_text($row['me_img'])?>" alt="<?=get_text($row['me_text'])?>" class="emoticon-thumb" loading="lazy"><?php } ?></td>
		<td class="td_input"><label for="me_text_<?=$i?>" class="sound_only">코드</label><input type="text" name="me_text[<?=$i?>]" id="me_text_<?=$i?>" value="<?=get_text($row['me_text'])?>" class="frm_input" size="20" maxlength="30"></td>
		<td class="td_etc"><?=get_text(basename((string)$row['me_img']))?></td>
	</tr>
	<?php } if ($i === 0) echo '<tr><td colspan="'.$colspan.'" class="empty_table">등록된 이모티콘이 없습니다.</td></tr>'; ?>
	</tbody>
	</table>
</div>
<div class="btn_confirm">
	<div class="btn ty3">
		<span class="material-icons">build</span>
		<input type="submit" name="act_button" value="선택수정" title="선택수정" onclick="document.pressed=this.value">
	</div>
	<div class="btn ty2">
		<span class="material-icons">delete</span>
		<input type="submit" name="act_button" value="선택삭제" title="선택삭제" onclick="document.pressed=this.value">
	</div>
</div>
</form>

<style>
.emoticon-thumb { display: block; max-width: 60px; max-height: 60px; margin: 0 auto; }
#femoticonlist td:last-child { word-break: break-all; }
.emoticon-preview { display: block; max-width: 80px; max-height: 80px; margin-top: 8px; }
.emoticon-preview[hidden] { display: none; }
</style>
<script>
document.getElementById("me_img").addEventListener("change", function () {
	var img = document.getElementById("me_img_preview"), file = this.files && this.files[0];
	if (!file || !/^image\//.test(file.type)) { img.hidden = true; return; }
	img.src = URL.createObjectURL(file);
	img.hidden = false;
});
function femoticonform_submit(f) {
	if (/[\s<>"'&\\]/.test(f.me_text.value)) { alert("코드에 띄어쓰기 · 따옴표 · 꺾쇠는 쓸 수 없습니다."); f.me_text.focus(); return false; }
	return true;
}
function femoticonlist_submit(f) {
	if (!is_checked("chk[]")) { alert(document.pressed + " 하실 이모티콘을 하나 이상 선택하세요."); return false; }
	if (document.pressed === "선택삭제" && !confirm("선택한 이모티콘을 삭제하시겠습니까?\n\n이미지 파일도 함께 지워집니다.")) return false;
	return true;
}
</script>

<?php include_once(G5_ADMIN_PATH.'/admin.tail.php'); ?>
