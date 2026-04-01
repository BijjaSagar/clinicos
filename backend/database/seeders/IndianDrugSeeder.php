<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Indian Drug Database Seeder
 * 
 * Seeds common drugs used in Indian clinical practice
 * Data structured for prescription auto-complete and dosage suggestions
 */
class IndianDrugSeeder extends Seeder
{
    public function run(): void
    {
        Log::info('IndianDrugSeeder: Starting to seed Indian drug database');

        $drugs = [
            // Dermatology drugs
            ['generic_name' => 'Adapalene', 'brand_names' => json_encode(['Adapalene', 'Adaferin', 'Deriva']), 'drug_class' => 'Retinoid', 'form' => 'Gel', 'strength' => '0.1%', 'manufacturer' => 'Galderma', 'schedule' => 'H', 'common_dosages' => json_encode(['Apply thin layer at night', 'Once daily at bedtime']), 'is_controlled' => false],
            ['generic_name' => 'Tretinoin', 'brand_names' => json_encode(['Retino-A', 'Tret', 'A-Ret']), 'drug_class' => 'Retinoid', 'form' => 'Cream', 'strength' => '0.025%', 'manufacturer' => 'Janssen', 'schedule' => 'H', 'common_dosages' => json_encode(['Apply at night', 'Start with 0.025%, increase to 0.05%']), 'is_controlled' => false],
            ['generic_name' => 'Clindamycin', 'brand_names' => json_encode(['Clindac A', 'Clindasol', 'Clinmi']), 'drug_class' => 'Antibiotic', 'form' => 'Gel', 'strength' => '1%', 'manufacturer' => 'Alkem', 'schedule' => 'H', 'common_dosages' => json_encode(['Apply twice daily', 'Apply to affected areas']), 'is_controlled' => false],
            ['generic_name' => 'Benzoyl Peroxide', 'brand_names' => json_encode(['Benzac AC', 'Persol', 'Brevoxyl']), 'drug_class' => 'Keratolytic', 'form' => 'Gel', 'strength' => '2.5%', 'manufacturer' => 'Galderma', 'schedule' => 'OTC', 'common_dosages' => json_encode(['Apply once daily', 'Start low, increase as tolerated']), 'is_controlled' => false],
            ['generic_name' => 'Isotretinoin', 'brand_names' => json_encode(['Isotroin', 'Tretiva', 'Sotret']), 'drug_class' => 'Retinoid', 'form' => 'Capsule', 'strength' => '10mg', 'manufacturer' => 'Cipla', 'schedule' => 'H', 'common_dosages' => json_encode(['0.5-1 mg/kg/day', '10mg twice daily']), 'is_controlled' => false],
            ['generic_name' => 'Betamethasone Valerate', 'brand_names' => json_encode(['Betnovate', 'Betasone', 'Diprovate']), 'drug_class' => 'Corticosteroid', 'form' => 'Cream', 'strength' => '0.1%', 'manufacturer' => 'GSK', 'schedule' => 'H', 'common_dosages' => json_encode(['Apply twice daily', 'Short-term use only']), 'is_controlled' => false],
            ['generic_name' => 'Calcipotriol', 'brand_names' => json_encode(['Daivonex', 'Calcitrene', 'Divonex']), 'drug_class' => 'Vitamin D Analogue', 'form' => 'Ointment', 'strength' => '0.005%', 'manufacturer' => 'Leo Pharma', 'schedule' => 'H', 'common_dosages' => json_encode(['Apply twice daily', 'Max 100g/week']), 'is_controlled' => false],
            ['generic_name' => 'Tacrolimus', 'brand_names' => json_encode(['Protopic', 'Tacroz', 'Talimus']), 'drug_class' => 'Calcineurin Inhibitor', 'form' => 'Ointment', 'strength' => '0.1%', 'manufacturer' => 'Astellas', 'schedule' => 'H', 'common_dosages' => json_encode(['Apply twice daily', 'Face and sensitive areas']), 'is_controlled' => false],
            ['generic_name' => 'Ketoconazole', 'brand_names' => json_encode(['Nizoral', 'Ketocip', 'Zocon']), 'drug_class' => 'Antifungal', 'form' => 'Cream', 'strength' => '2%', 'manufacturer' => 'Janssen', 'schedule' => 'H', 'common_dosages' => json_encode(['Apply once or twice daily', 'For 2-4 weeks']), 'is_controlled' => false],
            ['generic_name' => 'Terbinafine', 'brand_names' => json_encode(['Lamisil', 'Terbicip', 'Tyza']), 'drug_class' => 'Antifungal', 'form' => 'Cream', 'strength' => '1%', 'manufacturer' => 'Cipla', 'schedule' => 'H', 'common_dosages' => json_encode(['Apply once or twice daily', 'For 1-4 weeks']), 'is_controlled' => false],

            // Antibiotics
            ['generic_name' => 'Amoxicillin', 'brand_names' => json_encode(['Mox', 'Amoxil', 'Novamox']), 'drug_class' => 'Penicillin', 'form' => 'Capsule', 'strength' => '500mg', 'manufacturer' => 'Ranbaxy', 'schedule' => 'H', 'common_dosages' => json_encode(['500mg thrice daily', '250mg every 8 hours']), 'is_controlled' => false],
            ['generic_name' => 'Azithromycin', 'brand_names' => json_encode(['Azithral', 'Zithromax', 'Azee']), 'drug_class' => 'Macrolide', 'form' => 'Tablet', 'strength' => '500mg', 'manufacturer' => 'Alembic', 'schedule' => 'H', 'common_dosages' => json_encode(['500mg once daily for 3 days', '500mg on day 1, then 250mg for 4 days']), 'is_controlled' => false],
            ['generic_name' => 'Doxycycline', 'brand_names' => json_encode(['Doxt-SL', 'Doxy-1', 'Periostat']), 'drug_class' => 'Tetracycline', 'form' => 'Capsule', 'strength' => '100mg', 'manufacturer' => 'Sun Pharma', 'schedule' => 'H', 'common_dosages' => json_encode(['100mg twice daily', '100mg once daily for maintenance']), 'is_controlled' => false],
            ['generic_name' => 'Ciprofloxacin', 'brand_names' => json_encode(['Ciplox', 'Cifran', 'Ciprobid']), 'drug_class' => 'Fluoroquinolone', 'form' => 'Tablet', 'strength' => '500mg', 'manufacturer' => 'Cipla', 'schedule' => 'H', 'common_dosages' => json_encode(['500mg twice daily', '250mg twice daily']), 'is_controlled' => false],
            ['generic_name' => 'Metronidazole', 'brand_names' => json_encode(['Flagyl', 'Metrogyl', 'Arilin']), 'drug_class' => 'Nitroimidazole', 'form' => 'Tablet', 'strength' => '400mg', 'manufacturer' => 'Sanofi', 'schedule' => 'H', 'common_dosages' => json_encode(['400mg thrice daily', '500mg twice daily']), 'is_controlled' => false],
            ['generic_name' => 'Cephalexin', 'brand_names' => json_encode(['Phexin', 'Sporidex', 'Ceff']), 'drug_class' => 'Cephalosporin', 'form' => 'Capsule', 'strength' => '500mg', 'manufacturer' => 'GSK', 'schedule' => 'H', 'common_dosages' => json_encode(['500mg every 6 hours', '250mg every 6 hours']), 'is_controlled' => false],

            // Pain / Anti-inflammatory
            ['generic_name' => 'Paracetamol', 'brand_names' => json_encode(['Crocin', 'Dolo', 'Calpol']), 'drug_class' => 'Analgesic', 'form' => 'Tablet', 'strength' => '500mg', 'manufacturer' => 'GSK', 'schedule' => 'OTC', 'common_dosages' => json_encode(['500mg every 4-6 hours', 'Max 4g/day']), 'is_controlled' => false],
            ['generic_name' => 'Ibuprofen', 'brand_names' => json_encode(['Brufen', 'Ibugesic', 'Combiflam']), 'drug_class' => 'NSAID', 'form' => 'Tablet', 'strength' => '400mg', 'manufacturer' => 'Abbott', 'schedule' => 'H', 'common_dosages' => json_encode(['400mg thrice daily', '200-400mg every 6 hours']), 'is_controlled' => false],
            ['generic_name' => 'Diclofenac', 'brand_names' => json_encode(['Voveran', 'Diclomol', 'Volini']), 'drug_class' => 'NSAID', 'form' => 'Tablet', 'strength' => '50mg', 'manufacturer' => 'Novartis', 'schedule' => 'H', 'common_dosages' => json_encode(['50mg twice or thrice daily', 'With food']), 'is_controlled' => false],
            ['generic_name' => 'Naproxen', 'brand_names' => json_encode(['Naprosyn', 'Naxdom', 'Napex']), 'drug_class' => 'NSAID', 'form' => 'Tablet', 'strength' => '250mg', 'manufacturer' => 'Roche', 'schedule' => 'H', 'common_dosages' => json_encode(['250-500mg twice daily', 'With food']), 'is_controlled' => false],
            ['generic_name' => 'Aceclofenac', 'brand_names' => json_encode(['Zerodol', 'Hifenac', 'Dolowin']), 'drug_class' => 'NSAID', 'form' => 'Tablet', 'strength' => '100mg', 'manufacturer' => 'IPCA', 'schedule' => 'H', 'common_dosages' => json_encode(['100mg twice daily', 'After meals']), 'is_controlled' => false],

            // Antihistamines
            ['generic_name' => 'Cetirizine', 'brand_names' => json_encode(['Zyrtec', 'Cetzine', 'Okacet']), 'drug_class' => 'Antihistamine', 'form' => 'Tablet', 'strength' => '10mg', 'manufacturer' => 'UCB', 'schedule' => 'H', 'common_dosages' => json_encode(['10mg once daily', '5mg twice daily']), 'is_controlled' => false],
            ['generic_name' => 'Levocetirizine', 'brand_names' => json_encode(['Levocet', 'Xyzal', 'Vozet']), 'drug_class' => 'Antihistamine', 'form' => 'Tablet', 'strength' => '5mg', 'manufacturer' => 'GSK', 'schedule' => 'H', 'common_dosages' => json_encode(['5mg once daily at night', '2.5mg twice daily']), 'is_controlled' => false],
            ['generic_name' => 'Fexofenadine', 'brand_names' => json_encode(['Allegra', 'Fexo', 'Telfast']), 'drug_class' => 'Antihistamine', 'form' => 'Tablet', 'strength' => '120mg', 'manufacturer' => 'Sanofi', 'schedule' => 'H', 'common_dosages' => json_encode(['120mg once daily', '180mg once daily for urticaria']), 'is_controlled' => false],
            ['generic_name' => 'Hydroxyzine', 'brand_names' => json_encode(['Atarax', 'Anxiolest', 'Hyzine']), 'drug_class' => 'Antihistamine', 'form' => 'Tablet', 'strength' => '25mg', 'manufacturer' => 'UCB', 'schedule' => 'H', 'common_dosages' => json_encode(['25mg thrice daily', '10-25mg at night']), 'is_controlled' => false],

            // GI drugs
            ['generic_name' => 'Pantoprazole', 'brand_names' => json_encode(['Pantocid', 'Pantop', 'Pan-D']), 'drug_class' => 'PPI', 'form' => 'Tablet', 'strength' => '40mg', 'manufacturer' => 'Sun Pharma', 'schedule' => 'H', 'common_dosages' => json_encode(['40mg once daily before breakfast', '40mg twice daily']), 'is_controlled' => false],
            ['generic_name' => 'Omeprazole', 'brand_names' => json_encode(['Omez', 'Ocid', 'Omecip']), 'drug_class' => 'PPI', 'form' => 'Capsule', 'strength' => '20mg', 'manufacturer' => 'Dr Reddy', 'schedule' => 'H', 'common_dosages' => json_encode(['20mg once daily', '20mg twice daily']), 'is_controlled' => false],
            ['generic_name' => 'Ranitidine', 'brand_names' => json_encode(['Zinetac', 'Aciloc', 'Rantac']), 'drug_class' => 'H2 Blocker', 'form' => 'Tablet', 'strength' => '150mg', 'manufacturer' => 'GSK', 'schedule' => 'H', 'common_dosages' => json_encode(['150mg twice daily', '300mg at bedtime']), 'is_controlled' => false],
            ['generic_name' => 'Domperidone', 'brand_names' => json_encode(['Domstal', 'Vomistop', 'Motilium']), 'drug_class' => 'Prokinetic', 'form' => 'Tablet', 'strength' => '10mg', 'manufacturer' => 'Torrent', 'schedule' => 'H', 'common_dosages' => json_encode(['10mg thrice daily before meals', '10mg as needed']), 'is_controlled' => false],

            // Diabetes
            ['generic_name' => 'Metformin', 'brand_names' => json_encode(['Glycomet', 'Glucophage', 'Obimet']), 'drug_class' => 'Biguanide', 'form' => 'Tablet', 'strength' => '500mg', 'manufacturer' => 'USV', 'schedule' => 'H', 'common_dosages' => json_encode(['500mg twice daily', 'Start 500mg once daily, increase gradually']), 'is_controlled' => false],
            ['generic_name' => 'Glimepiride', 'brand_names' => json_encode(['Amaryl', 'Glimisave', 'Gemer']), 'drug_class' => 'Sulfonylurea', 'form' => 'Tablet', 'strength' => '1mg', 'manufacturer' => 'Sanofi', 'schedule' => 'H', 'common_dosages' => json_encode(['1-2mg once daily before breakfast', 'Max 8mg/day']), 'is_controlled' => false],

            // Cardiovascular
            ['generic_name' => 'Amlodipine', 'brand_names' => json_encode(['Amlovas', 'Amlong', 'Norvasc']), 'drug_class' => 'CCB', 'form' => 'Tablet', 'strength' => '5mg', 'manufacturer' => 'Pfizer', 'schedule' => 'H', 'common_dosages' => json_encode(['5mg once daily', '2.5-10mg once daily']), 'is_controlled' => false],
            ['generic_name' => 'Atenolol', 'brand_names' => json_encode(['Aten', 'Tenormin', 'Betacard']), 'drug_class' => 'Beta Blocker', 'form' => 'Tablet', 'strength' => '50mg', 'manufacturer' => 'AstraZeneca', 'schedule' => 'H', 'common_dosages' => json_encode(['50mg once daily', '25-100mg once daily']), 'is_controlled' => false],
            ['generic_name' => 'Losartan', 'brand_names' => json_encode(['Losar', 'Losacar', 'Repace']), 'drug_class' => 'ARB', 'form' => 'Tablet', 'strength' => '50mg', 'manufacturer' => 'Merck', 'schedule' => 'H', 'common_dosages' => json_encode(['50mg once daily', '25-100mg once daily']), 'is_controlled' => false],
            ['generic_name' => 'Atorvastatin', 'brand_names' => json_encode(['Atorva', 'Lipitor', 'Storvas']), 'drug_class' => 'Statin', 'form' => 'Tablet', 'strength' => '10mg', 'manufacturer' => 'Pfizer', 'schedule' => 'H', 'common_dosages' => json_encode(['10mg once daily at night', '10-40mg once daily']), 'is_controlled' => false],
            ['generic_name' => 'Aspirin', 'brand_names' => json_encode(['Ecosprin', 'Disprin', 'Aspirin']), 'drug_class' => 'Antiplatelet', 'form' => 'Tablet', 'strength' => '75mg', 'manufacturer' => 'USV', 'schedule' => 'OTC', 'common_dosages' => json_encode(['75mg once daily', '150mg once daily']), 'is_controlled' => false],

            // Supplements
            ['generic_name' => 'Vitamin D3', 'brand_names' => json_encode(['Calcirol', 'D-Rise', 'Uprise D3']), 'drug_class' => 'Vitamin', 'form' => 'Capsule', 'strength' => '60000 IU', 'manufacturer' => 'Cadila', 'schedule' => 'OTC', 'common_dosages' => json_encode(['60000 IU once weekly', '1000 IU daily']), 'is_controlled' => false],
            ['generic_name' => 'Vitamin B12', 'brand_names' => json_encode(['Methylcobal', 'Neurobion', 'Meconerv']), 'drug_class' => 'Vitamin', 'form' => 'Tablet', 'strength' => '1500mcg', 'manufacturer' => 'Merck', 'schedule' => 'OTC', 'common_dosages' => json_encode(['1500mcg once daily', '500mcg thrice daily']), 'is_controlled' => false],
            ['generic_name' => 'Calcium + Vitamin D3', 'brand_names' => json_encode(['Shelcal', 'Calcimax', 'CCM']), 'drug_class' => 'Supplement', 'form' => 'Tablet', 'strength' => '500mg', 'manufacturer' => 'Torrent', 'schedule' => 'OTC', 'common_dosages' => json_encode(['1 tablet twice daily', 'After meals']), 'is_controlled' => false],
            ['generic_name' => 'Iron + Folic Acid', 'brand_names' => json_encode(['Livogen', 'Feronia', 'Autrin']), 'drug_class' => 'Supplement', 'form' => 'Tablet', 'strength' => '100mg', 'manufacturer' => 'Ranbaxy', 'schedule' => 'OTC', 'common_dosages' => json_encode(['1 tablet once daily', 'Preferably empty stomach']), 'is_controlled' => false],

            // Sunscreen (commonly prescribed in dermatology)
            ['generic_name' => 'Sunscreen SPF 50+', 'brand_names' => json_encode(['Suncros', 'Photostable', 'La Shield']), 'drug_class' => 'Photoprotection', 'form' => 'Lotion', 'strength' => 'SPF 50+', 'manufacturer' => 'Sun Pharma', 'schedule' => 'OTC', 'common_dosages' => json_encode(['Apply 15-20 mins before sun exposure', 'Reapply every 2-3 hours']), 'is_controlled' => false],

            // Physiotherapy drugs
            ['generic_name' => 'Thiocolchicoside', 'brand_names' => json_encode(['Myoril', 'Relaxyl', 'Myospaz']), 'drug_class' => 'Muscle Relaxant', 'form' => 'Tablet', 'strength' => '4mg', 'manufacturer' => 'Sanofi', 'schedule' => 'H', 'common_dosages' => json_encode(['4mg twice daily', '8mg once daily']), 'is_controlled' => false],
            ['generic_name' => 'Etoricoxib', 'brand_names' => json_encode(['Arcoxia', 'Nucoxia', 'Etova']), 'drug_class' => 'COX-2 Inhibitor', 'form' => 'Tablet', 'strength' => '60mg', 'manufacturer' => 'Merck', 'schedule' => 'H', 'common_dosages' => json_encode(['60mg once daily', '90mg once daily for acute pain']), 'is_controlled' => false],
            ['generic_name' => 'Pregabalin', 'brand_names' => json_encode(['Lyrica', 'Pregastar', 'Pregalin']), 'drug_class' => 'Anticonvulsant', 'form' => 'Capsule', 'strength' => '75mg', 'manufacturer' => 'Pfizer', 'schedule' => 'H', 'common_dosages' => json_encode(['75mg twice daily', 'Start 75mg at night, increase as needed']), 'is_controlled' => true],

            // Dental drugs
            ['generic_name' => 'Amoxicillin + Clavulanic Acid', 'brand_names' => json_encode(['Augmentin', 'Clavam', 'Megamox']), 'drug_class' => 'Penicillin Combination', 'form' => 'Tablet', 'strength' => '625mg', 'manufacturer' => 'GSK', 'schedule' => 'H', 'common_dosages' => json_encode(['625mg twice daily', '625mg thrice daily for severe infections']), 'is_controlled' => false],
            ['generic_name' => 'Ornidazole', 'brand_names' => json_encode(['Ornidazole', 'Dazolic', 'Onit']), 'drug_class' => 'Nitroimidazole', 'form' => 'Tablet', 'strength' => '500mg', 'manufacturer' => 'Sun Pharma', 'schedule' => 'H', 'common_dosages' => json_encode(['500mg twice daily', 'For anaerobic infections']), 'is_controlled' => false],
            ['generic_name' => 'Chlorhexidine', 'brand_names' => json_encode(['Hexidine', 'Clohex', 'Rexidin']), 'drug_class' => 'Antiseptic', 'form' => 'Mouthwash', 'strength' => '0.2%', 'manufacturer' => 'ICPA', 'schedule' => 'OTC', 'common_dosages' => json_encode(['10ml twice daily', 'Rinse for 30 seconds, do not swallow']), 'is_controlled' => false],
        ];

        foreach ($drugs as $drug) {
            DB::table('indian_drugs')->updateOrInsert(
                ['generic_name' => $drug['generic_name'], 'strength' => $drug['strength']],
                $drug
            );
        }

        Log::info('IndianDrugSeeder: Seeded ' . count($drugs) . ' drugs');
    }
}
