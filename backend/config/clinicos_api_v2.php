<?php

/**
 * ClinicOS HTTP API v2 (same Laravel app; versioned routes under /api/v2).
 *
 * Blade workspace, mobile apps, or external SPAs should:
 * 1. Authenticate via Sanctum (same as v1).
 * 2. GET /api/v2/bootstrap after login for module-aware UI.
 * 3. Call /api/v2/* endpoints; v1 remains available at /api/v1/*.
 */
return [
    'version' => '2.0.0',
];
