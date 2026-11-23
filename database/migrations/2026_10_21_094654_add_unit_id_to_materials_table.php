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
        if (! Schema::hasColumn('materials', 'unit_id')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->foreignId('unit_id')->nullable()->after('name_ta')->constrained('units')->restrictOnDelete();
            });
        }

        if (Schema::hasColumn('materials', 'unit')) {
            $units = DB::table('units')->get();
            $unitMap = [];
            foreach ($units as $u) {
                $unitMap[strtolower($u->name_en)] = $u->id;
                $unitMap[strtolower($u->code)] = $u->id;
            }
            $unitMap['bag'] = 1;
            $unitMap['bags'] = 1;
            $unitMap['kg'] = 2;
            $unitMap['kilo'] = 2;
            $unitMap['kilogram'] = 2;
            $unitMap['kilograms'] = 2;
            $unitMap['litre'] = 3;
            $unitMap['litres'] = 3;
            $unitMap['ltr'] = 3;
            $unitMap['load'] = 4;
            $unitMap['brass'] = 5;
            $unitMap['nos'] = 6;
            $unitMap['piece'] = 6;
            $unitMap['pieces'] = 6;
            $unitMap['ton'] = 7;
            $unitMap['sqft'] = 8;
            $unitMap['sq.ft'] = 8;
            $unitMap['feet'] = 8;
            $unitMap['rft'] = 9;
            $unitMap['day'] = 10;
            $unitMap['shift'] = 10;

            $materials = DB::table('materials')->get();
            $defaultUnitId = $units->first()?->id ?? 1;

            foreach ($materials as $m) {
                $uStr = strtolower(trim((string) ($m->unit ?? '')));
                $matchedId = null;

                if (isset($unitMap[$uStr])) {
                    $matchedId = $unitMap[$uStr];
                } else {
                    foreach ($unitMap as $key => $id) {
                        if ($key !== '' && (str_contains($uStr, $key) || str_contains($key, $uStr))) {
                            $matchedId = $id;
                            break;
                        }
                    }
                }

                DB::table('materials')
                    ->where('id', $m->id)
                    ->update(['unit_id' => $matchedId ?? $defaultUnitId]);
            }

            Schema::table('materials', function (Blueprint $table) {
                $table->dropColumn('unit');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('materials', 'unit')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->string('unit')->nullable()->after('name_ta');
            });

            $materials = DB::table('materials')->get();
            foreach ($materials as $m) {
                if ($m->unit_id) {
                    $unitName = DB::table('units')->where('id', $m->unit_id)->value('name_en');
                    DB::table('materials')->where('id', $m->id)->update(['unit' => $unitName]);
                }
            }
        }

        if (Schema::hasColumn('materials', 'unit_id')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->dropConstrainedForeignId('unit_id');
            });
        }
    }
};
