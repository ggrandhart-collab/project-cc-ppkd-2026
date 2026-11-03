<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->text('script_draft')->nullable();
            $table->string('format_type', 30);          // short_video | long_video | carousel | single_post
            $table->string('status', 20)->default('idea'); // idea | scripting | recording | editing | ready | published
            $table->string('priority', 10)->default('medium'); // low | medium | high
            $table->date('scheduled_date')->nullable();
            $table->date('published_date')->nullable();
            $table->string('published_url', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('channel_id');
            $table->index('format_type');
            $table->index('scheduled_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
