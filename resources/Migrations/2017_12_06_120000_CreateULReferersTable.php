<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class CreateULReferersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            if (! Schema::connection($this->connection)->hasTable('referers')) {
                Schema::connection($this->connection)->create('referers', function (Blueprint $table) {
                    $table->bigIncrements('id');

                    $table->bigInteger('domain_id')->unsigned();
                    $table->text('url')->nullable();
                    $table->string('medium', 20)->nullable()->index();
                    $table->string('source', 30)->nullable()->index();
                    $table->string('keywords')->nullable()->index();
                    $table->string('campaign', 70)->nullable()->index();
                    $table->string('adgroup', 70)->nullable()->index();
                    $table->string('matchtype', 6)->nullable()->index();
                    $table->string('device', 7)->nullable()->index();
                    $table->string('adposition', 5)->nullable()->index();
                    $table->string('network', 7)->nullable()->index();

                    $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));

                    $table->foreign('domain_id')->references('id')->on('domains');
                });

                DB::connection($this->connection)->raw('alter table `referers` add index `tests_url_index`(url(255))');
            }
        } catch (Exception $e) {
            dd($e);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('referers');
    }
}
