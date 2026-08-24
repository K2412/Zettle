<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_note_id')->constrained('notes')->cascadeOnDelete();
            $table->foreignId('target_note_id')->constrained('notes')->cascadeOnDelete();
            $table->string('relationship');
            $table->text('rationale')->nullable();
            $table->timestamps();

            $table->unique(['source_note_id', 'target_note_id', 'relationship']);
            $table->index('source_note_id');
            $table->index('target_note_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connections');
    }
};
