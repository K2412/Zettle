<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('hotkey')->nullable();
            $table->string('title_prefix')->default('');
            $table->text('body_template')->default('');
            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->unique(['user_id', 'hotkey']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_templates');
    }
};
