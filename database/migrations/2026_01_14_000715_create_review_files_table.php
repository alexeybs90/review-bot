<?php

use App\Models\Review;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('review_files', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Review::class, 'review_id')
                ->index('review_files_review_id_idx')->constrained()->onDelete('cascade');
            $table->string('file_id');
            $table->string('file_unique_id');
            $table->string('path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_files');
    }
};
