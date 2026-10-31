export function initTheme() {
  const root = document.documentElement;
  const toggles = document.querySelectorAll('.theme-toggle');

  const refresh = () => {
    const dark = root.dataset.theme === 'dark';
    toggles.forEach((toggle) => {
      toggle.setAttribute('aria-pressed', String(dark));
      const icon = toggle.querySelector('.theme-icon');
      if (icon) icon.textContent = dark ? '☀' : '◐';
      toggle.setAttribute('title', dark ? 'Switch to light mode' : 'Switch to dark mode');
    });
  };

  toggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
      root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
      try {
        window.localStorage.setItem('edumia-theme', root.dataset.theme);
      } catch (_) {
        // Persistence is optional; toggling still works without local storage.
      }
      refresh();
    });
  });
  refresh();
}
