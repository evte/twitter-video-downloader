<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_downloads', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->integer('download_count')->default(0);
            $table->date('date');
            $table->timestamps();
            
            $table->unique(['ip_address', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_downloads');
    }
};