const { chromium } = require('../storage/testing/stage3-mariadb/browser-tools/node_modules/playwright-core');

(async () => {
  const port = Number(process.argv[2]);
  if (!Number.isInteger(port) || port < 1024 || port > 65535) throw new Error('Invalid local test port');
  const origin = `http://127.0.0.1:${port}`;
  const crossOrigin = `http://localhost:${port}`;
  let checks = 0;
  const check = (ok, label) => { checks++; if (!ok) throw new Error(label); };
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  try {
    for (const width of [360, 1440]) {
      const context = await browser.newContext({ viewport: { width, height: 900 } });
      await context.route('**/*', route => [origin, crossOrigin].includes(new URL(route.request().url()).origin) ? route.continue() : route.abort());
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      const response = await page.goto(origin + '/header-control.php', { waitUntil: 'networkidle' });
      check(response.status() === 200, 'public template control 200');
      check(await page.evaluate(() => window.headerInlineWorked === true), 'inline script works');
      check(await page.locator('#header-test-style').evaluate(node => getComputedStyle(node).color === 'rgb(1, 2, 3)'), 'inline style works');
      check(await page.locator('#header-test-button').evaluate(node => getComputedStyle(node).backgroundColor === 'rgb(4, 5, 6)'), 'style attribute works');
      await page.locator('#header-test-button').click();
      check(await page.evaluate(() => window.headerEventWorked === true), 'inline event handler works');
      if (width === 360) {
        await page.locator('.menu-toggle').click();
        check(await page.locator('.menu-toggle').getAttribute('aria-expanded') === 'true', 'existing mobile menu script works');
      } else {
        check(await page.locator('.site-nav').isVisible() && !(await page.locator('.menu-toggle').isVisible()), 'existing desktop navigation visible without hidden toggle');
      }
      const login = await page.goto(origin + '/admin/login.php', { waitUntil: 'networkidle' });
      check(login.status() === 200, 'real admin login 200');
      check(await page.locator('input[name=csrf_token]').count() === 2, 'existing logout/login CSRF fields retained');
      check(errors.length === 0, 'no page script errors');
      await page.goto(origin + '/frame-control.php', { waitUntil: 'networkidle' });
      check(await page.frameLocator('iframe').locator('#header-test-style').count() === 1, 'same-origin framing allowed');
      await page.goto(crossOrigin + '/frame-control.php', { waitUntil: 'networkidle' });
      check(!page.frames().some(frame => frame.url() === origin + '/header-control.php'), 'cross-origin frame blocked');
      await context.close();
    }
    console.log(`PASS: ${checks} browser security-header compatibility checks (mobile/desktop).`);
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error.message); process.exitCode = 1; });
