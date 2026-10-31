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
    const mobile = scenario.viewport.width <= 700;
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

    const horizontalOverflow = await page.evaluate(() =>
      document.documentElement.scrollWidth - document.documentElement.clientWidth);
    verify(horizontalOverflow <= 2, scenario.name + ' has horizontal overflow (' + horizontalOverflow + 'px)');
    await page.screenshot({ path: path.join(evidence, scenario.name + '.png') });

    await navigate(page, '/courses?q=sample');
    await page.locator('#language-select').selectOption({ label: 'Français' });
    await page.waitForURL(/\/fr\/courses\?q=sample/);
    verify(await page.locator('html').getAttribute('lang') === 'fr', 'French URL language not applied');
    verify((await page.locator('h1').first().innerText()).length > 0, 'Localized catalogue heading not rendered');
    await navigate(page, '/de/contact');
    verify(await page.getByRole('button', { name: 'Nachricht senden' }).isVisible(),
      'Translated contact form is unavailable');

    await navigate(page, '/');
    await page.locator('.theme-toggle').first().click();
    verify(await page.locator('html').getAttribute('data-theme') === 'dark', 'Theme toggle failed');
    await page.reload({ waitUntil: 'networkidle' });
    verify(await page.locator('html').getAttribute('data-theme') === 'dark', 'Theme choice not retained');
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
