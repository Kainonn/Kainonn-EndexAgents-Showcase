import { router } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, Clock, MessageSquare, Search, Target, Zap } from 'lucide-react';
import { useRef, useState } from 'react';
import { cn } from '@/lib/utils';
import type { KpiData, PriorityOptions, WorkspaceFilters } from '@/types/workspace';

type Props = {
    todayCount: number;
    totalFiltered: number;
    statusCounts: Record<string, number>;
    priorityCounts: Record<string, number>;
    kpis: KpiData;
    filters: WorkspaceFilters;
    statusOptions: Record<string, { label: string; color: string }>;
    priorityOptions: PriorityOptions;
};

type TabDef = { value: string; label: string; count: number; emoji?: string };

export function WorkspaceHeader({
    todayCount,
    totalFiltered,
    statusCounts,
    priorityCounts,
    kpis,
    filters,
    statusOptions,
    priorityOptions,
}: Props) {
    const [search, setSearch] = useState(filters.search);
    const debounceRef = useRef<ReturnType<typeof setTimeout>>(null);

    const totalLeads = Object.values(statusCounts).reduce((s, n) => s + n, 0);

    // Build status tabs
    const statusTabs: TabDef[] = [
        { value: 'all', label: 'Todos', count: totalLeads },
        { value: 'today', label: 'Para hoy', count: todayCount, emoji: '🔥' },
        ...Object.entries(statusOptions)
            .filter(([key]) => (statusCounts[key] ?? 0) > 0)
            .map(([value, opt]) => ({
                value,
                label: opt.label,
                count: statusCounts[value] ?? 0,
            })),
    ];

    // Build priority tabs
    const priorityTabs: TabDef[] = [
        { value: 'all', label: 'Todas', count: totalLeads },
        ...Object.entries(priorityOptions)
            .filter(([key]) => (priorityCounts[key] ?? 0) > 0)
            .map(([value, opt]) => ({
                value,
                label: opt.label,
                count: priorityCounts[value] ?? 0,
                emoji: opt.emoji,
            })),
    ];

    const navigate = (params: Partial<WorkspaceFilters>) => {
        const merged = { ...filters, ...params };
        const query: Record<string, string> = {};
        if (merged.status && merged.status !== 'all') query.status = merged.status;
        if (merged.priority && merged.priority !== 'all') query.priority = merged.priority;
        if (merged.search?.trim()) query.search = merged.search.trim();
        if (merged.quick_filter) query.quick_filter = merged.quick_filter;
        if (merged.follow_up_window) query.follow_up_window = merged.follow_up_window;

        router.get('/endex/workspace', query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleStatusTab = (value: string) => {
        // "today" is a special value - we map it to priority=contact_today
        if (value === 'today') {
            navigate({ status: 'all', priority: 'contact_today' });
        } else {
            navigate({ status: value, priority: filters.priority === 'contact_today' && value !== 'all' ? 'all' : filters.priority });
        }
    };

    const handlePriorityTab = (value: string) => {
        navigate({ priority: value });
    };

    const handleQuickFilter = (value: string) => {
        navigate({
            quick_filter: filters.quick_filter === value ? null : value,
            status: 'all',
            priority: 'all',
        });
    };

    const handleSearch = (value: string) => {
        setSearch(value);
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => navigate({ search: value }), 400);
    };

    const handleFollowUpWindow = (value: string) => {
        navigate({ follow_up_window: filters.follow_up_window === value ? null : value });
    };

    const activeStatusTab = filters.priority === 'contact_today' && filters.status === 'all'
        ? 'today'
        : filters.status;

    return (
        <div className="rounded-xl border border-sidebar-border/70 bg-white shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
            {/* Title bar */}
            <div className="flex flex-wrap items-center justify-between gap-3 px-5 pt-4 pb-3">
                <div className="flex items-center gap-3">
                    <div className="flex size-9 items-center justify-center rounded-lg bg-neutral-900 dark:bg-white">
                        <Target className="size-5 text-white dark:text-neutral-900" />
                    </div>
                    <div>
                        <h1 className="text-lg font-semibold leading-tight">Centro de Ataque Comercial</h1>
                        <p className="text-xs text-muted-foreground">
                            {totalFiltered} lead{totalFiltered !== 1 ? 's' : ''} en vista
                            {todayCount > 0 && (
                                <span className="ml-2 font-semibold text-rose-600 dark:text-rose-400">
                                    🔥 {todayCount} para hoy
                                </span>
                            )}
                        </p>
                    </div>
                </div>

                {/* Search */}
                <div className="relative w-full sm:w-56">
                    <Search className="absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-muted-foreground" />
                    <input
                        type="search"
                        placeholder="Buscar lead..."
                        value={search}
                        onChange={(e) => handleSearch(e.target.value)}
                        className="w-full rounded-lg border border-input bg-transparent py-1.5 pl-8 pr-3 text-sm shadow-xs placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-[3px] focus:ring-ring/50 dark:bg-neutral-900"
                    />
                </div>
            </div>

            {/* Status filter tabs */}
            <div className="flex flex-wrap items-center gap-1.5 px-5 pb-2">
                {statusTabs.map((tab) => (
                    <TabPill
                        key={tab.value}
                        active={activeStatusTab === tab.value}
                        onClick={() => handleStatusTab(tab.value)}
                        emoji={tab.emoji}
                        label={tab.label}
                        count={tab.count}
                    />
                ))}
            </div>

            {/* Priority filter row */}
            <div className="flex flex-wrap items-center gap-1.5 border-t border-dashed px-5 py-2">
                <span className="mr-1 text-[11px] font-medium text-muted-foreground">Prioridad:</span>
                {priorityTabs.map((tab) => (
                    <TabPill
                        key={tab.value}
                        active={filters.priority === tab.value}
                        onClick={() => handlePriorityTab(tab.value)}
                        emoji={tab.emoji}
                        label={tab.label}
                        count={tab.count}
                        small
                    />
                ))}
            </div>

            {/* Quick filters row */}
            <div className="flex flex-wrap items-center gap-1.5 border-t border-dashed px-5 py-2">
                <span className="mr-1 text-[11px] font-medium text-muted-foreground">Rápido:</span>
                {([
                    { value: 'actionable', label: 'Accionables', emoji: '⚡' },
                    { value: 'no_channel', label: 'Sin canal', emoji: '❌' },
                    { value: 'overdue_followup', label: 'Follow-up vencido', emoji: '⏰' },
                    { value: 'waiting', label: 'Esperando respuesta', emoji: '⏳' },
                ] as const).map((qf) => (
                    <button
                        key={qf.value}
                        type="button"
                        onClick={() => handleQuickFilter(qf.value)}
                        className={cn(
                            'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-medium transition-colors',
                            filters.quick_filter === qf.value
                                ? 'border-amber-500 bg-amber-50 text-amber-700 dark:border-amber-600 dark:bg-amber-950/40 dark:text-amber-300'
                                : 'border-sidebar-border/70 bg-white text-foreground hover:bg-neutral-50 dark:bg-neutral-950 dark:hover:bg-neutral-900',
                        )}
                    >
                        <span>{qf.emoji}</span>
                        <span>{qf.label}</span>
                    </button>
                ))}
            </div>

            {/* Follow-up calendar windows */}
            <div className="flex flex-wrap items-center gap-1.5 border-t border-dashed px-5 py-2">
                <span className="mr-1 text-[11px] font-medium text-muted-foreground">Agenda:</span>
                {([
                    { value: 'today', label: 'Hoy', emoji: '📅' },
                    { value: 'tomorrow', label: 'Mañana', emoji: '🌤️' },
                    { value: 'this_week', label: 'Semana', emoji: '🗓️' },
                    { value: 'this_month', label: 'Mes', emoji: '📆' },
                    { value: 'next_7_days', label: '+7 días', emoji: '7️⃣' },
                    { value: 'next_30_days', label: '+30 días', emoji: '3️⃣' },
                    { value: 'overdue', label: 'Vencidos', emoji: '⏰' },
                    { value: 'without_date', label: 'Sin fecha', emoji: '➖' },
                ] as const).map((windowFilter) => (
                    <button
                        key={windowFilter.value}
                        type="button"
                        onClick={() => handleFollowUpWindow(windowFilter.value)}
                        className={cn(
                            'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-medium transition-colors',
                            filters.follow_up_window === windowFilter.value
                                ? 'border-sky-500 bg-sky-50 text-sky-700 dark:border-sky-600 dark:bg-sky-950/40 dark:text-sky-300'
                                : 'border-sidebar-border/70 bg-white text-foreground hover:bg-neutral-50 dark:bg-neutral-950 dark:hover:bg-neutral-900',
                        )}
                    >
                        <span>{windowFilter.emoji}</span>
                        <span>{windowFilter.label}</span>
                    </button>
                ))}
            </div>

            {/* KPIs bar */}
            <div className="grid grid-cols-2 gap-px border-t bg-neutral-100 sm:grid-cols-3 xl:grid-cols-6 dark:bg-neutral-800">
                <KpiBox icon={<MessageSquare className="size-3.5 text-blue-500" />} label="Contactados hoy" value={kpis.contacted_today} />
                <KpiBox icon={<AlertTriangle className="size-3.5 text-amber-500" />} label="Follow-up vencido" value={kpis.overdue_follow_ups} highlight={kpis.overdue_follow_ups > 0} />
                <KpiBox icon={<Clock className="size-3.5 text-amber-500" />} label="Vencen hoy" value={kpis.follow_ups_due_today} highlight={kpis.follow_ups_due_today > 0} />
                <KpiBox icon={<CheckCircle2 className="size-3.5 text-emerald-500" />} label="Seguimientos hoy" value={kpis.follow_ups_completed_today} />
                <KpiBox icon={<AlertTriangle className="size-3.5 text-rose-500" />} label="Espera +48h" value={kpis.waiting_response_stale_48h} highlight={kpis.waiting_response_stale_48h > 0} />
                <KpiBox icon={<CheckCircle2 className="size-3.5 text-emerald-500" />} label="Cerrados (semana)" value={kpis.closed_this_week} />
            </div>
        </div>
    );
}

function TabPill({
    active,
    onClick,
    emoji,
    label,
    count,
    small,
}: {
    active: boolean;
    onClick: () => void;
    emoji?: string;
    label: string;
    count: number;
    small?: boolean;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'inline-flex items-center gap-1 rounded-full border font-medium transition-colors',
                small ? 'px-2 py-0.5 text-[11px]' : 'px-2.5 py-1 text-xs',
                active
                    ? 'border-neutral-900 bg-neutral-900 text-white dark:border-white dark:bg-white dark:text-neutral-900'
                    : 'border-sidebar-border/70 bg-white text-foreground hover:bg-neutral-50 dark:bg-neutral-950 dark:hover:bg-neutral-900',
            )}
        >
            {emoji && <span>{emoji}</span>}
            <span>{label}</span>
            <span className={cn(
                'rounded-full px-1 py-px text-[10px]',
                active ? 'bg-white/20 dark:bg-black/20' : 'bg-neutral-100 dark:bg-neutral-800',
            )}>
                {count}
            </span>
        </button>
    );
}

function KpiBox({
    icon,
    label,
    value,
    highlight,
}: {
    icon: React.ReactNode;
    label: string;
    value: number;
    highlight?: boolean;
}) {
    return (
        <div className={cn(
            'flex items-center gap-2 bg-white px-3 py-2 dark:bg-neutral-950',
            highlight && 'bg-amber-50/50 dark:bg-amber-950/20',
        )}>
            {icon}
            <div className="min-w-0">
                <p className={cn(
                    'text-sm font-bold tabular-nums',
                    highlight && 'text-amber-600 dark:text-amber-400',
                )}>{value}</p>
                <p className="truncate text-[10px] text-muted-foreground">{label}</p>
            </div>
        </div>
    );
}
