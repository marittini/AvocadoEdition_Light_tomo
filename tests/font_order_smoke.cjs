// 관리자 > 디자인 설정 > 웹폰트 등록: 한 줄씩 접힌 목록, 순서 바꾸기(저장 후 반영 · 글꼴 선택 목록 순서), 찾기, 모두 펼치기.
// 테스트 폰트는 끝나면 지우고 원래 등록 목록으로 되돌린다.
// Env: TEST_URL, TEST_USER(최고관리자), TEST_PASSWORD, TEST_SQL(예: "mysql -uroot g5").
const { chromium } = require("playwright"),
  assert = require("node:assert/strict"),
  { execSync } = require("node:child_process");
(async () => {
  const base = process.env.TEST_URL,
    prefix = process.env.TEST_PREFIX || "avo_",
    sql = (q) => execSync(process.env.TEST_SQL, { input: q }).toString(),
    b = await chromium.launch(),
    errors = [];
  const backup = sql(`SELECT HEX(cf_tomo_fonts) FROM ${prefix}config;`).split("\n")[1]?.trim() ?? "";
  const c = await b.newContext({ viewport: { width: 1280, height: 900 } });
  await c.route("**/*", (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
  const p = await c.newPage();
  p.setDefaultTimeout(15000);
  p.on("pageerror", (e) => errors.push(e.message));
  p.on("dialog", (d) => d.accept());
  try {
    sql(`UPDATE ${prefix}config SET cf_tomo_fonts='[]';`);
    await p.goto(base + "/bbs/login.php");
    await p.locator("[name=mb_id]").fill(process.env.TEST_USER);
    await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
    await p.locator("[type=submit]").first().click();
    await p.waitForLoadState();
    await p.goto(base + "/adm/design_form.php#tomo-fonts");

    // 폰트 셋 등록(첫 줄은 빈 줄이 열려 있음)
    const fonts = [["가 폰트", "TestFontA"], ["나 폰트", "TestFontB"], ["다 폰트", "TestFontC"]];
    for (const [i, [label, family]] of fonts.entries()) {
      if (i > 0) await p.locator("#tomo-font-add").click();
      const item = p.locator(".tomo-font-item").last();
      await item.locator("[data-font-field=label]").fill(label);
      await item.locator("[data-font-field=family]").fill(family);
    }
    const save = async () => {
      await Promise.all([p.waitForResponse((r) => r.url().includes("design_form_update.php")), p.locator("#fconfigform .btn_submit").click()]);
      await p.waitForTimeout(300);
      await p.goto(base + "/adm/design_form.php#tomo-fonts");
      await p.reload();
    };
    await save();
    const labels = () => p.locator(".tomo-font-item .tomo-font-sum-label").allInnerTexts();
    assert.deepEqual(await labels(), ["가 폰트", "나 폰트", "다 폰트"], "저장한 순서");
    assert.equal(await p.locator(".tomo-font-item[open]").count(), 0, "저장된 폰트는 접혀 있음");
    assert.equal((await p.locator("#tomo-font-count").innerText()).trim(), "등록한 웹폰트 3개");
    assert.equal(await p.locator(".tomo-font-item").first().locator("[data-font-move='-1']").isDisabled(), true, "맨 위는 위로 못 감");

    // 순서 바꾸기: 다 → 맨 위, 가 → 맨 아래
    const item = (label) => p.locator(".tomo-font-item", { has: p.locator(".tomo-font-sum-label", { hasText: label }) });
    await item("다 폰트").locator("[data-font-move='-1']").click();
    await item("다 폰트").locator("[data-font-move='-1']").click();
    assert.equal(await item("다 폰트").evaluate((e) => e.open), false, "순서 버튼은 펼치지 않음");
    await item("가 폰트").locator("[data-font-move='1']").click();
    assert.deepEqual(await labels(), ["다 폰트", "나 폰트", "가 폰트"]);
    await save();
    assert.deepEqual(await labels(), ["다 폰트", "나 폰트", "가 폰트"], "저장 후에도 바뀐 순서");
    const stored = JSON.parse(sql(`SELECT cf_tomo_fonts FROM ${prefix}config;`).split("\n")[1]).map((f) => f.family);
    assert.deepEqual(stored, ["TestFontC", "TestFontB", "TestFontA"], "DB 순서");
    const options = await p.locator(".tomo-font-choice select").first().locator("option").allInnerTexts();
    assert.deepEqual(options.slice(1, 4), ["다 폰트", "나 폰트", "가 폰트"], "글꼴 선택 목록도 같은 순서");

    // 찾기 · 모두 펼치기
    await p.locator("#tomo-font-filter").fill("TestFontB");
    assert.deepEqual(await p.locator(".tomo-font-item:not([hidden]) .tomo-font-sum-label").allInnerTexts(), ["나 폰트"], "찾기");
    await p.locator("#tomo-font-filter").fill("");
    await p.locator("#tomo-font-expand").click();
    assert.equal(await p.locator(".tomo-font-item[open]").count(), 3, "모두 펼치기");
    await p.locator(".tomo-font-item").first().locator("summary").click();
    assert.equal(await p.locator(".tomo-font-item").first().evaluate((e) => e.open), false, "눌러서 접기");

    assert.deepEqual(errors, [], "자바스크립트 오류 없음");
    console.log("font order smoke ok");
  } finally {
    sql(backup ? `UPDATE ${prefix}config SET cf_tomo_fonts=UNHEX('${backup}');` : `UPDATE ${prefix}config SET cf_tomo_fonts='[]';`);
    await b.close();
  }
})().catch((e) => { console.error(e); process.exit(1); });
