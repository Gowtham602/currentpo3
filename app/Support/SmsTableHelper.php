<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use InvalidArgumentException;

class SmsTableHelper
{
    public static function getTableName($sentTime): string
    {
        return 'sms_logs_' . strtolower(
            Carbon::parse($sentTime)->format('MY')
        );
    }

    public static function createMonthlyTable(string $table): void
    {
        if (!preg_match('/^sms_logs_[a-z]{3}[0-9]{4}$/', $table)) {
            throw new InvalidArgumentException('Invalid table name');
        }

        DB::statement("
            CREATE TABLE IF NOT EXISTS `$table` (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                status VARCHAR(50) NULL,
                description VARCHAR(255) NULL,
                credit_deducted INT NOT NULL DEFAULT 0,
                sent_time DATETIME NOT NULL,
                sender_id VARCHAR(100) NULL,
                track_id VARCHAR(150) NOT NULL,
                credit_type VARCHAR(100) NULL,
                circle VARCHAR(100) NULL,
                message LONGTEXT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,

                PRIMARY KEY (id),

                UNIQUE KEY unique_track_sent (track_id, sent_time),

                KEY idx_sent_time (sent_time),
                KEY idx_status (status),
                KEY idx_circle (circle)

            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
}
