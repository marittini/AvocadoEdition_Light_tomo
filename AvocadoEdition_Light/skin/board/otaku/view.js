(() => {
  const root = document.querySelector(".otaku-view");
  if (!root) return;

  /* ---------- 판(원본 / AU) 안의 탭 ---------- */
  function selectTab(version, key, focus) {
    const tabs = [...version.querySelectorAll("[data-tab]")];
    if (!tabs.some((t) => t.dataset.tab === key)) return false;
    tabs.forEach((t) => {
      const on = t.dataset.tab === key;
      t.setAttribute("aria-selected", String(on));
      t.tabIndex = on ? 0 : -1;
      if (on && focus) t.focus();
    });
    version.querySelectorAll("[data-panel]").forEach((p) => { p.hidden = p.dataset.panel !== key; });
    return true;
  }
  root.querySelectorAll("[data-version]").forEach((version) => {
    version.addEventListener("click", (e) => {
      const tab = e.target.closest("[data-tab]");
      if (tab) selectTab(version, tab.dataset.tab);
    });
    version.addEventListener("keydown", (e) => {
      const tab = e.target.closest("[data-tab]");
      if (!tab || !["ArrowLeft", "ArrowRight", "Home", "End"].includes(e.key)) return;
      const tabs = [...version.querySelectorAll("[data-tab]")];
      let i = tabs.indexOf(tab);
      i = e.key === "Home" ? 0 : e.key === "End" ? tabs.length - 1 : (i + (e.key === "ArrowRight" ? 1 : -1) + tabs.length) % tabs.length;
      e.preventDefault();
      selectTab(version, tabs[i].dataset.tab, true);
    });
  });

  /* ---------- 좁은 화면: 인물 한 명씩(프로필 · 관계가 같이 바뀐다) ---------- */
  function showPerson(version, i) {
    version.querySelectorAll(".otaku-person-tabs").forEach((group) => {
      const tabs = [...group.querySelectorAll("[data-person-tab]")];
      const cur = tabs.find((b) => b.dataset.personTab === i) || tabs[0];
      tabs.forEach((b) => {
        b.setAttribute("aria-pressed", String(b === cur));
        b.classList.toggle("point", b === cur);
      });
    });
    version.querySelectorAll("[data-people]").forEach((box) => {
      const items = [...box.querySelectorAll(":scope > [data-person]")];
      const has = items.some((el) => el.dataset.person === i);
      items.forEach((el, n) => el.classList.toggle("is-off", has ? el.dataset.person !== i : n > 0));
    });
  }
  root.querySelectorAll("[data-version]").forEach((version) => {
    const first = version.querySelector("[data-person-tab]");
    if (first) showPerson(version, first.dataset.personTab);
    version.addEventListener("click", (e) => {
      const b = e.target.closest("[data-person-tab]");
      if (b) showPerson(version, b.dataset.personTab);
    });
  });

  /* ---------- 원본 / AU 바꾸기: 새로고침 없이, 보던 탭 그대로 ---------- */
  const toggle = (attr, id) => root.querySelectorAll("[" + attr + "]").forEach((el) => { el.hidden = el.getAttribute(attr) !== id; });
  root.addEventListener("click", (e) => {
    const link = e.target.closest("[data-version-link]");
    if (!link || e.metaKey || e.ctrlKey || e.shiftKey || e.button) return;
    e.preventDefault();
    const id = link.dataset.versionLink;
    const tab = root.querySelector("[data-version]:not([hidden]) [data-tab][aria-selected=true]")?.dataset.tab;
    ["data-version", "data-version-title", "data-version-tools"].forEach((a) => toggle(a, id));
    root.querySelectorAll("[data-version-link]").forEach((a) => {
      const on = a === link;
      a.classList.toggle("point", on);
      on ? a.setAttribute("aria-current", "true") : a.removeAttribute("aria-current");
    });
    const next = root.querySelector('[data-version="' + id + '"]');
    if (!(tab && selectTab(next, tab))) selectTab(next, "profile");
    history.replaceState(null, "", link.href);
  });
})();
