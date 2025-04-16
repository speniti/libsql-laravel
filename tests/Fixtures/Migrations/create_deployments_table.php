<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('environment_id');
            $table->string('commit_hash');
            $table->timestamps();

            $table->foreign('environment_id')
                ->references('id')
                ->on('environments')
                ->onDelete('cascade');
        });
    }
};
