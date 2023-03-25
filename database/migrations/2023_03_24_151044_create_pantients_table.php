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
        Schema::create('pantients', function (Blueprint $table) {
            $table->id();
            $table->longText('photo');
            $table->string('name');
            $table->string('mon');
            $table->date('birthday');
            $table->string('cpf','14');
            $table->string('cns','15');
            $table->unsignedBigInteger('address_id');
            $table->timestamps();
        });

        Schema::table('pantients', function (Blueprint $table) {
            $table->foreignId('address_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pantients');
    }
};
