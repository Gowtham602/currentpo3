<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsCredit extends Model
{
    use HasFactory;

    protected $table = 'sms_credits';

    protected $fillable = [
        'user_id',
        'branch_id',
        'date',
        'message',
        'submit_count',
        'credit',
    ];

    protected $casts = [
        'date' => 'date',
        'submit_count' => 'integer',
        'credit' => 'integer',
    ];

    /**
     * Branch Relationship
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}