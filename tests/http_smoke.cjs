/* Run against a disposable installed copy. No credentials are checked into source. */
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
(async () => {
 const base=process.env.TEST_URL, user=process.env.TEST_USER, password=process.env.TEST_PASSWORD;
 if(!base||!user||!password)throw new Error('Set TEST_URL, TEST_USER and TEST_PASSWORD for a disposable site.');
 const browser=await chromium.launch({executablePath:process.env.CHROMIUM||'/usr/bin/chromium',args:['--no-sandbox']});
 const context=await browser.newContext({viewport:{width:1440,height:1000}});
 // External resources are not required to operate the administrator.
 await context.route('**/*',route=>route.request().url().startsWith(base)?route.continue():route.abort());
 const page=await context.newPage();page.setDefaultTimeout(15000);
 const errors=[];page.on('pageerror',error=>errors.push(error.message));page.on('dialog',d=>d.accept());
 await page.goto(base+'/bbs/login.php');await page.locator('[name=mb_id]').fill(user);await page.locator('[name=mb_password]').fill(password);await page.locator('button[type=submit]').click();
 for(const name of ['site_config_form','design_form','viewer_form','menu_list','disk_usage']) {
  const response=await page.goto(base+'/adm/'+name+'.php');assert.equal(response.status(),200);assert.equal(await page.locator('#gnb').count(),1);
  if(name==='design_form') {
   const count=await page.locator('form [name]').count();assert(count>100,'legacy fields preserved');
   assert.equal(await page.locator('.archive-preview').count(),0);assert.equal(await page.locator('[role=tabpanel]:visible').count(),1);
  }
  if(name==='disk_usage') {await page.locator('#disk-start').click();await page.waitForFunction(()=>document.querySelector('#disk-status').textContent.includes('측정 완료'));assert(Number(await page.locator('#disk-meter').getAttribute('aria-valuenow'))>0);}
 }
 await page.goto(base+'/adm/site_config_form.php');const original=await page.locator('[name=cf_title]').inputValue();
 await page.locator('[name=cf_title]').fill(original+' 검증');await page.locator('input[type=submit]').first().click();await page.waitForFunction(()=>document.querySelector('.archive-form-status').textContent.includes('저장 완료'));
 await page.reload();assert.equal(await page.locator('[name=cf_title]').inputValue(),original+' 검증');
 // Failed saves keep the current input.
 await page.locator('[name=cf_admin]').evaluate(el=>el.add(new Option('missing_test_owner','missing_test_owner')));await page.locator('[name=cf_admin]').selectOption('missing_test_owner');await page.locator('input[type=submit]').first().click();await page.waitForFunction(()=>document.querySelector('.archive-form-status').textContent.includes('존재하지'));
 assert.equal(await page.locator('[name=cf_admin]').inputValue(),'missing_test_owner');
 // Reject forged preview tokens and unauthenticated access.
 const tokenCheck=await context.request.post(base+'/adm/archive.ajax.php',{form:{action:'preview',kind:'main',archive_token:'forged'}});assert.equal(tokenCheck.status(),403);
 const guest=await browser.newContext();const anonymous=await guest.request.post(base+'/adm/archive.ajax.php',{form:{action:'disk_start'}});assert(!((await anonymous.text()).includes('"scan"')));await guest.close();
 await page.evaluate(()=>location.reload());await page.waitForTimeout(500);
 await page.goto(base+'/adm/design_form.php');await page.setViewportSize({width:390,height:844});await page.reload();assert((await page.evaluate(()=>document.documentElement.scrollWidth))<=390);

 assert.deepEqual(errors,[]);console.log('PASS: admin routes, fields, removed preview, disk scan, save/reload, failed-save retention, CSRF/auth, mobile layout, JS errors');await browser.close();
})().catch(error=>{console.error(error);process.exit(1)});
