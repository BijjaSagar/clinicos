<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HimsFeatureGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_clinic_without_ipd_feature_blocked(): void
    {
        $clinic = Clinic::factory()->create([
            'facility_type' => 'clinic',
            'hims_features' => ['ipd' => false],
        ]);
        $doctor = User::factory()->create([
            'clinic_id' => $clinic->id,
            'role' => 'doctor',
        ]);

        $response = $this->actingAs($doctor)->get('/ipd');
        $response->assertRedirect(route('dashboard'));
    }

    public function test_hospital_with_ipd_feature_allowed(): void
    {
        $clinic = Clinic::factory()->create([
            'facility_type' => 'hospital',
            'hims_features' => ['ipd' => true],
        ]);
        $doctor = User::factory()->create([
            'clinic_id' => $clinic->id,
            'role' => 'doctor',
        ]);

        $response = $this->actingAs($doctor)->get('/ipd');
        $response->assertStatus(200);
    }

    public function test_hospital_with_empty_features_gets_all_access(): void
    {
        $clinic = Clinic::factory()->create([
            'facility_type' => 'hospital',
            'hims_features' => null,
        ]);
        $doctor = User::factory()->create([
            'clinic_id' => $clinic->id,
            'role' => 'doctor',
        ]);

        $response = $this->actingAs($doctor)->get('/ipd');
        $response->assertStatus(200);
    }
}
