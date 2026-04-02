<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class BaseFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected Clinic $clinic;
    protected User $owner;
    protected User $doctor;
    protected User $receptionist;
    protected User $nurse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::factory()->create([
            'name' => 'Test Clinic',
            'slug' => 'test-clinic',
            'facility_type' => 'hospital',
            'is_active' => true,
            'settings' => ['setup_completed' => true],
            'hims_features' => [
                'bed_management' => true,
                'ipd' => true,
                'opd_hospital' => true,
                'pharmacy_inventory' => true,
                'lis_collection' => true,
            ],
        ]);

        $this->owner = User::factory()->create([
            'clinic_id' => $this->clinic->id,
            'role' => 'owner',
            'name' => 'Test Owner',
        ]);

        $this->doctor = User::factory()->create([
            'clinic_id' => $this->clinic->id,
            'role' => 'doctor',
            'name' => 'Dr. Test',
        ]);

        $this->receptionist = User::factory()->create([
            'clinic_id' => $this->clinic->id,
            'role' => 'receptionist',
            'name' => 'Test Receptionist',
        ]);

        $this->nurse = User::factory()->create([
            'clinic_id' => $this->clinic->id,
            'role' => 'nurse',
            'name' => 'Test Nurse',
        ]);
    }
}
