// 편집기 새 기능: 이미지 묶음(2~4열, 휴대폰은 한 줄에 하나), 그림 올려서 묶기 · 선택한 그림 묶기 · 열 바꾸기 · 묶음 풀기,
// 인용구 모양 · 구분선 모양, 색 고르기, 줄 간격 · 자간, 저장 후 읽는 화면.
// Env: TEST_URL, TEST_USER(최고관리자), TEST_PASSWORD, TEST_IMAGE.
const { chromium } = require("playwright"),
  assert = require("node:assert/strict"),
  fs = require("node:fs");
(async () => {
  const base = process.env.TEST_URL,
    b = await chromium.launch(),
    errors = [];
  const img = (name) => ({ name, mimeType: "image/png", buffer: fs.readFileSync(process.env.TEST_IMAGE) });
  try {
    const c = await b.newContext({ viewport: { width: 1280, height: 900 } });
    await c.route("**/*", (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
    const p = await c.newPage();
    p.setDefaultTimeout(20000);
    p.on("pageerror", (e) => errors.push(e.message));
    p.on("dialog", (d) => d.accept());
    await p.goto(base + "/bbs/login.php");
    await p.locator("[name=mb_id]").fill(process.env.TEST_USER);
    await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
    await p.locator("[type=submit]").first().click();
    await p.waitForLoadState();
    const id = "eb" + Date.now().toString().slice(-7);
    await p.goto(base + "/adm/board_form.php");
    await p.locator("[name=gr_id]").selectOption({ index: 1 });
    await p.locator("[name=bo_table]").fill(id);
    await p.locator("[name=bo_subject]").fill("편집기 묶음");
    await p.locator("[role=tab][aria-controls=anc_004]").click();
    await p.locator("[name=bo_skin]").selectOption("bbscategory");
    await p.locator("input[type=submit]").first().click();
    await p.waitForTimeout(600);
    await p.goto(base + "/bbs/write.php?bo_table=" + id);
    await p.locator("#wr_subject").fill("이미지 묶음");
    const ed = p.locator(".tiptap");
    await ed.click();
    await p.keyboard.insertText("첫 문단");

    // 1. 그림 올려서 묶기: 3열
    await p.getByRole("button", { name: "이미지 묶음", exact: true }).click();
    await p.getByLabel("묶음 열 수", { exact: true }).selectOption("3");
    const [chooser] = await Promise.all([p.waitForEvent("filechooser"), p.getByRole("button", { name: "그림 올려서 묶기", exact: true }).click()]);
    await chooser.setFiles([img("a.png"), img("b.png"), img("c.png")]);
    await p.waitForFunction(() => document.querySelectorAll(".tiptap .tomo-grid img").length === 3);
    assert.equal(await ed.locator(".tomo-grid.tomo-grid-3").count(), 1, "3열 묶음");

    // 2. 묶음 안 그림을 누르면 묶음 도구: 2열로 · 그림 추가
    await ed.locator(".tomo-grid img").first().click();
    const gridBar = p.locator(".tomo-editor-ctx[data-kind=grid]");
    assert.equal(await gridBar.isVisible(), true, "묶음 도구가 저절로 나옴");
    await gridBar.getByRole("button", { name: "2열", exact: true }).click();
    assert.equal(await ed.locator(".tomo-grid.tomo-grid-2").count(), 1, "2열로 바꿈");
    const [chooser2] = await Promise.all([p.waitForEvent("filechooser"), gridBar.getByRole("button", { name: "묶음에 그림 추가", exact: true }).click()]);
    await chooser2.setFiles([img("d.png")]);
    await p.waitForFunction(() => document.querySelectorAll(".tiptap .tomo-grid img").length === 4);

    // 3. 인용구 모양 · 구분선 모양 · 색 · 줄 간격 · 자간
    await ed.locator("p").first().click();
    await p.getByRole("button", { name: "인용", exact: true }).click();
    await p.getByRole("button", { name: "인용구 상자", exact: true }).click();
    assert.equal(await ed.locator("blockquote.tomo-quote-box").count(), 1, "상자 인용구");
    await p.keyboard.press("End");
    await p.getByRole("button", { name: "구분선", exact: true }).click();
    await p.getByRole("button", { name: "구분선 점선", exact: true }).click();
    assert.equal(await ed.locator("hr.tomo-hr-dotted").count(), 1, "점선 구분선");
    await ed.locator("blockquote p").first().click({ clickCount: 3 });
    await p.getByRole("button", { name: "색상", exact: true }).click();
    await p.getByRole("button", { name: "글자 색상 #1f618d", exact: true }).click();
    await p.getByRole("combobox", { name: "줄 간격", exact: true }).selectOption("2.0");
    await p.getByRole("combobox", { name: "자간", exact: true }).selectOption("1");
    const html = await p.evaluate(() => window.tomoEditors.get("wr_content").editor.getHTML());
    assert.match(html, /color: ?(#1f618d|rgb\(31, 97, 141\))/, "글자 색");
    assert.match(html, /line-height: ?2(\.0)?/, "줄 간격");
    assert.match(html, /letter-spacing: ?1px/, "자간");

    // 4. 저장 → 읽는 화면: PC는 나란히, 휴대폰은 한 줄에 하나씩
    await p.locator("#btn_submit").click();
    await p.waitForLoadState("load");
    const grid = p.locator("#bo_v_con .tomo-grid.tomo-grid-2");
    assert.equal(await grid.count(), 1, "저장 후에도 묶음");
    assert.equal(await grid.locator("img").count(), 4);
    assert.equal(await p.locator("#bo_v_con blockquote.tomo-quote-box").count(), 1, "저장 후에도 인용구 모양");
    assert.equal(await p.locator("#bo_v_con hr.tomo-hr-dotted").count(), 1, "저장 후에도 구분선 모양");
    const boxes = async () => grid.locator("img").evaluateAll((els) => els.slice(0, 2).map((e) => { const r = e.getBoundingClientRect(); return [Math.round(r.left), Math.round(r.top), Math.round(r.bottom)]; }));
    let [a, bb] = await boxes();
    assert.equal(a[1], bb[1], "PC: 같은 줄");
    assert.ok(bb[0] > a[0], "PC: 나란히");
    await p.setViewportSize({ width: 390, height: 844 });
    await p.waitForTimeout(200);
    [a, bb] = await boxes();
    assert.ok(bb[1] >= a[2] - 1, "휴대폰: 한 줄에 하나씩(위아래)");
    assert.equal(await p.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true, "휴대폰: 가로 넘침 없음");
    await p.setViewportSize({ width: 1280, height: 900 });

    // 5. 수정 화면에서 묶음 풀기 · 선택한 그림 묶기
    await p.goto(base + `/bbs/write.php?w=u&bo_table=${id}&wr_id=` + new URL(p.url()).searchParams.get("wr_id"));
    await p.locator(".tiptap .tomo-grid img").first().click();
    await p.locator(".tomo-editor-ctx[data-kind=grid]").getByRole("button", { name: "묶음 풀기", exact: true }).click();
    assert.equal(await p.locator(".tiptap .tomo-grid").count(), 0, "묶음 풀기");
    assert.equal(await p.locator(".tiptap > img, .tiptap > [data-resize-container]").count() >= 4, true, "그림은 그대로");
    await p.locator(".tiptap").click();
    await p.keyboard.press("Control+A");
    await p.getByRole("button", { name: "이미지 묶음", exact: true }).click();
    await p.getByLabel("묶음 열 수", { exact: true }).selectOption("4");
    await p.getByRole("button", { name: "선택한 그림 묶기", exact: true }).click();
    assert.equal(await p.locator(".tiptap .tomo-grid.tomo-grid-4 img").count(), 4, "선택한 그림 4장을 4열로 묶음");

    assert.deepEqual(errors, [], "자바스크립트 오류 없음");
    console.log("editor blocks smoke ok", id);
  } finally {
    await b.close();
  }
})().catch((e) => { console.error(e); process.exit(1); });
