/* Disposable site only; TEST_EDITOR selects the already configured editor. */
const {chromium}=require('playwright');const fs=require('fs');const crypto=require('crypto');const assert=require('assert/strict');
(async()=>{
 const base=process.env.TEST_URL,kind=process.env.TEST_EDITOR||'smarteditor2';
 if(!base||!process.env.TEST_USER||!process.env.TEST_PASSWORD||!process.env.TEST_IMAGE||!process.env.TEST_EXPECTED_WEBP)throw Error('Set disposable site credentials and PNG/expected WebP fixtures.');
 const b=await chromium.launch({executablePath:process.env.CHROMIUM||'/usr/bin/chromium',args:['--no-sandbox']});const c=await b.newContext();
 await c.route('**/*',r=>r.request().url().startsWith(base)?r.continue():r.abort());const p=await c.newPage();p.on('dialog',d=>d.accept());
 await p.goto(base+'/bbs/login.php');await p.locator('[name=mb_id]').fill(process.env.TEST_USER);await p.locator('[name=mb_password]').fill(process.env.TEST_PASSWORD);await p.locator('button[type=submit]').click();
 await p.goto(base+'/adm/site_config_form.php#tomo-media-settings');await p.locator('#cf_tomo_webp_quality').fill('73');
 const save=p.waitForResponse(r=>r.url().endsWith('/site_config_form_update.php')&&r.request().method()==='POST');await p.locator('input[type=submit]').first().click();assert.equal((await(await save).json()).saved,true);
 const viewer=await(await c.request.get(base+'/adm/viewer_form.php')).text();const nonce=viewer.match(/ed_nonce = "([^"]+)"/)[1];
 const image={name:'quality.png',mimeType:'image/png',buffer:fs.readFileSync(process.env.TEST_IMAGE)};
 const response=kind==='cheditor5'?await c.request.post(base+'/plugin/editor/cheditor5/imageUpload/upload.php',{multipart:{file:image}}):await c.request.post(base+'/plugin/editor/smarteditor2/photo_uploader/popup/php/index.php?_nonce='+encodeURIComponent(nonce),{multipart:{'files[]':image}});
 assert.equal(response.status(),200);const result=await response.json();const url=kind==='cheditor5'?result.fileUrl:result.files[0].url;
 assert(url.endsWith('.webp'));const download=await c.request.get(url);assert.equal(download.status(),200);
 const hash=buffer=>crypto.createHash('sha256').update(buffer).digest('hex');assert.equal(hash(await download.body()),hash(fs.readFileSync(process.env.TEST_EXPECTED_WEBP)));
 console.log('PASS '+kind+': HTTP upload matches encoder output at administrator quality 73');await b.close();
})().catch(e=>{console.error(e);process.exit(1)});
