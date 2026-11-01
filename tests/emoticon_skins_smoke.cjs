// 이모티콘 고르기 · 목록 팝업 · 표시: 방명록(글 · 댓글), pixiv(댓글), 다이어리(본문 편집기).
// Env: TEST_URL, TEST_USER(최고관리자), TEST_PASSWORD, TEST_IMAGE(png/jpg), TEST_SQL(예: "mysql -uroot g5").
const { chromium } = require("playwright"),
  assert = require("node:assert/strict"),
  { execSync } = require("node:child_process");
(async () => {
  const base = process.env.TEST_URL,
    prefix = process.env.TEST_PREFIX || "avo_",
    sql = (q) => execSync(process.env.TEST_SQL, { input: q }).toString(),
    b = await chromium.launch(),
    errors = [];
  const delay = sql(`SELECT cf_delay_sec FROM ${prefix}config;`).split("\n")[1].trim();
  sql(`UPDATE ${prefix}config SET cf_delay_sec=0;`);
  try {
    const c = await b.newContext({ viewport: { width: 1280, height: 900 } });
    await c.route("**/*", (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
    const p = await c.newPage();
    p.setDefaultTimeout(15000);
    p.on("pageerror", (e) => errors.push(e.message));
    p.dialogs = [];
    p.on("dialog", (d) => { p.dialogs.push(d.message()); d.accept(); });
    await p.goto(base + "/bbs/login.php");
    await p.locator("[name=mb_id]").fill(process.env.TEST_USER);
    await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
    await p.locator("[type=submit]").first().click();
    await p.waitForLoadState();

    // 이모티콘 하나 등록(관리자 화면)
    const code = "(e" + Date.now().toString().slice(-6) + ")";
    await p.goto(base + "/adm/emoticon_list.php");
    await p.locator("#me_text").fill(code);
    await p.locator("#me_img").setInputFiles(process.env.TEST_IMAGE);
    await p.locator("#femoticonform input[type=submit]").click();
    await p.waitForLoadState();

    async function board(skin, extra = "") {
      const id = "em" + Date.now().toString().slice(-7);
      await p.goto(base + "/adm/board_form.php");
      await p.locator("[name=gr_id]").selectOption({ index: 1 });
      await p.locator("[name=bo_table]").fill(id);
      await p.locator("[name=bo_subject]").fill("이모티콘 " + skin);
      await p.locator("[role=tab][aria-controls=anc_004]").click();
      await p.locator("[name=bo_skin]").selectOption("custom_skin/" + skin);
      await p.locator("[role=tab][aria-controls=anc_003]").click();
      await p.locator("[name=bo_upload_count]").fill("2");
      await p.locator("input[type=submit]").first().click();
      await p.waitForTimeout(600);
      if (extra) sql(`UPDATE ${prefix}board SET ${extra} WHERE bo_table='${id}';`);
      return id;
    }
    // 버튼 → 목록 창(새 창) → 코드 고르기 → 원래 페이지에 들어감
    async function pick(scope, check) {
      const [win] = await Promise.all([p.waitForEvent("popup"), scope.locator("[data-emoticon-open]").first().click()]);
      await win.waitForLoadState();
      assert.match(win.url(), /\/bbs\/emoticon\.php$/, "목록 창 주소");
      const item = win.locator(`[data-emoticon-code="${code}"]`);
      assert.ok(await item.locator("img").count(), "창에 이미지와 코드");
      await item.click();
      await p.waitForFunction(check, code);
      assert.match(await win.locator(".emoticon-window-status").innerText(), /넣었어요/);
      await win.close();
    }
    const areaHas = (sel) => new Function("code", `return (document.querySelector(${JSON.stringify(sel)})?.value || "").includes(code);`);

    // 1. 방명록: 글과 댓글
    const gb = await board("guestbook", "bo_comment_level=1");
    await p.goto(base + "/bbs/board.php?bo_table=" + gb);
    await p.locator("#gb-write textarea").fill("안녕하세요 ");
    await pick(p.locator("#gb-write"), areaHas("#gb-write textarea"));
    assert.equal(await p.locator("#gb-write textarea").inputValue(), "안녕하세요 " + code, "커서 자리에 코드");
    await p.locator("#gb-write [type=submit]").click();
    await p.waitForLoadState();
    assert.equal(await p.locator(".gb-entry .gb-body img.emoticon").count(), 1, "방명록 글에 이모티콘 이미지");
    const entry = p.locator(".gb-entry").first();
    await entry.locator("[data-gb-toggle]").click();
    await pick(entry.locator(".gb-form-comment"), areaHas(".gb-entry .gb-form-comment textarea"));
    await entry.locator(".gb-form-comment [type=submit]").click();
    await p.waitForLoadState();
    assert.equal(await p.locator(".gb-comment .gb-body img.emoticon").count(), 1, "방명록 댓글에 이모티콘 이미지");

    // 2. pixiv: 댓글
    const px = await board("pixiv");
    await p.goto(base + "/bbs/write.php?bo_table=" + px);
    await p.locator("[name=wr_subject]").fill("이모티콘 그림");
    await p.locator("#pixiv-files").setInputFiles(process.env.TEST_IMAGE);
    await p.locator("#btn_submit").click();
    await p.waitForLoadState();
    const form = p.locator("form[name=fviewcomment]");
    await form.locator("#wr_content").fill("좋아요 ");
    await pick(form, areaHas("form[name=fviewcomment] #wr_content"));
    await form.locator("#btn_submit").click();
    await p.waitForLoadState();
    assert.equal(await p.locator("#bo_vc img.emoticon, .cmt_contents img.emoticon").count() >= 1, true, "pixiv 댓글에 이모티콘 이미지");

    // 3. 다이어리: 본문 편집기
    const dy = await board("diary");
    await p.goto(base + "/bbs/write.php?bo_table=" + dy);
    await p.locator("#wr_subject").fill("이모티콘 일기");
    const ed = p.locator(".ProseMirror").first();
    await ed.click();
    await p.keyboard.insertText("오늘은 ");
    await pick(p.locator(".diary-emoticon"), new Function("code", "return (document.querySelector('.ProseMirror')?.innerText || '').includes(code);"));
    assert.ok((await ed.innerText()).includes(code), "편집기 커서 자리에 코드");
    await p.locator("#btn_submit").click();
    await p.waitForLoadState();
    assert.equal(await p.locator("#bo_v_con img.emoticon").count(), 1, "다이어리 본문에 이모티콘 이미지");

    // 새 창이 막히면 페이지 안 팝업으로
    await p.goto(base + "/bbs/board.php?bo_table=" + gb);
    await p.evaluate(() => { window.open = () => null; });
    await p.locator("#gb-write textarea").fill("");
    await p.locator("#gb-write [data-emoticon-open]").click();
    assert.equal(await p.locator(".emoticon-pop").isVisible(), true, "막히면 페이지 안 팝업");
    await p.locator(`.emoticon-pop [data-emoticon-code="${code}"]`).click();
    assert.equal(await p.locator("#gb-write textarea").inputValue(), code);

    assert.deepEqual(errors, [], "자바스크립트 오류 없음");
    console.log("emoticon skins smoke ok", gb, px, dy);
  } finally {
    sql(`UPDATE ${prefix}config SET cf_delay_sec=${Number(delay) || 0};`);
    await b.close();
  }
})().catch((e) => { console.error(e); process.exit(1); });
