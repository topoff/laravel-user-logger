<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class CreateULDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create('devices', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('kind', 16)->index()->nullable();
            $table->string('model', 64)->index()->nullable();
            $table->string('platform', 64)->index()->nullable();
            $table->string('platform_version', 16)->index()->nullable();
            $table->boolean('is_mobile')->index()->nullable();
            $table->boolean('is_robot')->index()->nullable();

            $table->unique(['kind', 'model', 'platform', 'platform_version']);

            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('devices');
    }
}
