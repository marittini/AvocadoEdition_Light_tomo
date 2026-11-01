(() => {
  if (window.__glReady) return; // 목록과 본문이 한 화면에 있어도 한 번만
  window.__glReady = true;

  const store = {
    get(k) { try { return localStorage.getItem(k); } catch (_) { return null; } },
    set(k, v) { try { localStorage.setItem(k, v); } catch (_) { /* 저장 못 해도 동작에는 지장 없음 */ } },
  };

  /* ---------- 폴더(분류) 펼치기 · 접기: 브라우저에 기억 ---------- */
  const list = document.querySelector(".gl-list");
  const board = list ? list.dataset.glBoard : "";
  function setFolder(button, open, remember) {
    const body = document.getElementById(button.getAttribute("aria-controls"));
    if (!body) return;
    button.setAttribute("aria-expanded", String(open));
    body.hidden = !open;
    const icon = button.querySelector(".gl-folder-icon");
    if (icon) icon.textContent = open ? "folder_open" : "folder";
    if (remember) store.set("gl:" + board + ":" + button.closest("[data-gl-folder]").dataset.glFolder, open ? "1" : "0");
  }
  document.querySelectorAll("[data-gl-toggle]").forEach((b) => {
    if (b.getAttribute("aria-expanded") === "true") return; // 주소로 연 분류는 그대로
    const saved = store.get("gl:" + board + ":" + b.closest("[data-gl-folder]").dataset.glFolder);
    if (saved === "1") setFolder(b, true, false);
  });

  /* ---------- 글상자 높이 맞춤 ---------- */
  function grow(t) {
    if (!t.offsetParent) return;
    t.style.height = "auto";
    t.style.height = t.scrollHeight + 2 + "px";
  }
  document.addEventListener("input", (e) => { if (e.target.matches("textarea[data-gl-grow]")) grow(e.target); });

  /* ---------- 본문 팝업: 그누보드 본문 화면을 불러와 글 부분만 띄운다 ---------- */
  const modal = document.getElementById("gl-modal");
  const body = modal && modal.querySelector(".gl-modal-body");
  const panel = modal && modal.querySelector(".gl-modal-panel");
  let pushed = false;
  let opener = null;
  let loading = 0;

  async function show(href, fromList) {
    const ticket = ++loading;
    modal.hidden = false;
    modal.classList.add("is-loading");
    document.documentElement.classList.add("gl-modal-open");
    try {
      const res = await fetch(href, { credentials: "same-origin" });
      const html = await res.text();
      if (ticket !== loading) return;
      const doc = new DOMParser().parseFromString(html, "text/html");
      const view = doc.querySelector("[data-gl-view]");
      if (!res.ok || !view) { location.href = href; return; } // 비밀번호 확인 · 로그인 안내 등은 원래 화면으로
      body.replaceChildren(document.importNode(view, true));
      body.querySelectorAll("textarea[data-gl-grow]").forEach(grow);
      panel.scrollTop = 0;
      panel.focus({ preventScroll: true });
      if (fromList) { history.pushState({ gl: 1 }, "", href); pushed = true; }
      else history.replaceState({ gl: 1 }, "", href);
    } catch (_) {
      location.href = href;
    } finally {
      modal.classList.remove("is-loading");
    }
  }
  function hide(fromHistory) {
    if (!modal || modal.hidden) return;
    loading++;
    modal.hidden = true;
    body.replaceChildren();
    document.documentElement.classList.remove("gl-modal-open");
    if (pushed && !fromHistory) history.back();
    pushed = false;
    if (opener) opener.focus({ preventScroll: true });
  }
  window.addEventListener("popstate", () => hide(true));

  /* ---------- 눌렀을 때 ---------- */
  document.addEventListener("click", (e) => {
    const t = e.target;
    const toggle = t.closest("[data-gl-toggle]");
    if (toggle) return setFolder(toggle, toggle.getAttribute("aria-expanded") !== "true", true);

    const link = t.closest("a[data-gl-open]");
    if (link && modal && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey && e.button === 0) {
      e.preventDefault();
      const inModal = modal.contains(link);
      if (!inModal) opener = link;
      return show(link.href, !inModal);
    }
    if (modal && t.closest("[data-gl-close], .gl-modal .gl-back")) { e.preventDefault(); return hide(false); }

    const edit = t.closest("[data-gl-edit]");
    if (edit) {
      const c = edit.closest(".gl-comment");
      c.querySelector("[data-gl-cview]").hidden = true;
      c.querySelector("[data-gl-ceditor]").hidden = false;
      const area = c.querySelector("[data-gl-ceditor] textarea");
      grow(area); area.focus();
      return;
    }
    const cancel = t.closest("[data-gl-cancel]");
    if (cancel) {
      const c = cancel.closest(".gl-comment");
      cancel.closest("form").reset();
      c.querySelector("[data-gl-cview]").hidden = false;
      c.querySelector("[data-gl-ceditor]").hidden = true;
      return;
    }
    const confirmLink = t.closest("[data-gl-confirm]");
    if (confirmLink && !window.confirm(confirmLink.dataset.glConfirm)) e.preventDefault();
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal && !modal.hidden) hide(false);
  });
  document.addEventListener("submit", (e) => {
    const b = e.target.matches("[data-gl-cform]") && e.target.querySelector("[type=submit]");
    if (b) setTimeout(() => { b.disabled = true; }, 0);
  });
})();
