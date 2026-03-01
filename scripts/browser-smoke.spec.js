import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { test } from '@playwright/test';

const baseUrl = process.env.SMOKE_BASE_URL || 'http://127.0.0.1:8099';
const locale = process.env.SMOKE_LOCALE || 'bs';
const email = process.env.SMOKE_EMAIL;
const password = process.env.SMOKE_PASSWORD;

if (!email || !password) {
  throw new Error('Missing SMOKE_EMAIL or SMOKE_PASSWORD environment variables.');
}

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const outputPath = path.resolve(__dirname, '../storage/logs/browser-smoke-results.json');

test('Browser smoke check', async ({ page }) => {
  const results = [];
  const diagnostics = [];
  const apiTrace = [];

  page.on('console', (msg) => {
    if (msg.type() === 'error') {
      diagnostics.push(`[console.error] ${msg.text()}`);
    }
  });

  page.on('pageerror', (error) => {
    diagnostics.push(`[pageerror] ${error.message}`);
  });

  page.on('response', (response) => {
    const url = response.url();
    if (url.includes('/api/v1/')) {
      apiTrace.push(`${response.status()} ${url}`);
    }
  });

  async function runStep(name, fn) {
    const startedAt = new Date().toISOString();
    try {
      const details = await fn();
      const result = {
        step: name,
        status: 'PASS',
        startedAt,
        finishedAt: new Date().toISOString(),
        details: details || null,
      };
      results.push(result);
      console.log(`[PASS] ${name}${details ? ` :: ${details}` : ''}`);
      return result;
    } catch (error) {
      const result = {
        step: name,
        status: 'FAIL',
        startedAt,
        finishedAt: new Date().toISOString(),
        details: error instanceof Error ? error.message : String(error),
        diagnostics: diagnostics.slice(-8),
        apiTrace: apiTrace.slice(-20),
      };
      results.push(result);
      console.log(`[FAIL] ${name} :: ${result.details}`);
      throw error;
    } finally {
      fs.mkdirSync(path.dirname(outputPath), { recursive: true });
      fs.writeFileSync(outputPath, JSON.stringify(results, null, 2), 'utf8');
    }
  }

  await runStep('login', async () => {
    await page.goto(`${baseUrl}/${locale}/login`, { waitUntil: 'domcontentloaded' });
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.locator('#loginSubmit').click();
    await page.waitForURL(`**/${locale}/dashboard`, { timeout: 20000 });
    return `URL=${page.url()}`;
  });

  let editUrl = null;
  let memorialName = null;

  await runStep('dashboard', async () => {
    await page.waitForSelector('#dashboardGrid', { timeout: 20000 });
    const createLink = page.locator(`a[href*='/${locale}/create']`).first();
    if ((await createLink.count()) === 0) {
      throw new Error('Create memorial link not found on dashboard.');
    }
    return 'dashboard loaded with create action';
  });

  await runStep('create memorial', async () => {
    await page.locator(`a[href*='/${locale}/create']`).first().click();
    await page.waitForURL(`**/${locale}/create`, { timeout: 15000 });

    const uniqueSuffix = Date.now();
    memorialName = `Smoke${uniqueSuffix}`;

    await page.locator('#first_name').fill(memorialName);
    await page.locator('#last_name').fill('Test');
    await page.locator('#birth_date').fill('1950-01-01');
    await page.locator('#death_date').fill('2020-01-01');
    await page.locator('#birth_place').fill('Sarajevo');
    await page.locator('#death_place').fill('Sarajevo');
    await page.locator('#biography').fill(`Automated smoke biography ${uniqueSuffix}`);
    await page.locator('#is_public').check();
    await page.locator('#memorialFormSubmit').click();
    await page.waitForURL(`**/${locale}/dashboard`, { timeout: 20000 });

    await page.waitForTimeout(1200);
    const editLink = page.locator(`#dashboardGrid a[href*='/${locale}/edit/']`).first();
    if ((await editLink.count()) === 0) {
      throw new Error('Edit link missing after memorial creation.');
    }
    editUrl = await editLink.getAttribute('href');
    if (!editUrl) {
      throw new Error('Edit URL could not be resolved.');
    }

    return `created memorial=${memorialName} editUrl=${editUrl}`;
  });

  await runStep('edit memorial', async () => {
    const editTargetUrl = editUrl.startsWith('http') ? editUrl : `${baseUrl}${editUrl}`;
    await page.goto(editTargetUrl, { waitUntil: 'domcontentloaded' });
    await page.waitForURL(`**/${locale}/edit/**`, { timeout: 10000 });
    await page.locator('#biography').fill(`Updated biography ${Date.now()}`);
    await page.locator('#memorialFormSubmit').click();
    await page.waitForURL(`**/${locale}/dashboard`, { timeout: 20000 });
    return `edited via ${editTargetUrl}`;
  });

  await runStep('admin tabs', async () => {
    const tokenBefore = await page.evaluate(() => localStorage.getItem('auth_token') || '');
    await page.goto(`${baseUrl}/${locale}/admin`, { waitUntil: 'domcontentloaded' });
    if (page.url().includes(`/${locale}/login`)) {
      const tokenAfter = await page.evaluate(() => localStorage.getItem('auth_token') || '');
      throw new Error(`Redirected to login immediately. tokenBeforeLen=${tokenBefore.length} tokenAfterLen=${tokenAfter.length}`);
    }
    await page.waitForSelector('#adminUser', { timeout: 20000 });

    const tabTargets = ['settings', 'users', 'memorials', 'hero', 'seo'];
    for (const tab of tabTargets) {
      await page.locator(`.admin-tab-trigger[data-tab-target='${tab}']`).click();
      const hiddenClass = await page.locator(`#adminTab-${tab}`).getAttribute('class');
      if ((hiddenClass || '').includes('hidden')) {
        throw new Error(`Tab ${tab} did not open.`);
      }
    }

    await page.locator('#adminSeoRunBtn').click();
    await page.waitForSelector('#adminSeoSummary', { state: 'visible', timeout: 30000 });
    return 'all admin tabs opened and SEO check executed';
  });

  await runStep('language switch', async () => {
    await page.locator('header .relative.hidden.md\\:block button').first().click();
    const targetLink = page.locator(`header .relative.hidden.md\\:block a[href*='/${locale}/admin']`).first();
    const srLink = page.locator("header .relative.hidden.md\\:block a[href*='/sr/']").first();
    const linkToClick = (await srLink.count()) > 0 ? srLink : targetLink;
    if ((await linkToClick.count()) === 0) {
      throw new Error('Language switch link not found.');
    }
    await linkToClick.click();
    await page.waitForURL('**/sr/**', { timeout: 15000 });
    if (!page.url().includes('/sr/')) {
      throw new Error(`Expected sr locale URL, got ${page.url()}`);
    }
    return `URL=${page.url()}`;
  });
});
