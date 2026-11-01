(function () {
'use strict';
const container=document.getElementById('tomo-font-rows');
if(!container)return;
const choices=[...document.querySelectorAll('.tomo-font-choice')];
const status=document.getElementById('tomo-font-status');
let nextIndex=container.children.length;
const fields=row=>Object.fromEntries([...row.querySelectorAll('[data-font-field]')].map(el=>[el.dataset.fontField,el.value.trim()]));
const fonts=()=>[...container.querySelectorAll('.tomo-font-row')].map(fields).filter(x=>x.label&&x.family);
const familyNames=value=>{
 const names=[];let token='',quote='';
 for(let i=0;i<value.length;i++){
  const c=value[i];
  if(c==='\\'&&i+1<value.length){token+=c+value[++i];continue;}
  if(quote){token+=c;if(c===quote)quote='';continue;}
  if(c==='"'||c==="'"){quote=c;token+=c;continue;}
  if(c===','){names.push(token.trim().replace(/^["']|["']$/g,'').toLowerCase());token='';continue;}
  token+=c;
 }
 names.push(token.trim().replace(/^["']|["']$/g,'').toLowerCase());return names;
};
const custom='__tomo_custom__';
function sync(){
 const registered=fonts();
 choices.forEach(wrapper=>{
  const input=wrapper.querySelector('input'),select=wrapper.querySelector('select');
  const value=input.value;
  select.replaceChildren(new Option('기본값 / 상속',''));
  registered.forEach(font=>select.add(new Option(font.label,font.family)));
  if(value&&!registered.some(font=>font.family===value))select.add(new Option('기존 사용자 설정: '+value,value));
  select.add(new Option('직접 입력',custom));
  select.value=value;select.hidden=false;
  input.hidden=!!value&&registered.some(font=>font.family===value);
 });
}
choices.forEach(wrapper=>{
 const input=wrapper.querySelector('input'),select=wrapper.querySelector('select');
 select.addEventListener('change',()=>{
  if(select.value===custom){input.hidden=false;input.focus();return;}
  input.value=select.value;input.hidden=select.value!=='';input.dispatchEvent(new Event('input',{bubbles:true}));
 });
});
container.addEventListener('input',()=>{sync();summaries();});
container.addEventListener('click',event=>{
 const move=event.target.closest('[data-font-move]');
 if(move){ // 순서 바꾸기: 요약 줄 안의 버튼이라 펼침/접힘은 막는다
  event.preventDefault();
  const item=move.closest('.tomo-font-item'),step=Number(move.dataset.fontMove);
  const target=step<0?item.previousElementSibling:item.nextElementSibling;
  if(target){step<0?target.before(item):target.after(item);move.focus();
   const label=fields(item).label||'웹폰트';status.textContent=`${label}을(를) ${[...container.children].indexOf(item)+1}번째로 옮겼습니다. 저장하면 반영됩니다.`;}
  summaries();sync();return;
 }
 const button=event.target.closest('[data-font-remove]');if(!button)return;
 const row=button.closest('.tomo-font-row'),font=fields(row);
 if(font.family&&choices.some(wrapper=>familyNames(wrapper.querySelector('input').value).includes(familyNames(font.family)[0]))){
  status.textContent='사용 중인 폰트입니다. 적용 글꼴을 먼저 변경한 뒤 삭제해 주세요.';return;
 }
 (row.closest('.tomo-font-item')||row).remove();sync();summaries();status.textContent='등록 목록에서 삭제했습니다. 저장하면 반영됩니다.';
 container.dispatchEvent(new Event('input',{bubbles:true}));
});
document.getElementById('tomo-font-add').addEventListener('click',()=>{
 const item=document.createElement('details');item.className='tomo-font-item';item.open=true;
 item.innerHTML='<summary><span class="tomo-font-sum-label">새 웹폰트</span><span class="tomo-font-sum-family"></span><span class="tomo-font-sum-sample" aria-hidden="true">가나다 Aa 123</span><span class="tomo-font-used" hidden>사용 중</span><span class="tomo-font-move"><button type="button" data-font-move="-1" aria-label="위로"><span class="material-icons" aria-hidden="true">arrow_upward</span></button><button type="button" data-font-move="1" aria-label="아래로"><span class="material-icons" aria-hidden="true">arrow_downward</span></button></span></summary>';
 const row=document.createElement('fieldset');row.className='tomo-font-row';item.append(row);
 const legend=document.createElement('legend');legend.textContent='웹폰트';row.append(legend);
 for(const [key,label] of [['id',''],['label','표시 이름'],['family','font-family'],['css','웹폰트 CSS']]){
  const input=document.createElement(key==='css'?'textarea':'input');
  input.name=`tomo_fonts[${nextIndex}][${key}]`;input.dataset.fontField=key;
  if(key==='id'){input.type='hidden';row.append(input);continue;}
  if(key==='label')input.maxLength=80;if(key==='family')input.maxLength=240;
  const caption=document.createElement('label');caption.append(document.createTextNode(label+' '),input);row.append(caption);
 }
 const remove=document.createElement('button');remove.type='button';remove.dataset.fontRemove='';remove.textContent='등록 삭제';row.append(remove);
 nextIndex++;container.append(item);row.querySelector('[data-font-field=label]').focus();
 if(filter)filter.value='';
 container.dispatchEvent(new Event('input',{bubbles:true}));
});

// 요약 줄: 이름 · font-family · 미리보기 · 사용 중 표시, 위아래 끝 버튼 끄기, 개수
const filter=document.getElementById('tomo-font-filter'),tools=document.querySelector('.tomo-font-tools'),count=document.getElementById('tomo-font-count'),expand=document.getElementById('tomo-font-expand');
function summaries(){
 const used=choices.map(w=>familyNames(w.querySelector('input').value)[0]).filter(Boolean);
 const items=[...container.querySelectorAll('.tomo-font-item')];
 items.forEach((item,i)=>{
  const f=fields(item);
  item.querySelector('.tomo-font-sum-label').textContent=f.label||'새 웹폰트';
  item.querySelector('.tomo-font-sum-family').textContent=f.family;
  const sample=item.querySelector('.tomo-font-sum-sample');sample.style.fontFamily=f.family?`${f.family}, sans-serif`:'';
  item.querySelector('.tomo-font-used').hidden=!(f.family&&used.includes(familyNames(f.family)[0]));
  const [up,down]=item.querySelectorAll('[data-font-move]');up.disabled=i===0;down.disabled=i===items.length-1;
 });
 const n=fonts().length;
 if(count)count.textContent=`등록한 웹폰트 ${n}개`;
 if(tools)tools.hidden=items.length<2;
 applyFilter();
}
function applyFilter(){
 if(!filter)return;
 const q=filter.value.trim().toLowerCase();
 container.querySelectorAll('.tomo-font-item').forEach(item=>{
  const f=fields(item);
  item.hidden=!!q&&!(`${f.label} ${f.family}`.toLowerCase().includes(q));
 });
}
if(filter)filter.addEventListener('input',applyFilter);
if(expand)expand.addEventListener('click',()=>{
 const items=[...container.querySelectorAll('.tomo-font-item')],open=!items.every(i=>i.open);
 items.forEach(i=>{i.open=open;});expand.textContent=open?'모두 접기':'모두 펼치기';
});
choices.forEach(w=>w.addEventListener('change',summaries));
sync();summaries();
})();
