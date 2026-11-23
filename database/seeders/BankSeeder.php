<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            [
                'bank_name' => 'State Bank of India (SBI)',
                'bank_name_ta' => 'பாரத ஸ்டேட் வங்கி',
                'status' => 'Active',
            ],
            [
                'bank_name' => 'HDFC Bank',
                'bank_name_ta' => 'ஹெச்டிஎஃப்சி வங்கி',
                'status' => 'Active',
            ],
            [
                'bank_name' => 'Indian Bank',
                'bank_name_ta' => 'இந்தியன் வங்கி',
                'status' => 'Active',
            ],
            [
                'bank_name' => 'Canara Bank',
                'bank_name_ta' => 'கனரா வங்கி',
                'status' => 'Active',
            ],
            [
                'bank_name' => 'ICICI Bank',
                'bank_name_ta' => 'ஐசிஐசிஐ வங்கி',
                'status' => 'Active',
            ],
            [
                'bank_name' => 'Axis Bank',
                'bank_name_ta' => 'ஆக்சிஸ் வங்கி',
                'status' => 'Active',
            ],
            [
                'bank_name' => 'Punjab National Bank',
                'bank_name_ta' => 'பஞ்சாப் நேஷனல் வங்கி',
                'status' => 'Active',
            ],
            [
                'bank_name' => 'Bank of Baroda',
                'bank_name_ta' => 'பாங்க் ஆஃப் பரோடா',
                'status' => 'Active',
            ],
            [
                'bank_name' => 'Kotak Mahindra Bank',
                'bank_name_ta' => 'கோடக் மஹிந்திரா வங்கி',
                'status' => 'Inactive',
            ],
        ];

        foreach ($banks as $bankData) {
            Bank::updateOrCreate(
                ['bank_name' => $bankData['bank_name']],
                $bankData
            );
        }
    }
}
