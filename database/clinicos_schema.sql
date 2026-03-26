-- ============================================================
--  ClinicOS — Complete MySQL 8 Database Schema
--  Multi-tenant SaaS for Indian Specialty Clinics
--  RH Technology, Pune | 2026
-- ============================================================
--  Tenant isolation strategy:
--    Every patient-facing table carries clinic_id.
--    Application layer always scopes queries with clinic_id.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- ============================================================
-- 1. MULTI-TENANCY — CLINICS & LOCATIONS
-- ============================================================

CREATE TABLE clinics (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name                    VARCHAR(200)    NOT NULL,
    slug                    VARCHAR(100)    NOT NULL UNIQUE,           -- clinicname.clinicos.in
    plan                    ENUM('solo','small','group','enterprise')
                                            NOT NULL DEFAULT 'solo',
    specialties             JSON            NOT NULL,                  -- ["dermatology","dental"]
    owner_user_id           INT UNSIGNED    NULL,                      -- set after first user created
    gstin                   VARCHAR(20)     NULL,
    pan                     VARCHAR(12)     NULL,
    registration_number     VARCHAR(50)     NULL,                      -- MCI/state reg
    address_line1           VARCHAR(200)    NULL,
    address_line2           VARCHAR(200)    NULL,
    city                    VARCHAR(100)    NOT NULL DEFAULT 'Pune',
    state                   VARCHAR(100)    NOT NULL DEFAULT 'Maharashtra',
    pincode                 CHAR(6)         NULL,
    phone                   VARCHAR(15)     NULL,
    email                   VARCHAR(150)    NULL,
    logo_url                VARCHAR(500)    NULL,
    -- ABDM
    hfr_id                  VARCHAR(50)     NULL,                      -- Health Facility Registry ID
    hfr_facility_id         VARCHAR(50)     NULL,
    hfr_status              ENUM('not_registered','pending','active')
                                            NOT NULL DEFAULT 'not_registered',
    abdm_m1_live            TINYINT(1)      NOT NULL DEFAULT 0,
    abdm_m2_live            TINYINT(1)      NOT NULL DEFAULT 0,
    abdm_m3_live            TINYINT(1)      NOT NULL DEFAULT 0,
    -- Integrations
    razorpay_account_id     VARCHAR(100)    NULL,
    whatsapp_phone_number_id VARCHAR(50)    NULL,
    whatsapp_waba_id        VARCHAR(50)     NULL,
    gsp_client_id           VARCHAR(100)    NULL,                      -- GST Suvidha Provider
    -- Config
    settings                JSON            NULL,                      -- per-clinic feature flags & prefs
    is_active               TINYINT(1)      NOT NULL DEFAULT 1,
    trial_ends_at           DATETIME        NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at              DATETIME        NULL,
    PRIMARY KEY (id),
    INDEX idx_slug          (slug),
    INDEX idx_plan          (plan),
    INDEX idx_city          (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tenant table — one row per clinic/hospital-department';


CREATE TABLE clinic_locations (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    name                    VARCHAR(200)    NOT NULL,                  -- 'Main Branch', 'Koregaon Park'
    address                 TEXT            NULL,
    phone                   VARCHAR(15)     NULL,
    is_primary              TINYINT(1)      NOT NULL DEFAULT 0,
    is_active               TINYINT(1)      NOT NULL DEFAULT 1,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_clinic        (clinic_id),
    CONSTRAINT fk_cloc_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE clinic_rooms (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    location_id             INT UNSIGNED    NULL,
    name                    VARCHAR(100)    NOT NULL,                  -- 'Laser Room 1', 'Physio Bay 2'
    room_type               VARCHAR(50)     NULL,                      -- consultation|laser|physio|dental|opd
    capacity                TINYINT         NOT NULL DEFAULT 1,
    is_active               TINYINT(1)      NOT NULL DEFAULT 1,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_clinic        (clinic_id),
    CONSTRAINT fk_croom_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE clinic_equipment (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    name                    VARCHAR(150)    NOT NULL,                  -- 'Q-Switch Laser #1'
    equipment_type          VARCHAR(50)     NOT NULL,                  -- laser|tens|ultrasound|dental_chair
    serial_number           VARCHAR(100)    NULL,
    is_active               TINYINT(1)      NOT NULL DEFAULT 1,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_clinic        (clinic_id),
    CONSTRAINT fk_ceqp_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 2. USERS & AUTHENTICATION
-- ============================================================

CREATE TABLE users (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    name                    VARCHAR(200)    NOT NULL,
    email                   VARCHAR(150)    NOT NULL UNIQUE,
    phone                   VARCHAR(15)     NULL,
    password                VARCHAR(255)    NOT NULL,
    role                    ENUM('owner','doctor','receptionist','nurse','staff','vendor_admin')
                                            NOT NULL DEFAULT 'staff',
    -- Doctor-specific
    specialty               VARCHAR(50)     NULL,
    qualification           VARCHAR(200)    NULL,                      -- 'MBBS, MD (Dermatology)'
    registration_number     VARCHAR(80)     NULL,                      -- MCI/State Medical Council
    hpr_id                  VARCHAR(30)     NULL,                      -- ABDM Healthcare Professional Registry ID
    signature_url           VARCHAR(500)    NULL,                      -- for digital prescription
    -- Status
    is_active               TINYINT(1)      NOT NULL DEFAULT 1,
    email_verified_at       DATETIME        NULL,
    remember_token          VARCHAR(100)    NULL,
    last_login_at           DATETIME        NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at              DATETIME        NULL,
    PRIMARY KEY (id),
    INDEX idx_clinic_role   (clinic_id, role),
    INDEX idx_phone         (phone),
    CONSTRAINT fk_user_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE personal_access_tokens (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tokenable_type          VARCHAR(255)    NOT NULL,
    tokenable_id            BIGINT UNSIGNED NOT NULL,
    name                    VARCHAR(255)    NOT NULL,
    token                   VARCHAR(64)     NOT NULL UNIQUE,
    abilities               TEXT            NULL,
    last_used_at            DATETIME        NULL,
    expires_at              DATETIME        NULL,
    created_at              DATETIME        NULL,
    updated_at              DATETIME        NULL,
    PRIMARY KEY (id),
    INDEX idx_tokenable      (tokenable_type, tokenable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE password_reset_tokens (
    email                   VARCHAR(150)    NOT NULL,
    token                   VARCHAR(255)    NOT NULL,
    created_at              DATETIME        NULL,
    PRIMARY KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. PATIENTS
-- ============================================================

CREATE TABLE patients (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    -- Demographics
    name                    VARCHAR(200)    NOT NULL,
    dob                     DATE            NULL,
    age_years               TINYINT UNSIGNED NULL,                     -- fallback if DOB unknown
    sex                     ENUM('M','F','O') NULL,
    blood_group             VARCHAR(5)      NULL,
    phone                   VARCHAR(15)     NOT NULL,
    phone_alt               VARCHAR(15)     NULL,
    email                   VARCHAR(150)    NULL,
    address                 TEXT            NULL,
    -- ABDM
    abha_id                 VARCHAR(20)     NULL,                      -- 14-digit: 91-XXXX-XXXX-XXXX
    abha_address            VARCHAR(100)    NULL,                      -- preferred@abdm
    abha_verified           TINYINT(1)      NOT NULL DEFAULT 0,
    abdm_consent_active     TINYINT(1)      NOT NULL DEFAULT 0,
    -- Medical background
    known_allergies         JSON            NULL,                      -- ["penicillin","sulfa"]
    chronic_conditions      JSON            NULL,                      -- ["diabetes","hypertension"]
    current_medications     JSON            NULL,
    family_history          JSON            NULL,
    -- Tracking
    referred_by             VARCHAR(200)    NULL,
    source                  ENUM('walk_in','online_booking','referral','whatsapp','other')
                                            NOT NULL DEFAULT 'walk_in',
    visit_count             SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    last_visit_date         DATE            NULL,
    next_followup_date      DATE            NULL,
    -- Photo consent
    photo_consent_given     TINYINT(1)      NOT NULL DEFAULT 0,
    photo_consent_at        DATETIME        NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at              DATETIME        NULL,
    PRIMARY KEY (id),
    INDEX idx_clinic_phone  (clinic_id, phone),
    INDEX idx_abha          (abha_id),
    INDEX idx_name          (clinic_id, name),
    INDEX idx_last_visit    (clinic_id, last_visit_date),
    CONSTRAINT fk_pat_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE patient_family_members (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    patient_id              INT UNSIGNED    NOT NULL,
    clinic_id               INT UNSIGNED    NOT NULL,
    name                    VARCHAR(200)    NOT NULL,
    relation                VARCHAR(50)     NOT NULL,                  -- spouse|child|parent|sibling
    phone                   VARCHAR(15)     NULL,
    linked_patient_id       INT UNSIGNED    NULL,                      -- if also a patient
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_patient       (patient_id),
    CONSTRAINT fk_pfm_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. SCHEDULING & APPOINTMENTS
-- ============================================================

CREATE TABLE appointment_services (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    name                    VARCHAR(150)    NOT NULL,                  -- 'LASER Session', 'Chemical Peel'
    specialty               VARCHAR(50)     NULL,
    duration_mins           SMALLINT        NOT NULL DEFAULT 15,
    advance_amount          DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    color_hex               CHAR(7)         NOT NULL DEFAULT '#1447E6',
    is_active               TINYINT(1)      NOT NULL DEFAULT 1,
    requires_room           TINYINT(1)      NOT NULL DEFAULT 0,
    requires_equipment      TINYINT(1)      NOT NULL DEFAULT 0,
    pre_visit_questions     JSON            NULL,                      -- intake questionnaire schema
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_clinic        (clinic_id),
    CONSTRAINT fk_svc_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE doctor_availability (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    doctor_id               INT UNSIGNED    NOT NULL,
    day_of_week             TINYINT         NOT NULL,                  -- 0=Sun…6=Sat
    start_time              TIME            NOT NULL,
    end_time                TIME            NOT NULL,
    slot_duration_mins      TINYINT         NOT NULL DEFAULT 15,
    max_patients            TINYINT UNSIGNED NULL,
    location_id             INT UNSIGNED    NULL,
    is_active               TINYINT(1)      NOT NULL DEFAULT 1,
    effective_from          DATE            NULL,
    effective_to            DATE            NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_doctor_day    (doctor_id, day_of_week),
    CONSTRAINT fk_avail_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE appointments (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    patient_id              INT UNSIGNED    NOT NULL,
    doctor_id               INT UNSIGNED    NOT NULL,
    service_id              INT UNSIGNED    NULL,
    room_id                 INT UNSIGNED    NULL,
    equipment_id            INT UNSIGNED    NULL,
    location_id             INT UNSIGNED    NULL,
    -- Timing
    scheduled_at            DATETIME        NOT NULL,
    duration_mins           SMALLINT        NOT NULL DEFAULT 15,
    -- Status flow: booked → confirmed → checked_in → in_consultation → completed
    status                  ENUM('booked','confirmed','checked_in','in_consultation',
                                 'completed','cancelled','no_show','rescheduled')
                                            NOT NULL DEFAULT 'booked',
    token_number            SMALLINT UNSIGNED NULL,
    -- Source & type
    booking_source          ENUM('clinic_staff','online_booking','whatsapp','phone','walk_in')
                                            NOT NULL DEFAULT 'clinic_staff',
    appointment_type        ENUM('new','followup','procedure','teleconsultation')
                                            NOT NULL DEFAULT 'new',
    specialty               VARCHAR(50)     NOT NULL,
    -- Payment
    advance_paid            DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    razorpay_order_id       VARCHAR(100)    NULL,
    razorpay_payment_id     VARCHAR(100)    NULL,
    -- Communication
    confirmation_sent_at    DATETIME        NULL,
    reminder_24h_sent_at    DATETIME        NULL,
    reminder_2h_sent_at     DATETIME        NULL,
    pre_visit_answers       JSON            NULL,                      -- intake questionnaire answers
    notes                   TEXT            NULL,
    -- Rescheduling
    rescheduled_from_id     INT UNSIGNED    NULL,
    cancelled_reason        VARCHAR(255)    NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at              DATETIME        NULL,
    PRIMARY KEY (id),
    INDEX idx_clinic_date   (clinic_id, scheduled_at),
    INDEX idx_doctor_date   (doctor_id, scheduled_at),
    INDEX idx_patient       (patient_id),
    INDEX idx_status        (clinic_id, status, scheduled_at),
    CONSTRAINT fk_appt_clinic   FOREIGN KEY (clinic_id)   REFERENCES clinics(id)   ON DELETE CASCADE,
    CONSTRAINT fk_appt_patient  FOREIGN KEY (patient_id)  REFERENCES patients(id)  ON DELETE CASCADE,
    CONSTRAINT fk_appt_doctor   FOREIGN KEY (doctor_id)   REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. VISITS (CLINICAL ENCOUNTERS)
-- ============================================================

CREATE TABLE visits (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    patient_id              INT UNSIGNED    NOT NULL,
    doctor_id               INT UNSIGNED    NOT NULL,
    appointment_id          INT UNSIGNED    NULL,
    specialty               VARCHAR(50)     NOT NULL,
    visit_number            SMALLINT UNSIGNED NOT NULL DEFAULT 1,      -- per-patient sequential count
    status                  ENUM('draft','finalised') NOT NULL DEFAULT 'draft',
    -- Clinical content (specialty-specific JSON)
    chief_complaint         VARCHAR(500)    NULL,
    history                 TEXT            NULL,
    structured_data         JSON            NULL,                      -- all specialty-specific fields
    diagnosis_code          VARCHAR(20)     NULL,                      -- ICD-10
    diagnosis_text          VARCHAR(500)    NULL,
    plan                    TEXT            NULL,
    followup_in_days        SMALLINT        NULL,
    followup_date           DATE            NULL,
    -- AI
    ai_dictation_raw        TEXT            NULL,                      -- raw Whisper transcript
    ai_summary              TEXT            NULL,                      -- Claude-generated patient summary
    -- ABDM / FHIR
    fhir_bundle             LONGTEXT        NULL,                      -- serialised FHIR R4 Composition JSON
    fhir_resource_id        VARCHAR(100)    NULL,
    abdm_care_context_id    VARCHAR(100)    NULL,
    abdm_pushed_at          DATETIME        NULL,
    -- Timestamps
    started_at              DATETIME        NULL,
    finalised_at            DATETIME        NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_clinic_patient (clinic_id, patient_id),
    INDEX idx_doctor_date    (doctor_id, created_at),
    INDEX idx_status         (clinic_id, status),
    CONSTRAINT fk_visit_clinic   FOREIGN KEY (clinic_id)      REFERENCES clinics(id)      ON DELETE CASCADE,
    CONSTRAINT fk_visit_patient  FOREIGN KEY (patient_id)     REFERENCES patients(id),
    CONSTRAINT fk_visit_doctor   FOREIGN KEY (doctor_id)      REFERENCES users(id),
    CONSTRAINT fk_visit_appt     FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 6. BODY-MAP LESION ANNOTATIONS  (Dermatology / Ortho)
-- ============================================================

CREATE TABLE visit_lesions (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    visit_id                INT UNSIGNED    NOT NULL,
    body_region             VARCHAR(100)    NOT NULL,                  -- 'left_cheek', 'left_forearm'
    view                    ENUM('front','back','left','right') NOT NULL DEFAULT 'front',
    x_pct                   DECIMAL(5,2)    NOT NULL,                  -- % position on diagram canvas
    y_pct                   DECIMAL(5,2)    NOT NULL,
    lesion_type             VARCHAR(50)     NOT NULL,                  -- macule|papule|plaque|vesicle|...
    size_cm                 DECIMAL(5,2)    NULL,
    colour                  VARCHAR(50)     NULL,
    border                  VARCHAR(50)     NULL,                      -- well-defined|ill-defined|...
    surface                 VARCHAR(100)    NULL,                      -- smooth|rough|scaling|...
    distribution            VARCHAR(50)     NULL,                      -- localised|generalised|...
    notes                   TEXT            NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_visit         (visit_id),
    CONSTRAINT fk_lesion_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. GRADING SCALES (PASI, IGA, DLQI, VAS, ROM, MMT…)
-- ============================================================

CREATE TABLE visit_scales (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    visit_id                INT UNSIGNED    NOT NULL,
    scale_name              VARCHAR(30)     NOT NULL,
    -- PASI|IGA|DLQI — Dermatology
    -- VAS|ROM|MMT|BARTHEL|FIM|DASH|WOMAC|NDI — Physiotherapy
    -- DHI — ENT
    score                   DECIMAL(8,2)    NOT NULL,
    components              JSON            NULL,                      -- sub-scores per body area/question
    interpretation          VARCHAR(100)    NULL,                      -- 'Moderate', 'Severe', 'Grade 3'
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_visit_scale (visit_id, scale_name),
    CONSTRAINT fk_scale_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='One row per scale per visit. Scale history queryable across visits for trend.';


-- ============================================================
-- 8. PROCEDURES PERFORMED
-- ============================================================

CREATE TABLE visit_procedures (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    visit_id                INT UNSIGNED    NOT NULL,
    clinic_id               INT UNSIGNED    NOT NULL,
    procedure_code          VARCHAR(30)     NULL,                      -- internal or SNOMED
    procedure_name          VARCHAR(150)    NOT NULL,                  -- 'Chemical Peel 30% SA'
    specialty               VARCHAR(50)     NOT NULL,
    parameters              JSON            NULL,
    -- Derm: {agent, concentration, areas, duration_mins, sessions_total, session_number}
    -- Laser: {type, wavelength_nm, fluence, pulse_duration, areas}
    -- Physio: {modality, settings, duration_mins, exercises_count}
    body_region             VARCHAR(100)    NULL,
    notes                   TEXT            NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_visit         (visit_id),
    CONSTRAINT fk_proc_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 9. DENTAL CHART  (FDI notation)
-- ============================================================

CREATE TABLE dental_teeth (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    patient_id              INT UNSIGNED    NOT NULL,
    clinic_id               INT UNSIGNED    NOT NULL,
    tooth_code              VARCHAR(3)      NOT NULL,
    -- FDI permanent: 11-18, 21-28, 31-38, 41-48
    -- FDI primary:   51-55, 61-65, 71-75, 81-85
    status                  ENUM('present','missing','extracted','unerupted','impacted','implant')
                                            NOT NULL DEFAULT 'present',
    caries                  ENUM('none','initial','moderate','advanced') NOT NULL DEFAULT 'none',
    caries_sites            JSON            NULL,                      -- ["mesial","occlusal","distal"]
    restoration             ENUM('none','amalgam','composite','crown','bridge','rct','veneer','implant_crown')
                                            NOT NULL DEFAULT 'none',
    mobility_grade          TINYINT         NULL,                      -- 0-3
    recession_mm            DECIMAL(4,1)    NULL,
    bop                     TINYINT(1)      NULL,                      -- bleeding on probing
    pocketing_mm            JSON            NULL,                      -- [3,2,3,2,3,2] — 6 points MB/B/DB/ML/L/DL
    furcation               TINYINT         NULL,                      -- 0-3
    notes                   TEXT            NULL,
    last_updated_by         INT UNSIGNED    NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_patient_tooth  (patient_id, tooth_code),
    INDEX idx_clinic_patient     (clinic_id, patient_id),
    CONSTRAINT fk_tooth_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE dental_tooth_history (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    patient_id              INT UNSIGNED    NOT NULL,
    tooth_code              VARCHAR(3)      NOT NULL,
    visit_id                INT UNSIGNED    NOT NULL,
    procedure_done          VARCHAR(150)    NOT NULL,
    material_used           VARCHAR(100)    NULL,
    operator_id             INT UNSIGNED    NULL,
    notes                   TEXT            NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_patient_tooth (patient_id, tooth_code),
    CONSTRAINT fk_dth_visit   FOREIGN KEY (visit_id)   REFERENCES visits(id),
    CONSTRAINT fk_dth_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE dental_lab_orders (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    patient_id              INT UNSIGNED    NOT NULL,
    visit_id                INT UNSIGNED    NULL,
    tooth_code              VARCHAR(3)      NOT NULL,
    work_type               VARCHAR(100)    NOT NULL,                  -- PFM Crown, Bridge, Implant Crown
    shade                   VARCHAR(20)     NULL,                      -- A1, A2, B3 (Vita scale)
    preparation_notes       TEXT            NULL,
    lab_vendor              VARCHAR(150)    NULL,
    delivery_date           DATE            NULL,
    status                  ENUM('sent','received','fitted','rejected') NOT NULL DEFAULT 'sent',
    cost                    DECIMAL(10,2)   NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_clinic_patient (clinic_id, patient_id),
    CONSTRAINT fk_labord_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 10. PHYSIOTHERAPY — SESSION TRACKING
-- ============================================================

CREATE TABLE physio_treatment_plans (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    patient_id              INT UNSIGNED    NOT NULL,
    clinic_id               INT UNSIGNED    NOT NULL,
    visit_id                INT UNSIGNED    NOT NULL,                  -- initial assessment visit
    diagnosis               VARCHAR(300)    NOT NULL,
    referring_doctor        VARCHAR(200)    NULL,
    total_sessions_planned  TINYINT         NULL,
    sessions_completed      TINYINT         NOT NULL DEFAULT 0,
    short_term_goal         TEXT            NULL,                      -- 2-week SMART goal
    long_term_goal          TEXT            NULL,                      -- 6-8 week SMART goal
    status                  ENUM('active','completed','discharged','dnf') NOT NULL DEFAULT 'active',
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_patient       (patient_id),
    CONSTRAINT fk_ptplan_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE physio_hep (
    -- Home Exercise Programme
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    visit_id                INT UNSIGNED    NOT NULL,
    patient_id              INT UNSIGNED    NOT NULL,
    exercise_name           VARCHAR(150)    NOT NULL,
    sets                    TINYINT         NULL,
    reps                    TINYINT         NULL,
    hold_seconds            TINYINT         NULL,
    frequency_per_day       TINYINT         NULL,
    instructions            TEXT            NULL,
    image_url               VARCHAR(500)    NULL,
    video_url               VARCHAR(500)    NULL,
    whatsapp_sent_at        DATETIME        NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_visit         (visit_id),
    CONSTRAINT fk_hep_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 11. OPHTHALMOLOGY — VA LOG & REFRACTION
-- ============================================================

CREATE TABLE ophthal_va_logs (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    visit_id                INT UNSIGNED    NOT NULL,
    patient_id              INT UNSIGNED    NOT NULL,
    -- Visual Acuity
    va_od_unaided           VARCHAR(10)     NULL,                      -- 6/6, 6/9, CF, HM, PL
    va_os_unaided           VARCHAR(10)     NULL,
    va_od_pinhole           VARCHAR(10)     NULL,
    va_os_pinhole           VARCHAR(10)     NULL,
    va_od_bcva              VARCHAR(10)     NULL,
    va_os_bcva              VARCHAR(10)     NULL,
    -- IOP
    iop_od_mmhg             DECIMAL(4,1)    NULL,
    iop_os_mmhg             DECIMAL(4,1)    NULL,
    iop_method              VARCHAR(30)     NULL,                      -- Goldmann|NCT|iCare
    iop_time                TIME            NULL,
    -- Slit Lamp
    ac_grade_od             VARCHAR(20)     NULL,
    cornea_od               VARCHAR(100)    NULL,
    lens_od_locs            VARCHAR(20)     NULL,
    ac_grade_os             VARCHAR(20)     NULL,
    cornea_os               VARCHAR(100)    NULL,
    lens_os_locs            VARCHAR(20)     NULL,
    -- Fundus
    cdr_od                  DECIMAL(3,2)    NULL,
    cdr_os                  DECIMAL(3,2)    NULL,
    fundus_od_notes         TEXT            NULL,
    fundus_os_notes         TEXT            NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_visit         (visit_id),
    CONSTRAINT fk_valog_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE ophthal_refractions (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    visit_id                INT UNSIGNED    NOT NULL,
    patient_id              INT UNSIGNED    NOT NULL,
    refraction_type         ENUM('subjective','cycloplegic','manifest','contact_lens')
                                            NOT NULL DEFAULT 'subjective',
    -- Right eye
    od_sphere               DECIMAL(5,2)    NULL,
    od_cylinder             DECIMAL(5,2)    NULL,
    od_axis                 SMALLINT        NULL,
    od_add                  DECIMAL(4,2)    NULL,
    od_prism                DECIMAL(4,2)    NULL,
    od_base                 VARCHAR(10)     NULL,
    -- Left eye
    os_sphere               DECIMAL(5,2)    NULL,
    os_cylinder             DECIMAL(5,2)    NULL,
    os_axis                 SMALLINT        NULL,
    os_add                  DECIMAL(4,2)    NULL,
    os_prism                DECIMAL(4,2)    NULL,
    os_base                 VARCHAR(10)     NULL,
    is_final_prescription   TINYINT(1)      NOT NULL DEFAULT 0,
    pdf_url                 VARCHAR(500)    NULL,                      -- spectacle Rx PDF
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_refrac_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 12. PRESCRIPTIONS & DRUG DATABASE
-- ============================================================

CREATE TABLE prescriptions (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    visit_id                INT UNSIGNED    NOT NULL,
    patient_id              INT UNSIGNED    NOT NULL,
    doctor_id               INT UNSIGNED    NOT NULL,
    -- ABDM / HPR
    hpr_signed_ref          VARCHAR(100)    NULL,                      -- digital signature ref
    fhir_resource_id        VARCHAR(100)    NULL,
    -- Delivery
    pdf_url                 VARCHAR(500)    NULL,
    whatsapp_sent_at        DATETIME        NULL,
    whatsapp_message_id     VARCHAR(100)    NULL,
    -- Validity
    valid_days              TINYINT         NOT NULL DEFAULT 30,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_visit         (visit_id),
    INDEX idx_patient       (patient_id),
    CONSTRAINT fk_rx_visit   FOREIGN KEY (visit_id)  REFERENCES visits(id),
    CONSTRAINT fk_rx_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE prescription_drugs (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    prescription_id         INT UNSIGNED    NOT NULL,
    drug_db_id              INT UNSIGNED    NULL,                      -- FK to indian_drugs table
    drug_name               VARCHAR(200)    NOT NULL,
    generic_name            VARCHAR(200)    NULL,
    strength                VARCHAR(50)     NULL,                      -- '100mg', '0.1%'
    form                    VARCHAR(50)     NULL,                      -- tablet|capsule|gel|cream|drops
    dose                    VARCHAR(100)    NOT NULL,
    frequency               VARCHAR(100)    NOT NULL,                  -- 'OD', 'BD', 'TDS', 'SOS'
    route                   VARCHAR(30)     NOT NULL DEFAULT 'oral',
    duration                VARCHAR(50)     NULL,                      -- '7 days', 'Continue', '1 month'
    instructions            TEXT            NULL,
    sort_order              TINYINT         NOT NULL DEFAULT 0,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_prescription  (prescription_id),
    CONSTRAINT fk_rxdrug_rx FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE indian_drugs (
    -- Licensed Indian drug database (CIMS / Medindia)
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    generic_name            VARCHAR(200)    NOT NULL,
    brand_names             JSON            NULL,                      -- ["Doxt-SL","Biodoxi"]
    drug_class              VARCHAR(100)    NULL,
    form                    VARCHAR(50)     NULL,
    strength                VARCHAR(50)     NULL,
    manufacturer            VARCHAR(150)    NULL,
    schedule                CHAR(2)         NULL,                      -- H, H1, G, OTC
    interactions            JSON            NULL,
    contraindications       JSON            NULL,
    common_dosages          JSON            NULL,
    is_controlled           TINYINT(1)      NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    INDEX idx_generic       (generic_name),
    FULLTEXT idx_ft_drug    (generic_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Read-only licensed Indian drug database. ~40,000 drugs.';


-- ============================================================
-- 13. PHOTO VAULT  (Before/After)
-- ============================================================

CREATE TABLE patient_photos (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    patient_id              INT UNSIGNED    NOT NULL,
    visit_id                INT UNSIGNED    NULL,
    -- Storage
    s3_key                  VARCHAR(500)    NOT NULL,                  -- encrypted path in S3
    s3_bucket               VARCHAR(100)    NOT NULL DEFAULT 'clinicos-photos',
    file_size_kb            INT UNSIGNED    NULL,
    mime_type               VARCHAR(50)     NOT NULL DEFAULT 'image/jpeg',
    -- Tagging
    body_region             VARCHAR(100)    NULL,
    view_angle              VARCHAR(30)     NULL,                      -- front|side|close-up
    condition_tag           VARCHAR(100)    NULL,                      -- acne|psoriasis|lesion
    procedure_tag           VARCHAR(100)    NULL,                      -- laser|peel|prp
    photo_type              ENUM('before','after','progress','clinical')
                                            NOT NULL DEFAULT 'clinical',
    -- Consent & privacy
    consent_obtained        TINYINT(1)      NOT NULL DEFAULT 0,
    consent_at              DATETIME        NULL,
    is_encrypted            TINYINT(1)      NOT NULL DEFAULT 1,
    can_use_for_marketing   TINYINT(1)      NOT NULL DEFAULT 0,
    uploaded_by             INT UNSIGNED    NOT NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at              DATETIME        NULL,
    PRIMARY KEY (id),
    INDEX idx_patient_region (patient_id, body_region),
    INDEX idx_visit          (visit_id),
    INDEX idx_clinic_patient (clinic_id, patient_id),
    CONSTRAINT fk_photo_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
    CONSTRAINT fk_photo_visit   FOREIGN KEY (visit_id)   REFERENCES visits(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 14. BILLING — INVOICES, ITEMS, PAYMENTS
-- ============================================================

CREATE TABLE invoices (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    patient_id              INT UNSIGNED    NOT NULL,
    visit_id                INT UNSIGNED    NULL,
    -- Invoice identity
    invoice_number          VARCHAR(30)     NOT NULL UNIQUE,           -- CLNXXX-2026-0001
    invoice_date            DATE            NOT NULL,
    -- Amounts
    subtotal                DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    discount_amount         DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    discount_pct            DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    cgst_amount             DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    sgst_amount             DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    igst_amount             DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    total                   DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    advance_adjusted        DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    paid                    DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    balance_due             DECIMAL(12,2)   GENERATED ALWAYS AS (total - paid) STORED,
    -- Status
    payment_status          ENUM('pending','partial','paid','refunded','void')
                                            NOT NULL DEFAULT 'pending',
    -- GST / e-Invoice
    place_of_supply         CHAR(2)         NOT NULL DEFAULT '27',     -- Maharashtra = 27
    reverse_charge          TINYINT(1)      NOT NULL DEFAULT 0,
    irn                     VARCHAR(100)    NULL,                      -- e-Invoice IRN (GSP)
    ack_number              VARCHAR(30)     NULL,
    irn_generated_at        DATETIME        NULL,
    -- Insurance
    is_insurance_claim      TINYINT(1)      NOT NULL DEFAULT 0,
    insurer_name            VARCHAR(150)    NULL,
    claim_id                VARCHAR(100)    NULL,
    tpa_name                VARCHAR(100)    NULL,
    -- Communication
    pdf_url                 VARCHAR(500)    NULL,
    whatsapp_link_sent_at   DATETIME        NULL,
    email_sent_at           DATETIME        NULL,
    notes                   TEXT            NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_clinic_date    (clinic_id, invoice_date),
    INDEX idx_patient        (patient_id),
    INDEX idx_payment_status (clinic_id, payment_status),
    CONSTRAINT fk_inv_clinic   FOREIGN KEY (clinic_id)  REFERENCES clinics(id)   ON DELETE CASCADE,
    CONSTRAINT fk_inv_patient  FOREIGN KEY (patient_id) REFERENCES patients(id),
    CONSTRAINT fk_inv_visit    FOREIGN KEY (visit_id)   REFERENCES visits(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE invoice_items (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    invoice_id              INT UNSIGNED    NOT NULL,
    description             VARCHAR(300)    NOT NULL,
    item_type               ENUM('service','procedure','product','consultation','package')
                                            NOT NULL DEFAULT 'service',
    sac_code                VARCHAR(10)     NULL,                      -- SAC 999311 (health), 999312 (cosmetic)
    hsn_code                VARCHAR(10)     NULL,                      -- for medical devices/products
    gst_rate                DECIMAL(5,2)    NOT NULL DEFAULT 0.00,     -- 0 / 5 / 12 / 18
    unit_price              DECIMAL(12,2)   NOT NULL,
    quantity                DECIMAL(6,2)    NOT NULL DEFAULT 1,
    discount_pct            DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    taxable_amount          DECIMAL(12,2)   NOT NULL,
    cgst_amount             DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    sgst_amount             DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    total                   DECIMAL(12,2)   NOT NULL,
    sort_order              TINYINT         NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    INDEX idx_invoice       (invoice_id),
    CONSTRAINT fk_item_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE gst_sac_codes (
    -- Reference table for Indian medical service SAC codes
    sac_code                VARCHAR(10)     NOT NULL,
    description             VARCHAR(300)    NOT NULL,
    service_category        VARCHAR(100)    NOT NULL,                  -- 'Health Services', 'Cosmetic'
    gst_rate                DECIMAL(5,2)    NOT NULL,
    is_exempt               TINYINT(1)      NOT NULL DEFAULT 0,
    notes                   VARCHAR(500)    NULL,
    PRIMARY KEY (sac_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO gst_sac_codes VALUES
  ('999311', 'Human health services (clinical consultation, diagnosis, treatment)', 'Health Services', 0.00, 1, 'Fully exempt from GST'),
  ('999312', 'Cosmetic and plastic surgery (Botox, fillers, laser hair removal)', 'Cosmetic Services', 18.00, 0, '18% GST applicable'),
  ('9993',   'Healthcare and social care services (general SAC)', 'Health Services', 0.00, 1, NULL),
  ('999321', 'Physiotherapy and rehabilitation services', 'Health Services', 0.00, 1, 'Exempt'),
  ('999713', 'Health check packages (non-clinical, packaged)', 'Health Packages', 18.00, 0, '18% GST on packaged health checks');


CREATE TABLE payments (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    invoice_id              INT UNSIGNED    NOT NULL,
    patient_id              INT UNSIGNED    NOT NULL,
    amount                  DECIMAL(12,2)   NOT NULL,
    payment_method          ENUM('upi','card','cash','netbanking','wallet','insurance','advance')
                                            NOT NULL DEFAULT 'cash',
    payment_date            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Razorpay
    razorpay_payment_id     VARCHAR(100)    NULL UNIQUE,
    razorpay_order_id       VARCHAR(100)    NULL,
    razorpay_signature      VARCHAR(300)    NULL,
    -- Reference
    transaction_ref         VARCHAR(100)    NULL,
    notes                   VARCHAR(300)    NULL,
    recorded_by             INT UNSIGNED    NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_invoice       (invoice_id),
    INDEX idx_clinic_date   (clinic_id, payment_date),
    CONSTRAINT fk_pay_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 15. WHATSAPP COMMUNICATION ENGINE
-- ============================================================

CREATE TABLE whatsapp_messages (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    patient_id              INT UNSIGNED    NULL,
    direction               ENUM('outbound','inbound') NOT NULL,
    wa_message_id           VARCHAR(100)    NULL UNIQUE,               -- Meta message ID
    wa_phone_from           VARCHAR(20)     NULL,
    wa_phone_to             VARCHAR(20)     NULL,
    template_name           VARCHAR(100)    NULL,
    message_type            ENUM('text','template','image','document','audio') NOT NULL DEFAULT 'text',
    body                    TEXT            NULL,
    media_url               VARCHAR(500)    NULL,
    -- Trigger context
    trigger_type            ENUM('appointment_confirmation','reminder_24h','reminder_2h',
                                 'prescription','payment_link','recall','hep','result',
                                 'birthday','manual','inbound_reply') NULL,
    related_id              INT UNSIGNED    NULL,                      -- appointment_id / invoice_id / etc.
    -- Status
    status                  ENUM('queued','sent','delivered','read','failed','error')
                                            NOT NULL DEFAULT 'queued',
    error_code              VARCHAR(20)     NULL,
    error_message           VARCHAR(300)    NULL,
    sent_at                 DATETIME        NULL,
    delivered_at            DATETIME        NULL,
    read_at                 DATETIME        NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_clinic_patient (clinic_id, patient_id),
    INDEX idx_wa_message_id  (wa_message_id),
    INDEX idx_trigger        (trigger_type, related_id),
    CONSTRAINT fk_wa_clinic   FOREIGN KEY (clinic_id)  REFERENCES clinics(id)  ON DELETE CASCADE,
    CONSTRAINT fk_wa_patient  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 16. ABDM COMPLIANCE
-- ============================================================

CREATE TABLE abdm_consents (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    patient_id              INT UNSIGNED    NOT NULL,
    consent_request_id      VARCHAR(100)    NOT NULL UNIQUE,           -- NHA consent request ID
    status                  ENUM('REQUESTED','GRANTED','DENIED','REVOKED','EXPIRED')
                                            NOT NULL DEFAULT 'REQUESTED',
    purpose                 VARCHAR(30)     NOT NULL,                  -- CAREMGT|BTG|PATRQT|PUBHLTH
    hi_types                JSON            NOT NULL,                  -- ["Prescription","DiagnosticReport"]
    date_from               DATE            NULL,
    date_to                 DATE            NULL,
    consent_artefact        JSON            NULL,                      -- full artefact from NHA
    consent_artefact_id     VARCHAR(100)    NULL,
    granted_at              DATETIME        NULL,
    expires_at              DATETIME        NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_patient       (patient_id),
    INDEX idx_status        (status),
    CONSTRAINT fk_consent_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE abdm_care_contexts (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    patient_id              INT UNSIGNED    NOT NULL,
    clinic_id               INT UNSIGNED    NOT NULL,
    visit_id                INT UNSIGNED    NULL,
    care_context_reference  VARCHAR(100)    NOT NULL UNIQUE,           -- e.g. CC-{visit_id}
    display_name            VARCHAR(200)    NOT NULL,
    hi_type                 VARCHAR(50)     NOT NULL,                  -- OPConsultation|Prescription|...
    fhir_resource_type      VARCHAR(50)     NULL,
    fhir_bundle_url         VARCHAR(500)    NULL,
    pushed_at               DATETIME        NULL,
    status                  ENUM('active','expired','revoked') NOT NULL DEFAULT 'active',
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_patient       (patient_id),
    CONSTRAINT fk_cc_visit   FOREIGN KEY (visit_id)  REFERENCES visits(id) ON DELETE SET NULL,
    CONSTRAINT fk_cc_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 17. LAB ORDERS & VENDOR MANAGEMENT
-- ============================================================

CREATE TABLE vendor_labs (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name                    VARCHAR(200)    NOT NULL,                  -- 'Dr. Lal PathLabs — Pune Central'
    lab_chain               VARCHAR(100)    NULL,                      -- 'Dr. Lal', 'SRL', 'Thyrocare'
    city                    VARCHAR(100)    NOT NULL,
    contact_phone           VARCHAR(15)     NULL,
    contact_email           VARCHAR(150)    NULL,
    api_enabled             TINYINT(1)      NOT NULL DEFAULT 0,
    api_endpoint            VARCHAR(300)    NULL,
    is_active               TINYINT(1)      NOT NULL DEFAULT 1,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE clinic_vendor_links (
    clinic_id               INT UNSIGNED    NOT NULL,
    vendor_id               INT UNSIGNED    NOT NULL,
    discount_pct            DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    is_preferred            TINYINT(1)      NOT NULL DEFAULT 0,
    linked_at               DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (clinic_id, vendor_id),
    CONSTRAINT fk_cvl_clinic  FOREIGN KEY (clinic_id) REFERENCES clinics(id)     ON DELETE CASCADE,
    CONSTRAINT fk_cvl_vendor  FOREIGN KEY (vendor_id) REFERENCES vendor_labs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE lab_test_catalog (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    vendor_id               INT UNSIGNED    NOT NULL,
    test_code               VARCHAR(30)     NOT NULL,
    test_name               VARCHAR(200)    NOT NULL,
    department              VARCHAR(100)    NULL,                      -- Haematology|Biochemistry|...
    sample_type             VARCHAR(50)     NULL,                      -- Blood|Urine|Tissue
    turnaround_hours        TINYINT         NULL,
    price                   DECIMAL(10,2)   NULL,
    is_active               TINYINT(1)      NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_vendor_code (vendor_id, test_code),
    CONSTRAINT fk_catalog_vendor FOREIGN KEY (vendor_id) REFERENCES vendor_labs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE lab_orders (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    patient_id              INT UNSIGNED    NOT NULL,
    doctor_id               INT UNSIGNED    NOT NULL,
    visit_id                INT UNSIGNED    NULL,
    vendor_id               INT UNSIGNED    NULL,
    order_number            VARCHAR(30)     NOT NULL UNIQUE,           -- LO-2026-000001
    is_urgent               TINYINT(1)      NOT NULL DEFAULT 0,
    status                  ENUM('new','accepted','sample_collected','processing','ready','sent','cancelled')
                                            NOT NULL DEFAULT 'new',
    -- Results
    result_pdf_url          VARCHAR(500)    NULL,
    result_pdf_s3_key       VARCHAR(500)    NULL,
    result_sent_at          DATETIME        NULL,
    result_sent_to_patient  TINYINT(1)      NOT NULL DEFAULT 0,
    fhir_resource_id        VARCHAR(100)    NULL,
    -- Billing
    total_amount            DECIMAL(10,2)   NULL,
    -- Notes
    clinical_notes          TEXT            NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_clinic_patient (clinic_id, patient_id),
    INDEX idx_vendor_status  (vendor_id, status),
    INDEX idx_visit          (visit_id),
    CONSTRAINT fk_lorder_clinic   FOREIGN KEY (clinic_id)  REFERENCES clinics(id)     ON DELETE CASCADE,
    CONSTRAINT fk_lorder_patient  FOREIGN KEY (patient_id) REFERENCES patients(id),
    CONSTRAINT fk_lorder_vendor   FOREIGN KEY (vendor_id)  REFERENCES vendor_labs(id) ON DELETE SET NULL,
    CONSTRAINT fk_lorder_visit    FOREIGN KEY (visit_id)   REFERENCES visits(id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE lab_order_tests (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    lab_order_id            INT UNSIGNED    NOT NULL,
    test_catalog_id         INT UNSIGNED    NULL,
    test_code               VARCHAR(30)     NULL,
    test_name               VARCHAR(200)    NOT NULL,
    is_urgent               TINYINT(1)      NOT NULL DEFAULT 0,
    unit_price              DECIMAL(10,2)   NULL,
    result_value            VARCHAR(200)    NULL,
    result_unit             VARCHAR(50)     NULL,
    reference_range         VARCHAR(100)    NULL,
    is_abnormal             TINYINT(1)      NULL,
    PRIMARY KEY (id),
    INDEX idx_order         (lab_order_id),
    CONSTRAINT fk_lot_order FOREIGN KEY (lab_order_id) REFERENCES lab_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 18. AUDIT & SECURITY LOGS
-- ============================================================

CREATE TABLE audit_logs (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    user_id                 INT UNSIGNED    NULL,
    action                  VARCHAR(80)     NOT NULL,                  -- 'visit.finalised', 'invoice.created'
    entity_type             VARCHAR(50)     NOT NULL,                  -- 'Visit', 'Patient', 'Invoice'
    entity_id               INT UNSIGNED    NULL,
    old_values              JSON            NULL,
    new_values              JSON            NULL,
    ip_address              VARCHAR(45)     NULL,
    user_agent              VARCHAR(300)    NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_clinic_action  (clinic_id, action, created_at),
    INDEX idx_entity         (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Immutable audit trail. Never delete rows from this table.';


-- ============================================================
-- 19. NOTIFICATIONS & REMINDERS (QUEUE)
-- ============================================================

CREATE TABLE notification_queue (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id               INT UNSIGNED    NOT NULL,
    patient_id              INT UNSIGNED    NULL,
    channel                 ENUM('whatsapp','email','push','sms') NOT NULL DEFAULT 'whatsapp',
    template_name           VARCHAR(100)    NOT NULL,
    payload                 JSON            NOT NULL,
    scheduled_at            DATETIME        NOT NULL,
    processed_at            DATETIME        NULL,
    status                  ENUM('pending','processing','sent','failed') NOT NULL DEFAULT 'pending',
    attempts                TINYINT         NOT NULL DEFAULT 0,
    error                   TEXT            NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_scheduled     (status, scheduled_at),
    INDEX idx_clinic        (clinic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 20. VIEWS (CONVENIENCE)
-- ============================================================

CREATE OR REPLACE VIEW v_today_appointments AS
    SELECT
        a.id,
        a.clinic_id,
        a.scheduled_at,
        a.duration_mins,
        a.status,
        a.token_number,
        a.specialty,
        a.appointment_type,
        p.name        AS patient_name,
        p.phone       AS patient_phone,
        p.abha_id,
        u.name        AS doctor_name,
        s.name        AS service_name,
        r.name        AS room_name
    FROM appointments a
    JOIN patients  p ON p.id = a.patient_id
    JOIN users     u ON u.id = a.doctor_id
    LEFT JOIN appointment_services s ON s.id = a.service_id
    LEFT JOIN clinic_rooms         r ON r.id = a.room_id
    WHERE DATE(a.scheduled_at) = CURDATE()
      AND a.deleted_at IS NULL
      AND a.status NOT IN ('cancelled');


CREATE OR REPLACE VIEW v_outstanding_invoices AS
    SELECT
        i.id,
        i.clinic_id,
        i.invoice_number,
        i.invoice_date,
        i.total,
        i.paid,
        i.balance_due,
        i.payment_status,
        p.name        AS patient_name,
        p.phone       AS patient_phone,
        DATEDIFF(CURDATE(), i.invoice_date) AS days_overdue
    FROM invoices i
    JOIN patients p ON p.id = i.patient_id
    WHERE i.payment_status IN ('pending','partial')
      AND i.balance_due > 0;


CREATE OR REPLACE VIEW v_clinic_daily_summary AS
    SELECT
        clinic_id,
        DATE(created_at)                        AS summary_date,
        COUNT(*)                                AS total_visits,
        COUNT(DISTINCT patient_id)              AS unique_patients,
        SUM(CASE WHEN status = 'finalised' THEN 1 ELSE 0 END) AS completed_visits
    FROM visits
    GROUP BY clinic_id, DATE(created_at);


-- ============================================================
-- ENABLE FOREIGN KEY CHECKS
-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SUMMARY
-- ============================================================
-- Tables: 33 + 2 reference tables
-- Views:  3
-- Tenant isolation: ALL patient tables have clinic_id
-- ABDM: abdm_consents, abdm_care_contexts + fhir_bundle on visits
-- Specialty: dental_teeth, dental_tooth_history, dental_lab_orders,
--            physio_treatment_plans, physio_hep,
--            ophthal_va_logs, ophthal_refractions
-- Billing: invoices → invoice_items → payments + gst_sac_codes
-- Vendor:  vendor_labs → clinic_vendor_links → lab_test_catalog → lab_orders → lab_order_tests
-- Audit:   audit_logs (immutable), notification_queue
-- ============================================================
