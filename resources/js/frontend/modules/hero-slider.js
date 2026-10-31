/**
 * Progressive-enhancement homepage slider.
 * Keeps all panels readable without JavaScript; only enhances the homepage.
 */
export function initHeroSlider() {
  const slider = document.querySelector('[data-hero-slider]');
  if (!slider) return;

  const track = slider.querySelector('[data-hero-track]');
  const slides = [...slider.querySelectorAll('[data-hero-slide]')];
  const dots = [...slider.querySelectorAll('[data-hero-dot]')];
  const previous = slider.querySelector('[data-hero-prev]');
  const next = slider.querySelector('[data-hero-next]');
  const pauseButton = slider.querySelector('[data-hero-pause]');
  const counter = slider.querySelector('[data-hero-count]');
  if (!track || slides.length < 2 || dots.length !== slides.length || !previous || !next || !pauseButton) return;

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  let current = 0;
  let manuallyPaused = reducedMotion.matches;
  let hovered = false;
  let focused = false;

  const render = () => {
    slider.dataset.currentSlide = String(current);
    track.style.transform = 'translate3d(-' + (current * 100) + '%, 0, 0)';
    slides.forEach((slide, index) => {
      const active = index === current;
      slide.inert = !active;
      slide.setAttribute('aria-hidden', String(!active));
    });
    dots.forEach((dot, index) => {
      const active = index === current;
      dot.classList.toggle('is-active', active);
      if (active) dot.setAttribute('aria-current', 'true');
      else dot.removeAttribute('aria-current');
    });
    if (counter) counter.textContent = String(current + 1).padStart(2, '0') + ' / ' + String(slides.length).padStart(2, '0');
    pauseButton.setAttribute('aria-pressed', String(manuallyPaused));
    pauseButton.setAttribute('aria-label', manuallyPaused ? 'Resume slides' : 'Pause slides');
    pauseButton.querySelector('span')?.replaceChildren(document.createTextNode(manuallyPaused ? '▶' : 'Ⅱ'));
  };

  const goTo = (index) => {
    current = (index + slides.length) % slides.length;
    render();
  };
  previous.addEventListener('click', () => goTo(current - 1));
  next.addEventListener('click', () => goTo(current + 1));
  dots.forEach((dot, index) => dot.addEventListener('click', () => goTo(index)));
  pauseButton.addEventListener('click', () => {
    manuallyPaused = !manuallyPaused;
    render();
  });

  slider.addEventListener('mouseenter', () => { hovered = true; });
  slider.addEventListener('mouseleave', () => { hovered = false; });
  slider.addEventListener('focusin', () => { focused = true; });
  slider.addEventListener('focusout', (event) => {
    if (!slider.contains(event.relatedTarget)) focused = false;
  });
  slider.addEventListener('keydown', (event) => {
    if (event.target.closest('input, textarea, select')) return;
    if (event.key === 'ArrowRight') { event.preventDefault(); goTo(current + 1); }
    if (event.key === 'ArrowLeft') { event.preventDefault(); goTo(current - 1); }
  });

  let touchX = null;
  slider.addEventListener('touchstart', (event) => {
    if (event.target.closest('input, button, a, select')) return;
    touchX = event.changedTouches[0]?.screenX ?? null;
  }, { passive: true });
  slider.addEventListener('touchend', (event) => {
    if (touchX === null) return;
    const delta = (event.changedTouches[0]?.screenX ?? touchX) - touchX;
    touchX = null;
    if (Math.abs(delta) > 65) goTo(current + (delta < 0 ? 1 : -1));
  }, { passive: true });

  if (reducedMotion.addEventListener) {
    reducedMotion.addEventListener('change', (event) => {
      if (event.matches) manuallyPaused = true;
      render();
    });
  }

  // Slide changes are automatic only when not being read or interacted with.
  window.setInterval(() => {
    if (!manuallyPaused && !reducedMotion.matches && !hovered && !focused && !document.hidden) goTo(current + 1);
  }, 7500);

  // Do this last: CSS only hides offscreen slides once JS has bound the controls.
  slider.classList.add('is-ready');
  render();
}
