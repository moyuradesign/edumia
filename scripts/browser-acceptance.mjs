/**
 * Disposable customer-installation and browser smoke acceptance.
 * Run only against the ephemeral MySQL + PHP server prepared by qa-package.yml.
 * Never pass a production origin or database.
 */
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { chromium, firefox, webkit } from 'playwright';

const base = process.env.EDUMIA_QA_BASE_URL || 'http://127.0.0.1:8765';
const token = process.env.EDUMIA_QA_INSTALL_CODE;
const database = process.env.EDUMIA_QA_DATABASE || 'edumia_browser_qa';
const dbUser = process.env.EDUMIA_QA_DB_USER || 'edumia_qa';
const dbPassword = process.env.EDUMIA_QA_DB_PASSWORD;
const adminEmail = 'qa-owner@example.test';
const adminPassword = process.env.EDUMIA_QA_ADMIN_PASSWORD;
const evidence = process.env.EDUMIA_QA_EVIDENCE || 'qa-browser-evidence';

assert.equal(new URL(base).hostname, '127.0.0.1', 'Only isolated loopback servers are allowed');
assert.ok(token && token.length >= 16, 'Missing disposable installation authorization');
assert.ok(dbPassword && adminPassword, 'Missing disposable QA credentials');
fs.mkdirSync(evidence, { recursive: true });

let assertions = 0;
function verify(condition, message) {
  assert.ok(condition, message);
  assertions += 1;
}

async function navigate(page, pathname, expected = 200) {
  const response = await page.goto(new URL(pathname, base).href, { waitUntil: 'networkidle' });
  verify(response && response.status() === expected, pathname + ' returned ' + response?.status());
  return response;
}

async function install() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const page = await context.newPage();
  page.setDefaultTimeout(40000);
  page.setDefaultNavigationTimeout(120000);
  try {
    await navigate(page, '/');
    verify(new URL(page.url()).pathname === '/installer', 'New installation must redirect to /installer');
    verify(await page.getByRole('heading', { name: 'Edumia Installer' }).isVisible(), 'Installation form not displayed');
    verify((await page.locator('li.check .fail').count()) === 0, 'Installer server prerequisites are not satisfied');
    const blocked = await page.request.post(base + '/installer', { form: { _csrf: 'invalid-token' } });
    verify(blocked.status() === 419, 'Installer must reject missing/incorrect CSRF');
    verify((await navigate(page, '/installer')).status() === 200, 'Installer must remain available after rejected CSRF');

    const values = {
      app_name: 'Edumia Global QA',
      app_url: base,
      timezone: 'UTC',
      db_host: '127.0.0.1',
      db_port: '3306',
      db_database: database,
      db_username: dbUser,
      db_password: dbPassword,
      admin_name: 'Quality Assurance',
      admin_email: adminEmail,
      admin_password: adminPassword,
      admin_password_confirmation: adminPassword,
      authorization: token,
    };
    for (const [name, value] of Object.entries(values)) {
      await page.locator('input[name="' + name + '"]').fill(value);
    }
    await page.locator('input[name="accept"]').check();
    await page.getByRole('button', { name: 'Install Edumia' }).click();
    verify(await page.getByRole('heading', { name: 'Edumia installed' }).isVisible(),
      'Fresh MySQL installation failed: installer did not report completion');
    verify(!(await page.locator('body').innerText()).includes(dbPassword), 'Database password leaked to page');
    await navigate(page, '/installer', 404);
    await navigate(page, '/');
    verify(await page.locator('html').getAttribute('lang') === 'en', 'Default language incorrect');
    await navigate(page, '/.env', 404);

    await navigate(page, '/login');
    await page.locator('input[name="email"]').fill(adminEmail);
    await page.locator('input[name="password"]').fill(adminPassword);
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    verify(new URL(page.url()).pathname === '/dashboard', 'Initial administrator cannot log in');
    await navigate(page, '/admin/settings/general');
    verify(await page.getByRole('heading', { name: 'General settings' }).isVisible(),
      'Initial administrator cannot access general settings');

    await page.setViewportSize({ width: 390, height: 844 });
    const menu = page.locator('.panel-header .menu-toggle');
    verify(await menu.isVisible(), 'Mobile dashboard menu is not displayed');
    await menu.click();
    verify(await page.locator('#panel-navigation').isVisible(), 'Dashboard menu does not open');
    await page.keyboard.press('Escape');
    verify(!(await page.locator('#panel-navigation').isVisible()), 'Escape did not close dashboard menu');
    verify(await menu.evaluate(element => element === document.activeElement), 'Keyboard focus not restored');

    console.log('EDUMIA_FRESH_MYSQL_INSTALL=PASS');
    console.log('EDUMIA_INSTALLER_CSRF_LOCK_ADMIN=PASS');
  } finally {
    await context.close();
    await browser.close();
  }
}

