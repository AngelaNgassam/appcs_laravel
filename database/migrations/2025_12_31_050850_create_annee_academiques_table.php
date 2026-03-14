<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annee_academiques', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('etablissement_id');
            $table->string('libelle', 20);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->boolean('active')->default(false);
            $table->timestamps();

            $table->foreign('etablissement_id')
                  ->references('id')
                  ->on('etablissements')
                  ->onDelete('cascade');

            $table->index('etablissement_id');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annee_academiques');
    }
};
