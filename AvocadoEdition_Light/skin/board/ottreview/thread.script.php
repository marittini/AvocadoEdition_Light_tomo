<?php 
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
if (empty($_SESSION['ott_action_token'])) $_SESSION['ott_action_token'] = bin2hex(random_bytes(32));
?>
<div class="thread_img_full">
    <button type="button" class="thread_img_close"><i class="fa-solid fa-circle-xmark"></i></button>
    <div class="thread_img_wrap"></div>
</div>
<script>
$(function(){ $('.thread_img_full').appendTo(document.body); });
/* X(구 트위터) 공식 widgets.js 로더
 * 최초 화면과 AJAX로 추가된 보호글/댓글 영역을 모두 다시 렌더링합니다.
 */
(function(window, document){
    var renderTimer = null;

    function renderXEmbeds(root) {
        root = root || document;
        if (!root.querySelector('.twitter-tweet')) return;

        if (window.twttr && window.twttr.widgets && typeof window.twttr.widgets.load === 'function') {
            try {
                window.twttr.widgets.load(root);
            } catch (e) {}
            return;
        }

        var script = document.getElementById('twitter-wjs');
        if (!script) {
            script = document.createElement('script');
            script.id = 'twitter-wjs';
            script.async = true;
            script.charset = 'utf-8';
            script.src = 'https://platform.x.com/widgets.js';
            script.onload = function(){
                if (window.twttr && window.twttr.widgets) {
                    window.twttr.widgets.load(root);
                }
            };
            (document.head || document.body).appendChild(script);
        }
    }

    window.ottLoadXEmbeds = renderXEmbeds;

    function scheduleXRender(root) {
        window.clearTimeout(renderTimer);
        renderTimer = window.setTimeout(function(){
            renderXEmbeds(root || document);
        }, 80);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function(){ renderXEmbeds(document); });
    } else {
        renderXEmbeds(document);
    }

    // AJAX로 본문/댓글 HTML이 들어오는 경우 자동 감지
    if (window.MutationObserver) {
        var observer = new MutationObserver(function(mutations){
            var found = false;
            var targetRoot = document;

            for (var i = 0; i < mutations.length && !found; i++) {
                for (var j = 0; j < mutations[i].addedNodes.length; j++) {
                    var node = mutations[i].addedNodes[j];
                    if (node.nodeType !== 1) continue;
                    if ((node.matches && node.matches('.twitter-tweet')) ||
                        (node.querySelector && node.querySelector('.twitter-tweet'))) {
                        found = true;
                        targetRoot = node.parentNode || document;
                        break;
                    }
                }
            }

            if (found) scheduleXRender(targetRoot);
        });

        var observeTarget = document.body || document.documentElement;
        if (observeTarget) {
            observer.observe(observeTarget, { childList: true, subtree: true });
        }
    }
})(window, document);

$(document).on('click', '.thread_btn button', function(e){
    $.ajax({
        url: '<?=$board_skin_url; ?>/write.skin.php',
        type: 'POST',
        data: {
            wr_id: '',
            bo_table : '<?=$bo_table;?>'
        },
        success: function(data) {
            $('.thread_comment').html('');
            $('#thread_write').html(data);
            if ($('.thread_btn').hasClass('on')) {
                $('.thread_btn').removeClass('on');
                $('.thread_write').stop().slideUp();
            } else {
                $('.thread_btn').addClass('on');
                $('.thread_write').stop().slideDown();
            }
            
        }
    });    
    
});

// 스포일러 확인
function spoilerThreadShow(wr_id){
    $('.thread_main'+wr_id).removeClass('spoiler');
    $('.spoiler_sec'+wr_id).remove();
}

// 보호글 확인
function showThreadContent(form){
    var formData = new FormData(form);
    var wr_id = formData.get('wr_id');
    $.ajax({
        url: '<?=$board_skin_url?>/list.skin.con.php',
        data: formData,
        processData: false,
		contentType: false,
		type: 'POST',
        success: function(data) {
            $('#thread'+wr_id).html(data);
            if (typeof window.ottLoadXEmbeds === 'function') window.ottLoadXEmbeds(document.getElementById('thread'+wr_id));
        }
    });
    return false;
}

// 타래 작성
function writeThreadComment(wr_id,ca_name=''){
    $.ajax({
        url: '<?=$board_skin_url; ?>/write.skin.php',
        type: 'POST',
        data: {
            wr_id: wr_id,
            bo_table : '<?=$bo_table;?>',
            is_comment : 'y',
            is_view : '<?=$is_view?>',
            pa_ca_name : ca_name
        },
        success: function(data) {            
            $('.thread_btn').removeClass('on');
            $('.thread_write').hide();
            $('.thread_write').html('');
            $('.thread_comment').html('');
            $('#thread_comment'+wr_id).html(data);
        }
    });
}

// 이미지 확대
$(document).on('click', '.thread_img', function(e){
    var img_tag = $(this).prop('outerHTML'); 
    if ($(this).parents('.thread_main').hasClass('spoiler')){

    } else {
        $('.thread_img_full').css('display','flex');
        $('.thread_img_full .thread_img_wrap').html(img_tag);
    }
});

$(document).on('click', '.thread_img_close', function(e){
    $('.thread_img_full').css('display','none');
    $('.thread_img_full .thread_img_wrap').html('');
});

$(document).mouseup(function (e){
	var LayerPopup = $(".thread_img_wrap");
	if(LayerPopup.has(e.target).length === 0){
		$('.thread_img_full').css('display','none');
        $('.thread_img_full .thread_img_wrap').html('');
	}
});

$(document).on('click', 'a.blur-txt', function(){ 
    $(this).toggleClass('none-blur');
});