const scenarios = [
  { name: 'chromium-desktop', engine: chromium, viewport: { width: 1440, height: 900 } },
  { name: 'chromium-mobile', engine: chromium, viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true },
  { name: 'chromium-tablet', engine: chromium, viewport: { width: 768, height: 1024 }, isMobile: true, hasTouch: true },
  { name: 'webkit-iphone', engine: webkit, viewport: { width: 375, height: 812 }, isMobile: true, hasTouch: true },
  { name: 'firefox-desktop', engine: firefox, viewport: { width: 1366, height: 768 } },
];

async function browse(scenario) {
  const browser = await scenario.engine.launch({ headless: true });
  const context = await browser.newContext({
    viewport: scenario.viewport,
    isMobile: scenario.isMobile || false,
    hasTouch: scenario.hasTouch || false,
    deviceScaleFactor: scenario.isMobile ? 2 : 1,
    locale: scenario.name.startsWith('webkit') ? 'ja-JP' : 'en-US',
  });
  const page = await context.newPage();
  page.setDefaultTimeout(30000);
  const errors = [];
  let assets = 0;
  page.on('pageerror', error => errors.push(error.message));
  page.on('response', response => {
    if (response.url().includes('/build/assets/')) {
      assets += 1;
      const type = response.headers()['content-type'] || '';
      if (!response.ok() || (!type.includes('javascript') && !type.includes('css'))) {
        errors.push('Asset response invalid: ' + response.status() + ' ' + response.url() + ' (' + type + ')');
      }
    }
  });
  try {
    await navigate(page, '/');
    verify((await page.title()).length > 0, 'Missing document title');
    verify(await page.locator('h1').first().isVisible(), 'Homepage heading not visible');
    verify(assets >= 2, 'Compiled JS and CSS assets must load via HTTP');
    // Premium header and footer: direct interaction, keyboard closure, and no overflow.
    const menuCategories = page.locator('details[data-header-details="categories"]');
    const menuProfile = page.locator('details[data-header-details="profile"]');
    verify(await page.locator('.premium-header-search input[name="q"]').count() === 1,
      'Search bar missing from center header');
    verify(await page.locator('.premium-footer-newsletter input[name="consent"]').count() === 1,
      'Premium footer newsletter consent missing');
    const mobile = scenario.viewport.width <= 1024;
    if (mobile) {
      const toggle = page.locator('.site-header .menu-toggle');
      verify(await toggle.isVisible(), scenario.name + ' is missing accessible menu control');
      verify(!(await page.locator('#primary-navigation').isVisible()), 'Mobile navigation must start closed');
      await toggle.click();
      verify(await page.locator('#primary-navigation').isVisible(), 'Mobile menu did not open');
      verify((await toggle.getAttribute('aria-expanded')) === 'true', 'Expanded state is inaccurate');
      await page.keyboard.press('Escape');
      verify(!(await page.locator('#primary-navigation').isVisible()), 'Escape did not close menu');
      verify((await toggle.getAttribute('aria-expanded')) === 'false', 'Collapsed state is inaccurate');
    } else {
      verify(await page.locator('#primary-navigation').isVisible(), 'Desktop navigation must remain visible');
    }

    // Test real layout geometry at all requested two-to-six-column breakpoints.
    const carousel = page.locator('[data-hero-slider]');
    verify(await carousel.isVisible(), 'Premium hero slider is missing');
    verify(await carousel.locator('[data-hero-slide]').count() === 3, 'Slider needs three panels');
    verify(await carousel.getAttribute('data-current-slide') === '0', 'First slide must start active');
    const secondSlide = carousel.locator('[data-hero-slide]').nth(1);
    verify(await secondSlide.getAttribute('aria-hidden') === 'true', 'Inactive slide must be hidden from accessibility tree');
    await carousel.locator('[data-hero-next]').click();
    verify(await carousel.getAttribute('data-current-slide') === '1', 'Next control did not change slide');
    verify(await secondSlide.getAttribute('aria-hidden') === 'false', 'Active slide remained hidden');
    await carousel.locator('[data-hero-dot="2"]').click();
    verify(await carousel.getAttribute('data-current-slide') === '2', 'Direct slide selection failed');
    await carousel.locator('[data-hero-prev]').click();
    verify(await carousel.getAttribute('data-current-slide') === '1', 'Previous control failed');
    await carousel.locator('[data-hero-pause]').click();
    verify(await carousel.locator('[data-hero-pause]').getAttribute('aria-pressed') === 'true',
      'Pause control did not update its state');
    await carousel.locator('[data-hero-dot="0"]').click();
    verify(await carousel.getAttribute('data-current-slide') === '0', 'Slider did not return to first panel');

    const breakpoints = [
      [390, 2], [600, 2], [601, 3], [768, 3], [899, 3],
      [900, 4], [1024, 4], [1199, 4], [1200, 5], [1440, 5], [1599, 5], [1600, 6], [1800, 6],
    ];
    for (const [width, columns] of breakpoints) {
      await page.setViewportSize({ width, height: 900 });
      const computed = await page.locator('.home-course-grid').evaluate(el =>
        getComputedStyle(el).gridTemplateColumns.trim().split(/\s+/).length);
      verify(computed === columns, width + 'px viewport has ' + computed + ' columns instead of ' + columns);
      if (width === 390 || width === 1800) {
        await page.screenshot({ path: path.join(evidence, 'home-grid-' + width + '.png') });
      }
    }
    await page.setViewportSize(scenario.viewport);
    verify(await page.locator('.home-hero-search input[name="q"]').count() === 1,
      'Homepage search must remain accessible inside the first slide');

    if (mobile) await page.locator('.site-header .menu-toggle').click();
    await menuCategories.locator('summary').click();
    verify(await menuCategories.getAttribute('open') !== null, 'Categories mega menu did not open');
    verify(await page.locator('.premium-mega-menu a[href$="/bootcamps"]').count() >= 1,
      'Mega menu is missing learning links');
    if (mobile) {
      await menuCategories.locator('summary').click();
      if (!(await page.locator('#primary-navigation').isVisible())) {
        await page.locator('.site-header .menu-toggle').click();
      }
    }
    const menuBefore = await page.evaluate(() => ({
      categories: document.querySelector('[data-header-details="categories"]')?.open,
      profile: document.querySelector('[data-header-details="profile"]')?.open,
      navOpen: document.querySelector('#primary-navigation')?.classList.contains('is-open'),
      profileInNav: document.querySelector('#primary-navigation')?.contains(document.querySelector('[data-header-details="profile"]')),
      width: window.innerWidth
    }));
    console.log('EDUMIA_HEADER_MENU_BEFORE=' + JSON.stringify(menuBefore));
    await menuProfile.locator('summary').click();
    const detailsState = await page.evaluate(() => ({
      categories: document.querySelector('[data-header-details="categories"]')?.open,
      profile: document.querySelector('[data-header-details="profile"]')?.open,
      mobileNavOpen: document.querySelector('#primary-navigation')?.classList.contains('is-open'),
      width: window.innerWidth
    }));
    console.log('EDUMIA_HEADER_MENU_STATE=' + JSON.stringify(detailsState));
    await page.waitForFunction(() =>
      document.querySelector('details[data-header-details="profile"]')?.open === true, null,
      { timeout: 5000 });
    verify(await menuProfile.getAttribute('open') !== null, 'Profile menu did not open');
    verify(await menuCategories.getAttribute('open') === null, 'Opening profile should close categories');
    await page.keyboard.press('Escape');
    verify(await menuProfile.getAttribute('open') === null, 'Escape did not close profile menu');
    if (mobile) await page.locator('.site-header .menu-toggle').click();
    await page.locator('.premium-header-search input[name="q"]').fill('sample');
    await page.locator('.premium-header-search button').click();
    await page.waitForURL(/\/courses\?q=sample/);
    await navigate(page, '/');

    const horizontalOverflow = await page.evaluate(() =>
      document.documentElement.scrollWidth - document.documentElement.clientWidth);
    verify(horizontalOverflow <= 2, scenario.name + ' has horizontal overflow (' + horizontalOverflow + 'px)');
    await page.screenshot({ path: path.join(evidence, scenario.name + '.png') });

    await navigate(page, '/courses?q=sample');
    if (mobile) await page.locator('.site-header .menu-toggle').click();
    await page.locator('details[data-header-details="profile"] > summary').click();
    await page.locator('#language-select').selectOption({ label: 'Français' });
    await page.waitForURL(/\/fr\/courses\?q=sample/);
    verify(await page.locator('html').getAttribute('lang') === 'fr', 'French URL language not applied');
    verify((await page.locator('h1').first().innerText()).length > 0, 'Localized catalogue heading not rendered');
    await navigate(page, '/de/contact');
    verify(await page.getByRole('button', { name: 'Nachricht senden' }).isVisible(),
      'Translated contact form is unavailable');

    await navigate(page, '/');
    if (mobile) await page.locator('.site-header .menu-toggle').click();
    await page.locator('details[data-header-details="profile"] > summary').click();
    await page.locator('.theme-toggle').first().click();
    verify(await page.locator('html').getAttribute('data-theme') === 'dark', 'Theme toggle failed');
    await page.reload({ waitUntil: 'networkidle' });
    verify(await page.locator('html').getAttribute('data-theme') === 'dark', 'Theme choice not retained');

    // Authentication is intentionally a centered standalone card, not a modal dialog.
    // Real browser checks validate focus/filled floating labels and SVG controls.
    await navigate(page, '/login');
    verify(await page.locator('.auth-card').isVisible(), 'Centered sign-in card unavailable');
    verify(await page.locator('header, footer').count() === 0, 'Auth page must have no header or footer');
    verify(await page.locator('.auth-field-icon').count() >= 2, 'SVG field icons missing');
    verify(await page.locator('.auth-page .auth-logo').isVisible(), 'Brand inside auth card missing');

    const emailLabel = page.locator('label[for="auth-email"]');
    const emailInput = page.locator('#auth-email');
    const labelTop = () => emailLabel.evaluate(el => Number.parseFloat(window.getComputedStyle(el).top));
    const restingPosition = await labelTop();
    await emailInput.focus();
    await page.waitForTimeout(240);
    const focusedPosition = await labelTop();
    verify(focusedPosition < restingPosition - 7, 'Floating email label did not rise on focus');
    await emailInput.fill('example@example.test');
    await page.locator('#auth-heading').click();
    await page.waitForTimeout(240);
    verify((await labelTop()) < restingPosition - 7, 'Floating label did not stay up when populated');

    const password = page.locator('#auth-password');
    const show = page.locator('button[data-password-toggle][aria-controls="auth-password"]');
    verify(await password.getAttribute('type') === 'password', 'Password initially visible');
    await show.click();
    verify(await password.getAttribute('type') === 'text', 'Password show control failed');
    verify(await show.getAttribute('aria-pressed') === 'true', 'Show password state not announced');
    await show.click();
    verify(await password.getAttribute('type') === 'password', 'Password hide control failed');

    const authOverflow = await page.evaluate(() =>
      document.documentElement.scrollWidth - document.documentElement.clientWidth);
    verify(authOverflow <= 2, 'Auth layout has horizontal overflow (' + authOverflow + 'px)');
    await emailInput.clear();
    await page.screenshot({ path: path.join(evidence, 'auth-' + scenario.name + '.png') });

    await navigate(page, '/register');
    verify(await page.locator('input[name="name"]').count() === 1, 'Registration name field missing');
    verify(await page.locator('select[name="locale"]').count() === 1, 'Registration locale select missing');
    verify(await page.locator('.auth-field-icon').count() >= 5, 'Registration SVG icons missing');
    verify(await page.locator('header, footer').count() === 0, 'Registration has unwanted header/footer');
    await navigate(page, '/forgot-password');
    verify(await page.locator('form input[name="email"]').count() === 1, 'Recovery email field missing');
    await navigate(page, '/reset-password/qa-token?email=example%40example.test');
    verify(await page.locator('input[type="hidden"][name="token"]').inputValue() === 'qa-token',
      'Reset token not preserved in form');
    verify(await page.locator('input[name="password_confirmation"]').count() === 1,
      'Password confirmation field missing');

    verify(errors.length === 0, 'Browser console or asset errors: ' + errors.join(' | '));

    console.log('EDUMIA_BROWSER=' + scenario.name + ' PASS');
  } finally {
    await context.close();
    await browser.close();
  }
}

try {
  await install();
  for (const scenario of scenarios) await browse(scenario);
  console.log('EDUMIA_BROWSER_ACCEPTANCE=PASS');
  console.log('EDUMIA_QA_ASSERTIONS=' + assertions);
} catch (error) {
  console.error('EDUMIA_BROWSER_ACCEPTANCE=FAIL');
  console.error(error.stack || String(error));
  process.exitCode = 1;
}
