const { chromium } = require("playwright"),
  assert = require("node:assert/strict"),
  fs = require("node:fs");
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
  p.on("dialog", (d) => {
    console.log("DIALOG", d.message());
    d.accept();
  });
  const jsErrors = [];
  p.on("pageerror", (e) => jsErrors.push(e.message));
  await p.goto(base + "/bbs/login.php");
  await p.locator("[name=mb_id]").fill(process.env.TEST_USER);
  await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
  await p.locator("[type=submit]").first().click();
  const id = "px" + Date.now().toString().slice(-7);
  await p.goto(base + "/adm/boardgroup_form.php");
  await p.locator("[name=gr_id]").fill(id);
  await p.locator("[name=gr_subject]").fill("픽시브 테스트");
  await p.locator("input[type=submit]").first().click();
  await p.waitForTimeout(400);
  await p.goto(base + "/adm/board_form.php");
  await p.locator("[name=gr_id]").selectOption(id);
  await p.locator("[name=bo_table]").fill(id);
  await p.locator("[name=bo_subject]").fill("픽시브 테스트");
  await p.locator("[role=tab][aria-controls=anc_004]").click();
  await p.locator("[name=bo_skin]").selectOption("pixiv");
  await p.locator("[role=tab][aria-controls=anc_003]").click();
  await p.locator("[name=bo_upload_count]").fill("5");
  await p.locator("[name=bo_use_secret]").selectOption("1");
  await p.locator("input[type=submit]").first().click();
  await p.waitForTimeout(700);
  assert(p.url().includes("w=u"), "board creation");
  await p.setViewportSize({ width: 390, height: 844 });
  await p.goto(base + "/bbs/write.php?bo_table=" + id);
  await p.locator("[name=wr_subject]").fill("두 이미지");
  await p.locator("[name=pixiv_type]").selectOption("manga");
  await p.locator("[name=pixiv_tags]").fill("판타지, 일상");
  await p.locator("[name=pixiv_note]").fill("작품 설명");
  await p.locator("#pixiv-files").setInputFiles([
    {
      name: "first.png",
      mimeType: "image/png",
      buffer: fs.readFileSync(process.env.TEST_IMAGE),
    },
    {
      name: "second.png",
      mimeType: "image/png",
      buffer: fs.readFileSync(process.env.TEST_IMAGE),
    },
  ]);
  assert.equal(await p.locator("#pixiv-file-list li").count(), 2);
  await p.setViewportSize({ width: 1280, height: 900 });
  await p
    .locator("#pixiv-file-list li")
    .nth(1)
    .locator(".px-drag-handle")
    .dragTo(p.locator("#pixiv-file-list li").first());
  assert(
    (await p.locator("#pixiv-file-list li").first().innerText()).includes(
      "second.png",
    ),
    "mouse drag changes order",
  );
  await p.getByRole("radio", { name: "first.png 대표 이미지로 선택" }).check();
  await p.setViewportSize({ width: 390, height: 844 });
  await p.locator("#btn_submit").click();
  await p.waitForURL(/wr_id=1/);
  console.log("SAVED", p.url());
  assert(await p.locator(".px-cover img").count());
  await p.locator(".px-cover img").evaluate((i) => i.decode());
  assert.equal(await p.locator(".px-note").innerText(), "작품 설명");
  await p.locator("#px-open").click();
  assert.equal(
    await p.evaluate(() => document.documentElement.scrollWidth <= innerWidth),
    true,
  );
  assert.equal(
    await p.locator("#px-reader").evaluate((e) => getComputedStyle(e).position),
    "fixed",
  );
  assert.equal(await p.locator(".px-images img").count(), 2);
  await p
    .locator(".px-images img")
    .first()
    .evaluate((i) => i.decode());
  const oldFirst = await p
    .locator(".px-images img")
    .first()
    .getAttribute("src");
  const oldSecond = await p
    .locator(".px-images img")
    .nth(1)
    .getAttribute("src");
  assert.notEqual(oldFirst, oldSecond);
  const selectedPath = new URL(oldSecond, base).searchParams
    .get("archive_path")
    .split("/")
    .pop();
  const coverPath = new URL(
    await p.locator(".px-cover img").getAttribute("src"),
    base,
  ).searchParams.get("archive_path");
  assert(
    coverPath.includes(selectedPath.replace(/\.[^.]+$/, "")),
    "cover matches selected second image",
  );
  await p.locator(".px-images button").first().click();
  assert.equal(
    await p.locator("#px-comic").evaluate((e) => getComputedStyle(e).position),
    "fixed",
  );
  assert.equal(await p.locator("[data-comic-progress]").innerText(), "1 / 2");
  await p.locator("[data-comic=next]").click();
  assert.equal(await p.locator("[data-comic-progress]").innerText(), "2 / 2");
  await p.setViewportSize({ width: 390, height: 844 });
  await p.goto(base + "/bbs/board.php?bo_table=" + id);
  await p.setViewportSize({ width: 1280, height: 900 });
  await p.goto(
    base +
      "/bbs/board.php?bo_table=" +
      id +
      "&px_type=manga&tag[]=판타지&tag[]=일상",
  );
  assert.equal(await p.locator(".px-item").count(), 1);
  const listCover = new URL(
    await p.locator(".px-item img").first().getAttribute("src"),
    base,
  ).searchParams.get("archive_path");
  assert(
    listCover.includes(selectedPath.replace(/\.[^.]+$/, "")),
    "list uses the selected cover too",
  );
  await p.goto(
    base + "/bbs/board.php?bo_table=" + id + "&tag[]=판타지&tag[]=없는태그",
  );
  assert.equal(await p.locator(".px-item").count(), 0);
  // A normal member cannot modify another member's artwork by submitting directly.
  const other = await b.newContext();
  await other.route("**/*", (r) =>
    r.request().url().startsWith(base) ? r.continue() : r.abort(),
  );
  const op = await other.newPage();
  op.on("dialog", (d) => d.accept());
  await op.goto(base + "/bbs/login.php");
  await op.locator("[name=mb_id]").fill("tomo_viewer");
  await op.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
  await op.locator("[type=submit]").first().click();
  await op.goto(base + "/bbs/board.php?bo_table=" + id);
  const publicThumb = op.locator(".px-item img").first();
  await publicThumb.evaluate((i) => i.decode());
  assert((await publicThumb.getAttribute("src")).includes("archive_file.php"));
  await op.goto(base + "/bbs/write.php?bo_table=" + id);
  const otherToken = await op.locator("[name=pixiv_token]").inputValue();
  const forbidden = await other.request.post(base + "/bbs/write_update.php", {
    headers: { referer: base + "/bbs/write.php?bo_table=" + id },
    form: {
      bo_table: id,
      w: "u",
      wr_id: "1",
      wr_subject: "변조 시도",
      wr_content: "변조",
      pixiv_type: "manga",
      pixiv_token: otherToken,
      pixiv_tags: "",
      pixiv_note: "",
      pixiv_order: "0,1",
    },
  });
  const forbiddenHtml = await forbidden.text();
  assert(!forbiddenHtml.includes("정상적으로 수정되었습니다"));
  await other.close();
  await p.goto(base + "/bbs/board.php?bo_table=" + id + "&wr_id=1");
  assert((await p.locator("h1").innerText()).includes("두 이미지"));
  // A forged token cannot submit a post in the administrator session.
  const forged = await c.request.post(base + "/bbs/write_update.php", {
    headers: { referer: base + "/bbs/write.php?bo_table=" + id },
    form: {
      bo_table: id,
      w: "",
      wr_subject: "위조 요청",
      wr_content: "내용",
      pixiv_type: "novel",
      pixiv_token: "forged",
    },
  });
  assert((await forged.text()).includes("작성 인증"));
  // Invalid image must be rejected before a post is created.
  await p.goto(base + "/bbs/write.php?bo_table=" + id);
  const token = await p.locator("[name=pixiv_token]").inputValue();
  const bad = await c.request.post(base + "/bbs/write_update.php", {
    headers: { referer: base + "/bbs/write.php?bo_table=" + id },
    multipart: {
      bo_table: id,
      w: "",
      wr_subject: "잘못된 이미지",
      wr_content: "",
      pixiv_type: "manga",
      pixiv_tags: "",
      pixiv_note: "",
      pixiv_order: "0",
      pixiv_token: token,
      "bf_file[]": {
        name: "fake.png",
        mimeType: "image/png",
        buffer: Buffer.from("not an image"),
      },
    },
  });
  assert((await bad.text()).includes("이미지 파일만 첨부"));
  await p.goto(base + "/bbs/write.php?w=u&bo_table=" + id + "&wr_id=1");
  assert.equal(await p.locator("#pixiv-file-list li").count(), 2);
  assert(
    await p
      .getByRole("radio", { name: /first.*대표 이미지로 선택/ })
      .isChecked(),
    "saved cover restored",
  );
  await p
    .locator("#pixiv-file-list li")
    .first()
    .getByRole("button", { name: "뒤로" })
    .click();
  await p.locator("#btn_submit").click();
  await p.waitForURL(/wr_id=1/);
  await p.locator("#px-open").click();
  await p
    .locator(".px-images img")
    .first()
    .evaluate((i) => i.decode());
  assert.equal(
    await p.locator(".px-images img").first().getAttribute("src"),
    oldSecond,
    "edited order persisted",
  );
  // Deleting one existing image while adding another preserves the untouched file.
  await p.goto(base + "/bbs/write.php?w=u&bo_table=" + id + "&wr_id=1");
  await p
    .locator("#pixiv-file-list li")
    .first()
    .getByRole("button", { name: "삭제" })
    .click();
  assert(
    await p
      .getByRole("radio", { name: /second.*대표 이미지로 선택/ })
      .isChecked(),
    "deleted cover falls back to remaining image",
  );
  await p.locator("#pixiv-files").setInputFiles({
    name: "third.png",
    mimeType: "image/png",
    buffer: fs.readFileSync(process.env.TEST_IMAGE),
  });
  await p.locator("#btn_submit").click();
  await p.waitForURL(/wr_id=1/);
  await p.locator("#px-open").click();
  assert.equal(await p.locator(".px-images img").count(), 2);
  assert.equal(
    await p.locator(".px-images img").first().getAttribute("src"),
    oldFirst,
  );
  assert.equal(
    (await c.request.get(oldSecond.replaceAll("&amp;", "&"))).status(),
    404,
  );
  assert.equal(
    (await c.request.get(oldFirst.replaceAll("&amp;", "&"))).status(),
    200,
  );
  await p.goto(base + "/bbs/write.php?bo_table=" + id);
  await p.locator("[name=wr_subject]").fill("소설 테스트");
  await p.locator("[name=pixiv_type]").selectOption("novel");
  await p
    .locator("[name=wr_content]")
    .fill("소설 본문을 읽습니다. ".repeat(1000));
  await p.locator("#btn_submit").click();
  await p.waitForURL(/wr_id=2/);
  await p.locator("#px-open").click();
  await p.locator("[data-action=settings]").click();
  await p.locator("[data-action=pages]").click();
  assert(
    await p
      .locator(".px-novel")
      .evaluate((e) => e.classList.contains("px-paged")),
  );
  let prog = await p.locator("[data-progress]").innerText();
  assert(Number(prog.split("/")[1]) > 1, "long novel paginates: " + prog);
  await p.locator("[data-action=next]").click();
  assert((await p.locator("[data-progress]").innerText()).startsWith("2 /"));
  await p.setViewportSize({ width: 390, height: 844 });
  assert.equal(
    await p.locator("#px-reader").evaluate((e) => getComputedStyle(e).position),
    "fixed",
  );
  // Novel cards show the character count (spaces excluded) in the thumbnail badge, where image works show "N장".
  await p.goto(base + "/bbs/board.php?bo_table=" + id);
  const novelCard = p.locator(".px-item").filter({ hasText: "소설 테스트" });
  assert.equal((await novelCard.locator(".px-thumb .px-count").innerText()).trim(), "10,000자");
  assert.equal(await novelCard.locator(".px-item-meta .px-chars").count(), 0, "not next to the date");
  assert.equal(await p.locator(".px-item").filter({ hasNotText: "소설 테스트" }).locator(".px-chars").count(), 0);
  // Secret tags and images are not exposed to another logged-in member.
  await p.setViewportSize({ width: 1280, height: 900 });
  await p.goto(base + "/bbs/write.php?bo_table=" + id);
  await p.locator("[name=wr_subject]").fill("비밀 작품");
  await p.locator("[name=pixiv_tags]").fill("감춰진태그");
  await p.locator("[name=secret]").check();
  await p.locator("#pixiv-files").setInputFiles(process.env.TEST_IMAGE);
  await p.locator("#btn_submit").click();
  await p.waitForURL(/wr_id=3/);
  await p.locator("#px-open").click();
  const privateImage = await p
    .locator(".px-images img")
    .first()
    .getAttribute("src");
  const outsider = await b.newContext();
  const outsiderPage = await outsider.newPage();
  outsiderPage.on("dialog", (d) => d.accept());
  await outsiderPage.goto(base + "/bbs/login.php");
  await outsiderPage.locator("[name=mb_id]").fill("tomo_viewer");
  await outsiderPage
    .locator("[name=mb_password]")
    .fill(process.env.TEST_PASSWORD);
  await outsiderPage.locator("[type=submit]").first().click();
  assert.equal(
    (
      await outsider.request.get(privateImage.replaceAll("&amp;", "&"))
    ).status(),
    403,
  );
  await outsiderPage.goto(
    base + "/bbs/board.php?bo_table=" + id + "&tag[]=감춰진태그",
  );
  assert.equal(await outsiderPage.locator(".px-item").count(), 0);
  await outsider.close();
  assert.deepEqual(jsErrors, []);
  console.log(
    "PASS board setup, multiple images/order, detail/reader, tag AND, edit, novel (+ list character count)",
  );
  await b.close();
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
