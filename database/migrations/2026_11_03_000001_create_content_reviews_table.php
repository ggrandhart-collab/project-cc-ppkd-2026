<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Riwayat keputusan editor pada tahap "editing" (setuju / minta revisi).
        Schema::create('content_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('decision', 20); // approved | revision_requested
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['content_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reviews');
    }
};
