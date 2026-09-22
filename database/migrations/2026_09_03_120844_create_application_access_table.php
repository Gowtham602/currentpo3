<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_access', function (Blueprint $table) {

            $table->id();

            // Public application identifier
            $table->uuid('api_token')->unique();

            // Application name
            $table->string('application_name');

            // Store HASHED token, not plain token
           

            // Comma separated domains
            $table->text('allowed_cors_domains')->nullable();

          

            // 1 = active, 0 = disabled
            $table->boolean('status')
                ->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_access');
    }
};