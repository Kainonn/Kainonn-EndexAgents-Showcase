import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type CampaignSummary = {
    campaign_id: number | null;
    campaign_name: string;
    leads_generated: number;
    leads_contacted: number;
    responses_received: number;
    conversions: number;
    lead_useful_count: number;
    ratios: {
        response_rate: number;
        conversion_rate: number;
        lead_useful_rate: number;
    };
    money_leak: {
        top_source: string | null;
        by_source: Record<string, number>;
    };
};

type AgentMetrics = {
    argos: {
        accepted: number;
        discarded: number;
        discarded_rate: number;
        accepted_to_contact_rate: number;
    };
    minos: {
        alto_response_rate: number;
        medio_response_rate: number;
        bajo_no_response_rate: number;
    };
    hermes: {
        channels: Array<{
            channel: string;
            contacted: number;
            responded: number;
            response_rate: number;
        }>;
    };
    caliope: {
        variants: Array<{
            variant: string;
            contacted: number;
            responded: number;
            response_rate: number;
        }>;
    };
    nestor: {
        message_to_interest_rate: number;
        messages_total: number;
        interested_after_message: number;
    };
    hestia: {
        reviewed_total: number;
        corrected_total: number;
        passed_without_changes_total: number;
        corrected_rate: number;
        pass_without_changes_rate: number;
    };
    mnemosine: {
        patterns: {
            business_types_that_convert: Array<{
                sector: string;
                converted_count: number;
            }>;
            top_conversion_signals: Array<{
                signal: string;
                count: number;
            }>;
        };
    };
};

type SalesMatrix = {
    global: {
        leads_generated: number;
        leads_contacted: number;
        responses_received: number;
        conversions: number;
        lead_useful_count: number;
        ratios: {
            response_rate: number;
            conversion_rate: number;
            lead_useful_rate: number;
        };
        money_leak: {
            top_source: string | null;
            by_source: Record<string, number>;
        };
    };
    by_campaign: CampaignSummary[];
    by_agent: AgentMetrics;
};

