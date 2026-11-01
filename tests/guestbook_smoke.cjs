// 방명록(guestbook): 목록에서 쓰기 · 공지 · #해시태그 · 자동 링크(수정해도 늘지 않음, 꾸미기 없음),
// 회원 수정 · 댓글(펼친 채로 돌아옴, 몇 쪽인지 계산) · 댓글 수정 · 삭제, 비밀글(비회원 비밀번호 확인), 위조 요청 거부.
// Env: TEST_URL, TEST_USER(최고관리자), TEST_PASSWORD, TEST_MEMBER(일반 회원, 같은 비밀번호), TEST_SQL(예: "mysql -uroot g5").
//      TEST_SESSION_DIR(선택): PHP 세션 폴더. 있으면 비회원 글(자동등록방지 숫자를 세션에서 읽음)까지 확인한다.
const { chromium } = require("playwright"),
  assert = require("node:assert/strict"),
  fs = require("node:fs"),
  path = require("node:path"),
  { execSync } = require("node:child_process");
(async () => {
  const base = process.env.TEST_URL,
    prefix = process.env.TEST_PREFIX || "avo_",
    sql = (q) => execSync(process.env.TEST_SQL, { input: q }).toString(),
    b = await chromium.launch(),
    errors = [];
  const [delay, siteOpen] = sql(`SELECT cf_delay_sec, cf_open FROM ${prefix}config;`).split("\n")[1].trim().split(/\s+/);
  // 연속 쓰기 제한(초)과 비공개 사이트 설정(비회원 확인용)은 끝나면 되돌린다
  sql(`UPDATE ${prefix}config SET cf_delay_sec=0${process.env.TEST_SESSION_DIR ? ", cf_open=1" : ""};`);
  try {
    async function open(id) {
      const c = await b.newContext();
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
    const id = "gb" + Date.now().toString().slice(-7);
    await admin.goto(base + "/adm/boardgroup_form.php");
    await admin.locator("[name=gr_id]").fill(id);
    await admin.locator("[name=gr_subject]").fill("방명록 테스트");
    await admin.locator("input[type=submit]").first().click();
    await admin.waitForTimeout(400);
    await admin.goto(base + "/adm/board_form.php");
    await admin.locator("[name=gr_id]").selectOption(id);
    await admin.locator("[name=bo_table]").fill(id);
    await admin.locator("[name=bo_subject]").fill("방명록 테스트");
    await admin.locator("[role=tab][aria-controls=anc_004]").click();
    await admin.locator("[name=bo_skin]").selectOption("guestbook");
    await admin.locator("[role=tab][aria-controls=anc_003]").click();
    await admin.locator("[name=bo_use_secret]").selectOption("1");
    await admin.locator("input[type=submit]").first().click();
    await admin.waitForTimeout(700);
    sql(`UPDATE ${prefix}board SET bo_list_level=1, bo_read_level=1, bo_write_level=1, bo_comment_level=1, bo_page_rows=3, bo_mobile_page_rows=3 WHERE bo_table='${id}';`);
    const board = base + "/bbs/board.php?bo_table=" + id;

    async function write(p, text, opts = {}) {
      await p.goto(board);
      const f = p.locator("#gb-write");
      await f.locator("textarea").fill(text);
      if (opts.guest) {
        await f.locator("[name=wr_name]").fill(opts.guest);
        await f.locator("[name=wr_password]").fill("pw1234");
      }
      if (opts.secret) await f.locator("[name=secret]").check();
      if (opts.notice) await f.locator("[name=notice]").check();
      await f.locator("[type=submit]").click();
      await p.waitForLoadState();
      const m = p.url().match(/#gb-(\d+)/);
      assert.ok(m, "저장 뒤 목록의 그 글로 돌아옴: " + p.url() + " " + p.dialogs.join("|"));
      return m[1];
    }

    // 1. 관리자: 공지 + 해시태그, 그리고 쪽을 넘길 만큼 글 두 개
    const old = await write(admin, "첫 글입니다.");
    await write(admin, "두 번째 글");
    const notice = await write(admin, "반가워요 #환영 인사", { notice: true });
    const n = admin.locator(`#gb-${notice}`).first();
    assert.equal((await n.locator(".gb-no").textContent()).trim(), "공지");
    assert.equal(await n.locator("a.gb-tag").textContent(), "#환영", "해시태그 링크");

    // 2. 회원: 글(주소, [[ ]]는 꾸미지 않고 글자 그대로) → 수정해도 링크가 늘지 않음
    const member = await open(process.env.TEST_MEMBER);
    const mine = await write(member, "안녕하세요 https://example.com/a [[소중한]] 방명록");
    const e = member.locator(`#gb-${mine}`);
    assert.equal(await e.locator(".gb-body a[href='https://example.com/a']").count(), 1, "주소 자동 링크");
    assert.match(await e.locator(".gb-body").innerText(), /\[\[소중한\]\]/, "텍스트 꾸미기 없음");
    assert.equal(await member.locator(`#gb-${notice} [data-gb-edit]`).count(), 0, "남의 글은 수정 버튼 없음");
    await e.locator(".gb-foot [data-gb-edit]").click();
    const ed = e.locator("[data-gb-editor] textarea");
    assert.equal(await ed.inputValue(), "안녕하세요 https://example.com/a [[소중한]] 방명록", "수정 칸에는 원문 그대로");
    await ed.fill("고쳤어요 https://example.com/a [[소중한]] 방명록");
    await e.locator("[data-gb-editor] [type=submit]").click();
    await member.waitForLoadState();
    const e2 = member.locator(`#gb-${mine}`);
    assert.match(await e2.locator(".gb-body").innerText(), /^고쳤어요/);
    assert.equal(await e2.locator(".gb-body a").count(), 1, "수정해도 링크 하나");

    // 3. 회원 댓글: 2쪽에 있는 글에 달아도 그 쪽으로, 펼친 채로 돌아옴
    await member.waitForTimeout(1100); // 같은 초 안의 연속 쓰기 제한
    await member.goto(board + "&page=2");
    const target = member.locator(`#gb-${old}`);
    await target.locator("[data-gb-toggle]").click();
    await target.locator(".gb-form-comment textarea").fill("좋은 글");
    await target.locator(".gb-form-comment [type=submit]").click();
    await member.waitForLoadState();
    assert.match(member.url(), new RegExp(`page=2.*#gb-${old}-c$`), "댓글 뒤 2쪽, 펼친 채로: " + member.dialogs.join("|"));
    assert.equal(await member.locator(`#gb-${old}-c`).isVisible(), true);
    assert.match(await member.locator(`#gb-${old} [data-gb-toggle]`).innerText(), /댓글\s*1/);
    const cm = member.locator(`#gb-${old} .gb-comment`).first();
    await cm.locator("[data-gb-edit]").click();
    await cm.locator("[data-gb-editor] textarea").fill("댓글을 고쳤어요");
    await cm.locator("[data-gb-editor] [type=submit]").click();
    await member.waitForLoadState();
    assert.equal((await member.locator(`#gb-${old} .gb-comment .gb-body`).first().innerText()).trim(), "댓글을 고쳤어요");
    await member.locator(`#gb-${old} .gb-comment [data-gb-confirm]`).first().click();
    await member.waitForLoadState();
    assert.equal(await member.locator(`#gb-${old} .gb-comment`).count(), 0, "댓글 삭제");
    assert.equal(await member.locator(`#gb-${old}-c`).isVisible(), true, "삭제 뒤에도 펼친 채로");

    // 4. 위조 요청은 막힌다
    const forged = await member.request.post(base + "/bbs/write_update.php", { form: { w: "", bo_table: id, wr_subject: "x", wr_content: "x" } });
    assert.match(await forged.text(), /요청을 확인할 수 없습니다/, "토큰 없는 글쓰기 거부");

    // 5. 비회원 비밀글 (세션 폴더가 있을 때)
    const dir = process.env.TEST_SESSION_DIR;
    if (dir) {
      const guest = await open("");
      await guest.goto(board);
      await guest.locator("#gb-write textarea").click(); // 자동등록방지가 쓰기 폼으로 옮겨옴
      await guest.waitForTimeout(500);
      const sid = (await guest.context().cookies()).find((c) => c.name === "PHPSESSID").value;
      const key = () => fs.readFileSync(path.join(dir, "sess_" + sid), "utf8").match(/ss_captcha_key\|s:\d+:"(\d+)"/)[1];
      const f = guest.locator("#gb-write");
      await f.locator("textarea").fill("비밀 이야기");
      await f.locator("[name=wr_name]").fill("손님");
      await f.locator("[name=wr_password]").fill("pw1234");
      await f.locator("[name=secret]").check();
      await f.locator("[name=captcha_key]").fill(key());
      await f.locator("[type=submit]").click();
      await guest.waitForLoadState();
      const sec = guest.url().match(/#gb-(\d+)/)?.[1];
      assert.ok(sec, "비회원 비밀글 저장: " + guest.dialogs.join("|"));
      assert.equal((await guest.locator(`#gb-${sec} .gb-body`).innerText()).trim(), "비밀 이야기", "쓴 사람은 바로 봄");
      const other = await open("");
      await other.goto(board);
      const locked = other.locator(`#gb-${sec}`);
      assert.match(await locked.locator(".gb-locked").innerText(), /비밀글입니다/);
      assert.equal(await member.goto(board).then(() => member.locator(`#gb-${sec} .gb-locked`).count()), 1, "다른 회원도 못 봄");
      assert.equal(await member.locator(`#gb-${sec} [data-gb-toggle]`).count(), 0, "잠긴 글은 댓글도 닫힘");
      await locked.locator(".gb-locked a").click();
      await other.locator("[name=wr_password]").fill("pw1234");
      await other.locator("[type=submit]").first().click();
      await other.waitForLoadState();
      assert.match(other.url(), new RegExp(`#gb-${sec}$`));
      assert.equal((await other.locator(`#gb-${sec} .gb-body`).innerText()).trim(), "비밀 이야기", "비밀번호 확인 뒤 열림");
      assert.equal(await admin.goto(board).then(() => admin.locator(`#gb-${sec} .gb-locked`).count()), 0, "관리자는 봄");
    }

    // 6. 잠긴 비밀글에는 댓글을 보낼 수 없다(토큰이 있어도)
    await member.goto(board);
    const token = await member.locator("#gb-write [name=gb_token]").inputValue();
    const secretId = sql(`SELECT wr_id FROM ${prefix}write_${id} WHERE wr_option LIKE '%secret%' AND wr_is_comment=0 LIMIT 1;`).split("\n")[1]?.trim();
    if (secretId) {
      const r = await member.request.post(base + "/bbs/write_comment_update.php", { form: { w: "c", bo_table: id, wr_id: secretId, gb_token: token, wr_content: "몰래" } });
      assert.match(await r.text(), /댓글을 달 수 없는 글입니다/, "잠긴 글 댓글 거부");
    }

    // 7. 해시태그 검색, 관리자 삭제
    await admin.goto(board);
    await admin.locator(`#gb-${notice} a.gb-tag`).click();
    await admin.waitForLoadState();
    const found = await admin.locator(".gb-body").allInnerTexts();
    assert.ok(found.length >= 1 && found.every((t) => t.includes("#환영")), "해시태그로 찾은 글만: " + found.join(" / "));
    await admin.goto(board);
    await admin.locator(`#gb-${mine} .gb-foot [data-gb-confirm]`).click();
    await admin.waitForLoadState();
    assert.equal(await admin.locator(`#gb-${mine}`).count(), 0, "관리자 삭제");

    // 8. 모바일: 가로로 넘치지 않음
    await admin.setViewportSize({ width: 360, height: 760 });
    await admin.goto(board);
    assert.equal(await admin.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true, "모바일 가로 넘침 없음");

    assert.deepEqual(errors, [], "자바스크립트 오류 없음");
    console.log("guestbook smoke ok", id);
  } finally {
    sql(`UPDATE ${prefix}config SET cf_delay_sec=${Number(delay) || 0}, cf_open=${Number(siteOpen) || 0};`);
    await b.close();
  }
})().catch((e) => { console.error(e); process.exit(1); });
