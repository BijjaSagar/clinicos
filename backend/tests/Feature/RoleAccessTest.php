<?php

namespace Tests\Feature;

class RoleAccessTest extends BaseFeatureTest
{
    public function test_owner_can_access_settings(): void
    {
        $response = $this->actingAs($this->owner)->get('/settings');
        $response->assertStatus(200);
    }

    public function test_doctor_cannot_access_settings(): void
    {
        $response = $this->actingAs($this->doctor)->get('/settings');
        $response->assertRedirect(route('dashboard'));
    }

    public function test_receptionist_cannot_access_ipd(): void
    {
        $response = $this->actingAs($this->receptionist)->get('/ipd');
        $response->assertRedirect(route('dashboard'));
    }

    public function test_nurse_can_access_ipd(): void
    {
        $response = $this->actingAs($this->nurse)->get('/ipd');
        $response->assertStatus(200);
    }

    public function test_doctor_can_access_pharmacy(): void
    {
        $response = $this->actingAs($this->doctor)->get('/pharmacy');
        $response->assertStatus(200);
    }

    public function test_receptionist_can_access_opd_queue(): void
    {
        $response = $this->actingAs($this->receptionist)->get('/opd/queue');
        $response->assertStatus(200);
    }

    public function test_nurse_cannot_access_pharmacy(): void
    {
        $response = $this->actingAs($this->nurse)->get('/pharmacy');
        $response->assertRedirect(route('dashboard'));
    }

    public function test_owner_can_access_all_modules(): void
    {
        $routes = ['/dashboard', '/ipd', '/pharmacy', '/opd/queue', '/settings', '/hospital-settings'];
        foreach ($routes as $route) {
            $response = $this->actingAs($this->owner)->get($route);
            $this->assertContains($response->getStatusCode(), [200, 302], "Failed for route: {$route}");
        }
    }

    public function test_unauthenticated_cannot_access_hims(): void
    {
        $routes = ['/ipd', '/pharmacy', '/opd/queue', '/laboratory'];
        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }
}
