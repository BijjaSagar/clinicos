# ClinicOS Expansion Plan: Full HIMS

> **Document version:** 2.0 (engineering specification)  
> **Status:** Authoritative written spec; foundation in DB (`clinics.facility_type`, `licensed_beds`, `hims_features`) + `config/hims_expansion.php`.  
> **Principles:** India-first (GST, ABDM, Indian drugs); same codebase; international packs later.  
> **Related:** `FEATURE_STATUS.md`, `config/hims_expansion.php` (feature flag keys).

---

## 0. Scope and boundaries

| In scope (this spec) | Out of scope (later documents) |
|----------------------|--------------------------------|
| Functional modules listed in sections 1–6 | OR scheduling, blood bank, CSSD, detailed HR/payroll |
| Tenant flags and super-admin licensing | Full revenue recognition (accounting ERP replacement) |
| Integration *hooks* (HL7, TPA APIs) | Every vendor-certified interface (per-device contracts) |

**Distinction:** Today’s **external lab partner** flows remain. **Full LIS** here means **in-house** lab operations (accession, processing, results on-site).

---

## 1. Hospital core

### 1.1 Bed management

| Area | Requirements |
|------|----------------|
| Master data | Hospital → wing/floor (optional) → ward → room → bed; bed attributes (gender isolation, ICU flag, isolation type). |
| Bed state | States: `available`, `occupied`, `reserved`, `cleaning`, `maintenance`, `blocked`; timestamps and user for last change. |
| Allocation | Link bed to **active IPD admission**; prevent double-booking; support **bed swap** and **inter-ward transfer** with audit trail. |
| Housekeeping | Workflow from discharge → cleaning → available (configurable). |
| Reporting | Real-time census by ward; historical occupancy. |
| Config key | `bed_management` |

### 1.2 OPD management (hospital-grade)

| Area | Requirements |
|------|----------------|
| Tokens | Sequential or priority tokens per department/session; display for waiting areas. |
| Schedules | Doctor / room / session templates; capacity per slot. |
| Queues | States: registered, waiting, in-consultation, completed, no-show; optional **triage** queue before doctor. |
| OPD register | Searchable daily register; link to **visit** / **appointment** records and billing. |
| Integration | Extends existing **appointments**; adds hospital-specific queue and token entities where needed. |
| Config key | `opd_hospital` |

### 1.3 IPD management (ADT)

| Area | Requirements |
|------|----------------|
| Admission | Admit from OPD/Emergency/direct; mandatory patient, attending doctor, provisional diagnosis; deposit/advance optional. |
| Discharge | Discharge summary, clearance checklist, final bill hook; release bed → housekeeping path. |
| Transfer | Internal transfer (ward/bed/doctor); audit who/when/why. |
| Clinical (IPD) | **Diet orders**, **structured vitals** (see section 5), **daily progress notes** (doctor); link to **patient** and **admission** id. |
| Data model | **Admission** (or encounter) as parent; multiple **bed assignments** over time; do not conflate with single outpatient `visit` only. |
| Config key | `ipd` |

### 1.4 Emergency

| Area | Requirements |
|------|----------------|
| Registration | Fast registration; unknown patient / trauma identifiers; link to MLC flags if used. |
| Triage | Levels **1–5** (ESI-style or hospital-defined); timestamped; nurse/doctor reassessment. |
| Resuscitation | Room/bay assignment; parallel documentation strip (vitals, airway, meds given). |
| Ambulance | Optional module: vehicle/run sheet, ETA, handoff to ER triage. |
| Billing | Often separate ER fee + investigations; hooks to unified bill (section 4). |
| Config key | `emergency` |

---

## 2. Pharmacy

