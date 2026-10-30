/* Disposable local site only. TEST_LOCAL_DESIGN_DATA is optional for destructive filesystem faults. */
const {chromium}=require('playwright');const assert=require('node:assert/strict');const fs=require('fs');const path=require('path');
(async()=>{
 const base=process.env.TEST_URL;if(!base||!process.env.TEST_USER||!process.env.TEST_PASSWORD)throw Error('Set disposable site credentials.');
 const b=await chromium.launch({executablePath:process.env.CHROMIUM||'/usr/bin/chromium',args:['--no-sandbox']});const c=await b.newContext();await c.route('**/*',r=>r.request().url().startsWith(base)?r.continue():r.abort());
 const a=await c.newPage();a.on('dialog',d=>d.accept());await a.goto(base+'/bbs/login.php');await a.locator('[name=mb_id]').fill(process.env.TEST_USER);await a.locator('[name=mb_password]').fill(process.env.TEST_PASSWORD);await a.locator('button[type=submit]').click();
 const z=await c.newPage();z.on('dialog',d=>d.accept());
 const load=p=>p.goto(base+'/adm/design_form.php');
 const slot=async(p,name,n)=>{const i=await p.locator('input[name^="cs_name["]').evaluateAll((inputs,name)=>inputs.find(x=>x.value===name).name.match(/\[(\d+)\]/)[1],name);return p.locator('[name="cs_etc_'+n+'['+i+']"]');};
 const submit=async p=>{const done=p.waitForResponse(r=>r.url().endsWith('/design_form_update.php')&&r.request().method()==='POST');await p.locator('input[type=submit]').first().click();const r=await done;const json=await r.json();await p.waitForFunction(()=>document.querySelector('#container form').dataset.archiveSaving==='0');return{status:r.status(),json};};
 await load(a);await load(z);const oldRevision=await a.locator('[name=tomo_design_revision]').inputValue();
 const originalSize=await(await slot(a,'default_font',2)).inputValue();await(await slot(a,'default_font',2)).fill('14');assert.equal((await submit(a)).status,200);
 const savedRevision=await a.locator('[name=tomo_design_revision]').inputValue();assert.notEqual(savedRevision,oldRevision);
 await(await slot(z,'default_font',2)).fill('15');const stale=await submit(z);assert.equal(stale.status,409);assert.match(stale.json.error,/다른 화면/);assert.equal(await(await slot(z,'default_font',2)).inputValue(),'15');
 await a.reload();assert.equal(await(await slot(a,'default_font',2)).inputValue(),'14');console.log('PASS actual HTTP stale tab is rejected without losing draft input');
 await(await slot(a,'default_font',2)).fill(originalSize);assert.equal((await submit(a)).status,200);
 // An invalid CSS calculation fails during rendering, before any DB mutation.
 const widthIndex=await a.locator('input[name^="cs_name["]').evaluateAll(xs=>xs.find(x=>x.value==='content_width').name.match(/\[(\d+)\]/)[1]);const width=a.locator('[name="cs_value['+widthIndex+']"]');const oldWidth=await width.inputValue();const before=await a.locator('[name=tomo_design_revision]').inputValue();
 await width.evaluate(el=>el.type='text');await width.fill('not-a-number');const bad=await submit(a);assert.equal(bad.status,422);assert.equal(await width.inputValue(),'not-a-number');await load(z);assert.equal(await z.locator('[name=tomo_design_revision]').inputValue(),before);await width.fill(oldWidth);
 console.log('PASS render failure leaves stored revision unchanged and retains input');
 const root=process.env.TEST_LOCAL_DESIGN_DATA;
 if(root){
  assert.equal(new URL(base).hostname,'127.0.0.1','filesystem fault tests require a local disposable site');
  const target=path.join(root,'css','_design.config.css'),backup=target+'.fixture-backup';const original=fs.readFileSync(target);const oldFiles=fs.readdirSync(path.join(root,'site')).sort();
  const revision=await a.locator('[name=tomo_design_revision]').inputValue();const file=a.locator('input[type=file][name^=cs_value_file]').first();
  await file.setInputFiles(process.env.TEST_IMAGE);fs.renameSync(target,backup);fs.mkdirSync(target);
  try{await(await slot(a,'default_font',2)).fill('16');const failed=await submit(a);assert.equal(failed.status,422);assert.equal(await(await slot(a,'default_font',2)).inputValue(),'16');assert.equal(await file.evaluate(el=>el.files.length),1);}
  finally{fs.rmdirSync(target);fs.renameSync(backup,target);}
  assert(fs.readFileSync(target).equals(original));assert.deepEqual(fs.readdirSync(path.join(root,'site')).sort(),oldFiles);await load(z);assert.equal(await z.locator('[name=tomo_design_revision]').inputValue(),revision);
  await file.setInputFiles([]);await(await slot(a,'default_font',2)).fill(originalSize);assert.equal((await submit(a)).status,200);
  console.log('PASS failed CSS publish path preserves DB/CSS/assets and selected upload; retry succeeds');
 }
 await b.close();
})().catch(e=>{console.error(e);process.exit(1)});
