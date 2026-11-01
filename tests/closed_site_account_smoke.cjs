// 비공개 사이트(사이트 오픈 꺼짐)에서도 계정 화면은 열린다: 가입(가입 허용일 때만) · 아이디 확인 · 비밀번호 찾기 ·
// 메일 인증 · 자동등록방지 그림. 그 밖의 화면은 로그인으로 보낸다. 로그인 화면에 회원가입 링크.
// 설정(cf_open, cf_1)은 끝나면 되돌린다. Env: TEST_URL, TEST_SQL(예: "mysql -uroot g5").
const assert = require("node:assert/strict"),
  { execSync } = require("node:child_process");
(async () => {
  const base = process.env.TEST_URL,
    prefix = process.env.TEST_PREFIX || "avo_",
    sql = (q) => execSync(process.env.TEST_SQL, { input: q }).toString();
  const [open, reg] = sql(`SELECT cf_open, cf_1 FROM ${prefix}config;`).split("\n")[1].split("\t").map((v) => v.trim());
  const go = async (path) => {
    const r = await fetch(base + path, { redirect: "manual" });
    const loc = r.headers.get("location") || "";
    return { status: r.status, toLogin: /\/bbs\/login\.php/.test(loc), body: r.status === 200 ? await r.text() : "" };
  };
  try {
    sql(`UPDATE ${prefix}config SET cf_open=0, cf_1='';`);
    assert.equal((await go("/bbs/register_form.php")).toLogin, true, "가입을 허용하지 않으면 가입 화면은 로그인으로");
    assert.equal((await go("/bbs/password_lost.php")).status, 200, "비밀번호 찾기는 열림");
    assert.equal((await go("/plugin/kcaptcha/kcaptcha_image.php")).status, 200, "자동등록방지 그림은 열림");
    assert.equal((await go("/bbs/new.php")).toLogin, true, "다른 화면은 로그인으로");
    assert.doesNotMatch((await go("/bbs/login.php")).body, /register\.php/, "가입을 허용하지 않으면 회원가입 링크 없음");

    sql(`UPDATE ${prefix}config SET cf_1='1';`);
    assert.equal((await go("/bbs/register_form.php")).status, 200, "가입을 허용하면 비공개여도 가입 화면");
    assert.equal((await go("/bbs/ajax.mb_id.php")).status, 200, "아이디 중복 확인");
    assert.equal((await go("/bbs/new.php")).toLogin, true, "가입을 허용해도 다른 화면은 로그인으로");
    assert.match((await go("/bbs/login.php")).body, /register\.php[^>]*>회원가입/, "로그인 화면에 회원가입 링크");
    console.log("closed site account smoke ok");
  } finally {
    sql(`UPDATE ${prefix}config SET cf_open=${Number(open) || 0}, cf_1='${(reg || "").replace(/'/g, "")}';`);
  }
})().catch((e) => { console.error(e); process.exit(1); });
