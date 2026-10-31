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
