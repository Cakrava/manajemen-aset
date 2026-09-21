<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            if (!Schema::hasColumn('devices', 'unit_type')) {
                $table->string('unit_type', 20)->default('pcs')->after('type');
            }
            if (!Schema::hasColumn('devices', 'length_value')) {
                $table->decimal('length_value', 8, 2)->nullable()->after('unit_type');
            }
            if (!Schema::hasColumn('devices', 'length_unit')) {
                $table->string('length_unit', 10)->default('meter')->after('length_value');
            }
            if (!Schema::hasColumn('devices', 'roll_capacity')) {
                $table->integer('roll_capacity')->nullable()->after('length_unit');
            }
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            if (!Schema::hasColumn('transaction_details', 'quantity_meter')) {
                $table->decimal('quantity_meter', 8, 2)->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('transaction_details', 'waste_quantity_meter')) {
                $table->decimal('waste_quantity_meter', 8, 2)->default(0.00)->after('quantity_meter');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['unit_type', 'length_value', 'length_unit', 'roll_capacity']);
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropColumn(['quantity_meter', 'waste_quantity_meter']);
        });
    }
};
