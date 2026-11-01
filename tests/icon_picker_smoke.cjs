// 관리자 > 메뉴설정 아이콘 선택창: 메뉴 추가 창으로 넣은 새 줄의 버튼, 검색(영어 · 한글), 고르기 → 입력칸 · 미리보기,
// 현재 아이콘 표시, 비우기, Esc, 저장, 모바일. 테스트용 메뉴는 끝나면 지운다(기존 메뉴는 건드리지 않음).
// Env: TEST_URL, TEST_USER(최고관리자), TEST_PASSWORD.
const { chromium } = require("playwright"),
  assert = require("node:assert/strict");
(async () => {
  const base = process.env.TEST_URL,
    b = await chromium.launch(),
    errors = [];
  let created = null;
  const c = await b.newContext({ viewport: { width: 1280, height: 900 } });
  await c.route("**/*", (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
  const p = await c.newPage();
  p.setDefaultTimeout(15000);
  p.on("pageerror", (e) => errors.push(e.message));
  p.on("dialog", (d) => d.accept());
  try {
    await p.goto(base + "/bbs/login.php");
    await p.locator("[name=mb_id]").fill(process.env.TEST_USER);
    await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
    await p.locator("[type=submit]").first().click();
    await p.waitForLoadState();
    await p.goto(base + "/adm/menu_list.php");

    const name = "아이콘테스트" + Date.now().toString().slice(-5);
    const inputs = p.locator('input[name="me_icon[]"]');
    const before = await inputs.count();
    assert.equal(await p.locator(".icon-picker-open").count(), before, "아이콘 칸마다 선택 버튼");
    // 메뉴 추가 창(menu_form.php)으로 새 줄 넣기 → 그 줄에도 버튼이 붙어야 한다
    const [win] = await Promise.all([p.waitForEvent("popup"), p.locator("#fmenulist .btn_confirm button.ty2").click()]);
    await win.waitForLoadState();
    await win.locator("#me_name").fill(name);
    await win.locator("#me_link").fill("/bbs/new.php");
    await win.locator("#add_manual").click();
    await p.waitForFunction((n) => [...document.querySelectorAll('input[name="me_name[]"]')].some((i) => i.value === n), name);
    const row = p.locator("tr", { has: p.locator(`input[name="me_name[]"][value="${name}"]`) });
    await p.waitForFunction((n) => { const r = [...document.querySelectorAll('input[name="me_name[]"]')].find((i) => i.value === n)?.closest("tr"); return !!r?.querySelector(".icon-picker-open"); }, name);
    const first = row.locator('input[name="me_icon[]"]');
    created = name;

    // 열기 · 전체 개수
    await row.locator(".icon-picker-open").click();
    const dlg = p.locator(".icon-picker");
    assert.equal(await dlg.isVisible(), true, "선택창 열림");
    assert.match(await dlg.locator(".icon-picker-meta").innerText(), /전체 [\d,]+개/);
    assert.ok((await dlg.locator(".icon-picker-item").count()) > 2000, "Material Icons 전체 목록");

    // 영어 검색 → Enter로 첫 결과
    await dlg.locator(".icon-picker-search input").fill("home");
    const shown = await dlg.locator(".icon-picker-item:not([hidden])").evaluateAll((els) => els.map((e) => e.dataset.icon));
    assert.ok(shown.length > 0 && shown.every((n) => n.includes("home")), "home 검색");
    await dlg.locator(".icon-picker-search input").press("Enter");
    assert.equal(await dlg.isVisible(), false, "고르면 닫힘");
    assert.equal(await first.inputValue(), shown[0], "입력칸에 이름");
    assert.equal((await row.locator("td i.material-icons").first().innerText()).trim(), shown[0], "미리보기도 바뀜");

    // 한글 검색 · 현재 아이콘 표시 · 클릭으로 고르기
    await row.locator(".icon-picker-open").click();
    assert.equal(await dlg.locator(`.icon-picker-item.is-current[data-icon="${shown[0]}"]`).count(), 1, "현재 아이콘 강조");
    await dlg.locator(".icon-picker-search input").fill("별");
    assert.equal(await dlg.locator('.icon-picker-item[data-icon="star"]').isVisible(), true, "한글 '별' → star");
    await dlg.locator('.icon-picker-item[data-icon="star"]').click();
    assert.equal(await first.inputValue(), "star");

    // 비우기 · Esc
    await row.locator(".icon-picker-open").click();
    await dlg.locator("[data-icon-clear]").click();
    assert.equal(await first.inputValue(), "", "비우기");
    await row.locator(".icon-picker-open").click();
    await p.keyboard.press("Escape");
    assert.equal(await dlg.isVisible(), false, "Esc로 닫힘");
    assert.equal(await first.evaluate((el) => el === document.activeElement), true, "입력칸으로 돌아옴");

    // 저장하면 반영
    await row.locator(".icon-picker-open").click();
    await dlg.locator(".icon-picker-search input").fill("favorite");
    await dlg.locator('.icon-picker-item[data-icon="favorite"]').click();
    await p.locator("#fmenulist .btn_submit").click();
    await p.waitForLoadState();
    await p.goto(base + "/adm/menu_list.php");
    const saved = p.locator("tr", { has: p.locator(`input[name="me_name[]"][value="${name}"]`) }).locator('input[name="me_icon[]"]');
    assert.equal(await saved.inputValue(), "favorite", "저장됨");

    // 휴대폰 폭에서도 화면 안
    await p.setViewportSize({ width: 390, height: 800 });
    await p.locator(".icon-picker-open").first().click();
    const box = await p.locator(".icon-picker-panel").evaluate((e) => { const r = e.getBoundingClientRect(); return r.left >= 0 && r.right <= innerWidth && r.bottom <= innerHeight; });
    assert.equal(box, true, "모바일 화면 안");
    await p.keyboard.press("Escape");
    await p.setViewportSize({ width: 1280, height: 900 });

    assert.deepEqual(errors, [], "자바스크립트 오류 없음");
    console.log("icon picker smoke ok");
  } finally {
    if (created) { // 테스트용 메뉴 지우기
      await p.setViewportSize({ width: 1280, height: 900 });
      await p.goto(base + "/adm/menu_list.php");
      const r = p.locator("tr", { has: p.locator(`input[name="me_name[]"][value="${created}"]`) });
      if (await r.count()) {
        await r.locator(".btn_del_menu").click();
        await p.locator("#fmenulist .btn_submit").click();
        await p.waitForLoadState();
      }
    }
    await b.close();
  }
})().catch((e) => { console.error(e); process.exit(1); });
