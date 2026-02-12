<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class CreateULUrisTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('uris')) {
            Schema::connection($this->connection)->create('uris', function (Blueprint $table): void {
                $table->bigIncrements('id');

                $table->string('uri')->unique()->index();

                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('uris');
    }
}
