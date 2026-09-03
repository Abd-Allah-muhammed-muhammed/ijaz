import {
  DETAIL_SECTION_LABEL_CLASS,
  EMPTY_VALUE_FALLBACK,
  type DetailSectionProps,
} from './types';

export default function DetailSection({
  label,
  children,
  value,
  emptyFallback = EMPTY_VALUE_FALLBACK,
  className,
}: DetailSectionProps) {
  const hasValue = value !== null && value !== undefined && value !== '';
  const content =
    children ?? (
      <p
        className={`fs-6 mb-0 lh-lg ${hasValue ? 'text-gray-800' : 'text-muted'}`}
      >
        {hasValue ? value : emptyFallback}
      </p>
    );

  return (
    <div className={className}>
      <div className={DETAIL_SECTION_LABEL_CLASS}>{label}</div>
      {content}
    </div>
  );
}
