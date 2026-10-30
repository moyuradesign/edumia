/** Edumia original single frontend JavaScript file. */
(() => {
  'use strict';
  const theme = document.querySelector('#theme');
  const lang = document.querySelector('#lang');
  const root = document.documentElement;
  const update = () => { if (theme) theme.setAttribute('aria-pressed', String(root.dataset.theme === 'dark')); };
  theme?.addEventListener('click', () => {
    root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    try { localStorage.setItem('edumia-theme', root.dataset.theme); } catch (_) {}
    update();
  });
  lang?.addEventListener('change', () => {
    const url = lang.value;
    if (url.startsWith(window.location.origin + '/')) window.location.assign(url);
  });
  update();
})();
