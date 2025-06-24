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
        Schema::create('data_user', function (Blueprint $table) {
            $table->id();
            $table->string('nama_user', 255);
            $table->text('alamat');
            $table->string('email', 255)->unique();
            $table->string('username', 100)->unique();
            $table->string('password');
            $table->enum('role', ['Admin', 'Mahasiswa']);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_users');
    }
};
