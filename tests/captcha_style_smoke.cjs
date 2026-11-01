// 자동등록방지(비회원 글쓰기): 공용 모양(captcha.css)이 붙고, PC는 [그림 · 듣기 · 새로고침 · 입력칸] 한 줄,
// 휴대폰은 입력칸이 한 줄 전체이며 화면 밖으로 넘치지 않는다. 사이트 공개 설정은 끝나면 되돌린다.
// Env: TEST_URL, TEST_USER(최고관리자), TEST_PASSWORD, TEST_SQL(예: "mysql -uroot g5").
const { chromium, devices } = require("playwright"),
  assert = require("node:assert/strict"),
  { execSync } = require("node:child_process");
(async () => {
  const base = process.env.TEST_URL,
    prefix = process.env.TEST_PREFIX || "avo_",
    sql = (q) => execSync(process.env.TEST_SQL, { input: q }).toString(),
    b = await chromium.launch(),
    errors = [];
  const open = sql(`SELECT cf_open FROM ${prefix}config;`).split("\n")[1].trim();
  sql(`UPDATE ${prefix}config SET cf_open=1;`);
  try {
    const admin = await (await b.newContext()).newPage();
    await admin.goto(base + "/bbs/login.php");
    await admin.locator("[name=mb_id]").fill(process.env.TEST_USER);
    await admin.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
    await admin.locator("[type=submit]").first().click();
    await admin.waitForLoadState();
    const id = "cp" + Date.now().toString().slice(-7);
    await admin.goto(base + "/adm/board_form.php");
    await admin.locator("[name=gr_id]").selectOption({ index: 1 });
    await admin.locator("[name=bo_table]").fill(id);
    await admin.locator("[name=bo_subject]").fill("자동등록방지 모양");
    await admin.locator("input[type=submit]").first().click();
    await admin.waitForTimeout(600);
    sql(`UPDATE ${prefix}board SET bo_write_level=1 WHERE bo_table='${id}';`);

    for (const [name, opt] of [["pc", { viewport: { width: 1280, height: 900 } }], ["mobile", devices["iPhone 13"]]]) {
      const c = await b.newContext(opt);
      await c.route("**/*", (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
      const p = await c.newPage();
      p.on("pageerror", (e) => errors.push(e.message));
      await p.goto(base + "/bbs/write.php?bo_table=" + id);
      const cap = p.locator("#captcha");
      await cap.waitFor();
      assert.equal(await p.locator("link[href*='/css/captcha.css']").count(), 1, name + ": 공용 모양");
      assert.equal(await cap.evaluate((e) => getComputedStyle(e).display), "grid", name + ": 정리된 배치");
      const r = await p.evaluate(() => {
        const box = (s) => { const el = document.querySelector(s); return el ? el.getBoundingClientRect() : null; };
        return { img: box("#captcha_img"), reload: box("#captcha_reload"), key: box("#captcha_key"), info: box("#captcha_info"), vw: innerWidth, sw: document.documentElement.scrollWidth };
      });
      assert.equal(Math.round(r.reload.width), 34, name + ": 새로고침은 동그란 아이콘");
      assert.ok(r.info.top >= r.img.bottom - 1, name + ": 안내는 아래 줄");
      assert.ok(r.key.right <= r.vw && r.sw <= r.vw, name + ": 화면 밖으로 넘치지 않음");
      if (name === "pc") assert.ok(Math.abs(r.key.top + r.key.height / 2 - (r.img.top + r.img.height / 2)) < 6, "PC: 입력칸은 그림과 같은 줄");
      else assert.ok(r.key.top >= r.img.bottom - 1, "휴대폰: 입력칸은 아래 줄");
      assert.equal(await p.locator("#captcha_key").getAttribute("inputmode"), "numeric", name + ": 숫자 자판");
      await c.close();
    }
    assert.deepEqual(errors, [], "자바스크립트 오류 없음");
    console.log("captcha style smoke ok", id);
  } finally {
    sql(`UPDATE ${prefix}config SET cf_open=${Number(open) || 0};`);
    await b.close();
  }
})().catch((e) => { console.error(e); process.exit(1); });
