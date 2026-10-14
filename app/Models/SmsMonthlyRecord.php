<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class SmsMonthlyRecord extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    /**
     * Convert a validated date to a safe monthly table name.
     * Example: 2026-09-27 => sep2026
     */
    public static function monthlyTableName(string $date): string
    {
        $monthNames = [
            1 => 'jan', 2 => 'feb', 3 => 'mar',
            4 => 'apr', 5 => 'may', 6 => 'jun',
            7 => 'jul', 8 => 'aug', 9 => 'sep',
            10 => 'oct', 11 => 'nov', 12 => 'dec',
        ];

        $parsedDate = \Carbon\Carbon::createFromFormat(
            '!Y-m-d',
            $date
        );

        return $monthNames[(int) $parsedDate->format('n')]
            . $parsedDate->format('Y');
    }

    /**
     * Create the monthly table if needed.
     */
    public static function ensureMonthlyTable(string $table): void
    {
        // Only allow generated names such as sep2026.
        if (!preg_match('/^[a-z]{3}\d{4}$/', $table)) {
            throw new \InvalidArgumentException(
                'Invalid monthly table name.'
            );
        }

        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('recipient')->nullable();
            $table->string('status')->nullable();
            $table->text('description')->nullable();
            $table->string('credit_deducted')->nullable();
            $table->string('sent_time')->nullable();
            $table->string('sender_id')->nullable();
            $table->string('track_id')->nullable();
            $table->string('credit_type')->nullable();
            $table->string('circle')->nullable();
            $table->longText('message')->nullable();

            // Stores the extracted date used to route this row.
            $table->date('message_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Insert a row into its selected monthly table.
     */
    public static function insertMonthlyRow(
        string $table,
        array $data
    ): void {
        DB::table($table)->insert($data);
    }
}
