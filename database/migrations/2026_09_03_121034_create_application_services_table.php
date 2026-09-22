<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_services', function (Blueprint $table) {

            $table->id();

            $table->foreignId('application_access_id')
                ->constrained('application_access')
                ->cascadeOnDelete();

            $table->string('service');

            $table->timestamps();

            $table->unique([
                'application_access_id',
                'service'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_services');
    }
};