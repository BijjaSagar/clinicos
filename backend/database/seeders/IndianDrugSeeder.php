<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class IndianDrugSeeder extends Seeder
{
    public function run(): void
    {
        Log::info('IndianDrugSeeder: Starting seeder');
        $now = Carbon::now();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('indian_drugs')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $drugs = [
            // Dermatology - Topicals
            ['name' => 'Adapalene Gel 0.1%', 'composition' => 'Adapalene 0.1%', 'form' => 'gel', 'strength' => '0.1%', 'manufacturer' => 'Galderma', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tretinoin Cream 0.025%', 'composition' => 'Tretinoin 0.025%', 'form' => 'cream', 'strength' => '0.025%', 'manufacturer' => 'Menarini', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Benzoyl Peroxide Gel 2.5%', 'composition' => 'Benzoyl Peroxide 2.5%', 'form' => 'gel', 'strength' => '2.5%', 'manufacturer' => 'Galderma', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Clindamycin Gel 1%', 'composition' => 'Clindamycin Phosphate 1%', 'form' => 'gel', 'strength' => '1%', 'manufacturer' => 'Cipla', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mometasone Cream 0.1%', 'composition' => 'Mometasone Furoate 0.1%', 'form' => 'cream', 'strength' => '0.1%', 'manufacturer' => 'Glenmark', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tacrolimus Ointment 0.1%', 'composition' => 'Tacrolimus 0.1%', 'form' => 'ointment', 'strength' => '0.1%', 'manufacturer' => 'Glenmark', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ketoconazole Cream 2%', 'composition' => 'Ketoconazole 2%', 'form' => 'cream', 'strength' => '2%', 'manufacturer' => 'Cipla', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Clobetasol Propionate Cream 0.05%', 'composition' => 'Clobetasol Propionate 0.05%', 'form' => 'cream', 'strength' => '0.05%', 'manufacturer' => 'Sun Pharma', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Minoxidil Solution 5%', 'composition' => 'Minoxidil 5%', 'form' => 'solution', 'strength' => '5%', 'manufacturer' => 'Dr Reddy', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Hydroquinone Cream 2%', 'composition' => 'Hydroquinone 2%', 'form' => 'cream', 'strength' => '2%', 'manufacturer' => 'Galderma', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            
            // Dermatology - Systemic
            ['name' => 'Isotretinoin Capsule 20mg', 'composition' => 'Isotretinoin 20mg', 'form' => 'capsule', 'strength' => '20mg', 'manufacturer' => 'Cipla', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Doxycycline Capsule 100mg', 'composition' => 'Doxycycline 100mg', 'form' => 'capsule', 'strength' => '100mg', 'manufacturer' => 'Ranbaxy', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Methotrexate Tablet 7.5mg', 'composition' => 'Methotrexate 7.5mg', 'form' => 'tablet', 'strength' => '7.5mg', 'manufacturer' => 'Ipca', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Cetirizine Tablet 10mg', 'composition' => 'Cetirizine 10mg', 'form' => 'tablet', 'strength' => '10mg', 'manufacturer' => 'Cipla', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Hydroxyzine Tablet 25mg', 'composition' => 'Hydroxyzine 25mg', 'form' => 'tablet', 'strength' => '25mg', 'manufacturer' => 'Sun Pharma', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Fluconazole Tablet 150mg', 'composition' => 'Fluconazole 150mg', 'form' => 'tablet', 'strength' => '150mg', 'manufacturer' => 'Cipla', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Itraconazole Capsule 100mg', 'composition' => 'Itraconazole 100mg', 'form' => 'capsule', 'strength' => '100mg', 'manufacturer' => 'Sun Pharma', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Terbinafine Tablet 250mg', 'composition' => 'Terbinafine 250mg', 'form' => 'tablet', 'strength' => '250mg', 'manufacturer' => 'Dr Reddy', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            
            // Analgesics / NSAIDs
            ['name' => 'Paracetamol Tablet 500mg', 'composition' => 'Paracetamol 500mg', 'form' => 'tablet', 'strength' => '500mg', 'manufacturer' => 'Cipla', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ibuprofen Tablet 400mg', 'composition' => 'Ibuprofen 400mg', 'form' => 'tablet', 'strength' => '400mg', 'manufacturer' => 'Cipla', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Diclofenac Tablet 50mg', 'composition' => 'Diclofenac 50mg', 'form' => 'tablet', 'strength' => '50mg', 'manufacturer' => 'Novartis', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Aceclofenac + Paracetamol Tablet', 'composition' => 'Aceclofenac 100mg + Paracetamol 325mg', 'form' => 'tablet', 'strength' => '100mg+325mg', 'manufacturer' => 'Intas', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Etoricoxib Tablet 90mg', 'composition' => 'Etoricoxib 90mg', 'form' => 'tablet', 'strength' => '90mg', 'manufacturer' => 'MSD', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tramadol + Paracetamol Tablet', 'composition' => 'Tramadol 37.5mg + Paracetamol 325mg', 'form' => 'tablet', 'strength' => '37.5mg+325mg', 'manufacturer' => 'Sun Pharma', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            
            // Muscle Relaxants
            ['name' => 'Thiocolchicoside Capsule 4mg', 'composition' => 'Thiocolchicoside 4mg', 'form' => 'capsule', 'strength' => '4mg', 'manufacturer' => 'Sun Pharma', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Chlorzoxazone + Paracetamol Tablet', 'composition' => 'Chlorzoxazone 250mg + Paracetamol 500mg', 'form' => 'tablet', 'strength' => '250mg+500mg', 'manufacturer' => 'Alkem', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            
            // Antibiotics (Dental)
            ['name' => 'Amoxicillin Capsule 500mg', 'composition' => 'Amoxicillin 500mg', 'form' => 'capsule', 'strength' => '500mg', 'manufacturer' => 'Cipla', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Amoxicillin + Clavulanic Acid Tablet 625mg', 'composition' => 'Amoxicillin 500mg + Clavulanic Acid 125mg', 'form' => 'tablet', 'strength' => '625mg', 'manufacturer' => 'GSK', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Azithromycin Tablet 500mg', 'composition' => 'Azithromycin 500mg', 'form' => 'tablet', 'strength' => '500mg', 'manufacturer' => 'Cipla', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Metronidazole Tablet 400mg', 'composition' => 'Metronidazole 400mg', 'form' => 'tablet', 'strength' => '400mg', 'manufacturer' => 'Cipla', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Clindamycin Capsule 300mg', 'composition' => 'Clindamycin 300mg', 'form' => 'capsule', 'strength' => '300mg', 'manufacturer' => 'Alkem', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            
            // Dental - Mouthwash / Topicals
            ['name' => 'Chlorhexidine Mouthwash 0.2%', 'composition' => 'Chlorhexidine Gluconate 0.2%', 'form' => 'mouthwash', 'strength' => '0.2%', 'manufacturer' => 'Colgate', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Povidone Iodine Mouthwash', 'composition' => 'Povidone Iodine 1%', 'form' => 'mouthwash', 'strength' => '1%', 'manufacturer' => 'Win Medicare', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Lignocaine Gel 2%', 'composition' => 'Lignocaine 2%', 'form' => 'gel', 'strength' => '2%', 'manufacturer' => 'Neon', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Benzydamine Mouthwash', 'composition' => 'Benzydamine HCl 0.15%', 'form' => 'mouthwash', 'strength' => '0.15%', 'manufacturer' => 'Sun Pharma', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            
            // Supplements
            ['name' => 'Vitamin D3 Tablet 60000IU', 'composition' => 'Cholecalciferol 60000IU', 'form' => 'tablet', 'strength' => '60000IU', 'manufacturer' => 'Sun Pharma', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Calcium + Vitamin D3 Tablet', 'composition' => 'Calcium Carbonate 500mg + Vitamin D3 250IU', 'form' => 'tablet', 'strength' => '500mg+250IU', 'manufacturer' => 'Cipla', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Biotin Tablet 5mg', 'composition' => 'Biotin 5mg', 'form' => 'tablet', 'strength' => '5mg', 'manufacturer' => 'Zydus', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Iron + Folic Acid Tablet', 'composition' => 'Ferrous Fumarate 100mg + Folic Acid 0.5mg', 'form' => 'tablet', 'strength' => '100mg+0.5mg', 'manufacturer' => 'Sun Pharma', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Glucosamine Tablet 1500mg', 'composition' => 'Glucosamine Sulfate 1500mg', 'form' => 'tablet', 'strength' => '1500mg', 'manufacturer' => 'Intas', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            
            // PPIs / Antacids
            ['name' => 'Pantoprazole Tablet 40mg', 'composition' => 'Pantoprazole 40mg', 'form' => 'tablet', 'strength' => '40mg', 'manufacturer' => 'Sun Pharma', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Omeprazole Capsule 20mg', 'composition' => 'Omeprazole 20mg', 'form' => 'capsule', 'strength' => '20mg', 'manufacturer' => 'Dr Reddy', 'hsn_code' => '30049099', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('indian_drugs')->insert($drugs);

        Log::info('IndianDrugSeeder: Created drugs', ['count' => count($drugs)]);
        $this->command->info('IndianDrugSeeder: created ' . count($drugs) . ' drugs in the catalog.');
    }
}
