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
        Schema::create('history_votes', function (Blueprint $table) {
            $table->id();
            $table->string('star_name');
            $table->bigInteger('total_votes_received');
            $table->integer('total_nominations');
            $table->integer('total_won');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_votes');
    }
};
