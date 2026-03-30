import { Flame } from 'lucide-react';

type Props = {
    count: number;
    onShowToday: () => void;
};

export function TodayLeadsBanner({ count, onShowToday }: Props) {
    if (count === 0) return null;

    return (
        <button
            type="button"
            onClick={onShowToday}
            className="group flex w-full items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-left transition-colors hover:bg-rose-100 dark:border-rose-800/50 dark:bg-rose-950/30 dark:hover:bg-rose-950/50"
        >
            <div className="flex size-9 shrink-0 items-center justify-center rounded-full bg-rose-100 dark:bg-rose-900/50">
                <Flame className="size-5 text-rose-600 dark:text-rose-400" />
            </div>
            <div className="min-w-0 flex-1">
                <p className="text-sm font-semibold text-rose-800 dark:text-rose-200">
                    {count} lead{count !== 1 ? 's' : ''} para contactar hoy
                </p>
                <p className="text-[11px] text-rose-600/70 dark:text-rose-400/70">
                    Leads con score alto, respuestas pendientes o seguimientos vencidos. Click para filtrar.
                </p>
            </div>
            <span className="text-2xl font-bold tabular-nums text-rose-600 dark:text-rose-400">
                {count}
            </span>
        </button>
    );
}
