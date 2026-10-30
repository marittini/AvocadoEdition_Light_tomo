/* Disposable installation with email delivery disabled. */
const { chromium } = require("playwright");
const assert = require("node:assert/strict");
(async () => {
  const base = process.env.TEST_URL,
    b = await chromium.launch({
      executablePath: "/usr/bin/chromium",
      args: ["--no-sandbox"],
    }),
    admin = await b.newContext();
  await admin.route("**/*", (r) =>
    r.request().url().startsWith(base) ? r.continue() : r.abort(),
  );
  const p = await admin.newPage();
  p.setDefaultTimeout(15000);
  const errors = [];
  p.on("pageerror", (e) => {
    errors.push(e.message);
    console.log("JS", e.message);
  });
  p.on("dialog", (d) => {
    console.log("ADMIN DIALOG", d.message());
    d.accept().catch(() => {});
  });
  const login = async (page, id) => {
    await page.goto(base + "/bbs/login.php");
    await page.locator("[name=mb_id]").fill(id);
    await page.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
    await page.locator("[type=submit]").first().click();
  };
  await login(p, process.env.TEST_USER);
  const save = async () => {
    const done = p.waitForResponse(
      (r) =>
        r.url().endsWith("/site_config_form_update.php") &&
        r.request().method() === "POST",
    );
    await p.locator("input[type=submit]").first().click();
    assert.equal((await (await done).json()).saved, true);
  };
  await p.goto(base + "/adm/site_config_form.php");
  const originalOpen = await p.locator("#cf_open").isChecked(),
    originalRegister = await p.locator("#cf_1").isChecked();
  await p.locator("#cf_open").check();
  await p.locator("#cf_1").check();
  await p.locator("[role=tab][aria-controls=tomo-member-settings]").click();
  await p.locator("[name=cf_tomo_require_approval]").check();
  await save();
  const signup = async (id) => {
    const context = await b.newContext();
    await context.route("**/*", (r) =>
      r.request().url().startsWith(base) ? r.continue() : r.abort(),
    );
    const page = await context.newPage();
    page.on("dialog", (d) => {
      console.log("REGISTER DIALOG", d.message());
      d.accept().catch(() => {});
    });
    await page.goto(base + "/bbs/register_form.php");
    await page.locator("[name=mb_id]").fill(id);
    await page.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
    await page.locator("[name=mb_password_re]").fill(process.env.TEST_PASSWORD);
    await page.locator("[name=mb_name]").fill("승인검증" + id);
    await page.locator("[name=mb_birth]").fill("2000");
    await page.locator("#btn_submit").click();
    await page.waitForURL(/register_result.php/);
    return { context, page };
  };
  const id = "approve" + Date.now().toString().slice(-7),
    user = await signup(id);
  assert(
    (await user.page.locator("body").innerText()).includes("가입 신청 접수"),
  );
  assert((await user.page.locator("body").innerText()).includes("승인"));
  assert(
    (
      await (
        await user.context.request.get(base + "/bbs/member_confirm.php")
      ).text()
    ).includes("로그인 한 회원만"),
  );
  const tryLogin = async () => {
    const messages = [];
    const handler = (d) => messages.push(d.message());
    user.page.on("dialog", handler);
    await login(user.page, id);
    await user.page.waitForTimeout(100);
    user.page.off("dialog", handler);
    return messages;
  };
  assert((await tryLogin()).some((text) => text.includes("승인")));
  await p.goto(base + "/adm/member_approval.php");
  assert.equal(
    await p.locator('input[name="member_ids[]"][value="' + id + '"]').count(),
    1,
  );
  await admin.request.post(base + "/adm/member_approval.php", {
    form: { token: "forged", approval_action: "approve", "member_ids[]": id },
  });
  await p.reload();
  assert.equal(await p.locator('input[value="' + id + '"]').count(), 1);
  await p.locator('input[value="' + id + '"]').check();
  await p.getByRole("button", { name: "선택 회원 승인", exact: true }).click();
  assert((await p.locator("[role=status]").innerText()).includes("1명"));
  await tryLogin();
  assert(
    !(
      await (
        await user.context.request.get(base + "/bbs/member_confirm.php")
      ).text()
    ).includes("로그인 한 회원만"),
  );
  // A normal member cannot access or submit the approval screen.
  const forbidden = await (
    await user.context.request.get(base + "/adm/member_approval.php")
  ).text();
  assert(!forbidden.includes('name="member_ids[]"'));
  await p.goto(base + "/adm/member_approval.php?status=approved");
  await p.locator('input[value="' + id + '"]').check();
  await p.getByRole("button", { name: "선택 회원 거절", exact: true }).click();
  assert(
    (
      await (
        await user.context.request.get(base + "/bbs/member_confirm.php")
      ).text()
    ).includes("로그인 한 회원만"),
  );
  assert((await tryLogin()).some((text) => text.includes("승인되지")));
  // Owner state cannot be changed even with a valid administrative token.
  await p.goto(base + "/adm/member_approval.php");
  const token = await p.locator("[name=token]").inputValue();
  await admin.request.post(base + "/adm/member_approval.php", {
    form: {
      token,
      approval_action: "reject",
      "member_ids[]": process.env.TEST_USER,
    },
  });
  await p.goto(base + "/adm/member_approval.php");
  assert.equal(await p.locator("input[name=token]").count(), 1);
  // Turning approval off affects new registrations; it does not auto-approve rejected users.
  await p.goto(base + "/adm/site_config_form.php#tomo-member-settings");
  await p.locator("[name=cf_tomo_require_approval]").uncheck();
  await save();
  const second = await signup("open" + Date.now().toString().slice(-7));
  assert(
    !(await second.page.locator("body").innerText()).includes("가입 신청 접수"),
  );
  assert(
    !(
      await (
        await second.context.request.get(base + "/bbs/member_confirm.php")
      ).text()
    ).includes("로그인 한 회원만"),
  );
  await second.context.close();
  await p.goto(base + "/adm/site_config_form.php#anc_001");
  await p.locator("#cf_open").setChecked(originalOpen);
  await p.locator("#cf_1").setChecked(originalRegister);
  await save();
  await user.context.close();
  assert.deepEqual(errors, []);
  console.log(
    "PASS real signup pending/no automatic login, approval enables login, rejection revokes sessions, ordinary-member/CSRF/owner protection, existing members and open signup retained",
  );
  await b.close();
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
