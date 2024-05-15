<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class CreateULLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::connection($this->connection)->hasTable('languages')) {
            Schema::connection($this->connection)->create('languages', function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->string('preference', 30)->nullable()->index();
                $table->string('range')->nullable();
                $table->unique(['preference', 'range']);

                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('languages');
    }
}
