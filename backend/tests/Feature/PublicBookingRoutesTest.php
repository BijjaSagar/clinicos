<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Smoke tests that do not require a migrated database (SQLite migrations fail on fulltext in this project).
 */
class PublicBookingRoutesTest extends TestCase
{
    public function test_public_booking_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('public.booking.directory'), 'GET /book (patient hub)');
        $this->assertTrue(Route::has('public.booking.show'), 'GET /book/{slug} (doctors + book)');
        $this->assertTrue(Route::has('public.booking.slots'));
        $this->assertTrue(Route::has('public.booking.book'));
        $this->assertTrue(Route::has('public.booking.create-order'));
    }
}
