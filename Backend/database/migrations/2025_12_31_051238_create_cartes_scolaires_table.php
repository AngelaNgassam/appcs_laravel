<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartes_scolaires', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('eleve_id');
            $table->unsignedBigInteger('photo_id');
            $table->unsignedBigInteger('modele_id')->nullable();
            $table->text('qr_code')->nullable();
            $table->string('chemin_pdf', 191)->nullable();
            $table->enum('statut', ['en_attente', 'generee', 'imprimee', 'distribuee'])->default('en_attente');
            $table->timestamp('date_generation')->nullable();
            $table->timestamp('date_impression')->nullable();
            $table->timestamp('date_distribution')->nullable();
            $table->unsignedBigInteger('imprimee_par')->nullable();
            $table->integer('nombre_impressions')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('eleve_id')
                  ->references('id')
                  ->on('eleves')
                  ->onDelete('cascade');

            $table->foreign('photo_id')
                  ->references('id')
                  ->on('photos')
                  ->onDelete('cascade');

            // $table->foreign('modele_id')
            //       ->references('id')
            //       ->on('modeles_cartes')
            //       ->onDelete('set null');

            $table->foreign('imprimee_par')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->index('eleve_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartes_scolaires');
    }
};
