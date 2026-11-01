<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가 
?>
<script>
var prefiles = <?=json_encode($prefiles ?? [], JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
var imageFiles = prefiles.map(x => ({ existing:true, idx:Number(x.idx), name:x.name, url:x.url }));
var removedFiles = [];
var ottCover = imageFiles.find(x => 'ottcover:'+x.idx === document.getElementById('ott-cover-slot').value) || null;
var maxImages = Math.min(4, <?=max(0,(int)($board['bo_upload_count'] ?? 0))?>);
function ottRenderFiles() {
    const preview = document.getElementById('preview');
    if (!preview) return;
    preview.replaceChildren();
    imageFiles.forEach((entry,index) => {
        const box=document.createElement('div'); box.className='preview-container';
        const image=document.createElement('img'); image.src=entry.url; image.className='preview-img'; image.alt=entry.name || '첨부 이미지';
        const remove=document.createElement('button'); remove.type='button'; remove.className='remove-btn'; remove.innerHTML='<i class="fa-solid fa-trash-can" aria-hidden="true"></i>'; remove.title='제거'; remove.setAttribute('aria-label','첨부 이미지 제거');
        remove.addEventListener('click', () => { if (!confirm('이미지 첨부를 제거하시겠습니까? 저장 전에는 서버 파일이 삭제되지 않습니다.')) return; const [item]=imageFiles.splice(index,1); if(item.existing) removedFiles.push(item.idx); else URL.revokeObjectURL(item.url); ottRenderFiles(); });
        const choose=document.createElement('button'); choose.type='button'; choose.className='ui-btn'; choose.textContent=ottCover===entry?'대표 이미지':'썸네일로 지정'; choose.addEventListener('click',()=>{ottCover=entry;ottRenderFiles();});
        box.append(image,remove,choose);preview.append(box);
    });
    if (window.ottThumbRefresh) window.ottThumbRefresh(); // 목록 썸네일 미리보기 · 보일 부분
}
function ottAddFiles(files) {
    for (const file of files) {
        if (imageFiles.length>=maxImages) { alert('게시판의 첨부 가능 개수를 초과했습니다.'); break; }
        if (!/^image\/(png|jpeg|gif|webp)$/.test(file.type)) { alert('JPG, PNG, GIF, WebP 이미지만 첨부할 수 있습니다.'); continue; }
        imageFiles.push({existing:false,file,name:file.name,url:URL.createObjectURL(file)});
    }
    ottRenderFiles();
}
function ottPrepareFiles(form) {
    form.querySelectorAll('[data-ott-generated]').forEach(x=>x.remove());
    let next=Math.max(-1,...prefiles.map(x=>Number(x.idx)))+1;
    imageFiles.filter(x=>!x.existing).forEach(x=>x.slot=next++);
    const coverInput=document.getElementById('ott-cover-slot');
    if(ottCover && imageFiles.includes(ottCover)) coverInput.value='ottcover:'+(ottCover.existing?ottCover.idx:ottCover.slot);
    else if(coverInput.value.startsWith('ottcover:')) coverInput.value='';
    if (next>100) { alert('첨부 가능한 파일 번호를 초과했습니다.'); return false; }
    for(let i=0;i<next;i++) {
        const input=document.createElement('input');input.type='file';input.name='bf_file[]';input.hidden=true;input.dataset.ottGenerated='1';
        const entry=imageFiles.find(x=>!x.existing && x.slot===i);
        if(entry) { try {const transfer=new DataTransfer();transfer.items.add(entry.file);input.files=transfer.files;} catch (_) {alert('브라우저가 여러 이미지 첨부를 지원하지 않습니다.');return false;} }
        form.append(input);
        if(removedFiles.includes(i)){const del=document.createElement('input');del.type='hidden';del.name='bf_file_del['+i+']';del.value='1';del.dataset.ottGenerated='1';form.append(del);}
    }
    return true;
}
$(function() {
    ottRenderFiles();
    $(document).off('.ottwrite');
    $(document).on('click.ottwrite','#upload-btn',()=>$('#image-input').trigger('click'));
    $(document).on('change.ottwrite','#image-input',function(){ottAddFiles(Array.from(this.files));this.value='';});
    $(document).on('paste.ottwrite','#wr_content',function(e){const data=e.originalEvent.clipboardData;if(data) ottAddFiles(Array.from(data.items).filter(x=>x.type.startsWith('image/')).map(x=>x.getAsFile()).filter(Boolean));});
    $(document).on('dragover.ottwrite','#dropzone',function(e){e.preventDefault();$(this).addClass('dragover');});
    $(document).on('dragleave.ottwrite','#dropzone',function(){$(this).removeClass('dragover');});
    $(document).on('drop.ottwrite','#dropzone',function(e){e.preventDefault();$(this).removeClass('dragover');ottAddFiles(Array.from(e.originalEvent.dataTransfer.files));});
});
function fwrite_submit(f)
{
    <?php echo get_editor_js('wr_content', $is_dhtml_editor); ?>
    if (!ottPrepareFiles(f)) return false;

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


    document.getElementById("btn_submit").disabled = "disabled";

    return true;
}	
$(document).off('change.ottvisibility', '#set_secret').on('change.ottvisibility', '#set_secret', function() {
    var selection = $(this).val();
    if(selection=='protect') $('#set_protect').css('display','block');
    else {$('#set_protect').css('display','none'); $('#wr_protect').val('');}
}); 

function autoResize(textarea) {
    textarea.style.height = 'auto' // 높이를 자동으로 초기화
    textarea.style.height = textarea.scrollHeight + 'px' // 스크롤 높이에 맞게 높이 설정
}
</script>
