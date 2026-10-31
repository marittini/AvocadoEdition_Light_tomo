(() => {
  const menus = document.querySelectorAll('.ucho-menu');
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
    menu.querySelectorAll('.has-sub').forEach(li => li.addEventListener('pointerenter', () => li.classList.remove('is-dismissed')));
    menu.addEventListener('keydown', event => {
      if (event.key !== 'Escape') return;
      const button = event.target.closest('.has-sub')?.querySelector('.ucho-menu-toggle');
      if (button) { button.setAttribute('aria-expanded', 'false'); button.closest('.has-sub').classList.add('is-dismissed'); button.focus(); }
    });
  });
})();
