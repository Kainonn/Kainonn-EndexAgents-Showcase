import { Head, router } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { ContactPreparationResult, WorkspaceCard, WorkspacePageProps } from '@/types/workspace';
import { ContactFeedbackToast, type FeedbackState } from '@/components/workspace/contact-feedback-toast';
import { LeadDecisionCard } from '@/components/workspace/lead-decision-card';
import { LeadDetailDrawer } from '@/components/workspace/lead-detail-drawer';
import { LeadManageModal } from '@/components/workspace/lead-manage-modal';
import { LeadMessageEditorModal } from '@/components/workspace/lead-message-editor-modal';
import { SalesWorkQueue } from '@/components/workspace/sales-work-queue';
import { TodayLeadsBanner } from '@/components/workspace/today-leads-banner';
import { WorkspaceEmptyState } from '@/components/workspace/workspace-empty-state';
import { WorkspaceHeader } from '@/components/workspace/workspace-header';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Endex', href: '/dashboard' },
    { title: 'Centro de Ataque', href: '/endex/workspace' },
];

export default function LeadWorkspace({
    cards,
    workQueue,
    todayCount,
    statusCounts,
    priorityCounts,
    kpis,
    filters,
    statusOptions,
    priorityOptions,
}: WorkspacePageProps) {
    // Drawer state
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [drawerCard, setDrawerCard] = useState<WorkspaceCard | null>(null);

    // Message editor state
    const [messageModalOpen, setMessageModalOpen] = useState(false);
    const [messageCard, setMessageCard] = useState<WorkspaceCard | null>(null);

    // Unified management modal
    const [manageModalOpen, setManageModalOpen] = useState(false);
    const [manageCard, setManageCard] = useState<WorkspaceCard | null>(null);

    // Contact feedback
    const [feedback, setFeedback] = useState<FeedbackState>({
        visible: false,
        type: 'info',
        title: '',
        description: '',
    });
    const dismissFeedback = useCallback(() => {
        setFeedback((prev) => ({ ...prev, visible: false }));
    }, []);

    // Handlers
    const handleViewDetail = useCallback((card: WorkspaceCard) => {
        setDrawerCard(card);
        setDrawerOpen(true);
    }, []);

    const handleEditMessage = useCallback((card: WorkspaceCard) => {
        setMessageCard(card);
        setMessageModalOpen(true);
    }, []);

    const handleManageLead = useCallback((card: WorkspaceCard) => {
        setDrawerCard(card);
        setDrawerOpen(true);
        setManageCard(card);
        setManageModalOpen(true);
    }, []);

    const handleContactNow = useCallback(async (card: WorkspaceCard) => {
        try {
            const response = await fetch(`/endex/workspace/leads/${card.id}/prepare-contact`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                setFeedback({
                    visible: true,
                    type: 'error',
                    title: 'Error al preparar contacto',
                    description: 'No se pudo preparar el contacto. Intenta de nuevo.',
                });
                return;
            }

            const result: ContactPreparationResult = await response.json();

            // Copy to clipboard
            if (result.copied_text) {
                await navigator.clipboard.writeText(result.copied_text).catch(() => {});
            }

            // Open channel
            const channelUrl = result.whatsapp_url ?? result.tel_url ?? result.mailto_url;

            if (channelUrl) {
                const channelLabels: Record<string, string> = {
                    whatsapp: 'Abriendo WhatsApp',
                    phone: 'Abriendo llamada',
                    email: 'Abriendo email',
                };

                setFeedback({
                    visible: true,
                    type: 'success',
                    title: 'Mensaje copiado al portapapeles',
                    description: channelLabels[result.channel] ?? `Canal: ${result.channel_label}`,
                });

                // Small delay so the user sees the feedback before the tab switch
                setTimeout(() => {
                    window.open(channelUrl, '_blank', 'noopener');
                }, 300);
            } else {
                // No direct channel — show the text
                setFeedback({
                    visible: true,
                    type: 'info',
                    title: 'Mensaje copiado',
                    description: 'No se detectó canal directo. Usa el texto copiado para contactar manualmente.',
                });
            }
        } catch {
            setFeedback({
                visible: true,
                type: 'error',
                title: 'Error de red',
                description: 'No se pudo conectar con el servidor.',
            });
        }
    }, []);

    const handleShowToday = useCallback(() => {
        router.get('/endex/workspace', { priority: 'contact_today' }, {
            preserveState: true,
            preserveScroll: false,
            replace: true,
        });
    }, []);

    const showTodayBanner = filters.status === 'all'
        && filters.priority === 'all'
        && !filters.quick_filter
        && !filters.follow_up_window
        && todayCount > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Centro de Ataque Comercial" />

            <div className="space-y-4 p-4 md:p-6">
                {/* Header with filters */}
                <WorkspaceHeader
                    todayCount={todayCount}
                    totalFiltered={cards.length}
                    statusCounts={statusCounts}
                    priorityCounts={priorityCounts}
                    kpis={kpis}
                    filters={filters}
                    statusOptions={statusOptions}
                    priorityOptions={priorityOptions}
                />

                {/* Today banner (only on "all" view) */}
                {showTodayBanner && (
                    <TodayLeadsBanner count={todayCount} onShowToday={handleShowToday} />
                )}

                {/* Work queue by SLA */}
                <SalesWorkQueue
                    items={workQueue}
                    onContactNow={handleContactNow}
                    onManageLead={handleManageLead}
                    onViewDetail={handleViewDetail}
                />

                {/* Cards grid */}
                {cards.length > 0 ? (
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {cards.map((card) => (
                            <LeadDecisionCard
                                key={card.id}
                                card={card}
                                statusOptions={statusOptions}
                                onContactNow={handleContactNow}
                                onEditMessage={handleEditMessage}
                                onViewDetail={handleViewDetail}
                                onManageLead={handleManageLead}
                            />
                        ))}
                    </div>
                ) : (
                    <WorkspaceEmptyState>
                        No hay leads que coincidan con los filtros actuales.
                        Intenta cambiar la vista o buscar otro término.
                    </WorkspaceEmptyState>
                )}
            </div>

            {/* Drawer */}
            <LeadDetailDrawer
                open={drawerOpen}
                onOpenChange={setDrawerOpen}
                card={drawerCard}
                onContactNow={handleContactNow}
            />

            {/* Message editor */}
            {messageCard && (
                <LeadMessageEditorModal
                    open={messageModalOpen}
                    onOpenChange={setMessageModalOpen}
                    leadId={messageCard.id}
                    companyName={messageCard.company_name}
                    initialSubject={messageCard.effective_message_subject}
                    initialBody={messageCard.effective_message_body}
                    messageSource={messageCard.effective_message_source}
                    messageUpdatedAt={messageCard.effective_message_updated_at}
                />
            )}

            {/* Unified management modal */}
            <LeadManageModal
                open={manageModalOpen}
                onOpenChange={setManageModalOpen}
                card={manageCard}
                onSuccess={(message) => {
                    setFeedback({
                        visible: true,
                        type: 'success',
                        title: 'Gestión registrada',
                        description: message,
                    });
                }}
                onError={(message) => {
                    setFeedback({
                        visible: true,
                        type: 'error',
                        title: 'Error al registrar gestión',
                        description: message,
                    });
                }}
            />

            {/* Contact feedback toast */}
            <ContactFeedbackToast feedback={feedback} onDismiss={dismissFeedback} />
        </AppLayout>
    );
}
