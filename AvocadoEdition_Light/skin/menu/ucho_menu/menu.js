(() => {
  const menus = document.querySelectorAll('.ucho-menu');
  const close = (li) => {
    const button = li.querySelector('.ucho-menu-toggle');
    if (button) button.setAttribute('aria-expanded', 'false');
    // 눌러서 생긴 포커스(BGM 창이 뜬 뒤 첫 화면에서 특히)를 풀어 줘야 마우스를 떼면 닫힌다
    if (li.contains(document.activeElement)) document.activeElement.blur();
  };
  menus.forEach(menu => {
    menu.addEventListener('click', event => {
      const button = event.target.closest('.ucho-menu-toggle');
      if (!button) return;
      button.closest('.has-sub').classList.remove('is-dismissed');
      const expanded = button.getAttribute('aria-expanded') !== 'true';
      menu.querySelectorAll('.ucho-menu-toggle').forEach(b => b.setAttribute('aria-expanded', 'false'));
      button.setAttribute('aria-expanded', String(expanded));
      button.closest('.has-sub').classList.toggle('is-dismissed', !expanded);
    });
    menu.querySelectorAll('.has-sub').forEach(li => {
      li.addEventListener('pointerenter', () => li.classList.remove('is-dismissed'));
      li.addEventListener('focusin', () => li.classList.remove('is-dismissed')); // 키보드로 다시 오면 열 수 있게
      // 마우스(PC)는 떼면 닫는다. 터치는 누른 채로 둔다.
      li.addEventListener('pointerleave', event => { if (event.pointerType === 'mouse') close(li); });
    });
    // 하위 메뉴 링크를 누르면(본문 창으로 이동) 닫는다
    menu.addEventListener('click', event => {
      const link = event.target.closest('.sub-menu a');
      if (link) { const li = link.closest('.has-sub'); close(li); li.classList.add('is-dismissed'); }
    });
    menu.addEventListener('keydown', event => {
      if (event.key !== 'Escape') return;
      const button = event.target.closest('.has-sub')?.querySelector('.ucho-menu-toggle');
      if (button) { button.setAttribute('aria-expanded', 'false'); button.closest('.has-sub').classList.add('is-dismissed'); button.focus(); }
    });
  });
  // 바깥(본문 창 포함)을 누르거나 창이 포커스를 잃으면 열린 하위 메뉴를 닫는다
  document.addEventListener('pointerdown', event => {
    menus.forEach(menu => menu.querySelectorAll('.has-sub').forEach(li => { if (!li.contains(event.target)) close(li); }));
  });
  window.addEventListener('blur', () => menus.forEach(menu => menu.querySelectorAll('.ucho-menu-toggle[aria-expanded=true]').forEach(b => close(b.closest('.has-sub')))));
})();
