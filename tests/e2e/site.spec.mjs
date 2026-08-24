import { test, expect } from '@playwright/test';

test('planetary scenes animate instead of freezing', async ({ page }) => {
  await page.goto('/physics');
  const scene = page.locator('[data-planetary]');
  await expect(scene).toHaveAttribute('data-animation-frame', /\d+/);
  const first = Number(await scene.getAttribute('data-animation-frame'));
  await expect.poll(async () => Number(await scene.getAttribute('data-animation-frame'))).toBeGreaterThan(first);
  await expect(page.locator('canvas')).toBeVisible();
});

test('physics label anchor dots remain tied to projected satellites', async ({ page }) => {
  await page.goto('/physics');
  const labels = page.locator('[data-satellite]');
  await expect(labels).toHaveCount(4);
  await expect(page.locator('[data-planetary="physics"]')).toHaveAttribute('data-spacecraft-count', '4');
  expect(await labels.evaluateAll(nodes => nodes.map(node => node.dataset.spacecraft))).toEqual(['VINASAT-1', 'VINASAT-2', 'VNREDSat-1', 'PicoDragon']);
  await expect(page.locator('[data-planetary="physics"]')).toHaveAttribute('data-earth-map', 'loaded');
  await expect(labels.first()).toHaveAttribute('data-anchor-x', /\d/);
  await expect(labels.first()).toHaveAttribute('style', /--label-offset-x/);
  expect(await labels.evaluateAll(nodes => nodes.some(node => node.classList.contains('isLeft')))).toBe(false);
  const labelClearances = await labels.evaluateAll(nodes => nodes.map(node => Math.hypot(Number(node.dataset.labelX)-Number(node.dataset.anchorX),Number(node.dataset.labelY)-Number(node.dataset.anchorY))));
  labelClearances.forEach(distance => expect(distance).toBeGreaterThan(40));
  const protectedSatelliteGaps = await labels.evaluateAll(nodes => nodes.map(node => {
    const marker=node.querySelector('i').getBoundingClientRect(),text=node.querySelector('span').getBoundingClientRect();
    const x=marker.left+marker.width/2,y=marker.top+marker.height/2;
    return Math.hypot(Math.max(text.left-x,0,x-text.right),Math.max(text.top-y,0,y-text.bottom));
  }));
  protectedSatelliteGaps.forEach(distance => expect(distance).toBeGreaterThan(20));
  const offsets = await labels.evaluateAll((nodes) => nodes.map((label) => {
    const host = label.closest('[data-planetary]').getBoundingClientRect();
    const marker = label.querySelector('i').getBoundingClientRect();
    return {
      x: Math.abs(marker.left + marker.width / 2 - host.left - Number(label.dataset.anchorX)),
      y: Math.abs(marker.top + marker.height / 2 - host.top - Number(label.dataset.anchorY)),
    };
  }));
  offsets.forEach(({ x, y }) => { expect(x).toBeLessThan(1.5); expect(y).toBeLessThan(1.5); });
});

test('hub renders eight planets with Earth as the Physics space', async ({ page }) => {
  await page.goto('/');
  const planets = page.locator('[data-hub-planet]');
  await expect(planets).toHaveCount(4);
  await expect(page.locator('[data-planetary="hub"]')).toHaveAttribute('data-planet-count', '8');
  const spaces = page.getByRole('navigation', { name: 'Các không gian học tập' });
  await expect(spaces.getByRole('link', { name: 'Vật lý', exact: true })).toHaveAttribute('href', '/physics');
  await expect(spaces.getByRole('link', { name: 'Vật lý', exact: true })).toHaveAttribute('data-hub-planet', '2');
  await expect(spaces.getByRole('link', { name: 'Toán học', exact: true })).toHaveAttribute('href', '/math');
  await expect(spaces.getByRole('link', { name: 'Tin học', exact: true })).toHaveAttribute('href', '/it');
  await expect(spaces.getByRole('link', { name: 'Hóa học', exact: true })).toHaveAttribute('href', '/chemistry');
  await expect(planets.first()).toHaveAttribute('style', /--label-offset-x/);
  expect(await planets.evaluateAll(links => links.some(link => link.classList.contains('isLeft')))).toBe(false);
  const planetLabelClearances = await planets.evaluateAll(nodes => nodes.map(node => Math.hypot(Number(node.dataset.labelX)-Number(node.dataset.anchorX),Number(node.dataset.labelY)-Number(node.dataset.anchorY))));
  planetLabelClearances.forEach(distance => expect(distance).toBeGreaterThan(30));
  const protectedPlanetGaps = await planets.evaluateAll(nodes => nodes.map(node => {
    const marker=node.querySelector('i').getBoundingClientRect(),text=node.querySelector('span').getBoundingClientRect();
    const x=marker.left+marker.width/2,y=marker.top+marker.height/2;
    return Math.hypot(Math.max(text.left-x,0,x-text.right),Math.max(text.top-y,0,y-text.bottom));
  }));
  protectedPlanetGaps.forEach(distance => expect(distance).toBeGreaterThan(12));
  const planetTargets = await planets.evaluateAll(links => links.map(link => {
    const marker = link.querySelector('i').getBoundingClientRect();
    return document.elementFromPoint(marker.left + marker.width / 2, marker.top + marker.height / 2)?.closest('a')?.getAttribute('href');
  }));
  expect(planetTargets).toEqual(['/math', '/physics', '/it', '/chemistry']);
  for (const route of ['/math', '/it', '/chemistry']) {
    const response = await page.goto(route);
    expect(response.status()).toBe(200);
    await expect(page.getByText('Chưa mở')).toBeVisible();
  }
});

