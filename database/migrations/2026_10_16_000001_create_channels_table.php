<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 30); // youtube | tiktok | instagram | linkedin
            $table->string('handle_name', 100);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'platform', 'handle_name']);
            $table->index('platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
