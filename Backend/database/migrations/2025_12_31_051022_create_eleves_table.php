<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eleves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('etablissement_id');
            $table->unsignedBigInteger('classe_id');
            $table->string('matricule', 50)->unique();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->date('date_naissance');
            $table->string('lieu_naissance', 150)->nullable();
            $table->enum('sexe', ['M', 'F']);
            $table->string('contact_parent', 20)->nullable();
            $table->string('nom_parent', 150)->nullable();
            $table->boolean('archive')->default(false);
            $table->timestamp('date_archivage')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('etablissement_id')
                  ->references('id')
                  ->on('etablissements')
                  ->onDelete('cascade');

            $table->foreign('classe_id')
                  ->references('id')
                  ->on('classes')
                  ->onDelete('cascade');

            $table->index('etablissement_id');
            $table->index('classe_id');
            $table->index('matricule');
            $table->index('archive');
            $table->index(['nom', 'prenom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eleves');
    }
};
