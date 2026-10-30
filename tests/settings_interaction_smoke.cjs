/* Disposable site only: exercises repeated saves, in-flight edits and distinct uploaded assets. */
const {chromium}=require('playwright');const assert=require('node:assert/strict');const fs=require('fs');const crypto=require('crypto');
(async()=>{
 const base=process.env.TEST_URL;if(!base||!process.env.TEST_USER||!process.env.TEST_PASSWORD||!process.env.TEST_IMAGE)throw Error('Set disposable site credentials and TEST_IMAGE.');
 const b=await chromium.launch({executablePath:process.env.CHROMIUM||'/usr/bin/chromium',args:['--no-sandbox']});const c=await b.newContext();await c.route('**/*',r=>r.request().url().startsWith(base)?r.continue():r.abort());
 const p=await c.newPage();p.setDefaultTimeout(15000);p.on('dialog',d=>d.accept());await p.goto(base+'/bbs/login.php');await p.locator('[name=mb_id]').fill(process.env.TEST_USER);await p.locator('[name=mb_password]').fill(process.env.TEST_PASSWORD);await p.locator('button[type=submit]').click();
 async function save(path){const done=p.waitForResponse(r=>r.url().endsWith('/'+path+'_update.php')&&r.request().method()==='POST');await p.locator('input[type=submit]').first().click();const response=await done;const json=await response.json();assert.equal(response.status(),200,JSON.stringify(json));assert.equal(json.saved,true);await p.waitForFunction(()=>document.querySelector('#container form').dataset.archiveSaving==='0');return json;}
 const results=[];
 // Keep the rest of the registry intact and exercise a new row without reloading after its first save.
 await p.goto(base+'/adm/design_form.php#tomo-fonts');await p.locator('#tomo-font-add').click();const row=p.locator('.tomo-font-row').last();const family='InteractionFace'+Date.now();await row.locator('[data-font-field=label]').fill('연속 저장 검사');await row.locator('[data-font-field=family]').fill(family);await p.locator('[role=tab][aria-controls=anc_001]').click();await p.locator('.tomo-font-choice select').first().selectOption(family);await p.locator('[role=tab][aria-controls=tomo-fonts]').click();
 await save('design_form');const id=await row.locator('[data-font-field=id]').inputValue();await row.locator('[data-font-field=label]').fill('연속 저장 검사 수정');
 try {await save('design_form');assert.match(id,/^[a-f0-9]{16}$/);assert.equal(await row.locator('[data-font-field=id]').inputValue(),id);results.push('PASS repeated new-font save keeps ID');}catch(e){results.push('FAIL repeated new-font save: '+e.message);}
 // A design image path is also returned to its legacy named input before a second save.
 const designFile=p.locator('input[type=file][name^=cs_value_file]').first();const fileName=await designFile.getAttribute('name');const index=fileName.match(/\[(\d+)\]/)[1];const valueName='cs_value['+index+']';
 const panel=await designFile.evaluate(el=>el.closest('[role=tabpanel]').id);await p.locator('[role=tab][aria-controls="'+panel+'"]').click();await p.locator('[name="'+valueName+'"]').fill('');await designFile.setInputFiles(process.env.TEST_IMAGE);await save('design_form');const designUrl=await p.locator('[name="'+valueName+'"]').inputValue();assert(designUrl.includes('/data/site/design_'));assert.equal(await designFile.evaluate(el=>el.files.length),0);await save('design_form');await p.reload();assert.equal(await p.locator('[name="'+valueName+'"]').inputValue(),designUrl);results.push('PASS design upload path retained across repeated saves');
 // A response for an earlier revision must not claim that an edit made while awaiting it was saved.
 await p.goto(base+'/adm/site_config_form.php');const old=await p.locator('[name=cf_title]').inputValue();let release,entered;const enteredPromise=new Promise(r=>entered=r);
 await p.route('**/site_config_form_update.php',async route=>{const response=await route.fetch();entered();await new Promise(r=>release=r);await route.fulfill({response});});
 await p.locator('[name=cf_title]').fill(old+' first');const pending=save('site_config_form');await enteredPromise;await p.locator('[name=cf_title]').fill(old+' second');release();await pending;
 assert(await p.evaluate(()=>!window.dispatchEvent(new Event('beforeunload',{cancelable:true}))), 'pending edits retain navigation warning');
 const state=await p.locator('.archive-form-status').innerText();if(state.includes('저장하지 않은'))results.push('PASS in-flight edit remains dirty');else results.push('FAIL in-flight edit reported saved');
 await p.unroute('**/site_config_form_update.php');await save('site_config_form');await p.reload();assert.equal(await p.locator('[name=cf_title]').inputValue(),old+' second');await p.locator('[name=cf_title]').fill(old);await save('site_config_form');
 // Different formats are not needed: two PNGs must retain different contents at different URLs.
 const png=fs.readFileSync(process.env.TEST_IMAGE);const cursor=Buffer.concat([png,Buffer.from('cursor-fixture')]);
 await p.locator('[name=cf_favicon_file]').setInputFiles({name:'favicon.png',mimeType:'image/png',buffer:png});await p.locator('[name=cf_cursor_file]').setInputFiles({name:'cursor.png',mimeType:'image/png',buffer:cursor});
 await save('site_config_form');await p.reload();const favicon=await p.locator('[name=cf_favicon]').inputValue(),cursorUrl=await p.locator('[name=cf_cursor]').inputValue();
 if(favicon===cursorUrl)results.push('FAIL favicon and cursor share a URL');else{const hash=x=>crypto.createHash('sha256').update(x).digest('hex');assert.equal(hash(await(await c.request.get(favicon)).body()),hash(png));assert.equal(hash(await(await c.request.get(cursorUrl)).body()),hash(cursor));results.push('PASS favicon and cursor stored separately');}
 // Upload again and save without reloading, so stale URL fields cannot erase a newly uploaded asset.
 const expected=base+'/data/site/site_favicon.png';await p.locator('[name=cf_favicon]').fill('');await p.locator('[name=cf_favicon_file]').setInputFiles({name:'favicon.png',mimeType:'image/png',buffer:png});await save('site_config_form');
 if(await p.locator('[name=cf_favicon]').inputValue()!==expected)results.push('FAIL saved upload URL not synchronized');else results.push('PASS saved upload URL synchronized');
 // Clearing a successfully submitted file simulates the next save without an upload; the URL must stay.
 await p.locator('[name=cf_favicon_file]').setInputFiles([]);await save('site_config_form');await p.reload();assert.equal(await p.locator('[name=cf_favicon]').inputValue(),expected,'uploaded asset retained on subsequent settings save');
 // A newly selected file and manually edited URL during upload must not be cleared by the old response.
 let releaseUpload,enteredUpload;const uploadEntered=new Promise(r=>enteredUpload=r);
 await p.route('**/site_config_form_update.php',async route=>{const response=await route.fetch();enteredUpload();await new Promise(r=>releaseUpload=r);await route.fulfill({response});});
 await p.locator('[name=cf_favicon_file]').setInputFiles({name:'first.png',mimeType:'image/png',buffer:png});const uploadPending=save('site_config_form');await uploadEntered;
 await p.locator('[name=cf_favicon]').fill('/pending-user-choice.png');await p.locator('[name=cf_favicon_file]').setInputFiles({name:'next.png',mimeType:'image/png',buffer:cursor});releaseUpload();await uploadPending;
 assert.equal(await p.locator('[name=cf_favicon]').inputValue(),'/pending-user-choice.png');assert.equal(await p.locator('[name=cf_favicon_file]').evaluate(el=>el.files[0]?.name),'next.png');assert.match(await p.locator('.archive-form-status').innerText(),/저장하지 않은/);
 await p.unroute('**/site_config_form_update.php');await save('site_config_form');assert.equal(await p.locator('[name=cf_favicon_file]').evaluate(el=>el.files.length),0);results.push('PASS in-flight file and URL edits preserved until next save');
 console.log(results.join('\n'));await b.close();if(results.some(x=>x.startsWith('FAIL')))process.exit(1);
})().catch(e=>{console.error(e);process.exit(1)});
