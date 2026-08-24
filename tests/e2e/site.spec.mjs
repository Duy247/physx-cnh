import { test, expect } from '@playwright/test';

test('planetary scenes animate instead of freezing', async ({ page }) => {
  await page.goto('/physics');
  const scene = page.locator('[data-planetary]');
  await expect(scene).toHaveAttribute('data-animation-frame', /\d+/);
  const first = Number(await scene.getAttribute('data-animation-frame'));
  await expect.poll(async () => Number(await scene.getAttribute('data-animation-frame'))).toBeGreaterThan(first);
  await expect(page.locator('canvas')).toBeVisible();
});

test('orbit link scopes books and removes redundant kind control', async ({ page }) => {
  await page.goto('/physics');
  await page.getByRole('link', { name: /Sách chuyên Vật lý/i }).click({ force: true });
  await expect(page).toHaveURL(/\/library\?kind=book&orbit=1/);
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Sách');
  await expect(page.locator('[data-result-count]')).toContainText('89');
  await expect(page.locator('[data-kind]')).toHaveCount(0);
});

test('library never fetches PDFs to render cards and preserves Back state', async ({ page }) => {
  const pdfRequests = [];
  page.on('request', request => {
    if (/\.pdf(?:$|\?)/i.test(request.url())) pdfRequests.push(request.url());
  });
  await page.goto('/library?kind=book&orbit=1');
  const search = page.getByRole('searchbox');
  await search.fill('Irodov');
  await expect(page).toHaveURL(/q=Irodov/);
  await expect(page.locator('[data-result-count]')).toContainText('4');
  expect(pdfRequests).toEqual([]);

  await page.getByRole('link', { name: /Problems in General Physics/i }).first().click();
  await expect(page).toHaveURL(/\/assets\/v2\/pdfjs\/web\/viewer\.html/);
  await page.goBack();
  await expect(search).toHaveValue('Irodov');
  await expect(page).toHaveURL(/kind=book.*orbit=1.*q=Irodov/);
  await expect(page.locator('[data-result-count]')).toContainText('4');
});

test('relay expands with Vietnamese copy and a stable session source', async ({ page }) => {
  await page.goto('/physics');
  const relay = page.locator('[data-relay]');
  const source = await relay.locator('[data-relay-source]').textContent();
  await relay.locator('summary').click();
  await expect(relay).toHaveAttribute('open', '');
  await expect(relay).toContainText('Tài liệu mới vào quỹ đạo');
  await page.reload();
  await expect(relay.locator('[data-relay-source]')).toHaveText(source.trim());
});

test('mobile menu closes after navigation', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'mobile', 'Mobile-only navigation behavior.');
  await page.goto('/physics');
  const menu = page.locator('.mobile-menu');
  await menu.locator('summary').click();
  await expect(menu.locator('nav')).toBeVisible();
  await page.getByRole('link', { name: 'Thư viện', exact: true }).last().click();
  await expect(page).toHaveURL(/\/library$/);
  await expect(page.locator('.mobile-menu nav')).not.toBeVisible();
});

test('locally bundled fonts, covers, scripts and graphs have no missing assets', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop', 'One asset pass is sufficient.');
  const missing = [];
  page.on('response', response => {
    if (response.status() === 404) missing.push(response.url());
  });
  for (const path of ['/physics', '/library', '/guides/roadmap', '/guides/research']) {
    await page.goto(path);
    await page.waitForLoadState('networkidle');
  }
  expect(missing).toEqual([]);
});

test('standard PDF.js viewer reaches late pages', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop', 'One PDF engine pass is sufficient.');
  test.skip(Boolean(process.env.PHYSX_SKIP_PDF_TEST), 'PDF blobs are intentionally absent from sparse CI checkout.');
  await page.goto('/document/problems-in-general-physics');
  await expect(page).toHaveURL(/\/assets\/v2\/pdfjs\/web\/viewer\.html/);
  await expect(page.locator('#numPages')).toContainText('402', { timeout: 20_000 });
  await page.locator('#pageNumber').fill('400');
  await page.locator('#pageNumber').press('Enter');
  await expect(page.locator('.page[data-page-number="400"] canvas')).toBeVisible({ timeout: 20_000 });
});
