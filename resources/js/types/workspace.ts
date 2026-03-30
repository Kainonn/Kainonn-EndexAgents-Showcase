export type EffectiveMessage = {
    id: number | null;
    subject: string;
    body: string;
    preview: string;
    source: 'human_edited' | 'ai_generated' | 'fallback';
    updated_at: string | null;
};

export type WorkspaceCard = {
    id: number;
    company_name: string;
    city: string | null;
    sector: string | null;
    score: number | null;
    operational_priority: string;
    operational_priority_label: string;
    operational_priority_emoji: string;
    commercial_status: string;
    commercial_status_label: string;
    commercial_status_color: string;
    primary_problem: string | null;
    sales_angle: string | null;
    recommended_channel: string;
    recommended_channel_label: string;
    recommended_channel_emoji: string;
    next_step: string;
    detail_ready: boolean;

    effective_message: EffectiveMessage;
    effective_message_id: number | null;
    effective_message_subject: string;
    effective_message_body: string;
    effective_message_preview: string;
    effective_message_source: 'human_edited' | 'ai_generated' | 'fallback';
    effective_message_updated_at: string | null;

    ready_message_preview: string | null;
    has_full_message: boolean;
    quick_tip: string | null;
    closing_priority: 'ALTO' | 'MEDIO' | 'BAJO' | 'INCOMPLETO' | string;
    closing_priority_label: string;
    closing_priority_source: 'minos' | 'score_fallback' | string;
    contact_recommendation: string | null;
    commercial_notes: string | null;
    latest_note: string | null;
    last_action_summary: string | null;
    last_contacted_at: string | null;
    next_follow_up_at: string | null;
    follow_up_overdue: boolean;

    contact: {
        whatsapp: string | null;
        phone: string | null;
        email: string | null;
        contact_name: string | null;
    };

    contacts: {
        whatsapp: string | null;
        phone: string | null;
        email: string | null;
        name: string | null;
    };

    findings: Array<{
        id: number;
        agent_name: string;
        summary: string | null;
        confidence: string | null;
        stage: string | null;
    }>;

    offer: {
        id: number;
        type: string;
        summary: string | null;
        price_min: number | null;
        price_max: number | null;
    } | null;

    latest_commercial_action: {
        type: string;
        label: string;
        channel: string | null;
        notes: string | null;
        occurred_at: string | null;
    } | null;

    quick_actions: {
        can_contact_now: boolean;
        can_edit_message: boolean;
        can_open_detail: boolean;
    };

    review_url: string | null;
};

export type WorkspaceFilters = {
    status: string;
    priority: string;
    search: string;
    quick_filter: string | null;
    follow_up_window: string | null;
};

export type StatusOptionValue = {
    label: string;
    color: string;
};

export type StatusOptions = Record<string, StatusOptionValue>;

export type PriorityOption = {
    label: string;
    emoji: string;
};

export type PriorityOptions = Record<string, PriorityOption>;

export type KpiData = {
    waiting_response: number;
    overdue_follow_ups: number;
    closed_this_week: number;
    contacted_today: number;
    follow_ups_due_today: number;
    follow_ups_completed_today: number;
    waiting_response_stale_48h: number;
};

export type WorkQueueItem = {
    card: WorkspaceCard;
    queue_group: 'overdue_follow_up' | 'due_today_follow_up' | 'stale_waiting_response' | 'new_without_contact' | 'default';
    queue_reason: string;
    sla_tone: 'critical' | 'warning' | 'neutral';
};

export type WorkspacePageProps = {
    cards: WorkspaceCard[];
    workQueue: WorkQueueItem[];
    todayCount: number;
    statusCounts: Record<string, number>;
    priorityCounts: Record<string, number>;
    kpis: KpiData;
    filters: WorkspaceFilters;
    statusOptions: StatusOptions;
    priorityOptions: PriorityOptions;
};

export type QuickDetailData = {
    id: number;
    company_name: string;
    city: string | null;
    sector: string | null;
    website_url: string | null;
    campaign_name: string | null;
    score_value: number | null;
    commercial_status: string | null;
    operational_priority: string | null;
    primary_problem: string | null;
    sales_angle: string | null;
    recommended_channel: string | null;
    next_step: string;
    detail_ready: boolean;

    score: {
        total: number | null;
        urgency: number | null;
        fit: number | null;
        payment_capacity: number | null;
    };

    contact: {
        email: string | null;
        phone: string | null;
        whatsapp: string | null;
        contact_name: string | null;
        contact_form_url: string | null;
    };

    contact_list: Array<{
        id: number;
        contact_name: string | null;
        email: string | null;
        phone: string | null;
        whatsapp: string | null;
        contact_form_url: string | null;
    }>;

    contacts: {
        whatsapp: string | null;
        phone: string | null;
        email: string | null;
        name: string | null;
    };

    message: {
        id: number | null;
        subject: string | null;
        body: string;
        tone: string | null;
        version: number | null;
        updated_at: string | null;
        source: 'human_edited' | 'ai_generated' | 'fallback';
    } | null;

    effective_message: EffectiveMessage;

    findings: Array<{
        id: number;
        agent_name: string;
        summary: string | null;
        confidence: string | null;
        stage: string | null;
    }>;

    offer: {
        id: number;
        type: string;
        summary: string | null;
        price_min: number | null;
        price_max: number | null;
    } | null;

    commercial: {
        status: string;
        status_label: string;
        operational_priority: string;
        primary_problem: string | null;
        sales_angle: string | null;
        recommended_channel: string;
        quick_tip: string | null;
        commercial_notes: string | null;
        last_contacted_at: string | null;
        next_follow_up_at: string | null;
    };

    insights: {
        argos_summary: string | null;
        hefesto_summary: string | null;
        maps_url: string;
    };

    recent_actions: Array<{
        type: string;
        label: string;
        channel: string | null;
        notes: string | null;
        occurred_at: string | null;
    }>;

    latest_commercial_action: {
        type: string;
        label: string;
        channel: string | null;
        notes: string | null;
        occurred_at: string | null;
    } | null;

    status_history: Array<{
        from: string;
        to: string;
        reason: string | null;
        changed_at: string | null;
    }>;

    latest_note: string | null;
    review_url: string;
};

export type ContactPreparationResult = {
    channel: string;
    channel_label: string;
    whatsapp_url?: string;
    tel_url?: string;
    mailto_url?: string;
    copied_text: string;
    contact_name: string | null;
    phone_clean?: string;
    speech_snippet?: string;
};
