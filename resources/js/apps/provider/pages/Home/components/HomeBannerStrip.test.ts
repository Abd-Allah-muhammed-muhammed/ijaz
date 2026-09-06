import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { bannerAffordanceLabel } from './home-banner-strip-utils';
import type { Banner } from '@/shared/types/models';

describe('HomeBannerStrip', () => {
  const src = readFileSync(join(__dirname, 'HomeBannerStrip.tsx'), 'utf8');

  it('renders a compact SectionCard strip with thumbnail + Swiper pagination', () => {
    expect(src).toContain('SectionCard');
    expect(src).toContain('home-banner-strip-thumb');
    expect(src).toContain('Swiper');
    expect(src).toContain('Pagination');
    expect(src).toContain("t('view_more')");
    expect(src).not.toContain('aspectRatio');
  });

  it('keeps click-through via banner.link with hash fallback', () => {
    expect(src).toContain("href={banner.link ?? '#'}");
  });
});

describe('bannerAffordanceLabel', () => {
  const viewMore = 'View More';

  it('uses the link when present instead of inventing a title field', () => {
    const banner = { id: 1, image: '/x.png', link: 'https://example.com/promo' } as Banner;
    expect(bannerAffordanceLabel(banner, viewMore)).toBe('https://example.com/promo');
  });

  it('falls back to view_more when link is missing or hash-only', () => {
    expect(bannerAffordanceLabel({ id: 2, image: '/x.png', link: null } as Banner, viewMore)).toBe(
      viewMore,
    );
    expect(bannerAffordanceLabel({ id: 3, image: '/x.png', link: '#' } as Banner, viewMore)).toBe(
      viewMore,
    );
    expect(bannerAffordanceLabel({ id: 4, image: '/x.png', link: '  ' } as Banner, viewMore)).toBe(
      viewMore,
    );
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
