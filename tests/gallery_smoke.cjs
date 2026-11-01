// 갤러리 · 글 보관함(custom_skin/gallery): 분류 폴더(펼침 기억), 그림 · 글 카드, 본문 팝업(주소 · 뒤로 가기 · Esc),
// 이미지만 있는 글, 이전 · 다음 글, 공개 범위(멤버공개 · 보호글 · 비밀글)와 보호 파일, 댓글, 위조 요청 거부.
// Env: TEST_URL, TEST_USER(최고관리자), TEST_PASSWORD, TEST_MEMBER(일반 회원, 같은 비밀번호), TEST_IMAGE(이미지 파일),
//      TEST_SQL(예: "mysql --default-character-set=utf8mb4 -uroot g5").
const { chromium } = require("playwright"),
  assert = require("node:assert/strict"),
  { execSync } = require("node:child_process");
(async () => {
  const base = process.env.TEST_URL,
    prefix = process.env.TEST_PREFIX || "avo_",
    sql = (q) => execSync(process.env.TEST_SQL, { input: q }).toString(),
    b = await chromium.launch(),
    errors = [];
  const [delay, siteOpen] = sql(`SELECT cf_delay_sec, cf_open FROM ${prefix}config;`).split("\n")[1].trim().split(/\s+/);
  sql(`UPDATE ${prefix}config SET cf_delay_sec=0, cf_open=1;`); // 끝나면 되돌린다
  try {
    async function open(id) {
      const c = await b.newContext({ viewport: { width: 1280, height: 900 } });
      await c.route("**/*", (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
      const p = await c.newPage();
      p.setDefaultTimeout(15000);
      p.on("pageerror", (e) => errors.push(e.message));
      p.dialogs = [];
      p.on("dialog", (d) => { p.dialogs.push(d.message()); d.accept(); });
      if (id) {
        await p.goto(base + "/bbs/login.php");
        await p.locator("[name=mb_id]").fill(id);
        await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
        await p.locator("[type=submit]").first().click();
        await p.waitForLoadState();
      }
      return p;
    }
    const admin = await open(process.env.TEST_USER);
    const id = "gl" + Date.now().toString().slice(-7);
    await admin.goto(base + "/adm/boardgroup_form.php");
    await admin.locator("[name=gr_id]").fill(id);
    await admin.locator("[name=gr_subject]").fill("갤러리 테스트");
    await admin.locator("input[type=submit]").first().click();
    await admin.waitForTimeout(400);
    await admin.goto(base + "/adm/board_form.php");
    await admin.locator("[name=gr_id]").selectOption(id);
    await admin.locator("[name=bo_table]").fill(id);
    await admin.locator("[name=bo_subject]").fill("갤러리 테스트");
    await admin.locator("[role=tab][aria-controls=anc_004]").click();
    await admin.locator("[name=bo_skin]").selectOption("custom_skin/gallery");
    await admin.locator("[role=tab][aria-controls=anc_003]").click();
    await admin.locator("[name=bo_upload_count]").fill("2");
    await admin.locator("[name=bo_use_secret]").selectOption("1");
    await admin.locator("input[type=submit]").first().click();
    await admin.waitForTimeout(700);
    sql(`UPDATE ${prefix}board SET bo_list_level=1, bo_read_level=1, bo_write_level=2, bo_comment_level=2, bo_use_category=1, bo_category_list='Art|Novel' WHERE bo_table='${id}';`);
    const board = base + "/bbs/board.php?bo_table=" + id;

    async function post(p, o) {
      await p.goto(base + "/bbs/write.php?bo_table=" + id);
      await p.locator(`[name=gl_kind][value=${o.kind}]`).check();
      await p.locator("#ca_name").selectOption(o.cat);
      if (o.vis) { await p.locator("#set_secret").selectOption(o.vis); if (o.vis === "protect") await p.locator("#wr_protect").fill("pw1234"); }
      await p.locator("#wr_subject").fill(o.title);
      if (o.src) await p.locator("#wr_1").fill(o.src);
      if (o.body) {
        const ed = p.locator(".ProseMirror").first();
        if (await ed.count()) { await ed.click(); await p.keyboard.insertText(o.body); } else await p.locator("#wr_content").fill(o.body);
      }
      if (o.image) await p.locator("input[name='bf_file[]']").first().setInputFiles(process.env.TEST_IMAGE);
      await p.locator("#btn_submit").click();
      await p.waitForLoadState();
      const wr = new URL(p.url()).searchParams.get("wr_id");
      assert.ok(wr, "저장: " + o.title + " " + p.dialogs.join("|"));
      return wr;
    }
    // 1. 그림(이미지만, 내용 없음) · 글 두 편(연재) · 멤버공개 · 보호글
    const art = await post(admin, { kind: "art", cat: "Art", title: "첫 그림", src: "그림 · 나", image: true });
    const ch1 = await post(admin, { kind: "text", cat: "Novel", title: "1화", body: "비가 오고 있었다. 서하는 책장을 넘겼다." });
    const ch2 = await post(admin, { kind: "text", cat: "Novel", title: "2화", body: "다음 날도 비가 왔다." });
    const mem = await post(admin, { kind: "art", cat: "Art", title: "회원 전용", vis: "member", image: true });
    const pro = await post(admin, { kind: "text", cat: "Novel", title: "보호된 외전", body: "비밀 이야기", vis: "protect" });
    const stored = sql(`SELECT wr_protect FROM ${prefix}write_${id} WHERE wr_id=${pro};`).split("\n")[1].trim();
    assert.match(stored, /^\$2y\$/, "보호글 비밀번호는 해시로 저장");

    // 2. 회원: 폴더 · 카드 · 펼침 기억
    const member = await open(process.env.TEST_MEMBER);
    await member.goto(board);
    const artFolder = member.locator('[data-gl-folder="Art"]');
    assert.equal(await artFolder.locator(".gl-folder-body").isHidden(), true, "처음엔 접힘");
    await artFolder.locator("[data-gl-toggle]").click();
    await member.locator('[data-gl-folder="Novel"] [data-gl-toggle]').click();
    await member.reload();
    assert.equal(await artFolder.locator(".gl-folder-body").isVisible(), true, "펼친 폴더 기억");
    assert.equal(await artFolder.locator(".gl-card").count(), 2, "그림 폴더 카드 2장(멤버공개 포함)");
    assert.ok(await artFolder.locator(".gl-thumb img").first().getAttribute("src").then((s) => s.includes("archive_file.php")), "썸네일은 보호 경로");
    const novel = member.locator('[data-gl-folder="Novel"]');
    assert.match(await novel.locator(`.gl-card[href*="wr_id=${ch1}"] .gl-count`).innerText(), /\d+자/, "글 카드 글자 수");
    assert.equal(await novel.locator(`.gl-card[href*="wr_id=${pro}"].is-locked`).count(), 1, "보호글은 잠긴 카드");

    // 3. 팝업: 주소 바뀜 · 이전/다음 · Esc · 뒤로 가기
    await novel.locator(`.gl-card[href*="wr_id=${ch1}"]`).click();
    await member.waitForSelector(".gl-modal-body [data-gl-view]");
    assert.equal(new URL(member.url()).searchParams.get("wr_id"), ch1, "팝업 주소");
    assert.match(await member.locator(".gl-modal-body .gl-v-title").innerText(), /1화/);
    await member.locator(".gl-modal-body .gl-v-nav .is-next").click();
    await member.waitForFunction((t) => document.querySelector(".gl-modal-body .gl-v-title")?.textContent.includes(t), "2화");
    assert.equal(new URL(member.url()).searchParams.get("wr_id"), ch2, "다음 글");
    await member.keyboard.press("Escape");
    await member.waitForFunction(() => document.getElementById("gl-modal").hidden);
    assert.equal(new URL(member.url()).searchParams.get("wr_id"), null, "닫으면 목록 주소로");
    await artFolder.locator(`.gl-card[href*="wr_id=${art}"]`).click();
    await member.waitForSelector(".gl-modal-body [data-gl-view]");
    assert.equal(await member.locator(".gl-modal-body .gl-v-images img").count(), 1, "이미지만 있는 글");
    assert.match(await member.locator(".gl-modal-body .gl-v-sub").innerText(), /그림 · 나/, "출처");
    await member.goBack();
    await member.waitForFunction(() => document.getElementById("gl-modal").hidden);

    // 4. 보호글: 잠긴 카드 → 비밀번호 화면(원래 화면으로) → 열림
    await novel.locator(`.gl-card[href*="wr_id=${pro}"]`).click();
    await member.waitForURL(/protect\.php/);
    await member.locator("[name=wr_password]").fill("pw1234");
    await member.locator("[type=submit]").first().click();
    await member.waitForLoadState();
    assert.match(await member.locator(".gl-v-body").innerText(), /비밀 이야기/, "보호글 열림");

    // 5. 댓글(본문 화면) · 위조 요청
    await member.goto(board + "&wr_id=" + ch1);
    await member.locator(".gl-c-form textarea").fill("잘 읽었어요");
    await member.locator(".gl-c-form [type=submit]").click();
    await member.waitForLoadState();
    assert.match(await member.locator(".gl-comment .gl-c-body").first().innerText(), /잘 읽었어요/);
    const forged = await member.request.post(base + "/bbs/write_update.php", { form: { w: "", bo_table: id, wr_subject: "x", wr_content: "x", ca_name: "Art" } });
    assert.match(await forged.text(), /글쓰기 요청을 확인할 수 없습니다/, "토큰 없는 글쓰기 거부");

    // 6. 비회원: 멤버공개 · 보호글은 잠김, 파일도 막힘
    const guest = await open("");
    await guest.goto(board);
    await guest.locator('[data-gl-folder="Art"] [data-gl-toggle]').click();
    const locked = guest.locator(`.gl-card[href*="wr_id=${mem}"]`);
    assert.ok(await locked.evaluate((a) => a.classList.contains("is-locked")), "멤버공개는 비회원에게 잠김");
    assert.equal(await locked.locator("img").count(), 0, "잠긴 카드엔 썸네일 없음");
    const file = sql(`SELECT bf_file FROM ${prefix}board_file WHERE bo_table='${id}' AND wr_id=${mem} LIMIT 1;`).split("\n")[1].trim();
    const res = await guest.request.get(`${base}/bbs/archive_file.php?archive_kind=file&archive_path=${encodeURIComponent(id + "/" + file)}`);
    assert.equal(res.status(), 403, "멤버공개 첨부는 비회원에게 막힘");

    // 7. 모바일: 가로로 넘치지 않음, 두 줄
    await member.setViewportSize({ width: 390, height: 800 });
    await member.goto(board);
    assert.equal(await member.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true, "모바일 가로 넘침 없음");

    assert.deepEqual(errors, [], "자바스크립트 오류 없음");
    console.log("gallery smoke ok", id);
  } finally {
    sql(`UPDATE ${prefix}config SET cf_delay_sec=${Number(delay) || 0}, cf_open=${Number(siteOpen) || 0};`);
    await b.close();
  }
})().catch((e) => { console.error(e); process.exit(1); });
