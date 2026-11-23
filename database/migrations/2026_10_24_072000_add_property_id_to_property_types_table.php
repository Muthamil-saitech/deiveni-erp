<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('property_types')) {
            if (! Schema::hasColumn('property_types', 'property_id')) {
                Schema::table('property_types', function (Blueprint $table) {
                    $table->foreignId('property_id')->nullable()->after('id')->constrained('properties')->restrictOnDelete();
                });
            }

            if (Schema::hasColumn('property_types', 'property_name')) {
                $properties = DB::table('properties')->get();
                foreach ($properties as $prop) {
                    DB::table('property_types')
                        ->where('property_name', $prop->property_name)
                        ->update(['property_id' => $prop->id]);
                }

                Schema::table('property_types', function (Blueprint $table) {
                    $table->dropColumn('property_name');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('property_types')) {
            if (! Schema::hasColumn('property_types', 'property_name')) {
                Schema::table('property_types', function (Blueprint $table) {
                    $table->string('property_name')->nullable()->after('property_id');
                });

                $items = DB::table('property_types')->get();
                foreach ($items as $item) {
                    if ($item->property_id) {
                        $propName = DB::table('properties')->where('id', $item->property_id)->value('property_name');
                        if ($propName) {
                            DB::table('property_types')
                                ->where('id', $item->id)
                                ->update(['property_name' => $propName]);
                        }
                    }
                }
            }

            if (Schema::hasColumn('property_types', 'property_id')) {
                Schema::table('property_types', function (Blueprint $table) {
                    $table->dropConstrainedForeignId('property_id');
                });
            }
        }
    }
};
