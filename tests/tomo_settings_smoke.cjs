/* Destructive settings test: disposable installed site only. */
const {chromium}=require('playwright');
const assert=require('node:assert/strict');
const fs=require('node:fs');
(async()=>{
 const base=process.env.TEST_URL;if(!base||!process.env.TEST_USER||!process.env.TEST_PASSWORD)throw Error('Set disposable TEST_URL, TEST_USER, TEST_PASSWORD.');
 const browser=await chromium.launch({executablePath:process.env.CHROMIUM||'/usr/bin/chromium',args:['--no-sandbox']});
 const context=await browser.newContext({viewport:{width:1440,height:1000}});
 await context.route('**/*',route=>route.request().url().startsWith(base)?route.continue():route.abort());
 const page=await context.newPage();page.setDefaultTimeout(20000);const errors=[];page.on('pageerror',e=>errors.push(e.message));page.on('dialog',d=>d.accept());
 await page.goto(base+'/bbs/login.php');await page.locator('[name=mb_id]').fill(process.env.TEST_USER);await page.locator('[name=mb_password]').fill(process.env.TEST_PASSWORD);await page.locator('button[type=submit]').click();
 async function reveal(locator){const id=await locator.evaluate(el=>el.closest('[role=tabpanel]')?.id);if(id)await page.locator('[role=tab][aria-controls="'+id+'"]').click();}
 async function save(path){const response=page.waitForResponse(r=>r.url().endsWith('/adm/'+path+'_update.php')&&r.request().method()==='POST');await page.locator('input[type=submit]').first().click();const r=await response;const result=await r.json();assert.equal(r.status(),200,JSON.stringify(result));assert.equal(result.saved,true);}
 async function submitModified(path,modify){return page.evaluate(async({path,source})=>{const data=new FormData(document.querySelector('#container form'));data.set('token',window.get_ajax_token());Function('data',source)(data);const r=await fetch(path+'_update.php',{method:'POST',body:data,headers:{'X-Archive-Save':'1'}});return{status:r.status,body:await r.json()};},{path,source:modify});}
 await page.goto(base+'/adm/design_form.php#tomo-fonts');await page.evaluate(()=>document.querySelectorAll('.tomo-font-item').forEach(d=>d.open=true)); // 등록한 웹폰트는 접혀 있으므로 펼친다
 const legacy=await page.locator('[name=cf_add_fonts]').inputValue();
 let row=page.locator('.tomo-font-row').first();
 await row.locator('[data-font-field=label]').fill('테스트 웹폰트');await row.locator('[data-font-field=family]').fill('TomoTestFace');
 await row.locator('[data-font-field=css]').fill('@font-face {font-family:TomoTestFace;src:url("/css/fonts/icomoon.woff") format("woff")}');
 assert.equal(await page.locator('.tomo-font-choice select').count(),3);
 for(const select of await page.locator('.tomo-font-choice select').all()){await reveal(select);await select.selectOption('TomoTestFace');}
 await reveal(page.locator('#tomo-font-add'));await page.locator('#tomo-font-add').click();const unused=page.locator('.tomo-font-row').last();await unused.locator('[data-font-field=label]').fill('미사용 폰트');await unused.locator('[data-font-field=family]').fill('TomoUnused');
 await save('design_form');await page.reload();await page.evaluate(()=>document.querySelectorAll('.tomo-font-item').forEach(d=>d.open=true));
 assert.equal(await page.locator('.tomo-font-row').count(),2);
 for(const select of await page.locator('.tomo-font-choice select').all())assert.equal(await select.inputValue(),'TomoTestFace');
 assert.equal(await page.locator('[name=cf_add_fonts]').inputValue(),legacy);
 await page.locator('.tomo-font-row').first().locator('[data-font-remove]').click();assert.equal(await page.locator('.tomo-font-row').count(),2);assert.match(await page.locator('#tomo-font-status').innerText(),/사용 중/);
 const denied=await submitModified('design_form',"for(const key of [...data.keys()])if(key.startsWith('tomo_fonts['))data.delete(key);");assert.equal(denied.status,422);assert.match(denied.body.error,/사용 중/);
 const missingSlots=await submitModified('design_form',"for(const key of [...data.keys()])if(key.startsWith('tomo_fonts[')||/^cs_(name|value|etc_)/.test(key))data.delete(key);");assert.equal(missingSlots.status,422);
 await page.locator('.tomo-font-row').last().locator('[data-font-remove]').click();await save('design_form');await page.reload();await page.evaluate(()=>document.querySelectorAll('.tomo-font-item').forEach(d=>d.open=true));assert.equal(await page.locator('.tomo-font-row').count(),1);
 const picker=page.locator('.tomo-font-choice').first();await reveal(picker);await picker.locator('select').selectOption('__tomo_custom__');await picker.locator('input').fill('serif');await save('design_form');await page.reload();await page.evaluate(()=>document.querySelectorAll('.tomo-font-item').forEach(d=>d.open=true));assert.equal(await page.locator('.tomo-font-choice input').first().inputValue(),'serif');await reveal(page.locator('.tomo-font-choice select').first());await page.locator('.tomo-font-choice select').first().selectOption('TomoTestFace');await save('design_form');
 const css=await(await context.request.get(base+'/data/css/_design.config.css')).text();assert(css.includes('font-family:TomoTestFace')&&css.includes('/css/fonts/icomoon.woff'));
 await page.goto(base+'/adm/site_config_form.php#tomo-media-settings');assert.equal(await page.locator('#cf_tomo_webp_quality').inputValue(),'95');
 await page.locator('#cf_tomo_webp_quality').fill('73');for(const name of ['context','drag','copy'])await page.locator('[name=tomo_protect_'+name+']').check();
 await save('site_config_form');await page.reload();assert.equal(await page.locator('#cf_tomo_webp_quality').inputValue(),'73');
 const invalid=await submitModified('site_config_form',"data.set('cf_tomo_webp_quality','101')");assert.equal(invalid.status,422);await page.reload();assert.equal(await page.locator('#cf_tomo_webp_quality').inputValue(),'73');
 const forged=await submitModified('site_config_form',"data.set('token','forged');data.set('cf_tomo_webp_quality','21')");assert.notEqual(forged.status,200);
 // Admin pages do not load public restrictions.
 assert.equal(await page.locator('script[data-tomo-protection]').count(),0);
 // Real public page, with test targets added only in the browser DOM.
 const pub=await context.newPage();await pub.goto(base+'/main.php');await pub.waitForLoadState('load');
 assert.equal(await pub.locator('script[data-tomo-protection]').getAttribute('data-tomo-protection'),'7');
 await pub.evaluate(()=>{const div=document.createElement('div');div.id='tomo-test-targets';div.innerHTML='<p id="protected">본문</p><img id="protected-image" src="/img/no_image.png"><input id="editable"><div contenteditable="true" id="editor">편집</div><div data-tomo-allow-copy id="allowed">허용</div>';document.body.append(div);});
 const flags=await pub.evaluate(()=>{const dispatch=(id,type)=>!document.getElementById(id).dispatchEvent(new Event(type,{bubbles:true,cancelable:true}));return{context:dispatch('protected','contextmenu'),drag:dispatch('protected-image','dragstart'),copy:dispatch('protected','copy'),select:dispatch('protected','selectstart'),input:dispatch('editable','copy'),editor:dispatch('editor','contextmenu'),allowed:dispatch('allowed','copy'),selection:getComputedStyle(document.getElementById('editable')).userSelect};});
 assert.deepEqual(flags,{context:true,drag:true,copy:true,select:true,input:false,editor:false,allowed:false,selection:'text'});
 await pub.evaluate(url=>{const iframe=document.createElement('iframe');iframe.id='tomo-frame-check';iframe.src=url;document.body.append(iframe);},base+'/main.php?frame_check=1');
 const frame=page.context().pages().find(p=>p===pub).frameLocator('#tomo-frame-check');
 await frame.locator('html.tomo-no-copy').waitFor();
 assert(await frame.locator('body').evaluate(el=>!el.dispatchEvent(new Event('copy',{bubbles:true,cancelable:true}))));
 await pub.evaluate(()=>document.fonts.load('12px TomoTestFace'));assert(await pub.evaluate(()=>document.fonts.check('12px TomoTestFace')));
 await page.goto(base+'/adm/site_config_form.php#tomo-media-settings');
 // Each flag persists independently and disabled behavior recovers after reload.
 for(const [name,mask]of [['context',1],['drag',2],['copy',4]]){
  for(const n of ['context','drag','copy'])await page.locator('[name=tomo_protect_'+n+']').setChecked(n===name);
  await save('site_config_form');await pub.reload();assert.equal(await pub.locator('script[data-tomo-protection]').getAttribute('data-tomo-protection'),String(mask));
  const state=await pub.evaluate(()=>({context:!document.body.dispatchEvent(new Event('contextmenu',{bubbles:true,cancelable:true})),copy:!document.body.dispatchEvent(new Event('copy',{bubbles:true,cancelable:true}))}));
  assert.equal(state.context,mask===1);assert.equal(state.copy,mask===4);
 }
 for(const n of ['context','drag','copy'])await page.locator('[name=tomo_protect_'+n+']').uncheck();await page.locator('#cf_tomo_webp_quality').fill('95');await save('site_config_form');await pub.reload();assert.equal(await pub.locator('script[data-tomo-protection]').count(),0);await pub.close();
 // Actual PHP pages, not static mockups.
 const widths=[320,390,768,1024,1440];const layouts=[];
 for(const view of ['design_form','site_config_form'])for(const width of widths){await page.setViewportSize({width,height:1000});await page.goto(base+'/adm/'+view+'.php');const actual=await page.evaluate(()=>document.documentElement.scrollWidth);layouts.push({view,width,actual});assert(actual<=width,JSON.stringify(layouts.at(-1)));if(process.env.SCREENSHOT_DIR){fs.mkdirSync(process.env.SCREENSHOT_DIR,{recursive:true});await page.screenshot({path:process.env.SCREENSHOT_DIR+'/'+view+'-'+width+'.png',fullPage:true});}}
 if(process.env.SCREENSHOT_DIR){
  await page.setViewportSize({width:1440,height:1000});await page.goto(base+'/adm/design_form.php#tomo-fonts');await page.locator('#tomo-fonts').screenshot({path:process.env.SCREENSHOT_DIR+'/font-registry-desktop.png'});
  await page.goto(base+'/adm/site_config_form.php#tomo-media-settings');await page.locator('#tomo-media-settings').screenshot({path:process.env.SCREENSHOT_DIR+'/media-settings-desktop.png'});
  await page.setViewportSize({width:390,height:1000});await page.reload();await page.locator('#tomo-media-settings').screenshot({path:process.env.SCREENSHOT_DIR+'/media-settings-mobile.png'});
 }
 if(process.env.SCREENSHOT_DIR)fs.writeFileSync(process.env.SCREENSHOT_DIR+'/layouts.json',JSON.stringify(layouts,null,2));
 assert.deepEqual(errors,[]);console.log('PASS: registered/custom font save and load, deletion guards, legacy CSS, quality setting/validation, CSRF, independent protection, input/editor exceptions, real font loading, actual PHP responsive pages');await browser.close();
})().catch(error=>{console.error(error);process.exit(1)});
