<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Drug Interaction Checker for Indian prescriptions.
 * Contains top 50 clinically significant drug-drug interactions commonly
 * encountered in Indian clinical practice (dermatology, general medicine,
 * cardiology, diabetes, dental, ophthalmology, physio).
 */
class DrugInteractionService
{
    /**
     * Known drug interactions — each entry maps two generic drug names
     * (or drug classes) to a severity level and clinical description.
     *
     * Severity: major | moderate | minor
     *
     * @var array<int, array{drug_a: string, drug_b: string, severity: string, description: string}>
     */
    private static array $interactions = [
        // ── Major interactions ──────────────────────────────────────────
        ['drug_a' => 'Warfarin',        'drug_b' => 'Aspirin',           'severity' => 'major',    'description' => 'Increased bleeding risk — avoid combination or monitor INR closely'],
        ['drug_a' => 'Warfarin',        'drug_b' => 'Ibuprofen',        'severity' => 'major',    'description' => 'NSAIDs increase anticoagulant effect and GI bleeding risk'],
        ['drug_a' => 'Warfarin',        'drug_b' => 'Metronidazole',    'severity' => 'major',    'description' => 'Metronidazole inhibits warfarin metabolism — INR may rise dangerously'],
        ['drug_a' => 'Warfarin',        'drug_b' => 'Fluconazole',      'severity' => 'major',    'description' => 'Fluconazole inhibits CYP2C9 causing elevated warfarin levels'],
        ['drug_a' => 'Metformin',       'drug_b' => 'Contrast Dye',     'severity' => 'major',    'description' => 'Risk of lactic acidosis — withhold metformin 48h before/after contrast'],
        ['drug_a' => 'Methotrexate',    'drug_b' => 'Trimethoprim',     'severity' => 'major',    'description' => 'Both are folate antagonists — pancytopenia risk'],
        ['drug_a' => 'Methotrexate',    'drug_b' => 'NSAIDs',           'severity' => 'major',    'description' => 'NSAIDs reduce renal clearance of methotrexate — toxicity risk'],
        ['drug_a' => 'Methotrexate',    'drug_b' => 'Ibuprofen',        'severity' => 'major',    'description' => 'Ibuprofen decreases renal clearance of methotrexate'],
        ['drug_a' => 'Methotrexate',    'drug_b' => 'Diclofenac',       'severity' => 'major',    'description' => 'Diclofenac decreases renal clearance of methotrexate'],
        ['drug_a' => 'Isotretinoin',    'drug_b' => 'Tetracycline',     'severity' => 'major',    'description' => 'Both cause intracranial hypertension (pseudotumour cerebri)'],
        ['drug_a' => 'Isotretinoin',    'drug_b' => 'Doxycycline',      'severity' => 'major',    'description' => 'Combination increases risk of pseudotumour cerebri'],
        ['drug_a' => 'Isotretinoin',    'drug_b' => 'Minocycline',      'severity' => 'major',    'description' => 'Combination increases risk of pseudotumour cerebri'],
        ['drug_a' => 'Isotretinoin',    'drug_b' => 'Vitamin A',        'severity' => 'major',    'description' => 'Additive hypervitaminosis A toxicity'],
        ['drug_a' => 'ACE Inhibitor',   'drug_b' => 'Potassium',        'severity' => 'major',    'description' => 'Hyperkalemia risk — monitor serum potassium'],
        ['drug_a' => 'Enalapril',       'drug_b' => 'Potassium',        'severity' => 'major',    'description' => 'Hyperkalemia risk with ACE inhibitor + potassium supplementation'],
        ['drug_a' => 'Lithium',         'drug_b' => 'Ibuprofen',        'severity' => 'major',    'description' => 'NSAIDs reduce lithium clearance — toxicity risk'],
        ['drug_a' => 'Simvastatin',     'drug_b' => 'Erythromycin',     'severity' => 'major',    'description' => 'CYP3A4 inhibition increases statin levels — rhabdomyolysis risk'],
        ['drug_a' => 'Atorvastatin',    'drug_b' => 'Itraconazole',     'severity' => 'major',    'description' => 'CYP3A4 inhibition increases statin levels — rhabdomyolysis risk'],
        ['drug_a' => 'Clopidogrel',     'drug_b' => 'Omeprazole',       'severity' => 'major',    'description' => 'Omeprazole inhibits CYP2C19 reducing clopidogrel activation'],
        ['drug_a' => 'Digoxin',         'drug_b' => 'Amiodarone',       'severity' => 'major',    'description' => 'Amiodarone increases digoxin levels — reduce digoxin dose by 50%'],

        // ── Moderate interactions ───────────────────────────────────────
        ['drug_a' => 'Ciprofloxacin',   'drug_b' => 'Theophylline',     'severity' => 'moderate', 'description' => 'Ciprofloxacin inhibits theophylline metabolism — seizure risk'],
        ['drug_a' => 'Ciprofloxacin',   'drug_b' => 'Antacids',         'severity' => 'moderate', 'description' => 'Antacids reduce ciprofloxacin absorption — separate by 2 hours'],
        ['drug_a' => 'Ciprofloxacin',   'drug_b' => 'Iron',             'severity' => 'moderate', 'description' => 'Iron chelates fluoroquinolones reducing absorption'],
        ['drug_a' => 'Ciprofloxacin',   'drug_b' => 'Calcium',          'severity' => 'moderate', 'description' => 'Calcium reduces fluoroquinolone absorption'],
        ['drug_a' => 'Doxycycline',     'drug_b' => 'Antacids',         'severity' => 'moderate', 'description' => 'Antacids reduce doxycycline absorption — separate doses'],
        ['drug_a' => 'Doxycycline',     'drug_b' => 'Iron',             'severity' => 'moderate', 'description' => 'Iron reduces tetracycline absorption — separate by 2-3 hours'],
        ['drug_a' => 'Doxycycline',     'drug_b' => 'Calcium',          'severity' => 'moderate', 'description' => 'Calcium reduces tetracycline absorption'],
        ['drug_a' => 'Azithromycin',    'drug_b' => 'Antacids',         'severity' => 'moderate', 'description' => 'Antacids reduce azithromycin peak levels — separate by 2 hours'],
        ['drug_a' => 'Metronidazole',   'drug_b' => 'Alcohol',          'severity' => 'moderate', 'description' => 'Disulfiram-like reaction — nausea, vomiting, flushing'],
        ['drug_a' => 'Fluconazole',     'drug_b' => 'Glimepiride',      'severity' => 'moderate', 'description' => 'Fluconazole inhibits glimepiride metabolism — hypoglycemia risk'],
        ['drug_a' => 'Itraconazole',    'drug_b' => 'Simvastatin',      'severity' => 'major',    'description' => 'CYP3A4 inhibition — rhabdomyolysis risk, contraindicated'],
        ['drug_a' => 'Ketoconazole',    'drug_b' => 'Simvastatin',      'severity' => 'major',    'description' => 'CYP3A4 inhibition — rhabdomyolysis risk, contraindicated'],
        ['drug_a' => 'Amlodipine',      'drug_b' => 'Simvastatin',      'severity' => 'moderate', 'description' => 'Limit simvastatin to 20 mg/day with amlodipine'],
        ['drug_a' => 'ACE Inhibitor',   'drug_b' => 'Spironolactone',   'severity' => 'moderate', 'description' => 'Hyperkalemia risk — monitor potassium regularly'],
        ['drug_a' => 'Metformin',       'drug_b' => 'Alcohol',          'severity' => 'moderate', 'description' => 'Increased risk of lactic acidosis'],
        ['drug_a' => 'Aspirin',         'drug_b' => 'Ibuprofen',        'severity' => 'moderate', 'description' => 'Ibuprofen may reduce cardioprotective effect of low-dose aspirin'],
        ['drug_a' => 'Diclofenac',      'drug_b' => 'Aspirin',          'severity' => 'moderate', 'description' => 'Increased GI bleeding risk and reduced aspirin efficacy'],
        ['drug_a' => 'Prednisolone',    'drug_b' => 'NSAIDs',           'severity' => 'moderate', 'description' => 'Increased risk of GI bleeding and peptic ulcer'],
        ['drug_a' => 'Prednisolone',    'drug_b' => 'Ibuprofen',        'severity' => 'moderate', 'description' => 'Corticosteroid + NSAID increases GI ulceration risk'],
        ['drug_a' => 'Telmisartan',     'drug_b' => 'Potassium',        'severity' => 'moderate', 'description' => 'ARBs can increase potassium — monitor levels'],
        ['drug_a' => 'Hydroxychloroquine', 'drug_b' => 'Azithromycin',  'severity' => 'moderate', 'description' => 'QT prolongation risk — ECG monitoring recommended'],
        ['drug_a' => 'Tramadol',        'drug_b' => 'SSRIs',            'severity' => 'moderate', 'description' => 'Serotonin syndrome risk — monitor for agitation, tremor'],
        ['drug_a' => 'Tramadol',        'drug_b' => 'Ondansetron',      'severity' => 'moderate', 'description' => 'Ondansetron may reduce tramadol analgesic efficacy'],
        ['drug_a' => 'Pregabalin',      'drug_b' => 'Opioids',          'severity' => 'moderate', 'description' => 'Additive CNS depression — respiratory depression risk'],
        ['drug_a' => 'Levothyroxine',   'drug_b' => 'Calcium',          'severity' => 'moderate', 'description' => 'Calcium reduces levothyroxine absorption — separate by 4 hours'],
        ['drug_a' => 'Levothyroxine',   'drug_b' => 'Iron',             'severity' => 'moderate', 'description' => 'Iron reduces levothyroxine absorption — separate by 4 hours'],

        // ── Minor interactions ──────────────────────────────────────────
        ['drug_a' => 'Pantoprazole',    'drug_b' => 'Iron',             'severity' => 'minor',    'description' => 'PPIs reduce iron absorption by raising gastric pH'],
        ['drug_a' => 'Omeprazole',      'drug_b' => 'Calcium',          'severity' => 'minor',    'description' => 'Long-term PPI use may reduce calcium absorption'],
        ['drug_a' => 'Cetirizine',      'drug_b' => 'Alcohol',          'severity' => 'minor',    'description' => 'Additive sedation — advise caution when driving'],
        ['drug_a' => 'Montelukast',     'drug_b' => 'Phenobarbital',    'severity' => 'minor',    'description' => 'Enzyme inducers may reduce montelukast levels'],
    ];

