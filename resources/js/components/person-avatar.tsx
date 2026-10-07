import { cn } from '@/lib/utils';
import { t } from '@/lib/i18n';

export function PersonAvatar({ name, className }: { name: string; className?: string }) {
    return (
        <span className={cn('flex size-10 shrink-0 items-center justify-center rounded-full bg-brand/12 text-base font-bold text-brand', className)}>
            {name.trim().charAt(0) || t('؟')}
        </span>
    );
}
