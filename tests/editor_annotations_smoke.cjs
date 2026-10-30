/* Only run on a disposable installed PHP site with Tiptap selected. */
const { chromium } = require("playwright");
const assert = require("node:assert/strict");
const fs = require("node:fs");
(async () => {
  const base = process.env.TEST_URL;
  const browser = await chromium.launch({
    executablePath: "/usr/bin/chromium",
    args: ["--no-sandbox"],
  });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 1000 },
  });
  await context.route("**/*", (r) =>
    r.request().url().startsWith(base) ? r.continue() : r.abort(),
  );
  const p = await context.newPage(),
    errors = [];
  p.setDefaultTimeout(15000);
  p.on("pageerror", (e) => errors.push(e.message));
  p.on("dialog", (d) => d.accept().catch(() => {}));
  await p.goto(base + "/bbs/login.php");
  await p.locator("[name=mb_id]").fill(process.env.TEST_USER);
  await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
  await p.locator("[type=submit]").click();
  await p.goto(base + "/adm/viewer_form.php");
  const selectText = async (text) =>
    p.evaluate((text) => {
      const e = tomoEditors.get("main_content").editor;
      let selected = false;
      e.state.doc.descendants((node, pos) => {
        if (!selected && node.isText && node.text.includes(text)) {
          const from = pos + node.text.indexOf(text);
          e.commands.setTextSelection({ from, to: from + text.length });
          selected = true;
        }
      });
      if (!selected) throw Error("missing text " + text);
    }, text);
  await p.evaluate(() =>
    tomoEditors
      .get("main_content")
      .editor.commands.setContent("<p><strong>漢字</strong>と 비밀 이야기</p>"),
  );
  await selectText("漢字");
  await p.getByRole("button", { name: "루비·스포일러", exact: true }).click();
  await p
    .getByRole("textbox", { name: "루비 발음·설명", exact: true })
    .fill("かんじ");
  await p.getByRole("button", { name: "루비 적용", exact: true }).click();
  assert.equal(await p.locator(".tiptap ruby rt").textContent(), "かんじ");
  assert.equal(await p.locator(".tiptap ruby strong").textContent(), "漢字");
  await selectText("비밀");
  await p.getByRole("button", { name: "스포일러 블러", exact: true }).click();
  assert.equal(await p.locator(".tiptap .tomo-spoiler").textContent(), "비밀");
  await selectText("이야기");
  await p.getByRole("button", { name: "추가 서식", exact: true }).click();
  await p
    .getByRole("spinbutton", { name: "자간(px)", exact: true })
    .fill("1.5");
  await p
    .getByRole("spinbutton", { name: "자간(px)", exact: true })
    .dispatchEvent("change");
  assert.match(
    await p.locator("[name=main_content]").inputValue(),
    /letter-spacing: 1.5px/,
  );
  // HTML round trip preserves ruby base exactly once and treats annotation text literally.
  await p.getByRole("button", { name: "HTML", exact: true }).click();
  await p.getByRole("button", { name: "HTML 적용", exact: true }).click();
  assert.equal(
    await p.locator(".tiptap ruby .tomo-ruby-base").textContent(),
    "漢字",
  );
  assert.equal(await p.locator(".tiptap ruby rt").textContent(), "かんじ");
  assert.equal(await p.locator(".tiptap ruby strong").textContent(), "漢字");
  // Staged gallery upload must not insert until requested; filenames are text nodes.
  await p.getByRole("button", { name: "이미지 관리", exact: true }).click();
  const img = fs.readFileSync(process.env.TEST_IMAGE);
  await p
    .getByLabel("관리창 이미지 업로드", { exact: true })
    .setInputFiles({
      name: "<script>unsafe</script>.png",
      mimeType: "image/png",
      buffer: img,
    });
  await p.waitForFunction(
    () =>
      tomoEditors.get("main_content").uploading === 0 &&
      document.querySelectorAll(".tomo-editor-gallery-card").length === 1,
  );
  assert.equal(await p.locator(".tiptap img").count(), 0);
  assert.equal(await p.locator(".tomo-editor-gallery script").count(), 0);
  const uploadURL = await p
    .locator(".tomo-editor-gallery img")
    .getAttribute("src");
  assert(uploadURL.endsWith(".webp"));
  await p
    .getByRole("button", { name: "선택 이미지 삽입", exact: true })
    .click();
  assert.equal(await p.locator(".tiptap img").count(), 1);
  await p.locator(".tomo-editor-gallery-card input").check();
  await p
    .getByRole("button", { name: "선택 이미지 본문에서 제거", exact: true })
    .click();
  assert.equal(await p.locator(".tiptap img").count(), 0);
  await p.getByRole("button", { name: "실행 취소", exact: true }).click();
  assert.equal(await p.locator(".tiptap img").count(), 1);
  // Real CSRF and guest denial at the upload endpoint.
  const token = await p.locator(".tomo-editor").getAttribute("data-token");
  const denied = await context.request.post(
    base + "/plugin/editor/tiptap/upload.php",
    {
      multipart: {
        editor_token: "forged",
        image: { name: "x.png", mimeType: "image/png", buffer: img },
      },
    },
  );
  assert.equal(denied.status(), 403);
  const guest = await browser.newContext();
  const guestUpload = await guest.request.post(
    base + "/plugin/editor/tiptap/upload.php",
    {
      multipart: {
        editor_token: token,
        image: { name: "x.png", mimeType: "image/png", buffer: img },
      },
    },
  );
  assert.equal(guestUpload.status(), 403);
  await guest.close();
  const save = p.waitForResponse(
    (r) =>
      r.url().endsWith("/viewer_form_update.php") &&
      r.request().method() === "POST",
  );
  await p.locator("[type=submit]").first().click();
  assert.equal((await (await save).json()).saved, true);
  await p.reload();
  assert.equal(await p.locator(".tiptap ruby rt").textContent(), "かんじ");
  assert.equal(await p.locator(".tiptap ruby strong").textContent(), "漢字");
  assert.equal(
    await p.locator(".tiptap ruby .tomo-ruby-base").textContent(),
    "漢字",
  );
  assert.equal(await p.locator(".tiptap .tomo-spoiler").textContent(), "비밀");
  assert.equal(await p.locator(".tiptap img").count(), 1);
  // Icon menus retain accessible names, a focus hint, and 44px mobile targets.
  for (const width of [320, 390, 768, 1440]) {
    await p.setViewportSize({ width, height: 1000 });
    await p.getByRole("button", { name: "루비·스포일러", exact: true }).click();
    assert(
      await p.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
    );
    const toggle = p.getByRole("button", { name: "이미지 관리", exact: true });
    assert.equal(await toggle.locator("svg").count(), 1);
    assert.equal(await toggle.getAttribute("title"), "이미지 관리");
    await toggle.focus();
    assert.equal(
      await p.locator(".tomo-editor-hint").textContent(),
      "이미지 관리",
    );
    if (width <= 390) assert((await toggle.boundingBox()).height >= 44);
    if (process.env.SCREENSHOT_DIR) {
      await p
        .locator(".tomo-editor")
        .screenshot({
          path: process.env.SCREENSHOT_DIR + "/editor-icons-" + width + ".png",
        });
    }
  }
  // Actual public output includes keyboard-operable spoilers; private images remain denied.
  await p.goto(base + "/main.php");
  const spoiler = p.locator(".tomo-spoiler").first();
  assert.equal(await spoiler.getAttribute("role"), "button");
  await spoiler.focus();
  await spoiler.press("Enter");
  assert.equal(await spoiler.getAttribute("aria-pressed"), "true");
  await p.waitForFunction(
    () =>
      getComputedStyle(document.querySelector(".tomo-spoiler")).filter ===
      "none",
  );
  const guest2 = await browser.newContext();
  const relative = new URL(uploadURL).pathname.split("/data/editor/")[1];
  assert.equal(
    (
      await guest2.request.get(
        base +
          "/bbs/archive_file.php?archive_kind=editor&archive_path=" +
          encodeURIComponent(relative),
      )
    ).status(),
    403,
  );
  await guest2.close();
  assert.deepEqual(errors, []);
  console.log(
    "PASS: ruby/spoiler/spacing, HTML and saved round trips, staged gallery/WebP/undo, auth/CSRF/private file gate, icon names/focus/44px/mobile, published keyboard spoiler",
  );
  await browser.close();
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
