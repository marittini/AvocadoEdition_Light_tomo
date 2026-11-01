(() => {
  const open = document.querySelector("#px-open"),
    reader = document.querySelector("#px-reader"),
    comic = document.querySelector("#px-comic");
  if (!open || !reader || !comic) return;
  const pages = [...reader.querySelectorAll(".px-images .px-page")],
    images = pages.map((b) => b.querySelector("img")),
    pic = comic.querySelector(".px-comic-image"),
    range = comic.querySelector("[data-comic-range]"),
    progress = comic.querySelector("[data-comic-progress]"),
    grid = comic.querySelector(".px-comic-grid"),
    gridToggle = comic.querySelector("[data-comic=grid]"),
    comicEnd = comic.querySelector(".px-comic-end");
  document.body.append(reader, comic);
  let index = 0,
    previous = null,
    paged = false,
    novelPage = 0,
    novelPages = 1;

  // index.php owns a second menu outside the board iframe.
  // Preserve individual properties so closing never overwrites other theme state.
  let hiddenMenus = [];
  function hideSiteMenus() {
    if (hiddenMenus.length) return;
    let frame = window;
    for (;;) {
      try {
        frame.document.querySelectorAll("#header, #mo_header, .control-mobile-menu").forEach((menu) => {
          hiddenMenus.push({ menu, display: menu.style.getPropertyValue("display"), priority: menu.style.getPropertyPriority("display"), inert: menu.inert, aria: menu.getAttribute("aria-hidden") });
          menu.style.setProperty("display", "none", "important");
          menu.inert = true;
          menu.setAttribute("aria-hidden", "true");
        });
        if (frame.parent === frame) break;
        frame = frame.parent;
      } catch (_) { break; } // Browsers prohibit accessing a different-origin parent.
    }
  }
  function restoreSiteMenus() {
    hiddenMenus.forEach(({ menu, display, priority, inert, aria }) => {
      if (display) menu.style.setProperty("display", display, priority);
      else menu.style.removeProperty("display");
      menu.inert = inert;
      if (aria === null) menu.removeAttribute("aria-hidden");
      else menu.setAttribute("aria-hidden", aria);
    });
    hiddenMenus = [];
  }
  window.addEventListener("pagehide", restoreSiteMenus);

  // Reuse the theme box's color while making the reading layer opaque.
  function solidBackground(layer) {
    const pick = (el) => {
      const c = getComputedStyle(el).backgroundColor.match(/[\d.]+/g);
      return c && c.length >= 3 && (c.length < 4 || Number(c[3]) > 0) ? "rgb(" + c.slice(0, 3).join(",") + ")" : "";
    };
    layer.style.backgroundColor = pick(layer) || pick(document.body) || "#fff";
  }

  // Size the page layer once for the whole work (the largest page decides), so the width does not jump between
  // pages; each page is then scaled inside it. Page sizes are read from the same image files (cached).
  const CHROME = 52 + 56; // header + footer
  const sizes = images.map(() => null);
  images.forEach((img, i) => {
    const probe = new Image();
    probe.onload = () => { sizes[i] = [probe.naturalWidth, probe.naturalHeight]; fitComic(); };
    probe.src = img.currentSrc || img.src;
  });
  function scaled([nw, nh], maxW, maxH) {
    const scale = Math.min((maxW - 112) / nw, (maxH - CHROME - 16) / nh, 2);
    return [Math.max(1, Math.round(nw * scale)), Math.max(1, Math.round(nh * scale))];
  }
  function fitComic() {
    if (comic.hidden || !pic.naturalWidth) return;
    const maxW = Math.min(1200, window.innerWidth - 24),
      maxH = window.innerHeight - 24;
    const known = sizes.filter(Boolean).concat([[pic.naturalWidth, pic.naturalHeight]]);
    let boxW = 0, boxH = 0;
    known.forEach((d) => { const [w, h] = scaled(d, maxW, maxH); boxW = Math.max(boxW, w); boxH = Math.max(boxH, h); });
    if (known.length <= sizes.length && sizes.some((d) => !d)) { boxW = maxW - 112; boxH = maxH - CHROME - 16; } // until every page size is known
    const layerW = Math.min(maxW, Math.max(320, boxW + 112)), layerH = Math.min(maxH, Math.max(320, boxH + CHROME + 16));
    comic.style.width = layerW + "px";
    comic.style.height = layerH + "px";
    const [w, h] = scaled([pic.naturalWidth, pic.naturalHeight], layerW, layerH);
    pic.style.width = w + "px";
    pic.style.height = h + "px";
  }
  pic.addEventListener("load", fitComic);
  window.addEventListener("resize", fitComic);

  function show(layer, focusSelector) {
    if (layer.hidden) previous = previous || document.activeElement;
    reader.hidden = comic.hidden = true;
    layer.hidden = false;
    solidBackground(layer);
    hideSiteMenus();
    document.documentElement.classList.add("px-reading");
    layer.querySelector(focusSelector)?.focus();
  }
  function closeAll() {
    reader.hidden = comic.hidden = true;
    document.documentElement.classList.remove("px-reading");
    restoreSiteMenus();
    previous?.focus();
    previous = null;
  }

  /* ---------- page-by-page viewer ---------- */
  function render() {
    if (!images.length) return;
    const atEnd = index >= images.length; // one step past the last page shows the end card
    index = Math.max(0, Math.min(images.length - (comicEnd ? 0 : 1), index));
    comicEnd && (comicEnd.hidden = !atEnd);
    pic.hidden = atEnd;
    const page = Math.min(index, images.length - 1);
    if (!atEnd) {
      pic.src = images[page].currentSrc || images[page].src;
      pic.alt = page + 1 + "쪽";
      if (pic.complete) fitComic();
      // Warm the next page so turning feels instant.
      if (images[page + 1]) new Image().src = images[page + 1].src;
    }
    range.value = String(page + 1);
    progress.textContent = atEnd ? "끝" : page + 1 + " / " + images.length;
    comic.querySelector("[data-comic=prev]").disabled = index === 0;
    comic.querySelector("[data-comic=next]").disabled = atEnd || (!comicEnd && index === images.length - 1);
    grid.querySelectorAll("[data-goto]").forEach((b) => b.toggleAttribute("aria-current", Number(b.dataset.goto) === page && !atEnd));
  }
  function openComic(at) {
    if (!images.length) return;
    index = at;
    show(comic, "[data-comic=close]");
    render();
  }
  function toggleGrid(force) {
    grid.hidden = force === undefined ? !grid.hidden : !force;
    gridToggle.setAttribute("aria-expanded", String(!grid.hidden));
    if (!grid.hidden) grid.querySelector("[aria-current]")?.scrollIntoView({ block: "nearest" });
  }
  comic.addEventListener("click", (e) => {
    if (e.target.closest("[data-end=close]")) return closeAll();
    const goto = e.target.closest("[data-goto]");
    if (goto) {
      index = Number(goto.dataset.goto);
      toggleGrid(false);
      return render();
    }
    // Clicking the left or right half of the page turns it, like pixiv's manga viewer.
    if (e.target === pic) {
      const box = pic.getBoundingClientRect();
      index += e.clientX < box.left + box.width / 2 ? -1 : 1;
      return render();
    }
    const a = e.target.closest("[data-comic]")?.dataset.comic;
    if (a === "close") closeAll();
    if (a === "next") { index++; render(); }
    if (a === "prev") { index--; render(); }
    if (a === "grid") toggleGrid();
    if (a === "vertical") {
      show(reader, "[data-action=close]");
      pages[Math.min(index, pages.length - 1)]?.scrollIntoView({ block: "start" });
    }
  });
  range.addEventListener("input", () => { index = Number(range.value) - 1; render(); });
  let x = null;
  comic.addEventListener("touchstart", (e) => { x = e.touches.length === 1 ? e.touches[0].clientX : null; }, { passive: true });
  comic.addEventListener("touchmove", (e) => { if (e.touches.length > 1) x = null; }, { passive: true });
  comic.addEventListener("touchend", (e) => {
    if (x === null || e.touches.length) return;
    const dx = e.changedTouches[0].clientX - x;
    x = null;
    if (Math.abs(dx) > 55) { index += dx < 0 ? 1 : -1; render(); }
  }, { passive: true });

  /* ---------- vertical reader (images and novels) ---------- */
  const scroll = reader.querySelector(".px-reader-scroll"),
    novel = reader.querySelector(".px-novel"),
    footer = reader.querySelector("footer"),
    end = reader.querySelector(".px-end");
  function refresh() {
    if (!novel || !paged) return;
    const count = Math.max(1, Math.ceil(novel.scrollWidth / novel.clientWidth));
    if (count !== novelPages && novelPages > 1) novelPage = Math.round((novelPage / (novelPages - 1)) * (count - 1));
    novelPages = count;
    novelPage = Math.max(0, Math.min(novelPage, count - 1));
    novel.scrollLeft = novelPage * novel.clientWidth;
    reader.querySelector("[data-progress]").textContent = novelPage + 1 + " / " + count;
    reader.querySelector("[data-action=prev]").disabled = novelPage === 0;
    // On the last page the "next" button continues to the end card.
    const last = novelPage === count - 1;
    end && (end.hidden = !last);
    reader.querySelector("[data-action=next]").disabled = last;
  }
  function setNovelMode(mode) {
    if (!novel || (mode === "pages") === paged) return;
    const fraction = paged
      ? novelPages > 1 ? novelPage / (novelPages - 1) : 0
      : scroll.scrollHeight > scroll.clientHeight ? scroll.scrollTop / (scroll.scrollHeight - scroll.clientHeight) : 0;
    paged = mode === "pages";
    novel.classList.toggle("px-paged", paged);
    reader.classList.toggle("is-paged", paged);
    footer.hidden = !paged;
    reader.querySelectorAll(".px-segment [data-action]").forEach((b) => b.setAttribute("aria-pressed", String(b.dataset.action === mode)));
    if (paged) {
      scroll.scrollTop = 0;
      novelPage = 0;
      novelPages = 1;
      refresh();
      novelPage = Math.round(fraction * (novelPages - 1));
      refresh();
    } else {
      novel.scrollLeft = 0;
      end && (end.hidden = false);
      requestAnimationFrame(() => { scroll.scrollTop = fraction * (scroll.scrollHeight - scroll.clientHeight); });
    }
  }
  reader.addEventListener("click", (e) => {
    if (e.target.closest("[data-end=close]")) return closeAll();
    const page = e.target.closest("[data-page]");
    if (page) return openComic(Number(page.dataset.page));
    const button = e.target.closest("[data-action]"),
      action = button?.dataset.action;
    if (action === "close") closeAll();
    if (action === "paged") openComic(0);
    if (action === "settings") {
      const opts = reader.querySelector(".px-reader-options");
      opts.hidden = !opts.hidden;
      button.setAttribute("aria-expanded", String(!opts.hidden));
    }
    if (action === "pages" || action === "scroll") setNovelMode(action);
    if (action === "next") { novelPage++; refresh(); }
    if (action === "prev") { novelPage = Math.max(0, novelPage - 1); refresh(); }
  });
  reader.querySelector("[data-font]")?.addEventListener("input", (e) => { novel.style.fontSize = e.target.value + "px"; refresh(); });
  reader.querySelector("[data-line]")?.addEventListener("input", (e) => { novel.style.lineHeight = e.target.value / 10; refresh(); });
  window.addEventListener("resize", refresh);

  /* ---------- opening ---------- */
  function openWork() {
    previous = document.activeElement;
    // Like pixiv: the cover opens every page stacked vertically; tapping a page opens the page-by-page viewer.
    show(reader, "[data-action=close]");
    scroll.scrollTop = 0;
    if (paged) refresh();
  }
  open.addEventListener("click", openWork);
  document.querySelectorAll("[data-open]").forEach((b) => b.addEventListener("click", openWork));

  document.addEventListener("keydown", (e) => {
    const active = !comic.hidden ? comic : !reader.hidden ? reader : null;
    if (!active) return;
    if (e.key === "Tab") {
      const focus = [...active.querySelectorAll("button:not([disabled]), input:not([disabled]), a[href]")].filter((el) => el.getClientRects().length);
      if (focus.length) {
        if (e.shiftKey && document.activeElement === focus[0]) { e.preventDefault(); focus[focus.length - 1].focus(); }
        else if (!e.shiftKey && document.activeElement === focus[focus.length - 1]) { e.preventDefault(); focus[0].focus(); }
      }
    }
    if (e.key === "Escape") {
      const opts = reader.querySelector(".px-reader-options");
      if (active === comic && !grid.hidden) return toggleGrid(false);
      if (active === reader && opts && !opts.hidden) return reader.querySelector("[data-action=settings]").click();
      return closeAll();
    }
    if (e.target.matches("input[type=range]")) return;
    const step = e.key === "ArrowRight" ? 1 : e.key === "ArrowLeft" ? -1 : 0;
    if (!step) return;
    if (active === comic) { index += step; render(); }
    else if (paged) { novelPage = Math.max(0, novelPage + step); refresh(); }
  });
})();
