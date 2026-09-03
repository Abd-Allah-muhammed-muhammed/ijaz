import type { ReactNode } from 'react';

export type SummaryRowProps = {
  label: string;
  value: ReactNode;
  /** Use a paragraph value style (about / address). */
  multiline?: boolean;
  multilineClassName?: string;
};

/**
 * Label + value pair used in the registration summary step.
 */
export default function SummaryRow({
  label,
  value,
  multiline = false,
  multilineClassName = 'text-gray-600 form-control bg-transparent h-auto',
}: SummaryRowProps) {
  return (
    <div className="col d-flex flex-column mb-4 mb-md-5">
      <span className="fw-bold text-gray-800">{label}</span>
      {multiline ? (
        <p className={multilineClassName}>{value}</p>
      ) : (
        <span className="text-gray-600">{value}</span>
      )}
    </div>
  );
}
