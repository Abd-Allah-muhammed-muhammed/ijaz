import type { Banner } from '@/shared/types/models';

/** Prefer the existing link as display text; fall back to view_more (no title field on Banner). */
export function bannerAffordanceLabel(banner: Banner, viewMoreLabel: string): string {
  const link = banner.link?.trim();
  if (link && link !== '#') {
    return link;
  }

  return viewMoreLabel;
}
