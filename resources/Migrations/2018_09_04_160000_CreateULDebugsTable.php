<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class CreateULDebugsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('debugs')) {
            Schema::connection($this->connection)->create('debugs', function (Blueprint $table): void {
                $table->bigIncrements('id');

                $table->string('kind', 20);
                $table->text('value')->nullable();

                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('debugs');
    }
}
