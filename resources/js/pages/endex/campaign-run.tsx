import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Campaign = {
    id: number;
    name: string;
    solution_name: string;
    status: string;
    leads_count: number;
};

type CampaignRunItem = {
    id: number;
    status: string;
    started_at: string | null;
    finished_at: string | null;
    total_leads_analyzed: number;
    total_leads_generated: number;
    error_count: number;
    created_at: string;
};

type Props = {
    campaign: Campaign;
    runs: CampaignRunItem[];
};

const mxStates = [
    'Aguascalientes',
    'Baja California',
    'Baja California Sur',
    'Campeche',
    'Chiapas',
    'Chihuahua',
    'Ciudad de Mexico',
    'Coahuila',
    'Colima',
    'Durango',
    'Estado de Mexico',
    'Guanajuato',
    'Guerrero',
    'Hidalgo',
    'Jalisco',
    'Michoacan',
    'Morelos',
    'Nayarit',
    'Nuevo Leon',
    'Oaxaca',
    'Puebla',
    'Queretaro',
    'Quintana Roo',
    'San Luis Potosi',
    'Sinaloa',
    'Sonora',
    'Tabasco',
    'Tamaulipas',
    'Tlaxcala',
    'Veracruz',
    'Yucatan',
    'Zacatecas',
];

export default function CampaignRun({ campaign, runs }: Props) {
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Endex', href: '/dashboard' },
        { title: 'Campaign Builder', href: '/endex/campaigns/builder' },
        { title: 'Campaign Run', href: `/endex/campaigns/${campaign.id}/run` },
    ];

    const { data, setData, post, processing, errors } = useForm({
        target_leads: 5,
        source_label: 'campaign_run',
        search_state: '',
        search_municipality: '',
        search_postal_code: '',
        _token: csrfToken,
    });
    const searchAreaError = (errors as Record<string, string | undefined>).search_area;

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(`/endex/campaigns/${campaign.id}/run`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Campaign Run" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <h1 className="text-xl font-semibold">{campaign.name}</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {campaign.solution_name} • Estado: {campaign.status}
                    </p>

                    <div className="mt-4 grid gap-3 md:grid-cols-3">
                        <div className="rounded-lg border p-3">
                            <p className="text-xs text-muted-foreground">Leads registrados</p>
                            <p className="text-2xl font-semibold">{campaign.leads_count}</p>
                        </div>
                        <div className="rounded-lg border p-3">
                            <p className="text-xs text-muted-foreground">Corridas recientes</p>
                            <p className="text-2xl font-semibold">{runs.length}</p>
                        </div>
                        <div className="rounded-lg border p-3">
                            <p className="text-xs text-muted-foreground">Pipeline</p>
                            <p className="text-sm font-medium">Argos to Mnemosine</p>
                        </div>
                    </div>

                    <form onSubmit={submit} className="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <label className="grid gap-1 text-sm">
                            Leads objetivo
                            <input
                                type="number"
                                min={1}
                                max={25}
                                className="rounded-md border px-3 py-2"
                                value={data.target_leads}
                                onChange={(event) => setData('target_leads', Number(event.target.value))}
                            />
                            {errors.target_leads && (
                                <span className="text-xs text-red-600">{errors.target_leads}</span>
                            )}
                        </label>

                        <label className="grid gap-1 text-sm">
                            Source label
                            <input
                                className="rounded-md border px-3 py-2"
                                value={data.source_label}
                                onChange={(event) => setData('source_label', event.target.value)}
                            />
                        </label>

                        <label className="grid gap-1 text-sm">
                            Estado
                            <select
                                className="rounded-md border px-3 py-2"
                                value={data.search_state}
                                onChange={(event) => setData('search_state', event.target.value)}
                            >
                                <option value="">Selecciona estado</option>
                                {mxStates.map((state) => (
                                    <option key={state} value={state}>
                                        {state}
                                    </option>
                                ))}
                            </select>
                        </label>

                        <label className="grid gap-1 text-sm">
                            Municipio
                            <input
                                className="rounded-md border px-3 py-2"
                                value={data.search_municipality}
                                onChange={(event) => setData('search_municipality', event.target.value)}
                                placeholder="Ej. Monterrey"
                            />
                        </label>

                        <label className="grid gap-1 text-sm">
                            Codigo postal (alternativa)
                            <input
                                className="rounded-md border px-3 py-2"
                                value={data.search_postal_code}
                                onChange={(event) => setData('search_postal_code', event.target.value.replace(/\D/g, '').slice(0, 5))}
                                placeholder="Ej. 64000"
                            />
                            {errors.search_postal_code && (
                                <span className="text-xs text-red-600">{errors.search_postal_code}</span>
                            )}
                        </label>

                        <div className="flex items-end gap-3">
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-md bg-black px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
                            >
                                {processing ? 'Corriendo...' : 'Lanzar corrida'}
                            </button>

                            <Link href="/endex/leads/inbox" className="text-sm underline">
                                Ver Lead Inbox
                            </Link>
                        </div>

                        {searchAreaError && (
                            <p className="text-sm text-red-600 md:col-span-2 xl:col-span-3">{searchAreaError}</p>
                        )}
                    </form>
                </section>

                <section className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <h2 className="text-lg font-semibold">Ultimas corridas</h2>

                    <div className="mt-4 overflow-x-auto">
                        <table className="w-full min-w-190 border-collapse text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="px-2 py-2 font-medium">ID</th>
                                    <th className="px-2 py-2 font-medium">Estado</th>
                                    <th className="px-2 py-2 font-medium">Analizados</th>
                                    <th className="px-2 py-2 font-medium">Generados</th>
                                    <th className="px-2 py-2 font-medium">Errores</th>
                                    <th className="px-2 py-2 font-medium">Inicio</th>
                                    <th className="px-2 py-2 font-medium">Fin</th>
                                </tr>
                            </thead>
                            <tbody>
                                {runs.map((run) => (
                                    <tr key={run.id} className="border-b">
                                        <td className="px-2 py-2">#{run.id}</td>
                                        <td className="px-2 py-2">{run.status}</td>
                                        <td className="px-2 py-2">{run.total_leads_analyzed}</td>
                                        <td className="px-2 py-2">{run.total_leads_generated}</td>
                                        <td className="px-2 py-2">{run.error_count}</td>
                                        <td className="px-2 py-2">{run.started_at ?? '-'}</td>
                                        <td className="px-2 py-2">{run.finished_at ?? '-'}</td>
                                    </tr>
                                ))}
                                {runs.length === 0 && (
                                    <tr>
                                        <td colSpan={7} className="px-2 py-4 text-center text-muted-foreground">
                                            Sin corridas todavia.
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
