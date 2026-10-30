/** Edumia v0.0.1 — single progressive-enhancement entry point. */
(() => {
  'use strict';
  const root = document.documentElement;
  const toggle = document.querySelector('.theme-toggle');
  const selection = document.querySelector('#language-select');

  const paint = () => {
    if (!toggle) return;
    const dark = root.dataset.theme === 'dark';
    toggle.setAttribute('aria-pressed', String(dark));
    toggle.querySelector('.theme-icon').textContent = dark ? '☀' : '◐';
    // Labels already translated by the server; keep the control accessible.
    toggle.setAttribute('title', dark ? 'Switch to light mode' : 'Switch to dark mode');
  };

  toggle?.addEventListener('click', () => {
    root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    try { localStorage.setItem('edumia-theme', root.dataset.theme); } catch (_) {}
    paint();
  });
  selection?.addEventListener('change', () => {
    const destination = selection.value;
    if (destination.startsWith(window.location.origin + '/')) window.location.assign(destination);
  });
  paint();
})();
