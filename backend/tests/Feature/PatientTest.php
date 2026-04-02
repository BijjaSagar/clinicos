<?php

namespace Tests\Feature;

use App\Models\Patient;

class PatientTest extends BaseFeatureTest
{
    public function test_doctor_can_view_patients_list(): void
    {
        Patient::factory()->count(3)->create(['clinic_id' => $this->clinic->id]);
        $response = $this->actingAs($this->doctor)->get('/patients');
        $response->assertStatus(200);
    }

    public function test_doctor_can_create_patient(): void
    {
        $response = $this->actingAs($this->doctor)->post('/patients', [
            'name' => 'John Doe',
            'phone' => '9876543210',
            'sex' => 'M',
            'dob' => '1990-01-15',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('patients', ['name' => 'John Doe', 'clinic_id' => $this->clinic->id]);
    }

    public function test_patient_scoped_to_clinic(): void
    {
        $otherClinic = \App\Models\Clinic::factory()->create();
        Patient::factory()->create(['clinic_id' => $otherClinic->id, 'name' => 'Other Clinic Patient']);
        Patient::factory()->create(['clinic_id' => $this->clinic->id, 'name' => 'My Patient']);

        $response = $this->actingAs($this->doctor)->get('/patients');
        $response->assertSee('My Patient');
    }
}
