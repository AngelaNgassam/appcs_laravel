<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('eleve_id');
            $table->unsignedBigInteger('operateur_id');
            $table->string('chemin_photo', 191);
            $table->string('photo_originale', 191)->nullable();
            $table->string('photo_traitee', 191)->nullable();
            $table->enum('statut', ['brouillon', 'validee', 'refusee', 'archivee'])->default('validee');
            $table->text('motif_refus')->nullable();
            $table->timestamp('date_prise');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('eleve_id')
                  ->references('id')
                  ->on('eleves')
                  ->onDelete('cascade');

            $table->foreign('operateur_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->index('eleve_id');
            $table->index('operateur_id');
            $table->index('statut');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
