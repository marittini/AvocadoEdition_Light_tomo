// 일반 로그인 화면의 자동로그인, 게시판 '모바일 페이지당 목록 수'(모바일 5개 고정 수정).
// Env: TEST_URL, TEST_USER(최고관리자), TEST_PASSWORD, TEST_MEMBER(일반 회원, 같은 비밀번호), TEST_SQL(예: "mysql -uroot g5").
const { chromium, devices } = require("playwright"),
  assert = require("node:assert/strict"),
  { execSync } = require("node:child_process"),
  md5 = (s) => require("node:crypto").createHash("md5").update(s).digest("hex");
// 그누보드 set_cookie()는 쿠키 이름을 md5로 바꿔 저장한다.
const CK_ID = md5("ck_mb_id"), CK_AUTO = md5("ck_auto");
(async () => {
  const base = process.env.TEST_URL,
    prefix = process.env.TEST_PREFIX || "avo_",
    sql = (q) => execSync(process.env.TEST_SQL, { input: q }).toString(),
    b = await chromium.launch(),
    errors = [];
  const delay = sql(`SELECT cf_delay_sec FROM ${prefix}config;`).split("\n")[1].trim();
  sql(`UPDATE ${prefix}config SET cf_delay_sec=0;`);
  try {
    async function context(opts = {}) {
      const c = await b.newContext(opts);
      await c.route("**/*", (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
      return c;
    }
    async function page(c) {
      const p = await c.newPage();
      p.setDefaultTimeout(15000);
      p.on("pageerror", (e) => errors.push(e.message));
      return p;
    }

    // 1. 자동로그인: 체크하면 안내 확인창, 취소하면 다시 꺼짐
    const c1 = await context();
    const p1 = await page(c1);
    await p1.goto(base + "/bbs/login.php");
    const box = p1.locator("#login_auto_login");
    assert.equal(await box.count(), 1, "일반 로그인 화면에 자동로그인");
    p1.once("dialog", (d) => d.dismiss());
    await box.click();
    assert.equal(await box.isChecked(), false, "확인창에서 취소하면 꺼짐");
    p1.once("dialog", (d) => d.accept());
    await box.click();
    assert.equal(await box.isChecked(), true);
    await p1.locator("[name=mb_id]").fill(process.env.TEST_MEMBER);
    await p1.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
    await p1.locator("[type=submit]").first().click();
    await p1.waitForLoadState();
    const cookies = await c1.cookies();
    const keep = cookies.filter((c) => (c.name === CK_ID || c.name === CK_AUTO) && c.value);
    assert.equal(keep.length, 2, "자동로그인 쿠키");
    assert.ok(keep.every((c) => c.expires > Date.now() / 1000 + 86400 * 30), "한 달 동안 유지");

    // 세션 없이 자동로그인 쿠키만 있는 새 브라우저 → 로그인 상태
    const c2 = await context();
    await c2.addCookies(keep);
    const p2 = await page(c2);
    await p2.goto(base + "/");
    assert.equal(await p2.evaluate(() => window.g5_is_member), "1", "자동로그인으로 로그인됨");

    // 체크하지 않으면 쿠키 없음
    const c3 = await context();
    const p3 = await page(c3);
    await p3.goto(base + "/bbs/login.php");
    await p3.locator("[name=mb_id]").fill(process.env.TEST_MEMBER);
    await p3.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
    await p3.locator("[type=submit]").first().click();
    await p3.waitForLoadState();
    assert.equal((await c3.cookies()).filter((c) => c.name === CK_AUTO && c.value).length, 0, "체크 안 하면 자동로그인 없음");

    // 2. 모바일 페이지당 목록 수: 관리자 화면에서 저장 → 모바일에서 그 수만큼
    const ca = await context();
    const admin = await page(ca);
    admin.on("dialog", (d) => d.accept());
    await admin.goto(base + "/bbs/login.php");
    await admin.locator("[name=mb_id]").fill(process.env.TEST_USER);
    await admin.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
    await admin.locator("[type=submit]").first().click();
    await admin.waitForLoadState();
    const id = "pr" + Date.now().toString().slice(-7);
    await admin.goto(base + "/adm/boardgroup_form.php");
    await admin.locator("[name=gr_id]").fill(id);
    await admin.locator("[name=gr_subject]").fill("목록 수 테스트");
    await admin.locator("input[type=submit]").first().click();
    await admin.waitForTimeout(400);
    await admin.goto(base + "/adm/board_form.php");
    await admin.locator("[name=gr_id]").selectOption(id);
    await admin.locator("[name=bo_table]").fill(id);
    await admin.locator("[name=bo_subject]").fill("목록 수 테스트");
    await admin.locator("[role=tab][aria-controls=anc_004]").click();
    await admin.locator("[name=bo_skin]").selectOption("custom_skin/guestbook");
    await admin.locator("[name=bo_page_rows]").fill("3"); // 스킨과 같은 탭
    assert.equal(await admin.locator("[name=bo_mobile_page_rows]").count(), 1, "관리자 화면에 모바일 목록 수 칸");
    await admin.locator("[name=bo_mobile_page_rows]").fill("2");
    await admin.locator("input[type=submit]").first().click();
    await admin.waitForTimeout(700);
    assert.match(sql(`SELECT bo_page_rows, bo_mobile_page_rows FROM ${prefix}board WHERE bo_table='${id}';`), /\b3\s+2\b/, "PC 3, 모바일 2로 저장");
    sql(`UPDATE ${prefix}board SET bo_list_level=1, bo_read_level=1 WHERE bo_table='${id}';`);
    const board = base + "/bbs/board.php?bo_table=" + id;
    for (let i = 1; i <= 5; i++) {
      await admin.goto(board);
      await admin.locator("#gb-write textarea").fill("글 " + i);
      await admin.locator("#gb-write [type=submit]").click();
      await admin.waitForLoadState();
    }
    const count = async (p) => { await p.goto(board); return p.locator(".gb-entry").count(); };
    const pc = await page(ca);
    assert.equal(await count(pc), 3, "PC는 3개");
    // 모바일 여부는 세션에 기억되므로 모바일 브라우저로 새로 로그인한다.
    const cm = await context({ ...devices["iPhone 13"] });
    const mobile = await page(cm);
    await mobile.goto(base + "/bbs/login.php");
    await mobile.locator("[name=mb_id]").fill(process.env.TEST_USER);
    await mobile.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
    await mobile.locator("[type=submit]").first().click();
    await mobile.waitForLoadState();
    assert.equal(await count(mobile), 2, "모바일은 모바일 목록 수(2개)");
    sql(`UPDATE ${prefix}board SET bo_mobile_page_rows=0 WHERE bo_table='${id}';`);
    assert.equal(await count(mobile), 3, "모바일 값이 0이면 PC 값");

    assert.deepEqual(errors, [], "자바스크립트 오류 없음");
    console.log("login · page rows smoke ok", id);
  } finally {
    sql(`UPDATE ${prefix}config SET cf_delay_sec=${Number(delay) || 0};`);
    await b.close();
  }
})().catch((e) => { console.error(e); process.exit(1); });
