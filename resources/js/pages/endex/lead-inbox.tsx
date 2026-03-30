import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type LeadItem = {
    id: number;
    company_name: string;
    city: string | null;
    sector: string | null;
    status: string;
    priority: number;
    latest_confidence: string | null;
    campaign: {
        id: number;
        name: string;
        solution_name: string;
    };
    scores: { total_score: number }[];
    offers: { offer_type: string }[];
    agent_progress: Record<string, boolean>;
    insights: {
        maps_url: string;
        maps_place_id: string | null;
        rating: number | null;
        reviews_count: number | null;
        website: string | null;
        phone: string | null;
        address: string | null;
        http_status: number | null;
    };
};

type Props = {
    leads: LeadItem[];
    campaignOptions: Array<{ id: number; name: string }>;
    filters: {
        search: string;
        status: string;
        campaign_id: string;
        agent_gap: string;
        min_score: string;
        priority_min: string;
        priority_max: string;
        sort: string;
    };
    summary: {
        total_leads: number;
        pending_review: number;
        approved_today: number;
        filtered_results: number;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Endex', href: '/dashboard' },
    { title: 'Lead Inbox', href: '/endex/leads/inbox' },
];

export default function LeadInbox({ leads, campaignOptions, filters, summary }: Props) {
    const { data, setData, get, processing } = useForm({
        search: filters.search,
        status: filters.status,
        campaign_id: filters.campaign_id,
        agent_gap: filters.agent_gap,
        min_score: filters.min_score,
        priority_min: filters.priority_min,
        priority_max: filters.priority_max,
        sort: filters.sort,
    });

    const exportParams = new URLSearchParams();
    Object.entries(data).forEach(([key, value]) => {
        if (String(value).trim() !== '') {
            exportParams.set(key, String(value));
        }
    });
    const exportUrl = `/endex/leads/inbox/export-csv${exportParams.toString() ? `?${exportParams.toString()}` : ''}`;

    const submitFilters: FormEventHandler = (event) => {
        event.preventDefault();
        get('/endex/leads/inbox', {
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Lead Inbox" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 className="text-xl font-semibold">Lead Inbox</h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Oportunidades generadas por el pipeline para revision humana.
                            </p>
                        </div>

                        <Link href="/endex/campaigns/builder" className="text-sm underline">
                            Crear campana
                        </Link>
                    </div>
                </section>

                <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <p className="text-xs text-muted-foreground">Leads totales</p>
                        <p className="mt-1 text-2xl font-semibold">{summary.total_leads}</p>
                    </article>
                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <p className="text-xs text-muted-foreground">Pendientes de revision</p>
                        <p className="mt-1 text-2xl font-semibold">{summary.pending_review}</p>
                    </article>
                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <p className="text-xs text-muted-foreground">Aprobados hoy</p>
                        <p className="mt-1 text-2xl font-semibold">{summary.approved_today}</p>
                    </article>
                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <p className="text-xs text-muted-foreground">Resultado filtrado</p>
                        <p className="mt-1 text-2xl font-semibold">{summary.filtered_results}</p>
                    </article>
                </section>

                <section className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <h2 className="text-sm font-semibold">Filtros</h2>

                    <form onSubmit={submitFilters} className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <label className="grid gap-1 text-sm">
                            Buscar empresa/ciudad/sector
                            <input
                                className="rounded-md border px-3 py-2"
                                value={data.search}
                                onChange={(event) => setData('search', event.target.value)}
                                placeholder="clinica, legal, Monterrey..."
                            />
                        </label>

                        <label className="grid gap-1 text-sm">
                            Estado
                            <select
                                className="rounded-md border px-3 py-2"
                                value={data.status}
                                onChange={(event) => setData('status', event.target.value)}
                            >
                                <option value="">Todos</option>
                                <option value="pending_human_review">pending_human_review</option>
                                <option value="offer_classified">offer_classified</option>
                                <option value="approved">approved</option>
                                <option value="needs_adjustment">needs_adjustment</option>
                                <option value="discarded">discarded</option>
                            </select>
                        </label>

                        <label className="grid gap-1 text-sm">
                            Campana
                            <select
                                className="rounded-md border px-3 py-2"
                                value={data.campaign_id}
                                onChange={(event) => setData('campaign_id', event.target.value)}
                            >
                                <option value="">Todas</option>
                                {campaignOptions.map((campaign) => (
                                    <option key={campaign.id} value={String(campaign.id)}>
                                        {campaign.name}
                                    </option>
                                ))}
                            </select>
                        </label>

                        <label className="grid gap-1 text-sm">
                            Brecha por agente
                            <select
                                className="rounded-md border px-3 py-2"
                                value={data.agent_gap}
                                onChange={(event) => setData('agent_gap', event.target.value)}
                            >
                                <option value="">Sin filtro</option>
                                <option value="Argos">Falta Argos</option>
                                <option value="Hefesto">Falta Hefesto</option>
                                <option value="Tique">Falta Tique</option>
                                <option value="Minos">Falta Minos</option>
                                <option value="Temis">Falta Temis</option>
                                <option value="Hermes">Falta Hermes</option>
                                <option value="Caliope">Falta Caliope</option>
                                <option value="Nestor">Falta Nestor</option>
                                <option value="Hestia">Falta Hestia</option>
                                <option value="Mnemosine">Falta Mnemosine</option>
                            </select>
                        </label>

                        <label className="grid gap-1 text-sm">
                            Orden
                            <select
                                className="rounded-md border px-3 py-2"
                                value={data.sort}
                                onChange={(event) => setData('sort', event.target.value)}
                            >
                                <option value="newest">Mas recientes</option>
                                <option value="priority_desc">Prioridad alta</option>
                                <option value="score_desc">Score alto</option>
                            </select>
                        </label>

                        <label className="grid gap-1 text-sm">
                            Score minimo
                            <input
                                type="number"
                                min={0}
                                max={100}
                                className="rounded-md border px-3 py-2"
                                value={data.min_score}
                                onChange={(event) => setData('min_score', event.target.value)}
                                placeholder="60"
                            />
                        </label>

                        <label className="grid gap-1 text-sm">
                            Prioridad minima
                            <input
                                type="number"
                                min={0}
                                max={100}
                                className="rounded-md border px-3 py-2"
                                value={data.priority_min}
                                onChange={(event) => setData('priority_min', event.target.value)}
                                placeholder="50"
                            />
                        </label>

                        <label className="grid gap-1 text-sm">
                            Prioridad maxima
                            <input
                                type="number"
                                min={0}
                                max={100}
                                className="rounded-md border px-3 py-2"
                                value={data.priority_max}
                                onChange={(event) => setData('priority_max', event.target.value)}
                                placeholder="90"
                            />
                        </label>

                        <div className="flex items-end gap-3">
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-md bg-black px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
                            >
                                {processing ? 'Filtrando...' : 'Aplicar filtros'}
                            </button>
                            <Link href="/endex/leads/inbox" className="text-sm underline">
                                Limpiar
                            </Link>
                            <a href={exportUrl} className="text-sm underline">
                                Exportar CSV
                            </a>
                        </div>
                    </form>
                </section>

                <section className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <div className="flex items-center justify-between gap-3">
                        <h2 className="text-sm font-semibold">Matriz de avance por agente</h2>
                        <p className="text-xs text-muted-foreground">Top {Math.min(leads.length, 20)} leads filtrados</p>
                    </div>

                    <div className="mt-3 overflow-x-auto">
                        <table className="min-w-full border-collapse text-xs">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="px-2 py-2">Lead</th>
                                    <th className="px-2 py-2">Estado</th>
                                    <th className="px-2 py-2">Arg</th>
                                    <th className="px-2 py-2">Hef</th>
                                    <th className="px-2 py-2">Tiq</th>
                                    <th className="px-2 py-2">Min</th>
                                    <th className="px-2 py-2">Tem</th>
                                    <th className="px-2 py-2">Her</th>
                                    <th className="px-2 py-2">Cal</th>
                                    <th className="px-2 py-2">Nes</th>
                                    <th className="px-2 py-2">Hes</th>
                                    <th className="px-2 py-2">Mne</th>
                                </tr>
                            </thead>
                            <tbody>
                                {leads.slice(0, 20).map((lead) => {
                                    const badge = (done: boolean) => (
                                        <span
                                            className={`inline-flex rounded px-2 py-1 ${done ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'}`}
                                        >
                                            {done ? 'ok' : 'pend'}
                                        </span>
                                    );

                                    return (
                                        <tr key={`matrix-${lead.id}`} className="border-b last:border-0">
                                            <td className="px-2 py-2 font-medium">
                                                <Link href={`/endex/leads/${lead.id}/review`} className="underline">
                                                    {lead.company_name}
                                                </Link>
                                            </td>
                                            <td className="px-2 py-2">{lead.status}</td>
                                            <td className="px-2 py-2">{badge(lead.agent_progress.Argos ?? false)}</td>
                                            <td className="px-2 py-2">{badge(lead.agent_progress.Hefesto ?? false)}</td>
                                            <td className="px-2 py-2">{badge(lead.agent_progress.Tique ?? false)}</td>
                                            <td className="px-2 py-2">{badge(lead.agent_progress.Minos ?? false)}</td>
                                            <td className="px-2 py-2">{badge(lead.agent_progress.Temis ?? false)}</td>
                                            <td className="px-2 py-2">{badge(lead.agent_progress.Hermes ?? false)}</td>
                                            <td className="px-2 py-2">{badge(lead.agent_progress.Caliope ?? false)}</td>
                                            <td className="px-2 py-2">{badge(lead.agent_progress.Nestor ?? false)}</td>
                                            <td className="px-2 py-2">{badge(lead.agent_progress.Hestia ?? false)}</td>
                                            <td className="px-2 py-2">{badge(lead.agent_progress.Mnemosine ?? false)}</td>
                                        </tr>
                                    );
                                })}

                                {leads.length === 0 && (
                                    <tr>
                                        <td className="px-2 py-3 text-muted-foreground" colSpan={12}>
                                            Sin leads para mostrar matriz.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {leads.map((lead) => {
                        const score = lead.scores[0]?.total_score;
                        const offer = lead.offers[0]?.offer_type;

                        return (
                            <article
                                key={lead.id}
                                className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950"
                            >
                                <p className="text-xs text-muted-foreground">{lead.campaign.name}</p>
                                <h2 className="mt-1 text-base font-semibold">{lead.company_name}</h2>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {lead.city ?? 'Sin ciudad'} • {lead.sector ?? 'Sin sector'}
                                </p>

                                <div className="mt-2 space-y-1 text-xs text-muted-foreground">
                                    <p>Direccion: {lead.insights.address ?? 'no detectada'}</p>
                                    <p>Telefono: {lead.insights.phone ?? 'no detectado'}</p>
                                    {lead.insights.website ? (
                                        <p>
                                            Sitio:{' '}
                                            <a
                                                href={lead.insights.website}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="underline"
                                            >
                                                {lead.insights.website}
                                            </a>
                                        </p>
                                    ) : (
                                        <p>Sitio: no detectado</p>
                                    )}
                                    <p>
                                        Maps:{' '}
                                        <a href={lead.insights.maps_url} target="_blank" rel="noreferrer" className="underline">
                                            abrir enlace
                                        </a>
                                    </p>
                                </div>

                                <div className="mt-3 flex flex-wrap gap-2 text-xs">
                                    <span className="rounded bg-neutral-100 px-2 py-1 dark:bg-neutral-800">
                                        {lead.status}
                                    </span>
                                    <span className="rounded bg-neutral-100 px-2 py-1 dark:bg-neutral-800">
                                        prioridad {lead.priority}
                                    </span>
                                    {score !== undefined && (
                                        <span className="rounded bg-neutral-100 px-2 py-1 dark:bg-neutral-800">
                                            score {score}
                                        </span>
                                    )}
                                    {offer && (
                                        <span className="rounded bg-neutral-100 px-2 py-1 dark:bg-neutral-800">
                                            {offer}
                                        </span>
                                    )}
                                    {lead.insights.rating !== null && (
                                        <span className="rounded bg-neutral-100 px-2 py-1 dark:bg-neutral-800">
                                            rating {lead.insights.rating}
                                        </span>
                                    )}
                                    {lead.insights.reviews_count !== null && (
                                        <span className="rounded bg-neutral-100 px-2 py-1 dark:bg-neutral-800">
                                            reseñas {lead.insights.reviews_count}
                                        </span>
                                    )}
                                </div>

                                <div className="mt-4 flex items-center justify-between">
                                    <p className="text-xs text-muted-foreground">
                                        conf. {lead.latest_confidence ?? '-'}
                                    </p>
                                    <Link
                                        href={`/endex/leads/${lead.id}/review`}
                                        className="text-sm font-medium underline"
                                    >
                                        Revisar lead
                                    </Link>
                                </div>
                            </article>
                        );
                    })}

                    {leads.length === 0 && (
                        <p className="text-sm text-muted-foreground">No hay leads en el inbox todavia.</p>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
