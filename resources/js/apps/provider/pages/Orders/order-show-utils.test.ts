import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import en from '@/lang/en.json';
import { OfferStatusEnum, OrderStatusEnum } from '@/Enums/Order';
import { formatCurrency, formatDateTime } from '@/shared/lib/formatters';
import {
  OFFER_COUNT_PLURAL_KEY,
  OFFER_COUNT_SINGULAR_KEY,
  ORDER_SHOW_ADD_OFFER_KEY,
  ORDER_SHOW_EMPTY_OFFERS_HINT_KEY,
  ORDER_SHOW_EMPTY_OFFERS_TITLE_KEY,
  ORDER_SHOW_END_ORDER_KEY,
  ORDER_SHOW_REVIEW_SUBMIT_KEY,
  canAddOffer,
  canDeleteOffer,
  canEditOffer,
  canEndOrder,
  canShowChatCta,
  getOfferStatusBadgeClass,
  getOrderStatusBadgeClass,
  offerCountLabelKey,
  shouldShowOrderEndedAlert,
  shouldShowProviderReviewForm,
  truncateText,
} from './order-show-utils';

const showSrc = readFileSync(join(__dirname, 'Show.tsx'), 'utf8');
const offersSrc = readFileSync(join(__dirname, 'Offers.tsx'), 'utf8');
const indexSrc = readFileSync(join(__dirname, 'Index.tsx'), 'utf8');

describe('order hero', () => {
  it('order hero renders status pill, formatted budget with currency, and 4 stat tiles', () => {
    expect(showSrc).toContain('getOrderStatusBadgeClass');
    expect(showSrc).toContain('StatusBadge');
    expect(showSrc).toContain('StatTile');
    expect(showSrc).toContain("from '@/shared/components/ui'");
    expect(showSrc).toContain('formatCurrency');
    expect(showSrc).toContain("t('budget')");
    expect(showSrc).toContain("t('expected_time')");
    expect(showSrc).toContain("t('attachments')");
    expect(showSrc).toContain("t('offer count')");
    expect(getOrderStatusBadgeClass(OrderStatusEnum.InProgress)).toContain('badge-light-');
    expect(formatCurrency(20, { locale: 'en', currencyLabel: 'SAR', maximumFractionDigits: 2 })).toMatch(/20/);
    expect(formatCurrency(20, { locale: 'en', currencyLabel: 'SAR', maximumFractionDigits: 2 })).toContain('SAR');
  });
});

