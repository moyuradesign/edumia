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
