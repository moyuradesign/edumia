export function initLanguageSelector() {
  const selector = document.getElementById('language-select');
  if (!selector) return;

  selector.addEventListener('change', () => {
    try {
      const destination = new URL(selector.value, window.location.origin);
      if (destination.origin === window.location.origin && (destination.protocol === 'https:' || (destination.protocol === 'http:' && ['localhost', '127.0.0.1'].includes(destination.hostname)))) {
        window.location.assign(destination.href);
      }
    } catch (_) {
      // Bad URL: remain on the current page.
    }
  });
}


/**
 * Progressive enhancement: without JS, links remain visible.
 * Mobile navigation remains ordinary inline content, not a modal or focus trap.
 */
export function initMobileMenus() {
  document.documentElement.classList.add('js-navigation');

  document.querySelectorAll('.menu-toggle[aria-controls]').forEach((button) => {
    const navigation = document.getElementById(button.getAttribute('aria-controls'));
    if (!navigation) return;

    const setOpen = (open) => {
      button.setAttribute('aria-expanded', String(open));
      button.setAttribute('aria-label', button.dataset[open ? 'closeLabel' : 'openLabel'] || 'Menu');
      navigation.classList.toggle('is-open', open);
    };

    button.addEventListener('click', () => setOpen(button.getAttribute('aria-expanded') !== 'true'));

    navigation.addEventListener('click', (event) => {
      if (event.target.closest('a')) setOpen(false);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') {
        setOpen(false);
        button.focus();
      }
    });

    document.addEventListener('click', (event) => {
      if (!navigation.contains(event.target) && !button.contains(event.target)) setOpen(false);
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 1024) setOpen(false);
    });
  });
}
