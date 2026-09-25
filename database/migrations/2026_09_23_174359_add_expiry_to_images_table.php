<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {

            $table->date('expires_at')
                ->nullable()
                ->after('short_code');

            $table->boolean('is_active')
                ->default(true)
                ->after('expires_at');

        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {

            $table->dropColumn([
                'expires_at',
                'is_active'
            ]);

        });
    }
};

