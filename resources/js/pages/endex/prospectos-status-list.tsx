import { Head, Link, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Prospecto = {
    id: number;
    nombre: string;
    ciudad: string | null;
    estado: string | null;
    telefono: string | null;
    sitio_web: string | null;
    estatus: string;
    notas: string | null;
    lead_id: number | null;
    commercial_status: string;
    score: number | null;
    has_contact: boolean;
    priority: {
        value: 'high' | 'medium' | 'low' | 'unknown';
        label: string;
        color: string;
    };
    review_url: string | null;
    email: string | null;
    generated_message: {
        subject: string | null;
        body: string;
        updated_at: string | null;
    } | null;
};

type ProspectosStatusListProps = {
    prospectos: Prospecto[];
    statusOptions: Record<string, string>;
};

type RowState = {
    estatus: string;
    notas: string;
    dirty: boolean;
    saving: boolean;
};

const getInitialStatusTab = (): string => {
    if (typeof window === 'undefined') {
        return 'all';
    }

    const params = new URLSearchParams(window.location.search);

    return params.get('status') ?? 'all';
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Endex', href: '/dashboard' },
    { title: 'Prospectos', href: '/endex/prospectos' },
    { title: 'Lista estatus', href: '/endex/prospectos/lista-estatus' },
];

export default function ProspectosStatusList({ prospectos, statusOptions }: ProspectosStatusListProps) {
    const [messageModalLeadId, setMessageModalLeadId] = useState<number | null>(null);
    const [sendingLeadId, setSendingLeadId] = useState<number | null>(null);
    const [activeStatusTab, setActiveStatusTab] = useState<string>(getInitialStatusTab);
    const [searchQuery, setSearchQuery] = useState('');
    const [priorityFilter, setPriorityFilter] = useState<Prospecto['priority']['value'] | 'all'>('all');
    const [rows, setRows] = useState<Record<number, RowState>>(() => {
        const mapped: Record<number, RowState> = {};

        prospectos.forEach((prospecto) => {
            const normalizedStatus = statusOptions[prospecto.commercial_status]
                ? prospecto.commercial_status
                : 'new';

            mapped[prospecto.id] = {
                estatus: normalizedStatus,
                notas: prospecto.notas ?? '',
                dirty: false,
                saving: false,
            };
        });

        return mapped;
    });

    const statusEntries = useMemo(() => Object.entries(statusOptions), [statusOptions]);
    const sortedProspectos = useMemo(() => {
        const rank: Record<Prospecto['priority']['value'], number> = {
            high: 0,
            medium: 1,
            low: 2,
            unknown: 3,
        };

        return [...prospectos].sort((a, b) => {
            const priorityDiff = rank[a.priority.value] - rank[b.priority.value];

            if (priorityDiff !== 0) {
                return priorityDiff;
            }

            return (b.score ?? -1) - (a.score ?? -1);
        });
    }, [prospectos]);

    const statusTabs = useMemo(() => {
        const counts: Record<string, number> = {};

        sortedProspectos.forEach((prospecto) => {
            const status = rows[prospecto.id]?.estatus ?? prospecto.commercial_status;
            counts[status] = (counts[status] ?? 0) + 1;
        });

        const tabs = [
            {
                value: 'all',
                label: 'Todos',
                count: sortedProspectos.length,
            },
            ...statusEntries.map(([value, label]) => ({
                value,
                label,
                count: counts[value] ?? 0,
            })),
        ];

        return tabs.filter((tab) => tab.value === 'all' || tab.count > 0);
    }, [rows, sortedProspectos, statusEntries]);

    const selectedStatusTab = statusTabs.some((tab) => tab.value === activeStatusTab)
        ? activeStatusTab
        : 'all';

    const filteredProspectos = useMemo(() => {
        let result = selectedStatusTab === 'all'
            ? sortedProspectos
            : sortedProspectos.filter((prospecto) => {
                const currentStatus = rows[prospecto.id]?.estatus ?? prospecto.commercial_status;
                return currentStatus === selectedStatusTab;
            });

        if (priorityFilter !== 'all') {
            result = result.filter((p) => p.priority.value === priorityFilter);
        }

        const q = searchQuery.trim().toLowerCase();
        if (q) {
            result = result.filter((p) => p.nombre.toLowerCase().includes(q));
        }

        return result;
    }, [rows, selectedStatusTab, sortedProspectos, priorityFilter, searchQuery]);

    const activeMessageProspecto = useMemo(
        () => {
            if (messageModalLeadId === null) {
                return null;
            }

            return prospectos.find((prospecto) => prospecto.lead_id === messageModalLeadId) ?? null;
        },
        [messageModalLeadId, prospectos],
    );

    const { data: messageData, setData: setMessageData, post: postMessage, processing: processingMessage } = useForm({
        subject: '',
        body: '',
    });

    const updateRow = (id: number, values: Partial<RowState>): void => {
        setRows((prev) => ({
            ...prev,
            [id]: {
                ...prev[id],
                ...values,
            },
        }));
    };

    const handleSave = (id: number): void => {
        const row = rows[id];

        if (!row || row.saving) {
            return;
        }

        closeMessageModal();

        updateRow(id, { saving: true });

        router.patch(
            `/endex/prospectos/${id}/status`,
            {
                estatus: row.estatus,
                notas: row.notas,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    updateRow(id, { saving: false, dirty: false });
                    closeMessageModal();
                },
                onError: () => {
                    updateRow(id, { saving: false });
                    closeMessageModal();
                },
            },
        );
    };

    const openMessageModal = (prospecto: Prospecto): void => {
        if (!prospecto.lead_id || !prospecto.generated_message || prospecto.generated_message.body.trim() === '') {
            return;
        }

        setMessageModalLeadId(prospecto.lead_id);
        setMessageData('subject', prospecto.generated_message.subject ?? '');
        setMessageData('body', prospecto.generated_message.body);
    };

    const closeMessageModal = (): void => {
        setMessageModalLeadId(null);
    };

    const saveMessage = (): void => {
        if (!activeMessageProspecto?.lead_id) {
            return;
        }

        setSendingLeadId(activeMessageProspecto.lead_id);

        postMessage(`/endex/leads/${activeMessageProspecto.lead_id}/send-message`, {
            preserveScroll: true,
            onSuccess: () => {
                setSendingLeadId(null);
                closeMessageModal();
            },
            onError: () => {
                setSendingLeadId(null);
            },
        });
    };

    const sendAsGeneratedByAi = (prospecto: Prospecto): void => {
        if (!prospecto.lead_id || !prospecto.generated_message || prospecto.generated_message.body.trim() === '') {
            return;
        }

        closeMessageModal();
        setSendingLeadId(prospecto.lead_id);

        router.post(
            `/endex/leads/${prospecto.lead_id}/send-message`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSendingLeadId(null);
                },
                onError: () => {
                    setSendingLeadId(null);
                },
            },
        );
    };

    const priorityClasses = (priorityValue: Prospecto['priority']['value']): string => {
        if (priorityValue === 'high') {
            return 'bg-rose-100 text-rose-800 ring-1 ring-rose-200 dark:bg-rose-900/40 dark:text-rose-200 dark:ring-rose-800';
        }

        if (priorityValue === 'medium') {
            return 'bg-amber-100 text-amber-800 ring-1 ring-amber-200 dark:bg-amber-900/40 dark:text-amber-200 dark:ring-amber-800';
        }

        if (priorityValue === 'low') {
            return 'bg-zinc-200 text-zinc-800 ring-1 ring-zinc-300 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700';
        }

        return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700';
    };

    const canSendNow = (prospecto: Prospecto, hasGeneratedMessage: boolean): boolean => {
        return Boolean(prospecto.lead_id && hasGeneratedMessage && prospecto.email && prospecto.email.trim() !== '');
    };

    const updateStatusQueryParam = (statusValue: string): void => {
        if (typeof window === 'undefined') {
            return;
        }

        const nextUrl = new URL(window.location.href);

        if (statusValue === 'all') {
            nextUrl.searchParams.delete('status');
        } else {
            nextUrl.searchParams.set('status', statusValue);
        }

        window.history.replaceState({}, '', nextUrl.toString());
    };

    const handleStatusTabChange = (statusValue: string): void => {
        setActiveStatusTab(statusValue);
        updateStatusQueryParam(statusValue);
    };

    const copyToClipboard = (text: string): void => {
        navigator.clipboard.writeText(text).catch(() => {});
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Prospectos lista de estatus" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 className="text-xl font-semibold">Prospectos en lista</h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Edita estatus y notas con opciones controladas para mantener el flujo comercial ordenado.
                            </p>
                        </div>

                        <Link href="/endex/prospectos" className="text-sm underline">
                            Volver a prospectos
                        </Link>
                    </div>
                </section>

                <section className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <div className="mb-4 flex flex-wrap items-center gap-3 rounded-lg border border-sidebar-border/60 bg-neutral-50 p-3 text-xs text-muted-foreground dark:bg-neutral-900">
                        <span className="font-medium text-foreground">Flujo de showcase:</span>
                        <span>Preparar = editar mensaje placeholder en modal.</span>
                        <span>Enviar = demostracion de accion, sin integracion de produccion.</span>
                    </div>

                    <div className="mb-4 flex flex-wrap items-center gap-2">
                        <div className="relative">
                            <input
                                type="search"
                                placeholder="Buscar prospecto..."
                                value={searchQuery}
                                onChange={(event) => setSearchQuery(event.target.value)}
                                className="rounded-lg border px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-black/20 dark:bg-neutral-900 dark:border-neutral-700"
                            />
                        </div>
                        <div className="flex flex-wrap items-center gap-1.5">
                            {(['all', 'high', 'medium', 'low'] as const).map((p) => (
                                <button
                                    key={p}
                                    type="button"
                                    onClick={() => setPriorityFilter(p)}
                                    className={`inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium transition ${
                                        priorityFilter === p
                                            ? 'border-black bg-black text-white dark:border-white dark:bg-white dark:text-black'
                                            : 'border-sidebar-border/70 bg-white text-foreground hover:bg-neutral-50 dark:bg-neutral-950 dark:hover:bg-neutral-900'
                                    }`}
                                >
                                    {p === 'all' && 'Todas prioridades'}
                                    {p === 'high' && '🔥 Alto'}
                                    {p === 'medium' && '⚠️ Medio'}
                                    {p === 'low' && '❌ Bajo'}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="mb-4 overflow-x-auto pb-1">
                        <div className="flex min-w-max items-center gap-2">
                            {statusTabs.map((tab) => (
                                <button
                                    key={tab.value}
                                    type="button"
                                    onClick={() => handleStatusTabChange(tab.value)}
                                    className={`inline-flex items-center gap-1 rounded-full border px-3 py-1.5 text-xs font-medium transition ${
                                        selectedStatusTab === tab.value
                                            ? 'border-black bg-black text-white dark:border-white dark:bg-white dark:text-black'
                                            : 'border-sidebar-border/70 bg-white text-foreground hover:bg-neutral-50 dark:bg-neutral-950 dark:hover:bg-neutral-900'
                                    }`}
                                >
                                    <span>{tab.label}</span>
                                    <span className="rounded-full bg-black/10 px-1.5 py-0.5 text-[11px] dark:bg-white/15">
                                        {tab.count}
                                    </span>
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="min-w-full table-fixed border-collapse text-sm">
                            <colgroup>
                                <col className="w-[18%]" />
                                <col className="w-[9%]" />
                                <col className="w-[11%]" />
                                <col className="w-[13%]" />
                                <col className="w-[16%]" />
                                <col className="w-[18%]" />
                                <col className="w-[15%]" />
                            </colgroup>
                            <thead>
                                <tr className="border-b text-left text-xs font-medium text-muted-foreground">
                                    <th className="px-3 py-3">Prospecto</th>
                                    <th className="px-3 py-3">Prioridad</th>
                                    <th className="px-3 py-3">Ubicacion</th>
                                    <th className="px-3 py-3">Contacto</th>
                                    <th className="px-3 py-3">Estatus</th>
                                    <th className="px-3 py-3">Notas</th>
                                    <th className="px-3 py-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredProspectos.map((prospecto) => {
                                    const row = rows[prospecto.id];
                                    const hasGeneratedMessage = Boolean(
                                        prospecto.generated_message && prospecto.generated_message.body.trim() !== '',
                                    );
                                    const allowSendNow = canSendNow(prospecto, hasGeneratedMessage);

                                    if (!row) {
                                        return null;
                                    }

                                    return (
                                        <tr key={prospecto.id} className={`border-b align-top last:border-0 hover:bg-neutral-50/70 dark:hover:bg-neutral-900/40 ${row.dirty ? 'border-l-2 border-l-amber-400' : ''}`}>
                                            <td className="px-3 py-3">
                                                <p className="font-medium">{prospecto.nombre}</p>
                                                {prospecto.score !== null && (
                                                    <p className="text-xs text-muted-foreground">score: {prospecto.score}</p>
                                                )}
                                            </td>
                                            <td className="px-3 py-3">
                                                <span className={`inline-flex rounded px-2 py-1 text-xs font-medium ${priorityClasses(prospecto.priority.value)}`}>
                                                    {prospecto.priority.value === 'high' && '🔥'}
                                                    {prospecto.priority.value === 'medium' && '⚠️'}
                                                    {prospecto.priority.value === 'low' && '❌'}
                                                    {prospecto.priority.value === 'unknown' && '❓'}
                                                    {' '}
                                                    {prospecto.priority.value === 'high' && 'Alto'}
                                                    {prospecto.priority.value === 'medium' && 'Medio'}
                                                    {prospecto.priority.value === 'low' && 'Bajo'}
                                                    {prospecto.priority.value === 'unknown' && 'Incompleto'}
                                                </span>
                                            </td>
                                            <td className="px-3 py-3 text-xs text-muted-foreground">
                                                {prospecto.ciudad ?? 'Sin ciudad'}{prospecto.estado ? `, ${prospecto.estado}` : ''}
                                            </td>
                                            <td className="px-3 py-3 text-xs text-muted-foreground">
                                                <div className="flex items-center gap-1">
                                                    <span className="truncate">{prospecto.telefono ?? '-'}</span>
                                                    {prospecto.telefono && (
                                                        <button
                                                            type="button"
                                                            onClick={() => copyToClipboard(prospecto.telefono!)}
                                                            title="Copiar teléfono"
                                                            className="shrink-0 opacity-40 hover:opacity-100"
                                                        >
                                                            📋
                                                        </button>
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-1">
                                                    <span className="truncate">{prospecto.email ?? '-'}</span>
                                                    {prospecto.email && (
                                                        <button
                                                            type="button"
                                                            onClick={() => copyToClipboard(prospecto.email!)}
                                                            title="Copiar email"
                                                            className="shrink-0 opacity-40 hover:opacity-100"
                                                        >
                                                            📋
                                                        </button>
                                                    )}
                                                </div>
                                                {prospecto.sitio_web ? (
                                                    <a href={prospecto.sitio_web} target="_blank" rel="noreferrer" className="block truncate underline">
                                                        {prospecto.sitio_web}
                                                    </a>
                                                ) : (
                                                    <span title="Sin sitio web" className="inline-flex rounded border px-1.5 py-0.5 text-[11px]">
                                                        ⚠️ sitio
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-3 py-3">
                                                <select
                                                    className="w-56 rounded-md border px-2 py-1 text-sm"
                                                    value={row.estatus}
                                                    onChange={(event) => updateRow(prospecto.id, {
                                                        estatus: event.target.value,
                                                        dirty: true,
                                                    })}
                                                >
                                                    {statusEntries.map(([value, label]) => (
                                                        <option key={value} value={value}>
                                                            {value} {'->'} {label}
                                                        </option>
                                                    ))}
                                                </select>
                                            </td>
                                            <td className="px-3 py-3">
                                                <textarea
                                                    className="min-h-20 w-72 rounded-md border px-2 py-1 text-sm"
                                                    value={row.notas}
                                                    onChange={(event) => updateRow(prospecto.id, {
                                                        notas: event.target.value,
                                                        dirty: true,
                                                    })}
                                                    placeholder="Notas comerciales"
                                                />
                                            </td>
                                            <td className="px-3 py-3">
                                                <div className="min-w-36 space-y-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => openMessageModal(prospecto)}
                                                        disabled={!hasGeneratedMessage || !prospecto.lead_id}
                                                        className="w-full rounded-md bg-black px-2 py-1.5 text-xs font-medium text-white disabled:opacity-50"
                                                    >
                                                        Preparar contacto
                                                    </button>

                                                    <button
                                                        type="button"
                                                        onClick={() => sendAsGeneratedByAi(prospecto)}
                                                        disabled={!allowSendNow || sendingLeadId === prospecto.lead_id}
                                                        className="w-full rounded-md bg-emerald-600 px-2 py-1.5 text-xs font-medium text-white disabled:opacity-50"
                                                    >
                                                        {sendingLeadId === prospecto.lead_id ? 'Contactando...' : 'Contactar ahora'}
                                                    </button>

                                                    <button
                                                        type="button"
                                                        onClick={() => handleSave(prospecto.id)}
                                                        disabled={!row.dirty || row.saving}
                                                        className="w-full rounded-md border px-2 py-1.5 text-xs font-medium disabled:opacity-60"
                                                    >
                                                        {row.saving ? 'Guardando...' : 'Guardar cambios'}
                                                    </button>

                                                    {prospecto.review_url ? (
                                                        <a href={prospecto.review_url} className="block text-center text-[11px] underline">
                                                            Ver propuesta
                                                        </a>
                                                    ) : (
                                                        <span className="block text-center text-[11px] text-muted-foreground">Sin review</span>
                                                    )}

                                                    {prospecto.generated_message?.updated_at && (
                                                        <span className="block text-center text-[11px] text-muted-foreground">
                                                            Mensaje: {new Date(prospecto.generated_message.updated_at).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: '2-digit' })}
                                                        </span>
                                                    )}

                                                    {!allowSendNow && (
                                                        <span className="block text-center text-[11px] text-amber-600 dark:text-amber-400">
                                                            Requiere email y mensaje generado
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}

                                {filteredProspectos.length === 0 && (
                                    <tr>
                                        <td className="px-2 py-3 text-muted-foreground" colSpan={7}>
                                            No hay prospectos en este estatus.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>

                {activeMessageProspecto && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                        <div className="max-h-[92vh] w-full max-w-6xl overflow-y-auto rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950 md:p-6">
                            <h2 className="text-base font-semibold">Editar mensaje para {activeMessageProspecto.nombre}</h2>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Ajusta el texto antes de marcar el lead como listo para contacto.
                            </p>

                            <label className="mt-3 grid gap-1 text-sm">
                                Asunto
                                <input
                                    className="rounded-md border px-3 py-2"
                                    value={messageData.subject}
                                    onChange={(event) => setMessageData('subject', event.target.value)}
                                />
                            </label>

                            <label className="mt-3 grid gap-1 text-sm">
                                Mensaje
                                <textarea
                                    className="min-h-[52vh] w-full resize-y rounded-md border px-3 py-2 md:min-h-[58vh]"
                                    value={messageData.body}
                                    onChange={(event) => setMessageData('body', event.target.value)}
                                />
                            </label>

                            <div className="mt-4 flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={closeMessageModal}
                                    className="rounded-md border px-3 py-2 text-sm"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    onClick={saveMessage}
                                    disabled={processingMessage || messageData.body.trim() === ''}
                                    className="rounded-md bg-black px-3 py-2 text-sm font-medium text-white disabled:opacity-60"
                                >
                                    {processingMessage ? 'Enviando...' : 'Enviar'}
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
