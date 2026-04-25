<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_esp', function (Blueprint $table) {
            $table->id();
            $table->string('id_esp')->unique();
            $table->string('name_esp');

            $table->macAddress('mac_esp')->unique();
            $table->ipAddress('ip_esp')->nullable();
            $table->string('loc_esp')->nullable();

            $table->timestamp('timestamp')->nullable();
            $table->timestamps();
        });

        Schema::create('device_sensor', function (Blueprint $table) {
            $table->id();
            $table->string('id_esp');
            $table->string('id_sensor');
            $table->string('name_sensor');

            $table->decimal('val_A', 12, 2)->nullable();
            $table->decimal('val_B', 12, 2)->nullable();
            $table->decimal('val_C', 12, 2)->nullable();
            $table->decimal('val_D', 12, 2)->nullable();
            $table->decimal('val_E', 12, 2)->nullable();
            $table->decimal('val_F', 12, 2)->nullable();
            $table->decimal('val_G', 12, 2)->nullable();
            $table->decimal('val_H', 12, 2)->nullable();

            $table->timestamp('timestamp')->nullable();
            $table->timestamps();

            $table->foreign('id_esp')
                ->references('id_esp')
                ->on('device_esp')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->index(['id_esp', 'id_sensor']);
        });

        Schema::create('device_act', function (Blueprint $table) {
            $table->id();
            $table->string('id_esp');
            $table->string('id_act');
            $table->string('name_act');

            $table->decimal('val_A', 12, 2)->nullable();
            $table->decimal('val_B', 12, 2)->nullable();
            $table->decimal('val_C', 12, 2)->nullable();
            $table->decimal('val_D', 12, 2)->nullable();

            $table->timestamp('timestamp')->nullable();
            $table->timestamps();

            $table->foreign('id_esp')
                ->references('id_esp')
                ->on('device_esp')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->index(['id_esp', 'id_act']);
        });

        Schema::create('status_news', function (Blueprint $table) {
            $table->id();
            $table->string('id_esp');
            $table->string('status_esp');
            $table->string('news_esp')->nullable();
            $table->timestamp('timestamp')->nullable();
            $table->timestamps();

            $table->foreign('id_esp')
                ->references('id_esp')
                ->on('device_esp')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->index(['id_esp', 'timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_news');
        Schema::dropIfExists('device_act');
        Schema::dropIfExists('device_sensor');
        Schema::dropIfExists('device_esp');
    }
};

// kemudian untuk status ONLINE dan OFFLINE dengan cara membandingkan waktu 10 detik setelah waktu timestamp di database status_news