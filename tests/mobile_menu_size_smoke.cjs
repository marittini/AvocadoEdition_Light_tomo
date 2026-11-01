// 관리자 > 디자인 설정 > 메뉴 디자인 > 모바일 메뉴 글자 크기: 저장하면 휴대폰에서 펼치는 메뉴(#mo_header)의
// 글자(와 아이콘)가 그 크기로 나오고, 비우면 원래 크기(아이콘 크기의 1.2배)로 돌아간다. 끝나면 원래 값으로 되돌린다.
// Env: TEST_URL, TEST_USER(최고관리자), TEST_PASSWORD.
const { chromium, devices } = require("playwright"),
  assert = require("node:assert/strict");
(async () => {
  const base = process.env.TEST_URL,
    b = await chromium.launch(),
    errors = [];
  const c = await b.newContext({ viewport: { width: 1280, height: 900 } });
  await c.route("**/*", (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
  const p = await c.newPage();
  p.setDefaultTimeout(15000);
  p.on("pageerror", (e) => errors.push(e.message));
  p.on("dialog", (d) => d.accept());
  let before = null;
  async function setSize(v) {
    await p.goto(base + "/adm/design_form.php#anc_008");
    const input = p.locator("#menu_mobile_size");
    if (before === null) before = await input.inputValue();
    await input.fill(v);
    await Promise.all([p.waitForResponse((r) => r.url().includes("design_form_update.php")), p.locator("#fconfigform .btn_submit").click()]);
    await p.waitForTimeout(300);
  }
  async function mobileSize() {
    const m = await b.newContext({ ...devices["iPhone 13"], storageState: await c.storageState() });
    await m.route("**/*", (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
    const mp = await m.newPage();
    mp.on("pageerror", (e) => errors.push(e.message));
    await mp.goto(base + "/main.php");
    const size = await mp.evaluate(() => {
      const wrap = document.querySelector("#mo_header .gnbWrap"), li = wrap && wrap.querySelector("li:not(.line)");
      return wrap ? [parseFloat(getComputedStyle(wrap).fontSize), parseFloat(getComputedStyle(li).fontSize)] : null;
    });
    await m.close();
    return size;
  }
  try {
    await p.goto(base + "/bbs/login.php");
    await p.locator("[name=mb_id]").fill(process.env.TEST_USER);
    await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
    await p.locator("[type=submit]").first().click();
    await p.waitForLoadState();

    await setSize("14");
    await p.goto(base + "/adm/design_form.php#anc_008");
    assert.equal(await p.locator("#menu_mobile_size").inputValue(), "14", "저장됨");
    assert.deepEqual(await mobileSize(), [14, 14], "모바일 메뉴 글자 14px");
    await setSize("");
    const [wrap, li] = await mobileSize();
    assert.equal(Math.round((li / wrap) * 10) / 10, 1.2, "비우면 원래대로(1.2배)");

    assert.deepEqual(errors, [], "자바스크립트 오류 없음");
    console.log("mobile menu size smoke ok");
  } finally {
    if (before !== null) await setSize(before);
    await b.close();
  }
})().catch((e) => { console.error(e); process.exit(1); });
