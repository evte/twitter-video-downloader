<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoDownload extends Model
{
    protected $fillable = [
        'tweet_url',
        'video_url',
        'resolution',
        'status',
        'download_count'
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
} 