import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { FormEventHandler } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const formatCurrencyRange = (min: number | null, max: number | null): string => {
    if (min === null && max === null) {
        return '-';
    }

    if (min !== null && max !== null) {
        return `$${min.toLocaleString('es-MX')} - $${max.toLocaleString('es-MX')}`;
    }

    if (min !== null) {
        return `Desde $${min.toLocaleString('es-MX')}`;
    }

    return `Hasta $${(max ?? 0).toLocaleString('es-MX')}`;
};

const getProbabilityLabel = (score: number | null): string => {
    if (score === null) {
        return 'INDETERMINADA';
    }

    if (score >= 80) {
        return 'ALTA';
    }

    if (score >= 60) {
        return 'MEDIA';
    }

    return 'BAJA';
};

type LeadReviewProps = {
    lead: {
        id: number;
        company_name: string;
        status: string;
        campaign: { name: string; solution_name: string };
        findings: Array<{
            id: number;
            agent_name: string;
            stage: string;
            summary: string;
            evidence?: Array<string | null>;
            payload?: Record<string, unknown> | null;
            confidence: string | null;
        }>;
        scores: Array<{
            id: number;
            total_score: number;
            urgency_score: number | null;
            fit_score: number | null;
            payment_capacity_score: number | null;
        }>;
        offers: Array<{
            id: number;
            offer_type: string;
            offer_summary: string | null;
            price_range_min: number | null;
            price_range_max: number | null;
        }>;
        messages: Array<{
            id: number;
            subject: string | null;
            body: string;
            tone: string | null;
            updated_at: string;
        }>;
        contacts: Array<{
            id: number;
            email: string | null;
            phone: string | null;
            contact_name: string | null;
        }>;
        insights: {
            maps_url: string;
            maps_place_id: string | null;
            rating: number | null;
            reviews_count: number | null;
            website: string | null;
            phone: string | null;
            email: string | null;
            city: string | null;
            sector: string | null;
            address: string | null;
            seo_ux: {
                http_status?: number;
                fetch_ok?: boolean;
                title?: string | null;
                meta_description?: string | null;
                h1_count?: number;
                forms_count?: number;
                cta_mentions?: number;
            } | null;
        };
        reviews: Array<{
            id: number;
            decision: string;
            review_notes: string | null;
            reviewed_at: string | null;
        }>;
        activities: Array<{
            id: number;
            actor_type: string;
            actor_name: string | null;
            event: string;
            occurred_at: string | null;
        }>;
        feedback: Array<{
            id: number;
            feedback_type: string;
            notes: string | null;
            created_at: string;
        }>;
    };
    nextPendingLeadId: number | null;
    sprintFiveReadiness: {
        ai_external_enabled: boolean;
        ai_provider: string;
        outreach_enabled: boolean;
        outreach_mode: string;
    };
    processingStatus: {
        is_processing: boolean;
        current_stage: string | null;
        stage_number: number | null;
        total_stages: number | null;
    };
    commercialFlow: {
        current_status: string;
        status_options: string[];
        allowed_next_statuses: string[];
    };
    feedbackOptions: string[];
};

