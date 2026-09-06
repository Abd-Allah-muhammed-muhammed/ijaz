/**
 * Withdraw Index toolbar verification at 1440px (and mobile regression at 375px).
 * Run: node scripts/verify-withdraw-toolbar-1440.mjs
 */
import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const outDir = join(__dirname, '../storage/app/mobile-verify-375');
mkdirSync(outDir, { recursive: true });

const html = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    .min-w-0 { min-width: 0 !important; }
    body { margin: 0; background: #f5f8fa; padding: 24px; }
    .form-control { height: calc(1.5em + 1.5rem + 2px); }
  </style>
</head>
<body>
  <div id="toolbar" class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-3 mb-4">
    <div class="flex-grow-1 min-w-0">
      <input id="search" class="form-control w-100" placeholder="Search" />
    </div>
    <div class="align-self-start flex-shrink-0">
      <button id="withdraw-btn" type="button" class="btn btn-primary me-0">Withdraw</button>
    </div>
  </div>
</body>
</html>`;

async function measure(page) {
  return page.evaluate(() => {
    const toolbar = document.getElementById('toolbar');
    const search = document.getElementById('search');
    const btn = document.getElementById('withdraw-btn');
    const searchBox = search.getBoundingClientRect();
    const btnBox = btn.getBoundingClientRect();
    const styles = getComputedStyle(btn);

    return {
      viewportWidth: window.innerWidth,
      searchTop: searchBox.top,
      searchBottom: searchBox.bottom,
      searchHeight: searchBox.height,
      btnTop: btnBox.top,
      btnBottom: btnBox.bottom,
      btnHeight: btnBox.height,
      btnWidth: btnBox.width,
      btnLeft: btnBox.left,
      searchRight: searchBox.right,
      sameRow: Math.abs(searchBox.top - btnBox.top) < 12,
      btnAfterSearch: btnBox.left >= searchBox.right - 1,
      btnBackground: styles.backgroundColor,
      btnNotFullWidth: btnBox.width < window.innerWidth * 0.5,
      verticalCentersClose:
        Math.abs(
          (searchBox.top + searchBox.height / 2) - (btnBox.top + btnBox.height / 2),
        ) < 10,
    };
  });
}

const browser = await chromium.launch();

const desktop = await browser.newPage({ viewport: { width: 1440, height: 900 } });
await desktop.setContent(html, { waitUntil: 'networkidle' });
const desktopMetrics = await measure(desktop);
await desktop.screenshot({
  path: join(outDir, 'withdraw-toolbar-1440.png'),
  fullPage: true,
});
await desktop.close();

const mobile = await browser.newPage({ viewport: { width: 375, height: 812 } });
await mobile.setContent(html, { waitUntil: 'networkidle' });
const mobileMetrics = await measure(mobile);
await mobile.screenshot({
  path: join(outDir, 'withdraw-toolbar-375.png'),
  fullPage: true,
});
await mobile.close();

await browser.close();

const desktopOk =
  desktopMetrics.sameRow &&
  desktopMetrics.btnAfterSearch &&
  desktopMetrics.btnNotFullWidth &&
  desktopMetrics.verticalCentersClose &&
  desktopMetrics.btnBackground !== 'rgba(0, 0, 0, 0)' &&
  desktopMetrics.btnBackground !== 'transparent';

const mobileOk =
  !mobileMetrics.sameRow &&
  mobileMetrics.btnNotFullWidth &&
  mobileMetrics.btnTop > mobileMetrics.searchBottom - 1;

const result = {
  outDir,
  desktopMetrics,
  mobileMetrics,
  desktopOk,
  mobileOk,
  ok: desktopOk && mobileOk,
};

writeFileSync(join(outDir, 'toolbar-1440-metrics.json'), JSON.stringify(result, null, 2));
console.log(JSON.stringify(result, null, 2));
process.exit(result.ok ? 0 : 1);
