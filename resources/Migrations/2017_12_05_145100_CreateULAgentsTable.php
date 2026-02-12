<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class CreateULAgentsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('agents')) {
            Schema::connection($this->connection)->create('agents', function (Blueprint $table): void {
                $table->bigIncrements('id');

                $table->text('name')->nullable(); // Better a duplicated name in the db than an error.. -> so no unique key
                $table->string('browser', 255)->nullable()->index();
                $table->string('browser_version', 255)->nullable()->index();
                $table->boolean('is_robot')->default(false);

                $table->timestamp('created_at')->useCurrent();
            });

            DB::connection($this->connection)->statement('alter table `agents` add index `tests_name_index`(name(255))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('agents');
    }
}
