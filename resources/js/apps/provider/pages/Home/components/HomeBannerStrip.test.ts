import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';

describe('HomeBannerStrip', () => {
  const src = readFileSync(join(__dirname, 'HomeBannerStrip.tsx'), 'utf8');

  it('renders a full-width image-only strip with overlay Swiper dots', () => {
    expect(src).toContain('aspect-ratio: 5 / 1');
    expect(src).toContain('object-fit: cover');
    expect(src).toContain('home-banner-strip-image');
    expect(src).toContain('Swiper');
    expect(src).toContain('Pagination');
    expect(src).toContain('bottom: 0.5rem');
    expect(src).toContain('rgba(255, 255, 255');
    expect(src).not.toContain("from '@/shared/components/ui'");
    expect(src).not.toContain('home-banner-strip-thumb');
    expect(src).not.toContain('view_more');
    expect(src).not.toContain('bannerAffordanceLabel');
    expect(src).not.toContain('KTIcon');
  });

  it('keeps click-through via banner.link with hash fallback and no visible text', () => {
    expect(src).toContain("href={banner.link ?? '#'}");
    expect(src).toContain('aria-label={ariaLabel}');
    expect(src).not.toContain('<span');
  });
});

describe('Home page banner placement', () => {
  const src = readFileSync(join(__dirname, '../Home.tsx'), 'utf8');

  it('places HomeBannerStrip between HomeMetrics and OrderTabsSection, not beside orders', () => {
    const metricsIdx = src.indexOf('<HomeMetrics');
    const stripIdx = src.indexOf('{hasBanners ? <HomeBannerStrip');
    const ordersIdx = src.indexOf('<OrderTabsSection');

    expect(metricsIdx).toBeGreaterThan(-1);
    expect(stripIdx).toBeGreaterThan(metricsIdx);
    expect(ordersIdx).toBeGreaterThan(stripIdx);
    expect(src).not.toContain('Col md={hasBanners');
    expect(src).not.toContain('Col md={6}');
    expect(src).toContain('{hasBanners ? <HomeBannerStrip banners={displayBanners} /> : null}');
  });
});
