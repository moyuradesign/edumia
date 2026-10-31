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
    // Close siblings on activation, before the native <details> toggle.
    // A delayed toggle event can otherwise race with a second mobile tap.
    menu.querySelector('summary')?.addEventListener('click', () => {
      menus.forEach(other => {
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
  // Mobile viewport height may change while menus expand or the browser UI moves.
  // Do not close dropdowns on those resize events: native <details> handles layout.
}
