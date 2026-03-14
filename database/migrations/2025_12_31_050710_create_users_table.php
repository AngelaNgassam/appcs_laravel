<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('email', 191)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'proviseur', 'surveillant', 'operateur'])->default('proviseur');
            $table->string('telephone', 20)->nullable();
            $table->unsignedBigInteger('etablissement_id')->nullable();
            $table->unsignedBigInteger('cree_par')->nullable();
            $table->boolean('actif')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('etablissement_id')
                  ->references('id')
                  ->on('etablissements')
                  ->onDelete('cascade');

            $table->foreign('cree_par')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->index('email');
            $table->index('role');
            $table->index('etablissement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
