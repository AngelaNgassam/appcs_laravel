<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etablissements', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 191);
            $table->text('adresse');
            $table->string('telephone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('logo', 191)->nullable();
            $table->string('ville', 100);
            $table->unsignedBigInteger('proviseur_id')->unique()->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('ville');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etablissements');
    }
};
