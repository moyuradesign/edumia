/**
 * Authentication-only progressive enhancement.
 * Labels float through CSS even when JavaScript is unavailable.
 */
export function initAuthPasswordVisibility() {
  document.querySelectorAll('[data-password-toggle][aria-controls]').forEach((button) => {
    const field = document.getElementById(button.getAttribute('aria-controls'));
    if (!(field instanceof HTMLInputElement) || field.type !== 'password') return;

    button.addEventListener('click', () => {
      const isRevealed = field.type === 'password';
      field.type = isRevealed ? 'text' : 'password';
      const fieldLabel = field.labels?.[0]?.textContent?.trim().toLowerCase() || 'password';
      const description = (isRevealed ? 'Hide ' : 'Show ') + fieldLabel;
      button.setAttribute('aria-label', description);
      button.setAttribute('title', description);
      button.setAttribute('aria-pressed', String(isRevealed));
    });
  });
}
