(function () {
'use strict';
const config = window.archiveAdmin;
if (!config) return;
const request = async data => {
  data.set('archive_token',config.token);
  const response = await fetch(config.endpoint,{method:'POST',body:data,credentials:'same-origin'});
  const json = await response.json();
  if (!response.ok || json.error) throw new Error(json.error || '서버 응답을 확인해 주세요.');
  return json;
};
const action = name => {const data=new FormData();data.set('action',name);return data;};
document.querySelectorAll('.btn_confirm a[title]').forEach(a=>{a.querySelectorAll('.material-icons').forEach(x=>x.remove());a.textContent=a.title;});
const toggle=document.getElementById('archive-nav-toggle');
if(toggle)toggle.addEventListener('click',()=>{const opened=document.body.classList.toggle('archive-menu-open');toggle.setAttribute('aria-expanded',String(opened));});
let dirty=false, submitting=false;
const forms=[...document.querySelectorAll('#container form')];
forms.forEach(form=>{
 const status=document.createElement('span');status.className='archive-form-status';status.setAttribute('role','status');status.textContent='현재 저장된 설정';
 const buttons=form.querySelector('.btn_confirm');if(buttons)buttons.prepend(status);
 const mark=()=>{dirty=true;status.textContent='저장하지 않은 변경사항';};
 form.addEventListener('input',mark);form.addEventListener('change',mark);
 // Use one-time legacy token for Enter submissions as well as mouse clicks.
 form.addEventListener('submit',async event=>{
  if(event.defaultPrevented)return;
  // Upgrade only the four reviewed settings handlers; legacy forms keep their original workflow.
  if(!/(site_config_form|design_form|viewer_form|menu_list)_update\.php$/.test(form.action)) {submitting=true;return;}
  event.preventDefault();
  if(form.dataset.archiveSaving==='1')return;
  form.dataset.archiveSaving='1';
  const controls=[...form.querySelectorAll('[type=submit]')];
  try {
   const token=typeof window.get_ajax_token==='function'?window.get_ajax_token():'';
   if(!token)throw new Error('저장 인증을 확인하지 못했습니다. 다시 시도해 주세요.');
   let input=form.querySelector('[name=token]');if(!input){input=document.createElement('input');input.type='hidden';input.name='token';form.append(input);}input.value=token;
   const data=new FormData(form);controls.forEach(x=>x.disabled=true);status.textContent='설정을 저장하고 있습니다.';
   const response=await fetch(form.action,{method:'POST',body:data,credentials:'same-origin',headers:{'X-Archive-Save':'1'}});
   const result=await response.json();
   if(!response.ok||!result.saved)throw new Error(result.error||'저장하지 못했습니다. 입력을 유지했습니다.');
   dirty=false;status.textContent='저장 완료 · 현재 화면을 다시 불러오면 저장된 값을 확인할 수 있습니다.';
  }catch(error){status.textContent=error.message;}
  finally{controls.forEach(x=>x.disabled=false);form.dataset.archiveSaving='0';}
 });
 if(buttons){const reset=document.createElement('button');reset.type='button';reset.textContent='변경 취소';reset.addEventListener('click',()=>{if(!dirty || confirm('저장하지 않은 변경을 취소하고 마지막 저장값을 불러올까요?')){dirty=false;location.reload();}});buttons.append(reset);}
});
window.addEventListener('beforeunload',event=>{if(dirty&&!submitting){event.preventDefault();event.returnValue='';}});
const path=location.pathname;
if(path.endsWith('site_config_form.php')){
 const info=document.createElement('p');info.className='archive-group-help';info.textContent='사이트 전체의 기본 설정입니다. 사이트 공개와 게시글·첨부파일의 열람 권한은 다릅니다. 기존 설정값과 모든 항목을 유지합니다.';forms[0]?.prepend(info);
 const form=forms[0];
 if(form){
  const groups=[['공개·계정','cf_admin'],['사이트 정보','cf_title'],['장식·배경음악','cf_favicon'],['접속 제어','cf_possible_ip']];
  const nav=document.createElement('nav');nav.className='archive-tools';nav.setAttribute('aria-label','환경설정 항목');
  groups.forEach(([title,name],i)=>{const input=form.querySelector('[name="'+name+'"]');const row=input?.closest('tr');if(!row)return;const heading=document.createElement('tr');heading.id='archive-settings-'+i;const cell=document.createElement('th');cell.colSpan=4;cell.textContent=title;heading.append(cell);row.before(heading);const link=document.createElement('a');link.href='#'+heading.id;link.textContent=title;nav.append(link);});
  form.prepend(nav);
 }

}
if(path.endsWith('design_form.php')){
 const tools=document.createElement('div');tools.className='archive-tools';
 const label=document.createElement('label');label.textContent='설정 항목 찾기 ';const input=document.createElement('input');input.type='search';input.placeholder='예: 배경, 로드비, 폰트';input.setAttribute('aria-label','디자인 설정 항목 찾기');label.append(input);tools.append(label);
 const button=document.createElement('button');button.type='button';button.textContent='다음 항목';tools.append(button);forms[0]?.before(tools);
 let index=-1;button.addEventListener('click',()=>{document.querySelectorAll('.archive-search-hit').forEach(x=>x.classList.remove('archive-search-hit'));const q=input.value.trim();if(!q)return;const hits=[...document.querySelectorAll('form th, form h2')].filter(x=>x.textContent.includes(q));if(hits.length){index=(index+1)%hits.length;hits[index].classList.add('archive-search-hit');hits[index].scrollIntoView({block:'center'});}});input.addEventListener('input',()=>{index=-1;});
}
const kind=path.endsWith('design_form.php')?'design':path.endsWith('viewer_form.php')?'main':path.endsWith('menu_list.php')?'menu':null;
if(kind && forms[0]){
 const form=forms[0],layout=document.createElement('div');layout.className='archive-layout';form.before(layout);layout.append(form);
 const panel=document.createElement('section');panel.className='archive-preview';panel.setAttribute('aria-label','저장 전 미리보기');
 panel.innerHTML='<h3>저장 전 미리보기</h3><p>공개 사이트에 반영하지 않습니다. 스크립트·외부 폰트·링크 동작은 제한됩니다.</p><button type="button" data-size="pc">PC 폭</button><button type="button" data-size="mobile">모바일 폭</button><button type="button" data-refresh>미리보기 갱신</button><p role="status" aria-live="polite">아직 미리보기를 갱신하지 않았습니다.</p><iframe sandbox="" referrerpolicy="no-referrer" title="저장 전 편집 미리보기"></iframe>';
 layout.append(panel);panel.id='archive-edit-preview';
 const jump=document.createElement('a');jump.href='#archive-edit-preview';jump.className='archive-preview-jump';jump.textContent='편집 미리보기로 이동';form.querySelector('.btn_confirm')?.append(jump);
 const state=panel.querySelector('[role=status]'), frame=panel.querySelector('iframe'), refresh=panel.querySelector('[data-refresh]');
 panel.querySelectorAll('[data-size]').forEach(button=>button.addEventListener('click',()=>{panel.dataset.width=button.dataset.size;}));
 form.addEventListener('input',()=>{state.textContent='변경사항이 미리보기에 반영되지 않았습니다.';});form.addEventListener('change',()=>{state.textContent='변경사항이 미리보기에 반영되지 않았습니다.';});
 refresh.addEventListener('click',async()=>{
  refresh.disabled=true;state.textContent='미리보기를 갱신하고 있습니다.';
  try{
   if(kind==='main' && window.oEditors?.getById?.main_content) window.oEditors.getById.main_content.exec('UPDATE_CONTENTS_FIELD',[]);
   const data=new FormData(form);data.set('action','preview');data.set('kind',kind);
   // Checkbox array indices must match the form rows (unchecked controls are omitted by FormData).
   if(kind==='menu') {data.delete('me_use[]');form.querySelectorAll('input[name="me_use[]"]').forEach((x,i)=>data.set('me_use['+i+']',x.checked?'1':'0'));}
   const result=await request(data);frame.srcdoc=result.html;
   state.textContent='미리보기 갱신 완료 · '+result.scope+' · 아직 저장되지 않음';
  }catch(error){state.textContent=error.message+' 이전 결과가 표시될 수 있습니다.';}finally{refresh.disabled=false;}
 });
}
const start=document.getElementById('disk-start');
if(start){
 const status=document.getElementById('disk-status'), summary=document.getElementById('disk-summary'), rows=document.getElementById('disk-categories'),pause=document.getElementById('disk-pause'),resume=document.getElementById('disk-resume');
 let paused=false,running=false;
 const bytes=n=>new Intl.NumberFormat('ko-KR',{maximumFractionDigits:2}).format(n/1048576)+' MiB';
 const render=scan=>{if(!scan)return;summary.replaceChildren();[['측정 범위 내 파일',bytes(scan.bytes)],['파일 수',String(scan.files)],['측정 시작',new Date(scan.started*1000).toLocaleString()],['측정 완료',scan.finished?new Date(scan.finished*1000).toLocaleString():'진행 중'],['범위 누락/건너뛴 링크',scan.errors+' / '+scan.skipped_links],['계정 전체 잔여량','미확인 · 호스팅 관리에서 대조하세요']].forEach(([label,value])=>{const dt=document.createElement('dt'),dd=document.createElement('dd');dt.textContent=label;dd.textContent=value;summary.append(dt,dd);});rows.replaceChildren();Object.entries(scan.categories).forEach(([label,value])=>{const row=document.createElement('tr');[label,bytes(value)].forEach(value=>{const cell=document.createElement('td');cell.textContent=value;row.append(cell);});rows.append(row);});status.textContent=scan.finished?(scan.errors?'부분 측정 완료 · 읽지 못한 범위가 있습니다.':'측정 완료 · 계정 전체 사용량이 아닐 수 있습니다.'):'측정 중 · 파일이 변경되는 시점에 따라 차이가 생길 수 있습니다.';};
 const run=async name=>{if(running)return;running=true;start.disabled=true;pause.hidden=false;resume.hidden=true;try{let result=await request(action(name));render(result.scan);while(result.scan&&!result.scan.finished&&!paused){await new Promise(r=>setTimeout(r,180));result=await request(action('disk_step'));render(result.scan);}if(paused){status.textContent='일시 정지됨 · 이어서 측정할 수 있습니다.';resume.hidden=false;}}catch(error){status.textContent=error.message;resume.hidden=false;}finally{running=false;start.disabled=false;pause.hidden=true;}};
 start.addEventListener('click',()=>{paused=false;run('disk_start');});pause.addEventListener('click',()=>{paused=true;});resume.addEventListener('click',()=>{paused=false;run('disk_step');});
 request(action('disk_status')).then(result=>{render(result.scan);if(result.scan&&!result.scan.finished)resume.hidden=false;}).catch(error=>status.textContent=error.message);
}
})();
