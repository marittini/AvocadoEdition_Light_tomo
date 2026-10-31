<?php
if (!defined('_GNUBOARD_')) exit;
include_once __DIR__.'/pixiv.lib.php';
if (empty($_SESSION['pixiv_write_token'])) $_SESSION['pixiv_write_token'] = bin2hex(random_bytes(32));
if ($w === 'u' && !in_array($write['wr_1'] ?? '', ['pixiv:illustration','pixiv:manga','pixiv:novel'], true)) alert('기존 스킨 게시물은 이 스킨에서 수정할 수 없습니다. 기존 스킨으로 수정해 주세요.');
$px_existing = $w === 'u' ? pixiv_files($bo_table,(int)$wr_id,$write['wr_3'] ?? '') : [];
if ($w === 'u') {
    $px_all_files = get_file($bo_table,(int)$wr_id);
    foreach ($px_all_files as $key=>$entry) {
        if (is_int($key) && !empty($entry['file']) && !in_array((int)$entry['image_type'],[1,2,3,18],true))
            alert('이 글에는 이미지가 아닌 첨부파일이 있어 이 스킨에서 수정할 수 없습니다.');
    }
}
$px_cover = pixiv_cover_file($px_existing,$write['wr_3'] ?? '');
$px_tags = pixiv_tags($write['wr_2'] ?? '');
$px_note = pixiv_note($write ?? []);

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css?v=3">', 0);
?>


