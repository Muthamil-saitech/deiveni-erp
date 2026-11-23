<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\PropertyType;
use Illuminate\Database\Seeder;

class PropertyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $propertyTypes = [
            [
                'property_name' => 'Medavakkam Commercial Complex',
                'shop_home_office_name' => 'Shop 1 (வணிகக் கடை 1)',
                'property_type' => 'Commercial Shop',
                'floor_type' => 'Ground Floor (GF)',
                'location' => 'Medavakkam Main Road, Chennai - 100',
                'tenant_name' => 'M/s Royal Travels',
                'tenant_phone' => '+91 98410 11223',
                'monthly_rent' => 7898.00,
                'security_deposit' => 80000.00,
                'rent_due_day' => '5th of Month',
                'status' => 'Active',
            ],
            [
                'property_name' => 'Medavakkam Commercial Complex',
                'shop_home_office_name' => 'Shop 2 (வணிகக் கடை 2)',
                'property_type' => 'Commercial Shop',
                'floor_type' => 'Ground Floor (GF)',
                'location' => 'Medavakkam Main Road, Chennai - 100',
                'tenant_name' => 'Sri Balaji Enterprises',
                'tenant_phone' => '+91 98411 22334',
                'monthly_rent' => 12500.00,
                'security_deposit' => 120000.00,
                'rent_due_day' => '1st of Month',
                'status' => 'Active',
            ],
            [
                'property_name' => 'Pallikaranai Residential Complex',
                'shop_home_office_name' => 'Flat 2B (குடியிருப்பு 2B)',
                'property_type' => 'Residential Flat',
                'floor_type' => 'First Floor (FF)',
                'location' => '2nd Floor, Sai Nagar, Pallikaranai',
                'tenant_name' => 'Mr. K. Suresh',
                'tenant_phone' => '+91 98412 33445',
                'monthly_rent' => 16000.00,
                'security_deposit' => 100000.00,
                'rent_due_day' => '10th of Month',
                'status' => 'Active',
            ],
            [
                'property_name' => 'Velachery Commercial G+1',
                'shop_home_office_name' => 'Unit 4 (அலுவலக தளம்)',
                'property_type' => 'Office Space',
                'floor_type' => 'First Floor (FF)',
                'location' => '1st Floor, Bypass Road, Velachery',
                'tenant_name' => 'Velan Tech Solutions',
                'tenant_phone' => '+91 98413 44556',
                'monthly_rent' => 25000.00,
                'security_deposit' => 200000.00,
                'rent_due_day' => '1st of Month',
                'status' => 'Active',
            ],
            [
                'property_name' => 'Tambaram Warehouse Space',
                'shop_home_office_name' => 'Shed 3 (கிடங்கு ஷெட் 3)',
                'property_type' => 'Warehouse / Shed',
                'floor_type' => 'Ground Floor (GF)',
                'location' => 'Industrial Estate, Tambaram West',
                'tenant_name' => 'M/s Express Logistics',
                'tenant_phone' => '+91 98414 55667',
                'monthly_rent' => 25000.00,
                'security_deposit' => 150000.00,
                'rent_due_day' => '7th of Month',
                'status' => 'Active',
            ],
            [
                'property_name' => 'GST Road Showroom Plaza',
                'shop_home_office_name' => 'Ground Floor Showroom (ஷோரூம் தளம்)',
                'property_type' => 'Commercial Shop',
                'floor_type' => 'Ground Floor (GF)',
                'location' => 'Corner Plot, GST Road, Chromepet',
                'tenant_name' => 'Vacant (Looking for Tenant)',
                'tenant_phone' => '—',
                'monthly_rent' => 35000.00,
                'security_deposit' => 300000.00,
                'rent_due_day' => '1st of Month',
                'status' => 'Inactive',
            ],
        ];

        foreach ($propertyTypes as $item) {
            $propName = $item['property_name'];
            $propertyId = Property::where('property_name', $propName)->value('id');

            unset($item['property_name']);
            $item['property_id'] = $propertyId;

            if ($propertyId) {
                PropertyType::updateOrCreate(
                    [
                        'property_id' => $propertyId,
                        'shop_home_office_name' => $item['shop_home_office_name'],
                    ],
                    $item
                );
            }
        }
    }
}
