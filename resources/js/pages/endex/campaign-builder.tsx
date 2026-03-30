import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type CampaignListItem = {
    id: number;
    name: string;
    slug: string;
    solution_name: string;
    status: string;
    created_at: string;
};

type BuilderProps = {
    campaigns: CampaignListItem[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Endex', href: '/dashboard' },
    { title: 'Campaign Builder', href: '/endex/campaigns/builder' },
];

export default function CampaignBuilder({ campaigns }: BuilderProps) {
    const { data, setData, post, processing, errors, transform } = useForm({
        name: '',
        solution_name: '',
        description: '',
        system_context_files: [] as File[],
        target_segments: '' as string,
        pain_points: '' as string,
        opportunity_signals: '' as string,
        max_leads_per_run: 10,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        transform((payload) => ({
            ...payload,
            target_segments: payload.target_segments
                .split('\n')
                .map((item: string) => item.trim())
                .filter(Boolean),
            pain_points: payload.pain_points
                .split('\n')
                .map((item: string) => item.trim())
                .filter(Boolean),
            opportunity_signals: payload.opportunity_signals
                .split('\n')
                .map((item: string) => item.trim())
                .filter(Boolean),
        }));

        post('/endex/campaigns', {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Campaign Builder" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <h1 className="text-xl font-semibold">Campaign Builder</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Crea campanas para correr el pipeline de EndexAgents.
                    </p>

                    <form onSubmit={submit} className="mt-5 grid gap-4 md:grid-cols-2">
                        <label className="grid gap-1 text-sm">
                            Nombre de campana
                            <input
                                className="rounded-md border px-3 py-2"
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                                placeholder="EndexCare - Clinicas MX"
                            />
                            {errors.name && <span className="text-xs text-red-600">{errors.name}</span>}
                        </label>

                        <label className="grid gap-1 text-sm">
                            Solucion principal
                            <input
                                className="rounded-md border px-3 py-2"
                                value={data.solution_name}
                                onChange={(event) => setData('solution_name', event.target.value)}
                                placeholder="EndexCare"
                            />
                            {errors.solution_name && <span className="text-xs text-red-600">{errors.solution_name}</span>}
                        </label>

                        <label className="grid gap-1 text-sm md:col-span-2">
                            Descripcion
                            <textarea
                                className="min-h-20 rounded-md border px-3 py-2"
                                value={data.description}
                                onChange={(event) => setData('description', event.target.value)}
                                placeholder="Que problema resuelve esta campana"
                            />
                        </label>

                        <label className="grid gap-1 text-sm md:col-span-2">
                            Contexto del sistema/oferta (.txt o .md)
                            <input
                                type="file"
                                multiple
                                accept=".txt,.md,text/plain,text/markdown"
                                className="rounded-md border px-3 py-2"
                                onChange={(event) => setData('system_context_files', Array.from(event.target.files ?? []))}
                            />
                            <span className="text-xs text-muted-foreground">
                                Puedes cargar hasta 10 archivos. Los agentes leeran este contexto para entender exactamente que se esta ofertando.
                            </span>
                            {data.system_context_files.length > 0 && (
                                <div className="mt-1 rounded-md border bg-neutral-50 p-2 text-xs text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                                    <p className="font-medium">Archivos seleccionados ({data.system_context_files.length})</p>
                                    <ul className="mt-1 space-y-1">
                                        {data.system_context_files.map((file) => (
                                            <li key={`${file.name}-${file.size}`}>{file.name}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                            {errors.system_context_file && <span className="text-xs text-red-600">{errors.system_context_file}</span>}
                            {errors.system_context_files && <span className="text-xs text-red-600">{errors.system_context_files}</span>}
                            {errors['system_context_files.0'] && <span className="text-xs text-red-600">{errors['system_context_files.0']}</span>}
                        </label>

                        <label className="grid gap-1 text-sm">
                            Segmentos objetivo (uno por linea)
                            <textarea
                                className="min-h-28 rounded-md border px-3 py-2"
                                value={data.target_segments}
                                onChange={(event) => setData('target_segments', event.target.value)}
                                placeholder={'clinicas\ndentistas\nconsultorios'}
                            />
                        </label>

                        <label className="grid gap-1 text-sm">
                            Pain points (uno por linea)
                            <textarea
                                className="min-h-28 rounded-md border px-3 py-2"
                                value={data.pain_points}
                                onChange={(event) => setData('pain_points', event.target.value)}
                                placeholder={'agenda manual\nseguimiento deficiente'}
                            />
                        </label>

                        <label className="grid gap-1 text-sm">
                            Opportunity signals (uno por linea)
                            <textarea
                                className="min-h-28 rounded-md border px-3 py-2"
                                value={data.opportunity_signals}
                                onChange={(event) => setData('opportunity_signals', event.target.value)}
                                placeholder={'sin sitio responsive\nproceso manual'}
                            />
                        </label>

                        <label className="grid gap-1 text-sm">
                            Maximo leads por corrida
                            <input
                                type="number"
                                min={1}
                                max={100}
                                className="rounded-md border px-3 py-2"
                                value={data.max_leads_per_run}
                                onChange={(event) => setData('max_leads_per_run', Number(event.target.value))}
                            />
                        </label>

                        <div className="md:col-span-2">
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-md bg-black px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
                            >
                                {processing ? 'Creando...' : 'Crear campana'}
                            </button>
                        </div>
                    </form>
                </section>

                <section className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <h2 className="text-lg font-semibold">Campanas recientes</h2>
                    <div className="mt-4 grid gap-3">
                        {campaigns.length === 0 && (
                            <p className="text-sm text-muted-foreground">Aun no hay campanas registradas.</p>
                        )}

                        {campaigns.map((campaign) => (
                            <div
                                key={campaign.id}
                                className="flex items-center justify-between rounded-lg border px-3 py-2"
                            >
                                <div>
                                    <p className="font-medium">{campaign.name}</p>
                                    <p className="text-xs text-muted-foreground">
                                        {campaign.solution_name} • {campaign.status}
                                    </p>
                                </div>
                                <Link
                                    href={`/endex/campaigns/${campaign.id}/run`}
                                    className="text-sm font-medium underline"
                                >
                                    Abrir corrida
                                </Link>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
