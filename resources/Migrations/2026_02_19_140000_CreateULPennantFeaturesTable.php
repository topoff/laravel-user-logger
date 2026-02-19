<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class CreateULPennantFeaturesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('pennant_features')) {
            Schema::connection($this->connection)->create('pennant_features', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('scope');
                $table->text('value');
                $table->timestamps();

                $table->unique(['name', 'scope']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('pennant_features');
    }
}
