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
        Schema::create('all_tables', function (Blueprint $table) {
            $table->id();
            $table->string('poll_title');
            $table->string('table_name_starts_with')->unique();
            $table->text('before_poll_description')->nullable();
            $table->text('after_poll_description')->nullable();
            $table->string('which_industry')->nullable();
            $table->string('starting_date');
            $table->string('ending_date');
            $table->string('winner_added');
            $table->string('winners_name');
            $table->string('winners_votes');
            $table->string('total_votes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('all_tables');
    }
};
