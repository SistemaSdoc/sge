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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('instituicao_id')->nullable();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('bi')->unique()->nullable();
            $table->string('telefone')->nullable();
            $table->string('avatar')->nullable();
            $table->string('google_id')->unique()->nullable();
            $table->string('facebook_id')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('candidatos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('nome');
            $table->string('bi')->unique();
            $table->enum('genero', ['M', 'F'])->nullable();
            $table->string('nacionalidade')->nullable();
            $table->string('naturalidade')->nullable();
            $table->boolean('portador_deficiencia')->default(false);
            $table->string('filiacao')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('numero_estudante')->unique();
            $table->string('telefone')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('morada')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *//*  */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('candidatos');
    }
};
