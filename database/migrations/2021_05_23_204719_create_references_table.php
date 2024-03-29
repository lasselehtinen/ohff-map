<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('references', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('reference', 10)->unique();
            $table->enum('status', ['active', 'deleted', 'national', 'proposed']);
            $table->string('name')->nullable();
            $table->date('first_activation_date')->nullable();
            $table->date('latest_activation_date')->nullable();
            $table->point('location')->nullable();
            $table->geometry('area')->nullable();
            $table->string('iota_reference', 6)->nullable();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->foreign('program_id')->references('id')->on('programs');
            $table->unsignedBigInteger('dxcc_id')->nullable();
            $table->foreign('dxcc_id')->references('id')->on('dxccs');
            $table->unsignedBigInteger('continent_id')->nullable();
            $table->foreign('continent_id')->references('id')->on('dxccs');
            $table->unsignedBigInteger('wdpa_id')->nullable();
            $table->boolean('natura_2000_area')->default(false);
            $table->enum('approval_status', ['received', 'declined', 'approved', 'saved'])->default('received');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('references');
    }
}
