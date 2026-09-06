<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ide_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('ide_projects')->cascadeOnDelete();
            $table->string('label');
            $table->longText('files_json');
            $table->timestamp('created_at')->nullable();

            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ide_snapshots');
    }
};
