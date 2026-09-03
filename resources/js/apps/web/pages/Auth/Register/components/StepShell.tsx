import type { ReactNode } from 'react';

export type StepShellProps = {
  isCurrent: boolean;
  children: ReactNode;
  /** Optional heading block rendered above children inside the step content. */
  heading?: ReactNode;
  /** Wrapper around heading; defaults to Metronic account-info spacing. */
  headingClassName?: string;
  /** Inner content wrapper class; OTP step uses a different layout. */
  contentClassName?: string;
};

/**
 * Metronic stepper content shell shared by registration steps.
 */
export default function StepShell({
  isCurrent,
  children,
  heading,
  headingClassName = 'pb-5',
  contentClassName = 'w-100',
}: StepShellProps) {
  return (
    <div className={isCurrent ? 'current' : ''} data-kt-stepper-element="content">
      <div className={contentClassName}>
        {heading ? (
          <div className={headingClassName}>
            {heading}
          </div>
        ) : null}
        {children}
      </div>
    </div>
  );
}
