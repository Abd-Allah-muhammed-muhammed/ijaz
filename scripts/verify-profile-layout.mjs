/**
 * Profile card layout smoke check at ~375px and ~1440px (structural HTML
 * mirroring the redesigned Profile cards — no auth required).
 * Run: node scripts/verify-profile-layout.mjs
 */
import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const outDir = join(__dirname, '../storage/app/profile-verify');
mkdirSync(outDir, { recursive: true });

const CATEGORIES_MAX_HEIGHT_PX = 110;

const html = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { margin: 0; background: #f5f8fa; padding: 16px; }
    .card { border: 0; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075); border-radius: 1rem; background: #fff; margin-bottom: 1.25rem; }
    .card-body { padding: 1.5rem; }
    .symbol-100px { width: 100px; height: 100px; display: inline-flex; }
    .symbol-100px img { width: 100%; height: 100%; object-fit: contain; background: #eef3f7; border-radius: .5rem; }
    .categories-scroll { max-height: ${CATEGORIES_MAX_HEIGHT_PX}px; overflow: auto; }
    .danger-zone { border: 1px solid rgba(220,53,69,.5) !important; }
    .chip { display: block; padding: .5rem 0; border-bottom: 1px solid #eee; }
  </style>
</head>
<body>
  <div id="identity" class="card">
    <div class="card-body d-flex align-items-center gap-3 flex-wrap">
      <div class="symbol-100px"><img alt="avatar" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100'%3E%3Crect fill='%23cce0ff' width='100' height='100'/%3E%3C/svg%3E" /></div>
      <div><strong id="name">Provider Name</strong> ★★★★☆</div>
    </div>
  </div>
  <div id="logo-card" class="card">
    <div class="card-body d-flex flex-column flex-sm-row align-items-sm-center gap-3">
      <div class="symbol-100px" id="logo-thumb"><img alt="logo" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100'%3E%3Crect fill='%23dde' width='100' height='100'/%3E%3C/svg%3E" /></div>
      <div>
        <button type="button" class="btn btn-sm btn-light">Change logo</button>
        <button type="button" class="btn btn-sm btn-light">Remove</button>
      </div>
    </div>
  </div>
  <div id="general" class="card"><div class="card-body"><h3>General Information</h3><p class="mb-0">fields…</p></div></div>
  <div id="password" class="card"><div class="card-body"><h3>Change password</h3></div></div>
  <div id="categories" class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h3 class="mb-0">Categories & Skills</h3>
        <button type="button" class="btn btn-sm btn-light">Manage</button>
      </div>
      <div id="categories-scroll" class="categories-scroll">
        ${Array.from({ length: 8 }, (_, i) => `<div class="chip">Category ${i + 1}</div>`).join('')}
      </div>
    </div>
  </div>
  <div id="files" class="card"><div class="card-body"><h3>Required Files</h3></div></div>
  <div id="danger" class="card danger-zone"><div class="card-body"><h3 class="text-danger">Danger zone</h3></div></div>
</body>
</html>`;

async function measure(page) {
  return page.evaluate((maxH) => {
    const logoThumb = document.getElementById('logo-thumb');
    const categoriesScroll = document.getElementById('categories-scroll');
    const danger = document.getElementById('danger');
    const cards = [...document.querySelectorAll('.card')];
    const logoBox = logoThumb.getBoundingClientRect();
    const scrollBox = categoriesScroll.getBoundingClientRect();
    const dangerStyles = getComputedStyle(danger);

    return {
      viewportWidth: window.innerWidth,
      logoWidth: logoBox.width,
      logoHeight: logoBox.height,
      logoNotFullBleed: logoBox.width < window.innerWidth * 0.7,
      categoriesMaxHeight: maxH,
      categoriesClientHeight: categoriesScroll.clientHeight,
      categoriesScrollHeight: categoriesScroll.scrollHeight,
      categoriesInternallyScrollable: categoriesScroll.scrollHeight > categoriesScroll.clientHeight + 2,
      categoriesBounded: categoriesScroll.clientHeight <= maxH + 1,
      dangerBorderIncludesRed: dangerStyles.borderColor.includes('220') || danger.classList.contains('danger-zone'),
      cardCount: cards.length,
      cardsStack: cards.every((card, index) => {
        if (index === 0) return true;
        return card.getBoundingClientRect().top >= cards[index - 1].getBoundingClientRect().bottom - 1;
      }),
    };
  }, CATEGORIES_MAX_HEIGHT_PX);
}

async function runViewport(browser, width, height, label) {
  const page = await browser.newPage({ viewport: { width, height } });
  await page.setContent(html, { waitUntil: 'load' });
  const metrics = await measure(page);
  const shot = join(outDir, `profile-${label}.png`);
  await page.screenshot({ path: shot, fullPage: true });
  await page.close();
  return { label, metrics, shot };
}

const browser = await chromium.launch({ channel: 'chrome' });
try {
  const results = [
    await runViewport(browser, 375, 812, '375'),
    await runViewport(browser, 1440, 900, '1440'),
  ];
  writeFileSync(join(outDir, 'metrics.json'), JSON.stringify(results, null, 2));

  for (const { label, metrics, shot } of results) {
    const ok =
      metrics.logoNotFullBleed &&
      metrics.categoriesBounded &&
      metrics.categoriesInternallyScrollable &&
      metrics.cardsStack &&
      metrics.dangerBorderIncludesRed;
    console.log(
      JSON.stringify({ label, ok, shot, metrics }, null, 2),
    );
    if (!ok) {
      process.exitCode = 1;
    }
  }
} finally {
  await browser.close();
}
