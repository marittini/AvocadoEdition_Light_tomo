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
container.addEventListener('input',sync);
container.addEventListener('click',event=>{
 const button=event.target.closest('[data-font-remove]');if(!button)return;
 const row=button.closest('.tomo-font-row'),font=fields(row);
 if(font.family&&choices.some(wrapper=>familyNames(wrapper.querySelector('input').value).includes(familyNames(font.family)[0]))){
  status.textContent='사용 중인 폰트입니다. 적용 글꼴을 먼저 변경한 뒤 삭제해 주세요.';return;
 }
 row.remove();sync();status.textContent='등록 목록에서 삭제했습니다. 저장하면 반영됩니다.';
 container.dispatchEvent(new Event('input',{bubbles:true}));
});
document.getElementById('tomo-font-add').addEventListener('click',()=>{
 const row=document.createElement('fieldset');row.className='tomo-font-row';
 const legend=document.createElement('legend');legend.textContent='웹폰트';row.append(legend);
 for(const [key,label] of [['id',''],['label','표시 이름'],['family','font-family'],['css','웹폰트 CSS']]){
  const input=document.createElement(key==='css'?'textarea':'input');
  input.name=`tomo_fonts[${nextIndex}][${key}]`;input.dataset.fontField=key;
  if(key==='id'){input.type='hidden';row.append(input);continue;}
  if(key==='label')input.maxLength=80;if(key==='family')input.maxLength=240;
  const caption=document.createElement('label');caption.append(document.createTextNode(label+' '),input);row.append(caption);
 }
 const remove=document.createElement('button');remove.type='button';remove.dataset.fontRemove='';remove.textContent='등록 삭제';row.append(remove);
 nextIndex++;container.append(row);row.querySelector('[data-font-field=label]').focus();
 container.dispatchEvent(new Event('input',{bubbles:true}));
});
sync();
})();
