// 클릭 효과(tail.click_effect.php): 누른 자리의 원, 포인트 색, 효과음 재생, 모션 줄이기 설정, 관리자 화면 제외.
// Env: TEST_URL, TEST_USER(최고관리자), TEST_PASSWORD.
const { chromium } = require("playwright"),
  assert = require("node:assert/strict");
(async () => {
  const base = process.env.TEST_URL,
    b = await chromium.launch(),
    errors = [];
  try {
    async function open(opts = {}) {
      const c = await b.newContext({ viewport: { width: 1280, height: 900 }, ...opts });
      await c.route("**/*", (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
      // 실제 소리 대신 재생 요청 횟수만 센다.
      await c.addInitScript(() => {
        window.__plays = [];
        HTMLMediaElement.prototype.play = function () { window.__plays.push(this.src); return Promise.resolve(); };
      });
      const p = await c.newPage();
      p.setDefaultTimeout(15000);
      p.on("pageerror", (e) => errors.push(e.message));
      await p.goto(base + "/bbs/login.php");
      await p.locator("[name=mb_id]").fill(process.env.TEST_USER);
      await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
      await p.locator("[type=submit]").first().click();
      await p.waitForLoadState();
      return p;
    }

    // 1. 일반 화면: 원 + 효과음
    const p = await open();
    await p.goto(base + "/bbs/new.php");
    assert.equal(await p.locator("script[src*='click-effect.js']").count(), 1, "클릭 효과 스크립트");
    const color = await p.evaluate(() => getComputedStyle(document.documentElement).getPropertyValue("--click-effect-color").trim());
    assert.ok(color && color !== "currentColor", "디자인 설정의 포인트 색: " + color);
    await p.mouse.move(300, 400);
    await p.mouse.down();
    const ring = p.locator(".click-effect");
    await ring.waitFor({ state: "attached" });
    const box = await ring.evaluate((e) => ({ left: e.style.left, top: e.style.top, events: getComputedStyle(e).pointerEvents }));
    assert.deepEqual(box, { left: "300px", top: "400px", events: "none" }, "누른 자리, 클릭을 막지 않음");
    await p.mouse.up();
    await p.waitForFunction(() => !document.querySelector(".click-effect"), null, { timeout: 4000 });
    const plays = await p.evaluate(() => window.__plays);
    assert.equal(plays.length, 1, "효과음 한 번");
    assert.match(plays[0], /\/sound\/click\.mp3$/);
    const mp3 = await p.request.get(base + "/sound/click.mp3");
    assert.equal(mp3.status(), 200, "효과음 파일");
    assert.ok((await mp3.body()).length > 1000);
    // 오른쪽 버튼은 무시
    await p.mouse.click(310, 410, { button: "right" });
    assert.equal(await p.evaluate(() => window.__plays.length), 1, "오른쪽 클릭은 무시");

    // 2. 모션 줄이기: 원은 없고 소리만
    const r = await open({ reducedMotion: "reduce" });
    await r.goto(base + "/bbs/new.php");
    await r.mouse.click(200, 200);
    assert.equal(await r.locator(".click-effect").count(), 0, "모션 줄이기면 원 없음");
    assert.equal(await r.evaluate(() => window.__plays.length), 1);

    // 3. 관리자 화면에는 없음
    await p.goto(base + "/adm/");
    assert.equal(await p.locator("script[src*='click-effect.js']").count(), 0, "관리자 화면 제외");

    assert.deepEqual(errors, [], "자바스크립트 오류 없음");
    console.log("click effect smoke ok");
  } finally {
    await b.close();
  }
})().catch((e) => { console.error(e); process.exit(1); });