describe('order ended alert', () => {
  it('the "order has been ended" message no longer shows for InProgress — only genuine terminal statuses', () => {
    expect(shouldShowOrderEndedAlert(OrderStatusEnum.InProgress)).toBe(false);
    expect(shouldShowOrderEndedAlert(OrderStatusEnum.New)).toBe(false);
    expect(shouldShowOrderEndedAlert(OrderStatusEnum.OfferProvided)).toBe(false);
    expect(shouldShowOrderEndedAlert(OrderStatusEnum.PaymentCompleted)).toBe(false);
    expect(shouldShowOrderEndedAlert(OrderStatusEnum.EndedByClient)).toBe(true);
    expect(shouldShowOrderEndedAlert(OrderStatusEnum.EndedByProvider)).toBe(true);
    expect(shouldShowOrderEndedAlert(OrderStatusEnum.CancelledByClient)).toBe(true);
    expect(shouldShowOrderEndedAlert(OrderStatusEnum.CancelledByProvider)).toBe(true);
    expect(showSrc).toContain('shouldShowOrderEndedAlert');
    expect(showSrc).not.toMatch(
      /!\(\[OrderStatusEnum\.New,\s*OrderStatusEnum\.OfferProvided\].*includes\(order\.status\.value\)/,
    );
  });
});

describe('End Order button', () => {
  it('End Order button is translated (not hardcoded English) and requires confirmation before submitting', () => {
    expect(ORDER_SHOW_END_ORDER_KEY).toBe('end_order');
    expect(en).toHaveProperty(ORDER_SHOW_END_ORDER_KEY);
    expect(typeof en[ORDER_SHOW_END_ORDER_KEY as keyof typeof en]).toBe('string');
    expect(showSrc).toContain(`t('${ORDER_SHOW_END_ORDER_KEY}')`);
    expect(showSrc).not.toMatch(/>End Order</);
    expect(canEndOrder(OrderStatusEnum.InProgress)).toBe(true);
    expect(canEndOrder(OrderStatusEnum.New)).toBe(false);
    expect(showSrc).toMatch(/Swal|swal\.fire|are_you_sure/);
    expect(showSrc).toContain('canEndOrder');
  });
});

describe('My Offers table', () => {
  it('My Offers count under the heading renders as "N offer(s)", not a bare number', () => {
    expect(offerCountLabelKey(1)).toBe(OFFER_COUNT_SINGULAR_KEY);
    expect(offerCountLabelKey(0)).toBe(OFFER_COUNT_PLURAL_KEY);
    expect(offerCountLabelKey(2)).toBe(OFFER_COUNT_PLURAL_KEY);
    expect(en).toHaveProperty(OFFER_COUNT_SINGULAR_KEY);
    expect(en).toHaveProperty(OFFER_COUNT_PLURAL_KEY);
    expect(en[OFFER_COUNT_SINGULAR_KEY as keyof typeof en]).toContain('{{count}}');
    expect(en[OFFER_COUNT_PLURAL_KEY as keyof typeof en]).toContain('{{count}}');
    expect(showSrc).toContain('offerCountLabelKey');
    expect(showSrc).toContain('offerCountLabelKey(offers.length)');
    expect(showSrc).not.toMatch(/text-muted fw-semibold fs-7">\{offers\.length\}</);
  });

  it('My Offers table shows a row number, not the raw offer UUID', () => {
    expect(showSrc).toMatch(/index\s*\+\s*1|rowNumber|row_number/);
    expect(showSrc).not.toMatch(/<td[^>]*>\{offer\.id\}<\/td>/);
  });

  it('My Offers action column shows Delete only when offer status is Pending, not Accepted — matches backend rejection behavior', () => {
    expect(canDeleteOffer(OfferStatusEnum.Pending, OrderStatusEnum.New)).toBe(true);
    expect(canDeleteOffer(OfferStatusEnum.Accepted, OrderStatusEnum.New)).toBe(false);
    expect(canDeleteOffer(OfferStatusEnum.Accepted, OrderStatusEnum.OfferProvided)).toBe(false);
    expect(canEditOffer(OfferStatusEnum.Accepted, OrderStatusEnum.OfferProvided)).toBe(true);
    expect(canEditOffer(OfferStatusEnum.Pending, OrderStatusEnum.New)).toBe(true);
    expect(showSrc).toContain('canDeleteOffer');
    expect(showSrc).toContain('canEditOffer');
  });

  it('My Offers price uses formatCurrency, date uses formatDateTime (single formatted value, not duplicated raw date)', () => {
    expect(showSrc).toContain('formatCurrency');
    expect(showSrc).toContain('formatDateTime');
    expect(showSrc).not.toMatch(
      /toLocaleDateString\(\)\}\s*\{new Date\(offer\.created_at\)\.toLocaleDateString\(\)/,
    );
    const formatted = formatDateTime('2024-01-15T10:30:00Z');
    expect(formatted.length).toBeGreaterThan(0);
    expect(formatted).not.toMatch(/(\d{1,2}\/\d{1,2}\/\d{4}).+\1/);
  });

  it('My Offers empty state shows an inviting message + Add offer CTA, not "hover to view offers table"', () => {
    expect(ORDER_SHOW_EMPTY_OFFERS_TITLE_KEY).toBe('no_offers_submitted_yet');
    expect(ORDER_SHOW_ADD_OFFER_KEY).toBe('add_offer');
    expect(en).toHaveProperty(ORDER_SHOW_EMPTY_OFFERS_TITLE_KEY);
    expect(en).toHaveProperty(ORDER_SHOW_EMPTY_OFFERS_HINT_KEY);
    expect(en).toHaveProperty(ORDER_SHOW_ADD_OFFER_KEY);
    expect(showSrc).toContain(`t('${ORDER_SHOW_EMPTY_OFFERS_TITLE_KEY}'`);
    expect(showSrc).toContain(`t('${ORDER_SHOW_ADD_OFFER_KEY}'`);
    expect(showSrc).not.toContain('Hover to view offers table');
    expect(canAddOffer(OrderStatusEnum.New, [])).toBe(true);
    expect(canAddOffer(OrderStatusEnum.New, [{ status: { value: OfferStatusEnum.Pending } }])).toBe(false);
  });
});

describe('Offers list page', () => {
  it('Offers list page has a search input and a status filter, wired via applyFilterParam/visitWithFilters matching Orders Index', () => {
    expect(indexSrc).toContain('applyFilterParam');
    expect(indexSrc).toContain('visitWithFilters');
    expect(offersSrc).toContain('applyFilterParam');
    expect(offersSrc).toContain('visitWithFilters');
    expect(offersSrc).toContain("searchPramsChanged('search'");
    expect(offersSrc).toContain("searchPramsChanged('status'");
    expect(offersSrc).toContain('OfferStatusEnum');
    expect(offersSrc).toContain('OrderController.offers().url');
    expect(offersSrc).toMatch(/type=['"]text['"]/);
    expect(offersSrc).toMatch(/<select[\s\S]*name=['"]status['"]/);
  });

  it('Offers list cards show the order title as the primary label, not the raw order UUID', () => {
    expect(offersSrc).toContain('row.order?.title');
    expect(offersSrc).toContain('title={row.order_id}');
    expect(offersSrc).not.toMatch(/\{t\(['"]Order ID['"]\)\}:\s*\{row\.order_id\}/);
  });

  it('Offers list cards use formatCurrency for price and formatDateTime for the date', () => {
    expect(offersSrc).toContain('formatCurrency');
    expect(offersSrc).toContain('formatDateTime');
    expect(offersSrc).not.toContain('toLocaleDateString');
  });

  it('Offers list empty state matches the established pattern (icon + message), consistent with Orders Index\'s empty state', () => {
    expect(indexSrc).toContain("t('no_orders_found')");
    expect(indexSrc).toContain('KTIcon');
    expect(offersSrc).toContain("t('no_offers')");
    expect(offersSrc).toContain('KTIcon');
    expect(offersSrc).toMatch(/card border-0 shadow-sm[\s\S]*text-center[\s\S]*no_offers/);
  });
});

describe('review cards', () => {
  it('Provider review submit button label is not "Continue" — updated to clearly mean saving a review', () => {
    expect(ORDER_SHOW_REVIEW_SUBMIT_KEY).toBe('save_review');
    expect(en).toHaveProperty(ORDER_SHOW_REVIEW_SUBMIT_KEY);
    expect(showSrc).toContain(`t('${ORDER_SHOW_REVIEW_SUBMIT_KEY}')`);
    expect(showSrc).not.toMatch(/t\('Continue'\)/);
    expect(shouldShowProviderReviewForm(OrderStatusEnum.EndedByClient)).toBe(true);
    expect(shouldShowProviderReviewForm(OrderStatusEnum.EndedByProvider)).toBe(false);
  });

  it('User review star list renders with stable keys — no React key warning', () => {
    expect(showSrc).toMatch(/userReview[\s\S]*?\.map\(\(i\)\s*=>\s*\([\s\S]*?key=\{i\}/);
  });
});

describe('attachments panel', () => {
  it('Attachments panel shows a proper empty state when order.media is empty, not a blank card body', () => {
    expect(en).toHaveProperty('no_attachments');
    expect(showSrc).toContain("t('no_attachments')");
  });

  it('Attachments filename is a real link/action, not href="#"', () => {
    expect(showSrc).not.toMatch(/href="#"[^>]*>\{media\.file_name\}/);
    expect(showSrc).toMatch(/media\.url/);
  });
});

describe('offer modals', () => {
  it('Create offer modal fields are controlled (value bound), matching Edit offer modal — regression against stale-DOM-on-reopen', () => {
    expect(showSrc).toMatch(/createOfferModal[\s\S]*value=\{OfferForm\.data\.price/);
    expect(showSrc).toMatch(/createOfferModal[\s\S]*value=\{OfferForm\.data\.description/);
    expect(showSrc).toMatch(/Form\.Label|form-label/);
  });
});

describe('helpers', () => {
  it('truncateText shortens long descriptions with an ellipsis', () => {
    expect(truncateText('short')).toBe('short');
    expect(truncateText('a'.repeat(100), 80)).toMatch(/…$/);
  });

  it('offer status badge classes are light pills', () => {
    expect(getOfferStatusBadgeClass(OfferStatusEnum.Pending)).toBe('badge-light-primary');
    expect(getOfferStatusBadgeClass(OfferStatusEnum.Accepted)).toBe('badge-light-success');
  });

  it('chat CTA only when offer provided and provider matches auth socket', () => {
    expect(canShowChatCta(OrderStatusEnum.OfferProvided, 'sock-1', 'sock-1')).toBe(true);
    expect(canShowChatCta(OrderStatusEnum.OfferProvided, 'sock-1', 'other')).toBe(false);
    expect(canShowChatCta(OrderStatusEnum.New, 'sock-1', 'sock-1')).toBe(false);
  });
});
