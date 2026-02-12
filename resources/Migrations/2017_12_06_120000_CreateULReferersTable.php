<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class CreateULReferersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('referers')) {
            Schema::connection($this->connection)->create('referers', function (Blueprint $table): void {
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

                $table->timestamp('created_at')->useCurrent();

                $table->foreign('domain_id')->references('id')->on('domains');
            });

            DB::connection($this->connection)->statement('alter table `referers` add index `tests_url_index`(url(255))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('referers');
    }
}
