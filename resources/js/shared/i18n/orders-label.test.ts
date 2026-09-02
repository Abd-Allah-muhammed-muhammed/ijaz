import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import ar from '@/lang/ar.json';
import en from '@/lang/en.json';
import hi from '@/lang/hi.json';
import ur from '@/lang/ur.json';
import {
  ORDERS_LABEL_CALL_SITE_FILES,
  ORDERS_NESTED_DISPUTE_KEYS,
  ORDERS_PAGE_TITLE_KEY,
} from './orders-label';

const repoRoot = join(__dirname, '../../../..');

const locales = {
  en,
  ar,
  hi,
  ur,
} as const;

const expectedLabels = {
  en: 'Orders',
  ar: 'الطلبات',
  hi: 'ऑर्डर',
  ur: 'آرڈرز',
} as const;

describe('orders i18next flat-key / nested-key collision', () => {
  it('orders_label resolves to a plain string ("Orders" / localized equivalent) in all 4 locales', () => {
    expect(ORDERS_PAGE_TITLE_KEY).toBe('orders_label');

    for (const [locale, resources] of Object.entries(locales)) {
      expect(resources).toHaveProperty(ORDERS_PAGE_TITLE_KEY);
      const label = resources[ORDERS_PAGE_TITLE_KEY as keyof typeof resources];
      expect(typeof label).toBe('string');
      expect(label).toBe(expectedLabels[locale as keyof typeof expectedLabels]);
    }
  });

  it('orders (bare key) resolves to an object post-merge, confirming the namespace shape is intact', () => {
    for (const resources of Object.values(locales)) {
      expect(resources).toHaveProperty('orders');
      expect(typeof resources.orders).toBe('object');
      expect(resources.orders).not.toBeNull();
      expect(Array.isArray(resources.orders)).toBe(false);
      expect(resources.orders).toHaveProperty('dispute_resolution');
      expect(resources.orders).toHaveProperty('status_transition_not_allowed');
    }
  });

  it('every one of the 13 known call sites (sidebar ×2, page titles ×4, home badges ×2, dashboard chart, UserCard, ProviderCard) uses orders_label, not bare orders', () => {
    expect(ORDERS_LABEL_CALL_SITE_FILES).toHaveLength(9);

    let ordersLabelUsages = 0;

    for (const relativePath of ORDERS_LABEL_CALL_SITE_FILES) {
      const src = readFileSync(join(repoRoot, relativePath), 'utf8');

      expect(src).not.toMatch(/\bt\(['"]orders['"]\)/);
      expect(src).toMatch(/ORDERS_PAGE_TITLE_KEY|t\(['"]orders_label['"]\)/);

      const constantMatches = src.match(/t\(ORDERS_PAGE_TITLE_KEY\)/g) ?? [];
      const literalMatches = src.match(/t\(['"]orders_label['"]\)/g) ?? [];
      ordersLabelUsages += constantMatches.length + literalMatches.length;
    }

    // 13 call sites: provider sidebar 1, provider Index 2, provider Show 1,
    // admin Metronic sidebar 2, admin Orders Index 2, admin Home 2,
    // DashboardCharts 1, UserCard 1, ProviderCard 1.
    expect(ordersLabelUsages).toBe(13);
  });

  it('all existing orders.* nested keys (dispute UI) continue to work correctly, unaffected by the rename', () => {
    for (const key of ORDERS_NESTED_DISPUTE_KEYS) {
      expect(typeof en[key as keyof typeof en]).toBe('string');
      expect(String(en[key as keyof typeof en]).length).toBeGreaterThan(0);
    }

    // PHP-merged nested path used by Laravel __() and available on the orders object.
    expect(typeof en.orders.dispute_resolution.full_user).toBe('string');
    expect(en.orders.dispute_resolution.full_user.length).toBeGreaterThan(0);
  });
});
