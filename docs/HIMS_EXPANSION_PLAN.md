# ClinicOS Expansion Plan: Full HIMS

> **Status:** Specification + foundation columns in `clinics` table (feature flags).  
> **Implementation:** Phased delivery per module; India-first billing/compliance, then international packs.  
> **Related:** `FEATURE_STATUS.md` roadmap, `config/hims_expansion.php` for valid keys.

---

## 1. Hospital core

| Module | Key features |
|--------|----------------|
| **Bed management** | Ward / floor / room / bed master data; real-time bed status (occupied, available, cleaning, maintenance); allocation; inter-ward transfers |
| **OPD management** | Token system; doctor schedules; consultation queues; triage; OPD register |
| **IPD management** | Admission, discharge, transfer (ADT); nursing notes; diet orders; vitals monitoring; daily progress notes |
| **Emergency** | Emergency registration; triage levels (1–5); resuscitation room workflow; ambulance tracking |

---

## 2. Pharmacy

| Feature | Detail |
|---------|--------|
| Drug inventory | Stock levels, expiry tracking, reorder alerts |
| Inpatient dispensing | Against doctor orders, ward-wise |
| Outpatient dispensing | Against OPD prescriptions |
| Purchase orders | Supplier management, GRN (goods receipt note) |
| Returns & adjustments | Expired stock, damages |

---

## 3. Lab management (full LIS)

| Feature | Detail |
|---------|--------|
| Sample collection | Barcode labelling, collection tracking |
| Test processing | Department-wise (Biochemistry, Haematology, Microbiology, Pathology, Radiology workflows) |
| Result entry | Normal ranges, critical value alerts |
| Report generation | Branded PDF reports |
| Equipment interface | HL7 / instrument LIS integration |

*(Distinct from current **external lab partner** integrations — this is in-house lab operations.)*

---

## 4. Billing & finance

| Feature | Detail |
|---------|--------|
| Unified billing | OPD + IPD + pharmacy + lab on one bill where configured |
| Insurance / TPA | Pre-auth, cashless, reimbursement (extends existing TPA scaffolding) |
| Credit billing | Corporate / account billing |
| GST | Multi–GST slab support (India) |
| MIS reports | Revenue, department-wise P&L |

---

## 5. Nursing & ward management

| Feature | Detail |
|---------|--------|
| Nursing notes | Shift-wise notes |
| Medication administration | MAR (Medication Administration Record) |
| Vitals chart | Temperature, BP, pulse, SpO2, RR, GCS |
| Care plans | Nursing care plans |
| Handover notes | Shift handover documentation |

---

## 6. HIMS dashboard & analytics

| Feature | Detail |
|---------|--------|
| Census report | Daily bed occupancy, ALOS (average length of stay) |
| Revenue dashboard | Department-wise, doctor-wise |
| Lab TAT | Turnaround time reporting |
| Inventory alerts | Pharmacy stock alerts |
| Appointment analytics | OPD load, no-show rates |

---

## 7. Tenancy & super admin

- **`facility_type`:** `clinic` (default) | `hospital` | `multispecialty_hospital`.
- **`licensed_beds`:** Nullable for clinics; set by **super admin** for hospital SKUs (commercial floor e.g. ~50 beds, no hard product maximum).
- **`hims_features`:** JSON map of feature keys (see `config/hims_expansion.php`) — enables modules per tenant as they are built and licensed.

---

## 8. Implementation order (suggested)

1. Master data: wards, rooms, beds + bed status + ADT core.  
2. IPD admission/discharge + link to existing patient/visit model where possible.  
3. Hospital OPD tokens + queues (extend appointments).  
4. Pharmacy inventory + OP dispensing; then IP dispensing + MAR.  
5. LIS sample accession + result entry + PDF; then HL7 interfaces.  
6. Unified billing + MIS; emergency & ambulance after ADT is stable.

---

*Document version: 1.0 — aligned with repository foundation migration.*