| Feature | Detail | Config keys (suggested) |
|---------|--------|-------------------------|
| Drug inventory | SKUs, batches, expiry dates, reorder levels, stock valuation method (FIFO baseline). | `pharmacy_inventory` |
| Inpatient dispensing | Against **doctor orders** (IPD); ward/indent requests; controlled drug rules placeholder. | `pharmacy_ip_dispensing` |
| Outpatient dispensing | Against **OPD prescriptions**; patient counselling log optional. | `pharmacy_op_dispensing` |
| Purchase orders | Suppliers, PO approval workflow, **GRN** with batch capture. | `pharmacy_purchase_grn` |
| Returns & adjustments | Expired quarantine, damage write-off, credit note to stock. | `pharmacy_returns` |

**Integration:** Stock deduction on dispense; alert on **interaction** if linked to prescription engine (existing drug services).

---

## 3. Lab management (full LIS)

| Feature | Detail | Config keys |
|---------|--------|---------------|
| Sample collection | Order from EMR/IPD; print **barcode**; collection time and collector; rejection reasons. | `lis_collection` |
| Test processing | Departments: **Biochemistry, Haematology, Microbiology, Pathology**; **Radiology** as order + report upload if not DICOM PACS in v1. | `lis_processing` |
| Result entry | Reference ranges by age/sex; flag **critical** values; delta checks optional. | `lis_results` |
| Report generation | Branded PDF; versioning; amend/correct with audit. | `lis_reports_pdf` |
| Equipment interface | **HL7** (ORM/ORU baseline target); instrument-specific adapters per vendor phase. | `lis_hl7` |

---

## 4. Billing & finance

| Feature | Detail | Config keys |
|---------|--------|---------------|
| Unified billing | Single invoice or consolidated statement spanning **OPD + IPD + pharmacy + LIS** charges; line items by service code. | `billing_unified` |
| Insurance / TPA | **Pre-auth**, **cashless**, **reimbursement** flows; extends existing insurance/TPA tables/controllers. | `billing_insurance_extended` |
| Credit billing | Corporate / credit limits; aging; statements. | `billing_credit_corporate` |
| GST (India) | Multiple **GST slabs** on line items; HSN/SAC; e-invoice hook when applicable. | `billing_gst_slabs` |
| MIS | Revenue by department/doctor; IPD package vs open charges; export for CA. | `mis_revenue` |

---

## 5. Nursing & ward management

| Feature | Detail | Config keys |
|---------|--------|-------------|
| Nursing notes | Shift-based narrative + structured checklists; signed with user/time. | `nursing_notes` |
| MAR | Medication Administration Record: due times, given/not given, reason, witness where required. | `mar` |
| Vitals chart | T, BP, pulse, SpO2, RR, **GCS**; frequency per protocol; graph/trend per admission. | `vitals_chart` |
| Care plans | Goal/intervention/outcome templates per ward specialty. | `nursing_care_plans` |
| Handover | Structured shift handover (SBAR-style optional); read receipt. | `nursing_handover` |

**Dependency:** Requires **IPD admission** and ideally **doctor orders** for MAR linkage.

---

## 6. HIMS dashboard & analytics

| Feature | Detail | Config keys |
|---------|--------|-------------|
| Census report | **Bed occupancy** by ward; admissions/discharges of day; **ALOS** = sum(length of stay) / discharges in period (define denominator in report UI). | `analytics_census` |
| Revenue dashboard | Department-wise and doctor-wise revenue; IPD vs OPD split. | (often tied to `mis_revenue`) |
| Lab TAT | **Turnaround time** = result authorised time − sample collected time (per test type benchmarks). | `analytics_lab_tat` |
| Inventory alerts | Near-expiry, below reorder level; pharmacy + optional consumables. | `analytics_pharmacy_alerts` |
| Appointment analytics | OPD load, **no-show** rate, average wait time token → seen. | `analytics_opd` |

---

## 7. Tenancy & super admin

| Item | Specification |
|------|----------------|
| `facility_type` | `clinic` (default) \| `hospital` \| `multispecialty_hospital`. |
| `licensed_beds` | Nullable for clinics; integer cap enforced in application logic for hospital SKUs (commercial minimum e.g. ~50; no hardcoded product maximum). |
| `hims_features` | JSON object; keys must match `config/hims_expansion.php` → `hims_feature_keys`; super admin toggles as modules go live. |
| Enforcement | Middleware or policy: block routes for disabled keys; show “upgrade” or “contact admin” in UI. |

