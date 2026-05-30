<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hardware_esp', function (Blueprint $table) {
            $table->id();
            $table->string('id_esp')->unique();
            $table->string('name_esp')->nullable();
            $table->string('topic_publish')->nullable()->index();
            $table->string('topic_subscribe')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('publish_mqtt', function (Blueprint $table) {
            $table->id();
            $table->string('id_esp');
            $table->string('topic_publish')->nullable()->index();
            $table->longText('message');
            $table->timestamps();

            $table->foreign('id_esp')
                ->references('id_esp')
                ->on('hardware_esp')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        Schema::create('subscriber_mqtt', function (Blueprint $table) {
            $table->id();
            $table->string('id_esp')->nullable()->index();
            $table->string('topic_subscribe')->index();
            $table->longText('message');
            $table->timestamps();

            $table->foreign('id_esp')
                ->references('id_esp')
                ->on('hardware_esp')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriber_mqtt');
        Schema::dropIfExists('publish_mqtt');
        Schema::dropIfExists('hardware_esp');
    }
};