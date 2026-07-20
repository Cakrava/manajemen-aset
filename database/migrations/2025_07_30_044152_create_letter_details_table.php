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
        Schema::create('letter_details', function (Blueprint $table) {
                $table->id();
                $table->string('letter_id');
                $table->unsignedBigInteger('stored_device_id');
                $table->integer('quantity');
       $table->boolean('status')->default(0);
       $table->boolean('withdrawcondition')->default(0);

                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_details');
    }
};
