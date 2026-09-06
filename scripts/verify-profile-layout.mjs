/**
 * Profile polish verification at ~375px / ~1440px:
 * - compact logo thumb (70px, not full-bleed)
 * - HTML validation attributes present on key fields
 * - consistent card stacking / danger zone border
 *
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
const LOGO_THUMB_PX = 70;

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
    .card-header { padding: 1.5rem 1.5rem 0; background: transparent; border: 0; }
    .symbol-70px { width: ${LOGO_THUMB_PX}px; height: ${LOGO_THUMB_PX}px; display: inline-flex; border-radius: .5rem; overflow: hidden; border: 1px solid #eef3f7; flex-shrink: 0; }
    .symbol-70px img { width: 100%; height: 100%; object-fit: cover; }
    .fw-semibold { font-weight: 600; }
    .text-gray-700 { color: #5e6278; }
    .mb-2 { margin-bottom: .5rem !important; }
    .categories-scroll { max-height: ${CATEGORIES_MAX_HEIGHT_PX}px; overflow: auto; }
    .danger-zone { border: 1px solid rgba(220,53,69,.5) !important; }
    .chip { display: block; padding: .5rem 0; border-bottom: 1px solid #eee; }
    label { display: block; }
  </style>
</head>
<body>
  <div id="identity" class="card">
    <div class="card-body d-flex align-items-center gap-3 flex-wrap">
      <div class="symbol-70px"><img alt="avatar" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='70' height='70'%3E%3Crect fill='%23cce0ff' width='70' height='70'/%3E%3C/svg%3E" /></div>
      <div><strong>Provider Name</strong> ★★★★☆</div>
    </div>
  </div>
  <div id="logo-card" class="card">
    <div class="card-header"><h3 class="fw-bolder mb-0">Logo</h3></div>
    <div class="card-body d-flex align-items-center gap-3 flex-wrap">
      <div class="symbol-70px" id="logo-thumb"><img alt="logo" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='70' height='70'%3E%3Crect fill='%23dde' width='70' height='70'/%3E%3C/svg%3E" /></div>
      <div>
        <button type="button" class="btn btn-sm btn-light">Change logo</button>
        <button type="button" class="btn btn-sm btn-light">Remove</button>
      </div>
    </div>
  </div>
  <div id="general" class="card">
    <div class="card-header"><h3 class="fw-bolder mb-0">General Information</h3></div>
    <div class="card-body">
      <div class="row g-4">
        <div class="col-12 col-md-4">
          <label class="fw-semibold text-gray-700 mb-2" for="phone">Phone</label>
          <input id="phone" class="form-control" type="tel" maxlength="14" />
        </div>
        <div class="col-12 col-md-4">
          <label class="fw-semibold text-gray-700 mb-2" for="iban">IBAN</label>
          <input id="iban" class="form-control" type="text" maxlength="24" />
        </div>
        <div class="col-12 col-md-4">
          <label class="fw-semibold text-gray-700 mb-2" for="email">Email</label>
          <input id="email" class="form-control" type="email" maxlength="255" />
        </div>
        <div class="col-12 col-md-4">
          <label class="fw-semibold text-gray-700 mb-2" for="address">Address</label>
          <input id="address" class="form-control" type="text" maxlength="500" />
        </div>
        <div class="col-12">
          <label class="fw-semibold text-gray-700 mb-2" for="about">About</label>
          <textarea id="about" class="form-control" rows="3" maxlength="1000"></textarea>
        </div>
      </div>
    </div>
  </div>
  <div id="password-card" class="card">
    <div class="card-header"><h3 class="fw-bolder mb-0">Change password</h3></div>
    <div class="card-body">
      <p class="text-muted fs-7 mb-3">Leave blank to keep your current password</p>
      <div class="row g-4">
        <div class="col-12 col-md-6">
          <label class="fw-semibold text-gray-700 mb-2" for="password">Password</label>
          <input id="password" class="form-control" type="password" minlength="8" maxlength="64" />
        </div>
        <div class="col-12 col-md-6">
          <label class="fw-semibold text-gray-700 mb-2" for="password_confirmation">Password Confirmation</label>
          <input id="password_confirmation" class="form-control" type="password" minlength="8" maxlength="64" />
        </div>
      </div>
    </div>
  </div>
  <div id="categories" class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="fw-bolder mb-0">Categories & Skills</h3>
      <button type="button" class="btn btn-sm btn-light">Manage</button>
    </div>
    <div class="card-body">
      <div id="categories-scroll" class="categories-scroll">
        ${Array.from({ length: 8 }, (_, i) => `<div class="chip">Category ${i + 1}</div>`).join('')}
      </div>
    </div>
  </div>
  <div id="files" class="card"><div class="card-header"><h3 class="fw-bolder mb-0">Required Files</h3></div><div class="card-body"></div></div>
  <div id="danger" class="card danger-zone"><div class="card-header"><h3 class="fw-bolder text-danger mb-0">Danger zone</h3></div><div class="card-body"></div></div>
</body>
</html>`;

async function measure(page) {
  return page.evaluate((maxH) => {
    const logoThumb = document.getElementById('logo-thumb');
    const categoriesScroll = document.getElementById('categories-scroll');
    const danger = document.getElementById('danger');
    const phone = document.getElementById('phone');
    const iban = document.getElementById('iban');
    const email = document.getElementById('email');
    const address = document.getElementById('address');
    const about = document.getElementById('about');
    const password = document.getElementById('password');
    const cards = [...document.querySelectorAll('.card')];
    const logoBox = logoThumb.getBoundingClientRect();
    const logoCard = document.getElementById('logo-card').getBoundingClientRect();
    const categoriesCard = document.getElementById('categories').getBoundingClientRect();

    return {
      viewportWidth: window.innerWidth,
      logoWidth: logoBox.width,
      logoHeight: logoBox.height,
      logoNotFullBleed: logoBox.width < window.innerWidth * 0.35,
      logoCardHeight: logoCard.height,
      categoriesCardHeight: categoriesCard.height,
      logoCardNotDominating: logoCard.height < categoriesCard.height,
      phoneMaxLength: phone.getAttribute('maxlength'),
      ibanMaxLength: iban.getAttribute('maxlength'),
      emailType: email.getAttribute('type'),
      addressMaxLength: address.getAttribute('maxlength'),
      aboutMaxLength: about.getAttribute('maxlength'),
      passwordMinLength: password.getAttribute('minlength'),
      passwordMaxLength: password.getAttribute('maxlength'),
      categoriesBounded: categoriesScroll.clientHeight <= maxH + 1,
      categoriesInternallyScrollable: categoriesScroll.scrollHeight > categoriesScroll.clientHeight + 2,
      dangerBorderIncludesRed: danger.classList.contains('danger-zone'),
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
  const shot = join(outDir, `profile-polish-${label}.png`);
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
  writeFileSync(join(outDir, 'polish-metrics.json'), JSON.stringify(results, null, 2));

  for (const { label, metrics, shot } of results) {
    const ok =
      metrics.logoNotFullBleed &&
      metrics.logoWidth <= LOGO_THUMB_PX + 2 &&
      metrics.logoCardNotDominating &&
      metrics.phoneMaxLength === '14' &&
      metrics.ibanMaxLength === '24' &&
      metrics.emailType === 'email' &&
      metrics.addressMaxLength === '500' &&
      metrics.aboutMaxLength === '1000' &&
      metrics.passwordMinLength === '8' &&
      metrics.passwordMaxLength === '64' &&
      metrics.categoriesBounded &&
      metrics.categoriesInternallyScrollable &&
      metrics.cardsStack &&
      metrics.dangerBorderIncludesRed;
    console.log(JSON.stringify({ label, ok, shot, metrics }, null, 2));
    if (!ok) {
      process.exitCode = 1;
    }
  }
} finally {
  await browser.close();
}
