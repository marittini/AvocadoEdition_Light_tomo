<?php
include_once __DIR__.'/_common.php';
if (empty($_SESSION['ott_write_token'])) $_SESSION['ott_write_token'] = bin2hex(random_bytes(32));
$ott_column = sql_fetch("SHOW COLUMNS FROM `{$write_table}` LIKE 'wr_protect'", false);
if (empty($ott_column['Field']) && $is_admin === 'super') {
    sql_query("ALTER TABLE `{$write_table}` ADD `wr_protect` varchar(255) NOT NULL DEFAULT '' AFTER `wr_url`", false);
    $ott_column = sql_fetch("SHOW COLUMNS FROM `{$write_table}` LIKE 'wr_protect'", false);
}
if (empty($ott_column['Field'])) alert('스킨 초기 설정이 필요합니다. 최고관리자가 글쓰기 화면을 열어 DB 권한을 확인해 주세요.');
include_once ($board_skin_path.'/setting/user.config.php');
include_once ($board_skin_path.'/color.config.php');
$action_url = https_url(G5_BBS_DIR)."/write_update.php";
$is_notice = true;

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css?v=20260721_ott_write_autogrow_v8">', 0);
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/txtggu/textggu.css?v=2">', 0);

$prefiles = array();
$write = is_array($write ?? null) ? $write : [];
$is_comment = !empty($is_comment);
$is_view = !empty($is_view);
$is_secret = $is_secret ?? (int)($board['bo_use_secret'] ?? 0);
$subject = $subject ?? '';
$pa_ca_name = $pa_ca_name ?? '';
$sec = $sec_select = $mem_select = $pro_select = '';
foreach (['wr_num','ca_name','wr_secret','wr_protect','wr_1','wr_2','wr_3','wr_4','wr_5','wr_6','wr_9','wr_10','wr_content','wr_parent'] as $key) $write[$key] = $write[$key] ?? '';
if ($w === 'u' && !empty($wr_id)) {
    $existing = get_file($bo_table, (int)$wr_id);
    foreach ($existing as $idx => $entry) {
        if (!is_int($idx) || empty($entry['file'])) continue;
        $prefiles[] = ['name'=>stripslashes($entry['source']), 'url'=>G5_BBS_URL.'/archive_file.php?archive_kind=file&archive_path='.rawurlencode($bo_table.'/'.$entry['file']), 'idx'=>$idx];
    }
}

$wr_num = $write['wr_num'];

if ($is_comment && $w != 'u') {
    $write = array_fill_keys(['wr_num','ca_name','wr_secret','wr_protect','wr_1','wr_2','wr_3','wr_4','wr_5','wr_6','wr_9','wr_10','wr_content','wr_parent'], '');
}

$is_category = false;
$category_option = '';
if ($board['bo_use_category']) {
    $ca_name = "";
    if (isset($write['ca_name']))
        $ca_name = $write['ca_name'];
    $category_option = get_category_option($bo_table, $ca_name);
    $is_category = true;
}

$pa_ca_name = $write['ca_name'] ? $write['ca_name'] : $pa_ca_name;
?>
<script type="text/javascript" src="<?=$board_skin_url;?>/js/paste.js"></script>

