/* 클릭 효과(tail.click_effect.php): 원 + 효과음. 설정은 tail.click_effect.php 맨 위에서 바꾼다. */
(() => {
  const me = document.currentScript;
  const src = me && me.dataset.sound;
  const volume = me ? Number(me.dataset.volume || 0.5) : 0.5;
  const reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)");

  // 연달아 눌러도 소리가 끊기지 않게 몇 개를 번갈아 쓴다.
  const pool = [];
  let next = 0;
  if (src) {
    for (let i = 0; i < 4; i++) {
      const a = new Audio(src);
      a.preload = "auto";
      a.volume = volume;
      pool.push(a);
    }
  }
  function play() {
    if (!pool.length) return;
    const a = pool[next];
    next = (next + 1) % pool.length;
    try {
      a.currentTime = 0;
      const p = a.play();
      if (p && p.catch) p.catch(() => {}); // 브라우저가 막으면 조용히 넘어간다
    } catch (_) { /* 재생할 수 없는 환경 */ }
  }

  function ripple(x, y) {
    if (reduce && reduce.matches) return;
    const dot = document.createElement("div");
    dot.className = "click-effect";
    dot.setAttribute("aria-hidden", "true");
    dot.style.left = x + "px";
    dot.style.top = y + "px";
    dot.addEventListener("animationend", () => dot.remove(), { once: true });
    setTimeout(() => dot.remove(), 3000); // 애니메이션이 끝나지 않는 환경 대비
    document.body.appendChild(dot);
  }

  // 링크를 눌러 페이지가 바뀌기 전에 보이고 들리도록 누르는 순간(캡처 단계)에 처리한다.
  document.addEventListener("pointerdown", (e) => {
    if (e.button !== 0 || !e.isPrimary) return;
    play();
    ripple(e.clientX, e.clientY);
  }, { capture: true, passive: true });
})();
