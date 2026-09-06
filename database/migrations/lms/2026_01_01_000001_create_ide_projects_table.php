<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ide_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('name');
            $table->string('slug');
            $table->enum('kind', ['web', 'python']);
            $table->string('package_set')->nullable();
            $table->string('entry_file');
            $table->unsignedInteger('file_count')->default(0);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('student_id');
            $table->unique(['student_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ide_projects');
    }
};
