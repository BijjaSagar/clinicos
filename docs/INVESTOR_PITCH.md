# ClinicOS — Investor Pitch

### The Operating System for India's 1.3 Million Specialty Clinics

---

## The Opportunity

India has **1.3 million registered clinics** — dermatology, dental, physiotherapy, ophthalmology, ENT, gynaecology, orthopaedics — and 93% of them run on paper registers, WhatsApp groups, and Excel sheets.

The Indian healthcare IT market is **$3.4 billion today and growing at 22% CAGR**. Yet no dominant EMR player has cracked the specialty clinic segment at scale. The existing solutions are either:
- Built for hospitals (too complex, too expensive)
- Built for general practice (no specialty depth)
- Built for Western markets (no GST, no ABDM, no Indian drug database)

**This is a $600M+ revenue opportunity that is essentially uncontested.**

---

## The Problem

A dermatologist in Pune spends:
- 45 min/day writing paper prescriptions
- 30 min/day chasing appointment confirmations on WhatsApp
- 20 min/day tracking payments manually
- 15 min/day looking up drug names

That's **2 hours of clinical time wasted every day** — time that could be seeing 4–6 more patients.

At ₹500 average consultation fee × 5 extra consultations × 250 working days = **₹6.25 lakh additional revenue per doctor per year** that ClinicOS enables.

---

## The Solution

**ClinicOS** is a multi-tenant SaaS EMR platform built exclusively for Indian specialty clinics.

Built on 3 core principles:
1. **Specialty-first** — purpose-built templates for each specialty, not generic forms
2. **India-first** — ABDM/ABHA compliant, GST invoicing, Indian drug database, Razorpay payments, WhatsApp automation
3. **Automation-first** — WhatsApp reminders, AI dictation, automatic payment reconciliation

---

## Product Depth (What We've Built)

### 7 Complete Specialty EMR Templates
| Specialty | Unique Features |
|-----------|----------------|
| Dermatology | Body diagram, lesion mapping, PASI/IGA/DLQI scales, before/after photos |
| Dental | 32-tooth FDI chart, periodontal charting, lab work orders |
| Ophthalmology | VA/IOP/refraction/slit-lamp/fundus, spectacle & contact lens PDFs |
| Physiotherapy | ROM/MMT/VAS + FIM/Barthel/WOMAC outcome measures, HEP generator |
| Orthopaedics | Joint exam, fracture classification (AO), implant records |
| ENT | Audiogram, tympanogram, DHI vertigo scale |
| Gynaecology | Obstetric history, antenatal tracking, menstrual cycle |

### Core Platform
- Multi-tenant architecture (1 platform serves unlimited clinics)
- Role-based access (Owner, Doctor, Receptionist, Nurse, Staff)
- WhatsApp automation (8 trigger events, Meta Cloud API)
- Digital prescription with drug interaction checking
- GST-compliant billing and Razorpay payment collection
- Photo vault with before/after comparison and encrypted storage
- ABDM M1 (ABHA creation/linking), M2 (HIP/FHIR), M3 (HIU) integration
- AI voice dictation (Whisper) + field mapping (Claude API)
- Custom EMR builder (no-code template creation)
- Multi-location support (clinic chains and franchise groups)
- Insurance/TPA claim management
- Lab integration (Dr. Lal, SRL, Thyrocare, Metropolis, Pathkind)
- Public online booking (`clinicname.clinic0s.com`)
- Super admin panel with subscription management

---

## Business Model

### SaaS Subscription (Primary Revenue)
| Tier | Target | Monthly ARR/clinic |
|------|--------|-------------------|
| Solo | Single doctors | ₹999 (~$12) |
| Small | 2–5 doctor clinics | ₹1,999 (~$24) |
| Group | Chains, 5–15 doctors | ₹3,999 (~$48) |
| Enterprise | Hospitals, large groups | ₹7,999 (~$96) |

**Blended ARPU target: ₹1,800/month**

### Additional Revenue Streams
- **Transaction fees** — 0.5–1% on Razorpay payments processed through ClinicOS
- **WhatsApp credits** — usage-based for high-volume messaging
- **Lab referral commissions** — ₹50–200 per lab order placed through platform
- **ABDM compliance module** — enterprise add-on
- **AI dictation credits** — per-minute Whisper transcription

---

## Market Sizing

| Segment | Clinics | Addressable |
|---------|---------|-------------|
| Dermatology | 180,000 | High — specialty-specific tools |
| Dental | 320,000 | High — FDI chart is a must-have |
| Physiotherapy | 140,000 | High — outcome measure requirements |
| Ophthalmology | 95,000 | High — refraction/spectacle workflow |
| ENT | 85,000 | Medium |
| Gynaecology | 120,000 | Medium |
| Orthopaedics | 100,000 | Medium |
| **Total TAM** | **~1.3M** | |

**Capturing 1% of TAM** (13,000 clinics) at ₹1,800 ARPU = **₹28 crore ARR**  
**Capturing 5% of TAM** (65,000 clinics) at ₹1,800 ARPU = **₹140 crore ARR**

---

## Go-To-Market Strategy

### Phase 1 — Dermatology Beachhead (Months 1–12)
- India has 12,000 practicing dermatologists, highly concentrated in metros
- ClinicOS has the deepest dermatology EMR of any Indian product
- Target through: Indian Association of Dermatologists (IADVL), dermat conferences, Instagram/YouTube ads targeting clinic owners
- Goal: 500 paying clinics

