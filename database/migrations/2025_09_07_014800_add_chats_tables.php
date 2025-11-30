<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sotrudnik_id')->constrained('sotrudniki')->onDelete('cascade');
            $table->string('n8n_session_id')->unique();
            $table->string('status')->default('active'); // active, archived
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['sotrudnik_id', 'status']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['text', 'audio']);
            $table->text('content')->nullable();
            $table->string('audio_file')->nullable();
            $table->enum('sender', ['user', 'bot']);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['chat_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chats');
    }
};
