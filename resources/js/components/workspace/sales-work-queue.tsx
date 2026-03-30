import { AlertTriangle, ArrowRight, CalendarClock, CheckSquare, ChevronDown, Clock3, Target } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { WorkQueueItem, WorkspaceCard } from '@/types/workspace';

type Props = {
    items: WorkQueueItem[];
    onContactNow: (card: WorkspaceCard) => void;
    onManageLead: (card: WorkspaceCard) => void;
    onViewDetail: (card: WorkspaceCard) => void;
};

export function SalesWorkQueue({ items, onContactNow, onManageLead, onViewDetail }: Props) {
    const [expanded, setExpanded] = useState(false);

    if (items.length === 0) {
        return null;
    }

    return (
        <section className="rounded-xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
            <div className="mb-3 flex items-start justify-between gap-3">
                <div>
                    <h2 className="flex items-center gap-2 text-sm font-semibold">
                        <Target className="size-4 text-blue-600" />
                        Mi cola de hoy
                    </h2>
                    <p className="text-xs text-muted-foreground">
                        Siguientes {Math.min(items.length, 20)} leads ordenados por SLA y oportunidad de cierre.
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    <div className="hidden sm:flex items-center gap-2 text-[11px] text-muted-foreground">
                        <span className="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-1 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">
                            <AlertTriangle className="size-3" /> Critico
                        </span>
                        <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-1 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">
                            <Clock3 className="size-3" /> Hoy
                        </span>
                    </div>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        className="h-7 px-2 text-[11px]"
                        onClick={() => setExpanded((prev) => !prev)}
                        aria-expanded={expanded}
                    >
                        {expanded ? 'Contraer' : 'Expandir'}
                        <ChevronDown className={cn('size-3 transition-transform', expanded && 'rotate-180')} />
                    </Button>
                </div>
            </div>

            {!expanded && (
                <p className="text-xs text-muted-foreground">
                    Cola contraida. Expande para ver y gestionar {Math.min(items.length, 20)} leads.
                </p>
            )}

            {expanded && (
                <div className="grid gap-2">
                    {items.slice(0, 20).map((item) => (
                    <div
                        key={item.card.id}
                        className={cn(
                            'rounded-lg border px-3 py-2.5',
                            item.sla_tone === 'critical' && 'border-rose-200 bg-rose-50/50 dark:border-rose-900/70 dark:bg-rose-950/20',
                            item.sla_tone === 'warning' && 'border-amber-200 bg-amber-50/50 dark:border-amber-900/70 dark:bg-amber-950/20',
                            item.sla_tone === 'neutral' && 'border-sidebar-border/60 bg-background',
                        )}
                    >
                        <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                            <div className="min-w-0">
                                <p className="truncate text-sm font-semibold text-foreground">{item.card.company_name}</p>
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    {[item.card.city, item.card.sector].filter(Boolean).join(' - ') || 'Sin ubicacion'}
                                </p>
                                <p className="mt-1 inline-flex items-center gap-1 text-xs font-medium text-foreground">
                                    <QueueIcon tone={item.sla_tone} />
                                    {item.queue_reason}
                                </p>
                                <div className="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <span className={cn(
                                        'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold',
                                        item.card.closing_priority === 'ALTO' && 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/30 dark:text-emerald-300',
                                        item.card.closing_priority === 'MEDIO' && 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-300',
                                        item.card.closing_priority === 'BAJO' && 'border-zinc-200 bg-zinc-50 text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300',
                                        item.card.closing_priority === 'INCOMPLETO' && 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-300',
                                    )}>
                                        Cierre: {item.card.closing_priority}
                                    </span>
                                    {item.card.contact_recommendation && (
                                        <span className="text-[11px] text-muted-foreground">
                                            {item.card.contact_recommendation.replaceAll('_', ' ')}
                                        </span>
                                    )}
                                </div>
                            </div>

                            <div className="flex flex-wrap items-center gap-1.5">
                                <Button
                                    size="sm"
                                    onClick={() => onContactNow(item.card)}
                                    disabled={!item.card.quick_actions.can_contact_now}
                                    className="h-7 px-2 text-[11px]"
                                >
                                    Contactar
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => onManageLead(item.card)}
                                    className="h-7 px-2 text-[11px]"
                                >
                                    <CheckSquare className="size-3" />
                                    Completar gestion
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => onViewDetail(item.card)}
                                    className="h-7 px-2 text-[11px]"
                                >
                                    Ver
                                    <ArrowRight className="size-3" />
                                </Button>
                            </div>
                        </div>

                        {item.card.next_follow_up_at && (
                            <p className="mt-2 inline-flex items-center gap-1 text-[11px] text-muted-foreground">
                                <CalendarClock className="size-3" />
                                Follow-up: {new Date(item.card.next_follow_up_at).toLocaleDateString('es-MX', { day: '2-digit', month: 'short' })}
                            </p>
                        )}
                    </div>
                    ))}
                </div>
            )}
        </section>
    );
}

function QueueIcon({ tone }: { tone: WorkQueueItem['sla_tone'] }) {
    if (tone === 'critical') {
        return <AlertTriangle className="size-3.5 text-rose-600" />;
    }

    if (tone === 'warning') {
        return <Clock3 className="size-3.5 text-amber-600" />;
    }

    return <CheckSquare className="size-3.5 text-emerald-600" />;
}