type DashboardProps = {
    salesMatrix: SalesMatrix;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

const leakLabel: Record<string, string> = {
    leads_malos: 'Leads malos',
    mensajes_malos: 'Mensajes malos',
    canal_incorrecto: 'Canal incorrecto',
    oferta_incorrecta: 'Oferta incorrecta',
};

function formatPercent(value: number): string {
    return `${value.toFixed(2)}%`;
}

export default function Dashboard({ salesMatrix }: DashboardProps) {
    const global = salesMatrix.global;
    const byAgent = salesMatrix.by_agent;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl bg-linear-to-b from-neutral-50 to-white p-4 dark:from-neutral-950 dark:to-neutral-900 md:p-6">
                <section className="rounded-2xl border border-sidebar-border/70 bg-white/85 p-5 shadow-sm backdrop-blur-sm dark:border-sidebar-border dark:bg-neutral-950/80">
                    <p className="text-xs font-medium tracking-wider text-neutral-500 uppercase dark:text-neutral-400">Revenue Control Matrix</p>
                    <h1 className="mt-1 text-2xl font-semibold tracking-tight">Impacto comercial real por campaña y por agente</h1>
                    <p className="mt-2 max-w-4xl text-sm text-muted-foreground">
                        Esta vista responde tres preguntas de negocio: qué leads responden, qué mensajes convierten y qué tipo de negocio vale la pena.
                        No muestra salud técnica del sistema, muestra puntos de dinero.
                    </p>
                </section>

                <section className="grid gap-4 md:grid-cols-5">
                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <p className="text-xs text-neutral-500 uppercase dark:text-neutral-400">Leads generados</p>
                        <p className="mt-2 text-2xl font-semibold">{global.leads_generated}</p>
                    </article>
                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <p className="text-xs text-neutral-500 uppercase dark:text-neutral-400">Leads contactados</p>
                        <p className="mt-2 text-2xl font-semibold">{global.leads_contacted}</p>
                    </article>
                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <p className="text-xs text-neutral-500 uppercase dark:text-neutral-400">Respuestas</p>
                        <p className="mt-2 text-2xl font-semibold">{global.responses_received}</p>
                        <p className="mt-1 text-xs text-muted-foreground">{formatPercent(global.ratios.response_rate)}</p>
                    </article>
                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <p className="text-xs text-neutral-500 uppercase dark:text-neutral-400">Conversiones</p>
                        <p className="mt-2 text-2xl font-semibold">{global.conversions}</p>
                        <p className="mt-1 text-xs text-muted-foreground">{formatPercent(global.ratios.conversion_rate)}</p>
                    </article>
                    <article className="rounded-2xl border border-amber-300/60 bg-amber-50 p-4 shadow-sm dark:border-amber-600/40 dark:bg-amber-950/30">
                        <p className="text-xs text-amber-700 uppercase dark:text-amber-300">Fuga principal</p>
                        <p className="mt-2 text-lg font-semibold text-amber-900 dark:text-amber-200">
                            {leakLabel[global.money_leak.top_source ?? ''] ?? 'Sin datos suficientes'}
                        </p>
                    </article>
                </section>

                <section className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <h2 className="text-base font-semibold">Dashboard global por campaña</h2>
                    <p className="mt-1 text-sm text-muted-foreground">Aquí se ve dónde se pierde dinero: leads malos, mensajes malos, canal incorrecto u oferta incorrecta.</p>
                    <div className="mt-4 overflow-x-auto">
                        <table className="min-w-full text-left text-sm">
                            <thead className="text-xs text-neutral-500 uppercase dark:text-neutral-400">
                                <tr>
                                    <th className="px-3 py-2">Campaña</th>
                                    <th className="px-3 py-2">Generados</th>
                                    <th className="px-3 py-2">Contactados</th>
                                    <th className="px-3 py-2">Respuestas</th>
                                    <th className="px-3 py-2">Conversiones</th>
                                    <th className="px-3 py-2">% Respuesta</th>
                                    <th className="px-3 py-2">% Conversión</th>
                                    <th className="px-3 py-2">% Leads útiles</th>
                                    <th className="px-3 py-2">Fuga principal</th>
                                </tr>
                            </thead>
                            <tbody>
                                {salesMatrix.by_campaign.map((campaign) => (
                                    <tr key={`${campaign.campaign_id ?? 'none'}-${campaign.campaign_name}`} className="border-t border-neutral-200 dark:border-neutral-800">
                                        <td className="px-3 py-2 font-medium">{campaign.campaign_name}</td>
                                        <td className="px-3 py-2">{campaign.leads_generated}</td>
                                        <td className="px-3 py-2">{campaign.leads_contacted}</td>
                                        <td className="px-3 py-2">{campaign.responses_received}</td>
                                        <td className="px-3 py-2">{campaign.conversions}</td>
                                        <td className="px-3 py-2">{formatPercent(campaign.ratios.response_rate)}</td>
                                        <td className="px-3 py-2">{formatPercent(campaign.ratios.conversion_rate)}</td>
                                        <td className="px-3 py-2">{formatPercent(campaign.ratios.lead_useful_rate)}</td>
                                        <td className="px-3 py-2">
                                            <span className="rounded-full bg-neutral-100 px-2 py-1 text-xs dark:bg-neutral-800">
                                                {leakLabel[campaign.money_leak.top_source ?? ''] ?? 'Sin datos'}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="grid gap-4 xl:grid-cols-2">
                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <h2 className="text-base font-semibold">Argos + Minos</h2>
                        <div className="mt-3 space-y-2 text-sm text-muted-foreground">
                            <p>Argos aceptados: <span className="font-semibold text-foreground">{byAgent.argos.accepted}</span></p>
                            <p>Argos descartados: <span className="font-semibold text-foreground">{byAgent.argos.discarded}</span></p>
                            <p>% leads descartados: <span className="font-semibold text-foreground">{formatPercent(byAgent.argos.discarded_rate)}</span></p>
                            <p>% aceptados que llegan a contacto: <span className="font-semibold text-foreground">{formatPercent(byAgent.argos.accepted_to_contact_rate)}</span></p>
                        </div>
                        <div className="mt-4 rounded-xl border border-neutral-200 p-3 dark:border-neutral-800">
                            <p className="text-sm font-semibold">Validación Minos</p>
                            <p className="mt-2 text-sm">ALTO responde: <span className="font-semibold">{formatPercent(byAgent.minos.alto_response_rate)}</span></p>
                            <p className="text-sm">MEDIO responde: <span className="font-semibold">{formatPercent(byAgent.minos.medio_response_rate)}</span></p>
                            <p className="text-sm">BAJO nunca responde: <span className="font-semibold">{formatPercent(byAgent.minos.bajo_no_response_rate)}</span></p>
                        </div>
                    </article>

                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <h2 className="text-base font-semibold">Hermes + Caliope</h2>
                        <div className="mt-3">
                            <p className="text-xs text-neutral-500 uppercase dark:text-neutral-400">Tasa de respuesta por canal</p>
                            <div className="mt-2 space-y-2">
                                {byAgent.hermes.channels.map((item) => (
                                    <div key={item.channel} className="rounded-lg border border-neutral-200 px-3 py-2 text-sm dark:border-neutral-800">
                                        <p className="font-medium uppercase">{item.channel}</p>
                                        <p className="text-muted-foreground">Contactados: {item.contacted} · Respondieron: {item.responded} · Tasa: {formatPercent(item.response_rate)}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                        <div className="mt-4">
                            <p className="text-xs text-neutral-500 uppercase dark:text-neutral-400">Tasa de respuesta A/B</p>
                            <div className="mt-2 grid gap-2 md:grid-cols-2">
                                {byAgent.caliope.variants.map((item) => (
                                    <div key={item.variant} className="rounded-lg border border-neutral-200 px-3 py-2 text-sm dark:border-neutral-800">
                                        <p className="font-medium">Variante {item.variant}</p>
                                        <p className="text-muted-foreground">Contactados: {item.contacted} · Respondieron: {item.responded}</p>
                                        <p className="font-semibold">{formatPercent(item.response_rate)}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </article>

                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <h2 className="text-base font-semibold">Nestor + Hestia</h2>
                        <div className="mt-3 space-y-2 text-sm text-muted-foreground">
                            <p>
                                Nestor (mensaje {'->'} interés real): <span className="font-semibold text-foreground">{formatPercent(byAgent.nestor.message_to_interest_rate)}</span>
                            </p>
                            <p>
                                Interesados tras mensaje: <span className="font-semibold text-foreground">{byAgent.nestor.interested_after_message}</span> de {byAgent.nestor.messages_total}
                            </p>
                            <p>
                                Hestia corregidos: <span className="font-semibold text-foreground">{byAgent.hestia.corrected_total}</span> ({formatPercent(byAgent.hestia.corrected_rate)})
                            </p>
                            <p>
                                Hestia sin cambios: <span className="font-semibold text-foreground">{byAgent.hestia.passed_without_changes_total}</span> ({formatPercent(byAgent.hestia.pass_without_changes_rate)})
                            </p>
                        </div>
                    </article>

                    <article className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                        <h2 className="text-base font-semibold">Mnemosine (aprendizaje comercial)</h2>
                        <div className="mt-3">
                            <p className="text-xs text-neutral-500 uppercase dark:text-neutral-400">Tipo de negocio que más convierte</p>
                            <div className="mt-2 space-y-2">
                                {byAgent.mnemosine.patterns.business_types_that_convert.map((item) => (
                                    <div key={item.sector} className="rounded-lg border border-neutral-200 px-3 py-2 text-sm dark:border-neutral-800">
                                        <span className="font-medium">{item.sector}</span>
                                        <span className="ml-2 text-muted-foreground">Conversiones: {item.converted_count}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                        <div className="mt-4">
                            <p className="text-xs text-neutral-500 uppercase dark:text-neutral-400">Señales más comunes en leads que convierten</p>
                            <div className="mt-2 flex flex-wrap gap-2">
                                {byAgent.mnemosine.patterns.top_conversion_signals.map((signal) => (
                                    <span key={signal.signal} className="rounded-full bg-neutral-100 px-3 py-1 text-xs dark:bg-neutral-800">
                                        {signal.signal} ({signal.count})
                                    </span>
                                ))}
                            </div>
                        </div>
                    </article>
                </section>
            </div>
        </AppLayout>
    );
}
