import { Avatar, AvatarFallback, AvatarImage } from '@/shared/components/ui/avatar';
import { cn } from '@/shared/lib/utils';

type AvatarCellProps = {
  name: string;
  image?: string | null;
  description?: string | null;
  className?: string;
};

/**
 * Avatar + primary label (+ optional secondary line) for identity columns.
 * Replaces ad-hoc UserInfo / OrderCard header combos inside a table cell.
 */
export function AvatarCell({ name, image, description, className }: AvatarCellProps) {
  const initial = name.trim().charAt(0).toUpperCase() || '?';

  return (
    <div className={cn('flex items-center gap-3', className)}>
      <Avatar className="h-9 w-9">
        {image ? <AvatarImage src={image} alt={name} /> : null}
        <AvatarFallback className="text-xs font-semibold">{initial}</AvatarFallback>
      </Avatar>
      <div className="flex min-w-0 flex-col">
        <span className="truncate font-medium text-foreground">{name}</span>
        {description ? (
          <span className="truncate text-xs text-muted-foreground">{description}</span>
        ) : null}
      </div>
    </div>
  );
}
