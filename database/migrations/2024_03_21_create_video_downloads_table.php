<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_downloads', function (Blueprint $table) {
            $table->id();
            $table->string('tweet_url');
            $table->string('video_url');
            $table->string('resolution');
            $table->string('status')->default('pending');
            $table->integer('download_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_downloads');
    }
}; 