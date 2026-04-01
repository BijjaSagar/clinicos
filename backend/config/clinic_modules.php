<?php

/**
 * Clinic-level product modules (super admin toggles).
 *
 * There is no single WHO "list of doctor types" — specialties vary by country
 * (e.g. ABMS in the US, GMC CCT in the UK, NMC/MD in India). For a global
 * product, use modular features + regional packs (e.g. India: ABDM, GST).
 *
 * @see https://icd.who.int/ (disease classification, not specialty taxonomy)
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Product modules (enable / disable per clinic)
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'core_scheduling' => [
            'label' => 'Scheduling & queue',
            'description' => 'Appointments, day view, tokens — baseline for all clinics.',
            'region' => 'global',
        ],
        'clinical_emr' => [
            'label' => 'EMR / clinical notes',
            'description' => 'Visit notes, specialty templates, structured charts.',
            'region' => 'global',
        ],
        'prescriptions' => [
            'label' => 'Prescriptions & drug tools',
            'description' => 'Rx pad, drug DB, interactions (jurisdiction-specific formularies later).',
            'region' => 'global',
        ],
        'clinical_media' => [
            'label' => 'Photo vault & imaging',
            'description' => 'Before/after, body maps — dermatology, plastics, aesthetics, wounds.',
            'region' => 'global',
        ],
        'lab_orders' => [
            'label' => 'Lab orders & integrations',
            'description' => 'External lab workflows, result hooks.',
            'region' => 'global',
        ],
        'care_coordination' => [
            'label' => 'Referrals & handoffs',
            'description' => 'Referral letters, specialist routing.',
            'region' => 'global',
        ],
        'remote_monitoring' => [
            'label' => 'Wearables / device data',
            'description' => 'Vitals and device feeds where regulations allow.',
            'region' => 'global',
        ],
        'billing_core' => [
            'label' => 'Invoices & payments',
            'description' => 'Billing, receipts, payment links.',
            'region' => 'global',
        ],
        'billing_gst_india' => [
            'label' => 'India GST reports',
            'description' => 'GST-specific reports and SAC alignment for India.',
            'region' => 'IN',
        ],
        'messaging_whatsapp' => [
            'label' => 'WhatsApp automation',
            'description' => 'Meta Cloud API reminders and campaigns (where Meta is available).',
            'region' => 'global',
        ],
        'abdm_india' => [
            'label' => 'India ABDM (ABHA / HFR / HIP / HIU)',
            'description' => 'National digital health stack for India only.',
            'region' => 'IN',
        ],
        'insurance_tpa' => [
            'label' => 'Insurance & TPA',
            'description' => 'Claims, pre-auth, cashless workflows (market-specific rules).',
            'region' => 'global',
        ],
        'quality_compliance' => [
            'label' => 'Quality & compliance checklists',
            'description' => 'NABH-style or custom audit lists.',
            'region' => 'global',
        ],
        'analytics' => [
            'label' => 'Analytics dashboard',
            'description' => 'Operational and revenue insights.',
            'region' => 'global',
        ],
        'multi_location' => [
            'label' => 'Multi-location',
            'description' => 'Branches, location-scoped analytics.',
            'region' => 'global',
        ],
        'ai_documentation' => [
            'label' => 'AI documentation assistant',
            'description' => 'Dictation / note assist (subject to local AI & privacy rules).',
            'region' => 'global',
        ],
        'custom_emr_builder' => [
            'label' => 'Custom EMR builder',
            'description' => 'No-code form templates for the clinic.',
            'region' => 'global',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Map sidebar nav item "key" → product module id (null = always on if role allows)
    |--------------------------------------------------------------------------
    */
    'nav_to_module' => [
        'dashboard' => null,
        'schedule' => null,
        'patients' => null,
        'emr' => 'clinical_emr',
        'whatsapp' => 'messaging_whatsapp',
        'billing' => 'billing_core',
        'payments' => 'billing_core',
        'gst-reports' => 'billing_gst_india',
        'photo-vault' => 'clinical_media',
        'prescriptions' => 'prescriptions',
        'referrals' => 'care_coordination',
        'wearables' => 'remote_monitoring',
        'vendor' => 'lab_orders',
        'users' => null,
        'abdm' => 'abdm_india',
        'abdm-hiu' => 'abdm_india',
        'compliance' => 'quality_compliance',
        'analytics' => 'analytics',
        'settings' => null,
        'insurance' => 'insurance_tpa',
        'locations' => 'multi_location',
        'emr-builder' => 'custom_emr_builder',
        'ai-assistant' => 'ai_documentation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Suggested modules when super admin picks a primary specialty (UI helper)
    |--------------------------------------------------------------------------
    */
    'specialty_suggestions' => [
        'general' => ['core_scheduling', 'clinical_emr', 'prescriptions', 'billing_core', 'messaging_whatsapp', 'analytics'],
        'dermatology' => ['core_scheduling', 'clinical_emr', 'prescriptions', 'clinical_media', 'billing_core', 'messaging_whatsapp', 'lab_orders', 'analytics'],
        'dental' => ['core_scheduling', 'clinical_emr', 'prescriptions', 'billing_core', 'lab_orders', 'care_coordination', 'analytics'],
        'ophthalmology' => ['core_scheduling', 'clinical_emr', 'prescriptions', 'clinical_media', 'billing_core', 'lab_orders', 'analytics'],
        'pediatrics' => ['core_scheduling', 'clinical_emr', 'prescriptions', 'billing_core', 'lab_orders', 'care_coordination', 'analytics'],
        'orthopedics' => ['core_scheduling', 'clinical_emr', 'prescriptions', 'billing_core', 'lab_orders', 'care_coordination', 'analytics'],
        'cardiology' => ['core_scheduling', 'clinical_emr', 'prescriptions', 'billing_core', 'lab_orders', 'remote_monitoring', 'analytics'],
        'gynecology' => ['core_scheduling', 'clinical_emr', 'prescriptions', 'billing_core', 'lab_orders', 'care_coordination', 'analytics'],
        'physiotherapy' => ['core_scheduling', 'clinical_emr', 'prescriptions', 'billing_core', 'care_coordination', 'analytics'],
        'ent' => ['core_scheduling', 'clinical_emr', 'prescriptions', 'billing_core', 'lab_orders', 'analytics'],
        'psychiatry' => ['core_scheduling', 'clinical_emr', 'prescriptions', 'billing_core', 'care_coordination', 'analytics'],
        'ayurveda' => ['core_scheduling', 'clinical_emr', 'prescriptions', 'billing_core', 'messaging_whatsapp', 'analytics'],
        'homeopathy' => ['core_scheduling', 'clinical_emr', 'prescriptions', 'billing_core', 'messaging_whatsapp', 'analytics'],
        'multi_specialty' => null,
    ],
];
