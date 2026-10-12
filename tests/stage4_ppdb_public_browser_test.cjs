const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('../storage/testing/stage3-mariadb/browser-tools/node_modules/playwright-core');

(async () => {
  const root = fs.realpathSync(process.argv[2]);
  const privateRoot = fs.realpathSync(path.join(__dirname, '../storage/testing/stage3-mariadb'));
  if (!root.startsWith(privateRoot + path.sep) || fs.lstatSync(process.argv[2]).isSymbolicLink()) {
    throw new Error('Unverified browser testing snapshot');
  }
  const manifest = JSON.parse(fs.readFileSync(path.join(root, 'manifest.json')));
  if (manifest.url !== 'http://127.0.0.1:8088' || fs.realpathSync(manifest.snapshot) !== root) {
    throw new Error('Unverified browser testing URL');
  }
  const expected = JSON.parse(fs.readFileSync(path.join(root, 'ppdb-public-expected.json')));
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  const checks = [];
  const check = (ok, label) => checks.push({ passed: Boolean(ok), label });
  try {
    for (const width of [320, 360, 390, 768, 1440]) {
      const context = await browser.newContext({ viewport: { width, height: 900 } });
      // Do not follow or fetch third-party URLs during a local-only audit.
      await context.route('**/*', route => new URL(route.request().url()).origin === manifest.url ? route.continue() : route.abort());
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      const response = await page.goto(manifest.url + '/ppdb', { waitUntil: 'networkidle' });
      check(response.status() === 200, `${width}: public route 200`);
      const state = await page.evaluate(() => {
        const rect = node => { const r = node.getBoundingClientRect(); return { x:r.x, y:r.y, width:r.width, height:r.height, bottom:r.bottom }; };
        return {
          text: document.body.innerText,
          headers: document.querySelectorAll('header.site-header').length,
          footers: document.querySelectorAll('footer.site-footer').length,
          mains: document.querySelectorAll('main').length,
          header: rect(document.querySelector('header.site-header')),
          main: rect(document.querySelector('main')),
          footer: rect(document.querySelector('footer.site-footer')),
          scrollWidth: document.documentElement.scrollWidth,
          forms: [...document.forms].map(f => ({ method:f.method, action:f.action, role:f.getAttribute('role') })),
          upload: document.querySelectorAll('input[type=file]').length,
          links: [...document.querySelectorAll('a[href],form[action]')].map(n => n.href || n.action),
          buttons: [...document.querySelectorAll('a,button,input[type=submit]')].map(n => n.textContent || n.value),
          images: [...document.images].map(i => ({ loaded:i.complete && i.naturalWidth > 0, src:i.getAttribute('src') })),
          columns: getComputedStyle(document.querySelector('.ppdb-criteria-grid')).gridTemplateColumns,
          cssLoaded: [...document.styleSheets].some(s => s.href && s.href.includes('/public/assets/css/style.css')),
          outside: [...document.querySelectorAll('main section, main article, main h1, main h2, footer .container, header .container')]
            .filter(n => { const r=n.getBoundingClientRect(); return r.width>0 && (r.left < -1 || r.right > innerWidth+1); }).map(n => n.className),
        };
      });
      check(state.headers === 1 && state.footers === 1 && state.mains === 1, `${width}: shared header/main/footer present once`);
      check(state.cssLoaded && state.images.every(i => i.loaded), `${width}: original stylesheet/logo assets load`);
      check(state.scrollWidth <= width + 1 && state.outside.length === 0, `${width}: no horizontal structural overflow`);
      check(state.main.y >= state.header.bottom - 1 && state.footer.y >= state.main.bottom - 1, `${width}: header/main/footer ordered without overlap`);
      check(state.forms.every(f => f.method === 'get' && f.role === 'search') && state.upload === 0, `${width}: only normal header search, no registration/upload form`);
      check(!state.links.some(u => /ppdb_applications|ppdb_export|ppdb_download|ppdb_receipt|ppdb\/bukti|ppdb\/(daftar|register|upload)/i.test(u)), `${width}: no legacy link or action`);
      check(!state.buttons.some(t => /daftar sekarang|kirim pendaftaran|daftar online/i.test(t)), `${width}: no local registration command`);
      const strings = [...expected.ppdbSteps, ...expected.ppdbRequirements, ...expected.ppdbNotes,
        ...expected.ppdbCriteria.flatMap(i => [i.title, i.desc]), ...expected.ppdbInformation.flatMap(i => [i.title, i.desc]),
        expected.schoolProfile.name, expected.schoolProfile.phone, expected.schoolProfile.email];
      check(strings.every(t => state.text.includes(t)), `${width}: existing PPDB content and school contact preserved`);
      check(state.text.includes('Dinas Pendidikan') && state.text.includes('Dokumen dan pengumuman resmi'), `${width}: informative responsibility and document section remain`);
      if (width <= 920) {
        check(state.columns.trim().split(/\s+/).length === 1, `${width}: criteria stack into one column`);
        const toggle = page.locator('.menu-toggle');
        await toggle.click();
        check(await toggle.getAttribute('aria-expanded') === 'true' && await page.locator('#site-nav').isVisible(), `${width}: mobile menu opens`);
        check(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${width}: expanded menu does not overflow`);
        await page.screenshot({ path:path.join(root, `ppdb-${width}-menu.png`) });
        await toggle.click();
        check(await toggle.getAttribute('aria-expanded') === 'false' && !await page.locator('#site-nav').isVisible(), `${width}: mobile menu closes`);
      }
      check(errors.length === 0, `${width}: no JavaScript error`);
      await page.screenshot({ path:path.join(root, `ppdb-${width}-full.png`), fullPage:true });
      await page.screenshot({ path:path.join(root, `ppdb-${width}-top.png`) });
      await page.locator('footer').scrollIntoViewIfNeeded();
      await page.screenshot({ path:path.join(root, `ppdb-${width}-footer.png`) });
      await context.close();
    }
  } finally {
    await browser.close();
  }
  const result = { checks:checks.length, failures:checks.filter(c => !c.passed).length, results:checks };
  fs.writeFileSync(path.join(root, 'ppdb-public-browser-result.json'), JSON.stringify(result, null, 2));
  console.log(JSON.stringify({ checks:result.checks, failures:result.failures, failed:checks.filter(c => !c.passed) }));
  process.exitCode = result.failures ? 1 : 0;
})().catch(error => { console.error(error.message); process.exitCode = 1; });