### Phase 2 — Dental & Ophthalmology Expansion (Months 6–18)
- Dental and ophthalmology are the next highest-density specialties
- Partnership with dental college alumni networks and IOS (Indian Ophthalmology Society)
- Goal: 2,000 paying clinics

### Phase 3 — Clinic Chain Segment (Months 12–24)
- Group/Enterprise plan targeting chains like Dr. Batra's, Vision Express, Fortis outpatient
- Significantly higher ARPU (₹10,000–50,000/month per chain)
- Goal: 50 enterprise accounts + 5,000 SMB clinics

### Distribution Channels
- **Direct / self-serve** — online signup, 14-day trial, credit card
- **Medical reps / channel partners** — commission-based sales in Tier 2/3 cities
- **Medical associations** — bulk licensing deals with IADVL, IDA, etc.
- **Hostinger/cloud resellers** — bundled with hosting for new clinics

---

## Competitive Landscape

| Competitor | Weakness vs. ClinicOS |
|------------|----------------------|
| **Practo** | Appointment booking only — no deep EMR, no specialty templates |
| **Dr. Vaidya / eVitalRx** | Pharmacy-focused, not clinic EMR |
| **Marg ERP** | Desktop billing software, not cloud EMR |
| **Healthplix** | General practice focus, limited specialty depth |
| **DrChrono / Epic** | US-built, no ABDM, no GST, no Indian drug database |
| **HealthDesk** | Limited automation, no WhatsApp, no specialty templates |

**ClinicOS moat:** ABDM compliance + WhatsApp automation + specialty depth + Indian drug database + Razorpay integration — all in one platform that took 18+ months to build.

---

## Technology

**Stack:** Laravel 11 / PHP 8.2 (backend API), React 18 + TypeScript (web), Flutter (mobile), MySQL 8.0

**Architecture:**
- Multi-tenant with clinic_id row-level isolation
- FHIR R4 compliant health record sharing
- OpenAPI 3.1 documented REST API
- Horizontal scaling ready (AWS-native deployment)
- Audit logging for every clinical action

**AI Layer:**
- OpenAI Whisper — voice-to-EMR transcription (Hindi + English)
- Claude API — intelligent field mapping and prescription suggestions

---

## Traction & Milestones

| Milestone | Status |
|-----------|--------|
| Full product architecture designed | Complete |
| All 7 specialty EMR templates built | Complete |
| WhatsApp automation (8 triggers) live | Complete |
| ABDM M1 (ABHA creation) complete | Complete |
| GST billing + Razorpay integration | Complete |
| Public booking + payment at booking | Complete |
| Insurance/TPA module scaffolded | Complete |
| Lab integration (5 providers) scaffolded | Complete |
| AI dictation wired (Whisper + Claude) | Complete |
| Multi-location / clinic chains | Complete |
| Beta testing with clinics | In progress |
| ABDM M2 production certification | In progress |

---

## Financial Projections

| Year | Clinics | ARPU/mo | ARR |
|------|---------|---------|-----|
| Year 1 | 500 | ₹1,200 | ₹72 L |
| Year 2 | 3,000 | ₹1,600 | ₹5.76 Cr |
| Year 3 | 12,000 | ₹2,000 | ₹28.8 Cr |
| Year 4 | 35,000 | ₹2,200 | ₹92.4 Cr |
| Year 5 | 80,000 | ₹2,500 | ₹240 Cr |

**Gross margin target: 75–80%** (SaaS infrastructure costs + WhatsApp API costs)  
**CAC target: ₹8,000–15,000** (12–18 month payback at ₹1,200 ARPU)

---

## The Ask

**Raising: ₹3 Crore (Seed Round)**

| Use of Funds | Amount | Purpose |
|--------------|--------|---------|
| Engineering (4 engineers × 12 months) | ₹1.2 Cr | ABDM M2 cert, AI features, mobile app |
| Sales & Marketing | ₹0.8 Cr | Dermatology conference circuit, digital ads |
| ABDM Certification & Compliance | ₹0.3 Cr | NHA sandbox → production |
| Drug Database License (CIMS) | ₹0.2 Cr | ₹1.8L/year full Indian drug DB |
| Infrastructure & Ops | ₹0.3 Cr | AWS, WhatsApp Business API setup |
| Working Capital | ₹0.2 Cr | |

**Target: 3,000 paying clinics, ₹5.76 Cr ARR by end of Year 2**

---

## Why Now?

1. **ABDM mandate** — Government of India's Digital Health Mission requires all clinics to support ABHA by 2025. Compliance creates a forcing function for clinic software adoption.

2. **WhatsApp Business API maturity** — Meta opened the Cloud API in India in 2022. The unit economics for automated patient communication now work at scale.

3. **Post-COVID digitization** — 68% of Indian clinic owners surveyed in 2024 say they plan to adopt clinic management software in the next 12 months (FICCI Healthcare Report 2024).

4. **AI tailwind** — Voice-to-EMR eliminates the last major friction in EMR adoption for older doctors. Hindi-English dictation makes it viable for Tier 2/3 markets.

---

## Team

**Sagar Bijja** — Founder & CEO, ClinicOS  
Healthcare technology and product leadership; building ClinicOS for Indian specialty clinics.

**Advisors** — Medical professionals across dermatology, dental, ophthalmology, and related specialties (to be named as formal advisory board is expanded).

---

## Contact

**Sagar Bijja**  
Founder & CEO, ClinicOS  

Email: hello@clinicos.in  
Phone / WhatsApp: **+91 89838 39143**  
Website: https://clinic0s.com/

*Pitch deck and financial model available on request.*
