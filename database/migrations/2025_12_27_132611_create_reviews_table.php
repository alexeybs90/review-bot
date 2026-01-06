<?php

use App\Models\Chat;
use App\Models\Company;
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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Chat::class, 'chat_id')
                ->index('reviews_chat_id_idx')->constrained()->onDelete('cascade');
            $table->foreignIdFor(Company::class, 'company_id')
                ->index('reviews_company_id_idx')->constrained()->onDelete('cascade');
            $table->integer('grade');
            $table->text('comment');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
