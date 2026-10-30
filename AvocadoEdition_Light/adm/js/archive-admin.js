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
const dirtyForms=new Set();
let submitting=false;
const forms=[...document.querySelectorAll('#container form:not([data-archive-no-settings])')];
forms.forEach(form=>{
 const status=document.createElement('span');status.className='archive-form-status';status.setAttribute('role','status');status.textContent='현재 저장된 설정';
 const buttons=form.querySelector('.btn_confirm');if(buttons)buttons.prepend(status);
 let revision=0;
 const mark=()=>{revision++;dirtyForms.add(form);status.textContent='저장하지 않은 변경사항';};
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
   const submittedRevision=revision;
   const uploaded=[...form.querySelectorAll('input[type=file]')].map(input=>({input,files:[...input.files]}));
   const data=new FormData(form);controls.forEach(x=>x.disabled=true);status.textContent='설정을 저장하고 있습니다.';
   const response=await fetch(form.action,{method:'POST',body:data,credentials:'same-origin',headers:{'X-Archive-Save':'1'}});
   const result=await response.json();
   if(!response.ok||!result.saved)throw new Error(result.error||'저장하지 못했습니다. 입력을 유지했습니다.');
   // Apply only fields that have not changed since this request's snapshot.
   Object.entries(result.fields||{}).forEach(([name,value])=>{
    const input=form.elements.namedItem(name);
    if(input&&'value' in input&&input.value===String(data.get(name)??''))input.value=String(value);
   });
   uploaded.forEach(({input,files})=>{
    const current=[...input.files];
    if((result.clear_files||[]).includes(input.name)&&current.length===files.length&&current.every((file,i)=>file===files[i]))input.value='';
   });
   if(revision===submittedRevision){dirtyForms.delete(form);status.textContent='저장 완료 · 현재 화면을 다시 불러오면 저장된 값을 확인할 수 있습니다.';}
   else{dirtyForms.add(form);status.textContent='저장 완료 · 저장하지 않은 변경사항이 남아 있습니다.';}
  }catch(error){status.textContent=error.message;}
  finally{controls.forEach(x=>x.disabled=false);form.dataset.archiveSaving='0';}
 });
 if(buttons){const reset=document.createElement('button');reset.type='button';reset.textContent='변경 취소';reset.addEventListener('click',()=>{if(!dirtyForms.size || confirm('저장하지 않은 변경을 취소하고 마지막 저장값을 불러올까요?')){dirtyForms.clear();location.reload();}});buttons.append(reset);}
});
window.addEventListener('beforeunload',event=>{if(dirtyForms.size&&!submitting){event.preventDefault();event.returnValue='';}});

// Turn the legacy repeated anchor menus into one actual tab list per form.
forms.forEach(form=>{
 const anchors=[...form.querySelectorAll('.anchor')];if(!anchors.length)return;
 const pairs=[...anchors[0].querySelectorAll('a[href^="#"]')].map(link=>({label:link.textContent,id:link.hash.slice(1)}));
 form.querySelectorAll('section.tomo-settings[id]').forEach(panel=>pairs.push({label:panel.querySelector('h2')?.textContent||'추가 설정',id:panel.id}));
 const tabs=pairs.map(item=>({...item,panel:document.getElementById(item.id)})).filter(item=>item.panel&&form.contains(item.panel)&&item.panel.style.display!=='none');
 if(!tabs.length)return;
 anchors.forEach(anchor=>anchor.remove());
 const nav=document.createElement('div');nav.className='archive-tabs';nav.setAttribute('role','tablist');nav.setAttribute('aria-label','설정 항목');
 const select=(item,focus=false)=>{
  tabs.forEach(tab=>{const active=tab===item;tab.panel.hidden=!active;tab.button.setAttribute('aria-selected',String(active));tab.button.tabIndex=active?0:-1;});
  if(focus)item.button.focus();
 };
 tabs.forEach((item,index)=>{
  const button=document.createElement('button');button.type='button';button.id='tab-'+item.id;button.textContent=item.label;button.setAttribute('role','tab');button.setAttribute('aria-controls',item.id);item.button=button;
  item.panel.setAttribute('role','tabpanel');item.panel.setAttribute('aria-labelledby',button.id);
  button.addEventListener('click',()=>{select(item);history.replaceState(null,'','#'+item.id);});
  button.addEventListener('keydown',event=>{let next;if(event.key==='ArrowRight')next=(index+1)%tabs.length;if(event.key==='ArrowLeft')next=(index+tabs.length-1)%tabs.length;if(event.key==='Home')next=0;if(event.key==='End')next=tabs.length-1;if(next!==undefined){event.preventDefault();select(tabs[next],true);history.replaceState(null,'','#'+tabs[next].id);}});
  item.panel.addEventListener('archive:show',()=>select(item));nav.append(button);
 });
 const panels=tabs.map(tab=>tab.panel);[...form.children].find(node=>panels.includes(node))?.before(nav);
 select(tabs.find(tab=>tab.id===location.hash.slice(1))||tabs[0]);
 window.addEventListener('hashchange',()=>{const tab=tabs.find(tab=>tab.id===location.hash.slice(1));if(tab)select(tab);});
 form.addEventListener('invalid',event=>{const tab=tabs.find(tab=>tab.panel.contains(event.target));if(tab)select(tab);},true);
});

