<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'bank_name' => 'State Bank of India (SBI)',
                'branch_name' => 'Valasaravakkam',
                'branch_name_ta' => 'பாரத ஸ்டேட் வங்கி (வளசரவாக்கம்)',
                'ifsc_code' => 'SBIN0001234',
                'contact_person' => 'Mr. S. Ramanathan (Chief Manager)',
                'phone' => '+91 98401 23456',
                'valuation_fee' => 7500.00,
                'status' => 'Active',
            ],
            [
                'bank_name' => 'HDFC Bank',
                'branch_name' => 'Porur Retail Asset',
                'branch_name_ta' => 'ஹெச்டிஎஃப்சி வங்கி (போரூர்)',
                'ifsc_code' => 'HDFC0001892',
                'contact_person' => 'Mr. K. Venkatesh (Loan Officer)',
                'phone' => '+91 98403 45678',
                'valuation_fee' => 6500.00,
                'status' => 'Active',
            ],
            [
                'bank_name' => 'Indian Bank',
                'branch_name' => 'Medavakkam',
                'branch_name_ta' => 'இந்தியன் வங்கி (மேடவாக்கம்)',
                'ifsc_code' => 'IDIB000M128',
                'contact_person' => 'Mr. G. Sundar (Branch Manager)',
                'phone' => '+91 98404 56789',
                'valuation_fee' => 5000.00,
                'status' => 'Active',
            ],
            [
                'bank_name' => 'Canara Bank',
                'branch_name' => 'Tambaram West',
                'branch_name_ta' => 'கனரா வங்கி (தாம்பரம்)',
                'ifsc_code' => 'CNRB0002041',
                'contact_person' => 'Mr. R. Anand (Valuation Head)',
                'phone' => '+91 98405 67890',
                'valuation_fee' => 6000.00,
                'status' => 'Active',
            ],
            [
                'bank_name' => 'ICICI Bank',
                'branch_name' => 'Anna Nagar',
                'branch_name_ta' => 'ஐசிஐசிஐ வங்கி (அண்ணா நகர்)',
                'ifsc_code' => 'ICIC0000184',
                'contact_person' => 'Ms. Deepa (Credit Manager)',
                'phone' => '+91 98406 78901',
                'valuation_fee' => 8500.00,
                'status' => 'Active',
            ],
            [
                'bank_name' => 'State Bank of India (SBI)',
                'branch_name' => 'Guindy SME',
                'branch_name_ta' => 'பாரத ஸ்டேட் வங்கி (கிண்டி)',
                'ifsc_code' => 'SBIN0004567',
                'contact_person' => 'Mrs. Priya (SME Relationship)',
                'phone' => '+91 98402 34567',
                'valuation_fee' => 8000.00,
                'status' => 'Active',
            ],
            [
                'bank_name' => 'Axis Bank',
                'branch_name' => 'T. Nagar',
                'branch_name_ta' => 'ஆக்சிஸ் வங்கி (தி. நகர்)',
                'ifsc_code' => 'UTIB0000088',
                'contact_person' => 'Mr. M. Dinesh (Branch Operations)',
                'phone' => '+91 98407 89012',
                'valuation_fee' => 7000.00,
                'status' => 'Inactive',
            ],
        ];

        foreach ($branches as $branchData) {
            $bankName = $branchData['bank_name'];
            unset($branchData['bank_name']);

            $bank = Bank::firstOrCreate(
                ['bank_name' => $bankName],
                ['status' => 'Active']
            );
            $branchData['bank_id'] = $bank->id;

            Branch::updateOrCreate(
                ['ifsc_code' => $branchData['ifsc_code']],
                $branchData
            );
        }
    }
}
