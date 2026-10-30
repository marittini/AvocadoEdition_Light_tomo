const {chromium}=require('playwright');const assert=require('node:assert/strict');
(async()=>{const base=process.env.TEST_URL;const browser=await chromium.launch({executablePath:'/usr/bin/chromium',args:['--no-sandbox']});const context=await browser.newContext();await context.route('**/*',r=>r.request().url().startsWith(base)?r.continue():r.abort());const page=await context.newPage();page.setDefaultTimeout(10000);page.on('dialog',d=>d.accept());await page.goto(base+'/bbs/login.php');await page.locator('[name=mb_id]').fill(process.env.TEST_USER);await page.locator('[name=mb_password]').fill(process.env.TEST_PASSWORD);await page.locator('button[type=submit]').click();
const errors=[];page.on('pageerror',e=>errors.push(e.message));
const pages=['/adm/site_config_form.php','/adm/design_form.php','/adm/viewer_form.php','/adm/menu_list.php','/adm/disk_usage.php','/main.php','/bbs/login.php'];
if(process.env.TEST_BOARD){pages.push('/bbs/board.php?bo_table='+process.env.TEST_BOARD,'/bbs/write.php?bo_table='+process.env.TEST_BOARD);}
for(const width of [320,390,768,1024,1440]){
 await page.setViewportSize({width,height:900});
 for(const path of pages){const r=await page.goto(base+path);assert.equal(r.status(),200,path);await page.waitForTimeout(100);if(path.startsWith('/bbs/write.php'))assert.equal(await page.locator('#bo_w').count(),1,'write form must actually render');if(path.startsWith('/bbs/board.php'))assert.equal(await page.locator('#bo_list').count(),1,'board list must actually render');const dimensions=await page.evaluate(()=>({scroll:document.documentElement.scrollWidth,width:innerWidth}));assert(dimensions.scroll<=width,path+' @ '+width+' overflow '+dimensions.scroll);
 const viewport=await page.locator('meta[name=viewport]').getAttribute('content');assert(!viewport.includes('user-scalable=no'));
 if(width===390&&path==='/adm/design_form.php'){await page.locator('#archive-nav-toggle').click();assert.equal(await page.locator('#archive-nav-toggle').getAttribute('aria-expanded'),'true');await page.locator('#archive-nav-toggle').click();}
 }
 console.log('PASS responsive width '+width+'px: '+pages.length+' routes');
}
assert.deepEqual(errors,[]);await browser.close();})().catch(e=>{console.error(e);process.exit(1)});
