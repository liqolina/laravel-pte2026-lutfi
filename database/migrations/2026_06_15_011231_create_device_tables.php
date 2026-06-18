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
        Schema::create('device_esp', function (Blueprint $table) {
            $table->id();
            $table->string('id_esp');
            $table->string('name_esp');
            $table->string('mac_esp');
            $table->string('ip_esp');
            $table->string('loc_esp');
            $table->timestamp('log_time')->nullable();
        });

        Schema::create('device_sensor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_device')->constrained('device_esp')->cascadeOnDelete();
            $table->string('id_sensor');
            $table->string('name_sensor');
            $table->decimal('val_A', 8, 2)->nullable();
            $table->decimal('val_B', 8, 2)->nullable();
            $table->decimal('val_C', 8, 2)->nullable();
            $table->decimal('val_D', 8, 2)->nullable();
            $table->decimal('val_E', 8, 2)->nullable();
            $table->decimal('val_F', 8, 2)->nullable();
            $table->decimal('val_G', 8, 2)->nullable();
            $table->decimal('val_h', 8, 2)->nullable();
            $table->timestamp('timestamp')->nullable();
        });

        Schema::create('device_act', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_device')->constrained('device_esp')->cascadeOnDelete();
            $table->string('id_act');
            $table->string('name_act');
            $table->decimal('val_A', 8, 2)->nullable();
            $table->decimal('val_B', 8, 2)->nullable();
            $table->decimal('val_C', 8, 2)->nullable();
            $table->decimal('val_D', 8, 2)->nullable();
            $table->timestamp('timestamp')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_act');
        Schema::dropIfExists('device_sensor');
        Schema::dropIfExists('device_esp');
    }
};
