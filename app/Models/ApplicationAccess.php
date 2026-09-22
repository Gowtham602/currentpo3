<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationAccess extends Model
{
    protected $table = 'application_access';

    protected $fillable = [
        'api_token',
        'application_name',
        'allowed_cors_domains', 
        'status',
    ];

    protected $hidden = [
        'api_token',
    ];

    protected $casts = [
        'status' => 'boolean',
        
    ];

    public function services(): HasMany
    {
        return $this->hasMany(
            ApplicationService::class
        );
    }
}