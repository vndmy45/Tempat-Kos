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
        Schema::create('kos', function (Blueprint $table) {
        $table->id();
        $table->string('nama_kos', 255);
        $table->text('alamat');
        $table->integer('harga');
        $table->enum('jenis_kost', ['Putra', 'Putri', 'Campur']);
        $table->decimal('longitude', 10, 7);
        $table->decimal('latitude', 10, 7);
        $table->float('nilai_rating')->nullable();
        $table->string('kontak_pemilik', 15);
        $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kos');
    }
};
