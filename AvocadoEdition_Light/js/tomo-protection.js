(function () {
'use strict';
const script=document.currentScript;
const mask=Number(script?.dataset.tomoProtection||0)&7;
if(!mask)return;
const element=target=>target instanceof Element?target:target?.parentElement;
const editable=target=>{
 const el=element(target);
 if(!el)return true;
 if(el.closest('img'))return !!el.closest('[data-tomo-allow-copy],[contenteditable=true]');
 return el.isContentEditable||!!el.closest('input,textarea,select,button,[data-tomo-allow-copy]');
};
if(mask&1)document.addEventListener('contextmenu',event=>{if(!editable(event.target))event.preventDefault();});
if(mask&2){
 document.documentElement.classList.add('tomo-no-image-drag');
 document.addEventListener('dragstart',event=>{if(!editable(event.target)&&element(event.target)?.closest('img'))event.preventDefault();});
}
if(mask&4){
 document.documentElement.classList.add('tomo-no-copy');
 for(const type of ['selectstart','copy','cut'])document.addEventListener(type,event=>{if(!editable(event.target))event.preventDefault();});
}
})();
