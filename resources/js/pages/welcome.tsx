import { Head, Link, usePage } from '@inertiajs/react';
import { dashboard, login } from '@/routes';

const agents = [
    { name: 'Argos', stage: 'Detección', desc: 'Localiza y enriquece el negocio en OpenStreetMap' },
    { name: 'Hefesto', stage: 'Auditoría web', desc: 'Audita SEO/UX y señales de presencia digital' },
    { name: 'Tique', stage: 'Enriquecimiento', desc: 'Construye hipótesis de oportunidad con IA' },
    { name: 'Minos', stage: 'Scoring', desc: 'Calcula urgencia, fit y capacidad de pago' },
    { name: 'Temis', stage: 'Oferta', desc: 'Selecciona el tipo de oferta y rango de precio' },
    { name: 'Hermes', stage: 'Contacto', desc: 'Extrae email y nombre del contacto principal' },
    { name: 'Calíope', stage: 'Mensaje', desc: 'Redacta el email de outreach personalizado' },
    { name: 'Néstor', stage: 'Propuesta', desc: 'Construye resumen ejecutivo y timeline' },
    { name: 'Hestia', stage: 'Compliance', desc: 'Valida duplicados y riesgo de spam' },
    { name: 'Mnemosine', stage: 'Memoria', desc: 'Consolida artefactos y pone en bandeja de revisión' },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="EndexAgents | Digitaliza, Automatiza, Escala">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|space-grotesk:500,700"
                    rel="stylesheet"
                />
            </Head>
            <div className="relative min-h-screen overflow-hidden bg-[#060708] text-[#EDEDEC]">
                <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_12%_18%,rgba(255,68,51,0.18),transparent_44%),radial-gradient(circle_at_88%_10%,rgba(255,117,15,0.12),transparent_35%),linear-gradient(180deg,#060708_0%,#0b0f12_100%)]" />
                <div className="pointer-events-none absolute top-16 -left-40 h-64 w-64 rounded-full border border-[#ff750f33]" />
                <div className="pointer-events-none absolute -right-32 bottom-20 h-72 w-72 rounded-full border border-[#ff443344]" />

                <div className="relative mx-auto max-w-6xl px-4 pb-16 sm:px-6 lg:px-8">
                    <header className="flex items-center justify-between py-6">
                        <div className="flex items-center gap-3">
                            <img src="/logo/endex-logo-new.jpg" alt="Endex" className="h-12 w-12 rounded-lg bg-white/95 p-1" />
                            <div>
                                <p className="font-['Space_Grotesk'] text-xl font-bold tracking-tight text-white">
                                    Endex<span className="text-[#FF4433]">Agents</span>
                                </p>
                                <p className="text-xs uppercase tracking-[0.22em] text-[#b3b2ad]">Uso interno | Single Tenant</p>
                            </div>
                        </div>

                        <nav className="flex items-center gap-2 text-sm text-[#b3b2ad] sm:gap-4">
                            {auth.user ? (
                                <>
                                    <Link href="/endex/campaigns/builder" className="hidden transition-colors hover:text-white sm:inline">
                                        Campanas
                                    </Link>
                                    <Link href="/endex/prospectos" className="hidden transition-colors hover:text-white sm:inline">
                                        Prospectos
                                    </Link>
                                    <Link href="/endex/leads/inbox" className="hidden transition-colors hover:text-white sm:inline">
                                        Inbox
                                    </Link>
                                    <Link
                                        href={dashboard()}
                                        className="rounded-full border border-[#ffffff2a] px-4 py-1.5 font-medium text-white transition hover:border-[#ff443388] hover:bg-[#ff44331a]"
                                    >
                                        Dashboard
                                    </Link>
                                </>
                            ) : (
                                <Link
                                    href={login()}
                                    className="rounded-full border border-[#ffffff2a] px-4 py-1.5 font-medium text-white transition hover:border-[#ff443388] hover:bg-[#ff44331a]"
                                >
                                    Entrar
                                </Link>
                            )}
                        </nav>
                    </header>

                    <section className="grid gap-10 pt-5 pb-12 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                        <div>
                            <p className="mb-4 inline-flex rounded-full border border-[#ff750f4a] bg-[#ff750f1a] px-4 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-[#ffb278]">
                                Digitaliza. Automatiza. Escala.
                            </p>
                            <h1 className="max-w-3xl font-['Space_Grotesk'] text-4xl font-bold leading-tight text-white sm:text-5xl lg:text-6xl">
                                Prospeccion B2B con 10 agentes de IA en pipeline
                            </h1>
                            <p className="mt-5 max-w-2xl text-base leading-relaxed text-[#c2c0bb] sm:text-lg">
                                Descubre negocios en Maps, audita su presencia digital, genera scoring, oferta, mensaje y propuesta para cerrar mas rapido.
                                Todo en una sola bandeja de revision humana.
                            </p>

                            <div className="mt-8 flex flex-wrap items-center gap-3">
                                {auth.user ? (
                                    <>
                                        <Link
                                            href="/endex/campaigns/builder"
                                            className="rounded-full border border-[#ff4433] bg-[#ff4433] px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-[#ff5a47]"
                                        >
                                            Crear campana
                                        </Link>
                                        <Link
                                            href="/endex/leads/inbox"
                                            className="rounded-full border border-[#ffffff2a] px-6 py-2.5 text-sm font-semibold text-[#f3f2ef] transition hover:border-[#ffffff59]"
                                        >
                                            Revisar leads
                                        </Link>
                                    </>
                                ) : (
                                    <Link
                                        href={login()}
                                        className="rounded-full border border-[#ff4433] bg-[#ff4433] px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-[#ff5a47]"
                                    >
                                        Iniciar sesion
                                    </Link>
                                )}
                            </div>

                            <div className="mt-7 grid max-w-xl grid-cols-2 gap-3 sm:grid-cols-4">
                                <div className="rounded-xl border border-[#ffffff1a] bg-[#ffffff08] px-3 py-3 text-center">
                                    <p className="font-['Space_Grotesk'] text-2xl font-bold text-white">10</p>
                                    <p className="text-xs text-[#b3b2ad]">Agentes</p>
                                </div>
                                <div className="rounded-xl border border-[#ffffff1a] bg-[#ffffff08] px-3 py-3 text-center">
                                    <p className="font-['Space_Grotesk'] text-2xl font-bold text-white">1</p>
                                    <p className="text-xs text-[#b3b2ad]">Orquestador</p>
                                </div>
                                <div className="rounded-xl border border-[#ffffff1a] bg-[#ffffff08] px-3 py-3 text-center">
                                    <p className="font-['Space_Grotesk'] text-2xl font-bold text-white">B2B</p>
                                    <p className="text-xs text-[#b3b2ad]">Foco comercial</p>
                                </div>
                                <div className="rounded-xl border border-[#ffffff1a] bg-[#ffffff08] px-3 py-3 text-center">
                                    <p className="font-['Space_Grotesk'] text-2xl font-bold text-white">IA</p>
                                    <p className="text-xs text-[#b3b2ad]">Motor local</p>
                                </div>
                            </div>
                        </div>

                        <aside className="rounded-3xl border border-[#ffffff1f] bg-[linear-gradient(165deg,rgba(255,68,51,0.18),rgba(255,255,255,0.03)_36%,rgba(255,255,255,0.06)_100%)] p-6 backdrop-blur-sm">
                            <p className="mb-3 text-xs uppercase tracking-[0.18em] text-[#ffc1b8]">Estado del sistema</p>
                            <h2 className="text-xl font-semibold text-white">Modo actual: uso propio</h2>
                            <p className="mt-2 text-sm leading-relaxed text-[#ddd9d4]">
                                Esta instancia opera para flujo interno, sin multi tenancy aun. El objetivo es velocidad de iteracion y revision controlada.
                            </p>
                            <ul className="mt-5 space-y-2 text-sm text-[#efece8]">
                                <li className="rounded-lg border border-[#ffffff1f] bg-[#0000002b] px-3 py-2">Campanas y runs centralizados</li>
                                <li className="rounded-lg border border-[#ffffff1f] bg-[#0000002b] px-3 py-2">Pipeline Argos a Mnemosine activo</li>
                                <li className="rounded-lg border border-[#ffffff1f] bg-[#0000002b] px-3 py-2">Revision humana en inbox antes de outreach</li>
                            </ul>
                        </aside>
                    </section>

                    <section className="rounded-3xl border border-[#ffffff1a] bg-[#0f1418cc] p-5 sm:p-7">
                        <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p className="text-xs uppercase tracking-[0.18em] text-[#ffb278]">Pipeline de Agentes</p>
                                <h3 className="mt-1 font-['Space_Grotesk'] text-2xl font-semibold text-white">De deteccion a revision en una sola corrida</h3>
                            </div>
                            <p className="text-sm text-[#b3b2ad]">Jobs encadenados por queue y artefactos persistidos por etapa</p>
                        </div>

                        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            {agents.map((agent, index) => (
                                <article
                                    key={agent.name}
                                    className="group rounded-2xl border border-[#ffffff1a] bg-[#0a0e12] p-4 transition hover:border-[#ff44336b] hover:bg-[#111820]"
                                >
                                    <div className="mb-3 flex items-center justify-between">
                                        <span className="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#ff44331f] font-['Space_Grotesk'] text-sm font-bold text-[#ff8a7f]">
                                            {index + 1}
                                        </span>
                                        <span className="text-[11px] uppercase tracking-[0.14em] text-[#ffb278]">{agent.stage}</span>
                                    </div>
                                    <h4 className="font-semibold text-white">{agent.name}</h4>
                                    <p className="mt-1 text-sm leading-relaxed text-[#bab7b1]">{agent.desc}</p>
                                </article>
                            ))}
                        </div>
                    </section>
                </div>
            </div>
        </>
    );
}
