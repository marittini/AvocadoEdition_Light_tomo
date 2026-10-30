/* Run only against a disposable PHP/MySQL installation. */
const { chromium } = require("playwright");
const assert = require("node:assert/strict");
const fs = require("node:fs");
(async () => {
  const base = process.env.TEST_URL,
    b = await chromium.launch({
      executablePath: process.env.CHROMIUM || "/usr/bin/chromium",
      args: ["--no-sandbox"],
    });
  const context = await b.newContext();
  await context.route("**/*", (r) =>
    r.request().url().startsWith(base) ? r.continue() : r.abort(),
  );
  const p = await context.newPage();
  p.setDefaultTimeout(15000);
  const errors = [];
  p.on("pageerror", (e) => {
    errors.push(e.message);
    console.log("JS ERROR", e.message);
  });
  p.on("dialog", (d) => {
    console.log("DIALOG", d.type(), d.message().slice(0, 140));
    d.accept().catch(() => {});
  });
  const login = async (page, user = process.env.TEST_USER) => {
    await page.goto(base + "/bbs/login.php");
    await page.locator("[name=mb_id]").fill(user);
    await page.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
    await page.locator("[type=submit]").first().click();
  };
  await login(p);
  const save = async (endpoint) => {
    const wait = p.waitForResponse(
      (r) =>
        r.url().endsWith("/" + endpoint + "_update.php") &&
        r.request().method() === "POST",
    );
    await p.locator("[type=submit]").first().click();
    const r = await wait;
    assert.equal(r.status(), 200);
    assert.equal((await r.json()).saved, true);
  };
  for (const path of [
    "design_form",
    "site_config_form",
    "config_form",
    "board_form",
  ]) {
    console.log("CHECK tabs", path);
    await p.goto(base + "/adm/" + path + ".php");
    assert.equal(await p.locator("[role=tabpanel]:visible").count(), 1, path);
    const tabs = p.locator("[role=tab]");
    assert((await tabs.count()) >= 2);
    await tabs.nth(1).click();
    assert.equal(await tabs.nth(1).getAttribute("aria-selected"), "true");
    assert.equal(await p.locator("[role=tabpanel]:visible").count(), 1);
    await tabs.nth(1).press("ArrowRight");
    assert.equal(await tabs.nth(2).getAttribute("aria-selected"), "true");
    assert.equal(
      await p
        .locator('.archive-preview, .archive-layout, [data-text="DB관리"]')
        .count(),
      0,
    );
  }
  await p.goto(base + "/adm/design_form.php#anc_004");
  assert.equal(await p.locator("#anc_004").isVisible(), true);
  assert.equal(
    await p.getByRole("link", { name: "가이드 미리보기" }).count(),
    1,
  );
  // Hidden required inputs open their panel instead of preventing a recoverable save.
  await p.evaluate(() => {
    const field = document.querySelector(
      "#anc_001 input[type=text]:not([readonly])",
    );
    field.required = true;
    field.value = "";
    document.querySelector("form").reportValidity();
  });
  assert.equal(await p.locator("#anc_001").isVisible(), true);
  console.log("CHECK disk");
  await p.goto(base + "/adm/disk_usage.php");
  await p.locator("#disk-start").click();
  await p.waitForFunction(
    () =>
      document.querySelector("#disk-status").textContent.includes("측정 완료"),
    null,
    { timeout: 45000 },
  );
  assert((await p.locator("#disk-percent").innerText()).includes("%"));
  assert(
    Number(await p.locator("#disk-meter").getAttribute("aria-valuenow")) > 0,
  );
  assert.notEqual(await p.locator("#disk-db-bytes").innerText(), "확인 불가");
  const shot = process.env.SCREENSHOT_DIR;
  if (shot) {
    fs.mkdirSync(shot, { recursive: true });
    await p.screenshot({ path: shot + "/disk-desktop.png", fullPage: true });
  }
  await p.goto(base + "/adm/site_config_form.php#tomo-media-settings");
  const previous = await p.locator("#cf_editor").inputValue();
  await p.locator("#cf_editor").selectOption("tiptap");
  await save("site_config_form");
  await p.goto(base + "/adm/viewer_form.php");
  await p.locator(".tiptap").waitFor();
  await p.locator(".tiptap").click();
  await p.locator(".tiptap").press("Control+a");
  await p.locator(".tiptap").press("Backspace");
  await p.locator(".tiptap").fill("새 편집기 한글 저장 검증");
  await p.locator(".tiptap").press("Control+a");
  if (
    (await p
      .getByRole("button", { name: "굵게", exact: true })
      .getAttribute("aria-pressed")) !== "true"
  )
    await p.getByRole("button", { name: "굵게", exact: true }).click();
  assert.equal(
    await p.locator(".tiptap strong").innerText(),
    "새 편집기 한글 저장 검증",
  );
  console.log("CHECK editor");
  const wrapper = p.locator(".tomo-editor");
  const endpoint = await wrapper.getAttribute("data-upload"),
    token = await wrapper.getAttribute("data-token");
  const denied = await context.request.post(endpoint, {
    multipart: {
      editor_token: "forged",
      image: {
        name: "x.png",
        mimeType: "image/png",
        buffer: fs.readFileSync(process.env.TEST_IMAGE),
      },
    },
  });
  assert.equal(denied.status(), 403);
  const bad = await context.request.post(endpoint, {
    multipart: {
      editor_token: token,
      image: {
        name: "x.svg",
        mimeType: "image/svg+xml",
        buffer: Buffer.from("<svg></svg>"),
      },
    },
  });
  assert.equal(bad.status(), 422);
  // Exercise the real editor's upload callback, not just its endpoint.
  await p.evaluate(async (image) => {
    const binary = atob(image),
      file = new File(
        [Uint8Array.from(binary, (c) => c.charCodeAt(0))],
        "본문.png",
        { type: "image/png" },
      );
    await window.tomoEditors.get("main_content").upload([file]);
  }, fs.readFileSync(process.env.TEST_IMAGE).toString("base64"));
  await p.waitForFunction(() => document.querySelector(".tiptap img"));
  const image = await p.locator(".tiptap img").getAttribute("src");
  assert(image.endsWith(".webp"));
  assert.equal((await context.request.get(image)).status(), 200);
  await save("viewer_form");
  await p.reload();
  assert(
    (await p.locator(".tiptap").innerText()).includes(
      "새 편집기 한글 저장 검증",
    ),
  );
  assert.equal(await p.locator(".tiptap img").count(), 1);
  for (const width of [390, 1440]) {
    await p.setViewportSize({ width, height: 900 });
    await p.reload();
    assert(await p.locator(".tomo-editor-toolbar").isVisible());
    assert(
      await p.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
    );
    if (shot)
      await p.screenshot({
        path: shot + "/editor-" + width + ".png",
        fullPage: true,
      });
  }
  // Test post creation with a live rich editor, then IP reveal only in the admin nickname menu.
  console.log("CHECK board");
  const id = "r" + Date.now().toString().slice(-8);
  await p.goto(base + "/adm/boardgroup_form.php");
  await p.locator("[name=gr_id]").fill(id);
  await p.locator("[name=gr_subject]").fill("회귀 검증");
  await p.locator("[type=submit]").first().click();
  await p.goto(base + "/adm/board_form.php");
  await p.evaluate((id) => {
    const f = document.querySelector("form");
    for (const [key, value] of Object.entries({
      gr_id: id,
      bo_table: id,
      bo_subject: "회귀 검증",
      bo_upload_count: 2,
      bo_use_dhtml_editor: 1,
    })) {
      const el = f.elements.namedItem(key);
      if (el.type === "checkbox") el.checked = Boolean(value);
      else el.value = value;
    }
  }, id);
  await p.locator("[type=submit]").first().click();
  await p.goto(base + "/bbs/write.php?bo_table=" + id);
  await p.locator("[name=wr_subject]").fill("편집기 게시글");
  await p.locator(".tiptap").fill("본문 저장");
  await p
    .locator('input[name="bf_file[]"]')
    .first()
    .setInputFiles(process.env.TEST_IMAGE);
  await p.locator("[type=submit]").first().click();
  await p.waitForURL(/wr_id=1/);
  const ip = p.locator(".sv-post-ip").first();
  assert.equal(await ip.isVisible(), false);
  await p.locator("#bo_v .sv_member").first().click();
  assert.equal(await ip.isVisible(), true);
  const member = await b.newContext();
  const memberPage = await member.newPage();
  await login(memberPage, "tomo_viewer");
  await memberPage.goto(base + "/bbs/board.php?bo_table=" + id + "&wr_id=1");
  assert.equal(await memberPage.locator("#bo_v").count(), 1);
  assert.equal(await memberPage.locator(".sv-post-ip").count(), 0);
  await member.close();
  for (const width of [390, 1440]) {
    await p.setViewportSize({ width, height: 900 });
    await p.goto(base + "/adm/design_form.php#anc_001");
    assert(
      await p.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
    );
    if (shot)
      await p.screenshot({
        path: shot + "/tabs-" + width + ".png",
        fullPage: true,
      });
  }
  await p.goto(base + "/adm/site_config_form.php#tomo-media-settings");
  await p.locator("#cf_editor").selectOption(previous);
  await save("site_config_form");
  assert.deepEqual(errors, []);
  console.log(
    "PASS: real tab switching/keyboard/deep links/validation, no preview or DB menu, storage gauge+DB, rich editor save/image upload/mobile, post attachment, admin-only IP nickname menu",
  );
  await b.close();
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
