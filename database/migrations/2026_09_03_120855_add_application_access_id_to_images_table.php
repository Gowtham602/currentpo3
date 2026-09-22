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
        Schema::table('images', function (Blueprint $table) {

            $table->foreignId('application_access_id')
                ->nullable()
                ->after('user_id')
                ->constrained('application_access')
                ->nullOnDelete();

        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {

            $table->dropForeign([
                'application_access_id'
            ]);

            $table->dropColumn(
                'application_access_id'
            );

        });
    }
};