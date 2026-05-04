<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modele_cartes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->onDelete('cascade');
            $table->string('nom_modele');
            $table->string('fichier_template')->nullable();
            $table->string('apercu')->nullable();
            $table->json('configuration')->nullable();
            $table->boolean('actif')->default(true);
            $table->boolean('est_defaut')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modele_cartes');
    }
};
