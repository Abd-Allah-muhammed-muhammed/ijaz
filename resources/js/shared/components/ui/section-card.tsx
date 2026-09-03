import {
  SECTION_CARD_BASE_CLASS,
  SECTION_CARD_BODY_CLASS,
  SECTION_CARD_HEADER_CLASS,
  SECTION_CARD_HERO_BODY_CLASS,
  SECTION_CARD_HERO_FOOTER_CLASS,
  SECTION_CARD_TITLE_CLASS,
  type SectionCardProps,
} from './types';

export default function SectionCard({
  children,
  title,
  header,
  headerExtra,
  footer,
  variant = 'default',
  className,
  bodyClassName,
  headerClassName,
  footerClassName,
}: SectionCardProps) {
  const rootClass = [
    SECTION_CARD_BASE_CLASS,
    variant === 'hero' ? 'overflow-hidden' : null,
    className,
  ]
    .filter(Boolean)
    .join(' ');

  if (variant === 'hero') {
    return (
      <div className={rootClass}>
        <div className={bodyClassName ?? SECTION_CARD_HERO_BODY_CLASS}>
          {children}
        </div>
        {footer != null ? (
          <div className={footerClassName ?? SECTION_CARD_HERO_FOOTER_CLASS}>
            {footer}
          </div>
        ) : null}
      </div>
    );
  }

  const resolvedHeader =
    header ??
    (title != null || headerExtra != null ? (
      <>
        <div className="d-flex align-items-center gap-2 m-0 min-w-0">
          {typeof title === 'string' ? (
            <h3 className={SECTION_CARD_TITLE_CLASS}>{title}</h3>
          ) : (
            title
          )}
        </div>
        {headerExtra}
      </>
    ) : null);

  return (
    <div className={rootClass}>
      {resolvedHeader != null ? (
        <div className={headerClassName ?? SECTION_CARD_HEADER_CLASS}>
          {resolvedHeader}
        </div>
      ) : null}
      <div className={bodyClassName ?? SECTION_CARD_BODY_CLASS}>{children}</div>
    </div>
  );
}
