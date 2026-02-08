<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mediafile_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mediafile_id')->constrained('mediafiles')->cascadeOnDelete();
            $table->morphs('attachable');
            $table->string('role', 40)->nullable();
            $table->timestamps();
            $table->unique(
                ['mediafile_id', 'attachable_id', 'attachable_type'],
                'mediafile_attach_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mediafile_attachments');
    }
};
