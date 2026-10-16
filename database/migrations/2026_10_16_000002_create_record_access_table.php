<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extra people (beyond the one team lead) who can read someone's reviews and feedback.
        Schema::create('record_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason')->nullable(); // e.g. "AU account lead", "ID line manager"
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['person_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_access');
    }
};
