import {
  EMPTY_STATE_COMPACT_DESCRIPTION_CLASS,
  EMPTY_STATE_COMPACT_PADDING_CLASS,
  EMPTY_STATE_COMPACT_TITLE_CLASS,
  EMPTY_STATE_DEFAULT_PADDING_CLASS,
  EMPTY_STATE_DESCRIPTION_CLASS,
  EMPTY_STATE_TITLE_CLASS,
  type EmptyStateProps,
} from './types';

export default function EmptyState({
  icon,
  title,
  description,
  action,
  compact = false,
  className,
}: EmptyStateProps) {
  const paddingClass = compact
    ? EMPTY_STATE_COMPACT_PADDING_CLASS
    : EMPTY_STATE_DEFAULT_PADDING_CLASS;
  const titleClass = compact
    ? EMPTY_STATE_COMPACT_TITLE_CLASS
    : EMPTY_STATE_TITLE_CLASS;
  const descriptionClass = compact
    ? EMPTY_STATE_COMPACT_DESCRIPTION_CLASS
    : EMPTY_STATE_DESCRIPTION_CLASS;
  const rootClass = [paddingClass, className].filter(Boolean).join(' ');
  const TitleTag = compact ? 'p' : 'h4';

  return (
    <div className={rootClass}>
      {icon}
      <TitleTag className={titleClass}>{title}</TitleTag>
      {description ? <p className={descriptionClass}>{description}</p> : null}
      {action}
    </div>
  );
}
