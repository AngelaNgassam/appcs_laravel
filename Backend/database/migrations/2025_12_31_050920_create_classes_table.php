<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('etablissement_id');
            $table->unsignedBigInteger('annee_academique_id');
            $table->string('nom', 100);
            $table->string('niveau', 50);
            $table->string('serie', 50)->nullable();
            $table->integer('effectif')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('etablissement_id')
                  ->references('id')
                  ->on('etablissements')
                  ->onDelete('cascade');

            $table->foreign('annee_academique_id')
                  ->references('id')
                  ->on('annee_academiques')
                  ->onDelete('cascade');

            $table->index('etablissement_id');
            $table->index('annee_academique_id');
            $table->index('niveau');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
