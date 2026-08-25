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
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('provisioning_target_status')->nullable()->after('status');
            $table->unsignedInteger('provisioning_attempts')->default(0)->after('provisioning_target_status');
            $table->text('provisioning_error')->nullable()->after('provisioning_attempts');
            $table->dateTime('provisioning_started_at')->nullable()->after('provisioning_error');
            $table->dateTime('provisioning_finished_at')->nullable()->after('provisioning_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'provisioning_target_status',
                'provisioning_attempts',
                'provisioning_error',
                'provisioning_started_at',
                'provisioning_finished_at',
            ]);
        });
    }
};
