<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ide_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('ide_projects')->cascadeOnDelete();
            $table->string('path');
            $table->mediumText('content')->nullable();
            $table->string('blob_path')->nullable();
            $table->boolean('is_binary')->default(false);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ide_files');
    }
};
