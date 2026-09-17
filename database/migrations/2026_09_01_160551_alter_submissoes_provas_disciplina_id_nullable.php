<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('submissoes_provas', function (Blueprint $table) {
            $table->uuid('disciplina_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('submissoes_provas', function (Blueprint $table) {
            $table->uuid('disciplina_id')->nullable(false)->change();
        });
    }
};