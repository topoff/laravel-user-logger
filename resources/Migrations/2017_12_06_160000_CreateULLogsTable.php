<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class CreateULLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::connection($this->connection)->hasTable('logs')) {
            Schema::connection($this->connection)->create('logs', function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->uuid('session_id')->index();
                $table->bigInteger('domain_id')->unsigned()->nullable()->index();
                $table->bigInteger('uri_id')->unsigned()->index();
                $table->string('event', 50)->nullable();
                $table->string('entity_type', 60)->nullable();
                $table->string('entity_id', 36)->nullable();

                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));

                $table->foreign('session_id')->references('id')->on('sessions')->onDelete('cascade');
                $table->foreign('domain_id')->references('id')->on('domains');
                $table->foreign('uri_id')->references('id')->on('uris');
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
        Schema::connection($this->connection)->dropIfExists('logs');
    }
}
