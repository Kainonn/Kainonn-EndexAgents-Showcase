import { cn } from '@/lib/utils';

const priorityConfig: Record<string, { bg: string; text: string; ring: string }> = {
    contact_today: {
        bg: 'bg-rose-50 dark:bg-rose-950/40',
        text: 'text-rose-700 dark:text-rose-300',
        ring: 'ring-1 ring-rose-200 dark:ring-rose-800',
    },
    this_week: {
        bg: 'bg-amber-50 dark:bg-amber-950/40',
        text: 'text-amber-700 dark:text-amber-300',
        ring: 'ring-1 ring-amber-200 dark:ring-amber-800',
    },
    low_priority: {
        bg: 'bg-zinc-100 dark:bg-zinc-800/60',
        text: 'text-zinc-500 dark:text-zinc-400',
        ring: 'ring-1 ring-zinc-200 dark:ring-zinc-700',
    },
};

type Props = {
    priority: string;
    label: string;
    emoji: string;
    className?: string;
};

export function LeadPriorityBadge({ priority, label, emoji, className }: Props) {
    const config = priorityConfig[priority] ?? priorityConfig.low_priority;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold leading-none',
                config.bg,
                config.text,
                config.ring,
                className,
            )}
        >
            <span>{emoji}</span>
            <span>{label}</span>
        </span>
    );
}
