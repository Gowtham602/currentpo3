<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationService extends Model
{
    protected $table = 'application_services';

    protected $fillable = [
        'application_access_id',
        'service',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(
            ApplicationAccess::class,
            'application_access_id'
        );
    }
}