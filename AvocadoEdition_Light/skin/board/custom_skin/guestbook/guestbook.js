(() => {
  const root = document.querySelector(".gb");
  if (!root) return;

  /* ---------- 글상자 높이 맞춤 ---------- */
  function grow(t) {
    if (!t.offsetParent) return;
    t.style.height = "auto";
    t.style.height = t.scrollHeight + 2 + "px";
  }
  root.querySelectorAll("textarea[data-gb-grow]").forEach(grow);
  root.addEventListener("input", (e) => { if (e.target.matches("textarea[data-gb-grow]")) grow(e.target); });

  /* ---------- 자동등록방지: 한 벌을 쓰고 있는 폼 아래로 옮긴다 ---------- */
  const captcha = document.getElementById("gb-captcha");
  let activeForm = null;
  function use(form) {
    if (!form || form === activeForm) return;
    activeForm = form;
    const slot = form.querySelector("[data-gb-slot]");
    if (captcha && slot) slot.append(captcha);
  }
  root.addEventListener("focusin", (e) => use(e.target.closest("[data-gb-form]")));
  if (captcha) use(captcha.closest("[data-gb-form]") || root.querySelector("#gb-write"));

  /* ---------- 펼치기 · 수정 ---------- */
  function setComments(button, open) {
    const panel = document.getElementById(button.getAttribute("aria-controls"));
    if (!panel) return;
    button.setAttribute("aria-expanded", String(open));
    panel.hidden = !open;
    if (open) panel.querySelectorAll("textarea[data-gb-grow]").forEach(grow);
  }
  function setEditing(box, on) {
    const view = box.querySelector(":scope > [data-gb-view]");
    const editor = box.querySelector(":scope > [data-gb-editor]");
    if (!view || !editor) return;
    view.hidden = on;
    editor.hidden = !on;
    if (on) {
      const t = editor.querySelector("textarea");
      grow(t);
      t.focus();
      t.setSelectionRange(t.value.length, t.value.length);
    }
  }

  root.addEventListener("click", (e) => {
    const t = e.target;
    const toggle = t.closest("[data-gb-toggle]");
    if (toggle) return setComments(toggle, toggle.getAttribute("aria-expanded") !== "true");

    const edit = t.closest("[data-gb-edit]");
    if (edit) return setEditing(edit.closest(".gb-comment, .gb-entry"), true);

    const cancel = t.closest("[data-gb-cancel]");
    if (cancel) {
      const form = cancel.closest("form");
      form.reset();
      return setEditing(cancel.closest(".gb-comment, .gb-entry"), false);
    }

    const confirmLink = t.closest("[data-gb-confirm]");
    if (confirmLink && !window.confirm(confirmLink.dataset.gbConfirm)) return e.preventDefault();
  });

  // 같은 폼을 두 번 보내지 않게
  root.addEventListener("submit", (e) => {
    const b = e.target.querySelector("[type=submit]");
    if (b) setTimeout(() => { b.disabled = true; }, 0);
  });

  /* ---------- 주소의 #gb-12-c: 그 글의 댓글을 펼친 채로 ---------- */
  const m = location.hash.match(/^#gb-(\d+)(-c)?$/);
  if (m) {
    const entry = document.getElementById("gb-" + m[1]);
    if (entry) {
      if (m[2]) {
        const b = entry.querySelector("[data-gb-toggle]");
        if (b) setComments(b, true);
      }
      entry.scrollIntoView({ block: "start" });
    }
  }
})();