test('legacy activity reel moves automatically without controls', async ({ page }) => {
  await page.goto('/');
  const showcase = page.locator('[data-showcase]');
  await expect(showcase).toBeVisible();
  await expect(showcase.locator('img')).toHaveCount(20);
  await expect(showcase.locator('figcaption, button')).toHaveCount(0);
  const track = showcase.locator('.showcaseTrack');
  const firstTransform = await track.evaluate((node) => getComputedStyle(node).transform);
  await page.waitForTimeout(250);
  expect(await track.evaluate((node) => getComputedStyle(node).transform)).not.toBe(firstTransform);
});

test('activity reel leads to the personal introduction', async ({ page }) => {
  await page.goto('/');
  await page.locator('.aboutLink').click();
  await expect(page).toHaveURL('/about');
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Chào, mình là Duy.');
  await expect(page.getByText('DUY / DUY247', { exact: true })).toBeVisible();
  await expect(page.getByRole('link', { name: /GitHub \/ Duy247/i })).toHaveAttribute('href', 'https://github.com/Duy247/physx-cnh');
});

test('field cards orbit a left-side pivot as the page scrolls', async ({ page }) => {
  await page.goto('/');
  const orbit = page.locator('[data-field-orbit]');
  const cards = orbit.locator('[data-field-card]');
  await expect(cards).toHaveCount(4);
  await orbit.evaluate((node) => scrollTo(0, node.offsetTop));
  await expect(orbit).toHaveAttribute('data-field-index', '0');
  const firstTransform = await cards.first().evaluate((node) => node.style.transform);
  await orbit.evaluate((node) => scrollTo(0, node.offsetTop + (node.offsetHeight - innerHeight) / 3));
  await expect(orbit).toHaveAttribute('data-field-index', '1');
  expect(Math.abs(await orbit.locator('.fieldStage').evaluate((node) => node.getBoundingClientRect().top))).toBeLessThan(2);
  expect(await cards.first().evaluate((node) => node.style.transform)).not.toBe(firstTransform);
});

test('orbit link scopes books and removes redundant kind control', async ({ page }) => {
  await page.goto('/physics');
  await page.getByRole('link', { name: /Sách chuyên Vật lý/i }).evaluate((link) => link.click());
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
  for (const path of ['/', '/physics', '/library', '/guides/roadmap', '/guides/research']) {
    await page.goto(path);
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(500);
  }
  expect(missing).toEqual([]);
});

test('standard PDF.js viewer reaches late pages', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop', 'One PDF engine pass is sufficient.');
  test.skip(Boolean(process.env.PHYSX_SKIP_PDF_TEST), 'PDF blobs are intentionally absent from sparse CI checkout.');
  await page.goto('/document/problems-in-general-physics');
  await expect(page).toHaveURL(/\/assets\/v2\/pdfjs\/web\/viewer\.html/);
  await expect(page.locator('#numPages')).toContainText('402', { timeout: 20_000 });
  await page.waitForFunction(() => window.PDFViewerApplication?.pdfViewer?.pagesCount === 402);
  await page.evaluate(() => { window.PDFViewerApplication.pdfViewer.currentPageNumber = 400; });
  await expect(page.locator('.page[data-page-number="400"] canvas')).toBeVisible({ timeout: 20_000 });
});
