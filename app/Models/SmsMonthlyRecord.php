<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

class SmsMonthlyRecord extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public static function monthlyTableName(string $date): string
    {
        $parsedDate = Carbon::createFromFormat('!Y-m-d', $date);

        if (!$parsedDate) {
            throw new InvalidArgumentException('Invalid date.');
        }

        return strtolower($parsedDate->format('MY'));
    }

    private static function validateTable(string $table): void
    {
        if (!preg_match(
            '/^(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\d{4}$/',
            $table
        )) {
            throw new InvalidArgumentException('Invalid monthly table name.');
        }
    }

    public static function ensureMonthlyTable(string $table): void
    {
        self::validateTable($table);

        if (!Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $blueprint) {
                $blueprint->bigIncrements('id');

                $blueprint->string('recipient')->nullable();
                $blueprint->string('status')->nullable();
                $blueprint->text('description')->nullable();
                $blueprint->string('credit_deducted')->nullable();
                $blueprint->string('sent_time')->nullable();
                $blueprint->string('sender_id')->nullable();
                $blueprint->string('track_id')->nullable();
                $blueprint->string('credit_type')->nullable();
                $blueprint->string('circle')->nullable();
                $blueprint->longText('message')->nullable();
                $blueprint->date('message_date')->nullable();

                $blueprint->string('message_type', 30)->nullable();
                $blueprint->string('card_number', 30)->nullable();
                $blueprint->unsignedInteger('points_added')->nullable();
                $blueprint->unsignedInteger('points_redeemed')->nullable();
                $blueprint->unsignedInteger('total_points')->nullable();
                $blueprint->string('branch', 50)->nullable();
                $blueprint->date('sms_date')->nullable();

                $blueprint->timestamps();
            });

            return;
        }

        // Add missing columns to existing monthly tables.
        $columns = [
            'message_type' => fn ($b) =>
                $b->string('message_type', 30)->nullable(),

            'card_number' => fn ($b) =>
                $b->string('card_number', 30)->nullable(),

            'points_added' => fn ($b) =>
                $b->unsignedInteger('points_added')->nullable(),

            'points_redeemed' => fn ($b) =>
                $b->unsignedInteger('points_redeemed')->nullable(),

            'total_points' => fn ($b) =>
                $b->unsignedInteger('total_points')->nullable(),

            'branch' => fn ($b) =>
                $b->string('branch', 50)->nullable(),

            'sms_date' => fn ($b) =>
                $b->date('sms_date')->nullable(),
        ];

        foreach ($columns as $name => $addColumn) {
            if (!Schema::hasColumn($table, $name)) {
                Schema::table($table, function (Blueprint $blueprint) use ($addColumn) {
                    $addColumn($blueprint);
                });
            }
        }
    }

    /**
     * Parse the SMS into one of four message types.
     */
    public static function parseMessage(?string $message): array
    {
        $message = trim($message ?? '');

        $data = [
            'message_type' => 'other',
            'card_number' => null,
            'points_added' => null,
            'points_redeemed' => null,
            'total_points' => null,
            'branch' => null,
            'sms_date' => null,
        ];

        if ($message === '') {
            return $data;
        }

        // Card number.
        if (preg_match(
            '/CARD\s*NUMBER\s*:\s*(\d+)/i',
            $message,
            $matches
        )) {
            $data['card_number'] = $matches[1];
        }

        // Branch in points messages.
        if (preg_match(
            '/BRANCH\s*:\s*([A-Z]{2,10})/i',
            $message,
            $matches
        )) {
            $data['branch'] = strtoupper($matches[1]);
        } elseif (preg_match(
            '/Greencard\s+([A-Z]{2,10})\s+Redeem\b/i',
            $message,
            $matches
        )) {
            // Branch in OTP messages, such as CBE.
            $data['branch'] = strtoupper($matches[1]);
        }

        // Date mentioned inside the message.
        if (preg_match(
            '/\bDATE\s*:\s*(\d{2}[-\/]\d{2}[-\/]\d{4})/i',
            $message,
            $matches
        )) {
            foreach (['d-m-Y', 'd/m/Y'] as $format) {
                try {
                    $date = Carbon::createFromFormat(
                        '!' . $format,
                        $matches[1]
                    );

                    $errors = Carbon::getLastErrors();

                    if (
                        $date !== false &&
                        (
                            $errors === false ||
                            (
                                $errors['warning_count'] === 0 &&
                                $errors['error_count'] === 0
                            )
                        )
                    ) {
                        $data['sms_date'] = $date->format('Y-m-d');
                        break;
                    }
                } catch (Throwable $e) {
                    // Try the next supported format.
                }
            }
        }

        // Check OTP before checking point values.
        if (preg_match('/\bOTP\b/i', $message)) {
            $data['message_type'] = 'redeem_otp';

            return $data;
        }

        // Points added.
        if (preg_match(
            '/POINTS\s+ADDED\s*:\s*(\d+)/i',
            $message,
            $matches
        )) {
            $data['message_type'] = 'points_added';
            $data['points_added'] = (int) $matches[1];
        }

        // Points redeemed.
        elseif (preg_match(
            '/POINTS\s+REDEEM(?:ED)?\s*:\s*(\d+)/i',
            $message,
            $matches
        )) {
            $data['message_type'] = 'points_redeemed';
            $data['points_redeemed'] = (int) $matches[1];
        }

        // Total points.
        if (preg_match(
            '/TOTAL\s+POINTS\s*:\s*(\d+)/i',
            $message,
            $matches
        )) {
            $data['total_points'] = (int) $matches[1];
        }

        return $data;
    }

    /**
     * Update matching records or insert new records.
     */
    public static function insertMonthlyRow(
        string $table,
        array $data
    ): string {
        self::validateTable($table);
        self::ensureMonthlyTable($table);

        $sentTime = trim((string) ($data['sent_time'] ?? ''));
        $trackId = trim((string) ($data['track_id'] ?? ''));

        if ($sentTime === '' || $trackId === '') {
            throw new InvalidArgumentException(
                'Sent Time and TrackID are required.'
            );
        }

        $data['sent_time'] = $sentTime;
        $data['track_id'] = $trackId;

        unset(
            $data['id'],
            $data['created_at'],
            $data['updated_at']
        );

        return DB::transaction(function () use (
            $table,
            $data,
            $sentTime,
            $trackId
        ) {
            $existing = DB::table($table)
                ->where('sent_time', $sentTime)
                ->where('track_id', $trackId)
                ->orderBy('id', 'asc')
                ->first();

            if ($existing) {
                DB::table($table)
                    ->where('id', $existing->id)
                    ->update(array_merge($data, [
                        'updated_at' => now(),
                    ]));

                return 'updated';
            }

            DB::table($table)->insert(array_merge($data, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            return 'inserted';
        });
    }
}
