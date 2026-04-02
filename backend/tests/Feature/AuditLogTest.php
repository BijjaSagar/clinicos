<?php

namespace Tests\Feature;

class AuditLogTest extends BaseFeatureTest
{
    public function test_owner_can_view_audit_log(): void
    {
        $response = $this->actingAs($this->owner)->get('/audit-log');
        $response->assertStatus(200);
    }

    public function test_doctor_cannot_view_audit_log(): void
    {
        $response = $this->actingAs($this->doctor)->get('/audit-log');
        $response->assertRedirect(route('dashboard'));
    }
}
