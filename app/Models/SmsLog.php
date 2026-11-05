<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $table = 'sms_logs_jul2026';

    protected $guarded = [];

    public $timestamps = true;

    public function setMonthlyTable(string $table): self
    {
        if (!preg_match('/^sms_logs_[a-z]{3}[0-9]{4}$/', $table)) {
            throw new \InvalidArgumentException(
                'Invalid SMS table name'
            );
        }

        $this->setTable($table);

        return $this;
    }
}
