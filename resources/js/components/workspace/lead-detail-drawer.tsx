import {
    ArrowRight,
    Calendar,
    Check,
    ChevronDown,
    Copy,
    ExternalLink,
    Globe,
    Plus,
    Mail,
    MapPin,
    Phone,
    Save,
    Send,
    User,
    Zap,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import type { QuickDetailData, WorkspaceCard } from '@/types/workspace';

type EditableContact = {
    id: number | null;
    contact_name: string;
    email: string;
    phone: string;
    whatsapp: string;
    contact_form_url: string;
};

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    card: WorkspaceCard | null;
    onContactNow: (card: WorkspaceCard) => void;
};

export function LeadDetailDrawer({ open, onOpenChange, card, onContactNow }: Props) {
    const [detail, setDetail] = useState<QuickDetailData | null>(null);
    const [loading, setLoading] = useState(false);
    const [loadError, setLoadError] = useState<string | null>(null);

    const fetchDetail = useCallback(async (leadId: number) => {
        setLoading(true);
        setDetail(null);
        setLoadError(null);
        try {
            const response = await fetch(`/endex/workspace/leads/${leadId}/detail`, {
                headers: { Accept: 'application/json' },
            });
            if (response.ok) {
                const data: QuickDetailData = await response.json();
                setDetail(data);
            } else {
                setLoadError('No se pudo cargar el detalle real del lead.');
            }
        } catch {
            setLoadError('Error de red al cargar el detalle del lead.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        if (open && card) {
            fetchDetail(card.id);
        }
        if (!open) {
            setDetail(null);
        }
    }, [open, card, fetchDetail]);

    const handleContactsSaved = useCallback((payload: { website_url: string | null; contact_list: Array<{
        id: number;
        contact_name: string | null;
        email: string | null;
        phone: string | null;
        whatsapp: string | null;
        contact_form_url: string | null;
    }> }) => {
        setDetail((prev) => {
            if (!prev) return prev;

            const updatedList = payload.contact_list;
            const first = updatedList[0] ?? null;

            return {
                ...prev,
                website_url: payload.website_url,
                contact: {
                    ...prev.contact,
                    contact_name: first?.contact_name ?? null,
                    email: first?.email ?? null,
                    phone: first?.phone ?? null,
                    whatsapp: first?.whatsapp ?? null,
                    contact_form_url: first?.contact_form_url ?? null,
                },
                contact_list: updatedList,
            };
        });
    }, []);

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent side="right" className="w-full overflow-y-auto sm:max-w-lg">
                <SheetHeader className="pb-0">
                    <SheetTitle className="text-base">
                        {card?.company_name ?? 'Detalle del lead'}
                    </SheetTitle>
                    <SheetDescription>
                        {card ? [card.city, card.sector].filter(Boolean).join(' · ') : ''}
                    </SheetDescription>
                </SheetHeader>

                {loading && <DrawerSkeleton />}

                {!loading && !detail && open && (
                    <div className="space-y-3 px-4 pb-6">
                        <div className="rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
                            <p className="font-medium">Detalle no disponible</p>
                            <p>{loadError ?? 'No llegaron datos reales para este lead.'}</p>
                        </div>
                        <div className="rounded-md border border-dashed p-3 text-xs text-muted-foreground">
                            Verifica que existan score, mensaje, contactos y findings en base de datos para este lead.
                        </div>
                    </div>
                )}

                {!loading && detail && (
                    <div className="space-y-4 px-4 pb-6">
                        {/* === RESUMEN EJECUTIVO === */}
                        <div className="rounded-lg border bg-neutral-50/50 p-3 dark:bg-neutral-900/50">
                            {/* Score + Status + Channel */}
                            <div className="flex items-center gap-3 mb-3">
                                <div className="grid grid-cols-4 gap-1.5 flex-1">
                                    <ScoreBox label="Total" value={detail.score.total} />
                                    <ScoreBox label="Urgencia" value={detail.score.urgency} />
                                    <ScoreBox label="Fit" value={detail.score.fit} />
                                    <ScoreBox label="Capacidad" value={detail.score.payment_capacity} />
                                </div>
                            </div>

                            <dl className="space-y-1.5 text-xs">
                                <DetailRow label="Estado" value={detail.commercial.status_label} />
                                <DetailRow label="Canal" value={detail.commercial.recommended_channel ?? 'Sin canal'} />
                                {detail.commercial.primary_problem && (
                                    <DetailRow icon={<Zap className="size-3.5 text-amber-500" />} label="Problema" value={detail.commercial.primary_problem} />
                                )}
                                {detail.commercial.sales_angle && (
                                    <DetailRow icon={<Send className="size-3.5 text-blue-500" />} label="Ángulo" value={detail.commercial.sales_angle} />
                                )}
                                {detail.commercial.quick_tip && (
                                    <DetailRow icon={<Zap className="size-3.5 text-emerald-500" />} label="Tip" value={detail.commercial.quick_tip} />
                                )}
                            </dl>

                            {/* Next step from card */}
                            {card?.next_step && (
                                <p className="mt-2 flex items-center gap-1.5 rounded bg-blue-50 px-2 py-1 text-[11px] text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">
                                    <ArrowRight className="size-3 shrink-0" />
                                    {card.next_step}
                                </p>
                            )}

                            <p className="mt-2 text-[11px] text-muted-foreground">
                                Mensaje efectivo: {detail.effective_message.source === 'human_edited'
                                    ? 'Humano'
                                    : detail.effective_message.source === 'ai_generated'
                                        ? 'IA'
                                        : 'Fallback'}
                                {detail.effective_message.updated_at
                                    ? ` · ${new Date(detail.effective_message.updated_at).toLocaleString('es-MX')}`
                                    : ' · Sin fecha de actualizacion'}
                            </p>
                        </div>

                        {/* CTA buttons */}
                        <div className="flex items-center gap-2">
                            {card && card.quick_actions.can_contact_now && (
                                <Button size="sm" className="flex-1" onClick={() => onContactNow(card)}>
                                    <Send className="size-3.5" />
                                    Contactar ahora
                                </Button>
                            )}
                            {detail.review_url && (
                                <Button size="sm" variant="outline" asChild>
                                    <a href={detail.review_url} target="_blank" rel="noreferrer">
                                        <ExternalLink className="size-3.5" />
                                        Ver propuesta
                                    </a>
                                </Button>
                            )}
                        </div>

                        <Separator />

                        {/* === DETALLE EXPANDIBLE === */}

                        {/* Contact info */}
                        <CollapsibleSection title="Contactos" defaultOpen>
                            <LeadContactEditor detail={detail} onSaved={handleContactsSaved} />
                        </CollapsibleSection>

                        {detail.commercial.commercial_notes && (
                            <CollapsibleSection title="Notas comerciales">
                                <p className="whitespace-pre-wrap text-xs text-muted-foreground">
                                    {detail.commercial.commercial_notes}
                                </p>
                            </CollapsibleSection>
                        )}

                        {/* Dates */}
                        {(detail.commercial.last_contacted_at || detail.commercial.next_follow_up_at) && (
                            <CollapsibleSection title="Fechas">
                                <dl className="space-y-1.5 text-xs">
                                    {detail.commercial.last_contacted_at && (
                                        <DetailRow
                                            icon={<Calendar className="size-3.5" />}
                                            label="Último contacto"
                                            value={new Date(detail.commercial.last_contacted_at).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' })}
                                        />
                                    )}
                                    {detail.commercial.next_follow_up_at && (
                                        <DetailRow
                                            icon={<Calendar className="size-3.5 text-amber-500" />}
                                            label="Próximo seguimiento"
                                            value={new Date(detail.commercial.next_follow_up_at).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' })}
                                        />
                                    )}
                                </dl>
                            </CollapsibleSection>
                        )}

                        {/* Message */}
                        {detail.message && (
                            <CollapsibleSection title="Mensaje preparado">
                                {detail.message.subject && (
                                    <p className="mb-1 text-xs font-medium">{detail.message.subject}</p>
                                )}
                                <pre className="max-h-48 overflow-y-auto whitespace-pre-wrap rounded-md bg-neutral-50 p-3 text-xs text-foreground dark:bg-neutral-900">
                                    {detail.message.body}
                                </pre>
                                <p className="mt-1 text-[10px] text-muted-foreground">
                                    Fuente: {detail.message.source === 'human_edited' ? 'Humano' : detail.message.source === 'ai_generated' ? 'IA' : 'Fallback'}
                                    {detail.message.updated_at ? ` · ${new Date(detail.message.updated_at).toLocaleString('es-MX')}` : ''}
                                </p>
                            </CollapsibleSection>
                        )}

                        {!detail.message && (
                            <CollapsibleSection title="Mensaje preparado">
                                <p className="text-xs text-muted-foreground">
                                    No hay mensaje persistido; se usara el fallback hasta que edites uno.
                                </p>
                            </CollapsibleSection>
                        )}

                        {/* Offer */}
                        {detail.offer && (
                            <CollapsibleSection title="Oferta">
                                <p className="text-xs">{detail.offer.type}</p>
                                {detail.offer.summary && <p className="text-xs text-muted-foreground">{detail.offer.summary}</p>}
                                {(detail.offer.price_min || detail.offer.price_max) && (
                                    <p className="text-xs font-medium">
                                        ${detail.offer.price_min?.toLocaleString('es-MX')} – ${detail.offer.price_max?.toLocaleString('es-MX')} MXN
                                    </p>
                                )}
                            </CollapsibleSection>
                        )}

                        {/* Insights */}
                        {(detail.insights.argos_summary || detail.insights.hefesto_summary) && (
                            <CollapsibleSection title="Insights de IA">
                                {detail.insights.argos_summary && (
                                    <div className="mb-2">
                                        <p className="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Argos (Presencia web)</p>
                                        <p className="mt-0.5 text-xs text-foreground">{detail.insights.argos_summary}</p>
                                    </div>
                                )}
                                {detail.insights.hefesto_summary && (
                                    <div>
                                        <p className="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Hefesto (Análisis técnico)</p>
                                        <p className="mt-0.5 text-xs text-foreground">{detail.insights.hefesto_summary}</p>
                                    </div>
                                )}
                                <a
                                    href={detail.insights.maps_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="mt-2 inline-flex items-center gap-1 text-[11px] text-blue-600 hover:underline dark:text-blue-400"
                                >
                                    <MapPin className="size-3" />
                                    Ver en Google Maps
                                </a>
                            </CollapsibleSection>
                        )}

                        <CollapsibleSection title="Findings reales de agentes">
                            {detail.findings.length > 0 ? (
                                <div className="space-y-1.5">
                                    {detail.findings.map((finding) => (
                                        <div key={finding.id} className="rounded border p-2 text-[11px]">
                                            <p className="font-medium">{finding.agent_name} {finding.stage ? `· ${finding.stage}` : ''}</p>
                                            <p className="text-muted-foreground">{finding.summary ?? 'Sin resumen'}</p>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-xs text-muted-foreground">No hay findings disponibles para este lead.</p>
                            )}
                        </CollapsibleSection>

                        {/* Recent actions */}
                        {detail.recent_actions.length > 0 && (
                            <CollapsibleSection title="Acciones recientes">
                                <div className="space-y-1.5">
                                    {detail.recent_actions.map((action, i) => (
                                        <div key={i} className="flex items-start gap-2 text-[11px]">
                                            <span className="mt-0.5 size-1.5 shrink-0 rounded-full bg-muted-foreground/50" />
                                            <div className="min-w-0">
                                                <span className="font-medium">{action.label}</span>
                                                {action.channel && <span className="text-muted-foreground"> · {action.channel}</span>}
                                                {action.occurred_at && (
                                                    <span className="text-muted-foreground">
                                                        {' '}· {new Date(action.occurred_at).toLocaleDateString('es-MX', { day: '2-digit', month: 'short' })}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CollapsibleSection>
                        )}

                        {/* Status history */}
                        {detail.status_history.length > 0 && (
                            <CollapsibleSection title="Historial de estados">
                                <div className="space-y-1">
                                    {detail.status_history.map((h, i) => (
                                        <p key={i} className="text-[11px] text-muted-foreground">
                                            {h.from} → {h.to}
                                            {h.reason && ` — ${h.reason}`}
                                            {h.changed_at && (
                                                <span> · {new Date(h.changed_at).toLocaleDateString('es-MX', { day: '2-digit', month: 'short' })}</span>
                                            )}
                                        </p>
                                    ))}
                                </div>
                            </CollapsibleSection>
                        )}
                    </div>
                )}
            </SheetContent>
        </Sheet>
    );
}

/* Sub-components */

function CollapsibleSection({ title, children, defaultOpen = false }: { title: string; children: React.ReactNode; defaultOpen?: boolean }) {
    const [open, setOpen] = useState(defaultOpen);
    return (
        <div className="border-t border-dashed pt-2">
            <button
                type="button"
                onClick={() => setOpen(!open)}
                className="flex w-full items-center justify-between text-[11px] font-semibold uppercase tracking-wider text-muted-foreground hover:text-foreground transition-colors"
            >
                {title}
                <ChevronDown className={cn('size-3.5 transition-transform', open && 'rotate-180')} />
            </button>
            {open && <div className="mt-2">{children}</div>}
        </div>
    );
}

function ScoreBox({ label, value }: { label: string; value: number | null }) {
    return (
        <div className="rounded-md bg-neutral-50 p-2 text-center dark:bg-neutral-900">
            <p className="text-lg font-bold tabular-nums text-foreground">{value ?? '—'}</p>
            <p className="text-[10px] text-muted-foreground">{label}</p>
        </div>
    );
}

function DetailRow({ icon, label, value }: { icon?: React.ReactNode; label: string; value: string }) {
    return (
        <div className="flex items-start gap-2">
            {icon && <span className="mt-0.5 shrink-0">{icon}</span>}
            <span className="shrink-0 font-medium text-muted-foreground w-28">{label}</span>
            <span className="text-foreground">{value}</span>
        </div>
    );
}

function ContactLine({ icon, value, href }: { icon: React.ReactNode; value: string; href?: string }) {
    const content = (
        <span className="flex items-center gap-2">
            {icon}
            <span className="truncate">{value}</span>
        </span>
    );

    if (href) {
        return (
            <a href={href} target="_blank" rel="noreferrer" className="flex items-center gap-2 text-blue-600 hover:underline dark:text-blue-400">
                {icon}
                <span className="truncate">{value}</span>
            </a>
        );
    }

    return <div className="flex items-center gap-2 text-foreground">{icon}<span className="truncate">{value}</span></div>;
}

function LeadContactEditor({
    detail,
    onSaved,
}: {
    detail: QuickDetailData;
    onSaved: (payload: { website_url: string | null; contact_list: Array<{
        id: number;
        contact_name: string | null;
        email: string | null;
        phone: string | null;
        whatsapp: string | null;
        contact_form_url: string | null;
    }> }) => void;
}) {
    const [websiteUrl, setWebsiteUrl] = useState(detail.website_url ?? '');
    const [rows, setRows] = useState<EditableContact[]>([]);
    const [saving, setSaving] = useState(false);
    const [savedMessage, setSavedMessage] = useState<string | null>(null);

    useEffect(() => {
        setWebsiteUrl(detail.website_url ?? '');

        if (detail.contact_list.length > 0) {
            setRows(detail.contact_list.map((contact) => ({
                id: contact.id,
                contact_name: contact.contact_name ?? '',
                email: contact.email ?? '',
                phone: contact.phone ?? '',
                whatsapp: contact.whatsapp ?? '',
                contact_form_url: contact.contact_form_url ?? '',
            })));
        } else {
            setRows([emptyContactRow()]);
        }

        setSavedMessage(null);
    }, [detail]);

    const setRow = (index: number, patch: Partial<EditableContact>) => {
        setRows((prev) => prev.map((row, i) => (i === index ? { ...row, ...patch } : row)));
    };

    const removeRow = (index: number) => {
        setRows((prev) => (prev.length > 1 ? prev.filter((_, i) => i !== index) : [emptyContactRow()]));
    };

    const addRow = () => {
        setRows((prev) => [...prev, emptyContactRow()]);
    };

    const copyValue = async (value: string, label: string) => {
        const clean = value.trim();
        if (clean === '') {
            setSavedMessage(`No hay ${label} para copiar.`);
            return;
        }

        try {
            await navigator.clipboard.writeText(clean);
            setSavedMessage(`${label} copiado.`);
        } catch {
            setSavedMessage(`No se pudo copiar ${label}.`);
        }
    };

    const openAction = (url: string | null, label: string) => {
        if (!url) {
            setSavedMessage(`No hay accion disponible para ${label}.`);
            return;
        }

        window.open(url, '_blank', 'noopener');
    };

    const save = async () => {
        if (saving) return;

        setSaving(true);
        setSavedMessage(null);

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const response = await fetch(`/endex/workspace/leads/${detail.id}/contacts`, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({
                    website_url: websiteUrl,
                    contacts: rows,
                }),
            });

            if (!response.ok) {
                throw new Error('request_failed');
            }

            const data = await response.json();
            onSaved({
                website_url: data.website_url ?? null,
                contact_list: data.contact_list ?? [],
            });
            setSavedMessage('Contactos guardados.');
        } catch {
            setSavedMessage('No se pudo guardar. Revisa telefono/correo/url e intenta de nuevo.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="space-y-3">
            <div className="grid gap-1">
                <label className="text-[11px] font-medium text-muted-foreground">Pagina web</label>
                <div className="flex items-center gap-1.5">
                    <FieldActions
                        onCopy={() => copyValue(websiteUrl, 'pagina web')}
                        onAction={() => openAction(normalizeUrl(websiteUrl), 'pagina web')}
                    />
                    <input
                        type="url"
                        placeholder="https://empresa.com"
                        value={websiteUrl}
                        onChange={(e) => setWebsiteUrl(e.target.value)}
                        className="h-8 flex-1 rounded-md border border-input bg-transparent px-2 text-xs shadow-xs focus:border-ring focus:outline-none focus:ring-[2px] focus:ring-ring/40"
                    />
                </div>
            </div>

            <div className="space-y-2">
                {rows.map((row, index) => (
                    <div key={`${row.id ?? 'new'}-${index}`} className="rounded-md border p-2">
                        <div className="mb-1 flex items-center justify-between">
                            <p className="text-[11px] font-medium text-muted-foreground">Contacto {index + 1}</p>
                            <button
                                type="button"
                                onClick={() => removeRow(index)}
                                className="text-[10px] text-muted-foreground hover:text-foreground"
                            >
                                Quitar
                            </button>
                        </div>
                        <div className="grid gap-1.5">
                            <input
                                type="text"
                                placeholder="Nombre de contacto"
                                value={row.contact_name}
                                onChange={(e) => setRow(index, { contact_name: e.target.value })}
                                className="h-8 rounded-md border border-input bg-transparent px-2 text-xs"
                            />
                            <div className="flex items-center gap-1.5">
                                <FieldActions
                                    onCopy={() => copyValue(row.phone, 'telefono')}
                                    onAction={() => openAction(toTelUrl(row.phone), 'telefono')}
                                />
                                <input
                                    type="tel"
                                    placeholder="Telefono"
                                    value={row.phone}
                                    onChange={(e) => setRow(index, { phone: e.target.value })}
                                    className="h-8 flex-1 rounded-md border border-input bg-transparent px-2 text-xs"
                                />
                            </div>
                            <div className="flex items-center gap-1.5">
                                <FieldActions
                                    onCopy={() => copyValue(row.email, 'correo')}
                                    onAction={() => openAction(toMailToUrl(row.email), 'correo')}
                                />
                                <input
                                    type="email"
                                    placeholder="Correo"
                                    value={row.email}
                                    onChange={(e) => setRow(index, { email: e.target.value })}
                                    className="h-8 flex-1 rounded-md border border-input bg-transparent px-2 text-xs"
                                />
                            </div>
                            <div className="flex items-center gap-1.5">
                                <FieldActions
                                    onCopy={() => copyValue(row.whatsapp, 'whatsapp')}
                                    onAction={() => openAction(toWhatsappUrl(row.whatsapp), 'whatsapp')}
                                />
                                <input
                                    type="tel"
                                    placeholder="WhatsApp"
                                    value={row.whatsapp}
                                    onChange={(e) => setRow(index, { whatsapp: e.target.value })}
                                    className="h-8 flex-1 rounded-md border border-input bg-transparent px-2 text-xs"
                                />
                            </div>
                            <div className="flex items-center gap-1.5">
                                <FieldActions
                                    onCopy={() => copyValue(row.contact_form_url, 'maps o formulario')}
                                    onAction={() => openAction(toMapsOrUrl(row.contact_form_url), 'maps o formulario')}
                                />
                                <input
                                    type="url"
                                    placeholder="Formulario web o perfil"
                                    value={row.contact_form_url}
                                    onChange={(e) => setRow(index, { contact_form_url: e.target.value })}
                                    className="h-8 flex-1 rounded-md border border-input bg-transparent px-2 text-xs"
                                />
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="flex flex-wrap items-center gap-2">
                <Button type="button" variant="outline" size="sm" onClick={addRow}>
                    <Plus className="size-3.5" />
                    Agregar telefono/correo
                </Button>
                <Button type="button" size="sm" onClick={save} disabled={saving}>
                    {saving ? <Save className="size-3.5 animate-pulse" /> : <Check className="size-3.5" />}
                    {saving ? 'Guardando...' : 'Guardar contactos'}
                </Button>
                {savedMessage && (
                    <span className="text-[11px] text-muted-foreground">{savedMessage}</span>
                )}
            </div>
        </div>
    );
}

function FieldActions({
    onCopy,
    onAction,
}: {
    onCopy: () => void;
    onAction: () => void;
}) {
    return (
        <div className="flex shrink-0 items-center gap-1">
            <Button
                type="button"
                variant="outline"
                size="icon"
                className="size-8"
                onClick={onCopy}
                title="Copiar"
                aria-label="Copiar"
            >
                <Copy className="size-3.5" />
            </Button>
            <Button
                type="button"
                variant="outline"
                size="icon"
                className="size-8"
                onClick={onAction}
                title="Accion"
                aria-label="Accion"
            >
                <ExternalLink className="size-3.5" />
            </Button>
        </div>
    );
}

function normalizeUrl(raw: string): string | null {
    const value = raw.trim();
    if (value === '') return null;
    if (value.startsWith('http://') || value.startsWith('https://')) return value;
    return `https://${value}`;
}

function toTelUrl(phone: string): string | null {
    const clean = phone.trim();
    if (clean === '') return null;
    return `tel:${clean}`;
}

function toMailToUrl(email: string): string | null {
    const clean = email.trim();
    if (clean === '') return null;
    return `mailto:${clean}`;
}

function toWhatsappUrl(raw: string): string | null {
    const digits = raw.replace(/\D/g, '');
    if (digits === '') return null;
    return `https://wa.me/${digits}`;
}

function toMapsOrUrl(raw: string): string | null {
    return normalizeUrl(raw);
}

function emptyContactRow(): EditableContact {
    return {
        id: null,
        contact_name: '',
        email: '',
        phone: '',
        whatsapp: '',
        contact_form_url: '',
    };
}

function DrawerSkeleton() {
    return (
        <div className="space-y-4 px-4">
            <div className="grid grid-cols-4 gap-2">
                {[...Array(4)].map((_, i) => <Skeleton key={i} className="h-14 rounded-md" />)}
            </div>
            <Skeleton className="h-4 w-1/3" />
            <Skeleton className="h-20" />
            <Skeleton className="h-4 w-1/4" />
            <Skeleton className="h-32" />
            <Skeleton className="h-4 w-1/2" />
            <Skeleton className="h-16" />
        </div>
    );
}