---

## 8. Suggested build order (engineering)

Phases are **sequential** where a row depends on the one above. Items in the **same phase** can be parallelised by different developers after shared master data exists.

### Phase A — Master data & bed engine (foundation)

| Step | Deliverable | Depends on |
|------|-------------|------------|
| A1 | Ward / room / bed tables; CRUD; `clinic_id` scoped | Existing `clinics` |
| A2 | Bed state machine + allocation to **admission id** (stub admission table if needed) | A1 |
| A3 | Admin UI + APIs; enable `bed_management` flag | A2 |

### Phase B — IPD ADT

| Step | Deliverable | Depends on |
|------|-------------|------------|
| B1 | **Admission** entity: patient, doctor, datetime, status; link bed | A2 |
| B2 | Discharge + transfer flows; bed release → cleaning | B1 |
| B3 | Enable `ipd`; vitals + progress notes **minimal** CRUD on admission | B1 |

### Phase C — Hospital OPD & emergency

| Step | Deliverable | Depends on |
|------|-------------|------------|
| C1 | Token + queue model; department session | Appointments / doctors |
| C2 | OPD register views | C1 |
| C3 | ER: registration + triage 1–5 + bay | B1 optional for admit from ER |
| C4 | Enable `opd_hospital`, `emergency` | C1–C3 |

### Phase D — Nursing & MAR

| Step | Deliverable | Depends on |
|------|-------------|------------|
| D1 | Nursing notes + handover | B1 |
| D2 | Vitals chart on admission | B1 |
| D3 | Doctor medication orders (IPD) minimal | B1 |
| D4 | MAR grid | D3 |
| D5 | Care plans | D1 |
| D6 | Enable `nursing_notes`, `vitals_chart`, `mar`, `nursing_care_plans`, `nursing_handover` | |

### Phase E — Pharmacy

| Step | Deliverable | Depends on |
|------|-------------|------------|
| E1 | Item master, batch, stock ledger | — |
| E2 | OP dispensing ↔ prescription | E1, existing Rx |
| E3 | IP dispensing ↔ orders | E1, D3 |
| E4 | PO / GRN / returns | E1 |
| E5 | Reorder & expiry alerts | E1 |
| E6 | Enable pharmacy keys | E2–E5 |

### Phase F — LIS

| Step | Deliverable | Depends on |
|------|-------------|------------|
| F1 | Test catalogue by department | — |
| F2 | Order → sample → accession barcode | F1, EMR/IPD orders |
| F3 | Result entry + critical flags + PDF | F2 |
| F4 | HL7 adapter (pilot instrument) | F3 |
| F5 | Enable LIS keys | F2–F4 |

### Phase G — Billing & analytics

| Step | Deliverable | Depends on |
|------|-------------|------------|
| G1 | Charge master; posting from OPD/IPD/pharmacy/LIS | B, C, E, F |
| G2 | Unified invoice / bill runner | G1 |
| G3 | Insurance extensions | G2, existing TPA |
| G4 | Credit / corporate | G2 |
| G5 | GST multi-slab on lines | G2 |
| G6 | MIS + dashboards (census, revenue, TAT, stock, OPD) | G1, data pipes |
| G7 | Enable billing/MIS keys | |

---

## 9. Traceability: spec → config flags

All toggles live in `clinics.hims_features` and are defined in `config/hims_expansion.php` under `hims_feature_keys`. Section references above align with those keys for traceability during implementation and QA.

---

## 10. Non-functional expectations

- **Audit:** All clinical and bed-state changes logged (user, time, old/new value).  
- **RBAC:** Roles e.g. nurse, billing, lab tech, ward clerk — separate from clinic-only roles.  
- **Performance:** Census and queue boards must tolerate real-time refresh for mid-size hospitals (target: &lt;500 beds per tenant initially).  

---

*End of specification v2.0.*