const path=location.pathname;
if(path.endsWith('design_form.php')){
 const tools=document.createElement('div');tools.className='archive-tools';
 const label=document.createElement('label');label.textContent='설정 항목 찾기 ';const input=document.createElement('input');input.type='search';input.placeholder='예: 배경, 로드비, 폰트';input.setAttribute('aria-label','디자인 설정 항목 찾기');label.append(input);tools.append(label);
 const button=document.createElement('button');button.type='button';button.textContent='다음 항목';tools.append(button);forms[0]?.before(tools);
 let index=-1;button.addEventListener('click',()=>{document.querySelectorAll('.archive-search-hit').forEach(x=>x.classList.remove('archive-search-hit'));const q=input.value.trim();if(!q)return;const hits=[...document.querySelectorAll('form th, form h2')].filter(x=>x.textContent.includes(q));if(hits.length){index=(index+1)%hits.length;hits[index].closest('[role=tabpanel]')?.dispatchEvent(new Event('archive:show'));hits[index].classList.add('archive-search-hit');hits[index].scrollIntoView({block:'center'});}});input.addEventListener('input',()=>{index=-1;});
}
const start=document.getElementById('disk-start');
if(start){
 const status=document.getElementById('disk-status'),summary=document.getElementById('disk-summary'),rows=document.getElementById('disk-categories'),pause=document.getElementById('disk-pause'),resume=document.getElementById('disk-resume');
 const quota=Number(document.querySelector('.archive-disk').dataset.quotaBytes);
 let paused=false,running=false;
 const number=n=>new Intl.NumberFormat('ko-KR',{maximumFractionDigits:1}).format(n),bytes=n=>number(n/1000000)+' MB';
 const render=scan=>{
  if(!scan)return;
  const known=Number.isFinite(scan.db_bytes),total=scan.bytes,percent=total/quota*100;
  document.getElementById('disk-used').textContent=bytes(total);document.getElementById('disk-percent').textContent=number(percent)+'%';
  const meter=document.getElementById('disk-meter');meter.setAttribute('aria-valuenow',String(Math.min(percent,100)));meter.setAttribute('aria-valuetext',bytes(total)+' / 500 MB'+(scan.finished?'':' · 측정 중'));meter.classList.toggle('is-warning',percent>=80);meter.classList.toggle('is-full',percent>=100);
  document.getElementById('disk-fill').style.width=Math.min(percent,100)+'%';
  document.getElementById('disk-remaining').textContent=(scan.finished?'측정 범위 기준 ':'측정 중 · ')+(total>quota?bytes(total-quota)+' 초과':bytes(quota-total)+' 여유')+(known?'':' · DB 크기 미확인');
  document.getElementById('disk-file-bytes').textContent=bytes(scan.bytes);document.getElementById('disk-db-bytes').textContent=known?bytes(scan.db_bytes):'확인 불가';
  summary.replaceChildren();[['파일 수',number(scan.files)+'개'],['측정 시작',new Date(scan.started*1000).toLocaleString()],['측정 완료',scan.finished?new Date(scan.finished*1000).toLocaleString():'진행 중'],['읽지 못한 항목',scan.errors+'개'],['제외한 링크',scan.skipped_links+'개']].forEach(([label,value])=>{const dt=document.createElement('dt'),dd=document.createElement('dd');dt.textContent=label;dd.textContent=value;summary.append(dt,dd);});
  rows.replaceChildren();Object.entries(scan.categories).sort((a,b)=>b[1]-a[1]).forEach(([label,value])=>{const row=document.createElement('tr');[label,bytes(value)].forEach(value=>{const cell=document.createElement('td');cell.textContent=value;row.append(cell);});rows.append(row);});
  status.textContent=scan.finished?(scan.errors?'일부 항목을 읽지 못했습니다. 표시된 값은 부분 측정값입니다.':'측정 완료 · 실제 계정 사용량은 닷홈 관리 화면에서 확인할 수 있습니다.'):'사용량 측정 중…';
 };
 const run=async name=>{if(running)return;running=true;start.disabled=true;pause.hidden=false;resume.hidden=true;try{let result=await request(action(name));render(result.scan);while(result.scan&&!result.scan.finished&&!paused){await new Promise(r=>setTimeout(r,180));result=await request(action('disk_step'));render(result.scan);}if(paused){status.textContent='일시 정지됨';resume.hidden=false;}}catch(error){status.textContent=error.message;resume.hidden=false;}finally{running=false;start.disabled=false;pause.hidden=true;}};
 start.addEventListener('click',()=>{paused=false;run('disk_start');});pause.addEventListener('click',()=>{paused=true;});resume.addEventListener('click',()=>{paused=false;run('disk_step');});
 request(action('disk_status')).then(result=>{if(!result.scan){run('disk_start');return;}render(result.scan);if(!result.scan.finished)resume.hidden=false;}).catch(error=>status.textContent=error.message);
}
})();
