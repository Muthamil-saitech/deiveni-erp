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
        if (! Schema::hasColumn('warehouse_items', 'unit_id')) {
            Schema::table('warehouse_items', function (Blueprint $table) {
                $table->foreignId('unit_id')->nullable()->after('category')->constrained('units')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('warehouse_items', 'unit')) {
            $units = DB::table('units')->get();
            $unitMap = [];
            foreach ($units as $u) {
                $unitMap[strtolower(trim($u->name_en))] = $u->id;
                $unitMap[strtolower(trim($u->code))] = $u->id;
            }

            $items = DB::table('warehouse_items')->get();
            $defaultUnitId = $units->first()?->id ?? 1;

            foreach ($items as $item) {
                $uStr = strtolower(trim((string) ($item->unit ?? '')));
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

                DB::table('warehouse_items')
                    ->where('id', $item->id)
                    ->update(['unit_id' => $matchedId ?? $defaultUnitId]);
            }

            Schema::table('warehouse_items', function (Blueprint $table) {
                $table->dropColumn('unit');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('warehouse_items', 'unit')) {
            Schema::table('warehouse_items', function (Blueprint $table) {
                $table->string('unit', 50)->nullable()->after('category');
            });

            $items = DB::table('warehouse_items')->get();
            foreach ($items as $item) {
                if ($item->unit_id) {
                    $unitName = DB::table('units')->where('id', $item->unit_id)->value('name_en');
                    DB::table('warehouse_items')->where('id', $item->id)->update(['unit' => $unitName]);
                }
            }
        }

        if (Schema::hasColumn('warehouse_items', 'unit_id')) {
            Schema::table('warehouse_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('unit_id');
            });
        }
    }
};
