<?php

/**
 * HIMS expansion — valid facility types and per-tenant feature keys.
 *
 * clinics.hims_features JSON stores booleans keyed by these strings.
 * Super admin UI can toggle keys as each module ships.
 *
 * @see docs/HIMS_EXPANSION_PLAN.md
 */
return [

    'facility_types' => [
        'clinic' => [
            'label' => 'Clinic (outpatient)',
            'description' => 'Default tenant; no licensed bed requirement.',
        ],
        'hospital' => [
            'label' => 'Hospital',
            'description' => 'IPD-capable; licensed_beds set by super admin.',
        ],
        'multispecialty_hospital' => [
            'label' => 'Multispecialty hospital',
            'description' => 'Large hospital profile; same flags, higher typical bed count.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Planned HIMS feature flags (clinics.hims_features)
    |--------------------------------------------------------------------------
    */
    'hims_feature_keys' => [
        'bed_management' => 'Ward/floor/room/bed master and status',
        'opd_hospital' => 'Hospital OPD tokens, queues, triage, register',
        'ipd' => 'ADT, nursing notes, diet, vitals, progress notes',
        'emergency' => 'ER registration, triage 1–5, resus room, ambulance',
        'pharmacy_inventory' => 'Stock, expiry, reorder alerts',
        'pharmacy_ip_dispensing' => 'Inpatient dispensing by ward/order',
        'pharmacy_op_dispensing' => 'Outpatient dispensing vs Rx',
        'pharmacy_purchase_grn' => 'PO, suppliers, GRN',
        'pharmacy_returns' => 'Returns, adjustments, expired stock',
        'lis_collection' => 'Sample collection, barcodes',
        'lis_processing' => 'Department-wise test processing',
        'lis_results' => 'Result entry, normals, critical alerts',
        'lis_reports_pdf' => 'Branded lab PDF reports',
        'lis_hl7' => 'Analyser / HL7 LIS interfaces',
        'billing_unified' => 'Single bill: OPD+IPD+pharmacy+lab',
        'billing_insurance_extended' => 'Pre-auth, cashless, reimbursement depth',
        'billing_credit_corporate' => 'Corporate / credit accounts',
        'billing_gst_slabs' => 'Multi–GST slab support',
        'mis_revenue' => 'Department P&L, revenue dashboards',
        'nursing_notes' => 'Shift nursing notes',
        'mar' => 'Medication Administration Record',
        'vitals_chart' => 'TPR, BP, SpO2, RR, GCS charts',
        'nursing_care_plans' => 'Structured care plans',
        'nursing_handover' => 'Shift handover documentation',
        'analytics_census' => 'Bed census, ALOS',
        'analytics_lab_tat' => 'Lab turnaround reporting',
        'analytics_pharmacy_alerts' => 'Stock alert dashboards',
        'analytics_opd' => 'OPD load, no-show analytics',
    ],
];
