<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * ClinicOS — Complete Schema Migration
 * Multi-tenant: every table has clinic_id for tenant isolation.
 * Matches: database/clinicos_schema.sql
 */
return new class extends Migration
{
    public function up(): void
    {
        Log::info('Starting ClinicOS migration');

        // ── 1. Clinics (tenants) ──────────────────────────────────────────────
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('slug', 100)->unique();
            $table->enum('plan', ['solo', 'small', 'group', 'enterprise'])->default('solo');
            $table->json('specialties');
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->string('gstin', 20)->nullable();
            $table->string('pan', 12)->nullable();
            $table->string('registration_number', 50)->nullable();
            $table->string('address_line1', 200)->nullable();
            $table->string('address_line2', 200)->nullable();
            $table->string('city', 100)->default('Pune');
            $table->string('state', 100)->default('Maharashtra');
            $table->char('pincode', 6)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('logo_url', 500)->nullable();
            // ABDM
            $table->string('hfr_id', 50)->nullable();
            $table->string('hfr_facility_id', 50)->nullable();
            $table->enum('hfr_status', ['not_registered', 'pending', 'active'])->default('not_registered');
            $table->boolean('abdm_m1_live')->default(false);
            $table->boolean('abdm_m2_live')->default(false);
            $table->boolean('abdm_m3_live')->default(false);
            // Integrations
            $table->string('razorpay_account_id', 100)->nullable();
            $table->string('whatsapp_phone_number_id', 50)->nullable();
            $table->string('whatsapp_waba_id', 50)->nullable();
            $table->string('gsp_client_id', 100)->nullable();
            // Config
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('trial_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('slug');
            $table->index('plan');
            $table->index('city');
        });
        Log::info('Created clinics table');

        // ── Clinic Locations ──────────────────────────────────────────────────
        Schema::create('clinic_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->text('address')->nullable();
            $table->string('phone', 15)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->index('clinic_id');
        });

        // ── Clinic Rooms ──────────────────────────────────────────────────────
        Schema::create('clinic_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('name', 100);
            $table->string('room_type', 50)->nullable();
            $table->tinyInteger('capacity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->index('clinic_id');
        });

        // ── Clinic Equipment ──────────────────────────────────────────────────
        Schema::create('clinic_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('equipment_type', 50);
            $table->string('serial_number', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->index('clinic_id');
        });

        // ── 2. Users / Staff ──────────────────────────────────────────────────
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('email', 150)->unique();
            $table->string('phone', 15)->nullable();
            $table->string('password');
            $table->enum('role', ['owner', 'doctor', 'receptionist', 'nurse', 'staff', 'vendor_admin'])->default('staff');
            // Doctor-specific
            $table->string('specialty', 50)->nullable();
            $table->string('qualification', 200)->nullable();
            $table->string('registration_number', 80)->nullable();
            $table->string('hpr_id', 30)->nullable();
            $table->string('signature_url', 500)->nullable();
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['clinic_id', 'role']);
            $table->index('phone');
        });
        Log::info('Created users table');

        // Laravel Sanctum tokens
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 150)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // ── 3. Patients ───────────────────────────────────────────────────────
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->date('dob')->nullable();
            $table->unsignedTinyInteger('age_years')->nullable();
            $table->enum('sex', ['M', 'F', 'O'])->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('phone', 15);
            $table->string('phone_alt', 15)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            // ABDM
            $table->string('abha_id', 20)->nullable();
            $table->string('abha_address', 100)->nullable();
            $table->boolean('abha_verified')->default(false);
            $table->boolean('abdm_consent_active')->default(false);
            // Medical background
            $table->json('known_allergies')->nullable();
            $table->json('chronic_conditions')->nullable();
            $table->json('current_medications')->nullable();
            $table->json('family_history')->nullable();
            // Tracking
            $table->string('referred_by', 200)->nullable();
            $table->enum('source', ['walk_in', 'online_booking', 'referral', 'whatsapp', 'other'])->default('walk_in');
            $table->unsignedSmallInteger('visit_count')->default(0);
            $table->date('last_visit_date')->nullable();
            $table->date('next_followup_date')->nullable();
            // Photo consent
            $table->boolean('photo_consent_given')->default(false);
            $table->dateTime('photo_consent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['clinic_id', 'phone']);
            $table->index('abha_id');
            $table->index(['clinic_id', 'name']);
            $table->index(['clinic_id', 'last_visit_date']);
        });
        Log::info('Created patients table');

        // Patient Family Members
        Schema::create('patient_family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('relation', 50);
            $table->string('phone', 15)->nullable();
            $table->unsignedBigInteger('linked_patient_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('patient_id');
        });

        // ── 4. Scheduling & Appointments ──────────────────────────────────────
        Schema::create('appointment_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('specialty', 50)->nullable();
            $table->smallInteger('duration_mins')->default(15);
            $table->decimal('advance_amount', 10, 2)->default(0);
            $table->char('color_hex', 7)->default('#1447E6');
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_room')->default(false);
            $table->boolean('requires_equipment')->default(false);
            $table->json('pre_visit_questions')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('clinic_id');
        });

        Schema::create('doctor_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->references('id')->on('users')->cascadeOnDelete();
            $table->tinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->tinyInteger('slot_duration_mins')->default(15);
            $table->unsignedTinyInteger('max_patients')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['doctor_id', 'day_of_week']);
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->references('id')->on('users');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('equipment_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->dateTime('scheduled_at');
            $table->smallInteger('duration_mins')->default(15);
            $table->enum('status', ['booked', 'confirmed', 'checked_in', 'in_consultation', 'completed', 'cancelled', 'no_show', 'rescheduled'])->default('booked');
            $table->unsignedSmallInteger('token_number')->nullable();
            $table->enum('booking_source', ['clinic_staff', 'online_booking', 'whatsapp', 'phone', 'walk_in'])->default('clinic_staff');
            $table->enum('appointment_type', ['new', 'followup', 'procedure', 'teleconsultation'])->default('new');
            $table->string('specialty', 50);
            $table->decimal('advance_paid', 10, 2)->default(0);
            $table->string('razorpay_order_id', 100)->nullable();
            $table->string('razorpay_payment_id', 100)->nullable();
            $table->dateTime('confirmation_sent_at')->nullable();
            $table->dateTime('reminder_24h_sent_at')->nullable();
            $table->dateTime('reminder_2h_sent_at')->nullable();
            $table->json('pre_visit_answers')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('rescheduled_from_id')->nullable();
            $table->string('cancelled_reason', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['clinic_id', 'scheduled_at']);
            $table->index(['doctor_id', 'scheduled_at']);
            $table->index('patient_id');
            $table->index(['clinic_id', 'status', 'scheduled_at']);
        });
        Log::info('Created appointments table');

        // ── 5. Visits (Clinical Encounters) ───────────────────────────────────
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('doctor_id')->references('id')->on('users');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->string('specialty', 50);
            $table->unsignedSmallInteger('visit_number')->default(1);
            $table->enum('status', ['draft', 'finalised'])->default('draft');
            $table->string('chief_complaint', 500)->nullable();
            $table->text('history')->nullable();
            $table->json('structured_data')->nullable();
            $table->string('diagnosis_code', 20)->nullable();
            $table->string('diagnosis_text', 500)->nullable();
            $table->text('plan')->nullable();
            $table->smallInteger('followup_in_days')->nullable();
            $table->date('followup_date')->nullable();
            $table->text('ai_dictation_raw')->nullable();
            $table->text('ai_summary')->nullable();
            $table->longText('fhir_bundle')->nullable();
            $table->string('fhir_resource_id', 100)->nullable();
            $table->string('abdm_care_context_id', 100)->nullable();
            $table->dateTime('abdm_pushed_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finalised_at')->nullable();
            $table->timestamps();
            $table->index(['clinic_id', 'patient_id']);
            $table->index(['doctor_id', 'created_at']);
            $table->index(['clinic_id', 'status']);
            $table->foreign('appointment_id')->references('id')->on('appointments')->nullOnDelete();
        });
        Log::info('Created visits table');

        // ── 6. Body-Map Lesion Annotations ────────────────────────────────────
        Schema::create('visit_lesions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->string('body_region', 100);
            $table->enum('view', ['front', 'back', 'left', 'right'])->default('front');
            $table->decimal('x_pct', 5, 2);
            $table->decimal('y_pct', 5, 2);
            $table->string('lesion_type', 50);
            $table->decimal('size_cm', 5, 2)->nullable();
            $table->string('colour', 50)->nullable();
            $table->string('border', 50)->nullable();
            $table->string('surface', 100)->nullable();
            $table->string('distribution', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('visit_id');
        });

        // ── 7. Grading Scales ─────────────────────────────────────────────────
        Schema::create('visit_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->string('scale_name', 30);
            $table->decimal('score', 8, 2);
            $table->json('components')->nullable();
            $table->string('interpretation', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['visit_id', 'scale_name']);
        });

        // ── 8. Procedures Performed ───────────────────────────────────────────
        Schema::create('visit_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('procedure_code', 30)->nullable();
            $table->string('procedure_name', 150);
            $table->string('specialty', 50);
            $table->json('parameters')->nullable();
            $table->string('body_region', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('visit_id');
        });

        // ── 9. Dental Chart ───────────────────────────────────────────────────
        Schema::create('dental_teeth', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('tooth_code', 3);
            $table->enum('status', ['present', 'missing', 'extracted', 'unerupted', 'impacted', 'implant'])->default('present');
            $table->enum('caries', ['none', 'initial', 'moderate', 'advanced'])->default('none');
            $table->json('caries_sites')->nullable();
            $table->enum('restoration', ['none', 'amalgam', 'composite', 'crown', 'bridge', 'rct', 'veneer', 'implant_crown'])->default('none');
            $table->tinyInteger('mobility_grade')->nullable();
            $table->decimal('recession_mm', 4, 1)->nullable();
            $table->boolean('bop')->nullable();
            $table->json('pocketing_mm')->nullable();
            $table->tinyInteger('furcation')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('last_updated_by')->nullable();
            $table->timestamps();
            $table->unique(['patient_id', 'tooth_code']);
            $table->index(['clinic_id', 'patient_id']);
        });

        Schema::create('dental_tooth_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->string('tooth_code', 3);
            $table->foreignId('visit_id')->constrained();
            $table->string('procedure_done', 150);
            $table->string('material_used', 100)->nullable();
            $table->unsignedBigInteger('operator_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['patient_id', 'tooth_code']);
        });

        Schema::create('dental_lab_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained();
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->string('tooth_code', 3);
            $table->string('work_type', 100);
            $table->string('shade', 20)->nullable();
            $table->text('preparation_notes')->nullable();
            $table->string('lab_vendor', 150)->nullable();
            $table->date('delivery_date')->nullable();
            $table->enum('status', ['sent', 'received', 'fitted', 'rejected'])->default('sent');
            $table->decimal('cost', 10, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['clinic_id', 'patient_id']);
        });

        // ── 10. Physiotherapy ─────────────────────────────────────────────────
        Schema::create('physio_treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained();
            $table->string('diagnosis', 300);
            $table->string('referring_doctor', 200)->nullable();
            $table->tinyInteger('total_sessions_planned')->nullable();
            $table->tinyInteger('sessions_completed')->default(0);
            $table->text('short_term_goal')->nullable();
            $table->text('long_term_goal')->nullable();
            $table->enum('status', ['active', 'completed', 'discharged', 'dnf'])->default('active');
            $table->timestamps();
            $table->index('patient_id');
        });

        Schema::create('physio_hep', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained();
            $table->string('exercise_name', 150);
            $table->tinyInteger('sets')->nullable();
            $table->tinyInteger('reps')->nullable();
            $table->tinyInteger('hold_seconds')->nullable();
            $table->tinyInteger('frequency_per_day')->nullable();
            $table->text('instructions')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('video_url', 500)->nullable();
            $table->dateTime('whatsapp_sent_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('visit_id');
        });

        // ── 11. Ophthalmology ─────────────────────────────────────────────────
        Schema::create('ophthal_va_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained();
            $table->string('va_od_unaided', 10)->nullable();
            $table->string('va_os_unaided', 10)->nullable();
            $table->string('va_od_pinhole', 10)->nullable();
            $table->string('va_os_pinhole', 10)->nullable();
            $table->string('va_od_bcva', 10)->nullable();
            $table->string('va_os_bcva', 10)->nullable();
            $table->decimal('iop_od_mmhg', 4, 1)->nullable();
            $table->decimal('iop_os_mmhg', 4, 1)->nullable();
            $table->string('iop_method', 30)->nullable();
            $table->time('iop_time')->nullable();
            $table->string('ac_grade_od', 20)->nullable();
            $table->string('cornea_od', 100)->nullable();
            $table->string('lens_od_locs', 20)->nullable();
            $table->string('ac_grade_os', 20)->nullable();
            $table->string('cornea_os', 100)->nullable();
            $table->string('lens_os_locs', 20)->nullable();
            $table->decimal('cdr_od', 3, 2)->nullable();
            $table->decimal('cdr_os', 3, 2)->nullable();
            $table->text('fundus_od_notes')->nullable();
            $table->text('fundus_os_notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('visit_id');
        });

        Schema::create('ophthal_refractions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained();
            $table->enum('refraction_type', ['subjective', 'cycloplegic', 'manifest', 'contact_lens'])->default('subjective');
            $table->decimal('od_sphere', 5, 2)->nullable();
            $table->decimal('od_cylinder', 5, 2)->nullable();
            $table->smallInteger('od_axis')->nullable();
            $table->decimal('od_add', 4, 2)->nullable();
            $table->decimal('od_prism', 4, 2)->nullable();
            $table->string('od_base', 10)->nullable();
            $table->decimal('os_sphere', 5, 2)->nullable();
            $table->decimal('os_cylinder', 5, 2)->nullable();
            $table->smallInteger('os_axis')->nullable();
            $table->decimal('os_add', 4, 2)->nullable();
            $table->decimal('os_prism', 4, 2)->nullable();
            $table->string('os_base', 10)->nullable();
            $table->boolean('is_final_prescription')->default(false);
            $table->string('pdf_url', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // ── 12. Prescriptions & Drug Database ─────────────────────────────────
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('doctor_id')->references('id')->on('users');
            $table->string('hpr_signed_ref', 100)->nullable();
            $table->string('fhir_resource_id', 100)->nullable();
            $table->string('pdf_url', 500)->nullable();
            $table->dateTime('whatsapp_sent_at')->nullable();
            $table->string('whatsapp_message_id', 100)->nullable();
            $table->tinyInteger('valid_days')->default(30);
            $table->timestamp('created_at')->useCurrent();
            $table->index('visit_id');
            $table->index('patient_id');
        });

        Schema::create('prescription_drugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('drug_db_id')->nullable();
            $table->string('drug_name', 200);
            $table->string('generic_name', 200)->nullable();
            $table->string('strength', 50)->nullable();
            $table->string('form', 50)->nullable();
            $table->string('dose', 100);
            $table->string('frequency', 100);
            $table->string('route', 30)->default('oral');
            $table->string('duration', 50)->nullable();
            $table->text('instructions')->nullable();
            $table->tinyInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->index('prescription_id');
        });

        Schema::create('indian_drugs', function (Blueprint $table) {
            $table->id();
            $table->string('generic_name', 200);
            $table->json('brand_names')->nullable();
            $table->string('drug_class', 100)->nullable();
            $table->string('form', 50)->nullable();
            $table->string('strength', 50)->nullable();
            $table->string('manufacturer', 150)->nullable();
            $table->char('schedule', 2)->nullable();
            $table->json('interactions')->nullable();
            $table->json('contraindications')->nullable();
            $table->json('common_dosages')->nullable();
            $table->boolean('is_controlled')->default(false);
            $table->index('generic_name');
            if (config('database.default') !== 'sqlite') {
                $table->fullText('generic_name');
            }
        });

        // ── 13. Photo Vault ───────────────────────────────────────────────────
        Schema::create('patient_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained();
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->string('s3_key', 500);
            $table->string('s3_bucket', 100)->default('clinicos-photos');
            $table->unsignedInteger('file_size_kb')->nullable();
            $table->string('mime_type', 50)->default('image/jpeg');
            $table->string('body_region', 100)->nullable();
            $table->string('view_angle', 30)->nullable();
            $table->string('condition_tag', 100)->nullable();
            $table->string('procedure_tag', 100)->nullable();
            $table->enum('photo_type', ['before', 'after', 'progress', 'clinical'])->default('clinical');
            $table->boolean('consent_obtained')->default(false);
            $table->dateTime('consent_at')->nullable();
            $table->boolean('is_encrypted')->default(true);
            $table->boolean('can_use_for_marketing')->default(false);
            $table->foreignId('uploaded_by')->references('id')->on('users');
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();
            $table->index(['patient_id', 'body_region']);
            $table->index('visit_id');
            $table->index(['clinic_id', 'patient_id']);
        });

        // ── 14. Billing ───────────────────────────────────────────────────────
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained();
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->string('invoice_number', 30)->unique();
            $table->date('invoice_date');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('cgst_amount', 12, 2)->default(0);
            $table->decimal('sgst_amount', 12, 2)->default(0);
            $table->decimal('igst_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('advance_adjusted', 12, 2)->default(0);
            $table->decimal('paid', 12, 2)->default(0);
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'refunded', 'void'])->default('pending');
            $table->char('place_of_supply', 2)->default('27');
            $table->boolean('reverse_charge')->default(false);
            $table->string('irn', 100)->nullable();
            $table->string('ack_number', 30)->nullable();
            $table->dateTime('irn_generated_at')->nullable();
            $table->boolean('is_insurance_claim')->default(false);
            $table->string('insurer_name', 150)->nullable();
            $table->string('claim_id', 100)->nullable();
            $table->string('tpa_name', 100)->nullable();
            $table->string('pdf_url', 500)->nullable();
            $table->dateTime('whatsapp_link_sent_at')->nullable();
            $table->dateTime('email_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['clinic_id', 'invoice_date']);
            $table->index('patient_id');
            $table->index(['clinic_id', 'payment_status']);
        });
        Log::info('Created invoices table');

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description', 300);
            $table->enum('item_type', ['service', 'procedure', 'product', 'consultation', 'package'])->default('service');
            $table->string('sac_code', 10)->nullable();
            $table->string('hsn_code', 10)->nullable();
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('quantity', 6, 2)->default(1);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('taxable_amount', 12, 2);
            $table->decimal('cgst_amount', 12, 2)->default(0);
            $table->decimal('sgst_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->tinyInteger('sort_order')->default(0);
            $table->index('invoice_id');
        });

        Schema::create('gst_sac_codes', function (Blueprint $table) {
            $table->string('sac_code', 10)->primary();
            $table->string('description', 300);
            $table->string('service_category', 100);
            $table->decimal('gst_rate', 5, 2);
            $table->boolean('is_exempt')->default(false);
            $table->string('notes', 500)->nullable();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['upi', 'card', 'cash', 'netbanking', 'wallet', 'insurance', 'advance'])->default('cash');
            $table->dateTime('payment_date')->useCurrent();
            $table->string('razorpay_payment_id', 100)->nullable()->unique();
            $table->string('razorpay_order_id', 100)->nullable();
            $table->string('razorpay_signature', 300)->nullable();
            $table->string('transaction_ref', 100)->nullable();
            $table->string('notes', 300)->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('invoice_id');
            $table->index(['clinic_id', 'payment_date']);
        });

        // ── 15. WhatsApp Communication ────────────────────────────────────────
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->enum('direction', ['outbound', 'inbound']);
            $table->string('wa_message_id', 100)->nullable()->unique();
            $table->string('wa_phone_from', 20)->nullable();
            $table->string('wa_phone_to', 20)->nullable();
            $table->string('template_name', 100)->nullable();
            $table->enum('message_type', ['text', 'template', 'image', 'document', 'audio'])->default('text');
            $table->text('body')->nullable();
            $table->string('media_url', 500)->nullable();
            $table->enum('trigger_type', ['appointment_confirmation', 'reminder_24h', 'reminder_2h', 'prescription', 'payment_link', 'recall', 'hep', 'result', 'birthday', 'manual', 'inbound_reply'])->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->enum('status', ['queued', 'sent', 'delivered', 'read', 'failed', 'error'])->default('queued');
            $table->string('error_code', 20)->nullable();
            $table->string('error_message', 300)->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['clinic_id', 'patient_id']);
            $table->index('wa_message_id');
            $table->index(['trigger_type', 'related_id']);
            $table->foreign('patient_id')->references('id')->on('patients')->nullOnDelete();
        });

        // ── 16. ABDM Compliance ───────────────────────────────────────────────
        Schema::create('abdm_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained();
            $table->string('consent_request_id', 100)->unique();
            $table->enum('status', ['REQUESTED', 'GRANTED', 'DENIED', 'REVOKED', 'EXPIRED'])->default('REQUESTED');
            $table->string('purpose', 30);
            $table->json('hi_types');
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->json('consent_artefact')->nullable();
            $table->string('consent_artefact_id', 100)->nullable();
            $table->dateTime('granted_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
            $table->index('patient_id');
            $table->index('status');
        });

        Schema::create('abdm_care_contexts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->string('care_context_reference', 100)->unique();
            $table->string('display_name', 200);
            $table->string('hi_type', 50);
            $table->string('fhir_resource_type', 50)->nullable();
            $table->string('fhir_bundle_url', 500)->nullable();
            $table->dateTime('pushed_at')->nullable();
            $table->enum('status', ['active', 'expired', 'revoked'])->default('active');
            $table->timestamp('created_at')->useCurrent();
            $table->index('patient_id');
            $table->foreign('visit_id')->references('id')->on('visits')->nullOnDelete();
        });

        // ── 17. Lab Orders & Vendor Management ────────────────────────────────
        Schema::create('vendor_labs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('lab_chain', 100)->nullable();
            $table->string('city', 100);
            $table->string('contact_phone', 15)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->boolean('api_enabled')->default(false);
            $table->string('api_endpoint', 300)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('clinic_vendor_links', function (Blueprint $table) {
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->references('id')->on('vendor_labs')->cascadeOnDelete();
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->boolean('is_preferred')->default(false);
            $table->timestamp('linked_at')->useCurrent();
            $table->primary(['clinic_id', 'vendor_id']);
        });

        Schema::create('lab_test_catalog', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->references('id')->on('vendor_labs');
            $table->string('test_code', 30);
            $table->string('test_name', 200);
            $table->string('department', 100)->nullable();
            $table->string('sample_type', 50)->nullable();
            $table->tinyInteger('turnaround_hours')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unique(['vendor_id', 'test_code']);
        });

        Schema::create('lab_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('doctor_id')->references('id')->on('users');
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->string('order_number', 30)->unique();
            $table->boolean('is_urgent')->default(false);
            $table->enum('status', ['new', 'accepted', 'sample_collected', 'processing', 'ready', 'sent', 'cancelled'])->default('new');
            $table->string('result_pdf_url', 500)->nullable();
            $table->string('result_pdf_s3_key', 500)->nullable();
            $table->dateTime('result_sent_at')->nullable();
            $table->boolean('result_sent_to_patient')->default(false);
            $table->string('fhir_resource_id', 100)->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->text('clinical_notes')->nullable();
            $table->timestamps();
            $table->index(['clinic_id', 'patient_id']);
            $table->index(['vendor_id', 'status']);
            $table->index('visit_id');
            $table->foreign('vendor_id')->references('id')->on('vendor_labs')->nullOnDelete();
            $table->foreign('visit_id')->references('id')->on('visits')->nullOnDelete();
        });

        Schema::create('lab_order_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('test_catalog_id')->nullable();
            $table->string('test_code', 30)->nullable();
            $table->string('test_name', 200);
            $table->boolean('is_urgent')->default(false);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->string('result_value', 200)->nullable();
            $table->string('result_unit', 50)->nullable();
            $table->string('reference_range', 100)->nullable();
            $table->boolean('is_abnormal')->nullable();
            $table->index('lab_order_id');
        });

        // ── 18. Audit & Security Logs ─────────────────────────────────────────
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 80);
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 300)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['clinic_id', 'action', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });

        // ── 19. Notification Queue ────────────────────────────────────────────
        Schema::create('notification_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->enum('channel', ['whatsapp', 'email', 'push', 'sms'])->default('whatsapp');
            $table->string('template_name', 100);
            $table->json('payload');
            $table->dateTime('scheduled_at');
            $table->dateTime('processed_at')->nullable();
            $table->enum('status', ['pending', 'processing', 'sent', 'failed'])->default('pending');
            $table->tinyInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['status', 'scheduled_at']);
            $table->index('clinic_id');
        });

        Log::info('ClinicOS migration completed successfully');
    }

    public function down(): void
    {
        Log::info('Rolling back ClinicOS migration');

        Schema::dropIfExists('notification_queue');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('lab_order_tests');
        Schema::dropIfExists('lab_orders');
        Schema::dropIfExists('lab_test_catalog');
        Schema::dropIfExists('clinic_vendor_links');
        Schema::dropIfExists('vendor_labs');
        Schema::dropIfExists('abdm_care_contexts');
        Schema::dropIfExists('abdm_consents');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('gst_sac_codes');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('patient_photos');
        Schema::dropIfExists('prescription_drugs');
        Schema::dropIfExists('indian_drugs');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('ophthal_refractions');
        Schema::dropIfExists('ophthal_va_logs');
        Schema::dropIfExists('physio_hep');
        Schema::dropIfExists('physio_treatment_plans');
        Schema::dropIfExists('dental_lab_orders');
        Schema::dropIfExists('dental_tooth_history');
        Schema::dropIfExists('dental_teeth');
        Schema::dropIfExists('visit_procedures');
        Schema::dropIfExists('visit_scales');
        Schema::dropIfExists('visit_lesions');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('doctor_availability');
        Schema::dropIfExists('appointment_services');
        Schema::dropIfExists('patient_family_members');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('clinic_equipment');
        Schema::dropIfExists('clinic_rooms');
        Schema::dropIfExists('clinic_locations');
        Schema::dropIfExists('clinics');

        Log::info('ClinicOS migration rollback completed');
    }
};
