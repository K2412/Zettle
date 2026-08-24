<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body')->nullable();
            $table->string('note_type')->default('fleeting');
            $table->timestamps();
            $table->index(['user_id', 'title']);
            $table->index('note_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
