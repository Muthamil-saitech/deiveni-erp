<?php

namespace Database\Seeders;

use App\Models\Property;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $properties = [
            [
                'property_name' => 'Medavakkam Commercial Complex',
                'property_name_ta' => 'மேடவாக்கம் வணிக வளாகம்',
                'status' => 'Active',
            ],
            [
                'property_name' => 'Pallikaranai Residential Complex',
                'property_name_ta' => 'பள்ளிக்கரணை குடியிருப்பு வளாகம்',
                'status' => 'Active',
            ],
            [
                'property_name' => 'Velachery Commercial G+1',
                'property_name_ta' => 'வேளச்சேரி வணிக வளாகம் G+1',
                'status' => 'Active',
            ],
            [
                'property_name' => 'Tambaram Warehouse Space',
                'property_name_ta' => 'தாம்பரம் கிடங்கு இடம்',
                'status' => 'Active',
            ],
            [
                'property_name' => 'GST Road Showroom Plaza',
                'property_name_ta' => 'ஜிஎஸ்டி ரோடு ஷோரூம் பிளாசா',
                'status' => 'Active',
            ],
            [
                'property_name' => 'Perungudi Tech Hub',
                'property_name_ta' => 'பெருங்குடி டெக் ஹப்',
                'status' => 'Active',
            ],
            [
                'property_name' => 'Anna Nagar Commercial Tower',
                'property_name_ta' => 'அண்ணா நகர் வணிக கோபுரம்',
                'status' => 'Active',
            ],
            [
                'property_name' => 'Guindy Industrial Estate',
                'property_name_ta' => 'கிண்டி தொழிற்பேட்டை',
                'status' => 'Active',
            ],
            [
                'property_name' => 'Tambaram South Bypass Shed',
                'property_name_ta' => 'தாம்பரம் தெற்கு பைபாஸ் கொட்டகை',
                'status' => 'Inactive',
            ],
        ];

        foreach ($properties as $property) {
            Property::updateOrCreate(
                ['property_name' => $property['property_name']],
                $property
            );
        }
    }
}
