/* Exercise the shipped stylesheets against content images with saved dimensions. */
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
(async () => {
  const browser = await chromium.launch({executablePath:'/usr/bin/chromium', args:['--no-sandbox']});
  const context = await browser.newContext();
  const page = await context.newPage();
  const base = process.env.TEST_URL;
  await context.route('**/*', r => r.request().url().startsWith(base) ? r.continue() : r.abort());
  await page.goto(base+'/bbs/login.php');
  await page.locator('[name=mb_id]').fill(process.env.TEST_USER);
  await page.locator('[name=mb_password]').fill(process.env.TEST_PASSWORD);
  await page.locator('[type=submit]').first().click();
  for (const width of [320,390,768,1024,1440]) {
    await page.setViewportSize({width,height:900});
    await page.goto(base+'/main.php');
    await page.evaluate(() => {
      const svg='<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="600"><rect width="1200" height="600" fill="gray"/></svg>';
      const src='data:image/svg+xml,'+encodeURIComponent(svg);
      const main=document.getElementById('main_body');
      main.innerHTML='<img id="test-main" src="'+src+'" style="width:1200px;height:600px">';
      const body=document.createElement('div'); body.id='bo_v_con';
      body.innerHTML='<img id="test-body" src="'+src+'" style="width:1200px;height:600px">';
      main.after(body);
      const decor=document.createElement('div');decor.className='character-card-list';
      decor.innerHTML='<img id="test-decor" src="'+src+'" style="width:80px;height:80px">';
      main.after(decor);
    });
    await page.waitForFunction(() => [...document.querySelectorAll('#test-main,#test-body,#test-decor')].every(x=>x.complete));
    for (const id of ['test-main','test-body']) {
      const d=await page.locator('#'+id).evaluate(i=>({w:i.getBoundingClientRect().width,h:i.getBoundingClientRect().height}));
      assert(Math.abs(d.w/d.h-2)<0.01,`${id} ${width}: ${JSON.stringify(d)}`);
    }
    const icon=await page.locator('#test-decor').evaluate(i=>({w:i.getBoundingClientRect().width,h:i.getBoundingClientRect().height}));
    assert.equal(icon.w,80);assert.equal(icon.h,80);
    console.log('PASS content aspect and unrelated fixed icon',width);
  }
  await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
