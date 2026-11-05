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
    // Fresh MySQL migrations can take longer on a cold independent runner.
    // Keep the navigation bounded, but do not use the short default action timeout.
    await page.getByRole('button', { name: 'Install Edumia' }).click({ timeout: 120000 });
    await page.getByRole('heading', { name: 'Edumia installed' }).waitFor({
      state: 'visible', timeout: 120000,
    });
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

    // Inspect admin workspaces, including read-only release readiness, in the fresh customer copy.
    for (const module of adminModules) {
      await navigate(page, module.url);
      verify(await page.locator(module.container).isVisible(),
        'Freshly installed admin module missing: ' + module.slug);
      verify(await page.getByRole('heading', { name: module.heading, exact: true }).isVisible(),
        'Admin module heading missing: ' + module.slug);
      const overflow = await page.evaluate(() =>
        document.documentElement.scrollWidth - document.documentElement.clientWidth);
      verify(overflow <= 2, 'Fresh install desktop overflow in ' + module.slug + ': ' + overflow);
      await page.screenshot({ path: path.join(evidence, 'installed-' + module.slug + '-desktop.png') });
    }

    // Smoke-check every existing admin management listing in the installed MySQL copy.
    // This verifies that protected routes render; it does not certify business feature parity.
    for (const moduleUrl of adminManagementRoutes) {
      await navigate(page, moduleUrl);
      verify(await page.locator('[data-admin-shell]').isVisible(),
        'Admin workspace shell missing for ' + moduleUrl);
      verify(await page.locator('#admin-main').isVisible(),
        'Admin content failed to render for ' + moduleUrl);
    }
    console.log('EDUMIA_ADMIN_MODULE_ROUTE_SMOKE=PASS');

    // New workflows must render on the independently installed MySQL app.
    await navigate(page, '/admin/team-training');
    verify(await page.getByRole('heading', { name: 'Team training packages' }).isVisible(),
      'Free team package administrator UI missing');
    await navigate(page, '/admin/commerce/gateway-readiness');
    verify(await page.getByRole('heading', { name: 'External payment readiness' }).isVisible(),
      'Real provider readiness diagnostics missing');
    verify((await page.getByRole('button', { name: 'Verify Stripe TEST API connection' }).count()) === 0,
      'Payment connection must not be falsely available without operator secrets');

    await navigate(page, '/tutors');
    verify(await page.getByRole('heading', { name: 'Find a tutor' }).isVisible(),
      'Tutor discovery not routed through the public site');
    await navigate(page, '/team-training');
    verify(await page.getByRole('heading', { name: 'Team training' }).isVisible(),
      'Team package discovery missing');

    await navigate(page, '/instructor/tutoring');
    verify(await page.getByRole('heading', { name: 'Tutoring studio' }).isVisible(),
      'Instructor-owned tutor schedule UI missing');
    await navigate(page, '/my-tutor-bookings');
    verify(await page.getByRole('heading', { name: 'My tutoring appointments' }).isVisible(),
      'Private learner tutor booking history missing');
    await navigate(page, '/my-team-training');
    verify(await page.getByRole('heading', { name: 'My team training' }).isVisible(),
      'Team invitation and seat workspace missing');
    console.log('EDUMIA_TUTORS_TEAMS_ASSESSMENT_PAYMENT_READINESS=PASS');


    // Audience-specific account screens must share the visual system,
    // but never expose the administrator sidebar on member routes.
    await navigate(page, '/account');
    verify(await page.locator('[data-member-shell]').isVisible(), 'Student workspace shell missing');
    verify(await page.locator('.admin-brand-text small').innerText() === 'STUDENT ACCOUNT',
      'Student workspace is showing the wrong brand or admin navigation');
    verify((await page.locator('[data-admin-shell]').count()) === 0,
      'Student workspace must not expose the admin shell');
    verify(await page.locator('a[href$="/account/certificates"]').count() >= 1,
      'Student certificate workspace navigation missing');
    await navigate(page, '/account/certificates');

    await navigate(page, '/instructor');
    verify(await page.locator('[data-member-shell]').isVisible(), 'Instructor workspace shell missing');
    verify(await page.locator('.admin-brand-text small').innerText() === 'INSTRUCTOR STUDIO',
      'Instructor workspace is showing the wrong brand or admin navigation');
    verify((await page.locator('[data-admin-shell]').count()) === 0,
      'Instructor workspace must not expose the admin shell');
    await navigate(page, '/instructor/students');
    verify(await page.locator('a[href*="/instructor/students/export"]').count() >= 1,
      'Instructor roster is missing its working export action');
    await navigate(page, '/instructor/students?status=active');
    verify(await page.locator('select[name="status"]').inputValue() === 'active',
      'Instructor roster status filter did not retain the selected value');
    const studentExport = await page.request.get(new URL('/instructor/students/export', base).href);
    verify(studentExport.status() === 200 &&
      (studentExport.headers()['content-type'] || '').startsWith('text/csv'),
      'Instructor roster export must return a real CSV file');
    verify((await studentExport.text()).includes('Enrollment status'),
      'Instructor CSV export is missing its real field headers');
    await navigate(page, '/instructor/reports');
    verify(await page.getByRole('heading', { name: 'My learning reports' }).isVisible(),
      'Instructor real learning report missing');

    await navigate(page, '/edumia-page-that-does-not-exist', 404);
    verify(await page.locator('.premium-header').count() === 1, '404 does not use the global site header');
    verify(await page.locator('footer.premium-footer').count() === 1, '404 does not use the global footer');
    verify(await page.locator('#page-not-found-title').innerText() === 'Page not found',
      'Focused branded 404 page missing');
    const large404 = page.locator('.site-error-artwork');
    verify(await large404.count() === 1 && await large404.isVisible(),
      'Large branded 404 SVG artwork missing');
    const artworkBounds = await large404.boundingBox();
    verify(artworkBounds && artworkBounds.width >= 280 && artworkBounds.height >= 100,
      '404 SVG artwork is too small for the desktop viewport');

    const errorLayout = await page.locator('.site-error-page').evaluate(section => {
      const content = section.querySelector('.site-error-content');
      if (!content) return null;
      const outer = section.getBoundingClientRect();
      const inner = content.getBoundingClientRect();
      return {
        horizontalGap: Math.abs((inner.left + inner.width / 2) - (outer.left + outer.width / 2)),
        verticalGap: Math.abs((inner.top + inner.height / 2) - (outer.top + outer.height / 2)),
        background: getComputedStyle(section).backgroundColor,
        cardBackground: getComputedStyle(content).backgroundColor,
        widthOverflow: inner.right > window.innerWidth + 2,
      };
    });
    verify(errorLayout && errorLayout.horizontalGap <= 3 && errorLayout.verticalGap <= 3,
      'Global 404 message is not centered in the page canvas');
    verify(errorLayout.background === 'rgba(0, 0, 0, 0)' &&
      errorLayout.cardBackground === 'rgba(0, 0, 0, 0)',
      'Global 404 page must have no tinted section or white card background');
    verify(!errorLayout.widthOverflow, '404 content overflows the viewport');
    await page.setViewportSize({ width: 390, height: 844 });
    await navigate(page, '/edumia-page-that-does-not-exist', 404);
    verify(await page.locator('.site-error-content').isVisible(),
      'Global 404 content missing on mobile');
    const mobileArtwork = await page.locator('.site-error-artwork').boundingBox();
    verify(mobileArtwork && mobileArtwork.width >= 260 && mobileArtwork.width <= 390,
      '404 SVG artwork must be large but fit on a phone');
    verify(await page.locator('#page-not-found-title').count() === 1,
      '404 title missing on mobile');

    const smallScreenOverflow = await page.evaluate(() =>
      document.documentElement.scrollWidth - document.documentElement.clientWidth);
    verify(smallScreenOverflow <= 2, 'Global 404 horizontal overflow on mobile');

    // Role-aware member navigation should remain accessible on a small screen.
    await page.setViewportSize({ width: 390, height: 844 });
    await navigate(page, '/account');
    const memberMenu = page.locator('.panel-header .menu-toggle');
    verify(await memberMenu.isVisible(), 'Student mobile sidebar toggle missing');
    await memberMenu.click();
    verify(await page.locator('#panel-navigation').isVisible(), 'Student mobile sidebar does not open');
    await page.keyboard.press('Escape');
    verify(!(await page.locator('#panel-navigation').isVisible()), 'Student sidebar does not close on Escape');

    await navigate(page, '/instructor');
    verify(await page.locator('.panel-header .menu-toggle').isVisible(), 'Instructor mobile menu missing');
    await page.locator('.panel-header .menu-toggle').click();
    verify(await page.locator('#panel-navigation').isVisible(), 'Instructor mobile sidebar does not open');
    await page.keyboard.press('Escape');

    await page.setViewportSize({ width: 1280, height: 900 });
    console.log('EDUMIA_MEMBER_AUDIENCE_AND_GLOBAL_404=PASS');

    // All admin modules retain their routes in the premium shell.
    await navigate(page, '/dashboard');
    verify(await page.locator('[data-admin-shell]').isVisible(), 'Admin shell was not rendered');
    verify(await page.locator('.admin-stats .admin-stat').count() === 4, 'Real admin summary tiles missing');
    verify(await page.locator('.admin-queue-links a').count() === 6, 'Operational queue links missing');
    verify(await page.locator('.admin-chart-column').count() === 7, 'Seven-day activity chart missing');
    verify(await page.locator('[data-admin-nav-link]').count() >= 15, 'Grouped admin navigation incomplete');
    verify(await page.locator('#admin-main').isVisible(), 'Admin content landmark missing');
    await page.locator('[data-admin-profile] summary').click();
    verify(await page.locator('[data-admin-profile]').getAttribute('open') !== null,
      'Administrator profile dropdown failed to open');
    await page.keyboard.press('Escape');
    verify(await page.locator('[data-admin-profile]').getAttribute('open') === null,
      'Escape did not close administrator dropdown');
    await page.locator('[data-admin-search]').fill('accounting');
    verify(await page.locator('[data-admin-nav-link]:not([hidden])').count() === 1,
      'Admin module search did not filter links');
    await page.locator('[data-admin-search]').fill('');

    await page.setViewportSize({ width: 390, height: 844 });
    const menu = page.locator('.panel-header .menu-toggle');
    verify(await menu.isVisible(), 'Mobile dashboard menu is not displayed');
    await menu.click();
    verify(await page.locator('#panel-navigation').isVisible(), 'Dashboard menu does not open');
    await page.keyboard.press('Escape');
    verify(!(await page.locator('#panel-navigation').isVisible()), 'Escape did not close dashboard menu');
    verify(await menu.evaluate(element => element === document.activeElement), 'Keyboard focus not restored');

    for (const module of adminModules) {
      await navigate(page, module.url);
      verify(await page.locator(module.container).isVisible(),
        'Freshly installed mobile admin module missing: ' + module.slug);
      const overflow = await page.evaluate(() =>
        document.documentElement.scrollWidth - document.documentElement.clientWidth);
      verify(overflow <= 2, 'Fresh install mobile overflow in ' + module.slug + ': ' + overflow);
      await page.screenshot({ path: path.join(evidence, 'installed-' + module.slug + '-mobile.png') });
    }

    console.log('EDUMIA_FRESH_MYSQL_INSTALL=PASS');
    console.log('EDUMIA_INSTALLER_CSRF_LOCK_ADMIN=PASS');
  } finally {
    await context.close();
    await browser.close();
  }
}

