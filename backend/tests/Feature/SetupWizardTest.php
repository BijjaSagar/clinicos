<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_owner_redirected_to_setup_wizard(): void
    {
        $clinic = Clinic::factory()->create(['settings' => null]);
        $owner = User::factory()->create([
            'clinic_id' => $clinic->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($owner)->get('/dashboard');
        $response->assertRedirect(route('setup-wizard.index'));
    }

    public function test_completed_owner_sees_dashboard(): void
    {
        $clinic = Clinic::factory()->create(['settings' => ['setup_completed' => true]]);
        $owner = User::factory()->create([
            'clinic_id' => $clinic->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($owner)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_wizard_save_step_works(): void
    {
        $clinic = Clinic::factory()->create(['settings' => null]);
        $owner = User::factory()->create([
            'clinic_id' => $clinic->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($owner)->postJson('/setup/save', [
            'step' => 'clinic-info',
            'name' => 'Apollo Hospital',
            'phone' => '9876543210',
            'city' => 'Pune',
        ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('clinics', ['id' => $clinic->id, 'name' => 'Apollo Hospital']);
    }
}
