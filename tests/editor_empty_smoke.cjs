/* Disposable installation; Tiptap must be selected. */
const { chromium } = require("playwright");
const assert = require("node:assert/strict");
(async () => {
  const base = process.env.TEST_URL,
    b = await chromium.launch({
      executablePath: "/usr/bin/chromium",
      args: ["--no-sandbox"],
    }),
    c = await b.newContext();
  await c.route("**/*", (r) =>
    r.request().url().startsWith(base) ? r.continue() : r.abort(),
  );
  const p = await c.newPage();
  p.setDefaultTimeout(15000);
  const errors = [];
  p.on("pageerror", (e) => errors.push(e.message));
  p.on("dialog", (d) => d.accept().catch(() => {}));
  await p.goto(base + "/bbs/login.php");
  await p.locator("[name=mb_id]").fill(process.env.TEST_USER);
  await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
  await p.locator("[type=submit]").click();
  await p.goto(base + "/adm/viewer_form.php");
  // Exercise serialization, required validation, and image-only documents through the live editor.
  const cases = await p.evaluate(() => {
    const { editor, field } = window.tomoEditors.get("main_content");
    return [
      "",
      "<p></p>",
      "<p><br></p><p></p>",
      "<p> &nbsp;&#8203; </p>",
      "<p><strong> </strong></p>",
    ].map((html) => {
      editor.commands.setContent(html);
      window.tomoEditorSync("main_content");
      return {
        value: field.value,
        valid: window.tomoEditorValidate("main_content"),
      };
    });
  });
  for (const value of cases)
    assert.deepEqual(value, { value: "", valid: false });
  for (const html of [
    "<p>0</p>",
    '<img src="/img/default_site_img/design_logo.png">',
    "<table><tbody><tr><td><p></p></td></tr></tbody></table>",
    "<hr>",
  ]) {
    const result = await p.evaluate((html) => {
      const item = window.tomoEditors.get("main_content");
      item.editor.commands.setContent(html);
      window.tomoEditorSync("main_content");
      return {
        value: item.field.value,
        valid: window.tomoEditorValidate("main_content"),
      };
    }, html);
    assert(result.value.length > 0);
    assert.equal(result.valid, true);
  }
  const mainSave = async () => {
    const done = p.waitForResponse(
      (r) =>
        r.url().endsWith("/viewer_form_update.php") &&
        r.request().method() === "POST",
    );
    await p.locator("[type=submit]").first().click();
    assert.equal((await (await done).json()).saved, true);
  };
  await p.evaluate(() =>
    window.tomoEditors.get("main_content").editor.commands.clearContent(),
  );
  await mainSave();
  await p.reload();
  assert.equal(await p.locator("[name=main_content]").inputValue(), "");
  const id = "e" + Date.now().toString().slice(-8);
  await p.goto(base + "/adm/boardgroup_form.php");
  await p.locator("[name=gr_id]").fill(id);
  await p.locator("[name=gr_subject]").fill("빈 공지 검증");
  await p.locator("[type=submit]").first().click();
  await p.goto(base + "/adm/board_form.php");
  await p.locator("[name=gr_id]").selectOption(id);
  await p.locator("[name=bo_table]").fill(id);
  await p.locator("[name=bo_subject]").fill("빈 공지 검증");
  await p.locator("[type=submit]").first().click();
  await p.waitForURL(/w=u/);
  const boardUrl = base + "/adm/board_form.php?w=u&bo_table=" + id;
  assert.equal(await p.locator("[name=bo_content_head]").inputValue(), "");
  await p.goto(base + "/bbs/board.php?bo_table=" + id);
  assert.equal(await p.locator(".board-notice-box").count(), 0);
  // A real notice survives, and deleting it removes the box again.
  const saveNotice = async (html) => {
    await p.goto(boardUrl);
    await p.evaluate(
      (html) =>
        window.tomoEditors
          .get("bo_content_head")
          .editor.commands.setContent(html),
      html,
    );
    await p.locator("[type=submit]").first().click();
    await p.waitForURL(/w=u/);
    await p.goto(base + "/bbs/board.php?bo_table=" + id);
  };
  await saveNotice("<p>확인할 공지</p>");
  assert.equal(await p.locator(".board-notice-box").count(), 1);
  assert(
    (await p.locator(".board-notice-box").innerText()).includes("확인할 공지"),
  );
  await saveNotice("<p><br></p>");
  assert.equal(await p.locator(".board-notice-box").count(), 0);
  // Bypass JavaScript to verify server-side normalization and group-apply variables.
  await p.goto(boardUrl);
  const posted = await p.evaluate(async () => {
    const form = document.querySelector("#fboardform"),
      data = new FormData(form);
    data.set("token", window.get_ajax_token());
    data.set("bo_content_head", "<p>&nbsp;<br></p>");
    data.set("chk_grp_content_head", "1");
    const r = await fetch(form.action, { method: "POST", body: data });
    return r.status;
  });
  assert.equal(posted, 200);
  await p.reload();
  assert.equal(await p.locator("[name=bo_content_head]").inputValue(), "");
  console.log("EMPTY_BOARD=" + id);
  assert.deepEqual(errors, []);
  console.log(
    "PASS: blank untouched/deleted/whitespace editors submit empty strings, image/table/zero retained, notice appears only for real content, server/group-save normalization",
  );
  await b.close();
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
