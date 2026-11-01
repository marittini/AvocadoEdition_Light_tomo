// ucho_menu(PC): 하위 메뉴는 마우스를 떼면 닫힌다(아이콘을 눌러 연 뒤에도, BGM이 켜진 첫 화면에서도),
// 하위 메뉴 링크를 누르면 닫힌다, 하위 메뉴가 없는 아이콘의 이름표는 꼬리 달린 말풍선.
// 테스트용 메뉴는 끝나면 지우고, 메뉴 스킨 설정도 되돌린다.
// Env: TEST_URL, TEST_USER(최고관리자), TEST_PASSWORD, TEST_SQL(예: "mysql --default-character-set=utf8mb4 -uroot g5").
const { chromium } = require("playwright"),
  assert = require("node:assert/strict"),
  { execSync } = require("node:child_process");
(async () => {
  const base = process.env.TEST_URL,
    prefix = process.env.TEST_PREFIX || "avo_",
    sql = (q) => execSync(process.env.TEST_SQL, { input: q }).toString(),
    b = await chromium.launch(),
    errors = [],
    code = String(90 + Math.floor(Math.random() * 9));
  const skin = (sql(`SELECT cs_value FROM ${prefix}css_config WHERE cs_name='menu_skin';`).split("\n")[1] ?? "").trim();
  sql(`UPDATE ${prefix}css_config SET cs_value='ucho_menu' WHERE cs_name='menu_skin';`);
  sql(`INSERT INTO ${prefix}menu (me_code, me_icon, me_name, me_link, me_target, me_order, me_use, me_mobile_use) VALUES
    ('${code}', 'book', '테스트 묶음', '', 'self', 999, 1, 1), ('${code}10', '', '테스트 하위', '/bbs/new.php', 'self', 1, 1, 1);`);
  try {
    const c = await b.newContext({ viewport: { width: 1280, height: 800 } });
    await c.route("**/*", (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
    const p = await c.newPage();
    p.setDefaultTimeout(15000);
    p.on("pageerror", (e) => errors.push(e.message));
    await p.goto(base + "/bbs/login.php");
    await p.locator("[name=mb_id]").fill(process.env.TEST_USER);
    await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
    await p.locator("[type=submit]").first().click();
    await p.waitForLoadState();
    await p.goto(base + "/index.php");
    await p.waitForTimeout(1000); // BGM 창이 뜨는 첫 화면

    const li = p.locator("#header .has-sub", { has: p.locator(".sub-menu a", { hasText: "테스트 하위" }) });
    const sub = li.locator(".sub-menu");
    await li.locator(".ucho-menu-toggle").hover();
    assert.equal(await sub.isVisible(), true, "마우스를 올리면 하위 메뉴");
    await li.locator(".ucho-menu-toggle").click();
    assert.equal(await sub.isVisible(), true, "아이콘을 눌러도 열려 있음");
    await p.mouse.move(640, 600);
    await p.waitForTimeout(200);
    assert.equal(await sub.isVisible(), false, "누른 뒤에도 마우스를 떼면 닫힘");
    await li.locator(".ucho-menu-toggle").hover();
    await sub.locator("a").click();
    await p.mouse.move(640, 600);
    await p.waitForTimeout(200);
    assert.equal(await sub.isVisible(), false, "하위 메뉴 링크를 누른 뒤에도 닫힘");

    // 하위 메뉴 없는 아이콘(홈): 이름표에 꼬리
    const home = p.locator("#header .ucho-menu > li").first();
    await home.hover();
    await p.waitForTimeout(400);
    const tail = await home.locator(".tooltips").evaluate((e) => { const s = getComputedStyle(e, "::before"); return [s.content, s.clipPath !== "none"]; });
    assert.deepEqual(tail, ['""', true], "이름표 말풍선 꼬리");

    // 키보드로는 포커스가 있는 동안 열림
    await p.mouse.move(640, 600);
    await li.evaluate((el) => { // 메뉴 안에서 이 아이콘 바로 앞의 링크에 포커스를 둔다
      const all = [...el.closest(".ucho-menu").querySelectorAll(":scope > li > a, :scope > li > button")];
      all[all.indexOf(el.querySelector(".ucho-menu-toggle")) - 1].focus();
    });
    await p.keyboard.press("Tab");
    assert.equal(await li.locator(".ucho-menu-toggle").evaluate((e) => e === document.activeElement), true, "Tab으로 아이콘에");
    assert.equal(await sub.isVisible(), true, "키보드 포커스로 열림");
    await p.keyboard.press("Tab");
    assert.equal(await sub.locator("a").evaluate((e) => e === document.activeElement), true, "Tab으로 하위 메뉴 링크에");
    assert.equal(await sub.isVisible(), true, "하위 메뉴 안에서도 열려 있음");

    assert.deepEqual(errors, [], "자바스크립트 오류 없음");
    console.log("ucho menu smoke ok");
  } finally {
    sql(`DELETE FROM ${prefix}menu WHERE me_code IN ('${code}', '${code}10');`);
    sql(`UPDATE ${prefix}css_config SET cs_value='${skin.replace(/'/g, "")}' WHERE cs_name='menu_skin';`);
    await b.close();
  }
})().catch((e) => { console.error(e); process.exit(1); });
