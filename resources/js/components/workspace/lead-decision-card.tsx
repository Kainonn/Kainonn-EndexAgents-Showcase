import {
    ArrowRight,
    Calendar,
    ClipboardCopy,
    Eye,
    Mail,
    MessageCircle,
    Pencil,
    Phone,
    Send,
    Zap,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import type { StatusOptions, WorkspaceCard } from '@/types/workspace';
import { LeadPriorityBadge } from './lead-priority-badge';
import { LeadQuickNote } from './lead-quick-note';
import { LeadQuickStatusSelector } from './lead-quick-status-selector';
import { LeadStatusBadge } from './lead-status-badge';

type Props = {
    card: WorkspaceCard;
    statusOptions: StatusOptions;
    onContactNow: (card: WorkspaceCard) => void;
    onEditMessage: (card: WorkspaceCard) => void;
    onViewDetail: (card: WorkspaceCard) => void;
    onManageLead: (card: WorkspaceCard) => void;
};

export function LeadDecisionCard({
    card,
    statusOptions,
    onContactNow,
    onEditMessage,
    onViewDetail,
    onManageLead,
}: Props) {
    const isUrgent = card.operational_priority === 'contact_today';
    const hasFollowUp = card.next_follow_up_at !== null;
    const followUpDate = hasFollowUp ? new Date(card.next_follow_up_at!).toLocaleDateString('es-MX', { day: '2-digit', month: 'short' }) : null;

    return (
        <div
            className={cn(
                'group relative flex flex-col rounded-xl border bg-card text-card-foreground shadow-sm transition-shadow hover:shadow-md',
                isUrgent && 'border-rose-200 dark:border-rose-800/60',
                card.follow_up_overdue && 'ring-2 ring-amber-300 dark:ring-amber-700',
            )}
        >
            {/* Header: Company + Badges */}
            <div className="flex items-start justify-between gap-2 px-4 pt-4 pb-2">
                <div className="min-w-0 flex-1">
                    <h3 className="truncate text-sm font-semibold leading-tight text-foreground">
                        {card.company_name}
                    </h3>
                    <p className="mt-0.5 truncate text-[11px] text-muted-foreground">
                        {[card.city, card.sector].filter(Boolean).join(' · ') || 'Sin ubicación'}
                    </p>
                </div>
                <div className="flex flex-col items-end gap-1 shrink-0">
                    {card.score !== null && (
                        <span className={cn(
                            'inline-flex items-center rounded-full px-1.5 py-0.5 text-[11px] font-bold tabular-nums',
                            card.score >= 65
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300'
                                : card.score >= 35
                                  ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300'
                                  : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
                        )}>
                            {card.score}
                        </span>
                    )}
                </div>
            </div>

            {/* Badges row */}
            <div className="flex flex-wrap items-center gap-1.5 px-4 pb-2">
                <LeadPriorityBadge
                    priority={card.operational_priority}
                    label={card.operational_priority_label}
                    emoji={card.operational_priority_emoji}
                />
                <LeadStatusBadge
                    status={card.commercial_status}
                    label={card.commercial_status_label}
                    color={card.commercial_status_color}
                />
                {card.recommended_channel !== 'none' && (
                    <span className="inline-flex items-center gap-0.5 text-[11px] text-muted-foreground">
                        {card.recommended_channel_emoji} {card.recommended_channel_label}
                    </span>
                )}
            </div>

            {/* Commercial intelligence */}
            <div className="space-y-1 border-t border-dashed px-4 py-2.5">
                {card.primary_problem && (
                    <div className="flex items-start gap-1.5">
                        <Zap className="mt-0.5 size-3 shrink-0 text-amber-500" />
                        <p className="line-clamp-1 text-xs text-foreground">{card.primary_problem}</p>
                    </div>
                )}
                {card.sales_angle && (
                    <div className="flex items-start gap-1.5">
                        <Send className="mt-0.5 size-3 shrink-0 text-blue-500" />
                        <p className="line-clamp-1 text-xs text-foreground">{card.sales_angle}</p>
                    </div>
                )}
                {card.ready_message_preview && (
                    <div className="flex items-start gap-1.5">
                        <MessageCircle className="mt-0.5 size-3 shrink-0 text-muted-foreground" />
                        <div className="min-w-0">
                            <p className="line-clamp-2 text-[11px] text-muted-foreground italic">
                                &ldquo;{card.effective_message_preview}&rdquo;
                            </p>
                            <p className="mt-0.5 text-[10px] text-muted-foreground">
                                Fuente:{' '}
                                {card.effective_message_source === 'human_edited'
                                    ? 'Humano'
                                    : card.effective_message_source === 'ai_generated'
                                        ? 'IA'
                                        : 'Fallback'}
                                {card.effective_message_updated_at
                                    ? ` · ${new Date(card.effective_message_updated_at).toLocaleDateString('es-MX', { day: '2-digit', month: 'short' })}`
                                    : ''}
                            </p>
                        </div>
                    </div>
                )}
                {card.quick_tip && (
                    <p className="rounded bg-amber-50 px-2 py-1 text-[11px] text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">
                        💡 {card.quick_tip}
                    </p>
                )}
            </div>

            {/* Meta row */}
            <div className="flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-dashed px-4 py-2 text-[11px] text-muted-foreground">
                {card.last_action_summary && (
                    <span className="truncate max-w-[200px]">{card.last_action_summary}</span>
                )}
                {hasFollowUp && (
                    <span className={cn('flex items-center gap-0.5', card.follow_up_overdue && 'font-semibold text-amber-600 dark:text-amber-400')}>
                        <Calendar className="size-3" />
                        {card.follow_up_overdue ? 'Atrasado: ' : ''}{followUpDate}
                    </span>
                )}
                {card.contact.contact_name && (
                    <span className="flex items-center gap-0.5">
                        <Phone className="size-3" />
                        {card.contact.contact_name}
                    </span>
                )}
            </div>

            {/* Next step */}
            <div className="border-t border-dashed px-4 py-2">
                <p className="flex items-center gap-1.5 text-[11px] text-muted-foreground">
                    <ArrowRight className="size-3 shrink-0 text-blue-500" />
                    <span className="line-clamp-1">{card.next_step}</span>
                </p>
            </div>

            {/* Quick note */}
            <div className="border-t border-dashed px-4 py-2">
                <LeadQuickNote leadId={card.id} currentNote={card.commercial_notes} />
            </div>

            {/* Status selector */}
            <div className="border-t border-dashed px-4 py-2">
                <LeadQuickStatusSelector
                    leadId={card.id}
                    currentStatus={card.commercial_status}
                    statusOptions={statusOptions}
                />
            </div>

            {/* Action buttons */}
            <div className="flex items-center gap-1 border-t px-3 py-2.5">
                <button
                    type="button"
                    onClick={() => onContactNow(card)}
                    disabled={!card.quick_actions.can_contact_now}
                    className={cn(
                        'flex flex-1 items-center justify-center gap-1.5 rounded-md px-2 py-1.5 text-xs font-medium transition-colors',
                        card.quick_actions.can_contact_now
                            ? channelCtaStyle(card.recommended_channel)
                            : 'bg-neutral-100 text-neutral-400 dark:bg-neutral-800 dark:text-neutral-500 cursor-not-allowed',
                    )}
                >
                    {channelCtaIcon(card.recommended_channel)}
                    {channelCtaLabel(card.recommended_channel)}
                </button>
                <button
                    type="button"
                    onClick={() => onManageLead(card)}
                    className="flex items-center justify-center rounded-md border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 transition-colors hover:bg-blue-100 dark:border-blue-900/70 dark:bg-blue-950/30 dark:text-blue-300"
                    title="Registrar gestión"
                >
                    Gestionar
                </button>
                <button
                    type="button"
                    onClick={() => onEditMessage(card)}
                    disabled={!card.quick_actions.can_edit_message}
                    className="flex items-center justify-center rounded-md border px-2 py-1.5 text-xs text-muted-foreground transition-colors hover:bg-accent hover:text-foreground disabled:opacity-40 disabled:cursor-not-allowed"
                    title="Editar mensaje"
                >
                    <Pencil className="size-3.5" />
                </button>
                <button
                    type="button"
                    onClick={() => onViewDetail(card)}
                    className="flex items-center justify-center rounded-md border px-2 py-1.5 text-xs text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                    title="Ver detalle"
                >
                    <Eye className="size-3.5" />
                </button>
            </div>
        </div>
    );
}

function channelCtaLabel(channel: string): string {
    return {
        whatsapp: 'WhatsApp',
        phone: 'Llamar',
        email: 'Email',
    }[channel] ?? 'Copiar mensaje';
}

function channelCtaIcon(channel: string) {
    switch (channel) {
        case 'whatsapp': return <MessageCircle className="size-3.5" />;
        case 'phone': return <Phone className="size-3.5" />;
        case 'email': return <Mail className="size-3.5" />;
        default: return <ClipboardCopy className="size-3.5" />;
    }
}

function channelCtaStyle(channel: string): string {
    switch (channel) {
        case 'whatsapp': return 'bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-600';
        case 'phone': return 'bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600';
        case 'email': return 'bg-violet-600 text-white hover:bg-violet-700 dark:bg-violet-700 dark:hover:bg-violet-600';
        default: return 'bg-neutral-600 text-white hover:bg-neutral-700 dark:bg-neutral-700 dark:hover:bg-neutral-600';
    }
}
