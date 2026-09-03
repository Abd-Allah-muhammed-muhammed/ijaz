import type { ReactNode } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowLeft, faArrowRight } from '@fortawesome/free-solid-svg-icons';
import { whenLocale } from '@/shared/helpers/general';

export type LocaleNavArrowProps = {
  /** Leading arrow (Previous) vs trailing arrow (Next / Submit). */
  position: 'start' | 'end';
};

/**
 * Locale-aware chevron used by Previous / Next / Submit registration buttons.
 * Arabic flips the arrow direction relative to LTR.
 */
export function LocaleNavArrow({ position }: LocaleNavArrowProps) {
  const isStart = position === 'start';

  return whenLocale<ReactNode>(
    'ar',
    () => (
      <FontAwesomeIcon
        icon={isStart ? faArrowRight : faArrowLeft}
        size="sm"
        className={isStart ? 'ms-0 me-2' : 'ms-2 me-0'}
      />
    ),
    () => (
      <FontAwesomeIcon
        icon={isStart ? faArrowLeft : faArrowRight}
        size="sm"
        className={isStart ? 'ms-0 me-2' : 'ms-2 me-0'}
      />
    ),
  );
}
