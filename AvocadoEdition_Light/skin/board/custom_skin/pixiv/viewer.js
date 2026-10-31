(() => {
  const open = document.querySelector("#px-open"),
    reader = document.querySelector("#px-reader"),
    comic = document.querySelector("#px-comic");
  if (!open || !reader || !comic) return;
  const images = [...reader.querySelectorAll(".px-images img")],
    pic = comic.querySelector(".px-comic-image");
  const progress = comic.querySelector("[data-comic-progress]");
  let index = 0,
    previous = null,
    paged = false,
    novelPage = 0,
    novelPages = 1;
  function render() {
    if (!images.length) return;
    index = Math.max(0, Math.min(images.length - 1, index));
    pic.src = images[index].src;
    pic.alt = index + 1 + "쪽";
    progress.textContent = index + 1 + " / " + images.length;
    comic.querySelector("[data-comic=prev]").disabled = index === 0;
    comic.querySelector("[data-comic=next]").disabled =
      index === images.length - 1;
  }
  // Reuse the theme box's color while making the reading layer opaque.
  function solidBackground(layer) {
    const c = getComputedStyle(layer).backgroundColor.match(/[\d.]+/g);
    if (c && c.length >= 3 && (c.length < 4 || Number(c[3]) > 0)) {
      layer.style.backgroundColor = "rgb(" + c.slice(0, 3).join(",") + ")";
      return;
    }
    const body = getComputedStyle(document.body).backgroundColor.match(
      /[\d.]+/g,
    );
    layer.style.backgroundColor =
      body && body.length >= 3 && (body.length < 4 || Number(body[3]) > 0)
        ? "rgb(" + body.slice(0, 3).join(",") + ")"
        : "#fff";
  }
  open.addEventListener("click", () => {
    previous = document.activeElement;
    if (reader.dataset.kind === "manga" && images.length) {
      comic.hidden = false;
      solidBackground(comic);
      index = 0;
      render();
      comic.querySelector("[data-comic=close]").focus();
    } else {
      reader.hidden = false;
      solidBackground(reader);
      reader.querySelector("[data-action=close]").focus();
    }
    document.documentElement.classList.add("px-reading");
  });
  reader.querySelector("[data-action=close]").addEventListener("click", () => {
    reader.hidden = true;
    document.documentElement.classList.remove("px-reading");
    previous?.focus();
  });
  comic.addEventListener("click", (e) => {
    const a = e.target.closest("[data-comic]")?.dataset.comic;
    if (a === "close") {
      comic.hidden = true;
      document.documentElement.classList.remove("px-reading");
      previous?.focus();
    }
    if (a === "next") {
      index++;
      render();
    }
    if (a === "prev") {
      index--;
      render();
    }
  });
  let x = null;
  comic.addEventListener(
    "touchstart",
    (e) => {
      x = e.touches.length === 1 ? e.touches[0].clientX : null;
    },
    { passive: true },
  );
  comic.addEventListener(
    "touchmove",
    (e) => {
      if (e.touches.length > 1) x = null;
    },
    { passive: true },
  );
  comic.addEventListener(
    "touchend",
    (e) => {
      if (x === null || e.touches.length) return;
      const dx = e.changedTouches[0].clientX - x;
      x = null;
      if (Math.abs(dx) > 55) {
        index += dx < 0 ? 1 : -1;
        render();
      }
    },
    { passive: true },
  );
  const scroll = reader.querySelector(".px-reader-scroll"),
    novel = reader.querySelector(".px-novel"),
    footer = reader.querySelector("footer");
  function refresh() {
    if (!novel || !paged) return;
    let pages = Math.max(1, Math.ceil(novel.scrollWidth / novel.clientWidth));
    if (pages !== novelPages && novelPages > 1)
      novelPage = Math.round((novelPage / (novelPages - 1)) * (pages - 1));
    novelPages = pages;
    novelPage = Math.max(0, Math.min(novelPage, pages - 1));
    novel.scrollLeft = novelPage * novel.clientWidth;
    reader.querySelector("[data-progress]").textContent =
      novelPage + 1 + " / " + pages;
  }
  reader.addEventListener("click", (e) => {
    let action = e.target.closest("[data-action]")?.dataset.action;
    if (action === "settings") {
      const opts = reader.querySelector(".px-reader-options");
      if (opts) {
        opts.hidden = !opts.hidden;
        e.target.setAttribute("aria-expanded", String(!opts.hidden));
      }
    }
    if (!novel) return;
    if ((action === "pages" && !paged) || (action === "scroll" && paged)) {
      const fraction = paged
        ? novelPages > 1
          ? novelPage / (novelPages - 1)
          : 0
        : scroll.scrollHeight > scroll.clientHeight
          ? scroll.scrollTop / (scroll.scrollHeight - scroll.clientHeight)
          : 0;
      paged = action === "pages";
      novel.classList.toggle("px-paged", paged);
      footer.hidden = !paged;
      if (paged) {
        novelPage = 0;
        novelPages = 1;
        refresh();
        novelPage = Math.round(fraction * (novelPages - 1));
        refresh();
      } else {
        novel.scrollLeft = 0;
        requestAnimationFrame(() => {
          scroll.scrollTop =
            fraction * (scroll.scrollHeight - scroll.clientHeight);
        });
      }
    }
    if (action === "next") {
      novelPage++;
      refresh();
    }
    if (action === "prev") {
      novelPage = Math.max(0, novelPage - 1);
      refresh();
    }
  });
  reader.querySelector("[data-font]")?.addEventListener("input", (e) => {
    novel.style.fontSize = e.target.value + "px";
    refresh();
  });
  reader.querySelector("[data-line]")?.addEventListener("input", (e) => {
    novel.style.lineHeight = e.target.value / 10;
    refresh();
  });
  window.addEventListener("resize", refresh);
  document.addEventListener("keydown", (e) => {
    const active = !comic.hidden ? comic : !reader.hidden ? reader : null;
    if (active && e.key === "Tab") {
      const focus = [
        ...active.querySelectorAll(
          "button:not([disabled]), input:not([disabled]), a[href]",
        ),
      ].filter((el) => el.getClientRects().length);
      if (focus.length) {
        if (e.shiftKey && document.activeElement === focus[0]) {
          e.preventDefault();
          focus[focus.length - 1].focus();
        } else if (
          !e.shiftKey &&
          document.activeElement === focus[focus.length - 1]
        ) {
          e.preventDefault();
          focus[0].focus();
        }
      }
    }
    if (!comic.hidden) {
      if (e.key === "Escape") comic.querySelector("[data-comic=close]").click();
      if (e.key === "ArrowRight") {
        index++;
        render();
      }
      if (e.key === "ArrowLeft") {
        index--;
        render();
      }
    } else if (!reader.hidden && e.key === "Escape")
      reader.querySelector("[data-action=close]").click();
  });
})();
