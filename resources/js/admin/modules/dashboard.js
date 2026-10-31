/**
 * Admin shell: keyboard-friendly navigation, module finder, and account menu.
 * All navigation links and forms work without JavaScript.
 */
export function initAdminShell() {
  const shell = document.querySelector('[data-admin-shell]');
  if (!shell) return;

  const openButton = shell.querySelector('[data-admin-open]');
  const closeButton = shell.querySelector('[data-admin-close]');
  const backdrop = shell.querySelector('[data-admin-backdrop]');
  const sidebar = shell.querySelector('[data-admin-sidebar]');
  const search = shell.querySelector('[data-admin-search]');
  const profile = shell.querySelector('[data-admin-profile]');
  const links = [...shell.querySelectorAll('[data-admin-nav-link]')];
  const groups = [...shell.querySelectorAll('[data-admin-nav-group]')];

  const expanded = () => openButton?.getAttribute('aria-expanded') === 'true';
  const syncBackdrop = () => {
    if (backdrop) backdrop.hidden = !expanded();
  };
  const dismissSidebar = (restoreFocus = false) => {
    if (!expanded()) return;
    openButton?.click();
    syncBackdrop();
    if (restoreFocus) openButton?.focus();
  };

  openButton?.addEventListener('click', () => {
    // The shared mobile-navigation controller toggles aria-expanded on click.
    syncBackdrop();
  });
  closeButton?.addEventListener('click', () => dismissSidebar(true));
  backdrop?.addEventListener('click', () => dismissSidebar(true));
  links.forEach(link => link.addEventListener('click', () => dismissSidebar()));
  window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
      backdrop.hidden = true;
    } else {
      syncBackdrop();
    }
  });

  const applySearch = () => {
    if (!search) return;
    const value = search.value.trim().toLocaleLowerCase();
    links.forEach(link => {
      link.hidden = value !== '' && !(link.textContent || '').toLocaleLowerCase().includes(value);
    });
    groups.forEach(group => {
      group.hidden = !group.querySelector('[data-admin-nav-link]:not([hidden])');
    });
    if (value && window.innerWidth <= 1024 && !expanded()) {
      openButton?.click();
      syncBackdrop();
    }
  };
  search?.addEventListener('input', applySearch);
  search?.addEventListener('keydown', event => {
    if (event.key === 'Enter') {
      const found = links.find(link => !link.hidden);
      if (found) {
        event.preventDefault();
        window.location.assign(found.href);
      }
    }
    if (event.key === 'Escape') {
      search.value = '';
      applySearch();
      dismissSidebar();
      search.blur();
    }
  });
  document.addEventListener('keydown', event => {
    if (event.key === '/' && !event.altKey && !event.metaKey && !event.ctrlKey
      && !(event.target instanceof HTMLInputElement)
      && !(event.target instanceof HTMLTextAreaElement)
      && !(event.target instanceof HTMLSelectElement)
      && !event.target?.isContentEditable) {
      event.preventDefault();
      search?.focus();
    }
    if (event.key === 'Escape') {
      if (profile?.open) {
        profile.open = false;
        profile.querySelector('summary')?.focus();
      }
      syncBackdrop();
    }
  });
  document.addEventListener('pointerdown', event => {
    if (profile?.open && !profile.contains(event.target)) profile.open = false;
  });
}
