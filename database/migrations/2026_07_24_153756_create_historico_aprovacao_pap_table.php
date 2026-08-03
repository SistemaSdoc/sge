<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('historico_aprovacao_pap', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('grupo_pap_id')
                ->constrained('grupo_pap')
                ->cascadeOnDelete();

            $table->foreignUuid('utilizador_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('tema');
            $table->text('problema')->nullable();     
            $table->text('objectivos')->nullable();   

            $table->string('estado_anterior')->nullable();

            $table->string('estado_novo');

            $table->text('comentario')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_aprovacao_pap');
    }
};