<section id="bo_w">

	<?php  if($board['bo_content_head']) { ?>
		<div class="board-notice-box">
			<?=stripslashes($board['bo_content_head']);?>
		</div>
	<?php  } ?>

	<!-- 게시물 작성/수정 시작 { -->
	<form name="fwrite" id="fwrite" action="<?php echo $action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
	<input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
	<input type="hidden" name="pixiv_token" value="<?=pixiv_escape($_SESSION['pixiv_write_token'])?>">
	<input type="hidden" name="w" value="<?php echo $w ?>">
	<input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
	<input type="hidden" name="wr_id" value="<?php echo $wr_id ?>">
	<input type="hidden" name="sca" value="<?php echo $sca ?>">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="spt" value="<?php echo $spt ?>">
	<input type="hidden" name="sst" value="<?php echo $sst ?>">
	<input type="hidden" name="sod" value="<?php echo $sod ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">
	<?php
	$option = '';
	$option_hidden = '';
	if ($is_notice || $is_html || $is_secret || $is_mail) {
		$option = '';
		if ($is_notice) {
			$option .= "\n".'<input type="checkbox" id="notice" name="notice" value="1" '.$notice_checked.'>'."\n".'<label for="notice">공지</label>';
		}

		if ($is_html) {
			if ($is_dhtml_editor) {
				$option_hidden .= '<input type="hidden" value="html1" name="html">';
			} else {
				$option .= "\n".'<input type="checkbox" id="html" name="html" onclick="html_auto_br(this);" value="'.$html_value.'" '.$html_checked.'>'."\n".'<label for="html">html</label>';
			}
		}

		if ($is_secret) {
			if ($is_admin || $is_secret==1) {
				$option .= "\n".'<input type="checkbox" id="secret" name="secret" value="secret" '.$secret_checked.'>'."\n".'<label for="secret">비밀글</label>';
			} else {
				$option_hidden .= '<input type="hidden" name="secret" value="secret">';
			}
		}

		if ($is_mail) {
			$option .= "\n".'<input type="checkbox" id="mail" name="mail" value="mail" '.$recv_email_checked.'>'."\n".'<label for="mail">답변메일받기</label>';
		}
	}

	echo $option_hidden;
	?>

	<noscript><p>여러 이미지 첨부와 순서 변경에는 자바스크립트가 필요합니다.</p></noscript>
	<div class="px-write-fields">
		<label for="wr_subject">제목 <strong class="sound_only">필수</strong></label>
		<div id="autosave_wrapper"><input type="text" name="wr_subject" value="<?php echo $subject ?>" id="wr_subject" required class="frm_input required" size="50" maxlength="255"></div>
		<label for="pixiv-type">작품 분류</label>
		<select id="pixiv-type" name="pixiv_type" required><?php foreach(['illustration'=>'일러스트','manga'=>'만화','novel'=>'소설'] as $value=>$label) { ?><option value="<?=$value?>" <?=($write['wr_1']??'pixiv:illustration')==='pixiv:'.$value?'selected':''?>><?=$label?></option><?php } ?></select>
		<label for="pixiv-tags">장르 태그</label><input id="pixiv-tags" name="pixiv_tags" class="frm_input" value="<?=pixiv_escape(implode(', ', $px_tags))?>" placeholder="예: 판타지, 일상" autocomplete="off"><small>쉼표로 구분합니다. 기존 태그를 선택할 수도 있습니다.</small>
		<div id="pixiv-tag-suggestions" class="px-tags"><?php $px_res=sql_query("SELECT wr_2 FROM `{$write_table}` WHERE wr_is_comment=0 AND wr_1 IN ('pixiv:illustration','pixiv:manga','pixiv:novel') AND wr_option NOT LIKE '%secret%' AND wr_2<>'' ORDER BY wr_num"); $px_seen=[]; while($px_row=sql_fetch_array($px_res)) foreach(pixiv_tags($px_row['wr_2']) as $px_tag) if(!isset($px_seen[$px_tag])){ $px_seen[$px_tag]=true; ?><button type="button" data-suggest="<?=pixiv_escape($px_tag)?>">#<?=pixiv_escape($px_tag)?></button><?php } ?></div>
		<label for="pixiv-note">설명</label><textarea id="pixiv-note" name="pixiv_note" rows="3" maxlength="500"><?=pixiv_escape($px_note)?></textarea>
	</div>
	<table class="theme-form">
		<colgroup>
			<col style="width: 100px;" />
			<col />
		</colgroup>
		<tbody>
		<?php if ($is_name) { ?>
		<tr>
			<th scope="row"><label for="wr_name">이름<strong class="sound_only">필수</strong></label></th>
			<td><input type="text" name="wr_name" value="<?php echo $name ?>" id="wr_name" required class="frm_input required" size="10" maxlength="20"></td>
		</tr>
		<?php } ?>

		<?php if ($is_password) { ?>
		<tr>
			<th scope="row"><label for="wr_password">비밀번호<strong class="sound_only">필수</strong></label></th>
			<td><input type="password" name="wr_password" id="wr_password" <?php echo $password_required ?> class="frm_input <?php echo $password_required ?>" maxlength="20"></td>
		</tr>
		<?php } ?>

		<?php if ($is_email) { ?>
		<tr>
			<th scope="row"><label for="wr_email">이메일</label></th>
			<td><input type="text" name="wr_email" value="<?php echo $email ?>" id="wr_email" class="frm_input email" size="50" maxlength="100"></td>
		</tr>
		<?php } ?>

		<?php if ($is_homepage) { ?>
		<tr>
			<th scope="row"><label for="wr_homepage">홈페이지</label></th>
			<td><input type="text" name="wr_homepage" value="<?php echo $homepage ?>" id="wr_homepage" class="frm_input" size="50"></td>
		</tr>
		<?php } ?>

		<?php if ($option) { ?>
		<tr>
			<th scope="row">옵션</th>
			<td><?php echo $option ?></td>
		</tr>
		<?php } ?>

		<?php if ($is_category) { ?>
		<tr>
			<th scope="row"><label for="ca_name">분류<strong class="sound_only">필수</strong></label></th>
			<td>
				<select name="ca_name" id="ca_name" required class="required" >
					<option value="">선택하세요</option>
					<?php echo $category_option ?>
				</select>
			</td>
		</tr>
		<?php } ?>



		<tr id="pixiv-content-row">
			<th scope="row"><label for="wr_content">본문<strong class="sound_only">소설 필수</strong></label></th>
			<td class="wr_content">
				<?php if($write_min || $write_max) { ?>
				<!-- 최소/최대 글자 수 사용 시 -->
				<p id="char_count_desc">이 게시판은 최소 <strong><?php echo $write_min; ?></strong>글자 이상, 최대 <strong><?php echo $write_max; ?></strong>글자 이하까지 글을 쓰실 수 있습니다.</p>
				<?php } ?>
				<?php echo $editor_html; // 에디터 사용시는 에디터로, 아니면 textarea 로 노출 ?>
				<?php if($write_min || $write_max) { ?>
				<!-- 최소/최대 글자 수 사용 시 -->
				<div id="char_count_wrap"><span id="char_count"></span>글자</div>
				<?php } ?>
			</td>
		</tr>

		<?php /*for ($i=1; $is_link && $i<=G5_LINK_COUNT; $i++) { ?>
		<tr>
			<th scope="row"><label for="wr_link<?php echo $i ?>">링크 #<?php echo $i ?></label></th>
			<td><input type="text" name="wr_link<?php echo $i ?>" value="<?php if($w=="u"){echo$write['wr_link'.$i];} ?>" id="wr_link<?php echo $i ?>" class="frm_input" size="50"></td>
		</tr>
		<?php }*/ ?>

		<?php if($is_file){ ?>
		<tr><th scope="row"><label for="pixiv-files">이미지</label></th><td>
		<label class="ui-btn px-picker">이미지 선택<input id="pixiv-files" class="px-file-picker" type="file" accept="image/jpeg,image/png,image/gif,image/webp" multiple></label>
		<p>이미지를 끌어 순서를 바꾸거나 버튼으로 이동하세요. 대표 이미지를 선택할 수 있습니다. 첨부 최대 <?=intval($file_count)?>장.</p>
		<ol id="pixiv-file-list" class="px-file-list"></ol><div id="pixiv-file-inputs"></div><input type="hidden" name="pixiv_order" id="pixiv-order"><input type="hidden" name="pixiv_cover" id="pixiv-cover" value="<?=isset($px_cover['index'])?(int)$px_cover['index']:''?>">
		<script type="application/json" id="pixiv-existing"><?=json_encode(array_map(static fn($f)=>['id'=>$f['index'],'url'=>html_entity_decode($f['url']),'name'=>stripslashes($f['source'])],$px_existing),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE)?></script>
		<script src="<?=$board_skin_url?>/write.js?v=2" defer></script>
		</td></tr><?php } ?>

		<?php if ($is_guest) { //자동등록방지  ?>
		<tr>
			<th scope="row">자동등록방지</th>
			<td>
				<?php echo $captcha_html ?>
			</td>
		</tr>
		<?php } ?>

		</tbody>
	</table>

	<div class="btn_confirm">
		<button type="submit" id="btn_submit" accesskey="s" class="ui-btn point"><i class="material-icons">edit</i> 작성</button>
		<a href="./board.php?bo_table=<?php echo $bo_table ?>" class="ui-btn"><i class="material-icons">list</i> 목록</a>
	</div>
	</form>

	<script>
	<?php if($write_min || $write_max) { ?>
	// 글자수 제한
	var char_min = parseInt(<?php echo $write_min; ?>); // 최소
	var char_max = parseInt(<?php echo $write_max; ?>); // 최대
	check_byte("wr_content", "char_count");

	$(function() {
		$("#wr_content").on("keyup", function() {
			check_byte("wr_content", "char_count");
		});
	});

	<?php } ?>
	function html_auto_br(obj)
	{
		if (obj.checked) {
			result = confirm("자동 줄바꿈을 하시겠습니까?\n\n자동 줄바꿈은 게시물 내용중 줄바뀐 곳을<br>태그로 변환하는 기능입니다.");
			if (result)
				obj.value = "html2";
			else
				obj.value = "html1";
		}
		else
			obj.value = "";
	}

	function fwrite_submit(f)
	{
		<?php echo get_editor_js('wr_content', $is_dhtml_editor); ?>
        if (f.pixiv_type.value === 'novel') { <?php echo chk_editor_js('wr_content', $is_dhtml_editor); ?> }
        if (window.pixivPrepareFiles && !window.pixivPrepareFiles()) return false;

		var subject = "";
		var content = "";
		$.ajax({
			url: g5_bbs_url+"/ajax.filter.php",
			type: "POST",
			data: {
				"subject": f.wr_subject.value,
				"content": f.wr_content.value
			},
			dataType: "json",
			async: false,
			cache: false,
			success: function(data, textStatus) {
				subject = data.subject;
				content = data.content;
			}
		});

		if (subject) {
			alert("제목에 금지단어('"+subject+"')가 포함되어있습니다");
			f.wr_subject.focus();
			return false;
		}

		if (content) {
			alert("내용에 금지단어('"+content+"')가 포함되어있습니다");
			if (typeof(ed_wr_content) != "undefined")
				ed_wr_content.returnFalse();
			else
				f.wr_content.focus();
			return false;
		}

		if (document.getElementById("char_count")) {
			if (char_min > 0 || char_max > 0) {
				var cnt = parseInt(check_byte("wr_content", "char_count"));
				if (char_min > 0 && char_min > cnt) {
					alert("내용은 "+char_min+"글자 이상 쓰셔야 합니다.");
					return false;
				}
				else if (char_max > 0 && char_max < cnt) {
					alert("내용은 "+char_max+"글자 이하로 쓰셔야 합니다.");
					return false;
				}
			}
		}

		<?php echo $captcha_js; // 캡챠 사용시 자바스크립트에서 입력된 캡챠를 검사함  ?>

		document.getElementById("btn_submit").disabled = "disabled";

		return true;
	}
	</script>
</section>
<!-- } 게시물 작성/수정 끝 -->
