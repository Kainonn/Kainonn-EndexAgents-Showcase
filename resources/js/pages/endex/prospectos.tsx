import { Head, Link, useForm, router } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Prospecto = {
    id: number;
    campaign_id: number | null;
    nombre: string;
    giro: string | null;
    categoria: string | null;
    calificacion: number | null;
    num_resenas: number | null;
    direccion: string | null;
    telefono: string | null;
    sitio_web: string | null;
    horario: string | null;
    ciudad: string | null;
    estado: string | null;
    fuente: string | null;
    url_maps: string | null;
    notas: string | null;
    contactado: boolean;
    estatus: string;
    creado_en: string;
};

type Props = {
    prospectos: Prospecto[];
    campaignOptions: Array<{ id: number; name: string }>;
    filters: {
        search: string;
        campaign_id: string;
        estatus: string;
        sort: string;
    };
    summary: {
        total: number;
        nuevos: number;
        contactados: number;
        en_analisis: number;
        analizados: number;
        filtered: number;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Endex', href: '/dashboard' },
    { title: 'Prospectos', href: '/endex/prospectos' },
];

export default function Prospectos({ prospectos, campaignOptions, filters, summary }: Props) {
    const [selected, setSelected] = useState<number[]>([]);
    const [analyzing, setAnalyzing] = useState(false);
    const [activeTab, setActiveTab] = useState<'pending' | 'analyzed'>(filters.estatus === 'analyzed' ? 'analyzed' : 'pending');
    const [selectionError, setSelectionError] = useState('');
    const missingCampaignCount = prospectos.filter((p) => p.campaign_id === null).length;
    const pendingProspectos = prospectos.filter((p) => ['new', 'nuevo', 'nuevo_lead'].includes(p.estatus));
    const analyzedProspectos = prospectos.filter((p) => !['new', 'nuevo', 'nuevo_lead'].includes(p.estatus));
    const visibleProspectos = activeTab === 'pending' ? pendingProspectos : analyzedProspectos;

    const isAnalyzable = (prospecto: Prospecto): boolean => ['new', 'nuevo', 'nuevo_lead'].includes(prospecto.estatus) && prospecto.campaign_id !== null;

    const { data, setData, get, processing } = useForm({
        search: filters.search,
        campaign_id: filters.campaign_id,
        estatus: filters.estatus,
        sort: filters.sort,
    });

    const submitFilters: FormEventHandler = (event) => {
        event.preventDefault();
        get('/endex/prospectos', {
            preserveScroll: true,
            replace: true,
        });
    };

    const toggleSelect = (id: number) => {
        const prospecto = prospectos.find((item) => item.id === id);

        if (! prospecto || ! isAnalyzable(prospecto)) {
            return;
        }

        setSelectionError('');
        setSelected((prev) => {
            if (prev.includes(id)) {
                return prev.filter((x) => x !== id);
            }

            if (prev.length >= 2) {
                setSelectionError('Solo puedes mandar 2 prospectos por corrida.');

                return prev;
            }

            return [...prev, id];
        });
    };

    const selectAllNuevos = () => {
        const ids = prospectos.filter((p) => isAnalyzable(p)).slice(0, 2).map((p) => p.id);
        setSelectionError('');
        setSelected(ids);
    };

    const analyzeSelected = () => {
        if (selected.length === 0 || selected.length > 2) {
            if (selected.length > 2) {
                setSelectionError('Solo puedes mandar 2 prospectos por corrida.');
            }

            return;
        }

        setSelectionError('');
        setAnalyzing(true);
        router.post(
            '/endex/prospectos/analyze',
            { prospecto_ids: selected },
            {
                preserveScroll: true,
                onFinish: () => {
                    setAnalyzing(false);
                    setSelected([]);
                    setSelectionError('');
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Prospectos" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 className="text-xl font-semibold">Prospectos</h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Negocios extraidos de Google Maps por el scraper.
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <Link href="/endex/campaigns/builder" className="text-sm underline">
                                Nueva corrida
                            </Link>
                            <Link href="/endex/prospectos/lista-estatus" className="text-sm underline">
                                Lista de estatus
                            </Link>
                            <button
                                type="button"
                                onClick={selectAllNuevos}
                                disabled={activeTab === 'analyzed'}
                                className="rounded-md border px-3 py-1.5 text-sm font-medium"
                            >
                                Seleccionar 2 analizables
                            </button>
                            <button
                                type="button"
                                onClick={analyzeSelected}
                                disabled={analyzing || selected.length === 0 || activeTab === 'analyzed'}
                                className="rounded-md bg-indigo-600 px-4 py-1.5 text-sm font-medium text-white disabled:opacity-50"
                            >
                                {analyzing ? 'Enviando...' : `Analizar seleccionados (${selected.length})`}
                            </button>
                        </div>
                    </div>
                    {selectionError !== '' && (
                        <p className="mt-3 text-xs text-rose-700 dark:text-rose-300">{selectionError}</p>
                    )}
                    {missingCampaignCount > 0 && (
                        <p className="mt-3 text-xs text-amber-700 dark:text-amber-300">
                            {missingCampaignCount} prospectos no tienen campana asociada y no pueden enviarse a analisis.
                        </p>
                    )}
                </section>

                <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <p className="text-xs text-muted-foreground">Total prospectos</p>
                        <p className="mt-1 text-2xl font-semibold">{summary.total}</p>
                    </article>
                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <p className="text-xs text-muted-foreground">Nuevos</p>
                        <p className="mt-1 text-2xl font-semibold">{summary.nuevos}</p>
                    </article>
                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <p className="text-xs text-muted-foreground">En analisis</p>
                        <p className="mt-1 text-2xl font-semibold">{summary.en_analisis}</p>
                    </article>
                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <p className="text-xs text-muted-foreground">Analizados</p>
                        <p className="mt-1 text-2xl font-semibold">{summary.analizados}</p>
                    </article>
                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <p className="text-xs text-muted-foreground">Contactados</p>
                        <p className="mt-1 text-2xl font-semibold">{summary.contactados}</p>
                    </article>
                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <p className="text-xs text-muted-foreground">Resultado filtrado</p>
                        <p className="mt-1 text-2xl font-semibold">{summary.filtered}</p>
                    </article>
                </section>

                <section className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <h2 className="text-sm font-semibold">Filtros</h2>

                    <form onSubmit={submitFilters} className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <label className="grid gap-1 text-sm">
                            Buscar nombre/ciudad/giro
                            <input
                                className="rounded-md border px-3 py-2"
                                value={data.search}
                                onChange={(e) => setData('search', e.target.value)}
                                placeholder="veterinaria, Tultepec..."
                            />
                        </label>

                        <label className="grid gap-1 text-sm">
                            Estatus
                            <select
                                className="rounded-md border px-3 py-2"
                                value={data.estatus}
                                onChange={(e) => setData('estatus', e.target.value)}
                            >
                                <option value="">Todos</option>
                                <option value="new">new</option>
                                <option value="analyzed">analyzed</option>
                                <option value="ready_to_contact">ready_to_contact</option>
                                <option value="in_contact">in_contact</option>
                                <option value="responded">responded</option>
                                <option value="interested">interested</option>
                                <option value="not_interested">not_interested</option>
                                <option value="closed">closed</option>
                            </select>
                        </label>

                        <label className="grid gap-1 text-sm">
                            Campana
                            <select
                                className="rounded-md border px-3 py-2"
                                value={data.campaign_id}
                                onChange={(e) => setData('campaign_id', e.target.value)}
                            >
                                <option value="">Todas</option>
                                {campaignOptions.map((c) => (
                                    <option key={c.id} value={String(c.id)}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                        </label>

                        <label className="grid gap-1 text-sm">
                            Orden
                            <select
                                className="rounded-md border px-3 py-2"
                                value={data.sort}
                                onChange={(e) => setData('sort', e.target.value)}
                            >
                                <option value="newest">Mas recientes</option>
                                <option value="rating_desc">Mejor calificacion</option>
                                <option value="reviews_desc">Mas resenas</option>
                            </select>
                        </label>

                        <div className="flex items-end gap-3">
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-md bg-black px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
                            >
                                {processing ? 'Filtrando...' : 'Aplicar filtros'}
                            </button>
                            <Link href="/endex/prospectos" className="text-sm underline">
                                Limpiar
                            </Link>
                        </div>
                    </form>
                </section>

                <section className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2 className="text-sm font-semibold">Vista lista</h2>
                        <div className="inline-flex rounded-lg border p-1">
                            <button
                                type="button"
                                onClick={() => setActiveTab('pending')}
                                className={`rounded-md px-3 py-1.5 text-sm ${activeTab === 'pending' ? 'bg-neutral-900 text-white dark:bg-neutral-100 dark:text-neutral-900' : 'text-muted-foreground'}`}
                            >
                                No analizados ({pendingProspectos.length})
                            </button>
                            <button
                                type="button"
                                onClick={() => setActiveTab('analyzed')}
                                className={`rounded-md px-3 py-1.5 text-sm ${activeTab === 'analyzed' ? 'bg-neutral-900 text-white dark:bg-neutral-100 dark:text-neutral-900' : 'text-muted-foreground'}`}
                            >
                                Ya analizados ({analyzedProspectos.length})
                            </button>
                        </div>
                    </div>

                    <div className="mt-4 overflow-x-auto">
                        <table className="min-w-full border-collapse text-sm">
                            <thead>
                                <tr className="border-b text-left text-xs text-muted-foreground">
                                    <th className="px-2 py-2">Sel</th>
                                    <th className="px-2 py-2">Prospecto</th>
                                    <th className="px-2 py-2">Ubicacion</th>
                                    <th className="px-2 py-2">Contacto</th>
                                    <th className="px-2 py-2">Rating</th>
                                    <th className="px-2 py-2">Estatus</th>
                                    <th className="px-2 py-2">Fuente</th>
                                </tr>
                            </thead>
                            <tbody>
                                {visibleProspectos.map((p) => {
                                    const showCheckbox = activeTab === 'pending' && ['new', 'nuevo', 'nuevo_lead'].includes(p.estatus);
                                    const isDisabled = !isAnalyzable(p) || (selected.length >= 2 && !selected.includes(p.id));

                                    return (
                                        <tr
                                            key={p.id}
                                            className={`border-b last:border-0 ${selected.includes(p.id) ? 'bg-indigo-50 dark:bg-indigo-950/20' : ''}`}
                                        >
                                            <td className="px-2 py-2 align-top">
                                                {showCheckbox ? (
                                                    <input
                                                        type="checkbox"
                                                        checked={selected.includes(p.id)}
                                                        onChange={() => toggleSelect(p.id)}
                                                        disabled={isDisabled}
                                                    />
                                                ) : (
                                                    <span className="text-xs text-muted-foreground">-</span>
                                                )}
                                            </td>
                                            <td className="px-2 py-2 align-top">
                                                <p className="font-medium">{p.nombre}</p>
                                                <p className="text-xs text-muted-foreground">{p.giro ?? p.categoria ?? 'Sin giro'}</p>
                                                {p.notas && (
                                                    <p className="mt-1 line-clamp-2 text-xs italic text-muted-foreground">&ldquo;{p.notas}&rdquo;</p>
                                                )}
                                            </td>
                                            <td className="px-2 py-2 align-top text-xs text-muted-foreground">
                                                <p>{p.ciudad ?? 'Sin ciudad'}{p.estado ? `, ${p.estado}` : ''}</p>
                                                <p className="truncate">{p.direccion ?? 'Sin direccion'}</p>
                                                {p.url_maps && (
                                                    <a href={p.url_maps} target="_blank" rel="noreferrer" className="underline">
                                                        Ver en Maps
                                                    </a>
                                                )}
                                            </td>
                                            <td className="px-2 py-2 align-top text-xs text-muted-foreground">
                                                <p>{p.telefono ?? 'Sin telefono'}</p>
                                                {p.sitio_web ? (
                                                    <a href={p.sitio_web} target="_blank" rel="noreferrer" className="block truncate underline">
                                                        {p.sitio_web}
                                                    </a>
                                                ) : (
                                                    <p>Sin sitio</p>
                                                )}
                                            </td>
                                            <td className="px-2 py-2 align-top text-xs text-muted-foreground">
                                                <p>{p.calificacion ?? '-'}</p>
                                                <p>{p.num_resenas ?? 0} reseñas</p>
                                            </td>
                                            <td className="px-2 py-2 align-top">
                                                <span className={`rounded px-2 py-1 text-xs ${
                                                                                                        p.estatus === 'new'
                                                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
                                                                                                                : p.estatus === 'analyzed'
                                                          ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
                                                                                                                    : p.estatus === 'ready_to_contact'
                                                            ? 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'
                                                                                                                        : p.estatus === 'in_contact'
                                                              ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                                                              : 'bg-neutral-100 dark:bg-neutral-800'
                                                }`}>
                                                    {p.estatus}
                                                </span>
                                            </td>
                                            <td className="px-2 py-2 align-top text-xs text-muted-foreground">
                                                <p>{p.fuente ?? '-'}</p>
                                                {p.campaign_id === null && (
                                                    <span className="mt-1 inline-block rounded bg-rose-50 px-2 py-1 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
                                                        sin campana
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}

                                {visibleProspectos.length === 0 && (
                                    <tr>
                                        <td className="px-2 py-3 text-muted-foreground" colSpan={7}>
                                            No hay prospectos en esta pestaña.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