<?php //=print_r2($file['count']);?>
<section id="bo_w" <?php if($board['bo_table_width']>0){?>style="max-width:<?=$board['bo_table_width']?><?=$board['bo_table_width']>100 ? "px":"%"?>;margin:0 auto;"<?php }?>>
	<!-- 게시물 작성/수정 시작 { -->
	<form name="fwrite" id="fwrite" action="<?php echo $action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
	<input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
    <input type="hidden" name="wr_9" id="ott-cover-slot" value="<?=htmlspecialchars((string)($write['wr_9'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
    <input type="hidden" name="ott_write_token" value="<?=htmlspecialchars($_SESSION['ott_write_token'], ENT_QUOTES, 'UTF-8')?>">
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

    <?php  if ($is_comment) { ?>
        <input type="hidden" name="pa_wr_id" value="<?php echo $write['wr_parent'] ? $write['wr_parent'] : $wr_id ?>">
        <input type="hidden" name="pa_wr_num" value="<?=$wr_num;?>">
        <input type="hidden" name="wr_is_comment" value="1">
        <input type="hidden" name="ca_name" value="<?=htmlspecialchars((string)$pa_ca_name, ENT_QUOTES, 'UTF-8')?>">
    <?php  } ?>

    <?php  if ($is_view) { ?>
        <input type="hidden" name="is_view" value="1">
    <?php  } ?>

	<?php
	$option = '';
	$option_hidden = '';

	if ($is_notice || $is_html || $is_secret || $is_mail) {
		$option = '';

		if ($is_notice && !$is_comment) {
			$option .= "\n".'<input type="checkbox" class="ott_w_chip_input" id="notice" name="notice" value="1" '.$notice_checked.'>'."\n".'<label class="ott_w_chip ott_w_notice_chip" for="notice"><i class="fa-solid fa-bullhorn"></i> 공지</label>';
		}

		if ($is_secret) {
			if ($is_admin || $is_secret==1) {
				if($secret_checked) $sec_select="selected";
				$sec .='<option value="secret" '.$sec_select.'>비밀글</option>';
			} else {
				$option_hidden .= '<input type="hidden" name="secret" value="secret">';
			}
		}
	}

	echo $option_hidden;

	if($write['wr_protect']!='') $pro_select="selected";
	else if($write['wr_secret']=='1') $mem_select="selected";

	if($is_member) {
		$sec .='<option value="protect" '.$pro_select.'>보호글</option>';
		$sec .='<option value="member" '.$mem_select.'>멤버공개</option>';
	}
	?>

	<div class="board-write theme-box ott_write">

	<div class="ott_w_toolbar">
		<?php  if($is_secret!=2 || $is_admin){ ?>
		<div class="ott_w_toolbar_item ott_w_visibility_item">
			<label class="sound_only" for="set_secret">공개 설정</label>
			<select name="set_secret" id="set_secret" class="ott_w_select">
				<option value="">전체공개</option>
				<?=$sec?>
			</select>
		</div>
		<?php  } ?>

		<?php  if ($is_category && !$is_comment) { ?>
		<div class="ott_w_toolbar_item ott_w_category_item">
			<label class="sound_only" for="ca_name">분류</label>
			<nav id="write_category">
				<select name="ca_name" id="ca_name" required class="required frm_input ott_w_category_select">
					<option value="">분류 선택</option>
					<?php echo $category_option ?>
				</select>
			</nav>
		</div>
		<?php  } ?>

		<?php echo $option ?>

		<label class="ott_w_chip ott_w_spoiler_chip" for="wr_1">
			<input type="checkbox" id="wr_1" name="wr_1" value="1"<?=($write['wr_1']) ? ' checked' : '';?>>
			<i class="fa-solid fa-eye-slash"></i> 스포일러
		</label>

	</div>


	<div id="set_protect" class="ott_w_group" style="display:<?=$w=='u' && $pro_select ? 'block':'none'?>;">
		<label class="ott_w_label" for="wr_protect">보호글 암호</label>
		<input type="password" name="wr_protect" id="wr_protect" value="" maxlength="20" class="frm_input full" autocomplete="new-password"><small>수정할 때 비워두면 기존 비밀번호를 유지합니다.</small>
	</div>

	<?php  if(!$is_comment && $_list_style == 'list') { ?>
	<div class="ott_w_top_grid">
		<div class="ott_w_group ott_w_thumb_group">
			<label class="ott_w_label" for="wr_10">썸네일 <span class="ott_w_label_sub">(이미지 외부링크)</span></label>
			<div class="ott_w_thumb_row">
				<div class="ott_w_thumb_preview no_img" id="ott_thumb_preview">
					<i class="fa-regular fa-image"></i>
				</div>
				<input type="text" name="wr_10" id="wr_10" value="<?=htmlspecialchars((string)$write['wr_10'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>" class="frm_input full" maxlength="255" placeholder="https://...">
			</div>
		</div>
	</div>
	<?php  } ?>

	<div class="ott_w_group ott_w_title_group">
		<label class="ott_w_label" for="wr_subject">제목</label>
		<?php  $subject = ($subject == 'noSubject') ? '' : $subject; ?>
		<input type="hidden" name="wr_subject" value="noSubject">
		<input type="text" name="wr_subject2" value="<?php echo $subject ?>" id="wr_subject" class="ott_w_title_input" maxlength="255" placeholder="작품명이나 감상 제목을 입력하세요. (선택)">
	</div>

	<?php  if(!$is_comment) { ?>
	<div class="ott_w_group ott_w_rating_group">
	<div class="ott_w_rating_head">
		<label class="ott_w_label">별점</label>
		<span class="ott_w_rating_help">감상 상태나 평점을 선택하세요.</span>
	</div>
		<div class="ott_w_rating" id="ott_w_rating">
			<button type="button" class="ott_w_star" data-val="1"><i class="fa-solid fa-star"></i></button>
			<button type="button" class="ott_w_star" data-val="2"><i class="fa-solid fa-star"></i></button>
			<button type="button" class="ott_w_star" data-val="3"><i class="fa-solid fa-star"></i></button>
			<button type="button" class="ott_w_star" data-val="4"><i class="fa-solid fa-star"></i></button>
			<button type="button" class="ott_w_star" data-val="5"><i class="fa-solid fa-star"></i></button>
			<button type="button" class="ott_w_watching" data-val="watching">감상 중···.</button>
			<button type="button" class="ott_w_clear" data-val="" title="선택 해제"><i class="fa-solid fa-xmark"></i></button>
		</div>
		<select name="wr_6" id="wr_6" class="ott_w_visually_hidden">
			<option value="">선택 안 함</option>
			<option value="watching" <?=($write['wr_6']=='watching') ? 'selected' : ''?>>감상 중···.</option>
			<option value="1" <?=($write['wr_6']=='1') ? 'selected' : ''?>>★☆☆☆☆ (1점)</option>
			<option value="2" <?=($write['wr_6']=='2') ? 'selected' : ''?>>★★☆☆☆ (2점)</option>
			<option value="3" <?=($write['wr_6']=='3') ? 'selected' : ''?>>★★★☆☆ (3점)</option>
			<option value="4" <?=($write['wr_6']=='4') ? 'selected' : ''?>>★★★★☆ (4점)</option>
			<option value="5" <?=($write['wr_6']=='5') ? 'selected' : ''?>>★★★★★ (5점)</option>
		</select>
	</div>
	<?php  } ?>

	<?php  if($board['bo_1']) { ?>
	<div class="write-notice">
		<?=$board['bo_1']?>
	</div>
	<?php  } ?>

	<div class="ott_w_group ott_w_content_group">
		<div class="ott_w_content_head">
			<label class="ott_w_label">내용</label>
			<div class="ott_w_content_tools" aria-label="내용 작성 도구">
				<label id="upload-btn" class="ott_w_upload_btn" title="이미지 추가"><i class="fa-regular fa-image"></i> 이미지 추가</label>
				<button type="button" class="ott_w_tool_btn ott_w_content_tool_btn" onclick="toggleWritePanel('txtggu')" title="텍스트 꾸미기"><i class="fa-solid fa-code"></i> 텍꾸</button>
				<button type="button" class="ott_w_tool_btn ott_w_content_tool_btn" onclick="toggleWritePanel('emoticon')" title="이모티콘"><i class="fa-regular fa-face-smile"></i> 이모티콘</button>
			</div>
		</div>

		<div id="write_tool_panel" class="write_tool_panel" style="display:none;">
			<div class="write_tool_panel_head">
				<strong id="write_tool_title">도구</strong>
				<button type="button" class="write_tool_close" onclick="closeWritePanel()">×</button>
			</div>
			<iframe id="write_tool_iframe" src="" frameborder="0"></iframe>
		</div>

		<div id="dropzone" class="ott_w_dropzone">
			<div class="wr_content">
				<textarea id="wr_content" name="wr_content" style="width:100%" rows="14" oninput="ottAutoResize(this)" placeholder="감상을 자유롭게 적어보세요. 유튜브 링크는 자동 재생되고, X(트위터) 게시물 링크는 자동으로 인용돼요."><?=htmlspecialchars((string)$write['wr_content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?></textarea>
			</div>
			<div id="preview"></div>

			<input type="file" id="image-input" multiple accept="image/*" style="display: none;">
		</div>
		<div class="ott_w_hint_group">
			<span class="frm_info"><i class="fa-solid fa-circle-exclamation"></i> 이미지: 복사+붙여넣기 / 드래그 앤 드롭 가능. [파일1], [파일2] 로 위치 지정 가능.</span>
			<span class="frm_info"><i class="fa-solid fa-circle-exclamation"></i> 토글[토글제목] 줄바꿈 [토글내용] 으로 토글 영역 작성 가능.</span>
			<span class="frm_info"><i class="fa-solid fa-circle-play"></i> 유튜브 링크를 붙여넣으면 게시글 상세에서 자동 재생됩니다.</span>
		</div>
	</div>

	<details class="ott_w_collapsible">
		<summary><i class="fa-solid fa-link"></i> 외부링크(이미지) 추가</summary>
		<div class="ott_w_collapsible_body">
			<input type="text" name="wr_2" id="wr_2" value="<?=htmlspecialchars((string)$write['wr_2'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>" class="frm_input full" placeholder="외부 이미지 링크 1">
			<input type="text" name="wr_3" id="wr_3" value="<?=htmlspecialchars((string)$write['wr_3'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>" class="frm_input full" placeholder="외부 이미지 링크 2">
			<input type="text" name="wr_4" id="wr_4" value="<?=htmlspecialchars((string)$write['wr_4'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>" class="frm_input full" placeholder="외부 이미지 링크 3">
			<input type="text" name="wr_5" id="wr_5" value="<?=htmlspecialchars((string)$write['wr_5'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>" class="frm_input full" placeholder="외부 이미지 링크 4">
			<span class="frm_info"><i class="fa-solid fa-circle-exclamation"></i> 첨부파일과 혼용 가능. [이미지1], [이미지2] 로 위치 지정 가능.</span>
		</div>
	</details>

	<?php  if(!$is_member){ ?>
	<div class="ott_w_group ott_w_guest_grid">
		<div>
			<label class="ott_w_label" for="wr_name">이름<strong class="sound_only">필수</strong></label>
			<input type="text" name="wr_name" value="<?php echo $name ?>" id="wr_name" required class="frm_input full required">
		</div>
		<div>
			<label class="ott_w_label" for="wr_password">비밀번호<strong class="sound_only">필수</strong></label>
			<input type="password" name="wr_password" id="wr_password" <?php echo $password_required ?> class="frm_input full <?php echo $password_required ?>">
		</div>
	</div>
	<?php  } ?>

	<div class="ott_w_actions">
        <input type="submit" value="작성" id="btn_submit" accesskey="s" class="btn_submit ui-btn point ott_w_submit">
        <?php  if ($w == 'u') { ?>
        <a href="./board.php?bo_table=<?php echo $bo_table ?>&wr_id=<?=$write['wr_parent']?>" class="btn_cancel ui-btn ott_w_cancel">취소</a>
        <?php  } ?>
    </div>

	</div>
	</form>
</section>
<!-- } 게시물 작성/수정 끝 -->

<script>
(function(){
    // 별점 위젯 <-> 실제 select#wr_6 동기화 (제출되는 값/기능은 기존과 동일)
    var sel = document.getElementById('wr_6');
    var box = document.getElementById('ott_w_rating');
    if (sel && box) {
        var stars = box.querySelectorAll('.ott_w_star');
        var watchingBtn = box.querySelector('.ott_w_watching');
        var clearBtn = box.querySelector('.ott_w_clear');

        function paint(val) {
            stars.forEach(function(st){
                var on = val !== 'watching' && val !== '' && parseInt(st.dataset.val, 10) <= parseInt(val || 0, 10);
                st.classList.toggle('on', !!on);
            });
            if (watchingBtn) watchingBtn.classList.toggle('on', val === 'watching');
        }

        function setVal(val) {
            sel.value = val;
            paint(val);
        }

        stars.forEach(function(st){
            st.addEventListener('click', function(){ setVal(st.dataset.val); });
        });
        if (watchingBtn) watchingBtn.addEventListener('click', function(){ setVal('watching'); });
        if (clearBtn) clearBtn.addEventListener('click', function(){ setVal(''); });

        paint(sel.value);
    }

    // 긴 글 작성 시 내용 입력칸 자동 확장
    var contentTextarea = document.getElementById('wr_content');
    if (contentTextarea) {
        ottAutoResize(contentTextarea);
    }

    // 썸네일 실시간 미리보기
    var thumbInput = document.getElementById('wr_10');
    var thumbPrev = document.getElementById('ott_thumb_preview');
    if (thumbInput && thumbPrev) {
        function renderThumb() {
            var url = thumbInput.value.trim();
            if (!url) {
                thumbPrev.style.backgroundImage = '';
                thumbPrev.classList.add('no_img');
                thumbPrev.innerHTML = '<i class="fa-regular fa-image"></i>';
                return;
            }
            var img = new Image();
            img.onload = function(){
                thumbPrev.classList.remove('no_img');
                thumbPrev.innerHTML = '';
                thumbPrev.style.backgroundImage = 'url(\'' + url + '\')';
            };
            img.onerror = function(){
                thumbPrev.classList.add('no_img');
                thumbPrev.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i>';
                thumbPrev.style.backgroundImage = '';
            };
            img.src = url;
        }
        thumbInput.addEventListener('input', renderThumb);
        renderThumb();
    }
})();

function ottAutoResize(textarea) {
    if (!textarea) return;

    var minimumHeight = window.matchMedia('(max-width: 640px)').matches ? 260 : 320;
    textarea.style.height = 'auto';
    textarea.style.height = Math.max(textarea.scrollHeight, minimumHeight) + 'px';
}

// 기존 텍꾸 패널에서 호출하는 함수명도 같은 자동 확장 기능으로 연결
function autoResize(textarea) {
    ottAutoResize(textarea);
}

function toggleWritePanel(type) {
    var panel = document.getElementById('write_tool_panel');
    var iframe = document.getElementById('write_tool_iframe');
    var title = document.getElementById('write_tool_title');

    var url = '';
    var label = '';

 if (type === 'txtggu') {
    url = '<?=$board_skin_url?>/txtggu/index.php';
    label = 'TXT TOOL';
} else if (type === 'emoticon') {
    url = '<?=$board_skin_url?>/emoticon_list.php';
    label = 'EMOTICON';
}

    if (panel.style.display !== 'none' && iframe.dataset.type === type) {
        panel.style.display = 'none';
        iframe.src = '';
        iframe.dataset.type = '';
        return;
    }

    iframe.src = url;
    iframe.dataset.type = type;
    title.textContent = label;
    panel.style.display = 'block';
}

function closeWritePanel() {
    var panel = document.getElementById('write_tool_panel');
    var iframe = document.getElementById('write_tool_iframe');

    panel.style.display = 'none';
    iframe.src = '';
    iframe.dataset.type = '';
}
</script>

<?php include_once($board_skin_path.'/write.script.php'); ?>