export default function LeadReview({ lead, nextPendingLeadId, sprintFiveReadiness, processingStatus: initialProcessingStatus, commercialFlow, feedbackOptions }: LeadReviewProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Endex', href: '/dashboard' },
        { title: 'Lead Inbox', href: '/endex/leads/inbox' },
        { title: `Lead #${lead.id}`, href: `/endex/leads/${lead.id}/review` },
    ];

    const [processingStatus, setProcessingStatus] = useState(initialProcessingStatus);
    const [showCompleted, setShowCompleted] = useState(false);
    const [discarding, setDiscarding] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        decision: 'approve',
        review_notes: '',
        adjusted_message: '',
    });
    const { post: postReprocess, processing: processingReprocess } = useForm({});
    const {
        data: commercialData,
        setData: setCommercialData,
        patch: patchCommercial,
        processing: processingCommercial,
    } = useForm({
        commercial_status: commercialFlow.current_status,
    });
    const {
        data: feedbackData,
        setData: setFeedbackData,
        post: postFeedback,
        processing: processingFeedback,
    } = useForm({
        feedback_type: 'buen_lead',
        notes: '',
    });
    const {
        data: messageData,
        setData: setMessageData,
        patch: patchMessage,
        processing: processingMessage,
    } = useForm({
        subject: '',
        body: '',
    });

    useEffect(() => {
        let isMounted = true;

        const syncProcessingStatus = async () => {
            try {
                const response = await fetch(`/endex/leads/${lead.id}/review/progress`);

                if (!response.ok) {
                    return;
                }

                const latestStatus = await response.json();

                if (isMounted) {
                    setProcessingStatus(latestStatus);
                }
            } catch (error) {
                console.error('❌ Error al sincronizar progreso inicial:', error);
            }
        };

        void syncProcessingStatus();

        return () => {
            isMounted = false;
        };
    }, [lead.id]);

    useEffect(() => {
        if (!processingStatus.is_processing && !showCompleted) {
            return;
        }

        const intervalId = setInterval(async () => {
            try {
                const response = await fetch(`/endex/leads/${lead.id}/review/progress`);
                const data = await response.json();
                console.log('🔄 Progress update:', data);
                setProcessingStatus(data);

                if (!data.is_processing && processingStatus.is_processing) {
                    clearInterval(intervalId);
                    console.log('✅ Processing completed!');
                    // Mostrar estado completado por 3 segundos antes de recargar
                    setShowCompleted(true);
                    setTimeout(() => {
                        console.log('🔄 Reloading page...');
                        window.location.reload();
                    }, 3000);
                }
            } catch (error) {
                console.error('❌ Error al consultar progreso:', error);
            }
        }, 1000);

        return () => clearInterval(intervalId);
    }, [processingStatus.is_processing, showCompleted, lead.id]);

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(`/endex/leads/${lead.id}/review`);
    };

    const triggerReprocess = (): void => {
        console.log('🚀 Iniciando reprocesamiento...');
        // Resetear estado completado
        setShowCompleted(false);
        // Actualizar estado local inmediatamente
        setProcessingStatus({
            is_processing: true,
            current_stage: 'Argos',
            stage_number: 1,
            total_stages: 10,
        });

        postReprocess(`/endex/leads/${lead.id}/review/reprocess`, {
            preserveScroll: true,
            onSuccess: () => {
                console.log('✓ Reprocesamiento despachado');
                // El polling ya debería estar corriendo gracias al useEffect
            },
            onError: (errors) => {
                console.error('❌ Error al reprocesar:', errors);
            },
        });
    };

    const score = lead.scores[0];
    const offer = lead.offers[0];
    const message = lead.messages[0];
    const contact = lead.contacts[0];
    const insights = lead.insights;
    const seo = insights?.seo_ux;
    const hestiaFinding = lead.findings.find((finding) => finding.agent_name === 'Hestia');
    const mnemosineFinding = lead.findings.find((finding) => finding.agent_name === 'Mnemosine');
    const externalAiFindings = lead.findings.filter(
        (finding) => finding.payload && (finding.payload.analysis_source as string | undefined) === 'showcase_stub',
    );

    const scoreValue = score?.total_score ?? null;
    const probability = getProbabilityLabel(scoreValue);
    const opportunityRange = formatCurrencyRange(offer?.price_range_min ?? null, offer?.price_range_max ?? null);
    const suggestedChannel = contact?.phone || insights.phone
        ? 'WhatsApp'
        : (contact?.email || insights.email ? 'Email' : 'Llamada');

    const humanFindings: string[] = [];

    if ((insights.reviews_count ?? 0) >= 600) {
        humanFindings.push('600+ reseñas: negocio consolidado y con demanda activa.');
    }

    if ((seo?.forms_count ?? 0) === 0) {
        humanFindings.push('No se detecta portal o formularios útiles para captación de pacientes.');
    }

    if ((seo?.cta_mentions ?? 0) <= 1) {
        humanFindings.push('Bajo nivel de llamados a la acción en sitio web.');
    }

    lead.findings.slice(0, 5).forEach((finding) => {
        if (humanFindings.length < 3 && finding.summary.trim() !== '') {
            humanFindings.push(finding.summary);
        }
    });

    const summaryDetections = humanFindings.slice(0, 3);
    const whatsHappening = mnemosineFinding?.summary
        ?? hestiaFinding?.summary
        ?? 'Hay interés potencial, pero la conversión puede estar limitada por fricción operativa y seguimiento comercial manual.';

    const offerFocus = [
        `Solución foco: ${lead.campaign.solution_name}.`,
        offer?.offer_summary ?? 'Propuesta: automatizar agenda, seguimiento y experiencia digital para cerrar más citas.',
        `Canal recomendado para primer contacto: ${suggestedChannel}.`,
    ];

    useEffect(() => {
        setMessageData('subject', message?.subject ?? '');
        setMessageData('body', message?.body ?? '');
    }, [message?.id, message?.subject, message?.body, setMessageData]);

    const setReadyToContact = (): void => {
        router.patch(`/endex/leads/${lead.id}/commercial-status`, {
            commercial_status: 'ready_to_contact',
        }, {
            preserveScroll: true,
        });
    };

    const saveCommercialStatus = (): void => {
        patchCommercial(`/endex/leads/${lead.id}/commercial-status`, {
            preserveScroll: true,
        });
    };

    const rejectWithFeedback = (): void => {
        postFeedback(`/endex/leads/${lead.id}/feedback`, {
            preserveScroll: true,
        });
    };

    const saveMessage = (): void => {
        patchMessage(`/endex/leads/${lead.id}/message`, {
            preserveScroll: true,
        });
    };

    const quickDiscard = (): void => {
        setDiscarding(true);

        router.post(`/endex/leads/${lead.id}/review`, {
            decision: 'discard',
            review_notes: feedbackData.notes,
            adjusted_message: '',
        }, {
            preserveScroll: true,
            onFinish: () => setDiscarding(false),
        });
    };

    const scrollToMessage = (): void => {
        const target = document.getElementById('mensaje-sugerido');

        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Lead Review" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <div className="grid gap-4 lg:grid-cols-[1fr_auto]">
                        <div>
                            <p className="text-xs uppercase tracking-wide text-muted-foreground">Panel de cierre</p>
                            <div className="mt-1 flex flex-wrap items-center gap-2">
                                <h1 className="text-2xl font-semibold">{lead.company_name}</h1>
                                <span className="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-900/40 dark:text-rose-200">
                                    {scoreValue !== null && scoreValue >= 80 ? 'ALTO' : scoreValue !== null && scoreValue >= 60 ? 'MEDIO' : 'POR REVISAR'}
                                </span>
                            </div>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {insights.city ?? 'Sin ciudad'}{insights.sector ? `, ${insights.sector}` : ''}
                            </p>

                            <div className="mt-4 grid gap-3 sm:grid-cols-3">
                                <article className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">Score</p>
                                    <p className="mt-1 text-lg font-semibold">{scoreValue ?? '-'}</p>
                                </article>
                                <article className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">Probabilidad</p>
                                    <p className="mt-1 text-lg font-semibold">{probability}</p>
                                </article>
                                <article className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">Oportunidad</p>
                                    <p className="mt-1 text-lg font-semibold">{opportunityRange}</p>
                                </article>
                            </div>

                            <div className="mt-4 flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    onClick={setReadyToContact}
                                    disabled={processingCommercial}
                                    className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                                >
                                    {processingCommercial ? 'Aplicando...' : 'Contactar'}
                                </button>
                                <button
                                    type="button"
                                    onClick={scrollToMessage}
                                    className="rounded-md border px-4 py-2 text-sm font-medium"
                                >
                                    Ver mensaje
                                </button>
                                <button
                                    type="button"
                                    onClick={quickDiscard}
                                    disabled={discarding}
                                    className="rounded-md border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700 disabled:opacity-60 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-200"
                                >
                                    {discarding ? 'Descartando...' : 'Descartar'}
                                </button>
                            </div>
                        </div>

                        <div className="flex flex-col items-start gap-2">
                            <button
                                type="button"
                                onClick={triggerReprocess}
                                disabled={processingReprocess || processingStatus.is_processing}
                                className="rounded-md border px-3 py-1.5 text-sm font-medium disabled:opacity-60"
                            >
                                {processingReprocess ? 'Reprocesando...' : 'Reprocesar lead'}
                            </button>
                            <p className="text-xs text-muted-foreground">estado comercial: {commercialFlow.current_status}</p>
                            <Link href="/endex/leads/inbox" className="text-sm underline">
                                Volver al inbox
                            </Link>
                        </div>
                    </div>

                    {(processingStatus.is_processing || showCompleted) && (
                        <div className={`mt-4 rounded-lg border p-4 ${showCompleted ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950' : 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950'}`}>
                            <div className="mb-2 flex items-center justify-between text-sm">
                                <span className={`font-medium ${showCompleted ? 'text-green-900 dark:text-green-100' : 'text-blue-900 dark:text-blue-100'}`}>
                                    {showCompleted ? '¡Procesamiento completado! ✅' : `Procesando con ${processingStatus.current_stage}...`}
                                </span>
                                <span className={showCompleted ? 'text-green-700 dark:text-green-300' : 'text-blue-700 dark:text-blue-300'}>
                                    {showCompleted ? '10/10' : `${processingStatus.stage_number} / ${processingStatus.total_stages}`}
                                </span>
                            </div>
                            <div className={`h-2 w-full overflow-hidden rounded-full ${showCompleted ? 'bg-green-200 dark:bg-green-900' : 'bg-blue-200 dark:bg-blue-900'}`}>
                                <div
                                    className={`h-full transition-all duration-500 ${showCompleted ? 'bg-green-600 dark:bg-green-400' : 'bg-blue-600 dark:bg-blue-400'}`}
                                    style={{
                                        width: `${((processingStatus.stage_number || 0) / (processingStatus.total_stages || 10)) * 100}%`,
                                    }}
                                />
                            </div>
                            <p className="mt-2 text-xs text-blue-700 dark:text-blue-300">
                                La página se actualizará automáticamente cuando termine el procesamiento.
                            </p>
                        </div>
                    )}

                    <div className="mt-4 grid gap-3 md:grid-cols-4">
                        <article className="rounded-lg border p-3">
                            <p className="text-xs text-muted-foreground">AI externa</p>
                            <p className="mt-1 text-sm font-medium">
                                {sprintFiveReadiness.ai_external_enabled ? 'habilitada' : 'deshabilitada'}
                            </p>
                            <p className="text-xs text-muted-foreground">provider: {sprintFiveReadiness.ai_provider}</p>
                        </article>

                        <article className="rounded-lg border p-3">
                            <p className="text-xs text-muted-foreground">Outreach controlado</p>
                            <p className="mt-1 text-sm font-medium">
                                {sprintFiveReadiness.outreach_enabled ? 'habilitado' : 'deshabilitado'}
                            </p>
                            <p className="text-xs text-muted-foreground">modo: {sprintFiveReadiness.outreach_mode}</p>
                        </article>

                        <article className="rounded-lg border p-3">
                            <p className="text-xs text-muted-foreground">Agentes usando IA externa</p>
                            <p className="mt-1 text-sm font-medium">
                                {externalAiFindings.length} agentes IA externa de {lead.findings.length || 0} agentes ejecutados
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {externalAiFindings.length > 0
                                    ? `Usaron IA externa: ${externalAiFindings.map((finding) => finding.agent_name).join(', ')}`
                                    : 'Sin ejecucion externa en este lead'}
                            </p>
                        </article>

                        <article className="rounded-lg border p-3">
                            <p className="text-xs text-muted-foreground">Siguiente en cola</p>
                            {nextPendingLeadId ? (
                                <Link
                                    href={`/endex/leads/${nextPendingLeadId}/review`}
                                    className="mt-1 inline-block text-sm font-medium underline"
                                >
                                    Abrir lead #{nextPendingLeadId}
                                </Link>
                            ) : (
                                <p className="mt-1 text-sm text-muted-foreground">No hay otro lead pendiente.</p>
                            )}
                        </article>
                    </div>
                </section>

                <section className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <h2 className="text-base font-semibold">Que detectamos</h2>
                    <ul className="mt-3 list-disc space-y-1 pl-5 text-sm">
                        {summaryDetections.map((line, index) => (
                            <li key={`detection-${index}`}>{line}</li>
                        ))}
                        {summaryDetections.length === 0 && (
                            <li className="text-muted-foreground">Sin hallazgos resumidos disponibles.</li>
                        )}
                    </ul>

                    <h3 className="mt-4 text-sm font-semibold">Que esta pasando</h3>
                    <p className="mt-1 text-sm text-muted-foreground">{whatsHappening}</p>

                    <h3 className="mt-4 text-sm font-semibold">Que vender</h3>
                    <ul className="mt-1 list-disc space-y-1 pl-5 text-sm text-muted-foreground">
                        {offerFocus.map((line, index) => (
                            <li key={`offer-focus-${index}`}>{line}</li>
                        ))}
                    </ul>
                </section>

                <section id="mensaje-sugerido" className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <h2 className="text-base font-semibold">Accion comercial</h2>
                    <div className="mt-3 grid gap-4 lg:grid-cols-2">
                        <article className="rounded-lg border p-3 text-sm">
                            <h3 className="text-sm font-semibold">Mensaje sugerido</h3>
                            <label className="mt-2 grid gap-1 text-sm">
                                Asunto
                                <input
                                    className="rounded-md border px-3 py-2"
                                    value={messageData.subject}
                                    onChange={(event) => setMessageData('subject', event.target.value)}
                                />
                            </label>

                            <label className="mt-2 grid gap-1 text-sm">
                                Mensaje
                                <textarea
                                    className="min-h-28 rounded-md border px-3 py-2"
                                    value={messageData.body}
                                    onChange={(event) => setMessageData('body', event.target.value)}
                                />
                            </label>

                            <button
                                type="button"
                                onClick={saveMessage}
                                disabled={processingMessage || messageData.body.trim() === ''}
                                className="mt-3 rounded-md bg-emerald-600 px-3 py-2 text-sm font-medium text-white disabled:opacity-60"
                            >
                                {processingMessage ? 'Guardando...' : 'Guardar mensaje'}
                            </button>
                        </article>

                        <article className="rounded-lg border p-3 text-sm">
                            <h3 className="text-sm font-semibold">Contacto</h3>
                            <div className="mt-2 space-y-2 text-muted-foreground">
                                <p>
                                    Email: <span className="font-medium text-foreground">{contact?.email ?? insights.email ?? '-'}</span>
                                </p>
                                <p>
                                    Telefono: <span className="font-medium text-foreground">{contact?.phone ?? insights.phone ?? '-'}</span>
                                </p>
                                <p>
                                    Nombre: <span className="font-medium text-foreground">{contact?.contact_name ?? '-'}</span>
                                </p>
                                <p>
                                    Canal recomendado: <span className="font-medium text-foreground">{suggestedChannel}</span>
                                </p>
                            </div>

                            {message && (
                                <p className="mt-3 text-xs text-muted-foreground">
                                    Ultima actualizacion del mensaje: {new Date(message.updated_at).toLocaleString('es-MX')}
                                </p>
                            )}
                        </article>
                    </div>
                </section>

                <section className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <h2 className="text-base font-semibold">Estatus comercial</h2>
                    <div className="mt-3 grid gap-3 md:grid-cols-3">
                        <label className="grid gap-1 text-sm">
                            Estado comercial
                            <select
                                className="rounded-md border px-3 py-2"
                                value={commercialData.commercial_status}
                                onChange={(event) => setCommercialData('commercial_status', event.target.value)}
                            >
                                {commercialFlow.status_options.map((status) => (
                                    <option key={status} value={status}>
                                        {status}
                                    </option>
                                ))}
                            </select>
                        </label>

                        <button
                            type="button"
                            onClick={saveCommercialStatus}
                            disabled={processingCommercial}
                            className="rounded-md border px-3 py-2 text-sm font-medium disabled:opacity-60"
                        >
                            Guardar estado comercial
                        </button>

                        <label className="grid gap-1 text-sm md:col-span-3">
                            Notas
                            <textarea
                                className="min-h-20 rounded-md border px-3 py-2"
                                value={feedbackData.notes}
                                onChange={(event) => setFeedbackData('notes', event.target.value)}
                                placeholder="Notas de contexto para seguimiento o descarte"
                            />
                        </label>

                        <label className="grid gap-1 text-sm">
                            Tipo de feedback
                            <select
                                className="rounded-md border px-3 py-2"
                                value={feedbackData.feedback_type}
                                onChange={(event) => setFeedbackData('feedback_type', event.target.value)}
                            >
                                {feedbackOptions.map((option) => (
                                    <option key={option} value={option}>
                                        {option}
                                    </option>
                                ))}
                            </select>
                        </label>

                        <button
                            type="button"
                            onClick={rejectWithFeedback}
                            disabled={processingFeedback}
                            className="rounded-md border px-3 py-2 text-sm font-medium disabled:opacity-60"
                        >
                            {processingFeedback ? 'Guardando...' : 'Guardar notas'}
                        </button>
                    </div>
                </section>

                <section className="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
                    <details>
                        <summary className="cursor-pointer text-sm font-semibold">Ver analisis completo (tecnico)</summary>

                        <div className="mt-4 grid gap-4 lg:grid-cols-2">
                            <article className="rounded-lg border p-3 text-sm">
                                <h3 className="text-sm font-semibold">Contexto tecnico</h3>
                                <div className="mt-2 space-y-2 text-muted-foreground">
                                    <p>Campana: <span className="font-medium text-foreground">{lead.campaign.name}</span></p>
                                    <p>Estado de lead: <span className="font-medium text-foreground">{lead.status}</span></p>
                                    <p>Offer type: <span className="font-medium text-foreground">{offer?.offer_type ?? '-'}</span></p>
                                    <p>Place ID: <span className="font-medium text-foreground">{insights.maps_place_id ?? '-'}</span></p>
                                    <p>
                                        Maps: <a href={insights.maps_url} target="_blank" rel="noreferrer" className="underline">abrir enlace</a>
                                    </p>
                                    <p>Sitio: {insights.website ? (
                                        <a href={insights.website} target="_blank" rel="noreferrer" className="underline">{insights.website}</a>
                                    ) : '-'} </p>
                                    <p>HTTP/H1/Form/CTA: <span className="font-medium text-foreground">{seo?.http_status ?? '-'} / {seo?.h1_count ?? '-'} / {seo?.forms_count ?? '-'} / {seo?.cta_mentions ?? '-'}</span></p>
                                </div>
                            </article>

                            <article className="rounded-lg border p-3 text-sm">
                                <h3 className="text-sm font-semibold">Historial de revision</h3>
                                <ul className="mt-2 space-y-2">
                                    {lead.reviews.map((review) => (
                                        <li key={review.id} className="rounded border p-2">
                                            <p className="font-medium">{review.decision}</p>
                                            <p className="text-muted-foreground">{review.review_notes ?? 'Sin notas.'}</p>
                                        </li>
                                    ))}
                                    {lead.reviews.length === 0 && (
                                        <li className="text-muted-foreground">Sin revisiones previas.</li>
                                    )}
                                </ul>

                                <form onSubmit={submit} className="mt-4 space-y-3">
                                    <label className="grid gap-1 text-sm">
                                        Decision
                                        <select
                                            className="rounded-md border px-3 py-2"
                                            value={data.decision}
                                            onChange={(event) => setData('decision', event.target.value)}
                                        >
                                            <option value="approve">Aprobar</option>
                                            <option value="needs_adjustment">Ajustar</option>
                                            <option value="discard">Descartar</option>
                                        </select>
                                        {errors.decision && <span className="text-xs text-red-600">{errors.decision}</span>}
                                    </label>

                                    <label className="grid gap-1 text-sm">
                                        Notas de revision
                                        <textarea
                                            className="min-h-20 rounded-md border px-3 py-2"
                                            value={data.review_notes}
                                            onChange={(event) => setData('review_notes', event.target.value)}
                                            placeholder="Comentarios de aprobacion o ajuste"
                                        />
                                    </label>

                                    <label className="grid gap-1 text-sm">
                                        Mensaje ajustado (opcional)
                                        <textarea
                                            className="min-h-24 rounded-md border px-3 py-2"
                                            value={data.adjusted_message}
                                            onChange={(event) => setData('adjusted_message', event.target.value)}
                                        />
                                    </label>

                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="rounded-md border px-4 py-2 text-sm font-medium disabled:opacity-60"
                                    >
                                        {processing ? 'Guardando...' : 'Guardar decision'}
                                    </button>
                                </form>
                            </article>
                        </div>

                        <div className="mt-4 rounded-lg border p-3">
                            <h3 className="text-sm font-semibold">Timeline de actividades</h3>
                            <ul className="mt-2 space-y-2 text-sm">
                                {lead.activities.slice(0, 10).map((activity) => (
                                    <li key={activity.id} className="rounded border p-2">
                                        <p className="font-medium">{activity.event}</p>
                                        <p className="text-muted-foreground">
                                            {activity.actor_type} {activity.actor_name ? `• ${activity.actor_name}` : ''}
                                        </p>
                                        <p className="text-xs text-muted-foreground">{activity.occurred_at ?? '-'}</p>
                                    </li>
                                ))}
                                {lead.activities.length === 0 && (
                                    <li className="text-muted-foreground">No hay actividades registradas.</li>
                                )}
                            </ul>
                        </div>

                        <div className="mt-4 space-y-2">
                            {lead.findings.map((finding) => (
                                <details key={`artifact-${finding.id}`} className="rounded-lg border p-3">
                                    <summary className="cursor-pointer list-none text-sm font-medium">
                                        {finding.agent_name} • {finding.stage} • conf: {finding.confidence ?? '-'}
                                    </summary>

                                    <div className="mt-3 space-y-2 text-sm text-muted-foreground">
                                        {finding.payload && (
                                            <div className="grid gap-2 md:grid-cols-4">
                                                <p className="rounded border bg-neutral-50 px-2 py-1 text-xs dark:bg-neutral-900">
                                                    <span className="font-medium text-foreground">Fuente:</span>{' '}
                                                    {String(finding.payload.analysis_source ?? '-')}
                                                </p>
                                                <p className="rounded border bg-neutral-50 px-2 py-1 text-xs dark:bg-neutral-900">
                                                    <span className="font-medium text-foreground">Modelo:</span>{' '}
                                                    {String(finding.payload.model_used ?? '-')}
                                                </p>
                                                <p className="rounded border bg-neutral-50 px-2 py-1 text-xs dark:bg-neutral-900">
                                                    <span className="font-medium text-foreground">Context digest:</span>{' '}
                                                    {String(finding.payload.knowledge_digest ?? '-')}
                                                </p>
                                                <p className="rounded border bg-neutral-50 px-2 py-1 text-xs dark:bg-neutral-900">
                                                    <span className="font-medium text-foreground">Knowledge files:</span>{' '}
                                                    {Array.isArray(finding.payload.knowledge_files_used)
                                                        ? finding.payload.knowledge_files_used.join(', ')
                                                        : '-'}
                                                </p>
                                            </div>
                                        )}

                                        <p>
                                            <span className="font-medium text-foreground">Resumen:</span> {finding.summary}
                                        </p>

                                        {finding.evidence && finding.evidence.filter(Boolean).length > 0 && (
                                            <div>
                                                <p className="font-medium text-foreground">Evidencia</p>
                                                <ul className="mt-1 list-disc space-y-1 pl-5">
                                                    {finding.evidence
                                                        .filter((item): item is string => Boolean(item))
                                                        .map((item, index) => (
                                                            <li key={`${finding.id}-evidence-${index}`}>{item}</li>
                                                        ))}
                                                </ul>
                                            </div>
                                        )}

                                        {finding.payload && (
                                            <div>
                                                <p className="font-medium text-foreground">Payload</p>
                                                <pre className="mt-1 max-h-72 overflow-auto rounded border bg-neutral-50 p-2 text-xs dark:bg-neutral-900">
                                                    {JSON.stringify(finding.payload, null, 2)}
                                                </pre>
                                            </div>
                                        )}
                                    </div>
                                </details>
                            ))}

                            {lead.findings.length === 0 && (
                                <p className="text-sm text-muted-foreground">Sin artefactos aun.</p>
                            )}
                        </div>
                    </details>
                </section>
            </div>
        </AppLayout>
    );
}
