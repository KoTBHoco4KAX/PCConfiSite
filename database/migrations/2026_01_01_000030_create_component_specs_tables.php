<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_cpu_specs', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('socket')->index();
            $table->unsignedSmallInteger('cores');
            $table->unsignedSmallInteger('performance');
            $table->unsignedSmallInteger('tdp');
            $table->boolean('has_igpu')->default(false);
            $table->timestamps();
        });

        Schema::create('product_gpu_specs', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('vram');
            $table->unsignedSmallInteger('performance');
            $table->unsignedSmallInteger('tdp');
            $table->timestamps();
        });

        Schema::create('product_motherboard_specs', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('socket')->index();
            $table->string('chipset')->nullable();
            $table->string('form_factor');
            $table->string('ram_type');
            $table->timestamps();
        });

        Schema::create('product_ram_specs', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('capacity');
            $table->string('type');
            $table->unsignedSmallInteger('speed');
            $table->timestamps();
        });

        Schema::create('product_storage_specs', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedInteger('capacity');
            $table->timestamps();
        });

        Schema::create('product_case_specs', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('form_factor');
            $table->string('color');
            $table->timestamps();
        });

        Schema::create('product_cooling_specs', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedSmallInteger('tdp');
            $table->timestamps();
        });

        Schema::create('product_psu_specs', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('power');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_psu_specs');
        Schema::dropIfExists('product_cooling_specs');
        Schema::dropIfExists('product_case_specs');
        Schema::dropIfExists('product_storage_specs');
        Schema::dropIfExists('product_ram_specs');
        Schema::dropIfExists('product_motherboard_specs');
        Schema::dropIfExists('product_gpu_specs');
        Schema::dropIfExists('product_cpu_specs');
    }
};