const adminModules = [
  { slug: 'media', url: '/admin/media', heading: 'Media library', container: '.admin-media-workspace' },
  { slug: 'settings', url: '/admin/settings/general', heading: 'General settings', container: '.admin-settings-workspace' },
  { slug: 'roles', url: '/admin/roles', heading: 'Roles & permissions', container: '.admin-roles-workspace' },
  { slug: 'instructors', url: '/admin/instructor-applications', heading: 'Instructor management', container: '.admin-instructor-workspace' },
  { slug: 'readiness', url: '/admin/release-readiness', heading: 'Release readiness', container: '.admin-readiness-workspace' },
];

const adminManagementRoutes = [
  '/admin/users',
  '/admin/courses',
  '/admin/categories',
  '/admin/reviews',
  '/admin/ebooks',
  '/admin/bootcamps',
  '/admin/commerce/orders',
  '/admin/commerce/accounting',
  '/admin/commerce/refund-reviews',
  '/admin/blogs',
  '/admin/knowledge-base',
  '/admin/forums',
  '/admin/tickets',
  '/admin/contacts',
  '/admin/reports/learning',
];

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

    // Browser automation verifies real administrator navigation and layout geometry.
    await navigate(page, '/login');
    await page.locator('input[name="email"]').fill(adminEmail);
    await page.locator('input[name="password"]').fill(adminPassword);
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    verify(new URL(page.url()).pathname === '/dashboard',
      scenario.name + ' administrator login failed');

    for (const module of adminModules) {
      await navigate(page, module.url);
      verify(await page.locator(module.container).isVisible(),
        scenario.name + ' cannot render ' + module.slug);
      verify(await page.getByRole('heading', { name: module.heading, exact: true }).isVisible(),
        scenario.name + ' missing ' + module.slug + ' heading');
      verify(await page.locator('.admin-topbar').isVisible(),
        scenario.name + ' missing admin topbar in ' + module.slug);
      const geometry = await page.evaluate(() => ({
        overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
        viewport: document.documentElement.clientWidth,
        main: document.querySelector('#admin-main')?.getBoundingClientRect().width,
      }));
      verify(geometry.overflow <= 2,
        scenario.name + ' ' + module.slug + ' overflows by ' + geometry.overflow + 'px');
      verify(geometry.main > 0 && geometry.main <= geometry.viewport,
        scenario.name + ' ' + module.slug + ' content extends beyond viewport');
      await page.screenshot({ path: path.join(evidence,
        'admin-' + module.slug + '-' + scenario.name + '.png') });
    }

    await navigate(page, '/admin/release-readiness');
    verify(await page.getByText('Customer release blocked').isVisible(),
      'Release approval must remain blocked in simulated browser acceptance');
    verify(await page.locator('[data-readiness-gate]').count() === 4,
      'External acceptance gates are not visible');
    verify(await page.getByText('NOT VERIFIED').count() >= 3,
      'Hosting, physical-device and commerce evidence must remain unverified');

    // The editor must be interactive, not a placeholder toolbar or static card.
    await navigate(page, '/admin/blogs/create');
    verify(await page.locator('[data-blog-editor]').isVisible(),
      'Premium blog composer missing from admin blog create');
    verify(await page.locator('[data-blog-copy-url] svg').count() === 1,
      'SVG copy-link control missing');
    verify(await page.getByRole('heading', { name: 'Featured Image' }).count() === 1,
      'Featured Image sidebar missing');
    await page.locator('[data-blog-title]').fill('Practical Study Techniques');
    verify(await page.locator('[data-blog-slug]').inputValue() === 'practical-study-techniques',
      'Article slug is not generated from its title');
    await page.locator('[data-blog-body]').fill('Learning with structure.');
    await page.locator('[data-blog-body]').selectText();
    await page.locator('[data-blog-format="bold"]').click();
    verify((await page.locator('[data-blog-body]').inputValue()).includes('**Learning with structure.**'),
      'Blog bold-formatting toolbar did not change article content');
    await page.locator('[data-blog-preview-button]').click();
    verify(await page.locator('[data-blog-preview]').isVisible(),
      'Safe article preview failed to render');
    verify(await page.locator('[data-blog-preview] strong').isVisible(),
      'Markdown preview did not format bold text');
    await page.locator('[data-blog-preview-button]').click();
    verify(await page.locator('[data-blog-body]').isVisible(),
      'Blog article editor did not return from preview');
    await page.locator('[data-blog-open-images]').click();
    verify(await page.locator('[data-blog-media-dialog]').isVisible(),
      'Blog media picker failed to open');
    await page.locator('[data-blog-close-images]').click();
    verify(!(await page.locator('[data-blog-media-dialog]').isVisible()),
      'Blog media picker failed to close');
    const blogOverflow = await page.evaluate(() =>
      document.documentElement.scrollWidth - document.documentElement.clientWidth);
    verify(blogOverflow <= 2, 'Premium blog composer overflows viewport');

    await navigate(page, '/admin/media');
    verify(await page.locator('input[name="file"][type="file"]').count() === 1,
      'Private media upload input missing');
    verify(await page.locator('form[action$="/admin/media"][enctype="multipart/form-data"]').count() === 1,
      'Private media upload form is missing');
    await page.locator('select[name="type"]').selectOption('document');
    await page.getByRole('button', { name: 'Apply filters' }).click();
    verify(new URL(page.url()).searchParams.get('type') === 'document',
      'Media filter failed to submit');

    await navigate(page, '/admin/settings/general');
    verify(await page.locator('form[action$="/admin/settings/general"] input[name="site_name"]').count() === 1,
      'General settings form target missing');
    verify(await page.locator('select[name="default_locale"]').count() === 1,
      'Locale selector missing');
    verify(await page.locator('input[name="allow_registration"][type="checkbox"]').count() === 1,
      'Registration setting missing');

    await navigate(page, '/admin/roles');
    verify(await page.locator('form[action$="/admin/roles"] input[name="slug"]').count() === 1,
      'Create staff role form missing');
    verify(await page.locator('fieldset.admin-role-checkboxes input[name="permissions[]"]').count() >= 1,
      'Delegatable permission controls missing');

    await navigate(page, '/admin/instructor-applications');
    await page.locator('select[name="status"]').selectOption('pending');
    await page.getByRole('button', { name: 'Apply filters' }).click();
    verify(new URL(page.url()).searchParams.get('status') === 'pending',
      'Instructor queue status filtering failed');

    await navigate(page, '/admin/media');
    const previousTheme = await page.locator('html').getAttribute('data-theme');
    await page.locator('.admin-topbar .theme-toggle').click();
    verify((await page.locator('html').getAttribute('data-theme')) !== previousTheme,
      'Admin theme toggle is not interactive');
    await page.reload({ waitUntil: 'networkidle' });
    verify((await page.locator('html').getAttribute('data-theme')) !== previousTheme,
      'Admin theme choice was not saved');

    if (mobile) {
      const open = page.locator('[data-admin-open]');
      verify(await open.isVisible(), scenario.name + ' mobile menu button missing');
      await open.click();
      verify(await page.locator('[data-admin-sidebar]').isVisible(),
        scenario.name + ' mobile admin sidebar did not open');
      // The backdrop fills the viewport; click the exposed strip to the right of the sidebar.
      await page.locator('[data-admin-backdrop]').click({
        position: { x: scenario.viewport.width - 12, y: Math.round(scenario.viewport.height / 2) },
      });
      verify(!(await page.locator('[data-admin-sidebar]').isVisible()),
        scenario.name + ' mobile admin sidebar did not close');
    } else {
      verify(await page.locator('[data-admin-sidebar]').isVisible(),
        scenario.name + ' desktop sidebar is not visible');
    }

    verify(errors.length === 0, 'Browser console or asset errors: ' + errors.join(' | '));

    console.log('EDUMIA_BROWSER=' + scenario.name + ' PASS');
  } finally {
    await context.close();
    await browser.close();
  }
}

try {
  await install();
  if (process.env.EDUMIA_QA_MODE === 'installation-only') {
    console.log('EDUMIA_ARCHIVE_CUSTOMER_INSTALL=PASS');
  } else {
    for (const scenario of scenarios) await browse(scenario);
    console.log('EDUMIA_ADMIN_RESPONSIVE_BROWSER_MATRIX=PASS');
  }
  console.log('EDUMIA_BROWSER_ACCEPTANCE=PASS');
  console.log('EDUMIA_QA_ASSERTIONS=' + assertions);
} catch (error) {
  console.error('EDUMIA_BROWSER_ACCEPTANCE=FAIL');
  console.error(error.stack || String(error));
  process.exitCode = 1;
}
