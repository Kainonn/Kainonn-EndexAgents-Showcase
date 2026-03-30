<?php

return [
    'ai' => [
        'external_enabled' => (bool) env('ENDEX_AI_EXTERNAL_ENABLED', false),
        'provider' => env('ENDEX_AI_PROVIDER', 'showcase_stub'),
        'endpoint' => env('ENDEX_AI_ENDPOINT', 'http://localhost:8000/mock-ai'),
        'model' => env('ENDEX_AI_MODEL'),
        'models' => [
            'lead_detection' => env('ENDEX_AI_MODEL_LEAD_DETECTION', env('ENDEX_AI_MODEL', 'showcase-model')),
            'web_audit' => env('ENDEX_AI_MODEL_WEB_AUDIT', env('ENDEX_AI_MODEL', 'showcase-model')),
            'opportunity' => env('ENDEX_AI_MODEL_OPPORTUNITY', env('ENDEX_AI_MODEL', 'showcase-model')),
            'scoring' => env('ENDEX_AI_MODEL_SCORING', env('ENDEX_AI_MODEL', 'showcase-model')),
            'offer_classification' => env('ENDEX_AI_MODEL_OFFER_CLASSIFICATION', env('ENDEX_AI_MODEL', 'showcase-model')),
            'contact_extraction' => env('ENDEX_AI_MODEL_CONTACT_EXTRACTION', env('ENDEX_AI_MODEL', 'showcase-model')),
            'message_generation' => env('ENDEX_AI_MODEL_MESSAGE_GENERATION', env('ENDEX_AI_MODEL', 'showcase-model')),
            'proposal_generation' => env('ENDEX_AI_MODEL_PROPOSAL_GENERATION', env('ENDEX_AI_MODEL', 'showcase-model')),
            'compliance_check' => env('ENDEX_AI_MODEL_COMPLIANCE_CHECK', env('ENDEX_AI_MODEL', 'showcase-model')),
        ],
        'system_prompt' => env('ENDEX_AI_SYSTEM_PROMPT', ''),
        'context_type' => env('ENDEX_AI_CONTEXT_TYPE', 'showcase'),
        'max_knowledge_chars' => (int) env('ENDEX_AI_MAX_KNOWLEDGE_CHARS', 6000),
        'timeout_seconds' => (int) env('ENDEX_AI_TIMEOUT_SECONDS', 25),
    ],

    'maps' => [
        'enabled' => (bool) env('ENDEX_MAPS_ENABLED', false),
        'base_url' => env('ENDEX_MAPS_BASE_URL', 'https://example.invalid/maps/search'),
        'discovery_url' => env('ENDEX_MAPS_DISCOVERY_URL', 'https://example.invalid/discovery'),
        'website_lookup_enabled' => (bool) env('ENDEX_MAPS_WEBSITE_LOOKUP_ENABLED', false),
        'website_lookup_url' => env('ENDEX_MAPS_WEBSITE_LOOKUP_URL', 'https://example.invalid/lookup'),
        'timeout_seconds' => (int) env('ENDEX_MAPS_TIMEOUT_SECONDS', 12),
        'user_agent' => env('ENDEX_MAPS_USER_AGENT', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
    ],

    'web_audit' => [
        'enabled' => (bool) env('ENDEX_WEB_AUDIT_ENABLED', false),
        'timeout_seconds' => (int) env('ENDEX_WEB_AUDIT_TIMEOUT_SECONDS', 12),
        'user_agent' => env('ENDEX_WEB_AUDIT_USER_AGENT', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
    ],

    'outreach' => [
        'enabled' => (bool) env('ENDEX_OUTREACH_ENABLED', false),
        'mode' => env('ENDEX_OUTREACH_MODE', 'manual_review_only'),
        'daily_send_limit' => (int) env('ENDEX_OUTREACH_DAILY_SEND_LIMIT', 25),
        'channel' => env('ENDEX_OUTREACH_CHANNEL', 'email'),
        'template_key' => env('ENDEX_OUTREACH_TEMPLATE_KEY', 'default'),
    ],
];
