/**
 * Public header: native <details> menus remain usable without JavaScript.
 */
export function initPremiumHeader() {
  const header = document.querySelector('[data-premium-header]');
  if (!header) return;

  const menus = [...header.querySelectorAll('details[data-header-details]')];
  const close = (menu, restoreFocus = false) => {
    if (!menu.open) return;
    menu.open = false;
    if (restoreFocus) menu.querySelector('summary')?.focus();
  };

  menus.forEach((menu) => {
    menu.addEventListener('toggle', () => {
      if (menu.open) menus.forEach(other => {
        if (other !== menu) close(other);
      });
    });
    menu.addEventListener('click', (event) => {
      if (event.target.closest('a')) close(menu);
    });
  });

  document.addEventListener('pointerdown', (event) => {
    menus.forEach(menu => {
      if (!menu.contains(event.target)) close(menu);
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const active = menus.find(menu => menu.open);
    if (active) {
      event.preventDefault();
      close(active, true);
    }
  });
  window.addEventListener('resize', () => {
    menus.forEach(menu => close(menu));
  });
}
