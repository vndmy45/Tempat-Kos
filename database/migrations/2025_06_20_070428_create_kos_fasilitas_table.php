<?php

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
        Schema::create('kos_fasilitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kos')->constrained('kos')->onDelete('cascade');
            $table->foreignId('id_fasilitas')->constrained('fasilitas')->onDelete('cascade');
            $table->integer('nilai_fasilitas');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kos_fasilitas');
    }
};
