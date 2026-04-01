<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * NABH-oriented checklist (documentation aid — not a certification).
 */
class ComplianceWebController extends Controller
{
    public function nabh(): View
    {
        Log::info('ComplianceWebController@nabh', ['user' => auth()->id(), 'clinic_id' => auth()->user()->clinic_id]);

        $sections = [
            'Patient rights' => [
                'Consent for examination and procedures documented where applicable.',
                'Patient identification verified at registration and before procedures.',
            ],
            'Care of patients' => [
                'Clinical records include history, examination, diagnosis, and plan.',
                'Critical values and drug allergies are visible in the EMR workflow.',
            ],
            'Management of medication' => [
                'Prescription includes drug name, dose, route, duration; interaction checks used when available.',
            ],
            'Hospital infection control' => [
                'Instrument reprocessing and hand hygiene per clinic SOP (document locally).',
            ],
            'Continuous quality improvement' => [
                'Review appointment wait times and no-show rates periodically.',
                'Incident log for near-misses and corrective actions.',
            ],
        ];

        return view('compliance.nabh', compact('sections'));
    }
}
