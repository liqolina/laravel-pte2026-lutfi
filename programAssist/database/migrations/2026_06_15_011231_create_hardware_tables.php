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
        Schema::create('hardware_esp', function (Blueprint $table) {
            $table->id();
            $table->string('id_esp');
            $table->string('name_esp');
            $table->string('topic_publish');
            $table->string('topic_subcribe');
            $table->timestamp('timestamp')->nullable();
        });

        Schema::create('subcriber_mqtt', function (Blueprint $table) {
            $table->id();
            $table->string('id_esp');
            $table->string('topic_subcribe');
            $table->string('message');
            $table->timestamp('timestamp')->nullable();
        });

        Schema::create('publish_mqtt', function (Blueprint $table) {
            $table->id();
            $table->string('id_esp');
            $table->string('topic_publish');
            $table->string('message');
            $table->timestamp('timestamp')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publish_mqtt');
        Schema::dropIfExists('subcriber_mqtt');
        Schema::dropIfExists('hardware_esp');
    }
};