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
        Schema::create('gambar_kos', function (Blueprint $table) {
            $table->id();
            $table->string('nama_foto', 255);
            $table->foreignId('id_kos')->constrained('kos')->onDelete('cascade');
            $table->text('link_foto');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gambar_kos');
    }
};
