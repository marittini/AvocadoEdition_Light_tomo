// 그림 보호(우클릭 금지)를 켜면 첨부 그림의 '크게 보기' 창(bbs/view_image.php)에서도 우클릭이 막힌다.
// 보호 스크립트가 늦게 붙거나 막혀도 그림 자체에 걸린 보호로 막히는지 본다. 보호 설정은 끝나면 되돌린다.
// Env: TEST_URL, TEST_USER(최고관리자), TEST_PASSWORD, TEST_IMAGE, TEST_SQL(예: "mysql -uroot g5").
const { chromium } = require("playwright"),
  assert = require("node:assert/strict"),
  { execSync } = require("node:child_process");
(async () => {
  const base = process.env.TEST_URL,
    prefix = process.env.TEST_PREFIX || "avo_",
    sql = (q) => execSync(process.env.TEST_SQL, { input: q }).toString(),
    b = await chromium.launch();
  const before = sql(`SELECT cf_tomo_protection FROM ${prefix}config;`).split("\n")[1].trim();
  sql(`UPDATE ${prefix}config SET cf_tomo_protection=3;`);
  try {
    const c = await b.newContext();
    const p = await c.newPage();
    p.on("dialog", (d) => d.accept());
    await p.goto(base + "/bbs/login.php");
    await p.locator("[name=mb_id]").fill(process.env.TEST_USER);
    await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
    await p.locator("[type=submit]").first().click();
    await p.waitForLoadState();
    const id = "vp" + Date.now().toString().slice(-7);
    await p.goto(base + "/adm/board_form.php");
    await p.locator("[name=gr_id]").selectOption({ index: 1 });
    await p.locator("[name=bo_table]").fill(id);
    await p.locator("[name=bo_subject]").fill("크게 보기 보호");
    await p.locator("[role=tab][aria-controls=anc_004]").click();
    await p.locator("[name=bo_skin]").selectOption("basic");
    await p.locator("[role=tab][aria-controls=anc_003]").click();
    await p.locator("[name=bo_upload_count]").fill("2");
    await p.locator("input[type=submit]").first().click();
    await p.waitForTimeout(600);
    await p.goto(base + "/bbs/write.php?bo_table=" + id);
    await p.locator("#wr_subject").fill("그림");
    const ed = p.locator(".ProseMirror").first();
    if (await ed.count()) { await ed.click(); await p.keyboard.insertText("본문"); } else await p.locator("#wr_content").fill("본문");
    await p.locator("input[name='bf_file[]']").first().setInputFiles(process.env.TEST_IMAGE);
    await p.locator("#btn_submit").click();
    await p.waitForLoadState();
    const href = await p.locator("a.view_image").first().getAttribute("href");
    assert.ok(href && href.includes("view_image.php"), "크게 보기 링크");

    // 보호 스크립트를 막은 창에서 크게 보기를 연다
    const c2 = await b.newContext({ storageState: await c.storageState() });
    await c2.route("**/tomo-protection.js*", (r) => r.abort());
    const v = await c2.newPage();
    await v.goto(new URL(href, base).href);
    const blocked = await v.evaluate(() => {
      const img = document.querySelector("img");
      const e = new MouseEvent("contextmenu", { bubbles: true, cancelable: true });
      img.dispatchEvent(e);
      return [e.defaultPrevented, img.getAttribute("draggable"), /-webkit-touch-callout:\s*none/.test(img.getAttribute("style"))];
    });
    assert.deepEqual(blocked, [true, "false", true], "크게 보기 창: 우클릭 · 끌기 · 길게 눌러 저장 막힘");
    await c2.close();

    // 보호를 끄면 크게 보기 창은 평소대로
    sql(`UPDATE ${prefix}config SET cf_tomo_protection=0;`);
    const v2 = await c.newPage();
    await v2.goto(new URL(href, base).href);
    assert.equal(await v2.locator("img").first().getAttribute("oncontextmenu"), null, "보호를 끄면 막지 않음");
    console.log("view image protection smoke ok", id);
  } finally {
    sql(`UPDATE ${prefix}config SET cf_tomo_protection=${Number(before) || 0};`);
    await b.close();
  }
})().catch((e) => { console.error(e); process.exit(1); });
