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
        Schema::create('normalisasi_kos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kos')->constrained('kos')->onDelete('cascade');
            $table->float('harga_normalized');
            $table->float('rating_normalized');
            $table->float('jarak_normalized');
            $table->json('fasilitas_normalized');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('normalisasi_');
    }
};
