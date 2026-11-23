<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ProjectSite;
use Illuminate\Database\Seeder;

class ProjectSiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = [
            [
                'name' => 'Mr. R. Balaji',
                'phone' => '98400 11223',
                'address' => 'No. 5, Mount Poonamallee Road, Porur, Chennai',
                'sites' => [
                    [
                        'name' => 'Site A - Duplex Villa',
                        'location' => 'Porur, Chennai',
                        'work_type' => 'New Build',
                        'start_date' => '2026-01-15',
                        'end_date' => '2026-12-31',
                        'estimated_budget' => 6500000.00,
                        'spent' => 0.00,
                        'status' => 'In Progress',
                    ],
                ],
            ],
            [
                'name' => 'Dr. K. Senthil Kumar',
                'phone' => '94440 55667',
                'address' => 'Plot 12, 4th Cross St, Anna Nagar, Chennai',
                'sites' => [
                    [
                        'name' => 'Site B - Clinic & Residence',
                        'location' => 'Anna Nagar, Chennai',
                        'work_type' => 'Alteration',
                        'start_date' => '2026-03-01',
                        'end_date' => '2026-09-30',
                        'estimated_budget' => 2800000.00,
                        'spent' => 0.00,
                        'status' => 'Planning',
                    ],
                ],
            ],
        ];

        foreach ($clients as $clientData) {
            $sites = $clientData['sites'];
            unset($clientData['sites']);

            $client = Client::firstOrCreate(
                ['phone' => $clientData['phone']],
                $clientData
            );

            foreach ($sites as $siteData) {
                $siteData['client_id'] = $client->id;
                ProjectSite::firstOrCreate(
                    ['name' => $siteData['name']],
                    $siteData
                );
            }
        }
    }
}
