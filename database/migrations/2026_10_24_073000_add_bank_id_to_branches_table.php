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
        if (Schema::hasTable('branches')) {
            if (! Schema::hasColumn('branches', 'bank_id')) {
                Schema::table('branches', function (Blueprint $table) {
                    $table->foreignId('bank_id')->nullable()->after('id')->constrained('banks')->restrictOnDelete();
                });
            }

            if (Schema::hasColumn('branches', 'bank_name')) {
                $branches = DB::table('branches')->get();
                foreach ($branches as $branch) {
                    if (! empty($branch->bank_name)) {
                        $bankId = DB::table('banks')->where('bank_name', $branch->bank_name)->value('id');
                        if (! $bankId) {
                            $bankId = DB::table('banks')->insertGetId([
                                'bank_name' => $branch->bank_name,
                                'status' => 'Active',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                        DB::table('branches')
                            ->where('id', $branch->id)
                            ->update(['bank_id' => $bankId]);
                    }
                }

                Schema::table('branches', function (Blueprint $table) {
                    $table->dropColumn('bank_name');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('branches')) {
            if (! Schema::hasColumn('branches', 'bank_name')) {
                Schema::table('branches', function (Blueprint $table) {
                    $table->string('bank_name')->nullable()->after('bank_id');
                });

                $items = DB::table('branches')->get();
                foreach ($items as $item) {
                    if ($item->bank_id) {
                        $bankName = DB::table('banks')->where('id', $item->bank_id)->value('bank_name');
                        if ($bankName) {
                            DB::table('branches')
                                ->where('id', $item->id)
                                ->update(['bank_name' => $bankName]);
                        }
                    }
                }
            }

            if (Schema::hasColumn('branches', 'bank_id')) {
                Schema::table('branches', function (Blueprint $table) {
                    $table->dropConstrainedForeignId('bank_id');
                });
            }
        }
    }
};
