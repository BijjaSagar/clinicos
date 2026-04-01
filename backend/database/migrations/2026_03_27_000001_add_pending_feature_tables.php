<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * ClinicOS — Pending Feature Tables & Column Additions
 *
 * Adds: walk-in queue, prescription templates, drug interactions,
 * insurance claims, photo consents, AI transcriptions, and
 * enhances existing rooms, equipment, appointments, photos,
 * visit procedures, dental lab orders, physio plans, and clinics.
 */
return new class extends Migration
{
    public function up(): void
    {
        Log::info('Starting ClinicOS pending-features migration');

        // =====================================================================
        // 1. ENHANCE clinic_rooms — add equipment JSON, available_hours, type
        // =====================================================================
        if (Schema::hasTable('clinic_rooms')) {
            Schema::table('clinic_rooms', function (Blueprint $table) {
                if (!Schema::hasColumn('clinic_rooms', 'type')) {
                    $table->string('type', 50)->nullable()->after('name')
                        ->comment('consultation, procedure, lab, dental');
                }
                if (!Schema::hasColumn('clinic_rooms', 'equipment')) {
                    $table->json('equipment')->nullable()->after('capacity')
                        ->comment('List of equipment in room');
                }
                if (!Schema::hasColumn('clinic_rooms', 'available_hours')) {
                    $table->json('available_hours')->nullable()->after('equipment')
                        ->comment('{"mon":"09:00-18:00", ...}');
                }
                if (!Schema::hasColumn('clinic_rooms', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->after('created_at');
                }
            });
            Log::info('Enhanced clinic_rooms table');
        }

        // =====================================================================
        // 2. ENHANCE clinic_equipment — add room_id, brand, model, maintenance
        // =====================================================================
        if (Schema::hasTable('clinic_equipment')) {
            Schema::table('clinic_equipment', function (Blueprint $table) {
                if (!Schema::hasColumn('clinic_equipment', 'room_id')) {
                    $table->unsignedBigInteger('room_id')->nullable()->after('clinic_id');
                    $table->foreign('room_id')->references('id')->on('clinic_rooms')->nullOnDelete();
                }
                if (!Schema::hasColumn('clinic_equipment', 'type')) {
                    $table->string('type', 50)->nullable()->after('equipment_type')
                        ->comment('laser, electrotherapy, imaging, dental_chair');
                }
                if (!Schema::hasColumn('clinic_equipment', 'brand')) {
                    $table->string('brand', 100)->nullable()->after('name');
                }
                if (!Schema::hasColumn('clinic_equipment', 'model')) {
                    $table->string('model', 100)->nullable()->after('brand');
                }
                if (!Schema::hasColumn('clinic_equipment', 'last_maintenance')) {
                    $table->date('last_maintenance')->nullable()->after('serial_number');
                }
                if (!Schema::hasColumn('clinic_equipment', 'next_maintenance')) {
                    $table->date('next_maintenance')->nullable()->after('last_maintenance');
                }
                if (!Schema::hasColumn('clinic_equipment', 'is_available')) {
                    $table->boolean('is_available')->default(true)->after('is_active');
                }
                if (!Schema::hasColumn('clinic_equipment', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->after('created_at');
                }
            });
            Log::info('Enhanced clinic_equipment table');
        }

        // =====================================================================
        // 3. WALK-IN QUEUE
        // =====================================================================
        if (!Schema::hasTable('walk_in_queue')) {
            Schema::create('walk_in_queue', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->string('token_number', 20);          // W-001
                $table->string('visit_type', 30)->default('walk_in'); // walk_in, emergency
                $table->string('chief_complaint', 500)->nullable();
                $table->enum('status', ['waiting', 'called', 'in_consultation', 'completed', 'cancelled'])
                    ->default('waiting');
                $table->timestamp('checked_in_at')->useCurrent();
                $table->timestamp('called_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->integer('estimated_wait_minutes')->nullable();
                $table->timestamps();

                $table->foreign('doctor_id')->references('id')->on('users')->nullOnDelete();
                $table->index(['clinic_id', 'status']);
                $table->index(['clinic_id', 'checked_in_at']);
                $table->index('patient_id');
            });
            Log::info('Created walk_in_queue table');
        }

        // =====================================================================
        // 4. ENHANCE appointments — pre_visit_data, pre_visit_token, procedure_duration
        // =====================================================================
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (!Schema::hasColumn('appointments', 'pre_visit_data')) {
                    $table->json('pre_visit_data')->nullable()->after('notes')
                        ->comment('Questionnaire responses from patient');
                }
                if (!Schema::hasColumn('appointments', 'pre_visit_token')) {
                    $table->string('pre_visit_token', 64)->nullable()->unique()->after('pre_visit_answers')
                        ->comment('Unique token for pre-visit form URL');
                }
                if (!Schema::hasColumn('appointments', 'procedure_duration_minutes')) {
                    $table->integer('procedure_duration_minutes')->nullable()->after('duration_mins')
                        ->comment('Separate procedure time beyond consultation');
                }
            });
            Log::info('Enhanced appointments table');
        }

        // =====================================================================
        // 5. PRESCRIPTION TEMPLATES
        // =====================================================================
        if (!Schema::hasTable('prescription_templates')) {
            Schema::create('prescription_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('name', 200);                // Acne Vulgaris, Eczema
                $table->string('diagnosis', 300);            // ICD-10 code or text
                $table->string('specialty', 50);             // dermatology, dental, etc.
                $table->json('drugs');                        // array of drug objects
                $table->boolean('is_global')->default(false); // system-wide template
                $table->timestamps();

                $table->index(['clinic_id', 'specialty']);
                $table->index('is_global');
            });
            Log::info('Created prescription_templates table');
        }

        // =====================================================================
        // 6. DRUG INTERACTIONS
        // =====================================================================
        if (!Schema::hasTable('drug_interactions')) {
            Schema::create('drug_interactions', function (Blueprint $table) {
                $table->id();
                $table->string('drug_a_generic', 200);
                $table->string('drug_b_generic', 200);
                $table->enum('severity', ['minor', 'moderate', 'major', 'contraindicated']);
                $table->text('description');
                $table->text('management')->nullable();
                $table->timestamps();

                $table->index('drug_a_generic');
                $table->index('drug_b_generic');
                $table->unique(['drug_a_generic', 'drug_b_generic'], 'uq_drug_pair');
            });
            Log::info('Created drug_interactions table');
        }

        // =====================================================================
        // 7. INSURANCE CLAIMS
        // =====================================================================
        if (!Schema::hasTable('insurance_claims')) {
            Schema::create('insurance_claims', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
                $table->string('insurance_company', 200);
                $table->string('policy_number', 100);
                $table->string('tpa_name', 150)->nullable();
                $table->string('tpa_id', 100)->nullable();
                $table->string('card_number', 100)->nullable();
                $table->decimal('sum_insured', 12, 2)->nullable();
                $table->enum('claim_type', ['cashless', 'reimbursement']);
                $table->decimal('claim_amount', 10, 2);
                $table->decimal('approved_amount', 10, 2)->nullable();
                $table->decimal('settled_amount', 10, 2)->nullable();
                $table->string('pre_auth_number', 100)->nullable();
                $table->enum('status', [
                    'draft', 'submitted', 'under_review',
                    'approved', 'rejected', 'settled',
                ])->default('draft');
                $table->text('provisional_diagnosis')->nullable();
                $table->text('treatment_plan')->nullable();
                $table->json('documents')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->date('submitted_at')->nullable();
                $table->date('settled_at')->nullable();
                $table->timestamps();

                $table->index(['clinic_id', 'status']);
                $table->index('patient_id');
                $table->index('invoice_id');
                $table->index('policy_number');
            });
            Log::info('Created insurance_claims table');
        }

        // =====================================================================
        // 8. PHOTO CONSENTS
        // =====================================================================
        if (!Schema::hasTable('photo_consents')) {
            Schema::create('photo_consents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->enum('consent_type', ['internal', 'education', 'publication']);
                $table->text('consent_text');
                $table->text('signature_data')->nullable();  // base64 signature image
                $table->timestamp('consented_at')->useCurrent();
                $table->timestamp('revoked_at')->nullable();
                $table->unsignedBigInteger('witnessed_by')->nullable();
                $table->timestamps();

                $table->foreign('witnessed_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['clinic_id', 'patient_id']);
                $table->index('patient_id');
            });
            Log::info('Created photo_consents table');
        }

        // ── Enhance patient_photos — consent_id, comparison_group, sort_order ──
        if (Schema::hasTable('patient_photos')) {
            Schema::table('patient_photos', function (Blueprint $table) {
                if (!Schema::hasColumn('patient_photos', 'consent_id')) {
                    $table->unsignedBigInteger('consent_id')->nullable()->after('body_region');
                    $table->foreign('consent_id')->references('id')->on('photo_consents')->nullOnDelete();
                }
                if (!Schema::hasColumn('patient_photos', 'comparison_group')) {
                    $table->string('comparison_group', 100)->nullable()->after('consent_id')
                        ->comment('Group photos for before/after comparison');
                }
                if (!Schema::hasColumn('patient_photos', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('comparison_group');
                }
            });
            Log::info('Enhanced patient_photos table');
        }

        // =====================================================================
        // 9. ENHANCE visit_procedures — sac_code, performed_by, charge
        // =====================================================================
        if (Schema::hasTable('visit_procedures')) {
            Schema::table('visit_procedures', function (Blueprint $table) {
                if (!Schema::hasColumn('visit_procedures', 'sac_code')) {
                    $table->string('sac_code', 10)->nullable()->after('procedure_code')
                        ->comment('GST SAC code e.g. 999312');
                }
                if (!Schema::hasColumn('visit_procedures', 'performed_by')) {
                    $table->string('performed_by', 200)->nullable()->after('notes');
                }
                if (!Schema::hasColumn('visit_procedures', 'charge')) {
                    $table->decimal('charge', 10, 2)->nullable()->after('performed_by');
                }
                if (!Schema::hasColumn('visit_procedures', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->after('created_at');
                }
            });
            Log::info('Enhanced visit_procedures table');
        }

        // =====================================================================
        // 10. ENHANCE physio_treatment_plans — extra fields for richer plans
        // =====================================================================
        if (Schema::hasTable('physio_treatment_plans')) {
            Schema::table('physio_treatment_plans', function (Blueprint $table) {
                if (!Schema::hasColumn('physio_treatment_plans', 'doctor_id')) {
                    $table->unsignedBigInteger('doctor_id')->nullable()->after('clinic_id');
                    $table->foreign('doctor_id')->references('id')->on('users')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('physio_treatment_plans', 'frequency')) {
                    $table->string('frequency', 50)->nullable()->after('sessions_completed')
                        ->comment('3x/week, daily, etc.');
                }
                if (!Schema::hasColumn('physio_treatment_plans', 'start_date')) {
                    $table->date('start_date')->nullable()->after('frequency');
                }
                if (!Schema::hasColumn('physio_treatment_plans', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date');
                }
                if (!Schema::hasColumn('physio_treatment_plans', 'outcome_measures')) {
                    $table->json('outcome_measures')->nullable()->after('status')
                        ->comment('FIM, Barthel scores over time');
                }
            });
            Log::info('Enhanced physio_treatment_plans table');
        }

        // ── Enhance physio_hep — clinic_id, treatment_plan_id, precautions, etc. ──
        if (Schema::hasTable('physio_hep')) {
            Schema::table('physio_hep', function (Blueprint $table) {
                if (!Schema::hasColumn('physio_hep', 'clinic_id')) {
                    $table->unsignedBigInteger('clinic_id')->nullable()->after('id');
                    $table->foreign('clinic_id')->references('id')->on('clinics')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('physio_hep', 'treatment_plan_id')) {
                    $table->unsignedBigInteger('treatment_plan_id')->nullable()->after('patient_id');
                    $table->foreign('treatment_plan_id')
                        ->references('id')->on('physio_treatment_plans')->nullOnDelete();
                }
                if (!Schema::hasColumn('physio_hep', 'exercises')) {
                    $table->json('exercises')->nullable()->after('treatment_plan_id')
                        ->comment('[{name, sets, reps, hold_time, frequency, instructions, image_url}]');
                }
                if (!Schema::hasColumn('physio_hep', 'precautions')) {
                    $table->text('precautions')->nullable()->after('instructions');
                }
                if (!Schema::hasColumn('physio_hep', 'progression_plan')) {
                    $table->text('progression_plan')->nullable()->after('precautions');
                }
                if (!Schema::hasColumn('physio_hep', 'prescribed_date')) {
                    $table->date('prescribed_date')->nullable()->after('progression_plan');
                }
                if (!Schema::hasColumn('physio_hep', 'review_date')) {
                    $table->date('review_date')->nullable()->after('prescribed_date');
                }
                if (!Schema::hasColumn('physio_hep', 'sent_via_whatsapp')) {
                    $table->boolean('sent_via_whatsapp')->default(false)->after('review_date');
                }
            });
            Log::info('Enhanced physio_hep table');
        }

        // =====================================================================
        // 11. ENHANCE dental_lab_orders — richer lab order columns
        // =====================================================================
        if (Schema::hasTable('dental_lab_orders')) {
            Schema::table('dental_lab_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('dental_lab_orders', 'doctor_id')) {
                    $table->unsignedBigInteger('doctor_id')->nullable()->after('patient_id');
                    $table->foreign('doctor_id')->references('id')->on('users')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('dental_lab_orders', 'order_type')) {
                    $table->string('order_type', 100)->nullable()->after('doctor_id')
                        ->comment('Crown, Bridge, Denture, Orthodontic, Night Guard');
                }
                if (!Schema::hasColumn('dental_lab_orders', 'material')) {
                    $table->string('material', 100)->nullable()->after('order_type')
                        ->comment('Zirconia, PFM, E-Max, Acrylic, Metal');
                }
                if (!Schema::hasColumn('dental_lab_orders', 'teeth_involved')) {
                    $table->json('teeth_involved')->nullable()->after('material')
                        ->comment('[11, 12, 13] FDI numbers');
                }
                if (!Schema::hasColumn('dental_lab_orders', 'lab_id')) {
                    $table->unsignedBigInteger('lab_id')->nullable()->after('teeth_involved');
                    $table->foreign('lab_id')->references('id')->on('vendor_labs')->nullOnDelete();
                }
                if (!Schema::hasColumn('dental_lab_orders', 'special_instructions')) {
                    $table->text('special_instructions')->nullable()->after('lab_vendor');
                }
                if (!Schema::hasColumn('dental_lab_orders', 'expected_delivery')) {
                    $table->date('expected_delivery')->nullable()->after('special_instructions');
                }
                if (!Schema::hasColumn('dental_lab_orders', 'actual_delivery')) {
                    $table->date('actual_delivery')->nullable()->after('expected_delivery');
                }
                if (!Schema::hasColumn('dental_lab_orders', 'lab_charge')) {
                    $table->decimal('lab_charge', 10, 2)->nullable()->after('actual_delivery');
                }
                if (!Schema::hasColumn('dental_lab_orders', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->after('created_at');
                }
            });
            Log::info('Enhanced dental_lab_orders table');
        }

        // =====================================================================
        // 12. AI TRANSCRIPTION LOG
        // =====================================================================
        if (!Schema::hasTable('ai_transcriptions')) {
            Schema::create('ai_transcriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('visit_id')->nullable();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('audio_file_path', 500)->nullable();
                $table->text('transcript')->nullable();
                $table->json('mapped_fields')->nullable();
                $table->text('summary')->nullable();
                $table->string('language_detected', 10)->nullable(); // en, hi, hi-en
                $table->integer('audio_duration_seconds')->nullable();
                $table->decimal('api_cost', 8, 4)->nullable();
                $table->timestamps();

                $table->foreign('visit_id')->references('id')->on('visits')->nullOnDelete();
                $table->index(['clinic_id', 'created_at']);
                $table->index('visit_id');
                $table->index('user_id');
            });
            Log::info('Created ai_transcriptions table');
        }

        // =====================================================================
        // 13. ONLINE BOOKING — enhance clinics table
        // =====================================================================
        if (Schema::hasTable('clinics')) {
            Schema::table('clinics', function (Blueprint $table) {
                if (!Schema::hasColumn('clinics', 'online_booking_enabled')) {
                    $table->boolean('online_booking_enabled')->default(false)->after('slug');
                }
                if (!Schema::hasColumn('clinics', 'booking_settings')) {
                    $table->json('booking_settings')->nullable()->after('online_booking_enabled')
                        ->comment('{"advance_payment_required":false,"advance_amount":0,"slot_duration_default":15,"buffer_between_appointments":5}');
                }
            });
            Log::info('Enhanced clinics table with booking settings');
        }

        Log::info('ClinicOS pending-features migration completed');
    }

    public function down(): void
    {
        Log::info('Rolling back ClinicOS pending-features migration');

        // Drop new tables (reverse order of dependencies)
        Schema::dropIfExists('ai_transcriptions');
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('drug_interactions');
        Schema::dropIfExists('prescription_templates');
        Schema::dropIfExists('walk_in_queue');

        // Remove added columns from patient_photos (before dropping photo_consents)
        if (Schema::hasTable('patient_photos')) {
            Schema::table('patient_photos', function (Blueprint $table) {
                if (Schema::hasColumn('patient_photos', 'consent_id')) {
                    $table->dropForeign(['consent_id']);
                    $table->dropColumn('consent_id');
                }
                if (Schema::hasColumn('patient_photos', 'comparison_group')) {
                    $table->dropColumn('comparison_group');
                }
                if (Schema::hasColumn('patient_photos', 'sort_order')) {
                    $table->dropColumn('sort_order');
                }
            });
        }

        Schema::dropIfExists('photo_consents');

        // Remove added columns from clinic_rooms
        if (Schema::hasTable('clinic_rooms')) {
            Schema::table('clinic_rooms', function (Blueprint $table) {
                $dropCols = [];
                if (Schema::hasColumn('clinic_rooms', 'type')) $dropCols[] = 'type';
                if (Schema::hasColumn('clinic_rooms', 'equipment')) $dropCols[] = 'equipment';
                if (Schema::hasColumn('clinic_rooms', 'available_hours')) $dropCols[] = 'available_hours';
                if (Schema::hasColumn('clinic_rooms', 'updated_at')) $dropCols[] = 'updated_at';
                if (!empty($dropCols)) $table->dropColumn($dropCols);
            });
        }

        // Remove added columns from clinic_equipment
        if (Schema::hasTable('clinic_equipment')) {
            Schema::table('clinic_equipment', function (Blueprint $table) {
                if (Schema::hasColumn('clinic_equipment', 'room_id')) {
                    $table->dropForeign(['room_id']);
                    $table->dropColumn('room_id');
                }
                $dropCols = [];
                if (Schema::hasColumn('clinic_equipment', 'type')) $dropCols[] = 'type';
                if (Schema::hasColumn('clinic_equipment', 'brand')) $dropCols[] = 'brand';
                if (Schema::hasColumn('clinic_equipment', 'model')) $dropCols[] = 'model';
                if (Schema::hasColumn('clinic_equipment', 'last_maintenance')) $dropCols[] = 'last_maintenance';
                if (Schema::hasColumn('clinic_equipment', 'next_maintenance')) $dropCols[] = 'next_maintenance';
                if (Schema::hasColumn('clinic_equipment', 'is_available')) $dropCols[] = 'is_available';
                if (Schema::hasColumn('clinic_equipment', 'updated_at')) $dropCols[] = 'updated_at';
                if (!empty($dropCols)) $table->dropColumn($dropCols);
            });
        }

        // Remove added columns from appointments
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $dropCols = [];
                if (Schema::hasColumn('appointments', 'pre_visit_data')) $dropCols[] = 'pre_visit_data';
                if (Schema::hasColumn('appointments', 'pre_visit_token')) $dropCols[] = 'pre_visit_token';
                if (Schema::hasColumn('appointments', 'procedure_duration_minutes')) $dropCols[] = 'procedure_duration_minutes';
                if (!empty($dropCols)) $table->dropColumn($dropCols);
            });
        }

        // Remove added columns from visit_procedures
        if (Schema::hasTable('visit_procedures')) {
            Schema::table('visit_procedures', function (Blueprint $table) {
                $dropCols = [];
                if (Schema::hasColumn('visit_procedures', 'sac_code')) $dropCols[] = 'sac_code';
                if (Schema::hasColumn('visit_procedures', 'performed_by')) $dropCols[] = 'performed_by';
                if (Schema::hasColumn('visit_procedures', 'charge')) $dropCols[] = 'charge';
                if (Schema::hasColumn('visit_procedures', 'updated_at')) $dropCols[] = 'updated_at';
                if (!empty($dropCols)) $table->dropColumn($dropCols);
            });
        }

        // Remove added columns from physio_treatment_plans
        if (Schema::hasTable('physio_treatment_plans')) {
            Schema::table('physio_treatment_plans', function (Blueprint $table) {
                if (Schema::hasColumn('physio_treatment_plans', 'doctor_id')) {
                    $table->dropForeign(['doctor_id']);
                    $table->dropColumn('doctor_id');
                }
                $dropCols = [];
                if (Schema::hasColumn('physio_treatment_plans', 'frequency')) $dropCols[] = 'frequency';
                if (Schema::hasColumn('physio_treatment_plans', 'start_date')) $dropCols[] = 'start_date';
                if (Schema::hasColumn('physio_treatment_plans', 'end_date')) $dropCols[] = 'end_date';
                if (Schema::hasColumn('physio_treatment_plans', 'outcome_measures')) $dropCols[] = 'outcome_measures';
                if (!empty($dropCols)) $table->dropColumn($dropCols);
            });
        }

        // Remove added columns from physio_hep
        if (Schema::hasTable('physio_hep')) {
            Schema::table('physio_hep', function (Blueprint $table) {
                if (Schema::hasColumn('physio_hep', 'treatment_plan_id')) {
                    $table->dropForeign(['treatment_plan_id']);
                    $table->dropColumn('treatment_plan_id');
                }
                if (Schema::hasColumn('physio_hep', 'clinic_id')) {
                    $table->dropForeign(['clinic_id']);
                    $table->dropColumn('clinic_id');
                }
                $dropCols = [];
                if (Schema::hasColumn('physio_hep', 'exercises')) $dropCols[] = 'exercises';
                if (Schema::hasColumn('physio_hep', 'precautions')) $dropCols[] = 'precautions';
                if (Schema::hasColumn('physio_hep', 'progression_plan')) $dropCols[] = 'progression_plan';
                if (Schema::hasColumn('physio_hep', 'prescribed_date')) $dropCols[] = 'prescribed_date';
                if (Schema::hasColumn('physio_hep', 'review_date')) $dropCols[] = 'review_date';
                if (Schema::hasColumn('physio_hep', 'sent_via_whatsapp')) $dropCols[] = 'sent_via_whatsapp';
                if (!empty($dropCols)) $table->dropColumn($dropCols);
            });
        }

        // Remove added columns from dental_lab_orders
        if (Schema::hasTable('dental_lab_orders')) {
            Schema::table('dental_lab_orders', function (Blueprint $table) {
                if (Schema::hasColumn('dental_lab_orders', 'doctor_id')) {
                    $table->dropForeign(['doctor_id']);
                    $table->dropColumn('doctor_id');
                }
                if (Schema::hasColumn('dental_lab_orders', 'lab_id')) {
                    $table->dropForeign(['lab_id']);
                    $table->dropColumn('lab_id');
                }
                $dropCols = [];
                if (Schema::hasColumn('dental_lab_orders', 'order_type')) $dropCols[] = 'order_type';
                if (Schema::hasColumn('dental_lab_orders', 'material')) $dropCols[] = 'material';
                if (Schema::hasColumn('dental_lab_orders', 'teeth_involved')) $dropCols[] = 'teeth_involved';
                if (Schema::hasColumn('dental_lab_orders', 'special_instructions')) $dropCols[] = 'special_instructions';
                if (Schema::hasColumn('dental_lab_orders', 'expected_delivery')) $dropCols[] = 'expected_delivery';
                if (Schema::hasColumn('dental_lab_orders', 'actual_delivery')) $dropCols[] = 'actual_delivery';
                if (Schema::hasColumn('dental_lab_orders', 'lab_charge')) $dropCols[] = 'lab_charge';
                if (Schema::hasColumn('dental_lab_orders', 'updated_at')) $dropCols[] = 'updated_at';
                if (!empty($dropCols)) $table->dropColumn($dropCols);
            });
        }

        // Remove added columns from clinics
        if (Schema::hasTable('clinics')) {
            Schema::table('clinics', function (Blueprint $table) {
                $dropCols = [];
                if (Schema::hasColumn('clinics', 'online_booking_enabled')) $dropCols[] = 'online_booking_enabled';
                if (Schema::hasColumn('clinics', 'booking_settings')) $dropCols[] = 'booking_settings';
                if (!empty($dropCols)) $table->dropColumn($dropCols);
            });
        }

        Log::info('ClinicOS pending-features migration rollback completed');
    }
};