    /**
     * Check a list of drug names for known interactions among them.
     *
     * @param  array<string>  $drugNames  Generic or brand names of drugs in the prescription
     * @return array<int, array{drug_a: string, drug_b: string, severity: string, description: string}>
     */
    public static function check(array $drugNames): array
    {
        $conflicts = [];
        $normalised = array_map('strtolower', $drugNames);

        for ($i = 0, $count = count($normalised); $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                foreach (self::$interactions as $interaction) {
                    $a = strtolower($interaction['drug_a']);
                    $b = strtolower($interaction['drug_b']);

                    $matchAB = (str_contains($normalised[$i], $a) && str_contains($normalised[$j], $b));
                    $matchBA = (str_contains($normalised[$i], $b) && str_contains($normalised[$j], $a));

                    if ($matchAB || $matchBA) {
                        $conflicts[] = [
                            'drug_a'      => $drugNames[$i],
                            'drug_b'      => $drugNames[$j],
                            'severity'    => $interaction['severity'],
                            'description' => $interaction['description'],
                        ];
                    }
                }
            }
        }

        if (!empty($conflicts)) {
            Log::warning('Drug interactions detected', [
                'drugs'     => $drugNames,
                'conflicts' => count($conflicts),
            ]);
        }

        return $conflicts;
    }

    /**
     * Check if a single new drug has interactions with existing drugs.
     *
     * @param  string         $newDrug       Generic name of the new drug
     * @param  array<string>  $existingDrugs Generic names already in the prescription
     * @return array<int, array{drug_a: string, drug_b: string, severity: string, description: string}>
     */
    public static function checkSingle(string $newDrug, array $existingDrugs): array
    {
        $allDrugs = array_merge($existingDrugs, [$newDrug]);
        $allConflicts = self::check($allDrugs);

        // Only return conflicts involving the new drug
        $newNorm = strtolower($newDrug);

        return array_values(array_filter($allConflicts, function (array $conflict) use ($newNorm) {
            return str_contains(strtolower($conflict['drug_a']), $newNorm)
                || str_contains(strtolower($conflict['drug_b']), $newNorm);
        }));
    }

    /**
     * Get all stored interactions (e.g. for front-end JSON download).
     *
     * @return array
     */
    public static function allInteractions(): array
    {
        return self::$interactions;
    }
}
