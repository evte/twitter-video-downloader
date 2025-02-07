<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpDownload extends Model
{
    protected $fillable = [
        'ip_address',
        'download_count',
        'date'
    ];

    protected $casts = [
        'date' => 'date'
    ];
} 