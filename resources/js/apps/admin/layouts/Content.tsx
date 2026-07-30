import { cn } from '@/shared/lib/utils';
import type { ReactNode } from 'react';

type ContentProps = {
  children: ReactNode;
  className?: string;
  /** When false, skip the inner max-width container (full-bleed pages). */
  contained?: boolean;
};

export function Content({ children, className, contained = true }: ContentProps) {
  return (
    <div className={cn('flex-1 px-4 py-6 md:px-6', className)}>
      {contained ? (
        <div className="mx-auto w-full max-w-[90rem]">{children}</div>
      ) : (
        children
      )}
    </div>
  );
}
