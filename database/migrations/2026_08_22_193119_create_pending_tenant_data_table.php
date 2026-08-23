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
        Schema::create('pending_tenant_data', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('nome');
            $table->string('sigla');
            $table->string('tipo');
            $table->boolean('status')->default(true);
            $table->string('user_nome');
            $table->string('user_email');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_tenant_data');
    }
};