// 게시글 view 주소 복사
$(document).on('click', '.view_link', function(){
    var view_url = $(this).attr('data-url');
    $('#view_copy').val(view_url);
    $('#view_copy').select();

    var banner_url = document.execCommand('copy');
    alert('링크가 복사되었습니다.');
});

<?php 
if ($_like_config == 'all') {
    $like_id = $member['mb_id'] ? $member['mb_id'] : $_SERVER['REMOTE_ADDR'];
} else {
    $like_id = $member['mb_id'];
}
?>

// 추천, 비추천
$(document).on('click', '.bo_v_good', function(){
    var wr_id = $(this).attr('data-wr_id')
    excute_good(wr_id, '<?=$like_id; ?>');
    return false;
});

function excute_good(wr_id, mb_id)
{
    $.ajax({
        url: '<?=$board_skin_url; ?>/good.php',
        type: 'POST',
        data: {
            wr_id: wr_id,
            bo_table : '<?=$bo_table;?>',
            ott_token : <?=json_encode($_SESSION['ott_action_token'])?>
        },
        success: function(data) {
            if (data == 'y') {
                alert('좋아요 하셨습니다.');
                $('.bo_v_good[data-wr_id="'+wr_id+'"]').parents('.wr_good').addClass('is_good');
                var good_cnt = $('.bo_v_good[data-wr_id="'+wr_id+'"] + .wr_good_cnt').text();
                    good_cnt = parseInt(good_cnt);
                    if (!good_cnt) { good_cnt = 0; }
                $('.bo_v_good[data-wr_id="'+wr_id+'"] + .wr_good_cnt').text(good_cnt+1);
            } else {
                alert(data);
            }            
        }
    });
}

// 답글(코멘트) 작성
function openReplyComment(idx) {
    $('.bo_vc_w_'+idx).toggle();
}

function showReplyComment(idx) {
    $('.board-comment-list'+idx).toggle();
}

function fviewreply_submit(f)
{
	var pattern = /(^\s*)|(\s*$)/g; // \s 공백 문자

	var subject = "";
	var content = "";
	$.ajax({
		url: g5_bbs_url+"/ajax.filter.php",
		type: "POST",
		data: {
			"subject": "",
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

	if (content) {
		alert("내용에 금지단어('"+content+"')가 포함되어있습니다");
		f.wr_content.focus();
		return false;
	}

	// 양쪽 공백 없애기
	var pattern = /(^\s*)|(\s*$)/g; // \s 공백 문자
	f.wr_content.value = f.wr_content.value.replace(pattern, "");
	if (char_min > 0 || char_max > 0)
	{
		check_byte('wr_content', 'char_count');
		var cnt = parseInt(document.getElementById('char_count').innerHTML);
		if (char_min > 0 && char_min > cnt)
		{
			alert("댓글은 "+char_min+"글자 이상 쓰셔야 합니다.");
			return false;
		} else if (char_max > 0 && char_max < cnt)
		{
			alert("댓글은 "+char_max+"글자 이하로 쓰셔야 합니다.");
			return false;
		}
	}
	else if (!f.wr_content.value)
	{
		alert("댓글을 입력하여 주십시오.");
		return false;
	}

	if (typeof(f.wr_name) != 'undefined')
	{
		f.wr_name.value = f.wr_name.value.replace(pattern, "");
		if (f.wr_name.value == '')
		{
			alert('이름이 입력되지 않았습니다.');
			f.wr_name.focus();
			return false;
		}
	}

	if (typeof(f.wr_password) != 'undefined')
	{
		f.wr_password.value = f.wr_password.value.replace(pattern, "");
		if (f.wr_password.value == '')
		{
			alert('비밀번호가 입력되지 않았습니다.');
			f.wr_password.focus();
			return false;
		}
	}
 

	set_comment_token(f);

	$('.btn_confirm button').attr('disabled', 'disabled');

	return true;
}

function comment_delete()
{
	return confirm("이 댓글을 삭제하시겠습니까?");
}

var save_before = '';

function comment_box(comment_id, work, wr_id)
{    

	var el_id;
	// 댓글 아이디가 넘어오면 답변, 수정
	if (comment_id)
	{
		if (work == 'r')
			el_id = 'reply_' + comment_id;
		else
			el_id = 'edit_' + comment_id;
	}
	else
		el_id = 'bo_vc_w_' + wr_id;


	if (save_before != el_id)
	{
		if (save_before)
		{
			document.getElementById(save_before).style.display = 'none';
			document.getElementById(save_before).innerHTML = '';
		}

		document.getElementById(el_id).style.display = '';
		document.getElementById(el_id).innerHTML = window['save_html' + wr_id];
		// 댓글 수정
		if (work == 'ru')
		{
            //console.log(document.getElementById('save_comment_' + comment_id + '_' + wr_id).value);
			document.getElementById('wr_content_' + wr_id).value = document.getElementById('save_comment_' + comment_id + '_' + wr_id).value;
			if (typeof char_count != 'undefined')
				check_byte('wr_content', 'char_count');
			if (document.getElementById('secret_comment_'+comment_id + '_' + wr_id).value)
				document.getElementById('co_wr_secret_' + wr_id).checked = true;
			else
				document.getElementById('co_wr_secret_' + wr_id).checked = false;

            if (document.getElementById('wr_1_comment_'+comment_id + '_' + wr_id).value)
				document.getElementById('co_wr_1_' + wr_id).checked = true;
			else
				document.getElementById('co_wr_1_' + wr_id).checked = false;
		}

		document.getElementById('comment_id_' + wr_id).value = comment_id;
		document.getElementById('w_' + wr_id).value = work;
 

		save_before = el_id;
	}
}

function toggleComment(co_id) {
    $('.co-inner'+co_id).toggle();
}
</script>
